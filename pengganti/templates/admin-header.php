<?php
/**
 * GuruSinergi - Admin Header Template
 * 
 * Header untuk semua halaman admin
 */

// Include file konfigurasi jika belum
if (!function_exists('config')) {
    require_once dirname(__DIR__) . '/config/config.php';
}

// Include file database jika belum
if (!function_exists('db_connect')) {
    require_once dirname(__DIR__) . '/config/database.php';
}

// Include file functions jika belum
if (!function_exists('is_logged_in')) {
    require_once dirname(__DIR__) . '/includes/functions.php';
}

// Include file notification functions jika belum
if (!function_exists('get_unread_notifications_count')) {
    require_once dirname(__DIR__) . '/includes/notification-functions.php';
}

// Inisialisasi variabel
$current_user = null;
$unread_notifications = 0;

// Dapatkan data user secara aman
if (is_logged_in() && function_exists('get_logged_in_user')) {
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
        $unread_notifications = get_unread_notifications_count($current_user['id']);
    }
}

$page_title = $page_title ?? 'Admin Dashboard';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Admin GuruSinergi</title>
    
    <!-- Font Google -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <!-- Font Awesome untuk ikon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!-- CSS -->
    <link rel="stylesheet" href="<?php echo asset('css/admin-style.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">
    
    <!-- CSS tambahan jika ada -->
    <?php if (isset($extra_css)): ?>
        <?php echo $extra_css; ?>
    <?php endif; ?>
    
    <!-- JavaScript tambahan di head jika ada -->
    <?php if (isset($head_js)): ?>
        <?php echo $head_js; ?>
    <?php endif; ?>
</head>
<body class="admin-body">
    <div class="admin-layout">
        <!-- Sidebar -->
        <div class="admin-sidebar">
            <div class="sidebar-header">
                <h1 class="sidebar-title">GuruSinergi</h1>
                <span class="admin-badge">Admin Panel</span>
            </div>
            
            <nav class="sidebar-nav">
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="<?php echo url('admin/index.php'); ?>" class="nav-link <?php echo get_current_page() == 'index.php' ? 'active' : ''; ?>">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo url('admin/verification.php'); ?>" class="nav-link <?php echo get_current_page() == 'verification.php' ? 'active' : ''; ?>">
                            <i class="fas fa-check-circle"></i>
                            <span>Verifikasi</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo url('admin/users.php'); ?>" class="nav-link <?php echo get_current_page() == 'users.php' ? 'active' : ''; ?>">
                            <i class="fas fa-users"></i>
                            <span>Pengguna</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo url('admin/assignments.php'); ?>" class="nav-link <?php echo get_current_page() == 'assignments.php' ? 'active' : ''; ?>">
                            <i class="fas fa-clipboard-list"></i>
                            <span>Penugasan</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo url('admin/payments.php'); ?>" class="nav-link <?php echo get_current_page() == 'payments.php' ? 'active' : ''; ?>">
                            <i class="fas fa-money-bill-wave"></i>
                            <span>Pembayaran</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo url('admin/materials.php'); ?>" class="nav-link <?php echo get_current_page() == 'materials.php' ? 'active' : ''; ?>">
                            <i class="fas fa-book"></i>
                            <span>Materi</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo url('admin/reviews.php'); ?>" class="nav-link <?php echo get_current_page() == 'reviews.php' ? 'active' : ''; ?>">
                            <i class="fas fa-star"></i>
                            <span>Ulasan</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo url('admin/settings.php'); ?>" class="nav-link <?php echo get_current_page() == 'settings.php' ? 'active' : ''; ?>">
                            <i class="fas fa-cog"></i>
                            <span>Pengaturan</span>
                        </a>
                    </li>
                </ul>
            </nav>
            
            <div class="sidebar-footer">
                <a href="<?php echo url(); ?>" class="btn btn-outline btn-sm" target="_blank">
                    <i class="fas fa-external-link-alt"></i>
                    <span>Lihat Situs</span>
                </a>
                <a href="<?php echo url('logout.php'); ?>" class="btn btn-danger btn-sm">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Keluar</span>
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="admin-main">
            <header class="admin-header">
                <div class="header-left">
                    <button id="sidebar-toggle" class="sidebar-toggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="page-title-section">
                        <h2 class="header-title"><?php echo $page_title; ?></h2>
                    </div>
                </div>
                
                <div class="header-right">
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
                                <a href="<?php echo url('admin/notifications.php'); ?>">Lihat Semua</a>
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
                                <a href="<?php echo url('admin/notifications.php'); ?>">Lihat Semua Notifikasi</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- User Account -->
                    <div class="admin-user-dropdown">
                        <button class="user-dropdown-toggle" id="user-dropdown-toggle">
                            <?php if (!empty($current_user['profile_image'])): ?>
                                <img src="<?php echo $current_user['profile_image']; ?>" alt="<?php echo $current_user['full_name']; ?>" class="user-avatar">
                            <?php else: ?>
                                <div class="user-avatar-placeholder">
                                    <span><?php echo substr($current_user['full_name'], 0, 1); ?></span>
                                </div>
                            <?php endif; ?>
                            <span class="user-name"><?php echo $current_user['full_name']; ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        
                        <div class="user-dropdown-menu" id="user-dropdown-menu">
                            <a href="<?php echo url('profile.php'); ?>" class="dropdown-item">
                                <i class="fas fa-user"></i>
                                <span>Profil Saya</span>
                            </a>
                            <a href="<?php echo url('admin/settings.php'); ?>" class="dropdown-item">
                                <i class="fas fa-cog"></i>
                                <span>Pengaturan</span>
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="<?php echo url('logout.php'); ?>" class="dropdown-item">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Keluar</span>
                            </a>
                        </div>
                    </div>
                </div>
            </header>
            
            <main class="admin-content">
                <div class="content-wrapper">
                    <!-- Display Messages -->
                    <?php display_messages(); ?>