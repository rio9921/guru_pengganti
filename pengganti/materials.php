<?php
/**
 * GuruSinergi - Materials Page
 * 
 * Halaman untuk melihat dan mengelola materi pembelajaran
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include file konfigurasi
require_once 'config/config.php';

// Include file database
require_once 'config/database.php';

// Include file functions
require_once 'includes/functions.php';

// Include file auth functions
require_once 'includes/auth-functions.php';

// Inisialisasi variabel
$user_logged_in = is_logged_in();
$current_user = $user_logged_in ? get_app_current_user() : null;
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle form submission untuk menambah/edit materi (hanya untuk guru)
if ($user_logged_in && $current_user['user_type'] == 'guru' && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_material'])) {
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
            $upload_dir = 'uploads/materials/';
            $file_upload = upload_file($_FILES['file_materi'], $upload_dir, ['pdf', 'doc', 'docx', 'ppt', 'pptx']);
            
            if ($file_upload['status']) {
                $file_path = $file_upload['file_path'];
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
                redirect(url('materials.php?action=my'));
            } else {
                set_error_message('Terjadi kesalahan saat menyimpan materi.');
            }
        } else {
            // Edit materi
            $material_id = intval($_POST['material_id']);
            
            // Verifikasi kepemilikan materi
            $stmt = $conn->prepare("SELECT * FROM materi_pembelajaran WHERE id = ? AND creator_id = ?");
            $stmt->execute([$material_id, $current_user['id']]);
            $material = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$material) {
                set_error_message('Anda tidak memiliki akses untuk mengedit materi ini.');
                redirect(url('materials.php'));
            }
            
            // Ambil path file lama jika tidak ada upload baru
            if (empty($file_path)) {
                $file_path = $material['file_path'];
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
                redirect(url('materials.php?action=my'));
            } else {
                set_error_message('Terjadi kesalahan saat memperbarui materi.');
            }
        }
    }
}

// Handle delete materi (hanya untuk pemilik atau admin)
if ($user_logged_in && $action == 'delete' && $id > 0) {
    $conn = db_connect();
    
    // Verifikasi kepemilikan materi
    $stmt = $conn->prepare("SELECT * FROM materi_pembelajaran WHERE id = ?");
    $stmt->execute([$id]);
    $material = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($material && ($material['creator_id'] == $current_user['id'] || $current_user['user_type'] == 'admin')) {
        // Hapus file jika ada
        if (!empty($material['file_path']) && file_exists($material['file_path'])) {
            unlink($material['file_path']);
        }
        
        // Hapus data dari database
        $stmt = $conn->prepare("DELETE FROM materi_pembelajaran WHERE id = ?");
        if ($stmt->execute([$id])) {
            set_success_message('Materi pembelajaran berhasil dihapus.');
        } else {
            set_error_message('Terjadi kesalahan saat menghapus materi.');
        }
    } else {
        set_error_message('Anda tidak memiliki akses untuk menghapus materi ini.');
    }
    
    redirect(url('materials.php' . ($current_user['user_type'] == 'guru' ? '?action=my' : '')));
}

// Ambil data untuk form edit
$material = null;
if ($user_logged_in && $action == 'edit' && $id > 0) {
    $conn = db_connect();
    
    // Verifikasi kepemilikan materi
    $stmt = $conn->prepare("SELECT * FROM materi_pembelajaran WHERE id = ?");
    $stmt->execute([$id]);
    $material = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$material || ($material['creator_id'] != $current_user['id'] && $current_user['user_type'] != 'admin')) {
        set_error_message('Anda tidak memiliki akses untuk mengedit materi ini.');
        redirect(url('materials.php'));
    }
}

// Ambil detail materi untuk view
if ($action == 'view' && $id > 0) {
    $conn = db_connect();
    
    // Query untuk detail materi
    $stmt = $conn->prepare("
        SELECT m.*, u.full_name as creator_name
        FROM materi_pembelajaran m
        JOIN users u ON m.creator_id = u.id
        WHERE m.id = ?
    ");
    $stmt->execute([$id]);
    $material = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Cek apakah materi ditemukan dan apakah user memiliki akses
    if (!$material || (!$material['is_public'] && (!$user_logged_in || ($material['creator_id'] != $current_user['id'] && $current_user['user_type'] != 'admin')))) {
        set_error_message('Materi tidak ditemukan atau Anda tidak memiliki akses.');
        redirect(url('materials.php'));
    }
}

// Ambil daftar materi untuk ditampilkan
$conn = db_connect();

// Filter dan paginasi
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$mata_pelajaran_filter = isset($_GET['mata_pelajaran']) ? sanitize($_GET['mata_pelajaran']) : '';
$tingkat_filter = isset($_GET['tingkat']) ? sanitize($_GET['tingkat']) : '';

// Build query dengan filter
$params = [];
$filter_sql = '';

if (!empty($search)) {
    $filter_sql .= " AND (m.judul LIKE ? OR m.deskripsi LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($mata_pelajaran_filter)) {
    $filter_sql .= " AND m.mata_pelajaran = ?";
    $params[] = $mata_pelajaran_filter;
}

if (!empty($tingkat_filter)) {
    $filter_sql .= " AND m.tingkat_kelas = ?";
    $params[] = $tingkat_filter;
}

// Query berbeda untuk "Materi Saya" dan materi publik
if ($user_logged_in && $action == 'my' && $current_user['user_type'] == 'guru') {
    $sql = "
        SELECT m.*, u.full_name as creator_name
        FROM materi_pembelajaran m
        JOIN users u ON m.creator_id = u.id
        WHERE m.creator_id = ? $filter_sql
        ORDER BY m.created_at DESC
        LIMIT $limit OFFSET $offset
    ";
    
    array_unshift($params, $current_user['id']);
} else {
    // Untuk publik, hanya tampilkan materi yang public atau milik sendiri
    if ($user_logged_in) {
        $sql = "
            SELECT m.*, u.full_name as creator_name
            FROM materi_pembelajaran m
            JOIN users u ON m.creator_id = u.id
            WHERE (m.is_public = 1 OR m.creator_id = ?) $filter_sql
            ORDER BY m.created_at DESC
            LIMIT $limit OFFSET $offset
        ";
        array_unshift($params, $current_user['id']);
    } else {
        $sql = "
            SELECT m.*, u.full_name as creator_name
            FROM materi_pembelajaran m
            JOIN users u ON m.creator_id = u.id
            WHERE m.is_public = 1 $filter_sql
            ORDER BY m.created_at DESC
            LIMIT $limit OFFSET $offset
        ";
    }
}

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$materials = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Query untuk total count (untuk paginasi)
if ($user_logged_in && $action == 'my' && $current_user['user_type'] == 'guru') {
    $count_sql = "
        SELECT COUNT(*) as total
        FROM materi_pembelajaran m
        WHERE m.creator_id = ? $filter_sql
    ";
} else {
    if ($user_logged_in) {
        $count_sql = "
            SELECT COUNT(*) as total
            FROM materi_pembelajaran m
            WHERE (m.is_public = 1 OR m.creator_id = ?) $filter_sql
        ";
    } else {
        $count_sql = "
            SELECT COUNT(*) as total
            FROM materi_pembelajaran m
            WHERE m.is_public = 1 $filter_sql
        ";
    }
}

$stmt = $conn->prepare($count_sql);
$stmt->execute($params);
$total_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_count / $limit);

// Ambil daftar mata pelajaran untuk filter
$mata_pelajaran_sql = "SELECT DISTINCT mata_pelajaran FROM materi_pembelajaran";
if (!$user_logged_in) {
    $mata_pelajaran_sql .= " WHERE is_public = 1";
} elseif ($action == 'my' && $current_user['user_type'] == 'guru') {
    $mata_pelajaran_sql .= " WHERE creator_id = " . $current_user['id'];
}
$mata_pelajaran_sql .= " ORDER BY mata_pelajaran";

$stmt = $conn->prepare($mata_pelajaran_sql);
$stmt->execute();
$mata_pelajaran_list = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Ambil daftar tingkat kelas untuk filter
$tingkat_kelas_sql = "SELECT DISTINCT tingkat_kelas FROM materi_pembelajaran";
if (!$user_logged_in) {
    $tingkat_kelas_sql .= " WHERE is_public = 1";
} elseif ($action == 'my' && $current_user['user_type'] == 'guru') {
    $tingkat_kelas_sql .= " WHERE creator_id = " . $current_user['id'];
}
$tingkat_kelas_sql .= " ORDER BY tingkat_kelas";

$stmt = $conn->prepare($tingkat_kelas_sql);
$stmt->execute();
$tingkat_kelas_list = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Set variabel untuk page title
if ($action == 'add') {
    $page_title = 'Tambah Materi Pembelajaran';
} elseif ($action == 'edit') {
    $page_title = 'Edit Materi Pembelajaran';
} elseif ($action == 'view') {
    $page_title = $material['judul'];
} elseif ($action == 'my') {
    $page_title = 'Materi Pembelajaran Saya';
} else {
    $page_title = 'Materi Pembelajaran';
}

// Include header
include_once 'templates/header.php';
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title"><?php echo $page_title; ?></h1>
            <p class="page-description">
                <?php if ($action == 'list'): ?>
                    Temukan materi pembelajaran untuk mendukung aktivitas mengajar
                <?php elseif ($action == 'my'): ?>
                    Kelola materi pembelajaran yang Anda buat
                <?php elseif ($action == 'add'): ?>
                    Tambahkan materi pembelajaran baru
                <?php elseif ($action == 'edit'): ?>
                    Edit materi pembelajaran Anda
                <?php elseif ($action == 'view'): ?>
                    Detail materi pembelajaran
                <?php endif; ?>
            </p>
        </div>
        <div class="page-actions">
            <?php if ($user_logged_in && $current_user['user_type'] == 'guru'): ?>
                <?php if ($action == 'list'): ?>
                    <a href="<?php echo url('materials.php?action=my'); ?>" class="btn btn-outline">
                        <i class="fas fa-folder-open"></i> Materi Saya
                    </a>
                    <a href="<?php echo url('materials.php?action=add'); ?>" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i> Tambah Materi
                    </a>
                <?php elseif ($action == 'my'): ?>
                    <a href="<?php echo url('materials.php'); ?>" class="btn btn-outline">
                        <i class="fas fa-th-list"></i> Semua Materi
                    </a>
                    <a href="<?php echo url('materials.php?action=add'); ?>" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i> Tambah Materi
                    </a>
                <?php elseif ($action == 'add' || $action == 'edit'): ?>
                    <a href="<?php echo url($action == 'edit' ? 'materials.php?action=my' : 'materials.php'); ?>" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                <?php elseif ($action == 'view'): ?>
                    <a href="<?php echo url('materials.php'); ?>" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i> Kembali ke Materi
                    </a>
                <?php endif; ?>
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
                            <label class="form-check-label" for="is_public">Publik (dapat dilihat semua pengguna)</label>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="deskripsi" class="form-label">Deskripsi</label>
                <textarea class="form-control" id="deskripsi" name="deskripsi" rows="4"><?php echo $action == 'edit' ? $material['deskripsi'] : ''; ?></textarea>
                <small class="form-text">Berikan deskripsi singkat tentang materi, misalnya tujuan pembelajaran, ringkasan konten, atau petunjuk penggunaan.</small>
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
                <a href="<?php echo url($action == 'edit' ? 'materials.php?action=my' : 'materials.php'); ?>" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<?php elseif ($action == 'view'): ?>
<!-- Detail Materi -->
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="card-title"><?php echo $material['judul']; ?></h2>
            <div class="material-badges">
                <span class="badge <?php echo $material['is_public'] ? 'badge-success' : 'badge-secondary'; ?>">
                    <?php echo $material['is_public'] ? 'Publik' : 'Privat'; ?>
                </span>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="material-details">
            <div class="row mb-4">
                <div class="col-md-8">
                    <h3>Deskripsi</h3>
                    <div class="material-description">
                        <?php if (!empty($material['deskripsi'])): ?>
                            <p><?php echo nl2br($material['deskripsi']); ?></p>
                        <?php else: ?>
                            <p class="text-muted">Tidak ada deskripsi.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="material-info-card">
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-book"></i> Mata Pelajaran</div>
                            <div class="info-value"><?php echo $material['mata_pelajaran']; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-users"></i> Tingkat Kelas</div>
                            <div class="info-value"><?php echo $material['tingkat_kelas']; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-user"></i> Dibuat Oleh</div>
                            <div class="info-value"><?php echo $material['creator_name']; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-calendar-alt"></i> Tanggal Dibuat</div>
                            <div class="info-value"><?php echo date('d M Y', strtotime($material['created_at'])); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="material-file">
                <h3>File Materi Pembelajaran</h3>
                <?php if (!empty($material['file_path'])): ?>
                    <div class="file-card">
                        <div class="file-icon">
                            <?php
                            $file_ext = strtolower(pathinfo($material['file_path'], PATHINFO_EXTENSION));
                            $icon_class = 'fa-file-alt';
                            
                            if ($file_ext == 'pdf') {
                                $icon_class = 'fa-file-pdf';
                            } elseif (in_array($file_ext, ['doc', 'docx'])) {
                                $icon_class = 'fa-file-word';
                            } elseif (in_array($file_ext, ['ppt', 'pptx'])) {
                                $icon_class = 'fa-file-powerpoint';
                            }
                            ?>
                            <i class="fas <?php echo $icon_class; ?>"></i>
                        </div>
                        <div class="file-details">
                            <div class="file-name"><?php echo basename($material['file_path']); ?></div>
                            <div class="file-actions">
                                <a href="<?php echo url($material['file_path']); ?>" target="_blank" class="btn btn-primary">
                                    <i class="fas fa-download"></i> Unduh File
                                </a>
                                <?php if (strtolower(pathinfo($material['file_path'], PATHINFO_EXTENSION)) == 'pdf'): ?>
                                    <a href="<?php echo url($material['file_path']); ?>" target="_blank" class="btn btn-outline">
                                        <i class="fas fa-eye"></i> Lihat PDF
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        Tidak ada file untuk materi ini.
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($user_logged_in && ($current_user['id'] == $material['creator_id'] || $current_user['user_type'] == 'admin')): ?>
                <div class="material-actions mt-4">
                    <hr>
                    <div class="d-flex">
                        <a href="<?php echo url('materials.php?action=edit&id=' . $material['id']); ?>" class="btn btn-primary me-2">
                            <i class="fas fa-edit"></i> Edit Materi
                        </a>
                        <a href="<?php echo url('materials.php?action=delete&id=' . $material['id']); ?>" class="btn btn-danger" onclick="return confirm('Anda yakin ingin menghapus materi ini? Tindakan ini tidak dapat dibatalkan.');">
                            <i class="fas fa-trash"></i> Hapus Materi
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Materi Terkait -->
<?php
// Ambil materi terkait (sama mata pelajaran atau tingkat kelas)
$conn = db_connect();
$related_sql = "
    SELECT m.*, u.full_name as creator_name
    FROM materi_pembelajaran m
    JOIN users u ON m.creator_id = u.id
    WHERE m.id != ? AND (m.mata_pelajaran = ? OR m.tingkat_kelas = ?)
    AND " . ($user_logged_in ? "(m.is_public = 1 OR m.creator_id = ?)" : "m.is_public = 1") . "
    ORDER BY m.created_at DESC
    LIMIT 3
";

$params = [$material['id'], $material['mata_pelajaran'], $material['tingkat_kelas']];
if ($user_logged_in) {
    $params[] = $current_user['id'];
}

$stmt = $conn->prepare($related_sql);
$stmt->execute($params);
$related_materials = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($related_materials)):
?>
<div class="card mt-4">
    <div class="card-header">
        <h2 class="card-title">Materi Terkait</h2>
    </div>
    <div class="card-body">
        <div class="row">
            <?php foreach ($related_materials as $related): ?>
                <div class="col-md-4">
                    <div class="material-card">
                        <div class="material-card-header">
                            <div class="material-type">
                                <?php
                                $file_ext = !empty($related['file_path']) ? strtolower(pathinfo($related['file_path'], PATHINFO_EXTENSION)) : '';
                                $icon_class = 'fa-file-alt';
                                
                                if ($file_ext == 'pdf') {
                                    $icon_class = 'fa-file-pdf';
                                } elseif (in_array($file_ext, ['doc', 'docx'])) {
                                    $icon_class = 'fa-file-word';
                                } elseif (in_array($file_ext, ['ppt', 'pptx'])) {
                                    $icon_class = 'fa-file-powerpoint';
                                }
                                ?>
                                <i class="fas <?php echo $icon_class; ?>"></i>
                            </div>
                            <div class="material-badges">
                                <span class="badge <?php echo $related['is_public'] ? 'badge-success' : 'badge-secondary'; ?>">
                                    <?php echo $related['is_public'] ? 'Publik' : 'Privat'; ?>
                                </span>
                            </div>
                        </div>
                        <div class="material-card-body">
                            <h3 class="material-title">
                                <a href="<?php echo url('materials.php?action=view&id=' . $related['id']); ?>"><?php echo $related['judul']; ?></a>
                            </h3>
                            <div class="material-meta">
                                <div class="meta-item">
                                    <i class="fas fa-book"></i> <?php echo $related['mata_pelajaran']; ?>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-users"></i> <?php echo $related['tingkat_kelas']; ?>
                                </div>
                            </div>
                            <div class="material-creator">
                                <i class="fas fa-user"></i> <?php echo $related['creator_name']; ?>
                            </div>
                        </div>
                        <div class="material-card-footer">
                            <a href="<?php echo url('materials.php?action=view&id=' . $related['id']); ?>" class="btn btn-sm btn-outline">Lihat Detail</a>
                            <?php if (!empty($related['file_path'])): ?>
                                <a href="<?php echo url($related['file_path']); ?>" target="_blank" class="btn btn-sm btn-primary">
                                    <i class="fas fa-download"></i> Unduh
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php else: ?>
<!-- Daftar Materi -->
<?php if ($action == 'list' || $action == 'my'): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h2 class="card-title">Filter Materi</h2>
        </div>
        <div class="card-body">
            <form action="" method="get" class="row">
                <?php if ($action == 'my'): ?>
                    <input type="hidden" name="action" value="my">
                <?php endif; ?>
                
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
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="card-title">
                <?php echo $action == 'my' ? 'Materi Pembelajaran Saya' : 'Daftar Materi Pembelajaran'; ?>
            </h2>
            <span class="badge badge-info"><?php echo $total_count; ?> total</span>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($materials)): ?>
            <div class="alert alert-info">
                <?php if ($action == 'my'): ?>
                    Anda belum memiliki materi pembelajaran. Klik "Tambah Materi" untuk membuat materi baru.
                <?php else: ?>
                    Tidak ada materi pembelajaran yang ditemukan. <?php echo !empty($search) || !empty($mata_pelajaran_filter) || !empty($tingkat_filter) ? 'Coba ubah filter pencarian.' : ''; ?>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="materials-grid">
                <div class="row">
                    <?php foreach ($materials as $item): ?>
                        <div class="col-md-4 col-lg-3 mb-4">
                            <div class="material-card h-100">
                                <div class="material-card-header">
                                    <div class="material-type">
                                        <?php
                                        $file_ext = !empty($item['file_path']) ? strtolower(pathinfo($item['file_path'], PATHINFO_EXTENSION)) : '';
                                        $icon_class = 'fa-file-alt';
                                        
                                        if ($file_ext == 'pdf') {
                                            $icon_class = 'fa-file-pdf';
                                        } elseif (in_array($file_ext, ['doc', 'docx'])) {
                                            $icon_class = 'fa-file-word';
                                        } elseif (in_array($file_ext, ['ppt', 'pptx'])) {
                                            $icon_class = 'fa-file-powerpoint';
                                        }
                                        ?>
                                        <i class="fas <?php echo $icon_class; ?>"></i>
                                    </div>
                                    <div class="material-badges">
                                        <span class="badge <?php echo $item['is_public'] ? 'badge-success' : 'badge-secondary'; ?>">
                                            <?php echo $item['is_public'] ? 'Publik' : 'Privat'; ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="material-card-body">
                                    <h3 class="material-title">
                                        <a href="<?php echo url('materials.php?action=view&id=' . $item['id']); ?>"><?php echo $item['judul']; ?></a>
                                    </h3>
                                    <div class="material-meta">
                                        <div class="meta-item">
                                            <i class="fas fa-book"></i> <?php echo $item['mata_pelajaran']; ?>
                                        </div>
                                        <div class="meta-item">
                                            <i class="fas fa-users"></i> <?php echo $item['tingkat_kelas']; ?>
                                        </div>
                                    </div>
                                    <div class="material-creator">
                                        <i class="fas fa-user"></i> <?php echo $item['creator_name']; ?>
                                    </div>
                                    <div class="material-date">
                                        <i class="fas fa-calendar-alt"></i> <?php echo date('d M Y', strtotime($item['created_at'])); ?>
                                    </div>
                                </div>
                                <div class="material-card-footer">
                                    <a href="<?php echo url('materials.php?action=view&id=' . $item['id']); ?>" class="btn btn-sm btn-outline">Lihat Detail</a>
                                    <?php if (!empty($item['file_path'])): ?>
                                        <a href="<?php echo url($item['file_path']); ?>" target="_blank" class="btn btn-sm btn-primary">
                                            <i class="fas fa-download"></i> Unduh
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($action == 'my' && $user_logged_in && ($current_user['id'] == $item['creator_id'] || $current_user['user_type'] == 'admin')): ?>
                                        <div class="dropdown material-actions">
                                            <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton-<?php echo $item['id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton-<?php echo $item['id']; ?>">
                                                <li>
                                                    <a class="dropdown-item" href="<?php echo url('materials.php?action=edit&id=' . $item['id']); ?>">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item text-danger" href="<?php echo url('materials.php?action=delete&id=' . $item['id']); ?>" onclick="return confirm('Anda yakin ingin menghapus materi ini?');">
                                                        <i class="fas fa-trash"></i> Hapus
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <?php if ($total_pages > 1): ?>
                <div class="pagination-wrapper mt-4">
                    <nav aria-label="Navigasi halaman">
                        <ul class="pagination justify-content-center">
                            <?php 
                            $query_params = http_build_query([
                                'action' => $action == 'my' ? 'my' : null,
                                'search' => $search,
                                'mata_pelajaran' => $mata_pelajaran_filter,
                                'tingkat' => $tingkat_filter
                            ]);
                            ?>
                            
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo url('materials.php?page=' . ($page - 1) . '&' . $query_params); ?>" aria-label="Sebelumnya">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?php echo url('materials.php?page=' . $i . '&' . $query_params); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo url('materials.php?page=' . ($page + 1) . '&' . $query_params); ?>" aria-label="Selanjutnya">
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

<!-- Call to Action untuk Non-Login User -->
<?php if (!$user_logged_in): ?>
<div class="card mt-4">
    <div class="card-body">
        <div class="cta-section">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h3>Akses Lebih Banyak Materi Pembelajaran</h3>
                    <p>Daftar atau masuk untuk mengakses lebih banyak materi pembelajaran dan fitur eksklusif lainnya.</p>
                </div>
                <div class="col-md-4 text-center text-md-end">
                    <a href="<?php echo url('register.php'); ?>" class="btn btn-primary">Daftar Sekarang</a>
                    <a href="<?php echo url('login.php'); ?>" class="btn btn-outline ms-2">Masuk</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Aktivasi dropdown untuk material actions
    var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
    dropdownElementList.map(function (dropdownToggleEl) {
        return new bootstrap.Dropdown(dropdownToggleEl);
    });
});
</script>

<?php
// Include footer
include_once 'templates/footer.php';
?>