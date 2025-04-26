<?php
/**
 * GuruSinergi - Index.php (Halaman Utama)
 * 
 * Landing page untuk platform guru pengganti dan les privat
 */

// Include file konfigurasi
require_once 'config/config.php';

// Cek apakah pengguna sudah login - TIDAK PERLU SESSION_START() DI SINI
// karena session_start() sudah ada di file lain yang diinclude sebelum ini
$logged_in = isset($_SESSION['user_id']);

// Set judul halaman
$page_title = 'Solusi Pendidikan Terpadu - GuruSinergi';

/**
 * BEGIN HEADER
 */

// Perbaikan path untuk include file lain - GUNAKAN PATH RELATIF
// Include file konfigurasi jika belum
if (!function_exists('config')) {
    require_once 'config/config.php';
}

// Include file database jika belum
if (!function_exists('db_connect')) {
    require_once 'config/database.php'; // Perbaikan path
}

// Include file functions jika belum
if (!function_exists('is_logged_in')) {
    require_once 'includes/functions.php'; // Perbaikan path
}

// Include file notification functions jika belum
if (!function_exists('get_unread_notifications_count')) {
    require_once 'includes/notification-functions.php'; // Perbaikan path
}

// Inisialisasi variabel
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
        $unread_notifications = get_unread_notifications_count($current_user['id']);
    }
}

$page_title = $page_title ?? 'Program Guru Pengganti';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - GuruSinergi</title>
    
    <!-- Font Google -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <!-- Font Awesome untuk ikon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!-- CSS -->
    <?php if (function_exists('asset')): ?>
    <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">
    <?php else: ?>
    <link rel="stylesheet" href="assets/css/style.css">
    <?php endif; ?>
    
    <!-- CSS tambahan jika ada -->
    <?php if (isset($extra_css)): ?>
        <?php echo $extra_css; ?>
    <?php endif; ?>
    
    <!-- CSS Inline untuk Landing Page -->
    <style>
    /* Landing Page CSS */
    .hero-section {
        background-color: #0d6efd;
        color: white;
        padding: 60px 0;
        position: relative;
        overflow: hidden;
    }
    
    .hero-content {
        position: relative;
        z-index: 2;
    }
    
    h1.hero-title {
        font-size: 2.8rem;
        font-weight: 700;
        margin-bottom: 20px;
        line-height: 1.2;
    }
    
    .hero-description {
        font-size: 1.25rem;
        margin-bottom: 30px;
        max-width: 600px;
    }
    
    .service-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 30px;
    }
    
    .service-button {
        display: inline-block;
        padding: 12px 25px;
        background-color: white;
        color: #0d6efd;
        border-radius: 30px;
        font-weight: 600;
        text-decoration: none;
        font-size: 1.1rem;
        transition: all 0.3s ease;
        border: 2px solid white;
    }
    
    .service-button:hover {
        background-color: rgba(255, 255, 255, 0.9);
        transform: translateY(-3px);
    }
    
    .platform-badge {
        position: absolute;
        top: 30px;
        right: 50px;
        background-color: #ffc107;
        color: #000;
        padding: 10px 20px;
        border-radius: 30px;
        font-weight: 700;
        transform: rotate(15deg);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        z-index: 10;
        font-size: 1.1rem;
    }
    
    .service-description {
        margin-bottom: 30px;
    }
    
    .service-description p {
        margin-bottom: 15px;
        font-size: 1.1rem;
        line-height: 1.6;
    }
    
    .cta-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .btn {
        display: inline-block;
        padding: 12px 25px;
        border-radius: 30px;
        text-decoration: none;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .btn-light {
        background-color: white;
        color: #0d6efd;
    }
    
    .btn-outline-light {
        background-color: transparent;
        color: white;
        border: 2px solid white;
    }
    
    .btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    
    .btn-light:hover {
        background-color: #f8f9fa;
    }
    
    .btn-outline-light:hover {
        background-color: rgba(255, 255, 255, 0.1);
    }
    
    /* Service Section */
    .services-section {
        padding: 60px 0;
        background-color: #f8f9fa;
    }
    
    .section-title {
        font-size: 2.2rem;
        font-weight: 700;
        margin-bottom: 15px;
        text-align: center;
    }
    
    .section-subtitle {
        font-size: 1.1rem;
        color: #6c757d;
        margin-bottom: 40px;
        text-align: center;
    }
    
    .service-card {
        background-color: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
        height: 100%;
    }
    
    .service-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
    }
    
    .service-card-header {
        padding: 20px;
        background-color: #0d6efd;
        color: white;
    }
    
    .private-card .service-card-header {
        background-color: #28a745;
    }
    
    .service-card-title {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 10px;
    }
    
    .service-card-body {
        padding: 25px;
    }
    
    .service-features {
        list-style-type: none;
        padding: 0;
        margin-bottom: 25px;
    }
    
    .service-features li {
        padding: 8px 0;
        display: flex;
        align-items: flex-start;
    }
    
    .service-features li i {
        color: #28a745;
        margin-right: 10px;
        margin-top: 4px;
    }
    
    .service-card-footer {
        padding: 15px 25px 25px;
    }
    
    /* Layout */
    .row {
        display: flex;
        flex-wrap: wrap;
        margin: 0 -15px;
    }
    
    .col-md-6 {
        width: 100%;
        padding: 0 15px;
        margin-bottom: 30px;
    }
    
    @media (min-width: 768px) {
        .col-md-6 {
            width: 50%;
            margin-bottom: 0;
        }
    }
    
    /* Responsive */
    @media (max-width: 767px) {
        .platform-badge {
            right: 20px;
            top: 20px;
            font-size: 0.9rem;
            padding: 8px 15px;
        }
        
        h1.hero-title {
            font-size: 2.2rem;
        }
        
        .hero-description {
            font-size: 1.1rem;
        }
        
        .service-button {
            width: 100%;
            text-align: center;
        }
    }
    </style>
    
    <!-- JavaScript tambahan di head jika ada -->
    <?php if (isset($head_js)): ?>
        <?php echo $head_js; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Header dengan Navigasi -->
    <header class="site-header">
        <div class="container">
            <div class="header-wrapper">
                <!-- Logo -->
                <div class="logo">
                    <?php if (function_exists('url')): ?>
                    <a href="<?php echo url(); ?>">
                        <span class="logo-text">Guru<span class="highlight">Sinergi</span></span>
                    </a>
                    <?php else: ?>
                    <a href="index.php">
                        <span class="logo-text">Guru<span class="highlight">Sinergi</span></span>
                    </a>
                    <?php endif; ?>
                </div>
                
                <!-- Navigasi -->
                <nav class="main-nav" id="main-nav">
                    <ul class="nav-list">
                        <?php if (function_exists('url') && function_exists('is_active_page')): ?>
                        <li><a href="<?php echo url(); ?>" class="<?php echo is_active_page('/') ? 'active' : ''; ?>">Beranda</a></li>
                        <li><a href="<?php echo url('assignments/browse.php'); ?>" class="<?php echo is_active_page('/assignments/browse.php') ? 'active' : ''; ?>">Penugasan</a></li>
                        <li><a href="<?php echo url('teachers/browse.php'); ?>" class="<?php echo is_active_page('/teachers/browse.php') ? 'active' : ''; ?>">Cari Guru</a></li>
                        <li><a href="<?php echo url('materials.php'); ?>" class="<?php echo is_active_page('/materials.php') ? 'active' : ''; ?>">Materi Pembelajaran</a></li>
                        <li><a href="<?php echo url('about.php'); ?>" class="<?php echo is_active_page('/about.php') ? 'active' : ''; ?>">Tentang Kami</a></li>
                        <li><a href="<?php echo url('contact.php'); ?>" class="<?php echo is_active_page('/contact.php') ? 'active' : ''; ?>">Kontak</a></li>
                        <?php else: ?>
                        <li><a href="index.php" class="active">Beranda</a></li>
                        <li><a href="assignments/browse.php">Penugasan</a></li>
                        <li><a href="teachers/browse.php">Cari Guru</a></li>
                        <li><a href="materials.php">Materi Pembelajaran</a></li>
                        <li><a href="about.php">Tentang Kami</a></li>
                        <li><a href="contact.php">Kontak</a></li>
                        <?php endif; ?>
                    </ul>
                    
                    <!-- Auth button untuk mobile -->
                    <div class="auth-mobile">
                        <?php if ($current_user): ?>
                            <?php if (function_exists('url')): ?>
                            <a href="<?php echo url('dashboard.php'); ?>" class="btn btn-primary">Dashboard</a>
                            <a href="<?php echo url('logout.php'); ?>" class="btn btn-outline">Keluar</a>
                            <?php else: ?>
                            <a href="dashboard.php" class="btn btn-primary">Dashboard</a>
                            <a href="logout.php" class="btn btn-outline">Keluar</a>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php if (function_exists('url')): ?>
                            <a href="<?php echo url('login.php'); ?>" class="btn btn-primary">Masuk</a>
                            <a href="<?php echo url('register.php'); ?>" class="btn btn-outline">Daftar</a>
                            <?php else: ?>
                            <a href="login.php" class="btn btn-primary">Masuk</a>
                            <a href="register.php" class="btn btn-outline">Daftar</a>
                            <?php endif; ?>
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
                            <?php if (function_exists('url')): ?>
                            <a href="<?php echo url('notifications.php'); ?>">Lihat Semua</a>
                            <?php else: ?>
                            <a href="notifications.php">Lihat Semua</a>
                            <?php endif; ?>
                        </div>
                        <div class="noti-body">
                            <?php
                            $notifications = [];
                            if (function_exists('get_notifications')) {
                                $notifications = get_notifications($current_user['id'], 5);
                            }
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
                            <?php if (function_exists('url')): ?>
                            <a href="<?php echo url('notifications.php'); ?>">Lihat Semua Notifikasi</a>
                            <?php else: ?>
                            <a href="notifications.php">Lihat Semua Notifikasi</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- User Account Dropdown -->
                <div class="account-dropdown" id="account-dropdown">
                    <button class="account-toggle" id="account-toggle">
                        <?php if (!empty($current_user['profile_image'])): ?>
                            <img src="<?php echo $current_user['profile_image']; ?>" alt="<?php echo $current_user['full_name']; ?>">
                        <?php else: ?>
                            <div style="width: 36px; height: 36px; border-radius: 50%; background-color: <?php echo $current_user['user_type'] == 'guru' ? '#4F46E5' : '#10B981'; ?>; color: white; display: flex; align-items: center; justify-content: center; font-weight: 600;">
                                <?php echo substr($current_user['full_name'], 0, 1); ?>
                            </div>
                        <?php endif; ?>
                        <span class="username"><?php echo $current_user['full_name']; ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    
                    <!-- Account Dropdown Menu -->
                    <div class="dropdown-menu">
                        <div class="user-info">
                            <div class="user-name"><?php echo $current_user['full_name']; ?></div>
                            <div class="user-email"><?php echo $current_user['email']; ?></div>
                        </div>
                        <ul class="menu-items">
                            <?php if (function_exists('url')): ?>
                            <li><a href="<?php echo url('dashboard.php'); ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                            <li><a href="<?php echo url('profile.php'); ?>"><i class="fas fa-user"></i> Profil Saya</a></li>
                            <?php if ($current_user['user_type'] == 'guru'): ?>
                                <li><a href="<?php echo url('applications/my-applications.php'); ?>"><i class="fas fa-file-alt"></i> Lamaran Saya</a></li>
                                <li><a href="<?php echo url('assignments/my-assignments.php'); ?>"><i class="fas fa-briefcase"></i> Penugasan Saya</a></li>
                            <?php elseif ($current_user['user_type'] == 'sekolah'): ?>
                                <li><a href="<?php echo url('assignments/create.php'); ?>"><i class="fas fa-plus-circle"></i> Buat Penugasan</a></li>
                                <li><a href="<?php echo url('assignments/my-assignments.php'); ?>"><i class="fas fa-clipboard-list"></i> Penugasan Saya</a></li>
                            <?php elseif ($current_user['user_type'] == 'admin'): ?>
                                <li><a href="<?php echo url('admin/'); ?>"><i class="fas fa-cog"></i> Admin Panel</a></li>
                            <?php endif; ?>
                            <li><a href="<?php echo url('messages.php'); ?>"><i class="fas fa-envelope"></i> Pesan</a></li>
                            <li><a href="<?php echo url('logout.php'); ?>"><i class="fas fa-sign-out-alt"></i> Keluar</a></li>
                            <?php else: ?>
                            <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                            <li><a href="profile.php"><i class="fas fa-user"></i> Profil Saya</a></li>
                            <?php if ($current_user['user_type'] == 'guru'): ?>
                                <li><a href="applications/my-applications.php"><i class="fas fa-file-alt"></i> Lamaran Saya</a></li>
                                <li><a href="assignments/my-assignments.php"><i class="fas fa-briefcase"></i> Penugasan Saya</a></li>
                            <?php elseif ($current_user['user_type'] == 'sekolah'): ?>
                                <li><a href="assignments/create.php"><i class="fas fa-plus-circle"></i> Buat Penugasan</a></li>
                                <li><a href="assignments/my-assignments.php"><i class="fas fa-clipboard-list"></i> Penugasan Saya</a></li>
                            <?php elseif ($current_user['user_type'] == 'admin'): ?>
                                <li><a href="admin/"><i class="fas fa-cog"></i> Admin Panel</a></li>
                            <?php endif; ?>
                            <li><a href="messages.php"><i class="fas fa-envelope"></i> Pesan</a></li>
                            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Keluar</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
                <?php else: ?>
                <!-- Tombol Masuk/Daftar untuk desktop -->
                <div class="auth-buttons">
                    <?php if (function_exists('url')): ?>
                    <a href="<?php echo url('login.php'); ?>" class="btn btn-primary">Masuk</a>
                    <a href="<?php echo url('register.php'); ?>" class="btn btn-outline">Daftar</a>
                    <?php else: ?>
                    <a href="login.php" class="btn btn-primary">Masuk</a>
                    <a href="register.php" class="btn btn-outline">Daftar</a>
                    <?php endif; ?>
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
            <?php if (function_exists('display_messages')) display_messages(); ?>

            <!-- LANDING PAGE CONTENT START -->
            
            <!-- Hero Section dengan 2 Layanan -->
            <section class="hero-section">
                <div class="platform-badge">2 Layanan dalam 1 Platform!</div>
                
                <div class="hero-content">
                    <h1 class="hero-title">Solusi Pendidikan Terpadu</h1>
                    <p class="hero-description">GuruSinergi menyediakan dua layanan utama untuk kebutuhan pendidikan Anda:</p>
                    
                    <div class="service-buttons">
                        <a href="#guru-pengganti" class="service-button">Guru Pengganti</a>
                        <a href="#les-privat" class="service-button">Les Privat</a>
                    </div>
                    
                    <div class="service-description">
                        <p>Butuh guru pengganti untuk sekolah Anda? GuruSinergi menghubungkan sekolah dengan guru pengganti berkualitas untuk menjaga keberlangsungan pembelajaran tanpa gangguan.</p>
                        
                        <p>Butuh guru les privat untuk pembelajaran di rumah? GuruSinergi menyediakan guru les privat yang terpercaya dengan jadwal fleksibel dan harga terjangkau.</p>
                    </div>
                    
                    <div class="cta-buttons">
                        <?php if (!$logged_in): ?>
                        <a href="register.php?type=client" class="btn btn-light">Saya Butuh Guru</a>
                        <a href="register.php?type=teacher" class="btn btn-outline-light">Saya Ingin Mengajar</a>
                        <?php else: ?>
                        <a href="dashboard.php" class="btn btn-light">Masuk ke Dashboard</a>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
            
            <!-- Service Section -->
            <section class="services-section" id="layanan">
                <h2 class="section-title">Layanan GuruSinergi</h2>
                <p class="section-subtitle">Solusi lengkap untuk kebutuhan pendidikan Anda</p>
                
                <div class="row">
                    <!-- Guru Pengganti Card -->
                    <div class="col-md-6">
                        <div class="service-card" id="guru-pengganti">
                            <div class="service-card-header">
                                <h3 class="service-card-title">Guru Pengganti untuk Sekolah</h3>
                                <p>Solusi cepat dan efektif untuk kebutuhan guru pengganti</p>
                            </div>
                            <div class="service-card-body">
                                <ul class="service-features">
                                    <li><i class="fas fa-check-circle"></i> Guru berkualitas dan terverifikasi</li>
                                    <li><i class="fas fa-check-circle"></i> Proses matching cepat dan tepat</li>
                                    <li><i class="fas fa-check-circle"></i> Materi pembelajaran standar tersedia</li>
                                    <li><i class="fas fa-check-circle"></i> Koordinasi mudah dengan guru utama</li>
                                    <li><i class="fas fa-check-circle"></i> Penggantian untuk jangka pendek atau panjang</li>
                                </ul>
                                <p>Untuk sekolah yang membutuhkan guru pengganti karena guru utama berhalangan hadir (cuti melahirkan, sakit, pelatihan, dan alasan lainnya).</p>
                            </div>
                            <div class="service-card-footer">
                                <?php if (!$logged_in): ?>
                                <a href="register.php?type=school" class="btn btn-primary">Daftar sebagai Sekolah</a>
                                <?php else: ?>
                                <a href="request.php?type=school" class="btn btn-primary">Buat Permintaan</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Les Privat Card -->
                    <div class="col-md-6">
                        <div class="service-card private-card" id="les-privat">
                            <div class="service-card-header">
                                <h3 class="service-card-title">Les Privat di Rumah</h3>
                                <p>Belajar lebih fokus dengan guru berpengalaman</p>
                            </div>
                            <div class="service-card-body">
                                <ul class="service-features">
                                    <li><i class="fas fa-check-circle"></i> Jadwal fleksibel sesuai kebutuhan</li>
                                    <li><i class="fas fa-check-circle"></i> Guru berkompeten sesuai mata pelajaran</li>
                                    <li><i class="fas fa-check-circle"></i> Harga terjangkau dan transparan</li>
                                    <li><i class="fas fa-check-circle"></i> Laporan perkembangan belajar</li>
                                    <li><i class="fas fa-check-circle"></i> Pembelajaran yang disesuaikan</li>
                                </ul>
                                <p>Untuk orang tua yang ingin memberikan pembelajaran tambahan bagi anak dengan pendekatan personal sesuai dengan kebutuhan dan kemampuan belajar.</p>
                            </div>
                            <div class="service-card-footer">
                                <?php if (!$logged_in): ?>
                                <a href="register.php?type=parent" class="btn btn-success">Daftar untuk Les Privat</a>
                                <?php else: ?>
                                <a href="request.php?type=private" class="btn btn-success">Pesan Les Privat</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- END LANDING PAGE CONTENT -->
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <div class="footer-logo">Guru<span class="highlight">Sinergi</span></div>
                    <p>Platform yang mendukung guru perempuan di Indonesia untuk menyeimbangkan tanggung jawab keluarga dan pengembangan karir profesional.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                
                <div class="footer-column">
                    <h3>Tautan Penting</h3>
                    <ul class="footer-links">
                        <?php if (function_exists('url')): ?>
                        <li><a href="<?php echo url(); ?>">Beranda</a></li>
                        <li><a href="<?php echo url('assignments/browse.php'); ?>">Penugasan</a></li>
                        <li><a href="<?php echo url('teachers/browse.php'); ?>">Cari Guru</a></li>
                        <li><a href="<?php echo url('materials.php'); ?>">Materi Pembelajaran</a></li>
                        <li><a href="<?php echo url('about.php'); ?>">Tentang Kami</a></li>
                        <?php else: ?>
                        <li><a href="index.php">Beranda</a></li>
                        <li><a href="assignments/browse.php">Penugasan</a></li>
                        <li><a href="teachers/browse.php">Cari Guru</a></li>
                        <li><a href="materials.php">Materi Pembelajaran</a></li>
                        <li><a href="about.php">Tentang Kami</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h3>Bantuan</h3>
                    <ul class="footer-links">
                        <?php if (function_exists('url')): ?>
                        <li><a href="<?php echo url('faq.php'); ?>">FAQ</a></li>
                        <li><a href="<?php echo url('terms.php'); ?>">Syarat & Ketentuan</a></li>
                        <li><a href="<?php echo url('privacy.php'); ?>">Kebijakan Privasi</a></li>
                        <li><a href="<?php echo url('contact.php'); ?>">Kontak Kami</a></li>
                        <?php else: ?>
                        <li><a href="faq.php">FAQ</a></li>
                        <li><a href="terms.php">Syarat & Ketentuan</a></li>
                        <li><a href="privacy.php">Kebijakan Privasi</a></li>
                        <li><a href="contact.php">Kontak Kami</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h3>Kontak</h3>
                    <div class="contact-info">
                        <i class="fas fa-map-marker-alt"></i>
                        <p>Jl. Umban Sari<br>Pekanbaru, 28265<br>Indonesia</p>
                    </div>
                    <div class="contact-info">
                        <i class="fas fa-envelope"></i>
                        <p><a href="mailto:<?php echo function_exists('config') ? config('admin_email') : 'admin@gurusinergi.com'; ?>"><?php echo function_exists('config') ? config('admin_email') : 'admin@gurusinergi.com'; ?></a></p>
                    </div>
                    <div class="contact-info">
                        <i class="fas fa-phone-alt"></i>
                        <p><a href="tel:<?php echo function_exists('config') ? config('support_phone') : '089513005831'; ?>"><?php echo function_exists('config') ? config('support_phone') : '089513005831'; ?></a></p>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> GuruSinergi. Hak Cipta Dilindungi.</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <?php if (function_exists('asset')): ?>
    <script src="<?php echo asset('js/script.js'); ?>"></script>
    <?php else: ?>
    <script src="assets/js/script.js"></script>
    <?php endif; ?>
    
    <!-- JavaScript tambahan jika ada -->
    <?php if (isset($extra_js)): ?>
        <?php echo $extra_js; ?>
    <?php endif; ?>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle mobile menu
        const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
        const mainNav = document.getElementById('main-nav');
        
        if (mobileMenuToggle && mainNav) {
            mobileMenuToggle.addEventListener('click', function() {
                this.classList.toggle('active');
                mainNav.classList.toggle('active');
            });
        }
        
        // Toggle account dropdown
        const accountToggle = document.getElementById('account-toggle');
        const accountDropdown = document.getElementById('account-dropdown');
        
        if (accountToggle && accountDropdown) {
            accountToggle.addEventListener('click', function() {
                accountDropdown.classList.toggle('active');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(event) {
                if (!accountDropdown.contains(event.target) && !accountToggle.contains(event.target)) {
                    accountDropdown.classList.remove('active');
                }
            });
        }
        
        // Toggle notification dropdown
        const notificationToggle = document.getElementById('notification-toggle');
        const notificationDropdown = document.getElementById('notification-dropdown');
        
        if (notificationToggle && notificationDropdown) {
            notificationToggle.addEventListener('click', function() {
                notificationDropdown.classList.toggle('active');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(event) {
                if (!notificationDropdown.contains(event.target) && !notificationToggle.contains(event.target)) {
                    notificationDropdown.classList.remove('active');
                }
            });
        }
        
        // Smooth scroll for anchor links
        const anchorLinks = document.querySelectorAll('a[href^="#"]');
        
        anchorLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                const targetId = this.getAttribute('href');
                
                if(targetId !== '#') {
                    const targetElement = document.querySelector(targetId);
                    
                    if(targetElement) {
                        window.scrollTo({
                            top: targetElement.offsetTop - 80,
                            behavior: 'smooth'
                        });
                    }
                }
            });
        });
    });
    </script>
</body>
</html>