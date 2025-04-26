<?php
/**
 * GuruSinergi - Fungsi-fungsi Penugasan
 * 
 * Kumpulan fungsi yang berhubungan dengan penugasan
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
if (!function_exists('notify_guru_new_assignment')) {
    require_once dirname(__DIR__) . '/includes/notification-functions.php';
}

/**
 * Fungsi untuk membuat penugasan baru
 * 
 * @param array $data Data penugasan
 * @return int|bool ID penugasan jika berhasil, false jika gagal
 */
function create_assignment($data) {
    // Validasi input
    if (empty($data['sekolah_id']) || empty($data['judul']) || empty($data['mata_pelajaran']) || 
        empty($data['tingkat_kelas']) || empty($data['tanggal_mulai']) || empty($data['tanggal_selesai']) || 
        empty($data['gaji'])) {
        return false;
    }
    
    $conn = db_connect();
    
    // Buat penugasan baru
    $stmt = $conn->prepare("
        INSERT INTO assignments (
            sekolah_id, judul, deskripsi, mata_pelajaran, tingkat_kelas,
            tanggal_mulai, tanggal_selesai, jam_mulai, jam_selesai,
            is_regular, gaji, persyaratan, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'open')
    ");
    
    $result = $stmt->execute([
        $data['sekolah_id'],
        $data['judul'],
        $data['deskripsi'] ?? '',
        $data['mata_pelajaran'],
        $data['tingkat_kelas'],
        $data['tanggal_mulai'],
        $data['tanggal_selesai'],
        $data['jam_mulai'] ?? null,
        $data['jam_selesai'] ?? null,
        $data['is_regular'] ?? 0,
        $data['gaji'],
        $data['persyaratan'] ?? ''
    ]);
    
    if (!$result) {
        return false;
    }
    
    $assignment_id = $conn->lastInsertId();
    
    // Kirim notifikasi ke guru yang cocok
    notify_guru_new_assignment($assignment_id);
    
    return $assignment_id;
}

/**
 * Fungsi untuk mengupdate penugasan
 * 
 * @param int $assignment_id ID penugasan
 * @param array $data Data penugasan yang diupdate
 * @return bool True jika berhasil, false jika gagal
 */
function update_assignment($assignment_id, $data) {
    // Validasi input
    if (empty($assignment_id) || empty($data)) {
        return false;
    }
    
    $conn = db_connect();
    
    // Periksa apakah penugasan masih berstatus 'open'
    $stmt = $conn->prepare("SELECT status FROM assignments WHERE id = ?");
    $stmt->execute([$assignment_id]);
    $current_status = $stmt->fetchColumn();
    
    if ($current_status != 'open') {
        return false; // Tidak bisa mengupdate penugasan yang sudah ditugaskan/selesai/dibatalkan
    }
    
    // Bangun query update
    $query = "UPDATE assignments SET ";
    $params = [];
    $fields = [];
    
    $allowed_fields = [
        'judul', 'deskripsi', 'mata_pelajaran', 'tingkat_kelas',
        'tanggal_mulai', 'tanggal_selesai', 'jam_mulai', 'jam_selesai',
        'is_regular', 'gaji', 'persyaratan'
    ];
    
    foreach ($allowed_fields as $field) {
        if (isset($data[$field])) {
            $fields[] = "$field = ?";
            $params[] = $data[$field];
        }
    }
    
    if (empty($fields)) {
        return false; // Tidak ada data untuk diupdate
    }
    
    $query .= implode(', ', $fields);
    $query .= ", updated_at = NOW() WHERE id = ?";
    $params[] = $assignment_id;
    
    $stmt = $conn->prepare($query);
    return $stmt->execute($params);
}

/**
 * Fungsi untuk menghapus penugasan
 * 
 * @param int $assignment_id ID penugasan
 * @param int $sekolah_id ID sekolah (untuk keamanan)
 * @return bool True jika berhasil, false jika gagal
 */
function delete_assignment($assignment_id, $sekolah_id) {
    $conn = db_connect();
    
    // Periksa apakah penugasan dimiliki oleh sekolah yang bersangkutan
    $stmt = $conn->prepare("SELECT id FROM assignments WHERE id = ? AND sekolah_id = ? AND status = 'open'");
    $stmt->execute([$assignment_id, $sekolah_id]);
    
    if (!$stmt->fetchColumn()) {
        return false; // Penugasan tidak ditemukan atau tidak dimiliki oleh sekolah yang bersangkutan
    }
    
    // Periksa apakah ada lamaran untuk penugasan ini
    $stmt = $conn->prepare("SELECT COUNT(*) FROM applications WHERE assignment_id = ?");
    $stmt->execute([$assignment_id]);
    
    if ($stmt->fetchColumn() > 0) {
        // Jika ada lamaran, ubah status menjadi 'canceled'
        $stmt = $conn->prepare("UPDATE assignments SET status = 'canceled', updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$assignment_id]);
    } else {
        // Jika tidak ada lamaran, hapus penugasan
        $stmt = $conn->prepare("DELETE FROM assignments WHERE id = ?");
        return $stmt->execute([$assignment_id]);
    }
}

/**
 * Fungsi untuk mendapatkan data penugasan
 * 
 * @param int $assignment_id ID penugasan
 * @return array|bool Data penugasan jika berhasil, false jika gagal
 */
function get_assignment($assignment_id) {
    $conn = db_connect();
    $stmt = $conn->prepare("
        SELECT a.*, u.full_name as sekolah_name, s.nama_sekolah, s.jenis_sekolah, 
               s.alamat_lengkap, s.kota, s.provinsi, s.kode_pos, s.contact_person,
               u_guru.full_name as guru_name
        FROM assignments a
        JOIN users u ON a.sekolah_id = u.id
        JOIN profiles_sekolah s ON u.id = s.user_id
        LEFT JOIN users u_guru ON a.guru_id = u_guru.id
        WHERE a.id = ?
    ");
    $stmt->execute([$assignment_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Fungsi untuk mendapatkan daftar penugasan berdasarkan parameter
 * 
 * @param array $params Parameter untuk filtering
 * @param int $limit Batas jumlah data
 * @param int $offset Offset untuk pagination
 * @return array Daftar penugasan
 */
function get_assignments($params = [], $limit = 10, $offset = 0) {
    $conn = db_connect();
    
    $query = "
        SELECT a.*, u.full_name as sekolah_name, s.nama_sekolah, s.kota, s.provinsi,
               (SELECT COUNT(*) FROM applications app WHERE app.assignment_id = a.id) as total_applicants
        FROM assignments a
        JOIN users u ON a.sekolah_id = u.id
        JOIN profiles_sekolah s ON u.id = s.user_id
        WHERE 1=1
    ";
    
    $query_params = [];
    
    // Filter berdasarkan status
    if (isset($params['status']) && !empty($params['status'])) {
        $query .= " AND a.status = ?";
        $query_params[] = $params['status'];
    }
    
    // Filter berdasarkan sekolah
    if (isset($params['sekolah_id']) && !empty($params['sekolah_id'])) {
        $query .= " AND a.sekolah_id = ?";
        $query_params[] = $params['sekolah_id'];
    }
    
    // Filter berdasarkan guru
    if (isset($params['guru_id']) && !empty($params['guru_id'])) {
        $query .= " AND a.guru_id = ?";
        $query_params[] = $params['guru_id'];
    }
    
    // Filter berdasarkan mata pelajaran
    if (isset($params['mata_pelajaran']) && !empty($params['mata_pelajaran'])) {
        $query .= " AND a.mata_pelajaran LIKE ?";
        $query_params[] = '%' . $params['mata_pelajaran'] . '%';
    }
    
    // Filter berdasarkan tingkat kelas
    if (isset($params['tingkat_kelas']) && !empty($params['tingkat_kelas'])) {
        $query .= " AND a.tingkat_kelas LIKE ?";
        $query_params[] = '%' . $params['tingkat_kelas'] . '%';
    }
    
    // Filter berdasarkan tanggal
    if (isset($params['tanggal_mulai']) && !empty($params['tanggal_mulai'])) {
        $query .= " AND a.tanggal_mulai >= ?";
        $query_params[] = $params['tanggal_mulai'];
    }
    
    if (isset($params['tanggal_selesai']) && !empty($params['tanggal_selesai'])) {
        $query .= " AND a.tanggal_selesai <= ?";
        $query_params[] = $params['tanggal_selesai'];
    }
    
    // Filter berdasarkan lokasi
    if (isset($params['kota']) && !empty($params['kota'])) {
        $query .= " AND s.kota LIKE ?";
        $query_params[] = '%' . $params['kota'] . '%';
    }
    
    if (isset($params['provinsi']) && !empty($params['provinsi'])) {
        $query .= " AND s.provinsi LIKE ?";
        $query_params[] = '%' . $params['provinsi'] . '%';
    }
    
    // Sorting
    if (isset($params['sort']) && !empty($params['sort'])) {
        $sort_field = 'a.created_at';
        $sort_order = 'DESC';
        
        if ($params['sort'] == 'tanggal_mulai_asc') {
            $sort_field = 'a.tanggal_mulai';
            $sort_order = 'ASC';
        } elseif ($params['sort'] == 'tanggal_mulai_desc') {
            $sort_field = 'a.tanggal_mulai';
            $sort_order = 'DESC';
        } elseif ($params['sort'] == 'gaji_asc') {
            $sort_field = 'a.gaji';
            $sort_order = 'ASC';
        } elseif ($params['sort'] == 'gaji_desc') {
            $sort_field = 'a.gaji';
            $sort_order = 'DESC';
        } elseif ($params['sort'] == 'created_asc') {
            $sort_field = 'a.created_at';
            $sort_order = 'ASC';
        }
        
        $query .= " ORDER BY $sort_field $sort_order";
    } else {
        $query .= " ORDER BY a.created_at DESC";
    }
    
    // Perbaikan: Konversi limit dan offset menjadi integer
    $limit = intval($limit);
    $offset = intval($offset);
    
    // Limit dan offset
    $query .= " LIMIT $limit OFFSET $offset";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($query_params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Fungsi untuk mengubah status penugasan
 * 
 * @param int $assignment_id ID penugasan
 * @param string $status Status baru
 * @param int $guru_id ID guru (opsional, untuk status 'assigned')
 * @return bool True jika berhasil, false jika gagal
 */
function update_assignment_status($assignment_id, $status, $guru_id = null) {
    if (empty($assignment_id) || empty($status)) {
        return false;
    }
    
    $conn = db_connect();
    
    // Periksa status saat ini
    $stmt = $conn->prepare("SELECT status FROM assignments WHERE id = ?");
    $stmt->execute([$assignment_id]);
    $current_status = $stmt->fetchColumn();
    
    // Validasi perubahan status
    $valid_transitions = [
        'open' => ['assigned', 'canceled'],
        'assigned' => ['completed', 'canceled'],
        'completed' => [],
        'canceled' => []
    ];
    
    if (!isset($valid_transitions[$current_status]) || !in_array($status, $valid_transitions[$current_status])) {
        return false; // Transisi status tidak valid
    }
    
    // Update status
    if ($status == 'assigned' && $guru_id) {
        $stmt = $conn->prepare("UPDATE assignments SET status = ?, guru_id = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$status, $guru_id, $assignment_id]);
    } else {
        $stmt = $conn->prepare("UPDATE assignments SET status = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$status, $assignment_id]);
    }
}

/**
 * Fungsi untuk mendapatkan total jumlah penugasan berdasarkan filter
 * 
 * @param array $params Parameter untuk filtering
 * @return int Total jumlah penugasan
 */
function get_total_assignments($params = []) {
    $conn = db_connect();
    
    $query = "
        SELECT COUNT(*) as total
        FROM assignments a
        JOIN users u ON a.sekolah_id = u.id
        JOIN profiles_sekolah s ON u.id = s.user_id
        WHERE 1=1
    ";
    
    $query_params = [];
    
    // Filter berdasarkan status
    if (isset($params['status']) && !empty($params['status'])) {
        $query .= " AND a.status = ?";
        $query_params[] = $params['status'];
    }
    
    // Filter berdasarkan sekolah
    if (isset($params['sekolah_id']) && !empty($params['sekolah_id'])) {
        $query .= " AND a.sekolah_id = ?";
        $query_params[] = $params['sekolah_id'];
    }
    
    // Filter berdasarkan guru
    if (isset($params['guru_id']) && !empty($params['guru_id'])) {
        $query .= " AND a.guru_id = ?";
        $query_params[] = $params['guru_id'];
    }
    
    // Filter berdasarkan mata pelajaran
    if (isset($params['mata_pelajaran']) && !empty($params['mata_pelajaran'])) {
        $query .= " AND a.mata_pelajaran LIKE ?";
        $query_params[] = '%' . $params['mata_pelajaran'] . '%';
    }
    
    // Filter berdasarkan tingkat kelas
    if (isset($params['tingkat_kelas']) && !empty($params['tingkat_kelas'])) {
        $query .= " AND a.tingkat_kelas LIKE ?";
        $query_params[] = '%' . $params['tingkat_kelas'] . '%';
    }
    
    // Filter berdasarkan tanggal
    if (isset($params['tanggal_mulai']) && !empty($params['tanggal_mulai'])) {
        $query .= " AND a.tanggal_mulai >= ?";
        $query_params[] = $params['tanggal_mulai'];
    }
    
    if (isset($params['tanggal_selesai']) && !empty($params['tanggal_selesai'])) {
        $query .= " AND a.tanggal_selesai <= ?";
        $query_params[] = $params['tanggal_selesai'];
    }
    
    // Filter berdasarkan lokasi
    if (isset($params['kota']) && !empty($params['kota'])) {
        $query .= " AND s.kota LIKE ?";
        $query_params[] = '%' . $params['kota'] . '%';
    }
    
    if (isset($params['provinsi']) && !empty($params['provinsi'])) {
        $query .= " AND s.provinsi LIKE ?";
        $query_params[] = '%' . $params['provinsi'] . '%';
    }
    
    $stmt = $conn->prepare($query);
    $stmt->execute($query_params);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result['total'] ?? 0;
}

/**
 * Fungsi untuk mendapatkan semua aplikasi/lamaran untuk penugasan tertentu
 * 
 * @param int $assignment_id ID penugasan
 * @return array Daftar aplikasi/lamaran
 */
function get_applications_for_assignment($assignment_id) {
    $conn = db_connect();
    $stmt = $conn->prepare("
        SELECT app.*, u.full_name, g.mata_pelajaran, g.tingkat_mengajar, g.rating, g.total_reviews, u.profile_image
        FROM applications app
        JOIN users u ON app.guru_id = u.id
        JOIN profiles_guru g ON u.id = g.user_id
        WHERE app.assignment_id = ?
        ORDER BY app.created_at DESC
    ");
    $stmt->execute([$assignment_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Fungsi untuk format status penugasan
 * 
 * @param string $status Status penugasan
 * @return string Status yang diformat
 */
function format_assignment_status($status) {
    switch ($status) {
        case 'open':
            return 'Terbuka';
        case 'assigned':
            return 'Ditugaskan';
        case 'completed':
            return 'Selesai';
        case 'canceled':
            return 'Dibatalkan';
        default:
            return 'Tidak Diketahui';
    }
}