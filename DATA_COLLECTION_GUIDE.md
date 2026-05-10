# Panduan Pengambilan Data Pengujian WiFi HaLow

Dokumen ini menjelaskan data apa saja yang perlu diambil, dari mana sumbernya, cara mengukurnya, dan bagaimana memasukkannya ke aplikasi web pengelolaan data skripsi.

## 1. Sumber Data Utama

### 1.1 Endpoint status ESP32 slave

Firmware `iperf.c` sudah menyediakan endpoint status:

```text
http://IP_SLAVE/api/status
```

Data yang bisa diambil dari endpoint ini:

| Data | Sumber | Keterangan |
|---|---|---|
| `device` | JSON `/api/status` | ID perangkat, contoh `SLAVE-HALOW-01` |
| `callsign` | JSON `/api/status` | Nama/callsign perangkat |
| `ipv4` | JSON `/api/status` | IP slave |
| `link_state` | JSON `/api/status` | Status koneksi jaringan |
| `rssi_dbm` | JSON `/api/status` | RSSI dari `mmwlan_get_rssi()` |
| `uptime_ms` | JSON `/api/status` | Lama perangkat hidup |
| `iperf_mode` | JSON `/api/status` | Mode iperf firmware |
| `iperf_port` | JSON `/api/status` | Port iperf, umumnya `5001` |
| `bmp180.temperature_c` | JSON `/api/status` | Suhu dari sensor BMP180 bila aktif |
| `bmp180.pressure_pa` | JSON `/api/status` | Tekanan udara dari sensor BMP180 bila aktif |

Catatan penting:

- RSSI sudah otomatis tersedia dari firmware.
- SNR belum otomatis tersedia di JSON firmware saat ini.
- Packet sent/received dan throughput bisa didapat dari `ping` dan `iperf`.

### 1.2 Serial monitor ESP32

Gunakan serial monitor untuk membaca log firmware:

```powershell
idf.py monitor
```

Data yang biasanya muncul:

| Data | Keterangan |
|---|---|
| IP slave | IP yang dipakai untuk akses HTTP/iperf |
| Iperf command hint | Perintah iperf yang harus dijalankan dari AP/master |
| Iperf report | Transferred, duration, bandwidth |
| CoT/GPS/BMP180 log | Jika fitur aktif |

### 1.3 Ping

Ping dipakai untuk connectivity dan latency:

```powershell
ping -n 100 IP_SLAVE
```

Contoh hasil:

```text
Packets: Sent = 100, Received = 98, Lost = 2
Minimum = 12ms, Maximum = 30ms, Average = 18ms
```

Masukkan:

| Field aplikasi | Nilai dari ping |
|---|---|
| `packet_sent` | `Sent` |
| `packet_received` | `Received` |
| `latency_ms` | `Average` |
| `minimum_latency` | `Minimum`, bila dicatat manual |
| `maximum_latency` | `Maximum`, bila dicatat manual |

### 1.4 Iperf

Iperf dipakai untuk throughput, bitrate, UDP packet, dan data transfer.

Jika slave menjadi UDP server:

```powershell
iperf -c IP_SLAVE -p 5001 -i 1 -u -b 20M -t 10
```

Jika TCP:

```powershell
iperf -c IP_SLAVE -p 5001 -i 1 -t 10
```

Data yang diambil:

| Data | Sumber |
|---|---|
| `data_sent_kb` | Total data terkirim dari output iperf client |
| `data_received_kb` | Total data diterima dari output iperf server/client |
| `transmission_time_second` | Durasi tes, contoh `10` detik |
| `throughput_kbps` | Bandwidth iperf dalam Kbits/sec |
| `bitrate_kbps` | Sama dengan bandwidth iperf atau PHY/link rate bila tersedia |
| `packet_sent` | UDP datagram terkirim, bila output tersedia |
| `packet_received` | UDP datagram diterima, bila output tersedia |
| `packet_loss_percent` | Loss iperf atau hitungan aplikasi |

Di kode firmware, data iperf tersedia di `struct mmiperf_report`:

| Field firmware | Makna |
|---|---|
| `bytes_transferred` | Jumlah byte transfer |
| `duration_ms` | Durasi tes |
| `bandwidth_kbitpsec` | Throughput rata-rata kbps |
| `tx_frames` | Jumlah frame UDP terkirim |
| `rx_frames` | Jumlah frame UDP diterima |
| `error_count` | Error/loss UDP |

### 1.5 GPS atau pengukuran jarak manual

Untuk range test:

| Data | Cara ambil |
|---|---|
| `master_gps_latitude` | GPS HP/GNSS di posisi master |
| `master_gps_longitude` | GPS HP/GNSS di posisi master |
| `gps_latitude` | GPS HP/GNSS di posisi slave |
| `gps_longitude` | GPS HP/GNSS di posisi slave |
| `distance_actual_meter` | Otomatis dari GPS atau manual pakai meteran |
| `coordinate_x_meter` | Otomatis dari selisih GPS atau manual |
| `coordinate_y_meter` | Otomatis dari selisih GPS atau manual |
| `coordinate_z_meter` | Elevasi/ketinggian relatif, manual |

Jika GPS master dan slave diisi, aplikasi menghitung jarak otomatis dengan rumus Haversine.

### 1.6 Alat ukur daya

Untuk power test, gunakan USB power meter, multimeter, INA219/INA226, atau power analyzer.

| Data | Cara ambil |
|---|---|
| `battery_voltage_v` | Tegangan baterai/input |
| `current_a` | Arus saat perangkat bekerja |
| `test_duration_hour` | Durasi pengujian |
| `battery_capacity_mah` | Kapasitas baterai |

## 2. Catatan Khusus SNR

Saat ini kode firmware sudah mengambil RSSI dengan:

```c
mmwlan_get_rssi()
```

Namun belum terlihat ada fungsi langsung seperti:

```c
mmwlan_get_snr()
```

Jadi SNR dapat diambil dengan salah satu cara berikut:

1. Dari dashboard/tool AP WiFi HaLow jika AP menampilkan SNR.
2. Dari tool/vendor Morse Micro jika statistik radio lengkap tersedia.
3. Dari sniffer/spectrum analyzer jika mengukur noise floor.
4. Estimasi sementara:

```text
SNR = RSSI - noise_floor
```

Contoh:

```text
RSSI = -70 dBm
Noise floor = -95 dBm
SNR = 25 dB
```

Catatan: estimasi ini hanya layak dipakai jika noise floor benar-benar diketahui. Jangan mengarang SNR dari RSSI saja.

## 3. Connectivity Test

Tujuan: menguji apakah node master dan slave tersambung stabil.

### Data yang diambil

| Field | Cara mendapatkan |
|---|---|
| `test_date` | Tanggal pengujian |
| `location_name` | Nama lokasi |
| `environment_type` | `lapangan`, `hangar`, `pantai`, `gunung`, `indoor`, `outdoor` |
| `node_id` | Dari `/api/status` field `device` atau label perangkat |
| `node_type` | `master` atau `slave` |
| `connection_status` | Dari `link_state`, ping, atau observasi koneksi |
| `rssi_dbm` | Dari `/api/status` field `rssi_dbm` |
| `snr_db` | Dari AP/tool radio, atau hitungan SNR valid |
| `packet_sent` | Dari ping atau UDP iperf |
| `packet_received` | Dari ping atau UDP iperf |
| `test_duration_second` | Durasi pengujian |
| `notes` | Kondisi lapangan, antena, obstacle, cuaca |

### Cara ukur

1. Nyalakan master/AP dan slave.
2. Pastikan slave mendapat IP.
3. Buka:

```text
http://IP_SLAVE/api/status
```

4. Catat `device`, `link_state`, dan `rssi_dbm`.
5. Jalankan ping:

```powershell
ping -n 100 IP_SLAVE
```

6. Masukkan `Sent` ke `packet_sent`, `Received` ke `packet_received`.

### Rumus otomatis aplikasi

```text
packet_lost = packet_sent - packet_received
packet_loss_percent = packet_lost / packet_sent x 100
packet_success_rate = packet_received / packet_sent x 100
```

## 4. Range Test

Tujuan: menguji jangkauan WiFi HaLow pada beberapa titik jarak.

### Data yang diambil

| Field | Cara mendapatkan |
|---|---|
| `test_date` | Tanggal pengujian |
| `location_name` | Nama lokasi |
| `environment_type` | Kondisi lokasi |
| `test_point_code` | Kode titik, contoh `TP-001` |
| `direction` | Arah dari master ke slave |
| `master_gps_latitude` | GPS master |
| `master_gps_longitude` | GPS master |
| `gps_latitude` | GPS slave |
| `gps_longitude` | GPS slave |
| `coordinate_x_meter` | Otomatis dari GPS atau manual |
| `coordinate_y_meter` | Otomatis dari GPS atau manual |
| `coordinate_z_meter` | Elevasi/ketinggian |
| `distance_actual_meter` | Otomatis dari GPS atau manual |
| `frequency_mhz` | Frekuensi kerja, default `915` |
| `rssi_dbm` | `/api/status` |
| `snr_db` | AP/tool radio |
| `bitrate_kbps` | Iperf bandwidth atau link rate |
| `connection_status` | Ping/status link |
| `receiver_sensitivity_dbm` | Nilai spesifikasi, default aplikasi `-90` |
| `photo_video_link` | Link dokumentasi lapangan |
| `notes` | Kondisi titik uji |

### Cara ukur

1. Tentukan posisi master sebagai titik pusat.
2. Catat GPS master.
3. Pindahkan slave ke titik uji.
4. Catat GPS slave.
5. Ambil RSSI dari `/api/status`.
6. Jalankan ping untuk memastikan status koneksi.
7. Jalankan iperf untuk bitrate:

```powershell
iperf -c IP_SLAVE -p 5001 -i 1 -u -b 20M -t 10
```

### Rumus otomatis aplikasi

```text
GPS Distance = Haversine(Master GPS, Slave GPS)
Distance 3D = sqrt(x^2 + y^2 + z^2)
FSPL = 32.44 + 20log10(frequency MHz) + 20log10(distance km)
Signal Margin = RSSI - Receiver Sensitivity
```

Status hasil:

```text
good     = SNR > 20 dB
moderate = SNR 10-20 dB
poor     = SNR < 10 dB atau disconnected
```

## 5. Signal Penetration Test

Tujuan: menguji penurunan sinyal ketika ada hambatan.

### Data yang diambil

| Field | Cara mendapatkan |
|---|---|
| `test_date` | Tanggal pengujian |
| `location_name` | Nama lokasi |
| `obstacle_type` | `wall`, `building`, `trees`, `vehicle`, `hangar`, `hill`, `none` |
| `condition_type` | `LOS` atau `NLOS` |
| `distance_meter` | Jarak master ke slave |
| `rssi_before_dbm` | RSSI tanpa hambatan atau sebelum obstacle |
| `rssi_after_dbm` | RSSI setelah hambatan |
| `snr_before_db` | SNR sebelum hambatan |
| `snr_after_db` | SNR setelah hambatan |
| `packet_sent` | Ping/iperf |
| `packet_received` | Ping/iperf |
| `bitrate_kbps` | Iperf |
| `notes` | Jenis material, tebal, posisi antena |

### Cara ukur

1. Ukur RSSI/SNR pada kondisi referensi.
2. Letakkan hambatan di antara master dan slave.
3. Ukur RSSI/SNR ulang.
4. Jalankan ping atau UDP iperf untuk packet sent/received.

### Rumus otomatis aplikasi

```text
RSSI Loss = RSSI Before - RSSI After
SNR Loss = SNR Before - SNR After
Packet Loss % = packet_lost / packet_sent x 100
Penetration Loss = RSSI Loss
```

## 6. Latency Test

Tujuan: mengukur delay komunikasi.

### Data yang diambil

| Field | Cara mendapatkan |
|---|---|
| `test_date` | Tanggal |
| `location_name` | Lokasi |
| `environment_type` | Kondisi lokasi |
| `node_id` | Target node |
| `distance_meter` | Jarak |
| `trial_number` | Nomor percobaan |
| `timestamp_send_ms` | Timestamp saat paket/request dikirim |
| `timestamp_receive_ms` | Timestamp saat response diterima |
| `packet_sent` | Ping/tes request |
| `packet_received` | Ping/tes request |
| `network_mode` | `HaLow only` atau `HaLow + VSAT` |
| `latency_ms` | Ping average atau response time |
| `jitter_ms` | Variasi latency |
| `notes` | Kondisi tes |

### Cara ukur cepat dengan ping

```powershell
ping -n 20 IP_SLAVE
```

Ambil:

```text
latency_ms = Average
packet_sent = Sent
packet_received = Received
```

Untuk jitter sederhana, catat beberapa nilai latency per ping, lalu hitung rata-rata selisih antar nilai berturut-turut.

### Rumus otomatis aplikasi

```text
Latency = timestamp_receive_ms - timestamp_send_ms
Packet Loss % = packet_lost / packet_sent x 100
```

## 7. Throughput Test

Tujuan: mengukur kapasitas transfer data.

### Data yang diambil

| Field | Cara mendapatkan |
|---|---|
| `test_date` | Tanggal |
| `location_name` | Lokasi |
| `environment_type` | Kondisi lokasi |
| `node_id` | Target node |
| `distance_meter` | Jarak |
| `data_sent_kb` | Output iperf |
| `data_received_kb` | Output iperf |
| `transmission_time_second` | Durasi iperf |
| `rssi_dbm` | `/api/status` |
| `snr_db` | AP/tool radio |
| `bitrate_kbps` | Iperf bandwidth |
| `notes` | Kondisi tes |

### Cara ukur

```powershell
iperf -c IP_SLAVE -p 5001 -i 1 -u -b 20M -t 10
```

Jika output dalam Mbits/sec, konversi ke kbps:

```text
1 Mbit/sec = 1000 kbps
```

Jika output transfer dalam MBytes, konversi ke KB:

```text
1 MByte = 1024 KB
```

### Rumus otomatis aplikasi

```text
Throughput = data_received_kb x 1024 x 8 / (time_second x 1000)
PDR = data_received_kb / data_sent_kb x 100
Data Loss = (data_sent_kb - data_received_kb) / data_sent_kb x 100
```

## 8. Interference Test

Tujuan: menguji kualitas link ketika ada interferensi.

### Data yang diambil

| Field | Cara mendapatkan |
|---|---|
| `test_date` | Tanggal |
| `location_name` | Lokasi |
| `interference_level` | `normal`, `low`, `medium`, `high` |
| `interference_source` | Sumber interferensi |
| `distance_meter` | Jarak |
| `rssi_dbm` | `/api/status` |
| `snr_db` | AP/tool radio |
| `throughput_kbps` | Iperf |
| `latency_ms` | Ping/request |
| `packet_sent` | Ping/iperf |
| `packet_received` | Ping/iperf |
| `notes` | Detail interferensi |

### Cara ukur

1. Ambil baseline tanpa interferensi.
2. Aktifkan sumber interferensi.
3. Ulangi ping dan iperf.
4. Bandingkan throughput, latency, packet loss, RSSI, dan SNR.

### Rumus otomatis aplikasi

```text
Packet Loss % = packet_lost / packet_sent x 100
```

Field degradation disimpan kosong jika baseline tidak dimasukkan.

## 9. Camera Test

Tujuan: menguji performa kamera slave.

### Data yang diambil

| Field | Cara mendapatkan |
|---|---|
| `test_date` | Tanggal |
| `location_name` | Lokasi |
| `node_id` | Node kamera |
| `distance_meter` | Jarak |
| `resolution` | Resolusi stream, contoh `720p` |
| `fps` | FPS aktual dari stream |
| `image_quality_score` | Penilaian 1-5 |
| `camera_delay_ms` | Delay request frame/stream |
| `packet_loss_percent` | Ping/iperf saat kamera aktif |
| `status` | `success` atau `fail` |
| `notes` | Kondisi cahaya, jarak, resolusi |

### Cara ukur

1. Buka stream kamera slave.
2. Catat resolusi.
3. Catat FPS dari aplikasi kamera atau observasi stream.
4. Ukur delay dari klik/buka stream sampai frame tampil.
5. Jalankan ping bersamaan untuk packet loss.

### Kategori otomatis aplikasi

```text
good     = image_quality_score >= 4
moderate = image_quality_score >= 3
poor     = image_quality_score < 3
```

## 10. Power Consumption Test

Tujuan: mengukur konsumsi daya perangkat.

### Data yang diambil

| Field | Cara mendapatkan |
|---|---|
| `test_date` | Tanggal |
| `device_id` | ID perangkat |
| `device_type` | `master` atau `slave` |
| `battery_voltage_v` | Multimeter/power meter |
| `current_a` | Multimeter/power meter |
| `test_duration_hour` | Durasi pengujian |
| `battery_capacity_mah` | Spesifikasi baterai |
| `cpu_usage_percent` | Jika tersedia dari firmware/OS |
| `ram_usage_percent` | Jika tersedia dari firmware/OS |
| `cpu_temperature_c` | Sensor suhu atau telemetry |
| `rssi_dbm` | `/api/status` |
| `snr_db` | AP/tool radio |
| `notes` | Mode kerja, TX/RX, kamera aktif/tidak |

### Rumus otomatis aplikasi

```text
Power W = Voltage x Current
Energy Wh = Power x Duration
Battery Capacity Wh = Voltage x Capacity mAh / 1000
Runtime hour = Battery Capacity Wh / Power W
Runtime day = Runtime hour / 24
```

## 11. Command Execution Test

Tujuan: menguji pengiriman dan eksekusi command ke node.

### Data yang diambil

| Field | Cara mendapatkan |
|---|---|
| `test_date` | Tanggal |
| `command_type` | Jenis command dari example ESP32: `iperf`, `porting assistant`, `rf-test`, `scan`, `sta connect`, `sta reboot`, `transfer reset`, atau `web camera server` |
| `source` | Pengirim command |
| `target_node_id` | Target |
| `command_sent_time_ms` | Timestamp saat command dikirim |
| `command_received_time_ms` | Timestamp saat command diterima node |
| `command_executed_time_ms` | Timestamp selesai dieksekusi |
| `execution_status` | `success` atau `fail` |
| `notes` | Detail command |

### Sumber data dari examples ESP32

Beberapa example firmware sekarang mencetak log timing standar:

```text
COMMAND_TIMING source="ESP32 local" command_type="scan" command_sent_time_ms=123 command_received_time_ms=124 command_executed_time_ms=4567 execution_status=success
COMMAND_TIMING source="morsectrl UART" command_type="rf-test" command_sent_time_ms=1000 command_received_time_ms=1020 command_executed_time_ms=1088 execution_status=success
COMMAND_TIMING source="ESP32 local" command_type="porting assistant" command_sent_time_ms=200 command_received_time_ms=200 command_executed_time_ms=230 execution_status=success step="Read chip ID"
```

Cara input ke web:

1. Flash example yang ingin diuji, misalnya `scan`, `rf-test`, atau `porting_assistant`.
2. Buka serial monitor.
3. Jalankan command/test sesuai example.
4. Salin nilai dari baris `COMMAND_TIMING` ke halaman Command Execution.
5. Isi `source` dengan asal command, misalnya `ESP32 local`, `morsectrl UART`, atau `Website`.
6. Isi `target_node_id` dengan target, misalnya `SLAVE-HALOW-01` atau `MM6108`.

Catatan: untuk example yang berjalan lokal seperti `scan` dan `porting_assistant`, `command_sent_time_ms` dan `command_received_time_ms` adalah waktu internal firmware saat command mulai diproses. Untuk command eksternal seperti `rf-test`, `command_sent_time_ms` adalah waktu firmware melihat data UART pertama masuk, bukan waktu absolut dari PC.

### Rumus otomatis aplikasi

```text
Delivery Delay = command_received_time_ms - command_sent_time_ms
Execution Delay = command_executed_time_ms - command_received_time_ms
Total Command Time = command_executed_time_ms - command_sent_time_ms
Command Success Rate = 100 jika success, 0 jika fail
```

## 12. Response Time Test

Tujuan: mengukur waktu respon request/command.

### Data yang diambil

| Field | Cara mendapatkan |
|---|---|
| `test_date` | Tanggal |
| `command_type` | Jenis command/request |
| `target_node_id` | Target |
| `request_time_ms` | Timestamp request dikirim |
| `response_time_ms` | Timestamp response diterima |
| `network_mode` | `HaLow only` atau `HaLow + VSAT` |
| `status` | `success`, `fail`, `timeout` |
| `notes` | Detail request |

### Rumus otomatis aplikasi

```text
Response Time Total = response_time_ms - request_time_ms
Average/Minimum/Maximum = nilai response time per input
```

## 13. Encryption Test

Tujuan: menguji keamanan komunikasi.

### Data yang diambil

| Field | Cara mendapatkan |
|---|---|
| `test_date` | Tanggal |
| `protocol_used` | Contoh `WiFi HaLow`, `HTTP`, `CoT`, `UDP`, `TCP` |
| `encryption_type` | Contoh `WPA3`, `WPA2`, `TLS`, `none` |
| `key_length_bit` | Panjang key, contoh `256` |
| `sniffing_test_result` | `readable` atau `unreadable` |
| `data_integrity_status` | `valid` atau `invalid` |
| `notes` | Tool sniffing, hasil observasi |

### Cara ukur

1. Jalankan komunikasi normal.
2. Capture trafik dengan Wireshark/tcpdump di sisi yang memungkinkan.
3. Jika payload bisa dibaca jelas, isi `readable`.
4. Jika payload tidak terbaca, isi `unreadable`.
5. Cek apakah data yang diterima sama dengan yang dikirim.

### Status otomatis aplikasi

```text
secure = sniffing unreadable dan data integrity valid
insecure = selain kondisi di atas
```

## 14. Text Communication Test

Tujuan: menguji pengiriman pesan teks master ke slave atau slave ke master.

### Data yang diambil

| Field | Cara mendapatkan |
|---|---|
| `test_date` | Tanggal |
| `source_node` | Pengirim |
| `target_node_id` | Penerima |
| `target_ip` | IP target |
| `target_port` | Port HTTP, umumnya `80` |
| `protocol` | Umumnya `HTTP` |
| `endpoint` | Contoh `/api/message` |
| `message_text` | Isi pesan |
| `request_payload` | JSON/body request |
| `response_status_code` | HTTP status |
| `response_body` | Isi response |
| `latency_ms` | Waktu request sampai response |
| `delivery_status` | `success` atau `fail` |
| `error_message` | Error jika gagal |

### Cara ukur

1. Buka halaman Text Communication.
2. Isi IP slave dan endpoint.
3. Kirim pesan.
4. Aplikasi mencatat latency, response status, body response, dan delivery status.
5. Jika perlu verifikasi, cek inbox master/slave.

Catatan:

- Port `80` dipakai untuk API HTTP ESP32.
- Port `5001` dipakai untuk iperf, bukan untuk API pesan.

## 15. Checklist Pengambilan Data Lapangan

Gunakan checklist ini untuk setiap titik uji.

```text
[ ] Tanggal pengujian dicatat
[ ] Lokasi dicatat
[ ] Environment dicatat
[ ] Node master/slave dicatat
[ ] IP slave dicatat
[ ] GPS master dicatat
[ ] GPS slave dicatat
[ ] Jarak dicatat atau dihitung GPS
[ ] RSSI dari /api/status dicatat
[ ] SNR dicatat dari AP/tool radio jika tersedia
[ ] Ping dijalankan dan hasil sent/received/loss dicatat
[ ] Latency average/min/max dicatat
[ ] Iperf dijalankan dan throughput dicatat
[ ] Data sent/received dicatat
[ ] Kondisi LOS/NLOS dicatat
[ ] Obstacle/interferensi dicatat bila ada
[ ] Foto/video bukti disimpan
[ ] Notes lapangan ditulis
```

## 16. Format Catatan Mentah yang Disarankan

Sebelum dimasukkan ke web, catat data mentah seperti ini:

```text
Tanggal:
Lokasi:
Environment:
Test point:
Master node:
Slave node:
IP slave:

Master GPS:
Slave GPS:
Jarak:
Arah:
Kondisi LOS/NLOS:
Obstacle:
Interferensi:

RSSI:
SNR:
Ping sent:
Ping received:
Ping lost:
Ping min:
Ping max:
Ping average:

Iperf mode:
Iperf duration:
Data sent:
Data received:
Bandwidth:
UDP datagram sent:
UDP datagram received:
UDP loss:

Power voltage:
Power current:
Battery capacity:
Temperature:

Catatan:
Link foto/video:
```

## 17. Rekomendasi Pengulangan Pengujian

Untuk data yang lebih kuat:

- Lakukan minimal 3 percobaan per titik.
- Gunakan durasi iperf yang sama, misalnya 10 detik atau 30 detik.
- Gunakan jumlah ping yang sama, misalnya 100 paket.
- Catat posisi antena dan tinggi antena.
- Jangan mencampur hasil indoor dan outdoor dalam satu titik tanpa catatan.
- Catat kondisi cuaca untuk outdoor.
- Untuk range test, gunakan kode titik konsisten seperti `TP-001`, `TP-002`, dan seterusnya.

## 18. Data yang Bisa Diotomatisasi Berikutnya

Firmware dapat dikembangkan agar `/api/status` juga mengeluarkan:

```text
snr_db
tx_packets
rx_packets
iperf_last_bandwidth_kbps
iperf_last_bytes_transferred
iperf_last_duration_ms
iperf_last_tx_frames
iperf_last_rx_frames
iperf_last_error_count
```

Bagian yang perlu dimodifikasi:

1. Simpan hasil `struct mmiperf_report` terakhir di variabel global.
2. Tambahkan field tersebut ke fungsi `build_status_json()`.
3. Jika SNR tersedia dari API/vendor/tool Morse, tambahkan ke JSON sebagai `snr_db`.
