<?php
/**
 * /api/location.php
 * API endpoint untuk fitur lokasi
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
require_once '../includes/location-functions.php';

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

// Handle berbagai actions
switch ($action) {
    case 'update_location':
        // Memperbarui lokasi pengguna
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $lat = isset($_POST['lat']) ? (float)$_POST['lat'] : 0;
            $lng = isset($_POST['lng']) ? (float)$_POST['lng'] : 0;
            
            if ($lat == 0 || $lng == 0) {
                $response['message'] = 'Invalid coordinates';
                break;
            }
            
            if ($_SESSION['role'] === 'teacher') {
                // Ambil ID profil guru
                $query = "SELECT id FROM teacher_profiles WHERE user_id = ?";
                $stmt = $db->prepare($query);
                $stmt->bind_param("i", $_SESSION['user_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0) {
                    $response['message'] = 'Teacher profile not found';
                    break;
                }
                
                $teacher = $result->fetch_assoc();
                $teacher_id = $teacher['id'];
                
                // Update lokasi guru
                if (update_teacher_location($teacher_id, $lat, $lng)) {
                    $response = [
                        'status' => 'success',
                        'message' => 'Teacher location updated successfully',
                        'data' => [
                            'lat' => $lat,
                            'lng' => $lng
                        ]
                    ];
                } else {
                    $response['message'] = 'Failed to update teacher location';
                }
            } elseif ($_SESSION['role'] === 'school') {
                // Ambil ID profil sekolah
                $query = "SELECT id FROM school_profiles WHERE user_id = ?";
                $stmt = $db->prepare($query);
                $stmt->bind_param("i", $_SESSION['user_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0) {
                    $response['message'] = 'School profile not found';
                    break;
                }
                
                $school = $result->fetch_assoc();
                $school_id = $school['id'];
                
                // Update lokasi sekolah
                if (update_school_location($school_id, $lat, $lng)) {
                    $response = [
                        'status' => 'success',
                        'message' => 'School location updated successfully',
                        'data' => [
                            'lat' => $lat,
                            'lng' => $lng
                        ]
                    ];
                } else {
                    $response['message'] = 'Failed to update school location';
                }
            } else {
                $response['message'] = 'Invalid user role for location update';
            }
        } else {
            $response['message'] = 'Method not allowed, use POST';
            http_response_code(405);
        }
        break;
        
    case 'get_location':
        // Mendapatkan lokasi sekolah atau guru
        $type = isset($_GET['type']) ? validate_input($_GET['type']) : '';
        $id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if ($id <= 0) {
            $response['message'] = 'Invalid ID parameter';
            break;
        }
        
        if ($type === 'school') {
            // Ambil lokasi sekolah
            $location = get_school_location($id);
            
            if ($location) {
                $response = [
                    'status' => 'success',
                    'message' => 'School location retrieved successfully',
                    'data' => [
                        'lat' => (float)$location['location_lat'],
                        'lng' => (float)$location['location_lng'],
                        'address' => $location['address']
                    ]
                ];
            } else {
                $response['message'] = 'School location not found';
            }
        } elseif ($type === 'teacher') {
            // Ambil lokasi guru
            $query = "SELECT location_lat, location_lng, address FROM teacher_profiles WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $location = $result->fetch_assoc();
                
                $response = [
                    'status' => 'success',
                    'message' => 'Teacher location retrieved successfully',
                    'data' => [
                        'lat' => (float)$location['location_lat'],
                        'lng' => (float)$location['location_lng'],
                        'address' => $location['address']
                    ]
                ];
            } else {
                $response['message'] = 'Teacher location not found';
            }
        } else {
            $response['message'] = 'Invalid type parameter, use "school" or "teacher"';
        }
        break;
        
    case 'calculate_distance':
        // Menghitung jarak antara dua lokasi
        $lat1 = isset($_GET['lat1']) ? (float)$_GET['lat1'] : 0;
        $lng1 = isset($_GET['lng1']) ? (float)$_GET['lng1'] : 0;
        $lat2 = isset($_GET['lat2']) ? (float)$_GET['lat2'] : 0;
        $lng2 = isset($_GET['lng2']) ? (float)$_GET['lng2'] : 0;
        
        if ($lat1 == 0 || $lng1 == 0 || $lat2 == 0 || $lng2 == 0) {
            $response['message'] = 'Invalid coordinates';
            break;
        }
        
        $distance = calculate_distance($lat1, $lng1, $lat2, $lng2);
        
        $response = [
            'status' => 'success',
            'message' => 'Distance calculated successfully',
            'data' => [
                'distance_km' => round($distance, 2),
                'distance_m' => round($distance * 1000, 2)
            ]
        ];
        break;
        
    case 'verify_attendance_location':
        // Memverifikasi lokasi kehadiran guru
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $teacher_lat = isset($_POST['teacher_lat']) ? (float)$_POST['teacher_lat'] : 0;
            $teacher_lng = isset($_POST['teacher_lng']) ? (float)$_POST['teacher_lng'] : 0;
            $assignment_id = isset($_POST['assignment_id']) && is_numeric($_POST['assignment_id']) ? (int)$_POST['assignment_id'] : 0;
            
            if ($teacher_lat == 0 || $teacher_lng == 0 || $assignment_id <= 0) {
                $response['message'] = 'Invalid parameters';
                break;
            }
            
            // Ambil lokasi sekolah untuk penugasan ini
            $query = "SELECT s.location_lat, s.location_lng, s.school_name, s.address 
                      FROM assignments a
                      JOIN school_profiles s ON a.school_id = s.id
                      WHERE a.id = ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("i", $assignment_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $response['message'] = 'Assignment or school not found';
                break;
            }
            
            $school = $result->fetch_assoc();
            $school_lat = (float)$school['location_lat'];
            $school_lng = (float)$school['location_lng'];
            
            if ($school_lat == 0 || $school_lng == 0) {
                $response['message'] = 'School location not available';
                break;
            }
            
            // Hitung jarak
            $distance = calculate_distance($teacher_lat, $teacher_lng, $school_lat, $school_lng);
            $is_at_location = $distance <= 1; // Dalam 1 km dianggap di lokasi
            
            $response = [
                'status' => 'success',
                'message' => 'Location verification completed',
                'data' => [
                    'distance' => round($distance, 2),
                    'is_at_location' => $is_at_location,
                    'school_name' => $school['school_name'],
                    'school_address' => $school['address'],
                    'school_lat' => $school_lat,
                    'school_lng' => $school_lng
                ]
            ];
        } else {
            $response['message'] = 'Method not allowed, use POST';
            http_response_code(405);
        }
        break;
        
    case 'get_attendance_map_data':
        // Mendapatkan data lokasi untuk peta kehadiran
        $attendance_id = isset($_GET['attendance_id']) && is_numeric($_GET['attendance_id']) ? (int)$_GET['attendance_id'] : 0;
        
        if ($attendance_id <= 0) {
            $response['message'] = 'Invalid attendance ID';
            break;
        }
        
        // Ambil detail kehadiran
        $attendance = get_attendance_detail($attendance_id);
        
        if (!$attendance) {
            $response['message'] = 'Attendance record not found';
            break;
        }
        
        // Cek apakah pengguna memiliki akses ke data ini
        $has_access = false;
        
        if ($_SESSION['role'] === 'admin') {
            $has_access = true;
        } elseif ($_SESSION['role'] === 'school') {
            // Ambil profil sekolah
            $query = "SELECT s.id FROM school_profiles s
                      JOIN assignments a ON s.id = a.school_id
                      WHERE s.user_id = ? AND a.id = ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("ii", $_SESSION['user_id'], $attendance['assignment_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $has_access = true;
            }
        } elseif ($_SESSION['role'] === 'teacher') {
            // Ambil profil guru
            $query = "SELECT id FROM teacher_profiles WHERE user_id = ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $teacher = $result->fetch_assoc();
                if ($teacher['id'] == $attendance['teacher_id']) {
                    $has_access = true;
                }
            }
        }
        
        if (!$has_access) {
            http_response_code(403);
            $response['message'] = 'You do not have access to this attendance data';
            break;
        }
        
        // Siapkan data lokasi
        $map_data = [
            'school' => [
                'name' => $attendance['school_name'],
                'address' => $attendance['school_address'],
                'lat' => (float)$attendance['school_lat'],
                'lng' => (float)$attendance['school_lng']
            ],
            'check_in' => null,
            'check_out' => null
        ];
        
        if ($attendance['check_in_location_lat'] && $attendance['check_in_location_lng']) {
            $map_data['check_in'] = [
                'lat' => (float)$attendance['check_in_location_lat'],
                'lng' => (float)$attendance['check_in_location_lng'],
                'time' => $attendance['check_in_time'],
                'distance' => $attendance['check_in_distance'] ?? calculate_distance(
                    $attendance['check_in_location_lat'],
                    $attendance['check_in_location_lng'],
                    $attendance['school_lat'],
                    $attendance['school_lng']
                )
            ];
        }
        
        if ($attendance['check_out_location_lat'] && $attendance['check_out_location_lng']) {
            $map_data['check_out'] = [
                'lat' => (float)$attendance['check_out_location_lat'],
                'lng' => (float)$attendance['check_out_location_lng'],
                'time' => $attendance['check_out_time'],
                'distance' => $attendance['check_out_distance'] ?? calculate_distance(
                    $attendance['check_out_location_lat'],
                    $attendance['check_out_location_lng'],
                    $attendance['school_lat'],
                    $attendance['school_lng']
                )
            ];
        }
        
        $response = [
            'status' => 'success',
            'message' => 'Attendance map data retrieved successfully',
            'data' => $map_data
        ];
        break;
        
    default:
        $response['message'] = 'Unknown action';
        break;
}

// Output JSON response
echo json_encode($response);
?>