<?php
$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_report_form'] ?? '') === 'generated_reports') {
    $action = $_POST['_report_action'] ?? 'create';
    $recordId = (int) ($_POST['id'] ?? 0);

    try {
        if ($action === 'delete') {
            if ($recordId <= 0) {
                throw new RuntimeException('ID laporan tidak valid.');
            }

            query('DELETE FROM generated_reports WHERE id = ?', [$recordId]);
            $success = 'Data laporan berhasil dihapus.';
        } else {
            $reportTitle = sanitize($_POST['report_title'] ?? '');
            $reportType = sanitize($_POST['report_type'] ?? '');
            $dateRangeStart = $_POST['date_range_start'] ?: null;
            $dateRangeEnd = $_POST['date_range_end'] ?: null;
            $locationFilter = sanitize($_POST['location_filter'] ?? '');
            $testTypeFilter = sanitize($_POST['test_type_filter'] ?? '');
            $content = sanitize($_POST['content'] ?? '');
            $fileType = sanitize($_POST['file_type'] ?? 'html');
            $filePath = 'public/reports/' . preg_replace('/[^a-z0-9_-]+/i', '-', strtolower($reportTitle)) . '.' . $fileType;

            if ($action === 'update') {
                if ($recordId <= 0) {
                    throw new RuntimeException('ID laporan tidak valid.');
                }

                query(
                    "UPDATE generated_reports SET
                        report_title = ?, report_type = ?, date_range_start = ?, date_range_end = ?,
                        location_filter = ?, test_type_filter = ?, content = ?, file_path = ?, file_type = ?
                    WHERE id = ?",
                    [
                        $reportTitle, $reportType, $dateRangeStart, $dateRangeEnd,
                        $locationFilter, $testTypeFilter, $content, $filePath, $fileType, $recordId
                    ]
                );
                $success = 'Data laporan berhasil diperbarui.';
            } else {
                $generatedBy = $_SESSION['user_id'] ?? null;
                query(
                    "INSERT INTO generated_reports (
                        report_title, report_type, date_range_start, date_range_end,
                        location_filter, test_type_filter, content, file_path, file_type, generated_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $reportTitle, $reportType, $dateRangeStart, $dateRangeEnd,
                        $locationFilter, $testTypeFilter, $content, $filePath, $fileType, $generatedBy
                    ]
                );
                $success = 'Data laporan berhasil disimpan.';
            }
        }
    } catch (PDOException $e) {
        $error = 'Gagal memproses laporan: ' . $e->getMessage();
    } catch (RuntimeException $e) {
        $error = $e->getMessage();
    }
}

$reports = fetchAll("SELECT gr.*, u.username
    FROM generated_reports gr
    LEFT JOIN users u ON u.id = gr.generated_by
    ORDER BY gr.created_at DESC
    LIMIT 50");

$reportTypeRows = fetchAll("SELECT COALESCE(NULLIF(report_type, ''), 'unknown') AS label, COUNT(*) AS total
    FROM generated_reports
    GROUP BY COALESCE(NULLIF(report_type, ''), 'unknown')
    ORDER BY total DESC");
$fileTypeRows = fetchAll("SELECT COALESCE(NULLIF(file_type, ''), 'unknown') AS label, COUNT(*) AS total
    FROM generated_reports
    GROUP BY COALESCE(NULLIF(file_type, ''), 'unknown')
    ORDER BY total DESC");
$reportTypeLabels = array_column($reportTypeRows, 'label');
$reportTypeValues = array_map('intval', array_column($reportTypeRows, 'total'));
$fileTypeLabels = array_column($fileTypeRows, 'label');
$fileTypeValues = array_map('intval', array_column($fileTypeRows, 'total'));
$reportMap = [];
foreach ($reports as $report) {
    $reportMap[(string) $report['id']] = $report;
}
?>

<div class="content-section">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h4 class="mb-1"><i class="fas fa-file-pdf"></i> Generated Reports</h4>
            <p class="text-muted mb-0">Catat metadata laporan berdasarkan periode, lokasi, dan jenis pengujian.</p>
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

    <div class="card">
        <div class="card-header bg-primary text-white">
            <h6 class="mb-0"><i class="fas fa-plus"></i> Buat Record Laporan</h6>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="_report_form" value="generated_reports">
                <input type="hidden" name="_report_action" value="create">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Report Title</label>
                        <input type="text" class="form-control" name="report_title" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Report Type</label>
                        <select class="form-select" name="report_type" required>
                            <option value="">Pilih</option>
                            <option value="summary">Summary</option>
                            <option value="detail">Detail</option>
                            <option value="analysis">Analysis</option>
                            <option value="chapter_4">Chapter 4</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Date Range Start</label>
                        <input type="date" class="form-control" name="date_range_start">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Date Range End</label>
                        <input type="date" class="form-control" name="date_range_end">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Location Filter</label>
                        <input type="text" class="form-control" name="location_filter">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Test Type Filter</label>
                        <input type="text" class="form-control" name="test_type_filter">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">File Type</label>
                        <select class="form-select" name="file_type" required>
                            <option value="html">HTML</option>
                            <option value="pdf">PDF</option>
                            <option value="csv">CSV</option>
                        </select>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">Content / Notes</label>
                        <textarea class="form-control" name="content" rows="4"></textarea>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan Laporan
                </button>
            </form>
        </div>
    </div>
</div>

<div class="content-section">
    <h4 class="mb-4"><i class="fas fa-chart-line"></i> Grafik Laporan</h4>
    <div class="row">
        <div class="col-xl-6 mb-4">
            <div class="card h-100">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Distribusi Report Type</h6>
                    <button type="button" class="btn btn-outline-primary btn-sm chart-download-btn" data-chart-target="reportTypeChart">
                        <i class="fas fa-download"></i> PNG
                    </button>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="reportTypeChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6 mb-4">
            <div class="card h-100">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Distribusi File Type</h6>
                    <button type="button" class="btn btn-outline-primary btn-sm chart-download-btn" data-chart-target="reportFileTypeChart">
                        <i class="fas fa-download"></i> PNG
                    </button>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="reportFileTypeChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="content-section">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <h4 class="mb-0"><i class="fas fa-table"></i> Daftar Laporan</h4>
        <div class="d-flex align-items-center flex-wrap gap-2">
            <a class="btn btn-success btn-sm" href="export_excel.php?table=generated_reports">
                <i class="fas fa-file-excel"></i> Export Excel 365
            </a>
            <span class="badge bg-secondary"><?php echo count($reports); ?> data</span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover data-table report-data-table" width="100%">
            <thead>
                <tr>
                    <th>Created</th>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Date Range</th>
                    <th>Filter</th>
                    <th>File</th>
                    <th>By</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reports as $report): ?>
                    <tr>
                        <td><?php echo formatDate($report['created_at']); ?></td>
                        <td><?php echo htmlspecialchars($report['report_title']); ?></td>
                        <td><?php echo htmlspecialchars($report['report_type']); ?></td>
                        <td><?php echo htmlspecialchars(($report['date_range_start'] ?: '-') . ' s/d ' . ($report['date_range_end'] ?: '-')); ?></td>
                        <td><?php echo htmlspecialchars(($report['location_filter'] ?: '-') . ' / ' . ($report['test_type_filter'] ?: '-')); ?></td>
                        <td><?php echo htmlspecialchars(($report['file_type'] ?: '-') . ' - ' . ($report['file_path'] ?: '-')); ?></td>
                        <td><?php echo htmlspecialchars($report['username'] ?: '-'); ?></td>
                        <td class="text-end text-nowrap table-action-buttons">
                            <button type="button" class="btn btn-outline-primary btn-sm report-view-btn" data-record-id="<?php echo (int) $report['id']; ?>" title="Lihat detail">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button type="button" class="btn btn-outline-warning btn-sm report-edit-btn" data-record-id="<?php echo (int) $report['id']; ?>" title="Edit laporan">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm report-delete-btn" data-record-id="<?php echo (int) $report['id']; ?>" title="Hapus laporan">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="reportViewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-eye"></i> Detail Laporan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <tbody id="reportViewBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="reportEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" id="reportEditForm">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Laporan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="_report_form" value="generated_reports">
                    <input type="hidden" name="_report_action" value="update">
                    <input type="hidden" name="id" id="reportEditId">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Report Title</label>
                            <input type="text" class="form-control" name="report_title" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Report Type</label>
                            <select class="form-select" name="report_type" required>
                                <option value="">Pilih</option>
                                <option value="summary">Summary</option>
                                <option value="detail">Detail</option>
                                <option value="analysis">Analysis</option>
                                <option value="chapter_4">Chapter 4</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date Range Start</label>
                            <input type="date" class="form-control" name="date_range_start">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date Range End</label>
                            <input type="date" class="form-control" name="date_range_end">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Location Filter</label>
                            <input type="text" class="form-control" name="location_filter">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Test Type Filter</label>
                            <input type="text" class="form-control" name="test_type_filter">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">File Type</label>
                            <select class="form-select" name="file_type" required>
                                <option value="html">HTML</option>
                                <option value="pdf">PDF</option>
                                <option value="csv">CSV</option>
                            </select>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Content / Notes</label>
                            <textarea class="form-control" name="content" rows="4"></textarea>
                        </div>
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

<div class="modal fade" id="reportDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-trash"></i> Hapus Laporan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="_report_form" value="generated_reports">
                    <input type="hidden" name="_report_action" value="delete">
                    <input type="hidden" name="id" id="reportDeleteId">
                    <p class="mb-0">Yakin ingin menghapus laporan <strong id="reportDeleteLabel"></strong>? Data yang sudah dihapus tidak bisa dikembalikan.</p>
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
    var reportRows = <?php echo json_encode($reportMap, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var reportLabels = {
        id: 'ID',
        report_title: 'Report Title',
        report_type: 'Report Type',
        date_range_start: 'Date Range Start',
        date_range_end: 'Date Range End',
        location_filter: 'Location Filter',
        test_type_filter: 'Test Type Filter',
        content: 'Content / Notes',
        file_path: 'File Path',
        file_type: 'File Type',
        username: 'Generated By',
        created_at: 'Created At'
    };

    function rowValue(value) {
        if (value === null || value === undefined || value === '') {
            return '-';
        }

        return $('<div>').text(value).html();
    }

    function getReportRow(id) {
        return reportRows[String(id)] || null;
    }

    $(document).on('click', '.report-view-btn', function() {
        var row = getReportRow($(this).data('record-id'));
        if (!row) return;

        var html = '';
        Object.keys(reportLabels).forEach(function(field) {
            if (!Object.prototype.hasOwnProperty.call(row, field)) return;
            html += '<tr><th style="width: 35%;">' + rowValue(reportLabels[field]) + '</th><td>' + rowValue(row[field]) + '</td></tr>';
        });

        $('#reportViewBody').html(html);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('reportViewModal')).show();
    });

    $(document).on('click', '.report-edit-btn', function() {
        var row = getReportRow($(this).data('record-id'));
        if (!row) return;

        var form = $('#reportEditForm');
        $('#reportEditId').val(row.id);
        [
            'report_title',
            'report_type',
            'date_range_start',
            'date_range_end',
            'location_filter',
            'test_type_filter',
            'file_type',
            'content'
        ].forEach(function(field) {
            form.find('[name="' + field + '"]').val(row[field] === null || row[field] === undefined ? '' : row[field]);
        });

        bootstrap.Modal.getOrCreateInstance(document.getElementById('reportEditModal')).show();
    });

    $(document).on('click', '.report-delete-btn', function() {
        var recordId = $(this).data('record-id');
        $('#reportDeleteId').val(recordId);
        $('#reportDeleteLabel').text('#' + recordId);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('reportDeleteModal')).show();
    });

    if ($.fn.DataTable) {
        $('.report-data-table').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/id.json'
            },
            order: [[0, 'desc']],
            columnDefs: [
                { targets: -1, orderable: false, searchable: false }
            ]
        });
    }
});
</script>

<script>
$(function() {
    new Chart(document.getElementById('reportTypeChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($reportTypeLabels); ?>,
            datasets: [{
                data: <?php echo json_encode($reportTypeValues); ?>,
                backgroundColor: ['#1e3c72', '#28a745', '#fd7e14', '#dc3545', '#6c757d']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
        }
    });

    new Chart(document.getElementById('reportFileTypeChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($fileTypeLabels); ?>,
            datasets: [{
                label: 'Jumlah',
                data: <?php echo json_encode($fileTypeValues); ?>,
                backgroundColor: '#1e3c72'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });
});
</script>
