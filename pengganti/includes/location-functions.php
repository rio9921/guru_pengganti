<?php
/**
 * Fungsi-fungsi untuk fitur lokasi
 * /includes/location-functions.php
 */

/**
 * Menghitung jarak antara dua titik koordinat (haversine formula)
 * 
 * @param float $lat1 Latitude titik pertama
 * @param float $lng1 Longitude titik pertama
 * @param float $lat2 Latitude titik kedua
 * @param float $lng2 Longitude titik kedua
 * @return float Jarak dalam kilometer
 */
function calculate_distance($lat1, $lng1, $lat2, $lng2) {
    // Konversi derajat ke radian
    $lat1 = deg2rad($lat1);
    $lng1 = deg2rad($lng1);
    $lat2 = deg2rad($lat2);
    $lng2 = deg2rad($lng2);
    
    // Haversine formula
    $dlat = $lat2 - $lat1;
    $dlng = $lng2 - $lng1;
    $a = sin($dlat/2) * sin($dlat/2) + cos($lat1) * cos($lat2) * sin($dlng/2) * sin($dlng/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    
    // Radius bumi dalam kilometer
    $r = 6371;
    
    // Jarak dalam kilometer
    return $r * $c;
}

/**
 * Mendapatkan riwayat kehadiran guru
 * 
 * @param int $teacher_id ID profil guru
 * @param string|null $start_date Tanggal mulai (format: YYYY-MM-DD)
 * @param string|null $end_date Tanggal selesai (format: YYYY-MM-DD)
 * @param int|null $assignment_id ID penugasan (opsional)
 * @return array Daftar kehadiran
 */
function get_teacher_attendance_history($teacher_id, $start_date = null, $end_date = null, $assignment_id = null) {
    global $db;
    
    $query = "SELECT a.*, 
              asn.title as assignment_title, 
              s.school_name,
              s.location_lat as school_lat,
              s.location_lng as school_lng
              FROM attendance a
              JOIN assignments asn ON a.assignment_id = asn.id
              JOIN school_profiles s ON asn.school_id = s.id
              WHERE a.teacher_id = ?";
    
    $params = [$teacher_id];
    $types = "i";
    
    if ($start_date) {
        $query .= " AND a.date >= ?";
        $params[] = $start_date;
        $types .= "s";
    }
    
    if ($end_date) {
        $query .= " AND a.date <= ?";
        $params[] = $end_date;
        $types .= "s";
    }
    
    if ($assignment_id) {
        $query .= " AND a.assignment_id = ?";
        $params[] = $assignment_id;
        $types .= "i";
    }
    
    $query .= " ORDER BY a.date DESC, a.check_in_time DESC";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $attendance = [];
    while ($row = $result->fetch_assoc()) {
        // Calculate check-in distance if coordinates are available
        if ($row['check_in_location_lat'] && $row['check_in_location_lng'] && $row['school_lat'] && $row['school_lng']) {
            $row['check_in_distance'] = calculate_distance(
                $row['check_in_location_lat'],
                $row['check_in_location_lng'],
                $row['school_lat'],
                $row['school_lng']
            );
        } else {
            $row['check_in_distance'] = null;
        }
        
        // Calculate check-out distance if coordinates are available
        if ($row['check_out_location_lat'] && $row['check_out_location_lng'] && $row['school_lat'] && $row['school_lng']) {
            $row['check_out_distance'] = calculate_distance(
                $row['check_out_location_lat'],
                $row['check_out_location_lng'],
                $row['school_lat'],
                $row['school_lng']
            );
        } else {
            $row['check_out_distance'] = null;
        }
        
        $attendance[] = $row;
    }
    
    return $attendance;
}

/**
 * Mendapatkan riwayat kehadiran untuk penugasan tertentu
 * 
 * @param int $assignment_id ID penugasan
 * @return array Daftar kehadiran
 */
function get_assignment_attendance($assignment_id) {
    global $db;
    
    $query = "SELECT a.*, 
              tp.full_name as teacher_name,
              tp.profile_picture
              FROM attendance a
              JOIN teacher_profiles tp ON a.teacher_id = tp.id
              WHERE a.assignment_id = ?
              ORDER BY a.date DESC, a.check_in_time DESC";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $assignment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $attendance = [];
    while ($row = $result->fetch_assoc()) {
        $attendance[] = $row;
    }
    
    return $attendance;
}

/**
 * Mendapatkan detail kehadiran
 * 
 * @param int $attendance_id ID kehadiran
 * @return array|false Detail kehadiran atau false jika tidak ditemukan
 */
function get_attendance_detail($attendance_id) {
    global $db;
    
    $query = "SELECT a.*, 
              asn.title as assignment_title, 
              s.school_name, s.address as school_address,
              s.location_lat as school_lat, s.location_lng as school_lng,
              tp.full_name as teacher_name, tp.profile_picture
              FROM attendance a
              JOIN assignments asn ON a.assignment_id = asn.id
              JOIN school_profiles s ON asn.school_id = s.id
              JOIN teacher_profiles tp ON a.teacher_id = tp.id
              WHERE a.id = ?";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $attendance_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }
    
    $attendance = $result->fetch_assoc();
    
    // Calculate check-in distance if coordinates are available
    if ($attendance['check_in_location_lat'] && $attendance['check_in_location_lng'] && $attendance['school_lat'] && $attendance['school_lng']) {
        $attendance['check_in_distance'] = calculate_distance(
            $attendance['check_in_location_lat'],
            $attendance['check_in_location_lng'],
            $attendance['school_lat'],
            $attendance['school_lng']
        );
    } else {
        $attendance['check_in_distance'] = null;
    }
    
    // Calculate check-out distance if coordinates are available
    if ($attendance['check_out_location_lat'] && $attendance['check_out_location_lng'] && $attendance['school_lat'] && $attendance['school_lng']) {
        $attendance['check_out_distance'] = calculate_distance(
            $attendance['check_out_location_lat'],
            $attendance['check_out_location_lng'],
            $attendance['school_lat'],
            $attendance['school_lng']
        );
    } else {
        $attendance['check_out_distance'] = null;
    }
    
    return $attendance;
}

/**
 * Memperbarui catatan kehadiran
 * 
 * @param int $attendance_id ID kehadiran
 * @param array $data Data yang akan diperbarui
 * @return bool True jika berhasil, false jika gagal
 */
function update_attendance($attendance_id, $data) {
    global $db;
    
    $valid_fields = [
        'check_in_time', 'check_out_time', 
        'check_in_location_lat', 'check_in_location_lng', 
        'check_out_location_lat', 'check_out_location_lng', 
        'status', 'notes'
    ];
    
    $update_fields = [];
    $params = [];
    $types = "";
    
    foreach ($data as $field => $value) {
        if (in_array($field, $valid_fields)) {
            $update_fields[] = "$field = ?";
            $params[] = $value;
            
            if ($field == 'status' || $field == 'notes' || $field == 'check_in_time' || $field == 'check_out_time') {
                $types .= "s";
            } else {
                $types .= "d"; // latitude/longitude are double
            }
        }
    }
    
    if (empty($update_fields)) {
        return false; // No valid fields to update
    }
    
    $query = "UPDATE attendance SET " . implode(", ", $update_fields) . " WHERE id = ?";
    $params[] = $attendance_id;
    $types .= "i";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param($types, ...$params);
    
    return $stmt->execute();
}

/**
 * Mendapatkan statistik kehadiran guru
 * 
 * @param int $teacher_id ID profil guru
 * @param string|null $start_date Tanggal mulai (format: YYYY-MM-DD)
 * @param string|null $end_date Tanggal selesai (format: YYYY-MM-DD)
 * @return array Statistik kehadiran
 */
function get_teacher_attendance_stats($teacher_id, $start_date = null, $end_date = null) {
    global $db;
    
    $stats = [
        'total' => 0,
        'present' => 0,
        'late' => 0,
        'absent' => 0,
        'present_percentage' => 0,
        'late_percentage' => 0,
        'absent_percentage' => 0
    ];
    
    $query = "SELECT 
              COUNT(*) as total,
              SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
              SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late,
              SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent
              FROM attendance
              WHERE teacher_id = ?";
    
    $params = [$teacher_id];
    $types = "i";
    
    if ($start_date) {
        $query .= " AND date >= ?";
        $params[] = $start_date;
        $types .= "s";
    }
    
    if ($end_date) {
        $query .= " AND date <= ?";
        $params[] = $end_date;
        $types .= "s";
    }
    
    $stmt = $db->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $stats['total'] = (int)$row['total'];
    $stats['present'] = (int)$row['present'];
    $stats['late'] = (int)$row['late'];
    $stats['absent'] = (int)$row['absent'];
    
    if ($stats['total'] > 0) {
        $stats['present_percentage'] = round(($stats['present'] / $stats['total']) * 100, 2);
        $stats['late_percentage'] = round(($stats['late'] / $stats['total']) * 100, 2);
        $stats['absent_percentage'] = round(($stats['absent'] / $stats['total']) * 100, 2);
    }
    
    // Get expected work days (days the teacher should have attended)
    $expected_query = "SELECT COUNT(DISTINCT asn.id) as assignment_count, 
                      MIN(asn.start_date) as earliest_date,
                      MAX(asn.end_date) as latest_date
                      FROM assignments asn
                      JOIN applications app ON asn.id = app.assignment_id
                      WHERE app.teacher_id = ? AND app.status = 'accepted'";
    
    $stmt = $db->prepare($expected_query);
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $expected_result = $stmt->get_result();
    $expected_row = $expected_result->fetch_assoc();
    
    $stats['assignment_count'] = (int)$expected_row['assignment_count'];
    $stats['earliest_date'] = $expected_row['earliest_date'];
    $stats['latest_date'] = $expected_row['latest_date'];
    
    // Calculate expected work days if dates are available
    if ($stats['earliest_date'] && $stats['latest_date']) {
        // Use custom start/end dates if provided
        $start = $start_date ? max($start_date, $stats['earliest_date']) : $stats['earliest_date'];
        $end = $end_date ? min($end_date, $stats['latest_date']) : $stats['latest_date'];
        
        // Calculate work days (excluding weekends)
        $stats['expected_days'] = calculate_work_days($start, $end);
        
        // Calculate attendance rate
        if ($stats['expected_days'] > 0) {
            $stats['attendance_rate'] = round((($stats['present'] + $stats['late']) / $stats['expected_days']) * 100, 2);
        } else {
            $stats['attendance_rate'] = 0;
        }
    } else {
        $stats['expected_days'] = 0;
        $stats['attendance_rate'] = 0;
    }
    
    return $stats;
}

/**
 * Menghitung jumlah hari kerja (Senin-Jumat) antara dua tanggal
 * 
 * @param string $start_date Tanggal mulai (format: YYYY-MM-DD)
 * @param string $end_date Tanggal selesai (format: YYYY-MM-DD)
 * @return int Jumlah hari kerja
 */
function calculate_work_days($start_date, $end_date) {
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $end->modify('+1 day'); // Include end date
    
    $interval = new DateInterval('P1D'); // 1 day interval
    $period = new DatePeriod($start, $interval, $end);
    
    $work_days = 0;
    foreach ($period as $day) {
        $weekday = $day->format('N'); // 1 (Monday) to 7 (Sunday)
        if ($weekday <= 5) { // 1-5 = Monday to Friday
            $work_days++;
        }
    }
    
    return $work_days;
}

/**
 * Mendapatkan lokasi sekolah
 * 
 * @param int $school_id ID profil sekolah
 * @return array|false Data lokasi atau false jika tidak ditemukan
 */
function get_school_location($school_id) {
    global $db;
    
    $query = "SELECT location_lat, location_lng, address FROM school_profiles WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $school_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }
    
    return $result->fetch_assoc();
}

/**
 * Memperbarui lokasi sekolah
 * 
 * @param int $school_id ID profil sekolah
 * @param float $lat Latitude
 * @param float $lng Longitude
 * @return bool True jika berhasil, false jika gagal
 */
function update_school_location($school_id, $lat, $lng) {
    global $db;
    
    $query = "UPDATE school_profiles SET location_lat = ?, location_lng = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("ddi", $lat, $lng, $school_id);
    
    return $stmt->execute();
}

/**
 * Memperbarui lokasi guru
 * 
 * @param int $teacher_id ID profil guru
 * @param float $lat Latitude
 * @param float $lng Longitude
 * @return bool True jika berhasil, false jika gagal
 */
function update_teacher_location($teacher_id, $lat, $lng) {
    global $db;
    
    $query = "UPDATE teacher_profiles SET location_lat = ?, location_lng = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("ddi", $lat, $lng, $teacher_id);
    
    return $stmt->execute();
}

/**
 * Mendapatkan guru terdekat berdasarkan lokasi
 * 
 * @param float $lat Latitude
 * @param float $lng Longitude
 * @param float $max_distance Jarak maksimum dalam kilometer
 * @param string|null $subject Filter berdasarkan mata pelajaran (opsional)
 * @param int $limit Jumlah maksimal hasil
 * @return array Daftar guru terdekat
 */
function get_nearby_teachers($lat, $lng, $max_distance = 10, $subject = null, $limit = 10) {
    global $db;
    
    // Hitung batas koordinat untuk mengoptimalkan query
    $earth_radius = 6371; // Radius bumi dalam kilometer
    $lat_min = $lat - rad2deg($max_distance / $earth_radius);
    $lat_max = $lat + rad2deg($max_distance / $earth_radius);
    $lng_min = $lng - rad2deg($max_distance / $earth_radius / cos(deg2rad($lat)));
    $lng_max = $lng + rad2deg($max_distance / $earth_radius / cos(deg2rad($lat)));
    
    $query = "SELECT tp.*, u.email,
              (6371 * acos(cos(radians(?)) * cos(radians(tp.location_lat)) * cos(radians(tp.location_lng) - radians(?)) + sin(radians(?)) * sin(radians(tp.location_lat)))) AS distance
              FROM teacher_profiles tp
              JOIN users u ON tp.user_id = u.id
              WHERE tp.location_lat BETWEEN ? AND ?
              AND tp.location_lng BETWEEN ? AND ?
              AND tp.verification_status = 'verified'
              AND tp.available_status = 'available'";
    
    if ($subject) {
        $query .= " AND tp.subject_expertise LIKE ?";
    }
    
    $query .= " HAVING distance <= ?
                ORDER BY distance
                LIMIT ?";
    
    if ($subject) {
        $subject_param = '%' . $subject . '%';
        $stmt = $db->prepare($query);
        $stmt->bind_param("ddddddsdi", $lat, $lng, $lat, $lat_min, $lat_max, $lng_min, $lng_max, $subject_param, $max_distance, $limit);
    } else {
        $stmt = $db->prepare($query);
        $stmt->bind_param("dddddddi", $lat, $lng, $lat, $lat_min, $lat_max, $lng_min, $lng_max, $max_distance, $limit);
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
 * Memverifikasi apakah guru benar-benar berada di lokasi sekolah
 * 
 * @param float $teacher_lat Latitude guru
 * @param float $teacher_lng Longitude guru
 * @param float $school_lat Latitude sekolah
 * @param float $school_lng Longitude sekolah
 * @param float $max_distance Jarak maksimum yang diizinkan dalam kilometer
 * @return bool True jika lokasi valid, false jika tidak
 */
function verify_teacher_location($teacher_lat, $teacher_lng, $school_lat, $school_lng, $max_distance = 1) {
    $distance = calculate_distance($teacher_lat, $teacher_lng, $school_lat, $school_lng);
    
    return ($distance <= $max_distance);
}
?>