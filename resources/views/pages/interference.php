<?php
$pageConfig = [
    'title' => 'Interference Test',
    'icon' => 'fas fa-wifi',
    'description' => 'Input pengujian ketahanan terhadap interferensi.',
    'table' => 'interference_tests',
    'order' => 'test_date DESC, created_at DESC',
    'fields' => [
        ['name' => 'test_date', 'label' => 'Test Date', 'type' => 'date', 'required' => true],
        ['name' => 'location_name', 'label' => 'Location Name', 'required' => true],
        ['name' => 'interference_level', 'label' => 'Interference Level', 'type' => 'select', 'options' => ['normal', 'low', 'medium', 'high']],
        ['name' => 'interference_source', 'label' => 'Interference Source'],
        ['name' => 'distance_meter', 'label' => 'Distance (m)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'rssi_dbm', 'label' => 'RSSI (dBm)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'snr_db', 'label' => 'SNR (dB)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'throughput_kbps', 'label' => 'Throughput (kbps)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'latency_ms', 'label' => 'Latency (ms)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'packet_sent', 'label' => 'Packet Sent', 'type' => 'number', 'integer' => true, 'default' => 1000],
        ['name' => 'packet_received', 'label' => 'Packet Received', 'type' => 'number', 'integer' => true, 'default' => 1000],
        ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
    ],
    'calculate' => function ($data) {
        return [
            'packet_loss_percent' => calculatePacketLoss((int) $data['packet_sent'], (int) $data['packet_received']),
            'throughput_degradation_percent' => null,
            'latency_increase_percent' => null,
            'snr_degradation_db' => null,
        ];
    },
    'formulas' => [
        'Packet Loss % = (Packet Lost / Packet Sent) x 100',
        'Throughput degradation, latency increase, dan SNR degradation disimpan kosong bila tidak ada baseline.',
    ],
    'columns' => [
        ['label' => 'Date', 'field' => 'test_date', 'format' => 'date'],
        ['label' => 'Location', 'field' => 'location_name'],
        ['label' => 'Level', 'field' => 'interference_level'],
        ['label' => 'Source', 'field' => 'interference_source'],
        ['label' => 'Throughput', 'field' => 'throughput_kbps', 'decimals' => 2, 'suffix' => ' kbps'],
        ['label' => 'Latency', 'field' => 'latency_ms', 'decimals' => 2, 'suffix' => ' ms'],
        ['label' => 'Packet Loss', 'field' => 'packet_loss_percent', 'decimals' => 2, 'suffix' => '%'],
    ],
];

include __DIR__ . '/_test_page.php';
