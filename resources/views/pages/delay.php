<?php
$pageConfig = [
    'title' => 'Monitoring Delay',
    'icon' => 'fas fa-hourglass-half',
    'description' => 'Input delay dari event node sampai tampil di dashboard.',
    'table' => 'monitoring_delay_tests',
    'order' => 'test_date DESC, created_at DESC',
    'fields' => [
        ['name' => 'test_date', 'label' => 'Test Date', 'type' => 'date', 'required' => true],
        ['name' => 'event_name', 'label' => 'Event Name', 'required' => true],
        ['name' => 'node_id', 'label' => 'Node ID', 'required' => true],
        ['name' => 'timestamp_event_generated_ms', 'label' => 'Event Generated Time (ms)', 'type' => 'number', 'integer' => true],
        ['name' => 'timestamp_displayed_dashboard_ms', 'label' => 'Displayed Dashboard Time (ms)', 'type' => 'number', 'integer' => true],
        ['name' => 'network_mode', 'label' => 'Network Mode', 'type' => 'select', 'options' => ['HaLow only', 'HaLow + VSAT']],
        ['name' => 'monitoring_delay_ms', 'label' => 'Monitoring Delay (ms)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
    ],
    'calculate' => function ($data) {
        $delay = (float) ($data['monitoring_delay_ms'] ?? 0);
        if (!$delay && $data['timestamp_event_generated_ms'] && $data['timestamp_displayed_dashboard_ms']) {
            $delay = max(0, (int) $data['timestamp_displayed_dashboard_ms'] - (int) $data['timestamp_event_generated_ms']);
        }
        $status = $delay < 100 ? 'fast' : ($delay <= 500 ? 'acceptable' : 'slow');

        return [
            'monitoring_delay_ms' => $delay,
            'average_monitoring_delay' => $delay,
            'delay_status' => $status,
        ];
    },
    'formulas' => [
        'Monitoring Delay = Displayed Dashboard Time - Event Generated Time',
        'Status: fast < 100 ms, acceptable <= 500 ms, slow > 500 ms.',
    ],
    'columns' => [
        ['label' => 'Date', 'field' => 'test_date', 'format' => 'date'],
        ['label' => 'Event', 'field' => 'event_name'],
        ['label' => 'Node', 'field' => 'node_id'],
        ['label' => 'Mode', 'field' => 'network_mode'],
        ['label' => 'Delay', 'field' => 'monitoring_delay_ms', 'decimals' => 2, 'suffix' => ' ms'],
        ['label' => 'Status', 'field' => 'delay_status', 'format' => 'status'],
    ],
];

include __DIR__ . '/_test_page.php';
