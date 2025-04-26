<?php
/**
 * GuruSinergi - Fungsi-fungsi Notifikasi
 * 
 * Kumpulan fungsi yang berhubungan dengan notifikasi pengguna
 */

// Include file konfigurasi jika belum
if (!function_exists('config')) {
    require_once dirname(__DIR__) . '/config/config.php';
}

// Include file database jika belum
if (!function_exists('db_connect')) {
    require_once dirname(__DIR__) . '/config/database.php';
}

/**
 * Fungsi untuk mengirim notifikasi
 * 
 * @param int $user_id ID pengguna penerima
 * @param string $title Judul notifikasi
 * @param string $message Isi notifikasi
 * @param string $link Link terkait (opsional)
 * @return bool True jika berhasil, false jika gagal
 */
function send_notification($user_id, $title, $message, $link = '') {
    $conn = db_connect();
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, link) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$user_id, $title, $message, $link]);
}

/**
 * Fungsi untuk mendapatkan jumlah notifikasi yang belum dibaca
 * 
 * @param int $user_id ID pengguna
 * @return int Jumlah notifikasi yang belum dibaca
 */
function get_unread_notifications_count($user_id) {
    $conn = db_connect();
    $stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn();
}

/**
 * Fungsi untuk mendapatkan daftar notifikasi
 * 
 * @param int $user_id ID pengguna
 * @param int $limit Batas jumlah notifikasi (opsional)
 * @param int $offset Offset untuk pagination (opsional)
 * @return array Daftar notifikasi
 */
function get_notifications($user_id, $limit = 10, $offset = 0) {
    $conn = db_connect();
    $stmt = $conn->prepare("
        SELECT * FROM notifications 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$user_id, $limit, $offset]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Fungsi untuk menandai notifikasi sebagai telah dibaca
 * 
 * @param int $notification_id ID notifikasi
 * @param int $user_id ID pengguna (untuk keamanan)
 * @return bool True jika berhasil, false jika gagal
 */
function mark_notification_as_read($notification_id, $user_id) {
    $conn = db_connect();
    $stmt = $conn->prepare("
        UPDATE notifications 
        SET is_read = 1 
        WHERE id = ? AND user_id = ?
    ");
    return $stmt->execute([$notification_id, $user_id]);
}

/**
 * Fungsi untuk menandai semua notifikasi sebagai telah dibaca
 * 
 * @param int $user_id ID pengguna
 * @return bool True jika berhasil, false jika gagal
 */
function mark_all_notifications_as_read($user_id) {
    $conn = db_connect();
    $stmt = $conn->prepare("
        UPDATE notifications 
        SET is_read = 1 
        WHERE user_id = ?
    ");
    return $stmt->execute([$user_id]);
}

/**
 * Fungsi untuk menghapus notifikasi
 * 
 * @param int $notification_id ID notifikasi
 * @param int $user_id ID pengguna (untuk keamanan)
 * @return bool True jika berhasil, false jika gagal
 */
function delete_notification($notification_id, $user_id) {
    $conn = db_connect();
    $stmt = $conn->prepare("
        DELETE FROM notifications 
        WHERE id = ? AND user_id = ?
    ");
    return $stmt->execute([$notification_id, $user_id]);
}

/**
 * Fungsi untuk menghapus semua notifikasi
 * 
 * @param int $user_id ID pengguna
 * @return bool True jika berhasil, false jika gagal
 */
function delete_all_notifications($user_id) {
    $conn = db_connect();
    $stmt = $conn->prepare("
        DELETE FROM notifications 
        WHERE user_id = ?
    ");
    return $stmt->execute([$user_id]);
}

/**
 * Fungsi untuk notifikasi admin tentang guru baru yang mendaftar
 * 
 * @param int $guru_id ID guru
 * @return bool True jika berhasil, false jika gagal
 */
function notify_admin_new_guru($guru_id) {
    $conn = db_connect();
    
    // Ambil data guru
    $stmt = $conn->prepare("
        SELECT u.full_name 
        FROM users u
        WHERE u.id = ?
    ");
    $stmt->execute([$guru_id]);
    $guru = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$guru) {
        return false;
    }
    
    // Ambil semua admin
    $stmt = $conn->prepare("
        SELECT id 
        FROM users 
        WHERE user_type = 'admin'
    ");
    $stmt->execute();
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($admins)) {
        return false;
    }
    
    $title = "Permohonan Verifikasi Guru Baru";
    $message = "Guru baru {$guru['full_name']} telah mendaftar dan menunggu verifikasi.";
    $link = "admin-verification.php?type=guru&id=" . $guru_id;
    
    $success = true;
    
    foreach ($admins as $admin) {
        if (!send_notification($admin['id'], $title, $message, $link)) {
            $success = false;
        }
    }
    
    return $success;
}

/**
 * Fungsi untuk notifikasi admin tentang sekolah baru yang mendaftar
 * 
 * @param int $sekolah_id ID sekolah
 * @return bool True jika berhasil, false jika gagal
 */
function notify_admin_new_sekolah($sekolah_id) {
    $conn = db_connect();
    
    // Ambil data sekolah
    $stmt = $conn->prepare("
        SELECT s.nama_sekolah 
        FROM profiles_sekolah s
        WHERE s.user_id = ?
    ");
    $stmt->execute([$sekolah_id]);
    $sekolah = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sekolah) {
        return false;
    }
    
    // Ambil semua admin
    $stmt = $conn->prepare("
        SELECT id 
        FROM users 
        WHERE user_type = 'admin'
    ");
    $stmt->execute();
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($admins)) {
        return false;
    }
    
    $title = "Permohonan Verifikasi Sekolah Baru";
    $message = "Sekolah baru {$sekolah['nama_sekolah']} telah mendaftar dan menunggu verifikasi.";
    $link = "admin-verification.php?type=sekolah&id=" . $sekolah_id;
    
    $success = true;
    
    foreach ($admins as $admin) {
        if (!send_notification($admin['id'], $title, $message, $link)) {
            $success = false;
        }
    }
    
    return $success;
}

/**
 * Fungsi untuk notifikasi guru tentang status verifikasi
 * 
 * @param int $guru_id ID guru
 * @param string $status Status verifikasi
 * @param string $catatan Catatan verifikasi (opsional)
 * @return bool True jika berhasil, false jika gagal
 */
function notify_guru_verification($guru_id, $status, $catatan = '') {
    $title = "Status Verifikasi Profil";
    
    if ($status == 'verified') {
        $message = "Selamat! Profil Anda telah diverifikasi. Anda sekarang dapat melamar penugasan.";
    } else {
        $message = "Maaf, profil Anda belum dapat diverifikasi.";
        if (!empty($catatan)) {
            $message .= " Catatan: " . $catatan;
        }
    }
    
    $link = "profile.php";
    
    return send_notification($guru_id, $title, $message, $link);
}

/**
 * Fungsi untuk notifikasi sekolah tentang status verifikasi
 * 
 * @param int $sekolah_id ID sekolah
 * @param string $status Status verifikasi
 * @param string $catatan Catatan verifikasi (opsional)
 * @return bool True jika berhasil, false jika gagal
 */
function notify_sekolah_verification($sekolah_id, $status, $catatan = '') {
    $title = "Status Verifikasi Profil Sekolah";
    
    if ($status == 'verified') {
        $message = "Selamat! Profil sekolah Anda telah diverifikasi. Anda sekarang dapat membuat penugasan.";
    } else {
        $message = "Maaf, profil sekolah Anda belum dapat diverifikasi.";
        if (!empty($catatan)) {
            $message .= " Catatan: " . $catatan;
        }
    }
    
    $link = "profile.php";
    
    return send_notification($sekolah_id, $title, $message, $link);
}

/**
 * Fungsi untuk notifikasi guru tentang penugasan baru
 * 
 * @param int $assignment_id ID penugasan
 * @return bool True jika berhasil, false jika gagal
 */
function notify_guru_new_assignment($assignment_id) {
    $conn = db_connect();
    
    // Ambil data penugasan
    $stmt = $conn->prepare("
        SELECT a.judul, a.mata_pelajaran, a.tingkat_kelas
        FROM assignments a
        WHERE a.id = ?
    ");
    $stmt->execute([$assignment_id]);
    $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$assignment) {
        return false;
    }
    
    // Cari guru yang sesuai dengan kriteria
    $stmt = $conn->prepare("
        SELECT u.id FROM users u
        JOIN profiles_guru p ON u.id = p.user_id
        WHERE u.user_type = 'guru'
        AND p.status_verifikasi = 'verified'
        AND p.is_available = 1
        AND (
            p.mata_pelajaran LIKE ? OR 
            p.mata_pelajaran LIKE '%Semua Mata Pelajaran%'
        )
        AND (
            p.tingkat_mengajar LIKE ? OR 
            p.tingkat_mengajar LIKE '%Semua Tingkat%'
        )
    ");
    
    $mata_pelajaran_param = "%{$assignment['mata_pelajaran']}%";
    $tingkat_kelas_param = "%{$assignment['tingkat_kelas']}%";
    $stmt->execute([$mata_pelajaran_param, $tingkat_kelas_param]);
    
    $guru_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($guru_list)) {
        return false;
    }
    
    $title = "Penugasan Baru Tersedia";
    $message = "Ada penugasan baru yang mungkin sesuai dengan Anda: {$assignment['judul']}";
    $link = "assignment-detail.php?id=" . $assignment_id;
    
    $success = true;
    
    foreach ($guru_list as $guru) {
        if (!send_notification($guru['id'], $title, $message, $link)) {
            $success = false;
        }
    }
    
    return $success;
}

/**
 * Fungsi untuk notifikasi sekolah tentang lamaran baru
 * 
 * @param int $application_id ID lamaran
 * @return bool True jika berhasil, false jika gagal
 */
function notify_sekolah_new_application($application_id) {
    $conn = db_connect();
    
    // Ambil data lamaran
    $stmt = $conn->prepare("
        SELECT a.assignment_id, u.full_name as guru_name, asg.judul, asg.sekolah_id
        FROM applications a
        JOIN users u ON a.guru_id = u.id
        JOIN assignments asg ON a.assignment_id = asg.id
        WHERE a.id = ?
    ");
    $stmt->execute([$application_id]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$application) {
        return false;
    }
    
    $title = "Lamaran Guru Baru";
    $message = "Guru {$application['guru_name']} melamar untuk penugasan \"{$application['judul']}\"";
    $link = "review-application.php?id=" . $application_id;
    
    return send_notification($application['sekolah_id'], $title, $message, $link);
}

/**
 * Fungsi untuk notifikasi guru tentang status lamaran
 * 
 * @param int $application_id ID lamaran
 * @param string $status Status lamaran ('accepted' atau 'rejected')
 * @return bool True jika berhasil, false jika gagal
 */
function notify_guru_application_status($application_id, $status) {
    $conn = db_connect();
    
    // Ambil data lamaran
    $stmt = $conn->prepare("
        SELECT a.guru_id, asg.judul, asg.id as assignment_id
        FROM applications a
        JOIN assignments asg ON a.assignment_id = asg.id
        WHERE a.id = ?
    ");
    $stmt->execute([$application_id]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$application) {
        return false;
    }
    
    if ($status == 'accepted') {
        $title = "Lamaran Diterima";
        $message = "Selamat! Lamaran Anda untuk penugasan \"{$application['judul']}\" telah diterima.";
    } else {
        $title = "Lamaran Ditolak";
        $message = "Maaf, lamaran Anda untuk penugasan \"{$application['judul']}\" telah ditolak.";
    }
    
    $link = "assignment-detail.php?id=" . $application['assignment_id'];
    
    return send_notification($application['guru_id'], $title, $message, $link);
}

/**
 * Fungsi untuk notifikasi terkait pembayaran
 * 
 * @param int $payment_id ID pembayaran
 * @param string $status Status pembayaran ('paid', 'expired', 'failed', 'refunded')
 * @return bool True jika berhasil, false jika gagal
 */
function notify_payment_status($payment_id, $status) {
    $conn = db_connect();
    
    // Ambil data pembayaran
    $stmt = $conn->prepare("
        SELECT p.*, a.judul, a.guru_id, a.sekolah_id
        FROM payments p
        JOIN assignments a ON p.assignment_id = a.id
        WHERE p.id = ?
    ");
    $stmt->execute([$payment_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        return false;
    }
    
    $success = true;
    
    // Notifikasi ke sekolah
    $sekolah_title = "";
    $sekolah_message = "";
    
    if ($status == 'paid') {
        $sekolah_title = "Pembayaran Berhasil";
        $sekolah_message = "Pembayaran untuk penugasan \"{$payment['judul']}\" telah berhasil.";
    } elseif ($status == 'expired') {
        $sekolah_title = "Pembayaran Kedaluwarsa";
        $sekolah_message = "Pembayaran untuk penugasan \"{$payment['judul']}\" telah kedaluwarsa.";
    } elseif ($status == 'failed') {
        $sekolah_title = "Pembayaran Gagal";
        $sekolah_message = "Pembayaran untuk penugasan \"{$payment['judul']}\" telah gagal.";
    } elseif ($status == 'refunded') {
        $sekolah_title = "Pembayaran Dikembalikan";
        $sekolah_message = "Pembayaran untuk penugasan \"{$payment['judul']}\" telah dikembalikan.";
    }
    
    $sekolah_link = "assignment-detail.php?id=" . $payment['assignment_id'];
    
    if (!send_notification($payment['sekolah_id'], $sekolah_title, $sekolah_message, $sekolah_link)) {
        $success = false;
    }
    
    // Notifikasi ke guru jika pembayaran berhasil
    if ($status == 'paid') {
        $guru_title = "Penugasan Telah Dibayar";
        $guru_message = "Pembayaran untuk penugasan \"{$payment['judul']}\" telah dikonfirmasi.";
        $guru_link = "assignment-detail.php?id=" . $payment['assignment_id'];
        
        if (!send_notification($payment['guru_id'], $guru_title, $guru_message, $guru_link)) {
            $success = false;
        }
    }
    
    return $success;
}