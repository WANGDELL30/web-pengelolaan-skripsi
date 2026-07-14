<?php
/**
 * Login Controller for WiFi HaLow Testing System
 */

require_once __DIR__ . '/../Helpers/functions.php';

class LoginController {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Show login page
     */
    public function index() {
        if (isLoggedIn()) {
            redirect('index.php');
        }

        $viewPath = __DIR__ . '/../../resources/views/auth/login.php';
        if (!is_file($viewPath) || !is_readable($viewPath)) {
            http_response_code(500);
            echo 'Login view tidak tersedia. Upload ulang folder resources/views/auth dari paket deployment.';
            return;
        }

        ob_start();
        $included = include $viewPath;
        $output = ob_get_clean();

        if ($included === false || $output === '') {
            http_response_code(500);
            echo 'Login view gagal dimuat. Pastikan resources/views/auth/login.php tidak kosong dan dapat dibaca.';
            return;
        }

        echo $output;
    }
    
    /**
     * Process login
     */
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = sanitize($_POST['username']);
            $password = $_POST['password'];
            
            $user = fetchOne("SELECT * FROM users WHERE username = ?", [$username]);
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];
                
                redirect('index.php');
            } else {
                $_SESSION['error'] = 'Username atau password salah';
                redirect('index.php');
            }
        }
    }
    
    /**
     * Logout
     */
    public function logout() {
        session_destroy();
        redirect('index.php');
    }
}
