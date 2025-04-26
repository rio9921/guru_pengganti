<?php
/**
 * GuruSinergi - Header Template
 * 
 * Header untuk semua halaman
 */

// PENTING: TIDAK MEMULAI SESSION DI SINI
// SESSION SUDAH DIMULAI DI FILE UTAMA SEBELUM INCLUDE HEADER

// Pastikan include file hanya terjadi jika belum di-include sebelumnya
// Gunakan path absolut untuk menghindari masalah dengan relative path
if (!function_exists('config')) {
    $base_path = realpath(dirname(__DIR__)) . '/';
    if (file_exists($base_path . 'config/config.php')) {
        require_once $base_path . 'config/config.php';
    }
}

// Include file database jika belum
if (!function_exists('db_connect')) {
    $base_path = realpath(dirname(__DIR__)) . '/';
    if (file_exists($base_path . 'config/database.php')) {
        require_once $base_path . 'config/database.php';
    }
}

// Include file functions jika belum
if (!function_exists('is_logged_in')) {
    $base_path = realpath(dirname(__DIR__)) . '/';
    if (file_exists($base_path . 'includes/functions.php')) {
        require_once $base_path . 'includes/functions.php';
    }
}

// Include file notification functions jika belum
if (!function_exists('get_unread_notifications_count')) {
    $base_path = realpath(dirname(__DIR__)) . '/';
    if (file_exists($base_path . 'includes/notification-functions.php')) {
        require_once $base_path . 'includes/notification-functions.php';
    }
}

// Inisialisasi variabel dengan penanganan error
try {
    $current_user = null;
    $unread_notifications = 0;

    // Dapatkan data user secara aman
    if (function_exists('is_logged_in') && is_logged_in() && function_exists('get_app_current_user')) {
        $current_user = get_app_current_user();
        
        // Pastikan current_user adalah array
        if (!is_array($current_user)) {
            $current_user = null;
        } 
        // Pastikan profile adalah array jika user valid
        elseif (!isset($current_user['profile']) || !is_array($current_user['profile'])) {
            $current_user['profile'] = [];
        }
        
        // Dapatkan notifikasi jika user valid
        if ($current_user && isset($current_user['id']) && function_exists('get_unread_notifications_count')) {
            try {
                $unread_notifications = get_unread_notifications_count($current_user['id']);
            } catch (Exception $e) {
                // Log error tetapi jangan hentikan eksekusi
                error_log("Error getting notifications: " . $e->getMessage());
                $unread_notifications = 0;
            }
        }
    }
} catch (Exception $e) {
    // Log error tetapi jangan hentikan eksekusi
    error_log("Error in header: " . $e->getMessage());
    $current_user = null;
    $unread_notifications = 0;
}

// Pengaturan judul halaman
$page_title = $page_title ?? 'Program Guru Pengganti';

// Fallback untuk fungsi asset() jika belum didefinisikan
if (!function_exists('asset')) {
    function asset($path = '') {
        $base_url = isset($GLOBALS['config']['site_url']) ? $GLOBALS['config']['site_url'] : 'https://pengganti.gurusinergi.com';
        return rtrim($base_url, '/') . '/assets/' . ltrim($path, '/');
    }
}

// Fallback untuk fungsi url() jika belum didefinisikan
if (!function_exists('url')) {
    function url($path = '') {
        $base_url = isset($GLOBALS['config']['site_url']) ? $GLOBALS['config']['site_url'] : 'https://pengganti.gurusinergi.com';
        return rtrim($base_url, '/') . '/' . ltrim($path, '/');
    }
}

// Fallback untuk fungsi is_active_page() jika belum didefinisikan
if (!function_exists('is_active_page')) {
    function is_active_page($path) {
        $current_path = $_SERVER['REQUEST_URI'] ?? '';
        return strpos($current_path, $path) !== false;
    }
}

// Fallback untuk fungsi get_notifications() jika belum didefinisikan
if (!function_exists('get_notifications')) {
    function get_notifications($user_id, $limit = 5) {
        return [];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<!-- Lanjutkan dengan kode HTML asli -->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - GuruSinergi</title>
    
    <!-- Font Google -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <!-- Font Awesome untuk ikon - Gunakan CDN langsung -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!-- CSS - Gunakan fungsi asset() dengan fallback -->
    <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">
    
    <!-- Bootstrap CSS untuk fallback jika style.css tidak tersedia -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
    
    <!-- CSS tambahan jika ada -->
    <?php if (isset($extra_css)): ?>
        <?php echo $extra_css; ?>
    <?php endif; ?>
    
    <!-- JavaScript tambahan di head jika ada -->
    <?php if (isset($head_js)): ?>
        <?php echo $head_js; ?>
    <?php endif; ?>
    
    <!-- Style tambahan untuk memastikan tampilan dasar -->
    <style>
        /* CSS dasar jika style.css tidak tersedia */
        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        .site-header {
            background-color: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 15px 0;
        }
        .header-wrapper {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo-text {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            text-decoration: none;
        }
        .highlight {
            color: #6f42c1;
        }
        .main-nav .nav-list {
            list-style: none;
            display: flex;
            margin: 0;
            padding: 0;
        }
        .main-nav .nav-list li {
            margin: 0 15px;
        }
        .main-nav .nav-list a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: color 0.3s;
        }
        .main-nav .nav-list a:hover, .main-nav .nav-list a.active {
            color: #6f42c1;
        }
        .auth-buttons .btn {
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: 500;
            margin-left: 10px;
            text-decoration: none;
        }
        .btn-primary {
            background-color: #6f42c1;
            color: white;
            border: none;
        }
        .btn-outline {
            background-color: transparent;
            color: #6f42c1;
            border: 1px solid #6f42c1;
        }
        .main-content {
            padding: 30px 0;
        }
        
        /* Media query untuk tampilan mobile */
        @media (max-width: 768px) {
            .header-wrapper {
                flex-direction: column;
                text-align: center;
            }
            .main-nav .nav-list {
                flex-direction: column;
                margin-top: 20px;
            }
            .main-nav .nav-list li {
                margin: 10px 0;
            }
            .auth-buttons {
                margin-top: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Header dengan Navigasi -->
    <header class="site-header">
        <div class="container">
            <div class="header-wrapper">
                <!-- Logo -->
                <div class="logo">
                    <a href="<?php echo url(); ?>">
                        <span class="logo-text">Guru<span class="highlight">Sinergi</span></span>
                    </a>
                </div>
                
                <!-- Navigasi -->
                <nav class="main-nav" id="main-nav">
                    <ul class="nav-list">
                        <li><a href="<?php echo url(); ?>" class="<?php echo is_active_page('/') ? 'active' : ''; ?>">Beranda</a></li>
                        <li><a href="<?php echo url('assignments/browse.php'); ?>" class="<?php echo is_active_page('/assignments/browse.php') ? 'active' : ''; ?>">Penugasan</a></li>
                        <li><a href="<?php echo url('teachers/browse.php'); ?>" class="<?php echo is_active_page('/teachers/browse.php') ? 'active' : ''; ?>">Cari Guru</a></li>
                        <li><a href="<?php echo url('materials.php'); ?>" class="<?php echo is_active_page('/materials.php') ? 'active' : ''; ?>">Materi Pembelajaran</a></li>
                        <li><a href="<?php echo url('about.php'); ?>" class="<?php echo is_active_page('/about.php') ? 'active' : ''; ?>">Tentang Kami</a></li>
                        <li><a href="<?php echo url('contact.php'); ?>" class="<?php echo is_active_page('/contact.php') ? 'active' : ''; ?>">Kontak</a></li>
                    </ul>
                    
                    <!-- Auth button untuk mobile -->
                    <div class="auth-mobile">
                        <?php if ($current_user): ?>
                            <a href="<?php echo url('dashboard.php'); ?>" class="btn btn-primary">Dashboard</a>
                            <a href="<?php echo url('logout.php'); ?>" class="btn btn-outline">Keluar</a>
                        <?php else: ?>
                            <a href="<?php echo url('login.php'); ?>" class="btn btn-primary">Masuk</a>
                            <a href="<?php echo url('register.php'); ?>" class="btn btn-outline">Daftar</a>
                        <?php endif; ?>
                    </div>
                </nav>
                
                <?php if ($current_user): ?>
                <!-- Notifikasi -->
                <div class="notification-bell">
                    <button id="notification-toggle">
                        <i class="fas fa-bell"></i>
                        <?php if ($unread_notifications > 0): ?>
                            <span class="noti-count"><?php echo $unread_notifications; ?></span>
                        <?php endif; ?>
                    </button>
                    
                    <!-- Notification Dropdown -->
                    <div class="notification-dropdown" id="notification-dropdown">
                        <div class="noti-header">
                            <h4>Notifikasi</h4>
                            <a href="<?php echo url('notifications.php'); ?>">Lihat Semua</a>
                        </div>
                        <div class="noti-body">
                            <?php
                            $notifications = get_notifications($current_user['id'], 5);
                            if (empty($notifications)):
                            ?>
                                <div class="text-center py-4">
                                    <p>Belum ada notifikasi</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($notifications as $noti): ?>
                                    <div class="noti-item <?php echo $noti['is_read'] ? '' : 'unread'; ?>">
                                        <div class="noti-icon">
                                            <i class="fas fa-bell"></i>
                                        </div>
                                        <div class="noti-content">
                                            <div class="noti-title"><?php echo $noti['title']; ?></div>
                                            <div class="noti-message"><?php echo $noti['message']; ?></div>
                                            <div class="noti-time"><?php echo date('d M Y, H:i', strtotime($noti['created_at'])); ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="noti-footer">
                            <a href="<?php echo url('notifications.php'); ?>">Lihat Semua Notifikasi</a>
                        </div>
                    </div>
                </div>
                
                <!-- User Account Dropdown -->
                <div class="account-dropdown" id="account-dropdown">
                    <button class="account-toggle" id="account-toggle">
                        <?php if (!empty($current_user['profile_image'])): ?>
                            <img src="<?php echo $current_user['profile_image']; ?>" alt="<?php echo $current_user['full_name'] ?? 'User'; ?>">
                        <?php else: ?>
                            <div style="width: 36px; height: 36px; border-radius: 50%; background-color: <?php echo $current_user['user_type'] == 'guru' ? '#4F46E5' : '#10B981'; ?>; color: white; display: flex; align-items: center; justify-content: center; font-weight: 600;">
                                <?php echo isset($current_user['full_name']) ? substr($current_user['full_name'], 0, 1) : 'U'; ?>
                            </div>
                        <?php endif; ?>
                        <span class="username"><?php echo $current_user['full_name'] ?? 'User'; ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    
                    <!-- Account Dropdown Menu -->
                    <div class="dropdown-menu">
                        <div class="user-info">
                            <div class="user-name"><?php echo $current_user['full_name'] ?? 'User'; ?></div>
                            <div class="user-email"><?php echo $current_user['email'] ?? 'email@example.com'; ?></div>
                        </div>
                        <ul class="menu-items">
                            <li><a href="<?php echo url('dashboard.php'); ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                            <li><a href="<?php echo url('profile.php'); ?>"><i class="fas fa-user"></i> Profil Saya</a></li>
                            <?php if (isset($current_user['user_type']) && $current_user['user_type'] == 'guru'): ?>
                                <li><a href="<?php echo url('applications/my-applications.php'); ?>"><i class="fas fa-file-alt"></i> Lamaran Saya</a></li>
                                <li><a href="<?php echo url('assignments/my-assignments.php'); ?>"><i class="fas fa-briefcase"></i> Penugasan Saya</a></li>
                            <?php elseif (isset($current_user['user_type']) && $current_user['user_type'] == 'sekolah'): ?>
                                <li><a href="<?php echo url('assignments/create.php'); ?>"><i class="fas fa-plus-circle"></i> Buat Penugasan</a></li>
                                <li><a href="<?php echo url('assignments/my-assignments.php'); ?>"><i class="fas fa-clipboard-list"></i> Penugasan Saya</a></li>
                            <?php elseif (isset($current_user['user_type']) && $current_user['user_type'] == 'admin'): ?>
                                <li><a href="<?php echo url('admin/'); ?>"><i class="fas fa-cog"></i> Admin Panel</a></li>
                            <?php endif; ?>
                            <li><a href="<?php echo url('messages.php'); ?>"><i class="fas fa-envelope"></i> Pesan</a></li>
                            <li><a href="<?php echo url('logout.php'); ?>"><i class="fas fa-sign-out-alt"></i> Keluar</a></li>
                        </ul>
                    </div>
                </div>
                <?php else: ?>
                <!-- Tombol Masuk/Daftar untuk desktop -->
                <div class="auth-buttons">
                    <a href="<?php echo url('login.php'); ?>" class="btn btn-primary">Masuk</a>
                    <a href="<?php echo url('register.php'); ?>" class="btn btn-outline">Daftar</a>
                </div>
                <?php endif; ?>
                
                <!-- Mobile Menu Toggle (Hamburger) -->
                <div class="menu-toggle" id="mobile-menu-toggle">
                    <span class="bar"></span>
                    <span class="bar"></span>
                    <span class="bar"></span>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="main-content">
        <div class="container">
            <!-- Display Messages -->
            <?php 
            if (function_exists('display_messages')) {
                display_messages();
            } elseif (isset($_SESSION['success_message']) || isset($_SESSION['error_message'])) {
                // Fallback untuk menampilkan pesan jika fungsi tidak tersedia
                if (isset($_SESSION['success_message'])) {
                    echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
                    unset($_SESSION['success_message']);
                }
                if (isset($_SESSION['error_message'])) {
                    echo '<div class="alert alert-danger">' . $_SESSION['error_message'] . '</div>';
                    unset($_SESSION['error_message']);
                }
            }
            ?>