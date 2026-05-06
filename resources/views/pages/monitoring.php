<?php
$pageConfig = [
    'title' => 'Data Monitoring',
    'icon' => 'fas fa-chart-line',
    'description' => 'Input data monitoring node: battery, suhu, RSSI, SNR, GPS, dan status koneksi.',
    'table' => 'data_monitoring',
    'order' => 'test_date DESC, created_at DESC',
    'fields' => [
        ['name' => 'test_date', 'label' => 'Test Date', 'type' => 'date', 'required' => true],
        ['name' => 'node_id', 'label' => 'Node ID', 'required' => true],
        ['name' => 'timestamp_ms', 'label' => 'Timestamp (ms)', 'type' => 'number', 'integer' => true],
        ['name' => 'battery_percent', 'label' => 'Battery (%)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'voltage_v', 'label' => 'Voltage (V)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'current_a', 'label' => 'Current (A)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'temperature_c', 'label' => 'Temperature (C)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'rssi_dbm', 'label' => 'RSSI (dBm)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'snr_db', 'label' => 'SNR (dB)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'gps_latitude', 'label' => 'GPS Latitude', 'type' => 'number', 'step' => '0.00000001'],
        ['name' => 'gps_longitude', 'label' => 'GPS Longitude', 'type' => 'number', 'step' => '0.00000001'],
        ['name' => 'status_connection', 'label' => 'Connection Status', 'type' => 'select', 'options' => ['connected', 'disconnected', 'intermittent']],
        ['name' => 'alert_status', 'label' => 'Alert Status', 'type' => 'select', 'options' => ['normal', 'warning', 'critical']],
        ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
    ],
    'calculate' => function ($data) {
        $battery = (float) ($data['battery_percent'] ?? 0);
        $temperature = (float) ($data['temperature_c'] ?? 0);
        $connection = $data['status_connection'] ?? '';
        $alert = $data['alert_status'] ?? '';
        $category = 'stable';

        if ($alert === 'critical' || $connection === 'disconnected' || $battery < 15 || $temperature >= 75) {
            $category = 'critical';
        } elseif ($alert === 'warning' || $connection === 'intermittent' || $battery < 30 || $temperature >= 60) {
            $category = 'degraded';
        }

        return [
            'power_w' => calculatePower((float) ($data['voltage_v'] ?? 0), (float) ($data['current_a'] ?? 0)),
            'status_category' => $category,
        ];
    },
    'formulas' => [
        'Power (W) = Voltage x Current',
        'Status stable/degraded/critical berdasarkan alert, koneksi, battery, dan suhu.',
    ],
    'columns' => [
        ['label' => 'Date', 'field' => 'test_date', 'format' => 'date'],
        ['label' => 'Node', 'field' => 'node_id'],
        ['label' => 'Battery', 'field' => 'battery_percent', 'decimals' => 2, 'suffix' => '%'],
        ['label' => 'Temp', 'field' => 'temperature_c', 'decimals' => 2, 'suffix' => ' C'],
        ['label' => 'RSSI', 'field' => 'rssi_dbm', 'decimals' => 2, 'suffix' => ' dBm'],
        ['label' => 'Power', 'field' => 'power_w', 'decimals' => 2, 'suffix' => ' W'],
        ['label' => 'Connection', 'field' => 'status_connection', 'format' => 'status'],
        ['label' => 'Category', 'field' => 'status_category', 'format' => 'status'],
    ],
];

include __DIR__ . '/_test_page.php';
