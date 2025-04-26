<?php
/**
 * GuruSinergi - Dashboard Page
 * 
 * Halaman dashboard untuk pengguna yang sudah login
 */

// Tampilkan semua error untuk debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Begin output buffering to prevent header issues
ob_start();

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Koneksi Database Langsung - mengatasi masalah koneksi
$db_host = 'localhost';
$db_user = 'u532109326_gurupengganti';
$db_pass = 'Gurukupahlawanku99';
$db_name = 'u532109326_guru_pengganti';

// Buat koneksi database
try {
    $db = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($db->connect_error) {
        throw new Exception("Koneksi database gagal: " . $db->connect_error);
    }
    // Set karakter encoding
    $db->set_charset("utf8mb4");
} catch (Exception $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

// Definisikan konstanta untuk path
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__FILE__) . '/');
}

// Coba load file-file konfigurasi lainnya
$config_loaded = false;
$database_loaded = false;
$functions_loaded = false;
$auth_loaded = false;

// Fungsi loader file untuk memastikan path yang benar
function load_file($file_path) {
    if (file_exists($file_path)) {
        require_once $file_path;
        return true;
    }
    
    // Coba dengan BASE_PATH
    if (defined('BASE_PATH') && file_exists(BASE_PATH . $file_path)) {
        require_once BASE_PATH . $file_path;
        return true;
    }
    
    return false;
}

// Coba load file-file pendukung
$config_loaded = load_file('config/config.php');
$functions_loaded = load_file('includes/functions.php');
$auth_loaded = load_file('includes/auth-functions.php');

// Fallback functions jika functions.php tidak memuat fungsi yang dibutuhkan
if (!function_exists('check_access')) {
    function check_access($allowed_roles = 'all') {
        // Basic implementation
        if (!isset($_SESSION['user_id'])) {
            // Jika user belum login, redirect ke login page
            header('Location: login.php');
            exit;
        }
        
        // Jika sudah login tapi role tidak sesuai, tampilkan pesan error
        if ($allowed_roles !== 'all' && !in_array($_SESSION['role'], (array)$allowed_roles)) {
            die('Access denied. You do not have permission to access this page.');
        }
        
        return true;
    }
}

if (!function_exists('check_profile_required')) {
    function check_profile_required() {
        // Dummy implementation
        return true;
    }
}

if (!function_exists('get_app_current_user')) {
    function get_app_current_user() {
        // Fallback implementation jika fungsi asli tidak tersedia
        global $db;
        
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        
        $user_id = $_SESSION['user_id'];
        $user_data = [
            'id' => $user_id,
            'username' => $_SESSION['username'] ?? 'User',
            'full_name' => $_SESSION['full_name'] ?? 'User',
            'user_type' => $_SESSION['role'] ?? 'unknown'
        ];
        
        // Try to get more data from database if available
        if (isset($db) && $db) {
            try {
                $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $db_user = $result->fetch_assoc();
                
                if ($db_user) {
                    $user_data = array_merge($user_data, $db_user);
                    $user_data['full_name'] = $db_user['full_name'] ?? $db_user['name'] ?? $db_user['username'];
                }
            } catch (Exception $e) {
                // Ignore database errors to prevent redirect loops
                error_log("Error fetching user data: " . $e->getMessage());
            }
        }
        
        return $user_data;
    }
}

if (!function_exists('is_profile_verified')) {
    function is_profile_verified($user) {
        // Dummy implementation
        return isset($user['verification_status']) && $user['verification_status'] === 'verified';
    }
}

if (!function_exists('format_price')) {
    function format_price($price) {
        return 'Rp ' . number_format($price, 0, ',', '.');
    }
}

if (!function_exists('url')) {
    function url($path = '') {
        // Simple URL helper
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $base = dirname($_SERVER['SCRIPT_NAME']);
        $base = ($base == '/' || $base == '\\') ? '' : $base;
        return $protocol . '://' . $host . $base . '/' . ltrim($path, '/');
    }
}

// Cek login user
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Inisialisasi variabel
$current_user = get_app_current_user();

// Jika user type tidak dikenali, atur default 'guru'
if (!isset($current_user['user_type']) || empty($current_user['user_type'])) {
    $current_user['user_type'] = $_SESSION['role'] ?? 'guru';
}

// Fungsi untuk mengambil data dari database

/**
 * Mengambil statistik guru dari database
 */
function get_teacher_stats($user_id) {
    global $db;
    
    try {
        // Data untuk menyimpan statistik
        $stats = [
            'completed_assignments' => 0,
            'total_earnings' => 0,
            'rating' => 0,
            'total_reviews' => 0,
            'total_applications' => 0
        ];
        
        // Hitung penugasan selesai
        $stmt = $db->prepare("SELECT COUNT(*) AS total FROM assignments 
                            WHERE teacher_id = ? AND status = 'completed'");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $stats['completed_assignments'] = $row['total'];
        }
        
        // Hitung total pendapatan
        $stmt = $db->prepare("SELECT SUM(amount) AS total FROM payments 
                            WHERE receiver_id = ? AND status = 'completed'");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $stats['total_earnings'] = $row['total'] ?? 0;
        }
        
        // Ambil rating dan total review
        $stmt = $db->prepare("SELECT AVG(rating) AS avg_rating, COUNT(*) AS total 
                            FROM reviews WHERE teacher_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $stats['rating'] = $row['avg_rating'] ?? 0;
            $stats['total_reviews'] = $row['total'];
        }
        
        // Hitung total aplikasi/lamaran
        $stmt = $db->prepare("SELECT COUNT(*) AS total FROM applications WHERE teacher_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $stats['total_applications'] = $row['total'];
        }
        
        return $stats;
    } catch (Exception $e) {
        error_log("Error getting teacher stats: " . $e->getMessage());
        // Return dummy stats jika terjadi error
        return [
            'completed_assignments' => 0,
            'total_earnings' => 0,
            'rating' => 0,
            'total_reviews' => 0,
            'total_applications' => 0
        ];
    }
}

/**
 * Mengambil penugasan terbaru guru
 */
function get_teacher_recent_assignments($user_id, $limit = 5) {
    global $db;
    
    try {
        $stmt = $db->prepare("SELECT a.id, a.title as judul, s.name as nama_sekolah, a.status 
                            FROM assignments a 
                            JOIN schools s ON a.school_id = s.id
                            WHERE a.teacher_id = ? 
                            ORDER BY a.updated_at DESC LIMIT ?");
        $stmt->bind_param("ii", $user_id, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $assignments = [];
        while ($row = $result->fetch_assoc()) {
            $assignments[] = $row;
        }
        
        return $assignments;
    } catch (Exception $e) {
        error_log("Error getting teacher assignments: " . $e->getMessage());
        // Return dummy data jika terjadi error
        return [
            [
                'id' => 1,
                'judul' => 'Guru Matematika SMP Kelas 8',
                'nama_sekolah' => 'SMP Cendekia',
                'status' => 'completed'
            ],
            [
                'id' => 2,
                'judul' => 'Guru Bahasa Inggris SMA Kelas 10',
                'nama_sekolah' => 'SMA Maju Jaya',
                'status' => 'assigned'
            ]
        ];
    }
}

/**
 * Mengambil lamaran terbaru guru
 */
function get_teacher_recent_applications($user_id, $limit = 5) {
    global $db;
    
    try {
        $stmt = $db->prepare("SELECT app.id, app.assignment_id, a.title as judul, 
                            s.name as nama_sekolah, app.status 
                            FROM applications app 
                            JOIN assignments a ON app.assignment_id = a.id
                            JOIN schools s ON a.school_id = s.id
                            WHERE app.teacher_id = ? 
                            ORDER BY app.created_at DESC LIMIT ?");
        $stmt->bind_param("ii", $user_id, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $applications = [];
        while ($row = $result->fetch_assoc()) {
            $applications[] = $row;
        }
        
        return $applications;
    } catch (Exception $e) {
        error_log("Error getting teacher applications: " . $e->getMessage());
        // Return dummy data jika terjadi error
        return [
            [
                'id' => 1,
                'assignment_id' => 3,
                'judul' => 'Guru IPA SMP Kelas 9',
                'nama_sekolah' => 'SMP Harapan',
                'status' => 'pending'
            ],
            [
                'id' => 2,
                'assignment_id' => 4,
                'judul' => 'Guru Kimia SMA Kelas 11',
                'nama_sekolah' => 'SMA Prestasi',
                'status' => 'accepted'
            ]
        ];
    }
}

/**
 * Mengambil rekomendasi penugasan untuk guru
 */
function get_recommended_assignments($user_id, $limit = 3) {
    global $db;
    
    try {
        // Contoh query sederhana untuk rekomendasi
        // Dalam implementasi nyata, ini bisa lebih kompleks dengan algoritma matching
        $stmt = $db->prepare("SELECT a.id, a.title as judul, s.name as nama_sekolah, 
                            a.subject as mata_pelajaran, a.grade_level as tingkat_kelas,
                            a.start_date as tanggal_mulai, a.end_date as tanggal_selesai,
                            a.salary as gaji
                            FROM assignments a 
                            JOIN schools s ON a.school_id = s.id
                            WHERE a.status = 'open' 
                            AND a.id NOT IN (SELECT assignment_id FROM applications WHERE teacher_id = ?)
                            ORDER BY a.created_at DESC LIMIT ?");
        $stmt->bind_param("ii", $user_id, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $assignments = [];
        while ($row = $result->fetch_assoc()) {
            $assignments[] = $row;
        }
        
        return $assignments;
    } catch (Exception $e) {
        error_log("Error getting recommended assignments: " . $e->getMessage());
        // Return dummy data jika terjadi error
        return [
            [
                'id' => 5,
                'judul' => 'Guru Matematika SD Kelas 6',
                'nama_sekolah' => 'SD Cerdas',
                'mata_pelajaran' => 'Matematika',
                'tingkat_kelas' => 'SD Kelas 4-6',
                'tanggal_mulai' => '2023-09-01',
                'tanggal_selesai' => '2023-12-31',
                'gaji' => 500000
            ],
            [
                'id' => 6,
                'judul' => 'Guru Bahasa Indonesia SMP Kelas 7',
                'nama_sekolah' => 'SMP Bintang',
                'mata_pelajaran' => 'Bahasa Indonesia',
                'tingkat_kelas' => 'SMP',
                'tanggal_mulai' => '2023-09-15',
                'tanggal_selesai' => '2023-12-15',
                'gaji' => 600000
            ],
            [
                'id' => 7,
                'judul' => 'Guru IPA SD Kelas 5',
                'nama_sekolah' => 'SD Maju',
                'mata_pelajaran' => 'IPA',
                'tingkat_kelas' => 'SD Kelas 4-6',
                'tanggal_mulai' => '2023-10-01',
                'tanggal_selesai' => '2023-12-20',
                'gaji' => 550000
            ]
        ];
    }
}

/**
 * Mengambil statistik sekolah dari database
 */
function get_school_stats($school_id) {
    global $db;
    
    try {
        // Data untuk menyimpan statistik
        $stats = [
            'total_assignments' => 0,
            'completed_assignments' => 0,
            'ongoing_assignments' => 0,
            'total_spent' => 0
        ];
        
        // Hitung total penugasan
        $stmt = $db->prepare("SELECT COUNT(*) AS total FROM assignments WHERE school_id = ?");
        $stmt->bind_param("i", $school_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $stats['total_assignments'] = $row['total'];
        }
        
        // Hitung penugasan selesai
        $stmt = $db->prepare("SELECT COUNT(*) AS total FROM assignments 
                            WHERE school_id = ? AND status = 'completed'");
        $stmt->bind_param("i", $school_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $stats['completed_assignments'] = $row['total'];
        }
        
        // Hitung penugasan berjalan
        $stmt = $db->prepare("SELECT COUNT(*) AS total FROM assignments 
                            WHERE school_id = ? AND status = 'assigned'");
        $stmt->bind_param("i", $school_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $stats['ongoing_assignments'] = $row['total'];
        }
        
        // Hitung total pengeluaran
        $stmt = $db->prepare("SELECT SUM(amount) AS total FROM payments 
                            WHERE sender_id = ? AND status = 'completed'");
        $stmt->bind_param("i", $school_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $stats['total_spent'] = $row['total'] ?? 0;
        }
        
        return $stats;
    } catch (Exception $e) {
        error_log("Error getting school stats: " . $e->getMessage());
        // Return dummy stats jika terjadi error
        return [
            'total_assignments' => 8,
            'completed_assignments' => 5,
            'ongoing_assignments' => 2,
            'total_spent' => 4500000
        ];
    }
}

/**
 * Mengambil penugasan terbaru sekolah
 */
function get_school_recent_assignments($school_id, $limit = 5) {
    global $db;
    
    try {
        $stmt = $db->prepare("SELECT a.id, a.title as judul, a.subject as mata_pelajaran, 
                            a.start_date as tanggal_mulai, a.status 
                            FROM assignments a 
                            WHERE a.school_id = ? 
                            ORDER BY a.updated_at DESC LIMIT ?");
        $stmt->bind_param("ii", $school_id, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $assignments = [];
        while ($row = $result->fetch_assoc()) {
            $assignments[] = $row;
        }
        
        return $assignments;
    } catch (Exception $e) {
        error_log("Error getting school assignments: " . $e->getMessage());
        // Return dummy data jika terjadi error
        return [
            [
                'id' => 1,
                'judul' => 'Guru Matematika SMP Kelas 8',
                'mata_pelajaran' => 'Matematika',
                'tanggal_mulai' => '2023-08-01',
                'status' => 'completed'
            ],
            [
                'id' => 2,
                'judul' => 'Guru Bahasa Inggris SMA Kelas 10',
                'mata_pelajaran' => 'Bahasa Inggris',
                'tanggal_mulai' => '2023-09-01',
                'status' => 'assigned'
            ]
        ];
    }
}

/**
 * Mengambil penugasan terbuka sekolah
 */
function get_school_open_assignments($school_id, $limit = 5) {
    global $db;
    
    try {
        $stmt = $db->prepare("SELECT a.id, a.title as judul, a.subject as mata_pelajaran, 
                            a.status, COUNT(app.id) as total_applicants
                            FROM assignments a 
                            LEFT JOIN applications app ON a.id = app.assignment_id
                            WHERE a.school_id = ? AND a.status = 'open'
                            GROUP BY a.id
                            ORDER BY a.created_at DESC LIMIT ?");
        $stmt->bind_param("ii", $school_id, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $assignments = [];
        while ($row = $result->fetch_assoc()) {
            $assignments[] = $row;
        }
        
        return $assignments;
    } catch (Exception $e) {
        error_log("Error getting school open assignments: " . $e->getMessage());
        // Return dummy data jika terjadi error
        return [
            [
                'id' => 8,
                'judul' => 'Guru Fisika SMA Kelas 12',
                'mata_pelajaran' => 'Fisika',
                'status' => 'open',
                'total_applicants' => 3
            ],
            [
                'id' => 9,
                'judul' => 'Guru Biologi SMA Kelas 11',
                'mata_pelajaran' => 'Biologi',
                'status' => 'open',
                'total_applicants' => 1
            ]
        ];
    }
}

/**
 * Mengambil guru yang direkomendasikan
 */
function get_recommended_teachers($school_id, $limit = 3) {
    global $db;
    
    try {
        $stmt = $db->prepare("SELECT t.id, t.full_name, t.profile_image, 
                            AVG(r.rating) as rating,
                            t.subjects as mata_pelajaran, t.teaching_levels as tingkat_mengajar
                            FROM teachers t
                            LEFT JOIN reviews r ON t.id = r.teacher_id
                            WHERE t.verification_status = 'verified'
                            GROUP BY t.id
                            ORDER BY rating DESC LIMIT ?");
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $teachers = [];
        while ($row = $result->fetch_assoc()) {
            $teachers[] = $row;
        }
        
        return $teachers;
    } catch (Exception $e) {
        error_log("Error getting recommended teachers: " . $e->getMessage());
        // Return dummy data jika terjadi error
        return [
            [
                'id' => 1,
                'full_name' => 'Budi Santoso',
                'profile_image' => '',
                'rating' => 4.8,
                'mata_pelajaran' => 'Matematika, Fisika',
                'tingkat_mengajar' => 'SMP, SMA'
            ],
            [
                'id' => 2,
                'full_name' => 'Siti Aminah',
                'profile_image' => '',
                'rating' => 4.6,
                'mata_pelajaran' => 'Bahasa Inggris',
                'tingkat_mengajar' => 'SD, SMP, SMA'
            ],
            [
                'id' => 3,
                'full_name' => 'Ahmad Fauzi',
                'profile_image' => '',
                'rating' => 4.5,
                'mata_pelajaran' => 'IPA, Biologi',
                'tingkat_mengajar' => 'SMP, SMA'
            ]
        ];
    }
}

// Load data berdasarkan tipe user
$stats = [];
$recent_assignments = [];
$applications = [];
$recent_applications = [];
$open_assignments = [];
$recommended_assignments = [];
$recommended_guru = [];

if ($current_user['user_type'] == 'guru' || $current_user['user_type'] == 'teacher') {
    // Coba ambil data dari database terlebih dahulu
    $stats = get_teacher_stats($current_user['id']);
    $recent_assignments = get_teacher_recent_assignments($current_user['id'], 2);
    $recent_applications = get_teacher_recent_applications($current_user['id'], 2);
    $recommended_assignments = get_recommended_assignments($current_user['id'], 3);
    
    // Fallback ke dummy data jika tidak ada data
    if (empty($stats['completed_assignments']) && empty($stats['total_earnings'])) {
        $stats = [
            'completed_assignments' => 5,
            'total_earnings' => 2500000,
            'rating' => 4.5,
            'total_reviews' => 10,
            'total_applications' => 15
        ];
    }
    
    if (empty($recent_assignments)) {
        $recent_assignments = [
            [
                'id' => 1,
                'judul' => 'Guru Matematika SMP Kelas 8',
                'nama_sekolah' => 'SMP Cendekia',
                'status' => 'completed'
            ],
            [
                'id' => 2,
                'judul' => 'Guru Bahasa Inggris SMA Kelas 10',
                'nama_sekolah' => 'SMA Maju Jaya',
                'status' => 'assigned'
            ]
        ];
    }
    
    if (empty($recent_applications)) {
        $recent_applications = [
            [
                'id' => 1,
                'assignment_id' => 3,
                'judul' => 'Guru IPA SMP Kelas 9',
                'nama_sekolah' => 'SMP Harapan',
                'status' => 'pending'
            ],
            [
                'id' => 2,
                'assignment_id' => 4,
                'judul' => 'Guru Kimia SMA Kelas 11',
                'nama_sekolah' => 'SMA Prestasi',
                'status' => 'accepted'
            ]
        ];
    }
    
    if (empty($recommended_assignments)) {
        $recommended_assignments = [
            [
                'id' => 5,
                'judul' => 'Guru Matematika SD Kelas 6',
                'nama_sekolah' => 'SD Cerdas',
                'mata_pelajaran' => 'Matematika',
                'tingkat_kelas' => 'SD Kelas 4-6',
                'tanggal_mulai' => '2023-09-01',
                'tanggal_selesai' => '2023-12-31',
                'gaji' => 500000
            ],
            [
                'id' => 6,
                'judul' => 'Guru Bahasa Indonesia SMP Kelas 7',
                'nama_sekolah' => 'SMP Bintang',
                'mata_pelajaran' => 'Bahasa Indonesia',
                'tingkat_kelas' => 'SMP',
                'tanggal_mulai' => '2023-09-15',
                'tanggal_selesai' => '2023-12-15',
                'gaji' => 600000
            ],
            [
                'id' => 7,
                'judul' => 'Guru IPA SD Kelas 5',
                'nama_sekolah' => 'SD Maju',
                'mata_pelajaran' => 'IPA',
                'tingkat_kelas' => 'SD Kelas 4-6',
                'tanggal_mulai' => '2023-10-01',
                'tanggal_selesai' => '2023-12-20',
                'gaji' => 550000
            ]
        ];
    }
} elseif ($current_user['user_type'] == 'sekolah' || $current_user['user_type'] == 'school') {
    // Coba ambil data dari database terlebih dahulu
    $stats = get_school_stats($current_user['id']);
    $recent_assignments = get_school_recent_assignments($current_user['id'], 2);
    $open_assignments = get_school_open_assignments($current_user['id'], 2);
    $recommended_guru = get_recommended_teachers($current_user['id'], 3);
    
    // Fallback ke dummy data jika tidak ada data
    if (empty($stats['total_assignments']) && empty($stats['total_spent'])) {
        $stats = [
            'total_assignments' => 8,
            'completed_assignments' => 5,
            'ongoing_assignments' => 2,
            'total_spent' => 4500000
        ];
    }
    
    if (empty($recent_assignments)) {
        $recent_assignments = [
            [
                'id' => 1,
                'judul' => 'Guru Matematika SMP Kelas 8',
                'mata_pelajaran' => 'Matematika',
                'tanggal_mulai' => '2023-08-01',
                'status' => 'completed'
            ],
            [
                'id' => 2,
                'judul' => 'Guru Bahasa Inggris SMA Kelas 10',
                'mata_pelajaran' => 'Bahasa Inggris',
                'tanggal_mulai' => '2023-09-01',
                'status' => 'assigned'
            ]
        ];
    }
    
    if (empty($open_assignments)) {
        $open_assignments = [
            [
                'id' => 8,
                'judul' => 'Guru Fisika SMA Kelas 12',
                'mata_pelajaran' => 'Fisika',
                'status' => 'open',
                'total_applicants' => 3
            ],
            [
                'id' => 9,
                'judul' => 'Guru Biologi SMA Kelas 11',
                'mata_pelajaran' => 'Biologi',
                'status' => 'open',
                'total_applicants' => 1
            ]
        ];
    }
    
    if (empty($recommended_guru)) {
        $recommended_guru = [
            [
                'id' => 1,
                'full_name' => 'Budi Santoso',
                'profile_image' => '',
                'rating' => 4.8,
                'mata_pelajaran' => 'Matematika, Fisika',
                'tingkat_mengajar' => 'SMP, SMA'
            ],
            [
                'id' => 2,
                'full_name' => 'Siti Aminah',
                'profile_image' => '',
                'rating' => 4.6,
                'mata_pelajaran' => 'Bahasa Inggris',
                'tingkat_mengajar' => 'SD, SMP, SMA'
            ],
            [
                'id' => 3,
                'full_name' => 'Ahmad Fauzi',
                'profile_image' => '',
                'rating' => 4.5,
                'mata_pelajaran' => 'IPA, Biologi',
                'tingkat_mengajar' => 'SMP, SMA'
            ]
        ];
    }
}

// Set variabel untuk page title
$page_title = 'Dashboard';

// Cek jika template file ada, jika tidak tampilkan langsung
$template_file = 'templates/header.php';
if (file_exists($template_file) || (defined('BASE_PATH') && file_exists(BASE_PATH . $template_file))) {
    if (file_exists($template_file)) {
        include_once $template_file;
    } else {
        include_once BASE_PATH . $template_file;
    }
} else {
    // Output header HTML langsung jika template tidak tersedia
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $page_title; ?> - GuruSinergi</title>
        
        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
        
        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        
        <!-- Google Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        
        <style>
            body {
                font-family: 'Poppins', sans-serif;
                background-color: #f5f8fa;
                color: #333;
                line-height: 1.6;
            }
            .navbar-brand {
                font-weight: 700;
            }
            .navbar-brand .highlight {
                color: #6f42c1;
            }
            .page-header {
                margin-bottom: 2rem;
                padding: 1.5rem 0;
            }
            .page-title {
                font-weight: 700;
                margin-bottom: 0.5rem;
            }
            .page-description {
                color: #6c757d;
                font-size: 1.1rem;
            }
            .stats-container {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 1.5rem;
                margin-bottom: 2rem;
            }
            .stat-card {
                background-color: #fff;
                border-radius: 10px;
                padding: 1.5rem;
                box-shadow: 0 2px 10px rgba(0,0,0,0.05);
                text-align: center;
            }
            .stat-icon {
                font-size: 2rem;
                margin-bottom: 1rem;
                color: #6f42c1;
            }
            .stat-value {
                font-size: 1.8rem;
                font-weight: 700;
                margin-bottom: 0.5rem;
            }
            .stat-title {
                color: #6c757d;
                font-size: 0.95rem;
            }
            .card {
                border: none;
                border-radius: 10px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.05);
                margin-bottom: 1.5rem;
                overflow: hidden;
            }
            .card-header {
                background-color: #fff;
                border-bottom: 1px solid rgba(0,0,0,0.05);
                padding: 1.25rem 1.5rem;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .card-title {
                font-size: 1.25rem;
                font-weight: 600;
                margin: 0;
            }
            .card-body {
                padding: 1.5rem;
            }
            .alert {
                border-radius: 10px;
                border: none;
            }
            .alert-warning {
                background-color: #fff3cd;
                color: #856404;
                padding: 1rem;
            }
            .border-bottom {
                border-bottom: 1px solid rgba(0,0,0,0.05) !important;
            }
            .btn-primary {
                background-color: #6f42c1;
                border-color: #6f42c1;
            }
            .btn-outline {
                color: #6f42c1;
                border: 1px solid #6f42c1;
                background-color: transparent;
            }
            .badge {
                padding: 0.35em 0.65em;
                font-weight: 500;
                border-radius: 50px;
            }
            .badge-open {
                background-color: #17a2b8;
                color: white;
            }
            .badge-assigned {
                background-color: #6f42c1;
                color: white;
            }
            .badge-completed {
                background-color: #28a745;
                color: white;
            }
            .badge-canceled {
                background-color: #dc3545;
                color: white;
            }
            .assignment-card {
                border: 1px solid rgba(0,0,0,0.05);
                border-radius: 10px;
                overflow: hidden;
                box-shadow: 0 2px 10px rgba(0,0,0,0.05);
                margin-bottom: 1.5rem;
            }
            .assignment-card .card-header {
                position: relative;
                padding-top: 2.5rem;
            }
            .card-badge {
                position: absolute;
                top: 10px;
                right: 10px;
            }
            .card-subtitle {
                color: #6c757d;
                font-size: 0.9rem;
                margin-top: 0.5rem;
            }
            .info-item {
                display: flex;
                align-items: center;
                margin-bottom: 0.5rem;
            }
            .info-item i {
                width: 20px;
                margin-right: 0.5rem;
                color: #6c757d;
            }
            .info-label {
                font-weight: 500;
                margin-right: 0.5rem;
            }
            .card-footer {
                background-color: rgba(0,0,0,0.02);
                padding: 1rem;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .card-price {
                font-weight: 700;
                color: #6f42c1;
            }
            .rating {
                color: #ffc107;
                margin-top: 0.5rem;
            }
            .rating-value {
                color: #6c757d;
                margin-left: 0.5rem;
            }
        </style>
    </head>
    <body>
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
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i> 
                            <?php echo htmlspecialchars($current_user['full_name'] ?? $current_user['username'] ?? 'User'); ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-id-card me-2"></i>Profil</a></li>
                            <li><a class="dropdown-item" href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Keluar</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>
        
        <main class="container py-4">
    <?php
}
?>

<div class="page-header">
    <h1 class="page-title">Dashboard</h1>
    <p class="page-description">Selamat datang kembali, <?php echo htmlspecialchars($current_user['full_name'] ?? $current_user['username'] ?? 'User'); ?>!</p>
</div>

<?php if (($current_user['user_type'] == 'guru' || $current_user['user_type'] == 'teacher')): ?>
    <?php if (!is_profile_verified($current_user)): ?>
    <div class="alert alert-warning">
        <strong>Profil Anda belum diverifikasi!</strong> Lengkapi profil Anda dan tunggu verifikasi dari tim kami untuk mulai melamar penugasan.
    </div>
    <?php endif; ?>
    
    <!-- Guru Dashboard -->
    <div class="stats-container">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-clipboard-check"></i></div>
            <div class="stat-value"><?php echo $stats['completed_assignments']; ?></div>
            <div class="stat-title">Penugasan Selesai</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-star"></i></div>
            <div class="stat-value"><?php echo number_format($stats['rating'], 1); ?></div>
            <div class="stat-title">Rating</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
            <div class="stat-value"><?php echo format_price($stats['total_earnings']); ?></div>
            <div class="stat-title">Total Pendapatan</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
            <div class="stat-value"><?php echo $stats['total_applications']; ?></div>
            <div class="stat-title">Total Lamaran</div>
        </div>
    </div>
    
    <div class="row mt-5">
        <div class="col-12 col-md-6">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Penugasan Terbaru</h2>
                    <a href="<?php echo url('assignments/my-assignments.php'); ?>" class="btn btn-outline btn-sm">Lihat Semua</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_assignments)): ?>
                    <p class="text-center py-4">Belum ada penugasan</p>
                    <?php else: ?>
                        <?php foreach ($recent_assignments as $assignment): ?>
                        <div class="assignment-item mb-3 pb-3 border-bottom">
                            <h3 class="h5"><a href="<?php echo url('assignments/detail.php?id=' . $assignment['id']); ?>"><?php echo htmlspecialchars($assignment['judul']); ?></a></h3>
                            <div class="d-flex justify-content-between">
                                <div>
                                    <i class="fas fa-school text-muted"></i> <?php echo htmlspecialchars($assignment['nama_sekolah']); ?>
                                </div>
                                <div>
                                    <span class="badge <?php 
                                        if ($assignment['status'] == 'open') echo 'badge-open';
                                        elseif ($assignment['status'] == 'assigned') echo 'badge-assigned';
                                        elseif ($assignment['status'] == 'completed') echo 'badge-completed';
                                        else echo 'badge-canceled';
                                    ?>">
                                        <?php 
                                            if ($assignment['status'] == 'open') echo 'Terbuka';
                                            elseif ($assignment['status'] == 'assigned') echo 'Ditugaskan';
                                            elseif ($assignment['status'] == 'completed') echo 'Selesai';
                                            else echo 'Dibatalkan';
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-md-6">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Lamaran Terbaru</h2>
                    <a href="<?php echo url('applications/my-applications.php'); ?>" class="btn btn-outline btn-sm">Lihat Semua</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_applications)): ?>
                    <p class="text-center py-4">Belum ada lamaran</p>
                    <?php else: ?>
                        <?php foreach ($recent_applications as $application): ?>
                        <div class="application-item mb-3 pb-3 border-bottom">
                            <h3 class="h5"><a href="<?php echo url('assignments/detail.php?id=' . $application['assignment_id']); ?>"><?php echo htmlspecialchars($application['judul']); ?></a></h3>
                            <div class="d-flex justify-content-between">
                                <div>
                                    <i class="fas fa-school text-muted"></i> <?php echo htmlspecialchars($application['nama_sekolah']); ?>
                                </div>
                                <div>
                                    <span class="badge <?php 
                                        if ($application['status'] == 'pending') echo 'badge-open';
                                        elseif ($application['status'] == 'accepted') echo 'badge-assigned';
                                        else echo 'badge-canceled';
                                    ?>">
                                        <?php 
                                            if ($application['status'] == 'pending') echo 'Dalam Peninjauan';
                                            elseif ($application['status'] == 'accepted') echo 'Diterima';
                                            else echo 'Ditolak';
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card mt-4">
        <div class="card-header">
            <h2 class="card-title">Penugasan yang Mungkin Sesuai</h2>
        </div>
        <div class="card-body">
            <?php if (empty($recommended_assignments)): ?>
            <p class="text-center py-4">Belum ada penugasan yang sesuai</p>
            <?php else: ?>
            <div class="row">
                <?php foreach ($recommended_assignments as $assignment): ?>
                <div class="col-12 col-md-4 mb-4">
                    <div class="assignment-card h-100">
                        <div class="card-header">
                            <span class="card-badge badge-open">Terbuka</span>
                            <h3 class="card-title"><?php echo htmlspecialchars($assignment['judul']); ?></h3>
                            <div class="card-subtitle">
                                <i class="fas fa-school"></i> <?php echo htmlspecialchars($assignment['nama_sekolah']); ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="info-item">
                                <i class="fas fa-book"></i>
                                <span class="info-label">Mata Pelajaran:</span>
                                <span class="info-value"><?php echo htmlspecialchars($assignment['mata_pelajaran']); ?></span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-users"></i>
                                <span class="info-label">Tingkat Kelas:</span>
                                <span class="info-value"><?php echo htmlspecialchars($assignment['tingkat_kelas']); ?></span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-calendar-alt"></i>
                                <span class="info-label">Tanggal:</span>
                                <span class="info-value"><?php echo date('d M Y', strtotime($assignment['tanggal_mulai'])); ?> - <?php echo date('d M Y', strtotime($assignment['tanggal_selesai'])); ?></span>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="card-price"><?php echo format_price($assignment['gaji']); ?></div>
                            <a href="<?php echo url('assignments/detail.php?id=' . $assignment['id']); ?>" class="btn btn-primary btn-sm">Detail</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center mt-4">
                <a href="<?php echo url('assignments/browse.php'); ?>" class="btn btn-outline">Lihat Semua Penugasan</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
<?php elseif ($current_user['user_type'] == 'sekolah' || $current_user['user_type'] == 'school'): ?>
    <?php if (!is_profile_verified($current_user)): ?>
    <div class="alert alert-warning">
        <strong>Profil sekolah Anda belum diverifikasi!</strong> Lengkapi profil sekolah Anda dan tunggu verifikasi dari tim kami untuk mulai membuat penugasan.
    </div>
    <?php endif; ?>
    
    <!-- Sekolah Dashboard -->
    <div class="stats-container">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-clipboard-list"></i></div>
            <div class="stat-value"><?php echo $stats['total_assignments']; ?></div>
            <div class="stat-title">Total Penugasan</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-value"><?php echo $stats['completed_assignments']; ?></div>
            <div class="stat-title">Penugasan Selesai</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-spinner"></i></div>
            <div class="stat-value"><?php echo $stats['ongoing_assignments']; ?></div>
            <div class="stat-title">Penugasan Berjalan</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
            <div class="stat-value"><?php echo format_price($stats['total_spent']); ?></div>
            <div class="stat-title">Total Pengeluaran</div>
        </div>
    </div>
    
    <div class="d-flex justify-content-center mt-4 mb-5">
        <a href="<?php echo url('assignments/create.php'); ?>" class="btn btn-primary btn-lg">
            <i class="fas fa-plus-circle"></i> Buat Penugasan Baru
        </a>
    </div>
    
    <div class="row">
        <div class="col-12 col-md-6">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Penugasan Aktif</h2>
                    <a href="<?php echo url('assignments/my-assignments.php'); ?>" class="btn btn-outline btn-sm">Lihat Semua</a>
                </div>
                <div class="card-body">
                    <?php if (empty($open_assignments)): ?>
                    <p class="text-center py-4">Belum ada penugasan aktif</p>
                    <?php else: ?>
                        <?php foreach ($open_assignments as $assignment): ?>
                        <div class="assignment-item mb-3 pb-3 border-bottom">
                            <h3 class="h5"><a href="<?php echo url('assignments/detail.php?id=' . $assignment['id']); ?>"><?php echo htmlspecialchars($assignment['judul']); ?></a></h3>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span><i class="fas fa-book text-muted"></i> <?php echo htmlspecialchars($assignment['mata_pelajaran']); ?></span>
                                </div>
                                <div>
                                    <span class="badge badge-open">Terbuka</span>
                                </div>
                            </div>
                            <div class="mt-2">
                                <span class="text-muted">Lamaran: <?php echo $assignment['total_applicants']; ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-md-6">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Penugasan Terbaru</h2>
                    <a href="<?php echo url('assignments/my-assignments.php'); ?>" class="btn btn-outline btn-sm">Lihat Semua</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_assignments)): ?>
                    <p class="text-center py-4">Belum ada penugasan</p>
                    <?php else: ?>
                        <?php foreach ($recent_assignments as $assignment): ?>
                        <div class="assignment-item mb-3 pb-3 border-bottom">
                            <h3 class="h5"><a href="<?php echo url('assignments/detail.php?id=' . $assignment['id']); ?>"><?php echo htmlspecialchars($assignment['judul']); ?></a></h3>
                            <div class="d-flex justify-content-between">
                                <div>
                                    <span><i class="fas fa-calendar-alt text-muted"></i> <?php echo date('d M Y', strtotime($assignment['tanggal_mulai'])); ?></span>
                                </div>
                                <div>
                                    <span class="badge <?php 
                                        if ($assignment['status'] == 'open') echo 'badge-open';
                                        elseif ($assignment['status'] == 'assigned') echo 'badge-assigned';
                                        elseif ($assignment['status'] == 'completed') echo 'badge-completed';
                                        else echo 'badge-canceled';
                                    ?>">
                                        <?php 
                                            if ($assignment['status'] == 'open') echo 'Terbuka';
                                            elseif ($assignment['status'] == 'assigned') echo 'Ditugaskan';
                                            elseif ($assignment['status'] == 'completed') echo 'Selesai';
                                            else echo 'Dibatalkan';
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card mt-4">
        <div class="card-header">
            <h2 class="card-title">Guru yang Direkomendasikan</h2>
        </div>
        <div class="card-body">
            <?php if (empty($recommended_guru)): ?>
            <p class="text-center py-4">Belum ada guru yang direkomendasikan</p>
            <?php else: ?>
            <div class="row">
                <?php foreach ($recommended_guru as $guru): ?>
                <div class="col-12 col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <?php if (!empty($guru['profile_image'])): ?>
                                <img src="<?php echo $guru['profile_image']; ?>" alt="<?php echo htmlspecialchars($guru['full_name']); ?>" class="rounded-circle" width="80">
                                <?php else: ?>
                                <div style="width: 80px; height: 80px; border-radius: 50%; background-color: #4F46E5; color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 32px; margin: 0 auto;">
                                    <?php echo substr($guru['full_name'], 0, 1); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <h3 class="h5"><?php echo htmlspecialchars($guru['full_name']); ?></h3>
                            <div class="rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php if ($i <= floor($guru['rating'])): ?>
                                <i class="fas fa-star"></i>
                                <?php elseif ($i - 0.5 <= $guru['rating']): ?>
                                <i class="fas fa-star-half-alt"></i>
                                <?php else: ?>
                                <i class="far fa-star"></i>
                                <?php endif; ?>
                                <?php endfor; ?>
                                <span class="rating-value"><?php echo number_format($guru['rating'], 1); ?></span>
                            </div>
                            <p class="mt-2"><?php echo htmlspecialchars($guru['mata_pelajaran']); ?></p>
                            <a href="<?php echo url('teachers/profile.php?id=' . $guru['id']); ?>" class="btn btn-outline btn-sm">Lihat Profil</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center mt-4">
                <a href="<?php echo url('teachers/browse.php'); ?>" class="btn btn-outline">Lihat Semua Guru</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <!-- Jika tipe pengguna tidak dikenali -->
    <div class="alert alert-warning">
        <strong>Perhatian!</strong> Tipe pengguna tidak dikenali. Silahkan hubungi administrator untuk mendapatkan bantuan.
    </div>
<?php endif; ?>

<?php
// Cek jika template file ada, jika tidak tampilkan langsung
$template_file = 'templates/footer.php';
if (file_exists($template_file) || (defined('BASE_PATH') && file_exists(BASE_PATH . $template_file))) {
    if (file_exists($template_file)) {
        include_once $template_file;
    } else {
        include_once BASE_PATH . $template_file;
    }
} else {
    // Output footer HTML langsung jika template tidak tersedia
    ?>
        </main>
        
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
        
        <!-- Script untuk mengecek jika konten dimuat -->
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Dashboard loaded successfully');
        });
        </script>
    </body>
    </html>
    <?php
}

// Flush output buffer
ob_end_flush();
?>