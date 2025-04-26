<?php
/**
 * GuruSinergi - Fungsi-fungsi Umum
 * 
 * Kumpulan fungsi yang sering digunakan dalam platform
 */

// Include file konfigurasi jika belum
if (!function_exists('config')) {
    require_once dirname(__DIR__) . '/config/config.php';
}

/**
 * Fungsi untuk sanitasi input
 * 
 * @param string $data Data yang akan disanitasi
 * @return string Data yang sudah disanitasi
 */
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Fungsi untuk format harga
 * 
 * @param float $price Harga yang akan diformat
 * @return string Harga yang sudah diformat
 */
function format_price($price) {
    if (empty($price) || $price == '0') {
        return 'Gratis';
    }
    return 'Rp ' . number_format((float)$price, 0, ',', '.');
}

/**
 * Fungsi untuk deteksi halaman aktif
 * 
 * @param string $path Path yang akan dicek
 * @return bool True jika halaman aktif, false jika tidak
 */
function is_active_page($path) {
    $current_path = $_SERVER['REQUEST_URI'];
    
    // Untuk halaman beranda
    if ($path == '/beranda' || $path == '/') {
        return ($current_path == '/' || $current_path == '/beranda' || $current_path == '/index.php');
    }
    
    // Untuk halaman lain
    return (strpos($current_path, $path) !== false);
}

/**
 * Fungsi untuk generate random string
 * 
 * @param int $length Panjang string yang diinginkan
 * @return string Random string
 */
function generate_random_string($length = 10) {
    return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
}

/**
 * Fungsi upload file
 * 
 * @param array $file File yang akan diupload ($_FILES)
 * @param string $target_dir Direktori tujuan
 * @param array $allowed_types Tipe file yang diperbolehkan
 * @return array Status dan path file
 */
if (!function_exists('upload_file')) {
    function upload_file($file, $target_dir, $allowed_types = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']) {
        // Buat direktori jika belum ada
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $target_file = $target_dir . basename($file["name"]);
        $file_extension = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        // Cek apakah file sudah ada, jika iya, buat nama unik
        if (file_exists($target_file)) {
            $file_name = pathinfo($file["name"], PATHINFO_FILENAME);
            $target_file = $target_dir . $file_name . '_' . time() . '.' . $file_extension;
        }
        
        // Cek ukuran file (max 5MB)
        if ($file["size"] > 5000000) {
            return ["status" => false, "message" => "Maaf, ukuran file terlalu besar (maksimal 5MB)."];
        }
        
        // Cek tipe file yang diperbolehkan
        if (!in_array($file_extension, $allowed_types)) {
            return ["status" => false, "message" => "Maaf, hanya file " . implode(', ', $allowed_types) . " yang diperbolehkan."];
        }
        
        // Upload file
        if (move_uploaded_file($file["tmp_name"], $target_file)) {
            return ["status" => true, "file_path" => $target_file];
        } else {
            return ["status" => false, "message" => "Maaf, terjadi kesalahan saat upload file."];
        }
    }
}

/**
 * Fungsi untuk kirim email
 * 
 * @param string $to Email penerima
 * @param string $subject Subjek email
 * @param string $message Isi email (HTML)
 * @return bool True jika berhasil, false jika gagal
 */
function send_email($to, $subject, $message) {
    $headers = "From: GuruSinergi <noreply@gurusinergi.com>\r\n";
    $headers .= "Reply-To: " . config('admin_email') . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    return mail($to, $subject, $message, $headers);
}

/**
 * Fungsi untuk memeriksa apakah pengguna sudah login
 * 
 * @return bool True jika sudah login, false jika belum
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Fungsi untuk mendapatkan data user yang sedang login
 * 
 * @return array|null Data user atau null jika tidak login
 */
function get_logged_in_user() {
    if (!is_logged_in()) {
        return null;
    }
    
    $conn = db_connect();
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // Ambil data profile berdasarkan tipe user
        if ($user['user_type'] == 'guru') {
            $stmt = $conn->prepare("SELECT * FROM profiles_guru WHERE user_id = ?");
            $stmt->execute([$user['id']]);
            $user['profile'] = $stmt->fetch(PDO::FETCH_ASSOC);
        } elseif ($user['user_type'] == 'sekolah') {
            $stmt = $conn->prepare("SELECT * FROM profiles_sekolah WHERE user_id = ?");
            $stmt->execute([$user['id']]);
            $user['profile'] = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
    
    return $user;
}

/**
 * Fungsi untuk mendapatkan data user aktif
 * Fungsi ini merupakan alias untuk get_logged_in_user()
 * Nama dirubah untuk menghindari konflik dengan fungsi bawaan PHP get_current_user()
 * 
 * @return array|null Data user atau null jika tidak login
 */
function get_app_current_user() {
    return get_logged_in_user();
}

/**
 * Fungsi untuk memeriksa apakah pengguna adalah guru
 * 
 * @return bool True jika pengguna adalah guru, false jika tidak
 */
function is_guru() {
    $user = get_app_current_user();
    return $user && $user['user_type'] == 'guru';
}

/**
 * Fungsi untuk memeriksa apakah pengguna adalah sekolah
 * 
 * @return bool True jika pengguna adalah sekolah, false jika tidak
 */
function is_sekolah() {
    $user = get_app_current_user();
    return $user && $user['user_type'] == 'sekolah';
}

/**
 * Fungsi untuk memeriksa apakah pengguna adalah admin
 * 
 * @return bool True jika pengguna adalah admin, false jika tidak
 */
function is_admin() {
    $user = get_app_current_user();
    return $user && $user['user_type'] == 'admin';
}

/**
 * Fungsi untuk memeriksa apakah profil sudah lengkap
 * 
 * @param mixed $user Data user
 * @return bool True jika profil sudah lengkap, false jika belum
 */
function is_profile_completed($user = null) {
    // Debugging
    error_log('User data in is_profile_completed: ' . print_r($user, true));
    
    // Pengecekan defensif
    if (!$user) {
        // Cek dulu apakah fungsi get_logged_in_user ada
        if (function_exists('get_logged_in_user')) {
            $user = get_logged_in_user();
        } else if (function_exists('get_app_current_user')) {
            $user = get_app_current_user();
        } else {
            error_log('Tidak ada fungsi untuk mendapatkan user');
            return false;
        }
    }
    
    // Validasi $user adalah array
    if (!is_array($user)) {
        error_log('User bukan array: ' . gettype($user));
        return false;
    }
    
    // Validasi user_type ada dan valid
    if (!isset($user['user_type']) || empty($user['user_type'])) {
        error_log('User type tidak ditemukan');
        return false;
    }
    
    // Validasi profile ada dan berbentuk array
    if (!isset($user['profile']) || !is_array($user['profile'])) {
        error_log('Profile tidak ada atau bukan array');
        return false;
    }
    
    // Cek kelengkapan profil
    if ($user['user_type'] == 'guru') {
        if (empty($user['profile']['dokumen_cv']) || 
            empty($user['profile']['dokumen_ijazah']) || 
            empty($user['profile']['dokumen_ktp'])) {
            return false;
        }
    } elseif ($user['user_type'] == 'sekolah') {
        if (empty($user['profile']['nama_sekolah']) || 
            empty($user['profile']['dokumen_npsn'])) {
            return false;
        }
    }
    
    return true;
}

/**
 * Fungsi untuk memeriksa apakah profil sudah diverifikasi
 * 
 * @param array $user Data user
 * @return bool True jika profil sudah diverifikasi, false jika belum
 */
function is_profile_verified($user = null) {
    if (!$user) {
        $user = get_app_current_user();
    }
    
    if (!$user) {
        return false;
    }
    
    if (empty($user['profile']) || $user['profile']['status_verifikasi'] != 'verified') {
        return false;
    }
    
    return true;
}

/**
 * Fungsi untuk mendapatkan daftar mata pelajaran
 * 
 * @return array Daftar mata pelajaran
 */
function get_mata_pelajaran_options() {
    return [
        '' => 'Pilih Mata Pelajaran',
        'Matematika' => 'Matematika',
        'Bahasa Indonesia' => 'Bahasa Indonesia',
        'Bahasa Inggris' => 'Bahasa Inggris',
        'IPA' => 'IPA',
        'IPS' => 'IPS',
        'Fisika' => 'Fisika',
        'Kimia' => 'Kimia',
        'Biologi' => 'Biologi',
        'Sejarah' => 'Sejarah',
        'Geografi' => 'Geografi',
        'Ekonomi' => 'Ekonomi',
        'Seni Budaya' => 'Seni Budaya',
        'PJOK' => 'PJOK',
        'Semua Mata Pelajaran' => 'Semua Mata Pelajaran (Guru Kelas)',
        'Lainnya' => 'Lainnya'
    ];
}

/**
 * Fungsi untuk mendapatkan daftar tingkat kelas
 * 
 * @return array Daftar tingkat kelas
 */
function get_tingkat_kelas_options() {
    return [
        '' => 'Pilih Tingkat Kelas',
        'SD Kelas 1-3' => 'SD Kelas 1-3',
        'SD Kelas 4-6' => 'SD Kelas 4-6',
        'SMP' => 'SMP',
        'SMA' => 'SMA',
        'SMK' => 'SMK',
        'Semua Tingkat' => 'Semua Tingkat'
    ];
}

/**
 * Fungsi untuk mendapatkan daftar jenis sekolah
 * 
 * @return array Daftar jenis sekolah
 */
function get_jenis_sekolah_options() {
    return [
        '' => 'Pilih Jenis Sekolah',
        'PAUD' => 'PAUD',
        'TK' => 'TK',
        'SD' => 'SD',
        'SMP' => 'SMP',
        'SMA' => 'SMA',
        'SMK' => 'SMK',
        'lainnya' => 'Lainnya'
    ];
}

/**
 * Fungsi untuk menampilkan pesan error atau sukses
 */
function display_messages() {
    if (isset($_SESSION['error_message']) && !empty($_SESSION['error_message'])) {
        echo '<div class="alert alert-danger">' . $_SESSION['error_message'] . '</div>';
        unset($_SESSION['error_message']);
    }
    
    if (isset($_SESSION['success_message']) && !empty($_SESSION['success_message'])) {
        echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
        unset($_SESSION['success_message']);
    }
}

/**
 * Mengatur pesan error ke session
 * 
 * @param string $message Pesan error
 */
function set_error_message($message) {
    $_SESSION['error_message'] = $message;
}

/**
 * Mengatur pesan sukses ke session
 * 
 * @param string $message Pesan sukses
 */
function set_success_message($message) {
    $_SESSION['success_message'] = $message;
}

/**
 * Fungsi untuk redirect
 * 
 * @param string $url URL tujuan
 */
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

/**
 * Fungsi untuk mendapatkan halaman saat ini
 * 
 * @return string Nama halaman saat ini
 */
function get_current_page() {
    return basename($_SERVER['PHP_SELF']);
}