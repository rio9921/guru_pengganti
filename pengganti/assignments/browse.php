<?php
/**
 * GuruSinergi - Browse Assignments Page
 * 
 * Halaman untuk mencari dan melihat daftar penugasan
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


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

// Inisialisasi variabel
$current_user = is_logged_in() ? get_app_current_user() : null;
$is_guru = $current_user && $current_user['user_type'] == 'guru';
$is_verified_guru = $is_guru && is_profile_verified($current_user);

// Parameter filter
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$mata_pelajaran = isset($_GET['mata_pelajaran']) ? sanitize($_GET['mata_pelajaran']) : '';
$tingkat_kelas = isset($_GET['tingkat_kelas']) ? sanitize($_GET['tingkat_kelas']) : '';
$lokasi = isset($_GET['lokasi']) ? sanitize($_GET['lokasi']) : '';
$sort = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'date_desc';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Filter paramater untuk assignments
$filter_params = [
    'status' => 'open' // Hanya menampilkan penugasan yang masih terbuka
];

if (!empty($search)) {
    $filter_params['search'] = $search;
}

if (!empty($mata_pelajaran)) {
    $filter_params['mata_pelajaran'] = $mata_pelajaran;
}

if (!empty($tingkat_kelas)) {
    $filter_params['tingkat_kelas'] = $tingkat_kelas;
}

if (!empty($lokasi)) {
    $filter_params['kota'] = $lokasi;
}

// Set sorting
if ($sort == 'date_desc') {
    $filter_params['sort'] = 'tanggal_mulai_desc';
} elseif ($sort == 'date_asc') {
    $filter_params['sort'] = 'tanggal_mulai_asc';
} elseif ($sort == 'price_desc') {
    $filter_params['sort'] = 'gaji_desc';
} elseif ($sort == 'price_asc') {
    $filter_params['sort'] = 'gaji_asc';
}

// Dapatkan daftar penugasan
$assignments = get_assignments($filter_params, $limit, $offset);

// Dapatkan total jumlah penugasan untuk pagination
$total_assignments = get_total_assignments($filter_params);
$total_pages = ceil($total_assignments / $limit);

// Set variabel untuk page title
$page_title = 'Cari Penugasan';

// Include header
include_once '../templates/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Cari Penugasan</h1>
    <p class="page-description">Temukan peluang mengajar sesuai keahlian dan preferensi Anda.</p>
</div>

<div class="row">
    <div class="col-12 col-lg-3">
        <!-- Filter Panel -->
        <div class="card mb-4">
            <div class="card-header">
                <h2 class="card-title">Filter Pencarian</h2>
            </div>
            <div class="card-body">
                <form action="" method="get" id="filterForm">
                    <div class="form-group">
                        <label for="search" class="form-label">Kata Kunci</label>
                        <input type="text" class="form-control" id="search" name="search" value="<?php echo $search; ?>" placeholder="Judul, deskripsi...">
                    </div>
                    
                    <div class="form-group">
                        <label for="mata_pelajaran" class="form-label">Mata Pelajaran</label>
                        <select name="mata_pelajaran" id="mata_pelajaran" class="form-select">
                            <option value="">Semua Mata Pelajaran</option>
                            <?php foreach (get_mata_pelajaran_options() as $value => $label): ?>
                                <?php if (!empty($value)): ?>
                                <option value="<?php echo $value; ?>" <?php echo ($mata_pelajaran == $value) ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="tingkat_kelas" class="form-label">Tingkat Kelas</label>
                        <select name="tingkat_kelas" id="tingkat_kelas" class="form-select">
                            <option value="">Semua Tingkat</option>
                            <?php foreach (get_tingkat_kelas_options() as $value => $label): ?>
                                <?php if (!empty($value)): ?>
                                <option value="<?php echo $value; ?>" <?php echo ($tingkat_kelas == $value) ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="lokasi" class="form-label">Lokasi</label>
                        <input type="text" class="form-control" id="lokasi" name="lokasi" value="<?php echo $lokasi; ?>" placeholder="Kota atau provinsi...">
                    </div>
                    
                    <div class="form-group">
                        <label for="sort" class="form-label">Urutkan</label>
                        <select name="sort" id="sort" class="form-select">
                            <option value="date_desc" <?php echo ($sort == 'date_desc') ? 'selected' : ''; ?>>Tanggal Mulai (Terbaru)</option>
                            <option value="date_asc" <?php echo ($sort == 'date_asc') ? 'selected' : ''; ?>>Tanggal Mulai (Terlama)</option>
                            <option value="price_desc" <?php echo ($sort == 'price_desc') ? 'selected' : ''; ?>>Gaji (Tertinggi)</option>
                            <option value="price_asc" <?php echo ($sort == 'price_asc') ? 'selected' : ''; ?>>Gaji (Terendah)</option>
                        </select>
                    </div>
                    
                    <div class="form-buttons">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                        <a href="<?php echo url('assignments/browse.php'); ?>" class="btn btn-outline w-100 mt-2">Reset</a>
                    </div>
                </form>
            </div>
        </div>
        
        <?php if (!$is_guru): ?>
        <!-- Informasi untuk Sekolah -->
        <div class="card mb-4">
            <div class="card-header">
                <h2 class="card-title">Untuk Sekolah</h2>
            </div>
            <div class="card-body">
                <p>Butuh guru pengganti untuk sekolah Anda?</p>
                <?php if (!$current_user): ?>
                <div class="text-center">
                    <a href="<?php echo url('register.php?type=sekolah'); ?>" class="btn btn-primary">Daftar Sekarang</a>
                    <a href="<?php echo url('login.php'); ?>" class="btn btn-outline mt-2">Masuk</a>
                </div>
                <?php elseif ($current_user['user_type'] == 'sekolah'): ?>
                <div class="text-center">
                    <a href="<?php echo url('assignments/create.php'); ?>" class="btn btn-primary">Buat Penugasan</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="col-12 col-lg-9">
        <!-- Results -->
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="card-title">Penugasan Tersedia</h2>
                    <div class="search-count">
                        <?php echo number_format($total_assignments); ?> penugasan ditemukan
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($assignments)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3>Tidak ada penugasan yang ditemukan</h3>
                    <p>Coba ubah filter pencarian Anda untuk menemukan penugasan yang sesuai.</p>
                </div>
                <?php else: ?>
                <div class="assignment-list">
                    <?php foreach ($assignments as $assignment): ?>
                    <div class="assignment-card">
                        <div class="assignment-header">
                            <h3 class="assignment-title">
                                <a href="<?php echo url('assignments/detail.php?id=' . $assignment['id']); ?>">
                                    <?php echo $assignment['judul']; ?>
                                </a>
                            </h3>
                            <div class="assignment-meta">
                                <div class="school-info">
                                    <i class="fas fa-school"></i> <?php echo $assignment['nama_sekolah']; ?>
                                </div>
                                <div class="location-info">
                                    <i class="fas fa-map-marker-alt"></i> <?php echo $assignment['kota'] . ', ' . $assignment['provinsi']; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="assignment-details">
                            <div class="detail-item">
                                <i class="fas fa-book"></i>
                                <span class="detail-label">Mata Pelajaran:</span>
                                <span class="detail-value"><?php echo $assignment['mata_pelajaran']; ?></span>
                            </div>
                            
                            <div class="detail-item">
                                <i class="fas fa-users"></i>
                                <span class="detail-label">Tingkat Kelas:</span>
                                <span class="detail-value"><?php echo $assignment['tingkat_kelas']; ?></span>
                            </div>
                            
                            <div class="detail-item">
                                <i class="fas fa-calendar-alt"></i>
                                <span class="detail-label">Periode:</span>
                                <span class="detail-value">
                                    <?php echo date('d M Y', strtotime($assignment['tanggal_mulai'])); ?> - 
                                    <?php echo date('d M Y', strtotime($assignment['tanggal_selesai'])); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="assignment-footer">
                            <div class="assignment-salary">
                                <?php echo format_price($assignment['gaji']); ?>
                            </div>
                            
                            <div class="assignment-actions">
                                <a href="<?php echo url('assignments/detail.php?id=' . $assignment['id']); ?>" class="btn btn-primary">
                                    <i class="fas fa-eye"></i> Detail
                                </a>
                                
                                <?php if ($is_verified_guru): ?>
                                <a href="<?php echo url('assignments/detail.php?id=' . $assignment['id']); ?>#apply" class="btn btn-success">
                                    <i class="fas fa-paper-plane"></i> Lamar
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if (strtotime($assignment['tanggal_mulai']) - time() < 7 * 24 * 60 * 60): ?>
                        <div class="urgency-badge">
                            <i class="fas fa-clock"></i> Segera Dimulai
                        </div>
                        <?php endif; ?>
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
                                <a class="page-link" href="<?php echo url('assignments/browse.php') . '?' . http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php 
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            if ($start_page > 1) {
                                echo '<li class="page-item"><a class="page-link" href="' . url('assignments/browse.php') . '?' . http_build_query(array_merge($_GET, ['page' => 1])) . '">1</a></li>';
                                if ($start_page > 2) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                            }
                            
                            for ($i = $start_page; $i <= $end_page; $i++) {
                                echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '"><a class="page-link" href="' . url('assignments/browse.php') . '?' . http_build_query(array_merge($_GET, ['page' => $i])) . '">' . $i . '</a></li>';
                            }
                            
                            if ($end_page < $total_pages) {
                                if ($end_page < $total_pages - 1) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                                echo '<li class="page-item"><a class="page-link" href="' . url('assignments/browse.php') . '?' . http_build_query(array_merge($_GET, ['page' => $total_pages])) . '">' . $total_pages . '</a></li>';
                            }
                            ?>
                            
                            <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo url('assignments/browse.php') . '?' . http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" aria-label="Next">
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
</div>

<style>
/* Assignment List */
.assignment-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.assignment-card {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
    transition: all 0.3s ease;
    position: relative;
}

.assignment-card:hover {
    border-color: #007bff;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    transform: translateY(-2px);
}

.assignment-header {
    margin-bottom: 15px;
}

.assignment-title {
    font-size: 1.25rem;
    margin-bottom: 5px;
}

.assignment-title a {
    color: #343a40;
    text-decoration: none;
    transition: color 0.2s ease;
}

.assignment-title a:hover {
    color: #007bff;
}

.assignment-meta {
    display: flex;
    gap: 15px;
    color: #6c757d;
    font-size: 0.9rem;
}

.school-info, .location-info {
    display: flex;
    align-items: center;
    gap: 5px;
}

.assignment-details {
    margin-bottom: 15px;
}

.detail-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 8px;
}

.detail-item i {
    color: #007bff;
    width: 20px;
    margin-top: 4px;
}

.detail-label {
    font-weight: 600;
    min-width: 100px;
}

.detail-value {
    flex: 1;
}

.assignment-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.assignment-salary {
    font-size: 1.25rem;
    font-weight: 600;
    color: #28a745;
}

.assignment-actions {
    display: flex;
    gap: 10px;
}

.urgency-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background-color: #dc3545;
    color: white;
    font-size: 0.85rem;
    padding: 3px 10px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 5px;
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

/* Filter Form */
.form-buttons {
    margin-top: 20px;
}
</style>

<?php
// Include footer
include_once '../templates/footer.php';
?>