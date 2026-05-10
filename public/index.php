<?php
/**
 * Main Entry Point for WiFi HaLow Testing System
 * Design and Implementation of a Wi-Fi HaLow-Based Tactical Monitoring and Communication Support System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Helpers/functions.php';
require_once __DIR__ . '/../app/Controllers/LoginController.php';
require_once __DIR__ . '/../app/Controllers/DashboardController.php';

// Initialize session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function renderView($path, $data = []) {
    if (!file_exists($path)) {
        return '<div class="content-section"><h5>Halaman belum tersedia</h5><p class="text-muted mb-0">File view untuk halaman ini belum dibuat.</p></div>';
    }

    extract($data, EXTR_SKIP);
    ob_start();
    include $path;
    return ob_get_clean();
}

$loginController = new LoginController($pdo);

// Handle login/logout
if (isset($_GET['action'])) {
    if ($_GET['action'] === 'login') {
        $loginController->login();
    } elseif ($_GET['action'] === 'logout') {
        $loginController->logout();
    }
}

// Check authentication
if (!isLoggedIn()) {
    $loginController->index();
    exit;
}

// Initialize controllers
$dashboardController = new DashboardController($pdo);

// Include the requested page
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

switch ($page) {
    case 'dashboard':
        $title = 'Dashboard';
        $subtitle = 'Overview of all test results';
        $stats = $dashboardController->getStats();
        $recentTests = $dashboardController->getRecentTests();
        $chartData = $dashboardController->getChartData();
        $content = renderView(__DIR__ . '/../resources/views/pages/dashboard.php', compact('stats', 'recentTests', 'chartData'));
        break;
    
    case 'connectivity':
        $title = 'Connectivity Tests';
        $content = renderView(__DIR__ . '/../resources/views/pages/connectivity.php');
        break;
    
    case 'range':
        $title = 'Range Tests';
        $content = renderView(__DIR__ . '/../resources/views/pages/range.php');
        break;
    
    case 'penetration':
        $title = 'Signal Penetration Tests';
        $content = renderView(__DIR__ . '/../resources/views/pages/penetration.php');
        break;
    
    case 'latency':
        $title = 'Latency Tests';
        $content = renderView(__DIR__ . '/../resources/views/pages/latency.php');
        break;
    
    case 'throughput':
        $title = 'Throughput Tests';
        $content = renderView(__DIR__ . '/../resources/views/pages/throughput.php');
        break;
    
    case 'interference':
        $title = 'Interference Tests';
        $content = renderView(__DIR__ . '/../resources/views/pages/interference.php');
        break;
    
    case 'camera':
        $title = 'Camera Tests';
        $content = renderView(__DIR__ . '/../resources/views/pages/camera.php');
        break;
    
    case 'power':
        $title = 'Power Tests';
        $content = renderView(__DIR__ . '/../resources/views/pages/power.php');
        break;
    
    case 'master-config':
        $title = 'Master Web Configuration';
        $subtitle = 'WiFi HaLow Master device panel';
        $content = renderView(__DIR__ . '/../resources/views/pages/master_config.php');
        break;
    
    case 'command':
        $title = 'Command Execution';
        $content = renderView(__DIR__ . '/../resources/views/pages/command.php');
        break;

    case 'text-communication':
        $title = 'Text Communication';
        $subtitle = 'Master to slave message delivery';
        $content = renderView(__DIR__ . '/../resources/views/pages/text_communication.php');
        break;
    
    case 'response':
        $title = 'Response Time';
        $content = renderView(__DIR__ . '/../resources/views/pages/response.php');
        break;
    
    case 'encryption':
        $title = 'Encryption Tests';
        $content = renderView(__DIR__ . '/../resources/views/pages/encryption.php');
        break;
    
    case 'analysis':
        $title = 'Analysis and Discussion';
        $content = renderView(__DIR__ . '/../resources/views/pages/analysis.php');
        break;
    
    case 'reports':
        $title = 'Generated Reports';
        $content = renderView(__DIR__ . '/../resources/views/pages/reports.php');
        break;
    
    default:
        $title = 'Dashboard';
        $stats = $dashboardController->getStats();
        $recentTests = $dashboardController->getRecentTests();
        $chartData = $dashboardController->getChartData();
        $content = renderView(__DIR__ . '/../resources/views/pages/dashboard.php', compact('stats', 'recentTests', 'chartData'));
        break;
}

include __DIR__ . '/../resources/views/layouts/app.php';
