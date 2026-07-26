<?php
/**
 * Integrated report dashboard.
 *
 * The distance matrix uses range_tests as its point index and joins every
 * distance-based parameter using date, location, and measured distance. This
 * matches the structure of the authoritative combined connectivity workbook.
 */

if (!function_exists('reportAverage')) {
    function reportAverage($values) {
        $numbers = array_values(array_filter($values, function ($value) {
            return $value !== null && $value !== '' && is_numeric($value);
        }));
        return $numbers ? array_sum($numbers) / count($numbers) : null;
    }
}

if (!function_exists('reportMedian')) {
    function reportMedian($values) {
        $numbers = array_values(array_map('floatval', array_filter($values, function ($value) {
            return $value !== null && $value !== '' && is_numeric($value);
        })));
        if (!$numbers) return null;
        sort($numbers, SORT_NUMERIC);
        $middle = (int) floor(count($numbers) / 2);
        return count($numbers) % 2
            ? $numbers[$middle]
            : ($numbers[$middle - 1] + $numbers[$middle]) / 2;
    }
}

if (!function_exists('reportExtremeRow')) {
    function reportExtremeRow($rows, $field, $mode = 'max', $validator = null) {
        $selected = null;
        foreach ($rows as $row) {
            $value = $row[$field] ?? null;
            if (!is_numeric($value)) continue;
            if ($validator && !call_user_func($validator, $value, $row)) continue;
            if ($selected === null) {
                $selected = $row;
                continue;
            }
            $current = (float) $selected[$field];
            if (($mode === 'max' && (float) $value > $current) || ($mode === 'min' && (float) $value < $current)) {
                $selected = $row;
            }
        }
        return $selected;
    }
}

if (!function_exists('reportCorrelation')) {
    function reportCorrelation($rows, $xField, $yField, $validator = null) {
        $pairs = [];
        foreach ($rows as $row) {
            $x = $row[$xField] ?? null;
            $y = $row[$yField] ?? null;
            if (!is_numeric($x) || !is_numeric($y)) continue;
            if ($validator && !call_user_func($validator, $x, $y, $row)) continue;
            $pairs[] = [(float) $x, (float) $y];
        }
        if (count($pairs) < 3) return null;

        $meanX = array_sum(array_column($pairs, 0)) / count($pairs);
        $meanY = array_sum(array_column($pairs, 1)) / count($pairs);
        $numerator = 0.0;
        $denominatorX = 0.0;
        $denominatorY = 0.0;
        foreach ($pairs as $pair) {
            $deltaX = $pair[0] - $meanX;
            $deltaY = $pair[1] - $meanY;
            $numerator += $deltaX * $deltaY;
            $denominatorX += $deltaX * $deltaX;
            $denominatorY += $deltaY * $deltaY;
        }
        $denominator = sqrt($denominatorX * $denominatorY);
        return $denominator > 0 ? $numerator / $denominator : null;
    }
}

if (!function_exists('reportCorrelationLabel')) {
    function reportCorrelationLabel($value) {
        if ($value === null) return 'data belum cukup';
        $strength = abs($value) >= 0.7 ? 'kuat' : (abs($value) >= 0.4 ? 'sedang' : 'lemah');
        $direction = $value < 0 ? 'negatif' : 'positif';
        return $direction . ' ' . $strength . ' (r=' . number_format($value, 2) . ')';
    }
}

if (!function_exists('reportNumber')) {
    function reportNumber($value, $decimals = 2, $unit = '') {
        if ($value === null || $value === '' || !is_numeric($value)) return '<span class="report-na">N/A</span>';
        return number_format((float) $value, $decimals, ',', '.') . ($unit ? ' ' . $unit : '');
    }
}

if (!function_exists('reportEnvironmentLabel')) {
    function reportEnvironmentLabel($environment) {
        $labels = [
            'lapangan' => 'Lapangan',
            'hangar' => 'Hangar',
            'pantai' => 'Pantai',
            'gunung' => 'Gunung',
            'indoor' => 'Indoor',
            'outdoor' => 'Outdoor',
        ];
        return $labels[$environment] ?? ucfirst((string) $environment);
    }
}

if (!function_exists('reportStatusBadge')) {
    function reportStatusBadge($status) {
        $normalized = strtolower((string) $status);
        $class = in_array($normalized, ['connected', 'good', 'success', 'achieved', 'normal', 'passed', 'locked', 'associated', 'active'], true)
            ? 'is-good'
            : (in_array($normalized, ['intermittent', 'moderate', 'medium', 'not evaluated', 'partial', 'not_checked'], true) ? 'is-warning' : 'is-danger');
        return '<span class="report-status ' . $class . '">' . htmlspecialchars(ucwords((string) $status)) . '</span>';
    }
}

$environmentOptions = fetchAll("SELECT DISTINCT environment_type FROM range_tests WHERE environment_type IS NOT NULL AND environment_type != '' ORDER BY environment_type");
$locationOptions = fetchAll("SELECT DISTINCT location_name FROM range_tests WHERE location_name IS NOT NULL AND location_name != '' ORDER BY location_name");
$bounds = fetchOne("
    SELECT
        MIN(date_rows.test_date) AS min_date,
        MAX(date_rows.test_date) AS max_date,
        (SELECT MIN(distance_actual_meter) FROM range_tests) AS min_distance,
        (SELECT MAX(distance_actual_meter) FROM range_tests) AS max_distance
    FROM (
        SELECT test_date FROM range_tests
        UNION ALL
        SELECT test_date FROM satellite_vsat_tests
    ) AS date_rows
");

$filters = [
    'date_from' => trim((string) ($_GET['date_from'] ?? '')),
    'date_to' => trim((string) ($_GET['date_to'] ?? '')),
    'environment' => trim((string) ($_GET['environment'] ?? '')),
    'location' => trim((string) ($_GET['location'] ?? '')),
    'distance_min' => trim((string) ($_GET['distance_min'] ?? '')),
    'distance_max' => trim((string) ($_GET['distance_max'] ?? '')),
    'status' => trim((string) ($_GET['status'] ?? '')),
];

$allowedEnvironments = array_column($environmentOptions, 'environment_type');
$allowedStatuses = ['connected', 'intermittent', 'disconnected'];
if ($filters['environment'] !== '' && !in_array($filters['environment'], $allowedEnvironments, true)) $filters['environment'] = '';
if ($filters['status'] !== '' && !in_array($filters['status'], $allowedStatuses, true)) $filters['status'] = '';
foreach (['date_from', 'date_to'] as $dateFilter) {
    if ($filters[$dateFilter] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters[$dateFilter])) $filters[$dateFilter] = '';
}
foreach (['distance_min', 'distance_max'] as $numberFilter) {
    if ($filters[$numberFilter] !== '' && !is_numeric($filters[$numberFilter])) $filters[$numberFilter] = '';
}

$where = [];
$params = [];
if ($filters['date_from'] !== '') { $where[] = 'r.test_date >= ?'; $params[] = $filters['date_from']; }
if ($filters['date_to'] !== '') { $where[] = 'r.test_date <= ?'; $params[] = $filters['date_to']; }
if ($filters['environment'] !== '') { $where[] = 'r.environment_type = ?'; $params[] = $filters['environment']; }
if ($filters['location'] !== '') { $where[] = 'r.location_name = ?'; $params[] = $filters['location']; }
if ($filters['distance_min'] !== '') { $where[] = 'r.distance_actual_meter >= ?'; $params[] = (float) $filters['distance_min']; }
if ($filters['distance_max'] !== '') { $where[] = 'r.distance_actual_meter <= ?'; $params[] = (float) $filters['distance_max']; }
if ($filters['status'] !== '') { $where[] = 'c.connection_status = ?'; $params[] = $filters['status']; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$matrixRows = fetchAll("
    SELECT
        r.id AS range_id,
        r.test_point_code,
        r.test_date,
        r.location_name,
        r.environment_type,
        r.distance_actual_meter,
        r.connection_status AS range_connection_status,
        r.rssi_dbm,
        r.snr_db,
        r.bitrate_kbps AS range_bitrate_kbps,
        r.status_result AS range_quality,
        c.connection_status,
        c.packet_loss_percent,
        c.packet_success_rate,
        l.latency_ms,
        l.jitter_ms,
        t.throughput_kbps,
        t.packet_delivery_ratio_percent,
        t.data_loss_percent,
        p.obstacle_type,
        p.condition_type,
        p.penetration_loss_db,
        p.bitrate_kbps AS penetration_throughput_kbps,
        i.interference_level,
        i.interference_source,
        i.throughput_kbps AS obstruction_throughput_kbps,
        i.latency_ms AS obstruction_latency_ms
    FROM range_tests r
    LEFT JOIN connectivity_tests c
        ON c.test_date = r.test_date
        AND c.location_name = r.location_name
        AND c.distance_meter = r.distance_actual_meter
    LEFT JOIN latency_tests l
        ON l.test_date = r.test_date
        AND l.location_name = r.location_name
        AND l.distance_meter = r.distance_actual_meter
    LEFT JOIN throughput_tests t
        ON t.test_date = r.test_date
        AND t.location_name = r.location_name
        AND t.distance_meter = r.distance_actual_meter
    LEFT JOIN signal_penetration_tests p
        ON p.test_date = r.test_date
        AND p.location_name = r.location_name
        AND p.distance_meter = r.distance_actual_meter
    LEFT JOIN interference_tests i
        ON i.test_date = r.test_date
        AND i.location_name = r.location_name
        AND i.distance_meter = r.distance_actual_meter
    $whereSql
    ORDER BY r.distance_actual_meter ASC, r.test_point_code ASC
", $params);

$opsWhere = [];
$opsParams = [];
if ($filters['date_from'] !== '') { $opsWhere[] = 'test_date >= ?'; $opsParams[] = $filters['date_from']; }
if ($filters['date_to'] !== '') { $opsWhere[] = 'test_date <= ?'; $opsParams[] = $filters['date_to']; }
$opsWhereSql = $opsWhere ? 'WHERE ' . implode(' AND ', $opsWhere) : '';

$powerData = fetchAll("SELECT test_date, device_id, device_type, battery_voltage_v, current_a, test_duration_hour, power_w, energy_wh, result, notes FROM power_consumption_tests $opsWhereSql ORDER BY test_date ASC, id ASC", $opsParams);
$commandData = fetchAll("SELECT test_date, command_type, source, target_node_id, execution_status, command_delivery_delay, total_command_time, notes FROM command_execution_tests $opsWhereSql ORDER BY test_date ASC, id ASC", $opsParams);
$textMessageData = fetchAll("SELECT test_date, source_node, target_node_id, message_text, delivery_status, latency_ms FROM text_message_logs $opsWhereSql ORDER BY test_date ASC, id ASC", $opsParams);
$satelliteData = fetchAll("
    SELECT
        test_date,
        test_session_code,
        planned_trials,
        trial_number,
        test_operator,
        node_id,
        gateway_ip,
        satellite_name,
        signal_quality_factor,
        server_target,
        packet_sent,
        packet_received,
        latency_min_ms,
        latency_ms,
        latency_max_ms,
        packet_loss_percent,
        vsat_lock_status,
        association_status,
        tdma_status,
        association_time,
        overall_status
    FROM satellite_vsat_tests
    $opsWhereSql
    ORDER BY test_date ASC, test_session_code ASC, trial_number ASC
", $opsParams);
$satelliteSummary = fetchOne("
    SELECT
        COUNT(*) AS total_trials,
        COUNT(DISTINCT test_session_code) AS total_sessions,
        SUM(overall_status = 'passed') AS passed_trials,
        SUM(packet_loss_percent = 0) AS zero_loss_trials,
        SUM(vsat_lock_status = 'locked') AS locked_trials,
        SUM(association_status = 'associated') AS associated_trials,
        SUM(tdma_status = 'active') AS active_tdma_trials,
        SUM(packet_sent) AS total_packet_sent,
        SUM(packet_received) AS total_packet_received,
        AVG(packet_sent) AS avg_packet_sent,
        AVG(packet_received) AS avg_packet_received,
        MIN(latency_min_ms) AS observed_latency_min_ms,
        AVG(latency_ms) AS avg_latency_ms,
        MAX(latency_max_ms) AS observed_latency_max_ms,
        MIN(latency_ms) AS lowest_trial_avg_latency_ms,
        MAX(latency_ms) AS highest_trial_avg_latency_ms,
        AVG(packet_loss_percent) AS avg_packet_loss_percent,
        MIN(signal_quality_factor) AS min_signal_quality_factor,
        MAX(signal_quality_factor) AS max_signal_quality_factor,
        GROUP_CONCAT(DISTINCT satellite_name ORDER BY satellite_name SEPARATOR ', ') AS satellite_names,
        MIN(test_date) AS first_test_date,
        MAX(test_date) AS last_test_date
    FROM satellite_vsat_tests
    $opsWhereSql
", $opsParams);
$satelliteTrialAverageSpan = (
    is_numeric($satelliteSummary['highest_trial_avg_latency_ms'] ?? null)
    && is_numeric($satelliteSummary['lowest_trial_avg_latency_ms'] ?? null)
)
    ? (float) $satelliteSummary['highest_trial_avg_latency_ms'] - (float) $satelliteSummary['lowest_trial_avg_latency_ms']
    : null;
$satellitePacketSuccessPercent = (int) ($satelliteSummary['total_packet_sent'] ?? 0) > 0
    ? ((float) ($satelliteSummary['total_packet_received'] ?? 0) / (float) $satelliteSummary['total_packet_sent']) * 100
    : null;

$summaryStats = [
    'connectivity' => (int) (fetchOne('SELECT COUNT(*) total FROM connectivity_tests')['total'] ?? 0),
    'range' => (int) (fetchOne('SELECT COUNT(*) total FROM range_tests')['total'] ?? 0),
    'penetration' => (int) (fetchOne('SELECT COUNT(*) total FROM signal_penetration_tests')['total'] ?? 0),
    'latency' => (int) (fetchOne('SELECT COUNT(*) total FROM latency_tests')['total'] ?? 0),
    'throughput' => (int) (fetchOne('SELECT COUNT(*) total FROM throughput_tests')['total'] ?? 0),
    'interference' => (int) (fetchOne('SELECT COUNT(*) total FROM interference_tests')['total'] ?? 0),
    'power' => (int) (fetchOne('SELECT COUNT(*) total FROM power_consumption_tests')['total'] ?? 0),
    'command' => (int) (fetchOne('SELECT COUNT(*) total FROM command_execution_tests')['total'] ?? 0),
    'text' => (int) (fetchOne('SELECT COUNT(*) total FROM text_message_logs')['total'] ?? 0),
    'satellite' => (int) (fetchOne('SELECT COUNT(*) total FROM satellite_vsat_tests')['total'] ?? 0),
];
$totalRecords = array_sum($summaryStats);

$remoteRows = array_values(array_filter($matrixRows, function ($row) {
    return is_numeric($row['distance_actual_meter'] ?? null) && (float) $row['distance_actual_meter'] > 0;
}));
$connectedRows = array_values(array_filter($remoteRows, function ($row) {
    return ($row['connection_status'] ?? '') === 'connected';
}));
$validRssiRows = array_values(array_filter($remoteRows, function ($row) {
    return is_numeric($row['rssi_dbm'] ?? null) && (float) $row['rssi_dbm'] < 0;
}));
$validSnrRows = array_values(array_filter($remoteRows, function ($row) {
    return is_numeric($row['snr_db'] ?? null) && (float) $row['snr_db'] > 0;
}));
$validLatencyRows = array_values(array_filter($remoteRows, function ($row) {
    return is_numeric($row['latency_ms'] ?? null) && (float) $row['latency_ms'] > 0;
}));
$validPdrRows = array_values(array_filter($remoteRows, function ($row) {
    return is_numeric($row['packet_delivery_ratio_percent'] ?? null)
        && (float) $row['packet_delivery_ratio_percent'] >= 0
        && (float) $row['packet_delivery_ratio_percent'] <= 100;
}));

$maxMeasuredRow = reportExtremeRow($remoteRows, 'distance_actual_meter');
$maxConnectedRow = reportExtremeRow($connectedRows, 'distance_actual_meter');
$weakestRssiRow = reportExtremeRow($validRssiRows, 'rssi_dbm', 'min');
$bestRssiRow = reportExtremeRow($validRssiRows, 'rssi_dbm', 'max');
$highestLossRow = reportExtremeRow($remoteRows, 'packet_loss_percent');
$highestLatencyRow = reportExtremeRow($validLatencyRows, 'latency_ms');
$peakThroughputRow = reportExtremeRow($remoteRows, 'throughput_kbps');
$highestPenetrationRow = reportExtremeRow($remoteRows, 'penetration_loss_db');

$averageRssi = reportAverage(array_column($validRssiRows, 'rssi_dbm'));
$averageSnr = reportAverage(array_column($validSnrRows, 'snr_db'));
$averageLoss = reportAverage(array_column($remoteRows, 'packet_loss_percent'));
$medianLatency = reportMedian(array_column($validLatencyRows, 'latency_ms'));
$averageThroughput = reportAverage(array_column($remoteRows, 'throughput_kbps'));
$averagePdr = reportAverage(array_column($validPdrRows, 'packet_delivery_ratio_percent'));

$distanceRssiCorrelation = reportCorrelation($remoteRows, 'distance_actual_meter', 'rssi_dbm', function ($x, $y) { return $x > 0 && $y < 0; });
$distanceLossCorrelation = reportCorrelation($remoteRows, 'distance_actual_meter', 'packet_loss_percent', function ($x) { return $x > 0; });
$distanceLatencyCorrelation = reportCorrelation($remoteRows, 'distance_actual_meter', 'latency_ms', function ($x, $y) { return $x > 0 && $y > 0; });
$distanceThroughputCorrelation = reportCorrelation($remoteRows, 'distance_actual_meter', 'throughput_kbps', function ($x) { return $x > 0; });
$distancePenetrationCorrelation = reportCorrelation($remoteRows, 'distance_actual_meter', 'penetration_loss_db', function ($x) { return $x > 0; });

$validPowerRows = array_values(array_filter($powerData, function ($row) {
    return is_numeric($row['power_w'] ?? null) && (float) $row['power_w'] > 0;
}));
$averagePower = reportAverage(array_column($validPowerRows, 'power_w'));
$peakPowerRow = reportExtremeRow($validPowerRows, 'power_w');
$achievedPowerCount = count(array_filter($powerData, function ($row) { return ($row['result'] ?? '') === 'Achieved'; }));
$commandSuccessCount = count(array_filter($commandData, function ($row) { return ($row['execution_status'] ?? '') === 'success'; }));
$messageSuccessCount = count(array_filter($textMessageData, function ($row) { return ($row['delivery_status'] ?? '') === 'success'; }));

$activeFilterCount = count(array_filter($filters, function ($value) { return $value !== ''; }));

$chartRows = [];
foreach ($matrixRows as $row) {
    $chartRows[] = [
        'point' => $row['test_point_code'],
        'date' => $row['test_date'],
        'location' => $row['location_name'],
        'environment' => $row['environment_type'],
        'distance' => is_numeric($row['distance_actual_meter']) ? (float) $row['distance_actual_meter'] : null,
        'status' => $row['connection_status'],
        'rssi' => is_numeric($row['rssi_dbm']) ? (float) $row['rssi_dbm'] : null,
        'snr' => is_numeric($row['snr_db']) ? (float) $row['snr_db'] : null,
        'packet_loss' => is_numeric($row['packet_loss_percent']) ? (float) $row['packet_loss_percent'] : null,
        'packet_success' => is_numeric($row['packet_success_rate']) ? (float) $row['packet_success_rate'] : null,
        'latency' => is_numeric($row['latency_ms']) ? (float) $row['latency_ms'] : null,
        'jitter' => is_numeric($row['jitter_ms']) ? (float) $row['jitter_ms'] : null,
        'throughput' => is_numeric($row['throughput_kbps']) ? (float) $row['throughput_kbps'] : null,
        'pdr' => is_numeric($row['packet_delivery_ratio_percent']) ? (float) $row['packet_delivery_ratio_percent'] : null,
        'data_loss' => is_numeric($row['data_loss_percent']) ? (float) $row['data_loss_percent'] : null,
        'range_bitrate' => is_numeric($row['range_bitrate_kbps']) ? (float) $row['range_bitrate_kbps'] : null,
        'penetration_loss' => is_numeric($row['penetration_loss_db']) ? (float) $row['penetration_loss_db'] : null,
        'obstruction_throughput' => is_numeric($row['obstruction_throughput_kbps']) ? (float) $row['obstruction_throughput_kbps'] : null,
        'obstruction_latency' => is_numeric($row['obstruction_latency_ms']) ? (float) $row['obstruction_latency_ms'] : null,
    ];
}

$technicalSources = [
    'itu' => [
        'label' => 'ITU-R P.525-5',
        'title' => 'Calculation of free-space attenuation',
        'url' => 'https://www.itu.int/rec/R-REC-P.525-5-202411-I/en',
    ],
    'paper' => [
        'label' => 'Adame dkk.',
        'title' => 'IEEE 802.11ah: The Wi-Fi Approach for M2M Communications',
        'url' => 'https://arxiv.org/abs/1402.4675',
    ],
    'morse' => [
        'label' => 'Morse Micro',
        'title' => 'HaLowLink User Guide',
        'url' => 'https://www.morsemicro.com/resources/user_guides/HaLowLink-User-Guide.pdf',
    ],
    'alliance' => [
        'label' => 'Wi-Fi Alliance',
        'title' => 'Wi-Fi CERTIFIED HaLow',
        'url' => 'https://www.wi-fi.org/discover-wi-fi/wi-fi-certified-halow',
    ],
];
?>

<style>
.report-page { --report-navy:#0f2747; --report-blue:#2563eb; --report-border:#dbe4ef; --report-muted:#64748b; }
.report-hero { background:linear-gradient(135deg,#0f2747 0%,#1e4f8f 62%,#2563eb 100%); color:#fff; border-radius:18px; padding:26px; margin-bottom:18px; box-shadow:0 18px 38px rgba(15,39,71,.18); }
.report-hero h2 { font-weight:750; margin:0 0 8px; }
.report-hero p { margin:0; max-width:820px; color:rgba(255,255,255,.86); }
.report-hero-meta { min-width:190px; padding:12px 16px; border:1px solid rgba(255,255,255,.25); background:rgba(255,255,255,.1); border-radius:12px; }
.report-jump-nav { position:sticky; top:0; z-index:25; display:flex; gap:8px; overflow-x:auto; padding:10px; margin-bottom:18px; background:rgba(248,250,252,.96); border:1px solid var(--report-border); border-radius:12px; backdrop-filter:blur(10px); }
.report-jump-nav a { white-space:nowrap; text-decoration:none; color:#334155; background:#fff; border:1px solid var(--report-border); border-radius:999px; padding:7px 12px; font-size:.84rem; font-weight:650; }
.report-jump-nav a:hover { color:var(--report-blue); border-color:#93b4ef; }
.report-card { background:#fff; border:1px solid var(--report-border); border-radius:14px; box-shadow:0 8px 24px rgba(15,23,42,.055); }
.report-card-header { padding:16px 18px; border-bottom:1px solid var(--report-border); display:flex; justify-content:space-between; align-items:flex-start; gap:12px; flex-wrap:wrap; }
.report-card-header h4,.report-card-header h5 { margin:0; color:var(--report-navy); font-weight:720; }
.report-card-header p { margin:5px 0 0; color:var(--report-muted); font-size:.88rem; }
.report-card-body { padding:18px; }
.report-filter { margin-bottom:18px; }
.report-filter .form-label { font-size:.78rem; color:#475569; font-weight:700; margin-bottom:5px; }
.report-filter .form-control,.report-filter .form-select { font-size:.88rem; }
.report-filter-count { display:inline-flex; align-items:center; gap:6px; padding:5px 9px; border-radius:999px; background:#e0edff; color:#1d4ed8; font-size:.78rem; font-weight:700; }
.report-stat { height:100%; padding:15px; border:1px solid var(--report-border); border-radius:12px; background:#fff; }
.report-stat-label { color:var(--report-muted); font-size:.78rem; font-weight:700; text-transform:uppercase; letter-spacing:.035em; }
.report-stat-value { color:var(--report-navy); font-size:1.42rem; font-weight:780; line-height:1.2; margin:7px 0 3px; }
.report-stat-note { color:#64748b; font-size:.76rem; }
.report-answer { height:100%; padding:16px; border-radius:12px; border:1px solid var(--report-border); border-left:5px solid var(--answer-color,#2563eb); background:linear-gradient(135deg,#fff,#f8fbff); }
.report-answer .question { color:#64748b; font-size:.75rem; font-weight:750; text-transform:uppercase; letter-spacing:.04em; }
.report-answer .answer { margin:7px 0 5px; color:#172554; font-weight:720; line-height:1.35; }
.report-answer .reason { color:#475569; font-size:.84rem; line-height:1.45; margin:0; }
.report-section-title { margin:28px 0 12px; color:var(--report-navy); font-weight:760; scroll-margin-top:76px; }
.report-section-title small { display:block; color:var(--report-muted); font-size:.86rem; font-weight:400; margin-top:4px; }
.report-chart { height:330px; position:relative; }
.report-chart.is-large { height:420px; }
.report-chart-note { color:#64748b; font-size:.78rem; margin-top:8px; }
.report-metric-select { min-width:230px; }
.report-matrix-tools { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
.report-matrix-tools .form-control { width:min(320px,100%); }
.matrix-focus-btn.active { color:#fff; background:#2563eb; border-color:#2563eb; }
.report-matrix-shell { max-height:620px; overflow:auto; border:1px solid var(--report-border); border-radius:10px; }
.report-matrix { margin:0; min-width:2450px; font-size:.78rem; }
.report-matrix th { position:sticky; top:0; z-index:4; color:#243b5a; background:#eef4fb; white-space:nowrap; vertical-align:middle; }
.report-matrix thead tr:first-child th { top:0; background:#dfeaf7; text-align:center; font-size:.72rem; letter-spacing:.035em; text-transform:uppercase; }
.report-matrix thead tr:nth-child(2) th { top:31px; }
.report-matrix td { white-space:nowrap; vertical-align:middle; }
.report-matrix th:first-child,.report-matrix td:first-child { position:sticky; left:0; z-index:3; background:#fff; box-shadow:3px 0 7px rgba(15,23,42,.07); }
.report-matrix thead th:first-child { z-index:6; background:#dfeaf7; }
.report-matrix tbody tr:hover td { background:#f7fbff; }
.report-matrix tbody tr:hover td:first-child { background:#f7fbff; }
.report-matrix.focus-signal [data-group]:not([data-group="identity"]):not([data-group="signal"]),
.report-matrix.focus-reliability [data-group]:not([data-group="identity"]):not([data-group="reliability"]),
.report-matrix.focus-timing [data-group]:not([data-group="identity"]):not([data-group="timing"]),
.report-matrix.focus-transfer [data-group]:not([data-group="identity"]):not([data-group="transfer"]),
.report-matrix.focus-obstacle [data-group]:not([data-group="identity"]):not([data-group="obstacle"]),
.report-matrix.focus-interference [data-group]:not([data-group="identity"]):not([data-group="interference"]) { display:none; }
.report-status { display:inline-flex; padding:3px 8px; border-radius:999px; font-weight:720; font-size:.7rem; }
.report-status.is-good { background:#dcfce7; color:#166534; }
.report-status.is-warning { background:#fef3c7; color:#92400e; }
.report-status.is-danger { background:#fee2e2; color:#991b1b; }
.report-na { color:#94a3b8; font-style:italic; }
.report-data-note { padding:12px 14px; border-radius:10px; border:1px solid #f5d589; background:#fff8e6; color:#7c5200; font-size:.83rem; }
.report-analysis { height:100%; padding:18px; border:1px solid var(--report-border); border-radius:13px; background:#fff; }
.report-analysis h5 { color:var(--report-navy); font-weight:730; margin:0 0 12px; }
.report-analysis .finding { padding:10px 12px; border-radius:9px; background:#eef5ff; color:#1e3a66; font-weight:690; margin-bottom:10px; }
.report-analysis p { color:#475569; font-size:.86rem; line-height:1.55; }
.report-analysis .sidang { margin:0; padding:10px 12px; border-left:4px solid #16a34a; background:#f0fdf4; color:#166534; font-size:.84rem; }
.report-source-link { font-size:.74rem; text-decoration:none; font-weight:700; }
.report-operational-table { font-size:.82rem; }
.report-operational-table th { color:#334155; background:#f1f5f9; white-space:nowrap; }
.report-empty { text-align:center; padding:40px 20px; color:#64748b; }
@media (max-width:767.98px) {
    .report-hero { padding:20px; }
    .report-hero-meta { width:100%; }
    .report-chart,.report-chart.is-large { height:310px; }
    .report-card-body { padding:14px; }
}
</style>

<div class="report-page">
    <section class="report-hero">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h2><i class="fas fa-chart-line"></i> Pusat Analisis Hasil Pengujian</h2>
                <p>Satu tampilan untuk menelusuri titik uji, membandingkan jarak dengan semua parameter, membaca anomali, dan menyiapkan jawaban berbasis data untuk sidang.</p>
            </div>
            <div class="report-hero-meta">
                <div class="small opacity-75">Data aktif</div>
                <div class="fs-4 fw-bold"><?php echo count($matrixRows); ?> titik uji</div>
                <div class="small opacity-75"><?php echo number_format($totalRecords); ?> rekaman parameter</div>
            </div>
        </div>
    </section>

    <nav class="report-jump-nav" aria-label="Navigasi laporan">
        <a href="#ringkasan"><i class="fas fa-gauge-high"></i> Ringkasan</a>
        <a href="#grafik-jarak"><i class="fas fa-chart-scatter"></i> Grafik Jarak</a>
        <a href="#matriks-jarak"><i class="fas fa-table-cells"></i> Matriks</a>
        <a href="#analisis-parameter"><i class="fas fa-magnifying-glass-chart"></i> Analisis</a>
        <a href="#satelit-vsat"><i class="fas fa-satellite-dish"></i> Satelit/VSAT</a>
        <a href="#operasional"><i class="fas fa-microchip"></i> Operasional</a>
        <a href="#referensi"><i class="fas fa-book"></i> Referensi</a>
    </nav>

    <section class="report-card report-filter" id="ringkasan">
        <div class="report-card-header">
            <div>
                <h5><i class="fas fa-filter"></i> Filter data</h5>
                <p>Semua ringkasan, grafik jarak, matriks, dan analisis di bawah mengikuti filter ini.</p>
            </div>
            <span class="report-filter-count"><i class="fas fa-sliders"></i> <?php echo $activeFilterCount; ?> filter aktif</span>
        </div>
        <div class="report-card-body">
            <form method="GET" action="index.php#ringkasan">
                <input type="hidden" name="page" value="reports">
                <div class="row g-3 align-items-end">
                    <div class="col-6 col-lg-2">
                        <label class="form-label">Dari tanggal</label>
                        <input type="date" class="form-control" name="date_from" min="<?php echo htmlspecialchars($bounds['min_date'] ?? ''); ?>" max="<?php echo htmlspecialchars($bounds['max_date'] ?? ''); ?>" value="<?php echo htmlspecialchars($filters['date_from']); ?>">
                    </div>
                    <div class="col-6 col-lg-2">
                        <label class="form-label">Sampai tanggal</label>
                        <input type="date" class="form-control" name="date_to" min="<?php echo htmlspecialchars($bounds['min_date'] ?? ''); ?>" max="<?php echo htmlspecialchars($bounds['max_date'] ?? ''); ?>" value="<?php echo htmlspecialchars($filters['date_to']); ?>">
                    </div>
                    <div class="col-6 col-lg-2">
                        <label class="form-label">Environment</label>
                        <select class="form-select" name="environment">
                            <option value="">Semua environment</option>
                            <?php foreach ($environmentOptions as $option): ?>
                                <option value="<?php echo htmlspecialchars($option['environment_type']); ?>" <?php echo $filters['environment'] === $option['environment_type'] ? 'selected' : ''; ?>><?php echo htmlspecialchars(reportEnvironmentLabel($option['environment_type'])); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-lg-3">
                        <label class="form-label">Lokasi</label>
                        <select class="form-select" name="location">
                            <option value="">Semua lokasi</option>
                            <?php foreach ($locationOptions as $option): ?>
                                <option value="<?php echo htmlspecialchars($option['location_name']); ?>" <?php echo $filters['location'] === $option['location_name'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($option['location_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-lg-1">
                        <label class="form-label">Jarak min.</label>
                        <input type="number" step="0.01" class="form-control" name="distance_min" placeholder="0" value="<?php echo htmlspecialchars($filters['distance_min']); ?>">
                    </div>
                    <div class="col-6 col-lg-1">
                        <label class="form-label">Jarak maks.</label>
                        <input type="number" step="0.01" class="form-control" name="distance_max" placeholder="<?php echo htmlspecialchars((string) round((float) ($bounds['max_distance'] ?? 0))); ?>" value="<?php echo htmlspecialchars($filters['distance_max']); ?>">
                    </div>
                    <div class="col-6 col-lg-2">
                        <label class="form-label">Status koneksi</label>
                        <select class="form-select" name="status">
                            <option value="">Semua status</option>
                            <?php foreach ($allowedStatuses as $status): ?>
                                <option value="<?php echo $status; ?>" <?php echo $filters['status'] === $status ? 'selected' : ''; ?>><?php echo ucfirst($status); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-lg-auto d-flex gap-2">
                        <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> Terapkan</button>
                        <a class="btn btn-outline-secondary" href="index.php?page=reports"><i class="fas fa-rotate-left"></i> Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <?php if (!$matrixRows): ?>
        <div class="report-card report-empty">
            <i class="fas fa-filter-circle-xmark fa-2x mb-3"></i>
            <h5>Tidak ada titik yang cocok</h5>
            <p class="mb-3">Longgarkan filter tanggal, lokasi, environment, jarak, atau status.</p>
            <a class="btn btn-primary" href="index.php?page=reports">Tampilkan semua data</a>
        </div>
    <?php else: ?>
        <div class="row g-3 mb-3">
            <div class="col-6 col-lg-2"><div class="report-stat"><div class="report-stat-label">Titik terfilter</div><div class="report-stat-value"><?php echo count($matrixRows); ?></div><div class="report-stat-note"><?php echo count($connectedRows); ?> berstatus connected</div></div></div>
            <div class="col-6 col-lg-2"><div class="report-stat"><div class="report-stat-label">Jarak terjauh</div><div class="report-stat-value"><?php echo $maxMeasuredRow ? reportNumber($maxMeasuredRow['distance_actual_meter'], 2, 'm') : 'N/A'; ?></div><div class="report-stat-note"><?php echo htmlspecialchars($maxMeasuredRow['test_point_code'] ?? '-'); ?>, semua status</div></div></div>
            <div class="col-6 col-lg-2"><div class="report-stat"><div class="report-stat-label">Jarak connected</div><div class="report-stat-value"><?php echo $maxConnectedRow ? reportNumber($maxConnectedRow['distance_actual_meter'], 2, 'm') : 'N/A'; ?></div><div class="report-stat-note">batas terjauh yang masih connected</div></div></div>
            <div class="col-6 col-lg-2"><div class="report-stat"><div class="report-stat-label">Rata-rata RSSI</div><div class="report-stat-value"><?php echo reportNumber($averageRssi, 2, 'dBm'); ?></div><div class="report-stat-note">nilai 0/baseline dikeluarkan</div></div></div>
            <div class="col-6 col-lg-2"><div class="report-stat"><div class="report-stat-label">Median latency</div><div class="report-stat-value"><?php echo reportNumber($medianLatency, 2, 'ms'); ?></div><div class="report-stat-note">lebih tahan terhadap nilai timeout</div></div></div>
            <div class="col-6 col-lg-2"><div class="report-stat"><div class="report-stat-label">Rata-rata throughput</div><div class="report-stat-value"><?php echo reportNumber($averageThroughput, 2, 'kbps'); ?></div><div class="report-stat-note">berdasarkan transfer aktual</div></div></div>
        </div>

        <div class="row g-3">
            <div class="col-md-6 col-xl-3"><div class="report-answer" style="--answer-color:#16a34a"><div class="question">Berapa jangkauan sistem?</div><div class="answer">Terukur sampai <?php echo reportNumber($maxMeasuredRow['distance_actual_meter'] ?? null, 2, 'm'); ?>, stabil connected sampai <?php echo reportNumber($maxConnectedRow['distance_actual_meter'] ?? null, 2, 'm'); ?>.</div><p class="reason">Jarak maksimum pengukuran tidak sama dengan jarak operasional stabil. Titik terjauh berstatus <?php echo htmlspecialchars($maxMeasuredRow['connection_status'] ?? '-'); ?>.</p></div></div>
            <div class="col-md-6 col-xl-3"><div class="report-answer" style="--answer-color:#2563eb"><div class="question">Apakah jarak selalu melemahkan RSSI?</div><div class="answer">Pada data terfilter, korelasinya <?php echo reportCorrelationLabel($distanceRssiCorrelation); ?>.</div><p class="reason">Jarak berpengaruh, tetapi environment, LOS/NLOS, obstacle, orientasi antena, dan kondisi tiap pengujian membuat pola tidak selalu lurus.</p></div></div>
            <div class="col-md-6 col-xl-3"><div class="report-answer" style="--answer-color:#d97706"><div class="question">Kenapa latency sangat besar?</div><div class="answer">Nilai tertinggi <?php echo reportNumber($highestLatencyRow['latency_ms'] ?? null, 2, 'ms'); ?> di <?php echo htmlspecialchars($highestLatencyRow['test_point_code'] ?? '-'); ?>.</div><p class="reason">Loss tinggi, link intermittent, retry, waktu tunggu, dan timeout dapat memperpanjang waktu respons. Nilai 0 ms pada titik timeout bukan hasil terbaik.</p></div></div>
            <div class="col-md-6 col-xl-3"><div class="report-answer" style="--answer-color:#7c3aed"><div class="question">Kenapa throughput tidak selalu turun berurutan?</div><div class="answer">Puncak <?php echo reportNumber($peakThroughputRow['throughput_kbps'] ?? null, 2, 'kbps'); ?> terjadi di <?php echo htmlspecialchars($peakThroughputRow['test_point_code'] ?? '-'); ?> (<?php echo reportNumber($peakThroughputRow['distance_actual_meter'] ?? null, 2, 'm'); ?>).</div><p class="reason">Throughput juga dipengaruhi data diterima, durasi transfer, loss, bitrate/MCS, dan kondisi kanal; bukan jarak saja.</p></div></div>
        </div>

        <h3 class="report-section-title" id="grafik-jarak">Grafik jarak vs parameter<small>Pilih parameter pada grafik utama atau lihat perbandingan kelompok metrik di bawahnya.</small></h3>

        <section class="report-card mb-3">
            <div class="report-card-header">
                <div>
                    <h4><i class="fas fa-chart-scatter"></i> Penjelajah metrik</h4>
                    <p>Setiap titik menampilkan point, lokasi, environment, jarak, nilai, dan status koneksi.</p>
                </div>
                <select class="form-select report-metric-select" id="reportMetricSelector" aria-label="Pilih metrik grafik">
                    <option value="rssi">RSSI (dBm)</option>
                    <option value="snr">SNR (dB)</option>
                    <option value="packet_loss">Packet Loss (%)</option>
                    <option value="packet_success">Packet Success (%)</option>
                    <option value="latency">Latency (ms)</option>
                    <option value="jitter">Jitter (ms)</option>
                    <option value="throughput">Throughput (kbps)</option>
                    <option value="pdr">PDR (%)</option>
                    <option value="data_loss">Data Loss (%)</option>
                    <option value="range_bitrate">Range Bitrate (kbps)</option>
                    <option value="penetration_loss">Penetration Loss (dB)</option>
                    <option value="obstruction_throughput">Obstruction Throughput (kbps)</option>
                    <option value="obstruction_latency">Obstruction Latency (ms)</option>
                </select>
            </div>
            <div class="report-card-body">
                <div class="report-chart is-large"><canvas id="reportMetricExplorer"></canvas></div>
                <div class="report-chart-note" id="reportMetricExplanation"></div>
            </div>
        </section>

        <div class="row g-3">
            <div class="col-xl-6"><section class="report-card h-100"><div class="report-card-header"><div><h5>Sinyal vs Jarak</h5><p>RSSI dan SNR pada tiap titik.</p></div></div><div class="report-card-body"><div class="report-chart"><canvas id="reportSignalChart"></canvas></div></div></section></div>
            <div class="col-xl-6"><section class="report-card h-100"><div class="report-card-header"><div><h5>Reliabilitas vs Jarak</h5><p>Packet loss konektivitas dibanding PDR transfer.</p></div></div><div class="report-card-body"><div class="report-chart"><canvas id="reportReliabilityChart"></canvas></div></div></section></div>
            <div class="col-xl-6"><section class="report-card h-100"><div class="report-card-header"><div><h5>Timing vs Jarak</h5><p>Latency dan jitter memakai skala log agar rentang 0,19–59.000 ms tetap terbaca.</p></div></div><div class="report-card-body"><div class="report-chart"><canvas id="reportTimingChart"></canvas></div></div></section></div>
            <div class="col-xl-6"><section class="report-card h-100"><div class="report-card-header"><div><h5>Throughput vs Jarak</h5><p>Transfer aktual dan throughput saat obstruction test.</p></div></div><div class="report-card-body"><div class="report-chart"><canvas id="reportThroughputChart"></canvas></div></div></section></div>
            <div class="col-12"><section class="report-card"><div class="report-card-header"><div><h5>Penetration Loss vs Jarak</h5><p>Dibaca bersama obstacle dan kondisi LOS/NLOS pada matriks.</p></div><span class="report-filter-count">Korelasi <?php echo htmlspecialchars(reportCorrelationLabel($distancePenetrationCorrelation)); ?></span></div><div class="report-card-body"><div class="report-chart"><canvas id="reportPenetrationChart"></canvas></div></div></section></div>
        </div>

        <h3 class="report-section-title" id="matriks-jarak">Matriks jarak vs seluruh parameter<small>Satu baris adalah satu titik uji. Gunakan pencarian atau fokus kelompok kolom supaya tabel lebih cepat dibaca.</small></h3>

        <section class="report-card">
            <div class="report-card-header">
                <div>
                    <h4><i class="fas fa-table-cells"></i> Matriks terpadu</h4>
                    <p><span id="matrixVisibleCount"><?php echo count($matrixRows); ?></span> dari <?php echo count($matrixRows); ?> titik terlihat.</p>
                </div>
                <div class="report-matrix-tools">
                    <input type="search" class="form-control form-control-sm" id="matrixQuickSearch" placeholder="Cari point, lokasi, environment, obstacle...">
                    <div class="btn-group btn-group-sm" role="group" aria-label="Fokus kolom matriks">
                        <button type="button" class="btn btn-outline-primary matrix-focus-btn active" data-focus="all">Semua</button>
                        <button type="button" class="btn btn-outline-primary matrix-focus-btn" data-focus="signal">Sinyal</button>
                        <button type="button" class="btn btn-outline-primary matrix-focus-btn" data-focus="reliability">Reliabilitas</button>
                        <button type="button" class="btn btn-outline-primary matrix-focus-btn" data-focus="timing">Timing</button>
                        <button type="button" class="btn btn-outline-primary matrix-focus-btn" data-focus="transfer">Transfer</button>
                        <button type="button" class="btn btn-outline-primary matrix-focus-btn" data-focus="obstacle">Obstacle</button>
                        <button type="button" class="btn btn-outline-primary matrix-focus-btn" data-focus="interference">Interferensi</button>
                    </div>
                </div>
            </div>
            <div class="report-card-body">
                <div class="report-matrix-shell">
                    <table class="table table-bordered table-hover report-matrix" id="distanceMatrixTable">
                        <thead>
                            <tr>
                                <th colspan="5" data-group="identity">Identitas titik</th>
                                <th colspan="4" data-group="signal">Sinyal & range</th>
                                <th colspan="3" data-group="reliability">Reliabilitas</th>
                                <th colspan="2" data-group="timing">Timing</th>
                                <th colspan="3" data-group="transfer">Transfer data</th>
                                <th colspan="4" data-group="obstacle">Penetrasi obstacle</th>
                                <th colspan="4" data-group="interference">Interferensi</th>
                            </tr>
                            <tr>
                                <th data-group="identity">Point</th><th data-group="identity">Tanggal</th><th data-group="identity">Lokasi</th><th data-group="identity">Env.</th><th data-group="identity">Jarak</th>
                                <th data-group="signal">RSSI</th><th data-group="signal">SNR</th><th data-group="signal">Bitrate</th><th data-group="signal">Quality</th>
                                <th data-group="reliability">Status</th><th data-group="reliability">Pkt Loss</th><th data-group="reliability">Success</th>
                                <th data-group="timing">Latency</th><th data-group="timing">Jitter</th>
                                <th data-group="transfer">Throughput</th><th data-group="transfer">PDR</th><th data-group="transfer">Data Loss</th>
                                <th data-group="obstacle">Obstacle</th><th data-group="obstacle">Kondisi</th><th data-group="obstacle">Pen. Loss</th><th data-group="obstacle">Obs. Throughput</th>
                                <th data-group="interference">Level</th><th data-group="interference">Source</th><th data-group="interference">Throughput</th><th data-group="interference">Latency</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($matrixRows as $row): ?>
                                <?php $searchText = strtolower(implode(' ', array_filter([$row['test_point_code'], $row['test_date'], $row['location_name'], $row['environment_type'], $row['connection_status'], $row['obstacle_type'], $row['condition_type'], $row['interference_level'], $row['interference_source']]))); ?>
                                <tr data-matrix-search="<?php echo htmlspecialchars($searchText); ?>">
                                    <td data-group="identity"><strong><?php echo htmlspecialchars($row['test_point_code'] ?? '-'); ?></strong></td>
                                    <td data-group="identity"><?php echo htmlspecialchars(formatDate($row['test_date'])); ?></td>
                                    <td data-group="identity"><?php echo htmlspecialchars($row['location_name'] ?? '-'); ?></td>
                                    <td data-group="identity"><?php echo htmlspecialchars(reportEnvironmentLabel($row['environment_type'] ?? '')); ?></td>
                                    <td data-group="identity"><strong><?php echo reportNumber($row['distance_actual_meter'], 2, 'm'); ?></strong></td>
                                    <td data-group="signal"><?php echo reportNumber($row['rssi_dbm'], 2, 'dBm'); ?></td>
                                    <td data-group="signal"><?php echo reportNumber($row['snr_db'], 2, 'dB'); ?></td>
                                    <td data-group="signal"><?php echo reportNumber($row['range_bitrate_kbps'], 2, 'kbps'); ?></td>
                                    <td data-group="signal"><?php echo reportStatusBadge($row['range_quality'] ?? '-'); ?></td>
                                    <td data-group="reliability"><?php echo reportStatusBadge($row['connection_status'] ?? '-'); ?></td>
                                    <td data-group="reliability"><?php echo reportNumber($row['packet_loss_percent'], 2, '%'); ?></td>
                                    <td data-group="reliability"><?php echo reportNumber($row['packet_success_rate'], 2, '%'); ?></td>
                                    <td data-group="timing"><?php echo reportNumber($row['latency_ms'], 2, 'ms'); ?><?php echo ((float) $row['latency_ms'] === 0.0 && ($row['connection_status'] ?? '') !== 'connected') ? ' <span class="text-warning" title="0 berarti tidak ada hasil/timeout, bukan latency sempurna">*</span>' : ''; ?></td>
                                    <td data-group="timing"><?php echo reportNumber($row['jitter_ms'], 2, 'ms'); ?></td>
                                    <td data-group="transfer"><?php echo reportNumber($row['throughput_kbps'], 2, 'kbps'); ?></td>
                                    <td data-group="transfer"><?php echo reportNumber($row['packet_delivery_ratio_percent'], 2, '%'); ?><?php echo ((float) $row['packet_delivery_ratio_percent'] > 100) ? ' <span class="text-danger" title="PDR di atas 100% adalah anomali data">!</span>' : ''; ?></td>
                                    <td data-group="transfer"><?php echo reportNumber($row['data_loss_percent'], 2, '%'); ?></td>
                                    <td data-group="obstacle"><?php echo htmlspecialchars(ucwords((string) ($row['obstacle_type'] ?? '-'))); ?></td>
                                    <td data-group="obstacle"><?php echo htmlspecialchars($row['condition_type'] ?? '-'); ?></td>
                                    <td data-group="obstacle"><?php echo reportNumber($row['penetration_loss_db'], 2, 'dB'); ?></td>
                                    <td data-group="obstacle"><?php echo reportNumber($row['penetration_throughput_kbps'], 2, 'kbps'); ?></td>
                                    <td data-group="interference"><?php echo reportStatusBadge($row['interference_level'] ?? '-'); ?></td>
                                    <td data-group="interference"><?php echo htmlspecialchars($row['interference_source'] ?? '-'); ?></td>
                                    <td data-group="interference"><?php echo reportNumber($row['obstruction_throughput_kbps'], 2, 'kbps'); ?></td>
                                    <td data-group="interference"><?php echo reportNumber($row['obstruction_latency_ms'], 2, 'ms'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="report-data-note mt-3">
                    <strong>Cara baca:</strong> `*` pada latency 0 berarti tidak ada respons/timeout, bukan latency sempurna. `!` pada PDR &gt;100% menandai baseline ketika data diterima lebih besar daripada data dikirim. Nilai anomali tetap ditampilkan agar laporan sama dengan workbook, tetapi dikeluarkan dari ringkasan yang relevan.
                </div>
            </div>
        </section>

        <h3 class="report-section-title" id="analisis-parameter">Analisis setiap parameter<small>Bagian “temuan data” adalah hasil perhitungan database; bagian “interpretasi” adalah penjelasan teknis, bukan klaim sebab-akibat tunggal.</small></h3>

        <div class="row g-3">
            <div class="col-lg-6"><article class="report-analysis"><h5><i class="fas fa-ruler-combined text-success"></i> 1. Range dan status koneksi</h5><div class="finding">Maksimum terukur <?php echo reportNumber($maxMeasuredRow['distance_actual_meter'] ?? null, 2, 'm'); ?> (<?php echo htmlspecialchars($maxMeasuredRow['test_point_code'] ?? '-'); ?>), sedangkan maksimum connected <?php echo reportNumber($maxConnectedRow['distance_actual_meter'] ?? null, 2, 'm'); ?> (<?php echo htmlspecialchars($maxConnectedRow['test_point_code'] ?? '-'); ?>).</div><p>Perbedaan ini penting: titik terjauh membuktikan pengujian dilakukan sampai jarak tersebut, tetapi status intermittent dan loss tinggi menunjukkan link belum layak disebut stabil. Secara propagasi, rugi ruang bebas meningkat saat jarak bertambah; obstacle, difraksi, pantulan, dan kondisi NLOS menambah rugi di luar model ruang bebas. <a class="report-source-link" href="<?php echo $technicalSources['itu']['url']; ?>" target="_blank" rel="noopener">[<?php echo $technicalSources['itu']['label']; ?>]</a></p><p class="sidang"><strong>Jawaban sidang:</strong> “Jarak pengujian maksimum adalah <?php echo reportNumber($maxMeasuredRow['distance_actual_meter'] ?? null, 2, 'm'); ?>, tetapi jarak terjauh yang masih connected pada dataset adalah <?php echo reportNumber($maxConnectedRow['distance_actual_meter'] ?? null, 2, 'm'); ?>.”</p></article></div>

            <div class="col-lg-6"><article class="report-analysis"><h5><i class="fas fa-signal text-primary"></i> 2. RSSI dan SNR</h5><div class="finding">Rata-rata RSSI <?php echo reportNumber($averageRssi, 2, 'dBm'); ?>, rata-rata SNR <?php echo reportNumber($averageSnr, 2, 'dB'); ?>. RSSI terbaik <?php echo reportNumber($bestRssiRow['rssi_dbm'] ?? null, 2, 'dBm'); ?> di <?php echo htmlspecialchars($bestRssiRow['test_point_code'] ?? '-'); ?>; terlemah <?php echo reportNumber($weakestRssiRow['rssi_dbm'] ?? null, 2, 'dBm'); ?> di <?php echo htmlspecialchars($weakestRssiRow['test_point_code'] ?? '-'); ?>.</div><p>Korelasi jarak–RSSI adalah <?php echo htmlspecialchars(reportCorrelationLabel($distanceRssiCorrelation)); ?>. Nilai tidak monoton karena data berasal dari indoor, gunung, pantai, lapangan, dan outdoor dengan obstacle berbeda. Operasi sub-1 GHz mendukung jangkauan dan penetrasi yang lebih baik, tetapi tidak menghilangkan attenuation akibat bangunan, pepohonan, batu, orientasi antena, atau noise. <a class="report-source-link" href="<?php echo $technicalSources['paper']['url']; ?>" target="_blank" rel="noopener">[<?php echo $technicalSources['paper']['label']; ?>]</a></p><p class="sidang"><strong>Jawaban sidang:</strong> “Jarak berkontribusi pada pelemahan, tetapi hasil lapangan harus dibaca bersama environment dan kondisi LOS/NLOS; itu sebabnya titik 128,64 m bisa lebih kuat daripada titik yang lebih dekat.”</p></article></div>

            <div class="col-lg-6"><article class="report-analysis"><h5><i class="fas fa-triangle-exclamation text-danger"></i> 3. Packet loss dan keberhasilan</h5><div class="finding">Rata-rata packet loss <?php echo reportNumber($averageLoss, 2, '%'); ?>. Nilai tertinggi <?php echo reportNumber($highestLossRow['packet_loss_percent'] ?? null, 2, '%'); ?> di <?php echo htmlspecialchars($highestLossRow['test_point_code'] ?? '-'); ?>; korelasi jarak–loss <?php echo htmlspecialchars(reportCorrelationLabel($distanceLossCorrelation)); ?>.</div><p>Loss dapat naik ketika frame gagal diterima, link intermittent, sinyal lemah, SNR rendah, terjadi collision/retry, atau batas timeout tercapai. Namun packet loss dan data loss berasal dari skenario ukur yang berbeda, sehingga persentasenya tidak wajib sama pada satu titik.</p><p class="sidang"><strong>Jawaban sidang:</strong> “Packet loss merupakan hasil end-to-end pada traffic uji, bukan konversi langsung dari RSSI. RSSI/SNR membantu menjelaskan kondisi radio, sedangkan loss menunjukkan paket yang benar-benar tidak sampai.”</p></article></div>

            <div class="col-lg-6"><article class="report-analysis"><h5><i class="fas fa-clock text-warning"></i> 4. Latency dan jitter</h5><div class="finding">Median latency <?php echo reportNumber($medianLatency, 2, 'ms'); ?>; nilai tertinggi <?php echo reportNumber($highestLatencyRow['latency_ms'] ?? null, 2, 'ms'); ?> di <?php echo htmlspecialchars($highestLatencyRow['test_point_code'] ?? '-'); ?>. Korelasi jarak–latency <?php echo htmlspecialchars(reportCorrelationLabel($distanceLatencyCorrelation)); ?>.</div><p>Median dipakai karena nilai 59.000 ms adalah timeout dan membuat rata-rata sangat bias. Latency besar dapat memuat waktu propagasi, antrean, retry MAC, proses sistem, serta timeout aplikasi. Jitter menunjukkan perubahan delay antarpercobaan; angka 0 pada titik tanpa respons tidak boleh ditafsirkan sebagai jaringan sempurna.</p><p class="sidang"><strong>Jawaban sidang:</strong> “Delay ribuan milidetik terutama menunjukkan kualitas link/end-to-end yang buruk atau menunggu timeout; bukan delay propagasi radio murni pada jarak ratusan meter.”</p></article></div>

            <div class="col-lg-6"><article class="report-analysis"><h5><i class="fas fa-bolt text-primary"></i> 5. Throughput, PDR, dan data loss</h5><div class="finding">Rata-rata throughput <?php echo reportNumber($averageThroughput, 2, 'kbps'); ?>, rata-rata PDR valid <?php echo reportNumber($averagePdr, 2, '%'); ?>, puncak <?php echo reportNumber($peakThroughputRow['throughput_kbps'] ?? null, 2, 'kbps'); ?> di <?php echo htmlspecialchars($peakThroughputRow['test_point_code'] ?? '-'); ?>. Korelasi jarak–throughput <?php echo htmlspecialchars(reportCorrelationLabel($distanceThroughputCorrelation)); ?>.</div><p>Throughput dihitung dari data diterima per durasi. Karena ukuran transfer, durasi, bitrate/MCS, loss, dan kondisi kanal berbeda, titik 50 m dapat melebihi 25 m. IEEE 802.11ah memang dirancang untuk rentang panjang, perangkat berdaya terbatas, serta traffic kecil/tidak sering; kompromi data-rate dan jangkauan tetap ada. <a class="report-source-link" href="<?php echo $technicalSources['paper']['url']; ?>" target="_blank" rel="noopener">[<?php echo $technicalSources['paper']['label']; ?>]</a></p><p class="sidang"><strong>Jawaban sidang:</strong> “Saya tidak menyimpulkan throughput hanya dari jarak. Saya membacanya bersama PDR, data loss, durasi transfer, bitrate, dan environment pada baris matriks yang sama.”</p></article></div>

            <div class="col-lg-6"><article class="report-analysis"><h5><i class="fas fa-shield-halved text-info"></i> 6. Penetrasi dan interferensi</h5><div class="finding">Penetration loss tertinggi <?php echo reportNumber($highestPenetrationRow['penetration_loss_db'] ?? null, 2, 'dB'); ?> di <?php echo htmlspecialchars($highestPenetrationRow['test_point_code'] ?? '-'); ?>, obstacle <?php echo htmlspecialchars($highestPenetrationRow['obstacle_type'] ?? '-'); ?>, kondisi <?php echo htmlspecialchars($highestPenetrationRow['condition_type'] ?? '-'); ?>.</div><p>Sub-1 GHz membantu penetrasi dibanding band Wi-Fi yang lebih tinggi, tetapi material dan geometri lintasan tetap memberi rugi tambahan. Pohon, bangunan, batu, permukaan basah, multipath, dan sumber interferensi dapat menurunkan kualitas. Karena itu label obstacle dan LOS/NLOS harus selalu dibawa saat membandingkan jarak. <a class="report-source-link" href="<?php echo $technicalSources['alliance']['url']; ?>" target="_blank" rel="noopener">[<?php echo $technicalSources['alliance']['label']; ?>]</a></p><p class="sidang"><strong>Jawaban sidang:</strong> “Keunggulan penetrasi HaLow bersifat relatif, bukan berarti tanpa loss. Data saya masih menunjukkan tambahan loss besar pada NLOS dan obstacle tertentu.”</p></article></div>

            <div class="col-lg-6"><article class="report-analysis"><h5><i class="fas fa-battery-half text-success"></i> 7. Konsumsi daya</h5><div class="finding"><?php echo $achievedPowerCount; ?> dari <?php echo count($powerData); ?> pengujian berstatus Achieved. Rata-rata daya valid <?php echo reportNumber($averagePower, 2, 'W'); ?>; tertinggi <?php echo reportNumber($peakPowerRow['power_w'] ?? null, 2, 'W'); ?> pada <?php echo htmlspecialchars($peakPowerRow['device_id'] ?? '-'); ?>.</div><p>Daya mengikuti P = V × I dan energi mengikuti E = P × durasi. Baris 0 V/0 A berstatus Not Evaluated sehingga tidak dimasukkan ke rata-rata daya valid. Fitur hemat daya 802.11ah bergantung pada pola tidur/bangun dan traffic; pengukuran perangkat penuh juga mencakup CPU, regulator, dan komponen lain. <a class="report-source-link" href="<?php echo $technicalSources['paper']['url']; ?>" target="_blank" rel="noopener">[<?php echo $technicalSources['paper']['label']; ?>]</a></p><p class="sidang"><strong>Jawaban sidang:</strong> “Angka sekitar <?php echo reportNumber($averagePower, 2, 'W'); ?> adalah konsumsi perangkat pada skenario uji ini, bukan klaim konsumsi radio HaLow saja.”</p></article></div>

            <div class="col-lg-6"><article class="report-analysis"><h5><i class="fas fa-clipboard-check text-secondary"></i> 8. Batas interpretasi dan kualitas data</h5><div class="finding">Dua anomali utama dipertahankan: latency 0 pada timeout dan PDR 143,58% pada baseline ketika received &gt; sent.</div><p>Keduanya tidak dihapus karena laporan harus konsisten dengan workbook. Ringkasan mengeluarkan nilai yang tidak valid untuk metrik terkait. Selain itu, parameter pada satu titik merupakan rangkaian pengujian yang disatukan berdasarkan point/date/location/distance; hubungan yang terlihat adalah asosiasi lapangan, bukan bukti bahwa satu variabel menjadi penyebab tunggal.</p><p class="sidang"><strong>Jawaban sidang:</strong> “Saya menampilkan data mentah secara transparan, memberi tanda pada outlier, dan memisahkan fakta pengukuran dari interpretasi teknis.”</p></article></div>
        </div>
    <?php endif; ?>

    <h3 class="report-section-title" id="satelit-vsat">Pengujian konektivitas Master ke Satelit/VSAT<small>Bagian tambahan untuk menganalisis uji konektivitas end-to-end. Data ini tidak digabungkan ke matriks jarak karena tidak mengukur jarak WiFi HaLow.</small></h3>

    <?php if (!$satelliteData): ?>
        <section class="report-card report-empty">
            <i class="fas fa-satellite-dish fa-2x mb-3"></i>
            <h5>Data satelit tidak tersedia pada rentang tanggal ini</h5>
            <p class="mb-0">Ubah filter tanggal untuk menampilkan kembali hasil pengujian VSAT.</p>
        </section>
    <?php else: ?>
        <div class="row g-3 mb-3">
            <div class="col-6 col-lg-2"><div class="report-stat"><div class="report-stat-label">Sesi VSAT</div><div class="report-stat-value"><?php echo number_format((int) ($satelliteSummary['total_sessions'] ?? 0)); ?></div><div class="report-stat-note"><?php echo number_format((int) ($satelliteSummary['total_trials'] ?? 0)); ?> pengulangan</div></div></div>
            <div class="col-6 col-lg-2"><div class="report-stat"><div class="report-stat-label">Status lulus</div><div class="report-stat-value"><?php echo number_format((int) ($satelliteSummary['passed_trials'] ?? 0)); ?>/<?php echo number_format((int) ($satelliteSummary['total_trials'] ?? 0)); ?></div><div class="report-stat-note">Locked, Associated, dan ping berhasil</div></div></div>
            <div class="col-6 col-lg-2"><div class="report-stat"><div class="report-stat-label">Rata-rata latency</div><div class="report-stat-value"><?php echo reportNumber($satelliteSummary['avg_latency_ms'] ?? null, 3, 'ms'); ?></div><div class="report-stat-note">rata-rata dari setiap pengulangan</div></div></div>
            <div class="col-6 col-lg-2"><div class="report-stat"><div class="report-stat-label">Rentang teramati</div><div class="report-stat-value"><?php echo reportNumber($satelliteSummary['observed_latency_min_ms'] ?? null, 3, 'ms'); ?></div><div class="report-stat-note">hingga <?php echo reportNumber($satelliteSummary['observed_latency_max_ms'] ?? null, 3, 'ms'); ?></div></div></div>
            <div class="col-6 col-lg-2"><div class="report-stat"><div class="report-stat-label">Rata-rata paket</div><div class="report-stat-value"><?php echo reportNumber($satelliteSummary['avg_packet_received'] ?? null, 2); ?>/<?php echo reportNumber($satelliteSummary['avg_packet_sent'] ?? null, 2); ?></div><div class="report-stat-note">received/sent per pengulangan</div></div></div>
            <div class="col-6 col-lg-2"><div class="report-stat"><div class="report-stat-label">Packet loss</div><div class="report-stat-value"><?php echo reportNumber($satelliteSummary['avg_packet_loss_percent'] ?? null, 2, '%'); ?></div><div class="report-stat-note"><?php echo reportNumber($satellitePacketSuccessPercent, 2, '%'); ?> paket berhasil</div></div></div>
        </div>

        <section class="report-card mb-3">
            <div class="report-card-header">
                <div>
                    <h4><i class="fas fa-satellite-dish"></i> Rincian pengulangan VSAT</h4>
                    <p>Nilai min/avg/max berasal dari ringkasan terminal ping; indikator modem berasal dari dashboard Hughes.</p>
                </div>
                <a class="btn btn-outline-primary btn-sm" href="index.php?page=satellite"><i class="fas fa-arrow-up-right-from-square"></i> Buka data sumber</a>
            </div>
            <div class="report-card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered report-operational-table mb-0">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Kode sesi</th>
                                <th>Uji ke-</th>
                                <th>Penguji</th>
                                <th>Satelit</th>
                                <th>SQF</th>
                                <th>Downlink</th>
                                <th>Association</th>
                                <th>TDMA</th>
                                <th>Packet</th>
                                <th>Min.</th>
                                <th>Avg.</th>
                                <th>Maks.</th>
                                <th>Loss</th>
                                <th>Hasil</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($satelliteData as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(formatDate($row['test_date'])); ?></td>
                                    <td><strong><?php echo htmlspecialchars($row['test_session_code']); ?></strong></td>
                                    <td><?php echo (int) $row['trial_number']; ?>/<?php echo (int) $row['planned_trials']; ?></td>
                                    <td><?php echo htmlspecialchars($row['test_operator'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($row['satellite_name'] ?? '-'); ?></td>
                                    <td><?php echo reportNumber($row['signal_quality_factor'], 0); ?></td>
                                    <td><?php echo reportStatusBadge($row['vsat_lock_status'] ?? '-'); ?></td>
                                    <td><?php echo reportStatusBadge($row['association_status'] ?? '-'); ?></td>
                                    <td><?php echo reportStatusBadge($row['tdma_status'] ?? '-'); ?></td>
                                    <td><?php echo (int) $row['packet_received']; ?>/<?php echo (int) $row['packet_sent']; ?></td>
                                    <td><?php echo reportNumber($row['latency_min_ms'], 3, 'ms'); ?></td>
                                    <td><strong><?php echo reportNumber($row['latency_ms'], 3, 'ms'); ?></strong></td>
                                    <td><?php echo reportNumber($row['latency_max_ms'], 3, 'ms'); ?></td>
                                    <td><?php echo reportNumber($row['packet_loss_percent'], 2, '%'); ?></td>
                                    <td><?php echo reportStatusBadge($row['overall_status'] ?? '-'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <div class="row g-3">
            <div class="col-lg-6">
                <article class="report-analysis">
                    <h5><i class="fas fa-chart-line text-primary"></i> Analisis hasil konektivitas</h5>
                    <div class="finding">
                        <?php echo (int) ($satelliteSummary['total_packet_received'] ?? 0); ?> dari <?php echo (int) ($satelliteSummary['total_packet_sent'] ?? 0); ?> paket diterima
                        (<?php echo reportNumber($satellitePacketSuccessPercent, 2, '%'); ?>), dengan latency rata-rata <?php echo reportNumber($satelliteSummary['avg_latency_ms'] ?? null, 3, 'ms'); ?>.
                    </div>
                    <p><?php echo (int) ($satelliteSummary['zero_loss_trials'] ?? 0); ?> dari <?php echo (int) ($satelliteSummary['total_trials'] ?? 0); ?> pengulangan menghasilkan packet loss 0%. Rata-rata latency per pengulangan berada pada <?php echo reportNumber($satelliteSummary['lowest_trial_avg_latency_ms'] ?? null, 3, 'ms'); ?> sampai <?php echo reportNumber($satelliteSummary['highest_trial_avg_latency_ms'] ?? null, 3, 'ms'); ?>, atau hanya berselisih <?php echo reportNumber($satelliteTrialAverageSpan, 3, 'ms'); ?>. Hal ini menunjukkan nilai rata-rata antarpercobaan relatif konsisten pada sesi tersebut.</p>
                    <p>Latency maksimum teramati mencapai <?php echo reportNumber($satelliteSummary['observed_latency_max_ms'] ?? null, 3, 'ms'); ?> pada salah satu paket. Lonjakan sesaat ini tidak menyebabkan packet loss, tetapi menunjukkan delay per paket masih dapat berfluktuasi. Data ping hanya mengukur RTT end-to-end sehingga tidak memisahkan waktu propagasi satelit, antrean jaringan, dan pemrosesan perangkat.</p>
                </article>
            </div>
            <div class="col-lg-6">
                <article class="report-analysis">
                    <h5><i class="fas fa-circle-check text-success"></i> Bukti koneksi dan batas kesimpulan</h5>
                    <div class="finding">Dashboard mencatat satelit <?php echo htmlspecialchars($satelliteSummary['satellite_names'] ?? '-'); ?> dengan SQF <?php echo reportNumber($satelliteSummary['min_signal_quality_factor'] ?? null, 0); ?><?php echo ($satelliteSummary['min_signal_quality_factor'] ?? null) !== ($satelliteSummary['max_signal_quality_factor'] ?? null) ? '–' . reportNumber($satelliteSummary['max_signal_quality_factor'] ?? null, 0) : ''; ?>. Locked <?php echo (int) ($satelliteSummary['locked_trials'] ?? 0); ?>/<?php echo (int) ($satelliteSummary['total_trials'] ?? 0); ?>, Associated <?php echo (int) ($satelliteSummary['associated_trials'] ?? 0); ?>/<?php echo (int) ($satelliteSummary['total_trials'] ?? 0); ?>, dan TDMA Active <?php echo (int) ($satelliteSummary['active_tdma_trials'] ?? 0); ?>/<?php echo (int) ($satelliteSummary['total_trials'] ?? 0); ?>.</div>
                    <p>Gabungan status modem dan respons ping membuktikan bahwa pada rentang <?php echo htmlspecialchars(formatDate($satelliteSummary['first_test_date'] ?? '')); ?> sampai <?php echo htmlspecialchars(formatDate($satelliteSummary['last_test_date'] ?? '')); ?>, Master dapat mencapai internet melalui link VSAT. Kesimpulan ini tidak otomatis membuktikan kestabilan jangka panjang, throughput, maupun performa saat cuaca atau beban jaringan berubah.</p>
                    <p class="sidang"><strong>Jawaban sidang:</strong> “Koneksi satelit dibuktikan berlapis: modem berstatus Locked dan Associated dengan TDMA Active, lalu ping internet berhasil <?php echo (int) ($satelliteSummary['total_packet_received'] ?? 0); ?> dari <?php echo (int) ($satelliteSummary['total_packet_sent'] ?? 0); ?> paket. Rata-rata RTT dari <?php echo (int) ($satelliteSummary['total_trials'] ?? 0); ?> pengulangan adalah <?php echo reportNumber($satelliteSummary['avg_latency_ms'] ?? null, 3, 'ms'); ?> dengan packet loss <?php echo reportNumber($satelliteSummary['avg_packet_loss_percent'] ?? null, 2, '%'); ?>.”</p>
                </article>
            </div>
        </div>
    <?php endif; ?>

    <h3 class="report-section-title" id="operasional">Parameter operasional<small>Power, command, dan text message tidak memiliki sumbu jarak, sehingga ditampilkan terpisah dari matriks.</small></h3>

    <div class="row g-3">
        <div class="col-xl-6">
            <section class="report-card h-100">
                <div class="report-card-header"><div><h5><i class="fas fa-battery-half"></i> Power Consumption</h5><p><?php echo $achievedPowerCount; ?> Achieved dari <?php echo count($powerData); ?> data.</p></div></div>
                <div class="report-card-body">
                    <div class="table-responsive"><table class="table table-sm table-bordered report-operational-table"><thead><tr><th>Tanggal</th><th>Device</th><th>V</th><th>A</th><th>Power</th><th>Energy</th><th>Result</th></tr></thead><tbody>
                    <?php foreach ($powerData as $row): ?><tr><td><?php echo htmlspecialchars(formatDate($row['test_date'])); ?></td><td><?php echo htmlspecialchars($row['device_id']); ?></td><td><?php echo reportNumber($row['battery_voltage_v'],2); ?></td><td><?php echo reportNumber($row['current_a'],2); ?></td><td><?php echo reportNumber($row['power_w'],2,'W'); ?></td><td><?php echo reportNumber($row['energy_wh'],2,'Wh'); ?></td><td><?php echo reportStatusBadge($row['result'] ?? '-'); ?></td></tr><?php endforeach; ?>
                    </tbody></table></div>
                </div>
            </section>
        </div>
        <div class="col-xl-6">
            <section class="report-card h-100">
                <div class="report-card-header"><div><h5><i class="fas fa-terminal"></i> Command Execution</h5><p><?php echo $commandSuccessCount; ?> sukses dari <?php echo count($commandData); ?> command.</p></div></div>
                <div class="report-card-body">
                    <div class="table-responsive"><table class="table table-sm table-bordered report-operational-table"><thead><tr><th>Tanggal</th><th>Command</th><th>Target</th><th>Status</th><th>Delivery</th><th>Total</th></tr></thead><tbody>
                    <?php foreach ($commandData as $row): ?><tr><td><?php echo htmlspecialchars(formatDate($row['test_date'])); ?></td><td><?php echo htmlspecialchars($row['command_type'] ?? '-'); ?></td><td><?php echo htmlspecialchars($row['target_node_id'] ?? '-'); ?></td><td><?php echo reportStatusBadge($row['execution_status'] ?? '-'); ?></td><td><?php echo reportNumber($row['command_delivery_delay'],2,'ms'); ?></td><td><?php echo reportNumber($row['total_command_time'],2,'ms'); ?></td></tr><?php endforeach; ?>
                    </tbody></table></div>
                </div>
            </section>
        </div>
        <div class="col-12">
            <section class="report-card">
                <div class="report-card-header"><div><h5><i class="fas fa-comment-dots"></i> Text Message</h5><p><?php echo $messageSuccessCount; ?> sukses dari <?php echo count($textMessageData); ?> pengiriman.</p></div></div>
                <div class="report-card-body">
                    <div class="table-responsive"><table class="table table-sm table-bordered report-operational-table"><thead><tr><th>Tanggal</th><th>Dari</th><th>Ke</th><th>Pesan</th><th>Status</th><th>Latency</th></tr></thead><tbody>
                    <?php foreach ($textMessageData as $row): ?><tr><td><?php echo htmlspecialchars(formatDate($row['test_date'])); ?></td><td><?php echo htmlspecialchars($row['source_node'] ?? '-'); ?></td><td><?php echo htmlspecialchars($row['target_node_id'] ?? '-'); ?></td><td><?php echo htmlspecialchars($row['message_text'] ?? '-'); ?></td><td><?php echo reportStatusBadge($row['delivery_status'] ?? '-'); ?></td><td><?php echo reportNumber($row['latency_ms'],2,'ms'); ?></td></tr><?php endforeach; ?>
                    </tbody></table></div>
                </div>
            </section>
        </div>
    </div>

    <h3 class="report-section-title" id="referensi">Landasan teknis<small>Sumber dipakai untuk menjelaskan mekanisme umum; kesimpulan angka tetap berasal dari database penelitian ini.</small></h3>
    <section class="report-card mb-4">
        <div class="report-card-body">
            <div class="row g-3">
                <?php foreach ($technicalSources as $source): ?>
                    <div class="col-md-6 col-xl-3">
                        <a class="d-block h-100 text-decoration-none p-3 border rounded-3" href="<?php echo htmlspecialchars($source['url']); ?>" target="_blank" rel="noopener">
                            <div class="small text-primary fw-bold mb-1"><?php echo htmlspecialchars($source['label']); ?> <i class="fas fa-arrow-up-right-from-square"></i></div>
                            <div class="text-dark fw-semibold"><?php echo htmlspecialchars($source['title']); ?></div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</div>

<script>
$(function() {
    var rows = <?php echo json_encode($chartRows, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    if (!window.Chart || !rows.length) return;

    var environmentColors = {
        indoor: '#2563eb', gunung: '#16a34a', pantai: '#0891b2',
        lapangan: '#d97706', outdoor: '#7c3aed', hangar: '#dc2626', unknown: '#64748b'
    };
    var metrics = {
        rssi: { label:'RSSI', unit:'dBm', color:'#2563eb', zero:false, note:'Semakin mendekati 0 dBm berarti sinyal diterima lebih kuat. Nilai 0 pada baseline bukan pembacaan radio yang valid.' },
        snr: { label:'SNR', unit:'dB', color:'#7c3aed', zero:true, note:'SNR lebih tinggi berarti sinyal lebih dominan terhadap noise pada saat pengukuran.' },
        packet_loss: { label:'Packet Loss', unit:'%', color:'#dc2626', zero:true, percent:true, note:'Persentase paket connectivity yang tidak diterima; baca bersama status, RSSI, dan SNR.' },
        packet_success: { label:'Packet Success', unit:'%', color:'#16a34a', zero:true, percent:true, note:'Persentase paket connectivity yang diterima.' },
        latency: { label:'Latency', unit:'ms', color:'#d97706', zero:true, log:true, note:'Nilai 59.000 ms merepresentasikan timeout. Nilai 0 pada titik tanpa respons bukan latency sempurna.' },
        jitter: { label:'Jitter', unit:'ms', color:'#f59e0b', zero:true, log:true, note:'Variasi delay; nilai 0 pada timeout perlu dibaca sebagai tidak adanya sampel respons.' },
        throughput: { label:'Throughput', unit:'kbps', color:'#0891b2', zero:true, note:'Kecepatan transfer aktual dari data diterima dibagi durasi.' },
        pdr: { label:'PDR', unit:'%', color:'#16a34a', zero:true, percent:true, note:'Rasio data diterima terhadap data dikirim. Nilai di atas 100% ditandai sebagai anomali baseline.' },
        data_loss: { label:'Data Loss', unit:'%', color:'#ef4444', zero:true, percent:true, note:'Kehilangan data pada throughput test; berbeda skenario dengan packet loss connectivity.' },
        range_bitrate: { label:'Range Bitrate', unit:'kbps', color:'#1d4ed8', zero:true, note:'Bitrate link yang dicatat pada range test.' },
        penetration_loss: { label:'Penetration Loss', unit:'dB', color:'#9333ea', zero:true, note:'Selisih RSSI sebelum dan sesudah obstacle; baca bersama jenis obstacle dan LOS/NLOS.' },
        obstruction_throughput: { label:'Obstruction Throughput', unit:'kbps', color:'#0f766e', zero:true, note:'Throughput yang dicatat pada skenario obstruction/interference.' },
        obstruction_latency: { label:'Obstruction Latency', unit:'ms', color:'#b45309', zero:true, log:true, note:'Latency pada skenario obstruction/interference; nilai 0 dapat berarti tidak ada respons.' }
    };

    function isDrawable(value, config) {
        if (value === null || value === undefined || !Number.isFinite(Number(value))) return false;
        if (config.log && Number(value) <= 0) return false;
        return config.zero || Number(value) !== 0;
    }

    function point(row, key) {
        return { x: Number(row.distance), y: Number(row[key]), point:row.point, location:row.location, environment:row.environment, status:row.status };
    }

    function tooltipCallbacks(unit) {
        return {
            title: function(items) {
                if (!items.length) return '';
                var raw = items[0].raw;
                return raw.point + ' · ' + raw.x.toLocaleString('id-ID') + ' m';
            },
            label: function(context) {
                var raw = context.raw;
                return context.dataset.label + ': ' + Number(raw.y).toLocaleString('id-ID', { maximumFractionDigits:2 }) + (unit ? ' ' + unit : '');
            },
            afterLabel: function(context) {
                var raw = context.raw;
                return [raw.location, 'Environment: ' + raw.environment, 'Status: ' + (raw.status || '-')];
            }
        };
    }

    var explorer = null;
    function renderExplorer(key) {
        var config = metrics[key];
        var grouped = {};
        rows.forEach(function(row) {
            if (!isDrawable(row[key], config) || row.distance === null) return;
            var env = row.environment || 'unknown';
            if (!grouped[env]) grouped[env] = [];
            grouped[env].push(point(row, key));
        });
        var datasets = Object.keys(grouped).map(function(env) {
            var color = environmentColors[env] || environmentColors.unknown;
            return { label:env.charAt(0).toUpperCase()+env.slice(1), data:grouped[env], borderColor:color, backgroundColor:color+'B8', pointRadius:6, pointHoverRadius:9 };
        });
        if (explorer) explorer.destroy();
        explorer = new Chart(document.getElementById('reportMetricExplorer'), {
            type:'scatter', data:{datasets:datasets},
            options:{ responsive:true, maintainAspectRatio:false, interaction:{mode:'nearest',intersect:false},
                plugins:{ legend:{position:'bottom',labels:{usePointStyle:true}}, tooltip:{callbacks:tooltipCallbacks(config.unit)} },
                scales:{ x:{type:'linear',title:{display:true,text:'Jarak (m)'},beginAtZero:true}, y:{type:config.log?'logarithmic':'linear',title:{display:true,text:config.label+' ('+config.unit+')'},beginAtZero:!!config.percent,suggestedMin:config.percent?0:undefined,suggestedMax:config.percent?100:undefined} }
            }
        });
        $('#reportMetricExplanation').text(config.note + (config.log ? ' Grafik memakai skala logaritmik agar rentang besar tetap terbaca.' : ''));
    }

    function metricDataset(key, axis, labelOverride) {
        var config = metrics[key];
        return {
            label:labelOverride || config.label,
            data:rows.filter(function(row){ return row.distance !== null && isDrawable(row[key], config); }).map(function(row){ return point(row,key); }),
            borderColor:config.color, backgroundColor:config.color+'A8', yAxisID:axis || 'y', showLine:true, tension:.25, pointRadius:5, pointHoverRadius:8
        };
    }

    function createComparisonChart(id, definitions, scaleOptions) {
        var canvas = document.getElementById(id);
        if (!canvas) return;
        var datasets = definitions.map(function(def){ return metricDataset(def.key, def.axis, def.label); });
        new Chart(canvas, {
            type:'scatter', data:{datasets:datasets},
            options:{ responsive:true, maintainAspectRatio:false, interaction:{mode:'nearest',intersect:false},
                plugins:{legend:{position:'bottom',labels:{usePointStyle:true}},tooltip:{callbacks:tooltipCallbacks(scaleOptions.tooltipUnit || '')}},
                scales:scaleOptions.scales
            }
        });
    }

    renderExplorer('rssi');
    $('#reportMetricSelector').on('change', function(){ renderExplorer(this.value); });

    createComparisonChart('reportSignalChart', [{key:'rssi',axis:'yRssi'},{key:'snr',axis:'ySnr'}], {scales:{x:{type:'linear',title:{display:true,text:'Jarak (m)'}},yRssi:{type:'linear',position:'left',title:{display:true,text:'RSSI (dBm)'}},ySnr:{type:'linear',position:'right',title:{display:true,text:'SNR (dB)'},grid:{drawOnChartArea:false}}}});
    createComparisonChart('reportReliabilityChart', [{key:'packet_loss',axis:'y'},{key:'pdr',axis:'y'}], {tooltipUnit:'%',scales:{x:{type:'linear',title:{display:true,text:'Jarak (m)'}},y:{beginAtZero:true,suggestedMax:100,title:{display:true,text:'Persentase (%)'}}}});
    createComparisonChart('reportTimingChart', [{key:'latency',axis:'y'},{key:'jitter',axis:'y'}], {tooltipUnit:'ms',scales:{x:{type:'linear',title:{display:true,text:'Jarak (m)'}},y:{type:'logarithmic',title:{display:true,text:'Waktu (ms, skala log)'}}}});
    createComparisonChart('reportThroughputChart', [{key:'throughput',axis:'y'},{key:'obstruction_throughput',axis:'y'}], {tooltipUnit:'kbps',scales:{x:{type:'linear',title:{display:true,text:'Jarak (m)'}},y:{beginAtZero:true,title:{display:true,text:'Throughput (kbps)'}}}});
    createComparisonChart('reportPenetrationChart', [{key:'penetration_loss',axis:'y'}], {tooltipUnit:'dB',scales:{x:{type:'linear',title:{display:true,text:'Jarak (m)'}},y:{beginAtZero:true,title:{display:true,text:'Penetration Loss (dB)'}}}});

    $('#matrixQuickSearch').on('input', function() {
        var query = String(this.value || '').toLowerCase().trim();
        var visible = 0;
        $('#distanceMatrixTable tbody tr').each(function() {
            var show = !query || String($(this).data('matrix-search') || '').indexOf(query) !== -1;
            $(this).toggle(show);
            if (show) visible++;
        });
        $('#matrixVisibleCount').text(visible);
    });

    $('.matrix-focus-btn').on('click', function() {
        var focus = $(this).data('focus');
        $('.matrix-focus-btn').removeClass('active');
        $(this).addClass('active');
        var table = $('#distanceMatrixTable');
        table.removeClass('focus-signal focus-reliability focus-timing focus-transfer focus-obstacle focus-interference');
        if (focus !== 'all') table.addClass('focus-' + focus);
    });
});
</script>
