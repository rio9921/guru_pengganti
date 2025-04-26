<?php
// /payments/process.php
// Halaman untuk memproses pembayaran menggunakan Tripay

// Mulai sesi
session_start();

// Cek apakah pengguna sudah login dan adalah sekolah
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'school') {
    // Redirect ke halaman login
    header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Include file konfigurasi dan database
require_once '../config/config.php';
require_once '../config/database.php';

// Include file fungsi
require_once '../includes/functions.php';
require_once '../includes/school-functions.php';
require_once '../includes/payment-functions.php';

// Proses parameter URL
$assignment_id = isset($_GET['assignment_id']) ? (int)$_GET['assignment_id'] : 0;

if ($assignment_id <= 0) {
    set_flash_message('error', 'ID penugasan tidak valid.');
    header('Location: /schools/dashboard.php');
    exit;
}

// Ambil data penugasan
$query = "SELECT a.*, s.user_id as school_user_id, s.school_name 
          FROM assignments a 
          JOIN school_profiles s ON a.school_id = s.id 
          WHERE a.id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $assignment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    set_flash_message('error', 'Penugasan tidak ditemukan.');
    header('Location: /schools/dashboard.php');
    exit;
}

$assignment = $result->fetch_assoc();

// Cek apakah pengguna adalah pemilik penugasan
if ($assignment['school_user_id'] != $_SESSION['user_id']) {
    set_flash_message('error', 'Anda tidak memiliki akses ke penugasan ini.');
    header('Location: /schools/dashboard.php');
    exit;
}

// Cek apakah ada guru yang sudah diterima untuk penugasan ini
$query = "SELECT tp.*, u.email
          FROM applications a
          JOIN teacher_profiles tp ON a.teacher_id = tp.id
          JOIN users u ON tp.user_id = u.id
          WHERE a.assignment_id = ? AND a.status = 'accepted'";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $assignment_id);
$stmt->execute();
$teacher_result = $stmt->get_result();

if ($teacher_result->num_rows === 0) {
    set_flash_message('error', 'Belum ada guru yang diterima untuk penugasan ini.');
    header('Location: /assignments/detail.php?id=' . $assignment_id);
    exit;
}

$teacher = $teacher_result->fetch_assoc();

// Cek apakah sudah ada pembayaran untuk penugasan ini
$query = "SELECT * FROM payments WHERE assignment_id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $assignment_id);
$stmt->execute();
$payment_result = $stmt->get_result();

$existing_payment = null;
if ($payment_result->num_rows > 0) {
    $existing_payment = $payment_result->fetch_assoc();
    
    // Jika pembayaran sudah dibayar, redirect ke halaman success
    if ($existing_payment['status'] === 'paid') {
        header('Location: /payments/success.php?payment_id=' . $existing_payment['id']);
        exit;
    }
}

// Ambil profil sekolah
$school_profile = get_school_profile_by_user_id($_SESSION['user_id']);

// Set judul halaman
$page_title = 'Proses Pembayaran';

// Tentukan biaya platform (fee)
$platform_fee_percentage = 0.10; // 10% dari total pembayaran
$platform_fee = $assignment['budget'] * $platform_fee_percentage;
$total_amount = $assignment['budget'] + $platform_fee;

// Proses form jika disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : '';
    
    if (empty($payment_method)) {
        $error_message = 'Pilih metode pembayaran.';
    } else {
        // Buat atau perbarui pembayaran
        if ($existing_payment) {
            // Jika pembayaran sudah ada tapi expired atau failed, buat pembayaran baru
            if ($existing_payment['status'] === 'expired' || $existing_payment['status'] === 'failed') {
                $payment_data = [
                    'assignment_id' => $assignment_id,
                    'amount' => $assignment['budget'],
                    'fee' => $platform_fee,
                    'payment_method' => $payment_method,
                    'customer_name' => $school_profile['school_name'],
                    'customer_email' => $_SESSION['email'],
                    'customer_phone' => $school_profile['phone']
                ];
                
                $payment_result = create_tripay_payment($payment_data);
                
                if ($payment_result['success']) {
                    $payment_id = $payment_result['payment_id'];
                    header('Location: /payments/success.php?payment_id=' . $payment_id);
                    exit;
                } else {
                    $error_message = 'Gagal membuat pembayaran: ' . $payment_result['message'];
                }
            } else {
                // Jika masih pending, gunakan pembayaran yang sudah ada
                header('Location: /payments/success.php?payment_id=' . $existing_payment['id']);
                exit;
            }
        } else {
            // Buat pembayaran baru
            $payment_data = [
                'assignment_id' => $assignment_id,
                'amount' => $assignment['budget'],
                'fee' => $platform_fee,
                'payment_method' => $payment_method,
                'customer_name' => $school_profile['school_name'],
                'customer_email' => $_SESSION['email'],
                'customer_phone' => $school_profile['phone']
            ];
            
            $payment_result = create_tripay_payment($payment_data);
            
            if ($payment_result['success']) {
                $payment_id = $payment_result['payment_id'];
                header('Location: /payments/success.php?payment_id=' . $payment_id);
                exit;
            } else {
                $error_message = 'Gagal membuat pembayaran: ' . $payment_result['message'];
            }
        }
    }
}

// Ambil daftar metode pembayaran dari Tripay API
$payment_channels = get_tripay_payment_channels();

// Include header
include('../templates/header.php');
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Proses Pembayaran</h5>
                        <a href="/assignments/detail.php?id=<?php echo $assignment_id; ?>" class="btn btn-sm btn-light">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $error_message; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mb-4">
                        <h5>Detail Penugasan</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Judul:</strong> <?php echo htmlspecialchars($assignment['title']); ?></p>
                                <p><strong>Mata Pelajaran:</strong> <?php echo htmlspecialchars($assignment['subject']); ?></p>
                                <p><strong>Periode:</strong> <?php echo format_date($assignment['start_date']); ?> - <?php echo format_date($assignment['end_date']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Guru:</strong> <?php echo htmlspecialchars($teacher['full_name']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($teacher['email']); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-4">
                        <h5>Rincian Pembayaran</h5>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <tr>
                                    <td>Biaya Guru</td>
                                    <td class="text-end">Rp <?php echo number_format($assignment['budget'], 0, ',', '.'); ?></td>
                                </tr>
                                <tr>
                                    <td>Biaya Layanan (<?php echo $platform_fee_percentage * 100; ?>%)</td>
                                    <td class="text-end">Rp <?php echo number_format($platform_fee, 0, ',', '.'); ?></td>
                                </tr>
                                <tr class="fw-bold">
                                    <td>Total Pembayaran</td>
                                    <td class="text-end">Rp <?php echo number_format($total_amount, 0, ',', '.'); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <form method="post" action="">
                        <h5 class="mb-3">Pilih Metode Pembayaran</h5>
                        
                        <?php if (empty($payment_channels)): ?>
                        <div class="alert alert-warning" role="alert">
                            Tidak dapat memuat metode pembayaran. Silakan coba lagi nanti.
                        </div>
                        <?php else: ?>
                        
                        <!-- Bank Transfer -->
                        <div class="mb-4">
                            <h6>Transfer Bank</h6>
                            <div class="row">
                                <?php 
                                foreach ($payment_channels as $channel):
                                    if ($channel['group'] === 'Virtual Account'):
                                ?>
                                <div class="col-md-4 mb-3">
                                    <div class="form-check payment-method-card">
                                        <input class="form-check-input" type="radio" name="payment_method" id="<?php echo $channel['code']; ?>" value="<?php echo $channel['code']; ?>">
                                        <label class="form-check-label payment-label d-flex align-items-center" for="<?php echo $channel['code']; ?>">
                                            <img src="<?php echo $channel['icon_url']; ?>" alt="<?php echo $channel['name']; ?>" class="payment-icon me-2">
                                            <span><?php echo $channel['name']; ?></span>
                                        </label>
                                    </div>
                                </div>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </div>
                        </div>
                        
                        <!-- E-Wallet -->
                        <div class="mb-4">
                            <h6>E-Wallet</h6>
                            <div class="row">
                                <?php 
                                foreach ($payment_channels as $channel):
                                    if ($channel['group'] === 'E-Wallet'):
                                ?>
                                <div class="col-md-4 mb-3">
                                    <div class="form-check payment-method-card">
                                        <input class="form-check-input" type="radio" name="payment_method" id="<?php echo $channel['code']; ?>" value="<?php echo $channel['code']; ?>">
                                        <label class="form-check-label payment-label d-flex align-items-center" for="<?php echo $channel['code']; ?>">
                                            <img src="<?php echo $channel['icon_url']; ?>" alt="<?php echo $channel['name']; ?>" class="payment-icon me-2">
                                            <span><?php echo $channel['name']; ?></span>
                                        </label>
                                    </div>
                                </div>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </div>
                        </div>
                        
                        <!-- Minimarket -->
                        <div class="mb-4">
                            <h6>Minimarket</h6>
                            <div class="row">
                                <?php 
                                foreach ($payment_channels as $channel):
                                    if ($channel['group'] === 'Convenience Store'):
                                ?>
                                <div class="col-md-4 mb-3">
                                    <div class="form-check payment-method-card">
                                        <input class="form-check-input" type="radio" name="payment_method" id="<?php echo $channel['code']; ?>" value="<?php echo $channel['code']; ?>">
                                        <label class="form-check-label payment-label d-flex align-items-center" for="<?php echo $channel['code']; ?>">
                                            <img src="<?php echo $channel['icon_url']; ?>" alt="<?php echo $channel['name']; ?>" class="payment-icon me-2">
                                            <span><?php echo $channel['name']; ?></span>
                                        </label>
                                    </div>
                                </div>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </div>
                        </div>
                        
                        <?php endif; ?>
                        
                        <div class="mt-4">
                            <div class="alert alert-info" role="alert">
                                <i class="fas fa-info-circle"></i> Pembayaran akan dikelola oleh Tripay sebagai pihak ketiga. Setelah pembayaran berhasil, guru akan menerima pembayaran sesuai dengan ketentuan platform.
                            </div>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="agree_terms" required>
                            <label class="form-check-label" for="agree_terms">
                                Saya setuju dengan <a href="/privacy.php" target="_blank">Kebijakan Privasi</a> dan <a href="/privacy.php" target="_blank">Syarat & Ketentuan</a> yang berlaku.
                            </label>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-credit-card"></i> Bayar Sekarang
                            </button>
                            <a href="/assignments/detail.php?id=<?php echo $assignment_id; ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.payment-method-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 10px;
    margin-bottom: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.payment-method-card:hover {
    border-color: #007bff;
    background-color: #f8f9fa;
}

.form-check-input:checked + .payment-label .payment-method-card {
    border-color: #007bff;
    background-color: #e6f2ff;
}

.payment-icon {
    max-height: 30px;
    max-width: 60px;
    object-fit: contain;
}

.payment-label {
    width: 100%;
    cursor: pointer;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Meningkatkan UX dengan membuat seluruh card bisa diklik
    const paymentCards = document.querySelectorAll('.payment-method-card');
    
    paymentCards.forEach(card => {
        card.addEventListener('click', function() {
            const radio = this.querySelector('input[type="radio"]');
            radio.checked = true;
            
            // Remove selected class from all cards
            paymentCards.forEach(c => {
                c.classList.remove('selected');
            });
            
            // Add selected class to clicked card
            this.classList.add('selected');
        });
    });
});
</script>

<?php
// Include footer
include('../templates/footer.php');
?>