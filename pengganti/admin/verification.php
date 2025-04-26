<?php
/**
 * GuruSinergi - Admin Verification Page
 * 
 * Halaman untuk admin melakukan verifikasi guru dan sekolah
 */

// Include file konfigurasi
require_once '../config/config.php';

// Include file database
require_once '../config/database.php';

// Include file functions
require_once '../includes/functions.php';

// Include file auth functions
require_once '../includes/auth-functions.php';

// Include file notification functions
require_once '../includes/notification-functions.php';

// Cek login admin
check_access('admin');

// Inisialisasi variabel
$current_user = get_app_current_user();
$type = isset($_GET['type']) && in_array($_GET['type'], ['guru', 'sekolah']) ? $_GET['type'] : 'guru';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Handle form verifikasi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verify'])) {
    $user_id = intval($_POST['user_id']);
    $status = sanitize($_POST['status']);
    $catatan = sanitize($_POST['catatan']);
    $user_type = sanitize($_POST['user_type']);
    
    $conn = db_connect();
    
    if ($user_type == 'guru') {
        $stmt = $conn->prepare("
            UPDATE profiles_guru 
            SET status_verifikasi = ?, catatan_verifikasi = ?
            WHERE user_id = ?
        ");
        $stmt->execute([$status, $catatan, $user_id]);
        
        // Kirim notifikasi ke guru
        notify_guru_verification($user_id, $status, $catatan);
    } elseif ($user_type == 'sekolah') {
        $stmt = $conn->prepare("
            UPDATE profiles_sekolah 
            SET status_verifikasi = ?, catatan_verifikasi = ?
            WHERE user_id = ?
        ");
        $stmt->execute([$status, $catatan, $user_id]);
        
        // Kirim notifikasi ke sekolah
        notify_sekolah_verification($user_id, $status, $catatan);
    }
    
    set_success_message('Status verifikasi berhasil diperbarui.');
    redirect(url('admin/verification.php?type=' . $user_type));
}

// Ambil data untuk tampilan detail jika ada ID
$user_detail = null;
if ($id > 0) {
    $conn = db_connect();
    
    if ($type == 'guru') {
        $stmt = $conn->prepare("
            SELECT u.*, pg.*
            FROM users u
            JOIN profiles_guru pg ON u.id = pg.user_id
            WHERE u.id = ? AND u.user_type = 'guru'
        ");
        $stmt->execute([$id]);
        $user_detail = $stmt->fetch(PDO::FETCH_ASSOC);
    } elseif ($type == 'sekolah') {
        $stmt = $conn->prepare("
            SELECT u.*, ps.*
            FROM users u
            JOIN profiles_sekolah ps ON u.id = ps.user_id
            WHERE u.id = ? AND u.user_type = 'sekolah'
        ");
        $stmt->execute([$id]);
        $user_detail = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// Ambil daftar user yang perlu diverifikasi
$conn = db_connect();

if ($type == 'guru') {
    // Filter berdasarkan status jika ada
    $status_filter = "";
    $params = [];
    
    if (!empty($status)) {
        $status_filter = "AND pg.status_verifikasi = ?";
        $params[] = $status;
    }
    
    $stmt = $conn->prepare("
        SELECT u.id, u.full_name, u.email, u.phone, u.created_at, pg.status_verifikasi
        FROM users u
        JOIN profiles_guru pg ON u.id = pg.user_id
        WHERE u.user_type = 'guru' $status_filter
        ORDER BY 
            CASE 
                WHEN pg.status_verifikasi = 'pending' THEN 1
                WHEN pg.status_verifikasi = 'rejected' THEN 2
                WHEN pg.status_verifikasi = 'verified' THEN 3
            END, u.created_at DESC
    ");
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($type == 'sekolah') {
    // Filter berdasarkan status jika ada
    $status_filter = "";
    $params = [];
    
    if (!empty($status)) {
        $status_filter = "AND ps.status_verifikasi = ?";
        $params[] = $status;
    }
    
    $stmt = $conn->prepare("
        SELECT u.id, u.full_name, u.email, u.phone, u.created_at, ps.nama_sekolah, ps.status_verifikasi
        FROM users u
        JOIN profiles_sekolah ps ON u.id = ps.user_id
        WHERE u.user_type = 'sekolah' $status_filter
        ORDER BY 
            CASE 
                WHEN ps.status_verifikasi = 'pending' THEN 1
                WHEN ps.status_verifikasi = 'rejected' THEN 2
                WHEN ps.status_verifikasi = 'verified' THEN 3
            END, u.created_at DESC
    ");
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Set variabel untuk page title
$page_title = 'Verifikasi ' . ($type == 'guru' ? 'Guru' : 'Sekolah');

// Include header
include_once '../templates/admin-header.php';
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title"><?php echo $page_title; ?></h1>
            <p class="page-description">Verifikasi akun <?php echo $type == 'guru' ? 'guru' : 'sekolah'; ?> yang terdaftar</p>
        </div>
        <div>
            <?php if ($user_detail): ?>
                <a href="<?php echo url('admin/verification.php?type=' . $type); ?>" class="btn btn-outline">Kembali ke Daftar</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($user_detail): ?>
<!-- Detail User -->
<div class="card mb-4">
    <div class="card-header">
        <h2 class="card-title">Detail <?php echo $type == 'guru' ? 'Guru' : 'Sekolah'; ?></h2>
        <div class="verification-status <?php echo $user_detail['status_verifikasi']; ?>">
            Status: 
            <?php 
                if ($user_detail['status_verifikasi'] == 'pending') echo 'Menunggu Verifikasi';
                elseif ($user_detail['status_verifikasi'] == 'verified') echo 'Terverifikasi';
                else echo 'Ditolak';
            ?>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="profile-section">
                    <h3>Informasi Umum</h3>
                    <div class="info-grid">
                        <div class="info-label">Nama</div>
                        <div class="info-value"><?php echo $user_detail['full_name']; ?></div>
                        
                        <div class="info-label">Email</div>
                        <div class="info-value"><?php echo $user_detail['email']; ?></div>
                        
                        <div class="info-label">Telepon</div>
                        <div class="info-value"><?php echo $user_detail['phone']; ?></div>
                        
                        <div class="info-label">Tanggal Pendaftaran</div>
                        <div class="info-value"><?php echo date('d M Y H:i', strtotime($user_detail['created_at'])); ?></div>
                    </div>
                </div>
                
                <!-- Form verifikasi -->
                <div class="verification-form mt-4">
                    <h3>Verifikasi</h3>
                    <form method="post" action="">
                        <input type="hidden" name="user_id" value="<?php echo $user_detail['id']; ?>">
                        <input type="hidden" name="user_type" value="<?php echo $type; ?>">
                        
                        <div class="form-group">
                            <label for="status" class="form-label">Status Verifikasi</label>
                            <select name="status" id="status" class="form-select">
                                <option value="pending" <?php echo $user_detail['status_verifikasi'] == 'pending' ? 'selected' : ''; ?>>Menunggu Verifikasi</option>
                                <option value="verified" <?php echo $user_detail['status_verifikasi'] == 'verified' ? 'selected' : ''; ?>>Terverifikasi</option>
                                <option value="rejected" <?php echo $user_detail['status_verifikasi'] == 'rejected' ? 'selected' : ''; ?>>Ditolak</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="catatan" class="form-label">Catatan</label>
                            <textarea name="catatan" id="catatan" rows="3" class="form-control"><?php echo $user_detail['catatan_verifikasi'] ?? ''; ?></textarea>
                            <small class="form-text">Catatan ini akan dikirimkan ke <?php echo $type; ?> sebagai informasi verifikasi.</small>
                        </div>
                        
                        <div class="mt-3">
                            <button type="submit" name="verify" class="btn btn-primary">Simpan Verifikasi</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="col-md-6">
                <?php if ($type == 'guru'): ?>
                <!-- Detail Guru -->
                <div class="profile-section mb-4">
                    <h3>Informasi Guru</h3>
                    <div class="info-grid">
                        <div class="info-label">Pendidikan</div>
                        <div class="info-value"><?php echo $user_detail['pendidikan']; ?></div>
                        
                        <div class="info-label">Pengalaman</div>
                        <div class="info-value"><?php echo $user_detail['pengalaman']; ?></div>
                        
                        <div class="info-label">Keahlian</div>
                        <div class="info-value"><?php echo $user_detail['keahlian']; ?></div>
                        
                        <div class="info-label">Mata Pelajaran</div>
                        <div class="info-value"><?php echo $user_detail['mata_pelajaran']; ?></div>
                        
                        <div class="info-label">Tingkat Mengajar</div>
                        <div class="info-value"><?php echo $user_detail['tingkat_mengajar']; ?></div>
                    </div>
                </div>
                
                <div class="document-section mb-4">
                    <h3>Dokumen</h3>
                    <div class="document-list">
                        <?php if (!empty($user_detail['dokumen_cv'])): ?>
                            <a href="<?php echo asset($user_detail['dokumen_cv']); ?>" target="_blank" class="document-item">
                                <i class="fas fa-file-pdf"></i>
                                <span>Curriculum Vitae (CV)</span>
                            </a>
                        <?php else: ?>
                            <div class="document-item document-missing">
                                <i class="fas fa-file-pdf"></i>
                                <span>Curriculum Vitae (CV) - Belum diunggah</span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($user_detail['dokumen_ijazah'])): ?>
                            <a href="<?php echo asset($user_detail['dokumen_ijazah']); ?>" target="_blank" class="document-item">
                                <i class="fas fa-graduation-cap"></i>
                                <span>Ijazah</span>
                            </a>
                        <?php else: ?>
                            <div class="document-item document-missing">
                                <i class="fas fa-graduation-cap"></i>
                                <span>Ijazah - Belum diunggah</span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($user_detail['dokumen_ktp'])): ?>
                            <a href="<?php echo asset($user_detail['dokumen_ktp']); ?>" target="_blank" class="document-item">
                                <i class="fas fa-id-card"></i>
                                <span>KTP</span>
                            </a>
                        <?php else: ?>
                            <div class="document-item document-missing">
                                <i class="fas fa-id-card"></i>
                                <span>KTP - Belum diunggah</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php elseif ($type == 'sekolah'): ?>
                <!-- Detail Sekolah -->
                <div class="profile-section mb-4">
                    <h3>Informasi Sekolah</h3>
                    <div class="info-grid">
                        <div class="info-label">Nama Sekolah</div>
                        <div class="info-value"><?php echo $user_detail['nama_sekolah']; ?></div>
                        
                        <div class="info-label">Jenis Sekolah</div>
                        <div class="info-value"><?php echo $user_detail['jenis_sekolah']; ?></div>
                        
                        <div class="info-label">Alamat</div>
                        <div class="info-value"><?php echo $user_detail['alamat_lengkap']; ?></div>
                        
                        <div class="info-label">Kecamatan</div>
                        <div class="info-value"><?php echo $user_detail['kecamatan']; ?></div>
                        
                        <div class="info-label">Kota</div>
                        <div class="info-value"><?php echo $user_detail['kota']; ?></div>
                        
                        <div class="info-label">Provinsi</div>
                        <div class="info-value"><?php echo $user_detail['provinsi']; ?></div>
                        
                        <div class="info-label">Kode Pos</div>
                        <div class="info-value"><?php echo $user_detail['kode_pos']; ?></div>
                        
                        <div class="info-label">Kontak Person</div>
                        <div class="info-value"><?php echo $user_detail['contact_person']; ?></div>
                        
                        <?php if (!empty($user_detail['website'])): ?>
                        <div class="info-label">Website</div>
                        <div class="info-value"><a href="<?php echo $user_detail['website']; ?>" target="_blank"><?php echo $user_detail['website']; ?></a></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="document-section mb-4">
                    <h3>Dokumen</h3>
                    <div class="document-list">
                        <?php if (!empty($user_detail['dokumen_npsn'])): ?>
                            <a href="<?php echo asset($user_detail['dokumen_npsn']); ?>" target="_blank" class="document-item">
                                <i class="fas fa-file-alt"></i>
                                <span>Dokumen NPSN/Izin Operasional</span>
                            </a>
                        <?php else: ?>
                            <div class="document-item document-missing">
                                <i class="fas fa-file-alt"></i>
                                <span>Dokumen NPSN/Izin Operasional - Belum diunggah</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<!-- Daftar User untuk Verifikasi -->
<div class="row mb-4">
    <div class="col">
        <div class="nav-tabs">
            <a href="<?php echo url('admin/verification.php?type=guru'); ?>" class="nav-tab <?php echo $type == 'guru' ? 'active' : ''; ?>">Guru</a>
            <a href="<?php echo url('admin/verification.php?type=sekolah'); ?>" class="nav-tab <?php echo $type == 'sekolah' ? 'active' : ''; ?>">Sekolah</a>
        </div>
    </div>
</div>

<div class="filter-section mb-4">
    <div class="card">
        <div class="card-body">
            <form action="" method="get" class="row align-items-end">
                <input type="hidden" name="type" value="<?php echo $type; ?>">
                
                <div class="col-md-4">
                    <label for="status" class="form-label">Status Verifikasi</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Menunggu Verifikasi</option>
                        <option value="verified" <?php echo $status == 'verified' ? 'selected' : ''; ?>>Terverifikasi</option>
                        <option value="rejected" <?php echo $status == 'rejected' ? 'selected' : ''; ?>>Ditolak</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Daftar <?php echo $type == 'guru' ? 'Guru' : 'Sekolah'; ?></h2>
    </div>
    <div class="card-body">
        <?php if (empty($users)): ?>
            <div class="alert alert-info">
                Tidak ada data <?php echo $type == 'guru' ? 'guru' : 'sekolah'; ?> yang tersedia.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama</th>
                            <?php if ($type == 'sekolah'): ?>
                                <th>Nama Sekolah</th>
                            <?php endif; ?>
                            <th>Email</th>
                            <th>Telepon</th>
                            <th>Tanggal Daftar</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo $user['full_name']; ?></td>
                                <?php if ($type == 'sekolah'): ?>
                                    <td><?php echo $user['nama_sekolah']; ?></td>
                                <?php endif; ?>
                                <td><?php echo $user['email']; ?></td>
                                <td><?php echo $user['phone']; ?></td>
                                <td><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <span class="badge <?php 
                                        if ($user['status_verifikasi'] == 'pending') echo 'badge-warning';
                                        elseif ($user['status_verifikasi'] == 'verified') echo 'badge-success';
                                        else echo 'badge-danger';
                                    ?>">
                                        <?php 
                                            if ($user['status_verifikasi'] == 'pending') echo 'Menunggu';
                                            elseif ($user['status_verifikasi'] == 'verified') echo 'Terverifikasi';
                                            else echo 'Ditolak';
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo url('admin/verification.php?type=' . $type . '&id=' . $user['id']); ?>" class="btn btn-sm btn-primary">Detail</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php
// Include footer
include_once '../templates/admin-footer.php';
?>