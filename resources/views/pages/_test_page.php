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

        if ($type === 'number' && !empty($field['preserve_precision'])) {
            return sanitize($source[$name]);
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

        if ($value === null || $value === '') {
            return 'N/A';
        }

        if (($column['format'] ?? '') === 'date' && $value) {
            $value = formatDate($value);
        }

        if (($column['format'] ?? '') === 'status') {
            return getStatusBadge((string) $value);
        }

        if (is_numeric($value) && isset($column['decimals'])) {
            $value = number_format((float) $value, $column['decimals']);
        }

        return htmlspecialchars((string) $value) . ($column['suffix'] ?? '');
    }
}

if (!function_exists('testPageChartLabel')) {
    function testPageChartLabel($row, $labelField) {
        if (is_callable($labelField)) {
            return (string) call_user_func($labelField, $row);
        }

        if (is_array($labelField)) {
            $parts = [];
            foreach ($labelField as $field) {
                $value = $row[$field] ?? '';
                if ($value === null || $value === '') {
                    continue;
                }
                if (in_array($field, ['test_date', 'analysis_date', 'created_at', 'updated_at'], true)) {
                    $value = date('d M', strtotime($value));
                }
                $parts[] = (string) $value;
            }
            return $parts ? implode(' - ', $parts) : '-';
        }

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

if (!function_exists('testPageMetricUnit')) {
    function testPageMetricUnit($metric) {
        if (isset($metric['unit'])) {
            return (string) $metric['unit'];
        }

        $field = strtolower((string) ($metric['field'] ?? ''));
        $label = strtolower((string) ($metric['label'] ?? ''));
        $needle = $field . ' ' . $label;

        if (strpos($needle, 'percent') !== false || strpos($needle, 'rate') !== false || strpos($needle, '%') !== false || strpos($needle, 'usage') !== false) {
            return '%';
        }
        if (strpos($needle, 'rssi') !== false && strpos($needle, 'loss') === false) {
            return 'dBm';
        }
        if (strpos($needle, 'snr') !== false || strpos($needle, 'penetration_loss') !== false || strpos($needle, 'rssi_loss') !== false) {
            return 'dB';
        }
        if (strpos($needle, 'latency') !== false || strpos($needle, 'delay') !== false || strpos($needle, 'response_time') !== false || strpos($needle, 'command_time') !== false) {
            return 'ms';
        }
        if (strpos($needle, 'throughput') !== false || strpos($needle, 'bitrate') !== false) {
            return 'kbps';
        }
        if (strpos($needle, 'distance') !== false) {
            return 'm';
        }
        if (strpos($needle, 'fps') !== false) {
            return 'fps';
        }
        if (strpos($needle, 'power_w') !== false || strpos($needle, 'power') !== false) {
            return 'W';
        }
        if (strpos($needle, 'energy') !== false) {
            return 'Wh';
        }
        if (strpos($needle, 'runtime') !== false) {
            return 'h';
        }
        if (strpos($needle, 'voltage') !== false) {
            return 'V';
        }
        if (strpos($needle, 'current') !== false) {
            return 'A';
        }
        if (strpos($needle, 'temperature') !== false || strpos($needle, 'temp') !== false) {
            return 'C';
        }
        if (strpos($needle, 'key') !== false && strpos($needle, 'bit') !== false) {
            return 'bit';
        }

        return '';
    }
}

if (!function_exists('testPageAxisId')) {
    function testPageAxisId($unit) {
        $unit = (string) $unit;
        if ($unit === '') {
            return 'axis_value';
        }
        return 'axis_' . preg_replace('/[^A-Za-z0-9_]/', '_', strtolower($unit));
    }
}

if (!function_exists('testPageMetricSummary')) {
    function testPageMetricSummary($data) {
        $values = array_values(array_filter($data, function ($value) {
            return $value !== null && is_numeric($value);
        }));

        if (!$values) {
            return ['avg' => null, 'min' => null, 'max' => null, 'last' => null];
        }

        $last = end($values);

        return [
            'avg' => round(array_sum($values) / count($values), 2),
            'min' => round(min($values), 2),
            'max' => round(max($values), 2),
            'last' => round((float) $last, 2),
        ];
    }
}

if (!function_exists('testPageBuildCharts')) {
    function testPageBuildCharts($pageConfig, $rows) {
        $chartRows = array_reverse(array_slice($rows, 0, $pageConfig['chart_limit'] ?? 24));
        $labelField = $pageConfig['chart_label_fields'] ?? ($pageConfig['chart_label'] ?? null);

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

        $metricColumns = array_slice($metricColumns, 0, $pageConfig['chart_metric_limit'] ?? 4);
        $labels = [];
        $contextLabels = [];

        foreach ($chartRows as $row) {
            $labels[] = testPageChartLabel($row, $labelField);
            $contextParts = [];
            foreach (['test_date', 'location_name', 'node_id', 'device_id', 'target_node_id', 'command_type'] as $field) {
                if (!empty($row[$field])) {
                    $contextParts[] = in_array($field, ['test_date'], true) ? formatDate($row[$field]) : (string) $row[$field];
                }
            }
            $contextLabels[] = $contextParts ? implode(' | ', array_unique($contextParts)) : '';
        }

        $datasets = [];
        $summaryCards = [];
        $colors = ['#2563eb', '#16a34a', '#d97706', '#dc2626', '#7c3aed', '#0891b2'];

        foreach ($metricColumns as $index => $metric) {
            $data = [];
            $unit = testPageMetricUnit($metric);
            $axisId = $metric['axis'] ?? testPageAxisId($unit);
            $chartType = $metric['type'] ?? (in_array($unit, ['%', 'ms'], true) ? 'bar' : 'line');

            foreach ($chartRows as $row) {
                $field = $metric['field'];
                $value = array_key_exists($field, $row) ? $row[$field] : null;
                $data[] = is_numeric($value) ? (float) $value : null;
            }

            $summary = testPageMetricSummary($data);

            $datasets[] = [
                'label' => $metric['label'],
                'data' => $data,
                'type' => $chartType,
                'borderColor' => $colors[$index % count($colors)],
                'backgroundColor' => $colors[$index % count($colors)] . ($chartType === 'bar' ? 'B8' : '26'),
                'borderWidth' => $chartType === 'bar' ? 1 : 3,
                'tension' => 0.35,
                'fill' => $chartType === 'line' ? false : true,
                'pointRadius' => $chartType === 'line' ? 4 : 0,
                'pointHoverRadius' => 7,
                'yAxisID' => $axisId,
                'unit' => $unit,
                'field' => $metric['field'],
            ];

            $summaryCards[] = [
                'label' => $metric['label'],
                'unit' => $unit,
                'avg' => $summary['avg'],
                'min' => $summary['min'],
                'max' => $summary['max'],
                'last' => $summary['last'],
                'color' => $colors[$index % count($colors)],
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
            'contextLabels' => $contextLabels,
            'datasets' => $datasets,
            'summaryCards' => $summaryCards,
            'statusLabels' => $statusLabels,
            'statusValues' => $statusValues,
            'labelCaption' => $pageConfig['chart_label_caption'] ?? 'Urutan data terbaru',
            'notes' => $pageConfig['chart_notes'] ?? [],
        ];
    }
}

$success = null;
$error = null;
$canManageProject = canManageProject();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_test_page'] ?? '') === $pageConfig['table']) {
    $action = $_POST['_test_action'] ?? 'create';
    $recordId = (int) ($_POST['_test_record_id'] ?? 0);

    try {
        if (!$canManageProject) {
            throw new RuntimeException('Akses ditolak. Viewer hanya bisa melihat data.');
        }

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
        <?php if ($canManageProject): ?>
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
        <?php else: ?>
            <div class="col-xl-8 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0"><i class="fas fa-eye"></i> Mode Viewer</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-0">Form input, edit, dan hapus dinonaktifkan untuk role ini. Data tetap bisa dilihat melalui tabel, detail, grafik, dan export.</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

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
            <p class="text-muted mb-0">Visualisasi otomatis dari data terbaru yang tersimpan, lengkap dengan satuan, tren, dan ringkasan pembacaan.</p>
        </div>
    </div>

    <?php if (count($chartData['summaryCards']) > 0): ?>
        <div class="row test-chart-summary-grid">
            <?php foreach ($chartData['summaryCards'] as $card): ?>
                <div class="col-md-6 col-xl-3 mb-3">
                    <div class="test-chart-summary-card" style="--summary-color: <?php echo htmlspecialchars($card['color']); ?>;">
                        <span><?php echo htmlspecialchars($card['label']); ?></span>
                        <strong>
                            <?php echo $card['avg'] === null ? 'N/A' : number_format((float) $card['avg'], 2); ?>
                            <?php echo htmlspecialchars($card['unit']); ?>
                        </strong>
                        <small>
                            Avg | Min <?php echo $card['min'] === null ? 'N/A' : number_format((float) $card['min'], 2); ?>
                            | Max <?php echo $card['max'] === null ? 'N/A' : number_format((float) $card['max'], 2); ?>
                        </small>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="<?php echo count($chartData['statusLabels']) > 0 ? 'col-xl-8' : 'col-12'; ?> mb-4">
            <div class="card h-100">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="m-0 font-weight-bold text-primary">Tren Metrik Utama</h6>
                        <small class="text-muted"><?php echo htmlspecialchars($chartData['labelCaption']); ?></small>
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm chart-download-btn" data-chart-target="<?php echo $chartBaseId; ?>MetricChart">
                        <i class="fas fa-download"></i> PNG
                    </button>
                </div>
                <div class="card-body">
                    <div class="chart-container test-metric-chart-container">
                        <canvas id="<?php echo $chartBaseId; ?>MetricChart"></canvas>
                    </div>
                    <?php if (count($chartData['datasets']) === 0): ?>
                        <p class="text-muted text-center mb-0">Belum ada data angka untuk ditampilkan.</p>
                    <?php elseif (count($chartData['notes']) > 0): ?>
                        <div class="test-chart-notes mt-3">
                            <?php foreach ($chartData['notes'] as $note): ?>
                                <span><i class="fas fa-circle-info"></i> <?php echo htmlspecialchars($note); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if (count($chartData['statusLabels']) > 0): ?>
            <div class="col-xl-4 mb-4">
                <div class="card h-100">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="m-0 font-weight-bold text-primary">Distribusi Status</h6>
                            <small class="text-muted">Proporsi kategori hasil pengujian</small>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm chart-download-btn" data-chart-target="<?php echo $chartBaseId; ?>StatusChart">
                            <i class="fas fa-download"></i> PNG
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="chart-container test-status-chart-container">
                            <canvas id="<?php echo $chartBaseId; ?>StatusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="content-section">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <h4 class="mb-0"><i class="fas fa-table"></i> Data Terbaru</h4>
        <div class="d-flex align-items-center flex-wrap gap-2">
            <a class="btn btn-success btn-sm" href="export_excel.php?table=<?php echo urlencode($pageConfig['table']); ?>">
                <i class="fas fa-file-excel"></i> Export Excel 365
            </a>
            <span class="badge bg-secondary"><?php echo count($rows); ?> data</span>
        </div>
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
                            <?php if ($canManageProject): ?>
                                <button type="button" class="btn btn-outline-warning btn-sm test-edit-btn" data-record-id="<?php echo (int) $row['id']; ?>" title="Edit data">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-sm test-delete-btn" data-record-id="<?php echo (int) $row['id']; ?>" title="Hapus data">
                                    <i class="fas fa-trash"></i>
                                </button>
                            <?php endif; ?>
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

<?php if ($canManageProject): ?>
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
<?php endif; ?>

<script>
$(function() {
    var testPageRows = <?php echo json_encode($rowMap, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var testPageLabels = <?php echo json_encode($detailLabels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var viewModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('<?php echo $chartBaseId; ?>ViewModal'));
    <?php if ($canManageProject): ?>
    var editModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('<?php echo $chartBaseId; ?>EditModal'));
    var deleteModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('<?php echo $chartBaseId; ?>DeleteModal'));
    <?php endif; ?>

    function getRow(recordId) {
        return testPageRows[String(recordId)] || null;
    }

    function displayValue(value) {
        if (value === null || value === undefined || value === '') {
            return 'N/A';
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

    <?php if ($canManageProject): ?>
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
    <?php endif; ?>

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

    var chartContextLabels = <?php echo json_encode($chartData['contextLabels'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var metricCanvas = document.getElementById('<?php echo $chartBaseId; ?>MetricChart');
    var metricDatasets = <?php echo json_encode($chartData['datasets']); ?>;

    function formatMetricValue(value, unit) {
        if (value === null || value === undefined || isNaN(Number(value))) {
            return 'N/A';
        }

        var number = Number(value);
        var decimals = Math.abs(number) >= 1000 || unit === 'bit' ? 0 : 2;
        return number.toLocaleString('id-ID', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        }) + (unit ? ' ' + unit : '');
    }

    function statusColor(label) {
        var key = String(label || '').toLowerCase();
        var colors = {
            success: '#16a34a',
            good: '#16a34a',
            secure: '#16a34a',
            valid: '#16a34a',
            unreadable: '#16a34a',
            normal: '#0891b2',
            moderate: '#0891b2',
            medium: '#d97706',
            warning: '#d97706',
            low: '#65a30d',
            high: '#dc2626',
            fail: '#dc2626',
            failed: '#dc2626',
            poor: '#dc2626',
            insecure: '#dc2626',
            invalid: '#dc2626',
            readable: '#dc2626',
            timeout: '#dc2626'
        };

        return colors[key] || '#2563eb';
    }

    var testEmptyChartPlugin = {
        id: 'testEmptyChartPlugin',
        afterDraw: function(chart, args, options) {
            var hasData = chart.data.datasets.some(function(dataset) {
                return (dataset.data || []).some(function(value) {
                    return value !== null && value !== undefined && !isNaN(Number(value));
                });
            });

            if (hasData) return;

            var ctx = chart.ctx;
            var area = chart.chartArea;
            ctx.save();
            ctx.fillStyle = '#64748b';
            ctx.font = '600 14px Segoe UI, sans-serif';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(options.message || 'Belum ada data grafik', (area.left + area.right) / 2, (area.top + area.bottom) / 2);
            ctx.restore();
        }
    };

    function buildMetricScales(datasets) {
        var usedAxes = [];
        datasets.forEach(function(dataset) {
            if (usedAxes.indexOf(dataset.yAxisID) === -1) {
                usedAxes.push(dataset.yAxisID);
            }
        });

        var scales = {
            x: {
                ticks: {
                    maxRotation: 0,
                    autoSkip: true,
                    callback: function(value) {
                        var label = this.getLabelForValue(value);
                        return label && label.length > 18 ? label.substring(0, 17) + '...' : label;
                    }
                },
                grid: {
                    display: false
                }
            }
        };

        usedAxes.forEach(function(axisId, index) {
            var sample = datasets.find(function(dataset) { return dataset.yAxisID === axisId; }) || {};
            var unit = sample.unit || '';
            scales[axisId] = {
                type: 'linear',
                display: true,
                position: index % 2 === 0 ? 'left' : 'right',
                beginAtZero: unit !== 'dBm',
                suggestedMin: unit === 'dBm' ? -100 : undefined,
                suggestedMax: unit === '%' ? 100 : (unit === 'score' ? 5 : undefined),
                min: unit === '%' ? 0 : undefined,
                max: unit === '%' ? 100 : undefined,
                title: {
                    display: true,
                    text: unit ? 'Nilai (' + unit + ')' : 'Nilai'
                },
                ticks: {
                    callback: function(value) {
                        return unit ? value + ' ' + unit : value;
                    }
                },
                grid: {
                    color: index === 0 ? 'rgba(15, 23, 42, 0.1)' : 'rgba(15, 23, 42, 0.04)',
                    drawOnChartArea: index === 0
                }
            };
        });

        return scales;
    }

    if (metricCanvas && metricDatasets.length > 0) {
        new Chart(metricCanvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chartData['labels'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
                datasets: metricDatasets
            },
            plugins: [testEmptyChartPlugin],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    testEmptyChartPlugin: {
                        message: 'Belum ada nilai metrik'
                    },
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 18
                        }
                    },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleMarginBottom: 8,
                        padding: 12,
                        callbacks: {
                            afterTitle: function(items) {
                                if (!items.length) return '';
                                return chartContextLabels[items[0].dataIndex] || '';
                            },
                            label: function(context) {
                                return context.dataset.label + ': ' + formatMetricValue(context.raw, context.dataset.unit || '');
                            }
                        }
                    }
                },
                scales: buildMetricScales(metricDatasets)
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
                    backgroundColor: <?php echo json_encode(array_map(function ($label) { return null; }, $chartData['statusLabels'])); ?>.map(function(empty, index) {
                        return statusColor(<?php echo json_encode($chartData['statusLabels']); ?>[index]);
                    }),
                    borderColor: '#ffffff',
                    borderWidth: 3
                }]
            },
            plugins: window.ChartDataLabels ? [ChartDataLabels, testEmptyChartPlugin] : [testEmptyChartPlugin],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '58%',
                plugins: {
                    testEmptyChartPlugin: {
                        message: 'Belum ada status'
                    },
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 16
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                var total = context.dataset.data.reduce(function(sum, value) {
                                    return sum + Number(value || 0);
                                }, 0);
                                var value = Number(context.raw || 0);
                                var percent = total > 0 ? ((value / total) * 100).toFixed(1) : '0.0';
                                return context.label + ': ' + value + ' data (' + percent + '%)';
                            }
                        }
                    },
                    datalabels: {
                        color: '#0f172a',
                        formatter: function(value, context) {
                            var total = context.dataset.data.reduce(function(sum, item) {
                                return sum + Number(item || 0);
                            }, 0);
                            if (!value || total === 0) return '';
                            return ((value / total) * 100).toFixed(0) + '%';
                        },
                        font: {
                            weight: '700',
                            size: 13
                        }
                    }
                }
            }
        });
    }
    <?php endif; ?>
});
</script>

<style>
.test-chart-summary-grid {
    margin-bottom: 8px;
}
.test-chart-summary-card {
    height: 100%;
    min-height: 108px;
    padding: 14px 16px;
    border: 1px solid #dbe4ef;
    border-left: 5px solid var(--summary-color, #2563eb);
    border-radius: 8px;
    background: #ffffff;
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05);
}
.test-chart-summary-card span,
.test-chart-summary-card small {
    display: block;
    color: #64748b;
    font-size: 12px;
    font-weight: 600;
    line-height: 1.35;
}
.test-chart-summary-card strong {
    display: block;
    margin: 7px 0 5px;
    color: #0f172a;
    font-size: 22px;
    font-weight: 750;
    line-height: 1.1;
}
.test-metric-chart-container {
    height: 390px;
    min-height: 390px;
}
.test-status-chart-container {
    height: 390px;
    min-height: 390px;
}
.test-chart-notes {
    display: grid;
    gap: 8px;
    color: #475569;
    font-size: 13px;
    line-height: 1.45;
}
.test-chart-notes span {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    padding: 9px 10px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: #f8fafc;
}
.test-chart-notes i {
    margin-top: 2px;
    color: #2563eb;
}
@media (max-width: 767.98px) {
    .test-metric-chart-container,
    .test-status-chart-container {
        height: 340px;
        min-height: 340px;
    }
    .test-chart-summary-card strong {
        font-size: 20px;
    }
}
</style>
