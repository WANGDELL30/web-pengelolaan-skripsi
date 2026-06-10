# Panduan Data dan Perhitungan Pengujian Bab 4

Dokumen ini disusun berdasarkan field form pada aplikasi pengelolaan data pengujian WiFi HaLow. Panduan ini dapat digunakan sebagai acuan penulisan Bab 4, terutama untuk menjelaskan data yang dikumpulkan, sumber pengambilan data, rumus perhitungan, dan cara membaca hasil pengujian.

## 1. Ketentuan Umum Pengambilan Data

Setiap satu baris data pada aplikasi dianggap sebagai satu kali pengujian atau satu titik pengujian. Agar hasil Bab 4 konsisten, gunakan identitas pengujian yang sama untuk seluruh modul, terutama tanggal, lokasi, environment, node, titik uji, dan jarak.

| Data umum | Keterangan | Contoh |
| --- | --- | --- |
| `test_date` | Tanggal pelaksanaan pengujian | `2026-06-08` |
| `location_name` | Nama lokasi pengujian | `Lapangan Kampus` |
| `environment_type` | Jenis lingkungan | `lapangan`, `hangar`, `pantai`, `gunung`, `indoor`, `outdoor` |
| `node_id` / `test_point_code` | Identitas node atau titik uji | `NODE-01`, `TP-001` |
| `distance_meter` / `distance_actual_meter` | Jarak master ke slave atau jarak titik uji | `50 m` |
| `rssi_dbm` | Kekuatan sinyal dalam dBm | `-67.25 dBm` |
| `snr_db` | Signal-to-noise ratio dalam dB | `18.50 dB` |
| `notes` | Catatan kondisi lapangan | Cuaca, obstacle, posisi antena |

Catatan pembacaan nilai:

- RSSI bernilai negatif. Nilai yang lebih mendekati 0 berarti sinyal lebih kuat, misalnya `-55 dBm` lebih baik daripada `-80 dBm`.
- SNR yang lebih tinggi menunjukkan kualitas sinyal terhadap noise yang lebih baik.
- Packet loss dan data loss yang lebih rendah menunjukkan link lebih andal.
- Latency dan jitter yang lebih rendah menunjukkan respon jaringan lebih cepat dan stabil.
- Throughput yang lebih tinggi menunjukkan kapasitas transfer data lebih baik.

Validasi data sebelum dianalisis:

- `packet_received` tidak boleh lebih besar dari `packet_sent`.
- `data_received_kb` tidak boleh lebih besar dari `data_sent_kb`.
- `transmission_time_second` harus lebih dari 0 untuk menghitung throughput.
- Latitude harus berada pada rentang `-90` sampai `90`.
- Longitude harus berada pada rentang `-180` sampai `180`.
- Untuk perbandingan antar jarak, gunakan posisi antena, daya pancar, dan konfigurasi perangkat yang sama.

## 2. Connectivity Test

### Tujuan Pengujian

Connectivity test digunakan untuk mengetahui keberhasilan koneksi antara node master dan slave, kualitas sinyal dasar, serta tingkat kehilangan paket selama durasi pengujian tertentu.

### Data yang Diambil dari Form

| Field | Data yang dicatat | Sumber data |
| --- | --- | --- |
| `test_date` | Tanggal pengujian | Jadwal pengujian |
| `location_name` | Lokasi pengujian | Catatan lapangan |
| `environment_type` | Jenis lingkungan | Observasi lapangan |
| `node_id` | Identitas node | Label perangkat |
| `node_type` | Jenis node, master atau slave | Konfigurasi perangkat |
| `connection_status` | Status koneksi | Hasil ping/status link/observasi |
| `test_duration_second` | Lama pengujian | Durasi pengamatan |
| `rssi_dbm` | Nilai RSSI | Status perangkat/router/API |
| `snr_db` | Nilai SNR | Status perangkat/router/API |
| `packet_sent` | Jumlah paket dikirim | Ping atau packet test |
| `packet_received` | Jumlah paket diterima | Ping atau packet test |
| `notes` | Catatan pengujian | Observasi lapangan |

### Data Hasil Perhitungan

| Field hasil | Keterangan |
| --- | --- |
| `packet_lost` | Jumlah paket hilang |
| `packet_loss_percent` | Persentase paket hilang |
| `packet_success_rate` | Persentase paket berhasil diterima |

### Rumus Perhitungan

```text
packet_lost = packet_sent - packet_received

packet_loss_percent = ((packet_sent - packet_received) / packet_sent) x 100

packet_success_rate = (packet_received / packet_sent) x 100
```

Jika `packet_sent = 0`, aplikasi menyimpan nilai persentase sebagai `0` untuk menghindari pembagian dengan nol.

### Contoh Penyajian Bab 4

| Titik/Node | Status | RSSI (dBm) | SNR (dB) | Packet Sent | Packet Received | Packet Loss (%) | Success Rate (%) |
| --- | --- | ---: | ---: | ---: | ---: | ---: | ---: |
| NODE-01 | connected | -62.00 | 22.50 | 1000 | 992 | 0.80 | 99.20 |

### Cara Membaca Hasil

Connectivity dinilai baik apabila status koneksi `connected`, packet loss rendah, success rate tinggi, RSSI masih berada pada batas penerimaan perangkat, dan SNR cukup tinggi. Jika status `intermittent` atau `disconnected`, bagian analisis perlu menjelaskan penyebab lapangan seperti jarak, obstacle, arah antena, atau gangguan sinyal.

## 3. Range Test

### Tujuan Pengujian

Range test digunakan untuk mengetahui jangkauan komunikasi antara master dan slave pada beberapa titik uji, serta melihat hubungan jarak terhadap RSSI, SNR, bitrate, FSPL, signal margin, dan status kualitas link.

### Data yang Diambil dari Form

| Field | Data yang dicatat | Sumber data |
| --- | --- | --- |
| `test_date` | Tanggal pengujian | Jadwal pengujian |
| `location_name` | Lokasi pengujian | Catatan lapangan |
| `environment_type` | Jenis lingkungan | Observasi lapangan |
| `test_point_code` | Kode titik pengujian | Penomoran titik, contoh `TP-001` |
| `direction` | Arah titik uji dari master | Kompas/GPS/denah |
| `master_gps_latitude` | Latitude master | GPS master |
| `master_gps_longitude` | Longitude master | GPS master |
| `gps_latitude` | Latitude slave | GPS slave |
| `gps_longitude` | Longitude slave | GPS slave |
| `coordinate_x_meter` | Selisih posisi sumbu X | Otomatis dari GPS atau input manual |
| `coordinate_y_meter` | Selisih posisi sumbu Y | Otomatis dari GPS atau input manual |
| `coordinate_z_meter` | Elevasi/selisih tinggi | Input lapangan |
| `distance_actual_meter` | Jarak master-slave | Otomatis dari GPS atau input manual |
| `frequency_mhz` | Frekuensi pengujian | Konfigurasi radio, default `915 MHz` |
| `rssi_dbm` | RSSI pada titik uji | Status perangkat/router/API |
| `snr_db` | SNR pada titik uji | Status perangkat/router/API |
| `bitrate_kbps` | Bitrate link | Status perangkat/iperf |
| `connection_status` | Status koneksi | Ping/status link/observasi |
| `receiver_sensitivity_dbm` | Sensitivitas receiver | Spesifikasi/perangkat, default `-90 dBm` |
| `photo_video_link` | Bukti visual lokasi | Dokumentasi lapangan |
| `notes` | Catatan pengujian | Observasi lapangan |

### Data Hasil Perhitungan

| Field hasil | Keterangan |
| --- | --- |
| `distance_actual_meter` | Jarak 2D aktual master ke slave |
| `distance_3d_meter` | Jarak 3D dengan memperhitungkan elevasi |
| `distance_km` | Jarak dalam kilometer |
| `fspl_db` | Free Space Path Loss |
| `signal_margin` | Selisih RSSI terhadap sensitivitas receiver |
| `status_result` | Kategori hasil: `good`, `moderate`, atau `poor` |

### Rumus Perhitungan Jarak GPS

Jika koordinat GPS master dan slave tersedia, aplikasi memakai rumus Haversine.

```text
R = 6371000 meter

dlat = lat_slave - lat_master
dlng = lng_slave - lng_master

a = sin(dlat / 2)^2 + cos(lat_master) x cos(lat_slave) x sin(dlng / 2)^2
c = 2 x atan2(sqrt(a), sqrt(1 - a))

distance_actual_meter = R x c
```

Semua latitude dan longitude pada rumus diubah ke radian.

### Rumus Perhitungan Jarak Manual

Jika GPS tidak tersedia, jarak dapat dihitung dari koordinat lokal.

```text
distance_2d_meter = sqrt(x^2 + y^2)

distance_3d_meter = sqrt(x^2 + y^2 + z^2)

distance_km = distance_actual_meter / 1000
```

### Rumus FSPL dan Signal Margin

```text
fspl_db = 32.44 + 20log10(frequency_mhz) + 20log10(distance_km)

signal_margin = rssi_dbm - receiver_sensitivity_dbm
```

Jika `distance_km = 0`, aplikasi menyimpan `fspl_db = 0`.

### Aturan Status Range pada Aplikasi

```text
Jika connection_status = disconnected:
    status_result = poor

Jika connection_status bukan disconnected dan snr_db > 20:
    status_result = good

Jika connection_status bukan disconnected dan 10 <= snr_db <= 20:
    status_result = moderate

Jika connection_status bukan disconnected dan snr_db < 10:
    status_result = poor
```

### Contoh Penyajian Bab 4

| Titik | Jarak (m) | RSSI (dBm) | SNR (dB) | Bitrate (kbps) | FSPL (dB) | Margin (dB) | Status |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | --- |
| TP-001 | 50.00 | -61.25 | 24.10 | 850.00 | 101.92 | 28.75 | good |

### Cara Membaca Hasil

Analisis range dapat dibuat dengan membandingkan jarak terhadap RSSI, SNR, bitrate, dan status. Titik jangkauan efektif dapat ditentukan dari jarak terjauh yang masih memiliki status `good` atau masih memenuhi kriteria koneksi penelitian. Jika RSSI melemah dan SNR turun saat jarak bertambah, hal tersebut menunjukkan redaman propagasi meningkat.

## 4. Penetration Test

### Tujuan Pengujian

Penetration test digunakan untuk mengetahui penurunan kualitas sinyal ketika sinyal melewati obstacle seperti dinding, bangunan, pohon, kendaraan, hangar, bukit, atau kondisi tanpa obstacle.

### Data yang Diambil dari Form

| Field | Data yang dicatat | Sumber data |
| --- | --- | --- |
| `test_date` | Tanggal pengujian | Jadwal pengujian |
| `location_name` | Lokasi pengujian | Catatan lapangan |
| `obstacle_type` | Jenis penghalang | Observasi lapangan |
| `condition_type` | Kondisi LOS atau NLOS | Observasi jalur sinyal |
| `distance_meter` | Jarak pengujian | GPS/meteran/estimasi lapangan |
| `rssi_before_dbm` | RSSI sebelum obstacle | Status perangkat sebelum obstacle |
| `rssi_after_dbm` | RSSI setelah obstacle | Status perangkat setelah obstacle |
| `snr_before_db` | SNR sebelum obstacle | Status perangkat sebelum obstacle |
| `snr_after_db` | SNR setelah obstacle | Status perangkat setelah obstacle |
| `packet_sent` | Jumlah paket dikirim | Ping/packet test |
| `packet_received` | Jumlah paket diterima | Ping/packet test |
| `bitrate_kbps` | Bitrate link | Status perangkat/iperf |
| `notes` | Catatan pengujian | Material obstacle, posisi antena |

### Data Hasil Perhitungan

| Field hasil | Keterangan |
| --- | --- |
| `rssi_loss` | Penurunan RSSI akibat obstacle |
| `snr_loss` | Penurunan SNR akibat obstacle |
| `packet_loss_percent` | Persentase paket hilang |
| `penetration_loss_db` | Nilai loss penetrasi, sama dengan `rssi_loss` |

### Rumus Perhitungan

```text
rssi_loss = rssi_before_dbm - rssi_after_dbm

snr_loss = snr_before_db - snr_after_db

packet_loss_percent = ((packet_sent - packet_received) / packet_sent) x 100

penetration_loss_db = rssi_loss
```

Contoh RSSI:

```text
rssi_before_dbm = -60 dBm
rssi_after_dbm  = -75 dBm

rssi_loss = -60 - (-75) = 15 dB
```

### Contoh Penyajian Bab 4

| Obstacle | Kondisi | Jarak (m) | RSSI Before | RSSI After | RSSI Loss (dB) | SNR Loss (dB) | Packet Loss (%) |
| --- | --- | ---: | ---: | ---: | ---: | ---: | ---: |
| wall | NLOS | 20.00 | -60.00 | -75.00 | 15.00 | 7.50 | 3.20 |

### Cara Membaca Hasil

Obstacle dengan `rssi_loss` dan `snr_loss` paling besar menunjukkan hambatan yang paling kuat terhadap sinyal. Jika packet loss meningkat setelah sinyal melewati obstacle, maka obstacle tersebut tidak hanya melemahkan sinyal tetapi juga menurunkan keandalan komunikasi.

## 5. Latency Test

### Tujuan Pengujian

Latency test digunakan untuk mengetahui waktu tempuh paket atau request dari pengirim ke penerima, stabilitas waktu respon melalui jitter, dan packet loss pada mode jaringan tertentu.

### Data yang Diambil dari Form

| Field | Data yang dicatat | Sumber data |
| --- | --- | --- |
| `test_date` | Tanggal pengujian | Jadwal pengujian |
| `location_name` | Lokasi pengujian | Catatan lapangan |
| `environment_type` | Jenis lingkungan | Observasi lapangan |
| `node_id` | Identitas node | Label perangkat |
| `distance_meter` | Jarak master-slave | GPS/meteran |
| `trial_number` | Nomor percobaan | Urutan pengujian |
| `timestamp_send_ms` | Waktu paket dikirim | Timestamp sistem |
| `timestamp_receive_ms` | Waktu paket diterima | Timestamp sistem |
| `packet_sent` | Jumlah paket dikirim | Ping/request test |
| `packet_received` | Jumlah paket diterima | Ping/request test |
| `network_mode` | Mode jaringan | `HaLow only` atau `HaLow + VSAT` |
| `latency_ms` | Latency hasil ukur | Ping/request/API |
| `jitter_ms` | Jitter hasil ukur | Ping/olah data manual |
| `notes` | Catatan pengujian | Observasi lapangan |

### Data Hasil Perhitungan

| Field hasil | Keterangan |
| --- | --- |
| `latency_ms` | Jika kosong, dihitung dari timestamp receive - send |
| `packet_loss_percent` | Persentase paket hilang |
| `average_latency` | Sama dengan latency pada satu record input |
| `minimum_latency` | Sama dengan latency pada satu record input |
| `maximum_latency` | Sama dengan latency pada satu record input |
| `average_jitter` | Sama dengan nilai `jitter_ms` yang diinput |

### Rumus Perhitungan Aplikasi

```text
latency_ms = timestamp_receive_ms - timestamp_send_ms

packet_loss_percent = ((packet_sent - packet_received) / packet_sent) x 100

average_latency = latency_ms
minimum_latency = latency_ms
maximum_latency = latency_ms
average_jitter = jitter_ms
```

Jika `latency_ms` diisi langsung dari hasil ping, aplikasi memakai nilai tersebut. Jika `latency_ms` kosong dan timestamp tersedia, aplikasi menghitung latency dari selisih timestamp.

### Rumus Jitter Jika Diolah dari Beberapa Sampel Ping

Jika dalam satu titik uji terdapat beberapa sampel latency, jitter dapat dihitung dari rata-rata selisih absolut antar latency berurutan.

```text
jitter_ms = (|L2 - L1| + |L3 - L2| + ... + |Ln - L(n-1)|) / (n - 1)
```

### Rumus Agregasi untuk Bab 4

```text
average_latency_per_titik = total_latency_ms / jumlah_sampel

minimum_latency_per_titik = nilai latency terkecil

maximum_latency_per_titik = nilai latency terbesar

average_jitter_per_titik = total_jitter_ms / jumlah_sampel
```

### Contoh Penyajian Bab 4

| Node | Mode | Jarak (m) | Trial | Latency (ms) | Jitter (ms) | Packet Loss (%) |
| --- | --- | ---: | ---: | ---: | ---: | ---: |
| NODE-01 | HaLow only | 50.00 | 1 | 42.50 | 3.20 | 0.00 |

### Cara Membaca Hasil

Latency yang rendah menunjukkan respon jaringan cepat. Jitter yang rendah menunjukkan latency stabil antar percobaan. Jika latency meningkat pada jarak yang lebih jauh atau pada mode jaringan tertentu, Bab 4 dapat menjelaskan bahwa kondisi tersebut dipengaruhi oleh kualitas link, jarak, obstacle, atau tambahan hop jaringan.

Sebagai kategori pembahasan opsional, dashboard aplikasi membaca latency kurang dari `100 ms` sebagai baik, `100 ms` sampai kurang dari `500 ms` sebagai sedang, dan `500 ms` atau lebih sebagai buruk.

## 6. Throughput Test

### Tujuan Pengujian

Throughput test digunakan untuk mengetahui kapasitas transfer data aktual pada jaringan, rasio data yang berhasil diterima, dan persentase kehilangan data.

### Data yang Diambil dari Form

| Field | Data yang dicatat | Sumber data |
| --- | --- | --- |
| `test_date` | Tanggal pengujian | Jadwal pengujian |
| `location_name` | Lokasi pengujian | Catatan lapangan |
| `environment_type` | Jenis lingkungan | Observasi lapangan |
| `node_id` | Identitas node | Label perangkat |
| `distance_meter` | Jarak master-slave | GPS/meteran |
| `data_sent_kb` | Total data dikirim | Iperf/file transfer |
| `data_received_kb` | Total data diterima | Iperf/file transfer |
| `transmission_time_second` | Durasi transfer | Iperf/stopwatch/log |
| `rssi_dbm` | RSSI saat transfer | Status perangkat/router/API |
| `snr_db` | SNR saat transfer | Status perangkat/router/API |
| `bitrate_kbps` | Bitrate link | Status perangkat/iperf |
| `notes` | Catatan pengujian | Observasi lapangan |

### Data Hasil Perhitungan

| Field hasil | Keterangan |
| --- | --- |
| `throughput_kbps` | Kecepatan transfer data aktual |
| `packet_delivery_ratio_percent` | Rasio data diterima terhadap data dikirim |
| `data_loss_percent` | Persentase data yang tidak diterima |

### Rumus Perhitungan

```text
throughput_kbps = (data_received_kb x 1024 x 8) / (transmission_time_second x 1000)

packet_delivery_ratio_percent = (data_received_kb / data_sent_kb) x 100

data_loss_percent = ((data_sent_kb - data_received_kb) / data_sent_kb) x 100
```

Jika `transmission_time_second = 0`, aplikasi menyimpan `throughput_kbps = 0`. Jika `data_sent_kb = 0`, aplikasi menyimpan PDR dan data loss sebagai `0`.

Catatan: pada aplikasi, PDR memakai perbandingan `data_received_kb` terhadap `data_sent_kb`. Jika Bab 4 ingin menyebut PDR sebagai packet delivery ratio murni, pastikan data yang dimasukkan mewakili jumlah paket. Jika data yang dimasukkan berupa ukuran transfer, istilah yang lebih tepat adalah rasio data diterima.

### Contoh Penyajian Bab 4

| Node | Jarak (m) | Data Sent (KB) | Data Received (KB) | Time (s) | Throughput (kbps) | PDR (%) | Data Loss (%) |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| NODE-01 | 50.00 | 1024.00 | 1000.00 | 10.00 | 819.20 | 97.66 | 2.34 |

### Cara Membaca Hasil

Throughput yang lebih tinggi menunjukkan kapasitas link lebih baik. Penurunan throughput saat jarak meningkat biasanya berhubungan dengan RSSI dan SNR yang turun, meningkatnya retransmission, atau kondisi lingkungan yang lebih buruk. PDR tinggi dan data loss rendah menunjukkan transfer berjalan andal.

## 7. Interface Test

### Catatan Implementasi Aplikasi

Pada aplikasi saat ini belum ada tabel atau form khusus bernama `interface_tests`. Oleh karena itu, interface test dapat dicatat sebagai pengujian manual black-box atau user acceptance test untuk menilai apakah antarmuka aplikasi berjalan sesuai fungsi.

### Tujuan Pengujian

Interface test digunakan untuk memastikan halaman, form, tombol, validasi, tabel, grafik, detail data, edit, hapus, export, login, dan role akses berjalan sesuai kebutuhan pengguna.

### Data yang Dicatat

| Field manual | Data yang dicatat | Contoh |
| --- | --- | --- |
| `test_date` | Tanggal pengujian interface | `2026-06-08` |
| `tester_name` | Nama penguji | `Penguji 1` |
| `page_name` | Halaman yang diuji | `Connectivity Test` |
| `test_scenario` | Skenario pengujian | `Input data connectivity` |
| `expected_result` | Hasil yang diharapkan | `Data tersimpan dan muncul di tabel` |
| `actual_result` | Hasil aktual | `Data tersimpan` |
| `status` | Status uji | `pass` atau `fail` |
| `response_time_second` | Waktu respon halaman/aksi | `1.25` |
| `error_message` | Pesan error bila ada | `-` |
| `notes` | Catatan penguji | `Validasi required berjalan` |

### Skenario yang Disarankan

| No | Halaman/Fitur | Skenario |
| ---: | --- | --- |
| 1 | Login | User valid berhasil login |
| 2 | Login | Password salah ditolak |
| 3 | Dashboard | Statistik dan grafik tampil |
| 4 | Connectivity | Input data baru berhasil |
| 5 | Connectivity | Field wajib tidak boleh kosong |
| 6 | Range | GPS/manual distance tersimpan dan dihitung |
| 7 | Penetration | RSSI loss dan packet loss dihitung |
| 8 | Latency | Latency dari timestamp dihitung |
| 9 | Throughput | Throughput, PDR, dan data loss dihitung |
| 10 | Edit Data | Data dapat diperbarui |
| 11 | Delete Data | Data dapat dihapus setelah konfirmasi |
| 12 | Export | Data dapat diekspor |
| 13 | Viewer Role | Viewer hanya dapat melihat data |
| 14 | Responsive | Tampilan tetap dapat digunakan pada layar kecil |

### Rumus Perhitungan Interface Test

```text
functional_success_rate = (jumlah_skenario_pass / total_skenario) x 100

failure_rate = (jumlah_skenario_fail / total_skenario) x 100

average_response_time = total_response_time_second / jumlah_skenario

task_completion_rate = (jumlah_task_selesai / total_task) x 100
```

Jika menggunakan kuesioner skala Likert 1 sampai 5:

```text
usability_score_percent = (total_skor / (jumlah_responden x jumlah_pertanyaan x skor_maksimum)) x 100
```

Contoh:

```text
total_skor = 420
jumlah_responden = 20
jumlah_pertanyaan = 5
skor_maksimum = 5

usability_score_percent = 420 / (20 x 5 x 5) x 100
                         = 84%
```

### Contoh Penyajian Bab 4

| Fitur | Total Skenario | Pass | Fail | Success Rate (%) | Rata-rata Respon (s) |
| --- | ---: | ---: | ---: | ---: | ---: |
| Form Pengujian | 5 | 5 | 0 | 100.00 | 1.10 |
| Login dan Role | 3 | 3 | 0 | 100.00 | 0.85 |

### Cara Membaca Hasil

Interface dinilai baik apabila success rate tinggi, tidak ada skenario kritis yang gagal, validasi input berjalan, role akses sesuai, dan waktu respon masih nyaman digunakan. Jika ada skenario gagal, Bab 4 perlu menjelaskan dampak kegagalan dan perbaikan yang dilakukan.

## 8. Rumus Agregasi untuk Analisis Bab 4

Gunakan agregasi berikut untuk menyusun ringkasan hasil per modul.

### Rata-rata

```text
average = total_nilai / jumlah_data
```

Contoh:

```text
average_rssi = total_rssi / jumlah_data_rssi
average_latency = total_latency / jumlah_data_latency
average_throughput = total_throughput / jumlah_data_throughput
```

### Nilai Minimum dan Maksimum

```text
minimum = nilai terkecil dari seluruh sampel
maximum = nilai terbesar dari seluruh sampel
```

Gunakan minimum dan maksimum untuk menjelaskan titik terbaik dan terburuk, misalnya RSSI terlemah, latency tertinggi, throughput tertinggi, atau jarak terjauh yang masih stabil.

### Persentase Status

```text
persentase_status = (jumlah_data_status_tertentu / total_data) x 100
```

Contoh:

```text
persentase_connected = (jumlah_connected / total_connectivity_test) x 100
persentase_good_range = (jumlah_status_good / total_range_test) x 100
```

### Hubungan Antar Metrik

Untuk pembahasan Bab 4, hubungan yang disarankan:

| Hubungan | Tujuan analisis |
| --- | --- |
| Jarak vs RSSI | Menjelaskan pelemahan sinyal saat jarak bertambah |
| Jarak vs SNR | Menjelaskan kualitas sinyal terhadap noise |
| Jarak vs Throughput | Menjelaskan pengaruh jarak terhadap kapasitas transfer |
| Jarak vs Latency | Menjelaskan pengaruh jarak terhadap waktu respon |
| Obstacle vs RSSI Loss | Menjelaskan pengaruh penghalang terhadap redaman |
| SNR vs Packet Loss | Menjelaskan pengaruh kualitas sinyal terhadap keandalan paket |
| RSSI/SNR vs Status | Menjelaskan batas kondisi link baik, sedang, dan buruk |

## 9. Format Narasi Singkat untuk Bab 4

Template narasi yang dapat disesuaikan:

```text
Berdasarkan hasil pengujian connectivity, koneksi antara master dan slave pada
lokasi [nama lokasi] menunjukkan success rate rata-rata sebesar [nilai]%
dengan packet loss rata-rata sebesar [nilai]%. Nilai RSSI rata-rata adalah
[nilai] dBm dan SNR rata-rata [nilai] dB. Hasil ini menunjukkan bahwa link
[stabil/cukup stabil/tidak stabil] pada kondisi pengujian tersebut.
```

```text
Pada range test, jarak terjauh yang masih menghasilkan status good adalah
[nilai] meter pada titik [kode titik]. Pada jarak tersebut, RSSI tercatat
[nilai] dBm dan SNR [nilai] dB. Penurunan RSSI seiring bertambahnya jarak
menunjukkan adanya peningkatan redaman propagasi.
```

```text
Pada penetration test, obstacle [jenis obstacle] menghasilkan RSSI loss
sebesar [nilai] dB dan SNR loss sebesar [nilai] dB. Hal ini menunjukkan bahwa
obstacle tersebut memberikan pengaruh redaman yang [rendah/sedang/tinggi]
terhadap sinyal WiFi HaLow.
```

```text
Pada latency test, latency rata-rata pada mode [mode jaringan] adalah [nilai]
ms dengan jitter rata-rata [nilai] ms. Nilai ini menunjukkan bahwa respon
jaringan [cepat/stabil/kurang stabil] untuk kebutuhan komunikasi sistem.
```

```text
Pada throughput test, throughput rata-rata yang diperoleh adalah [nilai] kbps
dengan PDR [nilai]% dan data loss [nilai]%. Hasil ini menunjukkan bahwa
kapasitas transfer data pada kondisi pengujian [memadai/menurun/tidak memadai].
```

```text
Pada interface test, seluruh skenario pengujian memperoleh success rate
[nilai]%. Hal ini menunjukkan bahwa fitur utama aplikasi, seperti input data,
edit, hapus, visualisasi, export, login, dan pembatasan role, telah berjalan
sesuai kebutuhan.
```

