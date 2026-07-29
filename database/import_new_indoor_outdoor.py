#!/usr/bin/env python3
"""Import repeated indoor/outdoor measurements from the raw XLSX workbook."""

from __future__ import annotations

import argparse
import math
import re
import subprocess
import sys
from datetime import datetime
from pathlib import Path
from typing import Any

from sync_connectivity_workbook import read_workbook, sql_value

TABLES = (
    "connectivity_tests", "range_tests", "signal_penetration_tests",
    "latency_tests", "throughput_tests", "interference_tests",
)


def coordinates(text: Any) -> tuple[float, float, str | None]:
    values = re.findall(r"-?\d+(?:\.\d+)?", str(text or ""))
    if len(values) < 2:
        raise ValueError(f"Koordinat tidak terbaca: {text!r}")
    latitude, longitude = map(float, values[-2:])
    corrections = []
    if abs(latitude) > 90 and 6_000_000 <= abs(latitude) <= 7_000_000:
        old = latitude
        latitude /= 1_000_000
        corrections.append(f"latitude {old:g} -> {latitude:.6f}")
    # One workbook cell says 106.329440 while adjacent points are 106.829xxx.
    if 106.0 <= longitude < 106.5:
        old = longitude
        longitude += 0.5
        corrections.append(f"longitude {old:.6f} -> {longitude:.6f}")
    if not (-90 <= latitude <= 90 and -180 <= longitude <= 180):
        raise ValueError(f"Koordinat di luar rentang: {latitude}, {longitude}")
    return latitude, longitude, "; ".join(corrections) or None


def distance_offsets(master: tuple[float, float], slave: tuple[float, float]) -> tuple[float, float, float]:
    lat1, lon1 = map(math.radians, master)
    lat2, lon2 = map(math.radians, slave)
    dlat, dlon = lat2 - lat1, lon2 - lon1
    a = math.sin(dlat / 2) ** 2 + math.cos(lat1) * math.cos(lat2) * math.sin(dlon / 2) ** 2
    distance = 6_371_000 * 2 * math.atan2(math.sqrt(a), math.sqrt(1 - a))
    north = math.degrees(dlat) * 111_320
    east = math.degrees(dlon) * 111_320 * math.cos((lat1 + lat2) / 2)
    return round(distance, 2), round(east, 2), round(north, 2)


def rssi_noise(text: Any) -> tuple[float, float]:
    match = re.search(r"RSSI\s*=\s*(-?\d+(?:\.\d+)?)\s*/\s*(-?\d+(?:\.\d+)?)", str(text), re.I)
    if not match:
        raise ValueError(f"RSSI/noise floor tidak terbaca: {text!r}")
    return float(match.group(1)), float(match.group(2))


def snr_value(text: Any) -> float:
    match = re.search(r"SNR\s*=\s*(-?\d+(?:\.\d+)?)", str(text), re.I)
    if not match:
        raise ValueError(f"SNR tidak terbaca: {text!r}")
    return float(match.group(1))


def ping_metrics(text: Any) -> dict[str, Any]:
    value = str(text or "")
    summary = re.search(
        r"(\d+)\s+packets transmitted,\s*(\d+)\s+packets received,\s*([\d.]+)%\s+packet loss",
        value, re.I,
    )
    round_trip = re.search(
        r"(?:round-trip|rtt)[^=]*=\s*([\d.]+)/([\d.]+)/([\d.]+)(?:/[\d.]+)?\s*ms",
        value, re.I,
    )
    if not summary:
        raise ValueError("Ringkasan ping tidak ditemukan")
    sent, received, loss = int(summary.group(1)), int(summary.group(2)), float(summary.group(3))
    times = [float(v) for v in re.findall(r"time[=<]([\d.]+)\s*ms", value, re.I)]
    jitter = (
        sum(abs(b - a) for a, b in zip(times, times[1:])) / (len(times) - 1)
        if len(times) > 1 else None
    )
    return {
        "sent": sent, "received": received, "loss": loss,
        "minimum": float(round_trip.group(1)) if round_trip else (min(times) if times else None),
        "average": float(round_trip.group(2)) if round_trip else (sum(times) / len(times) if times else None),
        "maximum": float(round_trip.group(3)) if round_trip else (max(times) if times else None),
        "jitter": round(jitter, 2) if jitter is not None else None,
    }


def throughput_metrics(text: Any) -> tuple[float | None, float | None]:
    value = str(text or "")
    matches = re.findall(
        r"0\.0+\s*-\s*60\.0+\s*sec\s+([\d.]+)\s*(KBytes|MBytes|Bytes)\s+"
        r"([\d.]+)\s*(Kbits/sec|Mbits/sec|bits/sec)",
        value, re.I,
    )
    if matches:
        transfer, transfer_unit, bandwidth, bandwidth_unit = matches[-1]
        transfer_kb = float(transfer) * {"bytes": 1 / 1024, "kbytes": 1, "mbytes": 1024}[transfer_unit.lower()]
        throughput = float(bandwidth) * {"bits/sec": .001, "kbits/sec": 1, "mbits/sec": 1000}[bandwidth_unit.lower()]
        return round(transfer_kb, 2), round(throughput, 2)

    # Interrupted iperf runs in the indoor sheet have all one-second intervals
    # but no final summary. Aggregate those observed intervals reproducibly.
    intervals = re.findall(
        r"(\d+(?:\.\d+)?)\s*-\s*(\d+(?:\.\d+)?)\s*sec\s+"
        r"([\d.]+)\s*(KBytes|MBytes|Bytes)\s+([\d.]+)\s*"
        r"(Kbits/sec|Mbits/sec|bits/sec)",
        value, re.I,
    )
    if not intervals:
        return None, None
    transfer_kb = 0.0
    weighted_kbits = 0.0
    observed_seconds = 0.0
    for start, end, transfer, transfer_unit, bandwidth, bandwidth_unit in intervals:
        duration = float(end) - float(start)
        if duration <= 0 or duration > 1.01:
            continue
        transfer_kb += float(transfer) * {
            "bytes": 1 / 1024, "kbytes": 1, "mbytes": 1024,
        }[transfer_unit.lower()]
        kbits = float(bandwidth) * {
            "bits/sec": .001, "kbits/sec": 1, "mbits/sec": 1000,
        }[bandwidth_unit.lower()]
        weighted_kbits += kbits * duration
        observed_seconds += duration
    if observed_seconds == 0:
        return None, None
    return round(transfer_kb, 2), round(weighted_kbits / observed_seconds, 2)


def connection_status(received: int) -> str:
    return "disconnected" if received == 0 else ("connected" if received == 60 else "intermittent")


def quality(rssi: float, received: int) -> str:
    if received == 0 or rssi < -100:
        return "poor"
    return "moderate" if rssi < -90 or received < 48 else "good"


def build_records(workbook: Path, test_date: str) -> dict[str, list[dict[str, Any]]]:
    sheets = {name.strip().lower(): rows for name, rows in read_workbook(workbook).items()}
    if set(sheets) != {"indoor", "outdoor"}:
        raise ValueError(f"Sheet harus Indoor dan Outdoor; ditemukan {list(sheets)}")
    records = {table: [] for table in TABLES}
    for environment in ("indoor", "outdoor"):
        rows = sheets[environment]
        if len(rows) != 50:
            raise ValueError(f"Sheet {environment}: diharapkan 50 baris, ditemukan {len(rows)}")
        location = {
            "indoor": "Kantor PSN Jakarta",
            "outdoor": "Jalanan Depan Kantor PSN Jakarta",
        }[environment]
        for point, start in enumerate(range(0, 50, 10), 1):
            for trial, column in enumerate((1, 3, 5), 1):
                rssi, noise = rssi_noise(rows[start + 2].get(column))
                snr = snr_value(rows[start + 3].get(column))
                master_lat, master_lon, master_fix = coordinates(rows[start + 4].get(column))
                slave_lat, slave_lon, slave_fix = coordinates(rows[start + 5].get(column))
                distance, east, north = distance_offsets((master_lat, master_lon), (slave_lat, slave_lon))
                ping = ping_metrics(rows[start + 7].get(column))
                transfer_kb, throughput = throughput_metrics(rows[start + 9].get(column))
                point_code = f"{environment[0].upper()}-{point:02}-R{trial}"
                corrections = "; ".join(v for v in (master_fix, slave_fix) if v)
                note = (
                    f"Sumber: {workbook.name}; titik {point}, pengulangan {trial}. "
                    "Jarak dihitung dari koordinat GPS dengan Haversine."
                )
                if corrections:
                    note += f" Koreksi format sumber: {corrections}."
                common = {
                    "test_date": test_date, "location_name": location,
                    "environment_type": environment, "measurement_code": point_code,
                }
                packet_success = round(100 - ping["loss"], 2)
                records["connectivity_tests"].append({
                    **common, "distance_meter": distance, "node_id": "NODE-SLAVE-01", "node_type": "slave",
                    "connection_status": connection_status(ping["received"]), "rssi_dbm": rssi, "snr_db": snr,
                    "packet_sent": ping["sent"], "packet_received": ping["received"],
                    "packet_lost": ping["sent"] - ping["received"], "packet_loss_percent": ping["loss"],
                    "packet_success_rate": packet_success, "test_duration_second": 60, "notes": note,
                })
                fspl = round(32.44 + 20 * math.log10(915) + 20 * math.log10(distance / 1000), 2)
                records["range_tests"].append({
                    **common, "test_point_code": point_code,
                    "direction": ("east" if east >= 0 else "west") if abs(east) > abs(north) else ("north" if north >= 0 else "south"),
                    "coordinate_x_meter": east, "coordinate_y_meter": north, "coordinate_z_meter": 0,
                    "distance_actual_meter": distance, "distance_3d_meter": distance,
                    "distance_km": round(distance / 1000, 4), "master_gps_latitude": master_lat,
                    "master_gps_longitude": master_lon, "gps_latitude": slave_lat, "gps_longitude": slave_lon,
                    "frequency_mhz": 915, "rssi_dbm": rssi, "snr_db": snr, "bitrate_kbps": throughput,
                    "connection_status": connection_status(ping["received"]), "fspl_db": fspl,
                    "signal_margin": round(rssi - noise, 2), "receiver_sensitivity_dbm": noise,
                    "status_result": quality(rssi, ping["received"]), "photo_video_link": None, "notes": note,
                })
                records["signal_penetration_tests"].append({
                    **common, "obstacle_type": None, "condition_type": None, "distance_meter": distance,
                    "rssi_before_dbm": None, "rssi_after_dbm": rssi, "snr_before_db": None, "snr_after_db": snr,
                    "packet_sent": ping["sent"], "packet_received": ping["received"], "bitrate_kbps": throughput,
                    "rssi_loss": None, "snr_loss": None, "packet_loss_percent": ping["loss"],
                    "penetration_loss_db": None,
                    "notes": note + " Obstacle dan LOS/NLOS tidak tercantum; tidak diestimasi.",
                })
                records["latency_tests"].append({
                    **common, "node_id": "NODE-SLAVE-01", "distance_meter": distance, "trial_number": trial,
                    "timestamp_send_ms": None, "timestamp_receive_ms": None, "packet_sent": ping["sent"],
                    "packet_received": ping["received"], "network_mode": "HaLow only",
                    "latency_ms": ping["average"], "jitter_ms": ping["jitter"],
                    "packet_loss_percent": ping["loss"], "average_latency": ping["average"],
                    "minimum_latency": ping["minimum"], "maximum_latency": ping["maximum"],
                    "average_jitter": ping["jitter"], "notes": note,
                })
                records["throughput_tests"].append({
                    **common, "node_id": "NODE-SLAVE-01", "distance_meter": distance,
                    "data_sent_kb": transfer_kb, "data_received_kb": transfer_kb,
                    "transmission_time_second": 60, "rssi_dbm": rssi, "snr_db": snr,
                    "bitrate_kbps": throughput, "throughput_kbps": throughput,
                    "packet_delivery_ratio_percent": packet_success, "data_loss_percent": ping["loss"],
                    "notes": note + " Transfer TCP iperf dipakai sebagai data terkirim/diterima.",
                })
                records["interference_tests"].append({
                    **common, "scan_status": "not_scanned", "scan_start_mhz": 860, "scan_end_mhz": 930,
                    "noise_floor_dbm": noise, "strongest_interferer_frequency_mhz": None,
                    "strongest_interferer_power_dbm": None, "channel_occupancy_percent": None,
                    "sdr_device": None, "interference_evidence": None, "interference_level": None,
                    "interference_source": None, "distance_meter": distance, "rssi_dbm": rssi, "snr_db": snr,
                    "throughput_kbps": throughput, "latency_ms": ping["average"],
                    "packet_sent": ping["sent"], "packet_received": ping["received"],
                    "packet_loss_percent": ping["loss"], "throughput_degradation_percent": None,
                    "latency_increase_percent": None, "snr_degradation_db": None,
                    "notes": note + " Noise floor dari angka kedua pasangan RSSI; pemindaian SDR belum dilakukan.",
                })
    return records


def mysql_command(args: argparse.Namespace) -> list[str]:
    command = [args.mysql, "-h", args.host, "-P", str(args.port), "-u", args.user,
               "--default-character-set=utf8mb4", "-N", "-B"]
    if args.password:
        command.append(f"--password={args.password}")
    return command + [args.database]


def run_mysql(args: argparse.Namespace, sql: str) -> str:
    result = subprocess.run(mysql_command(args), input=sql, capture_output=True, text=True, encoding="utf-8")
    if result.returncode:
        raise RuntimeError(result.stderr.strip() or "Perintah MySQL gagal")
    return result.stdout


def import_sql(records: dict[str, list[dict[str, Any]]]) -> str:
    lines = ["SET NAMES utf8mb4;", "START TRANSACTION;"]
    for table, rows in records.items():
        lines.append(f"DELETE FROM `{table}` WHERE `environment_type` IN ('indoor','outdoor');")
        columns = list(rows[0])
        for row in rows:
            values = ", ".join(sql_value(row[column]) for column in columns)
            lines.append(f"INSERT INTO `{table}` ({', '.join(f'`{c}`' for c in columns)}) VALUES ({values});")
    return "\n".join(lines + ["COMMIT;"])


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--workbook", type=Path, required=True)
    parser.add_argument("--test-date", help="YYYY-MM-DD; default tanggal modifikasi workbook")
    parser.add_argument("--mysql", default=r"C:\xampp\mysql\bin\mysql.exe")
    parser.add_argument("--host", default="localhost")
    parser.add_argument("--port", type=int, default=3306)
    parser.add_argument("--database", default="wifi_holow_testing")
    parser.add_argument("--user", default="root")
    parser.add_argument("--password", default="")
    parser.add_argument("--apply", action="store_true")
    args = parser.parse_args()
    workbook = args.workbook.resolve()
    test_date = args.test_date or datetime.fromtimestamp(workbook.stat().st_mtime).strftime("%Y-%m-%d")
    records = build_records(workbook, test_date)
    print(f"Workbook: {workbook}\nTanggal database: {test_date}")
    for table, rows in records.items():
        print(f"{table}: {len(rows)} baris")
    if not args.apply:
        print("Validasi workbook berhasil; gunakan --apply untuk mengimpor.")
        return 0
    run_mysql(args, import_sql(records))
    query = " UNION ALL ".join(
        f"SELECT '{table}', COUNT(*) FROM `{table}` WHERE environment_type IN ('indoor','outdoor')"
        for table in TABLES
    )
    counts = dict(line.split("\t") for line in run_mysql(args, query + ";").splitlines())
    bad = {table: count for table, count in counts.items() if int(count) != len(records[table])}
    if bad:
        raise RuntimeError(f"Verifikasi jumlah gagal: {bad}")
    print(f"Impor berhasil; {sum(map(len, records.values()))} baris diverifikasi.")
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except Exception as error:
        print(f"ERROR: {error}", file=sys.stderr)
        raise SystemExit(1)
