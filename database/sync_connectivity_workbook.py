#!/usr/bin/env python3
"""Synchronize the connectivity test tables from the authoritative workbook.

The workbook combines one test point across the connectivity, range, signal
penetration, latency, throughput, and interference tables. The second sheet is
the source for power consumption tests. This script intentionally updates the
existing rows in ID order and refuses to run when the row counts differ, so an
unrelated row cannot be overwritten silently.
"""

from __future__ import annotations

import argparse
import math
import os
import re
import subprocess
import sys
from datetime import datetime, timedelta
from pathlib import Path
from typing import Any
from xml.etree import ElementTree as ET
from zipfile import ZipFile


MAIN_NS = "http://schemas.openxmlformats.org/spreadsheetml/2006/main"
REL_NS = "http://schemas.openxmlformats.org/officeDocument/2006/relationships"
DEFAULT_WORKBOOK = "7. All Data Gabungan Pengujian Connectivity (1).xlsx"


def column_number(cell_reference: str) -> int:
    match = re.match(r"[A-Z]+", cell_reference)
    if not match:
        raise ValueError(f"Invalid cell reference: {cell_reference}")

    number = 0
    for character in match.group(0):
        number = (number * 26) + ord(character) - 64
    return number


def numeric_value(value: str | None) -> Any:
    if value is None or value == "":
        return None

    try:
        number = float(value)
        return int(number) if number.is_integer() else number
    except ValueError:
        return value


def read_workbook(path: Path) -> dict[str, list[dict[int, Any]]]:
    with ZipFile(path) as archive:
        shared_strings: list[str] = []
        if "xl/sharedStrings.xml" in archive.namelist():
            root = ET.fromstring(archive.read("xl/sharedStrings.xml"))
            shared_strings = [
                "".join(text.text or "" for text in item.iter(f"{{{MAIN_NS}}}t"))
                for item in root
            ]

        workbook = ET.fromstring(archive.read("xl/workbook.xml"))
        relationships = ET.fromstring(archive.read("xl/_rels/workbook.xml.rels"))
        targets = {item.attrib["Id"]: item.attrib["Target"] for item in relationships}
        sheets: dict[str, list[dict[int, Any]]] = {}

        sheet_collection = workbook.find(f"{{{MAIN_NS}}}sheets")
        if sheet_collection is None:
            raise ValueError("Workbook does not contain sheets")

        for sheet in sheet_collection:
            relationship_id = sheet.attrib[f"{{{REL_NS}}}id"]
            target = targets[relationship_id].lstrip("/")
            if not target.startswith("xl/"):
                target = f"xl/{target}"

            worksheet = ET.fromstring(archive.read(target))
            rows: list[dict[int, Any]] = []
            for row in worksheet.findall(
                f".//{{{MAIN_NS}}}sheetData/{{{MAIN_NS}}}row"
            ):
                values: dict[int, Any] = {}
                for cell in row.findall(f"{{{MAIN_NS}}}c"):
                    cell_type = cell.attrib.get("t")
                    raw_element = cell.find(f"{{{MAIN_NS}}}v")
                    raw_value = raw_element.text if raw_element is not None else None

                    if cell_type == "s" and raw_value is not None:
                        value: Any = shared_strings[int(raw_value)]
                    elif cell_type == "inlineStr":
                        value = "".join(
                            text.text or ""
                            for text in cell.iter(f"{{{MAIN_NS}}}t")
                        )
                    else:
                        value = numeric_value(raw_value)

                    values[column_number(cell.attrib["r"])] = value

                if any(value not in (None, "") for value in values.values()):
                    rows.append(values)

            sheets[sheet.attrib["name"]] = rows

        return sheets


def excel_date(value: Any) -> str:
    if not isinstance(value, (int, float)):
        raise ValueError(f"Expected an Excel date number, got {value!r}")
    return (datetime(1899, 12, 30) + timedelta(days=value)).strftime("%Y-%m-%d")


def environment(value: Any) -> str:
    normalized = str(value or "").strip().lower()
    mapping = {
        "indoor": "indoor",
        "mountainous": "gunung",
        "open field": "lapangan",
        "coastal": "pantai",
        "outdoor residential": "outdoor",
    }
    if normalized not in mapping:
        raise ValueError(f"Unsupported environment: {value!r}")
    return mapping[normalized]


def nullable_direction(value: Any) -> str | None:
    direction = str(value or "").strip().lower()
    return None if direction in ("", "n/a") else direction


def node_type(value: Any) -> str:
    return "master" if "MASTER" in str(value or "").upper() else "slave"


def split_rssi_snr(value: Any) -> tuple[float | int | None, float | int | None]:
    text = str(value or "").strip()
    if not text or text.upper() == "N/A" or "/" not in text:
        return None, None
    rssi, snr = text.split("/", 1)
    return numeric_value(rssi), numeric_value(snr)


def packet_lost(sent: Any, received: Any) -> int | None:
    if not isinstance(sent, (int, float)) or not isinstance(received, (int, float)):
        return None
    if received < 0 or received > sent:
        return None
    return int(sent - received)


def snr_loss(before: Any, after: Any) -> float | None:
    if not isinstance(before, (int, float)) or not isinstance(after, (int, float)):
        return None
    return round(float(before) - float(after), 2)


def fspl(frequency_mhz: Any, distance_meter: Any) -> float | None:
    if not isinstance(frequency_mhz, (int, float)) or not isinstance(
        distance_meter, (int, float)
    ):
        return None
    if frequency_mhz <= 0 or distance_meter <= 0:
        return None
    distance_km = distance_meter / 1000
    return round(32.44 + (20 * math.log10(frequency_mhz)) + (20 * math.log10(distance_km)), 2)


def build_records(sheets: dict[str, list[dict[int, Any]]]) -> dict[str, list[dict[str, Any]]]:
    if "Combined data" not in sheets or "Power Consumtion" not in sheets:
        raise ValueError("Expected sheets 'Combined data' and 'Power Consumtion'")

    combined = sheets["Combined data"][1:]
    power_rows = sheets["Power Consumtion"][1:]
    if len(combined) != 16:
        raise ValueError(f"Expected 16 combined rows, found {len(combined)}")
    if len(power_rows) != 8:
        raise ValueError(f"Expected 8 power rows, found {len(power_rows)}")

    records: dict[str, list[dict[str, Any]]] = {
        "connectivity_tests": [],
        "range_tests": [],
        "signal_penetration_tests": [],
        "latency_tests": [],
        "throughput_tests": [],
        "interference_tests": [],
        "power_consumption_tests": [],
    }

    for expected_number, row in enumerate(combined, start=1):
        if row.get(1) != expected_number:
            raise ValueError(
                f"Combined row order mismatch: expected {expected_number}, got {row.get(1)!r}"
            )

        date = excel_date(row.get(2))
        mapped_environment = environment(row.get(4))
        sent = row.get(13)
        received = row.get(14)
        remarks = row.get(39)

        records["connectivity_tests"].append(
            {
                "test_date": date,
                "location_name": row.get(3),
                "environment_type": mapped_environment,
                "distance_meter": row.get(9),
                "node_id": row.get(7),
                "node_type": node_type(row.get(7)),
                "connection_status": row.get(8),
                "rssi_dbm": row.get(11),
                "snr_db": row.get(12),
                "packet_sent": sent,
                "packet_received": received,
                "packet_lost": packet_lost(sent, received),
                "packet_loss_percent": row.get(15),
                "packet_success_rate": row.get(16),
                "test_duration_second": 60,
                "notes": remarks,
            }
        )

        distance = row.get(9)
        frequency = row.get(10)
        records["range_tests"].append(
            {
                "test_date": date,
                "location_name": row.get(3),
                "environment_type": mapped_environment,
                "test_point_code": row.get(5),
                "direction": nullable_direction(row.get(6)),
                "distance_actual_meter": distance,
                "distance_km": round(distance / 1000, 4) if distance else None,
                "frequency_mhz": frequency,
                "rssi_dbm": row.get(11),
                "snr_db": row.get(12),
                "bitrate_kbps": row.get(17),
                "connection_status": row.get(8),
                "fspl_db": fspl(frequency, distance),
                "status_result": row.get(18),
                "notes": remarks,
            }
        )

        records["signal_penetration_tests"].append(
            {
                "test_date": date,
                "location_name": row.get(3),
                "environment_type": mapped_environment,
                "obstacle_type": str(row.get(19) or "").lower(),
                "condition_type": row.get(20),
                "distance_meter": distance,
                "rssi_before_dbm": row.get(21),
                "rssi_after_dbm": row.get(22),
                # Column 23 has no header in the source file, but its position
                # and values identify it as SNR Before (dB).
                "snr_before_db": row.get(23),
                "snr_after_db": row.get(24),
                "packet_sent": sent,
                "packet_received": received,
                "bitrate_kbps": row.get(37),
                "rssi_loss": row.get(25),
                "snr_loss": snr_loss(row.get(23), row.get(24)),
                "packet_loss_percent": row.get(15),
                "penetration_loss_db": row.get(25),
                "notes": remarks,
            }
        )

        records["latency_tests"].append(
            {
                "test_date": date,
                "location_name": row.get(3),
                "environment_type": mapped_environment,
                "node_id": row.get(7),
                "distance_meter": distance,
                "trial_number": 1,
                "packet_sent": sent,
                "packet_received": received,
                "network_mode": "HaLow only",
                "latency_ms": row.get(26),
                "jitter_ms": row.get(27),
                "packet_loss_percent": row.get(15),
                "average_latency": row.get(26),
                "minimum_latency": row.get(26),
                "maximum_latency": row.get(26),
                "average_jitter": row.get(27),
                "notes": remarks,
            }
        )

        records["throughput_tests"].append(
            {
                "test_date": date,
                "location_name": row.get(3),
                "environment_type": mapped_environment,
                "node_id": row.get(7),
                "distance_meter": distance,
                "data_sent_kb": row.get(29),
                "data_received_kb": row.get(30),
                "transmission_time_second": row.get(31),
                "rssi_dbm": row.get(11),
                "snr_db": row.get(12),
                "bitrate_kbps": row.get(17),
                "throughput_kbps": row.get(32),
                "packet_delivery_ratio_percent": row.get(33),
                "data_loss_percent": row.get(34),
                "notes": remarks,
            }
        )

        records["interference_tests"].append(
            {
                "test_date": date,
                "location_name": row.get(3),
                "environment_type": mapped_environment,
                "interference_level": str(row.get(35) or "").lower(),
                "interference_source": row.get(36),
                "distance_meter": distance,
                "rssi_dbm": row.get(11),
                "snr_db": row.get(12),
                "throughput_kbps": row.get(37),
                "latency_ms": row.get(38),
                "packet_sent": sent,
                "packet_received": received,
                "packet_loss_percent": row.get(15),
                "throughput_degradation_percent": None,
                "latency_increase_percent": None,
                "snr_degradation_db": None,
                "notes": remarks,
            }
        )

    for expected_number, row in enumerate(power_rows, start=1):
        if row.get(1) != expected_number:
            raise ValueError(
                f"Power row order mismatch: expected {expected_number}, got {row.get(1)!r}"
            )
        rssi, snr = split_rssi_snr(row.get(9))
        records["power_consumption_tests"].append(
            {
                "test_date": excel_date(row.get(2)),
                "device_id": row.get(3),
                "device_type": str(row.get(4) or "").lower(),
                "battery_voltage_v": row.get(5),
                "current_a": row.get(6),
                "test_duration_hour": row.get(7),
                "battery_capacity_mah": None,
                "cpu_usage_percent": row.get(8),
                "ram_usage_percent": None,
                "cpu_temperature_c": None,
                "rssi_dbm": rssi,
                "snr_db": snr,
                "power_w": row.get(10),
                "energy_wh": row.get(11),
                "battery_capacity_wh": None,
                "estimated_runtime_hour": None,
                "estimated_runtime_day": None,
                "result": row.get(12),
                "notes": row.get(13),
            }
        )

    return records


def sql_value(value: Any) -> str:
    if value is None:
        return "NULL"
    if isinstance(value, bool):
        return "1" if value else "0"
    if isinstance(value, (int, float)):
        return format(value, ".15g")
    return "'" + str(value).replace("\\", "\\\\").replace("'", "''") + "'"


def mysql_command(arguments: argparse.Namespace) -> list[str]:
    command = [
        arguments.mysql,
        f"--host={arguments.host}",
        f"--port={arguments.port}",
        f"--user={arguments.user}",
        "--default-character-set=utf8mb4",
        "--batch",
        "--raw",
        "--skip-column-names",
    ]
    if arguments.password:
        command.append(f"--password={arguments.password}")
    command.append(arguments.database)
    return command


def existing_ids(arguments: argparse.Namespace, table: str) -> list[int]:
    result = subprocess.run(
        mysql_command(arguments) + ["--execute", f"SELECT id FROM `{table}` ORDER BY id"],
        check=False,
        capture_output=True,
        text=True,
        encoding="utf-8",
    )
    if result.returncode != 0:
        raise RuntimeError(result.stderr.strip() or f"Unable to query {table}")
    return [int(line) for line in result.stdout.splitlines() if line.strip()]


def values_match(expected: Any, actual: str) -> bool:
    if expected is None:
        return actual == "NULL"
    if isinstance(expected, (int, float)):
        try:
            return math.isclose(float(actual), float(expected), rel_tol=0, abs_tol=0.005)
        except ValueError:
            return False
    return actual == str(expected)


def verify_database(
    arguments: argparse.Namespace, records: dict[str, list[dict[str, Any]]]
) -> int:
    checked_values = 0
    mismatches: list[str] = []

    for table, table_records in records.items():
        columns = list(table_records[0].keys())
        select_columns = ", ".join(f"`{column}`" for column in columns)
        result = subprocess.run(
            mysql_command(arguments)
            + ["--execute", f"SELECT {select_columns} FROM `{table}` ORDER BY `id`"],
            check=False,
            capture_output=True,
            text=True,
            encoding="utf-8",
        )
        if result.returncode != 0:
            raise RuntimeError(result.stderr.strip() or f"Unable to verify {table}")

        database_rows = [line.split("\t") for line in result.stdout.splitlines()]
        if len(database_rows) != len(table_records):
            mismatches.append(
                f"{table}: expected {len(table_records)} rows, found {len(database_rows)}"
            )
            continue

        for row_number, (expected_row, actual_row) in enumerate(
            zip(table_records, database_rows), start=1
        ):
            if len(actual_row) != len(columns):
                mismatches.append(
                    f"{table} row {row_number}: expected {len(columns)} columns, "
                    f"found {len(actual_row)}"
                )
                continue
            for column, actual in zip(columns, actual_row):
                expected = expected_row[column]
                checked_values += 1
                if not values_match(expected, actual):
                    mismatches.append(
                        f"{table} row {row_number} {column}: "
                        f"expected {expected!r}, found {actual!r}"
                    )

    if mismatches:
        preview = "\n".join(mismatches[:20])
        remainder = len(mismatches) - 20
        suffix = f"\n...and {remainder} more" if remainder > 0 else ""
        raise RuntimeError(f"Database verification failed:\n{preview}{suffix}")

    return checked_values


def build_sql(
    arguments: argparse.Namespace, records: dict[str, list[dict[str, Any]]]
) -> str:
    statements = [
        "SET NAMES utf8mb4;",
        "UPDATE `range_tests` SET `environment_type` = NULL "
        "WHERE `environment_type` = '';",
        "ALTER TABLE `range_tests` MODIFY `environment_type` "
        "ENUM('lapangan','hangar','pantai','gunung','indoor','outdoor') NULL;",
        "ALTER TABLE `power_consumption_tests` ADD COLUMN IF NOT EXISTS `result` "
        "VARCHAR(30) NULL AFTER `estimated_runtime_day`;",
        "START TRANSACTION;",
    ]

    for table, table_records in records.items():
        ids = existing_ids(arguments, table)
        if len(ids) != len(table_records):
            raise RuntimeError(
                f"{table} has {len(ids)} rows, but the workbook has {len(table_records)}. "
                "No data was changed."
            )

        for record_id, record in zip(ids, table_records):
            assignments = ", ".join(
                f"`{column}` = {sql_value(value)}" for column, value in record.items()
            )
            statements.append(f"UPDATE `{table}` SET {assignments} WHERE `id` = {record_id};")

    statements.append("COMMIT;")
    return "\n".join(statements) + "\n"


def parse_arguments() -> argparse.Namespace:
    repository_root = Path(__file__).resolve().parents[1]
    default_mysql = Path(r"C:\xampp\mysql\bin\mysql.exe")
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument(
        "--workbook",
        type=Path,
        default=repository_root / DEFAULT_WORKBOOK,
        help="Authoritative XLSX workbook",
    )
    parser.add_argument(
        "--mysql",
        default=str(default_mysql) if default_mysql.exists() else "mysql",
        help="Path to the mysql client",
    )
    parser.add_argument("--host", default=os.getenv("DB_HOST", "localhost"))
    parser.add_argument("--port", type=int, default=int(os.getenv("DB_PORT", "3306")))
    parser.add_argument("--database", default=os.getenv("DB_NAME", "wifi_holow_testing"))
    parser.add_argument("--user", default=os.getenv("DB_USER", "root"))
    parser.add_argument("--password", default=os.getenv("DB_PASS", ""))
    parser.add_argument(
        "--apply",
        action="store_true",
        help="Apply the synchronization; without this flag only validate and preview",
    )
    return parser.parse_args()


def main() -> int:
    arguments = parse_arguments()
    workbook_path = arguments.workbook.resolve()
    if not workbook_path.is_file():
        raise FileNotFoundError(workbook_path)

    records = build_records(read_workbook(workbook_path))
    sql = build_sql(arguments, records)

    print(f"Workbook: {workbook_path}")
    for table, rows in records.items():
        print(f"  {table}: {len(rows)} rows")

    if not arguments.apply:
        print("Validation passed. Re-run with --apply to synchronize the database.")
        return 0

    result = subprocess.run(
        mysql_command(arguments),
        input=sql,
        check=False,
        capture_output=True,
        text=True,
        encoding="utf-8",
    )
    if result.returncode != 0:
        raise RuntimeError(result.stderr.strip() or "MySQL synchronization failed")

    checked_values = verify_database(arguments, records)
    print(
        "Database synchronization completed successfully; "
        f"verified {checked_values} mapped values."
    )
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except Exception as error:
        print(f"ERROR: {error}", file=sys.stderr)
        raise SystemExit(1)
