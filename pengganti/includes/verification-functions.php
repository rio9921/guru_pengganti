<?php
/**
 * Fungsi-fungsi untuk proses verifikasi identitas
 * /includes/verification-functions.php
 */

/**
 * Menyimpan dokumen verifikasi ke database
 * 
 * @param int $user_id ID pengguna
 * @param string $document_type Tipe dokumen
 * @param string $file_path Path file dokumen
 * @return bool True jika berhasil, false jika gagal
 */
function save_verification_document($user_id, $document_type, $file_path) {
    global $db;
    
    // Cek apakah dokumen sudah ada
    $query = "SELECT id FROM verification_documents WHERE user_id = ? AND document_type = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("is", $user_id, $document_type);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update dokumen yang sudah ada
        $document = $result->fetch_assoc();
        $document_id = $document['id'];
        
        $query = "UPDATE verification_documents SET file_path = ?, status = 'pending', notes = NULL, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("si", $file_path, $document_id);
        
        return $stmt->execute();
    } else {
        // Buat dokumen baru
        $query = "INSERT INTO verification_documents (user_id, document_type, file_path, status) VALUES (?, ?, ?, 'pending')";
        $stmt = $db->prepare($query);
        $stmt->bind_param("iss", $user_id, $document_type, $file_path);
        
        return $stmt->execute();
    }
}

/**
 * Mendapatkan dokumen verifikasi pengguna
 * 
 * @param int $user_id ID pengguna
 * @return array Daftar dokumen verifikasi
 */
function get_user_verification_documents($user_id) {
    global $db;
    
    $query = "SELECT * FROM verification_documents WHERE user_id = ? ORDER BY document_type ASC";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $documents = [];
    while ($row = $result->fetch_assoc()) {
        $documents[] = $row;
    }
    
    return $documents;
}

/**
 * Mengupdate status dokumen verifikasi
 * 
 * @param int $document_id ID dokumen
 * @param string $status Status baru ('pending', 'verified', 'rejected')
 * @param string|null $notes Catatan (opsional)
 * @return bool True jika berhasil, false jika gagal
 */
function update_document_status($document_id, $status, $notes = null) {
    global $db;
    
    $query = "UPDATE verification_documents SET status = ?, notes = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("ssi", $status, $notes, $document_id);
    
    // Jalankan query
    $result = $stmt->execute();
    
    if ($result) {
        // Jika dokumen diverifikasi atau ditolak, kirim notifikasi ke pengguna
        $query = "SELECT vd.user_id, vd.document_type, u.role 
                 FROM verification_documents vd 
                 JOIN users u ON vd.user_id = u.id 
                 WHERE vd.id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $document_id);
        $stmt->execute();
        $doc_result = $stmt->get_result();
        
        if ($doc_result->num_rows > 0) {
            $document = $doc_result->fetch_assoc();
            $user_id = $document['user_id'];
            $role = $document['role'];
            $document_type = $document['document_type'];
            
            // Map dokumen untuk tampilan yang lebih baik
            $document_labels = [
                'ktp' => 'KTP',
                'cv' => 'CV',
                'photo' => 'Foto Profil',
                'certificate' => 'Ijazah/Sertifikat',
                'school_license' => 'Izin Operasional Sekolah'
            ];
            
            $document_label = isset($document_labels[$document_type]) ? $document_labels[$document_type] : $document_type;
            
            if ($status === 'verified') {
                $notification_title = "Dokumen Terverifikasi";
                $notification_message = "Dokumen {$document_label} Anda telah diverifikasi.";
            } else if ($status === 'rejected') {
                $notification_title = "Dokumen Ditolak";
                $notification_message = "Dokumen {$document_label} Anda ditolak." . ($notes ? " Alasan: {$notes}" : "");
            } else {
                return $result;
            }
            
            create_notification($user_id, $notification_title, $notification_message, 'verification', $document_id);
            
            // Cek apakah semua dokumen sudah diverifikasi
            check_user_verification_status($user_id, $role);
        }
    }
    
    return $result;
}

/**
 * Memeriksa status verifikasi pengguna
 * 
 * @param int $user_id ID pengguna
 * @param string $role Peran pengguna ('teacher', 'school')
 * @return bool True jika berhasil, false jika gagal
 */
function check_user_verification_status($user_id, $role) {
    global $db;
    
    // Ambil semua dokumen pengguna
    $documents = get_user_verification_documents($user_id);
    
    // Tentukan dokumen yang diperlukan berdasarkan peran
    $required_documents = [];
    if ($role === 'teacher') {
        $required_documents = ['ktp', 'cv', 'photo', 'certificate'];
    } elseif ($role === 'school') {
        $required_documents = ['school_license'];
    } else {
        return false;
    }
    
    // Cek apakah semua dokumen yang diperlukan sudah diupload dan diverifikasi
    $all_verified = true;
    $any_rejected = false;
    $uploaded_types = [];
    
    foreach ($documents as $doc) {
        $uploaded_types[] = $doc['document_type'];
        
        if (in_array($doc['document_type'], $required_documents)) {
            if ($doc['status'] !== 'verified') {
                $all_verified = false;
            }
            
            if ($doc['status'] === 'rejected') {
                $any_rejected = true;
            }
        }
    }
    
    // Cek apakah semua dokumen yang diperlukan sudah diupload
    foreach ($required_documents as $type) {
        if (!in_array($type, $uploaded_types)) {
            $all_verified = false;
            break;
        }
    }
    
    // Update status verifikasi pengguna
    $new_status = $all_verified ? 'verified' : ($any_rejected ? 'rejected' : 'pending');
    
    if ($role === 'teacher') {
        $query = "UPDATE teacher_profiles SET verification_status = ? WHERE user_id = ?";
    } elseif ($role === 'school') {
        $query = "UPDATE school_profiles SET verification_status = ? WHERE user_id = ?";
    } else {
        return false;
    }
    
    $stmt = $db->prepare($query);
    $stmt->bind_param("si", $new_status, $user_id);
    $result = $stmt->execute();
    
    // Kirim notifikasi jika status verifikasi berubah
    if ($result) {
        if ($new_status === 'verified') {
            $notification_title = "Akun Terverifikasi";
            $notification_message = "Selamat! Akun Anda telah terverifikasi. Anda sekarang dapat menggunakan semua fitur platform.";
            create_notification($user_id, $notification_title, $notification_message, 'verification', null);
        } elseif ($new_status === 'rejected') {
            $notification_title = "Verifikasi Akun Ditolak";
            $notification_message = "Maaf, verifikasi akun Anda ditolak. Silakan periksa dokumen Anda dan upload ulang jika diperlukan.";
            create_notification($user_id, $notification_title, $notification_message, 'verification', null);
        }
    }
    
    return $result;
}

/**
 * Mendapatkan status verifikasi pengguna
 * 
 * @param int $user_id ID pengguna
 * @param string $role Peran pengguna ('teacher', 'school')
 * @return string|false Status verifikasi ('pending', 'verified', 'rejected') atau false jika gagal
 */
function get_user_verification_status($user_id, $role) {
    global $db;
    
    if ($role === 'teacher') {
        $query = "SELECT verification_status FROM teacher_profiles WHERE user_id = ?";
    } elseif ($role === 'school') {
        $query = "SELECT verification_status FROM school_profiles WHERE user_id = ?";
    } else {
        return false;
    }
    
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }
    
    $row = $result->fetch_assoc();
    return $row['verification_status'];
}

/**
 * Mendapatkan detail dokumen verifikasi
 * 
 * @param int $document_id ID dokumen
 * @return array|false Detail dokumen atau false jika tidak ditemukan
 */
function get_document_detail($document_id) {
    global $db;
    
    $query = "SELECT vd.*, u.role, u.email 
              FROM verification_documents vd
              JOIN users u ON vd.user_id = u.id
              WHERE vd.id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $document_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }
    
    return $result->fetch_assoc();
}

/**
 * Mendapatkan daftar dokumen yang perlu diverifikasi
 * 
 * @param int $limit Batas jumlah data
 * @param int $offset Offset untuk paginasi
 * @return array Daftar dokumen
 */
function get_pending_verification_documents($limit = 20, $offset = 0) {
    global $db;
    
    $query = "SELECT vd.*, u.role, u.email,
              CASE 
                  WHEN u.role = 'teacher' THEN tp.full_name
                  WHEN u.role = 'school' THEN sp.school_name
                  ELSE 'Unknown'
              END as user_name
              FROM verification_documents vd
              JOIN users u ON vd.user_id = u.id
              LEFT JOIN teacher_profiles tp ON u.id = tp.user_id AND u.role = 'teacher'
              LEFT JOIN school_profiles sp ON u.id = sp.user_id AND u.role = 'school'
              WHERE vd.status = 'pending'
              ORDER BY vd.created_at ASC
              LIMIT ?, ?";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param("ii", $offset, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $documents = [];
    while ($row = $result->fetch_assoc()) {
        $documents[] = $row;
    }
    
    return $documents;
}

/**
 * Mendapatkan total dokumen yang perlu diverifikasi
 * 
 * @return int Jumlah dokumen
 */
function get_pending_verification_count() {
    global $db;
    
    $query = "SELECT COUNT(*) as total FROM verification_documents WHERE status = 'pending'";
    $result = $db->query($query);
    $row = $result->fetch_assoc();
    
    return $row['total'];
}

/**
 * Mendapatkan statistik verifikasi
 * 
 * @return array Statistik verifikasi
 */
function get_verification_statistics() {
    global $db;
    
    $stats = [
        'total_documents' => 0,
        'pending_documents' => 0,
        'verified_documents' => 0,
        'rejected_documents' => 0,
        'total_users' => 0,
        'verified_users' => 0,
        'pending_users' => 0,
        'rejected_users' => 0,
        'avg_verification_time' => 0
    ];
    
    // Statistik dokumen
    $query = "SELECT 
              COUNT(*) as total,
              SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
              SUM(CASE WHEN status = 'verified' THEN 1 ELSE 0 END) as verified,
              SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
              FROM verification_documents";
    
    $result = $db->query($query);
    $row = $result->fetch_assoc();
    
    $stats['total_documents'] = $row['total'];
    $stats['pending_documents'] = $row['pending'];
    $stats['verified_documents'] = $row['verified'];
    $stats['rejected_documents'] = $row['rejected'];
    
    // Statistik pengguna - guru
    $query = "SELECT 
              COUNT(*) as total,
              SUM(CASE WHEN verification_status = 'pending' THEN 1 ELSE 0 END) as pending,
              SUM(CASE WHEN verification_status = 'verified' THEN 1 ELSE 0 END) as verified,
              SUM(CASE WHEN verification_status = 'rejected' THEN 1 ELSE 0 END) as rejected
              FROM teacher_profiles";
    
    $result = $db->query($query);
    if ($result) {
        $row = $result->fetch_assoc();
        
        $stats['total_users'] += $row['total'];
        $stats['pending_users'] += $row['pending'];
        $stats['verified_users'] += $row['verified'];
        $stats['rejected_users'] += $row['rejected'];
    }
    
    // Statistik pengguna - sekolah
    $query = "SELECT 
              COUNT(*) as total,
              SUM(CASE WHEN verification_status = 'pending' THEN 1 ELSE 0 END) as pending,
              SUM(CASE WHEN verification_status = 'verified' THEN 1 ELSE 0 END) as verified,
              SUM(CASE WHEN verification_status = 'rejected' THEN 1 ELSE 0 END) as rejected
              FROM school_profiles";
    
    $result = $db->query($query);
    if ($result) {
        $row = $result->fetch_assoc();
        
        $stats['total_users'] += $row['total'];
        $stats['pending_users'] += $row['pending'];
        $stats['verified_users'] += $row['verified'];
        $stats['rejected_users'] += $row['rejected'];
    }
    
    // Rata-rata waktu verifikasi (dalam jam)
    $query = "SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_time 
              FROM verification_documents 
              WHERE status IN ('verified', 'rejected') AND updated_at > created_at";
    
    $result = $db->query($query);
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['avg_verification_time'] = round($row['avg_time'], 1);
    }
    
    return $stats;
}

/**
 * Memeriksa apakah pengguna sudah mengupload semua dokumen yang diperlukan
 * 
 * @param int $user_id ID pengguna
 * @param string $role Peran pengguna ('teacher', 'school')
 * @return bool True jika sudah mengupload semua, false jika belum
 */
function has_uploaded_all_required_documents($user_id, $role) {
    // Ambil semua dokumen pengguna
    $documents = get_user_verification_documents($user_id);
    
    // Tentukan dokumen yang diperlukan berdasarkan peran
    $required_documents = [];
    if ($role === 'teacher') {
        $required_documents = ['ktp', 'cv', 'photo', 'certificate'];
    } elseif ($role === 'school') {
        $required_documents = ['school_license'];
    } else {
        return false;
    }
    
    // Cek apakah semua dokumen yang diperlukan sudah diupload
    $uploaded_types = [];
    foreach ($documents as $doc) {
        $uploaded_types[] = $doc['document_type'];
    }
    
    foreach ($required_documents as $type) {
        if (!in_array($type, $uploaded_types)) {
            return false;
        }
    }
    
    return true;
}

/**
 * Memeriksa apakah ada pengguna yang perlu diverifikasi
 * 
 * @return bool True jika ada, false jika tidak
 */
function has_pending_verifications() {
    global $db;
    
    $query = "SELECT COUNT(*) as count FROM verification_documents WHERE status = 'pending'";
    $result = $db->query($query);
    $row = $result->fetch_assoc();
    
    return ($row['count'] > 0);
}
?>