<?php
/**
 * GuruSinergi - Admin Dashboard
 * 
 * Halaman dashboard admin
 */

// Include file konfigurasi
require_once '../config/config.php';

// Include file database
require_once '../config/database.php';

// Include file functions
require_once '../includes/functions.php';

// Include file auth functions
require_once '../includes/auth-functions.php';

// Cek login admin
check_access('admin');

// Inisialisasi variabel
$current_user = get_app_current_user();

// Ambil data statistik
$conn = db_connect();

// Total Pengguna
$stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE user_type != 'admin'");
$stmt->execute();
$total_users = $stmt->fetchColumn();

// Total Guru
$stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE user_type = 'guru'");
$stmt->execute();
$total_guru = $stmt->fetchColumn();

// Total Sekolah
$stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE user_type = 'sekolah'");
$stmt->execute();
$total_sekolah = $stmt->fetchColumn();

// Total Penugasan
$stmt = $conn->prepare("SELECT COUNT(*) FROM assignments");
$stmt->execute();
$total_assignments = $stmt->fetchColumn();

// Penugasan Aktif
$stmt = $conn->prepare("SELECT COUNT(*) FROM assignments WHERE status = 'open'");
$stmt->execute();
$active_assignments = $stmt->fetchColumn();

// Penugasan Selesai
$stmt = $conn->prepare("SELECT COUNT(*) FROM assignments WHERE status = 'completed'");
$stmt->execute();
$completed_assignments = $stmt->fetchColumn();

// Total Transaksi
$stmt = $conn->prepare("SELECT COUNT(*) FROM payments");
$stmt->execute();
$total_transactions = $stmt->fetchColumn();

// Total Pendapatan Platform
$stmt = $conn->prepare("SELECT SUM(platform_fee) FROM payments WHERE status = 'paid'");
$stmt->execute();
$total_platform_revenue = $stmt->fetchColumn() ?: 0;

// Menunggu Verifikasi Guru
$stmt = $conn->prepare("SELECT COUNT(*) FROM profiles_guru WHERE status_verifikasi = 'pending'");
$stmt->execute();
$pending_guru_verification = $stmt->fetchColumn();

// Menunggu Verifikasi Sekolah
$stmt = $conn->prepare("SELECT COUNT(*) FROM profiles_sekolah WHERE status_verifikasi = 'pending'");
$stmt->execute();
$pending_sekolah_verification = $stmt->fetchColumn();

// Guru Terbaru
$stmt = $conn->prepare("
    SELECT u.id, u.full_name, u.email, u.phone, u.created_at, pg.status_verifikasi
    FROM users u
    JOIN profiles_guru pg ON u.id = pg.user_id
    WHERE u.user_type = 'guru'
    ORDER BY u.created_at DESC
    LIMIT 5
");
$stmt->execute();
$recent_guru = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Sekolah Terbaru
$stmt = $conn->prepare("
    SELECT u.id, u.full_name, u.email, u.phone, u.created_at, ps.nama_sekolah, ps.status_verifikasi
    FROM users u
    JOIN profiles_sekolah ps ON u.id = ps.user_id
    WHERE u.user_type = 'sekolah'
    ORDER BY u.created_at DESC
    LIMIT 5
");
$stmt->execute();
$recent_sekolah = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Penugasan Terbaru
$stmt = $conn->prepare("
    SELECT a.id, a.judul, a.mata_pelajaran, a.status, a.created_at, 
           u.full_name as sekolah_name, ps.nama_sekolah
    FROM assignments a
    JOIN users u ON a.sekolah_id = u.id
    JOIN profiles_sekolah ps ON u.id = ps.user_id
    ORDER BY a.created_at DESC
    LIMIT 5
");
$stmt->execute();
$recent_assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pembayaran Terbaru
$stmt = $conn->prepare("
    SELECT p.id, p.amount, p.status, p.created_at, a.judul, u.full_name as sekolah_name
    FROM payments p
    JOIN assignments a ON p.assignment_id = a.id
    JOIN users u ON a.sekolah_id = u.id
    ORDER BY p.created_at DESC
    LIMIT 5
");
$stmt->execute();
$recent_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set variabel untuk page title
$page_title = 'Dashboard Admin';

// Include header
include_once '../templates/admin-header.php';
?>

<!-- Statistik Ringkasan -->
<div class="dashboard-stats">
    <div class="row">
        <div class="col-md-3">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-value"><?php echo number_format($total_users); ?></h3>
                    <p class="stat-label">Total Pengguna</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-value"><?php echo number_format($total_guru); ?></h3>
                    <p class="stat-label">Total Guru</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card info">
                <div class="stat-icon">
                    <i class="fas fa-school"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-value"><?php echo number_format($total_sekolah); ?></h3>
                    <p class="stat-label">Total Sekolah</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card warning">
                <div class="stat-icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-value"><?php echo number_format($total_assignments); ?></h3>
                    <p class="stat-label">Total Penugasan</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-3">
            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-value"><?php echo number_format($completed_assignments); ?></h3>
                    <p class="stat-label">Penugasan Selesai</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card info">
                <div class="stat-icon">
                    <i class="fas fa-spinner"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-value"><?php echo number_format($active_assignments); ?></h3>
                    <p class="stat-label">Penugasan Aktif</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card warning">
                <div class="stat-icon">
                    <i class="fas fa-credit-card"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-value"><?php echo number_format($total_transactions); ?></h3>
                    <p class="stat-label">Total Transaksi</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-value"><?php echo format_price($total_platform_revenue); ?></h3>
                    <p class="stat-label">Pendapatan Platform</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Verifikasi yang Menunggu -->
<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Menunggu Verifikasi</h2>
            </div>
            <div class="card-body">
                <div class="verification-stats">
                    <div class="verification-item">
                        <div class="verification-icon">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div class="verification-content">
                            <h3 class="verification-value"><?php echo number_format($pending_guru_verification); ?></h3>
                            <p class="verification-label">Guru</p>
                        </div>
                        <a href="<?php echo url('admin/verification.php?type=guru&status=pending'); ?>" class="btn btn-sm btn-primary">Lihat</a>
                    </div>
                    
                    <div class="verification-item">
                        <div class="verification-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="verification-content">
                            <h3 class="verification-value"><?php echo number_format($pending_sekolah_verification); ?></h3>
                            <p class="verification-label">Sekolah</p>
                        </div>
                        <a href="<?php echo url('admin/verification.php?type=sekolah&status=pending'); ?>" class="btn btn-sm btn-primary">Lihat</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Navigasi Cepat</h2>
            </div>
            <div class="card-body">
                <div class="quick-links">
                    <a href="<?php echo url('admin/verification.php'); ?>" class="quick-link-item">
                        <div class="quick-link-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="quick-link-content">
                            <h3 class="quick-link-title">Verifikasi Pengguna</h3>
                            <p class="quick-link-desc">Verifikasi guru dan sekolah</p>
                        </div>
                    </a>
                    
                    <a href="<?php echo url('admin/assignments.php'); ?>" class="quick-link-item">
                        <div class="quick-link-icon">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <div class="quick-link-content">
                            <h3 class="quick-link-title">Penugasan</h3>
                            <p class="quick-link-desc">Kelola semua penugasan</p>
                        </div>
                    </a>
                    
                    <a href="<?php echo url('admin/payments.php'); ?>" class="quick-link-item">
                        <div class="quick-link-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="quick-link-content">
                            <h3 class="quick-link-title">Pembayaran</h3>
                            <p class="quick-link-desc">Kelola transaksi pembayaran</p>
                        </div>
                    </a>
                    
                    <a href="<?php echo url('admin/settings.php'); ?>" class="quick-link-item">
                        <div class="quick-link-icon">
                            <i class="fas fa-cog"></i>
                        </div>
                        <div class="quick-link-content">
                            <h3 class="quick-link-title">Pengaturan</h3>
                            <p class="quick-link-desc">Konfigurasi platform</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Daftar Terbaru -->
<div class="dashboard-recent mt-4">
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Guru Terbaru</h2>
                    <a href="<?php echo url('admin/verification.php?type=guru'); ?>" class="btn btn-sm btn-outline">Lihat Semua</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_guru)): ?>
                        <div class="text-center py-4">
                            <p>Belum ada data guru</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nama</th>
                                        <th>Email</th>
                                        <th>Tanggal Daftar</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_guru as $guru): ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo url('admin/verification.php?type=guru&id=' . $guru['id']); ?>">
                                                    <?php echo $guru['full_name']; ?>
                                                </a>
                                            </td>
                                            <td><?php echo $guru['email']; ?></td>
                                            <td><?php echo date('d M Y', strtotime($guru['created_at'])); ?></td>
                                            <td>
                                                <span class="badge <?php 
                                                    if ($guru['status_verifikasi'] == 'pending') echo 'badge-warning';
                                                    elseif ($guru['status_verifikasi'] == 'verified') echo 'badge-success';
                                                    else echo 'badge-danger';
                                                ?>">
                                                    <?php 
                                                        if ($guru['status_verifikasi'] == 'pending') echo 'Menunggu';
                                                        elseif ($guru['status_verifikasi'] == 'verified') echo 'Terverifikasi';
                                                        else echo 'Ditolak';
                                                    ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Sekolah Terbaru</h2>
                    <a href="<?php echo url('admin/verification.php?type=sekolah'); ?>" class="btn btn-sm btn-outline">Lihat Semua</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_sekolah)): ?>
                        <div class="text-center py-4">
                            <p>Belum ada data sekolah</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nama Sekolah</th>
                                        <th>PIC</th>
                                        <th>Tanggal Daftar</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_sekolah as $sekolah): ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo url('admin/verification.php?type=sekolah&id=' . $sekolah['id']); ?>">
                                                    <?php echo $sekolah['nama_sekolah']; ?>
                                                </a>
                                            </td>
                                            <td><?php echo $sekolah['full_name']; ?></td>
                                            <td><?php echo date('d M Y', strtotime($sekolah['created_at'])); ?></td>
                                            <td>
                                                <span class="badge <?php 
                                                    if ($sekolah['status_verifikasi'] == 'pending') echo 'badge-warning';
                                                    elseif ($sekolah['status_verifikasi'] == 'verified') echo 'badge-success';
                                                    else echo 'badge-danger';
                                                ?>">
                                                    <?php 
                                                        if ($sekolah['status_verifikasi'] == 'pending') echo 'Menunggu';
                                                        elseif ($sekolah['status_verifikasi'] == 'verified') echo 'Terverifikasi';
                                                        else echo 'Ditolak';
                                                    ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Penugasan Terbaru</h2>
                    <a href="<?php echo url('admin/assignments.php'); ?>" class="btn btn-sm btn-outline">Lihat Semua</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_assignments)): ?>
                        <div class="text-center py-4">
                            <p>Belum ada data penugasan</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Judul</th>
                                        <th>Sekolah</th>
                                        <th>Tanggal Dibuat</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_assignments as $assignment): ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo url('admin/assignments.php?action=view&id=' . $assignment['id']); ?>">
                                                    <?php echo $assignment['judul']; ?>
                                                </a>
                                            </td>
                                            <td><?php echo $assignment['nama_sekolah']; ?></td>
                                            <td><?php echo date('d M Y', strtotime($assignment['created_at'])); ?></td>
                                            <td>
                                                <span class="badge <?php 
                                                    if ($assignment['status'] == 'open') echo 'badge-info';
                                                    elseif ($assignment['status'] == 'assigned') echo 'badge-primary';
                                                    elseif ($assignment['status'] == 'completed') echo 'badge-success';
                                                    else echo 'badge-danger';
                                                ?>">
                                                    <?php 
                                                        if ($assignment['status'] == 'open') echo 'Terbuka';
                                                        elseif ($assignment['status'] == 'assigned') echo 'Ditugaskan';
                                                        elseif ($assignment['status'] == 'completed') echo 'Selesai';
                                                        else echo 'Dibatalkan';
                                                    ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Pembayaran Terbaru</h2>
                    <a href="<?php echo url('admin/payments.php'); ?>" class="btn btn-sm btn-outline">Lihat Semua</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_payments)): ?>
                        <div class="text-center py-4">
                            <p>Belum ada data pembayaran</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Penugasan</th>
                                        <th>Jumlah</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_payments as $payment): ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo url('admin/payments.php?action=view&id=' . $payment['id']); ?>">
                                                    #<?php echo $payment['id']; ?>
                                                </a>
                                            </td>
                                            <td><?php echo $payment['judul']; ?></td>
                                            <td><?php echo format_price($payment['amount']); ?></td>
                                            <td>
                                                <span class="badge <?php 
                                                    if ($payment['status'] == 'paid') echo 'badge-success';
                                                    elseif ($payment['status'] == 'pending') echo 'badge-warning';
                                                    elseif ($payment['status'] == 'expired') echo 'badge-secondary';
                                                    else echo 'badge-danger';
                                                ?>">
                                                    <?php echo format_payment_status($payment['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once '../templates/admin-footer.php';
?>