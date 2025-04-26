<?php
/**
 * Fungsi-fungsi untuk sistem pencocokan guru dengan permintaan
 * /includes/matching-functions.php
 */

/**
 * Mencatat log penggunaan algoritma pencocokan
 * 
 * @param int $assignment_id ID permintaan
 * @param int $result_count Jumlah hasil pencocokan
 * @param float $query_time Waktu eksekusi query (detik)
 * @param string $algorithm Jenis algoritma yang digunakan (claude, basic)
 * @return bool True jika berhasil, false jika gagal
 */
function log_matching_algorithm($assignment_id, $result_count, $query_time, $algorithm = 'basic') {
    global $db;
    
    $query = "INSERT INTO algorithm_logs (assignment_id, user_id, result_count, query_time, algorithm_type)
              VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param("iiids", $assignment_id, $_SESSION['user_id'], $result_count, $query_time, $algorithm);
    
    return $stmt->execute();
}

/**
 * Mendapatkan daftar guru yang tersedia berdasarkan mata pelajaran
 * 
 * @param string $subject Mata pelajaran
 * @param int $limit Jumlah maksimal data yang diambil
 * @return array Daftar guru yang tersedia
 */
function get_available_teachers_by_subject($subject, $limit = 10) {
    global $db;
    
    $query = "SELECT tp.* FROM teacher_profiles tp 
              JOIN users u ON tp.user_id = u.id 
              WHERE u.role = 'teacher' 
              AND tp.verification_status = 'verified'
              AND tp.available_status = 'available'";
    
    if (!empty($subject) && $subject !== 'Lainnya') {
        $query .= " AND (tp.subject_expertise LIKE ? OR tp.subject_expertise LIKE ?)";
        $subject_param = '%' . $subject . '%';
        $subject_end = $subject . ',%';
        
        $stmt = $db->prepare($query);
        $stmt->bind_param("ss", $subject_param, $subject_end);
    } else {
        $stmt = $db->prepare($query);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $teachers = [];
    while ($row = $result->fetch_assoc()) {
        $teachers[] = $row;
    }
    
    return $teachers;
}

/**
 * Mendapatkan rekomendasi guru terbaik untuk permintaan tertentu
 * 
 * @param int $assignment_id ID permintaan
 * @return array|false Data guru atau false jika tidak ditemukan
 */
function get_best_teacher_recommendation($assignment_id) {
    global $db;
    
    // Ambil detail permintaan
    $query = "SELECT * FROM assignments WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $assignment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }
    
    $assignment = $result->fetch_assoc();
    
    // Dapatkan guru-guru yang cocok
    $matching_teachers = get_matching_teachers($assignment, 1);
    
    if (empty($matching_teachers)) {
        return false;
    }
    
    return $matching_teachers[0];
}

/**
 * Memeriksa apakah guru tersedia pada jadwal tertentu
 * 
 * @param int $teacher_id ID profil guru
 * @param string $start_date Tanggal mulai
 * @param string $end_date Tanggal selesai
 * @param string $start_time Waktu mulai
 * @param string $end_time Waktu selesai
 * @return bool True jika tersedia, false jika sudah ada jadwal
 */
function is_teacher_available($teacher_id, $start_date, $end_date, $start_time, $end_time) {
    global $db;
    
    // Cek status ketersediaan guru
    $query1 = "SELECT available_status FROM teacher_profiles WHERE id = ?";
    $stmt1 = $db->prepare($query1);
    $stmt1->bind_param("i", $teacher_id);
    $stmt1->execute();
    $result1 = $stmt1->get_result();
    
    if ($result1->num_rows === 0) {
        return false;
    }
    
    $row = $result1->fetch_assoc();
    if ($row['available_status'] !== 'available') {
        return false;
    }
    
    // Cek jadwal guru yang sudah ada
    $query2 = "SELECT a.* FROM assignments a
               JOIN applications app ON a.id = app.assignment_id
               WHERE app.teacher_id = ? 
               AND app.status = 'accepted'
               AND a.status = 'in_progress'
               AND (
                   (a.start_date <= ? AND a.end_date >= ?)
                   OR
                   (a.start_date <= ? AND a.end_date >= ?)
                   OR
                   (a.start_date >= ? AND a.end_date <= ?)
               )";
    
    $stmt2 = $db->prepare($query2);
    $stmt2->bind_param("issssss", $teacher_id, $start_date, $start_date, $end_date, $end_date, $start_date, $end_date);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    
    if ($result2->num_rows === 0) {
        return true; // Tidak ada jadwal yang bentrok
    }
    
    // Cek apakah jadwal bentrok
    while ($assignment = $result2->fetch_assoc()) {
        // Konversi waktu ke menit untuk perbandingan yang lebih mudah
        $new_start_minutes = time_to_minutes($start_time);
        $new_end_minutes = time_to_minutes($end_time);
        $existing_start_minutes = time_to_minutes($assignment['start_time']);
        $existing_end_minutes = time_to_minutes($assignment['end_time']);
        
        // Cek apakah waktu bentrok
        if (
            ($new_start_minutes <= $existing_end_minutes && $new_end_minutes >= $existing_start_minutes) ||
            ($existing_start_minutes <= $new_end_minutes && $existing_end_minutes >= $new_start_minutes)
        ) {
            return false; // Jadwal bentrok
        }
    }
    
    return true; // Tidak ada bentrok jadwal
}

/**
 * Konversi waktu (HH:MM:SS) ke menit
 * 
 * @param string $time Waktu dalam format HH:MM:SS
 * @return int Waktu dalam menit
 */
function time_to_minutes($time) {
    $parts = explode(':', $time);
    return ($parts[0] * 60) + $parts[1];
}

/**
 * Mendapatkan daftar aplikasi/lamaran untuk permintaan tertentu
 * 
 * @param int $assignment_id ID permintaan
 * @param string|null $status Filter berdasarkan status (opsional)
 * @return array Daftar aplikasi/lamaran
 */
function get_assignment_applications($assignment_id, $status = null) {
    global $db;
    
    $query = "SELECT a.*, tp.full_name, tp.profile_picture, tp.rating 
              FROM applications a 
              JOIN teacher_profiles tp ON a.teacher_id = tp.id 
              WHERE a.assignment_id = ?";
    
    if ($status) {
        $query .= " AND a.status = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("is", $assignment_id, $status);
    } else {
        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $assignment_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $applications = [];
    while ($row = $result->fetch_assoc()) {
        $applications[] = $row;
    }
    
    return $applications;
}

/**
 * Memeriksa apakah guru sudah melamar untuk permintaan tertentu
 * 
 * @param int $teacher_id ID profil guru
 * @param int $assignment_id ID permintaan
 * @return bool True jika sudah melamar, false jika belum
 */
function has_teacher_applied($teacher_id, $assignment_id) {
    global $db;
    
    $query = "SELECT id FROM applications WHERE teacher_id = ? AND assignment_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("ii", $teacher_id, $assignment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return ($result->num_rows > 0);
}

/**
 * Membuat aplikasi/lamaran baru
 * 
 * @param int $teacher_id ID profil guru
 * @param int $assignment_id ID permintaan
 * @param string $cover_letter Surat lamaran
 * @return int|false ID aplikasi baru jika berhasil, false jika gagal
 */
function create_application($teacher_id, $assignment_id, $cover_letter) {
    global $db;
    
    // Periksa apakah guru sudah melamar
    if (has_teacher_applied($teacher_id, $assignment_id)) {
        return false;
    }
    
    $query = "INSERT INTO applications (teacher_id, assignment_id, cover_letter) VALUES (?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->bind_param("iis", $teacher_id, $assignment_id, $cover_letter);
    
    if ($stmt->execute()) {
        $application_id = $db->insert_id;
        
        // Kirim notifikasi ke sekolah
        $assignment_query = "SELECT s.user_id, a.title 
                            FROM assignments a 
                            JOIN school_profiles s ON a.school_id = s.id 
                            WHERE a.id = ?";
        $stmt = $db->prepare($assignment_query);
        $stmt->bind_param("i", $assignment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $school_user_id = $row['user_id'];
            $assignment_title = $row['title'];
            
            // Ambil nama guru
            $teacher_query = "SELECT full_name FROM teacher_profiles WHERE id = ?";
            $stmt = $db->prepare($teacher_query);
            $stmt->bind_param("i", $teacher_id);
            $stmt->execute();
            $teacher_result = $stmt->get_result();
            
            if ($teacher_result->num_rows > 0) {
                $teacher_row = $teacher_result->fetch_assoc();
                $teacher_name = $teacher_row['full_name'];
                
                // Kirim notifikasi
                $notification_title = "Lamaran Guru Baru";
                $notification_message = "$teacher_name telah melamar untuk permintaan \"$assignment_title\"";
                
                create_notification($school_user_id, $notification_title, $notification_message, 'application', $application_id);
            }
        }
        
        return $application_id;
    }
    
    return false;
}

/**
 * Mendapatkan detail aplikasi/lamaran
 * 
 * @param int $application_id ID aplikasi/lamaran
 * @return array|false Detail aplikasi atau false jika tidak ditemukan
 */
function get_application_detail($application_id) {
    global $db;
    
    $query = "SELECT a.*, tp.full_name, tp.profile_picture, tp.rating, tp.education, tp.experience, tp.subject_expertise
              FROM applications a 
              JOIN teacher_profiles tp ON a.teacher_id = tp.id 
              WHERE a.id = ?";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $application_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }
    
    return $result->fetch_assoc();
}

/**
 * Mengubah status aplikasi/lamaran
 * 
 * @param int $application_id ID aplikasi/lamaran
 * @param string $status Status baru ('pending', 'accepted', 'rejected', 'withdrawn')
 * @return bool True jika berhasil, false jika gagal
 */
function update_application_status($application_id, $status) {
    global $db;
    
    $query = "UPDATE applications SET status = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("si", $status, $application_id);
    
    return $stmt->execute();
}

/**
 * Menerima aplikasi/lamaran dari guru
 * 
 * @param int $application_id ID aplikasi/lamaran
 * @return bool True jika berhasil, false jika gagal
 */
function accept_teacher_application($application_id) {
    global $db;
    
    // Mulai transaksi
    $db->begin_transaction();
    
    try {
        // Ambil detail aplikasi
        $application = get_application_detail($application_id);
        
        if (!$application) {
            throw new Exception("Aplikasi tidak ditemukan");
        }
        
        $assignment_id = $application['assignment_id'];
        $teacher_id = $application['teacher_id'];
        
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
        
        // Kirim notifikasi ke guru yang diterima
        $teacher_query = "SELECT user_id FROM teacher_profiles WHERE id = ?";
        $stmt = $db->prepare($teacher_query);
        $stmt->bind_param("i", $teacher_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $teacher_user_id = $row['user_id'];
            
            // Ambil detail permintaan
            $assignment_query = "SELECT title FROM assignments WHERE id = ?";
            $stmt = $db->prepare($assignment_query);
            $stmt->bind_param("i", $assignment_id);
            $stmt->execute();
            $assignment_result = $stmt->get_result();
            
            if ($assignment_result->num_rows > 0) {
                $assignment_row = $assignment_result->fetch_assoc();
                $assignment_title = $assignment_row['title'];
                
                // Kirim notifikasi
                $notification_title = "Lamaran Anda Diterima";
                $notification_message = "Selamat! Lamaran Anda untuk \"$assignment_title\" telah diterima.";
                
                create_notification($teacher_user_id, $notification_title, $notification_message, 'application', $application_id);
            }
        }
        
        // Kirim notifikasi ke guru yang ditolak
        $rejected_query = "SELECT a.id, tp.user_id 
                         FROM applications a 
                         JOIN teacher_profiles tp ON a.teacher_id = tp.id 
                         WHERE a.assignment_id = ? AND a.status = 'rejected'";
        $stmt = $db->prepare($rejected_query);
        $stmt->bind_param("i", $assignment_id);
        $stmt->execute();
        $rejected_result = $stmt->get_result();
        
        while ($rejected = $rejected_result->fetch_assoc()) {
            $rejected_user_id = $rejected['user_id'];
            $rejected_application_id = $rejected['id'];
            
            // Ambil detail permintaan
            $assignment_title_query = "SELECT title FROM assignments WHERE id = ?";
            $stmt = $db->prepare($assignment_title_query);
            $stmt->bind_param("i", $assignment_id);
            $stmt->execute();
            $title_result = $stmt->get_result();
            
            if ($title_result->num_rows > 0) {
                $title_row = $title_result->fetch_assoc();
                $assignment_title = $title_row['title'];
                
                // Kirim notifikasi
                $notification_title = "Lamaran Anda Tidak Diterima";
                $notification_message = "Maaf, lamaran Anda untuk \"$assignment_title\" tidak diterima.";
                
                create_notification($rejected_user_id, $notification_title, $notification_message, 'application', $rejected_application_id);
            }
        }
        
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
 * Membatalkan aplikasi/lamaran
 * 
 * @param int $application_id ID aplikasi/lamaran
 * @return bool True jika berhasil, false jika gagal
 */
function withdraw_application($application_id) {
    global $db;
    
    // Ambil detail aplikasi
    $application = get_application_detail($application_id);
    
    if (!$application) {
        return false;
    }
    
    // Hanya dapat membatalkan jika statusnya masih pending
    if ($application['status'] !== 'pending') {
        return false;
    }
    
    return update_application_status($application_id, 'withdrawn');
}

/**
 * Menolak aplikasi/lamaran
 * 
 * @param int $application_id ID aplikasi/lamaran
 * @return bool True jika berhasil, false jika gagal
 */
function reject_application($application_id) {
    global $db;
    
    // Ambil detail aplikasi
    $application = get_application_detail($application_id);
    
    if (!$application) {
        return false;
    }
    
    // Hanya dapat menolak jika statusnya masih pending
    if ($application['status'] !== 'pending') {
        return false;
    }
    
    if (update_application_status($application_id, 'rejected')) {
        // Kirim notifikasi ke guru
        $teacher_id = $application['teacher_id'];
        $assignment_id = $application['assignment_id'];
        
        $teacher_query = "SELECT user_id FROM teacher_profiles WHERE id = ?";
        $stmt = $db->prepare($teacher_query);
        $stmt->bind_param("i", $teacher_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $teacher_user_id = $row['user_id'];
            
            // Ambil detail permintaan
            $assignment_query = "SELECT title FROM assignments WHERE id = ?";
            $stmt = $db->prepare($assignment_query);
            $stmt->bind_param("i", $assignment_id);
            $stmt->execute();
            $assignment_result = $stmt->get_result();
            
            if ($assignment_result->num_rows > 0) {
                $assignment_row = $assignment_result->fetch_assoc();
                $assignment_title = $assignment_row['title'];
                
                // Kirim notifikasi
                $notification_title = "Lamaran Anda Tidak Diterima";
                $notification_message = "Maaf, lamaran Anda untuk \"$assignment_title\" tidak diterima.";
                
                create_notification($teacher_user_id, $notification_title, $notification_message, 'application', $application_id);
            }
        }
        
        return true;
    }
    
    return false;
}

/**
 * Mendapatkan rekomendasi permintaan untuk guru
 * 
 * @param int $teacher_id ID profil guru
 * @param int $limit Batas jumlah rekomendasi
 * @return array Daftar permintaan yang direkomendasikan
 */
function get_recommended_assignments_for_teacher($teacher_id, $limit = 5) {
    global $db;
    
    // Ambil data guru
    $query = "SELECT * FROM teacher_profiles WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return [];
    }
    
    $teacher = $result->fetch_assoc();
    $subject_expertise = $teacher['subject_expertise'];
    
    // Parsing keahlian mata pelajaran
    $subjects = explode(',', $subject_expertise);
    $subject_conditions = [];
    
    foreach ($subjects as $subject) {
        $subject = trim($subject);
        if (!empty($subject)) {
            $subject_conditions[] = "a.subject LIKE '%" . $db->real_escape_string($subject) . "%'";
        }
    }
    
    $subject_filter = '';
    if (!empty($subject_conditions)) {
        $subject_filter = " AND (" . implode(" OR ", $subject_conditions) . ")";
    }
    
    // Ambil permintaan yang cocok
    $query = "SELECT a.*, s.school_name, 
              (SELECT COUNT(*) FROM applications WHERE assignment_id = a.id) as application_count
              FROM assignments a 
              JOIN school_profiles s ON a.school_id = s.id 
              WHERE a.status = 'open'" . $subject_filter . "
              AND NOT EXISTS (
                  SELECT 1 FROM applications 
                  WHERE assignment_id = a.id AND teacher_id = ?
              )
              ORDER BY a.created_at DESC
              LIMIT ?";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param("ii", $teacher_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $assignments = [];
    while ($row = $result->fetch_assoc()) {
        $assignments[] = $row;
    }
    
    return $assignments;
}

/**
 * Mendapatkan statistik pencocokan
 * 
 * @return array Statistik pencocokan
 */
function get_matching_statistics() {
    global $db;
    
    $stats = [
        'total_matches' => 0,
        'successful_matches' => 0,
        'success_rate' => 0,
        'average_score' => 0,
        'average_time' => 0
    ];
    
    // Total matches
    $query1 = "SELECT COUNT(*) as total FROM algorithm_logs";
    $result1 = $db->query($query1);
    $row1 = $result1->fetch_assoc();
    $stats['total_matches'] = $row1['total'];
    
    // Successful matches (yang menjadi permintaan dengan status in_progress atau completed)
    $query2 = "SELECT COUNT(*) as successful 
               FROM algorithm_logs l 
               JOIN assignments a ON l.assignment_id = a.id 
               WHERE a.status IN ('in_progress', 'completed')";
    $result2 = $db->query($query2);
    $row2 = $result2->fetch_assoc();
    $stats['successful_matches'] = $row2['successful'];
    
    // Success rate
    if ($stats['total_matches'] > 0) {
        $stats['success_rate'] = round(($stats['successful_matches'] / $stats['total_matches']) * 100, 2);
    }
    
    // Average query time
    $query3 = "SELECT AVG(query_time) as avg_time FROM algorithm_logs";
    $result3 = $db->query($query3);
    $row3 = $result3->fetch_assoc();
    $stats['average_time'] = round($row3['avg_time'], 2);
    
    return $stats;
}

/**
 * Mendapatkan log aktivitas algoritma
 * 
 * @param int $limit Batas jumlah log
 * @param int $offset Offset untuk paginasi
 * @return array Log aktivitas algoritma
 */
function get_algorithm_logs($limit = 20, $offset = 0) {
    global $db;
    
    $query = "SELECT l.*, a.title as assignment_title, u.email as user_email, 
              s.school_name
              FROM algorithm_logs l
              JOIN assignments a ON l.assignment_id = a.id
              JOIN users u ON l.user_id = u.id
              LEFT JOIN school_profiles s ON a.school_id = s.id
              ORDER BY l.created_at DESC
              LIMIT ?, ?";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param("ii", $offset, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $logs = [];
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    
    return $logs;
}

/**
 * Mendapatkan total log algoritma
 * 
 * @return int Total log
 */
function get_algorithm_logs_count() {
    global $db;
    
    $query = "SELECT COUNT(*) as total FROM algorithm_logs";
    $result = $db->query($query);
    $row = $result->fetch_assoc();
    
    return $row['total'];
}
?>