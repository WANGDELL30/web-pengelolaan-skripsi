<?php
$pageConfig = [
    'title' => 'Camera Test',
    'icon' => 'fas fa-video',
    'description' => 'Input pengujian slave camera, delay kamera, FPS, dan kualitas gambar.',
    'table' => 'slave_camera_tests',
    'order' => 'test_date DESC, created_at DESC',
    'fields' => [
        ['name' => 'test_date', 'label' => 'Test Date', 'type' => 'date', 'required' => true],
        ['name' => 'location_name', 'label' => 'Location Name', 'required' => true],
        ['name' => 'node_id', 'label' => 'Node ID', 'required' => true],
        ['name' => 'distance_meter', 'label' => 'Distance (m)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'resolution', 'label' => 'Resolution', 'default' => '720p'],
        ['name' => 'fps', 'label' => 'FPS', 'type' => 'number', 'integer' => true],
        ['name' => 'image_quality_score', 'label' => 'Image Quality Score (1-5)', 'type' => 'number', 'integer' => true, 'min' => 1, 'max' => 5],
        ['name' => 'camera_delay_ms', 'label' => 'Camera Delay (ms)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'packet_loss_percent', 'label' => 'Packet Loss (%)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['success', 'fail']],
        ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
    ],
    'calculate' => function ($data) {
        return [
            'average_camera_delay' => (float) ($data['camera_delay_ms'] ?? 0),
            'average_fps' => (float) ($data['fps'] ?? 0),
            'camera_quality_category' => determineCameraQuality((int) ($data['image_quality_score'] ?? 0)),
        ];
    },
    'formulas' => [
        'Average Camera Delay mengikuti nilai delay per input.',
        'Average FPS mengikuti nilai FPS per input.',
        'Quality Category: good >= 4, moderate >= 3, poor < 3.',
    ],
    'columns' => [
        ['label' => 'Date', 'field' => 'test_date', 'format' => 'date'],
        ['label' => 'Location', 'field' => 'location_name'],
        ['label' => 'Node', 'field' => 'node_id'],
        ['label' => 'Resolution', 'field' => 'resolution'],
        ['label' => 'FPS', 'field' => 'fps'],
        ['label' => 'Delay', 'field' => 'camera_delay_ms', 'decimals' => 2, 'suffix' => ' ms'],
        ['label' => 'Quality', 'field' => 'camera_quality_category', 'format' => 'status'],
        ['label' => 'Status', 'field' => 'status', 'format' => 'status'],
    ],
];

include __DIR__ . '/_test_page.php';
