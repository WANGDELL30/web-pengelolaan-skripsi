<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Helpers/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isLoggedIn()) {
    http_response_code(403);
    echo 'Anda harus login untuk export data.';
    exit;
}

$exportTables = [
    'connectivity_tests' => ['title' => 'Connectivity Tests', 'order' => 'test_date DESC, created_at DESC, id DESC'],
    'range_tests' => ['title' => 'Range Tests', 'order' => 'test_date DESC, created_at DESC, id DESC'],
    'signal_penetration_tests' => ['title' => 'Signal Penetration Tests', 'order' => 'test_date DESC, created_at DESC, id DESC'],
    'latency_tests' => ['title' => 'Latency Tests', 'order' => 'test_date DESC, created_at DESC, id DESC'],
    'throughput_tests' => ['title' => 'Throughput Tests', 'order' => 'test_date DESC, created_at DESC, id DESC'],
    'interference_tests' => ['title' => 'Interference Tests', 'order' => 'test_date DESC, created_at DESC, id DESC'],
    'slave_camera_tests' => ['title' => 'Camera Tests', 'order' => 'test_date DESC, created_at DESC, id DESC'],
    'power_consumption_tests' => ['title' => 'Power Consumption Tests', 'order' => 'test_date DESC, created_at DESC, id DESC'],
    'command_execution_tests' => ['title' => 'Command Execution Tests', 'order' => 'test_date DESC, created_at DESC, id DESC'],
    'text_message_logs' => ['title' => 'Text Message Delivery Logs', 'order' => 'sent_at DESC, id DESC'],
    'text_message_inbox_logs' => ['title' => 'Text Message Inbox Logs', 'order' => 'received_at DESC, id DESC'],
    'response_time_tests' => ['title' => 'Response Time Tests', 'order' => 'test_date DESC, created_at DESC, id DESC'],
    'encryption_tests' => ['title' => 'Encryption Tests', 'order' => 'test_date DESC, created_at DESC, id DESC'],
    'generated_reports' => ['title' => 'Generated Reports', 'order' => 'created_at DESC, id DESC'],
];

$table = $_GET['table'] ?? '';

if ($table === 'analysis_key_metrics') {
    try {
        $columns = ['metric', 'value', 'interpretation'];
        $headers = ['Metric', 'Value', 'Interpretation'];
        $rows = exportAnalysisRows();
        outputXlsx('Analysis Key Metrics', $headers, $columns, $rows);
    } catch (Throwable $e) {
        http_response_code(500);
        echo 'Gagal export data: ' . htmlspecialchars($e->getMessage());
        exit;
    }
}

if (!isset($exportTables[$table])) {
    http_response_code(400);
    echo 'Tabel export tidak valid.';
    exit;
}

try {
    $config = $exportTables[$table];
    $dbColumns = fetchAll('SHOW COLUMNS FROM ' . sqlIdentifier($table));
    $columns = array_map(function ($column) {
        return $column['Field'];
    }, $dbColumns);

    if (!$columns) {
        throw new RuntimeException('Kolom tabel tidak ditemukan.');
    }

    $selectColumns = implode(', ', array_map('sqlIdentifier', $columns));
    $rows = fetchAll('SELECT ' . $selectColumns . ' FROM ' . sqlIdentifier($table) . ' ORDER BY ' . $config['order']);
    $headers = array_map('exportHeaderLabel', $columns);

    outputXlsx($config['title'], $headers, $columns, $rows);
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Gagal export data: ' . htmlspecialchars($e->getMessage());
    exit;
}

function exportAnalysisRows() {
    return [
        [
            'metric' => 'Average Latency',
            'value' => exportAnalysisValue('SELECT AVG(latency_ms) AS value FROM latency_tests WHERE latency_ms IS NOT NULL', ' ms'),
            'interpretation' => 'Semakin rendah semakin baik untuk kontrol real-time.',
        ],
        [
            'metric' => 'Average Throughput',
            'value' => exportAnalysisValue('SELECT AVG(throughput_kbps) AS value FROM throughput_tests WHERE throughput_kbps IS NOT NULL', ' kbps'),
            'interpretation' => 'Menunjukkan kapasitas transfer data aktual.',
        ],
        [
            'metric' => 'Average RSSI',
            'value' => exportAnalysisValue('SELECT AVG(rssi_dbm) AS value FROM connectivity_tests WHERE rssi_dbm IS NOT NULL', ' dBm'),
            'interpretation' => 'Nilai mendekati 0 berarti sinyal lebih kuat.',
        ],
        [
            'metric' => 'Average Power',
            'value' => exportAnalysisValue('SELECT AVG(power_w) AS value FROM power_consumption_tests WHERE power_w IS NOT NULL', ' W'),
            'interpretation' => 'Dipakai untuk evaluasi konsumsi daya perangkat.',
        ],
        [
            'metric' => 'Secure Encryption',
            'value' => (int) exportAnalysisMetric("SELECT COUNT(*) AS value FROM encryption_tests WHERE encryption_status = 'secure'") . ' record',
            'interpretation' => 'Jumlah pengujian enkripsi yang lolos sniffing dan integrity check.',
        ],
    ];
}

function exportAnalysisValue($sql, $suffix) {
    $value = exportAnalysisMetric($sql);

    if ($value === null || $value === '') {
        return 'N/A';
    }

    return number_format((float) $value, 2) . $suffix;
}

function exportAnalysisMetric($sql) {
    try {
        $row = fetchOne($sql);
        return $row['value'] ?? null;
    } catch (PDOException $e) {
        return null;
    }
}

function sqlIdentifier($identifier) {
    return '`' . str_replace('`', '``', $identifier) . '`';
}

function exportHeaderLabel($column) {
    return ucwords(str_replace('_', ' ', $column));
}

function outputXlsx($title, array $headers, array $columns, array $rows) {
    $safeTitle = preg_replace('/[^A-Za-z0-9_-]+/', '_', strtolower($title));
    $safeTitle = trim($safeTitle, '_') ?: 'export';
    $filename = $safeTitle . '_' . date('Ymd_His') . '.xlsx';
    $sheetName = substr(preg_replace('/[\\\\\\/?*\\[\\]:]+/', ' ', $title), 0, 31) ?: 'Data';
    $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');

    if ($tempFile === false) {
        throw new RuntimeException('Tidak bisa membuat file sementara untuk export.');
    }

    $files = xlsxPackageFiles($sheetName, $headers, $columns, $rows);

    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($tempFile, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Tidak bisa membuat paket file Excel.');
        }

        foreach ($files as $path => $contents) {
            $zip->addFromString($path, $contents);
        }

        $zip->close();
    } else {
        xlsxWriteZipFallback($tempFile, $files);
    }

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($tempFile));
    header('Cache-Control: max-age=0');
    readfile($tempFile);
    unlink($tempFile);
    exit;
}

function xlsxPackageFiles($sheetName, array $headers, array $columns, array $rows) {
    return [
        '[Content_Types].xml' => xlsxContentTypesXml(),
        '_rels/.rels' => xlsxRootRelsXml(),
        'xl/workbook.xml' => xlsxWorkbookXml($sheetName),
        'xl/_rels/workbook.xml.rels' => xlsxWorkbookRelsXml(),
        'xl/styles.xml' => xlsxStylesXml(),
        'xl/worksheets/sheet1.xml' => xlsxSheetXml($headers, $columns, $rows),
    ];
}

function xlsxWriteZipFallback($targetFile, array $files) {
    $zipData = '';
    $centralDirectory = '';
    $offset = 0;
    [$dosTime, $dosDate] = xlsxDosTimestamp();

    foreach ($files as $path => $contents) {
        $path = str_replace('\\', '/', $path);
        $crc = crc32($contents);
        $size = strlen($contents);
        $pathLength = strlen($path);

        $localHeader = pack('V', 0x04034b50)
            . pack('vvvvv', 20, 0, 0, $dosTime, $dosDate)
            . pack('VVV', $crc, $size, $size)
            . pack('vv', $pathLength, 0)
            . $path;

        $zipData .= $localHeader . $contents;

        $centralDirectory .= pack('V', 0x02014b50)
            . pack('vvvvvv', 20, 20, 0, 0, $dosTime, $dosDate)
            . pack('VVV', $crc, $size, $size)
            . pack('vvvvv', $pathLength, 0, 0, 0, 0)
            . pack('VV', 0, $offset)
            . $path;

        $offset += strlen($localHeader) + $size;
    }

    $centralDirectoryOffset = strlen($zipData);
    $centralDirectorySize = strlen($centralDirectory);
    $fileCount = count($files);

    $endOfCentralDirectory = pack('V', 0x06054b50)
        . pack('vvvv', 0, 0, $fileCount, $fileCount)
        . pack('VV', $centralDirectorySize, $centralDirectoryOffset)
        . pack('v', 0);

    if (file_put_contents($targetFile, $zipData . $centralDirectory . $endOfCentralDirectory) === false) {
        throw new RuntimeException('Tidak bisa menulis file Excel sementara.');
    }
}

function xlsxDosTimestamp() {
    $year = (int) date('Y');
    $month = (int) date('n');
    $day = (int) date('j');
    $hour = (int) date('G');
    $minute = (int) date('i');
    $second = (int) date('s');

    $year = max(1980, $year);
    $dosTime = ($hour << 11) | ($minute << 5) | ((int) floor($second / 2));
    $dosDate = (($year - 1980) << 9) | ($month << 5) | $day;

    return [$dosTime, $dosDate];
}

function xlsxContentTypesXml() {
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
        . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
        . '<Default Extension="xml" ContentType="application/xml"/>'
        . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
        . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
        . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
        . '</Types>';
}

function xlsxRootRelsXml() {
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
        . '</Relationships>';
}

function xlsxWorkbookXml($sheetName) {
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
        . '<sheets><sheet name="' . xlsxXml($sheetName) . '" sheetId="1" r:id="rId1"/></sheets>'
        . '</workbook>';
}

function xlsxWorkbookRelsXml() {
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
        . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
        . '</Relationships>';
}

function xlsxStylesXml() {
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts>'
        . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF1E3C72"/><bgColor rgb="FF1E3C72"/></patternFill></fill></fills>'
        . '<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left style="thin"><color rgb="FFD9E2EC"/></left><right style="thin"><color rgb="FFD9E2EC"/></right><top style="thin"><color rgb="FFD9E2EC"/></top><bottom style="thin"><color rgb="FFD9E2EC"/></bottom><diagonal/></border></borders>'
        . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
        . '<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="1" applyBorder="1"/><xf numFmtId="0" fontId="1" fillId="2" borderId="1" applyFont="1" applyFill="1" applyBorder="1"/></cellXfs>'
        . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
        . '</styleSheet>';
}

function xlsxSheetXml(array $headers, array $columns, array $rows) {
    $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
        . xlsxColumnsXml($headers, $columns, $rows)
        . '<sheetData>';

    $xml .= '<row r="1">';
    foreach ($headers as $index => $header) {
        $xml .= xlsxCell(1, $index + 1, $header, 1);
    }
    $xml .= '</row>';

    $rowNumber = 2;
    foreach ($rows as $row) {
        $xml .= '<row r="' . $rowNumber . '">';
        foreach ($columns as $index => $column) {
            $xml .= xlsxCell($rowNumber, $index + 1, $row[$column] ?? null);
        }
        $xml .= '</row>';
        $rowNumber++;
    }

    $lastColumn = xlsxColumnName(max(count($headers), 1));
    $lastRow = max($rowNumber - 1, 1);

    return $xml
        . '</sheetData>'
        . '<autoFilter ref="A1:' . $lastColumn . $lastRow . '"/>'
        . '</worksheet>';
}

function xlsxColumnsXml(array $headers, array $columns, array $rows) {
    $xml = '<cols>';

    foreach ($columns as $index => $column) {
        $width = strlen((string) ($headers[$index] ?? $column)) + 2;
        foreach (array_slice($rows, 0, 100) as $row) {
            $width = max($width, strlen((string) ($row[$column] ?? '')) + 2);
        }
        $width = max(12, min(48, $width));
        $columnIndex = $index + 1;
        $xml .= '<col min="' . $columnIndex . '" max="' . $columnIndex . '" width="' . $width . '" customWidth="1"/>';
    }

    return $xml . '</cols>';
}

function xlsxCell($rowNumber, $columnNumber, $value, $style = 0) {
    $cellRef = xlsxColumnName($columnNumber) . $rowNumber;
    $styleAttribute = $style > 0 ? ' s="' . $style . '"' : '';

    if ($value === null) {
        return '<c r="' . $cellRef . '"' . $styleAttribute . ' t="inlineStr"><is><t>N/A</t></is></c>';
    }

    if ($value === '') {
        return '<c r="' . $cellRef . '"' . $styleAttribute . '/>';
    }

    if (xlsxIsNumber($value)) {
        return '<c r="' . $cellRef . '"' . $styleAttribute . '><v>' . xlsxXml((string) $value) . '</v></c>';
    }

    return '<c r="' . $cellRef . '" t="inlineStr"' . $styleAttribute . '><is><t>' . xlsxXml((string) $value) . '</t></is></c>';
}

function xlsxIsNumber($value) {
    if (is_int($value) || is_float($value)) {
        return true;
    }

    if (!is_string($value)) {
        return false;
    }

    $value = trim($value);
    if ($value === '' || !is_numeric($value)) {
        return false;
    }

    return !preg_match('/^0\d+$/', $value);
}

function xlsxColumnName($number) {
    $name = '';
    while ($number > 0) {
        $number--;
        $name = chr(65 + ($number % 26)) . $name;
        $number = (int) floor($number / 26);
    }

    return $name;
}

function xlsxXml($value) {
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', (string) $value);
    return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}
