<?php
/**
 * Helper Functions for WiFi HaLow Testing System
 */

/**
 * Calculate packet loss
 */
function calculatePacketLoss($sent, $received) {
    if ($sent == 0) return 0;
    return round((($sent - $received) / $sent) * 100, 2);
}

/**
 * Calculate success rate
 */
function calculateSuccessRate($sent, $received) {
    if ($sent == 0) return 0;
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
    if ($timeSec == 0) return 0;
    return round(($dataKB * 1024 * 8) / ($timeSec * 1000), 2);
}

/**
 * Calculate power
 */
function calculatePower($voltage, $current) {
    return round($voltage * $current, 2);
}

/**
 * Calculate energy
 */
function calculateEnergy($power, $duration) {
    return round($power * $duration, 4);
}

/**
 * Calculate battery capacity in Wh
 */
function calculateBatteryCapacityWh($voltage, $capacitymAh) {
    return round(($voltage * $capacitymAh) / 1000, 4);
}

/**
 * Calculate estimated runtime
 */
function calculateRuntime($capacityWh, $powerW) {
    if ($powerW == 0) return 0;
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
        'connected' => 'success',
        'stable' => 'success',
        'secure' => 'success',
        'normal' => 'info',
        'moderate' => 'info',
        'warning' => 'warning',
        'degraded' => 'warning',
        'poor' => 'danger',
        'bad' => 'danger',
        'fail' => 'danger',
        'disconnected' => 'danger',
        'critical' => 'danger',
        'insecure' => 'danger',
        'slow' => 'danger'
    ];
    
    $color = isset($colors[strtolower($status)]) ? $colors[strtolower($status)] : 'secondary';
    return "<span class=\"badge bg-{$color}\">{$status}</span>";
}
