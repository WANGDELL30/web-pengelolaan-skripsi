<?php
$pageConfig = [
    'title' => 'Power Consumption Test',
    'icon' => 'fas fa-battery-full',
    'description' => 'Input konsumsi daya, energi, dan estimasi runtime perangkat.',
    'table' => 'power_consumption_tests',
    'order' => 'test_date DESC, created_at DESC',
    'chart_label_fields' => ['device_id', 'test_date'],
    'chart_label_caption' => 'Label grafik: device - tanggal test',
    'chart_metrics' => [
        ['field' => 'power_w', 'label' => 'Power', 'unit' => 'W', 'type' => 'bar'],
        ['field' => 'estimated_runtime_hour', 'label' => 'Runtime', 'unit' => 'h', 'type' => 'line'],
        ['field' => 'cpu_usage_percent', 'label' => 'CPU Usage', 'unit' => '%', 'type' => 'bar'],
    ],
    'chart_status_field' => 'device_type',
    'chart_notes' => [
        'Power tinggi biasanya mengurangi estimasi runtime baterai.',
        'CPU usage dan RAM usage membantu membaca beban perangkat saat pengujian.',
    ],
    'fields' => [
        ['name' => 'test_date', 'label' => 'Test Date', 'type' => 'date', 'required' => true],
        ['name' => 'device_id', 'label' => 'Device ID', 'required' => true],
        ['name' => 'device_type', 'label' => 'Device Type', 'type' => 'select', 'options' => ['master', 'slave']],
        ['name' => 'battery_voltage_v', 'label' => 'Battery Voltage (V)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'current_a', 'label' => 'Current (A)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'test_duration_hour', 'label' => 'Test Duration (hour)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'battery_capacity_mah', 'label' => 'Battery Capacity (mAh)', 'type' => 'number', 'integer' => true],
        ['name' => 'cpu_usage_percent', 'label' => 'CPU Usage (%)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'ram_usage_percent', 'label' => 'RAM Usage (%)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'rssi_dbm', 'label' => 'RSSI (dBm)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'snr_db', 'label' => 'SNR (dB)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
    ],
    'calculate' => function ($data) {
        $power = calculatePower($data['battery_voltage_v'] ?? null, $data['current_a'] ?? null);
        $capacityWh = calculateBatteryCapacityWh($data['battery_voltage_v'] ?? null, $data['battery_capacity_mah'] ?? null);
        $runtimeHour = calculateRuntime($capacityWh, $power);

        return [
            'power_w' => $power,
            'energy_wh' => calculateEnergy($power, $data['test_duration_hour'] ?? null),
            'battery_capacity_wh' => $capacityWh,
            'estimated_runtime_hour' => $runtimeHour,
            'estimated_runtime_day' => $runtimeHour === null ? null : round($runtimeHour / 24, 2),
        ];
    },
    'formulas' => [
        'Power (W) = Voltage x Current',
        'Energy (Wh) = Power x Duration',
        'Battery Capacity (Wh) = Voltage x Capacity mAh / 1000',
        'Runtime (hour) = Battery Capacity Wh / Power W',
    ],
    'columns' => [
        ['label' => 'Date', 'field' => 'test_date', 'format' => 'date'],
        ['label' => 'Device', 'field' => 'device_id'],
        ['label' => 'Type', 'field' => 'device_type'],
        ['label' => 'Power', 'field' => 'power_w', 'decimals' => 2, 'suffix' => ' W'],
        ['label' => 'Energy', 'field' => 'energy_wh', 'decimals' => 4, 'suffix' => ' Wh'],
        ['label' => 'Runtime', 'field' => 'estimated_runtime_hour', 'decimals' => 2, 'suffix' => ' h'],
    ],
];

include __DIR__ . '/_test_page.php';
