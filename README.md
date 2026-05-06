# WiFi HaLow Testing System

## Design and Implementation of a Wi-Fi HaLow-Based Tactical Monitoring and Communication Support System for Manpack VSAT in Military Operations

## Deskripsi
Sistem pengelolaan data pengujian skripsi Bab 4 berbasis web untuk sistem komunikasi WiFi HaLow. Sistem ini dirancang khusus untuk kebutuhan pengujian sistem komunikasi taktis militer dengan fitur analisis dan pelaporan otomatis.

## Fitur Utama
1. **Dashboard Utama** - Menampilkan statistik dan grafik analisis
2. **Modul Pengujian** - 17 jenis pengujian berbeda
3. **Analisis Otomatis** - Perhitungan rumus otomatis
4. **Generate Laporan** - Export PDF, CSV, dan Print
5. **Manajemen Data** - CRUD untuk semua jenis pengujian

## Modul Pengujian
1. Communication System Testing
   - Connectivity Test
   - Range Test
   - Signal Penetration Test

2. Network Performance Testing
   - Latency Test
   - Throughput Test
   - Interference Resistance Test

3. Device and Energy Testing
   - Slave Camera Test
   - Power Consumption Test

4. Network Topology Testing
   - Star Topology Test
   - Mesh Topology Analysis

5. Evaluation System Monitoring
   - Data Monitoring
   - Monitoring Delay

6. Control System Testing
   - Command Execution
   - Response Time

7. System Security Evaluation
   - Authentication
   - Encryption

8. Analysis and Discussion
   - Laporan otomatis

## Teknologi yang Digunakan
- Apache Web Server
- PHP Native
- MySQL/MariaDB
- HTML5, CSS3, JavaScript
- Bootstrap 5
- Chart.js
- DataTables
- jQuery

## Persyaratan Sistem
- Web Server: Apache 2.4+
- PHP: 7.4+
- Database: MySQL 5.7+ atau MariaDB 10.3+
- Browser: Chrome, Firefox, Safari, Edge (versi terbaru)

## Instalasi

### 1. Persiapan Database
```sql
-- Import file database.sql ke MySQL
mysql -u root -p < database.sql
```

### 2. Konfigurasi Database
Edit file `config/database.php`:
```php
$config = [
    'host' => 'localhost',
    'dbname' => 'wifi_holow_testing',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4'
];
```

### 3. Deploy ke Web Server
#### XAMPP / Laragon / WAMP:
1. Copy folder `wifi-testing-system` ke `htdocs`
2. Pastikan Apache dan MySQL berjalan
3. Akses: `http://localhost/wifi-testing-system/public/`

#### Linux Apache Server:
1. Copy ke `/var/www/html/`
2. Set permissions:
   ```bash
   sudo chown -R www-data:www-data /var/www/html/wifi-testing-system
   sudo chmod -R 755 /var/www/html/wifi-testing-system
   ```
3. Enable mod_rewrite:
   ```bash
   sudo a2enmod rewrite
   sudo systemctl restart apache2
   ```

### 4. Akses Sistem
Buka browser dan akses:
```
http://localhost/wifi-testing-system/public/
```

### 5. Login Default
- Username: `admin`
- Password: `admin123`

## Struktur Folder
```
wifi-testing-system/
├── app/
│   ├── Controllers/    # Controller PHP
│   ├── Models/         # Model PHP
│   └── Helpers/        # Fungsi helper
├── config/            # Konfigurasi
├── database/          # Database schema dan seeds
├── public/            # File publik
│   ├── css/          # Stylesheets
│   ├── js/           # JavaScript
│   ├── uploads/      # File upload
│   └── reports/      # File laporan
├── resources/
│   └── views/        # Template PHP
├── database.sql      # Schema database
└── README.md
```

## Penggunaan

### Input Data Pengujian
1. Login ke sistem
2. Pilih modul pengujian dari sidebar
3. Klik tombol "Add New" untuk input data baru
4. Isi form dengan data pengujian
5. Sistem akan menghitung otomatis
6. Klik "Save" untuk menyimpan

### View Data
- Tabel data otomatis memuat hasil pengujian
- Gunakan filter dan search untuk data spesifik
- Export data ke CSV/Excel/PDF

### Analisis & Grafik
- Dashboard menampilkan grafik analisis otomatis
- Grafik interaktif berbasis Chart.js
- Statistik real-time

### Generate Laporan
1. Pilih "Reports" di sidebar
2. Tentukan periode dan filter
3. Pilih format export (PDF/CSV)
4. Download laporan

## Database Schema
Terdapat 19 tabel utama:
- `users` - Manajemen pengguna
- `connectivity_tests` - Data connectivity test
- `range_tests` - Data range test
- `signal_penetration_tests` - Data penetration test
- `latency_tests` - Data latency test
- `throughput_tests` - Data throughput test
- `interference_tests` - Data interference test
- `slave_camera_tests` - Data camera test
- `power_consumption_tests` - Data power test
- `star_topology_tests` - Data star topology
- `mesh_topology_analysis` - Data mesh topology
- `data_monitoring` - Data monitoring
- `monitoring_delay_tests` - Data delay
- `command_execution_tests` - Data command execution
- `response_time_tests` - Data response time
- `authentication_tests` - Data auth test
- `encryption_tests` - Data encryption test
- `test_locations` - Data lokasi pengujian
- `devices` - Data perangkat
- `generated_reports` - Data laporan

## Rumus Perhitungan Otomatis

### Connectivity Test
- Packet Lost = Packet Sent - Packet Received
- Packet Loss % = (Packet Lost / Packet Sent) × 100
- Success Rate % = (Packet Received / Packet Sent) × 100

### Range Test
- Distance 3D = √(x² + y² + z²)
- FSPL = 32.44 + 20log₁₀(frequency) + 20log₁₀(distance in km)
- Signal Margin = RSSI - Receiver Sensitivity (-90 dBm)

### Throughput Test
- Throughput (kbps) = (Data Received × 1024 × 8) / (Time × 1000)
- PDR % = (Data Received / Data Sent) × 100
- Data Loss % = ((Data Sent - Data Received) / Data Sent) × 100

### Power Test
- Power (W) = Voltage × Current
- Energy (Wh) = Power × Duration
- Battery Capacity (Wh) = (Voltage × Capacity_mAh) / 1000
- Runtime (hours) = Battery Capacity / Power

## Fitur Khusus

### Automatic Analysis
- Perhitungan rumus otomatis
- Status sistem otomatis (Stable/Degraded/Critical)
- Rekomendasi berbasis data

### Visualization
- Chart.js untuk grafik interaktif
- DataTables untuk tabel dinamis
- Dashboard real-time

### Reporting
- Export PDF (menggunakan jsPDF)
- Export CSV
- Print-friendly format
- Periode dan filter custom

## Security Features
- Session management
- CSRF protection
- Input sanitization
- SQL injection prevention (PDO)
- Password hashing

## Maintenance

### Backup Database
```bash
mysqldump -u root -p wifi_holow_testing > backup_$(date +%Y%m%d).sql
```

### Update System
1. Backup database dan file
2. Update file aplikasi
3. Cek kompatibilitas PHP
4. Test sistem

## Troubleshooting

### Masalah Umum
1. **Blank Page** - Cek error log PHP, pastikan koneksi database benar
2. **404 Error** - Pastikan mod_rewrite Apache aktif
3. **Database Error** - Cek kredensial database di config/database.php
4. **Chart Not Loading** - Pastikan internet koneksi untuk CDN

### Log Error
- PHP Error Log: `php_errors.log`
- Apache Log: `/var/log/apache2/error.log`
- MySQL Log: `/var/log/mysql/error.log`

## Support
Untuk bantuan dan dukungan:
- Email: support@wifiholow.system
- Dokumentasi: docs/wifi-holow-docs.pdf
- Issue Tracker: GitHub Issues

## License
MIT License - Free for Educational Use

## Changelog

### v1.0.0 (2026-04-27)
- Initial Release
- 17 modul pengujian
- Dashboard real-time
- Export laporan
- Analisis otomatis

## Credits
Developed for: Militer Tactical Communication System
Author: AI Assistant
Version: 1.0.0
Date: April 2026
