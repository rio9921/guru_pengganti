<?php
/**
 * GuruSinergi - My Assignments Page
 * 
 * Halaman untuk melihat daftar penugasan milik sendiri (guru atau sekolah)
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

// Cek login user
check_access('all');

// Inisialisasi variabel
$current_user = get_app_current_user();
$is_guru = $current_user['user_type'] == 'guru';
$is_sekolah = $current_user['user_type'] == 'sekolah';

// Parameter filter
$status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$sort = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'date_desc';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Filter paramater untuk assignments
$filter_params = [];

if ($is_guru) {
    $filter_params['guru_id'] = $current_user['id'];
} elseif ($is_sekolah) {
    $filter_params['sekolah_id'] = $current_user['id'];
}

if (!empty($status)) {
    $filter_params['status'] = $status;
}

// Set sorting
if ($sort == 'date_desc') {
    $filter_params['sort'] = 'tanggal_mulai_desc';
} elseif ($sort == 'date_asc') {
    $filter_params['sort'] = 'tanggal_mulai_asc';
} elseif ($sort == 'created_desc') {
    $filter_params['sort'] = 'created_desc';
} elseif ($sort == 'created_asc') {
    $filter_params['sort'] = 'created_asc';
}

// Dapatkan daftar penugasan
$assignments = get_assignments($filter_params, $limit, $offset);

// Dapatkan total jumlah penugasan untuk pagination
$total_assignments = get_total_assignments($filter_params);
$total_pages = ceil($total_assignments / $limit);

// Dapatkan statistik penugasan
$stats = [];

if ($is_guru) {
    $stats = [
        'total' => get_total_assignments(['guru_id' => $current_user['id']]),
        'active' => get_total_assignments(['guru_id' => $current_user['id'], 'status' => 'assigned']),
        'completed' => get_total_assignments(['guru_id' => $current_user['id'], 'status' => 'completed']),
        'earnings' => 0 // Hitung total pendapatan
    ];
    
    // Hitung total pendapatan
    $conn = db_connect();
    $stmt = $conn->prepare("
        SELECT SUM(a.gaji) as total_earnings
        FROM assignments a
        WHERE a.guru_id = ? AND a.status = 'completed'
    ");
    $stmt->execute([$current_user['id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['earnings'] = $result['total_earnings'] ?? 0;
} elseif ($is_sekolah) {
    $stats = [
        'total' => get_total_assignments(['sekolah_id' => $current_user['id']]),
        'open' => get_total_assignments(['sekolah_id' => $current_user['id'], 'status' => 'open']),
        'active' => get_total_assignments(['sekolah_id' => $current_user['id'], 'status' => 'assigned']),
        'completed' => get_total_assignments(['sekolah_id' => $current_user['id'], 'status' => 'completed']),
        'spending' => 0 // Hitung total pengeluaran
    ];
    
    // Hitung total pengeluaran
    $conn = db_connect();
    $stmt = $conn->prepare("
        SELECT SUM(p.amount) as total_spending
        FROM payments p
        JOIN assignments a ON p.assignment_id = a.id
        WHERE a.sekolah_id = ? AND p.status = 'paid'
    ");
    $stmt->execute([$current_user['id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['spending'] = $result['total_spending'] ?? 0;
}

// Set variabel untuk page title
$page_title = 'Penugasan Saya';

// Include header
include_once '../templates/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Penugasan Saya</h1>
    <p class="page-description">
        <?php if ($is_guru): ?>
        Kelola penugasan yang telah Anda terima
        <?php else: ?>
        Kelola penugasan yang telah Anda buat
        <?php endif; ?>
    </p>
</div>

<div class="stats-container mb-4">
    <?php if ($is_guru): ?>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-clipboard-list"></i></div>
        <div class="stat-value"><?php echo $stats['total']; ?></div>
        <div class="stat-title">Total Penugasan</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-chalkboard-teacher"></i></div>
        <div class="stat-value"><?php echo $stats['active']; ?></div>
        <div class="stat-title">Penugasan Aktif</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
        <div class="stat-value"><?php echo $stats['completed']; ?></div>
        <div class="stat-title">Penugasan Selesai</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
        <div class="stat-value"><?php echo format_price($stats['earnings']); ?></div>
        <div class="stat-title">Total Pendapatan</div>
    </div>
    <?php else: ?>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-clipboard-list"></i></div>
        <div class="stat-value"><?php echo $stats['total']; ?></div>
        <div class="stat-title">Total Penugasan</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-clipboard"></i></div>
        <div class="stat-value"><?php echo $stats['open']; ?></div>
        <div class="stat-title">Penugasan Terbuka</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-chalkboard-teacher"></i></div>
        <div class="stat-value"><?php echo $stats['active']; ?></div>
        <div class="stat-title">Penugasan Aktif</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
        <div class="stat-value"><?php echo $stats['completed']; ?></div>
        <div class="stat-title">Penugasan Selesai</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
        <div class="stat-value"><?php echo format_price($stats['spending']); ?></div>
        <div class="stat-title">Total Pengeluaran</div>
    </div>
    <?php endif; ?>
</div>

<div class="row">
    <div class="col-12">
        <!-- Daftar Penugasan -->
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="card-title">Daftar Penugasan</h2>
                    
                    <div class="header-actions d-flex gap-2">
                        <div class="filter-controls">
                            <form action="" method="get" class="d-flex align-items-center">
                                <div class="form-group mb-0 me-2">
                                    <select name="status" id="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                        <option value="" <?php echo empty($status) ? 'selected' : ''; ?>>Semua Status</option>
                                        <?php if ($is_sekolah): ?>
                                        <option value="open" <?php echo $status === 'open' ? 'selected' : ''; ?>>Terbuka</option>
                                        <?php endif; ?>
                                        <option value="assigned" <?php echo $status === 'assigned' ? 'selected' : ''; ?>>Aktif</option>
                                        <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Selesai</option>
                                        <option value="canceled" <?php echo $status === 'canceled' ? 'selected' : ''; ?>>Dibatalkan</option>
                                    </select>
                                </div>
                                <div class="form-group mb-0">
                                    <select name="sort" id="sort" class="form-select form-select-sm" onchange="this.form.submit()">
                                        <option value="date_desc" <?php echo $sort === 'date_desc' ? 'selected' : ''; ?>>Tanggal Mulai (Terbaru)</option>
                                        <option value="date_asc" <?php echo $sort === 'date_asc' ? 'selected' : ''; ?>>Tanggal Mulai (Terlama)</option>
                                        <option value="created_desc" <?php echo $sort === 'created_desc' ? 'selected' : ''; ?>>Tanggal Dibuat (Terbaru)</option>
                                        <option value="created_asc" <?php echo $sort === 'created_asc' ? 'selected' : ''; ?>>Tanggal Dibuat (Terlama)</option>
                                    </select>
                                </div>
                            </form>
                        </div>
                        
                        <?php if ($is_sekolah): ?>
                        <a href="<?php echo url('assignments/create.php'); ?>" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Buat Penugasan
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($assignments)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <h3>Belum Ada Penugasan</h3>
                    <p>
                        <?php if ($is_guru): ?>
                        Anda belum menerima penugasan apapun. Mulai cari dan lamar penugasan sekarang!
                        <?php else: ?>
                        Anda belum membuat penugasan apapun. Mulai buat penugasan sekarang!
                        <?php endif; ?>
                    </p>
                    
                    <?php if ($is_guru): ?>
                    <a href="<?php echo url('assignments/browse.php'); ?>" class="btn btn-primary mt-3">
                        <i class="fas fa-search"></i> Cari Penugasan
                    </a>
                    <?php else: ?>
                    <a href="<?php echo url('assignments/create.php'); ?>" class="btn btn-primary mt-3">
                        <i class="fas fa-plus"></i> Buat Penugasan
                    </a>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Judul</th>
                                <th>Mata Pelajaran</th>
                                <th>Periode</th>
                                <?php if ($is_sekolah): ?>
                                <th>Guru</th>
                                <th>Lamaran</th>
                                <?php else: ?>
                                <th>Sekolah</th>
                                <?php endif; ?>
                                <th>Status</th>
                                <th>Gaji</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assignments as $assignment): ?>
                            <tr>
                                <td>
                                    <a href="<?php echo url('assignments/detail.php?id=' . $assignment['id']); ?>" class="fw-bold">
                                        <?php echo $assignment['judul']; ?>
                                    </a>
                                </td>
                                <td><?php echo $assignment['mata_pelajaran']; ?></td>
                                <td>
                                    <?php echo date('d M Y', strtotime($assignment['tanggal_mulai'])); ?> - 
                                    <?php echo date('d M Y', strtotime($assignment['tanggal_selesai'])); ?>
                                </td>
                                <?php if ($is_sekolah): ?>
                                <td>
                                    <?php if (!empty($assignment['guru_name'])): ?>
                                    <?php echo $assignment['guru_name']; ?>
                                    <?php else: ?>
                                    <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($assignment['status'] == 'open'): ?>
                                    <a href="<?php echo url('assignments/detail.php?id=' . $assignment['id']) . '#applications'; ?>" class="badge bg-primary">
                                        <?php echo $assignment['total_applicants']; ?> Lamaran
                                    </a>
                                    <?php else: ?>
                                    <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <?php else: ?>
                                <td><?php echo $assignment['nama_sekolah']; ?></td>
                                <?php endif; ?>
                                <td>
                                    <span class="status-badge <?php echo $assignment['status']; ?>">
                                        <?php echo format_assignment_status($assignment['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo format_price($assignment['gaji']); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="<?php echo url('assignments/detail.php?id=' . $assignment['id']); ?>" class="btn btn-sm btn-outline-primary" title="Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        <?php if ($is_sekolah && $assignment['status'] == 'open'): ?>
                                        <a href="<?php echo url('assignments/edit.php?id=' . $assignment['id']); ?>" class="btn btn-sm btn-outline-secondary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($is_sekolah && $assignment['status'] == 'open'): ?>
                                        <form method="post" action="<?php echo url('assignments/delete.php'); ?>" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus penugasan ini?');">
                                            <input type="hidden" name="assignment_id" value="<?php echo $assignment['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination-container">
                    <nav aria-label="Page navigation">
                        <ul class="pagination">
                            <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo url('assignments/my-assignments.php') . '?' . http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php 
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            if ($start_page > 1) {
                                echo '<li class="page-item"><a class="page-link" href="' . url('assignments/my-assignments.php') . '?' . http_build_query(array_merge($_GET, ['page' => 1])) . '">1</a></li>';
                                if ($start_page > 2) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                            }
                            
                            for ($i = $start_page; $i <= $end_page; $i++) {
                                echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '"><a class="page-link" href="' . url('assignments/my-assignments.php') . '?' . http_build_query(array_merge($_GET, ['page' => $i])) . '">' . $i . '</a></li>';
                            }
                            
                            if ($end_page < $total_pages) {
                                if ($end_page < $total_pages - 1) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                                echo '<li class="page-item"><a class="page-link" href="' . url('assignments/my-assignments.php') . '?' . http_build_query(array_merge($_GET, ['page' => $total_pages])) . '">' . $total_pages . '</a></li>';
                            }
                            ?>
                            
                            <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo url('assignments/my-assignments.php') . '?' . http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" aria-label="Next">
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
/* Stats Container */
.stats-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 20px;
}

.stat-card {
    background-color: #fff;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    text-align: center;
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.stat-icon {
    font-size: 24px;
    color: #007bff;
    margin-bottom: 10px;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 5px;
    color: #343a40;
}

.stat-title {
    font-size: 0.9rem;
    color: #6c757d;
}

/* Table */
.table {
    margin-bottom: 0;
}

.table th {
    font-weight: 600;
    color: #343a40;
    border-top: none;
}

.table td {
    vertical-align: middle;
}

.table a {
    text-decoration: none;
}

/* Status Badge */
.status-badge {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.8rem;
    text-align: center;
}

.status-badge.open {
    background-color: #cff4fc;
    color: #055160;
}

.status-badge.assigned {
    background-color: #fff3cd;
    color: #856404;
}

.status-badge.completed {
    background-color: #d1e7dd;
    color: #0f5132;
}

.status-badge.canceled {
    background-color: #f8d7da;
    color: #721c24;
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

/* Header Actions */
.header-actions {
    display: flex;
    align-items: center;
}

@media (max-width: 768px) {
    .header-actions {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .filter-controls form {
        flex-direction: column;
        gap: 10px;
    }
    
    .form-group.mb-0.me-2 {
        margin-right: 0 !important;
    }
}
</style>

<?php
// Include footer
include_once '../templates/footer.php';
?>