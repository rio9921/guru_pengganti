<?php
/**
 * Fungsi-fungsi untuk manajemen sekolah
 * /includes/school-functions.php
 */

/**
 * Mendapatkan profil sekolah berdasarkan user_id
 * 
 * @param int $user_id ID pengguna
 * @return array|false Data profil sekolah atau false jika tidak ditemukan
 */
function get_school_profile_by_user_id($user_id) {
    global $db;
    
    $query = "SELECT * FROM school_profiles WHERE user_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }
    
    return $result->fetch_assoc();
}

/**
 * Memeriksa apakah profil sekolah sudah lengkap
 * 
 * @param array $profile Data profil sekolah
 * @return bool True jika profil lengkap, false jika tidak
 */
function is_profile_complete($profile) {
    // Daftar field yang wajib diisi
    $required_fields = [
        'school_name', 
        'phone', 
        'address', 
        'principal_name', 
        'contact_person', 
        'contact_phone'
    ];
    
    foreach ($required_fields as $field) {
        if (empty($profile[$field])) {
            return false;
        }
    }
    
    return true;
}

/**
 * Mendapatkan semua permintaan guru yang dibuat oleh sekolah
 * 
 * @param int $school_id ID profil sekolah
 * @param string|null $status Filter berdasarkan status (opsional)
 * @return array Daftar permintaan guru
 */
function get_school_assignments($school_id, $status = null) {
    global $db;
    
    $query = "SELECT * FROM assignments WHERE school_id = ?";
    
    if ($status) {
        $query .= " AND status = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("is", $school_id, $status);
    } else {
        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $school_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $assignments = [];
    while ($row = $result->fetch_assoc()) {
        $assignments[] = $row;
    }
    
    return $assignments;
}

/**
 * Mendapatkan detail permintaan guru berdasarkan ID
 * 
 * @param int $assignment_id ID permintaan guru
 * @return array|false Detail permintaan atau false jika tidak ditemukan
 */
function get_assignment_detail($assignment_id) {
    global $db;
    
    $query = "SELECT * FROM assignments WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $assignment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }
    
    return $result->fetch_assoc();
}

/**
 * Membuat permintaan guru baru
 * 
 * @param array $data Data permintaan guru
 * @return int|false ID permintaan baru jika berhasil, false jika gagal
 */
function create_assignment($data) {
    global $db;
    
    $query = "INSERT INTO assignments (
                school_id, 
                title, 
                description, 
                subject, 
                grade, 
                start_date, 
                end_date, 
                start_time, 
                end_time, 
                location, 
                is_permanent, 
                budget
              ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param(
        "isssssssssid",
        $data['school_id'],
        $data['title'],
        $data['description'],
        $data['subject'],
        $data['grade'],
        $data['start_date'],
        $data['end_date'],
        $data['start_time'],
        $data['end_time'],
        $data['location'],
        $data['is_permanent'],
        $data['budget']
    );
    
    if ($stmt->execute()) {
        return $db->insert_id;
    }
    
    return false;
}

/**
 * Memperbarui status permintaan guru
 * 
 * @param int $assignment_id ID permintaan guru
 * @param string $status Status baru ('open', 'in_progress', 'completed', 'cancelled')
 * @return bool True jika berhasil, false jika gagal
 */
function update_assignment_status($assignment_id, $status) {
    global $db;
    
    $query = "UPDATE assignments SET status = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("si", $status, $assignment_id);
    
    return $stmt->execute();
}

/**
 * Mendapatkan daftar aplikasi/lamaran untuk sebuah permintaan guru
 * 
 * @param int $assignment_id ID permintaan guru
 * @return array Daftar aplikasi/lamaran
 */
function get_assignment_applications($assignment_id) {
    global $db;
    
    $query = "SELECT a.*, tp.full_name, tp.profile_picture, tp.rating, tp.education, tp.experience 
              FROM applications a 
              JOIN teacher_profiles tp ON a.teacher_id = tp.id 
              WHERE a.assignment_id = ?
              ORDER BY a.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $assignment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $applications = [];
    while ($row = $result->fetch_assoc()) {
        $applications[] = $row;
    }
    
    return $applications;
}

/**
 * Menerima aplikasi/lamaran dari guru
 * 
 * @param int $application_id ID aplikasi/lamaran
 * @param int $assignment_id ID permintaan guru
 * @return bool True jika berhasil, false jika gagal
 */
function accept_application($application_id, $assignment_id) {
    global $db;
    
    // Mulai transaksi
    $db->begin_transaction();
    
    try {
        // Update status aplikasi yang diterima
        $query1 = "UPDATE applications SET status = 'accepted' WHERE id = ?";
        $stmt1 = $db->prepare($query1);
        $stmt1->bind_param("i", $application_id);
        $stmt1->execute();
        
        // Update status permintaan menjadi in_progress
        $query2 = "UPDATE assignments SET status = 'in_progress' WHERE id = ?";
        $stmt2 = $db->prepare($query2);
        $stmt2->bind_param("i", $assignment_id);
        $stmt2->execute();
        
        // Tolak semua aplikasi lain untuk permintaan yang sama
        $query3 = "UPDATE applications SET status = 'rejected' WHERE assignment_id = ? AND id != ?";
        $stmt3 = $db->prepare($query3);
        $stmt3->bind_param("ii", $assignment_id, $application_id);
        $stmt3->execute();
        
        // Commit transaksi
        $db->commit();
        return true;
    } catch (Exception $e) {
        // Rollback jika terjadi error
        $db->rollback();
        return false;
    }
}

/**
 * Mendapatkan guru yang diterima untuk sebuah permintaan
 * 
 * @param int $assignment_id ID permintaan guru
 * @return array|false Data guru atau false jika tidak ada
 */
function get_accepted_teacher($assignment_id) {
    global $db;
    
    $query = "SELECT a.*, tp.* 
              FROM applications a 
              JOIN teacher_profiles tp ON a.teacher_id = tp.id 
              JOIN users u ON tp.user_id = u.id
              WHERE a.assignment_id = ? AND a.status = 'accepted'";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $assignment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }
    
    return $result->fetch_assoc();
}

/**
 * Mendapatkan judul permintaan berdasarkan ID
 * 
 * @param int $assignment_id ID permintaan
 * @return string Judul permintaan
 */
function get_assignment_title($assignment_id) {
    global $db;
    
    $query = "SELECT title FROM assignments WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $assignment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return "Permintaan #" . $assignment_id;
    }
    
    $row = $result->fetch_assoc();
    return $row['title'];
}

/**
 * Mendapatkan daftar pembayaran untuk sekolah
 * 
 * @param int $school_id ID profil sekolah
 * @param int|null $limit Jumlah maksimal data yang diambil (opsional)
 * @return array Daftar pembayaran
 */
function get_school_payments($school_id, $limit = null) {
    global $db;
    
    $query = "SELECT p.* 
              FROM payments p 
              JOIN assignments a ON p.assignment_id = a.id 
              WHERE a.school_id = ? 
              ORDER BY p.created_at DESC";
    
    if ($limit) {
        $query .= " LIMIT ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("ii", $school_id, $limit);
    } else {
        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $school_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $payments = [];
    while ($row = $result->fetch_assoc()) {
        $payments[] = $row;
    }
    
    return $payments;
}

/**
 * Mendapatkan URL logo sekolah
 * 
 * @param int $school_id ID profil sekolah
 * @return string|false URL logo atau false jika tidak ada
 */
function get_school_logo($school_id) {
    global $db;
    
    $query = "SELECT d.file_path 
              FROM verification_documents d 
              JOIN school_profiles sp ON d.user_id = sp.user_id 
              WHERE sp.id = ? AND d.document_type = 'school_license' AND d.status = 'verified'";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $school_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }
    
    $row = $result->fetch_assoc();
    return $row['file_path'];
}

/**
 * Membuat percakapan baru antara sekolah dan guru
 * 
 * @param int $school_user_id ID pengguna sekolah
 * @param int $teacher_user_id ID pengguna guru
 * @param int|null $assignment_id ID permintaan (opsional)
 * @return int|false ID percakapan baru jika berhasil, false jika gagal
 */
function create_school_teacher_conversation($school_user_id, $teacher_user_id, $assignment_id = null) {
    global $db;
    
    // Cek apakah sudah ada percakapan sebelumnya
    $query = "SELECT c.id 
              FROM conversations c 
              JOIN messages m ON c.id = m.conversation_id 
              WHERE (
                  (m.sender_id = ? AND EXISTS (SELECT 1 FROM messages WHERE conversation_id = c.id AND sender_id = ?)) 
                  OR 
                  (m.sender_id = ? AND EXISTS (SELECT 1 FROM messages WHERE conversation_id = c.id AND sender_id = ?))
              )";
    
    if ($assignment_id) {
        $query .= " AND c.assignment_id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("iiiii", $school_user_id, $teacher_user_id, $teacher_user_id, $school_user_id, $assignment_id);
    } else {
        $query .= " AND c.assignment_id IS NULL";
        $stmt = $db->prepare($query);
        $stmt->bind_param("iiii", $school_user_id, $teacher_user_id, $teacher_user_id, $school_user_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['id'];
    }
    
    // Buat percakapan baru jika belum ada
    if ($assignment_id) {
        $query = "INSERT INTO conversations (assignment_id) VALUES (?)";
        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $assignment_id);
    } else {
        $query = "INSERT INTO conversations (assignment_id) VALUES (NULL)";
        $stmt = $db->prepare($query);
    }
    
    if ($stmt->execute()) {
        return $db->insert_id;
    }
    
    return false;
}

/**
 * Membuat ulasan untuk guru
 * 
 * @param array $data Data ulasan
 * @return int|false ID ulasan baru jika berhasil, false jika gagal
 */
function create_teacher_review($data) {
    global $db;
    
    $query = "INSERT INTO reviews (
                assignment_id, 
                reviewer_id, 
                teacher_id, 
                rating, 
                comment
              ) VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param(
        "iiiis",
        $data['assignment_id'],
        $data['reviewer_id'],
        $data['teacher_id'],
        $data['rating'],
        $data['comment']
    );
    
    if ($stmt->execute()) {
        // Update rating guru
        update_teacher_rating($data['teacher_id']);
        return $db->insert_id;
    }
    
    return false;
}

/**
 * Memeriksa apakah sekolah berhak memberi ulasan untuk guru
 * 
 * @param int $school_id ID profil sekolah
 * @param int $assignment_id ID permintaan
 * @return bool True jika berhak, false jika tidak
 */
function can_review_teacher($school_id, $assignment_id) {
    global $db;
    
    // Cek apakah permintaan milik sekolah ini dan sudah selesai
    $query = "SELECT * FROM assignments WHERE id = ? AND school_id = ? AND status = 'completed'";
    $stmt = $db->prepare($query);
    $stmt->bind_param("ii", $assignment_id, $school_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }
    
    // Cek apakah sudah pernah memberi ulasan
    $query = "SELECT r.id 
              FROM reviews r 
              JOIN users u ON r.reviewer_id = u.id 
              JOIN school_profiles sp ON u.id = sp.user_id 
              WHERE r.assignment_id = ? AND sp.id = ?";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param("ii", $assignment_id, $school_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Return true jika belum pernah memberi ulasan
    return ($result->num_rows === 0);
}

/**
 * Mendapatkan pilihan mata pelajaran untuk dropdown
 * 
 * @return array Daftar mata pelajaran
 */
function get_subject_options() {
    return [
        'Matematika',
        'Bahasa Indonesia',
        'Bahasa Inggris',
        'IPA',
        'Fisika',
        'Kimia',
        'Biologi',
        'IPS',
        'Sejarah',
        'Geografi',
        'Ekonomi',
        'Sosiologi',
        'Pendidikan Agama',
        'PPKN',
        'Penjas',
        'Seni Budaya',
        'TIK/Informatika',
        'Prakarya',
        'Bahasa Daerah',
        'Lainnya'
    ];
}

/**
 * Mendapatkan pilihan kelas untuk dropdown
 * 
 * @return array Daftar kelas
 */
function get_grade_options() {
    return [
        'TK/PAUD',
        'SD Kelas 1',
        'SD Kelas 2',
        'SD Kelas 3',
        'SD Kelas 4',
        'SD Kelas 5',
        'SD Kelas 6',
        'SMP Kelas 7',
        'SMP Kelas 8',
        'SMP Kelas 9',
        'SMA Kelas 10',
        'SMA Kelas 11',
        'SMA Kelas 12',
        'SMK Kelas 10',
        'SMK Kelas 11',
        'SMK Kelas 12',
    ];
}
?>