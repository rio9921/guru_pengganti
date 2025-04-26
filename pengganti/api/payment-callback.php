<?php
/**
 * /api/payment-callback.php
 * Endpoint untuk menerima callback dari Tripay saat status pembayaran berubah
 * 
 * PENTING: File ini harus dapat diakses publik tanpa autentikasi
 */

// Header untuk JSON response
header('Content-Type: application/json');

// Nonaktifkan output buffering
if (ob_get_level()) ob_end_clean();

// Include file konfigurasi dan database
require_once '../config/config.php';
require_once '../config/database.php';

// Include file fungsi
require_once '../includes/functions.php';
require_once '../includes/payment-functions.php';

// Log error jika terjadi kesalahan
ini_set('log_errors', 1);
ini_set('error_log', '../logs/payment_callback_errors.log');

// Default response
$response = [
    'success' => false,
    'message' => 'Invalid request'
];

// Pastikan request method adalah POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    $response['message'] = 'Method not allowed';
    echo json_encode($response);
    exit;
}

// Ambil data JSON dari request body
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Log request data untuk debugging
$log_date = date('Y-m-d H:i:s');
$log_file = '../logs/payment_callbacks.log';
file_put_contents($log_file, "[$log_date] Received callback: " . $json . PHP_EOL, FILE_APPEND);

// Validasi data callback
if (!$data || !isset($data['reference']) || !isset($data['status'])) {
    http_response_code(400);
    $response['message'] = 'Invalid callback data';
    echo json_encode($response);
    exit;
}

// Validasi signature
$callbackSignature = isset($_SERVER['HTTP_X_CALLBACK_SIGNATURE']) ? $_SERVER['HTTP_X_CALLBACK_SIGNATURE'] : '';
$signature = create_tripay_callback_signature($data);

if ($callbackSignature !== $signature) {
    http_response_code(403);
    $response['message'] = 'Invalid signature';
    file_put_contents($log_file, "[$log_date] Invalid signature: $callbackSignature" . PHP_EOL, FILE_APPEND);
    echo json_encode($response);
    exit;
}

// Proses callback
try {
    // Cari pembayaran berdasarkan reference
    $query = "SELECT * FROM payments WHERE tripay_reference = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("s", $data['reference']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        $response['message'] = 'Payment not found';
        file_put_contents($log_file, "[$log_date] Payment not found: {$data['reference']}" . PHP_EOL, FILE_APPEND);
        echo json_encode($response);
        exit;
    }
    
    $payment = $result->fetch_assoc();
    
    // Update status pembayaran
    if (update_payment_status($data)) {
        $response = [
            'success' => true,
            'message' => 'Payment status updated successfully'
        ];
        
        // Log aktivitas
        file_put_contents($log_file, "[$log_date] Payment status updated: {$data['reference']} to {$data['status']}" . PHP_EOL, FILE_APPEND);
        
        // Jika status pembayaran PAID, update status lain yang terkait
        if ($data['status'] === 'PAID') {
            // Ambil data penugasan
            $query = "SELECT a.* FROM assignments a WHERE a.id = ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("i", $payment['assignment_id']);
            $stmt->execute();
            $assignment_result = $stmt->get_result();
            
            if ($assignment_result->num_rows > 0) {
                $assignment = $assignment_result->fetch_assoc();
                
                // Tambahkan log khusus
                file_put_contents($log_file, "[$log_date] Processing paid payment for assignment ID: {$assignment['id']}" . PHP_EOL, FILE_APPEND);
                
                // Periksa apakah ada guru yang diterima untuk penugasan ini
                $query = "SELECT * FROM applications WHERE assignment_id = ? AND status = 'accepted'";
                $stmt = $db->prepare($query);
                $stmt->bind_param("i", $assignment['id']);
                $stmt->execute();
                $application_result = $stmt->get_result();
                
                if ($application_result->num_rows > 0) {
                    // Update status penugasan jika belum in_progress
                    if ($assignment['status'] !== 'in_progress') {
                        $query = "UPDATE assignments SET status = 'in_progress' WHERE id = ?";
                        $stmt = $db->prepare($query);
                        $stmt->bind_param("i", $assignment['id']);
                        $stmt->execute();
                        
                        file_put_contents($log_file, "[$log_date] Updated assignment status to in_progress: {$assignment['id']}" . PHP_EOL, FILE_APPEND);
                    }
                    
                    // Ambil detail aplikasi yang diterima
                    $application = $application_result->fetch_assoc();
                    
                    // Kirim notifikasi ke guru
                    $query = "SELECT tp.user_id, tp.full_name FROM teacher_profiles tp WHERE tp.id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->bind_param("i", $application['teacher_id']);
                    $stmt->execute();
                    $teacher_result = $stmt->get_result();
                    
                    if ($teacher_result->num_rows > 0) {
                        $teacher = $teacher_result->fetch_assoc();
                        
                        $notification_title = "Pembayaran Dikonfirmasi";
                        $notification_message = "Pembayaran untuk penugasan \"{$assignment['title']}\" telah dikonfirmasi. Anda dapat mulai mengajar sesuai jadwal.";
                        
                        create_notification($teacher['user_id'], $notification_title, $notification_message, 'payment', $payment['id']);
                        
                        file_put_contents($log_file, "[$log_date] Sent notification to teacher: {$teacher['full_name']}" . PHP_EOL, FILE_APPEND);
                    }
                }
            }
        }
        
        echo json_encode($response);
    } else {
        http_response_code(500);
        $response['message'] = 'Failed to update payment status';
        file_put_contents($log_file, "[$log_date] Failed to update payment status: {$data['reference']}" . PHP_EOL, FILE_APPEND);
        echo json_encode($response);
    }
} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Internal server error: ' . $e->getMessage();
    file_put_contents($log_file, "[$log_date] Exception: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
    echo json_encode($response);
}
?>