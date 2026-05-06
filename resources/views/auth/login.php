<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - WiFi HaLow Testing System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
        }
        .login-header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .login-body {
            padding: 30px;
        }
        .form-control:focus {
            border-color: #2a5298;
            box-shadow: 0 0 0 0.25rem rgba(42, 82, 152, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            border: none;
            padding: 10px 30px;
            border-radius: 25px;
            transition: all 0.3s;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(42, 82, 152, 0.3);
        }
        .logo-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo-icon">🛰️</div>
            <h3 class="mb-0">WiFi HaLow Testing System</h3>
            <p class="mb-0" style="font-size: 14px; opacity: 0.9;">Tactical Monitoring & Communication Support</p>
        </div>
        <div class="login-body">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="?action=login">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required autofocus>
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </button>
                </div>
            </form>
            
            <div class="text-center mt-4">
                <small class="text-muted">Demo: admin / admin123</small>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>
