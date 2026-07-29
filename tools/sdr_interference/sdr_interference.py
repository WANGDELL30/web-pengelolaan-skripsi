#!/usr/bin/env python3
"""
Measure RF activity around a Wi-Fi HaLow channel using rtl_power.

The tool never transmits RF. It records power-spectrum sweeps, estimates a
local noise floor from guard bands, measures channel occupancy, and produces
machine-readable and human-readable evidence.
"""

from __future__ import annotations

import argparse
import csv
import hashlib
import html
import json
import math
import random
import re
import shutil
import statistics
import subprocess
import sys
from collections import defaultdict
from dataclasses import asdict, dataclass
from datetime import datetime, timedelta
from pathlib import Path
from typing import Iterable, Sequence


DEFAULT_CENTER_MHZ = 923.5
DEFAULT_CHANNEL_BW_KHZ = 1000.0
DEFAULT_SPAN_MHZ = 4.0
DEFAULT_BIN_KHZ = 10.0


@dataclass(frozen=True)
class SpectrumBin:
    frequency_hz: float
    power_dbm: float


@dataclass
class Sweep:
    timestamp_text: str
    timestamp: datetime | None
    bins: list[SpectrumBin]


@dataclass(frozen=True)
class AnalysisSettings:
    center_mhz: float
    channel_bw_khz: float
    threshold_db: float
    max_occupancy_percent: float
    max_busy_sweep_percent: float
    min_event_bins: int
    calibration_offset_db: float
    halow_state: str


def percentile(values: Sequence[float], percent: float) -> float:
    if not values:
        raise ValueError("percentile requires at least one value")
    ordered = sorted(values)
    position = (len(ordered) - 1) * (percent / 100.0)
    lower = math.floor(position)
    upper = math.ceil(position)
    if lower == upper:
        return ordered[lower]
    weight = position - lower
    return ordered[lower] * (1.0 - weight) + ordered[upper] * weight


def dbm_sum(values: Iterable[float]) -> float | None:
    linear_mw = sum(10.0 ** (value / 10.0) for value in values)
    if linear_mw <= 0:
        return None
    return 10.0 * math.log10(linear_mw)


def parse_timestamp(date_text: str, time_text: str) -> datetime | None:
    combined = f"{date_text.strip()} {time_text.strip()}"
    for pattern in (
        "%Y-%m-%d %H:%M:%S",
        "%Y-%m-%d %H:%M:%S.%f",
        "%m/%d/%Y %H:%M:%S",
    ):
        try:
            return datetime.strptime(combined, pattern)
        except ValueError:
            continue
    return None


def parse_rtl_power_csv(path: Path, calibration_offset_db: float = 0.0) -> list[Sweep]:
    grouped: dict[str, list[SpectrumBin]] = defaultdict(list)
    timestamps: dict[str, datetime | None] = {}

    with path.open("r", encoding="utf-8-sig", newline="") as handle:
        reader = csv.reader(handle)
        for line_number, row in enumerate(reader, start=1):
            if not row or row[0].lstrip().startswith("#"):
                continue
            if len(row) < 7:
                raise ValueError(
                    f"CSV line {line_number} has {len(row)} columns; expected at least 7"
                )

            try:
                low_hz = float(row[2].strip())
                step_hz = float(row[4].strip())
                powers = [
                    float(value.strip()) + calibration_offset_db
                    for value in row[6:]
                    if value.strip() != ""
                ]
            except ValueError as exc:
                raise ValueError(f"Invalid number on CSV line {line_number}: {exc}") from exc

            if step_hz <= 0 or not powers:
                continue

            timestamp_text = f"{row[0].strip()} {row[1].strip()}"
            timestamps[timestamp_text] = parse_timestamp(row[0], row[1])

            # rtl_power reports Hz low and Hz step. The value is represented at
            # the bin centre to avoid claiming edge precision.
            for index, power_dbm in enumerate(powers):
                frequency_hz = low_hz + ((index + 0.5) * step_hz)
                grouped[timestamp_text].append(SpectrumBin(frequency_hz, power_dbm))

    sweeps = [
        Sweep(
            timestamp_text=timestamp_text,
            timestamp=timestamps[timestamp_text],
            bins=sorted(bins, key=lambda item: item.frequency_hz),
        )
        for timestamp_text, bins in grouped.items()
    ]
    sweeps.sort(key=lambda item: (item.timestamp or datetime.min, item.timestamp_text))

    if not sweeps:
        raise ValueError(f"No rtl_power spectrum rows found in {path}")
    return sweeps


def contiguous_event_count(flags: Sequence[bool], minimum_bins: int) -> tuple[int, int]:
    event_count = 0
    longest_run = 0
    current_run = 0

    for flag in flags:
        if flag:
            current_run += 1
            longest_run = max(longest_run, current_run)
            continue
        if current_run >= minimum_bins:
            event_count += 1
        current_run = 0

    if current_run >= minimum_bins:
        event_count += 1
    return event_count, longest_run


def analyse_sweeps(
    sweeps: Sequence[Sweep],
    settings: AnalysisSettings,
    source_csv: Path,
    simulated: bool = False,
) -> tuple[dict, list[dict], list[dict]]:
    center_hz = settings.center_mhz * 1_000_000.0
    half_channel_hz = settings.channel_bw_khz * 500.0
    channel_low_hz = center_hz - half_channel_hz
    channel_high_hz = center_hz + half_channel_hz

    per_sweep: list[dict] = []
    spectrum_values: dict[int, list[float]] = defaultdict(list)
    all_channel_powers: list[float] = []
    all_excess_values: list[float] = []
    channel_observations = 0
    occupied_observations = 0
    total_events = 0
    busy_sweeps = 0
    global_peak: tuple[float, float] | None = None

    for sweep in sweeps:
        channel_bins = [
            item
            for item in sweep.bins
            if channel_low_hz <= item.frequency_hz <= channel_high_hz
        ]
        reference_bins = [
            item
            for item in sweep.bins
            if item.frequency_hz < channel_low_hz
            or item.frequency_hz > channel_high_hz
        ]

        if not channel_bins:
            continue
        if len(reference_bins) < 4:
            raise ValueError(
                "Scan requires guard-band bins outside the target channel. "
                "Increase --span-mhz."
            )

        noise_floor = statistics.median(item.power_dbm for item in reference_bins)
        threshold_dbm = noise_floor + settings.threshold_db
        flags = [item.power_dbm >= threshold_dbm for item in channel_bins]
        event_count, longest_run = contiguous_event_count(
            flags, settings.min_event_bins
        )
        occupied_count = sum(flags)
        occupied_percent = (occupied_count / len(channel_bins)) * 100.0
        busy = event_count > 0

        if busy:
            busy_sweeps += 1
        total_events += event_count
        channel_observations += len(channel_bins)
        occupied_observations += occupied_count

        peak_bin = max(channel_bins, key=lambda item: item.power_dbm)
        if global_peak is None or peak_bin.power_dbm > global_peak[1]:
            global_peak = (peak_bin.frequency_hz, peak_bin.power_dbm)

        channel_powers = [item.power_dbm for item in channel_bins]
        excess_values = [item.power_dbm - noise_floor for item in channel_bins]
        all_channel_powers.extend(channel_powers)
        all_excess_values.extend(excess_values)

        for item in sweep.bins:
            spectrum_values[round(item.frequency_hz)].append(item.power_dbm)

        per_sweep.append(
            {
                "timestamp": sweep.timestamp_text,
                "noise_floor_dbm_per_bin": round(noise_floor, 3),
                "detection_threshold_dbm_per_bin": round(threshold_dbm, 3),
                "channel_median_dbm_per_bin": round(
                    statistics.median(channel_powers), 3
                ),
                "channel_integrated_power_dbm_estimate": round(
                    dbm_sum(channel_powers) or 0.0, 3
                ),
                "peak_power_dbm_per_bin": round(peak_bin.power_dbm, 3),
                "peak_frequency_mhz": round(peak_bin.frequency_hz / 1_000_000.0, 6),
                "max_excess_above_noise_db": round(max(excess_values), 3),
                "occupied_bins": occupied_count,
                "channel_bins": len(channel_bins),
                "occupancy_percent": round(occupied_percent, 4),
                "event_count": event_count,
                "longest_event_bins": longest_run,
                "busy": busy,
            }
        )

    if not per_sweep or not all_channel_powers or global_peak is None:
        raise ValueError("No target-channel bins were found in the SDR data")

    sweep_noise = [row["noise_floor_dbm_per_bin"] for row in per_sweep]
    channel_occupancy = (
        (occupied_observations / channel_observations) * 100.0
        if channel_observations
        else 0.0
    )
    busy_sweep_percent = (busy_sweeps / len(per_sweep)) * 100.0
    significant_activity = (
        channel_occupancy > settings.max_occupancy_percent
        or busy_sweep_percent > settings.max_busy_sweep_percent
    )

    if simulated:
        verdict = "SIMULATION_ONLY_NOT_EMPIRICAL_EVIDENCE"
    elif settings.halow_state != "off":
        verdict = "INCONCLUSIVE_HALOW_TRANSMITTER_ACTIVE"
    elif significant_activity:
        verdict = "SIGNIFICANT_RF_ACTIVITY_DETECTED"
    else:
        verdict = "NO_SIGNIFICANT_INTERFERENCE_DETECTED"

    measured_timestamps = [sweep.timestamp for sweep in sweeps if sweep.timestamp]
    observed_seconds = None
    if len(measured_timestamps) >= 2:
        observed_seconds = max(
            0.0,
            (max(measured_timestamps) - min(measured_timestamps)).total_seconds(),
        )

    spectrum_rows: list[dict] = []
    for frequency_hz in sorted(spectrum_values):
        values = spectrum_values[frequency_hz]
        spectrum_rows.append(
            {
                "frequency_mhz": round(frequency_hz / 1_000_000.0, 6),
                "median_dbm_per_bin": round(statistics.median(values), 3),
                "p95_dbm_per_bin": round(percentile(values, 95), 3),
                "max_dbm_per_bin": round(max(values), 3),
                "inside_target_channel": (
                    channel_low_hz <= frequency_hz <= channel_high_hz
                ),
            }
        )

    source_hash = hashlib.sha256(source_csv.read_bytes()).hexdigest()
    summary = {
        "schema_version": 1,
        "generated_at": datetime.now().astimezone().isoformat(timespec="seconds"),
        "source_csv": str(source_csv.resolve()),
        "source_csv_sha256": source_hash,
        "simulated": simulated,
        "measurement": {
            "center_frequency_mhz": settings.center_mhz,
            "target_channel_low_mhz": channel_low_hz / 1_000_000.0,
            "target_channel_high_mhz": channel_high_hz / 1_000_000.0,
            "channel_bandwidth_khz": settings.channel_bw_khz,
            "halow_transmitter_state": settings.halow_state,
            "calibration_offset_db": settings.calibration_offset_db,
            "sweep_count": len(per_sweep),
            "observed_duration_seconds": observed_seconds,
            "channel_bin_observations": channel_observations,
        },
        "decision_thresholds": {
            "power_above_local_noise_db": settings.threshold_db,
            "maximum_occupancy_percent": settings.max_occupancy_percent,
            "maximum_busy_sweep_percent": settings.max_busy_sweep_percent,
            "minimum_adjacent_event_bins": settings.min_event_bins,
        },
        "results": {
            "noise_floor_median_dbm_per_bin": round(
                statistics.median(sweep_noise), 3
            ),
            "noise_floor_p95_dbm_per_bin": round(percentile(sweep_noise, 95), 3),
            "channel_median_dbm_per_bin": round(
                statistics.median(all_channel_powers), 3
            ),
            "channel_p95_dbm_per_bin": round(
                percentile(all_channel_powers, 95), 3
            ),
            "channel_integrated_power_dbm_estimate": round(
                dbm_sum(
                    [
                        row["channel_integrated_power_dbm_estimate"]
                        for row in per_sweep
                    ]
                )
                - (10.0 * math.log10(len(per_sweep))),
                3,
            ),
            "peak_power_dbm_per_bin": round(global_peak[1], 3),
            "peak_frequency_mhz": round(global_peak[0] / 1_000_000.0, 6),
            "p95_excess_above_noise_db": round(
                percentile(all_excess_values, 95), 3
            ),
            "maximum_excess_above_noise_db": round(max(all_excess_values), 3),
            "channel_occupancy_percent": round(channel_occupancy, 4),
            "busy_sweep_percent": round(busy_sweep_percent, 4),
            "detected_event_count": total_events,
            "significant_activity": significant_activity,
            "verdict": verdict,
        },
        "interpretation": {
            "statement": verdict_statement(verdict),
            "scope": (
                "The verdict applies only to the measured frequency span, "
                "location, antenna, receiver gain, sensitivity, thresholds, "
                "and observation period."
            ),
            "limitation": (
                "An uncalibrated RTL-SDR provides useful relative spectrum "
                "evidence, but its dBm values are estimates. Calibration and "
                "a characterized antenna are required for traceable absolute power."
            ),
        },
    }
    return summary, per_sweep, spectrum_rows


def verdict_statement(verdict: str) -> str:
    statements = {
        "NO_SIGNIFICANT_INTERFERENCE_DETECTED": (
            "No significant external RF activity was detected in the target "
            "channel above the configured local-noise threshold during this measurement."
        ),
        "SIGNIFICANT_RF_ACTIVITY_DETECTED": (
            "RF activity exceeding the configured occupancy limits was detected "
            "in the target channel. Further identification is required."
        ),
        "INCONCLUSIVE_HALOW_TRANSMITTER_ACTIVE": (
            "The HaLow transmitter was active, so the SDR cannot reliably "
            "separate intended HaLow energy from external interference."
        ),
        "SIMULATION_ONLY_NOT_EMPIRICAL_EVIDENCE": (
            "This report was generated from simulated data and must not be "
            "presented as a physical RF measurement."
        ),
    }
    return statements[verdict]


def write_csv(path: Path, rows: Sequence[dict]) -> None:
    if not rows:
        return
    with path.open("w", encoding="utf-8", newline="") as handle:
        writer = csv.DictWriter(handle, fieldnames=list(rows[0].keys()))
        writer.writeheader()
        writer.writerows(rows)


def polyline_points(
    rows: Sequence[dict],
    field: str,
    width: int,
    height: int,
    padding: int,
    min_power: float,
    max_power: float,
) -> str:
    frequencies = [float(row["frequency_mhz"]) for row in rows]
    min_frequency = min(frequencies)
    max_frequency = max(frequencies)
    frequency_span = max(max_frequency - min_frequency, 1e-9)
    power_span = max(max_power - min_power, 1e-9)
    points = []
    for row in rows:
        x = padding + (
            (float(row["frequency_mhz"]) - min_frequency) / frequency_span
        ) * (width - (2 * padding))
        y = padding + (
            (max_power - float(row[field])) / power_span
        ) * (height - (2 * padding))
        points.append(f"{x:.1f},{y:.1f}")
    return " ".join(points)


def render_html_report(summary: dict, spectrum_rows: Sequence[dict]) -> str:
    results = summary["results"]
    measurement = summary["measurement"]
    thresholds = summary["decision_thresholds"]
    values = [
        float(row[field])
        for row in spectrum_rows
        for field in ("median_dbm_per_bin", "max_dbm_per_bin")
    ]
    min_power = math.floor(min(values) / 5.0) * 5.0
    max_power = math.ceil(max(values) / 5.0) * 5.0
    if min_power == max_power:
        max_power += 5.0
    width, height, padding = 1000, 440, 58
    median_points = polyline_points(
        spectrum_rows,
        "median_dbm_per_bin",
        width,
        height,
        padding,
        min_power,
        max_power,
    )
    max_points = polyline_points(
        spectrum_rows,
        "max_dbm_per_bin",
        width,
        height,
        padding,
        min_power,
        max_power,
    )
    min_frequency = min(float(row["frequency_mhz"]) for row in spectrum_rows)
    max_frequency = max(float(row["frequency_mhz"]) for row in spectrum_rows)
    frequency_span = max(max_frequency - min_frequency, 1e-9)
    channel_x1 = padding + (
        (measurement["target_channel_low_mhz"] - min_frequency) / frequency_span
    ) * (width - (2 * padding))
    channel_x2 = padding + (
        (measurement["target_channel_high_mhz"] - min_frequency) / frequency_span
    ) * (width - (2 * padding))
    verdict_class = (
        "good"
        if results["verdict"] == "NO_SIGNIFICANT_INTERFERENCE_DETECTED"
        else "warn"
    )

    rows = [
        ("Center frequency", f'{measurement["center_frequency_mhz"]:.6f} MHz'),
        ("Channel bandwidth", f'{measurement["channel_bandwidth_khz"]:.1f} kHz'),
        ("HaLow transmitter", measurement["halow_transmitter_state"].upper()),
        ("Sweeps", str(measurement["sweep_count"])),
        (
            "Noise floor median",
            f'{results["noise_floor_median_dbm_per_bin"]:.2f} dBm/bin',
        ),
        ("Peak power", f'{results["peak_power_dbm_per_bin"]:.2f} dBm/bin'),
        ("Peak frequency", f'{results["peak_frequency_mhz"]:.6f} MHz'),
        (
            "Channel occupancy",
            f'{results["channel_occupancy_percent"]:.3f}%',
        ),
        ("Busy sweeps", f'{results["busy_sweep_percent"]:.3f}%'),
        ("Detected events", str(results["detected_event_count"])),
        (
            "Detection rule",
            f'{thresholds["power_above_local_noise_db"]:.1f} dB above local noise',
        ),
    ]
    table_html = "".join(
        f"<tr><th>{html.escape(label)}</th><td>{html.escape(value)}</td></tr>"
        for label, value in rows
    )
    simulated_warning = (
        '<div class="warning">SIMULATION: this is not physical RF evidence.</div>'
        if summary["simulated"]
        else ""
    )

    return f"""<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SDR Interference Report</title>
<style>
body {{ font-family: Arial, sans-serif; margin: 0; background:#f3f6fa; color:#182230; }}
main {{ max-width:1100px; margin:28px auto; padding:0 18px; }}
.card {{ background:white; border:1px solid #dfe5ec; border-radius:12px; padding:22px; margin:16px 0; }}
.verdict {{ border-left:7px solid #d97706; }} .verdict.good {{ border-left-color:#15935c; }}
.warning {{ background:#fff1c2; border:1px solid #d8a800; padding:12px; border-radius:8px; font-weight:bold; }}
table {{ border-collapse:collapse; width:100%; }} th,td {{ padding:9px; border-bottom:1px solid #e6ebf0; text-align:left; }}
th {{ width:38%; color:#415166; }} svg {{ width:100%; height:auto; background:#101822; border-radius:8px; }}
.legend span {{ display:inline-block; margin-right:18px; }} .dot {{ width:11px; height:11px; border-radius:50%; margin-right:5px; }}
code {{ word-break:break-all; }} small {{ color:#5f6f82; }}
</style>
</head>
<body><main>
<h1>SDR Channel Interference Report</h1>
{simulated_warning}
<section class="card verdict {verdict_class}">
<h2>{html.escape(results["verdict"])}</h2>
<p>{html.escape(summary["interpretation"]["statement"])}</p>
</section>
<section class="card"><h2>Output variables</h2><table>{table_html}</table></section>
<section class="card"><h2>Spectrum around the target channel</h2>
<div class="legend"><span><i class="dot" style="background:#38bdf8"></i>Median</span>
<span><i class="dot" style="background:#fb7185"></i>Maximum</span>
<span><i class="dot" style="background:#facc15"></i>Target channel</span></div>
<svg viewBox="0 0 {width} {height}" role="img" aria-label="Spectrum plot">
<rect x="{channel_x1:.1f}" y="{padding}" width="{max(1.0, channel_x2-channel_x1):.1f}" height="{height-(2*padding)}" fill="#facc15" opacity="0.13"/>
<line x1="{padding}" y1="{height-padding}" x2="{width-padding}" y2="{height-padding}" stroke="#8290a3"/>
<line x1="{padding}" y1="{padding}" x2="{padding}" y2="{height-padding}" stroke="#8290a3"/>
<polyline points="{median_points}" fill="none" stroke="#38bdf8" stroke-width="2"/>
<polyline points="{max_points}" fill="none" stroke="#fb7185" stroke-width="1.5" opacity="0.85"/>
<text x="{padding}" y="{height-18}" fill="#cbd5e1">{min_frequency:.3f} MHz</text>
<text x="{width-padding-90}" y="{height-18}" fill="#cbd5e1">{max_frequency:.3f} MHz</text>
<text x="8" y="{padding+5}" fill="#cbd5e1">{max_power:.0f} dBm/bin</text>
<text x="8" y="{height-padding}" fill="#cbd5e1">{min_power:.0f} dBm/bin</text>
</svg></section>
<section class="card"><h2>Evidence scope and limitations</h2>
<p>{html.escape(summary["interpretation"]["scope"])}</p>
<p>{html.escape(summary["interpretation"]["limitation"])}</p>
<small>Raw CSV SHA-256: <code>{summary["source_csv_sha256"]}</code></small>
</section>
</main></body></html>"""


def write_outputs(
    output_dir: Path,
    summary: dict,
    per_sweep: Sequence[dict],
    spectrum_rows: Sequence[dict],
) -> None:
    output_dir.mkdir(parents=True, exist_ok=True)
    (output_dir / "summary.json").write_text(
        json.dumps(summary, indent=2, ensure_ascii=False) + "\n",
        encoding="utf-8",
    )
    write_csv(output_dir / "sweep_metrics.csv", per_sweep)
    write_csv(output_dir / "spectrum_summary.csv", spectrum_rows)
    (output_dir / "report.html").write_text(
        render_html_report(summary, spectrum_rows),
        encoding="utf-8",
    )

    if summary["simulated"]:
        (output_dir / "DO_NOT_USE_AS_EVIDENCE.txt").write_text(
            "SIMULATED DATA: do not import these values into the research "
            "database or present them as an SDR measurement.\n",
            encoding="utf-8",
        )
        return

    results = summary["results"]
    web_values = {
        "interference_level": (
            "normal"
            if results["verdict"] == "NO_SIGNIFICANT_INTERFERENCE_DETECTED"
            else "medium"
        ),
        "interference_source": (
            "Tidak terdeteksi oleh SDR"
            if results["verdict"] == "NO_SIGNIFICANT_INTERFERENCE_DETECTED"
            else "Aktivitas RF terdeteksi; sumber belum teridentifikasi"
        ),
        "notes": (
            f'SDR {summary["measurement"]["center_frequency_mhz"]:.3f} MHz; '
            f'noise floor {results["noise_floor_median_dbm_per_bin"]:.2f} dBm/bin; '
            f'occupancy {results["channel_occupancy_percent"]:.3f}%; '
            f'busy sweeps {results["busy_sweep_percent"]:.3f}%; '
            f'verdict {results["verdict"]}; '
            f'raw SHA-256 {summary["source_csv_sha256"]}.'
        ),
    }
    (output_dir / "web_form_values.json").write_text(
        json.dumps(web_values, indent=2, ensure_ascii=False) + "\n",
        encoding="utf-8",
    )


def simulate_rtl_power_csv(
    path: Path,
    center_mhz: float,
    span_mhz: float,
    bin_khz: float,
    sweeps: int,
    scenario: str,
) -> None:
    rng = random.Random(9235)
    low_hz = (center_mhz - (span_mhz / 2.0)) * 1_000_000.0
    high_hz = (center_mhz + (span_mhz / 2.0)) * 1_000_000.0
    step_hz = bin_khz * 1000.0
    bin_count = max(16, int((high_hz - low_hz) / step_hz))
    start = datetime(2026, 7, 28, 12, 0, 0)

    path.parent.mkdir(parents=True, exist_ok=True)
    with path.open("w", encoding="utf-8", newline="") as handle:
        writer = csv.writer(handle)
        for sweep_index in range(sweeps):
            powers = [-101.0 + rng.gauss(0, 1.1) for _ in range(bin_count)]
            if scenario == "busy" and sweep_index % 2 == 0:
                event_center_hz = center_mhz * 1_000_000.0 + 120_000.0
                for index in range(bin_count):
                    frequency_hz = low_hz + ((index + 0.5) * step_hz)
                    if abs(frequency_hz - event_center_hz) <= 40_000.0:
                        powers[index] += 20.0 + rng.uniform(-1.0, 1.0)

            timestamp = start + timedelta(seconds=sweep_index)
            writer.writerow(
                [
                    timestamp.strftime("%Y-%m-%d"),
                    timestamp.strftime("%H:%M:%S"),
                    f"{low_hz:.0f}",
                    f"{high_hz:.0f}",
                    f"{step_hz:.0f}",
                    "1",
                    *[f"{power:.3f}" for power in powers],
                ]
            )


def analysis_settings_from_args(args: argparse.Namespace) -> AnalysisSettings:
    return AnalysisSettings(
        center_mhz=args.center_mhz,
        channel_bw_khz=args.channel_bw_khz,
        threshold_db=args.threshold_db,
        max_occupancy_percent=args.max_occupancy_percent,
        max_busy_sweep_percent=args.max_busy_sweep_percent,
        min_event_bins=args.min_event_bins,
        calibration_offset_db=args.calibration_offset_db,
        halow_state=args.halow_state,
    )


def analyse_file(
    csv_path: Path,
    output_dir: Path,
    args: argparse.Namespace,
    simulated: bool = False,
) -> dict:
    settings = analysis_settings_from_args(args)
    sweeps = parse_rtl_power_csv(csv_path, settings.calibration_offset_db)
    summary, per_sweep, spectrum_rows = analyse_sweeps(
        sweeps, settings, csv_path, simulated=simulated
    )
    write_outputs(output_dir, summary, per_sweep, spectrum_rows)
    print_summary(summary, output_dir)
    return summary


def print_summary(summary: dict, output_dir: Path) -> None:
    results = summary["results"]
    print()
    print("SDR interference result")
    print(f"  Verdict             : {results['verdict']}")
    print(
        "  Noise floor         : "
        f"{results['noise_floor_median_dbm_per_bin']:.2f} dBm/bin"
    )
    print(
        "  Channel occupancy   : "
        f"{results['channel_occupancy_percent']:.3f}%"
    )
    print(f"  Busy sweeps         : {results['busy_sweep_percent']:.3f}%")
    print(
        "  Peak                : "
        f"{results['peak_power_dbm_per_bin']:.2f} dBm/bin at "
        f"{results['peak_frequency_mhz']:.6f} MHz"
    )
    print(f"  Report              : {(output_dir / 'report.html').resolve()}")


def find_rtl_power(explicit_path: str | None) -> str:
    if explicit_path:
        candidate = Path(explicit_path)
        if not candidate.is_file():
            raise FileNotFoundError(f"rtl_power not found: {candidate}")
        return str(candidate.resolve())

    executable = shutil.which("rtl_power") or shutil.which("rtl_power.exe")
    if executable:
        return executable
    raise FileNotFoundError(
        "rtl_power was not found in PATH. Install the RTL-SDR utilities or "
        "pass --rtl-power-path C:\\path\\to\\rtl_power.exe"
    )


def validate_scan_values(args: argparse.Namespace) -> None:
    if args.span_mhz <= (args.channel_bw_khz / 1000.0):
        raise ValueError("--span-mhz must be wider than --channel-bw-khz")
    if args.bin_khz <= 0 or args.bin_khz >= args.channel_bw_khz:
        raise ValueError("--bin-khz must be positive and smaller than channel bandwidth")
    if not re.fullmatch(r"[1-9]\d*[smh]", args.duration):
        raise ValueError("--duration must look like 60s, 15m, or 1h")
    if not re.fullmatch(r"[1-9]\d*[smh]", args.integration):
        raise ValueError("--integration must look like 1s or 1m")


def run_rtl_power(args: argparse.Namespace, output_dir: Path) -> Path:
    validate_scan_values(args)
    rtl_power = find_rtl_power(args.rtl_power_path)
    raw_csv = output_dir / "rtl_power_raw.csv"
    log_path = output_dir / "rtl_power.log"
    output_dir.mkdir(parents=True, exist_ok=True)

    center_hz = args.center_mhz * 1_000_000.0
    half_span_hz = args.span_mhz * 500_000.0
    low_hz = round(center_hz - half_span_hz)
    high_hz = round(center_hz + half_span_hz)
    bin_hz = round(args.bin_khz * 1000.0)
    command = [
        rtl_power,
        "-f",
        f"{low_hz}:{high_hz}:{bin_hz}",
        "-i",
        args.integration,
        "-e",
        args.duration,
        "-d",
        str(args.device),
        "-p",
        str(args.ppm),
        "-w",
        "blackman-harris",
    ]
    if args.gain.lower() != "auto":
        float(args.gain)
        command.extend(["-g", args.gain])
    command.append(str(raw_csv))

    (output_dir / "acquisition.json").write_text(
        json.dumps(
            {
                "started_at": datetime.now().astimezone().isoformat(timespec="seconds"),
                "command": command,
                "center_mhz": args.center_mhz,
                "span_mhz": args.span_mhz,
                "bin_khz_requested": args.bin_khz,
                "duration": args.duration,
                "integration": args.integration,
                "gain": args.gain,
                "device": args.device,
                "ppm": args.ppm,
            },
            indent=2,
        )
        + "\n",
        encoding="utf-8",
    )

    print("Running receive-only SDR measurement:")
    print(" ".join(f'"{item}"' if " " in item else item for item in command))
    with log_path.open("w", encoding="utf-8") as log_handle:
        process = subprocess.Popen(
            command,
            stdout=subprocess.PIPE,
            stderr=subprocess.STDOUT,
            text=True,
            encoding="utf-8",
            errors="replace",
        )
        assert process.stdout is not None
        for line in process.stdout:
            print(line, end="")
            log_handle.write(line)
        exit_code = process.wait()

    if exit_code != 0:
        raise RuntimeError(
            f"rtl_power exited with code {exit_code}. See {log_path.resolve()}"
        )
    if not raw_csv.is_file() or raw_csv.stat().st_size == 0:
        raise RuntimeError("rtl_power finished without producing spectrum data")
    return raw_csv


def add_analysis_arguments(parser: argparse.ArgumentParser) -> None:
    parser.add_argument("--center-mhz", type=float, default=DEFAULT_CENTER_MHZ)
    parser.add_argument(
        "--channel-bw-khz", type=float, default=DEFAULT_CHANNEL_BW_KHZ
    )
    parser.add_argument(
        "--threshold-db",
        type=float,
        default=10.0,
        help="A bin is occupied when this many dB above local noise (default: 10)",
    )
    parser.add_argument("--max-occupancy-percent", type=float, default=1.0)
    parser.add_argument("--max-busy-sweep-percent", type=float, default=5.0)
    parser.add_argument("--min-event-bins", type=int, default=2)
    parser.add_argument(
        "--calibration-offset-db",
        type=float,
        default=0.0,
        help="Known receiver correction added to every bin",
    )
    parser.add_argument(
        "--halow-state",
        choices=("off", "on"),
        default="off",
        help="Use off for external-interference baseline evidence",
    )


def build_parser() -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser(
        description=(
            "Receive-only SDR measurement for external activity around a "
            "Wi-Fi HaLow channel."
        )
    )
    subparsers = parser.add_subparsers(dest="command", required=True)

    scan = subparsers.add_parser("scan", help="Run rtl_power and analyse the result")
    add_analysis_arguments(scan)
    scan.add_argument("--span-mhz", type=float, default=DEFAULT_SPAN_MHZ)
    scan.add_argument("--bin-khz", type=float, default=DEFAULT_BIN_KHZ)
    scan.add_argument("--duration", default="15m")
    scan.add_argument("--integration", default="1s")
    scan.add_argument("--gain", default="20", help="Tuner gain in dB or auto")
    scan.add_argument("--device", type=int, default=0)
    scan.add_argument("--ppm", type=int, default=0)
    scan.add_argument("--rtl-power-path")
    scan.add_argument("--output-root", type=Path, default=Path("sdr_results"))

    analyse = subparsers.add_parser("analyze", help="Analyse an existing rtl_power CSV")
    add_analysis_arguments(analyse)
    analyse.add_argument("csv_path", type=Path)
    analyse.add_argument("--output-dir", type=Path)

    simulate = subparsers.add_parser(
        "simulate", help="Test the software with clearly-labelled synthetic data"
    )
    add_analysis_arguments(simulate)
    simulate.add_argument("--scenario", choices=("clean", "busy"), default="clean")
    simulate.add_argument("--span-mhz", type=float, default=DEFAULT_SPAN_MHZ)
    simulate.add_argument("--bin-khz", type=float, default=DEFAULT_BIN_KHZ)
    simulate.add_argument("--sweeps", type=int, default=60)
    simulate.add_argument("--output-dir", type=Path, required=True)
    return parser


def main(argv: Sequence[str] | None = None) -> int:
    parser = build_parser()
    args = parser.parse_args(argv)

    try:
        if args.command == "scan":
            timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
            output_dir = args.output_root / f"halow_923_5_{timestamp}"
            raw_csv = run_rtl_power(args, output_dir)
            analyse_file(raw_csv, output_dir, args)
            return 0

        if args.command == "analyze":
            csv_path = args.csv_path.resolve()
            if not csv_path.is_file():
                raise FileNotFoundError(csv_path)
            output_dir = args.output_dir or (
                csv_path.parent / f"{csv_path.stem}_analysis"
            )
            analyse_file(csv_path, output_dir, args)
            return 0

        if args.command == "simulate":
            args.output_dir.mkdir(parents=True, exist_ok=True)
            raw_csv = args.output_dir / "SIMULATED_rtl_power_raw.csv"
            simulate_rtl_power_csv(
                raw_csv,
                args.center_mhz,
                args.span_mhz,
                args.bin_khz,
                args.sweeps,
                args.scenario,
            )
            analyse_file(raw_csv, args.output_dir, args, simulated=True)
            return 0
    except (FileNotFoundError, RuntimeError, ValueError) as exc:
        print(f"ERROR: {exc}", file=sys.stderr)
        return 2

    parser.error("Unknown command")
    return 2


if __name__ == "__main__":
    raise SystemExit(main())
