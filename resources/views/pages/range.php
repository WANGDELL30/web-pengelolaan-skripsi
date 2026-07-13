<?php
if (!function_exists('rangeGpsValue')) {
    function rangeGpsValue($value, $type = 'lat') {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        $coordinate = (float) $value;

        if ($type === 'lat' && ($coordinate < -90 || $coordinate > 90)) {
            return null;
        }

        if ($type === 'lng' && ($coordinate < -180 || $coordinate > 180)) {
            return null;
        }

        return $coordinate;
    }
}

if (!function_exists('rangeHasGpsPair')) {
    function rangeHasGpsPair($masterLat, $masterLng, $slaveLat, $slaveLng) {
        return $masterLat !== null && $masterLng !== null && $slaveLat !== null && $slaveLng !== null;
    }
}

if (!function_exists('rangeHaversineDistance')) {
    function rangeHaversineDistance($masterLat, $masterLng, $slaveLat, $slaveLng) {
        $earthRadius = 6371000;
        $lat1 = deg2rad((float) $masterLat);
        $lat2 = deg2rad((float) $slaveLat);
        $deltaLat = deg2rad((float) $slaveLat - (float) $masterLat);
        $deltaLng = deg2rad((float) $slaveLng - (float) $masterLng);

        $a = sin($deltaLat / 2) * sin($deltaLat / 2)
            + cos($lat1) * cos($lat2) * sin($deltaLng / 2) * sin($deltaLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }
}

if (!function_exists('rangeGpsOffsetMeters')) {
    function rangeGpsOffsetMeters($masterLat, $masterLng, $slaveLat, $slaveLng) {
        $earthRadius = 6371000;
        $lat1 = deg2rad((float) $masterLat);
        $lat2 = deg2rad((float) $slaveLat);
        $deltaLat = deg2rad((float) $slaveLat - (float) $masterLat);
        $deltaLng = deg2rad((float) $slaveLng - (float) $masterLng);
        $avgLat = ($lat1 + $lat2) / 2;

        return [
            round($earthRadius * $deltaLng * cos($avgLat), 2),
            round($earthRadius * $deltaLat, 2),
        ];
    }
}

if (!function_exists('rangeBearingDegrees')) {
    function rangeBearingDegrees($masterLat, $masterLng, $slaveLat, $slaveLng) {
        $lat1 = deg2rad((float) $masterLat);
        $lat2 = deg2rad((float) $slaveLat);
        $deltaLng = deg2rad((float) $slaveLng - (float) $masterLng);
        $y = sin($deltaLng) * cos($lat2);
        $x = cos($lat1) * sin($lat2) - sin($lat1) * cos($lat2) * cos($deltaLng);

        return round(fmod(rad2deg(atan2($y, $x)) + 360, 360), 1);
    }
}

if (!function_exists('rangeFormatGpsPair')) {
    function rangeFormatGpsPair($lat, $lng) {
        $latValue = rangeGpsValue($lat, 'lat');
        $lngValue = rangeGpsValue($lng, 'lng');

        if ($latValue === null || $lngValue === null) {
            return '-';
        }

        $formatCoordinate = function ($value) {
            $coordinate = rtrim(rtrim((string) $value, '0'), '.');

            return $coordinate === '-0' ? '0' : $coordinate;
        };

        return htmlspecialchars($formatCoordinate($lat) . ', ' . $formatCoordinate($lng));
    }
}

$pageConfig = [
    'title' => 'Range Test',
    'icon' => 'fas fa-ruler',
    'description' => 'Input dan analisis data pengujian jangkauan WiFi HaLow.',
    'table' => 'range_tests',
    'order' => 'test_date DESC, created_at DESC',
    'chart_metrics' => [
        ['field' => 'rssi_dbm', 'label' => 'RSSI', 'unit' => 'dBm', 'type' => 'line'],
        ['field' => 'snr_db', 'label' => 'SNR', 'unit' => 'dB', 'type' => 'line'],
        ['field' => 'fspl_db', 'label' => 'FSPL', 'unit' => 'dB', 'type' => 'bar'],
        ['field' => 'bitrate_kbps', 'label' => 'Bitrate', 'unit' => 'kbps', 'type' => 'line'],
    ],
    'fields' => [
        ['name' => 'test_date', 'label' => 'Test Date', 'type' => 'date', 'required' => true],
        ['name' => 'location_name', 'label' => 'Location Name', 'required' => true],
        ['name' => 'environment_type', 'label' => 'Environment Type', 'type' => 'select', 'required' => true, 'options' => ['lapangan', 'hangar', 'pantai', 'gunung', 'indoor', 'outdoor']],
        ['name' => 'test_point_code', 'label' => 'Test Point Code', 'required' => true],
        ['name' => 'direction', 'label' => 'Direction', 'type' => 'select', 'options' => ['north', 'south', 'east', 'west', 'vertical', 'diagonal']],
        ['name' => 'master_gps_latitude', 'label' => 'Master GPS Latitude', 'type' => 'number', 'step' => 'any', 'preserve_precision' => true, 'col' => 'col-md-3'],
        ['name' => 'master_gps_longitude', 'label' => 'Master GPS Longitude', 'type' => 'number', 'step' => 'any', 'preserve_precision' => true, 'col' => 'col-md-3'],
        ['name' => 'gps_latitude', 'label' => 'Slave GPS Latitude', 'type' => 'number', 'step' => 'any', 'preserve_precision' => true, 'col' => 'col-md-3'],
        ['name' => 'gps_longitude', 'label' => 'Slave GPS Longitude', 'type' => 'number', 'step' => 'any', 'preserve_precision' => true, 'col' => 'col-md-3'],
        ['name' => 'coordinate_x_meter', 'label' => 'Coordinate X Auto (m)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'coordinate_y_meter', 'label' => 'Coordinate Y Auto (m)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'coordinate_z_meter', 'label' => 'Coordinate Z / Elevation (m)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'distance_actual_meter', 'label' => 'Master-Slave Distance Auto (m)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'frequency_mhz', 'label' => 'Frequency (MHz)', 'type' => 'number', 'step' => '0.01', 'default' => 915],
        ['name' => 'rssi_dbm', 'label' => 'RSSI (dBm)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'snr_db', 'label' => 'SNR (dB)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'bitrate_kbps', 'label' => 'Bitrate (kbps)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'connection_status', 'label' => 'Connection Status', 'type' => 'select', 'options' => ['connected', 'disconnected', 'intermittent']],
        ['name' => 'receiver_sensitivity_dbm', 'label' => 'Receiver Sensitivity (dBm)', 'type' => 'number', 'step' => '0.01', 'default' => -90],
        ['name' => 'photo_video_link', 'label' => 'Photo/Video Link'],
        ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
    ],
    'calculate' => function ($data) {
        $masterLat = rangeGpsValue($data['master_gps_latitude'] ?? null, 'lat');
        $masterLng = rangeGpsValue($data['master_gps_longitude'] ?? null, 'lng');
        $slaveLat = rangeGpsValue($data['gps_latitude'] ?? null, 'lat');
        $slaveLng = rangeGpsValue($data['gps_longitude'] ?? null, 'lng');
        $x = is_numeric($data['coordinate_x_meter'] ?? null) ? (float) $data['coordinate_x_meter'] : null;
        $y = is_numeric($data['coordinate_y_meter'] ?? null) ? (float) $data['coordinate_y_meter'] : null;
        $z = is_numeric($data['coordinate_z_meter'] ?? null) ? (float) $data['coordinate_z_meter'] : null;
        $distanceMeter = is_numeric($data['distance_actual_meter'] ?? null) ? (float) $data['distance_actual_meter'] : null;

        if (rangeHasGpsPair($masterLat, $masterLng, $slaveLat, $slaveLng)) {
            $distanceMeter = rangeHaversineDistance($masterLat, $masterLng, $slaveLat, $slaveLng);
            [$x, $y] = rangeGpsOffsetMeters($masterLat, $masterLng, $slaveLat, $slaveLng);
        } elseif (($distanceMeter === null || $distanceMeter <= 0) && $x !== null && $y !== null) {
            $distanceMeter = sqrt(($x * $x) + ($y * $y));
        }

        $distanceKm = $distanceMeter !== null && $distanceMeter > 0 ? $distanceMeter / 1000 : null;
        $snr = is_numeric($data['snr_db'] ?? null) ? (float) $data['snr_db'] : null;
        $status = ($data['connection_status'] ?? null) === 'disconnected' ? 'poor' : determineRangeStatus($snr, 0);
        $hasCoordinates = $x !== null && $y !== null;
        $distance3d = $hasCoordinates ? sqrt(($x * $x) + ($y * $y) + (($z ?? 0) * ($z ?? 0))) : null;
        $frequency = is_numeric($data['frequency_mhz'] ?? null) ? (float) $data['frequency_mhz'] : null;
        $rssi = is_numeric($data['rssi_dbm'] ?? null) ? (float) $data['rssi_dbm'] : null;
        $receiverSensitivity = is_numeric($data['receiver_sensitivity_dbm'] ?? null) ? (float) $data['receiver_sensitivity_dbm'] : null;

        return [
            'coordinate_x_meter' => $x === null ? null : round($x, 2),
            'coordinate_y_meter' => $y === null ? null : round($y, 2),
            'distance_actual_meter' => $distanceMeter === null ? null : round($distanceMeter, 2),
            'distance_3d_meter' => $distance3d === null ? null : round($distance3d, 2),
            'distance_km' => $distanceKm === null ? null : round($distanceKm, 4),
            'fspl_db' => $distanceKm !== null && $frequency !== null ? calculateFSPL($frequency, $distanceKm) : null,
            'signal_margin' => $rssi !== null && $receiverSensitivity !== null ? calculateSignalMargin($rssi, $receiverSensitivity) : null,
            'status_result' => $status,
        ];
    },
    'formulas' => [
        'GPS Distance = Haversine(Master GPS, Slave GPS)',
        'Coordinate X/Y otomatis dari selisih longitude/latitude GPS',
        'Distance 3D = sqrt(x^2 + y^2 + z^2)',
        'FSPL = 32.44 + 20log10(frequency) + 20log10(distance km)',
        'Signal Margin = RSSI - Receiver Sensitivity',
    ],
    'columns' => [
        ['label' => 'Date', 'field' => 'test_date', 'format' => 'date'],
        ['label' => 'Location', 'field' => 'location_name'],
        ['label' => 'Point', 'field' => 'test_point_code'],
        ['label' => 'Master GPS', 'value' => fn($row) => rangeFormatGpsPair($row['master_gps_latitude'] ?? null, $row['master_gps_longitude'] ?? null)],
        ['label' => 'Slave GPS', 'value' => fn($row) => rangeFormatGpsPair($row['gps_latitude'] ?? null, $row['gps_longitude'] ?? null)],
        ['label' => 'Distance', 'field' => 'distance_actual_meter', 'decimals' => 2, 'suffix' => ' m'],
        ['label' => 'RSSI', 'field' => 'rssi_dbm', 'decimals' => 2, 'suffix' => ' dBm'],
        ['label' => 'SNR', 'field' => 'snr_db', 'decimals' => 2, 'suffix' => ' dB'],
        ['label' => 'FSPL', 'field' => 'fspl_db', 'decimals' => 2, 'suffix' => ' dB'],
        ['label' => 'Status', 'field' => 'status_result', 'format' => 'status'],
    ],
];

include __DIR__ . '/_test_page.php';
?>

<style>
    .live-gps-card {
        border: 1px solid #dbe4ef;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
    }

    .live-gps-card .card-header {
        border-bottom: 1px solid #e5edf7;
        background: #ffffff;
    }

    .live-gps-status {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0;
        border: 1px solid transparent;
    }

    .live-gps-dot {
        width: 9px;
        height: 9px;
        border-radius: 50%;
        background: currentColor;
        box-shadow: 0 0 0 4px rgba(100, 116, 139, 0.12);
    }

    .live-gps-status-waiting,
    .live-gps-status-loading {
        color: #475569;
        background: #f1f5f9;
        border-color: #cbd5e1;
    }

    .live-gps-status-no-fix {
        color: #92400e;
        background: #fffbeb;
        border-color: #fde68a;
    }

    .live-gps-status-fix {
        color: #166534;
        background: #dcfce7;
        border-color: #86efac;
    }

    .live-gps-status-error {
        color: #991b1b;
        background: #fee2e2;
        border-color: #fecaca;
    }

    .live-gps-metric {
        height: 100%;
        min-height: 82px;
        padding: 12px 14px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #f8fafc;
    }

    .live-gps-metric span {
        display: block;
        color: #64748b;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
    }

    .live-gps-metric strong {
        display: block;
        margin-top: 7px;
        color: #0f172a;
        font-size: 18px;
        font-weight: 800;
        line-height: 1.2;
        overflow-wrap: anywhere;
    }

    .live-gps-bars {
        display: grid;
        gap: 18px;
    }

    .live-gps-axis {
        padding: 14px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #ffffff;
    }

    .live-gps-axis-head {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        gap: 12px;
        margin-bottom: 10px;
    }

    .live-gps-axis-head span {
        color: #64748b;
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
    }

    .live-gps-axis-head strong {
        color: #0f172a;
        font-size: 14px;
        font-weight: 800;
    }

    .live-gps-track {
        position: relative;
        height: 14px;
        border-radius: 999px;
        background: linear-gradient(90deg, #dbeafe 0%, #e2e8f0 50%, #dcfce7 100%);
        overflow: visible;
    }

    .live-gps-zero {
        position: absolute;
        top: -5px;
        bottom: -5px;
        left: 50%;
        width: 2px;
        background: rgba(15, 23, 42, 0.28);
    }

    .live-gps-fill {
        position: absolute;
        top: 3px;
        height: 8px;
        border-radius: 999px;
        background: #2563eb;
        transition: left 0.25s ease, width 0.25s ease;
    }

    .live-gps-marker {
        position: absolute;
        top: 50%;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: #0f172a;
        border: 3px solid #ffffff;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.22);
        transform: translate(-50%, -50%);
        transition: left 0.25s ease, opacity 0.25s ease;
    }

    .live-gps-marker.is-empty {
        opacity: 0.45;
        background: #64748b;
    }

    .live-gps-scale {
        display: flex;
        justify-content: space-between;
        margin-top: 8px;
        color: #64748b;
        font-size: 12px;
        font-weight: 700;
    }

    .live-gps-message {
        padding: 10px 12px;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        color: #475569;
        font-size: 13px;
        line-height: 1.45;
    }

    .live-gps-message-fix {
        border-color: #bbf7d0;
        background: #f0fdf4;
        color: #166534;
    }

    .live-gps-message-no-fix {
        border-color: #fde68a;
        background: #fffbeb;
        color: #92400e;
    }

    .live-gps-message-error {
        border-color: #fecaca;
        background: #fef2f2;
        color: #991b1b;
    }

    .live-gps-autofill-section {
        padding: 16px;
        border: 2px dashed #c7d2fe;
        border-radius: 10px;
        background: linear-gradient(135deg, #eef2ff 0%, #f0fdf4 100%);
    }

    .live-gps-autofill-section.is-active {
        border-color: #86efac;
        background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    }

    .live-gps-autofill-toggle {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 14px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        border: 2px solid #c7d2fe;
        background: #ffffff;
        color: #4338ca;
        transition: all 0.2s ease;
    }

    .live-gps-autofill-toggle:hover {
        background: #eef2ff;
    }

    .live-gps-autofill-toggle.is-active {
        border-color: #16a34a;
        background: #dcfce7;
        color: #166534;
    }

    .live-gps-autofill-toggle .toggle-icon {
        transition: transform 0.3s ease;
    }

    .live-gps-autofill-toggle.is-active .toggle-icon {
        transform: rotate(180deg);
    }

    .live-gps-autofill-indicator {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 700;
        background: #f1f5f9;
        color: #64748b;
    }

    .live-gps-autofill-indicator.is-active {
        background: #dcfce7;
        color: #166534;
        animation: gps-pulse 2s infinite;
    }

    @keyframes gps-pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }

    @media (max-width: 767.98px) {
        .live-gps-axis-head {
            display: block;
        }

        .live-gps-axis-head strong {
            display: block;
            margin-top: 4px;
        }
    }
</style>

<div class="content-section">
    <div class="card live-gps-card">
        <div class="card-header py-3 d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-location-crosshairs"></i> Live GPS Slave
                </h6>
                <small class="text-muted">Data real-time dari modul GPS 7M pada slave ESP32. Koordinat bisa digunakan otomatis atau input manual.</small>
            </div>
            <span class="live-gps-status live-gps-status-waiting" id="liveGpsStateBadge">
                <span class="live-gps-dot"></span>
                Waiting
            </span>
        </div>
        <div class="card-body">
            <div class="row g-3 align-items-end mb-3">
                <div class="col-lg-7">
                    <label for="liveGpsApiUrl" class="form-label">GPS Status API URL</label>
                    <input type="url" class="form-control" id="liveGpsApiUrl" value="http://192.168.1.112/api/status" placeholder="http://192.168.1.112/api/status">
                </div>
                <div class="col-lg-5">
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-primary" id="liveGpsSaveUrl">
                            <i class="fas fa-link"></i> Pakai URL
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="liveGpsRefreshNow">
                            <i class="fas fa-rotate"></i> Refresh
                        </button>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-3 col-6">
                    <div class="live-gps-metric">
                        <span>Latitude</span>
                        <strong id="liveGpsLatitude">-</strong>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="live-gps-metric">
                        <span>Longitude</span>
                        <strong id="liveGpsLongitude">-</strong>
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="live-gps-metric">
                        <span>Satelit</span>
                        <strong id="liveGpsSatellites">0</strong>
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="live-gps-metric">
                        <span>NMEA</span>
                        <strong id="liveGpsNmea">-</strong>
                    </div>
                </div>
                <div class="col-md-2 col-12">
                    <div class="live-gps-metric">
                        <span>Update</span>
                        <strong id="liveGpsUpdated">-</strong>
                    </div>
                </div>
            </div>

            <!-- Auto-fill Section -->
            <div class="live-gps-autofill-section mb-4" id="liveGpsAutofillSection">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <button type="button" class="live-gps-autofill-toggle" id="liveGpsAutofillToggle">
                            <i class="fas fa-satellite-dish toggle-icon"></i>
                            <span id="liveGpsAutofillToggleLabel">Auto-fill OFF</span>
                        </button>
                        <span class="live-gps-autofill-indicator" id="liveGpsAutofillIndicator">
                            <i class="fas fa-circle-dot"></i>
                            <span id="liveGpsAutofillIndicatorLabel">Manual mode</span>
                        </span>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="liveGpsApplyOnce" title="Terapkan koordinat GPS saat ini ke form sekali saja">
                        <i class="fas fa-crosshairs"></i> Terapkan Sekarang
                    </button>
                </div>
                <small class="text-muted d-block">
                    <i class="fas fa-info-circle"></i>
                    <strong>Auto-fill:</strong> Saat aktif, field <em>Slave GPS Latitude</em> & <em>Longitude</em> di form input akan otomatis terisi dari modul GPS setiap ada update.
                    Anda tetap bisa mengetik manual kapan saja — auto-fill akan berhenti sementara saat field diedit manual.
                    Klik <strong>Terapkan Sekarang</strong> untuk mengisi sekali tanpa mengaktifkan auto-fill terus-menerus.
                </small>
            </div>

            <div class="live-gps-bars">
                <div class="live-gps-axis">
                    <div class="live-gps-axis-head">
                        <span>Latitude range</span>
                        <strong id="liveGpsLatitudeLabel">Belum ada fix</strong>
                    </div>
                    <div class="live-gps-track">
                        <div class="live-gps-zero"></div>
                        <div class="live-gps-fill" id="liveGpsLatitudeFill"></div>
                        <div class="live-gps-marker" id="liveGpsLatitudeMarker"></div>
                    </div>
                    <div class="live-gps-scale">
                        <span>-90</span>
                        <span>0</span>
                        <span>90</span>
                    </div>
                </div>

                <div class="live-gps-axis">
                    <div class="live-gps-axis-head">
                        <span>Longitude range</span>
                        <strong id="liveGpsLongitudeLabel">Belum ada fix</strong>
                    </div>
                    <div class="live-gps-track">
                        <div class="live-gps-zero"></div>
                        <div class="live-gps-fill" id="liveGpsLongitudeFill"></div>
                        <div class="live-gps-marker" id="liveGpsLongitudeMarker"></div>
                    </div>
                    <div class="live-gps-scale">
                        <span>-180</span>
                        <span>0</span>
                        <span>180</span>
                    </div>
                </div>
            </div>

            <div class="live-gps-message mt-3" id="liveGpsMessage">
                Menunggu data GPS dari slave.
            </div>
        </div>
    </div>
</div>

<script>
(function initLiveGpsPanel() {
    var panel = document.querySelector('.live-gps-card');
    if (!panel) return;

    var storageKey = 'wifiHalowGpsStatusUrl';
    var autofillKey = 'wifiHalowGpsAutofill';
    var refreshMs = 5000;
    var fetchTimeoutMs = 4000;
    var timer = null;
    var activeRequest = null;
    var autofillEnabled = localStorage.getItem(autofillKey) === '1';
    var lastGpsLat = null;
    var lastGpsLng = null;
    var manualOverride = false;

    var apiInput = document.getElementById('liveGpsApiUrl');
    var saveButton = document.getElementById('liveGpsSaveUrl');
    var refreshButton = document.getElementById('liveGpsRefreshNow');
    var stateBadge = document.getElementById('liveGpsStateBadge');
    var message = document.getElementById('liveGpsMessage');
    var autofillToggle = document.getElementById('liveGpsAutofillToggle');
    var autofillSection = document.getElementById('liveGpsAutofillSection');
    var autofillIndicator = document.getElementById('liveGpsAutofillIndicator');
    var autofillIndicatorLabel = document.getElementById('liveGpsAutofillIndicatorLabel');
    var autofillToggleLabel = document.getElementById('liveGpsAutofillToggleLabel');
    var applyOnceButton = document.getElementById('liveGpsApplyOnce');

    function text(id, value) {
        var el = document.getElementById(id);
        if (el) el.textContent = value;
    }

    function numberValue(value) {
        var parsed = Number(value);
        return Number.isFinite(parsed) ? parsed : null;
    }

    function formatCoord(value) {
        var parsed = numberValue(value);
        return parsed === null ? '-' : parsed.toFixed(6);
    }

    function normalizeApiUrl(value) {
        var url = String(value || '').trim();
        if (url === '') {
            url = 'http://192.168.1.112/api/status';
        }

        if (!/^https?:\/\//i.test(url)) {
            url = 'http://' + url;
        }

        try {
            var parsed = new URL(url);
            if (parsed.pathname === '/' || parsed.pathname === '') {
                parsed.pathname = '/api/status';
            }
            return parsed.href;
        } catch (error) {
            return url;
        }
    }

    function setStatus(state, label) {
        if (!stateBadge) return;

        stateBadge.className = 'live-gps-status live-gps-status-' + state;
        stateBadge.innerHTML = '<span class="live-gps-dot"></span>' + label;
    }

    function setAxis(prefix, value, min, max) {
        var marker = document.getElementById(prefix + 'Marker');
        var fill = document.getElementById(prefix + 'Fill');
        var label = document.getElementById(prefix + 'Label');
        var parsed = numberValue(value);
        var zeroPct = ((0 - min) / (max - min)) * 100;

        if (!marker || !fill || !label) return;

        if (parsed === null) {
            marker.style.left = zeroPct + '%';
            fill.style.left = zeroPct + '%';
            fill.style.width = '0%';
            marker.classList.add('is-empty');
            label.textContent = 'Belum ada fix';
            return;
        }

        var pct = ((Math.max(min, Math.min(max, parsed)) - min) / (max - min)) * 100;
        var left = Math.min(pct, zeroPct);
        var width = Math.abs(pct - zeroPct);

        marker.style.left = pct + '%';
        fill.style.left = left + '%';
        fill.style.width = width + '%';
        marker.classList.remove('is-empty');
        label.textContent = parsed.toFixed(6);
    }

    function stateFromGps(gps) {
        if (!gps) {
            return { cls: 'error', label: 'API Error' };
        }

        if (gps.last_error && gps.last_error !== 'ESP_OK') {
            return { cls: 'error', label: 'GPS Error' };
        }

        if (gps.fix_valid) {
            return { cls: 'fix', label: 'GPS Fix' };
        }

        if (gps.nmea_seen) {
            return { cls: 'no-fix', label: 'No Fix' };
        }

        return { cls: 'waiting', label: 'Waiting NMEA' };
    }

    function messageFromGps(gps) {
        if (!gps) {
            return 'API tidak mengembalikan field gps.';
        }

        if (gps.fix_valid) {
            return 'GPS sudah mendapat fix. Latitude dan longitude siap dipakai.' + (autofillEnabled ? ' Auto-fill aktif — form Slave GPS diperbarui otomatis.' : '');
        }

        if (gps.nmea_seen) {
            return 'Data NMEA sudah masuk, tetapi modul GPS belum lock satelit. Bawa antena ke area terbuka.';
        }

        return 'Belum ada data NMEA dari GPS. Cek kabel GPS TX ke GPIO44/D7, GND, power, dan baud 9600.';
    }

    function updateAutofillUi() {
        if (autofillToggle) {
            autofillToggle.classList.toggle('is-active', autofillEnabled);
        }
        if (autofillToggleLabel) {
            autofillToggleLabel.textContent = autofillEnabled ? 'Auto-fill ON' : 'Auto-fill OFF';
        }
        if (autofillSection) {
            autofillSection.classList.toggle('is-active', autofillEnabled);
        }
        if (autofillIndicator) {
            autofillIndicator.classList.toggle('is-active', autofillEnabled);
        }
        if (autofillIndicatorLabel) {
            autofillIndicatorLabel.textContent = autofillEnabled ? 'GPS → Form aktif' : 'Manual mode';
        }
    }

    function fillSlaveGpsFields(lat, lng) {
        var latFields = document.querySelectorAll('[name="gps_latitude"]');
        var lngFields = document.querySelectorAll('[name="gps_longitude"]');

        latFields.forEach(function(field) {
            field.value = lat;
            $(field).trigger('change');
        });

        lngFields.forEach(function(field) {
            field.value = lng;
            $(field).trigger('change');
        });
    }

    function updateGpsUi(data) {
        var gps = data && data.gps ? data.gps : null;
        var state = stateFromGps(gps);
        var lat = gps ? gps.latitude : '';
        var lon = gps ? gps.longitude : '';

        lastGpsLat = numberValue(lat);
        lastGpsLng = numberValue(lon);

        setStatus(state.cls, state.label);
        text('liveGpsLatitude', formatCoord(lat));
        text('liveGpsLongitude', formatCoord(lon));
        text('liveGpsSatellites', gps ? String(gps.satellites || 0) : '0');
        text('liveGpsNmea', gps && gps.nmea_seen ? String(gps.sentence_count || 0) : '-');
        text('liveGpsUpdated', new Date().toLocaleTimeString('id-ID'));
        setAxis('liveGpsLatitude', lat, -90, 90);
        setAxis('liveGpsLongitude', lon, -180, 180);

        if (message) {
            message.textContent = messageFromGps(gps);
            message.className = 'live-gps-message mt-3 live-gps-message-' + state.cls;
        }

        // Auto-fill form fields if enabled and we have a valid fix
        if (autofillEnabled && !manualOverride && lastGpsLat !== null && lastGpsLng !== null && gps && gps.fix_valid) {
            fillSlaveGpsFields(lastGpsLat.toFixed(6), lastGpsLng.toFixed(6));
        }
    }

    async function refreshGps() {
        // Hindari polling kalau tab background, hemat trafik HaLow
        if (typeof document !== 'undefined' && document.hidden) {
            return;
        }

        // Kalau request sebelumnya masih jalan, skip cycle ini supaya tidak menumpuk
        if (activeRequest) {
            return;
        }

        var apiUrl = normalizeApiUrl(apiInput ? apiInput.value : '');
        if (apiInput) apiInput.value = apiUrl;

        var controller = new AbortController();
        activeRequest = controller;
        var timeoutId = window.setTimeout(function () {
            controller.abort();
        }, fetchTimeoutMs);

        try {
            setStatus('loading', 'Refreshing');
            var response = await fetch(apiUrl, {
                cache: 'no-store',
                signal: controller.signal,
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }

            updateGpsUi(await response.json());
        } catch (error) {
            if (error.name === 'AbortError') {
                setStatus('error', 'Timeout');
                text('liveGpsUpdated', new Date().toLocaleTimeString('id-ID'));
                if (message) {
                    message.textContent = 'API GPS lambat merespons (>' + (fetchTimeoutMs / 1000) + 's). Cek koneksi HaLow ke slave.';
                    message.className = 'live-gps-message mt-3 live-gps-message-error';
                }
                return;
            }
            setStatus('error', 'API Error');
            text('liveGpsUpdated', new Date().toLocaleTimeString('id-ID'));
            if (message) {
                message.textContent = 'Gagal membaca API GPS: ' + error.message;
                message.className = 'live-gps-message mt-3 live-gps-message-error';
            }
        } finally {
            window.clearTimeout(timeoutId);
            if (activeRequest === controller) {
                activeRequest = null;
            }
        }
    }

    function startAutoRefresh() {
        window.clearInterval(timer);
        timer = window.setInterval(refreshGps, refreshMs);
    }

    // Saat tab kembali aktif, refresh sekali biar data tidak basi
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            refreshGps();
        }
    });

    // Detect manual edits to the GPS fields - pause auto-fill temporarily
    $(document).on('focus', '[name="gps_latitude"], [name="gps_longitude"]', function() {
        if (autofillEnabled) {
            manualOverride = true;
        }
    });

    $(document).on('blur', '[name="gps_latitude"], [name="gps_longitude"]', function() {
        // Resume auto-fill after blur if the value hasn't been changed manually
        setTimeout(function() {
            manualOverride = false;
        }, 500);
    });

    // Auto-fill toggle
    if (autofillToggle) {
        autofillToggle.addEventListener('click', function() {
            autofillEnabled = !autofillEnabled;
            manualOverride = false;
            localStorage.setItem(autofillKey, autofillEnabled ? '1' : '0');
            updateAutofillUi();

            // Immediately fill if enabling and we have data
            if (autofillEnabled && lastGpsLat !== null && lastGpsLng !== null) {
                fillSlaveGpsFields(lastGpsLat.toFixed(6), lastGpsLng.toFixed(6));
            }
        });
    }

    // Apply once button
    if (applyOnceButton) {
        applyOnceButton.addEventListener('click', function() {
            if (lastGpsLat !== null && lastGpsLng !== null) {
                fillSlaveGpsFields(lastGpsLat.toFixed(6), lastGpsLng.toFixed(6));

                // Brief visual feedback
                applyOnceButton.innerHTML = '<i class="fas fa-check"></i> Diterapkan!';
                applyOnceButton.classList.remove('btn-outline-primary');
                applyOnceButton.classList.add('btn-success');
                setTimeout(function() {
                    applyOnceButton.innerHTML = '<i class="fas fa-crosshairs"></i> Terapkan Sekarang';
                    applyOnceButton.classList.remove('btn-success');
                    applyOnceButton.classList.add('btn-outline-primary');
                }, 1500);
            } else {
                applyOnceButton.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Belum ada fix';
                applyOnceButton.classList.remove('btn-outline-primary');
                applyOnceButton.classList.add('btn-warning');
                setTimeout(function() {
                    applyOnceButton.innerHTML = '<i class="fas fa-crosshairs"></i> Terapkan Sekarang';
                    applyOnceButton.classList.remove('btn-warning');
                    applyOnceButton.classList.add('btn-outline-primary');
                }, 1500);
            }
        });
    }

    // Init
    updateAutofillUi();

    if (apiInput && localStorage.getItem(storageKey)) {
        apiInput.value = localStorage.getItem(storageKey);
    }

    if (saveButton) {
        saveButton.addEventListener('click', function() {
            var apiUrl = normalizeApiUrl(apiInput ? apiInput.value : '');
            if (apiInput) apiInput.value = apiUrl;
            localStorage.setItem(storageKey, apiUrl);
            refreshGps();
            startAutoRefresh();
        });
    }

    if (refreshButton) {
        refreshButton.addEventListener('click', refreshGps);
    }

    refreshGps();
    startAutoRefresh();
})();
</script>

<?php
if (!function_exists('rangeSpotPointColor')) {
    function rangeSpotPointColor($status) {
        $status = strtolower((string) $status);

        if ($status === 'good') {
            return '#16a34a';
        }

        if ($status === 'moderate') {
            return '#f59e0b';
        }

        return '#dc2626';
    }
}

if (!function_exists('rangeSpotFallbackCoordinate')) {
    function rangeSpotFallbackCoordinate($direction, $distance) {
        $distance = (float) $distance;
        $angleMap = [
            'east' => 0,
            'diagonal' => 45,
            'north' => 90,
            'vertical' => 90,
            'west' => 180,
            'south' => 270,
        ];
        $angle = $angleMap[strtolower((string) $direction)] ?? 0;
        $radians = deg2rad($angle);

        return [
            round(cos($radians) * $distance, 2),
            round(sin($radians) * $distance, 2),
        ];
    }
}

$locationRows = fetchAll("SELECT location_name, latitude, longitude FROM test_locations WHERE latitude IS NOT NULL AND longitude IS NOT NULL");
$locationGpsMap = [];
$defaultSatelliteCenter = ['lat' => -6.2088, 'lng' => 106.8456];

foreach ($locationRows as $locationRow) {
    $locationLat = rangeGpsValue($locationRow['latitude'] ?? null, 'lat');
    $locationLng = rangeGpsValue($locationRow['longitude'] ?? null, 'lng');

    if ($locationLat === null || $locationLng === null) {
        continue;
    }

    $locationGpsMap[(string) $locationRow['location_name']] = [
        'lat' => $locationLat,
        'lng' => $locationLng,
    ];

    if ($defaultSatelliteCenter['lat'] === -6.2088 && $defaultSatelliteCenter['lng'] === 106.8456) {
        $defaultSatelliteCenter = ['lat' => $locationLat, 'lng' => $locationLng];
    }
}

$spotRows = fetchAll("SELECT * FROM range_tests ORDER BY test_date DESC, created_at DESC LIMIT 100");
$spotPoints = [];
$satellitePoints = [];
$spotEnvelopeMap = [];
$maxAxisValue = 0;
$maxTestedRange = 0;
$goodCoverageRange = 0;
$rssiTotal = 0;
$rssiCount = 0;
$weakestPoint = null;
$strongestPoint = null;

foreach ($spotRows as $row) {
    $distance = (float) ($row['distance_actual_meter'] ?? 0);
    $x = (float) ($row['coordinate_x_meter'] ?? 0);
    $y = (float) ($row['coordinate_y_meter'] ?? 0);

    if (abs($x) < 0.00001 && abs($y) < 0.00001 && $distance > 0) {
        [$x, $y] = rangeSpotFallbackCoordinate($row['direction'] ?? '', $distance);
    }

    if ($distance <= 0) {
        $distance = sqrt(($x * $x) + ($y * $y));
    }

    $status = $row['status_result'] ?? 'poor';
    $rssi = isset($row['rssi_dbm']) ? (float) $row['rssi_dbm'] : null;
    $snr = isset($row['snr_db']) ? (float) $row['snr_db'] : null;
    $locationName = (string) ($row['location_name'] ?? '');
    $locationGps = $locationGpsMap[$locationName] ?? null;
    $masterLat = rangeGpsValue($row['master_gps_latitude'] ?? ($locationGps['lat'] ?? null), 'lat');
    $masterLng = rangeGpsValue($row['master_gps_longitude'] ?? ($locationGps['lng'] ?? null), 'lng');
    $slaveLat = rangeGpsValue($row['gps_latitude'] ?? null, 'lat');
    $slaveLng = rangeGpsValue($row['gps_longitude'] ?? null, 'lng');
    $angle = rad2deg(atan2($y, $x));

    if ($angle < 0) {
        $angle += 360;
    }

    $point = [
        'x' => round($x, 2),
        'y' => round($y, 2),
        'r' => round($distance, 2),
        'angle' => round($angle, 1),
        'label' => $row['test_point_code'] ?: ('Point #' . $row['id']),
        'direction' => $row['direction'] ?: '-',
        'status' => $status,
        'rssi' => $rssi,
        'snr' => $snr,
        'color' => rangeSpotPointColor($status),
    ];

    if (rangeHasGpsPair($masterLat, $masterLng, $slaveLat, $slaveLng)) {
        $gpsDistance = rangeHaversineDistance($masterLat, $masterLng, $slaveLat, $slaveLng);
        $satellitePoints[] = [
            'id' => (int) $row['id'],
            'label' => $point['label'],
            'location' => $locationName ?: '-',
            'masterLat' => $masterLat,
            'masterLng' => $masterLng,
            'slaveLat' => $slaveLat,
            'slaveLng' => $slaveLng,
            'distance' => $gpsDistance,
            'distance3d' => round(sqrt(($gpsDistance * $gpsDistance) + (((float) ($row['coordinate_z_meter'] ?? 0)) ** 2)), 2),
            'elevation' => round((float) ($row['coordinate_z_meter'] ?? 0), 2),
            'bearing' => rangeBearingDegrees($masterLat, $masterLng, $slaveLat, $slaveLng),
            'status' => $status,
            'rssi' => $rssi,
            'snr' => $snr,
            'color' => rangeSpotPointColor($status),
        ];
    }

    $spotPoints[] = $point;
    $maxAxisValue = max($maxAxisValue, abs($x), abs($y), $distance);
    $maxTestedRange = max($maxTestedRange, $distance);

    if (strtolower((string) $status) === 'good') {
        $goodCoverageRange = max($goodCoverageRange, $distance);
    }

    if ($rssi !== null) {
        $rssiTotal += $rssi;
        $rssiCount++;

        if ($weakestPoint === null || $rssi < $weakestPoint['rssi']) {
            $weakestPoint = $point;
        }

        if ($strongestPoint === null || $rssi > $strongestPoint['rssi']) {
            $strongestPoint = $point;
        }
    }

    $bucket = (int) round($angle / 10) * 10;

    if (!isset($spotEnvelopeMap[$bucket]) || $distance > $spotEnvelopeMap[$bucket]['r']) {
        $spotEnvelopeMap[$bucket] = $point;
    }
}

ksort($spotEnvelopeMap);
$spotEnvelope = array_values($spotEnvelopeMap);

if (count($spotEnvelope) > 2) {
    $spotEnvelope[] = $spotEnvelope[0];
}

$axisMax = max(100, (int) ceil(($maxAxisValue * 1.15) / 50) * 50);
$avgRssi = $rssiCount > 0 ? round($rssiTotal / $rssiCount, 2) : null;
$goodCount = count(array_filter($spotPoints, function ($point) {
    return strtolower((string) $point['status']) === 'good';
}));
$moderateCount = count(array_filter($spotPoints, function ($point) {
    return strtolower((string) $point['status']) === 'moderate';
}));
$poorCount = max(0, count($spotPoints) - $goodCount - $moderateCount);
?>

<style>
    .spot-beam-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 12px;
    }

    .spot-beam-stat {
        border: 1px solid #e9ecef;
        border-radius: 10px;
        padding: 14px;
        background: #f8fafc;
    }

    .spot-beam-stat span {
        display: block;
        color: #4b5563;
        font-size: 0.78rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .spot-beam-stat strong {
        display: block;
        margin-top: 4px;
        color: #1f2937;
        font-size: 1.2rem;
        line-height: 1.2;
    }

    .spot-beam-chart-container {
        height: clamp(420px, 62vh, 680px);
        min-height: 420px;
    }

    .range-map-actions {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
    }

    .range-map-shell {
        position: relative;
        width: 100%;
        min-height: 430px;
        height: clamp(430px, 64vh, 720px);
        border: 1px solid #d8dee8;
        border-radius: 10px;
        overflow: hidden;
        background: #e8e4df;
    }

    .range-map-plane {
        position: absolute;
        inset: 0;
        z-index: 0;
        transform-origin: center center;
        transition: transform 0.4s ease-out;
        will-change: transform;
    }

    .range-map-shell.is-3d .range-map-plane {
        transform: scale(1.05);
    }

    .range-map-shell.is-3d {
        box-shadow: inset 0 0 60px rgba(0, 0, 0, 0.3);
    }

    .range-satellite-map {
        position: absolute;
        inset: 0;
        z-index: auto;
        width: 100%;
        height: 100%;
        min-height: 100%;
        overflow: hidden;
        background: #0f172a;
    }

    .range-map-line-overlay {
        position: absolute;
        inset: 0;
        z-index: 450;
        width: 100%;
        height: 100%;
        pointer-events: none;
    }

    .range-map-toolbar {
        position: absolute;
        top: 14px;
        left: 14px;
        z-index: 4;
        width: 200px;
        max-width: calc(100% - 28px);
        padding: 10px;
        border: 1px solid rgba(226, 232, 240, 0.82);
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.88);
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.18);
        backdrop-filter: blur(8px);
        transition: transform 0.2s ease, opacity 0.2s ease;
    }

    .range-map-toolbar:hover {
        transform: scale(1.02);
    }

    .range-map-toolbar.collapsed {
        width: auto;
        padding: 8px 12px;
    }

    .range-map-toolbar.collapsed .range-map-toolbar-content {
        display: none;
    }

    .range-map-toolbar-title {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        color: #0f172a;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        cursor: pointer;
    }

    .range-map-toolbar-content {
        margin-top: 10px;
    }

    .range-map-toolbar label {
        display: block;
        margin-top: 8px;
        color: #475569;
        font-size: 11px;
        font-weight: 700;
    }

    .range-map-zoom-controls {
        display: grid;
        grid-template-columns: 36px 36px 1fr;
        gap: 6px;
        margin-top: 8px;
    }

    .range-map-zoom-controls .btn {
        min-height: 30px;
        font-size: 12px;
        font-weight: 800;
        padding: 4px 8px;
    }

    .range-map-toolbar .form-range {
        margin: 2px 0 0;
        height: 4px;
    }

    .range-map-toolbar .form-range::-webkit-slider-thumb {
        width: 14px;
        height: 14px;
    }

    .range-map-state {
        position: absolute;
        right: 14px;
        bottom: 14px;
        z-index: 4;
        max-width: min(360px, calc(100% - 28px));
        padding: 8px 10px;
        border-radius: 8px;
        color: #e2e8f0;
        background: rgba(15, 23, 42, 0.82);
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.24);
        font-size: 12px;
        font-weight: 700;
        line-height: 1.35;
        pointer-events: none;
    }

    .range-map-capture-message {
        display: none;
        margin-bottom: 12px;
        padding: 10px 12px;
        border: 1px solid #bfdbfe;
        border-radius: 8px;
        background: #eff6ff;
        color: #1e3a8a;
        font-size: 13px;
        font-weight: 700;
    }

    .range-map-capture-message.is-visible {
        display: block;
    }

    .range-map-floating-capture {
        position: fixed;
        right: 22px;
        bottom: 22px;
        z-index: 1045;
        display: none;
        align-items: center;
        gap: 10px;
        padding: 12px 14px;
        border-radius: 10px;
        border: 1px solid rgba(22, 101, 52, 0.3);
        background: rgba(22, 163, 74, 0.96);
        color: #ffffff;
        box-shadow: 0 18px 42px rgba(15, 23, 42, 0.28);
        font-size: 13px;
        font-weight: 800;
    }

    .range-map-floating-capture.is-visible {
        display: inline-flex;
    }

    .range-map-floating-capture small {
        display: block;
        color: rgba(255, 255, 255, 0.82);
        font-size: 11px;
        font-weight: 700;
        line-height: 1.2;
    }

    .range-map-floating-capture .btn {
        color: #166534;
        background: #ffffff;
        border-color: #ffffff;
        font-weight: 800;
    }

    .range-map-marker {
        width: 18px;
        height: 18px;
        border: 3px solid #ffffff;
        border-radius: 999px;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.35);
    }

    .range-map-marker.master {
        width: 24px;
        height: 24px;
        border-radius: 6px;
        background: linear-gradient(135deg, #1e3c72, #2a5298);
        transform: rotate(45deg);
    }

    .range-map-marker.slave {
        background: var(--marker-color, #16a34a);
    }

    .range-map-distance-label {
        color: #0f172a;
        font-weight: 700;
        font-size: 12px;
        padding: 3px 7px;
        border: 1px solid rgba(15, 23, 42, 0.15);
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.92);
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.18);
        white-space: nowrap;
        position: relative;
        z-index: 20;
    }

    .range-map-shell.is-3d .range-map-marker {
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
    }

    .range-map-shell.is-3d .range-map-marker.master {
        box-shadow: 0 10px 25px rgba(30, 60, 114, 0.5);
    }

    .range-map-shell.is-3d .range-map-distance-label {
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.25);
    }

    .spot-beam-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 10px 16px;
        color: #4b5563;
        font-size: 0.9rem;
    }

    .spot-beam-legend span {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .spot-beam-legend i {
        width: 10px;
        height: 10px;
        border-radius: 999px;
        display: inline-block;
    }

    @media (max-width: 767.98px) {
        .range-map-actions {
            width: 100%;
        }

        .range-map-actions .btn,
        .range-map-actions .badge {
            flex: 1 1 auto;
        }

        .range-map-toolbar {
            top: 10px;
            left: 10px;
            width: calc(100% - 20px);
            max-width: none;
        }

        .range-map-toolbar.collapsed {
            width: auto;
        }

        .range-map-floating-capture {
            right: 12px;
            bottom: 12px;
            left: 12px;
            justify-content: space-between;
        }
    }
</style>

<div class="content-section">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h4 class="mb-1"><i class="fas fa-satellite-dish"></i> Spot Beam Coverage Result</h4>
            <p class="text-muted mb-0">Pola cakupan lingkaran dari data jarak, koordinat, RSSI, dan SNR Range Test.</p>
        </div>
        <span class="badge bg-secondary"><?php echo count($spotPoints); ?> titik ukur</span>
    </div>

    <div class="spot-beam-summary mb-4">
        <div class="spot-beam-stat">
            <span>Max Tested Range</span>
            <strong><?php echo number_format($maxTestedRange, 2); ?> m</strong>
        </div>
        <div class="spot-beam-stat">
            <span>Good Coverage Radius</span>
            <strong><?php echo number_format($goodCoverageRange, 2); ?> m</strong>
        </div>
        <div class="spot-beam-stat">
            <span>Average RSSI</span>
            <strong><?php echo $avgRssi === null ? '-' : number_format($avgRssi, 2) . ' dBm'; ?></strong>
        </div>
        <div class="spot-beam-stat">
            <span>Strongest Point</span>
            <strong><?php echo $strongestPoint ? htmlspecialchars($strongestPoint['label']) . ' (' . number_format($strongestPoint['rssi'], 2) . ' dBm)' : '-'; ?></strong>
        </div>
        <div class="spot-beam-stat">
            <span>Weakest Point</span>
            <strong><?php echo $weakestPoint ? htmlspecialchars($weakestPoint['label']) . ' (' . number_format($weakestPoint['rssi'], 2) . ' dBm)' : '-'; ?></strong>
        </div>
        <div class="spot-beam-stat">
            <span>Status Split</span>
            <strong><?php echo $goodCount; ?> good / <?php echo $moderateCount; ?> moderate / <?php echo $poorCount; ?> poor</strong>
        </div>
    </div>

    <div class="card">
        <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h6 class="m-0 font-weight-bold text-primary">Top View / 3D Beam Pattern - Satellite Map</h6>
            <div class="range-map-actions">
                <div class="btn-group btn-group-sm" role="group" aria-label="Mode tampilan map">
                    <button type="button" class="btn btn-outline-primary active" id="rangeMapMode2d">2D</button>
                    <button type="button" class="btn btn-outline-primary" id="rangeMapMode3d">3D</button>
                </div>
                <button type="button" class="btn btn-success btn-sm range-map-capture-btn">
                    <i class="fas fa-camera"></i> Ambil Gambar Map
                </button>
                <span class="badge bg-secondary"><?php echo count($satellitePoints); ?> GPS link</span>
            </div>
        </div>
        <div class="card-body">
            <div id="rangeMapCaptureMessage" class="range-map-capture-message" role="status"></div>
            <div id="rangeMapShell" class="range-map-shell">
                <div class="range-map-toolbar" data-map-capture-ignore="true">
                    <div class="range-map-toolbar-title" id="rangeMapToolbarToggle">
                        <span><i class="fas fa-cube"></i> Kontrol 3D</span>
                        <span class="badge bg-primary" id="rangeMapModeBadge">2D</span>
                    </div>
                    <div class="range-map-toolbar-content">
                        <div class="range-map-zoom-controls" aria-label="Kontrol zoom map">
                            <button type="button" class="btn btn-outline-primary btn-sm" id="rangeMapZoomIn" title="Zoom in">+</button>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="rangeMapZoomOut" title="Zoom out">-</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="rangeMapFit" title="Tampilkan semua titik">
                                <i class="fas fa-expand"></i> Fit
                            </button>
                        </div>
                        <label for="rangeMapTerrain">Terrain overlay</label>
                        <input type="range" class="form-range" id="rangeMapTerrain" min="0" max="50" step="5" value="30">
                    </div>
                </div>
                <div class="range-map-state" id="rangeMapState" data-map-capture-ignore="true">
                    Mode 2D Satellite. Geser map atau pakai mouse wheel/tombol +/- untuk mengatur posisi gambar.
                </div>
                <div id="rangeMapPlane" class="range-map-plane">
                    <div id="rangeSatelliteMap" class="range-satellite-map"></div>
                    <canvas id="rangeMapLineOverlay" class="range-map-line-overlay"></canvas>
                </div>
            </div>
            <?php if (count($satellitePoints) === 0): ?>
                <p class="text-muted text-center mb-0 mt-3">Isi GPS master dan GPS slave pada Range Test untuk menampilkan garis jarak di map satelit.</p>
            <?php endif; ?>
        </div>
    </div>

    <div id="rangeMapFloatingCapture" class="range-map-floating-capture" data-map-capture-ignore="true">
        <span>
            Map satelit terlihat
            <small>Capture mengikuti center, zoom, mode 3D, dan rotasi saat ini.</small>
        </span>
        <button type="button" class="btn btn-sm range-map-capture-btn">
            <i class="fas fa-camera"></i> Ambil
        </button>
    </div>

    <div class="card">
        <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h6 class="m-0 font-weight-bold text-primary">Coordinate Beam Pattern</h6>
            <div class="spot-beam-legend">
                <span><i style="background:#dc2626"></i>Strong RSSI</span>
                <span><i style="background:#f59e0b"></i>Medium</span>
                <span><i style="background:#22c55e"></i>Weak Edge</span>
                <span><i style="background:#1e3c72"></i>Master</span>
            </div>
        </div>
        <div class="card-body">
            <div class="chart-container spot-beam-chart-container">
                <canvas id="rangeSpotBeamChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    var satellitePoints = <?php echo json_encode($satellitePoints, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var satelliteDefaultCenter = <?php echo json_encode($defaultSatelliteCenter, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var locationGpsMap = <?php echo json_encode($locationGpsMap, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var spotPoints = <?php echo json_encode($spotPoints, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var spotEnvelope = <?php echo json_encode($spotEnvelope, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var axisMax = <?php echo (int) $axisMax; ?>;
    var canvas = document.getElementById('rangeSpotBeamChart');

    function meterLabel(value) {
        var distance = Number(value) || 0;

        if (distance >= 1000) {
            return (distance / 1000).toFixed(3) + ' km';
        }

        return distance.toFixed(2) + ' m';
    }

    function popupHtml(point) {
        return [
            '<strong>' + $('<div>').text(point.label || 'Point').html() + '</strong>',
            'Lokasi: ' + $('<div>').text(point.location || '-').html(),
            'Jarak GPS: ' + meterLabel(point.distance),
            'Jarak 3D: ' + meterLabel(point.distance3d),
            'Elevasi Z: ' + meterLabel(point.elevation),
            'Bearing: ' + (point.bearing || 0) + '&deg;',
            'RSSI: ' + (point.rssi === null ? '-' : point.rssi + ' dBm'),
            'SNR: ' + (point.snr === null ? '-' : point.snr + ' dB'),
            'Status: ' + $('<div>').text(point.status || '-').html()
        ].join('<br>');
    }

    function initSatelliteMap() {
        var mapElement = document.getElementById('rangeSatelliteMap');
        var shell = document.getElementById('rangeMapShell');
        var lineOverlay = document.getElementById('rangeMapLineOverlay');
        var mode2dButton = document.getElementById('rangeMapMode2d');
        var mode3dButton = document.getElementById('rangeMapMode3d');
        var tiltInput = document.getElementById('rangeMapTilt');
        var bearingInput = document.getElementById('rangeMapBearing');
        var zoomInButton = document.getElementById('rangeMapZoomIn');
        var zoomOutButton = document.getElementById('rangeMapZoomOut');
        var fitButton = document.getElementById('rangeMapFit');
        var toolbarToggle = document.getElementById('rangeMapToolbarToggle');
        var toolbar = document.querySelector('.range-map-toolbar');
        var modeBadge = document.getElementById('rangeMapModeBadge');
        var stateText = document.getElementById('rangeMapState');
        var floatingCapture = document.getElementById('rangeMapFloatingCapture');
        var captureMessage = document.getElementById('rangeMapCaptureMessage');

        if (!mapElement || !shell || !window.L) {
            return;
        }

        var map = L.map(mapElement, {
            zoomControl: false,
            attributionControl: true,
            maxZoom: 18,
            scrollWheelZoom: true,
            wheelPxPerZoomLevel: 90
        });

        // Base tile layer
        var baseLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            maxZoom: 18,
            maxNativeZoom: 18,
            crossOrigin: true,
            attribution: 'Tiles &copy; Esri'
        }).addTo(map);

        // Terrain overlay for 3D effect
        var terrainOverlay = L.tileLayer('https://stamen-tiles.a.ssl.fastly.net/terrain-lines/{z}/{x}/{y}.png', {
            maxZoom: 18,
            opacity: 0,
            attribution: ''
        }).addTo(map);

        L.control.scale({
            metric: true,
            imperial: false
        }).addTo(map);

        var bounds = [];
        var masterMarkers = {};
        var distanceLabelMarkers = [];
        var distanceLabelData = [];

        function redrawRangeLineOverlay() {
            if (!lineOverlay) {
                return;
            }

            var width = mapElement.clientWidth;
            var height = mapElement.clientHeight;
            var ratio = Math.max(2, window.devicePixelRatio || 1);

            if (width <= 0 || height <= 0) {
                return;
            }

            if (lineOverlay.width !== Math.round(width * ratio) || lineOverlay.height !== Math.round(height * ratio)) {
                lineOverlay.width = Math.round(width * ratio);
                lineOverlay.height = Math.round(height * ratio);
            }

            lineOverlay.style.width = width + 'px';
            lineOverlay.style.height = height + 'px';

            var ctx = lineOverlay.getContext('2d');
            ctx.clearRect(0, 0, lineOverlay.width, lineOverlay.height);
            ctx.save();
            ctx.scale(ratio, ratio);
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';

            satellitePoints
                .filter(function(point) {
                    return point.distance > 0 && point.distance < 50000;
                })
                .sort(function(a, b) {
                    return Number(b.distance || 0) - Number(a.distance || 0);
                })
                .forEach(function(point) {
                    var centerLatLng = L.latLng(point.masterLat, point.masterLng);
                    var centerPixel = map.latLngToContainerPoint(centerLatLng);
                    var edgePixel = map.latLngToContainerPoint(destinationLatLng(centerLatLng, point.distance, 90));
                    var radiusPx = Math.sqrt(Math.pow(edgePixel.x - centerPixel.x, 2) + Math.pow(edgePixel.y - centerPixel.y, 2));

                    if (!isFinite(radiusPx) || radiusPx <= 1) {
                        return;
                    }

                    var color = point.color || '#1e3c72';
                    var fillGradient = ctx.createRadialGradient(
                        centerPixel.x,
                        centerPixel.y,
                        Math.max(1, radiusPx * 0.18),
                        centerPixel.x,
                        centerPixel.y,
                        radiusPx
                    );

                    fillGradient.addColorStop(0, hexToRgba(color, 0.12));
                    fillGradient.addColorStop(0.72, hexToRgba(color, 0.055));
                    fillGradient.addColorStop(1, hexToRgba(color, 0.015));

                    ctx.save();
                    ctx.beginPath();
                    ctx.arc(centerPixel.x, centerPixel.y, radiusPx, 0, Math.PI * 2);
                    ctx.fillStyle = fillGradient;
                    ctx.fill();

                    ctx.beginPath();
                    ctx.arc(centerPixel.x, centerPixel.y, radiusPx, 0, Math.PI * 2);
                    ctx.lineWidth = 1.8;
                    ctx.shadowColor = hexToRgba(color, 0.28);
                    ctx.shadowBlur = 6;
                    ctx.strokeStyle = hexToRgba(color, 0.62);
                    ctx.stroke();
                    ctx.restore();
                });

            satellitePoints.forEach(function(point) {
                var masterPixel = map.latLngToContainerPoint([point.masterLat, point.masterLng]);
                var slavePixel = map.latLngToContainerPoint([point.slaveLat, point.slaveLng]);
                var color = point.color || '#1e3c72';
                var labelPixel = labelOffsetPixel(masterPixel, slavePixel, 20);

                ctx.beginPath();
                ctx.moveTo(masterPixel.x, masterPixel.y);
                ctx.lineTo(slavePixel.x, slavePixel.y);
                ctx.strokeStyle = 'rgba(15,23,42,0.56)';
                ctx.lineWidth = 4.5;
                ctx.shadowColor = 'rgba(255,255,255,0.28)';
                ctx.shadowBlur = 2;
                ctx.stroke();

                ctx.beginPath();
                ctx.moveTo(masterPixel.x, masterPixel.y);
                ctx.lineTo(slavePixel.x, slavePixel.y);
                ctx.strokeStyle = color;
                ctx.lineWidth = 2.4;
                ctx.shadowBlur = 0;
                ctx.stroke();

                ctx.save();
                ctx.globalCompositeOperation = 'destination-out';
                ctx.beginPath();
                ctx.ellipse(labelPixel.x, labelPixel.y, 48, 15, 0, 0, Math.PI * 2);
                ctx.fill();
                ctx.restore();

                [
                    { pixel: masterPixel, radius: 4.8, fill: '#1e3c72' },
                    { pixel: slavePixel, radius: 4.2, fill: color }
                ].forEach(function(endpoint) {
                    ctx.beginPath();
                    ctx.arc(endpoint.pixel.x, endpoint.pixel.y, endpoint.radius + 1.8, 0, Math.PI * 2);
                    ctx.fillStyle = 'rgba(255,255,255,0.9)';
                    ctx.fill();

                    ctx.beginPath();
                    ctx.arc(endpoint.pixel.x, endpoint.pixel.y, endpoint.radius, 0, Math.PI * 2);
                    ctx.fillStyle = endpoint.fill;
                    ctx.fill();
                });
            });

            ctx.restore();
        }

        function destinationLatLng(origin, distanceMeters, bearingDegrees) {
            var earthRadius = 6378137;
            var angularDistance = distanceMeters / earthRadius;
            var bearing = bearingDegrees * Math.PI / 180;
            var lat1 = origin.lat * Math.PI / 180;
            var lng1 = origin.lng * Math.PI / 180;
            var sinLat1 = Math.sin(lat1);
            var cosLat1 = Math.cos(lat1);
            var sinAngular = Math.sin(angularDistance);
            var cosAngular = Math.cos(angularDistance);
            var lat2 = Math.asin((sinLat1 * cosAngular) + (cosLat1 * sinAngular * Math.cos(bearing)));
            var lng2 = lng1 + Math.atan2(
                Math.sin(bearing) * sinAngular * cosLat1,
                cosAngular - (sinLat1 * Math.sin(lat2))
            );

            return L.latLng(lat2 * 180 / Math.PI, lng2 * 180 / Math.PI);
        }

        function hexToRgba(hex, alpha) {
            var normalized = String(hex || '#1e3c72').replace('#', '').trim();

            if (normalized.length === 3) {
                normalized = normalized.split('').map(function(char) {
                    return char + char;
                }).join('');
            }

            if (!/^[0-9a-f]{6}$/i.test(normalized)) {
                normalized = '1e3c72';
            }

            return 'rgba('
                + parseInt(normalized.slice(0, 2), 16) + ', '
                + parseInt(normalized.slice(2, 4), 16) + ', '
                + parseInt(normalized.slice(4, 6), 16) + ', '
                + alpha + ')';
        }

        function labelOffsetLatLng(masterLatLng, slaveLatLng, offsetPixels) {
            var masterPixel = map.latLngToContainerPoint(masterLatLng);
            var slavePixel = map.latLngToContainerPoint(slaveLatLng);
            var offsetPixel = labelOffsetPixel(masterPixel, slavePixel, offsetPixels);

            return map.containerPointToLatLng(offsetPixel);
        }

        function labelOffsetPixel(masterPixel, slavePixel, offsetPixels) {
            var midX = (masterPixel.x + slavePixel.x) / 2;
            var midY = (masterPixel.y + slavePixel.y) / 2;
            var dx = slavePixel.x - masterPixel.x;
            var dy = slavePixel.y - masterPixel.y;
            var length = Math.sqrt((dx * dx) + (dy * dy)) || 1;
            var normalX = -dy / length;
            var normalY = dx / length;

            return L.point(midX + (normalX * offsetPixels), midY + (normalY * offsetPixels));
        }

        function fitSatelliteBounds() {
            if (bounds.length > 0) {
                map.fitBounds(bounds, {
                    padding: [32, 32],
                    maxZoom: 18
                });
            } else {
                map.setView([satelliteDefaultCenter.lat || -6.2088, satelliteDefaultCenter.lng || 106.8456], 17);
            }
        }

        satellitePoints.forEach(function(point) {
            var master = [point.masterLat, point.masterLng];
            var slave = [point.slaveLat, point.slaveLng];
            var masterKey = point.masterLat.toFixed(7) + ',' + point.masterLng.toFixed(7);

            if (!masterMarkers[masterKey]) {
                masterMarkers[masterKey] = L.marker(master, {
                    icon: L.divIcon({
                        className: '',
                        html: '<div class="range-map-marker master"></div>',
                        iconSize: [22, 22],
                        iconAnchor: [11, 11]
                    })
                }).addTo(map).bindPopup('<strong>Master</strong><br>' + point.masterLat.toFixed(7) + ', ' + point.masterLng.toFixed(7));
            }

            L.marker(slave, {
                icon: L.divIcon({
                    className: '',
                    html: '<div class="range-map-marker slave" style="--marker-color:' + point.color + '"></div>',
                    iconSize: [18, 18],
                    iconAnchor: [9, 9]
                })
            }).addTo(map).bindPopup(popupHtml(point));

            L.polyline([master, slave], {
                color: point.color || '#1e3c72',
                weight: 3,
                opacity: 0.9
            }).addTo(map).bindTooltip(point.label + ' - ' + meterLabel(point.distance), {
                sticky: true
            });

            distanceLabelData.push({
                point: point,
                master: master,
                slave: slave
            });

            bounds.push(master, slave);
        });

        fitSatelliteBounds();

        distanceLabelData.forEach(function(item) {
            var distanceLabelMarker = L.marker(labelOffsetLatLng(item.master, item.slave, 20), {
                interactive: false,
                icon: L.divIcon({
                    className: '',
                    html: '<div class="range-map-distance-label">' + meterLabel(item.point.distance) + '</div>',
                    iconSize: [90, 24],
                    iconAnchor: [45, 12]
                }),
                zIndexOffset: 1200
            }).addTo(map);

            distanceLabelMarkers.push({
                marker: distanceLabelMarker,
                master: item.master,
                slave: item.slave
            });
        });

        setTimeout(function() {
            map.invalidateSize();
            updateMap3dState();
            redrawRangeLineOverlay();
        }, 250);

        var map3d = {
            enabled: false,
            tilt: Number(tiltInput && tiltInput.value) || 50,
            bearing: Number(bearingInput && bearingInput.value) || 0
        };
        var mapVisibleInViewport = false;

        function formatCenter() {
            var center = map.getCenter();
            return center.lat.toFixed(6) + ', ' + center.lng.toFixed(6);
        }

        function updateMap3dState() {
            if (map.getZoom() > 18) {
                map.setZoom(18);
                return;
            }

            shell.classList.toggle('is-3d', map3d.enabled);

            if (mode2dButton) {
                mode2dButton.classList.toggle('active', !map3d.enabled);
            }

            if (mode3dButton) {
                mode3dButton.classList.toggle('active', map3d.enabled);
            }

            if (modeBadge) {
                modeBadge.textContent = map3d.enabled ? '3D' : '2D';
                modeBadge.className = 'badge ' + (map3d.enabled ? 'bg-success' : 'bg-primary');
            }

            if (stateText) {
                var terrainOpacity = terrainOverlay ? terrainOverlay.options.opacity : 0;
                stateText.textContent = (map3d.enabled ? 'Mode 3D Satellite' : 'Mode 2D Satellite')
                    + ' | Center ' + formatCenter()
                    + ' | Zoom ' + map.getZoom()
                    + ' | Terrain ' + Math.round(terrainOpacity * 100) + '%'
                    + ' | Mouse wheel aktif';
            }
        }

        function setMap3dMode(enabled) {
            map3d.enabled = enabled;
            updateMap3dState();

            // Toggle terrain overlay opacity for 3D effect
            if (terrainOverlay) {
                terrainOverlay.setOpacity(enabled ? 0.3 : 0);
            }

            setTimeout(function() {
                map.invalidateSize();
            }, 180);
        }

        function slugifyFilename(value) {
            return String(value || 'range-satellite-map')
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '') || 'range-satellite-map';
        }

        function showCaptureMessage(message, type) {
            if (!captureMessage) {
                return;
            }

            captureMessage.textContent = message;
            captureMessage.classList.add('is-visible');
            captureMessage.style.borderColor = type === 'error' ? '#fecaca' : '#bfdbfe';
            captureMessage.style.background = type === 'error' ? '#fef2f2' : '#eff6ff';
            captureMessage.style.color = type === 'error' ? '#991b1b' : '#1e3a8a';

            clearTimeout(showCaptureMessage.timer);
            showCaptureMessage.timer = setTimeout(function() {
                captureMessage.classList.remove('is-visible');
            }, 4200);
        }

        function setCaptureLoading(button, isLoading) {
            if (!button) {
                return;
            }

            if (isLoading) {
                button.dataset.originalHtml = button.innerHTML;
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengambil...';
            } else {
                button.disabled = false;
                button.innerHTML = button.dataset.originalHtml || '<i class="fas fa-camera"></i> Ambil';
            }
        }

        function captureCurrentMap(button) {
            if (!window.html2canvas) {
                showCaptureMessage('Library html2canvas belum ter-load. Pastikan koneksi CDN aktif, lalu refresh halaman.', 'error');
                return;
            }

            // Keep an open Leaflet popup visible so point details are included in the exported image.
            map.invalidateSize();
            updateMap3dState();
            redrawRangeLineOverlay();
            setCaptureLoading(button, true);

            setTimeout(function() {
                window.html2canvas(shell, {
                    backgroundColor: '#0f172a',
                    useCORS: true,
                    allowTaint: false,
                    logging: false,
                    scale: 2,
                    ignoreElements: function(element) {
                        return element.hasAttribute && element.hasAttribute('data-map-capture-ignore');
                    }
                }).then(function(canvas) {
                    var center = map.getCenter();
                    var filename = slugifyFilename([
                        'range-satellite-map',
                        map3d.enabled ? '3d' : '2d',
                        'z' + map.getZoom(),
                        center.lat.toFixed(5),
                        center.lng.toFixed(5),
                        new Date().toISOString().slice(0, 19).replace(/[:T]/g, '-')
                    ].join('-'));
                    var link = document.createElement('a');
                    link.href = canvas.toDataURL('image/png');
                    link.download = filename + '.png';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    showCaptureMessage('Gambar map berhasil diunduh sesuai posisi center, zoom, tilt, rotasi, dan popup titik yang sedang terbuka.', 'success');
                }).catch(function(error) {
                    showCaptureMessage('Gagal mengambil gambar map. Coba tunggu tile satelit selesai loading, lalu ulangi. Detail: ' + (error && error.message ? error.message : 'capture error'), 'error');
                }).finally(function() {
                    setCaptureLoading(button, false);
                });
            }, 250);
        }

        function updateFloatingCapture() {
            if (!floatingCapture) {
                return;
            }

            floatingCapture.classList.toggle('is-visible', mapVisibleInViewport && window.scrollY > 80);
        }

        if (mode2dButton) {
            mode2dButton.addEventListener('click', function() {
                setMap3dMode(false);
            });
        }

        if (mode3dButton) {
            mode3dButton.addEventListener('click', function() {
                setMap3dMode(true);
            });
        }

        var terrainInput = document.getElementById('rangeMapTerrain');

        if (tiltInput) {
            tiltInput.addEventListener('input', function() {
                map3d.tilt = Number(tiltInput.value) || 0;
                setMap3dMode(map3d.tilt > 0);
            });
        }

        if (bearingInput) {
            bearingInput.addEventListener('input', function() {
                map3d.bearing = Number(bearingInput.value) || 0;
                setMap3dMode(true);
            });
        }

        if (terrainInput) {
            terrainInput.addEventListener('input', function() {
                var opacity = Number(terrainInput.value) / 100;
                if (terrainOverlay) {
                    terrainOverlay.setOpacity(opacity);
                }
            });
        }

        if (zoomInButton) {
            zoomInButton.addEventListener('click', function() {
                map.zoomIn();
            });
        }

        if (zoomOutButton) {
            zoomOutButton.addEventListener('click', function() {
                map.zoomOut();
            });
        }

        if (fitButton) {
            fitButton.addEventListener('click', function() {
                fitSatelliteBounds();
            });
        }

        document.querySelectorAll('.range-map-capture-btn').forEach(function(button) {
            button.addEventListener('click', function(event) {
                event.preventDefault();
                event.stopPropagation();
                captureCurrentMap(button);
            });
        });

        map.on('move zoom resize moveend zoomend', function() {
            updateMap3dState();
            redrawRangeLineOverlay();
            distanceLabelMarkers.forEach(function(item) {
                item.marker.setLatLng(labelOffsetLatLng(item.master, item.slave, 20));
            });
        });
        updateMap3dState();
        redrawRangeLineOverlay();

        if ('IntersectionObserver' in window) {
            new IntersectionObserver(function(entries) {
                mapVisibleInViewport = entries.some(function(entry) {
                    return entry.isIntersecting && entry.intersectionRatio > 0.08;
                });
                updateFloatingCapture();
            }, {
                threshold: [0, 0.08, 0.25],
                rootMargin: '0px 0px -10% 0px'
            }).observe(shell);
        } else {
            var checkMapVisibility = function() {
                var rect = shell.getBoundingClientRect();
                mapVisibleInViewport = rect.top < window.innerHeight && rect.bottom > 0;
                updateFloatingCapture();
            };
            window.addEventListener('scroll', checkMapVisibility, { passive: true });
            window.addEventListener('resize', checkMapVisibility);
            checkMapVisibility();
        }

        window.addEventListener('scroll', updateFloatingCapture, { passive: true });

        if (toolbarToggle && toolbar) {
            toolbarToggle.addEventListener('click', function(e) {
                if (e.target.tagName === 'BUTTON' || e.target.tagName === 'INPUT') {
                    return;
                }
                toolbar.classList.toggle('collapsed');
            });
        }
    }

    function parseGps(value, min, max) {
        if (value === null || value === undefined || value === '') {
            return null;
        }

        var coordinate = Number(value);

        if (!Number.isFinite(coordinate) || coordinate < min || coordinate > max) {
            return null;
        }

        return coordinate;
    }

    function gpsDistance(masterLat, masterLng, slaveLat, slaveLng) {
        var earthRadius = 6371000;
        var lat1 = masterLat * Math.PI / 180;
        var lat2 = slaveLat * Math.PI / 180;
        var deltaLat = (slaveLat - masterLat) * Math.PI / 180;
        var deltaLng = (slaveLng - masterLng) * Math.PI / 180;
        var a = Math.sin(deltaLat / 2) * Math.sin(deltaLat / 2)
            + Math.cos(lat1) * Math.cos(lat2) * Math.sin(deltaLng / 2) * Math.sin(deltaLng / 2);
        var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

        return earthRadius * c;
    }

    function gpsOffset(masterLat, masterLng, slaveLat, slaveLng) {
        var earthRadius = 6371000;
        var lat1 = masterLat * Math.PI / 180;
        var lat2 = slaveLat * Math.PI / 180;
        var deltaLat = (slaveLat - masterLat) * Math.PI / 180;
        var deltaLng = (slaveLng - masterLng) * Math.PI / 180;

        return {
            x: earthRadius * deltaLng * Math.cos((lat1 + lat2) / 2),
            y: earthRadius * deltaLat
        };
    }

    function syncRangeGpsForm(form, fillMasterFromLocation) {
        var $form = $(form);

        if (!$form.find('[name="_test_page"][value="range_tests"]').length || $form.find('[name="_test_action"]').val() === 'delete') {
            return;
        }

        var locationName = String($form.find('[name="location_name"]').val() || '').trim();
        var masterLocation = locationGpsMap[locationName];
        var $masterLat = $form.find('[name="master_gps_latitude"]');
        var $masterLng = $form.find('[name="master_gps_longitude"]');

        if (fillMasterFromLocation && masterLocation && !$masterLat.val() && !$masterLng.val()) {
            $masterLat.val(String(masterLocation.lat));
            $masterLng.val(String(masterLocation.lng));
        }

        var masterLat = parseGps($masterLat.val(), -90, 90);
        var masterLng = parseGps($masterLng.val(), -180, 180);
        var slaveLat = parseGps($form.find('[name="gps_latitude"]').val(), -90, 90);
        var slaveLng = parseGps($form.find('[name="gps_longitude"]').val(), -180, 180);

        if (masterLat === null || masterLng === null || slaveLat === null || slaveLng === null) {
            return;
        }

        var distance = gpsDistance(masterLat, masterLng, slaveLat, slaveLng);
        var offset = gpsOffset(masterLat, masterLng, slaveLat, slaveLng);

        $form.find('[name="distance_actual_meter"]').val(distance.toFixed(2));
        $form.find('[name="coordinate_x_meter"]').val(offset.x.toFixed(2));
        $form.find('[name="coordinate_y_meter"]').val(offset.y.toFixed(2));
    }

    initSatelliteMap();

    $(document).on('input change blur', '[name="master_gps_latitude"], [name="master_gps_longitude"], [name="gps_latitude"], [name="gps_longitude"]', function() {
        syncRangeGpsForm($(this).closest('form'), false);
    });

    $(document).on('input change blur', '[name="location_name"]', function() {
        syncRangeGpsForm($(this).closest('form'), true);
    });

    $('form').each(function() {
        syncRangeGpsForm(this, true);
    });

    if (!canvas || !window.Chart) {
        return;
    }

    function heatValue(point) {
        if (typeof point.rssi === 'number') {
            return point.rssi;
        }

        var status = String(point.status || '').toLowerCase();

        if (status === 'good') {
            return -58;
        }

        if (status === 'moderate') {
            return -72;
        }

        return -88;
    }

    function heatColor(value, alpha) {
        if (value >= -55) {
            return 'rgba(220, 38, 38, ' + alpha + ')';
        }

        if (value >= -65) {
            return 'rgba(249, 115, 22, ' + alpha + ')';
        }

        if (value >= -75) {
            return 'rgba(250, 204, 21, ' + alpha + ')';
        }

        if (value >= -85) {
            return 'rgba(34, 197, 94, ' + alpha + ')';
        }

        return 'rgba(45, 212, 191, ' + alpha + ')';
    }

    function clipEnvelope(ctx, xScale, yScale, area) {
        if (spotEnvelope.length > 2) {
            ctx.beginPath();
            spotEnvelope.forEach(function(point, index) {
                var x = xScale.getPixelForValue(point.x);
                var y = yScale.getPixelForValue(point.y);

                if (index === 0) {
                    ctx.moveTo(x, y);
                } else {
                    ctx.lineTo(x, y);
                }
            });
            ctx.closePath();
            ctx.clip();
            return;
        }

        ctx.beginPath();
        ctx.rect(area.left, area.top, area.right - area.left, area.bottom - area.top);
        ctx.clip();
    }

    var heatmapPlugin = {
        id: 'rangeSpotBeamHeatmap',
        beforeDatasetsDraw: function(chart) {
            if (spotPoints.length === 0) {
                return;
            }

            var ctx = chart.ctx;
            var area = chart.chartArea;
            var xScale = chart.scales.x;
            var yScale = chart.scales.y;
            var step = Math.max(7, Math.floor(Math.min(area.width, area.height) / 90));

            ctx.save();
            clipEnvelope(ctx, xScale, yScale, area);

            ctx.fillStyle = 'rgba(187, 247, 208, 0.42)';
            ctx.fillRect(area.left, area.top, area.right - area.left, area.bottom - area.top);

            for (var py = area.top; py <= area.bottom; py += step) {
                for (var px = area.left; px <= area.right; px += step) {
                    var wx = xScale.getValueForPixel(px + step / 2);
                    var wy = yScale.getValueForPixel(py + step / 2);
                    var weighted = 0;
                    var weights = 0;

                    spotPoints.forEach(function(point) {
                        var dx = wx - point.x;
                        var dy = wy - point.y;
                        var distance = Math.sqrt(dx * dx + dy * dy);
                        var influence = Math.max(axisMax * 0.08, Math.min(axisMax * 0.38, Math.max(point.r || 0, axisMax * 0.2) * 0.45));
                        var weight = 1 / (Math.pow(distance / influence, 2) + 0.08);

                        weighted += heatValue(point) * weight;
                        weights += weight;
                    });

                    if (weights > 0) {
                        ctx.fillStyle = heatColor(weighted / weights, 0.58);
                        ctx.fillRect(px, py, step + 1, step + 1);
                    }
                }
            }

            spotPoints.forEach(function(point) {
                var centerX = xScale.getPixelForValue(point.x);
                var centerY = yScale.getPixelForValue(point.y);
                var haloMeters = Math.max(axisMax * 0.08, Math.min(axisMax * 0.22, Math.max(point.r || 0, axisMax * 0.16) * 0.28));
                var haloRadius = Math.abs(xScale.getPixelForValue(point.x + haloMeters) - centerX);
                var gradient = ctx.createRadialGradient(centerX, centerY, 0, centerX, centerY, haloRadius);

                gradient.addColorStop(0, heatColor(heatValue(point), 0.88));
                gradient.addColorStop(0.45, heatColor(heatValue(point) - 8, 0.38));
                gradient.addColorStop(1, 'rgba(255, 255, 255, 0)');

                ctx.fillStyle = gradient;
                ctx.beginPath();
                ctx.arc(centerX, centerY, haloRadius, 0, Math.PI * 2);
                ctx.fill();
            });

            ctx.restore();
        }
    };

    var ringPlugin = {
        id: 'rangeSpotBeamRings',
        beforeDatasetsDraw: function(chart) {
            var ctx = chart.ctx;
            var area = chart.chartArea;
            var xScale = chart.scales.x;
            var yScale = chart.scales.y;
            var centerX = xScale.getPixelForValue(0);
            var centerY = yScale.getPixelForValue(0);
            var rings = [0.25, 0.5, 0.75, 1];

            ctx.save();
            ctx.strokeStyle = 'rgba(30, 60, 114, 0.16)';
            ctx.fillStyle = '#64748b';
            ctx.lineWidth = 1;
            ctx.setLineDash([5, 5]);

            rings.forEach(function(part) {
                var value = axisMax * part;
                var radius = Math.abs(xScale.getPixelForValue(value) - centerX);

                ctx.beginPath();
                ctx.arc(centerX, centerY, radius, 0, Math.PI * 2);
                ctx.stroke();
                ctx.fillText(Math.round(value) + ' m', centerX + radius + 6, Math.max(area.top + 14, centerY - 6));
            });

            ctx.setLineDash([]);
            ctx.strokeStyle = 'rgba(15, 23, 42, 0.2)';
            ctx.beginPath();
            ctx.moveTo(area.left, centerY);
            ctx.lineTo(area.right, centerY);
            ctx.moveTo(centerX, area.top);
            ctx.lineTo(centerX, area.bottom);
            ctx.stroke();
            ctx.restore();
        }
    };

    function byStatus(status) {
        return spotPoints.filter(function(point) {
            return String(point.status).toLowerCase() === status;
        });
    }

    function pointTooltip(context) {
        var point = context.raw || {};

        if (point.master) {
            return 'Master / Gateway';
        }

        return [
            (point.label || 'Point') + ' - ' + (point.status || '-'),
            'Distance: ' + (point.r || 0) + ' m',
            'Direction: ' + (point.direction || '-'),
            'RSSI: ' + (point.rssi === null ? '-' : point.rssi + ' dBm'),
            'SNR: ' + (point.snr === null ? '-' : point.snr + ' dB')
        ];
    }

    new Chart(canvas.getContext('2d'), {
        type: 'scatter',
        plugins: [heatmapPlugin, ringPlugin],
        data: {
            datasets: [
                {
                    type: 'line',
                    label: 'Beam Boundary',
                    data: spotEnvelope,
                    borderColor: '#dc2626',
                    backgroundColor: 'rgba(220, 38, 38, 0.03)',
                    borderWidth: 2.5,
                    pointRadius: 0,
                    tension: 0.25,
                    fill: false,
                    order: 4
                },
                {
                    label: 'Good',
                    data: byStatus('good'),
                    backgroundColor: '#16a34a',
                    borderColor: '#ffffff',
                    borderWidth: 2,
                    pointRadius: 7,
                    pointHoverRadius: 10,
                    order: 1
                },
                {
                    label: 'Moderate',
                    data: byStatus('moderate'),
                    backgroundColor: '#f59e0b',
                    borderColor: '#ffffff',
                    borderWidth: 2,
                    pointRadius: 7,
                    pointHoverRadius: 10,
                    order: 1
                },
                {
                    label: 'Poor',
                    data: byStatus('poor'),
                    backgroundColor: '#dc2626',
                    borderColor: '#ffffff',
                    borderWidth: 2,
                    pointRadius: 7,
                    pointHoverRadius: 10,
                    order: 1
                },
                {
                    label: 'Master',
                    data: [{ x: 0, y: 0, master: true }],
                    backgroundColor: '#1e3c72',
                    borderColor: '#ffffff',
                    borderWidth: 3,
                    pointStyle: 'rectRot',
                    pointRadius: 10,
                    pointHoverRadius: 12,
                    order: 0
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            parsing: false,
            interaction: {
                mode: 'nearest',
                intersect: true
            },
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: pointTooltip
                    }
                }
            },
            scales: {
                x: {
                    min: -axisMax,
                    max: axisMax,
                    grid: {
                        color: 'rgba(30, 60, 114, 0.08)'
                    },
                    title: {
                        display: true,
                        text: 'X Coordinate (m)'
                    }
                },
                y: {
                    min: -axisMax,
                    max: axisMax,
                    grid: {
                        color: 'rgba(30, 60, 114, 0.08)'
                    },
                    title: {
                        display: true,
                        text: 'Y Coordinate (m)'
                    }
                }
            }
        }
    });
});
</script>
