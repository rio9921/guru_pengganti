<?php
/**
 * GuruSinergi - Login Page
 * 
 * Halaman login dengan redirect yang benar ke dashboard
 */
 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Aktifkan error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Mulai output buffering untuk mencegah header issues
ob_start();

// Mulai session atau refresh jika sudah ada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set timezone
date_default_timezone_set('Asia/Jakarta');

// Include file konfigurasi dan database jika ada
$base_path = __DIR__ . '/';
if (file_exists($base_path . 'config/config.php')) {
    require_once $base_path . 'config/config.php';
}

if (file_exists($base_path . 'config/database.php')) {
    require_once $base_path . 'config/database.php';
}

if (file_exists($base_path . 'includes/functions.php')) {
    require_once $base_path . 'includes/functions.php';
}

// Fungsi fallback untuk penanganan error
if (!function_exists('set_error_message')) {
    function set_error_message($message) {
        $_SESSION['error_message'] = $message;
    }
}

if (!function_exists('has_error_message')) {
    function has_error_message() {
        return isset($_SESSION['error_message']) && !empty($_SESSION['error_message']);
    }
}

if (!function_exists('get_error_message')) {
    function get_error_message() {
        $message = $_SESSION['error_message'];
        unset($_SESSION['error_message']);
        return $message;
    }
}

// Variabel untuk menangani login
$is_logged_in = false;
$login_error = '';
$debug_info = [];

// Jika user sudah login, redirect ke dashboard yang sesuai
if (isset($_SESSION['user_id'])) {
    $is_logged_in = true;
    
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'teacher') {
        header("Location: /teachers/dashboard.php");
        exit;
    } else if (isset($_SESSION['role']) && $_SESSION['role'] === 'school') {
        header("Location: /schools/dashboard.php");
        exit;
    } else {
        header("Location: /dashboard.php");
        exit;
    }
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'login') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']) ? true : false;
    
    $debug_info['submission_received'] = 'yes';
    $debug_info['username'] = $username;
    $debug_info['password_length'] = strlen($password);
    
    // Validasi input
    if (empty($username) || empty($password)) {
        $login_error = 'Username dan password harus diisi.';
    } else {
        // Menggunakan fungsi login jika tersedia
        if (function_exists('login')) {
            $debug_info['login_function'] = 'exists';
            $login_result = login($username, $password, $remember);
            
            if ($login_result) {
                $debug_info['login_result'] = 'success';
                
                // Redirect berdasarkan role pengguna
                if (isset($_SESSION['role']) && $_SESSION['role'] === 'teacher') {
                    // Jika ada parameter redirect di URL, gunakan itu
                    if (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
                        header("Location: " . $_GET['redirect']);
                    } else {
                        // Jika tidak ada, redirect ke dashboard guru
                        header("Location: /teachers/dashboard.php");
                    }
                    exit;
                } else if (isset($_SESSION['role']) && $_SESSION['role'] === 'school') {
                    header("Location: /schools/dashboard.php");
                    exit;
                } else {
                    header("Location: /dashboard.php");
                    exit;
                }
            } else {
                $debug_info['login_result'] = 'failed';
                $login_error = 'Username atau password salah.';
            }
        } else {
            $debug_info['login_function'] = 'not_exists';
            
            // Fallback: Login manual menggunakan database
            if (isset($db) && $db) {
                try {
                    $stmt = $db->prepare("SELECT * FROM users WHERE (username = ? OR email = ?)");
                    $stmt->bind_param("ss", $username, $username);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $user = $result->fetch_assoc();
                    
                    if ($user && (password_verify($password, $user['password']) || $password === $user['password'])) {
                        // Set session
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['role'] = $user['user_type']; // Simpan role user
                        
                        // Redirect berdasarkan role pengguna
                        if ($_SESSION['role'] === 'teacher') {
                            // Jika ada parameter redirect di URL, gunakan itu
                            if (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
                                header("Location: " . $_GET['redirect']);
                            } else {
                                // Jika tidak ada, redirect ke dashboard guru
                                header("Location: /teachers/dashboard.php");
                            }
                            exit;
                        } else if ($_SESSION['role'] === 'school') {
                            header("Location: /schools/dashboard.php");
                            exit;
                        } else {
                            header("Location: /dashboard.php");
                            exit;
                        }
                    } else {
                        $login_error = 'Username atau password salah.';
                    }
                } catch (Exception $e) {
                    error_log("Login failed: " . $e->getMessage());
                    $login_error = 'Terjadi kesalahan saat login. Silakan coba lagi.';
                }
            } else {
                $login_error = 'Koneksi database tidak tersedia.';
            }
        }
    }
}

// Define base URL
function get_base_url() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script = dirname($_SERVER['SCRIPT_NAME']);
    return $protocol . '://' . $host . $script;
}

$base_url = get_base_url();

// Get redirect parameter from URL if exists
$redirect_url = isset($_GET['redirect']) ? $_GET['redirect'] : '';

// Flush buffer before HTML output
ob_flush();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - GuruSinergi</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        .navbar-brand {
            font-weight: 700;
        }
        .navbar-brand .highlight {
            color: #6f42c1;
        }
        .login-container {
            max-width: 450px;
            margin: 0 auto;
            padding-top: 80px;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        .card-header {
            background-color: #6f42c1;
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 20px;
        }
        .form-control {
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid #ced4da;
            margin-bottom: 15px;
        }
        .form-control:focus {
            border-color: #6f42c1;
            box-shadow: 0 0 0 0.25rem rgba(111, 66, 193, 0.25);
        }
        .btn-primary {
            background-color: #6f42c1;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
        }
        .btn-primary:hover {
            background-color: #5a36a0;
        }
        .debug-panel {
            margin-top: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
        }
        .nav-link {
            color: #555;
            transition: all 0.3s;
        }
        .nav-link:hover, .nav-link.active {
            color: #6f42c1;
        }
        .nav-link.active {
            font-weight: 600;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="index.php">Guru<span class="highlight">Sinergi</span></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="assignments.php">Penugasan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="teachers.php">Guru</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="materials.php">Materi</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">Tentang</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Kontak</a>
                    </li>
                </ul>
                
                <div class="d-flex">
                    <a href="login.php" class="btn btn-outline-primary me-2 active">Masuk</a>
                    <a href="register.php" class="btn btn-primary">Daftar</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main>
        <!-- Login Form -->
        <div class="login-container">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0 text-center">Masuk ke Akun Anda</h4>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($login_error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $login_error; ?>
                    </div>
                    <?php endif; ?>
                    
                    <form method="post" action="<?php echo $_SERVER['PHP_SELF'] . ($redirect_url ? '?redirect='.urlencode($redirect_url) : ''); ?>">
                        <input type="hidden" name="action" value="login">
                        
                        <div class="mb-3">
                            <label for="username" class="form-label">Username atau Email</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">
                                    <i class="fas fa-user text-muted"></i>
                                </span>
                                <input type="text" class="form-control" id="username" name="username" required autofocus>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">
                                    <i class="fas fa-lock text-muted"></i>
                                </span>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <button type="button" class="input-group-text bg-light" id="togglePassword">
                                    <i class="fas fa-eye text-muted"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label" for="remember">Ingat saya</label>
                            </div>
                            <a href="forgot-password.php" class="text-decoration-none">Lupa password?</a>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 mb-3">
                            <i class="fas fa-sign-in-alt me-2"></i>Masuk
                        </button>
                        
                        <p class="text-center mb-0">Belum punya akun? <a href="register.php" class="text-decoration-none">Daftar sekarang</a></p>
                    </form>
                </div>
            </div>
            
            <?php if (!empty($debug_info)): ?>
            <div class="debug-panel mt-4">
                <h6 class="mb-3"><i class="fas fa-bug me-2"></i>Debug Info</h6>
                
                <div class="small">
                    <ul class="list-unstyled mb-0">
                        <?php foreach ($debug_info as $key => $value): ?>
                        <li><strong><?php echo htmlspecialchars($key); ?>:</strong> <?php echo htmlspecialchars(is_bool($value) ? ($value ? 'true' : 'false') : $value); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>GuruSinergi</h5>
                    <p class="small">Platform Guru Pengganti dan Les Privat</p>
                </div>
                <div class="col-md-3">
                    <h6>Tautan</h6>
                    <ul class="list-unstyled small">
                        <li><a href="about.php" class="text-white-50">Tentang Kami</a></li>
                        <li><a href="faq.php" class="text-white-50">FAQ</a></li>
                        <li><a href="terms.php" class="text-white-50">Syarat & Ketentuan</a></li>
                        <li><a href="privacy.php" class="text-white-50">Kebijakan Privasi</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h6>Kontak</h6>
                    <ul class="list-unstyled small">
                        <li><i class="fas fa-envelope me-2"></i>info@gurusinergi.com</li>
                        <li><i class="fas fa-phone me-2"></i>+62 89513005831</li>
                        <li><i class="fas fa-map-marker-alt me-2"></i>Pekanbaru, Riau</li>
                    </ul>
                </div>
            </div>
            <hr class="my-3 bg-secondary">
            <div class="text-center small text-white-50">
                &copy; <?php echo date('Y'); ?> GuruSinergi. Hak Cipta Dilindungi.
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Password toggle
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            
            if (togglePassword && passwordInput) {
                togglePassword.addEventListener('click', function () {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    
                    // Toggle icon
                    const icon = this.querySelector('i');
                    icon.classList.toggle('fa-eye');
                    icon.classList.toggle('fa-eye-slash');
                });
            }
        });
    </script>
</body>
</html>
<?php
// End buffer
ob_end_flush();
?>