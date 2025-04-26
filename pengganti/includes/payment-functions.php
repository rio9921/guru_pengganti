<?php
/**
 * Fungsi-fungsi untuk integrasi pembayaran dengan Tripay
 * /includes/payment-functions.php
 */

/**
 * Konfigurasi API Tripay
 */
define('TRIPAY_API_URL', 'https://tripay.co.id/api');
define('TRIPAY_API_KEY', getenv('TRIPAY_API_KEY')); // Ambil dari environment variable
define('TRIPAY_PRIVATE_KEY', getenv('TRIPAY_PRIVATE_KEY')); // Ambil dari environment variable
define('TRIPAY_MERCHANT_CODE', getenv('TRIPAY_MERCHANT_CODE')); // Ambil dari environment variable

/**
 * Mendapatkan daftar metode pembayaran dari Tripay API
 * 
 * @return array Daftar metode pembayaran
 */
function get_tripay_payment_channels() {
    $curl = curl_init();
    
    curl_setopt_array($curl, [
        CURLOPT_FRESH_CONNECT => true,
        CURLOPT_URL => TRIPAY_API_URL . '/merchant/payment-channel',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => false,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . TRIPAY_API_KEY],
        CURLOPT_FAILONERROR => false,
    ]);
    
    $response = curl_exec($curl);
    $error = curl_error($curl);
    
    curl_close($curl);
    
    if ($error) {
        error_log('Error getting Tripay payment channels: ' . $error);
        return [];
    }
    
    $channels = json_decode($response, true);
    
    if (isset($channels['success']) && $channels['success'] && isset($channels['data'])) {
        return $channels['data'];
    }
    
    return [];
}

/**
 * Membuat pembayaran baru menggunakan Tripay API
 * 
 * @param array $data Data pembayaran
 * @return array Hasil pembuatan pembayaran
 */
function create_tripay_payment($data) {
    global $db;
    
    // Validasi data
    if (empty($data['assignment_id']) || empty($data['amount']) || empty($data['payment_method'])) {
        return [
            'success' => false,
            'message' => 'Data pembayaran tidak lengkap.'
        ];
    }
    
    // Siapkan merchant ref
    $merchant_ref = 'GS-' . date('YmdHis') . '-' . $data['assignment_id'];
    
    // Ambil detail penugasan
    $query = "SELECT a.*, s.school_name
              FROM assignments a
              JOIN school_profiles s ON a.school_id = s.id
              WHERE a.id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $data['assignment_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return [
            'success' => false,
            'message' => 'Penugasan tidak ditemukan.'
        ];
    }
    
    $assignment = $result->fetch_assoc();
    
    // Siapkan item detail
    $items = [
        [
            'name' => 'Pembayaran Guru - ' . $assignment['title'],
            'price' => $data['amount'],
            'quantity' => 1
        ]
    ];
    
    if ($data['fee'] > 0) {
        $items[] = [
            'name' => 'Biaya Layanan',
            'price' => $data['fee'],
            'quantity' => 1
        ];
    }
    
    // Siapkan data untuk Tripay API
    $params = [
        'method' => $data['payment_method'],
        'merchant_ref' => $merchant_ref,
        'amount' => $data['amount'] + $data['fee'],
        'customer_name' => $data['customer_name'],
        'customer_email' => $data['customer_email'],
        'customer_phone' => $data['customer_phone'],
        'order_items' => $items,
        'return_url' => 'https://pengganti.gurusinergi.com/payments/success.php',
        'callback_url' => 'https://pengganti.gurusinergi.com/api/payment-callback.php',
        'expired_time' => (time() + (24 * 60 * 60)), // 24 jam
        'signature' => hash_hmac('sha256', TRIPAY_MERCHANT_CODE . $merchant_ref . ($data['amount'] + $data['fee']), TRIPAY_PRIVATE_KEY)
    ];
    
    $curl = curl_init();
    
    curl_setopt_array($curl, [
        CURLOPT_FRESH_CONNECT => true,
        CURLOPT_URL => TRIPAY_API_URL . '/transaction/create',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => false,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . TRIPAY_API_KEY],
        CURLOPT_FAILONERROR => false,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($params)
    ]);
    
    $response = curl_exec($curl);
    $error = curl_error($curl);
    
    curl_close($curl);
    
    if ($error) {
        error_log('Error creating Tripay payment: ' . $error);
        return [
            'success' => false,
            'message' => 'Error koneksi ke gateway pembayaran: ' . $error
        ];
    }
    
    $result = json_decode($response, true);
    
    if (!isset($result['success']) || !$result['success']) {
        $error_message = isset($result['message']) ? $result['message'] : 'Unknown error';
        error_log('Tripay API error: ' . $error_message);
        return [
            'success' => false,
            'message' => 'Gagal membuat pembayaran: ' . $error_message
        ];
    }
    
    // Simpan data pembayaran ke database
    $tripay_reference = $result['data']['reference'];
    $payment_url = $result['data']['checkout_url'];
    $payment_status = 'pending';
    
    $query = "INSERT INTO payments (
                assignment_id, 
                amount, 
                fee, 
                payment_method, 
                tripay_reference, 
                tripay_merchant_ref, 
                status, 
                payment_url
              ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param(
        "iddssss",
        $data['assignment_id'],
        $data['amount'],
        $data['fee'],
        $data['payment_method'],
        $tripay_reference,
        $merchant_ref,
        $payment_status,
        $payment_url
    );
    
    if (!$stmt->execute()) {
        error_log('Error saving payment to database: ' . $db->error);
        return [
            'success' => false,
            'message' => 'Gagal menyimpan data pembayaran: ' . $db->error
        ];
    }
    
    $payment_id = $db->insert_id;
    
    return [
        'success' => true,
        'message' => 'Pembayaran berhasil dibuat.',
        'payment_id' => $payment_id,
        'payment_url' => $payment_url,
        'reference' => $tripay_reference,
        'merchant_ref' => $merchant_ref
    ];
}

/**
 * Mendapatkan detail pembayaran dari database
 * 
 * @param int $payment_id ID pembayaran
 * @return array|false Detail pembayaran atau false jika tidak ditemukan
 */
function get_payment_detail($payment_id) {
    global $db;
    
    $query = "SELECT p.*, a.title as assignment_title, a.school_id,
              s.school_name, tp.full_name as teacher_name
              FROM payments p
              JOIN assignments a ON p.assignment_id = a.id
              JOIN school_profiles s ON a.school_id = s.id
              LEFT JOIN applications app ON app.assignment_id = a.id AND app.status = 'accepted'
              LEFT JOIN teacher_profiles tp ON app.teacher_id = tp.id
              WHERE p.id = ?";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $payment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }
    
    return $result->fetch_assoc();
}

/**
 * Mendapatkan detail pembayaran dari Tripay API
 * 
 * @param string $reference Referensi pembayaran Tripay
 * @return array|false Detail pembayaran atau false jika gagal
 */
function get_tripay_transaction_detail($reference) {
    $curl = curl_init();
    
    curl_setopt_array($curl, [
        CURLOPT_FRESH_CONNECT => true,
        CURLOPT_URL => TRIPAY_API_URL . '/transaction/detail?reference=' . $reference,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => false,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . TRIPAY_API_KEY],
        CURLOPT_FAILONERROR => false,
    ]);
    
    $response = curl_exec($curl);
    $error = curl_error($curl);
    
    curl_close($curl);
    
    if ($error) {
        error_log('Error getting Tripay transaction detail: ' . $error);
        return false;
    }
    
    $result = json_decode($response, true);
    
    if (!isset($result['success']) || !$result['success']) {
        error_log('Tripay API error: ' . ($result['message'] ?? 'Unknown error'));
        return false;
    }
    
    return $result['data'];
}

/**
 * Memperbarui status pembayaran berdasarkan callback dari Tripay
 * 
 * @param array $data Data callback dari Tripay
 * @return bool True jika berhasil, false jika gagal
 */
function update_payment_status($data) {
    global $db;
    
    // Cek apakah data valid
    if (!isset($data['reference']) || !isset($data['status'])) {
        return false;
    }
    
    // Map status dari Tripay ke status di database
    $status_map = [
        'UNPAID' => 'pending',
        'PAID' => 'paid',
        'EXPIRED' => 'expired',
        'FAILED' => 'failed',
        'REFUND' => 'refunded'
    ];
    
    $status = isset($status_map[$data['status']]) ? $status_map[$data['status']] : 'pending';
    
    // Update status pembayaran
    $query = "UPDATE payments SET 
              status = ?, 
              payment_date = " . ($status === 'paid' ? 'CURRENT_TIMESTAMP' : 'NULL') . "
              WHERE tripay_reference = ?";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param("ss", $status, $data['reference']);
    
    if (!$stmt->execute()) {
        error_log('Error updating payment status: ' . $db->error);
        return false;
    }
    
    // Jika pembayaran berhasil, buat notifikasi
    if ($status === 'paid') {
        // Ambil detail pembayaran
        $query = "SELECT p.*, a.school_id, s.user_id as school_user_id, 
                  app.teacher_id, tp.user_id as teacher_user_id,
                  a.title as assignment_title
                  FROM payments p
                  JOIN assignments a ON p.assignment_id = a.id
                  JOIN school_profiles s ON a.school_id = s.id
                  LEFT JOIN applications app ON app.assignment_id = a.id AND app.status = 'accepted'
                  LEFT JOIN teacher_profiles tp ON app.teacher_id = tp.id
                  WHERE p.tripay_reference = ?";
        
        $stmt = $db->prepare($query);
        $stmt->bind_param("s", $data['reference']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $payment = $result->fetch_assoc();
            
            // Kirim notifikasi ke sekolah
            $notification_title = "Pembayaran Berhasil";
            $notification_message = "Pembayaran Anda untuk penugasan \"{$payment['assignment_title']}\" telah berhasil.";
            create_notification($payment['school_user_id'], $notification_title, $notification_message, 'payment', $payment['id']);
            
            // Kirim notifikasi ke guru
            if ($payment['teacher_user_id']) {
                $notification_title = "Pembayaran Diterima";
                $notification_message = "Pembayaran untuk penugasan \"{$payment['assignment_title']}\" telah diterima.";
                create_notification($payment['teacher_user_id'], $notification_title, $notification_message, 'payment', $payment['id']);
            }
        }
    }
    
    return true;
}

/**
 * Membuat signature untuk callback dari Tripay
 * 
 * @param array $data Data callback dari Tripay
 * @return string Signature untuk validasi callback
 */
function create_tripay_callback_signature($data) {
    $json = json_encode($data);
    return hash_hmac('sha256', $json, TRIPAY_PRIVATE_KEY);
}

/**
 * Mendapatkan instruksi pembayaran dari Tripay
 * 
 * @param string $payment_code Kode metode pembayaran
 * @param string $pay_code Kode pembayaran
 * @return array|false Instruksi pembayaran atau false jika gagal
 */
function get_payment_instructions($payment_code, $pay_code) {
    $curl = curl_init();
    
    curl_setopt_array($curl, [
        CURLOPT_FRESH_CONNECT => true,
        CURLOPT_URL => TRIPAY_API_URL . '/payment/instruction?code=' . $payment_code . '&pay_code=' . $pay_code,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => false,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . TRIPAY_API_KEY],
        CURLOPT_FAILONERROR => false,
    ]);
    
    $response = curl_exec($curl);
    $error = curl_error($curl);
    
    curl_close($curl);
    
    if ($error) {
        error_log('Error getting payment instructions: ' . $error);
        return false;
    }
    
    $result = json_decode($response, true);
    
    if (!isset($result['success']) || !$result['success']) {
        error_log('Tripay API error: ' . ($result['message'] ?? 'Unknown error'));
        return false;
    }
    
    return $result['data'];
}

/**
 * Mendapatkan daftar pembayaran untuk sekolah
 * 
 * @param int $school_id ID profil sekolah
 * @param int|null $limit Limit jumlah data
 * @param int|null $offset Offset untuk paginasi
 * @return array Daftar pembayaran
 */
function get_school_payments_list($school_id, $limit = null, $offset = null) {
    global $db;
    
    $query = "SELECT p.*, a.title as assignment_title, tp.full_name as teacher_name
              FROM payments p
              JOIN assignments a ON p.assignment_id = a.id
              LEFT JOIN applications app ON app.assignment_id = a.id AND app.status = 'accepted'
              LEFT JOIN teacher_profiles tp ON app.teacher_id = tp.id
              WHERE a.school_id = ?
              ORDER BY p.created_at DESC";
    
    if ($limit !== null) {
        $query .= " LIMIT ?, ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("iii", $school_id, $offset, $limit);
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
 * Mendapatkan total pembayaran untuk sekolah
 * 
 * @param int $school_id ID profil sekolah
 * @return int Total pembayaran
 */
function get_school_payments_count($school_id) {
    global $db;
    
    $query = "SELECT COUNT(*) as total FROM payments p
              JOIN assignments a ON p.assignment_id = a.id
              WHERE a.school_id = ?";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $school_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['total'];
}

/**
 * Mendapatkan daftar pembayaran untuk guru
 * 
 * @param int $teacher_id ID profil guru
 * @param int|null $limit Limit jumlah data
 * @param int|null $offset Offset untuk paginasi
 * @return array Daftar pembayaran
 */
function get_teacher_payments_list($teacher_id, $limit = null, $offset = null) {
    global $db;
    
    $query = "SELECT p.*, a.title as assignment_title, s.school_name
              FROM payments p
              JOIN assignments a ON p.assignment_id = a.id
              JOIN school_profiles s ON a.school_id = s.id
              JOIN applications app ON app.assignment_id = a.id
              WHERE app.teacher_id = ? AND app.status = 'accepted' AND p.status = 'paid'
              ORDER BY p.created_at DESC";
    
    if ($limit !== null) {
        $query .= " LIMIT ?, ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("iii", $teacher_id, $offset, $limit);
    } else {
        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $teacher_id);
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
 * Mendapatkan total pembayaran untuk guru
 * 
 * @param int $teacher_id ID profil guru
 * @return int Total pembayaran
 */
function get_teacher_payments_count($teacher_id) {
    global $db;
    
    $query = "SELECT COUNT(*) as total FROM payments p
              JOIN assignments a ON p.assignment_id = a.id
              JOIN applications app ON app.assignment_id = a.id
              WHERE app.teacher_id = ? AND app.status = 'accepted' AND p.status = 'paid'";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['total'];
}

/**
 * Mendapatkan total pendapatan guru
 * 
 * @param int $teacher_id ID profil guru
 * @return int Total pendapatan
 */
function get_teacher_total_income($teacher_id) {
    global $db;
    
    $query = "SELECT SUM(p.amount) as total FROM payments p
              JOIN assignments a ON p.assignment_id = a.id
              JOIN applications app ON app.assignment_id = a.id
              WHERE app.teacher_id = ? AND app.status = 'accepted' AND p.status = 'paid'";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['total'] ?? 0;
}

/**
 * Mendapatkan total pengeluaran sekolah
 * 
 * @param int $school_id ID profil sekolah
 * @return int Total pengeluaran
 */
function get_school_total_expense($school_id) {
    global $db;
    
    $query = "SELECT SUM(p.amount + p.fee) as total FROM payments p
              JOIN assignments a ON p.assignment_id = a.id
              WHERE a.school_id = ? AND p.status = 'paid'";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $school_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['total'] ?? 0;
}

/**
 * Cek apakah penugasan sudah dibayar
 * 
 * @param int $assignment_id ID penugasan
 * @return bool True jika sudah dibayar, false jika belum
 */
function is_assignment_paid($assignment_id) {
    global $db;
    
    $query = "SELECT status FROM payments WHERE assignment_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $assignment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }
    
    $payment = $result->fetch_assoc();
    return ($payment['status'] === 'paid');
}

/**
 * Mendapatkan ID pembayaran berdasarkan penugasan
 * 
 * @param int $assignment_id ID penugasan
 * @return int|false ID pembayaran atau false jika tidak ditemukan
 */
function get_payment_id_by_assignment($assignment_id) {
    global $db;
    
    $query = "SELECT id FROM payments WHERE assignment_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $assignment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }
    
    $payment = $result->fetch_assoc();
    return $payment['id'];
}
?>