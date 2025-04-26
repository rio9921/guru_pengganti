<?php
/**
 * GuruSinergi - Fungsi-fungsi Autentikasi
 * 
 * Kumpulan fungsi yang berhubungan dengan autentikasi pengguna
 */

// Include file konfigurasi jika belum
if (!function_exists('config')) {
    require_once dirname(__DIR__) . '/config/config.php';
}

// Include file database jika belum
if (!function_exists('db_connect')) {
    require_once dirname(__DIR__) . '/config/database.php';
}

/**
 * Fungsi untuk login pengguna
 * 
 * @param string $username Username atau email
 * @param string $password Password
 * @param bool $remember Apakah fitur "ingat saya" diaktifkan
 * @return array|bool Data user jika berhasil, false jika gagal
 */
function login($username, $password, $remember = false) {
    if (empty($username) || empty($password)) {
        set_error_message('Username dan password harus diisi.');
        return false;
    }
    
    $conn = db_connect();
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        // Set session
        $_SESSION['user_id'] = $user['id'];
        
        // Set cookie jika "ingat saya" diaktifkan
        if ($remember) {
            $token = generate_random_string(32);
            $expires = time() + (30 * 24 * 60 * 60); // 30 hari
            
            $stmt = $conn->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
            $stmt->execute([$token, $user['id']]);
            
            setcookie('remember_token', $token, $expires, '/');
        }
        
        return $user;
    }
    
    set_error_message('Username atau password salah.');
    return false;
}

/**
 * Fungsi untuk mendapatkan tipe pengguna yang sedang login
 * 
 * @return string|bool Tipe pengguna ('guru', 'sekolah', 'orangtua', 'admin') atau false jika belum login
 */
function get_user_type() {
    if (!is_logged_in()) {
        return false;
    }
    
    $user = get_app_current_user();
    
    if (isset($user['user_type'])) {
        return $user['user_type'];
    }
    
    return false;
}

/**
 * Fungsi untuk logout pengguna
 */
function logout() {
    // Hapus session
    unset($_SESSION['user_id']);
    
    // Hapus cookie "ingat saya"
    if (isset($_COOKIE['remember_token'])) {
        $conn = db_connect();
        $stmt = $conn->prepare("UPDATE users SET remember_token = NULL WHERE remember_token = ?");
        $stmt->execute([$_COOKIE['remember_token']]);
        
        setcookie('remember_token', '', time() - 3600, '/');
    }
    
    // Hancurkan session
    session_destroy();
}

/**
 * Fungsi untuk registrasi pengguna baru
 * 
 * @param array $data Data pengguna (username, email, password, full_name, phone, user_type)
 * @return int|bool ID pengguna jika berhasil, false jika gagal
 */
function register($data) {
    // Validasi input
    if (empty($data['username']) || empty($data['email']) || empty($data['password']) || 
        empty($data['confirm_password']) || empty($data['full_name']) || empty($data['phone'])) {
        set_error_message('Semua field harus diisi.');
        return false;
    }
    
    if ($data['password'] != $data['confirm_password']) {
        set_error_message('Password dan konfirmasi password tidak cocok.');
        return false;
    }
    
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        set_error_message('Format email tidak valid.');
        return false;
    }
    
    // Ubah validasi tipe user untuk menerima orangtua
    if (!in_array($data['user_type'], ['guru', 'sekolah', 'orangtua'])) {
        set_error_message('Tipe user tidak valid.');
        return false;
    }
    
    $conn = db_connect();
    
    // Cek apakah username atau email sudah terdaftar
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$data['username'], $data['email']]);
    
    if ($stmt->fetchColumn() > 0) {
        set_error_message('Username atau email sudah terdaftar.');
        return false;
    }
    
    // Hash password
    $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
    
    // Begin transaction
    $conn->beginTransaction();
    
    try {
        // Simpan user ke database
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, phone, user_type) 
                                VALUES (?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $data['username'], 
            $data['email'], 
            $hashed_password, 
            $data['full_name'], 
            $data['phone'], 
            $data['user_type']
        ]);
        
        $user_id = $conn->lastInsertId();
        
        // Buat profile berdasarkan tipe user
        if ($data['user_type'] == 'guru') {
            $stmt = $conn->prepare("INSERT INTO profiles_guru (user_id) VALUES (?)");
            $stmt->execute([$user_id]);
        } elseif ($data['user_type'] == 'sekolah') {
            $stmt = $conn->prepare("INSERT INTO profiles_sekolah (user_id) VALUES (?)");
            $stmt->execute([$user_id]);
        } elseif ($data['user_type'] == 'orangtua') {
            // Tambahkan table profiles_orangtua jika belum ada
            $stmt = $conn->prepare("INSERT INTO profiles_orangtua (user_id) VALUES (?)");
            $stmt->execute([$user_id]);
        }
        
        // Commit transaction
        $conn->commit();
        
        // Set session
        $_SESSION['user_id'] = $user_id;
        
        // Kirim email selamat datang
        $subject = "Selamat Datang di GuruSinergi";
        $message = "<html><body>";
        $message .= "<h2>Selamat Datang di GuruSinergi!</h2>";
        $message .= "<p>Hai {$data['full_name']},</p>";
        $message .= "<p>Terima kasih telah mendaftar di platform GuruSinergi. Akun Anda berhasil dibuat.</p>";
        $message .= "<p>Silakan lengkapi profil Anda untuk dapat menggunakan fitur-fitur di platform kami.</p>";
        $message .= "<p>Salam,<br>Tim GuruSinergi</p>";
        $message .= "</body></html>";
        
        send_email($data['email'], $subject, $message);
        
        return $user_id;
    } catch (Exception $e) {
        // Rollback jika terjadi error
        $conn->rollBack();
        set_error_message('Terjadi kesalahan saat mendaftar: ' . $e->getMessage());
        return false;
    }
}

/**
 * Fungsi untuk reset password
 * 
 * @param string $email Email pengguna
 * @return bool True jika berhasil, false jika gagal
 */
function reset_password_request($email) {
    if (empty($email)) {
        set_error_message('Email harus diisi.');
        return false;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_error_message('Format email tidak valid.');
        return false;
    }
    
    $conn = db_connect();
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        set_error_message('Email tidak terdaftar.');
        return false;
    }
    
    // Generate token
    $token = generate_random_string(32);
    $expires = date('Y-m-d H:i:s', time() + (24 * 60 * 60)); // 24 jam
    
    // Simpan token
    $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
    $stmt->execute([$token, $expires, $user['id']]);
    
    // Kirim email reset password
    $reset_url = config('site_url') . '/reset-password.php?token=' . $token;
    
    $subject = "Reset Password GuruSinergi";
    $message = "<html><body>";
    $message .= "<h2>Reset Password</h2>";
    $message .= "<p>Hai {$user['full_name']},</p>";
    $message .= "<p>Kami menerima permintaan untuk mereset password akun GuruSinergi Anda.</p>";
    $message .= "<p>Klik link berikut untuk mereset password Anda:<br><a href='{$reset_url}'>{$reset_url}</a></p>";
    $message .= "<p>Link ini akan kedaluwarsa dalam 24 jam.</p>";
    $message .= "<p>Jika Anda tidak meminta reset password, abaikan email ini.</p>";
    $message .= "<p>Salam,<br>Tim GuruSinergi</p>";
    $message .= "</body></html>";
    
    if (send_email($email, $subject, $message)) {
        set_success_message('Instruksi reset password telah dikirim ke email Anda.');
        return true;
    } else {
        set_error_message('Terjadi kesalahan saat mengirim email. Silakan coba lagi.');
        return false;
    }
}

/**
 * Fungsi untuk konfirmasi reset password
 * 
 * @param string $token Token reset password
 * @param string $password Password baru
 * @param string $confirm_password Konfirmasi password baru
 * @return bool True jika berhasil, false jika gagal
 */
function reset_password_confirm($token, $password, $confirm_password) {
    if (empty($token) || empty($password) || empty($confirm_password)) {
        set_error_message('Semua field harus diisi.');
        return false;
    }
    
    if ($password != $confirm_password) {
        set_error_message('Password dan konfirmasi password tidak cocok.');
        return false;
    }
    
    $conn = db_connect();
    $stmt = $conn->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        set_error_message('Token tidak valid atau sudah kedaluwarsa.');
        return false;
    }
    
    // Hash password baru
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Update password dan hapus token
    $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
    $stmt->execute([$hashed_password, $user['id']]);
    
    set_success_message('Password berhasil diubah. Silakan login dengan password baru Anda.');
    return true;
}

/**
 * Fungsi untuk cek apakah pengguna memiliki akses ke halaman tertentu
 * 
 * @param string $page_type Tipe halaman ('guru', 'sekolah', 'admin', 'all')
 * @param bool $redirect Apakah redirect jika tidak memiliki akses
 * @return bool True jika memiliki akses, false jika tidak
 */
function check_access($page_type = 'all', $redirect = true) {
    if (!is_logged_in()) {
        if ($redirect) {
            set_error_message('Anda harus login terlebih dahulu.');
            redirect(url('login.php'));
        }
        return false;
    }
    
    $user = get_app_current_user();
    
    if ($page_type == 'all') {
        return true;
    }
    
    if ($page_type == 'guru' && $user['user_type'] != 'guru') {
        if ($redirect) {
            set_error_message('Anda tidak memiliki akses ke halaman ini.');
            redirect(url('dashboard.php'));
        }
        return false;
    }
    
    if ($page_type == 'sekolah' && $user['user_type'] != 'sekolah') {
        if ($redirect) {
            set_error_message('Anda tidak memiliki akses ke halaman ini.');
            redirect(url('dashboard.php'));
        }
        return false;
    }
    
    if ($page_type == 'admin' && $user['user_type'] != 'admin') {
        if ($redirect) {
            set_error_message('Anda tidak memiliki akses ke halaman ini.');
            redirect(url('dashboard.php'));
        }
        return false;
    }
    
    return true;
}

/**
 * Fungsi untuk memeriksa apakah profil perlu dilengkapi
 * 
 * @param bool $redirect Apakah redirect jika profil belum lengkap
 * @return bool True jika profil perlu dilengkapi, false jika tidak
 */
function check_profile_required($redirect = true) {
    if (!is_logged_in()) {
        if ($redirect) {
            redirect(url('login.php'));
        }
        return false;
    }
    
    $user = get_app_current_user();
    
    if (!is_profile_completed($user)) {
        if ($redirect) {
            set_error_message('Anda harus melengkapi profil terlebih dahulu.');
            redirect(url('complete-profile.php'));
        }
        return true;
    }
    
    return false;
}

/**
 * Fungsi untuk memeriksa apakah profil sudah diverifikasi
 * 
 * @param bool $redirect Apakah redirect jika profil belum diverifikasi
 * @return bool True jika profil sudah diverifikasi, false jika belum
 */
function check_profile_verified_status($redirect = true) {
    if (!is_logged_in()) {
        if ($redirect) {
            redirect(url('login.php'));
        }
        return false;
    }
    
    $user = get_app_current_user();
    
    if (!is_profile_verified($user)) {
        if ($redirect) {
            set_error_message('Profil Anda belum diverifikasi. Harap tunggu verifikasi dari admin.');
            redirect(url('dashboard.php'));
        }
        return false;
    }
    
    return true;
}