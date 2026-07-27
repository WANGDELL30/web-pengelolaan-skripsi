<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0f172a">
    <title>Login - WiFi HaLow Testing System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        :root {
            --military-bg: #0f172a;
            --military-panel: #111827;
            --military-panel-2: #16213d;
            --military-field: #0b1224;
            --military-line: rgba(42, 82, 152, 0.26);
            --military-olive: #1e3c72;
            --military-olive-bright: #2a5298;
            --military-amber: #d6b35a;
            --military-text: #eef4ff;
            --military-muted: #a8b5c7;
            --military-danger: #c2554f;
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            color: var(--military-text);
            background:
                linear-gradient(rgba(15, 23, 42, 0.86), rgba(8, 15, 29, 0.95)),
                repeating-linear-gradient(0deg, transparent 0, transparent 31px, rgba(42, 82, 152, 0.1) 32px),
                repeating-linear-gradient(90deg, transparent 0, transparent 31px, rgba(42, 82, 152, 0.1) 32px),
                radial-gradient(circle at 50% 42%, rgba(42, 82, 152, 0.24), transparent 42%),
                var(--military-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            overflow-x: hidden;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body::before,
        body::after {
            content: '';
            position: fixed;
            pointer-events: none;
            inset: 0;
        }

        body::before {
            background:
                linear-gradient(90deg, transparent 0 49%, rgba(214, 179, 90, 0.18) 49.8% 50.2%, transparent 51%),
                linear-gradient(0deg, transparent 0 49%, rgba(214, 179, 90, 0.12) 49.8% 50.2%, transparent 51%);
            opacity: 0.45;
        }

        body::after {
            border: 1px solid rgba(42, 82, 152, 0.28);
            inset: 16px;
        }

        /* Military Corner Markers */
        .corner-marker {
            position: fixed;
            width: 60px;
            height: 60px;
            pointer-events: none;
            z-index: 100;
        }

        .corner-marker::before,
        .corner-marker::after {
            content: '';
            position: absolute;
            background: var(--military-amber);
            opacity: 0.6;
        }

        .corner-marker.top-left { top: 24px; left: 24px; }
        .corner-marker.top-left::before { width: 35px; height: 2px; top: 0; left: 0; }
        .corner-marker.top-left::after { width: 2px; height: 35px; top: 0; left: 0; }

        .corner-marker.top-right { top: 24px; right: 24px; }
        .corner-marker.top-right::before { width: 35px; height: 2px; top: 0; right: 0; }
        .corner-marker.top-right::after { width: 2px; height: 35px; top: 0; right: 0; }

        .corner-marker.bottom-left { bottom: 24px; left: 24px; }
        .corner-marker.bottom-left::before { width: 35px; height: 2px; bottom: 0; left: 0; }
        .corner-marker.bottom-left::after { width: 2px; height: 35px; bottom: 0; left: 0; }

        .corner-marker.bottom-right { bottom: 24px; right: 24px; }
        .corner-marker.bottom-right::before { width: 35px; height: 2px; bottom: 0; right: 0; }
        .corner-marker.bottom-right::after { width: 2px; height: 35px; bottom: 0; right: 0; }

        /* Chevron Pattern */
        .chevron-strip {
            position: fixed;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 6px;
            z-index: 100;
        }

        .chevron-strip.top { top: 28px; }
        .chevron-strip.bottom { bottom: 28px; }

        .chevron {
            width: 16px;
            height: 8px;
            position: relative;
        }

        .chevron::before,
        .chevron::after {
            content: '';
            position: absolute;
            width: 10px;
            height: 2px;
            background: var(--military-amber);
            opacity: 0.5;
        }

        .chevron::before { transform: rotate(45deg); left: 0; }
        .chevron::after { transform: rotate(-45deg); right: 0; }
        .chevron-strip.bottom .chevron::before { transform: rotate(-45deg); }
        .chevron-strip.bottom .chevron::after { transform: rotate(45deg); }

        /* Classification Banner */
        .classification-banner {
            position: fixed;
            top: 50px;
            left: 50%;
            transform: translateX(-50%);
            padding: 4px 30px;
            border: 1px solid rgba(214, 179, 90, 0.4);
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--military-amber);
            background: rgba(10, 18, 35, 0.9);
            z-index: 100;
        }

        /* Status Bar Top */
        .status-bar-top {
            position: fixed;
            top: 20px;
            left: 100px;
            display: flex;
            align-items: center;
            gap: 20px;
            font-size: 0.68rem;
            font-weight: 600;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--military-muted);
            z-index: 100;
        }

        .status-bar-top .status-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .status-bar-top .status-dot {
            width: 6px;
            height: 6px;
            background: #22c55e;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        .status-bar-top-right {
            position: fixed;
            top: 20px;
            right: 100px;
            font-size: 0.68rem;
            font-weight: 600;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--military-muted);
            z-index: 100;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }

        /* Tactical Info Sides */
        .tactical-info-left {
            position: fixed;
            left: 20px;
            top: 50%;
            transform: translateY(-50%) rotate(-90deg);
            font-size: 0.62rem;
            font-weight: 600;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: var(--military-muted);
            opacity: 0.6;
            white-space: nowrap;
            z-index: 100;
        }

        .tactical-info-right {
            position: fixed;
            right: 20px;
            top: 50%;
            transform: translateY(-50%) rotate(90deg);
            font-size: 0.62rem;
            font-weight: 600;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: var(--military-muted);
            opacity: 0.6;
            white-space: nowrap;
            z-index: 100;
        }

        /* Compass */
        .compass {
            position: fixed;
            bottom: 90px;
            right: 36px;
            z-index: 100;
            text-align: center;
        }

        .compass::before {
            content: 'N';
            display: block;
            font-size: 0.58rem;
            font-weight: 700;
            color: var(--military-amber);
            opacity: 0.6;
            letter-spacing: 0.1em;
        }

        .compass::after {
            content: '▲';
            display: block;
            font-size: 1rem;
            color: var(--military-amber);
            opacity: 0.4;
        }

        /* Radar Animation */
        .radar {
            position: fixed;
            bottom: 90px;
            left: 36px;
            width: 50px;
            height: 50px;
            border: 1px solid rgba(42, 82, 152, 0.4);
            border-radius: 50%;
            z-index: 100;
            overflow: hidden;
        }

        .radar::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 50%;
            height: 50%;
            background: linear-gradient(90deg, transparent, rgba(214, 179, 90, 0.3));
            transform-origin: 0 0;
            animation: radar-sweep 3s linear infinite;
        }

        @keyframes radar-sweep {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .login-container {
            position: relative;
            z-index: 1;
            background: linear-gradient(180deg, rgba(17, 24, 39, 0.98), rgba(10, 18, 35, 0.98));
            border: 1px solid rgba(42, 82, 152, 0.48);
            border-radius: 0;
            box-shadow: 0 22px 60px rgba(0,0,0,0.48);
            overflow: visible;
            width: 100%;
            max-width: 480px;
        }

        /* Panel Corner Brackets */
        .login-container::before,
        .login-container::after {
            content: '';
            position: absolute;
            width: 18px;
            height: 18px;
            border-color: var(--military-amber);
            border-style: solid;
            opacity: 0.6;
            z-index: 10;
        }

        .login-container::before {
            top: 8px;
            left: 8px;
            border-width: 2px 0 0 2px;
        }

        .login-container::after {
            top: 8px;
            right: 8px;
            border-width: 2px 2px 0 0;
        }

        .panel-corner-bl,
        .panel-corner-br {
            position: absolute;
            width: 18px;
            height: 18px;
            border-color: var(--military-amber);
            border-style: solid;
            opacity: 0.6;
            z-index: 10;
        }

        .panel-corner-bl {
            bottom: 8px;
            left: 8px;
            border-width: 0 0 2px 2px;
        }

        .panel-corner-br {
            bottom: 8px;
            right: 8px;
            border-width: 0 2px 2px 0;
        }

        .login-header {
            position: relative;
            padding: 26px 28px 20px;
            background:
                linear-gradient(135deg, rgba(30, 60, 114, 0.98), rgba(16, 30, 58, 0.98)),
                repeating-linear-gradient(135deg, rgba(255,255,255,0.05) 0 2px, transparent 2px 9px);
            border-bottom: 1px solid rgba(42, 82, 152, 0.42);
        }

        /* Striped Bottom Border */
        .login-header::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            right: 0;
            height: 5px;
            background: repeating-linear-gradient(
                90deg,
                transparent 0,
                transparent 6px,
                rgba(42, 82, 152, 0.3) 6px,
                rgba(42, 82, 152, 0.3) 12px
            );
        }

        .system-tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 5px 12px;
            margin-bottom: 14px;
            border: 1px solid rgba(214, 179, 90, 0.42);
            border-radius: 0;
            color: var(--military-amber);
            background: rgba(10, 18, 35, 0.56);
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .system-tag::before {
            content: '//';
            margin-right: 2px;
            opacity: 0.6;
        }

        .login-title-row {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .logo-icon {
            width: 56px;
            height: 56px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 56px;
            border: 2px solid rgba(214, 179, 90, 0.48);
            border-radius: 0;
            color: var(--military-amber);
            background: var(--military-field);
            box-shadow: inset 0 0 18px rgba(42, 82, 152, 0.22);
            font-size: 24px;
            position: relative;
        }

        .logo-icon::before,
        .logo-icon::after {
            content: '';
            position: absolute;
            background: var(--military-amber);
            opacity: 0.3;
        }

        .logo-icon::before {
            width: 100%;
            height: 1px;
            top: 50%;
            left: 0;
        }

        .logo-icon::after {
            width: 1px;
            height: 100%;
            top: 0;
            left: 50%;
        }

        .login-header h3 {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 750;
            letter-spacing: 0.02em;
        }

        .login-header p {
            margin: 4px 0 0;
            color: var(--military-muted);
            font-size: 0.85rem;
            letter-spacing: 0.03em;
        }

        .login-body {
            padding: 26px 28px 28px;
        }

        .access-strip {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 10px;
            align-items: center;
            padding: 10px 12px;
            margin-bottom: 22px;
            border: 1px solid rgba(42, 82, 152, 0.34);
            border-radius: 0;
            background: rgba(22, 33, 61, 0.78);
            position: relative;
        }

        .access-strip::before {
            content: '◢';
            position: absolute;
            left: -1px;
            top: -1px;
            color: var(--military-amber);
            opacity: 0.5;
            font-size: 0.55rem;
            line-height: 1;
        }

        .access-strip::after {
            content: '◣';
            position: absolute;
            right: -1px;
            bottom: -1px;
            color: var(--military-amber);
            opacity: 0.5;
            font-size: 0.55rem;
            line-height: 1;
        }

        .access-strip strong {
            display: block;
            color: var(--military-text);
            font-size: 0.85rem;
            font-weight: 700;
            letter-spacing: 0.05em;
        }

        .access-strip > span:first-child {
            color: var(--military-muted);
            font-size: 0.75rem;
        }

        .status-indicator {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }

        .status-indicator .status-led {
            width: 8px;
            height: 8px;
            background: #22c55e;
            border-radius: 0;
            animation: pulse 1.5s infinite;
            box-shadow: 0 0 8px rgba(34, 197, 94, 0.6);
        }

        .status-indicator .status-text {
            color: var(--military-amber);
            font-weight: 700;
            font-size: 0.68rem;
            letter-spacing: 0.1em;
        }

        .form-label {
            color: var(--military-muted);
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-label::before {
            content: '▸';
            color: var(--military-amber);
            opacity: 0.6;
            font-size: 0.6rem;
        }

        .input-group-text {
            width: 48px;
            justify-content: center;
            color: var(--military-amber);
            background: var(--military-field);
            border: 1px solid rgba(42, 82, 152, 0.48);
            border-right: 0;
            border-radius: 0;
        }

        .form-control {
            min-height: 48px;
            color: var(--military-text);
            background: var(--military-field);
            border: 1px solid rgba(42, 82, 152, 0.48);
            border-left: 0;
            border-radius: 0;
        }

        .form-control::placeholder {
            color: rgba(168, 181, 199, 0.72);
        }

        .form-control:focus {
            color: var(--military-text);
            background: var(--military-field);
            border-color: rgba(214, 179, 90, 0.7);
            box-shadow: 0 0 0 0.2rem rgba(214, 179, 90, 0.16);
        }

        .input-group:focus-within .input-group-text {
            border-color: rgba(214, 179, 90, 0.7);
        }

        .btn-primary {
            min-height: 50px;
            background: linear-gradient(135deg, var(--military-olive) 0%, var(--military-olive-bright) 100%);
            border: 1px solid rgba(214, 179, 90, 0.42);
            border-radius: 0;
            color: #f6f9ff;
            font-weight: 750;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            transition: transform 0.16s ease, box-shadow 0.16s ease, border-color 0.16s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--military-amber), transparent);
            opacity: 0.6;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            border-color: rgba(214, 179, 90, 0.72);
            box-shadow: 0 8px 22px rgba(30, 60, 114, 0.36);
            background: linear-gradient(135deg, #254985 0%, #3463ad 100%);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-primary i {
            margin-right: 8px;
        }

        .alert-danger {
            color: #ffe8e6;
            background: rgba(194, 85, 79, 0.18);
            border-color: rgba(194, 85, 79, 0.42);
            border-radius: 0;
        }

        .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }

        .login-footer {
            margin-top: 22px;
            padding-top: 14px;
            border-top: 1px solid rgba(42, 82, 152, 0.3);
        }

        .login-footer-row {
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 0.65rem;
            color: var(--military-muted);
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .login-footer-row .divider {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .login-footer-row .divider::before,
        .login-footer-row .divider::after {
            content: '';
            width: 30px;
            height: 1px;
            background: rgba(42, 82, 152, 0.4);
        }

        /* Barcode Style Decoration */
        .barcode {
            position: fixed;
            bottom: 60px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 2px;
            opacity: 0.3;
            z-index: 100;
        }

        .barcode span {
            width: 2px;
            height: 20px;
            background: var(--military-amber);
        }

        .barcode span:nth-child(odd) {
            height: 14px;
        }

        .barcode span:nth-child(3n) {
            width: 3px;
        }

        @media (max-width: 575.98px) {
            body {
                align-items: flex-start;
                padding: max(24px, env(safe-area-inset-top)) 12px max(16px, env(safe-area-inset-bottom));
                overflow-y: auto;
            }

            body::after {
                inset: 8px;
            }

            .corner-marker {
                display: none;
            }

            .status-bar-top,
            .status-bar-top-right,
            .tactical-info-left,
            .tactical-info-right,
            .compass,
            .radar,
            .barcode,
            .classification-banner,
            .chevron-strip {
                display: none;
            }

            .login-header,
            .login-body {
                padding-left: 16px;
                padding-right: 16px;
            }

            .login-header {
                padding-top: 20px;
                padding-bottom: 17px;
            }

            .login-body {
                padding-top: 21px;
                padding-bottom: 20px;
            }

            .login-title-row {
                gap: 11px;
            }

            .logo-icon {
                width: 48px;
                height: 48px;
                flex-basis: 48px;
                font-size: 20px;
            }

            .login-header h3 {
                font-size: 1.1rem;
            }

            .login-header p {
                font-size: 0.77rem;
                line-height: 1.35;
            }

            .system-tag {
                margin-bottom: 11px;
                padding: 4px 9px;
                font-size: 0.62rem;
            }

            .access-strip {
                margin-bottom: 17px;
            }

            .form-control,
            .input-group-text {
                min-height: 48px;
                font-size: 16px;
            }

            .login-footer {
                margin-top: 18px;
            }
        }

        @media (max-width: 360px) {
            .login-header h3 {
                font-size: 1rem;
            }

            .login-header p {
                font-size: 0.7rem;
            }

            .access-strip {
                grid-template-columns: 1fr;
            }

            .status-indicator {
                flex-direction: row;
                justify-content: flex-start;
            }
        }

        @media (max-height: 640px) and (orientation: landscape) {
            body {
                align-items: flex-start;
                overflow-y: auto;
            }

            .classification-banner,
            .status-bar-top,
            .status-bar-top-right,
            .chevron-strip,
            .corner-marker,
            .tactical-info-left,
            .tactical-info-right,
            .compass,
            .radar,
            .barcode {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Military Corner Markers -->
    <div class="corner-marker top-left"></div>
    <div class="corner-marker top-right"></div>
    <div class="corner-marker bottom-left"></div>
    <div class="corner-marker bottom-right"></div>

    <!-- Chevron Strips -->
    <div class="chevron-strip top">
        <div class="chevron"></div>
        <div class="chevron"></div>
        <div class="chevron"></div>
        <div class="chevron"></div>
        <div class="chevron"></div>
    </div>
    <div class="chevron-strip bottom">
        <div class="chevron"></div>
        <div class="chevron"></div>
        <div class="chevron"></div>
        <div class="chevron"></div>
        <div class="chevron"></div>
    </div>

    <!-- Classification Banner -->
    <div class="classification-banner">Authorized Personnel Only</div>

    <!-- Status Bar Top -->
    <div class="status-bar-top">
        <div class="status-item">
            <span class="status-dot"></span>
            <span>System Active</span>
        </div>
        <div class="status-item">
            <span>SEC-LVL 3</span>
        </div>
    </div>
    <div class="status-bar-top-right">ID: WH-TAC-2026</div>

    <!-- Tactical Info Sides -->
    <div class="tactical-info-left">WiFi HaLow Tactical System</div>
    <div class="tactical-info-right">Secure Authentication</div>

    <!-- Compass -->
    <div class="compass"></div>

    <!-- Radar Animation -->
    <div class="radar"></div>

    <!-- Barcode Decoration -->
    <div class="barcode">
        <span></span><span></span><span></span><span></span><span></span>
        <span></span><span></span><span></span><span></span><span></span>
        <span></span><span></span><span></span><span></span><span></span>
    </div>

    <div class="login-container">
        <div class="panel-corner-bl"></div>
        <div class="panel-corner-br"></div>
        
        <div class="login-header">
            <div class="system-tag">
                <i class="fas fa-shield-halved"></i> Restricted Access
            </div>
            <div class="login-title-row">
                <div class="logo-icon">
                    <i class="fas fa-satellite-dish"></i>
                </div>
                <div>
                    <h3>WiFi HaLow Testing System</h3>
                    <p>Tactical Monitoring & Communication Support</p>
                </div>
            </div>
        </div>
        <div class="login-body">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="access-strip">
                <span>
                    <strong>AUTHENTICATION NODE</strong>
                    Secure operator console
                </span>
                <div class="status-indicator">
                    <div class="status-led"></div>
                    <span class="status-text">ONLINE</span>
                </div>
            </div>
            
            <form method="POST" action="?action=login">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user-shield"></i></span>
                        <input type="text" class="form-control" id="username" name="username" autocomplete="username" required autofocus>
                    </div>
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-key"></i></span>
                        <input type="password" class="form-control" id="password" name="password" autocomplete="current-password" required>
                        <button type="button" class="input-group-text toggle-password" data-target="#password" title="Tampilkan/sembunyikan password" style="cursor:pointer; border-left:0;">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-right-to-bracket"></i>Authenticate
                    </button>
                </div>
            </form>

            <div class="login-footer">
                <div class="login-footer-row">
                    <div class="divider">End of Secure Zone</div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
