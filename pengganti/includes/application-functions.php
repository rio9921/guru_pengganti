<?php
/**
 * GuruSinergi - Fungsi-fungsi Aplikasi/Lamaran
 * 
 * Kumpulan fungsi yang berhubungan dengan aplikasi/lamaran guru
 */

// Include file konfigurasi jika belum
if (!function_exists('config')) {
    require_once dirname(__DIR__) . '/config/config.php';
}

// Include file database jika belum
if (!function_exists('db_connect')) {
    require_once dirname(__DIR__) . '/config/database.php';
}

// Include file notifikasi jika belum
if (!function_exists('notify_sekolah_new_application')) {
    require_once dirname(__DIR__) . '/includes/notification-functions.php';
}

// Include file assignment functions jika belum
if (!function_exists('update_assignment_status')) {
    require_once dirname(__DIR__) . '/includes/assignment-functions.php';
}

/**
 * Fungsi untuk membuat aplikasi/lamaran baru
 * 
 * @param array $data Data aplikasi/lamaran
 * @return int|bool ID aplikasi jika berhasil, false jika gagal
 */
function create_application($data) {
    // Validasi input
    if (empty($data['assignment_id']) || empty($data['guru_id'])) {
        return false;
    }
    
    $conn = db_connect();
    
    // Cek apakah guru sudah melamar untuk penugasan ini
    $stmt = $conn->prepare("SELECT id FROM applications WHERE assignment_id = ? AND guru_id = ?");
    $stmt->execute([$data['assignment_id'], $data['guru_id']]);
    
    if ($stmt->fetchColumn()) {
        return false; // Guru sudah melamar untuk penugasan ini
    }
    
    // Cek apakah penugasan masih terbuka
    $stmt = $conn->prepare("SELECT status FROM assignments WHERE id = ?");
    $stmt->execute([$data['assignment_id']]);
    $assignment_status = $stmt->fetchColumn();
    
    if ($assignment_status != 'open') {
        return false; // Penugasan tidak terbuka
    }
    
    // Buat aplikasi/lamaran baru
    $stmt = $conn->prepare("
        INSERT INTO applications (assignment_id, guru_id, pesan, status)
        VALUES (?, ?, ?, 'pending')
    ");
    
    $result = $stmt->execute([
        $data['assignment_id'],
        $data['guru_id'],
        $data['pesan'] ?? ''
    ]);
    
    if (!$result) {
        return false;
    }
    
    $application_id = $conn->lastInsertId();
    
    // Ambil ID sekolah dari penugasan
    $stmt = $conn->prepare("SELECT sekolah_id FROM assignments WHERE id = ?");
    $stmt->execute([$data['assignment_id']]);
    $sekolah_id = $stmt->fetchColumn();
    
    // Kirim notifikasi ke sekolah
    notify_sekolah_new_application($application_id);
    
    return $application_id;
}

/**
 * Fungsi untuk mendapatkan data aplikasi/lamaran
 * 
 * @param int $application_id ID aplikasi/lamaran
 * @return array|bool Data aplikasi jika berhasil, false jika gagal
 */
function get_application($application_id) {
    $conn = db_connect();
    $stmt = $conn->prepare("
        SELECT app.*, a.judul, a.mata_pelajaran, a.tingkat_kelas, a.tanggal_mulai, a.tanggal_selesai,
               a.gaji, a.status as assignment_status, a.sekolah_id,
               u_guru.full_name as guru_name, g.mata_pelajaran as guru_mata_pelajaran,
               g.tingkat_mengajar as guru_tingkat_mengajar, g.rating as guru_rating,
               u_sekolah.full_name as sekolah_name, s.nama_sekolah
        FROM applications app
        JOIN assignments a ON app.assignment_id = a.id
        JOIN users u_guru ON app.guru_id = u_guru.id
        JOIN profiles_guru g ON u_guru.id = g.user_id
        JOIN users u_sekolah ON a.sekolah_id = u_sekolah.id
        JOIN profiles_sekolah s ON u_sekolah.id = s.user_id
        WHERE app.id = ?
    ");
    $stmt->execute([$application_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Fungsi untuk mendapatkan aplikasi/lamaran berdasarkan guru dan penugasan
 * 
 * @param int $guru_id ID guru
 * @param int $assignment_id ID penugasan
 * @return array|bool Data aplikasi jika berhasil, false jika gagal
 */
function get_application_by_guru_assignment($guru_id, $assignment_id) {
    $conn = db_connect();
    $stmt = $conn->prepare("
        SELECT * FROM applications 
        WHERE guru_id = ? AND assignment_id = ?
    ");
    $stmt->execute([$guru_id, $assignment_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Fungsi untuk mendapatkan daftar aplikasi/lamaran berdasarkan parameter
 * 
 * @param array $params Parameter untuk filtering
 * @param int $limit Batas jumlah data
 * @param int $offset Offset untuk pagination
 * @return array Daftar aplikasi/lamaran
 */
function get_applications($params = [], $limit = 10, $offset = 0) {
    $conn = db_connect();
    
    $query = "
        SELECT app.*, a.judul, a.mata_pelajaran, a.tingkat_kelas, a.tanggal_mulai, a.tanggal_selesai,
               a.gaji, a.status as assignment_status,
               u_guru.full_name as guru_name, g.mata_pelajaran as guru_mata_pelajaran,
               g.tingkat_mengajar as guru_tingkat_mengajar, g.rating as guru_rating,
               u_sekolah.full_name as sekolah_name, s.nama_sekolah
        FROM applications app
        JOIN assignments a ON app.assignment_id = a.id
        JOIN users u_guru ON app.guru_id = u_guru.id
        JOIN profiles_guru g ON u_guru.id = g.user_id
        JOIN users u_sekolah ON a.sekolah_id = u_sekolah.id
        JOIN profiles_sekolah s ON u_sekolah.id = s.user_id
        WHERE 1=1
    ";
    
    $query_params = [];
    
    // Filter berdasarkan status aplikasi
    if (isset($params['status']) && !empty($params['status'])) {
        $query .= " AND app.status = ?";
        $query_params[] = $params['status'];
    }
    
    // Filter berdasarkan guru
    if (isset($params['guru_id']) && !empty($params['guru_id'])) {
        $query .= " AND app.guru_id = ?";
        $query_params[] = $params['guru_id'];
    }
    
    // Filter berdasarkan penugasan
    if (isset($params['assignment_id']) && !empty($params['assignment_id'])) {
        $query .= " AND app.assignment_id = ?";
        $query_params[] = $params['assignment_id'];
    }
    
    // Filter berdasarkan sekolah
    if (isset($params['sekolah_id']) && !empty($params['sekolah_id'])) {
        $query .= " AND a.sekolah_id = ?";
        $query_params[] = $params['sekolah_id'];
    }
    
    // Sorting
    if (isset($params['sort']) && !empty($params['sort'])) {
        $sort_field = 'app.created_at';
        $sort_order = 'DESC';
        
        if ($params['sort'] == 'created_asc') {
            $sort_field = 'app.created_at';
            $sort_order = 'ASC';
        } elseif ($params['sort'] == 'rating_desc') {
            $sort_field = 'g.rating';
            $sort_order = 'DESC';
        } elseif ($params['sort'] == 'rating_asc') {
            $sort_field = 'g.rating';
            $sort_order = 'ASC';
        }
        
        $query .= " ORDER BY $sort_field $sort_order";
    } else {
        $query .= " ORDER BY app.created_at DESC";
    }
    
    // Limit dan offset
    $query .= " LIMIT ? OFFSET ?";
    $query_params[] = $limit;
    $query_params[] = $offset;
    
    $stmt = $conn->prepare($query);
    $stmt->execute($query_params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Fungsi untuk mendapatkan total jumlah aplikasi/lamaran berdasarkan filter
 * 
 * @param array $params Parameter untuk filtering
 * @return int Total jumlah aplikasi/lamaran
 */
function get_total_applications($params = []) {
    $conn = db_connect();
    
    $query = "
        SELECT COUNT(*) as total
        FROM applications app
        JOIN assignments a ON app.assignment_id = a.id
        JOIN users u_guru ON app.guru_id = u_guru.id
        JOIN profiles_guru g ON u_guru.id = g.user_id
        JOIN users u_sekolah ON a.sekolah_id = u_sekolah.id
        JOIN profiles_sekolah s ON u_sekolah.id = s.user_id
        WHERE 1=1
    ";
    
    $query_params = [];
    
    // Filter berdasarkan status aplikasi
    if (isset($params['status']) && !empty($params['status'])) {
        $query .= " AND app.status = ?";
        $query_params[] = $params['status'];
    }
    
    // Filter berdasarkan guru
    if (isset($params['guru_id']) && !empty($params['guru_id'])) {
        $query .= " AND app.guru_id = ?";
        $query_params[] = $params['guru_id'];
    }
    
    // Filter berdasarkan penugasan
    if (isset($params['assignment_id']) && !empty($params['assignment_id'])) {
        $query .= " AND app.assignment_id = ?";
        $query_params[] = $params['assignment_id'];
    }
    
    // Filter berdasarkan sekolah
    if (isset($params['sekolah_id']) && !empty($params['sekolah_id'])) {
        $query .= " AND a.sekolah_id = ?";
        $query_params[] = $params['sekolah_id'];
    }
    
    $stmt = $conn->prepare($query);
    $stmt->execute($query_params);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result['total'] ?? 0;
}

/**
 * Fungsi untuk menerima aplikasi/lamaran
 * 
 * @param int $application_id ID aplikasi/lamaran
 * @param int $sekolah_id ID sekolah (untuk keamanan)
 * @return bool True jika berhasil, false jika gagal
 */
function accept_application($application_id, $sekolah_id) {
    $conn = db_connect();
    
    // Ambil data aplikasi
    $application = get_application($application_id);
    
    if (!$application || $application['sekolah_id'] != $sekolah_id) {
        return false; // Aplikasi tidak ditemukan atau bukan milik sekolah yang bersangkutan
    }
    
    if ($application['status'] != 'pending') {
        return false; // Aplikasi sudah diproses sebelumnya
    }
    
    if ($application['assignment_status'] != 'open') {
        return false; // Penugasan sudah tidak terbuka
    }
    
    // Mulai transaksi
    $conn->beginTransaction();
    
    try {
        // Update status aplikasi
        $stmt = $conn->prepare("
            UPDATE applications 
            SET status = 'accepted', updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$application_id]);
        
        // Update status penugasan
        update_assignment_status($application['assignment_id'], 'assigned', $application['guru_id']);
        
        // Tolak semua aplikasi lain untuk penugasan ini
        $stmt = $conn->prepare("
            UPDATE applications 
            SET status = 'rejected', updated_at = NOW() 
            WHERE assignment_id = ? AND id != ? AND status = 'pending'
        ");
        $stmt->execute([$application['assignment_id'], $application_id]);
        
        // Kirim notifikasi ke guru
        notify_guru_application_status($application_id, 'accepted');
        
        // Commit transaksi
        $conn->commit();
        
        return true;
        
    } catch (Exception $e) {
        // Rollback jika terjadi error
        $conn->rollBack();
        return false;
    }
}

/**
 * Fungsi untuk menolak aplikasi/lamaran
 * 
 * @param int $application_id ID aplikasi/lamaran
 * @param int $sekolah_id ID sekolah (untuk keamanan)
 * @return bool True jika berhasil, false jika gagal
 */
function reject_application($application_id, $sekolah_id) {
    $conn = db_connect();
    
    // Ambil data aplikasi
    $application = get_application($application_id);
    
    if (!$application || $application['sekolah_id'] != $sekolah_id) {
        return false; // Aplikasi tidak ditemukan atau bukan milik sekolah yang bersangkutan
    }
    
    if ($application['status'] != 'pending') {
        return false; // Aplikasi sudah diproses sebelumnya
    }
    
    // Update status aplikasi
    $stmt = $conn->prepare("
        UPDATE applications 
        SET status = 'rejected', updated_at = NOW() 
        WHERE id = ?
    ");
    $result = $stmt->execute([$application_id]);
    
    if ($result) {
        // Kirim notifikasi ke guru
        notify_guru_application_status($application_id, 'rejected');
    }
    
    return $result;
}

/**
 * Fungsi untuk membatalkan aplikasi/lamaran oleh guru
 * 
 * @param int $application_id ID aplikasi/lamaran
 * @param int $guru_id ID guru (untuk keamanan)
 * @return bool True jika berhasil, false jika gagal
 */
function cancel_application($application_id, $guru_id) {
    $conn = db_connect();
    
    // Periksa apakah aplikasi dimiliki oleh guru yang bersangkutan
    $stmt = $conn->prepare("
        SELECT * FROM applications 
        WHERE id = ? AND guru_id = ? AND status = 'pending'
    ");
    $stmt->execute([$application_id, $guru_id]);
    
    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        return false; // Aplikasi tidak ditemukan, bukan milik guru, atau sudah diproses
    }
    
    // Hapus aplikasi
    $stmt = $conn->prepare("DELETE FROM applications WHERE id = ?");
    return $stmt->execute([$application_id]);
}

/**
 * Fungsi untuk format status aplikasi/lamaran
 * 
 * @param string $status Status aplikasi/lamaran
 * @return string Status yang diformat
 */
function format_application_status($status) {
    switch ($status) {
        case 'pending':
            return 'Dalam Peninjauan';
        case 'accepted':
            return 'Diterima';
        case 'rejected':
            return 'Ditolak';
        default:
            return 'Tidak Diketahui';
    }
}