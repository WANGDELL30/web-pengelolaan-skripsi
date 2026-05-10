<?php
$pageConfig = [
    'title' => 'Response Time',
    'icon' => 'fas fa-stopwatch',
    'description' => 'Input waktu respons command/request dari node target.',
    'table' => 'response_time_tests',
    'order' => 'test_date DESC, created_at DESC',
    'chart_label_fields' => ['command_type', 'target_node_id'],
    'chart_label_caption' => 'Label grafik: command - target node',
    'chart_metrics' => [
        ['field' => 'response_time_total_ms', 'label' => 'Response Time', 'unit' => 'ms', 'type' => 'bar'],
        ['field' => 'average_response_time', 'label' => 'Average Response', 'unit' => 'ms', 'type' => 'line'],
        ['field' => 'minimum_response_time', 'label' => 'Minimum Response', 'unit' => 'ms', 'type' => 'line'],
        ['field' => 'maximum_response_time', 'label' => 'Maximum Response', 'unit' => 'ms', 'type' => 'line'],
    ],
    'chart_status_field' => 'status',
    'chart_notes' => [
        'Response time makin rendah berarti command/request makin cepat dijawab.',
        'Perbedaan minimum dan maximum membantu melihat konsistensi respons.',
    ],
    'fields' => [
        ['name' => 'test_date', 'label' => 'Test Date', 'type' => 'date', 'required' => true],
        ['name' => 'command_type', 'label' => 'Command Type', 'required' => true],
        ['name' => 'target_node_id', 'label' => 'Target Node ID', 'required' => true],
        ['name' => 'request_time_ms', 'label' => 'Request Time (ms)', 'type' => 'number', 'integer' => true],
        ['name' => 'response_time_ms', 'label' => 'Response Time (ms)', 'type' => 'number', 'integer' => true],
        ['name' => 'network_mode', 'label' => 'Network Mode', 'type' => 'select', 'options' => ['HaLow only', 'HaLow + VSAT', 'HaLow + Starlink']],
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['success', 'fail', 'timeout']],
        ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
    ],
    'calculate' => function ($data) {
        $total = max(0, (int) ($data['response_time_ms'] ?? 0) - (int) ($data['request_time_ms'] ?? 0));
        return [
            'response_time_total_ms' => $total,
            'average_response_time' => $total,
            'minimum_response_time' => $total,
            'maximum_response_time' => $total,
        ];
    },
    'formulas' => [
        'Response Time Total = Response Time - Request Time',
        'Average/Minimum/Maximum mengikuti satu nilai respons per input.',
    ],
    'columns' => [
        ['label' => 'Date', 'field' => 'test_date', 'format' => 'date'],
        ['label' => 'Command', 'field' => 'command_type'],
        ['label' => 'Target', 'field' => 'target_node_id'],
        ['label' => 'Mode', 'field' => 'network_mode'],
        ['label' => 'Response', 'field' => 'response_time_total_ms', 'decimals' => 2, 'suffix' => ' ms'],
        ['label' => 'Status', 'field' => 'status', 'format' => 'status'],
    ],
];

include __DIR__ . '/_test_page.php';
