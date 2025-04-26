<?php
/**
 * Fungsi-fungsi untuk integrasi dengan API Claude Anthropic
 * untuk sistem pencocokan guru pengganti
 * /includes/claude-matching.php
 */

/**
 * Konfigurasi API Claude Anthropic
 */
define('CLAUDE_API_URL', 'https://api.anthropic.com/v1/messages');
define('CLAUDE_API_KEY', getenv('CLAUDE_API_KEY')); // Ambil dari environment variable
define('CLAUDE_MODEL', 'claude-3-haiku-20240307'); // Model yang digunakan

/**
 * Fungsi untuk mengirim permintaan ke API Claude
 * 
 * @param array $messages Array pesan dalam format yang diperlukan Claude API
 * @param array $metadata Metadata tambahan untuk analisis
 * @return array|false Respons dari Claude API atau false jika gagal
 */
function send_claude_request($messages, $metadata = []) {
    $headers = [
        'Content-Type: application/json',
        'anthropic-version: 2023-06-01',
        'x-api-key: ' . CLAUDE_API_KEY
    ];
    
    $data = [
        'model' => CLAUDE_MODEL,
        'messages' => $messages,
        'max_tokens' => 4000,
        'temperature' => 0.2, // Nilai rendah untuk hasil yang lebih deterministik
        'metadata' => $metadata
    ];
    
    $ch = curl_init(CLAUDE_API_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        return json_decode($response, true);
    } else {
        // Log error
        error_log('Error calling Claude API: ' . $response);
        return false;
    }
}

/**
 * Fungsi untuk mencocokkan guru dengan permintaan sekolah
 * 
 * @param array $assignment Data permintaan guru
 * @param array $teachers Daftar guru yang tersedia
 * @return array Daftar guru yang cocok dengan skor kecocokan
 */
function match_teachers_with_assignment($assignment, $teachers) {
    // Siapkan konteks untuk Claude
    $context = "Saya adalah algoritma pencocokan guru pengganti di platform pembelajaran. " .
               "Saya perlu mencocokkan guru dengan permintaan sekolah berdasarkan kualifikasi, " .
               "pengalaman, keahlian, dan ketersediaan. " .
               "Tolong analisis kesesuaian antara guru yang tersedia dan kebutuhan sekolah " .
               "dan berikan skor kesesuaian (1-100) beserta alasannya. " .
               "Format output menggunakan JSON dengan struktur: { 'matches': [{ 'teacher_id': 123, 'score': 85, 'reason': '...' }] }";
    
    // Buat deskripsi permintaan
    $assignmentDesc = "Permintaan Sekolah:\n" .
                     "- Judul: " . $assignment['title'] . "\n" .
                     "- Mata Pelajaran: " . $assignment['subject'] . "\n" .
                     "- Kelas: " . $assignment['grade'] . "\n" .
                     "- Lokasi: " . $assignment['location'] . "\n" .
                     "- Deskripsi: " . $assignment['description'] . "\n" .
                     "- Tanggal Mulai: " . $assignment['start_date'] . "\n" .
                     "- Tanggal Selesai: " . $assignment['end_date'] . "\n" .
                     "- Waktu Mengajar: " . $assignment['start_time'] . " - " . $assignment['end_time'] . "\n" .
                     "- Permanen: " . ($assignment['is_permanent'] ? 'Ya' : 'Tidak');
    
    // Siapkan deskripsi guru-guru yang tersedia
    $teachersDesc = "Guru yang Tersedia:\n";
    foreach ($teachers as $index => $teacher) {
        $teachersDesc .= ($index + 1) . ") ID: " . $teacher['id'] . "\n" .
                        "   - Nama: " . $teacher['full_name'] . "\n" .
                        "   - Pendidikan: " . $teacher['education'] . "\n" .
                        "   - Pengalaman: " . $teacher['experience'] . "\n" .
                        "   - Keahlian: " . $teacher['subject_expertise'] . "\n" .
                        "   - Rating: " . $teacher['rating'] . "\n\n";
    }
    
    // Siapkan pesan untuk Claude
    $messages = [
        ['role' => 'system', 'content' => $context],
        ['role' => 'user', 'content' => $assignmentDesc . "\n\n" . $teachersDesc . "\n\nTolong analisis kesesuaian dan berikan skor untuk setiap guru dalam format JSON yang telah ditentukan."]
    ];
    
    // Kirim permintaan ke Claude API
    $response = send_claude_request($messages, [
        'assignment_id' => $assignment['id'],
        'teacher_count' => count($teachers)
    ]);
    
    if (!$response || empty($response['content'][0]['text'])) {
        // Jika gagal, gunakan algoritma pencocokan sederhana
        return fallback_matching_algorithm($assignment, $teachers);
    }
    
    // Ekstrak respons JSON dari Claude
    $responseText = $response['content'][0]['text'];
    
    // Cari blok JSON dalam respons
    preg_match('/\{.*\}/s', $responseText, $matches);
    
    if (empty($matches)) {
        // Jika tidak menemukan format JSON, gunakan algoritma pencocokan sederhana
        return fallback_matching_algorithm($assignment, $teachers);
    }
    
    $matchesJson = $matches[0];
    $matchesData = json_decode($matchesJson, true);
    
    if (!$matchesData || !isset($matchesData['matches'])) {
        // Jika gagal parsing JSON, gunakan algoritma pencocokan sederhana
        return fallback_matching_algorithm($assignment, $teachers);
    }
    
    // Urutkan hasil berdasarkan skor tertinggi
    usort($matchesData['matches'], function($a, $b) {
        return $b['score'] - $a['score'];
    });
    
    return $matchesData['matches'];
}

/**
 * Algoritma pencocokan sederhana sebagai fallback jika Claude gagal
 * 
 * @param array $assignment Data permintaan guru
 * @param array $teachers Daftar guru yang tersedia
 * @return array Daftar guru yang cocok dengan skor kecocokan
 */
function fallback_matching_algorithm($assignment, $teachers) {
    $matches = [];
    
    foreach ($teachers as $teacher) {
        $score = 0;
        $reason = [];
        
        // Cek kecocokan mata pelajaran
        $subjectExpertise = explode(',', $teacher['subject_expertise']);
        $subjectMatch = false;
        
        foreach ($subjectExpertise as $expertise) {
            if (stripos(trim($expertise), $assignment['subject']) !== false) {
                $score += 40;
                $subjectMatch = true;
                $reason[] = "Mata pelajaran sesuai dengan keahlian";
                break;
            }
        }
        
        if (!$subjectMatch) {
            $score += 10; // Skor minimal untuk ketidakcocokan mata pelajaran
            $reason[] = "Mata pelajaran tidak sesuai dengan keahlian utama";
        }
        
        // Cek pendidikan
        if (stripos($teacher['education'], 'S2') !== false || stripos($teacher['education'], 'Master') !== false) {
            $score += 15;
            $reason[] = "Memiliki kualifikasi pendidikan tinggi (S2/Master)";
        } elseif (stripos($teacher['education'], 'S1') !== false || stripos($teacher['education'], 'Sarjana') !== false) {
            $score += 10;
            $reason[] = "Memiliki kualifikasi pendidikan S1/Sarjana";
        }
        
        // Cek pengalaman
        if (!empty($teacher['experience'])) {
            $experienceWords = str_word_count($teacher['experience']);
            if ($experienceWords > 100) {
                $score += 20;
                $reason[] = "Memiliki pengalaman mengajar yang signifikan";
            } elseif ($experienceWords > 50) {
                $score += 15;
                $reason[] = "Memiliki pengalaman mengajar yang cukup";
            } else {
                $score += 10;
                $reason[] = "Memiliki beberapa pengalaman mengajar";
            }
        }
        
        // Cek rating
        if ($teacher['rating'] >= 4.5) {
            $score += 25;
            $reason[] = "Rating sangat tinggi (≥4.5/5)";
        } elseif ($teacher['rating'] >= 4.0) {
            $score += 20;
            $reason[] = "Rating tinggi (≥4.0/5)";
        } elseif ($teacher['rating'] >= 3.5) {
            $score += 15;
            $reason[] = "Rating cukup baik (≥3.5/5)";
        } elseif ($teacher['rating'] > 0) {
            $score += 10;
            $reason[] = "Memiliki rating";
        }
        
        // Batasi skor maksimum
        $score = min($score, 100);
        
        $matches[] = [
            'teacher_id' => $teacher['id'],
            'score' => $score,
            'reason' => implode(", ", $reason)
        ];
    }
    
    // Urutkan berdasarkan skor tertinggi
    usort($matches, function($a, $b) {
        return $b['score'] - $a['score'];
    });
    
    return $matches;
}

/**
 * Mendapatkan guru-guru yang tersedia berdasarkan kriteria permintaan
 * 
 * @param array $assignment Data permintaan guru
 * @param int $limit Batas jumlah guru yang dikembalikan
 * @return array Daftar guru yang cocok
 */
function get_matching_teachers($assignment, $limit = 10) {
    global $db;
    
    // Siapkan query dasar
    $query = "SELECT tp.* FROM teacher_profiles tp 
              JOIN users u ON tp.user_id = u.id 
              WHERE u.role = 'teacher' 
              AND tp.verification_status = 'verified'
              AND tp.available_status = 'available'";
    
    // Tambahkan filter berdasarkan mata pelajaran (jika ada)
    if (!empty($assignment['subject']) && $assignment['subject'] !== 'Lainnya') {
        $query .= " AND (tp.subject_expertise LIKE ? OR tp.subject_expertise LIKE ?)";
        $subject = '%' . $assignment['subject'] . '%';
        $subjectEnd = $assignment['subject'] . ',%';
        
        $stmt = $db->prepare($query);
        $stmt->bind_param("ss", $subject, $subjectEnd);
    } else {
        $stmt = $db->prepare($query);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $teachers = [];
    while ($row = $result->fetch_assoc()) {
        $teachers[] = $row;
    }
    
    // Jika tidak ada guru yang cocok dengan filter mata pelajaran, ambil semua guru
    if (count($teachers) === 0 && !empty($assignment['subject']) && $assignment['subject'] !== 'Lainnya') {
        $query = "SELECT tp.* FROM teacher_profiles tp 
                  JOIN users u ON tp.user_id = u.id 
                  WHERE u.role = 'teacher' 
                  AND tp.verification_status = 'verified'
                  AND tp.available_status = 'available'";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $teachers[] = $row;
        }
    }
    
    // Jika masih tidak ada guru yang tersedia, return array kosong
    if (count($teachers) === 0) {
        return [];
    }
    
    // Gunakan Claude untuk mencocokkan guru
    $matches = match_teachers_with_assignment($assignment, $teachers);
    
    // Ambil data guru yang cocok
    $matchedTeachers = [];
    $count = 0;
    
    foreach ($matches as $match) {
        if ($count >= $limit) {
            break;
        }
        
        foreach ($teachers as $teacher) {
            if ($teacher['id'] == $match['teacher_id']) {
                $teacher['match_score'] = $match['score'];
                $teacher['match_reason'] = $match['reason'];
                $matchedTeachers[] = $teacher;
                $count++;
                break;
            }
        }
    }
    
    return $matchedTeachers;
}

/**
 * Mendapatkan rekomendasi guru pengganti
 * 
 * @param int $assignment_id ID permintaan guru
 * @param int $limit Batas jumlah guru yang direkomendasikan
 * @return array|false Daftar guru yang direkomendasikan atau false jika gagal
 */
function get_teacher_recommendations($assignment_id, $limit = 5) {
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
    $matchedTeachers = get_matching_teachers($assignment, $limit);
    
    return $matchedTeachers;
}

/**
 * Mendapatkan prediksi kecocokan antara guru dan permintaan
 * 
 * @param int $teacher_id ID profil guru
 * @param int $assignment_id ID permintaan
 * @return array Data prediksi kecocokan (skor dan alasan)
 */
function get_teacher_assignment_match_prediction($teacher_id, $assignment_id) {
    global $db;
    
    // Ambil detail guru
    $query1 = "SELECT * FROM teacher_profiles WHERE id = ?";
    $stmt1 = $db->prepare($query1);
    $stmt1->bind_param("i", $teacher_id);
    $stmt1->execute();
    $teacherResult = $stmt1->get_result();
    
    if ($teacherResult->num_rows === 0) {
        return ['score' => 0, 'reason' => 'Guru tidak ditemukan'];
    }
    
    $teacher = $teacherResult->fetch_assoc();
    
    // Ambil detail permintaan
    $query2 = "SELECT * FROM assignments WHERE id = ?";
    $stmt2 = $db->prepare($query2);
    $stmt2->bind_param("i", $assignment_id);
    $stmt2->execute();
    $assignmentResult = $stmt2->get_result();
    
    if ($assignmentResult->num_rows === 0) {
        return ['score' => 0, 'reason' => 'Permintaan tidak ditemukan'];
    }
    
    $assignment = $assignmentResult->fetch_assoc();
    
    // Gunakan Claude untuk prediksi
    $matches = match_teachers_with_assignment($assignment, [$teacher]);
    
    if (empty($matches)) {
        // Gunakan algoritma fallback jika Claude gagal
        $fallbackMatches = fallback_matching_algorithm($assignment, [$teacher]);
        return [
            'score' => $fallbackMatches[0]['score'],
            'reason' => $fallbackMatches[0]['reason']
        ];
    }
    
    return [
        'score' => $matches[0]['score'],
        'reason' => $matches[0]['reason']
    ];
}

/**
 * Mendapatkan ringkasan kualifikasi guru untuk permintaan tertentu
 * 
 * @param int $teacher_id ID profil guru
 * @param int $assignment_id ID permintaan
 * @return string Ringkasan kualifikasi
 */
function get_teacher_qualification_summary($teacher_id, $assignment_id) {
    global $db;
    
    // Ambil detail guru
    $query1 = "SELECT tp.*, u.email FROM teacher_profiles tp JOIN users u ON tp.user_id = u.id WHERE tp.id = ?";
    $stmt1 = $db->prepare($query1);
    $stmt1->bind_param("i", $teacher_id);
    $stmt1->execute();
    $teacherResult = $stmt1->get_result();
    
    if ($teacherResult->num_rows === 0) {
        return "Data guru tidak ditemukan.";
    }
    
    $teacher = $teacherResult->fetch_assoc();
    
    // Ambil detail permintaan
    $query2 = "SELECT * FROM assignments WHERE id = ?";
    $stmt2 = $db->prepare($query2);
    $stmt2->bind_param("i", $assignment_id);
    $stmt2->execute();
    $assignmentResult = $stmt2->get_result();
    
    if ($assignmentResult->num_rows === 0) {
        return "Data permintaan tidak ditemukan.";
    }
    
    $assignment = $assignmentResult->fetch_assoc();
    
    // Siapkan konteks untuk Claude
    $context = "Saya adalah asisten untuk platform guru pengganti. " .
               "Tolong buatkan ringkasan singkat kualifikasi guru untuk permintaan tertentu " .
               "yang menyoroti kekuatan dan kesesuaian guru dengan kebutuhan. " .
               "Ringkasan harus informatif, positif, dan tidak lebih dari 200 kata.";
    
    // Siapkan data guru
    $teacherData = "Data Guru:\n" .
                   "- Nama: " . $teacher['full_name'] . "\n" .
                   "- Pendidikan: " . $teacher['education'] . "\n" .
                   "- Pengalaman: " . $teacher['experience'] . "\n" .
                   "- Keahlian Mata Pelajaran: " . $teacher['subject_expertise'] . "\n" .
                   "- Rating: " . $teacher['rating'] . "\n" .
                   "- Bio: " . $teacher['bio'];
    
    // Siapkan data permintaan
    $assignmentData = "Data Permintaan:\n" .
                      "- Judul: " . $assignment['title'] . "\n" .
                      "- Mata Pelajaran: " . $assignment['subject'] . "\n" .
                      "- Kelas: " . $assignment['grade'] . "\n" .
                      "- Deskripsi: " . $assignment['description'];
    
    // Siapkan pesan untuk Claude
    $messages = [
        ['role' => 'system', 'content' => $context],
        ['role' => 'user', 'content' => $teacherData . "\n\n" . $assignmentData . "\n\nTolong buatkan ringkasan kualifikasi guru untuk permintaan ini."]
    ];
    
    // Kirim permintaan ke Claude API
    $response = send_claude_request($messages, [
        'teacher_id' => $teacher_id,
        'assignment_id' => $assignment_id
    ]);
    
    if (!$response || empty($response['content'][0]['text'])) {
        // Jika gagal, buat ringkasan sederhana
        return create_simple_qualification_summary($teacher, $assignment);
    }
    
    return $response['content'][0]['text'];
}

/**
 * Membuat ringkasan kualifikasi sederhana (fallback)
 * 
 * @param array $teacher Data guru
 * @param array $assignment Data permintaan
 * @return string Ringkasan kualifikasi sederhana
 */
function create_simple_qualification_summary($teacher, $assignment) {
    $summary = $teacher['full_name'] . " memiliki latar belakang pendidikan " . $teacher['education'] . ". ";
    
    // Cek kesesuaian mata pelajaran
    $subjectExpertise = explode(',', $teacher['subject_expertise']);
    $subjectMatch = false;
    
    foreach ($subjectExpertise as $expertise) {
        if (stripos(trim($expertise), $assignment['subject']) !== false) {
            $summary .= "Guru ini memiliki keahlian khusus dalam mata pelajaran " . $assignment['subject'] . ". ";
            $subjectMatch = true;
            break;
        }
    }
    
    if (!$subjectMatch) {
        $summary .= "Meskipun spesialisasi utamanya bukan di " . $assignment['subject'] . ", guru ini memiliki keahlian dalam " . $teacher['subject_expertise'] . ". ";
    }
    
    // Tambahkan informasi pengalaman
    if (!empty($teacher['experience'])) {
        $summary .= "Guru ini memiliki pengalaman mengajar sebelumnya. ";
    }
    
    // Tambahkan informasi rating
    if ($teacher['rating'] >= 4.5) {
        $summary .= "Guru ini memiliki rating sangat baik dari pengguna lain. ";
    } elseif ($teacher['rating'] >= 4.0) {
        $summary .= "Guru ini mendapatkan penilaian baik dari pengguna lain. ";
    } elseif ($teacher['rating'] >= 3.5) {
        $summary .= "Guru ini memiliki rating cukup baik. ";
    } elseif ($teacher['rating'] > 0) {
        $summary .= "Guru ini telah menerima beberapa penilaian dari pengguna lain. ";
    }
    
    $summary .= "Kelas yang diminta (" . $assignment['grade'] . ") cocok dengan kualifikasi guru.";
    
    return $summary;
}
?>