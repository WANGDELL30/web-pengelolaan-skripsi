<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

        .login-container {
            position: relative;
            z-index: 1;
            background: linear-gradient(180deg, rgba(17, 24, 39, 0.98), rgba(10, 18, 35, 0.98));
            border: 1px solid rgba(42, 82, 152, 0.48);
            border-radius: 8px;
            box-shadow: 0 22px 60px rgba(0,0,0,0.48);
            overflow: hidden;
            width: 100%;
            max-width: 460px;
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--military-olive), var(--military-amber), var(--military-olive-bright));
        }

        .login-header {
            position: relative;
            padding: 28px 30px 22px;
            background:
                linear-gradient(135deg, rgba(30, 60, 114, 0.98), rgba(16, 30, 58, 0.98)),
                repeating-linear-gradient(135deg, rgba(255,255,255,0.05) 0 2px, transparent 2px 9px);
            border-bottom: 1px solid rgba(42, 82, 152, 0.42);
        }

        .system-tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 5px 10px;
            margin-bottom: 16px;
            border: 1px solid rgba(214, 179, 90, 0.42);
            border-radius: 4px;
            color: var(--military-amber);
            background: rgba(10, 18, 35, 0.56);
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .login-title-row {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .logo-icon {
            width: 54px;
            height: 54px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 54px;
            border: 1px solid rgba(214, 179, 90, 0.48);
            border-radius: 8px;
            color: var(--military-amber);
            background: var(--military-field);
            box-shadow: inset 0 0 18px rgba(42, 82, 152, 0.22);
            font-size: 24px;
        }

        .login-header h3 {
            margin: 0;
            font-size: 1.35rem;
            font-weight: 750;
            letter-spacing: 0.01em;
        }

        .login-header p {
            margin: 5px 0 0;
            color: var(--military-muted);
            font-size: 0.9rem;
        }

        .login-body {
            padding: 28px 30px 30px;
        }

        .access-strip {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 12px;
            align-items: center;
            padding: 10px 12px;
            margin-bottom: 22px;
            border: 1px solid rgba(42, 82, 152, 0.34);
            border-radius: 6px;
            background: rgba(22, 33, 61, 0.78);
            color: var(--military-muted);
            font-size: 0.82rem;
        }

        .access-strip strong {
            display: block;
            color: var(--military-text);
            font-size: 0.9rem;
            font-weight: 700;
        }

        .access-strip span:last-child {
            color: var(--military-amber);
            font-weight: 700;
            white-space: nowrap;
        }

        .form-label {
            color: var(--military-muted);
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .input-group-text {
            width: 46px;
            justify-content: center;
            color: var(--military-amber);
            background: var(--military-field);
            border: 1px solid rgba(42, 82, 152, 0.48);
            border-right: 0;
            border-radius: 6px 0 0 6px;
        }

        .form-control {
            min-height: 46px;
            color: var(--military-text);
            background: var(--military-field);
            border: 1px solid rgba(42, 82, 152, 0.48);
            border-left: 0;
            border-radius: 0 6px 6px 0;
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

        .form-control:focus + .input-group-text,
        .input-group:focus-within .input-group-text {
            border-color: rgba(214, 179, 90, 0.7);
        }

        .btn-primary {
            min-height: 48px;
            background: linear-gradient(135deg, var(--military-olive) 0%, var(--military-olive-bright) 100%);
            border: 1px solid rgba(214, 179, 90, 0.42);
            border-radius: 6px;
            color: #f6f9ff;
            font-weight: 750;
            letter-spacing: 0.03em;
            transition: transform 0.16s ease, box-shadow 0.16s ease, border-color 0.16s ease;
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

        .alert-danger {
            color: #ffe8e6;
            background: rgba(194, 85, 79, 0.18);
            border-color: rgba(194, 85, 79, 0.42);
        }

        .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }

        .login-footer-line {
            height: 1px;
            margin-top: 24px;
            background: linear-gradient(90deg, transparent, rgba(42, 82, 152, 0.46), transparent);
        }

        @media (max-width: 575.98px) {
            body {
                padding: 16px;
            }

            body::after {
                inset: 8px;
            }

            .login-header,
            .login-body {
                padding-left: 22px;
                padding-right: 22px;
            }

            .login-header h3 {
                font-size: 1.15rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
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
                <span>ONLINE</span>
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
                        <i class="fas fa-right-to-bracket"></i> Login
                    </button>
                </div>
            </form>

            <div class="login-footer-line"></div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
