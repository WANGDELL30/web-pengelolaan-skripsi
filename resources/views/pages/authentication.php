<?php
$pageConfig = [
    'title' => 'Authentication Test',
    'icon' => 'fas fa-user-shield',
    'description' => 'Input pengujian autentikasi user, metode login, dan tingkat keberhasilan.',
    'table' => 'authentication_tests',
    'order' => 'test_date DESC, created_at DESC',
    'chart_label_fields' => ['login_attempt_type', 'user_role'],
    'chart_label_caption' => 'Label grafik: attempt type - user role',
    'chart_metric_limit' => 5,
    'chart_metrics' => [
        ['field' => 'attempt_count', 'label' => 'Attempts', 'unit' => '', 'type' => 'bar'],
        ['field' => 'success_count', 'label' => 'Success Count', 'unit' => '', 'type' => 'bar'],
        ['field' => 'failed_count', 'label' => 'Failed Count', 'unit' => '', 'type' => 'bar'],
        ['field' => 'authentication_success_rate', 'label' => 'Success Rate', 'unit' => '%', 'type' => 'line'],
        ['field' => 'authentication_failure_rate', 'label' => 'Failure Rate', 'unit' => '%', 'type' => 'line'],
    ],
    'chart_status_field' => 'login_attempt_type',
    'chart_notes' => [
        'Success rate tinggi menunjukkan skenario autentikasi valid berjalan baik.',
        'Failure rate penting untuk membaca percobaan login yang ditolak atau salah password.',
    ],
    'fields' => [
        ['name' => 'test_date', 'label' => 'Test Date', 'type' => 'date', 'required' => true],
        ['name' => 'user_role', 'label' => 'User Role', 'type' => 'select', 'options' => ['admin', 'operator', 'viewer']],
        ['name' => 'login_attempt_type', 'label' => 'Login Attempt Type', 'type' => 'select', 'options' => ['valid_user', 'invalid_user', 'wrong_password', 'unauthorized_access']],
        ['name' => 'authentication_method', 'label' => 'Authentication Method', 'default' => 'password'],
        ['name' => 'attempt_count', 'label' => 'Attempt Count', 'type' => 'number', 'integer' => true, 'default' => 1],
        ['name' => 'success_count', 'label' => 'Success Count', 'type' => 'number', 'integer' => true, 'default' => 1],
        ['name' => 'failed_count', 'label' => 'Failed Count', 'type' => 'number', 'integer' => true, 'default' => 0],
        ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
    ],
    'calculate' => function ($data) {
        $attempt = max(0, (int) ($data['attempt_count'] ?? 0));
        $success = max(0, (int) ($data['success_count'] ?? 0));
        $failed = max(0, (int) ($data['failed_count'] ?? 0));

        return [
            'authentication_success_rate' => $attempt > 0 ? round(($success / $attempt) * 100, 2) : 0,
            'authentication_failure_rate' => $attempt > 0 ? round(($failed / $attempt) * 100, 2) : 0,
        ];
    },
    'formulas' => [
        'Success Rate = Success Count / Attempt Count x 100',
        'Failure Rate = Failed Count / Attempt Count x 100',
    ],
    'columns' => [
        ['label' => 'Date', 'field' => 'test_date', 'format' => 'date'],
        ['label' => 'Role', 'field' => 'user_role'],
        ['label' => 'Attempt Type', 'field' => 'login_attempt_type'],
        ['label' => 'Method', 'field' => 'authentication_method'],
        ['label' => 'Attempts', 'field' => 'attempt_count'],
        ['label' => 'Success', 'field' => 'authentication_success_rate', 'decimals' => 2, 'suffix' => '%'],
        ['label' => 'Failure', 'field' => 'authentication_failure_rate', 'decimals' => 2, 'suffix' => '%'],
    ],
];

include __DIR__ . '/_test_page.php';
