<?php
/**
 * /api/matching.php
 * API endpoint untuk sistem pencocokan guru dengan permintaan sekolah
 */

// Header untuk JSON response
header('Content-Type: application/json');

// Mulai sesi
session_start();

// Cek apakah pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized access'
    ]);
    exit;
}

// Include file konfigurasi dan koneksi database
require_once '../config/config.php';
require_once '../config/database.php';

// Include file fungsi
require_once '../includes/functions.php';
require_once '../includes/matching-functions.php';
require_once '../includes/claude-matching.php';
require_once '../includes/school-functions.php';

// Fungsi untuk validasi dan sanitasi input
function validate_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Default response
$response = [
    'status' => 'error',
    'message' => 'Invalid request',
    'data' => null
];

// Dapatkan action dari parameter
$action = isset($_GET['action']) ? validate_input($_GET['action']) : '';

// Mulai pengukur waktu untuk performa
$start_time = microtime(true);

// Handle berbagai actions
switch ($action) {
    case 'get_matching_teachers':
        // Mendapatkan guru yang cocok untuk permintaan tertentu
        if (isset($_GET['assignment_id']) && is_numeric($_GET['assignment_id'])) {
            $assignment_id = (int)$_GET['assignment_id'];
            
            // Cek apakah permintaan ada
            $query = "SELECT * FROM assignments WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("i", $assignment_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $response['message'] = 'Assignment not found';
                break;
            }
            
            $assignment = $result->fetch_assoc();
            
            // Cek apakah pengguna memiliki akses ke permintaan ini
            $has_access = false;
            
            if ($_SESSION['role'] === 'admin') {
                $has_access = true;
            } elseif ($_SESSION['role'] === 'school') {
                // Ambil profil sekolah
                $query = "SELECT * FROM school_profiles WHERE user_id = ?";
                $stmt = $db->prepare($query);
                $stmt->bind_param("i", $_SESSION['user_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $school_profile = $result->fetch_assoc();
                    if ($assignment['school_id'] === $school_profile['id']) {
                        $has_access = true;
                    }
                }
            }
            
            if (!$has_access) {
                http_response_code(403);
                $response['message'] = 'You do not have access to this assignment';
                break;
            }
            
            // Dapatkan limit jika ada
            $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int)$_GET['limit'] : 10;
            
            // Ambil guru yang cocok
            $matching_teachers = get_matching_teachers($assignment, $limit);
            
            // Pengukur waktu selesai
            $execution_time = microtime(true) - $start_time;
            
            // Log penggunaan algoritma
            log_matching_algorithm($assignment_id, count($matching_teachers), $execution_time, 'api');
            
            // Sanitasi data sebelum dikirim ke client
            $sanitized_teachers = [];
            
            foreach ($matching_teachers as $teacher) {
                $sanitized_teacher = [
                    'id' => $teacher['id'],
                    'full_name' => $teacher['full_name'],
                    'education' => $teacher['education'],
                    'subject_expertise' => $teacher['subject_expertise'],
                    'rating' => $teacher['rating'],
                    'match_score' => $teacher['match_score'],
                    'match_reason' => $teacher['match_reason'],
                    'profile_picture' => $teacher['profile_picture']
                ];
                
                $sanitized_teachers[] = $sanitized_teacher;
            }
            
            $response = [
                'status' => 'success',
                'message' => 'Teachers retrieved successfully',
                'data' => [
                    'teachers' => $sanitized_teachers,
                    'count' => count($sanitized_teachers),
                    'execution_time' => $execution_time
                ]
            ];
        } else {
            $response['message'] = 'Missing or invalid assignment_id parameter';
        }
        break;
        
    case 'get_teacher_match_prediction':
        // Mendapatkan prediksi kecocokan antara guru dan permintaan
        if (isset($_GET['teacher_id']) && is_numeric($_GET['teacher_id']) && isset($_GET['assignment_id']) && is_numeric($_GET['assignment_id'])) {
            $teacher_id = (int)$_GET['teacher_id'];
            $assignment_id = (int)$_GET['assignment_id'];
            
            // Cek apakah guru dan permintaan ada
            $query1 = "SELECT * FROM teacher_profiles WHERE id = ?";
            $stmt1 = $db->prepare($query1);
            $stmt1->bind_param("i", $teacher_id);
            $stmt1->execute();
            $result1 = $stmt1->get_result();
            
            $query2 = "SELECT * FROM assignments WHERE id = ?";
            $stmt2 = $db->prepare($query2);
            $stmt2->bind_param("i", $assignment_id);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            
            if ($result1->num_rows === 0 || $result2->num_rows === 0) {
                $response['message'] = 'Teacher or assignment not found';
                break;
            }
            
            // Dapatkan prediksi kecocokan
            $prediction = get_teacher_assignment_match_prediction($teacher_id, $assignment_id);
            
            // Pengukur waktu selesai
            $execution_time = microtime(true) - $start_time;
            
            $response = [
                'status' => 'success',
                'message' => 'Match prediction retrieved successfully',
                'data' => [
                    'score' => $prediction['score'],
                    'reason' => $prediction['reason'],
                    'execution_time' => $execution_time
                ]
            ];
        } else {
            $response['message'] = 'Missing or invalid teacher_id or assignment_id parameter';
        }
        break;
        
    case 'get_qualification_summary':
        // Mendapatkan ringkasan kualifikasi guru untuk permintaan tertentu
        if (isset($_GET['teacher_id']) && is_numeric($_GET['teacher_id']) && isset($_GET['assignment_id']) && is_numeric($_GET['assignment_id'])) {
            $teacher_id = (int)$_GET['teacher_id'];
            $assignment_id = (int)$_GET['assignment_id'];
            
            // Cek apakah guru dan permintaan ada
            $query1 = "SELECT * FROM teacher_profiles WHERE id = ?";
            $stmt1 = $db->prepare($query1);
            $stmt1->bind_param("i", $teacher_id);
            $stmt1->execute();
            $result1 = $stmt1->get_result();
            
            $query2 = "SELECT * FROM assignments WHERE id = ?";
            $stmt2 = $db->prepare($query2);
            $stmt2->bind_param("i", $assignment_id);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            
            if ($result1->num_rows === 0 || $result2->num_rows === 0) {
                $response['message'] = 'Teacher or assignment not found';
                break;
            }
            
            // Dapatkan ringkasan kualifikasi
            $summary = get_teacher_qualification_summary($teacher_id, $assignment_id);
            
            // Pengukur waktu selesai
            $execution_time = microtime(true) - $start_time;
            
            $response = [
                'status' => 'success',
                'message' => 'Qualification summary retrieved successfully',
                'data' => [
                    'summary' => $summary,
                    'execution_time' => $execution_time
                ]
            ];
        } else {
            $response['message'] = 'Missing or invalid teacher_id or assignment_id parameter';
        }
        break;
        
    case 'get_recommended_assignments':
        // Mendapatkan rekomendasi permintaan untuk guru
        if ($_SESSION['role'] === 'teacher' && isset($_GET['teacher_id']) && is_numeric($_GET['teacher_id'])) {
            $teacher_id = (int)$_GET['teacher_id'];
            
            // Cek apakah ini adalah guru yang login
            $query = "SELECT * FROM teacher_profiles WHERE id = ? AND user_id = ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("ii", $teacher_id, $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                http_response_code(403);
                $response['message'] = 'You do not have access to this teacher profile';
                break;
            }
            
            // Dapatkan limit jika ada
            $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int)$_GET['limit'] : 5;
            
            // Ambil rekomendasi permintaan
            $recommended_assignments = get_recommended_assignments_for_teacher($teacher_id, $limit);
            
            // Sanitasi data sebelum dikirim ke client
            $sanitized_assignments = [];
            
            foreach ($recommended_assignments as $assignment) {
                $sanitized_assignment = [
                    'id' => $assignment['id'],
                    'title' => $assignment['title'],
                    'subject' => $assignment['subject'],
                    'grade' => $assignment['grade'],
                    'start_date' => $assignment['start_date'],
                    'end_date' => $assignment['end_date'],
                    'budget' => $assignment['budget'],
                    'school_name' => $assignment['school_name'],
                    'application_count' => $assignment['application_count'],
                    'created_at' => $assignment['created_at']
                ];
                
                $sanitized_assignments[] = $sanitized_assignment;
            }
            
            // Pengukur waktu selesai
            $execution_time = microtime(true) - $start_time;
            
            $response = [
                'status' => 'success',
                'message' => 'Recommended assignments retrieved successfully',
                'data' => [
                    'assignments' => $sanitized_assignments,
                    'count' => count($sanitized_assignments),
                    'execution_time' => $execution_time
                ]
            ];
        } else {
            $response['message'] = 'Only teachers can access this endpoint or invalid teacher_id parameter';
        }
        break;
        
    case 'get_nearby_teachers':
        // Mendapatkan guru terdekat berdasarkan lokasi
        if (isset($_GET['lat']) && isset($_GET['lng'])) {
            $lat = (float)$_GET['lat'];
            $lng = (float)$_GET['lng'];
            
            if ($lat == 0 && $lng == 0) {
                $response['message'] = 'Invalid coordinates';
                break;
            }
            
            // Dapatkan parameter opsional
            $max_distance = isset($_GET['max_distance']) && is_numeric($_GET['max_distance']) ? (float)$_GET['max_distance'] : 10;
            $subject = isset($_GET['subject']) ? validate_input($_GET['subject']) : null;
            $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int)$_GET['limit'] : 10;
            
            // Dapatkan guru terdekat
            $nearby_teachers = get_nearby_teachers($lat, $lng, $max_distance, $subject, $limit);
            
            // Sanitasi data sebelum dikirim ke client
            $sanitized_teachers = [];
            
            foreach ($nearby_teachers as $teacher) {
                $sanitized_teacher = [
                    'id' => $teacher['id'],
                    'full_name' => $teacher['full_name'],
                    'education' => $teacher['education'],
                    'subject_expertise' => $teacher['subject_expertise'],
                    'rating' => $teacher['rating'],
                    'distance' => round($teacher['distance'], 2),
                    'profile_picture' => $teacher['profile_picture']
                ];
                
                $sanitized_teachers[] = $sanitized_teacher;
            }
            
            // Pengukur waktu selesai
            $execution_time = microtime(true) - $start_time;
            
            $response = [
                'status' => 'success',
                'message' => 'Nearby teachers retrieved successfully',
                'data' => [
                    'teachers' => $sanitized_teachers,
                    'count' => count($sanitized_teachers),
                    'execution_time' => $execution_time
                ]
            ];
        } else {
            $response['message'] = 'Missing lat or lng parameter';
        }
        break;
        
    case 'get_best_recommendation':
        // Mendapatkan rekomendasi guru terbaik untuk permintaan tertentu
        if (isset($_GET['assignment_id']) && is_numeric($_GET['assignment_id'])) {
            $assignment_id = (int)$_GET['assignment_id'];
            
            // Cek apakah permintaan ada
            $query = "SELECT * FROM assignments WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("i", $assignment_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $response['message'] = 'Assignment not found';
                break;
            }
            
            $assignment = $result->fetch_assoc();
            
            // Cek apakah pengguna memiliki akses ke permintaan ini
            $has_access = false;
            
            if ($_SESSION['role'] === 'admin') {
                $has_access = true;
            } elseif ($_SESSION['role'] === 'school') {
                // Ambil profil sekolah
                $query = "SELECT * FROM school_profiles WHERE user_id = ?";
                $stmt = $db->prepare($query);
                $stmt->bind_param("i", $_SESSION['user_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $school_profile = $result->fetch_assoc();
                    if ($assignment['school_id'] === $school_profile['id']) {
                        $has_access = true;
                    }
                }
            }
            
            if (!$has_access) {
                http_response_code(403);
                $response['message'] = 'You do not have access to this assignment';
                break;
            }
            
            // Dapatkan rekomendasi guru terbaik
            $teacher = get_best_teacher_recommendation($assignment_id);
            
            if ($teacher) {
                // Sanitasi data sebelum dikirim ke client
                $sanitized_teacher = [
                    'id' => $teacher['id'],
                    'full_name' => $teacher['full_name'],
                    'education' => $teacher['education'],
                    'subject_expertise' => $teacher['subject_expertise'],
                    'rating' => $teacher['rating'],
                    'match_score' => $teacher['match_score'],
                    'match_reason' => $teacher['match_reason'],
                    'profile_picture' => $teacher['profile_picture']
                ];
                
                // Pengukur waktu selesai
                $execution_time = microtime(true) - $start_time;
                
                $response = [
                    'status' => 'success',
                    'message' => 'Best teacher recommendation retrieved successfully',
                    'data' => [
                        'teacher' => $sanitized_teacher,
                        'execution_time' => $execution_time
                    ]
                ];
            } else {
                $response['message'] = 'No teacher recommendation found';
            }
        } else {
            $response['message'] = 'Missing or invalid assignment_id parameter';
        }
        break;
        
    default:
        $response['message'] = 'Unknown action';
        break;
}

// Output JSON response
echo json_encode($response);
?>