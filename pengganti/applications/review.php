<?php
/**
 * GuruSinergi - Review Application Page
 * 
 * Halaman untuk melihat detail lamaran dan menerima/menolak lamaran
 */

// Include file konfigurasi
require_once '../config/config.php';

// Include file database
require_once '../config/database.php';

// Include file functions
require_once '../includes/functions.php';

// Include file auth functions
require_once '../includes/auth-functions.php';

// Include file application functions
require_once '../includes/application-functions.php';

// Include file matching functions
require_once '../includes/matching-functions.php';

// Cek login user dan pastikan user adalah sekolah
check_access('sekolah');

// Inisialisasi variabel
$current_user = get_app_current_user();

// Ambil ID aplikasi dari URL
$application_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$application_id) {
    set_error_message('ID lamaran tidak valid.');
    redirect(url('dashboard.php'));
}

// Ambil data aplikasi
$application = get_application($application_id);

if (!$application) {
    set_error_message('Lamaran tidak ditemukan.');
    redirect(url('dashboard.php'));
}

// Periksa apakah sekolah adalah pemilik penugasan
if ($application['sekolah_id'] != $current_user['id']) {
    set_error_message('Anda tidak memiliki akses ke lamaran ini.');
    redirect(url('dashboard.php'));
}

// Handle aksi menerima lamaran
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accept'])) {
    if (accept_application($application_id, $current_user['id'])) {
        set_success_message('Lamaran berhasil diterima. Guru telah ditetapkan untuk penugasan ini.');
        redirect(url('assignments/detail.php?id=' . $application['assignment_id']));
    } else {
        set_error_message('Terjadi kesalahan saat menerima lamaran. Silakan coba lagi.');
    }
}

// Handle aksi menolak lamaran
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reject'])) {
    if (reject_application($application_id, $current_user['id'])) {
        set_success_message('Lamaran berhasil ditolak.');
        redirect(url('assignments/detail.php?id=' . $application['assignment_id']));
    } else {
        set_error_message('Terjadi kesalahan saat menolak lamaran. Silakan coba lagi.');
    }
}

// Analisis kecocokan guru dengan penugasan
$guru_data = [
    'mata_pelajaran' => $application['guru_mata_pelajaran'],
    'tingkat_mengajar' => $application['guru_tingkat_mengajar'],
    'rating' => $application['guru_rating'],
    'total_reviews' => 0 // Tidak ada data total_reviews
];

$assignment_data = [
    'mata_pelajaran' => $application['mata_pelajaran'],
    'tingkat_kelas' => $application['tingkat_kelas'],
    'kota' => '', // Tidak ada data kota
    'provinsi' => '' // Tidak ada data provinsi
];

$matching_score = calculate_matching_score($guru_data, $assignment_data);
$matching_analysis = analyze_matching_with_claude($guru_data, $assignment_data);

// Ambil data guru dari database
$conn = db_connect();
$stmt = $conn->prepare("
    SELECT u.*, g.* 
    FROM users u
    JOIN profiles_guru g ON u.id = g.user_id
    WHERE u.id = ?
");
$stmt->execute([$application['guru_id']]);
$guru = $stmt->fetch(PDO::FETCH_ASSOC);

// Set variabel untuk page title
$page_title = 'Review Lamaran';

// Include header
include_once '../templates/header.php';
?>

<div class="page-header">
    <div class="page-header-content">
        <h1 class="page-title">Review Lamaran</h1>
        <div class="assignment-meta">
            <span class="meta-item">
                <i class="fas fa-clipboard-list"></i>
                <span>Penugasan: </span>
                <a href="<?php echo url('assignments/detail.php?id=' . $application['assignment_id']); ?>">
                    <?php echo $application['judul']; ?>
                </a>
            </span>
            
            <span class="meta-item">
                <i class="fas fa-calendar-alt"></i>
                <span>Tanggal Lamaran: </span>
                <?php echo date('d M Y, H:i', strtotime($application['created_at'])); ?>
            </span>
            
            <span class="meta-item">
                <i class="fas fa-bookmark"></i>
                <span>Status: </span>
                <span class="status-badge <?php echo $application['status']; ?>">
                    <?php echo format_application_status($application['status']); ?>
                </span>
            </span>
        </div>
    </div>
    
    <?php if ($application['status'] == 'pending' && $application['assignment_status'] == 'open'): ?>
    <div class="page-header-actions">
        <form method="post" action="" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menerima lamaran ini? Guru ini akan ditetapkan untuk penugasan.');">
            <button type="submit" name="accept" class="btn btn-success">
                <i class="fas fa-check"></i> Terima Lamaran
            </button>
        </form>
        
        <form method="post" action="" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menolak lamaran ini?');">
            <button type="submit" name="reject" class="btn btn-danger">
                <i class="fas fa-times"></i> Tolak Lamaran
            </button>
        </form>
    </div>
    <?php endif; ?>
</div>

<div class="row">
    <div class="col-12 col-lg-8">
        <!-- Profil Guru -->
        <div class="card mb-4">
            <div class="card-header">
                <h2 class="card-title">Profil Guru</h2>
            </div>
            <div class="card-body">
                <div class="guru-profile">
                    <div class="guru-header">
                        <?php if (!empty($guru['profile_image'])): ?>
                        <img src="<?php echo $guru['profile_image']; ?>" alt="<?php echo $guru['full_name']; ?>" class="guru-avatar">
                        <?php else: ?>
                        <div class="guru-avatar-placeholder">
                            <?php echo substr($guru['full_name'], 0, 1); ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="guru-info">
                            <h3 class="guru-name"><?php echo $guru['full_name']; ?></h3>
                            <div class="guru-rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?php if ($i <= floor($guru['rating'])): ?>
                                        <i class="fas fa-star"></i>
                                    <?php elseif ($i - 0.5 <= $guru['rating']): ?>
                                        <i class="fas fa-star-half-alt"></i>
                                    <?php else: ?>
                                        <i class="far fa-star"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                <span class="rating-value"><?php echo number_format($guru['rating'], 1); ?></span>
                                <span class="reviews-count">(<?php echo $guru['total_reviews']; ?> ulasan)</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="guru-details mt-4">
                        <div class="detail-section">
                            <h3 class="detail-title">Mata Pelajaran</h3>
                            <div class="detail-content"><?php echo $guru['mata_pelajaran']; ?></div>
                        </div>
                        
                        <div class="detail-section">
                            <h3 class="detail-title">Tingkat Mengajar</h3>
                            <div class="detail-content"><?php echo $guru['tingkat_mengajar']; ?></div>
                        </div>
                        
                        <div class="detail-section">
                            <h3 class="detail-title">Pendidikan</h3>
                            <div class="detail-content">
                                <?php if (!empty($guru['pendidikan'])): ?>
                                    <?php echo nl2br($guru['pendidikan']); ?>
                                <?php else: ?>
                                    <p class="text-muted">Tidak ada informasi pendidikan.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="detail-section">
                            <h3 class="detail-title">Pengalaman Mengajar</h3>
                            <div class="detail-content">
                                <?php if (!empty($guru['pengalaman'])): ?>
                                    <?php echo nl2br($guru['pengalaman']); ?>
                                <?php else: ?>
                                    <p class="text-muted">Tidak ada informasi pengalaman mengajar.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="detail-section">
                            <h3 class="detail-title">Keahlian</h3>
                            <div class="detail-content">
                                <?php if (!empty($guru['keahlian'])): ?>
                                    <?php echo nl2br($guru['keahlian']); ?>
                                <?php else: ?>
                                    <p class="text-muted">Tidak ada informasi keahlian.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Pesan Lamaran -->
        <div class="card mb-4">
            <div class="card-header">
                <h2 class="card-title">Pesan Lamaran</h2>
            </div>
            <div class="card-body">
                <?php if (!empty($application['pesan'])): ?>
                    <div class="message-content">
                        <?php echo nl2br($application['pesan']); ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted">Tidak ada pesan dari guru.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-12 col-lg-4">
        <!-- Analisis Kecocokan -->
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
                    <div class="score-label"><?php echo $matching_analysis['recommendation']; ?></div>
                </div>
                
                <div class="matching-details mt-4">
                    <h3 class="h5">Analisis:</h3>
                    <p><?php echo $matching_analysis['explanation']; ?></p>
                    
                    <h3 class="h5 mt-3">Detail Kecocokan:</h3>
                    <ul>
                        <?php 
                        $mp_match = strpos($guru['mata_pelajaran'], $application['mata_pelajaran']) !== false || 
                                    strpos($guru['mata_pelajaran'], 'Semua Mata Pelajaran') !== false;
                        $tk_match = strpos($guru['tingkat_mengajar'], $application['tingkat_kelas']) !== false || 
                                    strpos($guru['tingkat_mengajar'], 'Semua Tingkat') !== false;
                        ?>
                        <li>
                            <i class="<?php echo $mp_match ? 'fas fa-check-circle text-success' : 'fas fa-times-circle text-danger'; ?>"></i>
                            Mata Pelajaran: <?php echo $mp_match ? 'Sesuai' : 'Tidak Sesuai'; ?>
                        </li>
                        <li>
                            <i class="<?php echo $tk_match ? 'fas fa-check-circle text-success' : 'fas fa-times-circle text-danger'; ?>"></i>
                            Tingkat Kelas: <?php echo $tk_match ? 'Sesuai' : 'Tidak Sesuai'; ?>
                        </li>
                        <li>
                            <i class="fas fa-star text-warning"></i>
                            Rating: <?php echo number_format($guru['rating'], 1); ?> / 5.0
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Detail Penugasan -->
        <div class="card mb-4">
            <div class="card-header">
                <h2 class="card-title">Detail Penugasan</h2>
            </div>
            <div class="card-body">
                <div class="assignment-details">
                    <div class="detail-item">
                        <span class="detail-label">Judul:</span>
                        <span class="detail-value"><?php echo $application['judul']; ?></span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Mata Pelajaran:</span>
                        <span class="detail-value"><?php echo $application['mata_pelajaran']; ?></span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Tingkat Kelas:</span>
                        <span class="detail-value"><?php echo $application['tingkat_kelas']; ?></span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Tanggal Mengajar:</span>
                        <span class="detail-value">
                            <?php echo date('d M Y', strtotime($application['tanggal_mulai'])); ?> - 
                            <?php echo date('d M Y', strtotime($application['tanggal_selesai'])); ?>
                        </span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Gaji/Honor:</span>
                        <span class="detail-value price-tag"><?php echo format_price($application['gaji']); ?></span>
                    </div>
                </div>
                
                <div class="mt-3 text-center">
                    <a href="<?php echo url('assignments/detail.php?id=' . $application['assignment_id']); ?>" class="btn btn-outline">
                        <i class="fas fa-external-link-alt"></i> Lihat Detail Penugasan
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Lamaran Lain -->
        <div class="card mb-4">
            <div class="card-header">
                <h2 class="card-title">Lamaran Lain</h2>
            </div>
            <div class="card-body">
                <?php
                $conn = db_connect();
                $stmt = $conn->prepare("
                    SELECT app.id, u.full_name, g.rating, g.mata_pelajaran, g.tingkat_mengajar
                    FROM applications app
                    JOIN users u ON app.guru_id = u.id
                    JOIN profiles_guru g ON u.id = g.user_id
                    WHERE app.assignment_id = ? AND app.id != ?
                    ORDER BY app.created_at DESC
                    LIMIT 5
                ");
                $stmt->execute([$application['assignment_id'], $application_id]);
                $other_applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>
                
                <?php if (!empty($other_applications)): ?>
                    <div class="other-applications">
                        <?php foreach ($other_applications as $other_app): ?>
                        <div class="other-app-item">
                            <div class="other-app-info">
                                <h3><?php echo $other_app['full_name']; ?></h3>
                                <div class="other-app-meta">
                                    <div class="other-app-rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?php if ($i <= floor($other_app['rating'])): ?>
                                                <i class="fas fa-star"></i>
                                            <?php elseif ($i - 0.5 <= $other_app['rating']): ?>
                                                <i class="fas fa-star-half-alt"></i>
                                            <?php else: ?>
                                                <i class="far fa-star"></i>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                        <span><?php echo number_format($other_app['rating'], 1); ?></span>
                                    </div>
                                    <div class="other-app-subjects">
                                        <?php echo $other_app['mata_pelajaran']; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="other-app-action">
                                <a href="<?php echo url('applications/review.php?id=' . $other_app['id']); ?>" class="btn btn-sm btn-outline">Review</a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center">Tidak ada lamaran lain untuk penugasan ini.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
/* Guru Profile */
.guru-profile {
    margin-bottom: 20px;
}

.guru-header {
    display: flex;
    align-items: center;
    gap: 20px;
}

.guru-avatar, .guru-avatar-placeholder {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    object-fit: cover;
}

.guru-avatar-placeholder {
    background-color: #6c757d;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 36px;
}

.guru-name {
    font-size: 1.5rem;
    margin-bottom: 5px;
}

.guru-rating {
    color: #ffc107;
    font-size: 1rem;
}

.rating-value, .reviews-count {
    color: #666;
    margin-left: 5px;
}

.detail-section {
    margin-bottom: 20px;
}

.detail-title {
    font-size: 1.1rem;
    margin-bottom: 10px;
    color: #333;
    border-bottom: 1px solid #eee;
    padding-bottom: 5px;
}

/* Message Content */
.message-content {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    border-left: 4px solid #007bff;
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

/* Assignment Details */
.assignment-details {
    margin-bottom: 20px;
}

.detail-item {
    margin-bottom: 10px;
}

.detail-label {
    font-weight: 600;
    display: block;
    margin-bottom: 3px;
    color: #555;
}

.detail-value {
    color: #333;
}

.price-tag {
    color: #28a745;
    font-weight: 600;
}

/* Other Applications */
.other-applications {
    margin-top: 10px;
}

.other-app-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #eee;
}

.other-app-item:last-child {
    border-bottom: none;
}

.other-app-info h3 {
    font-size: 1rem;
    margin-bottom: 3px;
}

.other-app-meta {
    font-size: 0.85rem;
    color: #666;
}

.other-app-rating {
    color: #ffc107;
    margin-bottom: 3px;
}

.other-app-subjects {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 200px;
}

/* Status Badge */
.status-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-badge.pending {
    background-color: #fff3cd;
    color: #856404;
}

.status-badge.accepted {
    background-color: #d4edda;
    color: #155724;
}

.status-badge.rejected {
    background-color: #f8d7da;
    color: #721c24;
}

/* Page header overrides */
.page-header {
    margin-bottom: 30px;
}

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

.page-header-actions {
    margin-top: 20px;
    display: flex;
    gap: 10px;
}
</style>

<?php
// Include footer
include_once '../templates/footer.php';
?>