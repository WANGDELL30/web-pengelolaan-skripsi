<?php
$currentRole = currentUserRole();
$canManageProject = canManageProject();
$roleBadgeClass = $canManageProject ? 'danger' : ($currentRole === 'viewer' ? 'secondary' : 'info');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'WiFi HaLow Testing System'; ?> - Sistem Monitoring & Komunikasi Taktis</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <?php if (($_GET['page'] ?? '') === 'range'): ?>
        <link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" rel="stylesheet">
    <?php endif; ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
    <script>
        if (window.Chart) {
            Chart.defaults.color = '#4b5563';
            Chart.defaults.borderColor = 'rgba(30, 60, 114, 0.14)';
            Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
        }
    </script>
    <link rel="stylesheet" href="css/style.css?v=20260511-sidebar-fix">
    <style>
        :root {
            --navy: #1e3c72;
            --navy-light: #2a5298;
            --navy-dark: #1a2f5a;
            --gray: #6c757d;
            --gray-light: #f8f9fa;
            --green: #28a745;
            --orange: #fd7e14;
            --white: #ffffff;
            --sidebar-width: 280px;
            --sidebar-min-width: 280px;
            --sidebar-max-width: 280px;
        }
        
        body {
            background-color: #f5f6fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }
        
        .sidebar {
            background: linear-gradient(180deg, var(--navy) 0%, var(--navy-dark) 100%);
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: var(--sidebar-width);
            min-width: var(--sidebar-width);
            max-width: var(--sidebar-width);
            z-index: 1040;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            transition: width 0.16s ease, transform 0.3s ease;
        }

        body.sidebar-hidden .sidebar {
            transform: translateX(-100%);
            pointer-events: none;
        }

        .sidebar.is-resizing {
            transition: none;
        }

        body.sidebar-resizing {
            cursor: ew-resize;
            user-select: none;
        }

        body.sidebar-resizing * {
            cursor: ew-resize !important;
        }

        .sidebar-nav-scroll {
            display: block !important;
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
            padding-bottom: 24px !important;
            scrollbar-gutter: stable;
            column-count: auto !important;
            column-width: auto !important;
            columns: auto !important;
        }

        .sidebar-nav-scroll > .nav-link,
        .sidebar-nav-scroll > .sidebar-heading {
            display: flex;
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
            break-inside: avoid;
            float: none;
            clear: both;
        }

        .sidebar-nav-scroll > .sidebar-heading {
            display: block;
        }

        .sidebar-nav-scroll::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar-nav-scroll::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.08);
        }

        .sidebar-nav-scroll::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.28);
            border-radius: 999px;
        }

        .sidebar-resizer {
            display: none;
        }

        .sidebar-resizer::after {
            display: none;
        }

        .sidebar-resizer::before {
            display: none;
        }

        .sidebar-resizer:hover::after,
        .sidebar.is-resizing .sidebar-resizer::after {
            opacity: 1;
        }
        
        .main-content {
            margin-left: var(--sidebar-width, 240px);
            min-height: 100vh;
            padding: 16px 20px 24px;
            transition: margin-left 0.16s ease;
            width: calc(100% - var(--sidebar-width, 240px));
            position: relative;
            z-index: auto;
        }

        body.sidebar-hidden .main-content {
            margin-left: 0;
            width: 100%;
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            border-radius: 8px;
            margin: 4px 0;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            white-space: nowrap;
            min-width: 0;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: var(--white);
            background: rgba(255,255,255,0.1);
        }
        
        .sidebar .nav-link i {
            width: 24px;
            flex: 0 0 24px;
            text-align: center;
            margin-right: 12px;
        }
        
        .header {
            background: var(--white);
            padding: 14px 18px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            min-height: 64px;
        }

        .header-title-block {
            min-width: 0;
        }

        .header-title-block h4 {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .layout-toggle-btn {
            width: 40px;
            height: 40px;
            border: 0;
            border-radius: 8px;
            color: var(--navy);
            background: #edf2f7;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
        }

        .layout-toggle-btn:hover {
            background: #dde7f3;
        }
        
        .card {
            border: none;
            border-radius: 12px;
            background: var(--white);
            color: #212529;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            transition: all 0.3s;
        }

        .text-gray-800 {
            color: #1f2937 !important;
        }

        .text-xs {
            font-size: 0.78rem;
            letter-spacing: 0;
        }

        .card .text-xs {
            color: #4b5563;
        }
        
        .card:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .stat-card {
            border-left: 4px solid var(--navy);
        }
        
        .stat-card.green { border-left-color: var(--green); }
        .stat-card.orange { border-left-color: var(--orange); }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-stable { background: #d4edda; color: #155724; }
        .status-degraded { background: #fff3cd; color: #856404; }
        .status-critical { background: #f8d7da; color: #721c24; }
        
        .chart-container {
            position: relative;
            width: 100%;
            min-height: 280px;
            height: clamp(280px, 38vh, 420px);
            margin-top: 12px;
            padding: 12px;
            background: var(--white);
            border: 1px solid #e9ecef;
            border-radius: 10px;
            overflow: hidden;
        }

        .chart-container canvas {
            display: block;
            width: 100% !important;
            height: 100% !important;
            background: var(--white);
            border-radius: 8px;
        }
        
        .data-table {
            border-radius: 12px;
            overflow: hidden;
        }
        
        .data-table th {
            background: var(--navy);
            color: var(--white);
            border: none;
            padding: 15px;
        }
        
        .data-table td {
            padding: 12px 15px;
        }

        .table-action-buttons {
            min-width: 118px;
        }

        .table-action-buttons .btn {
            width: 32px;
            height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            margin-left: 4px;
        }

        .modal {
            z-index: 1060;
        }

        .modal-backdrop {
            z-index: 1050;
        }

        .modal-dialog {
            max-width: var(--bs-modal-width);
        }

        .modal-dialog-scrollable .modal-content {
            max-height: calc(100vh - 32px);
        }

        .modal-dialog-scrollable .modal-content > form {
            display: flex;
            flex-direction: column;
            max-height: calc(100vh - 32px);
            min-height: 0;
        }

        .modal-dialog-scrollable .modal-content > form .modal-header,
        .modal-dialog-scrollable .modal-content > form .modal-footer {
            flex: 0 0 auto;
        }

        .modal-dialog-scrollable .modal-content > form .modal-body {
            min-height: 0;
            overflow-y: auto;
        }

        .modal-dialog-scrollable .modal-footer {
            background: var(--white);
            box-shadow: 0 -8px 18px rgba(15, 23, 42, 0.08);
        }

        .modal-header .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
            opacity: 0.9;
        }
        
        .sidebar-brand {
            padding: 20px;
            color: var(--white);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            flex: 0 0 auto;
            z-index: 2;
            background: var(--navy);
        }

        .sidebar-tool-btn,
        .sidebar-close-btn {
            width: 36px;
            height: 36px;
            border: 0;
            border-radius: 8px;
            color: var(--white);
            background: rgba(255,255,255,0.12);
            align-items: center;
            justify-content: center;
        }

        .sidebar-tool-btn {
            display: inline-flex;
        }

        .sidebar-tool-btn:hover,
        .sidebar-close-btn:hover {
            background: rgba(255,255,255,0.2);
        }

        .sidebar-close-btn {
            display: none;
        }

        .mobile-menu-btn {
            display: none;
            position: fixed;
            top: 14px;
            left: 14px;
            z-index: 1050;
            width: 42px;
            height: 42px;
            border: 0;
            border-radius: 8px;
            color: var(--white);
            background: var(--navy);
            box-shadow: 0 8px 20px rgba(0,0,0,0.18);
            align-items: center;
            justify-content: center;
        }

        .sidebar-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.45);
            z-index: 1035;
        }
        
        .sidebar-brand h3 {
            margin: 0;
            font-size: 18px;
        }
        
        .sidebar-brand p {
            margin: 0;
            font-size: 12px;
            opacity: 0.7;
        }

        .sidebar-heading {
            position: relative;
            z-index: auto;
            padding-top: 10px !important;
            padding-bottom: 6px !important;
            background: transparent;
            border-radius: 4px;
        }
        
        .content-section {
            background: var(--white);
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 25px;
        }
        
        @media (max-width: 768px) {
            body.sidebar-hidden .sidebar {
                transform: translateX(-100%);
                pointer-events: auto;
            }

            .sidebar {
                transform: translateX(-100%);
                position: fixed;
                z-index: 1040;
                width: min(320px, 88vw);
                min-width: 0;
                max-width: min(320px, 88vw);
                height: 100vh;
                top: 0;
                bottom: 0;
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .sidebar-resizer,
            .sidebar-tool-btn {
                display: none;
            }

            .sidebar-close-btn,
            .mobile-menu-btn {
                display: inline-flex;
            }

            .sidebar-backdrop.show {
                display: block;
            }

            body.sidebar-open {
                overflow: hidden;
            }

            .main-content {
                margin-left: 0;
                width: 100%;
                padding-top: 70px;
            }

            .chart-container {
                height: 320px;
                min-height: 320px;
                padding: 8px;
            }

            .modal-dialog {
                margin: 8px;
            }

            .modal-dialog-scrollable .modal-content,
            .modal-dialog-scrollable .modal-content > form {
                max-height: calc(100vh - 16px);
            }

            .modal-footer {
                display: flex;
                flex-wrap: nowrap;
                gap: 8px;
            }

            .modal-footer .btn {
                flex: 1;
                min-width: 0;
            }
        }
    </style>
</head>
<body>
    <button type="button" class="mobile-menu-btn" aria-label="Buka menu">
        <i class="fas fa-bars"></i>
    </button>
    <div class="sidebar-backdrop"></div>

    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="d-flex align-items-start justify-content-between gap-3">
                <div>
                    <h3><i class="fas fa-satellite"></i> WiFi HaLow</h3>
                    <p>Military Tactical System</p>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="sidebar-tool-btn sidebar-hide-btn" aria-label="Mode full width" title="Mode full width">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                    <button type="button" class="sidebar-close-btn" aria-label="Tutup menu">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
        <nav class="p-3 sidebar-nav-scroll">
            <a href="index.php" class="nav-link <?php echo empty($_GET['page']) || $_GET['page'] === 'dashboard' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <div class="sidebar-heading text-white-50 small mt-3 px-3">KONFIGURASI MASTER</div>
            <a href="index.php?page=master-config" class="nav-link <?php echo ($_GET['page'] ?? '') === 'master-config' ? 'active' : ''; ?>">
                <i class="fas fa-sliders"></i> Master Config
            </a>

            <div class="sidebar-heading text-white-50 small mt-3 px-3">PENGUJIAN KOMUNIKASI</div>
            <a href="index.php?page=connectivity" class="nav-link <?php echo ($_GET['page'] ?? '') === 'connectivity' ? 'active' : ''; ?>">
                <i class="fas fa-link"></i> Connectivity Test
            </a>
            <a href="index.php?page=range" class="nav-link <?php echo ($_GET['page'] ?? '') === 'range' ? 'active' : ''; ?>">
                <i class="fas fa-ruler"></i> Range Test
            </a>
            <a href="index.php?page=penetration" class="nav-link <?php echo ($_GET['page'] ?? '') === 'penetration' ? 'active' : ''; ?>">
                <i class="fas fa-shield-alt"></i> Penetration Test
            </a>
            <a href="index.php?page=latency" class="nav-link <?php echo ($_GET['page'] ?? '') === 'latency' ? 'active' : ''; ?>">
                <i class="fas fa-clock"></i> Latency Test
            </a>
            <a href="index.php?page=throughput" class="nav-link <?php echo ($_GET['page'] ?? '') === 'throughput' ? 'active' : ''; ?>">
                <i class="fas fa-bolt"></i> Throughput Test
            </a>
            <a href="index.php?page=interference" class="nav-link <?php echo ($_GET['page'] ?? '') === 'interference' ? 'active' : ''; ?>">
                <i class="fas fa-wifi"></i> Interference Test
            </a>
            
            <div class="sidebar-heading text-white-50 small mt-3 px-3">PERANGKAT & KONFIGURASI</div>
            <a href="index.php?page=camera" class="nav-link <?php echo ($_GET['page'] ?? '') === 'camera' ? 'active' : ''; ?>">
                <i class="fas fa-video"></i> Camera Test
            </a>
            <a href="index.php?page=power" class="nav-link <?php echo ($_GET['page'] ?? '') === 'power' ? 'active' : ''; ?>">
                <i class="fas fa-battery-full"></i> Power Test
            </a>
            
            <div class="sidebar-heading text-white-50 small mt-3 px-3">KONTROL KOMUNIKASI</div>
            <a href="index.php?page=text-communication" class="nav-link <?php echo ($_GET['page'] ?? '') === 'text-communication' ? 'active' : ''; ?>">
                <i class="fas fa-comments"></i> Text Communication
            </a>
            <a href="index.php?page=command" class="nav-link <?php echo ($_GET['page'] ?? '') === 'command' ? 'active' : ''; ?>">
                <i class="fas fa-terminal"></i> Command Execution
            </a>
            <a href="index.php?page=response" class="nav-link <?php echo ($_GET['page'] ?? '') === 'response' ? 'active' : ''; ?>">
                <i class="fas fa-stopwatch"></i> Response Time
            </a>
            
            <div class="sidebar-heading text-white-50 small mt-3 px-3">KEAMANAN</div>
            <a href="index.php?page=encryption" class="nav-link <?php echo ($_GET['page'] ?? '') === 'encryption' ? 'active' : ''; ?>">
                <i class="fas fa-lock"></i> Encryption
            </a>
            
            <div class="sidebar-heading text-white-50 small mt-3 px-3">ANALISIS</div>
            <a href="index.php?page=analysis" class="nav-link <?php echo ($_GET['page'] ?? '') === 'analysis' ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i> Analysis
            </a>
            <a href="index.php?page=mesh-simulation" class="nav-link <?php echo ($_GET['page'] ?? '') === 'mesh-simulation' ? 'active' : ''; ?>">
                <i class="fas fa-diagram-project"></i> Mesh Simulation
            </a>
            <a href="index.php?page=jamming-simulation" class="nav-link <?php echo ($_GET['page'] ?? '') === 'jamming-simulation' ? 'active' : ''; ?>">
                <i class="fas fa-shield-alt"></i> Jamming Simulation
            </a>
            <a href="index.php?page=reports" class="nav-link <?php echo ($_GET['page'] ?? '') === 'reports' ? 'active' : ''; ?>">
                <i class="fas fa-file-pdf"></i> Reports
            </a>

            <?php if ($currentRole === 'admin'): ?>
                <div class="sidebar-heading text-white-50 small mt-3 px-3">ADMIN</div>
                <a href="index.php?page=users" class="nav-link <?php echo ($_GET['page'] ?? '') === 'users' ? 'active' : ''; ?>">
                    <i class="fas fa-users-cog"></i> Users
                </a>
            <?php endif; ?>
            
            <a href="?action=logout" class="nav-link mt-4" style="background: rgba(220,53,69,0.2);">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
        <div class="sidebar-resizer" role="separator" aria-label="Geser untuk mengubah lebar sidebar"></div>
    </div>
    
    <div class="main-content" id="mainContent">
        <div class="header">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3 header-title-block">
                    <button type="button" class="layout-toggle-btn" aria-label="Tampilkan atau sembunyikan sidebar" title="Tampilkan atau sembunyikan sidebar">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="header-title-block">
                        <h4 class="mb-0"><?php echo $title ?? 'Dashboard'; ?></h4>
                        <?php if (!empty($subtitle)): ?>
                            <small class="text-muted"><?php echo $subtitle; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="d-flex align-items-center">
                    <span class="me-3">
                        <i class="fas fa-user-circle"></i>
                        <?php echo $_SESSION['full_name'] ?? ($_SESSION['username'] ?? 'User'); ?>
                    </span>
                    <span class="badge bg-<?php echo $roleBadgeClass; ?>">
                        <?php echo strtoupper($currentRole ?? 'user'); ?>
                    </span>
                </div>
            </div>
        </div>
        
        <div class="content">
            <?php if (!$canManageProject): ?>
                <div class="alert alert-secondary d-flex align-items-center gap-2" role="alert">
                    <i class="fas fa-eye"></i>
                    <span>Mode viewer aktif. Role ini hanya bisa melihat data; input, edit, hapus, command, dan konfigurasi perangkat dinonaktifkan.</span>
                </div>
            <?php endif; ?>
            <?php echo $content ?? ''; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php if (($_GET['page'] ?? '') === 'range'): ?>
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <?php endif; ?>
    <script src="js/main.js?v=20260511-sidebar-fix"></script>
</body>
</html>
