<?php
$pageConfig = [
    'title' => 'Star Topology Test',
    'icon' => 'fas fa-project-diagram',
    'description' => 'Input performa topologi star, node aktif, latency, throughput, dan status gateway.',
    'table' => 'star_topology_tests',
    'order' => 'test_date DESC, created_at DESC',
    'fields' => [
        ['name' => 'test_date', 'label' => 'Test Date', 'type' => 'date', 'required' => true],
        ['name' => 'location_name', 'label' => 'Location Name', 'required' => true],
        ['name' => 'master_id', 'label' => 'Master ID', 'required' => true],
        ['name' => 'total_slave_nodes', 'label' => 'Total Slave Nodes', 'type' => 'number', 'integer' => true, 'default' => 1],
        ['name' => 'active_slave_nodes', 'label' => 'Active Slave Nodes', 'type' => 'number', 'integer' => true, 'default' => 1],
        ['name' => 'distance_average_meter', 'label' => 'Average Distance (m)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'average_latency_ms', 'label' => 'Average Latency (ms)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'average_throughput_kbps', 'label' => 'Average Throughput (kbps)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'packet_sent', 'label' => 'Packet Sent', 'type' => 'number', 'integer' => true, 'default' => 1000],
        ['name' => 'packet_received', 'label' => 'Packet Received', 'type' => 'number', 'integer' => true, 'default' => 1000],
        ['name' => 'gateway_cpu_usage_percent', 'label' => 'Gateway CPU Usage (%)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'gateway_temperature_c', 'label' => 'Gateway Temperature (C)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
    ],
    'calculate' => function ($data) {
        $packetLoss = calculatePacketLoss((int) $data['packet_sent'], (int) $data['packet_received']);
        $totalNodes = (int) ($data['total_slave_nodes'] ?? 0);
        $activeNodes = (int) ($data['active_slave_nodes'] ?? 0);

        return [
            'packet_loss_percent' => $packetLoss,
            'node_success_rate' => $totalNodes > 0 ? round(($activeNodes / $totalNodes) * 100, 2) : 0,
            'topology_status' => determineTopologyStatus($packetLoss),
        ];
    },
    'formulas' => [
        'Packet Loss % = (Packet Lost / Packet Sent) x 100',
        'Node Success Rate = Active Slave Nodes / Total Slave Nodes x 100',
        'Topology Status berdasarkan packet loss: stable, degraded, critical.',
    ],
    'columns' => [
        ['label' => 'Date', 'field' => 'test_date', 'format' => 'date'],
        ['label' => 'Location', 'field' => 'location_name'],
        ['label' => 'Master', 'field' => 'master_id'],
        ['label' => 'Nodes', 'value' => fn($row) => htmlspecialchars($row['active_slave_nodes'] . '/' . $row['total_slave_nodes'])],
        ['label' => 'Latency', 'field' => 'average_latency_ms', 'decimals' => 2, 'suffix' => ' ms'],
        ['label' => 'Throughput', 'field' => 'average_throughput_kbps', 'decimals' => 2, 'suffix' => ' kbps'],
        ['label' => 'Packet Loss', 'field' => 'packet_loss_percent', 'decimals' => 2, 'suffix' => '%'],
        ['label' => 'Status', 'field' => 'topology_status', 'format' => 'status'],
    ],
];

include __DIR__ . '/_test_page.php';
