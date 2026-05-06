<?php
$pageConfig = [
    'title' => 'Command Execution',
    'icon' => 'fas fa-terminal',
    'description' => 'Input pengujian pengiriman dan eksekusi command ke node target.',
    'table' => 'command_execution_tests',
    'order' => 'test_date DESC, created_at DESC',
    'fields' => [
        ['name' => 'test_date', 'label' => 'Test Date', 'type' => 'date', 'required' => true],
        ['name' => 'command_type', 'label' => 'Command Type', 'type' => 'select', 'options' => ['reset', 'shutdown', 'restart', 'turn_on', 'turn_off', 'configuration_update']],
        ['name' => 'source', 'label' => 'Source', 'required' => true],
        ['name' => 'target_node_id', 'label' => 'Target Node ID', 'required' => true],
        ['name' => 'command_sent_time_ms', 'label' => 'Command Sent Time (ms)', 'type' => 'number', 'integer' => true],
        ['name' => 'command_received_time_ms', 'label' => 'Command Received Time (ms)', 'type' => 'number', 'integer' => true],
        ['name' => 'command_executed_time_ms', 'label' => 'Command Executed Time (ms)', 'type' => 'number', 'integer' => true],
        ['name' => 'execution_status', 'label' => 'Execution Status', 'type' => 'select', 'options' => ['success', 'fail']],
        ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
    ],
    'calculate' => function ($data) {
        $sent = (int) ($data['command_sent_time_ms'] ?? 0);
        $received = (int) ($data['command_received_time_ms'] ?? 0);
        $executed = (int) ($data['command_executed_time_ms'] ?? 0);

        return [
            'command_delivery_delay' => max(0, $received - $sent),
            'command_execution_delay' => max(0, $executed - $received),
            'total_command_time' => max(0, $executed - $sent),
            'command_success_rate' => ($data['execution_status'] ?? '') === 'success' ? 100 : 0,
        ];
    },
    'formulas' => [
        'Delivery Delay = Received Time - Sent Time',
        'Execution Delay = Executed Time - Received Time',
        'Total Command Time = Executed Time - Sent Time',
    ],
    'columns' => [
        ['label' => 'Date', 'field' => 'test_date', 'format' => 'date'],
        ['label' => 'Command', 'field' => 'command_type'],
        ['label' => 'Source', 'field' => 'source'],
        ['label' => 'Target', 'field' => 'target_node_id'],
        ['label' => 'Delivery', 'field' => 'command_delivery_delay', 'decimals' => 2, 'suffix' => ' ms'],
        ['label' => 'Total', 'field' => 'total_command_time', 'decimals' => 2, 'suffix' => ' ms'],
        ['label' => 'Status', 'field' => 'execution_status', 'format' => 'status'],
    ],
];

include __DIR__ . '/_test_page.php';
