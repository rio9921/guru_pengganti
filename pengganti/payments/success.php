<?php
/**
 * GuruSinergi - Payment Success Page
 * 
 * Halaman yang ditampilkan setelah pembayaran berhasil
 */

// Include file konfigurasi
require_once '../config/config.php';

// Include file database
require_once '../config/database.php';

// Include file functions
require_once '../includes/functions.php';

// Include file auth functions
require_once '../includes/auth-functions.php';

// Include file payment functions
require_once '../includes/payment-functions.php';

// Cek login user
check_access('all');

// Inisialisasi variabel
$current_user = get_app_current_user();

// Ambil parameter dari URL
$reference = isset($_GET['reference']) ? sanitize($_GET['reference']) : '';
$status = isset($_GET['status']) ? sanitize($_GET['status']) : '';

// Cek referensi pembayaran
if (empty($reference)) {
    set_error_message('Referensi pembayaran tidak valid.');
    redirect(url('dashboard.php'));
}

// Ambil data pembayaran dari database
$conn = db_connect();
$stmt = $conn->prepare("
    SELECT p.*, a.judul, a.mata_pelajaran, a.tingkat_kelas, a.tanggal_mulai, a.tanggal_selesai,
           a.guru_id, a.sekolah_id, u_guru.full_name as guru_name, u_sekolah.full_name as sekolah_name
    FROM payments p
    JOIN assignments a ON p.assignment_id = a.id
    LEFT JOIN users u_guru ON a.guru_id = u_guru.id
    JOIN users u_sekolah ON a.sekolah_id = u_sekolah.id
    WHERE p.reference = ?
");
$stmt->execute([$reference]);
$payment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$payment) {
    set_error_message('Data pembayaran tidak ditemukan.');
    redirect(url('dashboard.php'));
}

// Cek apakah user adalah pemilik pembayaran
$is_owner = ($current_user['id'] == $payment['sekolah_id'] || $current_user['id'] == $payment['guru_id']);

if (!$is_owner) {
    set_error_message('Anda tidak memiliki akses ke pembayaran ini.');
    redirect(url('dashboard.php'));
}

// Cek status pembayaran dari Tripay
$payment_status = tripay_check_payment_status($reference);

// Update status pembayaran jika berhasil
if ($payment_status['status'] && $payment_status['data']['status'] == 'PAID' && $payment['status'] != 'paid') {
    $stmt = $conn->prepare("UPDATE payments SET status = 'paid' WHERE reference = ?");
    $stmt->execute([$reference]);
    
    // Kirim notifikasi
    notify_payment_status($payment['id'], 'paid');
    
    // Reload payment data
    $stmt = $conn->prepare("SELECT * FROM payments WHERE reference = ?");
    $stmt->execute([$reference]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Set variabel untuk page title
$page_title = 'Pembayaran Sukses';

// Include header
include_once '../templates/header.php';
?>

<div class="page-header text-center">
    <div class="header-icon success">
        <i class="fas fa-check-circle"></i>
    </div>
    <h1 class="page-title">Pembayaran Berhasil</h1>
    <p class="page-description">Terima kasih! Pembayaran Anda telah kami terima.</p>
</div>

<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card mb-4">
            <div class="card-header">
                <h2 class="card-title">Detail Pembayaran</h2>
            </div>
            <div class="card-body">
                <div class="detail-item">
                    <span class="detail-label">Status Pembayaran</span>
                    <span class="detail-value">
                        <span class="status-badge <?php echo $payment['status']; ?>">
                            <?php echo format_payment_status($payment['status']); ?>
                        </span>
                    </span>
                </div>
                
                <div class="detail-item">
                    <span class="detail-label">Nomor Referensi</span>
                    <span class="detail-value"><?php echo $payment['reference']; ?></span>
                </div>
                
                <div class="detail-item">
                    <span class="detail-label">Tanggal Pembayaran</span>
                    <span class="detail-value">
                        <?php echo date('d F Y, H:i', strtotime($payment['updated_at'])); ?> WIB
                    </span>
                </div>
                
                <div class="detail-item">
                    <span class="detail-label">Metode Pembayaran</span>
                    <span class="detail-value"><?php echo $payment['payment_method']; ?></span>
                </div>
                
                <div class="detail-divider"></div>
                
                <div class="detail-item">
                    <span class="detail-label">Penugasan</span>
                    <span class="detail-value"><?php echo $payment['judul']; ?></span>
                </div>
                
                <div class="detail-item">
                    <span class="detail-label">Guru</span>
                    <span class="detail-value"><?php echo $payment['guru_name']; ?></span>
                </div>
                
                <div class="detail-item">
                    <span class="detail-label">Sekolah</span>
                    <span class="detail-value"><?php echo $payment['sekolah_name']; ?></span>
                </div>
                
                <div class="detail-item">
                    <span class="detail-label">Periode</span>
                    <span class="detail-value">
                        <?php echo date('d M Y', strtotime($payment['tanggal_mulai'])); ?> - 
                        <?php echo date('d M Y', strtotime($payment['tanggal_selesai'])); ?>
                    </span>
                </div>
                
                <div class="detail-divider"></div>
                
                <div class="detail-item">
                    <span class="detail-label">Gaji Guru</span>
                    <span class="detail-value"><?php echo format_price($payment['amount'] - $payment['platform_fee']); ?></span>
                </div>
                
                <div class="detail-item">
                    <span class="detail-label">Biaya Platform (<?php echo config('platform_fee'); ?>%)</span>
                    <span class="detail-value"><?php echo format_price($payment['platform_fee']); ?></span>
                </div>
                
                <div class="detail-item total">
                    <span class="detail-label">Total Pembayaran</span>
                    <span class="detail-value"><?php echo format_price($payment['amount']); ?></span>
                </div>
            </div>
        </div>
        
        <div class="text-center mb-4">
            <a href="<?php echo url('assignments/detail.php?id=' . $payment['assignment_id']); ?>" class="btn btn-primary">
                <i class="fas fa-clipboard-list"></i> Lihat Detail Penugasan
            </a>
            
            <a href="<?php echo url('assignments/my-assignments.php'); ?>" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Kembali ke Penugasan Saya
            </a>
        </div>
        
        <div class="info-box">
            <div class="info-icon">
                <i class="fas fa-info-circle"></i>
            </div>
            <div class="info-content">
                <h3>Informasi Penting</h3>
                <p>
                    Pembayaran ini digunakan untuk membayar gaji guru dan biaya platform. 
                    Guru akan menerima <?php echo format_price($payment['amount'] - $payment['platform_fee']); ?> 
                    setelah menyelesaikan penugasan.
                </p>
                <p>
                    Jika Anda memiliki pertanyaan tentang pembayaran ini, silakan hubungi tim dukungan kami 
                    di <?php echo config('support_phone'); ?> atau email <?php echo config('admin_email'); ?>.
                </p>
            </div>
        </div>
    </div>
</div>

<style>
/* Header */
.header-icon {
    font-size: 56px;
    margin-bottom: 20px;
}

.header-icon.success {
    color: #28a745;
}

/* Detail Styles */
.detail-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 15px;
}

.detail-label {
    font-weight: 600;
    color: #495057;
}

.detail-value {
    text-align: right;
    color: #212529;
}

.detail-divider {
    height: 1px;
    background-color: #e9ecef;
    margin: 20px 0;
}

.detail-item.total {
    font-size: 1.2rem;
    font-weight: 600;
}

.detail-item.total .detail-value {
    color: #28a745;
}

/* Status Badge */
.status-badge {
    display: inline-block;
    padding: 5px 15px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.85rem;
}

.status-badge.pending {
    background-color: #fff3cd;
    color: #856404;
}

.status-badge.paid {
    background-color: #d4edda;
    color: #155724;
}

.status-badge.expired {
    background-color: #f8d7da;
    color: #721c24;
}

.status-badge.failed {
    background-color: #f8d7da;
    color: #721c24;
}

/* Info Box */
.info-box {
    display: flex;
    gap: 15px;
    background-color: #e9f5fe;
    border-radius: 8px;
    padding: 20px;
}

.info-icon {
    font-size: 24px;
    color: #007bff;
}

.info-content h3 {
    font-size: 1.1rem;
    margin-bottom: 10px;
}

.info-content p {
    font-size: 0.95rem;
    color: #495057;
    margin-bottom: 10px;
}

.info-content p:last-child {
    margin-bottom: 0;
}
</style>

<?php
// Include footer
include_once '../templates/footer.php';
?>