<?php
// /verifications/status.php
// Halaman untuk melihat status verifikasi dokumen

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

// Ambil peran pengguna untuk menentukan tipe dokumen yang diperlukan
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
$verification_status = get_user_verification_status($_SESSION['user_id'], $role);

// Ambil dokumen yang sudah diupload
$uploaded_documents = get_user_verification_documents($_SESSION['user_id']);
$documents_by_type = [];

foreach ($uploaded_documents as $doc) {
    $documents_by_type[$doc['document_type']] = $doc;
}

// Ambil profil pengguna
$user_info = [];
if ($role === 'teacher') {
    $query = "SELECT * FROM teacher_profiles WHERE user_id = ?";
} else {
    $query = "SELECT * FROM school_profiles WHERE user_id = ?";
}

$stmt = $db->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user_info = $result->fetch_assoc();
}

// Set judul halaman
$page_title = 'Status Verifikasi';

// Include header
include('../templates/header.php');
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>Status Verifikasi</h1>
            <p class="text-muted">Periksa status verifikasi akun dan dokumen Anda.</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="/verifications/upload.php" class="btn btn-primary">
                <i class="fas fa-upload"></i> Upload Dokumen
            </a>
            <a href="/dashboard.php" class="btn btn-secondary ms-2">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-4 order-lg-2">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Status Akun</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <?php if ($verification_status === 'verified'): ?>
                        <div class="verification-status verified mb-3">
                            <i class="fas fa-check-circle fa-5x text-success"></i>
                        </div>
                        <h4 class="text-success">Terverifikasi</h4>
                        <p class="text-muted">Akun Anda telah terverifikasi dan dapat menggunakan semua fitur platform.</p>
                        
                        <?php elseif ($verification_status === 'rejected'): ?>
                        <div class="verification-status rejected mb-3">
                            <i class="fas fa-times-circle fa-5x text-danger"></i>
                        </div>
                        <h4 class="text-danger">Verifikasi Ditolak</h4>
                        <p class="text-muted">Verifikasi akun Anda ditolak. Silakan periksa dokumen Anda dan upload ulang jika diperlukan.</p>
                        
                        <?php else: ?>
                        <div class="verification-status pending mb-3">
                            <i class="fas fa-clock fa-5x text-warning"></i>
                        </div>
                        <h4 class="text-warning">Menunggu Verifikasi</h4>
                        <p class="text-muted">Dokumen Anda sedang dalam proses verifikasi. Proses ini biasanya membutuhkan waktu 1-2 hari kerja.</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-4">
                        <h6>Kelengkapan Dokumen</h6>
                        <div class="progress mb-3">
                            <?php
                            // Hitung persentase dokumen yang sudah diupload
                            $uploaded_count = count($uploaded_documents);
                            $total_count = count($document_types);
                            $upload_percentage = ($total_count > 0) ? ($uploaded_count / $total_count) * 100 : 0;
                            ?>
                            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $upload_percentage; ?>%;" 
                                 aria-valuenow="<?php echo $upload_percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                <?php echo number_format($upload_percentage); ?>%
                            </div>
                        </div>
                        
                        <p class="text-center">
                            <span class="fw-bold"><?php echo $uploaded_count; ?></span> dari 
                            <span class="fw-bold"><?php echo $total_count; ?></span> dokumen telah diupload
                        </p>
                        
                        <?php if ($uploaded_count < $total_count): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> Anda perlu mengupload semua dokumen yang diperlukan untuk verifikasi.
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($role === 'teacher'): ?>
                    <div class="d-grid">
                        <a href="/teachers/profile.php" class="btn btn-outline-primary">
                            <i class="fas fa-user-edit"></i> Lengkapi Profil
                        </a>
                    </div>
                    <?php elseif ($role === 'school'): ?>
                    <div class="d-grid">
                        <a href="/schools/profile.php" class="btn btn-outline-primary">
                            <i class="fas fa-school"></i> Lengkapi Profil Sekolah
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Butuh Bantuan?</h5>
                </div>
                <div class="card-body">
                    <p>Jika Anda mengalami kesulitan dalam proses verifikasi atau memiliki pertanyaan, jangan ragu untuk menghubungi tim dukungan kami.</p>
                    <ul class="list-unstyled mb-0">
                        <li><i class="fas fa-phone me-2"></i> 089513005831</li>
                        <li><i class="fas fa-envelope me-2"></i> support@gurusinergi.com</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="col-lg-8 order-lg-1">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Status Dokumen</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Jenis Dokumen</th>
                                    <th>Status</th>
                                    <th>Tanggal Upload</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($document_types as $type => $label): ?>
                                <tr>
                                    <td><?php echo $label; ?></td>
                                    <td>
                                        <?php
                                        if (isset($documents_by_type[$type])) {
                                            $status = $documents_by_type[$type]['status'];
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
                                            
                                            if ($status === 'rejected' && !empty($documents_by_type[$type]['notes'])) {
                                                echo '<div class="small text-danger mt-1">' . htmlspecialchars($documents_by_type[$type]['notes']) . '</div>';
                                            }
                                        } else {
                                            echo '<span class="text-muted">Belum diupload</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        if (isset($documents_by_type[$type])) {
                                            echo format_datetime($documents_by_type[$type]['created_at']);
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if (isset($documents_by_type[$type])): ?>
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?php echo $documents_by_type[$type]['file_path']; ?>" class="btn btn-outline-primary" target="_blank">
                                                <i class="fas fa-eye"></i> Lihat
                                            </a>
                                            <a href="/verifications/upload.php?type=<?php echo $type; ?>" class="btn btn-outline-secondary">
                                                <i class="fas fa-upload"></i> Upload Ulang
                                            </a>
                                        </div>
                                        <?php else: ?>
                                        <a href="/verifications/upload.php?type=<?php echo $type; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-upload"></i> Upload
                                        </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-light">
                    <div class="small text-muted">
                        <i class="fas fa-info-circle"></i> Semua dokumen diperlukan untuk verifikasi akun Anda. Pastikan dokumen yang diupload jelas dan tidak terpotong.
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Riwayat Verifikasi</h5>
                </div>
                <div class="card-body">
                    <?php
                    // Ambil riwayat verifikasi
                    $has_activity = false;
                    
                    if (!empty($uploaded_documents)) {
                        $has_activity = true;
                        
                        // Kelompokkan berdasarkan tanggal
                        $activities_by_date = [];
                        
                        foreach ($uploaded_documents as $doc) {
                            $date = date('Y-m-d', strtotime($doc['created_at']));
                            
                            if (!isset($activities_by_date[$date])) {
                                $activities_by_date[$date] = [];
                            }
                            
                            $activities_by_date[$date][] = [
                                'time' => date('H:i', strtotime($doc['created_at'])),
                                'type' => 'upload',
                                'document_type' => $doc['document_type'],
                                'status' => $doc['status']
                            ];
                            
                            // Jika dokumen sudah diverifikasi atau ditolak
                            if ($doc['status'] !== 'pending' && !empty($doc['updated_at']) && $doc['updated_at'] !== $doc['created_at']) {
                                $update_date = date('Y-m-d', strtotime($doc['updated_at']));
                                
                                if (!isset($activities_by_date[$update_date])) {
                                    $activities_by_date[$update_date] = [];
                                }
                                
                                $activities_by_date[$update_date][] = [
                                    'time' => date('H:i', strtotime($doc['updated_at'])),
                                    'type' => 'status_update',
                                    'document_type' => $doc['document_type'],
                                    'status' => $doc['status'],
                                    'notes' => $doc['notes']
                                ];
                            }
                        }
                        
                        // Urutkan berdasarkan tanggal terbaru
                        krsort($activities_by_date);
                        
                        foreach ($activities_by_date as $date => $activities) {
                            // Urutkan aktivitas berdasarkan waktu
                            usort($activities, function($a, $b) {
                                return strtotime($b['time']) - strtotime($a['time']);
                            });
                            
                            echo '<div class="mb-4">';
                            echo '<h6 class="border-bottom pb-2 mb-3">' . format_date($date) . '</h6>';
                            
                            echo '<ul class="timeline-activity">';
                            foreach ($activities as $activity) {
                                $icon_class = '';
                                $text_class = '';
                                $document_label = $document_types[$activity['document_type']] ?? $activity['document_type'];
                                
                                if ($activity['type'] === 'upload') {
                                    $icon_class = 'fa-upload text-primary';
                                    $message = 'Upload dokumen ' . $document_label;
                                } else {
                                    if ($activity['status'] === 'verified') {
                                        $icon_class = 'fa-check-circle text-success';
                                        $text_class = 'text-success';
                                        $message = 'Dokumen ' . $document_label . ' telah diverifikasi';
                                    } else if ($activity['status'] === 'rejected') {
                                        $icon_class = 'fa-times-circle text-danger';
                                        $text_class = 'text-danger';
                                        $message = 'Dokumen ' . $document_label . ' ditolak';
                                        
                                        if (!empty($activity['notes'])) {
                                            $message .= ': ' . htmlspecialchars($activity['notes']);
                                        }
                                    }
                                }
                                
                                echo '<li class="timeline-item">';
                                echo '<div class="timeline-marker"><i class="fas ' . $icon_class . '"></i></div>';
                                echo '<div class="timeline-content">';
                                echo '<p class="mb-0 ' . $text_class . '">' . $message . '</p>';
                                echo '<small class="text-muted">' . $activity['time'] . '</small>';
                                echo '</div>';
                                echo '</li>';
                            }
                            echo '</ul>';
                            echo '</div>';
                        }
                    }
                    
                    if (!$has_activity) {
                        echo '<div class="text-center text-muted py-4">';
                        echo '<i class="fas fa-history fa-3x mb-3"></i>';
                        echo '<p>Belum ada aktivitas verifikasi.</p>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Timeline Style */
.timeline-activity {
    position: relative;
    padding-left: 40px;
    list-style: none;
    margin: 0;
}

.timeline-activity::before {
    content: '';
    position: absolute;
    top: 0;
    left: 15px;
    height: 100%;
    width: 2px;
    background-color: #e9ecef;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-item:last-child {
    margin-bottom: 0;
}

.timeline-marker {
    position: absolute;
    left: -40px;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background-color: #fff;
    border: 2px solid #e9ecef;
    z-index: 1;
}

.timeline-content {
    padding-bottom: 5px;
}
</style>

<?php
// Include footer
include('../templates/footer.php');
?>