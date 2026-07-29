# SDR Interference Measurement for Wi-Fi HaLow

Tool ini menggunakan RTL-SDR secara **receive-only** untuk mengukur aktivitas RF
di sekitar kanal Wi-Fi HaLow. Default-nya mengikuti hasil perangkat proyek:

- S1G channel: 43
- Center frequency: 923.5 MHz
- Target primary bandwidth: 1 MHz
- Scan span: 921.5–925.5 MHz

## Batas kesimpulan

SDR tidak bisa membuktikan spektrum “selamanya kosong”. Kesimpulan yang sah:

> Tidak terdeteksi interferensi signifikan di atas ambang ukur selama durasi,
> lokasi, antena, gain, bandwidth, dan sensitivitas pengukuran yang dicatat.

Untuk baseline interferensi eksternal, **matikan sementara pemancar HaLow pada
kanal yang diuji**. Jika pemancar aktif, energi HaLow sendiri tidak dapat
dipisahkan secara andal dari interferensi hanya menggunakan power spectrum.

## Perangkat

- RTL-SDR dengan tuner yang mencakup 923.5 MHz.
- Antena 900/915 MHz yang sesuai.
- `rtl_power` dari paket RTL-SDR.
- Python 3.10 atau lebih baru; tidak ada package Python tambahan.

Pada Windows, pastikan driver SDR sudah menggunakan WinUSB dan perintah berikut
berhasil:

```powershell
rtl_test -t
rtl_power -h
```

Jika `rtl_power` tidak ada di PATH, gunakan `--rtl-power-path`.

## Uji software tanpa SDR

Data simulasi hanya untuk menguji parser dan laporan:

```powershell
python tools\sdr_interference\sdr_interference.py simulate `
  --scenario clean `
  --output-dir tools\sdr_interference\demo_clean
```

Laporan simulasi diberi verdict
`SIMULATION_ONLY_NOT_EMPIRICAL_EVIDENCE` dan tidak boleh dipakai sebagai bukti
pengukuran.

## Pengukuran baseline yang disarankan

1. Letakkan antena SDR di lokasi receiver/master.
2. Pastikan gain, antena, kabel, posisi, dan orientasi tidak berubah.
3. Matikan sementara radio HaLow pada kanal 923.5 MHz.
4. Jalankan pengukuran minimal 15 menit:

```powershell
python tools\sdr_interference\sdr_interference.py scan `
  --center-mhz 923.5 `
  --channel-bw-khz 1000 `
  --span-mhz 4 `
  --bin-khz 10 `
  --duration 15m `
  --integration 1s `
  --gain 20 `
  --halow-state off
```

Contoh dengan lokasi executable eksplisit:

```powershell
python tools\sdr_interference\sdr_interference.py scan `
  --rtl-power-path "C:\rtl-sdr\rtl_power.exe" `
  --center-mhz 923.5 `
  --duration 15m `
  --halow-state off
```

Untuk laporan yang lebih kuat, lakukan tiga pengulangan pada waktu berbeda
(misalnya pagi, siang, malam), masing-masing 30–60 menit.

## Pengukuran saat HaLow aktif

Pengukuran kedua dapat dilakukan untuk menunjukkan adanya sinyal sistem:

```powershell
python tools\sdr_interference\sdr_interference.py scan `
  --center-mhz 923.5 `
  --duration 15m `
  --gain 20 `
  --halow-state on
```

Verdict sengaja menjadi `INCONCLUSIVE_HALOW_TRANSMITTER_ACTIVE`, karena
aktivitas di kanal dapat berasal dari sistem HaLow sendiri.

## Output

Setiap scan nyata menghasilkan folder bertanggal pada `sdr_results`:

- `rtl_power_raw.csv`: data mentah yang tidak diubah.
- `acquisition.json`: parameter dan command akuisisi.
- `summary.json`: variabel hasil dan verdict.
- `sweep_metrics.csv`: noise, occupancy, peak, dan event per detik.
- `spectrum_summary.csv`: median, P95, dan maksimum per frekuensi.
- `report.html`: laporan dan grafik yang dapat dibuka di browser.
- `web_form_values.json`: nilai ringkas untuk form Interference Test; file ini
  tidak dibuat pada mode simulasi.
- `rtl_power.log`: log perangkat dan akuisisi.

Variabel utama:

- `noise_floor_median_dbm_per_bin`
- `channel_median_dbm_per_bin`
- `peak_power_dbm_per_bin`
- `peak_frequency_mhz`
- `p95_excess_above_noise_db`
- `maximum_excess_above_noise_db`
- `channel_occupancy_percent`
- `busy_sweep_percent`
- `detected_event_count`
- `verdict`

Default keputusan “tidak terdeteksi interferensi signifikan”:

- Pemancar HaLow dinyatakan `off`.
- Bin dianggap aktif jika minimal 10 dB di atas noise lokal.
- Occupancy kanal maksimal 1%.
- Busy sweep maksimal 5%.
- Event minimal dua bin frekuensi bersebelahan.

Ambang tersebut harus dicantumkan di metode penelitian dan dapat diubah melalui
opsi CLI. Nilai daya RTL-SDR tanpa kalibrasi adalah estimasi relatif, bukan
pengukuran dBm yang traceable.
