<?php
/**
 * GuruSinergi - Notification API
 * 
 * API Endpoint untuk sistem notifikasi
 */

// Include file konfigurasi
require_once '../config/config.php';

// Include file database
require_once '../config/database.php';

// Include file functions
require_once '../includes/functions.php';

// Include file auth functions
require_once '../includes/auth-functions.php';

// Include file notification functions
require_once '../includes/notification-functions.php';

// Headers untuk API
header('Content-Type: application/json');

// Verifikasi API key
$api_key = isset($_SERVER['HTTP_X_API_KEY']) ? $_SERVER['HTTP_X_API_KEY'] : '';
$valid_api_key = config('api_key');

if ($api_key !== $valid_api_key) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

// Tangkap request method
$method = $_SERVER['REQUEST_METHOD'];

// Ambil path endpoint
$request_uri = $_SERVER['REQUEST_URI'];
$uri_parts = explode('/', $request_uri);
$endpoint = end($uri_parts);

// Handle GET request - mendapatkan notifikasi
if ($method === 'GET') {
    // Cek parameter user_id
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    
    if ($user_id <= 0) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'User ID is required']);
        exit;
    }
    
    // Parameter opsional
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    $is_read = isset($_GET['is_read']) ? (($_GET['is_read'] === '1' || $_GET['is_read'] === 'true') ? 1 : 0) : null;
    
    // Query filter untuk status read/unread
    $read_filter = "";
    $params = [$user_id];
    
    if ($is_read !== null) {
        $read_filter = "AND is_read = ?";
        $params[] = $is_read;
    }
    
    // Dapatkan notifikasi dari database
    $conn = db_connect();
    $stmt = $conn->prepare("
        SELECT id, title, message, is_read, link, created_at
        FROM notifications
        WHERE user_id = ? $read_filter
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?
    ");
    
    // Tambahkan limit dan offset ke params
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt->execute($params);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Hitung total notifikasi untuk pagination
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total
        FROM notifications
        WHERE user_id = ? $read_filter
    ");
    
    // Hapus limit dan offset dari params untuk count query
    array_pop($params);
    array_pop($params);
    
    $stmt->execute($params);
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Kirim response
    echo json_encode([
        'status' => 'success',
        'data' => [
            'notifications' => $notifications,
            'total' => $count['total'],
            'limit' => $limit,
            'offset' => $offset
        ]
    ]);
    exit;
}

// Handle POST request - membuat notifikasi baru
elseif ($method === 'POST') {
    // Tangkap request body
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    // Validasi data yang diperlukan
    if (!isset($data['user_id']) || !isset($data['title']) || !isset($data['message'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'user_id, title, and message are required']);
        exit;
    }
    
    // Kirim notifikasi
    $user_id = intval($data['user_id']);
    $title = sanitize($data['title']);
    $message = sanitize($data['message']);
    $link = isset($data['link']) ? sanitize($data['link']) : '';
    
    $result = send_notification($user_id, $title, $message, $link);
    
    if ($result) {
        http_response_code(201);
        echo json_encode(['status' => 'success', 'message' => 'Notification created']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to create notification']);
    }
    exit;
}

// Handle PUT request - menandai notifikasi sebagai dibaca
elseif ($method === 'PUT' || $method === 'PATCH') {
    // Tangkap request body
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    // Endpoint untuk menandai semua notifikasi sebagai dibaca
    if ($endpoint === 'read-all') {
        if (!isset($data['user_id'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'user_id is required']);
            exit;
        }
        
        $user_id = intval($data['user_id']);
        $result = mark_all_notifications_as_read($user_id);
        
        if ($result) {
            echo json_encode(['status' => 'success', 'message' => 'All notifications marked as read']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to mark notifications as read']);
        }
        exit;
    }
    
    // Endpoint untuk menandai satu notifikasi sebagai dibaca
    else {
        if (!isset($data['notification_id']) || !isset($data['user_id'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'notification_id and user_id are required']);
            exit;
        }
        
        $notification_id = intval($data['notification_id']);
        $user_id = intval($data['user_id']);
        
        $result = mark_notification_as_read($notification_id, $user_id);
        
        if ($result) {
            echo json_encode(['status' => 'success', 'message' => 'Notification marked as read']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to mark notification as read']);
        }
        exit;
    }
}

// Handle DELETE request - menghapus notifikasi
elseif ($method === 'DELETE') {
    // Endpoint untuk menghapus semua notifikasi
    if ($endpoint === 'delete-all') {
        $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
        
        if ($user_id <= 0) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'User ID is required']);
            exit;
        }
        
        $result = delete_all_notifications($user_id);
        
        if ($result) {
            echo json_encode(['status' => 'success', 'message' => 'All notifications deleted']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete notifications']);
        }
        exit;
    }
    
    // Endpoint untuk menghapus satu notifikasi
    else {
        $notification_id = isset($_GET['notification_id']) ? intval($_GET['notification_id']) : 0;
        $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
        
        if ($notification_id <= 0 || $user_id <= 0) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Notification ID and User ID are required']);
            exit;
        }
        
        $result = delete_notification($notification_id, $user_id);
        
        if ($result) {
            echo json_encode(['status' => 'success', 'message' => 'Notification deleted']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete notification']);
        }
        exit;
    }
}

// Jika method tidak didukung
else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}