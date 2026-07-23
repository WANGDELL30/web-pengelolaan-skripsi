<?php
/**
 * Helper Functions for WiFi HaLow Testing System
 */

/**
 * Calculate packet loss
 */
function calculatePacketLoss($sent, $received) {
    if ($sent === null || $received === null || $sent === '' || $received === '') return null;
    if (!is_numeric($sent) || !is_numeric($received)) return null;

    $sent = (float) $sent;
    $received = (float) $received;

    if ($sent <= 0 || $received < 0 || $received > $sent) return null;
    return round((($sent - $received) / $sent) * 100, 2);
}

/**
 * Calculate success rate
 */
function calculateSuccessRate($sent, $received) {
    if ($sent === null || $received === null || $sent === '' || $received === '') return null;
    if (!is_numeric($sent) || !is_numeric($received)) return null;

    $sent = (float) $sent;
    $received = (float) $received;

    if ($sent <= 0 || $received < 0 || $received > $sent) return null;
    return round(($received / $sent) * 100, 2);
}

/**
 * Calculate 3D distance
 */
function calculate3DDistance($x, $y, $z) {
    return round(sqrt(pow($x, 2) + pow($y, 2) + pow($z, 2)), 2);
}

/**
 * Calculate FSPL (Free Space Path Loss)
 */
function calculateFSPL($frequencyMHz, $distanceKm) {
    return round(32.44 + 20 * log10($frequencyMHz) + 20 * log10($distanceKm), 2);
}

/**
 * Calculate signal margin
 */
function calculateSignalMargin($rssi, $sensitivity = -90) {
    return round($rssi - $sensitivity, 2);
}

/**
 * Determine range test status
 */
function determineRangeStatus($snr, $packetLoss) {
    if ($snr === null || $packetLoss === null || $snr === '' || $packetLoss === '') return null;

    if ($snr > 20 && $packetLoss < 5) {
        return 'good';
    } elseif ($snr >= 10 && $snr <= 20) {
        return 'moderate';
    } else {
        return 'poor';
    }
}

/**
 * Determine connection quality
 */
function determineConnectionQuality($rssi) {
    if ($rssi === null || $rssi === '' || !is_numeric($rssi)) return 'N/A';

    if ($rssi >= -50) return 'Excellent';
    if ($rssi >= -60) return 'Good';
    if ($rssi >= -70) return 'Fair';
    if ($rssi >= -80) return 'Weak';
    return 'Poor';
}

/**
 * Calculate throughput
 */
function calculateThroughput($dataKB, $timeSec) {
    if ($dataKB === null || $timeSec === null || $dataKB === '' || $timeSec === '') return null;
    if (!is_numeric($dataKB) || !is_numeric($timeSec)) return null;

    $dataKB = (float) $dataKB;
    $timeSec = (float) $timeSec;

    if ($timeSec <= 0 || $dataKB < 0) return null;
    return round(($dataKB * 1024 * 8) / ($timeSec * 1000), 2);
}

/**
 * Calculate power
 */
function calculatePower($voltage, $current) {
    if ($voltage === null || $current === null || $voltage === '' || $current === '') return null;
    if (!is_numeric($voltage) || !is_numeric($current)) return null;

    $voltage = (float) $voltage;
    $current = (float) $current;

    if ($voltage < 0 || $current < 0) return null;
    return round($voltage * $current, 2);
}

/**
 * Calculate energy
 */
function calculateEnergy($power, $duration) {
    if ($power === null || $duration === null || $power === '' || $duration === '') return null;
    if (!is_numeric($power) || !is_numeric($duration)) return null;

    $power = (float) $power;
    $duration = (float) $duration;

    if ($power < 0 || $duration < 0) return null;
    return round($power * $duration, 4);
}

/**
 * Calculate battery capacity in Wh
 */
function calculateBatteryCapacityWh($voltage, $capacitymAh) {
    if ($voltage === null || $capacitymAh === null || $voltage === '' || $capacitymAh === '') return null;
    if (!is_numeric($voltage) || !is_numeric($capacitymAh)) return null;

    $voltage = (float) $voltage;
    $capacitymAh = (float) $capacitymAh;

    if ($voltage <= 0 || $capacitymAh <= 0) return null;
    return round(($voltage * $capacitymAh) / 1000, 4);
}

/**
 * Calculate estimated runtime
 */
function calculateRuntime($capacityWh, $powerW) {
    if ($capacityWh === null || $powerW === null || $capacityWh === '' || $powerW === '') return null;
    if (!is_numeric($capacityWh) || !is_numeric($powerW)) return null;

    $capacityWh = (float) $capacityWh;
    $powerW = (float) $powerW;

    if ($capacityWh <= 0 || $powerW <= 0) return null;
    return round($capacityWh / $powerW, 2);
}

/**
 * Determine camera quality category
 */
function determineCameraQuality($score) {
    if ($score >= 4) return 'good';
    if ($score >= 3) return 'moderate';
    return 'poor';
}

/**
 * Determine system status
 */
function determineSystemStatus($metrics) {
    $criticalCount = 0;
    $degradedCount = 0;
    
    if (isset($metrics['avg_latency']) && $metrics['avg_latency'] > 500) $criticalCount++;
    if (isset($metrics['avg_throughput']) && $metrics['avg_throughput'] < 100) $degradedCount++;
    if (isset($metrics['avg_packet_loss']) && $metrics['avg_packet_loss'] > 20) $criticalCount++;
    if (isset($metrics['avg_rssi']) && $metrics['avg_rssi'] < -85) $degradedCount++;
    
    if ($criticalCount > 0) return 'critical';
    if ($degradedCount > 2) return 'degraded';
    return 'stable';
}

/**
 * Format date for display
 */
function formatDate($date) {
    return date('d M Y', strtotime($date));
}

function formatNullableNumber($value, $decimals = 2, $suffix = '') {
    if ($value === null || $value === '' || !is_numeric($value)) {
        return 'N/A';
    }

    return number_format((float) $value, $decimals) . $suffix;
}

/**
 * Generate random color for charts
 */
function generateChartColor($alpha = 1) {
    $colors = [
        "rgba(0, 123, 255, $alpha)",
        "rgba(40, 167, 69, $alpha)",
        "rgba(255, 193, 7, $alpha)",
        "rgba(220, 53, 69, $alpha)",
        "rgba(108, 117, 125, $alpha)",
        "rgba(13, 110, 253, $alpha)",
        "rgba(23, 162, 184, $alpha)",
        "rgba(102, 51, 153, $alpha)"
    ];
    return $colors[array_rand($colors)];
}

/**
 * Redirect to URL
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check user role
 */
function checkRole($requiredRole) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $requiredRole;
}

/**
 * Get current user role
 */
function currentUserRole() {
    return $_SESSION['user_role'] ?? null;
}

/**
 * Check read-only viewer state
 */
function isViewerRole() {
    return currentUserRole() === 'viewer';
}

/**
 * Roles allowed to create, update, delete, send commands, or change device config.
 */
function canManageProject() {
    return isLoggedIn() && !isViewerRole();
}

/**
 * Sanitize input
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * Convert bytes to human readable format
 */
function bytesToHuman($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * Get status badge HTML
 */
function getStatusBadge($status) {
    $colors = [
        'good' => 'success',
        'excellent' => 'success',
        'success' => 'success',
        'passed' => 'success',
        'connected' => 'success',
        'locked' => 'success',
        'stable' => 'success',
        'secure' => 'success',
        'normal' => 'info',
        'moderate' => 'info',
        'partial' => 'warning',
        'not_checked' => 'secondary',
        'not_tested' => 'secondary',
        'warning' => 'warning',
        'degraded' => 'warning',
        'poor' => 'danger',
        'bad' => 'danger',
        'fail' => 'danger',
        'failed' => 'danger',
        'unlocked' => 'danger',
        'disconnected' => 'danger',
        'critical' => 'danger',
        'insecure' => 'danger',
        'slow' => 'danger'
    ];
    
    $color = isset($colors[strtolower($status)]) ? $colors[strtolower($status)] : 'secondary';
    return "<span class=\"badge bg-{$color}\">{$status}</span>";
}
