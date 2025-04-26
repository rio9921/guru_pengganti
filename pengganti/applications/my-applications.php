<?php
/**
 * GuruSinergi - My Applications Page
 * 
 * Halaman untuk melihat daftar lamaran yang sudah dikirim oleh guru
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

// Cek login user dan pastikan user adalah guru
check_access('guru');

// Inisialisasi variabel
$current_user = get_app_current_user();

// Parameter filter
$status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$sort = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'created_desc';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Filter parameter untuk aplikasi
$filter_params = [
    'guru_id' => $current_user['id']
];

if (!empty($status)) {
    $filter_params['status'] = $status;
}

// Set sorting
if ($sort == 'created_desc') {
    $filter_params['sort'] = 'created_desc';
} elseif ($sort == 'created_asc') {
    $filter_params['sort'] = 'created_asc';
}

// Dapatkan daftar aplikasi/lamaran
$applications = get_applications($filter_params, $limit, $offset);

// Dapatkan total jumlah aplikasi untuk pagination
$total_applications = get_total_applications($filter_params);
$total_pages = ceil($total_applications / $limit);

// Dapatkan statistik lamaran
$stats = [
    'total' => get_total_applications(['guru_id' => $current_user['id']]),
    'pending' => get_total_applications(['guru_id' => $current_user['id'], 'status' => 'pending']),
    'accepted' => get_total_applications(['guru_id' => $current_user['id'], 'status' => 'accepted']),
    'rejected' => get_total_applications(['guru_id' => $current_user['id'], 'status' => 'rejected'])
];

// Set variabel untuk page title
$page_title = 'Lamaran Saya';

// Include header
include_once '../templates/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Lamaran Saya</h1>
    <p class="page-description">Pantau status lamaran yang telah Anda kirim</p>
</div>

<div class="row">
    <div class="col-12 col-lg-9">
        <!-- Daftar Lamaran -->
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="card-title">Daftar Lamaran</h2>
                    <div class="filter-controls">
                        <form action="" method="get" class="d-flex align-items-center">
                            <div class="form-group mb-0 me-2">
                                <select name="status" id="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="" <?php echo empty($status) ? 'selected' : ''; ?>>Semua Status</option>
                                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Dalam Peninjauan</option>
                                    <option value="accepted" <?php echo $status === 'accepted' ? 'selected' : ''; ?>>Diterima</option>
                                    <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Ditolak</option>
                                </select>
                            </div>
                            <div class="form-group mb-0">
                                <select name="sort" id="sort" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="created_desc" <?php echo $sort === 'created_desc' ? 'selected' : ''; ?>>Terbaru</option>
                                    <option value="created_asc" <?php echo $sort === 'created_asc' ? 'selected' : ''; ?>>Terlama</option>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($applications)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <h3>Belum Ada Lamaran</h3>
                    <p>Anda belum mengirimkan lamaran untuk penugasan apapun.</p>
                    <a href="<?php echo url('assignments/browse.php'); ?>" class="btn btn-primary mt-3">
                        <i class="fas fa-search"></i> Cari Penugasan
                    </a>
                </div>
                <?php else: ?>
                <div class="applications-list">
                    <?php foreach ($applications as $application): ?>
                    <div class="application-card">
                        <div class="application-status <?php echo $application['status']; ?>">
                            <span class="status-badge">
                                <?php echo format_application_status($application['status']); ?>
                            </span>
                            <span class="application-date">
                                <?php echo date('d M Y, H:i', strtotime($application['created_at'])); ?>
                            </span>
                        </div>
                        
                        <div class="application-details">
                            <h3 class="application-title">
                                <a href="<?php echo url('assignments/detail.php?id=' . $application['assignment_id']); ?>">
                                    <?php echo $application['judul']; ?>
                                </a>
                            </h3>
                            
                            <div class="school-info">
                                <i class="fas fa-school"></i> <?php echo $application['nama_sekolah']; ?>
                            </div>
                            
                            <div class="application-meta">
                                <div class="meta-item">
                                    <i class="fas fa-book"></i>
                                    <span class="meta-label">Mata Pelajaran:</span>
                                    <span class="meta-value"><?php echo $application['mata_pelajaran']; ?></span>
                                </div>
                                
                                <div class="meta-item">
                                    <i class="fas fa-users"></i>
                                    <span class="meta-label">Tingkat Kelas:</span>
                                    <span class="meta-value"><?php echo $application['tingkat_kelas']; ?></span>
                                </div>
                                
                                <div class="meta-item">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span class="meta-label">Periode:</span>
                                    <span class="meta-value">
                                        <?php echo date('d M Y', strtotime($application['tanggal_mulai'])); ?> - 
                                        <?php echo date('d M Y', strtotime($application['tanggal_selesai'])); ?>
                                    </span>
                                </div>
                                
                                <div class="meta-item">
                                    <i class="fas fa-money-bill-wave"></i>
                                    <span class="meta-label">Gaji:</span>
                                    <span class="meta-value"><?php echo format_price($application['gaji']); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="application-message">
                            <h4>Pesan Lamaran:</h4>
                            <div class="message-content">
                                <?php echo !empty($application['pesan']) ? nl2br($application['pesan']) : '<em>Tidak ada pesan</em>'; ?>
                            </div>
                        </div>
                        
                        <div class="application-footer">
                            <a href="<?php echo url('assignments/detail.php?id=' . $application['assignment_id']); ?>" class="btn btn-primary">
                                <i class="fas fa-eye"></i> Lihat Penugasan
                            </a>
                            
                            <?php if ($application['status'] == 'pending'): ?>
                            <form method="post" action="<?php echo url('applications/cancel.php'); ?>" class="d-inline-block" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan lamaran ini?');">
                                <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-times"></i> Batalkan
                                </button>
                            </form>
                            <?php endif; ?>
                            
                            <?php if ($application['status'] == 'accepted'): ?>
                            <a href="<?php echo url('assignments/my-assignments.php'); ?>" class="btn btn-success">
                                <i class="fas fa-clipboard-list"></i> Lihat Penugasan Saya
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination-container">
                    <nav aria-label="Page navigation">
                        <ul class="pagination">
                            <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo url('applications/my-applications.php') . '?' . http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php 
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            if ($start_page > 1) {
                                echo '<li class="page-item"><a class="page-link" href="' . url('applications/my-applications.php') . '?' . http_build_query(array_merge($_GET, ['page' => 1])) . '">1</a></li>';
                                if ($start_page > 2) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                            }
                            
                            for ($i = $start_page; $i <= $end_page; $i++) {
                                echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '"><a class="page-link" href="' . url('applications/my-applications.php') . '?' . http_build_query(array_merge($_GET, ['page' => $i])) . '">' . $i . '</a></li>';
                            }
                            
                            if ($end_page < $total_pages) {
                                if ($end_page < $total_pages - 1) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                                echo '<li class="page-item"><a class="page-link" href="' . url('applications/my-applications.php') . '?' . http_build_query(array_merge($_GET, ['page' => $total_pages])) . '">' . $total_pages . '</a></li>';
                            }
                            ?>
                            
                            <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo url('applications/my-applications.php') . '?' . http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
                
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-12 col-lg-3">
        <!-- Statistik Lamaran -->
        <div class="card mb-4">
            <div class="card-header">
                <h2 class="card-title">Statistik Lamaran</h2>
            </div>
            <div class="card-body">
                <div class="stats-item">
                    <div class="stats-info">
                        <h3>Total Lamaran</h3>
                        <p>Semua lamaran yang pernah Anda kirim</p>
                    </div>
                    <div class="stats-value"><?php echo $stats['total']; ?></div>
                </div>
                
                <div class="stats-item">
                    <div class="stats-info">
                        <h3>Dalam Peninjauan</h3>
                        <p>Lamaran yang sedang diproses</p>
                    </div>
                    <div class="stats-value pending"><?php echo $stats['pending']; ?></div>
                </div>
                
                <div class="stats-item">
                    <div class="stats-info">
                        <h3>Diterima</h3>
                        <p>Lamaran yang diterima oleh sekolah</p>
                    </div>
                    <div class="stats-value accepted"><?php echo $stats['accepted']; ?></div>
                </div>
                
                <div class="stats-item">
                    <div class="stats-info">
                        <h3>Ditolak</h3>
                        <p>Lamaran yang ditolak oleh sekolah</p>
                    </div>
                    <div class="stats-value rejected"><?php echo $stats['rejected']; ?></div>
                </div>
            </div>
        </div>
        
        <!-- Tips Lamaran Sukses -->
        <div class="card mb-4">
            <div class="card-header">
                <h2 class="card-title">Tips Lamaran Sukses</h2>
            </div>
            <div class="card-body">
                <div class="tips-list">
                    <div class="tip-item">
                        <div class="tip-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="tip-content">
                            <h3>Lengkapi Profil</h3>
                            <p>Pastikan profil Anda lengkap dan terverifikasi sebelum melamar.</p>
                        </div>
                    </div>
                    
                    <div class="tip-item">
                        <div class="tip-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="tip-content">
                            <h3>Pesan Lamaran</h3>
                            <p>Tulis pesan lamaran yang jelas dan relevan dengan penugasan.</p>
                        </div>
                    </div>
                    
                    <div class="tip-item">
                        <div class="tip-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="tip-content">
                            <h3>Kecocokan</h3>
                            <p>Lamar penugasan yang sesuai dengan keahlian dan pengalaman Anda.</p>
                        </div>
                    </div>
                    
                    <div class="tip-item">
                        <div class="tip-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="tip-content">
                            <h3>Responsif</h3>
                            <p>Tanggapi cepat jika sekolah membutuhkan informasi tambahan.</p>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <a href="<?php echo url('assignments/browse.php'); ?>" class="btn btn-primary">
                        <i class="fas fa-search"></i> Cari Penugasan
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Applications List */
.applications-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.application-card {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
    transition: all 0.3s ease;
}

.application-card:hover {
    border-color: #007bff;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
}

.application-status {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.status-badge {
    display: inline-block;
    padding: 5px 15px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.85rem;
}

.application-status.pending .status-badge {
    background-color: #fff3cd;
    color: #856404;
}

.application-status.accepted .status-badge {
    background-color: #d4edda;
    color: #155724;
}

.application-status.rejected .status-badge {
    background-color: #f8d7da;
    color: #721c24;
}

.application-date {
    color: #6c757d;
    font-size: 0.85rem;
}

.application-details {
    margin-bottom: 15px;
}

.application-title {
    font-size: 1.25rem;
    margin-bottom: 5px;
}

.application-title a {
    color: #343a40;
    text-decoration: none;
    transition: color 0.2s ease;
}

.application-title a:hover {
    color: #007bff;
}

.school-info {
    color: #6c757d;
    font-size: 0.95rem;
    margin-bottom: 10px;
}

.school-info i {
    color: #007bff;
    margin-right: 5px;
}

.application-meta {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
}

.meta-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
}

.meta-item i {
    color: #007bff;
    width: 16px;
}

.meta-label {
    font-weight: 600;
    margin-right: 5px;
}

.application-message {
    margin-top: 15px;
    margin-bottom: 15px;
}

.application-message h4 {
    font-size: 1rem;
    margin-bottom: 10px;
}

.message-content {
    background-color: #f8f9fa;
    padding: 10px;
    border-radius: 5px;
    border-left: 3px solid #007bff;
    font-size: 0.95rem;
}

.application-footer {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 40px 20px;
}

.empty-state-icon {
    font-size: 48px;
    color: #adb5bd;
    margin-bottom: 15px;
}

.empty-state h3 {
    font-size: 1.25rem;
    margin-bottom: 10px;
    color: #343a40;
}

.empty-state p {
    color: #6c757d;
    max-width: 400px;
    margin: 0 auto;
}

/* Stats */
.stats-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e9ecef;
}

.stats-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.stats-info h3 {
    font-size: 1rem;
    margin-bottom: 3px;
}

.stats-info p {
    font-size: 0.85rem;
    color: #6c757d;
    margin-bottom: 0;
}

.stats-value {
    font-size: 1.5rem;
    font-weight: 600;
    color: #343a40;
}

.stats-value.pending {
    color: #ffc107;
}

.stats-value.accepted {
    color: #28a745;
}

.stats-value.rejected {
    color: #dc3545;
}

/* Tips */
.tips-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.tip-item {
    display: flex;
    gap: 15px;
}

.tip-icon {
    color: #28a745;
    font-size: 1.25rem;
    margin-top: 3px;
}

.tip-content h3 {
    font-size: 1rem;
    margin-bottom: 5px;
}

.tip-content p {
    font-size: 0.9rem;
    color: #6c757d;
    margin-bottom: 0;
}

/* Pagination */
.pagination-container {
    margin-top: 30px;
    display: flex;
    justify-content: center;
}

.pagination {
    display: flex;
    list-style: none;
    padding: 0;
    margin: 0;
}

.page-item {
    margin: 0 2px;
}

.page-link {
    display: block;
    padding: 0.5rem 0.75rem;
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
    color: #007bff;
    text-decoration: none;
    transition: all 0.2s ease;
}

.page-item.active .page-link {
    background-color: #007bff;
    border-color: #007bff;
    color: white;
}

.page-item.disabled .page-link {
    color: #6c757d;
    pointer-events: none;
    cursor: not-allowed;
    background-color: #fff;
    border-color: #dee2e6;
}

.page-link:hover {
    background-color: #e9ecef;
    border-color: #dee2e6;
    color: #0056b3;
}
</style>

<?php
// Include footer
include_once '../templates/footer.php';
?>