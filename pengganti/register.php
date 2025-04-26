<?php
/**
 * GuruSinergi - Register Page
 * 
 * Halaman untuk registrasi pengguna baru
 */

// Include file konfigurasi
require_once 'config/config.php';

// Include file database
require_once 'config/database.php';

// Include file functions
require_once 'includes/functions.php';

// Include file auth functions
require_once 'includes/auth-functions.php';

// Redirect jika sudah login
if (is_logged_in()) {
    redirect(url('dashboard.php'));
}

// Ambil tipe user dari query string (sekarang termasuk 'orangtua')
$default_user_type = isset($_GET['type']) && in_array($_GET['type'], ['guru', 'sekolah', 'orangtua']) ? $_GET['type'] : 'guru';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = [
        'username' => sanitize($_POST['username']),
        'email' => sanitize($_POST['email']),
        'password' => $_POST['password'],
        'confirm_password' => $_POST['confirm_password'],
        'full_name' => sanitize($_POST['full_name']),
        'phone' => sanitize($_POST['phone']),
        'user_type' => sanitize($_POST['user_type'])
    ];
    
    // Validasi persetujuan syarat dan ketentuan
    if (!isset($_POST['agree_terms'])) {
        set_error_message('Anda harus menyetujui Syarat dan Ketentuan untuk mendaftar.');
    } else {
        // Lakukan registrasi
        $register_result = register($data);
        
        if ($register_result) {
            // Redirect ke halaman lengkapi profil
            redirect(url('complete-profile.php'));
        }
    }
}

// Set variabel untuk page title
$page_title = 'Registrasi';

// Include header
include_once 'templates/header.php';
?>

<div class="row">
    <div class="col-12 col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h1 class="card-title">Daftar Akun Baru</h1>
            </div>
            <div class="card-body">
                <!-- Pilihan tipe akun (sekarang termasuk 'orangtua') -->
                <div class="user-type-selector mb-4">
                    <div class="d-flex justify-content-center gap-3">
                        <a href="?type=guru" class="btn <?php echo $default_user_type == 'guru' ? 'btn-primary' : 'btn-outline-secondary'; ?>">Guru</a>
                        <a href="?type=sekolah" class="btn <?php echo $default_user_type == 'sekolah' ? 'btn-primary' : 'btn-outline-secondary'; ?>">Sekolah</a>
                        <a href="?type=orangtua" class="btn <?php echo $default_user_type == 'orangtua' ? 'btn-primary' : 'btn-outline-secondary'; ?>">Orang Tua</a>
                    </div>
                </div>
                
                <form method="post" action="" data-validate="true">
                    <div class="row">
                        <div class="col-12 col-md-6">
                            <div class="form-group">
                                <label for="full_name" class="form-label">Nama Lengkap</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" required>
                            </div>
                        </div>
                        
                        <div class="col-12 col-md-6">
                            <div class="form-group">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12 col-md-6">
                            <div class="form-group">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        
                        <div class="col-12 col-md-6">
                            <div class="form-group">
                                <label for="phone" class="form-label">Nomor Telepon</label>
                                <input type="tel" class="form-control" id="phone" name="phone" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12 col-md-6">
                            <div class="form-group">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                        </div>
                        
                        <div class="col-12 col-md-6">
                            <div class="form-group">
                                <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                    </div>
                    
                    <input type="hidden" name="user_type" value="<?php echo $default_user_type; ?>">
                    
                    <div class="form-check mt-3">
                        <input type="checkbox" class="form-check-input" id="agree_terms" name="agree_terms" required>
                        <label class="form-check-label" for="agree_terms">Saya menyetujui <a href="<?php echo url('terms.php'); ?>">Syarat dan Ketentuan</a> serta <a href="<?php echo url('privacy.php'); ?>">Kebijakan Privasi</a></label>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary w-100">Daftar Sekarang</button>
                    </div>
                </form>
            </div>
            <div class="card-footer">
                <p class="text-center mb-0">Sudah punya akun? <a href="<?php echo url('login.php'); ?>">Masuk disini</a></p>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once 'templates/footer.php';
?>