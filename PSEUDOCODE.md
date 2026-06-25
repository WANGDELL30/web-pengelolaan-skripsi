# Pseudocode Sistem Pemantauan dan Komunikasi Berbasis Wi-Fi HaLow

> **Judul Skripsi:** Perancangan dan Implementasi Sistem Pemantauan dan Dukungan Komunikasi Taktis Berbasis Wi-Fi HaLow  
> **Dokumen:** Pseudocode Algoritma Sistem  

---

## Daftar Isi

1. [Konfigurasi Koneksi Database](#1-konfigurasi-koneksi-database)
2. [Algoritma Autentikasi Pengguna](#2-algoritma-autentikasi-pengguna)
3. [Algoritma Pengambilan Statistik Dashboard](#3-algoritma-pengambilan-statistik-dashboard)
4. [Algoritma Pengambilan Data Grafik](#4-algoritma-pengambilan-data-grafik)
5. [Fungsi-Fungsi Kalkulasi Metrik Jaringan](#5-fungsi-fungsi-kalkulasi-metrik-jaringan)
6. [Algoritma Penentuan Status Sistem](#6-algoritma-penentuan-status-sistem)
7. [Algoritma Manajemen Hak Akses](#7-algoritma-manajemen-hak-akses)
8. [Algoritma Sanitasi Input](#8-algoritma-sanitasi-input)
9. [Alur Sistem Keseluruhan](#9-alur-sistem-keseluruhan)

---

## 1. Konfigurasi Koneksi Database

Pseudocode ini menggambarkan proses inisialisasi koneksi ke basis data secara adaptif berdasarkan lingkungan server (lokal atau produksi).

```
PROSEDUR KonfigurasiDatabase()
BEGIN
    hostName ← Baca HTTP_HOST dari server

    JIKA hostName cocok dengan pola domain produksi MAKA
        config ← {
            host     : "localhost",
            dbname   : "arndilh2_skripsi",
            username : "arndilh2_skripsi",
            password : "***"
        }
    SEBALIKNYA
        config ← {
            host     : "localhost",
            dbname   : "wifi_holow_testing",
            username : "root",
            password : ""
        }
    AKHIR JIKA

    JIKA terdapat file konfigurasi lokal (database.local.php) MAKA
        localConfig ← Muat file konfigurasi lokal
        config ← Gabungkan config dengan localConfig
    AKHIR JIKA

    // Override dari environment variable jika ada
    JIKA ENV["DB_HOST"] tidak kosong MAKA config.host ← ENV["DB_HOST"]
    JIKA ENV["DB_NAME"] tidak kosong MAKA config.dbname ← ENV["DB_NAME"]
    JIKA ENV["DB_USER"] tidak kosong MAKA config.username ← ENV["DB_USER"]
    JIKA ENV["DB_PASS"] tidak kosong MAKA config.password ← ENV["DB_PASS"]

    COBA
        pdo ← Buat koneksi PDO dengan config
        Set PDO mode error : ERRMODE_EXCEPTION
        Set PDO fetch mode : FETCH_ASSOC
        Set emulasi prepare : NONAKTIF
    TANGKAP PDOException sebagai e
        Tampilkan "Database connection failed: " + e.getMessage()
        Hentikan eksekusi
    AKHIR COBA
END

FUNGSI query(sql, params = [])
BEGIN
    stmt ← pdo.prepare(sql)
    stmt.execute(params)
    KEMBALIKAN stmt
END

FUNGSI fetchAll(sql, params = [])
BEGIN
    stmt ← query(sql, params)
    KEMBALIKAN stmt.fetchAll()
END

FUNGSI fetchOne(sql, params = [])
BEGIN
    stmt ← query(sql, params)
    KEMBALIKAN stmt.fetch()
END
```

---

## 2. Algoritma Autentikasi Pengguna

Pseudocode ini menggambarkan proses login, proteksi halaman, dan logout pengguna.

```
KELAS LoginController
BEGIN

    PROSEDUR __construct(pdo)
    BEGIN
        this.pdo ← pdo
        JIKA sesi belum aktif MAKA mulai sesi
    END

    // Tampilkan Halaman Login
    PROSEDUR index()
    BEGIN
        JIKA isLoggedIn() = BENAR MAKA
            Redirect ke halaman utama (index.php)
        AKHIR JIKA
        Tampilkan view halaman login
    END

    // Proses Login
    PROSEDUR login()
    BEGIN
        JIKA method request bukan POST MAKA
            Keluar dari prosedur
        AKHIR JIKA

        username ← sanitize(POST["username"])
        password ← POST["password"]

        user ← fetchOne("SELECT * FROM users WHERE username = ?", [username])

        JIKA user ditemukan DAN password_verify(password, user.password) = BENAR MAKA
            Simpan ke sesi:
                SESSION["user_id"]   ← user.id
                SESSION["username"]  ← user.username
                SESSION["user_role"] ← user.role
                SESSION["full_name"] ← user.full_name
            Redirect ke halaman utama (index.php)
        SEBALIKNYA
            SESSION["error"] ← "Username atau password salah"
            Redirect ke halaman login (index.php)
        AKHIR JIKA
    END

    // Logout Pengguna
    PROSEDUR logout()
    BEGIN
        Hapus semua data sesi (session_destroy)
        Redirect ke halaman login (index.php)
    END

END KELAS
```

---

## 3. Algoritma Pengambilan Statistik Dashboard

Pseudocode ini menggambarkan proses pengambilan dan kalkulasi statistik ringkasan dari seluruh tabel pengujian.

```
KELAS DashboardController
BEGIN

    PROSEDUR __construct(pdo)
    BEGIN
        this.pdo ← pdo
    END

    FUNGSI getStats()
    BEGIN
        stats ← {}

        // 1. Hitung total uji konektivitas
        result ← fetchOne("SELECT COUNT(*) as total FROM connectivity_tests")
        stats["total_connectivity"] ← result["total"]

        // 2. Hitung total uji jangkauan
        result ← fetchOne("SELECT COUNT(*) as total FROM range_tests")
        stats["total_range"] ← result["total"]

        // 3. Hitung total lokasi unik dari semua tabel pengujian
        result ← fetchOne("
            SELECT COUNT(DISTINCT location_name) as total FROM (
                SELECT location_name FROM test_locations
                UNION SELECT location_name FROM connectivity_tests
                UNION SELECT location_name FROM range_tests
                UNION SELECT location_name FROM signal_penetration_tests
                UNION SELECT location_name FROM latency_tests
                UNION SELECT location_name FROM throughput_tests
                UNION SELECT location_name FROM interference_tests
                UNION SELECT location_name FROM slave_camera_tests
            ) WHERE location_name IS NOT NULL
        ")
        stats["total_locations"] ← result["total"]

        // 4. Hitung total node/perangkat unik
        result ← fetchOne("
            SELECT COUNT(DISTINCT node_id) as total FROM (
                SELECT node_id FROM connectivity_tests
                UNION SELECT node_id FROM latency_tests
                UNION SELECT node_id FROM throughput_tests
                UNION SELECT node_id FROM slave_camera_tests
                UNION SELECT target_node_id FROM command_execution_tests
                UNION SELECT target_node_id FROM response_time_tests
            )
        ")
        stats["total_nodes"] ← result["total"]

        // 5. Hitung rata-rata latensi
        result ← fetchOne("SELECT AVG(latency_ms) FROM latency_tests WHERE latency_ms IS NOT NULL")
        JIKA result["avg"] tidak null MAKA
            stats["avg_latency"] ← round(result["avg"], 2)
        SEBALIKNYA
            stats["avg_latency"] ← null
        AKHIR JIKA

        // 6. Hitung rata-rata throughput
        result ← fetchOne("SELECT AVG(throughput_kbps) FROM throughput_tests WHERE throughput_kbps IS NOT NULL")
        JIKA result["avg"] tidak null MAKA
            stats["avg_throughput"] ← round(result["avg"], 2)
        SEBALIKNYA
            stats["avg_throughput"] ← null
        AKHIR JIKA

        // 7. Hitung rata-rata RSSI dari tiga tabel
        result ← fetchOne("
            SELECT AVG(rssi_dbm) FROM (
                SELECT rssi_dbm FROM connectivity_tests
                UNION ALL SELECT rssi_dbm FROM range_tests
                UNION ALL SELECT rssi_before_dbm FROM signal_penetration_tests
            ) WHERE rssi_dbm IS NOT NULL
        ")
        JIKA result["avg"] tidak null MAKA
            stats["avg_rssi"] ← round(result["avg"], 2)
        SEBALIKNYA
            stats["avg_rssi"] ← null
        AKHIR JIKA

        // 8. Hitung rata-rata SNR dari tiga tabel
        result ← fetchOne("
            SELECT AVG(snr_db) FROM (
                SELECT snr_db FROM connectivity_tests
                UNION ALL SELECT snr_db FROM range_tests
                UNION ALL SELECT snr_before_db FROM signal_penetration_tests
            ) WHERE snr_db IS NOT NULL
        ")
        JIKA result["avg"] tidak null MAKA
            stats["avg_snr"] ← round(result["avg"], 2)
        SEBALIKNYA
            stats["avg_snr"] ← null
        AKHIR JIKA

        // 9. Hitung rata-rata packet loss dari empat tabel
        result ← fetchOne("
            SELECT AVG(packet_loss_percent) FROM (
                SELECT packet_loss_percent FROM connectivity_tests
                UNION ALL SELECT packet_loss_percent FROM latency_tests
                UNION ALL SELECT data_loss_percent FROM throughput_tests
                UNION ALL SELECT packet_loss_percent FROM interference_tests
            ) WHERE packet_loss_percent IS NOT NULL
        ")
        JIKA result["avg"] tidak null MAKA
            stats["avg_packet_loss"] ← round(result["avg"], 2)
        SEBALIKNYA
            stats["avg_packet_loss"] ← null
        AKHIR JIKA

        // 10. Hitung rata-rata konsumsi daya
        result ← fetchOne("SELECT AVG(power_w) FROM power_consumption_tests WHERE power_w IS NOT NULL")
        JIKA result["avg"] tidak null MAKA
            stats["avg_power"] ← round(result["avg"], 2)
        SEBALIKNYA
            stats["avg_power"] ← null
        AKHIR JIKA

        // 11. Tentukan status sistem secara keseluruhan
        stats["system_status"] ← determineSystemStatus(stats)

        KEMBALIKAN stats
    END

    FUNGSI getRecentTests()
    BEGIN
        tests ← {}
        tests["connectivity"] ← fetchAll("SELECT * FROM connectivity_tests ORDER BY test_date DESC LIMIT 5")
        tests["range"]        ← fetchAll("SELECT * FROM range_tests ORDER BY test_date DESC LIMIT 5")
        tests["latency"]      ← fetchAll("SELECT * FROM latency_tests ORDER BY test_date DESC LIMIT 5")
        KEMBALIKAN tests
    END

END KELAS
```

---

## 4. Algoritma Pengambilan Data Grafik

Pseudocode ini menggambarkan pengambilan data untuk visualisasi grafik hubungan jarak dengan berbagai metrik jaringan.

```
FUNGSI getChartData()
BEGIN
    data ← {}

    // Grafik 1: Jarak vs RSSI
    data["distance_rssi"] ← fetchAll("
        SELECT distance_actual_meter, rssi_dbm, location_name,
               test_point_code, status_result, test_date
        FROM range_tests
        WHERE distance_actual_meter IS NOT NULL
          AND rssi_dbm IS NOT NULL
        ORDER BY distance_actual_meter
        LIMIT 30
    ")

    // Grafik 2: Jarak vs SNR
    data["distance_snr"] ← fetchAll("
        SELECT distance_actual_meter, snr_db, location_name,
               test_point_code, status_result, test_date
        FROM range_tests
        WHERE distance_actual_meter IS NOT NULL
          AND snr_db IS NOT NULL
        ORDER BY distance_actual_meter
        LIMIT 30
    ")

    // Grafik 3: Jarak vs Bitrate
    data["distance_bitrate"] ← fetchAll("
        SELECT distance_actual_meter, bitrate_kbps, location_name,
               test_point_code, status_result, test_date
        FROM range_tests
        WHERE distance_actual_meter IS NOT NULL
          AND bitrate_kbps IS NOT NULL
        ORDER BY distance_actual_meter
        LIMIT 30
    ")

    // Grafik 4: Jarak vs Latensi (dikelompokkan per jarak)
    data["distance_latency"] ← fetchAll("
        SELECT
            distance_meter,
            AVG(latency_ms)          AS avg_latency,
            AVG(jitter_ms)           AS avg_jitter,
            AVG(packet_loss_percent) AS avg_packet_loss,
            COUNT(*)                 AS total_tests,
            MIN(test_date)           AS first_test_date,
            MAX(test_date)           AS last_test_date
        FROM latency_tests
        WHERE distance_meter IS NOT NULL
          AND latency_ms IS NOT NULL
        GROUP BY distance_meter
        ORDER BY distance_meter
        LIMIT 30
    ")

    // Grafik 5: Jarak vs Throughput (dikelompokkan per jarak)
    data["distance_throughput"] ← fetchAll("
        SELECT
            distance_meter,
            AVG(throughput_kbps)              AS avg_throughput,
            AVG(packet_delivery_ratio_percent) AS avg_pdr,
            AVG(data_loss_percent)            AS avg_data_loss,
            COUNT(*)                          AS total_tests,
            MIN(test_date)                    AS first_test_date,
            MAX(test_date)                    AS last_test_date
        FROM throughput_tests
        WHERE distance_meter IS NOT NULL
          AND throughput_kbps IS NOT NULL
        GROUP BY distance_meter
        ORDER BY distance_meter
        LIMIT 30
    ")

    KEMBALIKAN data
END
```

---

## 5. Fungsi-Fungsi Kalkulasi Metrik Jaringan

Pseudocode ini menggambarkan kumpulan fungsi pembantu untuk menghitung berbagai parameter teknis sistem Wi-Fi HaLow.

### 5.1 Kalkulasi Packet Loss

```
FUNGSI calculatePacketLoss(sent, received)
BEGIN
    JIKA sent atau received kosong/null/bukan angka MAKA
        KEMBALIKAN null
    AKHIR JIKA

    sent     ← KonversiFloat(sent)
    received ← KonversiFloat(received)

    JIKA sent <= 0 ATAU received < 0 ATAU received > sent MAKA
        KEMBALIKAN null
    AKHIR JIKA

    packetLoss ← ((sent - received) / sent) × 100
    KEMBALIKAN round(packetLoss, 2)
END
```

### 5.2 Kalkulasi Tingkat Keberhasilan (Success Rate)

```
FUNGSI calculateSuccessRate(sent, received)
BEGIN
    JIKA sent atau received kosong/null/bukan angka MAKA
        KEMBALIKAN null
    AKHIR JIKA

    sent     ← KonversiFloat(sent)
    received ← KonversiFloat(received)

    JIKA sent <= 0 ATAU received < 0 ATAU received > sent MAKA
        KEMBALIKAN null
    AKHIR JIKA

    successRate ← (received / sent) × 100
    KEMBALIKAN round(successRate, 2)
END
```

### 5.3 Kalkulasi Jarak 3D

```
FUNGSI calculate3DDistance(x, y, z)
BEGIN
    jarak ← sqrt(x² + y² + z²)
    KEMBALIKAN round(jarak, 2)
END
```

### 5.4 Kalkulasi FSPL (Free Space Path Loss)

```
FUNGSI calculateFSPL(frequencyMHz, distanceKm)
BEGIN
    // Rumus FSPL: 32.44 + 20·log10(f) + 20·log10(d)
    fspl ← 32.44 + 20 × log10(frequencyMHz) + 20 × log10(distanceKm)
    KEMBALIKAN round(fspl, 2)
END
```

### 5.5 Kalkulasi Signal Margin

```
FUNGSI calculateSignalMargin(rssi, sensitivity = -90)
BEGIN
    margin ← rssi - sensitivity
    KEMBALIKAN round(margin, 2)
END
```

### 5.6 Kalkulasi Throughput

```
FUNGSI calculateThroughput(dataKB, timeSec)
BEGIN
    JIKA dataKB atau timeSec kosong/null/bukan angka MAKA
        KEMBALIKAN null
    AKHIR JIKA

    dataKB  ← KonversiFloat(dataKB)
    timeSec ← KonversiFloat(timeSec)

    JIKA timeSec <= 0 ATAU dataKB < 0 MAKA
        KEMBALIKAN null
    AKHIR JIKA

    // Konversi KB ke bit, bagi dengan waktu dalam ms
    throughput ← (dataKB × 1024 × 8) / (timeSec × 1000)
    KEMBALIKAN round(throughput, 2)   // Satuan: Kbps
END
```

### 5.7 Kalkulasi Konsumsi Daya

```
FUNGSI calculatePower(voltage, current)
BEGIN
    JIKA voltage atau current kosong/null/bukan angka MAKA
        KEMBALIKAN null
    AKHIR JIKA

    voltage ← KonversiFloat(voltage)
    current ← KonversiFloat(current)

    JIKA voltage < 0 ATAU current < 0 MAKA
        KEMBALIKAN null
    AKHIR JIKA

    power ← voltage × current
    KEMBALIKAN round(power, 2)   // Satuan: Watt
END
```

### 5.8 Kalkulasi Energi

```
FUNGSI calculateEnergy(power, duration)
BEGIN
    JIKA power atau duration kosong/null/bukan angka MAKA
        KEMBALIKAN null
    AKHIR JIKA

    power    ← KonversiFloat(power)
    duration ← KonversiFloat(duration)

    JIKA power < 0 ATAU duration < 0 MAKA
        KEMBALIKAN null
    AKHIR JIKA

    energy ← power × duration
    KEMBALIKAN round(energy, 4)   // Satuan: Joule (Watt × detik)
END
```

### 5.9 Kalkulasi Kapasitas Baterai

```
FUNGSI calculateBatteryCapacityWh(voltage, capacitymAh)
BEGIN
    JIKA voltage atau capacitymAh kosong/null/bukan angka MAKA
        KEMBALIKAN null
    AKHIR JIKA

    voltage      ← KonversiFloat(voltage)
    capacitymAh  ← KonversiFloat(capacitymAh)

    JIKA voltage <= 0 ATAU capacitymAh <= 0 MAKA
        KEMBALIKAN null
    AKHIR JIKA

    capacityWh ← (voltage × capacitymAh) / 1000
    KEMBALIKAN round(capacityWh, 4)   // Satuan: Watt-hour
END
```

### 5.10 Kalkulasi Estimasi Waktu Operasi

```
FUNGSI calculateRuntime(capacityWh, powerW)
BEGIN
    JIKA capacityWh atau powerW kosong/null/bukan angka MAKA
        KEMBALIKAN null
    AKHIR JIKA

    capacityWh ← KonversiFloat(capacityWh)
    powerW     ← KonversiFloat(powerW)

    JIKA capacityWh <= 0 ATAU powerW <= 0 MAKA
        KEMBALIKAN null
    AKHIR JIKA

    runtime ← capacityWh / powerW
    KEMBALIKAN round(runtime, 2)   // Satuan: Jam
END
```

---

## 6. Algoritma Penentuan Status Sistem

Pseudocode ini menggambarkan logika klasifikasi status kualitas jaringan berdasarkan nilai metrik.

### 6.1 Penentuan Status Uji Jangkauan

```
FUNGSI determineRangeStatus(snr, packetLoss)
BEGIN
    JIKA snr atau packetLoss kosong/null MAKA
        KEMBALIKAN null
    AKHIR JIKA

    JIKA snr > 20 DAN packetLoss < 5 MAKA
        KEMBALIKAN "good"       // Sinyal Baik
    SEBALIKNYA JIKA snr >= 10 DAN snr <= 20 MAKA
        KEMBALIKAN "moderate"   // Sinyal Sedang
    SEBALIKNYA
        KEMBALIKAN "poor"       // Sinyal Buruk
    AKHIR JIKA
END
```

### 6.2 Penentuan Kualitas Koneksi Berdasarkan RSSI

```
FUNGSI determineConnectionQuality(rssi)
BEGIN
    JIKA rssi kosong/null/bukan angka MAKA
        KEMBALIKAN "N/A"
    AKHIR JIKA

    JIKA rssi >= -50 MAKA KEMBALIKAN "Excellent"   // Sangat Baik
    JIKA rssi >= -60 MAKA KEMBALIKAN "Good"        // Baik
    JIKA rssi >= -70 MAKA KEMBALIKAN "Fair"        // Cukup
    JIKA rssi >= -80 MAKA KEMBALIKAN "Weak"        // Lemah
    KEMBALIKAN "Poor"                              // Buruk
END
```

### 6.3 Penentuan Status Keseluruhan Sistem

```
FUNGSI determineSystemStatus(metrics)
BEGIN
    criticalCount ← 0
    degradedCount ← 0

    // Periksa kondisi kritis
    JIKA metrics["avg_latency"] > 500 MAKA
        criticalCount ← criticalCount + 1    // Latensi terlalu tinggi
    AKHIR JIKA

    JIKA metrics["avg_packet_loss"] > 20 MAKA
        criticalCount ← criticalCount + 1    // Packet loss terlalu besar
    AKHIR JIKA

    // Periksa kondisi terdegradasi
    JIKA metrics["avg_throughput"] < 100 MAKA
        degradedCount ← degradedCount + 1    // Throughput rendah
    AKHIR JIKA

    JIKA metrics["avg_rssi"] < -85 MAKA
        degradedCount ← degradedCount + 1    // Sinyal sangat lemah
    AKHIR JIKA

    // Tentukan status akhir
    JIKA criticalCount > 0 MAKA
        KEMBALIKAN "critical"    // Sistem dalam kondisi kritis
    AKHIR JIKA

    JIKA degradedCount > 2 MAKA
        KEMBALIKAN "degraded"    // Sistem terdegradasi
    AKHIR JIKA

    KEMBALIKAN "stable"          // Sistem stabil
END
```

### 6.4 Penentuan Kualitas Kamera

```
FUNGSI determineCameraQuality(score)
BEGIN
    JIKA score >= 4 MAKA KEMBALIKAN "good"
    JIKA score >= 3 MAKA KEMBALIKAN "moderate"
    KEMBALIKAN "poor"
END
```

---

## 7. Algoritma Manajemen Hak Akses

Pseudocode ini menggambarkan mekanisme kontrol akses berbasis peran (Role-Based Access Control).

```
FUNGSI isLoggedIn()
BEGIN
    KEMBALIKAN (SESSION["user_id"] terdefinisi)
END

FUNGSI checkRole(requiredRole)
BEGIN
    KEMBALIKAN (SESSION["user_role"] terdefinisi DAN SESSION["user_role"] = requiredRole)
END

FUNGSI currentUserRole()
BEGIN
    JIKA SESSION["user_role"] terdefinisi MAKA
        KEMBALIKAN SESSION["user_role"]
    SEBALIKNYA
        KEMBALIKAN null
    AKHIR JIKA
END

FUNGSI isViewerRole()
BEGIN
    KEMBALIKAN (currentUserRole() = "viewer")
END

FUNGSI canManageProject()
BEGIN
    // Admin dan researcher boleh mengelola, viewer hanya boleh melihat
    KEMBALIKAN (isLoggedIn() DAN BUKAN isViewerRole())
END
```

---

## 8. Algoritma Sanitasi Input

Pseudocode ini menggambarkan proses pembersihan dan validasi data masukan pengguna untuk keamanan sistem.

```
FUNGSI sanitize(data)
BEGIN
    // Langkah 1: Hapus spasi di awal dan akhir
    data ← trim(data)

    // Langkah 2: Hapus tag HTML berbahaya
    data ← strip_tags(data)

    // Langkah 3: Konversi karakter khusus ke entitas HTML
    data ← htmlspecialchars(data)

    KEMBALIKAN data
END
```

---

## 9. Alur Sistem Keseluruhan

Pseudocode ini menggambarkan alur utama sistem dari awal hingga tampilan dashboard.

```
PROGRAM SistemPemantauanWiFiHaLow

BEGIN
    // === INISIALISASI ===
    KonfigurasiDatabase()          // Sambungkan ke database
    Muat file helper fungsi        // Load functions.php

    // === AUTENTIKASI ===
    JIKA BUKAN isLoggedIn() MAKA
        controller ← LoginController(pdo)
        controller.index()         // Tampilkan halaman login
        JIKA POST request masuk MAKA
            controller.login()     // Proses login
        AKHIR JIKA
        Hentikan eksekusi
    AKHIR JIKA

    // === DASHBOARD ===
    dashboardController ← DashboardController(pdo)

    // Ambil data statistik ringkasan
    stats ← dashboardController.getStats()
    /*
        stats berisi:
        - total_connectivity  : jumlah uji konektivitas
        - total_range         : jumlah uji jangkauan
        - total_locations     : jumlah lokasi unik
        - total_nodes         : jumlah node aktif
        - avg_latency         : rata-rata latensi (ms)
        - avg_throughput      : rata-rata throughput (Kbps)
        - avg_rssi            : rata-rata kekuatan sinyal (dBm)
        - avg_snr             : rata-rata SNR (dB)
        - avg_packet_loss     : rata-rata packet loss (%)
        - avg_power           : rata-rata konsumsi daya (W)
        - system_status       : status sistem (stable/degraded/critical)
    */

    // Ambil data pengujian terbaru
    recentTests ← dashboardController.getRecentTests()

    // Ambil data untuk visualisasi grafik
    chartData ← dashboardController.getChartData()
    /*
        chartData berisi:
        - distance_rssi       : data jarak vs RSSI
        - distance_snr        : data jarak vs SNR
        - distance_bitrate    : data jarak vs Bitrate
        - distance_latency    : data jarak vs Latensi (rata-rata per jarak)
        - distance_throughput : data jarak vs Throughput (rata-rata per jarak)
    */

    // Render halaman dashboard dengan semua data
    Tampilkan view dashboard dengan:
        - stats (statistik ringkasan)
        - recentTests (data terbaru)
        - chartData (data grafik)

END PROGRAM
```

---

## Ringkasan Alur Data

```
                    ┌─────────────────────────────────────┐
                    │         PENGGUNA / USER              │
                    └──────────────┬──────────────────────┘
                                   │  HTTP Request
                                   ▼
                    ┌─────────────────────────────────────┐
                    │         AUTENTIKASI                  │
                    │   isLoggedIn() → Session Check       │
                    │   login() → password_verify()        │
                    └──────────────┬──────────────────────┘
                                   │ Login berhasil
                                   ▼
          ┌────────────────────────────────────────────────────┐
          │              DASHBOARD CONTROLLER                   │
          │  ┌─────────────┐  ┌──────────────┐  ┌──────────┐  │
          │  │  getStats() │  │getRecentTests│  │getChart  │  │
          │  │             │  │    ()        │  │  Data()  │  │
          │  └──────┬──────┘  └──────┬───────┘  └────┬─────┘  │
          └─────────┼────────────────┼───────────────┼────────┘
                    │                │               │
                    ▼                ▼               ▼
          ┌─────────────────────────────────────────────────────┐
          │                    DATABASE                          │
          │  connectivity_tests │ range_tests │ latency_tests    │
          │  throughput_tests   │ interference_tests             │
          │  signal_penetration │ power_consumption_tests        │
          │  slave_camera_tests │ command_execution_tests        │
          └─────────────────────────────────────────────────────┘
                    │
                    ▼
          ┌─────────────────────────────────────────────────────┐
          │              FUNGSI KALKULASI                        │
          │  calculatePacketLoss()    │ calculateThroughput()    │
          │  calculateFSPL()          │ calculatePower()         │
          │  determineSystemStatus()  │ determineConnectionQual  │
          └─────────────────────────────────────────────────────┘
                    │
                    ▼
          ┌─────────────────────────────────────────────────────┐
          │                TAMPILAN / VIEW                       │
          │         Dashboard dengan Grafik dan Tabel            │
          └─────────────────────────────────────────────────────┘
```

---

*Pseudocode ini disusun untuk keperluan presentasi sidang skripsi.*  
*Sistem: Perancangan dan Implementasi Sistem Pemantauan dan Dukungan Komunikasi Taktis Berbasis Wi-Fi HaLow*
