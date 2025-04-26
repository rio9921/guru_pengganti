<?php
/**
 * GuruSinergi - Admin Materials Page
 * 
 * Halaman untuk admin mengelola materi pembelajaran
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
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle form submission untuk menambah/edit materi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_material'])) {
    $judul = sanitize($_POST['judul']);
    $deskripsi = sanitize($_POST['deskripsi']);
    $mata_pelajaran = sanitize($_POST['mata_pelajaran']);
    $tingkat_kelas = sanitize($_POST['tingkat_kelas']);
    $is_public = isset($_POST['is_public']) ? 1 : 0;
    
    // Validasi input
    if (empty($judul) || empty($mata_pelajaran) || empty($tingkat_kelas)) {
        set_error_message('Judul, mata pelajaran, dan tingkat kelas wajib diisi.');
    } else {
        $conn = db_connect();
        
        // Jika ada file baru diupload
        $file_path = '';
        if (!empty($_FILES['file_materi']['name'])) {
            $upload_dir = '../uploads/materials/';
            $file_upload = upload_file($_FILES['file_materi'], $upload_dir, ['pdf', 'doc', 'docx', 'ppt', 'pptx']);
            
            if ($file_upload['status']) {
                $file_path = str_replace('../', '', $file_upload['file_path']);
            } else {
                set_error_message($file_upload['message']);
            }
        }
        
        // Proses tambah atau edit
        if (empty($_POST['material_id'])) {
            // Tambah materi baru
            $stmt = $conn->prepare("
                INSERT INTO materi_pembelajaran (
                    creator_id, judul, deskripsi, mata_pelajaran, tingkat_kelas, 
                    file_path, is_public
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            if ($stmt->execute([
                $current_user['id'], $judul, $deskripsi, $mata_pelajaran, 
                $tingkat_kelas, $file_path, $is_public
            ])) {
                set_success_message('Materi pembelajaran berhasil ditambahkan.');
                redirect(url('admin/materials.php'));
            } else {
                set_error_message('Terjadi kesalahan saat menyimpan materi.');
            }
        } else {
            // Edit materi
            $material_id = intval($_POST['material_id']);
            
            // Ambil path file lama jika tidak ada upload baru
            if (empty($file_path)) {
                $stmt = $conn->prepare("SELECT file_path FROM materi_pembelajaran WHERE id = ?");
                $stmt->execute([$material_id]);
                $current_material = $stmt->fetch(PDO::FETCH_ASSOC);
                $file_path = $current_material['file_path'];
            }
            
            $stmt = $conn->prepare("
                UPDATE materi_pembelajaran SET 
                    judul = ?, deskripsi = ?, mata_pelajaran = ?, tingkat_kelas = ?, 
                    file_path = ?, is_public = ?
                WHERE id = ?
            ");
            
            if ($stmt->execute([
                $judul, $deskripsi, $mata_pelajaran, $tingkat_kelas, 
                $file_path, $is_public, $material_id
            ])) {
                set_success_message('Materi pembelajaran berhasil diperbarui.');
                redirect(url('admin/materials.php'));
            } else {
                set_error_message('Terjadi kesalahan saat memperbarui materi.');
            }
        }
    }
}

// Handle delete materi
if ($action == 'delete' && $id > 0) {
    $conn = db_connect();
    
    // Ambil info file untuk dihapus
    $stmt = $conn->prepare("SELECT file_path FROM materi_pembelajaran WHERE id = ?");
    $stmt->execute([$id]);
    $material = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($material) {
        // Hapus file jika ada
        if (!empty($material['file_path']) && file_exists('../' . $material['file_path'])) {
            unlink('../' . $material['file_path']);
        }
        
        // Hapus data dari database
        $stmt = $conn->prepare("DELETE FROM materi_pembelajaran WHERE id = ?");
        if ($stmt->execute([$id])) {
            set_success_message('Materi pembelajaran berhasil dihapus.');
        } else {
            set_error_message('Terjadi kesalahan saat menghapus materi.');
        }
    } else {
        set_error_message('Materi tidak ditemukan.');
    }
    
    redirect(url('admin/materials.php'));
}

// Ambil data untuk form edit
$material = null;
if ($action == 'edit' && $id > 0) {
    $conn = db_connect();
    $stmt = $conn->prepare("SELECT * FROM materi_pembelajaran WHERE id = ?");
    $stmt->execute([$id]);
    $material = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$material) {
        set_error_message('Materi tidak ditemukan.');
        redirect(url('admin/materials.php'));
    }
}

// Ambil daftar materi untuk ditampilkan
$conn = db_connect();

// Filter dan paginasi
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$mata_pelajaran_filter = isset($_GET['mata_pelajaran']) ? sanitize($_GET['mata_pelajaran']) : '';
$tingkat_filter = isset($_GET['tingkat']) ? sanitize($_GET['tingkat']) : '';

// Build query dengan filter
$params = [];
$filter_sql = '';

if (!empty($search)) {
    $filter_sql .= " AND (judul LIKE ? OR deskripsi LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($mata_pelajaran_filter)) {
    $filter_sql .= " AND mata_pelajaran = ?";
    $params[] = $mata_pelajaran_filter;
}

if (!empty($tingkat_filter)) {
    $filter_sql .= " AND tingkat_kelas = ?";
    $params[] = $tingkat_filter;
}

// Query untuk data materi
$sql = "
    SELECT m.*, u.full_name as creator_name
    FROM materi_pembelajaran m
    JOIN users u ON m.creator_id = u.id
    WHERE 1=1 $filter_sql
    ORDER BY m.created_at DESC
    LIMIT ? OFFSET ?
";

// Tambahkan parameters untuk limit dan offset
$params[] = $limit;
$params[] = $offset;

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$materials = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Query untuk total count (untuk paginasi)
$count_sql = "
    SELECT COUNT(*) as total
    FROM materi_pembelajaran m
    WHERE 1=1 $filter_sql
";

// Remove limit and offset for count query
array_pop($params);
array_pop($params);

$stmt = $conn->prepare($count_sql);
$stmt->execute($params);
$total_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_count / $limit);

// Ambil daftar mata pelajaran untuk filter
$stmt = $conn->prepare("SELECT DISTINCT mata_pelajaran FROM materi_pembelajaran ORDER BY mata_pelajaran");
$stmt->execute();
$mata_pelajaran_list = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Ambil daftar tingkat kelas untuk filter
$stmt = $conn->prepare("SELECT DISTINCT tingkat_kelas FROM materi_pembelajaran ORDER BY tingkat_kelas");
$stmt->execute();
$tingkat_kelas_list = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Set variabel untuk page title
$page_title = $action == 'add' ? 'Tambah Materi Pembelajaran' : 
             ($action == 'edit' ? 'Edit Materi Pembelajaran' : 'Kelola Materi Pembelajaran');

// Include header
include_once '../templates/admin-header.php';
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title"><?php echo $page_title; ?></h1>
            <p class="page-description">
                <?php if ($action == 'list'): ?>
                    Kelola semua materi pembelajaran yang tersedia di platform
                <?php else: ?>
                    <?php echo $action == 'add' ? 'Tambahkan' : 'Edit'; ?> materi pembelajaran untuk platform
                <?php endif; ?>
            </p>
        </div>
        <div>
            <?php if ($action == 'list'): ?>
                <a href="<?php echo url('admin/materials.php?action=add'); ?>" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i> Tambah Materi
                </a>
            <?php else: ?>
                <a href="<?php echo url('admin/materials.php'); ?>" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i> Kembali ke Daftar
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($action == 'add' || $action == 'edit'): ?>
<!-- Form Tambah/Edit Materi -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title"><?php echo $action == 'add' ? 'Tambah Materi Baru' : 'Edit Materi'; ?></h2>
    </div>
    <div class="card-body">
        <form method="post" action="" enctype="multipart/form-data">
            <?php if ($action == 'edit'): ?>
                <input type="hidden" name="material_id" value="<?php echo $material['id']; ?>">
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-8">
                    <div class="form-group">
                        <label for="judul" class="form-label">Judul Materi <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="judul" name="judul" value="<?php echo $action == 'edit' ? $material['judul'] : ''; ?>" required>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="is_public" class="form-label d-block">Visibilitas</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_public" name="is_public" <?php echo ($action == 'edit' && $material['is_public']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_public">Publik (tersedia untuk semua pengguna)</label>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="deskripsi" class="form-label">Deskripsi</label>
                <textarea class="form-control" id="deskripsi" name="deskripsi" rows="4"><?php echo $action == 'edit' ? $material['deskripsi'] : ''; ?></textarea>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="mata_pelajaran" class="form-label">Mata Pelajaran <span class="text-danger">*</span></label>
                        <select name="mata_pelajaran" id="mata_pelajaran" class="form-select" required>
                            <option value="">Pilih Mata Pelajaran</option>
                            <?php foreach (get_mata_pelajaran_options() as $value => $label): ?>
                                <?php if (empty($value)) continue; ?>
                                <option value="<?php echo $value; ?>" <?php echo ($action == 'edit' && $material['mata_pelajaran'] == $value) ? 'selected' : ''; ?>><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="tingkat_kelas" class="form-label">Tingkat Kelas <span class="text-danger">*</span></label>
                        <select name="tingkat_kelas" id="tingkat_kelas" class="form-select" required>
                            <option value="">Pilih Tingkat Kelas</option>
                            <?php foreach (get_tingkat_kelas_options() as $value => $label): ?>
                                <?php if (empty($value)) continue; ?>
                                <option value="<?php echo $value; ?>" <?php echo ($action == 'edit' && $material['tingkat_kelas'] == $value) ? 'selected' : ''; ?>><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="file_materi" class="form-label">File Materi <?php echo $action == 'add' ? '<span class="text-danger">*</span>' : ''; ?></label>
                <input type="file" class="form-control" id="file_materi" name="file_materi" <?php echo $action == 'add' ? 'required' : ''; ?> accept=".pdf,.doc,.docx,.ppt,.pptx">
                <small class="form-text">Format yang diterima: PDF, DOC, DOCX, PPT, PPTX. Maksimal 10MB.</small>
                
                <?php if ($action == 'edit' && !empty($material['file_path'])): ?>
                    <div class="current-file mt-2">
                        <p>File saat ini: <a href="<?php echo url($material['file_path']); ?>" target="_blank">
                            <i class="fas fa-file-alt"></i> <?php echo basename($material['file_path']); ?>
                        </a></p>
                        <p class="small text-muted">Unggah file baru untuk mengganti file ini, atau biarkan kosong untuk mempertahankan file saat ini.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="form-actions mt-4">
                <button type="submit" name="save_material" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo $action == 'add' ? 'Tambah Materi' : 'Simpan Perubahan'; ?>
                </button>
                <a href="<?php echo url('admin/materials.php'); ?>" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<?php else: ?>
<!-- Daftar Materi -->
<div class="card mb-4">
    <div class="card-header">
        <h2 class="card-title">Filter Materi</h2>
    </div>
    <div class="card-body">
        <form action="" method="get" class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="search" class="form-label">Cari</label>
                    <input type="text" class="form-control" id="search" name="search" value="<?php echo $search; ?>" placeholder="Judul atau deskripsi...">
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="form-group">
                    <label for="mata_pelajaran" class="form-label">Mata Pelajaran</label>
                    <select name="mata_pelajaran" id="mata_pelajaran" class="form-select">
                        <option value="">Semua</option>
                        <?php foreach ($mata_pelajaran_list as $mp): ?>
                            <option value="<?php echo $mp; ?>" <?php echo $mata_pelajaran_filter == $mp ? 'selected' : ''; ?>><?php echo $mp; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="form-group">
                    <label for="tingkat" class="form-label">Tingkat Kelas</label>
                    <select name="tingkat" id="tingkat" class="form-select">
                        <option value="">Semua</option>
                        <?php foreach ($tingkat_kelas_list as $tk): ?>
                            <option value="<?php echo $tk; ?>" <?php echo $tingkat_filter == $tk ? 'selected' : ''; ?>><?php echo $tk; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="col-md-2 d-flex align-items-end">
                <div class="form-group w-100">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Daftar Materi Pembelajaran</h2>
        <span class="badge badge-info"><?php echo $total_count; ?> total</span>
    </div>
    <div class="card-body">
        <?php if (empty($materials)): ?>
            <div class="alert alert-info">
                Tidak ada materi pembelajaran yang ditemukan. <?php echo !empty($search) || !empty($mata_pelajaran_filter) || !empty($tingkat_filter) ? 'Coba ubah filter pencarian.' : ''; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Judul</th>
                            <th>Mata Pelajaran</th>
                            <th>Tingkat Kelas</th>
                            <th>Visibilitas</th>
                            <th>Pembuat</th>
                            <th>Tanggal Dibuat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($materials as $item): ?>
                            <tr>
                                <td><?php echo $item['id']; ?></td>
                                <td>
                                    <strong><?php echo $item['judul']; ?></strong>
                                    <?php if (!empty($item['file_path'])): ?>
                                        <br>
                                        <a href="<?php echo url($item['file_path']); ?>" target="_blank" class="small">
                                            <i class="fas fa-file-alt"></i> Lihat File
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $item['mata_pelajaran']; ?></td>
                                <td><?php echo $item['tingkat_kelas']; ?></td>
                                <td>
                                    <span class="badge <?php echo $item['is_public'] ? 'badge-success' : 'badge-secondary'; ?>">
                                        <?php echo $item['is_public'] ? 'Publik' : 'Privat'; ?>
                                    </span>
                                </td>
                                <td><?php echo $item['creator_name']; ?></td>
                                <td><?php echo date('d M Y H:i', strtotime($item['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="<?php echo url('admin/materials.php?action=edit&id=' . $item['id']); ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?php echo url('admin/materials.php?action=delete&id=' . $item['id']); ?>" class="btn btn-sm btn-danger" onclick="return confirm('Anda yakin ingin menghapus materi ini?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($total_pages > 1): ?>
                <div class="pagination-wrapper mt-4">
                    <nav aria-label="Navigasi halaman">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo url('admin/materials.php?page=' . ($page - 1) . '&search=' . urlencode($search) . '&mata_pelajaran=' . urlencode($mata_pelajaran_filter) . '&tingkat=' . urlencode($tingkat_filter)); ?>" aria-label="Sebelumnya">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?php echo url('admin/materials.php?page=' . $i . '&search=' . urlencode($search) . '&mata_pelajaran=' . urlencode($mata_pelajaran_filter) . '&tingkat=' . urlencode($tingkat_filter)); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo url('admin/materials.php?page=' . ($page + 1) . '&search=' . urlencode($search) . '&mata_pelajaran=' . urlencode($mata_pelajaran_filter) . '&tingkat=' . urlencode($tingkat_filter)); ?>" aria-label="Selanjutnya">
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
<?php endif; ?>

<?php
// Include footer
include_once '../templates/admin-footer.php';
?>