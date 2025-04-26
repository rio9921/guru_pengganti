<?php
// /schools/dashboard.php
// Dashboard untuk sekolah melihat status permintaan guru pengganti

// Mulai sesi
session_start();

// Cek apakah pengguna sudah login dan adalah sekolah
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'school') {
    // Redirect ke halaman login jika belum login atau bukan sekolah
    header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Ambil ID profil sekolah
$user_id = $_SESSION['user_id'];

// Include file konfigurasi dan koneksi database
require_once '../config/config.php';
require_once '../config/database.php';

// Include file fungsi helper
require_once '../includes/functions.php';
require_once '../includes/school-functions.php';

// Ambil data profil sekolah
$school_profile = get_school_profile_by_user_id($user_id);

// Jika profil belum lengkap, redirect untuk melengkapi profil
if (!$school_profile || !is_profile_complete($school_profile)) {
    header('Location: /schools/profile.php?incomplete=1');
    exit;
}

// Ambil status verifikasi sekolah
$verification_status = $school_profile['verification_status'];

// Ambil daftar permintaan guru pengganti yang sudah dibuat
$assignments = get_school_assignments($school_profile['id']);

// Ambil jumlah permintaan yang masih aktif, sudah terisi, dan total
$active_assignments = 0;
$filled_assignments = 0;
$completed_assignments = 0;

foreach ($assignments as $assignment) {
    if ($assignment['status'] == 'open') {
        $active_assignments++;
    } elseif ($assignment['status'] == 'in_progress') {
        $filled_assignments++;
    } elseif ($assignment['status'] == 'completed') {
        $completed_assignments++;
    }
}

// Ambil notifikasi terbaru untuk sekolah
$notifications = get_user_notifications($user_id, 5); // Ambil 5 notifikasi terbaru

// Ambil pembayaran terbaru
$payments = get_school_payments($school_profile['id'], 5);

// Include header
include('../templates/header.php');
?>

<!-- Konten Dashboard Sekolah -->
<div class="container mt-4">
    <!-- Alert jika verifikasi masih pending -->
    <?php if ($verification_status === 'pending'): ?>
    <div class="alert alert-warning" role="alert">
        <h4 class="alert-heading">Status Verifikasi: Menunggu Verifikasi</h4>
        <p>Akun sekolah Anda sedang dalam proses verifikasi. Beberapa fitur mungkin terbatas sampai verifikasi selesai.</p>
        <hr>
        <p class="mb-0">Tim kami akan memproses verifikasi dalam 1-2 hari kerja. Terima kasih atas kesabaran Anda.</p>
    </div>
    <?php elseif ($verification_status === 'rejected'): ?>
    <div class="alert alert-danger" role="alert">
        <h4 class="alert-heading">Status Verifikasi: Ditolak</h4>
        <p>Mohon maaf, verifikasi akun sekolah Anda tidak disetujui. Silakan perbarui informasi dan dokumen Anda.</p>
        <hr>
        <p class="mb-0">Untuk bantuan lebih lanjut, silakan hubungi tim support di 089513005831.</p>
    </div>
    <?php endif; ?>

    <h1 class="mb-4">Dashboard Sekolah</h1>
    
    <div class="row">
        <!-- Kolom Kiri - Ringkasan & Statistik -->
        <div class="col-md-8">
            <!-- Kartu Ringkasan -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Ringkasan Permintaan Guru Pengganti</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <h3><?php echo $active_assignments; ?></h3>
                            <p>Permintaan Aktif</p>
                        </div>
                        <div class="col-md-4 text-center">
                            <h3><?php echo $filled_assignments; ?></h3>
                            <p>Sudah Terisi</p>
                        </div>
                        <div class="col-md-4 text-center">
                            <h3><?php echo $completed_assignments; ?></h3>
                            <p>Selesai</p>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-center">
                    <a href="/schools/requests.php" class="btn btn-primary">Lihat Semua Permintaan</a>
                    <a href="/schools/create-request.php" class="btn btn-success">Buat Permintaan Baru</a>
                </div>
            </div>

            <!-- Permintaan Terakhir -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Permintaan Terbaru</h5>
                </div>
                <div class="card-body">
                    <?php if (count($assignments) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Judul</th>
                                        <th>Mata Pelajaran</th>
                                        <th>Tanggal Mulai</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($assignments, 0, 5) as $assignment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($assignment['title']); ?></td>
                                        <td><?php echo htmlspecialchars($assignment['subject']); ?></td>
                                        <td><?php echo format_date($assignment['start_date']); ?></td>
                                        <td>
                                            <?php
                                            switch ($assignment['status']) {
                                                case 'open':
                                                    echo '<span class="badge bg-warning">Mencari Guru</span>';
                                                    break;
                                                case 'in_progress':
                                                    echo '<span class="badge bg-success">Sudah Terisi</span>';
                                                    break;
                                                case 'completed':
                                                    echo '<span class="badge bg-secondary">Selesai</span>';
                                                    break;
                                                case 'cancelled':
                                                    echo '<span class="badge bg-danger">Dibatalkan</span>';
                                                    break;
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <a href="/assignments/detail.php?id=<?php echo $assignment['id']; ?>" class="btn btn-sm btn-info">Detail</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center p-4">
                            <p>Anda belum membuat permintaan guru pengganti.</p>
                            <a href="/schools/create-request.php" class="btn btn-primary">Buat Permintaan Sekarang</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Pembayaran Terakhir -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Pembayaran Terbaru</h5>
                </div>
                <div class="card-body">
                    <?php if (count($payments) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>No. Referensi</th>
                                        <th>Permintaan</th>
                                        <th>Jumlah</th>
                                        <th>Status</th>
                                        <th>Tanggal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($payment['tripay_merchant_ref']); ?></td>
                                        <td><?php echo htmlspecialchars(get_assignment_title($payment['assignment_id'])); ?></td>
                                        <td>Rp <?php echo number_format($payment['amount'], 0, ',', '.'); ?></td>
                                        <td>
                                            <?php
                                            switch ($payment['status']) {
                                                case 'pending':
                                                    echo '<span class="badge bg-warning">Menunggu Pembayaran</span>';
                                                    break;
                                                case 'paid':
                                                    echo '<span class="badge bg-success">Dibayar</span>';
                                                    break;
                                                case 'failed':
                                                    echo '<span class="badge bg-danger">Gagal</span>';
                                                    break;
                                                case 'expired':
                                                    echo '<span class="badge bg-secondary">Kadaluarsa</span>';
                                                    break;
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo format_datetime($payment['created_at']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center p-4">
                            <p>Belum ada riwayat pembayaran.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan - Notifikasi & Info Profil -->
        <div class="col-md-4">
            <!-- Profil Sekolah -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Profil Sekolah</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <img src="<?php echo get_school_logo($school_profile['id']) ?: '../assets/img/school-placeholder.png'; ?>" 
                             alt="Logo Sekolah" class="img-thumbnail" style="max-width: 100px;">
                    </div>
                    <h5 class="text-center"><?php echo htmlspecialchars($school_profile['school_name']); ?></h5>
                    <p class="text-center">
                        <span class="badge <?php echo $verification_status === 'verified' ? 'bg-success' : ($verification_status === 'rejected' ? 'bg-danger' : 'bg-warning'); ?>">
                            <?php 
                            echo $verification_status === 'verified' ? 'Terverifikasi' : 
                                 ($verification_status === 'rejected' ? 'Verifikasi Ditolak' : 'Menunggu Verifikasi'); 
                            ?>
                        </span>
                    </p>
                    <hr>
                    <div class="mb-2">
                        <small class="text-muted">Alamat:</small>
                        <p><?php echo htmlspecialchars($school_profile['address']); ?></p>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Kontak:</small>
                        <p><?php echo htmlspecialchars($school_profile['contact_person']); ?> - <?php echo htmlspecialchars($school_profile['contact_phone']); ?></p>
                    </div>
                    <div class="text-center mt-3">
                        <a href="/schools/profile.php" class="btn btn-sm btn-outline-primary">Edit Profil</a>
                    </div>
                </div>
            </div>

            <!-- Notifikasi Terbaru -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Notifikasi Terbaru</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (count($notifications) > 0): ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($notifications as $notification): ?>
                            <li class="list-group-item <?php echo $notification['is_read'] ? '' : 'bg-light'; ?>">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                        <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                                        <small class="text-muted"><?php echo time_elapsed_string($notification['created_at']); ?></small>
                                    </div>
                                    <?php if (!$notification['is_read']): ?>
                                    <span class="badge rounded-pill bg-primary">Baru</span>
                                    <?php endif; ?>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="text-center p-4">
                            <p>Tidak ada notifikasi baru.</p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer text-center">
                    <a href="/notifications.php" class="btn btn-sm btn-outline-primary">Lihat Semua Notifikasi</a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Menu Cepat</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <a href="/schools/create-request.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-plus-circle"></i> Buat Permintaan Guru
                        </a>
                        <a href="/teachers/browse.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-search"></i> Cari Guru Tersedia
                        </a>
                        <a href="/chat/inbox.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-comments"></i> Pesan
                            <?php 
                            $unread_count = get_unread_messages_count($user_id);
                            if ($unread_count > 0): 
                            ?>
                            <span class="badge rounded-pill bg-danger float-end"><?php echo $unread_count; ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="/materials.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-book"></i> Materi Pembelajaran
                        </a>
                        <a href="/faq.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-question-circle"></i> Bantuan & FAQ
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include('../templates/footer.php');
?>