<?php
/**
 * GuruSinergi - Dashboard Guru
 * 
 * File dashboard guru yang sudah digabung dengan header untuk mengatasi masalah redirect
 * Dengan CSS dan JavaScript yang sudah digabung untuk tampilan yang lebih baik
 */

// Mulai output buffering untuk mencegah masalah header
ob_start();

// Mulai sesi
session_start();

// Cek apakah pengguna sudah login dan adalah guru
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    // Redirect ke halaman login jika belum login atau bukan guru
    header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Include file konfigurasi dan database
require_once '../config/config.php';
require_once '../config/database.php';

// Include file fungsi
require_once '../includes/functions.php';
require_once '../includes/matching-functions.php';
require_once '../includes/verification-functions.php';
require_once '../includes/payment-functions.php';

// Ambil ID pengguna
$user_id = $_SESSION['user_id'];

// Ambil profil guru
$query = "SELECT * FROM teacher_profiles WHERE user_id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Jika belum ada profil, redirect ke halaman pembuatan profil
    header('Location: /teachers/profile.php?new=1');
    exit;
}

$teacher_profile = $result->fetch_assoc();
$teacher_id = $teacher_profile['id'];

// Cek status verifikasi
$verification_status = $teacher_profile['verification_status'];

// Ambil penugasan yang aktif
$query = "SELECT a.*, s.school_name, s.address as school_address, 
          (SELECT MAX(date) FROM attendance WHERE assignment_id = a.id AND teacher_id = ?) as last_attendance_date
          FROM assignments a
          JOIN school_profiles s ON a.school_id = s.id
          JOIN applications app ON a.id = app.assignment_id
          WHERE app.teacher_id = ? AND app.status = 'accepted' AND a.status = 'in_progress'
          ORDER BY a.start_date ASC";

$stmt = $db->prepare($query);
$stmt->bind_param("ii", $teacher_id, $teacher_id);
$stmt->execute();
$active_assignments_result = $stmt->get_result();
$active_assignments = [];

while ($row = $active_assignments_result->fetch_assoc()) {
    $active_assignments[] = $row;
}

// Ambil penugasan yang akan datang (sudah diterima tapi belum mulai)
$query = "SELECT a.*, s.school_name, s.address as school_address
          FROM assignments a
          JOIN school_profiles s ON a.school_id = s.id
          JOIN applications app ON a.id = app.assignment_id
          WHERE app.teacher_id = ? AND app.status = 'accepted' 
          AND (a.status = 'open' OR (a.status = 'in_progress' AND a.start_date > CURDATE()))
          ORDER BY a.start_date ASC";

$stmt = $db->prepare($query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$upcoming_assignments_result = $stmt->get_result();
$upcoming_assignments = [];

while ($row = $upcoming_assignments_result->fetch_assoc()) {
    $upcoming_assignments[] = $row;
}

// Ambil penugasan yang sudah selesai
$query = "SELECT a.*, s.school_name, p.amount, p.status as payment_status, 
          (SELECT MAX(date) FROM attendance WHERE assignment_id = a.id AND teacher_id = ?) as last_attendance_date
          FROM assignments a
          JOIN school_profiles s ON a.school_id = s.id
          JOIN applications app ON a.id = app.assignment_id
          LEFT JOIN payments p ON a.id = p.assignment_id
          WHERE app.teacher_id = ? AND app.status = 'accepted' AND a.status = 'completed'
          ORDER BY a.end_date DESC
          LIMIT 5";

$stmt = $db->prepare($query);
$stmt->bind_param("ii", $teacher_id, $teacher_id);
$stmt->execute();
$completed_assignments_result = $stmt->get_result();
$completed_assignments = [];

while ($row = $completed_assignments_result->fetch_assoc()) {
    $completed_assignments[] = $row;
}

// Ambil aplikasi yang sedang menunggu (belum diterima/ditolak)
$query = "SELECT app.*, a.title, a.subject, a.start_date, a.end_date, s.school_name
          FROM applications app
          JOIN assignments a ON app.assignment_id = a.id
          JOIN school_profiles s ON a.school_id = s.id
          WHERE app.teacher_id = ? AND app.status = 'pending' AND a.status = 'open'
          ORDER BY app.created_at DESC";

$stmt = $db->prepare($query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$pending_applications_result = $stmt->get_result();
$pending_applications = [];

while ($row = $pending_applications_result->fetch_assoc()) {
    $pending_applications[] = $row;
}

// Ambil notifikasi terbaru
$query = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notifications_result = $stmt->get_result();
$notifications = [];

while ($row = $notifications_result->fetch_assoc()) {
    $notifications[] = $row;
}

// Ambil statistik guru
$total_income = get_teacher_total_income($teacher_id);
$completed_count = count($completed_assignments);
$attendance_stats = get_teacher_attendance_stats($teacher_id);

// Ambil rekomendasi penugasan
$recommended_assignments = get_recommended_assignments_for_teacher($teacher_id, 3);

// Set judul halaman
$page_title = 'Dashboard Guru';

// Inisialisasi variabel untuk header
$current_user = [
    'id' => $user_id,
    'full_name' => $teacher_profile['full_name'],
    'email' => $teacher_profile['email'] ?? '',
    'user_type' => 'guru',
    'profile' => $teacher_profile
];

// Fungsi format helper (jika belum didefinisikan)
if (!function_exists('format_date')) {
    function format_date($date) {
        return date('d M Y', strtotime($date));
    }
}

if (!function_exists('format_time')) {
    function format_time($time) {
        return date('H:i', strtotime($time));
    }
}

if (!function_exists('time_elapsed_string')) {
    function time_elapsed_string($datetime, $full = false) {
        $now = new DateTime;
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);
    
        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;
    
        $string = array(
            'y' => 'tahun',
            'm' => 'bulan',
            'w' => 'minggu',
            'd' => 'hari',
            'h' => 'jam',
            'i' => 'menit',
            's' => 'detik',
        );
    
        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? '' : '');
            } else {
                unset($string[$k]);
            }
        }
    
        if (!$full) $string = array_slice($string, 0, 1);
        return $string ? implode(', ', $string) . ' yang lalu' : 'baru saja';
    }
}

// Fallback untuk fungsi asset()
if (!function_exists('asset')) {
    function asset($path = '') {
        $base_url = isset($GLOBALS['config']['site_url']) ? $GLOBALS['config']['site_url'] : 'https://pengganti.gurusinergi.com';
        return rtrim($base_url, '/') . '/assets/' . ltrim($path, '/');
    }
}

// Fallback untuk fungsi url()
if (!function_exists('url')) {
    function url($path = '') {
        $base_url = isset($GLOBALS['config']['site_url']) ? $GLOBALS['config']['site_url'] : 'https://pengganti.gurusinergi.com';
        return rtrim($base_url, '/') . '/' . ltrim($path, '/');
    }
}

// Fallback untuk fungsi is_active_page()
if (!function_exists('is_active_page')) {
    function is_active_page($path) {
        $current_path = $_SERVER['REQUEST_URI'] ?? '';
        return strpos($current_path, $path) !== false;
    }
}

// Sekarang sudah aman untuk output HTML
ob_flush();
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
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
    
    <style>
        /* CSS Reset khusus untuk mengatasi bullet points */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        /* Reset khusus untuk daftar */
        body ul, 
        body ol, 
        body li, 
        body .nav-list, 
        body .nav-list li, 
        body .menu-items, 
        body .menu-items li {
            list-style-type: none !important;
            list-style-image: none !important;
            list-style-position: outside !important;
            list-style: none !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        
        a {
            text-decoration: none;
            color: inherit;
        }
        
        /* Variabel CSS */
        :root {
            --primary-color: #6f42c1;
            --primary-light: #8c68d6;
            --primary-dark: #5a32a3;
            --secondary-color: #17a2b8;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-400: #ced4da;
            --gray-500: #adb5bd;
            --gray-600: #6c757d;
            --gray-700: #495057;
            --gray-800: #343a40;
            --gray-900: #212529;
            --body-bg: #f5f8fa;
            --card-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            --header-shadow: 0 2px 10px rgba(0, 0, 0, 0.07);
            --transition-fast: 0.15s;
            --transition-normal: 0.3s;
            --border-radius-sm: 0.25rem;
            --border-radius: 0.375rem;
            --border-radius-lg: 0.5rem;
            --box-shadow-sm: 0 .125rem .25rem rgba(0,0,0,.075);
            --box-shadow: 0 .5rem 1rem rgba(0,0,0,.08);
            --box-shadow-lg: 0 1rem 3rem rgba(0,0,0,.1);
            --font-family-main: 'Poppins', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            --font-family-headings: 'Montserrat', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        
        /* Base Styles */
        html {
            scroll-behavior: smooth;
        }
        
        body {
            font-family: var(--font-family-main);
            font-size: 0.95rem;
            line-height: 1.6;
            color: var(--gray-800);
            background-color: var(--body-bg);
            overflow-x: hidden;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
        }
        
        /* Typography */
        h1, h2, h3, h4, h5, h6 {
            font-family: var(--font-family-headings);
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: var(--gray-900);
            line-height: 1.3;
        }
        
        p {
            margin-bottom: 1rem;
        }
        
        .text-primary { color: var(--primary-color) !important; }
        .text-secondary { color: var(--secondary-color) !important; }
        .text-success { color: var(--success-color) !important; }
        .text-warning { color: var(--warning-color) !important; }
        .text-danger { color: var(--danger-color) !important; }
        .text-light { color: var(--light-color) !important; }
        .text-dark { color: var(--gray-800) !important; }
        .text-muted { color: var(--gray-600) !important; }
        
        .bg-primary { background-color: var(--primary-color) !important; }
        .bg-secondary { background-color: var(--secondary-color) !important; }
        .bg-success { background-color: var(--success-color) !important; }
        .bg-warning { background-color: var(--warning-color) !important; }
        .bg-danger { background-color: var(--danger-color) !important; }
        .bg-light { background-color: var(--light-color) !important; }
        .bg-dark { background-color: var(--dark-color) !important; }
        
        /* Layout & Container */
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        
        .main-content {
            flex: 1;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
        }
        
        /* Header */
        .site-header {
            background-color: #fff;
            box-shadow: var(--header-shadow);
            padding: 0.75rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo-container {
            display: flex;
            align-items: center;
        }
        
        .logo-text {
            font-family: var(--font-family-headings);
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gray-900);
            display: flex;
            align-items: center;
        }
        
        .logo-text .highlight {
            color: var(--primary-color);
        }
        
        /* Navigation Main */
        .main-nav {
            display: flex;
            align-items: center;
        }
        
        .nav-list {
            display: flex;
            margin: 0;
            padding: 0;
            gap: 0.5rem;
        }
        
        .nav-list li {
            margin: 0;
            padding: 0;
        }
        
        .nav-list a {
            display: block;
            padding: 0.5rem 0.75rem;
            color: var(--gray-700);
            font-weight: 500;
            border-radius: var(--border-radius-sm);
            transition: all var(--transition-fast);
        }
        
        .nav-list a:hover, 
        .nav-list a.active {
            color: var(--primary-color);
            background-color: rgba(111, 66, 193, 0.08);
        }
        
        /* User Account Dropdown */
        .user-controls {
            display: flex;
            align-items: center;
            margin-left: auto;
        }
        
        .account-dropdown {
            position: relative;
            margin-left: 1rem;
        }
        
        .account-toggle {
            display: flex;
            align-items: center;
            background: none;
            border: none;
            padding: 0.5rem 0.75rem;
            border-radius: var(--border-radius-sm);
            cursor: pointer;
            transition: background-color var(--transition-fast);
        }
        
        .account-toggle:focus {
            outline: none;
        }
        
        .account-toggle:hover {
            background-color: rgba(111, 66, 193, 0.08);
        }
        
        .account-toggle img,
        .account-toggle .avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            margin-right: 8px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: white;
        }
        
        .account-toggle .username {
            font-weight: 500;
            color: var(--gray-800);
            margin-right: 6px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 150px;
        }
        
        .account-toggle .icon {
            font-size: 0.75rem;
            color: var(--gray-600);
            transition: transform var(--transition-fast);
        }
        
        .account-toggle:hover .icon {
            color: var(--primary-color);
        }
        
        .dropdown-menu {
            position: absolute;
            top: calc(100% + 5px);
            right: 0;
            min-width: 240px;
            background-color: #fff;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            border: 1px solid var(--gray-200);
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all var(--transition-fast);
            z-index: 1000;
        }
        
        .dropdown-menu.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .user-info {
            padding: 1rem;
            border-bottom: 1px solid var(--gray-200);
            text-align: center;
            background-color: rgba(111, 66, 193, 0.03);
        }
        
        .user-name {
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 0.25rem;
        }
        
        .user-email {
            font-size: 0.875rem;
            color: var(--gray-600);
        }
        
        .menu-items li {
            margin: 0;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .menu-items li:last-child {
            border-bottom: none;
        }
        
        .menu-items li a {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: var(--gray-700);
            transition: all var(--transition-fast);
        }
        
        .menu-items li a:hover {
            background-color: rgba(111, 66, 193, 0.05);
            color: var(--primary-color);
        }
        
        .menu-items li a i {
            width: 1.25rem;
            margin-right: 0.75rem;
            color: var(--gray-600);
            font-size: 0.875rem;
            text-align: center;
        }
        
        .menu-items li a:hover i {
            color: var(--primary-color);
        }
        
        /* Mobile Toggle */
        .menu-toggle {
            display: none;
            flex-direction: column;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            padding: 0.25rem;
            background: none;
            border: none;
            cursor: pointer;
            margin-left: 0.5rem;
        }
        
        .menu-toggle .bar {
            width: 1.5rem;
            height: 2px;
            background-color: var(--gray-700);
            border-radius: 1px;
            transition: all var(--transition-fast);
            margin: 2px 0;
        }
        
        .menu-toggle.active .bar:nth-child(1) {
            transform: translateY(6px) rotate(45deg);
            background-color: var(--primary-color);
        }
        
        .menu-toggle.active .bar:nth-child(2) {
            opacity: 0;
        }
        
        .menu-toggle.active .bar:nth-child(3) {
            transform: translateY(-6px) rotate(-45deg);
            background-color: var(--primary-color);
        }
        
        /* Cards */
        .card {
            background-color: #fff;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            border: none;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            padding: 1rem 1.25rem;
            background-color: #fff;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-header h5 {
            margin-bottom: 0;
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--gray-800);
        }
        
        .card-body {
            padding: 1.25rem;
        }
        
        .card-footer {
            padding: 0.75rem 1.25rem;
            background-color: rgba(0,0,0,0.01);
            border-top: 1px solid var(--gray-200);
        }
        
        /* Panels for Dashboard sections */
        .dashboard-header {
            margin-bottom: 1.5rem;
        }
        
        .dashboard-title {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .dashboard-subtitle {
            color: var(--gray-600);
            font-size: 1rem;
        }
        
        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
            text-align: center;
            vertical-align: middle;
            cursor: pointer;
            user-select: none;
            padding: 0.5rem 1rem;
            font-size: 0.95rem;
            line-height: 1.5;
            border-radius: var(--border-radius);
            transition: all var(--transition-fast);
            white-space: nowrap;
        }
        
        .btn i, .btn svg {
            margin-right: 0.5rem;
        }
        
        .btn:focus, .btn:active {
            outline: none;
            box-shadow: none;
        }
        
        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            line-height: 1.5;
            border-radius: var(--border-radius-sm);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            color: white;
        }
        
        .btn-outline-primary {
            background-color: transparent;
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
            color: white;
        }
        
        .btn-warning {
            background-color: var(--warning-color);
            border-color: var(--warning-color);
            color: white;
        }
        
        .btn-group {
            display: inline-flex;
        }
        
        .btn-group .btn:not(:last-child) {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }
        
        .btn-group .btn:not(:first-child) {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
            margin-left: -1px;
        }
        
        /* Badges */
        .badge {
            display: inline-block;
            padding: 0.35em 0.65em;
            font-size: 0.75em;
            font-weight: 600;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: var(--border-radius-sm);
        }
        
        /* Tables */
        .table {
            width: 100%;
            margin-bottom: 1rem;
            color: var(--gray-800);
            vertical-align: top;
            border-color: var(--gray-300);
        }
        
        .table > :not(caption) > * > * {
            padding: 0.75rem 1rem;
            background-color: transparent;
            border-bottom-width: 1px;
            box-shadow: inset 0 0 0 9999px transparent;
        }
        
        .table-hover > tbody > tr:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }
        
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .table th {
            color: var(--gray-700);
            font-weight: 600;
        }
        
        /* Status Indicators */
        .status-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 5px;
        }
        
        .status-dot-success {
            background-color: var(--success-color);
        }
        
        .status-dot-warning {
            background-color: var(--warning-color);
        }
        
        .status-dot-danger {
            background-color: var(--danger-color);
        }
        
        /* Alerts */
        .alert {
            position: relative;
            padding: 0.75rem 1.25rem;
            margin-bottom: 1rem;
            border: 1px solid transparent;
            border-radius: var(--border-radius);
        }
        
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        
        .alert-warning {
            color: #856404;
            background-color: #fff3cd;
            border-color: #ffeeba;
        }
        
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        
        /* Empty States */
        .empty-state {
            text-align: center;
            padding: 3rem 1.5rem;
        }
        
        .empty-state-icon {
            font-size: 3rem;
            color: var(--gray-400);
            margin-bottom: 1rem;
        }
        
        .empty-state-title {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
            color: var(--gray-700);
        }
        
        .empty-state-description {
            color: var(--gray-600);
            margin-bottom: 1.5rem;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }
        
        /* Profile Card */
        .profile-card {
            text-align: center;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 1rem;
            border: 3px solid white;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        /* Quick Links */
        .quick-links .list-group-item {
            border: none;
            padding: 0.75rem 1rem;
            transition: all var(--transition-fast);
        }
        
        .quick-links .list-group-item:hover {
            background-color: rgba(111, 66, 193, 0.05);
        }
        
        .quick-links .list-group-item i {
            width: 1.25rem;
            margin-right: 0.75rem;
            color: var(--gray-600);
        }
        
        .quick-links .list-group-item:hover i {
            color: var(--primary-color);
        }
        
        /* Footer */
        .footer {
            background-color: #fff;
            padding: 1.5rem 0;
            border-top: 1px solid var(--gray-200);
            font-size: 0.875rem;
            color: var(--gray-600);
        }
        
        .footer a {
            color: var(--gray-600);
            transition: color var(--transition-fast);
        }
        
        .footer a:hover {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        /* Responsive Design */
        @media (max-width: 992px) {
            .logo-text {
                font-size: 1.25rem;
            }
            
            .account-toggle .username {
                max-width: 100px;
            }
            
            .btn {
                padding: 0.4rem 0.8rem;
            }
        }
        
        @media (max-width: 768px) {
            .container {
                padding-left: 0.75rem;
                padding-right: 0.75rem;
            }
            
            .site-header {
                padding: 0.625rem 0;
            }
            
            .header-container {
                padding: 0 0.5rem;
            }
            
            .menu-toggle {
                display: flex;
            }
            
            .main-nav {
                position: fixed;
                top: 64px;
                left: 0;
                right: 0;
                background-color: white;
                box-shadow: 0 5px 10px rgba(0,0,0,0.1);
                max-height: 0;
                overflow: hidden;
                transition: max-height 0.3s ease-in-out;
                z-index: 100;
            }
            
            .main-nav.show {
                max-height: 500px;
            }
            
            .nav-list {
                flex-direction: column;
                gap: 0;
                padding: 0.75rem 0;
            }
            
            .nav-list li {
                width: 100%;
            }
            
            .nav-list a {
                padding: 0.75rem 1.5rem;
                border-left: 3px solid transparent;
                border-radius: 0;
            }
            
            .nav-list a:hover,
            .nav-list a.active {
                border-left-color: var(--primary-color);
            }
            
            .account-toggle .username,
            .account-toggle .icon {
                display: none;
            }
            
            .dropdown-menu {
                right: -0.5rem;
            }
            
            .dashboard-title {
                font-size: 1.25rem;
            }
            
            .dashboard-subtitle {
                font-size: 0.875rem;
            }
            
            .col-md-4.text-end {
                margin-top: 1rem;
                text-align: left !important;
            }
            
            .card {
                margin-bottom: 1rem;
            }
            
            .table-responsive {
                border: 0;
            }
            
            .btn-group {
                display: flex;
                flex-direction: column;
                width: 100%;
            }
            
            .btn-group .btn {
                border-radius: var(--border-radius) !important;
                margin-left: 0 !important;
                margin-bottom: 0.5rem;
            }
            
            .footer {
                text-align: center;
                padding: 1.25rem 0;
            }
            
            .footer .text-md-end {
                text-align: center !important;
                margin-top: 0.75rem;
            }
        }
        
        @media (max-width: 576px) {
            .card-header {
                padding: 0.875rem 1rem;
            }
            
            .card-body {
                padding: 1rem;
            }
            
            .card-footer {
                padding: 0.75rem 1rem;
            }
            
            h5 {
                font-size: 1rem;
            }
            
            .btn-sm {
                font-size: 0.75rem;
                padding: 0.25rem 0.5rem;
            }
            
            .profile-avatar {
                width: 80px;
                height: 80px;
            }
        }
    </style>
</head>
<body>
    <!-- Header dengan Navigasi -->
    <header class="site-header">
        <div class="container header-container">
            <!-- Logo -->
            <div class="logo-container">
                <a href="<?php echo url(); ?>" class="logo-text">
                    Guru<span class="highlight">Sinergi</span>
                </a>
            </div>
            
            <!-- Navigasi -->
            <nav class="main-nav" id="main-nav">
                <ul class="nav-list">
                    <li><a href="<?php echo url(); ?>" class="<?php echo is_active_page('/') ? 'active' : ''; ?>">Beranda</a></li>
                    <li><a href="<?php echo url('assignments/browse.php'); ?>" class="<?php echo is_active_page('/assignments/browse.php') ? 'active' : ''; ?>">Penugasan</a></li>
                    <li><a href="<?php echo url('teachers/browse.php'); ?>" class="<?php echo is_active_page('/teachers/browse.php') ? 'active' : ''; ?>">Cari Guru</a></li>
                    <li><a href="<?php echo url('materials.php'); ?>" class="<?php echo is_active_page('/materials.php') ? 'active' : ''; ?>">Materi</a></li>
                    <li><a href="<?php echo url('about.php'); ?>" class="<?php echo is_active_page('/about.php') ? 'active' : ''; ?>">Tentang</a></li>
                    <li><a href="<?php echo url('contact.php'); ?>" class="<?php echo is_active_page('/contact.php') ? 'active' : ''; ?>">Kontak</a></li>
                </ul>
            </nav>
            
            <!-- User Controls -->
            <div class="user-controls">
                <!-- User Account Dropdown -->
                <div class="account-dropdown" id="account-dropdown">
                    <button class="account-toggle" id="account-toggle">
                        <?php if (!empty($current_user['profile_image'])): ?>
                            <img src="<?php echo $current_user['profile_image']; ?>" alt="<?php echo $current_user['full_name'] ?? 'User'; ?>" class="avatar">
                        <?php else: ?>
                            <div class="avatar" style="background-color: <?php echo $current_user['user_type'] == 'guru' ? '#4F46E5' : '#10B981'; ?>;">
                                <?php echo isset($current_user['full_name']) ? substr($current_user['full_name'], 0, 1) : 'U'; ?>
                            </div>
                        <?php endif; ?>
                        <span class="username"><?php echo $current_user['full_name'] ?? 'User'; ?></span>
                        <i class="fas fa-chevron-down icon"></i>
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
                            <li><a href="<?php echo url('applications/my-applications.php'); ?>"><i class="fas fa-file-alt"></i> Lamaran Saya</a></li>
                            <li><a href="<?php echo url('assignments/my-assignments.php'); ?>"><i class="fas fa-briefcase"></i> Penugasan Saya</a></li>
                            <li><a href="<?php echo url('messages.php'); ?>"><i class="fas fa-envelope"></i> Pesan</a></li>
                            <li><a href="<?php echo url('logout.php'); ?>"><i class="fas fa-sign-out-alt"></i> Keluar</a></li>
                        </ul>
                    </div>
                </div>
                
                <!-- Mobile Menu Toggle (Hamburger) -->
                <button class="menu-toggle" id="mobile-menu-toggle">
                    <span class="bar"></span>
                    <span class="bar"></span>
                    <span class="bar"></span>
                </button>
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

            <!-- Dashboard Content -->
            <div class="dashboard-container">
                <?php if ($verification_status !== 'verified'): ?>
                <div class="alert alert-<?php echo $verification_status === 'pending' ? 'warning' : 'danger'; ?> alert-dismissible fade show" role="alert">
                    <?php if ($verification_status === 'pending'): ?>
                    <i class="fas fa-exclamation-circle"></i> <strong>Akun Anda belum terverifikasi.</strong> Beberapa fitur mungkin terbatas hingga dokumen Anda diverifikasi.
                    <a href="/verifications/status.php" class="alert-link">Periksa status verifikasi</a>
                    <?php else: ?>
                    <i class="fas fa-times-circle"></i> <strong>Verifikasi akun Anda ditolak.</strong> Silakan periksa kembali dokumen Anda dan upload ulang.
                    <a href="/verifications/status.php" class="alert-link">Lihat detail penolakan</a>
                    <?php endif; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <div class="dashboard-header">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="dashboard-title">Dashboard Guru</h1>
                            <p class="dashboard-subtitle">Selamat datang kembali, <?php echo htmlspecialchars($teacher_profile['full_name']); ?>!</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="btn-group">
                                <a href="/teachers/assignments.php" class="btn btn-primary">
                                    <i class="fas fa-clipboard-list"></i> Lihat Semua Penugasan
                                </a>
                                <a href="/assignments/browse.php" class="btn btn-outline-primary">
                                    <i class="fas fa-search"></i> Cari Penugasan
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <!-- Left Column (Main Content) -->
                    <div class="col-lg-8">
                        <!-- Active Assignments -->
                        <div class="card">
                            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-chalkboard-teacher me-2"></i> Penugasan Aktif
                                </h5>
                                <span class="badge bg-light text-dark"><?php echo count($active_assignments); ?> penugasan</span>
                            </div>
                            
                            <div class="card-body p-0">
                                <?php if (empty($active_assignments)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-clipboard-list empty-state-icon"></i>
                                    <h5 class="empty-state-title">Tidak Ada Penugasan Aktif</h5>
                                    <p class="empty-state-description">
                                        Anda tidak memiliki penugasan aktif saat ini.<br>
                                        Cari penugasan baru atau tunggu konfirmasi dari aplikasi yang Anda kirim.
                                    </p>
                                    <a href="/assignments/browse.php" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Cari Penugasan
                                    </a>
                                </div>
                                <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Penugasan</th>
                                                <th>Sekolah</th>
                                                <th>Periode</th>
                                                <th>Status</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($active_assignments as $assignment): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($assignment['title']); ?></div>
                                                    <div class="small text-muted"><?php echo htmlspecialchars($assignment['subject']); ?> - <?php echo htmlspecialchars($assignment['grade']); ?></div>
                                                </td>
                                                <td>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($assignment['school_name']); ?></div>
                                                    <div class="small text-muted"><?php echo htmlspecialchars(substr($assignment['school_address'], 0, 30)); ?>...</div>
                                                </td>
                                                <td>
                                                    <div><?php echo format_date($assignment['start_date']); ?> - <?php echo format_date($assignment['end_date']); ?></div>
                                                    <div class="small text-muted"><?php echo format_time($assignment['start_time']); ?> - <?php echo format_time($assignment['end_time']); ?></div>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $is_today = (date('Y-m-d') === $assignment['last_attendance_date']);
                                                    if ($is_today): 
                                                    ?>
                                                    <span class="badge bg-success">Check-in Hari Ini</span>
                                                    <?php else: ?>
                                                    <span class="badge bg-warning">Perlu Check-in</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="/assignments/detail.php?id=<?php echo $assignment['id']; ?>" class="btn btn-outline-primary">
                                                            <i class="fas fa-info-circle"></i> Detail
                                                        </a>
                                                        <?php if (!$is_today): ?>
                                                        <a href="/locations/check-in.php?assignment_id=<?php echo $assignment['id']; ?>" class="btn btn-success">
                                                            <i class="fas fa-check"></i> Check-in
                                                        </a>
                                                        <?php else: ?>
                                                        <a href="/locations/check-in.php?assignment_id=<?php echo $assignment['id']; ?>&type=out" class="btn btn-warning">
                                                            <i class="fas fa-sign-out-alt"></i> Check-out
                                                        </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($active_assignments)): ?>
                            <div class="card-footer text-end">
                                <a href="/teachers/attendance.php" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-history"></i> Riwayat Kehadiran
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Upcoming Assignments -->
                        <?php if (!empty($upcoming_assignments)): ?>
                        <div class="card">
                            <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-calendar-alt me-2"></i> Penugasan Mendatang
                                </h5>
                                <span class="badge bg-light text-dark"><?php echo count($upcoming_assignments); ?> penugasan</span>
                            </div>
                            
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Penugasan</th>
                                                <th>Sekolah</th>
                                                <th>Periode</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($upcoming_assignments as $assignment): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($assignment['title']); ?></div>
                                                    <div class="small text-muted"><?php echo htmlspecialchars($assignment['subject']); ?> - <?php echo htmlspecialchars($assignment['grade']); ?></div>
                                                </td>
                                                <td>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($assignment['school_name']); ?></div>
                                                    <div class="small text-muted"><?php echo htmlspecialchars(substr($assignment['school_address'], 0, 30)); ?>...</div>
                                                </td>
                                                <td>
                                                    <div><?php echo format_date($assignment['start_date']); ?> - <?php echo format_date($assignment['end_date']); ?></div>
                                                    <div class="small text-muted"><?php echo format_time($assignment['start_time']); ?> - <?php echo format_time($assignment['end_time']); ?></div>
                                                </td>
                                                <td>
                                                    <a href="/assignments/detail.php?id=<?php echo $assignment['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-info-circle"></i> Detail
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Right Column (Sidebar) -->
                    <div class="col-lg-4">
                        <!-- Profile Card -->
                        <div class="card profile-card">
                            <div class="card-body">
                                <img src="<?php echo $teacher_profile['profile_picture'] ?: '../assets/img/default-avatar.png'; ?>" 
                                    alt="<?php echo htmlspecialchars($teacher_profile['full_name']); ?>" 
                                    class="profile-avatar">
                                
                                <h5 class="mb-1"><?php echo htmlspecialchars($teacher_profile['full_name']); ?></h5>
                                <p class="text-muted mb-3"><?php echo htmlspecialchars($teacher_profile['subject_expertise']); ?></p>
                                
                                <div class="mb-3">
                                    <?php if ($verification_status === 'verified'): ?>
                                    <span class="badge bg-success">
                                        <i class="fas fa-check-circle me-1"></i> Terverifikasi
                                    </span>
                                    <?php elseif ($verification_status === 'pending'): ?>
                                    <span class="badge bg-warning text-dark">
                                        <i class="fas fa-clock me-1"></i> Menunggu Verifikasi
                                    </span>
                                    <?php else: ?>
                                    <span class="badge bg-danger">
                                        <i class="fas fa-times-circle me-1"></i> Verifikasi Ditolak
                                    </span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <a href="/teachers/profile.php" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-user-edit"></i> Edit Profil
                                    </a>
                                    <a href="/verifications/status.php" class="btn btn-outline-info btn-sm">
                                        <i class="fas fa-id-card"></i> Status Verifikasi
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Quick Links -->
                        <div class="card quick-links">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0">Menu Cepat</h5>
                            </div>
                            <div class="list-group list-group-flush">
                                <a href="/teachers/assignments.php" class="list-group-item list-group-item-action">
                                    <i class="fas fa-clipboard-list"></i> Penugasan Saya
                                </a>
                                <a href="/assignments/browse.php" class="list-group-item list-group-item-action">
                                    <i class="fas fa-search"></i> Cari Penugasan
                                </a>
                                <a href="/teachers/attendance.php" class="list-group-item list-group-item-action">
                                    <i class="fas fa-calendar-check"></i> Riwayat Kehadiran
                                </a>
                                <a href="/teachers/payments.php" class="list-group-item list-group-item-action">
                                    <i class="fas fa-money-bill-wave"></i> Riwayat Pembayaran
                                </a>
                                <a href="/chat/inbox.php" class="list-group-item list-group-item-action">
                                    <i class="fas fa-comments"></i> Pesan
                                </a>
                                <a href="/materials.php" class="list-group-item list-group-item-action">
                                    <i class="fas fa-book"></i> Materi Pembelajaran
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> GuruSinergi. Hak Cipta Dilindungi.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="<?php echo url('about.php'); ?>" class="me-3">Tentang Kami</a>
                    <a href="<?php echo url('contact.php'); ?>" class="me-3">Kontak</a>
                    <a href="<?php echo url('privacy.php'); ?>" class="me-3">Kebijakan Privasi</a>
                    <a href="<?php echo url('terms.php'); ?>">Syarat & Ketentuan</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Account dropdown functionality
            const accountToggle = document.getElementById('account-toggle');
            const accountDropdown = document.getElementById('account-dropdown');
            
            if (accountToggle && accountDropdown) {
                const dropdownMenu = accountDropdown.querySelector('.dropdown-menu');
                
                // Toggle dropdown saat tombol akun diklik
                accountToggle.addEventListener('click', function(event) {
                    event.preventDefault();
                    event.stopPropagation();
                    
                    const isVisible = dropdownMenu.classList.contains('show');
                    
                    // Tutup dropdown lain jika ada yang terbuka
                    document.querySelectorAll('.dropdown-menu.show').forEach(function(menu) {
                        if (menu !== dropdownMenu) {
                            menu.classList.remove('show');
                        }
                    });
                    
                    // Toggle dropdown sendiri
                    dropdownMenu.classList.toggle('show');
                    
                    // Ubah ikon arrow
                    const arrowIcon = accountToggle.querySelector('.icon');
                    if (arrowIcon) {
                        if (!isVisible) {
                            arrowIcon.classList.remove('fa-chevron-down');
                            arrowIcon.classList.add('fa-chevron-up');
                        } else {
                            arrowIcon.classList.remove('fa-chevron-up');
                            arrowIcon.classList.add('fa-chevron-down');
                        }
                    }
                });
                
                // Tutup dropdown jika klik di luar
                document.addEventListener('click', function(event) {
                    if (!accountDropdown.contains(event.target)) {
                        dropdownMenu.classList.remove('show');
                        
                        // Reset ikon arrow
                        const arrowIcon = accountToggle.querySelector('.icon');
                        if (arrowIcon && arrowIcon.classList.contains('fa-chevron-up')) {
                            arrowIcon.classList.remove('fa-chevron-up');
                            arrowIcon.classList.add('fa-chevron-down');
                        }
                    }
                });
            }
            
            // Mobile menu toggle
            const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
            const mainNav = document.getElementById('main-nav');
            
            if (mobileMenuToggle && mainNav) {
                mobileMenuToggle.addEventListener('click', function(event) {
                    event.preventDefault();
                    event.stopPropagation();
                    
                    mobileMenuToggle.classList.toggle('active');
                    mainNav.classList.toggle('show');
                    
                    // Tutup dropdown jika menu mobile dibuka
                    const dropdownMenus = document.querySelectorAll('.dropdown-menu');
                    dropdownMenus.forEach(function(menu) {
                        menu.classList.remove('show');
                    });
                    
                    // Reset ikon arrows
                    const arrowIcons = document.querySelectorAll('.account-toggle .icon');
                    arrowIcons.forEach(function(icon) {
                        if (icon.classList.contains('fa-chevron-up')) {
                            icon.classList.remove('fa-chevron-up');
                            icon.classList.add('fa-chevron-down');
                        }
                    });
                });
                
                // Klik di luar menu mobile untuk menutup
                document.addEventListener('click', function(event) {
                    if (mainNav.classList.contains('show') && 
                        !mainNav.contains(event.target) && 
                        !mobileMenuToggle.contains(event.target)) {
                        mainNav.classList.remove('show');
                        mobileMenuToggle.classList.remove('active');
                    }
                });
                
                // Item menu mobile - tutup menu setelah klik
                const navItems = mainNav.querySelectorAll('.nav-list a');
                navItems.forEach(function(item) {
                    item.addEventListener('click', function() {
                        mainNav.classList.remove('show');
                        mobileMenuToggle.classList.remove('active');
                    });
                });
            }
            
            // Tutup alert saat tombol close diklik
            const alertCloseButtons = document.querySelectorAll('.alert .btn-close');
            
            if (alertCloseButtons.length > 0) {
                alertCloseButtons.forEach(function(button) {
                    button.addEventListener('click', function() {
                        const alert = this.closest('.alert');
                        alert.classList.remove('show');
                        setTimeout(function() {
                            alert.style.display = 'none';
                        }, 150);
                    });
                });
            }
            
            // Aktifkan tab sesuai URL saat ini
            const activateTabs = function() {
                const currentUrl = window.location.pathname;
                
                document.querySelectorAll('.nav-list a').forEach(function(link) {
                    const href = link.getAttribute('href');
                    
                    if (href && currentUrl.includes(href) && href !== '/') {
                        link.classList.add('active');
                    } else if (href === '/' && currentUrl === '/') {
                        link.classList.add('active');
                    }
                });
            };
            
            activateTabs();
        });
    </script>
</body>
</html>
<?php
// Flush remaining buffer
ob_end_flush();
?>