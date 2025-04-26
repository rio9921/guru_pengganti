<?php
// /verifications/upload.php
// Halaman untuk upload dokumen verifikasi

// Mulai sesi
session_start();

// Cek apakah pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    // Redirect ke halaman login
    header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Include file konfigurasi dan database
require_once '../config/config.php';
require_once '../config/database.php';

// Include file fungsi
require_once '../includes/functions.php';
require_once '../includes/verification-functions.php';

// Set direktori upload
$upload_dir = '../uploads/documents/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Ambil peran pengguna untuk menentukan tipe dokumen yang harus diupload
$role = $_SESSION['role'];
$document_types = [];

if ($role === 'teacher') {
    $document_types = [
        'ktp' => 'KTP (Kartu Tanda Penduduk)',
        'cv' => 'CV (Curriculum Vitae)',
        'photo' => 'Foto Profil',
        'certificate' => 'Ijazah / Sertifikat'
    ];
} elseif ($role === 'school') {
    $document_types = [
        'school_license' => 'Izin Operasional Sekolah'
    ];
} else {
    // Redirect ke dashboard jika bukan guru atau sekolah
    header('Location: /dashboard.php');
    exit;
}

// Ambil status verifikasi pengguna
$verification_status = '';
if ($role === 'teacher') {
    $query = "SELECT verification_status FROM teacher_profiles WHERE user_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $verification_status = $row['verification_status'];
    }
} elseif ($role === 'school') {
    $query = "SELECT verification_status FROM school_profiles WHERE user_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $verification_status = $row['verification_status'];
    }
}

// Ambil dokumen yang sudah diupload
$existing_documents = [];
$query = "SELECT * FROM verification_documents WHERE user_id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $existing_documents[$row['document_type']] = $row;
}

// Proses upload dokumen
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['document_type'])) {
    $document_type = $_POST['document_type'];
    
    // Cek apakah tipe dokumen valid
    if (!array_key_exists($document_type, $document_types)) {
        $error_message = 'Tipe dokumen tidak valid.';
    } else {
        // Cek apakah ada file yang diupload
        if (!isset($_FILES['document']) || $_FILES['document']['error'] === UPLOAD_ERR_NO_FILE) {
            $error_message = 'Anda harus memilih file untuk diupload.';
        } else {
            $file = $_FILES['document'];
            
            // Cek error upload
            if ($file['error'] !== UPLOAD_ERR_OK) {
                switch ($file['error']) {
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        $error_message = 'Ukuran file terlalu besar.';
                        break;
                    default:
                        $error_message = 'Terjadi kesalahan saat upload file.';
                }
            } else {
                // Cek tipe file
                $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
                $file_type = mime_content_type($file['tmp_name']);
                
                if (!in_array($file_type, $allowed_types)) {
                    $error_message = 'Tipe file tidak diizinkan. Hanya JPG, PNG, dan PDF yang diperbolehkan.';
                } else {
                    // Cek ukuran file (maksimal 2MB)
                    $max_size = 2 * 1024 * 1024; // 2MB
                    
                    if ($file['size'] > $max_size) {
                        $error_message = 'Ukuran file terlalu besar. Maksimal 2MB.';
                    } else {
                        // Generate nama file unik
                        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                        $new_filename = 'doc_' . $document_type . '_' . $_SESSION['user_id'] . '_' . time() . '.' . $file_extension;
                        $file_path = $upload_dir . $new_filename;
                        
                        // Pindahkan file ke direktori upload
                        if (move_uploaded_file($file['tmp_name'], $file_path)) {
                            // Simpan informasi dokumen ke database
                            $result = save_verification_document(
                                $_SESSION['user_id'],
                                $document_type,
                                '/uploads/documents/' . $new_filename
                            );
                            
                            if ($result) {
                                $success_message = 'Dokumen berhasil diupload.';
                                
                                // Refresh halaman untuk menampilkan dokumen yang baru diupload
                                header('Location: ' . $_SERVER['REQUEST_URI'] . '?success=1');
                                exit;
                            } else {
                                $error_message = 'Gagal menyimpan informasi dokumen.';
                                // Hapus file jika gagal menyimpan ke database
                                unlink($file_path);
                            }
                        } else {
                            $error_message = 'Gagal memindahkan file.';
                        }
                    }
                }
            }
        }
    }
}

// Set judul halaman
$page_title = 'Upload Dokumen Verifikasi';

// Include header
include('../templates/header.php');
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>Upload Dokumen Verifikasi</h1>
            <p class="text-muted">Upload dokumen yang diperlukan untuk verifikasi akun Anda.</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="/verifications/status.php" class="btn btn-primary">
                <i class="fas fa-check-circle"></i> Cek Status Verifikasi
            </a>
            <a href="/dashboard.php" class="btn btn-secondary ms-2">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>
    
    <?php if (!empty($success_message) || isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <?php echo !empty($success_message) ? $success_message : 'Dokumen berhasil diupload.'; ?>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($error_message)): ?>
    <div class="alert alert-danger">
        <?php echo $error_message; ?>
    </div>
    <?php endif; ?>
    
    <?php if ($verification_status === 'verified'): ?>
    <div class="alert alert-success">
        <h5><i class="fas fa-check-circle"></i> Akun Anda Telah Terverifikasi</h5>
        <p class="mb-0">Semua dokumen Anda telah diverifikasi. Anda dapat menggunakan semua fitur platform.</p>
    </div>
    <?php elseif ($verification_status === 'rejected'): ?>
    <div class="alert alert-danger">
        <h5><i class="fas fa-exclamation-circle"></i> Verifikasi Ditolak</h5>
        <p class="mb-0">Verifikasi akun Anda ditolak. Silakan periksa dokumen Anda dan upload ulang jika diperlukan.</p>
    </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Dokumen Yang Diperlukan</h5>
                </div>
                <div class="list-group list-group-flush">
                    <?php foreach ($document_types as $type => $label): ?>
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1"><?php echo $label; ?></h6>
                                <p class="text-muted mb-0">
                                    <?php
                                    if (isset($existing_documents[$type])) {
                                        $status = $existing_documents[$type]['status'];
                                        $status_label = '';
                                        $status_class = '';
                                        
                                        switch ($status) {
                                            case 'pending':
                                                $status_label = 'Menunggu Verifikasi';
                                                $status_class = 'text-warning';
                                                break;
                                            case 'verified':
                                                $status_label = 'Terverifikasi';
                                                $status_class = 'text-success';
                                                break;
                                            case 'rejected':
                                                $status_label = 'Ditolak';
                                                $status_class = 'text-danger';
                                                break;
                                        }
                                        
                                        echo '<span class="' . $status_class . '"><i class="fas fa-circle me-1"></i> ' . $status_label . '</span>';
                                        
                                        if ($status === 'rejected' && !empty($existing_documents[$type]['notes'])) {
                                            echo '<br><small class="text-danger">' . htmlspecialchars($existing_documents[$type]['notes']) . '</small>';
                                        }
                                    } else {
                                        echo '<span class="text-muted">Belum diupload</span>';
                                    }
                                    ?>
                                </p>
                            </div>
                            
                            <div>
                                <?php if (isset($existing_documents[$type])): ?>
                                <a href="<?php echo $existing_documents[$type]['file_path']; ?>" class="btn btn-sm btn-outline-primary me-2" target="_blank">
                                    <i class="fas fa-eye"></i> Lihat
                                </a>
                                <?php endif; ?>
                                
                                <button type="button" class="btn btn-sm <?php echo isset($existing_documents[$type]) ? 'btn-outline-secondary' : 'btn-primary'; ?>" 
                                        data-bs-toggle="modal" data-bs-target="#uploadModal" 
                                        data-document-type="<?php echo $type; ?>" 
                                        data-document-label="<?php echo $label; ?>">
                                    <i class="fas fa-upload"></i> <?php echo isset($existing_documents[$type]) ? 'Upload Ulang' : 'Upload'; ?>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="card-footer">
                    <div class="alert alert-info mb-0">
                        <h6><i class="fas fa-info-circle"></i> Informasi Penting</h6>
                        <ul class="mb-0">
                            <li>Semua dokumen wajib diupload untuk verifikasi akun.</li>
                            <li>Format file yang diizinkan: JPG, PNG, dan PDF.</li>
                            <li>Ukuran maksimal file: 2MB.</li>
                            <li>Pastikan dokumen yang diupload jelas dan tidak terpotong.</li>
                            <li>Proses verifikasi membutuhkan waktu 1-2 hari kerja.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Status Verifikasi</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <?php if ($verification_status === 'verified'): ?>
                        <div class="verification-status verified">
                            <i class="fas fa-check-circle fa-4x text-success"></i>
                        </div>
                        <h5 class="mt-3 text-success">Terverifikasi</h5>
                        <p>Akun Anda telah terverifikasi dan dapat menggunakan semua fitur platform.</p>
                        
                        <?php elseif ($verification_status === 'rejected'): ?>
                        <div class="verification-status rejected">
                            <i class="fas fa-times-circle fa-4x text-danger"></i>
                        </div>
                        <h5 class="mt-3 text-danger">Ditolak</h5>
                        <p>Verifikasi akun Anda ditolak. Silakan periksa dokumen Anda dan upload ulang jika diperlukan.</p>
                        
                        <?php else: ?>
                        <div class="verification-status pending">
                            <i class="fas fa-clock fa-4x text-warning"></i>
                        </div>
                        <h5 class="mt-3 text-warning">Menunggu Verifikasi</h5>
                        <p>Dokumen Anda sedang dalam proses verifikasi. Proses ini membutuhkan waktu 1-2 hari kerja.</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="progress mb-3">
                        <?php
                        // Hitung persentase dokumen yang sudah diupload
                        $uploaded_count = count($existing_documents);
                        $total_count = count($document_types);
                        $upload_percentage = ($total_count > 0) ? ($uploaded_count / $total_count) * 100 : 0;
                        ?>
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $upload_percentage; ?>%;" 
                             aria-valuenow="<?php echo $upload_percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                            <?php echo number_format($upload_percentage); ?>%
                        </div>
                    </div>
                    
                    <p class="text-muted text-center">
                        <?php echo $uploaded_count; ?> dari <?php echo $total_count; ?> dokumen telah diupload
                    </p>
                    
                    <?php if ($uploaded_count < $total_count): ?>
                    <div class="alert alert-warning mb-0">
                        <i class="fas fa-exclamation-triangle"></i> Anda perlu mengupload semua dokumen yang diperlukan untuk verifikasi.
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="card-footer">
                    <div class="d-grid">
                        <a href="/verifications/status.php" class="btn btn-primary">
                            <i class="fas fa-info-circle"></i> Detail Status Verifikasi
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Butuh Bantuan?</h5>
                </div>
                <div class="card-body">
                    <p>Jika Anda mengalami kesulitan dalam proses verifikasi, jangan ragu untuk menghubungi tim dukungan kami.</p>
                    <ul class="list-unstyled mb-0">
                        <li><i class="fas fa-phone me-2"></i> 089513005831</li>
                        <li><i class="fas fa-envelope me-2"></i> support@gurusinergi.com</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Upload Dokumen -->
<div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadModalLabel">Upload Dokumen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="document_type" id="document_type" value="">
                    
                    <div class="mb-3">
                        <label for="document_label" class="form-label">Jenis Dokumen</label>
                        <input type="text" class="form-control" id="document_label" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="document" class="form-label">Pilih File</label>
                        <input type="file" class="form-control" id="document" name="document" required>
                        <div class="form-text">Format: JPG, PNG, PDF. Ukuran maksimal: 2MB</div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Pastikan dokumen yang diupload jelas dan tidak terpotong untuk mempercepat proses verifikasi.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set document type dan label pada modal upload
    const uploadModal = document.getElementById('uploadModal');
    uploadModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const documentType = button.getAttribute('data-document-type');
        const documentLabel = button.getAttribute('data-document-label');
        
        uploadModal.querySelector('#document_type').value = documentType;
        uploadModal.querySelector('#document_label').value = documentLabel;
    });
    
    // Preview file yang akan diupload
    const documentInput = document.getElementById('document');
    documentInput.addEventListener('change', function() {
        const file = documentInput.files[0];
        if (file) {
            const fileSize = file.size / 1024 / 1024; // Convert to MB
            if (fileSize > 2) {
                alert('Ukuran file terlalu besar. Maksimal 2MB.');
                documentInput.value = '';
            }
        }
    });
});
</script>

<?php
// Include footer
include('../templates/footer.php');
?>