<?php
$pageConfig = [
    'title' => 'Throughput Test',
    'icon' => 'fas fa-bolt',
    'description' => 'Input pengujian throughput, PDR, dan data loss.',
    'table' => 'throughput_tests',
    'order' => 'test_date DESC, created_at DESC',
    'chart_label_fields' => ['node_id', 'distance_meter'],
    'chart_label_caption' => 'Label grafik: node - distance meter',
    'chart_metrics' => [
        ['field' => 'throughput_kbps', 'label' => 'Throughput', 'unit' => 'kbps', 'type' => 'line'],
        ['field' => 'packet_delivery_ratio_percent', 'label' => 'PDR', 'unit' => '%', 'type' => 'bar'],
        ['field' => 'data_loss_percent', 'label' => 'Data Loss', 'unit' => '%', 'type' => 'bar'],
        ['field' => 'snr_db', 'label' => 'SNR', 'unit' => 'dB', 'type' => 'line'],
    ],
    'chart_notes' => [
        'Throughput dan PDR yang tinggi menandakan transfer data berjalan baik.',
        'Data Loss yang naik biasanya berbanding terbalik dengan kualitas link.',
    ],
    'fields' => [
        ['name' => 'test_date', 'label' => 'Test Date', 'type' => 'date', 'required' => true],
        ['name' => 'location_name', 'label' => 'Location Name', 'required' => true],
        ['name' => 'environment_type', 'label' => 'Environment Type', 'type' => 'select', 'options' => ['lapangan', 'hangar', 'pantai', 'gunung', 'indoor', 'outdoor']],
        ['name' => 'node_id', 'label' => 'Node ID', 'required' => true],
        ['name' => 'distance_meter', 'label' => 'Distance (m)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'data_sent_kb', 'label' => 'Data Sent (KB)', 'type' => 'number', 'step' => '0.01', 'required' => true],
        ['name' => 'data_received_kb', 'label' => 'Data Received (KB)', 'type' => 'number', 'step' => '0.01', 'required' => true],
        ['name' => 'transmission_time_second', 'label' => 'Transmission Time (s)', 'type' => 'number', 'step' => '0.01', 'required' => true],
        ['name' => 'rssi_dbm', 'label' => 'RSSI (dBm)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'snr_db', 'label' => 'SNR (dB)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'bitrate_kbps', 'label' => 'Bitrate (kbps)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
    ],
    'calculate' => function ($data) {
        $sent = (float) ($data['data_sent_kb'] ?? 0);
        $received = (float) ($data['data_received_kb'] ?? 0);
        return [
            'throughput_kbps' => calculateThroughput($received, (float) ($data['transmission_time_second'] ?? 0)),
            'packet_delivery_ratio_percent' => $sent > 0 ? round(($received / $sent) * 100, 2) : 0,
            'data_loss_percent' => $sent > 0 ? round((($sent - $received) / $sent) * 100, 2) : 0,
        ];
    },
    'formulas' => [
        'Throughput = (Data Received x 1024 x 8) / (Time x 1000)',
        'PDR % = (Data Received / Data Sent) x 100',
        'Data Loss % = ((Data Sent - Data Received) / Data Sent) x 100',
    ],
    'columns' => [
        ['label' => 'Date', 'field' => 'test_date', 'format' => 'date'],
        ['label' => 'Location', 'field' => 'location_name'],
        ['label' => 'Node', 'field' => 'node_id'],
        ['label' => 'Distance', 'field' => 'distance_meter', 'decimals' => 2, 'suffix' => ' m'],
        ['label' => 'Throughput', 'field' => 'throughput_kbps', 'decimals' => 2, 'suffix' => ' kbps'],
        ['label' => 'PDR', 'field' => 'packet_delivery_ratio_percent', 'decimals' => 2, 'suffix' => '%'],
        ['label' => 'Data Loss', 'field' => 'data_loss_percent', 'decimals' => 2, 'suffix' => '%'],
    ],
];

include __DIR__ . '/_test_page.php';
