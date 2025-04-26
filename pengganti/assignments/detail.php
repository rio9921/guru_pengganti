<?php
/**
 * GuruSinergi - Assignment Detail Page
 * 
 * Halaman untuk melihat detail penugasan dan melamar (untuk guru)
 */

// Include file konfigurasi
require_once '../config/config.php';

// Include file database
require_once '../config/database.php';

// Include file functions
require_once '../includes/functions.php';

// Include file auth functions
require_once '../includes/auth-functions.php';

// Include file assignment functions
require_once '../includes/assignment-functions.php';

// Include file application functions
require_once '../includes/application-functions.php';

// Include file matching functions
require_once '../includes/matching-functions.php';

// Include file payment functions
require_once '../includes/payment-functions.php';

// Cek login user
check_access('all');

// Inisialisasi variabel
$current_user = get_app_current_user();
$is_guru = is_guru();
$is_sekolah = is_sekolah();
$is_verified = is_profile_verified($current_user);

// Ambil ID penugasan dari URL
$assignment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$assignment_id) {
    set_error_message('ID penugasan tidak valid.');
    redirect(url('assignments/browse.php'));
}

// Ambil data penugasan
$assignment = get_assignment($assignment_id);

if (!$assignment) {
    set_error_message('Penugasan tidak ditemukan.');
    redirect(url('assignments/browse.php'));
}

// Periksa akses ke penugasan
$is_owner = $is_sekolah && $assignment['sekolah_id'] == $current_user['id'];
$is_assigned_guru = $is_guru && $assignment['guru_id'] == $current_user['id'];
$can_apply = $is_guru && $is_verified && $assignment['status'] == 'open';

// Periksa apakah sudah melamar
$has_applied = false;
$application = null;

if ($is_guru) {
    $application = get_application_by_guru_assignment($current_user['id'], $assignment_id);
    $has_applied = !empty($application);
}

// Handle aksi melamar
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['apply']) && $can_apply && !$has_applied) {
    $pesan = sanitize($_POST['pesan']);
    
    $application_data = [
        'assignment_id' => $assignment_id,
        'guru_id' => $current_user['id'],
        'pesan' => $pesan
    ];
    
    $application_id = create_application($application_data);
    
    if ($application_id) {
        set_success_message('Lamaran Anda berhasil dikirim. Tunggu konfirmasi dari sekolah.');
        redirect(url('assignments/detail.php?id=' . $assignment_id));
    } else {
        set_error_message('Terjadi kesalahan saat mengirim lamaran. Silakan coba lagi.');
    }
}

// Handle aksi membatalkan lamaran
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cancel_application']) && $has_applied) {
    if (cancel_application($application['id'], $current_user['id'])) {
        set_success_message('Lamaran Anda berhasil dibatalkan.');
        redirect(url('assignments/detail.php?id=' . $assignment_id));
    } else {
        set_error_message('Terjadi kesalahan saat membatalkan lamaran. Silakan coba lagi.');
    }
}

// Handle aksi menyelesaikan penugasan
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['complete_assignment']) && $is_owner && $assignment['status'] == 'assigned') {
    if (update_assignment_status($assignment_id, 'completed')) {
        set_success_message('Penugasan berhasil diselesaikan.');
        redirect(url('assignments/detail.php?id=' . $assignment_id));
    } else {
        set_error_message('Terjadi kesalahan saat menyelesaikan penugasan. Silakan coba lagi.');
    }
}

// Handle aksi membatalkan penugasan
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cancel_assignment']) && $is_owner && in_array($assignment['status'], ['open', 'assigned'])) {
    if (update_assignment_status($assignment_id, 'canceled')) {
        set_success_message('Penugasan berhasil dibatalkan.');
        redirect(url('assignments/detail.php?id=' . $assignment_id));
    } else {
        set_error_message('Terjadi kesalahan saat membatalkan penugasan. Silakan coba lagi.');
    }
}

// Ambil daftar aplikasi/lamaran (jika pemilik)
$applications = [];
if ($is_owner) {
    $applications = get_applications_for_assignment($assignment_id);
}

// Ambil data pembayaran (jika pemilik atau guru yang ditugaskan)
$payment = null;
if ($is_owner || $is_assigned_guru) {
    $payment = get_payment_by_assignment($assignment_id);
}

// Analisis kecocokan (untuk guru)
$matching_score = 0;
if ($is_guru && $is_verified && $assignment['status'] == 'open') {
    // Data guru untuk matching
    $guru_data = [
        'mata_pelajaran' => $current_user['profile']['mata_pelajaran'],
        'tingkat_mengajar' => $current_user['profile']['tingkat_mengajar'],
        'rating' => $current_user['profile']['rating'],
        'total_reviews' => $current_user['profile']['total_reviews']
    ];
    
    // Data assignment untuk matching
    $assignment_data = [
        'mata_pelajaran' => $assignment['mata_pelajaran'],
        'tingkat_kelas' => $assignment['tingkat_kelas'],
        'kota' => $assignment['kota'],
        'provinsi' => $assignment['provinsi']
    ];
    
    $matching_score = calculate_matching_score($guru_data, $assignment_data);
}

// Set variabel untuk page title
$page_title = 'Detail Penugasan';

// Include header
include_once '../templates/header.php';
?>

<div class="page-header">
    <div class="page-header-content">
        <h1 class="page-title"><?php echo $assignment['judul']; ?></h1>
        <div class="assignment-meta">
            <span class="badge <?php 
                if ($assignment['status'] == 'open') echo 'badge-open';
                elseif ($assignment['status'] == 'assigned') echo 'badge-assigned';
                elseif ($assignment['status'] == 'completed') echo 'badge-completed';
                else echo 'badge-canceled';
            ?>">
                <?php echo format_assignment_status($assignment['status']); ?>
            </span>
            
            <span class="meta-item">
                <i class="fas fa-school"></i>
                <?php echo $assignment['nama_sekolah']; ?>
            </span>
            
            <span class="meta-item">
                <i class="fas fa-map-marker-alt"></i>
                <?php echo $assignment['kota'] . ', ' . $assignment['provinsi']; ?>
            </span>
            
            <span class="meta-item">
                <i class="fas fa-calendar-alt"></i>
                <?php echo date('d M Y', strtotime($assignment['tanggal_mulai'])); ?> - 
                <?php echo date('d M Y', strtotime($assignment['tanggal_selesai'])); ?>
            </span>
        </div>
    </div>
    
    <?php if ($is_guru && $can_apply && !$has_applied): ?>
    <div class="page-header-actions">
        <button class="btn btn-primary" data-toggle="modal" data-target="#applyModal">
            <i class="fas fa-paper-plane"></i> Lamar Penugasan
        </button>
    </div>
    <?php elseif ($is_guru && $has_applied && $application['status'] == 'pending'): ?>
    <div class="page-header-actions">
        <span class="badge badge-info">Lamaran Terkirim</span>
        <form method="post" action="" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan lamaran ini?');">
            <button type="submit" name="cancel_application" class="btn btn-outline">
                <i class="fas fa-times"></i> Batalkan Lamaran
            </button>
        </form>
    </div>
    <?php elseif ($is_owner && $assignment['status'] == 'open'): ?>
    <div class="page-header-actions">
        <a href="<?php echo url('assignments/edit.php?id=' . $assignment_id); ?>" class="btn btn-primary">
            <i class="fas fa-edit"></i> Edit Penugasan
        </a>
        <form method="post" action="" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan penugasan ini?');">
            <button type="submit" name="cancel_assignment" class="btn btn-danger">
                <i class="fas fa-trash"></i> Batalkan Penugasan
            </button>
        </form>
    </div>
    <?php elseif ($is_owner && $assignment['status'] == 'assigned'): ?>
    <div class="page-header-actions">
        <form method="post" action="" class="d-inline" onsubmit="return confirm('Apakah Anda yakin penugasan ini telah selesai?');">
            <button type="submit" name="complete_assignment" class="btn btn-success">
                <i class="fas fa-check-circle"></i> Selesaikan Penugasan
            </button>
        </form>
    </div>
    <?php endif; ?>
</div>

<div class="row">
    <div class="col-12 col-lg-8">
        <!-- Detail Penugasan -->
        <div class="card mb-4">
            <div class="card-header">
                <h2 class="card-title">Detail Penugasan</h2>
            </div>
            <div class="card-body">
                <div class="detail-section">
                    <h3 class="detail-title">Deskripsi</h3>
                    <div class="detail-content">
                        <?php if (!empty($assignment['deskripsi'])): ?>
                            <?php echo nl2br($assignment['deskripsi']); ?>
                        <?php else: ?>
                            <p class="text-muted">Tidak ada deskripsi.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="detail-section">
                    <h3 class="detail-title">Informasi Mengajar</h3>
                    <div class="detail-content">
                        <div class="info-item">
                            <span class="info-label">Mata Pelajaran:</span>
                            <span class="info-value"><?php echo $assignment['mata_pelajaran']; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Tingkat Kelas:</span>
                            <span class="info-value"><?php echo $assignment['tingkat_kelas']; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Tanggal Mengajar:</span>
                            <span class="info-value">
                                <?php echo date('d M Y', strtotime($assignment['tanggal_mulai'])); ?> - 
                                <?php echo date('d M Y', strtotime($assignment['tanggal_selesai'])); ?>
                                (<?php echo ceil((strtotime($assignment['tanggal_selesai']) - strtotime($assignment['tanggal_mulai'])) / (60 * 60 * 24)); ?> hari)
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Jam Mengajar:</span>
                            <span class="info-value">
                                <?php 
                                if (!empty($assignment['jam_mulai']) && !empty($assignment['jam_selesai'])) {
                                    echo date('H:i', strtotime($assignment['jam_mulai'])) . ' - ' . date('H:i', strtotime($assignment['jam_selesai']));
                                } else {
                                    echo 'Fleksibel (sesuai jadwal sekolah)';
                                }
                                ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Jadwal:</span>
                            <span class="info-value">
                                <?php echo $assignment['is_regular'] ? 'Rutin (minggunan)' : 'Tidak rutin'; ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="detail-section">
                    <h3 class="detail-title">Persyaratan</h3>
                    <div class="detail-content">
                        <?php if (!empty($assignment['persyaratan'])): ?>
                            <?php echo nl2br($assignment['persyaratan']); ?>
                        <?php else: ?>
                            <p class="text-muted">Tidak ada persyaratan khusus.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="detail-section">
                    <h3 class="detail-title">Honor/Gaji</h3>
                    <div class="detail-content">
                        <div class="price-tag"><?php echo format_price($assignment['gaji']); ?></div>
                        <p class="text-muted">
                            Total untuk keseluruhan periode penugasan 
                            (<?php echo ceil((strtotime($assignment['tanggal_selesai']) - strtotime($assignment['tanggal_mulai'])) / (60 * 60 * 24)); ?> hari).
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($is_guru && $is_verified && $assignment['status'] == 'open'): ?>
        <!-- Analisis Kecocokan (untuk guru) -->
        <div class="card mb-4">
            <div class="card-header">
                <h2 class="card-title">Analisis Kecocokan</h2>
            </div>
            <div class="card-body">
                <div class="matching-score">
                    <div class="score-gauge">
                        <div class="score-value" style="width: <?php echo $matching_score; ?>%"></div>
                    </div>
                    <div class="score-number"><?php echo round($matching_score); ?>%</div>
                    <div class="score-label">
                        <?php 
                        if ($matching_score >= 80) echo 'Sangat Cocok';
                        elseif ($matching_score >= 60) echo 'Cocok';
                        elseif ($matching_score >= 40) echo 'Cukup Cocok';
                        else echo 'Kurang Cocok';
                        ?>
                    </div>
                </div>
                
                <div class="matching-details mt-4">
                    <h3 class="h5">Detail Kecocokan:</h3>
                    <ul>
                        <?php 
                        $mp_match = strpos($current_user['profile']['mata_pelajaran'], $assignment['mata_pelajaran']) !== false || 
                                    strpos($current_user['profile']['mata_pelajaran'], 'Semua Mata Pelajaran') !== false;
                        $tk_match = strpos($current_user['profile']['tingkat_mengajar'], $assignment['tingkat_kelas']) !== false || 
                                    strpos($current_user['profile']['tingkat_mengajar'], 'Semua Tingkat') !== false;
                        ?>
                        <li>
                            <i class="<?php echo $mp_match ? 'fas fa-check-circle text-success' : 'fas fa-times-circle text-danger'; ?>"></i>
                            Mata Pelajaran: <?php echo $mp_match ? 'Sesuai' : 'Tidak Sesuai'; ?>
                        </li>
                        <li>
                            <i class="<?php echo $tk_match ? 'fas fa-check-circle text-success' : 'fas fa-times-circle text-danger'; ?>"></i>
                            Tingkat Kelas: <?php echo $tk_match ? 'Sesuai' : 'Tidak Sesuai'; ?>
                        </li>
                    </ul>
                    
                    <p class="mt-3">
                        <strong>Catatan:</strong> Analisis ini hanya menjadi panduan awal. 
                        Pihak sekolah akan mengevaluasi lamaran Anda secara menyeluruh.
                    </p>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($is_owner && !empty($applications)): ?>
        <!-- Daftar Lamaran (untuk pemilik) -->
        <div class="card mb-4">
            <div class="card-header">
                <h2 class="card-title">Lamaran Guru</h2>
                <span class="badge badge-primary"><?php echo count($applications); ?></span>
            </div>
            <div class="card-body">
                <?php if ($assignment['status'] == 'open'): ?>
                    <?php foreach ($applications as $app): ?>
                    <div class="application-item">
                        <div class="application-header">
                            <div class="guru-profile">
                                <?php if (!empty($app['profile_image'])): ?>
                                <img src="<?php echo $app['profile_image']; ?>" alt="<?php echo $app['full_name']; ?>" class="guru-avatar">
                                <?php else: ?>
                                <div class="guru-avatar-placeholder">
                                    <?php echo substr($app['full_name'], 0, 1); ?>
                                </div>
                                <?php endif; ?>
                                <div class="guru-info">
                                    <h3 class="guru-name"><?php echo $app['full_name']; ?></h3>
                                    <div class="guru-meta">
                                        <span><?php echo $app['mata_pelajaran']; ?></span> | 
                                        <span><?php echo $app['tingkat_mengajar']; ?></span>
                                    </div>
                                    <div class="guru-rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?php if ($i <= floor($app['rating'])): ?>
                                                <i class="fas fa-star"></i>
                                            <?php elseif ($i - 0.5 <= $app['rating']): ?>
                                                <i class="fas fa-star-half-alt"></i>
                                            <?php else: ?>
                                                <i class="far fa-star"></i>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                        <span class="rating-value"><?php echo number_format($app['rating'], 1); ?></span>
                                        <span class="reviews-count">(<?php echo $app['total_reviews']; ?> ulasan)</span>
                                    </div>
                                </div>
                            </div>
                            <div class="application-actions">
                                <a href="<?php echo url('applications/review.php?id=' . $app['id']); ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-eye"></i> Lihat Detail
                                </a>
                            </div>
                        </div>
                        <div class="application-content">
                            <?php if (!empty($app['pesan'])): ?>
                                <h4 class="message-title">Pesan:</h4>
                                <div class="message-content">
                                    <?php echo nl2br($app['pesan']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info">
                        Penugasan sudah tidak terbuka. Anda tidak dapat menerima lamaran baru.
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="col-12 col-lg-4">
        <!-- Informasi Sekolah -->
        <div class="card mb-4">
            <div class="card-header">
                <h2 class="card-title">Informasi Sekolah</h2>
            </div>
            <div class="card-body">
                <div class="school-profile">
                    <div class="school-avatar-placeholder">
                        <i class="fas fa-school"></i>
                    </div>
                    <h3 class="school-name"><?php echo $assignment['nama_sekolah']; ?></h3>
                    <div class="school-type"><?php echo $assignment['jenis_sekolah']; ?></div>
                </div>
                
                <div class="school-details mt-3">
                    <div class="detail-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div>
                            <span class="detail-label">Alamat:</span>
                            <span class="detail-value">
                                <?php echo $assignment['alamat_lengkap']; ?><br>
                                <?php echo $assignment['kecamatan'] . ', ' . $assignment['kota']; ?><br>
                                <?php echo $assignment['provinsi'] . ' ' . $assignment['kode_pos']; ?>
                            </span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-user"></i>
                        <div>
                            <span class="detail-label">Kontak:</span>
                            <span class="detail-value"><?php echo $assignment['contact_person']; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($assignment['status'] == 'assigned' || $assignment['status'] == 'completed'): ?>
        <!-- Informasi Guru yang Ditugaskan -->
        <div class="card mb-4">
            <div class="card-header">
                <h2 class="card-title">Guru yang Ditugaskan</h2>
            </div>
            <div class="card-body">
                <div class="guru-profile">
                    <div class="guru-avatar-placeholder">
                        <?php echo substr($assignment['guru_name'], 0, 1); ?>
                    </div>
                    <h3 class="guru-name"><?php echo $assignment['guru_name']; ?></h3>
                </div>
                
                <?php if (($is_owner || $is_assigned_guru) && isset($payment) && $payment): ?>
                <div class="payment-status mt-3">
                    <h4>Status Pembayaran:</h4>
                    <div class="status-badge <?php 
                        if ($payment['status'] == 'paid') echo 'status-paid';
                        elseif ($payment['status'] == 'pending') echo 'status-pending';
                        else echo 'status-failed';
                    ?>">
                        <?php echo format_payment_status($payment['status']); ?>
                    </div>
                    
                    <?php if ($is_owner && $payment['status'] == 'pending'): ?>
                    <div class="mt-3">
                        <a href="<?php echo url('payments/process.php?id=' . $payment['id']); ?>" class="btn btn-primary btn-sm btn-block">
                            <i class="fas fa-credit-card"></i> Bayar Sekarang
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Status Penugasan -->
        <div class="card mb-4">
            <div class="card-header">
                <h2 class="card-title">Status Penugasan</h2>
            </div>
            <div class="card-body">
                <div class="status-timeline">
                    <div class="timeline-item <?php echo $assignment['status'] != 'canceled' ? 'active' : ''; ?>">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <h3>Terbuka</h3>
                            <p>Penugasan menunggu lamaran guru.</p>
                            <?php if ($assignment['created_at']): ?>
                            <div class="timeline-date">
                                <?php echo date('d M Y, H:i', strtotime($assignment['created_at'])); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="timeline-item <?php echo in_array($assignment['status'], ['assigned', 'completed']) ? 'active' : ''; ?>">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <h3>Ditugaskan</h3>
                            <p>Guru telah dipilih dan penugasan sedang berlangsung.</p>
                            <?php if (in_array($assignment['status'], ['assigned', 'completed']) && $assignment['updated_at']): ?>
                            <div class="timeline-date">
                                <?php echo date('d M Y, H:i', strtotime($assignment['updated_at'])); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="timeline-item <?php echo $assignment['status'] == 'completed' ? 'active' : ''; ?>">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <h3>Selesai</h3>
                            <p>Penugasan telah selesai dilaksanakan.</p>
                            <?php if ($assignment['status'] == 'completed' && $assignment['updated_at']): ?>
                            <div class="timeline-date">
                                <?php echo date('d M Y, H:i', strtotime($assignment['updated_at'])); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($assignment['status'] == 'canceled'): ?>
                    <div class="timeline-item canceled active">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <h3>Dibatalkan</h3>
                            <p>Penugasan telah dibatalkan.</p>
                            <?php if ($assignment['updated_at']): ?>
                            <div class="timeline-date">
                                <?php echo date('d M Y, H:i', strtotime($assignment['updated_at'])); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Melamar -->
<?php if ($is_guru && $can_apply && !$has_applied): ?>
<div class="modal" id="applyModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Lamar Penugasan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="pesan">Pesan untuk Sekolah</label>
                        <textarea class="form-control" id="pesan" name="pesan" rows="5" placeholder="Ceritakan mengapa Anda cocok untuk penugasan ini..."></textarea>
                    </div>
                    
                    <div class="matching-score-modal">
                        <p>Tingkat kecocokan Anda dengan penugasan ini:</p>
                        <div class="score-gauge">
                            <div class="score-value" style="width: <?php echo $matching_score; ?>%"></div>
                        </div>
                        <div class="score-number"><?php echo round($matching_score); ?>%</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" name="apply" class="btn btn-primary">Kirim Lamaran</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
/* Detail Styling */
.assignment-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-top: 10px;
}

.meta-item {
    color: #555;
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    gap: 5px;
}

.detail-section {
    margin-bottom: 25px;
}

.detail-title {
    font-size: 1.2rem;
    margin-bottom: 10px;
    color: #333;
    border-bottom: 1px solid #eee;
    padding-bottom: 5px;
}

.detail-content {
    color: #444;
}

.info-item {
    margin-bottom: 8px;
}

.info-label {
    font-weight: 600;
    margin-right: 5px;
}

.price-tag {
    font-size: 1.5rem;
    font-weight: 600;
    color: #28a745;
    margin-bottom: 5px;
}

/* School Styling */
.school-profile {
    text-align: center;
    margin-bottom: 15px;
}

.school-avatar-placeholder {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background-color: #007bff;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    margin: 0 auto 15px;
}

.school-name {
    font-size: 1.2rem;
    margin-bottom: 5px;
}

.school-type {
    color: #666;
    font-size: 0.9rem;
}

.detail-item {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
}

.detail-item i {
    font-size: 18px;
    color: #555;
    margin-top: 3px;
}

.detail-label {
    font-weight: 600;
    display: block;
    margin-bottom: 3px;
}

/* Status Timeline */
.status-timeline {
    position: relative;
    margin: 0 0 0 20px;
}

.status-timeline:before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    height: 100%;
    width: 2px;
    background: #e9ecef;
}

.timeline-item {
    position: relative;
    padding-left: 35px;
    margin-bottom: 25px;
}

.timeline-marker {
    position: absolute;
    left: -9px;
    top: 0;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    border: 2px solid #e9ecef;
    background: white;
}

.timeline-item.active .timeline-marker {
    border-color: #007bff;
    background: #007bff;
}

.timeline-item.canceled .timeline-marker {
    border-color: #dc3545;
    background: #dc3545;
}

.timeline-item h3 {
    font-size: 1.1rem;
    margin-bottom: 5px;
}

.timeline-item p {
    margin-bottom: 5px;
    color: #666;
    font-size: 0.9rem;
}

.timeline-date {
    font-size: 0.8rem;
    color: #888;
}

/* Application Styling */
.application-item {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
}

.application-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
}

.guru-profile {
    display: flex;
    align-items: center;
    gap: 15px;
}

.guru-avatar, .guru-avatar-placeholder {
    width: 60px;
    height: 60px;
    border-radius: 50%;
}

.guru-avatar-placeholder {
    background-color: #6c757d;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.guru-name {
    font-size: 1.1rem;
    margin-bottom: 5px;
}

.guru-meta {
    font-size: 0.85rem;
    color: #666;
    margin-bottom: 5px;
}

.guru-rating {
    color: #ffc107;
    font-size: 0.9rem;
}

.rating-value, .reviews-count {
    color: #666;
    margin-left: 5px;
}

.message-title {
    font-size: 0.95rem;
    margin-bottom: 5px;
}

.message-content {
    background-color: #f8f9fa;
    padding: 10px;
    border-radius: 5px;
    font-size: 0.9rem;
}

/* Matching Score */
.matching-score {
    text-align: center;
    padding: 15px 0;
}

.score-gauge {
    width: 100%;
    height: 12px;
    background-color: #e9ecef;
    border-radius: 6px;
    margin-bottom: 10px;
    position: relative;
    overflow: hidden;
}

.score-value {
    height: 100%;
    border-radius: 6px;
    background-color: #007bff;
}

.score-number {
    font-size: 1.5rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 5px;
}

.score-label {
    font-size: 1.1rem;
    color: #555;
}

.matching-details ul {
    margin-top: 15px;
    padding-left: 20px;
}

.matching-details li {
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 10px;
}

/* Payment Status */
.payment-status {
    text-align: center;
}

.status-badge {
    display: inline-block;
    padding: 5px 15px;
    border-radius: 20px;
    font-weight: 600;
    margin-top: 10px;
}

.status-paid {
    background-color: #d4edda;
    color: #155724;
}

.status-pending {
    background-color: #fff3cd;
    color: #856404;
}

.status-failed {
    background-color: #f8d7da;
    color: #721c24;
}

/* Modal */
.matching-score-modal {
    margin-top: 20px;
    padding: 15px;
    background-color: #f8f9fa;
    border-radius: 5px;
    text-align: center;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle modal close/open
    const applyModal = document.getElementById('applyModal');
    const modalToggles = document.querySelectorAll('[data-toggle="modal"]');
    const modalCloses = document.querySelectorAll('[data-dismiss="modal"]');
    
    if (applyModal) {
        modalToggles.forEach(toggle => {
            toggle.addEventListener('click', function() {
                const target = this.getAttribute('data-target');
                if (target === '#applyModal') {
                    applyModal.style.display = 'block';
                    applyModal.classList.add('show');
                }
            });
        });
        
        modalCloses.forEach(close => {
            close.addEventListener('click', function() {
                applyModal.style.display = 'none';
                applyModal.classList.remove('show');
            });
        });
        
        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === applyModal) {
                applyModal.style.display = 'none';
                applyModal.classList.remove('show');
            }
        });
    }
});
</script>

<?php
// Include footer
include_once '../templates/footer.php';
?>