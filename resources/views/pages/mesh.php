<?php
$pageConfig = [
    'title' => 'Mesh Topology Analysis',
    'icon' => 'fas fa-sitemap',
    'description' => 'Analisis estimasi latency, daya, throughput, dan efisiensi topologi mesh.',
    'table' => 'mesh_topology_analysis',
    'order' => 'analysis_date DESC, created_at DESC',
    'fields' => [
        ['name' => 'analysis_date', 'label' => 'Analysis Date', 'type' => 'date', 'required' => true],
        ['name' => 'scenario_name', 'label' => 'Scenario Name', 'required' => true],
        ['name' => 'total_nodes', 'label' => 'Total Nodes', 'type' => 'number', 'integer' => true, 'default' => 1],
        ['name' => 'hop_count', 'label' => 'Hop Count', 'type' => 'number', 'integer' => true, 'default' => 1],
        ['name' => 'estimated_latency_per_hop_ms', 'label' => 'Latency per Hop (ms)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'estimated_power_per_node_w', 'label' => 'Power per Node (W)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'estimated_throughput_kbps', 'label' => 'Estimated Throughput (kbps)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'reliability_score_percent', 'label' => 'Reliability Score (%)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
    ],
    'calculate' => function ($data) {
        $nodes = max(0, (int) ($data['total_nodes'] ?? 0));
        $hops = max(0, (int) ($data['hop_count'] ?? 0));
        $latency = (float) ($data['estimated_latency_per_hop_ms'] ?? 0);
        $power = (float) ($data['estimated_power_per_node_w'] ?? 0);
        $throughput = (float) ($data['estimated_throughput_kbps'] ?? 0);
        $reliability = (float) ($data['reliability_score_percent'] ?? 0);
        $totalLatency = $hops * $latency;
        $totalPower = $nodes * $power;
        $denominator = max(1, $totalLatency + $totalPower);

        return [
            'total_estimated_latency' => round($totalLatency, 2),
            'total_estimated_power' => round($totalPower, 2),
            'efficiency_score' => round(($reliability * $throughput) / $denominator, 4),
        ];
    },
    'formulas' => [
        'Total Latency = Hop Count x Latency per Hop',
        'Total Power = Total Nodes x Power per Node',
        'Efficiency Score = Reliability x Throughput / (Total Latency + Total Power)',
    ],
    'columns' => [
        ['label' => 'Date', 'field' => 'analysis_date', 'format' => 'date'],
        ['label' => 'Scenario', 'field' => 'scenario_name'],
        ['label' => 'Nodes', 'field' => 'total_nodes'],
        ['label' => 'Hop', 'field' => 'hop_count'],
        ['label' => 'Total Latency', 'field' => 'total_estimated_latency', 'decimals' => 2, 'suffix' => ' ms'],
        ['label' => 'Total Power', 'field' => 'total_estimated_power', 'decimals' => 2, 'suffix' => ' W'],
        ['label' => 'Efficiency', 'field' => 'efficiency_score', 'decimals' => 4],
    ],
];

include __DIR__ . '/_test_page.php';
