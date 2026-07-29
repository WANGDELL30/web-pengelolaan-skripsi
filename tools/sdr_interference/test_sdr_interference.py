import json
import tempfile
import unittest
from pathlib import Path

import sdr_interference as tool


class SdrInterferenceTest(unittest.TestCase):
    def run_scenario(self, scenario: str):
        with tempfile.TemporaryDirectory() as temporary:
            root = Path(temporary)
            raw = root / "raw.csv"
            tool.simulate_rtl_power_csv(
                raw,
                center_mhz=923.5,
                span_mhz=4.0,
                bin_khz=10.0,
                sweeps=60,
                scenario=scenario,
            )
            settings = tool.AnalysisSettings(
                center_mhz=923.5,
                channel_bw_khz=1000.0,
                threshold_db=10.0,
                max_occupancy_percent=1.0,
                max_busy_sweep_percent=5.0,
                min_event_bins=2,
                calibration_offset_db=0.0,
                halow_state="off",
            )
            sweeps = tool.parse_rtl_power_csv(raw)
            summary, per_sweep, spectrum = tool.analyse_sweeps(
                sweeps, settings, raw, simulated=False
            )
            tool.write_outputs(root, summary, per_sweep, spectrum)
            saved = json.loads((root / "summary.json").read_text(encoding="utf-8"))
            self.assertEqual(saved["measurement"]["sweep_count"], 60)
            self.assertTrue((root / "report.html").is_file())
            return saved

    def test_clean_spectrum(self):
        result = self.run_scenario("clean")
        self.assertEqual(
            result["results"]["verdict"],
            "NO_SIGNIFICANT_INTERFERENCE_DETECTED",
        )
        self.assertLessEqual(result["results"]["channel_occupancy_percent"], 1.0)

    def test_busy_spectrum(self):
        result = self.run_scenario("busy")
        self.assertEqual(
            result["results"]["verdict"],
            "SIGNIFICANT_RF_ACTIVITY_DETECTED",
        )
        self.assertGreater(result["results"]["busy_sweep_percent"], 5.0)


if __name__ == "__main__":
    unittest.main()
