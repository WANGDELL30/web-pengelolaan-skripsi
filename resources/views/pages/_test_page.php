<?php
if (!function_exists('sanitize')) {
    require_once __DIR__ . '/../../../app/Helpers/functions.php';
}

if (!isset($pageConfig)) {
    return;
}

if (!function_exists('testPageInputValue')) {
    function testPageInputValue($field, $source) {
        $name = $field['name'];
        $type = $field['type'] ?? 'text';

        if (!array_key_exists($name, $source) || $source[$name] === '') {
            return $field['default'] ?? null;
        }

        if ($type === 'number') {
            return isset($field['integer']) && $field['integer'] ? (int) $source[$name] : (float) $source[$name];
        }

        return sanitize($source[$name]);
    }
}

if (!function_exists('testPageBuildData')) {
    function testPageBuildData($pageConfig, $source) {
        $inputData = [];
        $saveData = [];

        foreach ($pageConfig['fields'] as $field) {
            $value = testPageInputValue($field, $source);
            $inputData[$field['name']] = $value;

            if (empty($field['virtual'])) {
                $saveData[$field['name']] = $value;
            }
        }

        if (isset($pageConfig['calculate']) && is_callable($pageConfig['calculate'])) {
            $saveData = array_merge($saveData, call_user_func($pageConfig['calculate'], $inputData));
        }

        return $saveData;
    }
}

if (!function_exists('testPageFormatValue')) {
    function testPageFormatValue($row, $column) {
        if (isset($column['value']) && is_callable($column['value'])) {
            return call_user_func($column['value'], $row);
        }

        $value = $row[$column['field']] ?? '';

        if (($column['format'] ?? '') === 'date' && $value) {
            $value = formatDate($value);
        }

        if (($column['format'] ?? '') === 'status') {
            return getStatusBadge((string) $value);
        }

        if ($value === null || $value === '') {
            return '-';
        }

        if (is_numeric($value) && isset($column['decimals'])) {
            $value = number_format((float) $value, $column['decimals']);
        }

        return htmlspecialchars((string) $value) . ($column['suffix'] ?? '');
    }
}

if (!function_exists('testPageChartLabel')) {
    function testPageChartLabel($row, $labelField) {
        $value = $row[$labelField] ?? '';

        if (!$value) {
            return '-';
        }

        if (in_array($labelField, ['test_date', 'analysis_date', 'created_at', 'updated_at'], true)) {
            return date('d M', strtotime($value));
        }

        return (string) $value;
    }
}

if (!function_exists('testPageBuildCharts')) {
    function testPageBuildCharts($pageConfig, $rows) {
        $chartRows = array_reverse(array_slice($rows, 0, 20));
        $labelField = $pageConfig['chart_label'] ?? null;

        if (!$labelField) {
            foreach (['test_date', 'analysis_date', 'created_at'] as $candidate) {
                if (isset($chartRows[0][$candidate])) {
                    $labelField = $candidate;
                    break;
                }
            }
        }

        if (!$labelField) {
            $labelField = 'id';
        }

        $metricColumns = $pageConfig['chart_metrics'] ?? [];

        if (!$metricColumns) {
            foreach ($pageConfig['columns'] as $column) {
                if (empty($column['field']) || ($column['format'] ?? '') === 'status') {
                    continue;
                }

                $field = $column['field'];
                $hasNumericValue = false;

                foreach ($chartRows as $row) {
                    if (isset($row[$field]) && is_numeric($row[$field])) {
                        $hasNumericValue = true;
                        break;
                    }
                }

                if ($hasNumericValue) {
                    $metricColumns[] = [
                        'field' => $field,
                        'label' => $column['label'] ?? $field,
                    ];
                }
            }
        }

        $metricColumns = array_slice($metricColumns, 0, 4);
        $labels = [];

        foreach ($chartRows as $row) {
            $labels[] = testPageChartLabel($row, $labelField);
        }

        $datasets = [];
        $colors = ['#1e3c72', '#28a745', '#fd7e14', '#dc3545'];

        foreach ($metricColumns as $index => $metric) {
            $data = [];

            foreach ($chartRows as $row) {
                $value = $row[$metric['field']] ?? 0;
                $data[] = is_numeric($value) ? (float) $value : null;
            }

            $datasets[] = [
                'label' => $metric['label'],
                'data' => $data,
                'borderColor' => $colors[$index % count($colors)],
                'backgroundColor' => $colors[$index % count($colors)] . '33',
                'borderWidth' => 2,
                'tension' => 0.3,
                'fill' => false,
            ];
        }

        $statusField = $pageConfig['chart_status_field'] ?? null;

        if (!$statusField) {
            foreach ($pageConfig['columns'] as $column) {
                if (($column['format'] ?? '') === 'status' && !empty($column['field'])) {
                    $statusField = $column['field'];
                    break;
                }
            }
        }

        $statusLabels = [];
        $statusValues = [];

        if ($statusField) {
            $counts = [];
            foreach ($rows as $row) {
                $status = $row[$statusField] ?? null;
                if ($status === null || $status === '') {
                    continue;
                }
                $counts[$status] = ($counts[$status] ?? 0) + 1;
            }
            $statusLabels = array_keys($counts);
            $statusValues = array_values($counts);
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets,
            'statusLabels' => $statusLabels,
            'statusValues' => $statusValues,
        ];
    }
}

$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_test_page'] ?? '') === $pageConfig['table']) {
    $action = $_POST['_test_action'] ?? 'create';
    $recordId = (int) ($_POST['_test_record_id'] ?? 0);

    try {
        if ($action === 'delete') {
            if ($recordId <= 0) {
                throw new RuntimeException('ID data tidak valid.');
            }

            query("DELETE FROM {$pageConfig['table']} WHERE id = ?", [$recordId]);
            $success = 'Data berhasil dihapus.';
        } elseif ($action === 'update') {
            if ($recordId <= 0) {
                throw new RuntimeException('ID data tidak valid.');
            }

            $updateData = testPageBuildData($pageConfig, $_POST);
            $columns = array_keys($updateData);
            $assignments = implode(', ', array_map(function ($column) {
                return "{$column} = ?";
            }, $columns));
            $params = array_values($updateData);
            $params[] = $recordId;

            query("UPDATE {$pageConfig['table']} SET {$assignments} WHERE id = ?", $params);
            $success = 'Data berhasil diperbarui.';
        } else {
            $insertData = testPageBuildData($pageConfig, $_POST);
            $columns = array_keys($insertData);
            $placeholders = implode(', ', array_fill(0, count($columns), '?'));
            $sql = "INSERT INTO {$pageConfig['table']} (" . implode(', ', $columns) . ") VALUES ($placeholders)";
            query($sql, array_values($insertData));
            $success = 'Data berhasil disimpan.';
        }
    } catch (PDOException $e) {
        $error = 'Gagal memproses data: ' . $e->getMessage();
    } catch (RuntimeException $e) {
        $error = $e->getMessage();
    }
}

$rows = fetchAll("SELECT * FROM {$pageConfig['table']} ORDER BY {$pageConfig['order']} LIMIT 50");
$chartData = testPageBuildCharts($pageConfig, $rows);
$chartBaseId = preg_replace('/[^A-Za-z0-9_]/', '', $pageConfig['table']);
$rowMap = [];
foreach ($rows as $row) {
    $rowMap[(string) $row['id']] = $row;
}

$detailLabels = ['id' => 'ID'];
foreach ($pageConfig['fields'] as $field) {
    $detailLabels[$field['name']] = $field['label'];
}
foreach ($pageConfig['columns'] as $column) {
    if (!empty($column['field']) && !isset($detailLabels[$column['field']])) {
        $detailLabels[$column['field']] = $column['label'] ?? $column['field'];
    }
}
$detailLabels['created_at'] = 'Created At';
$detailLabels['updated_at'] = 'Updated At';
?>

<div class="content-section">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h4 class="mb-1"><i class="<?php echo $pageConfig['icon']; ?>"></i> <?php echo htmlspecialchars($pageConfig['title']); ?></h4>
            <p class="text-muted mb-0"><?php echo htmlspecialchars($pageConfig['description']); ?></p>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-xl-8 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fas fa-edit"></i> Input Data</h6>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="_test_page" value="<?php echo htmlspecialchars($pageConfig['table']); ?>">
                        <input type="hidden" name="_test_action" value="create">
                        <div class="row">
                            <?php foreach ($pageConfig['fields'] as $field): ?>
                                <?php
                                $type = $field['type'] ?? 'text';
                                $name = $field['name'];
                                $value = $_POST[$name] ?? ($field['default'] ?? '');
                                $col = $field['col'] ?? ($type === 'textarea' ? 'col-12' : 'col-md-6');
                                ?>
                                <div class="<?php echo $col; ?> mb-3">
                                    <label class="form-label"><?php echo htmlspecialchars($field['label']); ?></label>

                                    <?php if ($type === 'select'): ?>
                                        <select class="form-select" name="<?php echo htmlspecialchars($name); ?>" <?php echo !empty($field['required']) ? 'required' : ''; ?>>
                                            <option value="">Pilih</option>
                                            <?php foreach ($field['options'] as $optionValue => $optionLabel): ?>
                                                <?php if (is_int($optionValue)) $optionValue = $optionLabel; ?>
                                                <option value="<?php echo htmlspecialchars($optionValue); ?>" <?php echo (string) $value === (string) $optionValue ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($optionLabel); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php elseif ($type === 'textarea'): ?>
                                        <textarea class="form-control" name="<?php echo htmlspecialchars($name); ?>" rows="<?php echo $field['rows'] ?? 3; ?>"><?php echo htmlspecialchars((string) $value); ?></textarea>
                                    <?php else: ?>
                                        <input
                                            type="<?php echo htmlspecialchars($type); ?>"
                                            class="form-control"
                                            name="<?php echo htmlspecialchars($name); ?>"
                                            value="<?php echo htmlspecialchars((string) $value); ?>"
                                            <?php echo isset($field['step']) ? 'step="' . htmlspecialchars((string) $field['step']) . '"' : ''; ?>
                                            <?php echo isset($field['min']) ? 'min="' . htmlspecialchars((string) $field['min']) . '"' : ''; ?>
                                            <?php echo isset($field['max']) ? 'max="' . htmlspecialchars((string) $field['max']) . '"' : ''; ?>
                                            <?php echo !empty($field['required']) ? 'required' : ''; ?>
                                        >
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan Data
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-4 mb-4">
            <div class="card h-100">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-calculator"></i> Kalkulasi Otomatis</h6>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <?php foreach ($pageConfig['formulas'] as $formula): ?>
                            <li><?php echo htmlspecialchars($formula); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="content-section">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h4 class="mb-1"><i class="fas fa-chart-line"></i> Grafik Hasil Olahan</h4>
            <p class="text-muted mb-0">Visualisasi otomatis dari data terbaru yang tersimpan.</p>
        </div>
    </div>

    <div class="row">
        <div class="<?php echo count($chartData['statusLabels']) > 0 ? 'col-xl-8' : 'col-12'; ?> mb-4">
            <div class="card h-100">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Tren Metrik Utama</h6>
                    <button type="button" class="btn btn-outline-primary btn-sm chart-download-btn" data-chart-target="<?php echo $chartBaseId; ?>MetricChart">
                        <i class="fas fa-download"></i> PNG
                    </button>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="<?php echo $chartBaseId; ?>MetricChart"></canvas>
                    </div>
                    <?php if (count($chartData['datasets']) === 0): ?>
                        <p class="text-muted text-center mb-0">Belum ada data angka untuk ditampilkan.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if (count($chartData['statusLabels']) > 0): ?>
            <div class="col-xl-4 mb-4">
                <div class="card h-100">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">Distribusi Status</h6>
                        <button type="button" class="btn btn-outline-primary btn-sm chart-download-btn" data-chart-target="<?php echo $chartBaseId; ?>StatusChart">
                            <i class="fas fa-download"></i> PNG
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="<?php echo $chartBaseId; ?>StatusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="content-section">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0"><i class="fas fa-table"></i> Data Terbaru</h4>
        <span class="badge bg-secondary"><?php echo count($rows); ?> data</span>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover data-table test-data-table" width="100%">
            <thead>
                <tr>
                    <?php foreach ($pageConfig['columns'] as $column): ?>
                        <th><?php echo htmlspecialchars($column['label']); ?></th>
                    <?php endforeach; ?>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <?php foreach ($pageConfig['columns'] as $column): ?>
                            <td><?php echo testPageFormatValue($row, $column); ?></td>
                        <?php endforeach; ?>
                        <td class="text-end text-nowrap table-action-buttons">
                            <button type="button" class="btn btn-outline-primary btn-sm test-view-btn" data-record-id="<?php echo (int) $row['id']; ?>" title="Lihat detail">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button type="button" class="btn btn-outline-warning btn-sm test-edit-btn" data-record-id="<?php echo (int) $row['id']; ?>" title="Edit data">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm test-delete-btn" data-record-id="<?php echo (int) $row['id']; ?>" title="Hapus data">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="<?php echo $chartBaseId; ?>ViewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="<?php echo $pageConfig['icon']; ?>"></i> Detail Data</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <tbody id="<?php echo $chartBaseId; ?>ViewBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="<?php echo $chartBaseId; ?>EditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" id="<?php echo $chartBaseId; ?>EditForm">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Edit <?php echo htmlspecialchars($pageConfig['title']); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="_test_page" value="<?php echo htmlspecialchars($pageConfig['table']); ?>">
                    <input type="hidden" name="_test_action" value="update">
                    <input type="hidden" name="_test_record_id" id="<?php echo $chartBaseId; ?>EditRecordId">

                    <div class="row">
                        <?php foreach ($pageConfig['fields'] as $field): ?>
                            <?php
                            $type = $field['type'] ?? 'text';
                            $name = $field['name'];
                            $col = $field['col'] ?? ($type === 'textarea' ? 'col-12' : 'col-md-6');
                            ?>
                            <div class="<?php echo $col; ?> mb-3">
                                <label class="form-label"><?php echo htmlspecialchars($field['label']); ?></label>

                                <?php if ($type === 'select'): ?>
                                    <select class="form-select test-edit-field" name="<?php echo htmlspecialchars($name); ?>" data-field="<?php echo htmlspecialchars($name); ?>" <?php echo !empty($field['required']) ? 'required' : ''; ?>>
                                        <option value="">Pilih</option>
                                        <?php foreach ($field['options'] as $optionValue => $optionLabel): ?>
                                            <?php if (is_int($optionValue)) $optionValue = $optionLabel; ?>
                                            <option value="<?php echo htmlspecialchars($optionValue); ?>"><?php echo htmlspecialchars($optionLabel); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php elseif ($type === 'textarea'): ?>
                                    <textarea class="form-control test-edit-field" name="<?php echo htmlspecialchars($name); ?>" data-field="<?php echo htmlspecialchars($name); ?>" rows="<?php echo $field['rows'] ?? 3; ?>"></textarea>
                                <?php else: ?>
                                    <input
                                        type="<?php echo htmlspecialchars($type); ?>"
                                        class="form-control test-edit-field"
                                        name="<?php echo htmlspecialchars($name); ?>"
                                        data-field="<?php echo htmlspecialchars($name); ?>"
                                        <?php echo isset($field['step']) ? 'step="' . htmlspecialchars((string) $field['step']) . '"' : ''; ?>
                                        <?php echo isset($field['min']) ? 'min="' . htmlspecialchars((string) $field['min']) . '"' : ''; ?>
                                        <?php echo isset($field['max']) ? 'max="' . htmlspecialchars((string) $field['max']) . '"' : ''; ?>
                                        <?php echo !empty($field['required']) ? 'required' : ''; ?>
                                    >
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="<?php echo $chartBaseId; ?>DeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-trash"></i> Hapus Data</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="_test_page" value="<?php echo htmlspecialchars($pageConfig['table']); ?>">
                    <input type="hidden" name="_test_action" value="delete">
                    <input type="hidden" name="_test_record_id" id="<?php echo $chartBaseId; ?>DeleteRecordId">
                    <p class="mb-0">Yakin ingin menghapus data <strong id="<?php echo $chartBaseId; ?>DeleteRecordLabel"></strong>? Data yang sudah dihapus tidak bisa dikembalikan.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Hapus
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(function() {
    var testPageRows = <?php echo json_encode($rowMap, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var testPageLabels = <?php echo json_encode($detailLabels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var viewModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('<?php echo $chartBaseId; ?>ViewModal'));
    var editModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('<?php echo $chartBaseId; ?>EditModal'));
    var deleteModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('<?php echo $chartBaseId; ?>DeleteModal'));

    function getRow(recordId) {
        return testPageRows[String(recordId)] || null;
    }

    function displayValue(value) {
        if (value === null || value === undefined || value === '') {
            return '-';
        }
        return $('<div>').text(value).html();
    }

    $(document).on('click', '.test-view-btn', function() {
        var row = getRow($(this).data('record-id'));
        if (!row) return;

        var html = '';
        Object.keys(testPageLabels).forEach(function(field) {
            if (!Object.prototype.hasOwnProperty.call(row, field)) return;
            html += '<tr><th style="width: 35%;">' + displayValue(testPageLabels[field]) + '</th><td>' + displayValue(row[field]) + '</td></tr>';
        });

        $('#<?php echo $chartBaseId; ?>ViewBody').html(html);
        viewModal.show();
    });

    $(document).on('click', '.test-edit-btn', function() {
        var row = getRow($(this).data('record-id'));
        if (!row) return;

        $('#<?php echo $chartBaseId; ?>EditRecordId').val(row.id);
        $('#<?php echo $chartBaseId; ?>EditForm .test-edit-field').each(function() {
            var field = $(this).data('field');
            $(this).val(row[field] === null || row[field] === undefined ? '' : row[field]);
        });

        editModal.show();
    });

    $(document).on('click', '.test-delete-btn', function() {
        var recordId = $(this).data('record-id');
        $('#<?php echo $chartBaseId; ?>DeleteRecordId').val(recordId);
        $('#<?php echo $chartBaseId; ?>DeleteRecordLabel').text('#' + recordId);
        deleteModal.show();
    });

    if ($.fn.DataTable) {
        $('.test-data-table').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/id.json'
            },
            order: [[0, 'desc']],
            columnDefs: [
                { targets: -1, orderable: false, searchable: false }
            ]
        });
    }

    var metricCanvas = document.getElementById('<?php echo $chartBaseId; ?>MetricChart');
    var metricDatasets = <?php echo json_encode($chartData['datasets']); ?>;

    if (metricCanvas && metricDatasets.length > 0) {
        new Chart(metricCanvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chartData['labels']); ?>,
                datasets: metricDatasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false
                    }
                }
            }
        });
    }

    <?php if (count($chartData['statusLabels']) > 0): ?>
    var statusCanvas = document.getElementById('<?php echo $chartBaseId; ?>StatusChart');
    if (statusCanvas) {
        new Chart(statusCanvas.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($chartData['statusLabels']); ?>,
                datasets: [{
                    data: <?php echo json_encode($chartData['statusValues']); ?>,
                    backgroundColor: ['#28a745', '#fd7e14', '#dc3545', '#1e3c72', '#6c757d', '#17a2b8'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
    <?php endif; ?>
});
</script>
