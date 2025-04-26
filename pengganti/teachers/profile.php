<?php
/**
 * GuruSinergi - Teacher Profile Page
 * 
 * Halaman untuk melihat profil guru
 */

// Include file konfigurasi
require_once '../config/config.php';

// Include file database
require_once '../config/database.php';

// Include file functions
require_once '../includes/functions.php';

// Include file auth functions
require_once '../includes/auth-functions.php';

// Cek login user
if (is_logged_in()) {
    $current_user = get_app_current_user();
    $is_sekolah = $current_user['user_type'] == 'sekolah';
} else {
    $current_user = null;
    $is_sekolah = false;
}

// Ambil ID guru dari URL
$teacher_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$teacher_id) {
    set_error_message('ID guru tidak valid.');
    redirect(url('teachers/browse.php'));
}

// Ambil data guru dari database
$conn = db_connect();
$stmt = $conn->prepare("
    SELECT u.*, g.* 
    FROM users u
    JOIN profiles_guru g ON u.id = g.user_id
    WHERE u.id = ? AND u.user_type = 'guru' AND g.status_verifikasi = 'verified'
");
$stmt->execute([$teacher_id]);
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$teacher) {
    set_error_message('Profil guru tidak ditemukan atau belum diverifikasi.');
    redirect(url('teachers/browse.php'));
}

// Ambil riwayat penugasan guru
$stmt = $conn->prepare("
    SELECT a.*, u.full_name as sekolah_name, s.nama_sekolah
    FROM assignments a
    JOIN users u ON a.sekolah_id = u.id
    JOIN profiles_sekolah s ON u.id = s.user_id
    WHERE a.guru_id = ? AND a.status = 'completed'
    ORDER BY a.tanggal_selesai DESC
    LIMIT 5
");
$stmt->execute([$teacher_id]);
$completed_assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil ulasan untuk guru
$stmt = $conn->prepare("
    SELECT r.*, u.full_name as reviewer_name, a.judul as assignment_title
    FROM reviews r
    JOIN users u ON r.reviewer_id = u.id
    JOIN assignments a ON r.assignment_id = a.id
    WHERE r.reviewee_id = ?
    ORDER BY r.created_at DESC
    LIMIT 10
");
$stmt->execute([$teacher_id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set variabel untuk page title
$page_title = 'Profil Guru: ' . $teacher['full_name'];

// Include header
include_once '../templates/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Profil Guru</h1>
    <div class="teacher-meta">
        <?php if ($teacher['is_available']): ?>
        <span class="badge badge-available">Tersedia</span>
        <?php else: ?>
        <span class="badge badge-unavailable">Tidak Tersedia</span>
        <?php endif; ?>
    </div>
</div>

<div class="row">
    <div class="col-12 col-lg-8">
        <!-- Profil Guru -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="teacher-profile">
                    <div class="profile-header">
                        <?php if (!empty($teacher['profile_image'])): ?>
                        <img src="<?php echo $teacher['profile_image']; ?>" alt="<?php echo $teacher['full_name']; ?>" class="profile-avatar">
                        <?php else: ?>
                        <div class="profile-avatar-placeholder">
                            <?php echo substr($teacher['full_name'], 0, 1); ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="profile-info">
                            <h2 class="profile-name"><?php echo $teacher['full_name']; ?></h2>
                            <div class="profile-rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?php if ($i <= floor($teacher['rating'])): ?>
                                        <i class="fas fa-star"></i>
                                    <?php elseif ($i - 0.5 <= $teacher['rating']): ?>
                                        <i class="fas fa-star-half-alt"></i>
                                    <?php else: ?>
                                        <i class="far fa-star"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                <span class="rating-value"><?php echo number_format($teacher['rating'], 1); ?></span>
                                <span class="reviews-count">(<?php echo $teacher['total_reviews']; ?> ulasan)</span>
                            </div>
                        </div>
                        
                        <?php if ($is_sekolah): ?>
                        <div class="profile-actions">
                            <a href="<?php echo url('assignments/create.php?teacher_id=' . $teacher_id); ?>" class="btn btn-primary">
                                <i class="fas fa-briefcase"></i> Buat Penugasan
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="profile-details">
                        <div class="detail-section">
                            <h3 class="section-title">Informasi Umum</h3>
                            <div class="info-grid">
                                <div class="info-item">
                                    <i class="fas fa-book"></i>
                                    <div>
                                        <span class="info-label">Mata Pelajaran</span>
                                        <span class="info-value"><?php echo $teacher['mata_pelajaran']; ?></span>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <i class="fas fa-school"></i>
                                    <div>
                                        <span class="info-label">Tingkat Mengajar</span>
                                        <span class="info-value"><?php echo $teacher['tingkat_mengajar']; ?></span>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <i class="fas fa-history"></i>
                                    <div>
                                        <span class="info-label">Penugasan Selesai</span>
                                        <span class="info-value"><?php echo count($completed_assignments); ?></span>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <i class="fas fa-calendar-alt"></i>
                                    <div>
                                        <span class="info-label">Bergabung Sejak</span>
                                        <span class="info-value"><?php echo date('d M Y', strtotime($teacher['created_at'])); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="detail-section">
                            <h3 class="section-title">Pendidikan</h3>
                            <div class="section-content">
                                <?php if (!empty($teacher['pendidikan'])): ?>
                                    <?php echo nl2br($teacher['pendidikan']); ?>
                                <?php else: ?>
                                    <p class="text-muted">Tidak ada informasi pendidikan.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="detail-section">
                            <h3 class="section-title">Pengalaman Mengajar</h3>
                            <div class="section-content">
                                <?php if (!empty($teacher['pengalaman'])): ?>
                                    <?php echo nl2br($teacher['pengalaman']); ?>
                                <?php else: ?>
                                    <p class="text-muted">Tidak ada informasi pengalaman mengajar.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="detail-section">
                            <h3 class="section-title">Keahlian</h3>
                            <div class="section-content">
                                <?php if (!empty($teacher['keahlian'])): ?>
                                    <?php echo nl2br($teacher['keahlian']); ?>
                                <?php else: ?>
                                    <p class="text-muted">Tidak ada informasi keahlian.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($teacher['sertifikasi'])): ?>
                        <div class="detail-section">
                            <h3 class="section-title">Sertifikasi</h3>
                            <div class="section-content">
                                <?php echo nl2br($teacher['sertifikasi']); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Riwayat Penugasan -->
        <div class="card mb-4">
            <div class="card-header">
                <h2 class="card-title">Riwayat Penugasan</h2>
            </div>
            <div class="card-body">
                <?php if (empty($completed_assignments)): ?>
                <p class="text-muted text-center">Belum ada riwayat penugasan.</p>
                <?php else: ?>
                <div class="assignment-history">
                    <?php foreach ($completed_assignments as $assignment): ?>
                    <div class="history-item">
                        <div class="history-content">
                            <h3 class="history-title"><?php echo $assignment['judul']; ?></h3>
                            <div class="history-meta">
                                <span><i class="fas fa-school"></i> <?php echo $assignment['nama_sekolah']; ?></span>
                                <span><i class="fas fa-book"></i> <?php echo $assignment['mata_pelajaran']; ?></span>
                            </div>
                            <div class="history-period">
                                <i class="fas fa-calendar-alt"></i> 
                                <?php echo date('d M Y', strtotime($assignment['tanggal_mulai'])); ?> - 
                                <?php echo date('d M Y', strtotime($assignment['tanggal_selesai'])); ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Ulasan -->
        <div class="card mb-4">
            <div class="card-header">
                <h2 class="card-title">Ulasan</h2>
            </div>
            <div class="card-body">
                <?php if (empty($reviews)): ?>
                <p class="text-muted text-center">Belum ada ulasan.</p>
                <?php else: ?>
                <div class="reviews-list">
                    <?php foreach ($reviews as $review): ?>
                    <div class="review-item">
                        <div class="review-header">
                            <div class="reviewer-info">
                                <div class="reviewer-avatar">
                                    <?php echo substr($review['reviewer_name'], 0, 1); ?>
                                </div>
                                <div class="reviewer-details">
                                    <div class="reviewer-name"><?php echo $review['reviewer_name']; ?></div>
                                    <div class="review-date"><?php echo date('d M Y', strtotime($review['created_at'])); ?></div>
                                </div>
                            </div>
                            <div class="review-rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?php if ($i <= $review['rating']): ?>
                                        <i class="fas fa-star"></i>
                                    <?php else: ?>
                                        <i class="far fa-star"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div class="review-assignment">
                            <i class="fas fa-briefcase"></i> Penugasan: <?php echo $review['assignment_title']; ?>
                        </div>
                        <div class="review-content">
                            <?php echo nl2br($review['comment']); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-12 col-lg-4">
        <!-- Kartu Kontak -->
        <div class="card mb-4">
            <div class="card-header">
                <h2 class="card-title">Kontak</h2>
            </div>
            <div class="card-body">
                <div class="contact-info">
                    <?php if ($is_sekolah): ?>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <span class="contact-label">Email</span>
                            <span class="contact-value"><?php echo $teacher['email']; ?></span>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <div>
                            <span class="contact-label">Telepon</span>
                            <span class="contact-value"><?php echo $teacher['phone']; ?></span>
                        </div>
                    </div>
                    <?php else: ?>
                    <p class="text-center">
                        Untuk melihat informasi kontak, silakan <a href="<?php echo url('login.php'); ?>">masuk</a> sebagai sekolah.
                    </p>
                    <?php endif; ?>
                </div>
                
                <?php if ($is_sekolah): ?>
                <div class="contact-buttons mt-4">
                    <a href="mailto:<?php echo $teacher['email']; ?>" class="btn btn-outline w-100 mb-2">
                        <i class="fas fa-envelope"></i> Kirim Email
                    </a>
                    <a href="tel:<?php echo $teacher['phone']; ?>" class="btn btn-outline w-100">
                        <i class="fas fa-phone"></i> Hubungi
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Ketersediaan -->
        <div class="card mb-4">
            <div class="card-header">
                <h2 class="card-title">Ketersediaan</h2>
            </div>
            <div class="card-body">
                <div class="availability-status <?php echo $teacher['is_available'] ? 'available' : 'unavailable'; ?>">
                    <div class="status-icon">
                        <i class="fas <?php echo $teacher['is_available'] ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                    </div>
                    <div class="status-text">
                        <?php if ($teacher['is_available']): ?>
                        <h3>Tersedia untuk Mengajar</h3>
                        <p>Guru ini tersedia untuk menerima penugasan baru.</p>
                        <?php else: ?>
                        <h3>Tidak Tersedia</h3>
                        <p>Guru ini sedang tidak tersedia untuk menerima penugasan baru.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($is_sekolah && $teacher['is_available']): ?>
                <div class="mt-4 text-center">
                    <a href="<?php echo url('assignments/create.php?teacher_id=' . $teacher_id); ?>" class="btn btn-primary w-100">
                        <i class="fas fa-briefcase"></i> Buat Penugasan
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
/* Profile Header */
.teacher-meta {
    display: flex;
    gap: 10px;
    margin-top: 10px;
}

.badge-available {
    background-color: #28a745;
    color: white;
}

.badge-unavailable {
    background-color: #dc3545;
    color: white;
}

/* Profile Styling */
.profile-header {
    display: flex;
    gap: 20px;
    margin-bottom: 30px;
    align-items: center;
}

.profile-avatar, .profile-avatar-placeholder {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
}

.profile-avatar-placeholder {
    background-color: #6c757d;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 48px;
}

.profile-info {
    flex: 1;
}

.profile-name {
    font-size: 1.8rem;
    margin-bottom: 5px;
}

.profile-rating {
    color: #ffc107;
    font-size: 1rem;
    margin-bottom: 10px;
}

.rating-value, .reviews-count {
    color: #666;
    margin-left: 5px;
}

.profile-details {
    margin-top: 20px;
}

.detail-section {
    margin-bottom: 25px;
}

.section-title {
    font-size: 1.2rem;
    margin-bottom: 15px;
    color: #333;
    border-bottom: 1px solid #eee;
    padding-bottom: 5px;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
}

.info-item {
    display: flex;
    gap: 10px;
}

.info-item i {
    color: #007bff;
    font-size: 18px;
    width: 20px;
    text-align: center;
    margin-top: 3px;
}

.info-label {
    font-weight: 600;
    display: block;
    margin-bottom: 3px;
    color: #555;
}

.info-value {
    color: #333;
}

.section-content {
    line-height: 1.6;
    color: #444;
}

/* Contact Info */
.contact-info {
    margin-bottom: 20px;
}

.contact-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 15px;
}

.contact-item i {
    color: #007bff;
    font-size: 18px;
    width: 20px;
    text-align: center;
    margin-top: 3px;
}

.contact-label {
    font-weight: 600;
    display: block;
    margin-bottom: 3px;
    color: #555;
}

.contact-value {
    color: #333;
    word-break: break-word;
}

/* Availability */
.availability-status {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    border-radius: 8px;
}

.availability-status.available {
    background-color: #d4edda;
}

.availability-status.unavailable {
    background-color: #f8d7da;
}

.status-icon {
    font-size: 36px;
}

.availability-status.available .status-icon {
    color: #28a745;
}

.availability-status.unavailable .status-icon {
    color: #dc3545;
}

.status-text h3 {
    font-size: 1.1rem;
    margin-bottom: 5px;
    color: #333;
}

.status-text p {
    margin-bottom: 0;
    color: #555;
    font-size: 0.9rem;
}

/* Assignment History */
.assignment-history {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.history-item {
    border-left: 3px solid #007bff;
    padding-left: 15px;
    padding-bottom: 10px;
}

.history-title {
    font-size: 1.1rem;
    margin-bottom: 5px;
}

.history-meta, .history-period {
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 5px;
}

.history-meta span {
    margin-right: 15px;
}

.history-meta i, .history-period i {
    color: #007bff;
    width: 20px;
}

/* Reviews */
.reviews-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.review-item {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 15px;
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 10px;
}

.reviewer-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.reviewer-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #6c757d;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

.reviewer-name {
    font-weight: 600;
    margin-bottom: 3px;
}

.review-date {
    font-size: 0.85rem;
    color: #666;
}

.review-rating {
    color: #ffc107;
}

.review-assignment {
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 10px;
}

.review-assignment i {
    color: #007bff;
}

.review-content {
    color: #444;
    line-height: 1.5;
}
</style>

<?php
// Include footer
include_once '../templates/footer.php';
?>