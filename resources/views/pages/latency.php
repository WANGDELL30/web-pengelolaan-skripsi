<?php
$pageConfig = [
    'title' => 'Latency Test',
    'icon' => 'fas fa-clock',
    'description' => 'Input pengujian latency, jitter, dan packet loss.',
    'table' => 'latency_tests',
    'order' => 'test_date DESC, created_at DESC',
    'fields' => [
        ['name' => 'test_date', 'label' => 'Test Date', 'type' => 'date', 'required' => true],
        ['name' => 'location_name', 'label' => 'Location Name', 'required' => true],
        ['name' => 'environment_type', 'label' => 'Environment Type', 'type' => 'select', 'options' => ['lapangan', 'hangar', 'pantai', 'gunung', 'indoor', 'outdoor']],
        ['name' => 'node_id', 'label' => 'Node ID', 'required' => true],
        ['name' => 'distance_meter', 'label' => 'Distance (m)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'trial_number', 'label' => 'Trial Number', 'type' => 'number', 'integer' => true, 'default' => 1],
        ['name' => 'timestamp_send_ms', 'label' => 'Timestamp Send (ms)', 'type' => 'number', 'integer' => true],
        ['name' => 'timestamp_receive_ms', 'label' => 'Timestamp Receive (ms)', 'type' => 'number', 'integer' => true],
        ['name' => 'packet_sent', 'label' => 'Packet Sent', 'type' => 'number', 'integer' => true, 'default' => 100],
        ['name' => 'packet_received', 'label' => 'Packet Received', 'type' => 'number', 'integer' => true, 'default' => 100],
        ['name' => 'network_mode', 'label' => 'Network Mode', 'type' => 'select', 'options' => ['HaLow only', 'HaLow + VSAT']],
        ['name' => 'latency_ms', 'label' => 'Latency (ms)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'jitter_ms', 'label' => 'Jitter (ms)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
    ],
    'calculate' => function ($data) {
        $latency = (float) ($data['latency_ms'] ?? 0);
        if (!$latency && $data['timestamp_send_ms'] && $data['timestamp_receive_ms']) {
            $latency = max(0, (int) $data['timestamp_receive_ms'] - (int) $data['timestamp_send_ms']);
        }

        return [
            'latency_ms' => $latency,
            'packet_loss_percent' => calculatePacketLoss((int) $data['packet_sent'], (int) $data['packet_received']),
            'average_latency' => $latency,
            'minimum_latency' => $latency,
            'maximum_latency' => $latency,
            'average_jitter' => (float) ($data['jitter_ms'] ?? 0),
        ];
    },
    'formulas' => [
        'Latency = Timestamp Receive - Timestamp Send',
        'Packet Loss % = (Packet Lost / Packet Sent) x 100',
        'Average/Min/Max mengikuti nilai latency per input.',
    ],
    'columns' => [
        ['label' => 'Date', 'field' => 'test_date', 'format' => 'date'],
        ['label' => 'Location', 'field' => 'location_name'],
        ['label' => 'Node', 'field' => 'node_id'],
        ['label' => 'Mode', 'field' => 'network_mode'],
        ['label' => 'Latency', 'field' => 'latency_ms', 'decimals' => 2, 'suffix' => ' ms'],
        ['label' => 'Jitter', 'field' => 'jitter_ms', 'decimals' => 2, 'suffix' => ' ms'],
        ['label' => 'Packet Loss', 'field' => 'packet_loss_percent', 'decimals' => 2, 'suffix' => '%'],
    ],
];

include __DIR__ . '/_test_page.php';
