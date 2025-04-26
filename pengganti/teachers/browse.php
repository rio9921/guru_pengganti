<?php
/**
 * GuruSinergi - Browse Teachers Page
 * 
 * Halaman untuk mencari dan melihat daftar guru
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

// Inisialisasi variabel
$current_user = is_logged_in() ? get_app_current_user() : null;
$is_sekolah = $current_user && $current_user['user_type'] == 'sekolah';

// Parameter filter
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$mata_pelajaran = isset($_GET['mata_pelajaran']) ? sanitize($_GET['mata_pelajaran']) : '';
$tingkat_mengajar = isset($_GET['tingkat_mengajar']) ? sanitize($_GET['tingkat_mengajar']) : '';
$sort = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'rating_desc';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Daftar guru
$conn = db_connect();
$query_params = [];
$query = "
    SELECT u.id, u.full_name, u.profile_image, g.mata_pelajaran, g.tingkat_mengajar, 
           g.rating, g.total_reviews, g.keahlian
    FROM users u
    JOIN profiles_guru g ON u.id = g.user_id
    WHERE u.user_type = 'guru'
    AND g.status_verifikasi = 'verified'
    AND g.is_available = 1
";

// Filter pencarian
if (!empty($search)) {
    $query .= " AND (u.full_name LIKE ? OR g.mata_pelajaran LIKE ? OR g.keahlian LIKE ?)";
    $search_param = '%' . $search . '%';
    $query_params[] = $search_param;
    $query_params[] = $search_param;
    $query_params[] = $search_param;
}

// Filter mata pelajaran
if (!empty($mata_pelajaran)) {
    $query .= " AND (g.mata_pelajaran LIKE ? OR g.mata_pelajaran LIKE '%Semua Mata Pelajaran%')";
    $query_params[] = '%' . $mata_pelajaran . '%';
}

// Filter tingkat mengajar
if (!empty($tingkat_mengajar)) {
    $query .= " AND (g.tingkat_mengajar LIKE ? OR g.tingkat_mengajar LIKE '%Semua Tingkat%')";
    $query_params[] = '%' . $tingkat_mengajar . '%';
}

// Sort
if ($sort == 'rating_desc') {
    $query .= " ORDER BY g.rating DESC, g.total_reviews DESC";
} elseif ($sort == 'rating_asc') {
    $query .= " ORDER BY g.rating ASC, g.total_reviews ASC";
} elseif ($sort == 'name_asc') {
    $query .= " ORDER BY u.full_name ASC";
} elseif ($sort == 'name_desc') {
    $query .= " ORDER BY u.full_name DESC";
} elseif ($sort == 'reviews_desc') {
    $query .= " ORDER BY g.total_reviews DESC, g.rating DESC";
} else {
    $query .= " ORDER BY g.rating DESC, g.total_reviews DESC";
}

// Limit dan offset
$query .= " LIMIT ? OFFSET ?";
$query_params[] = intval($limit);
$query_params[] = intval($offset);

$stmt = $conn->prepare($query);

// Eksekusi query dengan bind parameter secara eksplisit
$param_number = 1;
foreach ($query_params as $param) {
    if (is_int($param)) {
        $stmt->bindValue($param_number++, $param, PDO::PARAM_INT);
    } else {
        $stmt->bindValue($param_number++, $param, PDO::PARAM_STR);
    }
}
$stmt->execute();
$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Total guru untuk pagination
$query_count = "
    SELECT COUNT(*) as total
    FROM users u
    JOIN profiles_guru g ON u.id = g.user_id
    WHERE u.user_type = 'guru'
    AND g.status_verifikasi = 'verified'
    AND g.is_available = 1
";

$count_params = [];

// Filter pencarian (untuk count)
if (!empty($search)) {
    $query_count .= " AND (u.full_name LIKE ? OR g.mata_pelajaran LIKE ? OR g.keahlian LIKE ?)";
    $search_param = '%' . $search . '%';
    $count_params[] = $search_param;
    $count_params[] = $search_param;
    $count_params[] = $search_param;
}

// Filter mata pelajaran (untuk count)
if (!empty($mata_pelajaran)) {
    $query_count .= " AND (g.mata_pelajaran LIKE ? OR g.mata_pelajaran LIKE '%Semua Mata Pelajaran%')";
    $count_params[] = '%' . $mata_pelajaran . '%';
}

// Filter tingkat mengajar (untuk count)
if (!empty($tingkat_mengajar)) {
    $query_count .= " AND (g.tingkat_mengajar LIKE ? OR g.tingkat_mengajar LIKE '%Semua Tingkat%')";
    $count_params[] = '%' . $tingkat_mengajar . '%';
}

$stmt_count = $conn->prepare($query_count);
$stmt_count->execute($count_params);
$total_teachers = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_teachers / $limit);

// Set variabel untuk page title
$page_title = 'Cari Guru';

// Include header
include_once '../templates/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Cari Guru</h1>
    <p class="page-description">Temukan guru berkualitas sesuai kebutuhan Anda.</p>
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
                        <input type="text" class="form-control" id="search" name="search" value="<?php echo $search; ?>" placeholder="Nama, mata pelajaran, keahlian...">
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
                        <label for="tingkat_mengajar" class="form-label">Tingkat Mengajar</label>
                        <select name="tingkat_mengajar" id="tingkat_mengajar" class="form-select">
                            <option value="">Semua Tingkat</option>
                            <?php foreach (get_tingkat_kelas_options() as $value => $label): ?>
                                <?php if (!empty($value)): ?>
                                <option value="<?php echo $value; ?>" <?php echo ($tingkat_mengajar == $value) ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="sort" class="form-label">Urutkan</label>
                        <select name="sort" id="sort" class="form-select">
                            <option value="rating_desc" <?php echo ($sort == 'rating_desc') ? 'selected' : ''; ?>>Rating Tertinggi</option>
                            <option value="rating_asc" <?php echo ($sort == 'rating_asc') ? 'selected' : ''; ?>>Rating Terendah</option>
                            <option value="reviews_desc" <?php echo ($sort == 'reviews_desc') ? 'selected' : ''; ?>>Jumlah Ulasan Tertinggi</option>
                            <option value="name_asc" <?php echo ($sort == 'name_asc') ? 'selected' : ''; ?>>Nama (A-Z)</option>
                            <option value="name_desc" <?php echo ($sort == 'name_desc') ? 'selected' : ''; ?>>Nama (Z-A)</option>
                        </select>
                    </div>
                    
                    <div class="form-buttons">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                        <a href="<?php echo url('teachers/browse.php'); ?>" class="btn btn-outline w-100 mt-2">Reset</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-12 col-lg-9">
        <!-- Results -->
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="card-title">Hasil Pencarian</h2>
                    <div class="search-count">
                        <?php echo number_format($total_teachers); ?> guru ditemukan
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($teachers)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3>Tidak ada guru yang ditemukan</h3>
                    <p>Coba ubah filter pencarian Anda untuk menemukan guru yang sesuai.</p>
                </div>
                <?php else: ?>
                <div class="teacher-list">
                    <?php foreach ($teachers as $teacher): ?>
                    <div class="teacher-card">
                        <div class="teacher-profile">
                            <?php if (!empty($teacher['profile_image'])): ?>
                            <img src="<?php echo $teacher['profile_image']; ?>" alt="<?php echo $teacher['full_name']; ?>" class="teacher-avatar">
                            <?php else: ?>
                            <div class="teacher-avatar-placeholder">
                                <?php echo substr($teacher['full_name'], 0, 1); ?>
                            </div>
                            <?php endif; ?>
                            
                            <div class="teacher-info">
                                <h3 class="teacher-name"><?php echo $teacher['full_name']; ?></h3>
                                <div class="teacher-rating">
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
                                <div class="teacher-subjects">
                                    <i class="fas fa-book"></i> <?php echo $teacher['mata_pelajaran']; ?>
                                </div>
                                <div class="teacher-levels">
                                    <i class="fas fa-school"></i> <?php echo $teacher['tingkat_mengajar']; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="teacher-summary">
                            <?php 
                            // Ekstrak keahlian singkat
                            $keahlian_short = '';
                            if (!empty($teacher['keahlian'])) {
                                $keahlian = explode("\n", $teacher['keahlian']);
                                $keahlian_short = $keahlian[0];
                                if (strlen($keahlian_short) > 100) {
                                    $keahlian_short = substr($keahlian_short, 0, 97) . '...';
                                }
                            }
                            ?>
                            <p><?php echo !empty($keahlian_short) ? $keahlian_short : 'Tidak ada informasi keahlian.'; ?></p>
                        </div>
                        
                        <div class="teacher-actions">
                            <a href="<?php echo url('teachers/profile.php?id=' . $teacher['id']); ?>" class="btn btn-primary">
                                <i class="fas fa-user"></i> Lihat Profil
                            </a>
                            
                            <?php if ($is_sekolah): ?>
                            <a href="<?php echo url('assignments/create.php?teacher_id=' . $teacher['id']); ?>" class="btn btn-outline">
                                <i class="fas fa-briefcase"></i> Buat Penugasan
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
                                <a class="page-link" href="<?php echo url('teachers/browse.php') . '?' . http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php 
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            if ($start_page > 1) {
                                echo '<li class="page-item"><a class="page-link" href="' . url('teachers/browse.php') . '?' . http_build_query(array_merge($_GET, ['page' => 1])) . '">1</a></li>';
                                if ($start_page > 2) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                            }
                            
                            for ($i = $start_page; $i <= $end_page; $i++) {
                                echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '"><a class="page-link" href="' . url('teachers/browse.php') . '?' . http_build_query(array_merge($_GET, ['page' => $i])) . '">' . $i . '</a></li>';
                            }
                            
                            if ($end_page < $total_pages) {
                                if ($end_page < $total_pages - 1) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                                echo '<li class="page-item"><a class="page-link" href="' . url('teachers/browse.php') . '?' . http_build_query(array_merge($_GET, ['page' => $total_pages])) . '">' . $total_pages . '</a></li>';
                            }
                            ?>
                            
                            <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo url('teachers/browse.php') . '?' . http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" aria-label="Next">
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
/* Teacher List */
.teacher-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.teacher-card {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
    transition: all 0.3s ease;
}

.teacher-card:hover {
    border-color: #007bff;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    transform: translateY(-2px);
}

.teacher-profile {
    display: flex;
    gap: 20px;
    margin-bottom: 15px;
}

.teacher-avatar, .teacher-avatar-placeholder {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
}

.teacher-avatar-placeholder {
    background-color: #6c757d;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
}

.teacher-info {
    flex: 1;
}

.teacher-name {
    font-size: 1.25rem;
    margin-bottom: 5px;
}

.teacher-rating {
    color: #ffc107;
    font-size: 0.9rem;
    margin-bottom: 5px;
}

.rating-value, .reviews-count {
    color: #666;
    margin-left: 5px;
}

.teacher-subjects, .teacher-levels {
    color: #555;
    font-size: 0.9rem;
    margin-bottom: 3px;
}

.teacher-subjects i, .teacher-levels i {
    width: 20px;
    color: #007bff;
}

.teacher-summary {
    margin-bottom: 15px;
    color: #444;
    font-size: 0.95rem;
}

.teacher-actions {
    display: flex;
    gap: 10px;
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