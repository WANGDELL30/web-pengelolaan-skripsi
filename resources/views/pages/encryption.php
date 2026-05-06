<?php
$pageConfig = [
    'title' => 'Encryption Test',
    'icon' => 'fas fa-lock',
    'description' => 'Input pengujian enkripsi, sniffing, dan integritas data.',
    'table' => 'encryption_tests',
    'order' => 'test_date DESC, created_at DESC',
    'chart_label_fields' => ['protocol_used', 'encryption_type'],
    'chart_label_caption' => 'Label grafik: protocol - encryption type',
    'chart_metrics' => [
        ['field' => 'key_length_bit', 'label' => 'Key Length', 'unit' => 'bit', 'type' => 'bar'],
    ],
    'chart_status_field' => 'encryption_status',
    'chart_notes' => [
        'Status secure hanya tercapai bila sniffing unreadable dan data integrity valid.',
        'Distribusi status membantu melihat berapa banyak pengujian yang masih insecure.',
    ],
    'fields' => [
        ['name' => 'test_date', 'label' => 'Test Date', 'type' => 'date', 'required' => true],
        ['name' => 'protocol_used', 'label' => 'Protocol Used', 'required' => true],
        ['name' => 'encryption_type', 'label' => 'Encryption Type', 'default' => 'WPA3'],
        ['name' => 'key_length_bit', 'label' => 'Key Length (bit)', 'type' => 'number', 'integer' => true, 'default' => 256],
        ['name' => 'sniffing_test_result', 'label' => 'Sniffing Test Result', 'type' => 'select', 'options' => ['readable', 'unreadable']],
        ['name' => 'data_integrity_status', 'label' => 'Data Integrity Status', 'type' => 'select', 'options' => ['valid', 'invalid']],
        ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
    ],
    'calculate' => function ($data) {
        $secure = ($data['sniffing_test_result'] ?? '') === 'unreadable' && ($data['data_integrity_status'] ?? '') === 'valid';
        return [
            'encryption_status' => $secure ? 'secure' : 'insecure',
        ];
    },
    'formulas' => [
        'Status secure bila hasil sniffing unreadable dan integritas data valid.',
    ],
    'columns' => [
        ['label' => 'Date', 'field' => 'test_date', 'format' => 'date'],
        ['label' => 'Protocol', 'field' => 'protocol_used'],
        ['label' => 'Encryption', 'field' => 'encryption_type'],
        ['label' => 'Key', 'field' => 'key_length_bit', 'suffix' => ' bit'],
        ['label' => 'Sniffing', 'field' => 'sniffing_test_result'],
        ['label' => 'Integrity', 'field' => 'data_integrity_status', 'format' => 'status'],
        ['label' => 'Status', 'field' => 'encryption_status', 'format' => 'status'],
    ],
];

include __DIR__ . '/_test_page.php';
