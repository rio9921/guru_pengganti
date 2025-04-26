<?php
/**
 * GuruSinergi - Complete Profile Page (Gabungan)
 * 
 * File lengkap yang menggabungkan complete-profile dengan header dan footer
 * Dirancang untuk bekerja tanpa perlu file external
 */

// Aktifkan tampilan error untuk debugging (jika diperlukan)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ========== BAGIAN KONFIGURASI DAN FUNGSI ==========

// Mulai session jika belum dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Konfigurasi dasar
$config = [
    'site_url' => 'https://pengganti.gurusinergi.com',
    'main_site_url' => 'https://gurusinergi.com',
    'site_name' => 'GuruSinergi - Program Guru Pengganti',
    'admin_email' => 'admin@gurusinergi.com',
    'support_phone' => '+62 895 1300 5831',
    
    // Database
    'db_host' => 'localhost',
    'db_name' => 'u532109326_guru_pengganti',
    'db_user' => 'u532109326_gurupengganti',
    'db_pass' => 'Gurukupahlawanku99',
];

// Fungsi koneksi database
function db_connect() {
    global $config;
    try {
        $conn = new PDO(
            "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8",
            $config['db_user'],
            $config['db_pass']
        );
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch (PDOException $e) {
        // Log error tetapi kembalikan null
        error_log("Database connection error: " . $e->getMessage());
        return null;
    }
}

// Fungsi sanitize input
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Fungsi untuk URL absolut
function url($path = '') {
    global $config;
    return rtrim($config['site_url'], '/') . '/' . ltrim($path, '/');
}

// Fungsi untuk asset URL
function asset($path = '') {
    global $config;
    return rtrim($config['site_url'], '/') . '/assets/' . ltrim($path, '/');
}

// Fungsi untuk redirect
function redirect($url) {
    header("Location: $url");
    exit;
}

// Fungsi untuk cek login
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Fungsi untuk cek akses
function check_access($role = 'all') {
    if (!is_logged_in()) {
        set_error_message('Silakan login terlebih dahulu.');
        redirect(url('login.php'));
    }
    
    if ($role != 'all') {
        if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != $role) {
            set_error_message('Anda tidak memiliki akses ke halaman ini.');
            redirect(url('dashboard.php'));
        }
    }
}

// Fungsi untuk set error message
function set_error_message($message) {
    $_SESSION['error_message'] = $message;
}

// Fungsi untuk set success message
function set_success_message($message) {
    $_SESSION['success_message'] = $message;
}

// Fungsi untuk display messages
function display_messages() {
    $output = '';
    
    if (isset($_SESSION['error_message'])) {
        $output .= '<div class="alert alert-danger">' . $_SESSION['error_message'] . '</div>';
        unset($_SESSION['error_message']);
    }
    
    if (isset($_SESSION['success_message'])) {
        $output .= '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
        unset($_SESSION['success_message']);
    }
    
    echo $output;
}

// Fungsi untuk ambil data user
function get_app_current_user() {
    if (!is_logged_in()) {
        return null;
    }
    
    try {
        $conn = db_connect();
        if (!$conn) {
            return [
                'id' => $_SESSION['user_id'],
                'user_type' => $_SESSION['user_type'],
                'email' => $_SESSION['email'] ?? '',
                'full_name' => $_SESSION['name'] ?? 'User'
            ];
        }
        
        $user_id = $_SESSION['user_id'];
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return [
                'id' => $_SESSION['user_id'],
                'user_type' => $_SESSION['user_type'],
                'email' => $_SESSION['email'] ?? '',
                'full_name' => $_SESSION['name'] ?? 'User'
            ];
        }
        
        // Tambahkan data profil jika ada
        if ($user['user_type'] == 'guru') {
            $profile_stmt = $conn->prepare("SELECT * FROM profiles_guru WHERE user_id = ?");
            $profile_stmt->execute([$user_id]);
            $profile = $profile_stmt->fetch(PDO::FETCH_ASSOC);
            if ($profile) {
                $user['profile'] = $profile;
            }
        } else if ($user['user_type'] == 'sekolah') {
            $profile_stmt = $conn->prepare("SELECT * FROM profiles_sekolah WHERE user_id = ?");
            $profile_stmt->execute([$user_id]);
            $profile = $profile_stmt->fetch(PDO::FETCH_ASSOC);
            if ($profile) {
                $user['profile'] = $profile;
            }
        }
        
        return $user;
    } catch (Exception $e) {
        error_log("Error getting user data: " . $e->getMessage());
        return [
            'id' => $_SESSION['user_id'],
            'user_type' => $_SESSION['user_type'],
            'email' => $_SESSION['email'] ?? '',
            'full_name' => $_SESSION['name'] ?? 'User'
        ];
    }
}

// Fungsi untuk cek profil lengkap
function is_profile_completed($user) {
    if (!$user || !isset($user['id']) || !isset($user['user_type'])) {
        return false;
    }
    
    try {
        $conn = db_connect();
        if (!$conn) return false;
        
        if ($user['user_type'] == 'guru') {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM profiles_guru WHERE user_id = ? AND pendidikan IS NOT NULL");
            $stmt->execute([$user['id']]);
            return $stmt->fetchColumn() > 0;
        } else if ($user['user_type'] == 'sekolah') {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM profiles_sekolah WHERE user_id = ? AND nama_sekolah IS NOT NULL");
            $stmt->execute([$user['id']]);
            return $stmt->fetchColumn() > 0;
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Error checking profile completion: " . $e->getMessage());
        return false;
    }
}

// Fungsi untuk upload file
function upload_file($file, $upload_dir, $allowed_extensions = []) {
    // Periksa apakah file berhasil diupload
    if ($file['error'] !== 0) {
        return ['status' => false, 'message' => 'Error uploading file: ' . $file['error']];
    }
    
    // Periksa ukuran file (max 5MB)
    $max_size = 5 * 1024 * 1024;
    if ($file['size'] > $max_size) {
        return ['status' => false, 'message' => 'File terlalu besar. Maksimal 5MB'];
    }
    
    // Periksa ekstensi file
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!empty($allowed_extensions) && !in_array($file_ext, $allowed_extensions)) {
        return ['status' => false, 'message' => 'Tipe file tidak diizinkan. Tipe yang diizinkan: ' . implode(', ', $allowed_extensions)];
    }
    
    // Buat direktori jika belum ada
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            return ['status' => false, 'message' => 'Gagal membuat direktori upload'];
        }
    }
    
    // Buat nama file unik
    $new_filename = uniqid() . '.' . $file_ext;
    $target_path = $upload_dir . $new_filename;
    
    // Upload file
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        return ['status' => true, 'file_path' => $target_path];
    } else {
        return ['status' => false, 'message' => 'Gagal memindahkan file yang diupload'];
    }
}

// Fungsi untuk mendapatkan opsi mata pelajaran
function get_mata_pelajaran_options() {
    return [
        'matematika' => 'Matematika',
        'bahasa_indonesia' => 'Bahasa Indonesia',
        'bahasa_inggris' => 'Bahasa Inggris',
        'ipa' => 'IPA',
        'ips' => 'IPS',
        'fisika' => 'Fisika',
        'kimia' => 'Kimia',
        'biologi' => 'Biologi',
        'ekonomi' => 'Ekonomi',
        'sejarah' => 'Sejarah',
        'geografi' => 'Geografi',
        'pkn' => 'PKN',
        'agama_islam' => 'Agama Islam',
        'agama_kristen' => 'Agama Kristen',
        'agama_katolik' => 'Agama Katolik',
        'agama_hindu' => 'Agama Hindu',
        'agama_buddha' => 'Agama Buddha',
        'seni_budaya' => 'Seni Budaya',
        'penjaskes' => 'Penjaskes',
        'prakarya' => 'Prakarya',
        'tik' => 'TIK',
        'lainnya' => 'Lainnya'
    ];
}

// Fungsi untuk mendapatkan opsi tingkat kelas
function get_tingkat_kelas_options() {
    return [
        'sd' => 'SD',
        'smp' => 'SMP',
        'sma' => 'SMA',
        'smk' => 'SMK',
        'tk' => 'TK/PAUD',
        'semua' => 'Semua Tingkat'
    ];
}

// Fungsi untuk mendapatkan opsi jenis sekolah
function get_jenis_sekolah_options() {
    return [
        'tk' => 'TK/PAUD',
        'sd' => 'SD/MI',
        'smp' => 'SMP/MTs',
        'sma' => 'SMA/MA',
        'smk' => 'SMK',
        'lainnya' => 'Lainnya'
    ];
}

// Fungsi untuk mengecek active page
function is_active_page($path) {
    $current_path = $_SERVER['REQUEST_URI'] ?? '';
    return strpos($current_path, $path) !== false;
}

// Fungsi untuk notifikasi
function get_unread_notifications_count($user_id) {
    return 0; // Stub function
}

function get_notifications($user_id, $limit = 5) {
    return []; // Stub function
}

// ========== BAGIAN LOGIKA COMPLETE PROFILE ==========

// Cek login user
check_access('all');

// Inisialisasi variabel user
$current_user = get_app_current_user();

// Jika profil sudah lengkap, redirect ke dashboard
if (is_profile_completed($current_user)) {
    redirect(url('dashboard.php'));
}

// Pastikan direktori upload ada
$guru_upload_dir = 'uploads/guru/';
$sekolah_upload_dir = 'uploads/sekolah/';

if (!is_dir($guru_upload_dir)) {
    mkdir($guru_upload_dir, 0755, true);
}

if (!is_dir($sekolah_upload_dir)) {
    mkdir($sekolah_upload_dir, 0755, true);
}

// Handle formulir lengkapi profil guru
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['complete_profile_guru'])) {
    if ($current_user['user_type'] != 'guru') {
        set_error_message('Anda tidak memiliki akses ke halaman ini.');
        redirect(url('dashboard.php'));
    }
    
    $pendidikan = sanitize($_POST['pendidikan'] ?? '');
    $pengalaman = sanitize($_POST['pengalaman'] ?? '');
    $keahlian = sanitize($_POST['keahlian'] ?? '');
    $mata_pelajaran = sanitize($_POST['mata_pelajaran'] ?? '');
    $tingkat_mengajar = sanitize($_POST['tingkat_mengajar'] ?? '');
    
    // Validasi input
    if (empty($pendidikan) || empty($pengalaman) || empty($keahlian) || empty($mata_pelajaran) || empty($tingkat_mengajar)) {
        set_error_message('Semua field harus diisi.');
    } else {
        // Upload file
        $cv_file = isset($_FILES['dokumen_cv']) ? $_FILES['dokumen_cv'] : null;
        $ijazah_file = isset($_FILES['dokumen_ijazah']) ? $_FILES['dokumen_ijazah'] : null;
        $ktp_file = isset($_FILES['dokumen_ktp']) ? $_FILES['dokumen_ktp'] : null;
        
        $upload_dir = 'uploads/guru/' . $current_user['id'] . '/';
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Default values
        $cv_path = '';
        $ijazah_path = '';
        $ktp_path = '';
        $upload_success = true;
        
        // Upload CV jika ada
        if ($cv_file && $cv_file['error'] == 0) {
            $cv_upload = upload_file($cv_file, $upload_dir, ['pdf', 'doc', 'docx']);
            if (!$cv_upload['status']) {
                set_error_message($cv_upload['message']);
                $upload_success = false;
            } else {
                $cv_path = $cv_upload['file_path'];
            }
        }
        
        // Upload Ijazah jika berhasil CV dan ada file
        if ($upload_success && $ijazah_file && $ijazah_file['error'] == 0) {
            $ijazah_upload = upload_file($ijazah_file, $upload_dir, ['pdf', 'jpg', 'jpeg', 'png']);
            if (!$ijazah_upload['status']) {
                set_error_message($ijazah_upload['message']);
                $upload_success = false;
            } else {
                $ijazah_path = $ijazah_upload['file_path'];
            }
        }
        
        // Upload KTP jika berhasil Ijazah dan ada file
        if ($upload_success && $ktp_file && $ktp_file['error'] == 0) {
            $ktp_upload = upload_file($ktp_file, $upload_dir, ['jpg', 'jpeg', 'png']);
            if (!$ktp_upload['status']) {
                set_error_message($ktp_upload['message']);
                $upload_success = false;
            } else {
                $ktp_path = $ktp_upload['file_path'];
            }
        }
        
        // Jika upload sukses, simpan ke database
        if ($upload_success) {
            $conn = db_connect();
            if ($conn) {
                try {
                    // Cek apakah profil sudah ada
                    $check_stmt = $conn->prepare("SELECT COUNT(*) FROM profiles_guru WHERE user_id = ?");
                    $check_stmt->execute([$current_user['id']]);
                    $exists = $check_stmt->fetchColumn() > 0;
                    
                    if ($exists) {
                        // Update profil yang sudah ada
                        $stmt = $conn->prepare("UPDATE profiles_guru SET 
                            pendidikan = ?, 
                            pengalaman = ?, 
                            keahlian = ?, 
                            mata_pelajaran = ?, 
                            tingkat_mengajar = ?, 
                            dokumen_cv = ?, 
                            dokumen_ijazah = ?, 
                            dokumen_ktp = ? 
                            WHERE user_id = ?");
                        
                        if ($stmt->execute([
                            $pendidikan, $pengalaman, $keahlian, $mata_pelajaran, $tingkat_mengajar,
                            $cv_path, $ijazah_path, $ktp_path, $current_user['id']
                        ])) {
                            set_success_message('Profil berhasil dilengkapi! Silakan tunggu verifikasi dari admin.');
                            
                            // Redirect ke dashboard
                            redirect(url('dashboard.php'));
                        } else {
                            set_error_message('Terjadi kesalahan saat menyimpan profil. Silakan coba lagi.');
                        }
                    } else {
                        // Insert profil baru
                        $stmt = $conn->prepare("INSERT INTO profiles_guru 
                            (user_id, pendidikan, pengalaman, keahlian, mata_pelajaran, tingkat_mengajar, 
                            dokumen_cv, dokumen_ijazah, dokumen_ktp, status, created_at) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
                        
                        if ($stmt->execute([
                            $current_user['id'], $pendidikan, $pengalaman, $keahlian, $mata_pelajaran, $tingkat_mengajar,
                            $cv_path, $ijazah_path, $ktp_path
                        ])) {
                            set_success_message('Profil berhasil dilengkapi! Silakan tunggu verifikasi dari admin.');
                            
                            // Redirect ke dashboard
                            redirect(url('dashboard.php'));
                        } else {
                            set_error_message('Terjadi kesalahan saat menyimpan profil. Silakan coba lagi.');
                        }
                    }
                } catch (PDOException $e) {
                    error_log("Error saving profile: " . $e->getMessage());
                    set_error_message('Terjadi kesalahan database. Silakan coba lagi nanti.');
                }
            } else {
                set_error_message('Tidak dapat terhubung ke database. Silakan coba lagi nanti.');
            }
        }
    }
}

// Handle formulir lengkapi profil sekolah
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['complete_profile_sekolah'])) {
    if ($current_user['user_type'] != 'sekolah') {
        set_error_message('Anda tidak memiliki akses ke halaman ini.');
        redirect(url('dashboard.php'));
    }
    
    $nama_sekolah = sanitize($_POST['nama_sekolah'] ?? '');
    $jenis_sekolah = sanitize($_POST['jenis_sekolah'] ?? '');
    $alamat_lengkap = sanitize($_POST['alamat_lengkap'] ?? '');
    $kecamatan = sanitize($_POST['kecamatan'] ?? '');
    $kota = sanitize($_POST['kota'] ?? '');
    $provinsi = sanitize($_POST['provinsi'] ?? '');
    $kode_pos = sanitize($_POST['kode_pos'] ?? '');
    $contact_person = sanitize($_POST['contact_person'] ?? '');
    $website = sanitize($_POST['website'] ?? '');
    
    // Validasi input
    if (empty($nama_sekolah) || empty($jenis_sekolah) || empty($alamat_lengkap) || empty($kecamatan) || empty($kota) || empty($provinsi) || empty($kode_pos) || empty($contact_person)) {
        set_error_message('Semua field harus diisi kecuali website.');
    } else {
        // Upload dokumen NPSN
        $npsn_file = isset($_FILES['dokumen_npsn']) ? $_FILES['dokumen_npsn'] : null;
        $upload_dir = 'uploads/sekolah/' . $current_user['id'] . '/';
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Default values
        $npsn_path = '';
        $upload_success = true;
        
        // Upload NPSN jika ada
        if ($npsn_file && $npsn_file['error'] == 0) {
            $npsn_upload = upload_file($npsn_file, $upload_dir, ['pdf', 'jpg', 'jpeg', 'png']);
            if (!$npsn_upload['status']) {
                set_error_message($npsn_upload['message']);
                $upload_success = false;
            } else {
                $npsn_path = $npsn_upload['file_path'];
            }
        }
        
        // Jika upload sukses, simpan ke database
        if ($upload_success) {
            $conn = db_connect();
            if ($conn) {
                try {
                    // Cek apakah profil sudah ada
                    $check_stmt = $conn->prepare("SELECT COUNT(*) FROM profiles_sekolah WHERE user_id = ?");
                    $check_stmt->execute([$current_user['id']]);
                    $exists = $check_stmt->fetchColumn() > 0;
                    
                    if ($exists) {
                        // Update profil yang sudah ada
                        $stmt = $conn->prepare("UPDATE profiles_sekolah SET 
                            nama_sekolah = ?, 
                            jenis_sekolah = ?, 
                            alamat_lengkap = ?, 
                            kecamatan = ?, 
                            kota = ?, 
                            provinsi = ?, 
                            kode_pos = ?, 
                            contact_person = ?, 
                            website = ?, 
                            dokumen_npsn = ? 
                            WHERE user_id = ?");
                        
                        if ($stmt->execute([
                            $nama_sekolah, $jenis_sekolah, $alamat_lengkap, $kecamatan, $kota, $provinsi,
                            $kode_pos, $contact_person, $website, $npsn_path, $current_user['id']
                        ])) {
                            set_success_message('Profil sekolah berhasil dilengkapi! Silakan tunggu verifikasi dari admin.');
                            
                            // Redirect ke dashboard
                            redirect(url('dashboard.php'));
                        } else {
                            set_error_message('Terjadi kesalahan saat menyimpan profil sekolah. Silakan coba lagi.');
                        }
                    } else {
                        // Insert profil baru
                        $stmt = $conn->prepare("INSERT INTO profiles_sekolah 
                            (user_id, nama_sekolah, jenis_sekolah, alamat_lengkap, kecamatan, kota, 
                            provinsi, kode_pos, contact_person, website, dokumen_npsn, status, created_at) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
                        
                        if ($stmt->execute([
                            $current_user['id'], $nama_sekolah, $jenis_sekolah, $alamat_lengkap, $kecamatan, $kota, $provinsi,
                            $kode_pos, $contact_person, $website, $npsn_path
                        ])) {
                            set_success_message('Profil sekolah berhasil dilengkapi! Silakan tunggu verifikasi dari admin.');
                            
                            // Redirect ke dashboard
                            redirect(url('dashboard.php'));
                        } else {
                            set_error_message('Terjadi kesalahan saat menyimpan profil sekolah. Silakan coba lagi.');
                        }
                    }
                } catch (PDOException $e) {
                    error_log("Error saving profile: " . $e->getMessage());
                    set_error_message('Terjadi kesalahan database. Silakan coba lagi nanti.');
                }
            } else {
                set_error_message('Tidak dapat terhubung ke database. Silakan coba lagi nanti.');
            }
        }
    }
}

// Set variabel untuk page title
$page_title = 'Lengkapi Profil';
$unread_notifications = 0;
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
    
    <!-- Bootstrap CSS untuk fallback -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
    
    <!-- CSS -->
    <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">
    
    <!-- CSS inline untuk memastikan halaman bekerja -->
    <style>
        /* CSS dasar yang diperlukan */
        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f9f9f9;
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
            margin-bottom: 30px;
        }
        .header-wrapper {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo a {
            text-decoration: none;
        }
        .logo-text {
            font-family: 'Montserrat', sans-serif;
            font-size: 24px;
            font-weight: 700;
            color: #333;
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
        .auth-buttons .btn, .auth-mobile .btn {
            padding: 8px 20px;
            border-radius: 5px;
            font-weight: 500;
            margin-left: 10px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background-color: #6f42c1;
            color: white;
            border: none;
        }
        .btn-primary:hover {
            background-color: #5a32a3;
        }
        .btn-outline {
            background-color: transparent;
            color: #6f42c1;
            border: 1px solid #6f42c1;
        }
        .btn-outline:hover {
            background-color: #f0ebfa;
        }
        .main-content {
            padding: 30px 0;
        }
        .card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            overflow: hidden;
        }
        .card-header {
            padding: 15px 20px;
            border-bottom: 1px solid #f1f1f1;
            background-color: #6f42c1;
            color: white;
        }
        .card-body {
            padding: 20px;
        }
        .form-label {
            font-weight: 500;
            margin-bottom: 5px;
            display: block;
        }
        .form-control, .form-select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 5px;
        }
        .form-text {
            font-size: 12px;
            color: #888;
        }
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .auth-mobile {
            display: none;
        }
        .menu-toggle {
            display: none;
            flex-direction: column;
            justify-content: space-between;
            width: 30px;
            height: 21px;
            cursor: pointer;
        }
        .menu-toggle .bar {
            height: 3px;
            width: 100%;
            background-color: #333;
            border-radius: 3px;
        }
        
        /* Media queries */
        @media (max-width: 992px) {
            .main-nav .nav-list {
                display: none;
                flex-direction: column;
                position: absolute;
                top: 100%;
                left: 0;
                width: 100%;
                background-color: #fff;
                padding: 20px;
                box-shadow: 0 5px 10px rgba(0,0,0,0.1);
                z-index: 100;
            }
            .main-nav .nav-list.active {
                display: flex;
            }
            .main-nav .nav-list li {
                margin: 10px 0;
            }
            .auth-buttons {
                display: none;
            }
            .auth-mobile {
                display: block;
                margin-top: 20px;
            }
            .menu-toggle {
                display: flex;
            }
            .header-wrapper {
                flex-wrap: wrap;
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
                <!-- Tombol Dashboard dan Keluar untuk desktop -->
                <div class="auth-buttons">
                    <a href="<?php echo url('dashboard.php'); ?>" class="btn btn-primary">Dashboard</a>
                    <a href="<?php echo url('logout.php'); ?>" class="btn btn-outline">Keluar</a>
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
            <?php display_messages(); ?>
            
            <!-- Debug Info (Jika perlu) -->
            <?php if (isset($_GET['debug']) && $_GET['debug'] == '1'): ?>
            <div class="card mb-4">
                <div class="card-header">Debug Info</div>
                <div class="card-body">
                    <p>PHP Version: <?php echo phpversion(); ?></p>
                    <p>Memory Limit: <?php echo ini_get('memory_limit'); ?></p>
                    <p>Current File: <?php echo __FILE__; ?></p>
                    <p>Session ID: <?php echo session_id(); ?></p>
                    <p>User ID: <?php echo $current_user['id'] ?? 'Not set'; ?></p>
                    <p>User Type: <?php echo $current_user['user_type'] ?? 'Not set'; ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="page-header text-center mb-4">
                <h1 class="page-title">Lengkapi Profil Anda</h1>
                <p class="page-description text-muted">Satu langkah lagi sebelum Anda dapat mulai menggunakan platform kami</p>
            </div>

            <?php if (isset($current_user['user_type']) && $current_user['user_type'] == 'guru'): ?>
            <!-- Form untuk Guru -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h2 class="card-title h5 mb-0">Profil Guru</h2>
                </div>
                <div class="card-body">
                    <form method="post" action="" enctype="multipart/form-data" data-validate="true">
                        <div class="form-group mb-3">
                            <label for="pendidikan" class="form-label fw-bold">Pendidikan <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="pendidikan" name="pendidikan" rows="3" required placeholder="Contoh: S1 Pendidikan Matematika, Universitas Indonesia, 2015-2019"></textarea>
                            <div class="form-text">Tuliskan riwayat pendidikan formal Anda</div>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="pengalaman" class="form-label fw-bold">Pengalaman Mengajar <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="pengalaman" name="pengalaman" rows="3" required placeholder="Contoh: Guru Matematika SMP Negeri 1 Jakarta (2019-2023)"></textarea>
                            <div class="form-text">Tuliskan pengalaman mengajar Anda beserta durasi waktu</div>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="keahlian" class="form-label fw-bold">Keahlian <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="keahlian" name="keahlian" rows="3" required placeholder="Contoh: Matematika Dasar, Aljabar, Geometri, Metode Mengajar Interaktif"></textarea>
                            <div class="form-text">Tuliskan bidang keahlian khusus yang Anda miliki</div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-12 col-md-6 mb-3 mb-md-0">
                                <div class="form-group">
                                    <label for="mata_pelajaran" class="form-label fw-bold">Mata Pelajaran <span class="text-danger">*</span></label>
                                    <select name="mata_pelajaran" id="mata_pelajaran" class="form-select" required>
                                        <option value="">-- Pilih Mata Pelajaran --</option>
                                        <?php foreach (get_mata_pelajaran_options() as $value => $label): ?>
                                            <option value="<?php echo htmlspecialchars($value); ?>"><?php echo htmlspecialchars($label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <label for="tingkat_mengajar" class="form-label fw-bold">Tingkat Mengajar <span class="text-danger">*</span></label>
                                    <select name="tingkat_mengajar" id="tingkat_mengajar" class="form-select" required>
                                        <option value="">-- Pilih Tingkat Mengajar --</option>
                                        <?php foreach (get_tingkat_kelas_options() as $value => $label): ?>
                                            <option value="<?php echo htmlspecialchars($value); ?>"><?php echo htmlspecialchars($label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-3 bg-light">
                            <div class="card-header">
                                <h3 class="h6 mb-0">Dokumen Pendukung</h3>
                            </div>
                            <div class="card-body">
                                <div class="form-group mb-3">
                                    <label for="dokumen_cv" class="form-label fw-bold">Upload CV <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control" id="dokumen_cv" name="dokumen_cv" required accept=".pdf,.doc,.docx">
                                    <div class="form-text">Format file: PDF, DOC, atau DOCX. Maksimal 5MB.</div>
                                </div>
                                
                                <div class="form-group mb-3">
                                    <label for="dokumen_ijazah" class="form-label fw-bold">Upload Ijazah <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control" id="dokumen_ijazah" name="dokumen_ijazah" required accept=".pdf,.jpg,.jpeg,.png">
                                    <div class="form-text">Format file: PDF, JPG, JPEG, atau PNG. Maksimal 5MB.</div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="dokumen_ktp" class="form-label fw-bold">Upload KTP <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control" id="dokumen_ktp" name="dokumen_ktp" required accept=".jpg,.jpeg,.png">
                                    <div class="form-text">Format file: JPG, JPEG, atau PNG. Maksimal 5MB.</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info mt-4">
                            <p class="mb-1"><strong>Penting:</strong></p>
                            <p class="mb-0">Semua dokumen Anda akan diverifikasi oleh tim kami. Pastikan informasi yang Anda berikan akurat dan lengkap.</p>
                        </div>
                        
                        <div class="mt-4 text-center">
                            <button type="submit" name="complete_profile_guru" class="btn btn-primary btn-lg px-4">Simpan Profil</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php elseif (isset($current_user['user_type']) && $current_user['user_type'] == 'sekolah'): ?>
            <!-- Form untuk Sekolah -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h2 class="card-title h5 mb-0">Profil Sekolah</h2>
                </div>
                <div class="card-body">
                    <form method="post" action="" enctype="multipart/form-data" data-validate="true">
                        <div class="form-group mb-3">
                            <label for="nama_sekolah" class="form-label fw-bold">Nama Sekolah <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nama_sekolah" name="nama_sekolah" required>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="jenis_sekolah" class="form-label fw-bold">Jenis Sekolah <span class="text-danger">*</span></label>
                            <select name="jenis_sekolah" id="jenis_sekolah" class="form-select" required>
                                <option value="">-- Pilih Jenis Sekolah --</option>
                                <?php foreach (get_jenis_sekolah_options() as $value => $label): ?>
                                    <option value="<?php echo htmlspecialchars($value); ?>"><?php echo htmlspecialchars($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="alamat_lengkap" class="form-label fw-bold">Alamat Lengkap <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="alamat_lengkap" name="alamat_lengkap" rows="3" required></textarea>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-12 col-md-4 mb-3 mb-md-0">
                                <div class="form-group">
                                    <label for="kecamatan" class="form-label fw-bold">Kecamatan <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="kecamatan" name="kecamatan" required>
                                </div>
                            </div>
                            <div class="col-12 col-md-4 mb-3 mb-md-0">
                                <div class="form-group">
                                    <label for="kota" class="form-label fw-bold">Kota/Kabupaten <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="kota" name="kota" required>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="form-group">
                                    <label for="provinsi" class="form-label fw-bold">Provinsi <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="provinsi" name="provinsi" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-12 col-md-6 mb-3 mb-md-0">
                                <div class="form-group">
                                    <label for="kode_pos" class="form-label fw-bold">Kode Pos <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="kode_pos" name="kode_pos" required>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <label for="contact_person" class="form-label fw-bold">Nama Kontak <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="contact_person" name="contact_person" required>
                                    <div class="form-text">Nama kontak yang dapat dihubungi oleh GuruSinergi</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="website" class="form-label fw-bold">Website Sekolah</label>
                            <input type="url" class="form-control" id="website" name="website" placeholder="https://www.example.com">
                            <div class="form-text">Opsional. Masukkan URL lengkap dengan http:// atau https://</div>
                        </div>
                        
                        <div class="card mb-3 bg-light">
                            <div class="card-header">
                                <h3 class="h6 mb-0">Dokumen Pendukung</h3>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="dokumen_npsn" class="form-label fw-bold">Upload Dokumen NPSN/Izin Operasional <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control" id="dokumen_npsn" name="dokumen_npsn" required accept=".pdf,.jpg,.jpeg,.png">
                                    <div class="form-text">Format file: PDF, JPG, JPEG, atau PNG. Maksimal 5MB.</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info mt-4">
                            <p class="mb-1"><strong>Penting:</strong></p>
                            <p class="mb-0">Semua dokumen Anda akan diverifikasi oleh tim kami. Pastikan informasi yang Anda berikan akurat dan lengkap.</p>
                        </div>
                        
                        <div class="mt-4 text-center">
                            <button type="submit" name="complete_profile_sekolah" class="btn btn-primary btn-lg px-4">Simpan Profil</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php else: ?>
            <!-- Jika tipe user tidak dikenali -->
            <div class="alert alert-danger">
                <h4>Tipe Pengguna Tidak Valid</h4>
                <p>Mohon maaf, tipe pengguna Anda tidak dikenali. Silakan hubungi administrator untuk bantuan.</p>
                <p><a href="index.php" class="btn btn-outline-danger">Kembali ke Beranda</a></p>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="py-4 mt-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>GuruSinergi</h5>
                    <p class="text-muted">Platform untuk menghubungkan guru pengganti dengan sekolah yang membutuhkan.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> GuruSinergi. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle menu mobile
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
            const navList = document.querySelector('.nav-list');
            
            if (mobileMenuToggle && navList) {
                mobileMenuToggle.addEventListener('click', function() {
                    navList.classList.toggle('active');
                });
            }
        });
    </script>
</body>
</html>