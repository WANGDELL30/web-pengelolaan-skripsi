<?php
$pageConfig = [
    'title' => 'Signal Penetration Test',
    'icon' => 'fas fa-shield-alt',
    'description' => 'Input data penetrasi sinyal terhadap hambatan dan kondisi LOS/NLOS.',
    'table' => 'signal_penetration_tests',
    'order' => 'test_date DESC, created_at DESC',
    'chart_label_fields' => ['obstacle_type', 'distance_meter'],
    'chart_label_caption' => 'Label grafik: obstacle - distance meter',
    'chart_metrics' => [
        ['field' => 'rssi_before_dbm', 'label' => 'RSSI Before', 'unit' => 'dBm', 'type' => 'line'],
        ['field' => 'rssi_after_dbm', 'label' => 'RSSI After', 'unit' => 'dBm', 'type' => 'line'],
        ['field' => 'rssi_loss', 'label' => 'RSSI Loss', 'unit' => 'dB', 'type' => 'bar'],
        ['field' => 'packet_loss_percent', 'label' => 'Packet Loss', 'unit' => '%', 'type' => 'bar'],
    ],
    'chart_notes' => [
        'RSSI After yang lebih mendekati 0 dBm berarti sinyal setelah hambatan masih lebih kuat.',
        'RSSI Loss dan Packet Loss yang makin tinggi menunjukkan hambatan makin berdampak pada link.',
    ],
    'fields' => [
        ['name' => 'test_date', 'label' => 'Test Date', 'type' => 'date', 'required' => true],
        ['name' => 'location_name', 'label' => 'Location Name', 'required' => true],
        ['name' => 'obstacle_type', 'label' => 'Obstacle Type', 'type' => 'select', 'options' => ['wall', 'building', 'trees', 'vehicle', 'hangar', 'hill', 'none']],
        ['name' => 'condition_type', 'label' => 'Condition', 'type' => 'select', 'options' => ['LOS', 'NLOS']],
        ['name' => 'distance_meter', 'label' => 'Distance (m)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'rssi_before_dbm', 'label' => 'RSSI Before (dBm)', 'type' => 'number', 'step' => '0.01', 'required' => true],
        ['name' => 'rssi_after_dbm', 'label' => 'RSSI After (dBm)', 'type' => 'number', 'step' => '0.01', 'required' => true],
        ['name' => 'snr_before_db', 'label' => 'SNR Before (dB)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'snr_after_db', 'label' => 'SNR After (dB)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'packet_sent', 'label' => 'Packet Sent', 'type' => 'number', 'integer' => true, 'default' => 1000],
        ['name' => 'packet_received', 'label' => 'Packet Received', 'type' => 'number', 'integer' => true, 'default' => 1000],
        ['name' => 'bitrate_kbps', 'label' => 'Bitrate (kbps)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
    ],
    'calculate' => function ($data) {
        $rssiLoss = round((float) $data['rssi_before_dbm'] - (float) $data['rssi_after_dbm'], 2);
        return [
            'rssi_loss' => $rssiLoss,
            'snr_loss' => round((float) $data['snr_before_db'] - (float) $data['snr_after_db'], 2),
            'packet_loss_percent' => calculatePacketLoss((int) $data['packet_sent'], (int) $data['packet_received']),
            'penetration_loss_db' => $rssiLoss,
        ];
    },
    'formulas' => [
        'RSSI Loss = RSSI Before - RSSI After',
        'SNR Loss = SNR Before - SNR After',
        'Packet Loss % = (Packet Lost / Packet Sent) x 100',
    ],
    'columns' => [
        ['label' => 'Date', 'field' => 'test_date', 'format' => 'date'],
        ['label' => 'Location', 'field' => 'location_name'],
        ['label' => 'Obstacle', 'field' => 'obstacle_type'],
        ['label' => 'Condition', 'field' => 'condition_type'],
        ['label' => 'Distance', 'field' => 'distance_meter', 'decimals' => 2, 'suffix' => ' m'],
        ['label' => 'RSSI Loss', 'field' => 'rssi_loss', 'decimals' => 2, 'suffix' => ' dB'],
        ['label' => 'Packet Loss', 'field' => 'packet_loss_percent', 'decimals' => 2, 'suffix' => '%'],
    ],
];

include __DIR__ . '/_test_page.php';
