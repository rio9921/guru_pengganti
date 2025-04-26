<?php
// /matching/results.php
// Halaman untuk menampilkan hasil pencocokan guru dengan permintaan

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
require_once '../includes/matching-functions.php';
require_once '../includes/claude-matching.php';
require_once '../includes/school-functions.php';

// Cek apakah ada parameter assignment_id
if (!isset($_GET['assignment_id']) || empty($_GET['assignment_id'])) {
    // Redirect ke halaman dashboard
    if ($_SESSION['role'] === 'school') {
        header('Location: /schools/dashboard.php');
    } else {
        header('Location: /dashboard.php');
    }
    exit;
}

$assignment_id = (int)$_GET['assignment_id'];

// Ambil detail permintaan
$query = "SELECT * FROM assignments WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $assignment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Permintaan tidak ditemukan
    set_flash_message('error', 'Permintaan guru tidak ditemukan.');
    if ($_SESSION['role'] === 'school') {
        header('Location: /schools/dashboard.php');
    } else {
        header('Location: /dashboard.php');
    }
    exit;
}

$assignment = $result->fetch_assoc();

// Cek apakah pengguna berhak melihat halaman ini
$has_permission = false;

if ($_SESSION['role'] === 'school') {
    // Ambil profil sekolah
    $query = "SELECT * FROM school_profiles WHERE user_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $school_profile = $result->fetch_assoc();
        // Cek apakah permintaan ini milik sekolah ini
        if ($assignment['school_id'] === $school_profile['id']) {
            $has_permission = true;
        }
    }
} elseif ($_SESSION['role'] === 'admin') {
    // Admin selalu punya akses
    $has_permission = true;
}

if (!$has_permission) {
    // Tidak punya akses
    set_flash_message('error', 'Anda tidak memiliki akses untuk melihat halaman ini.');
    header('Location: /dashboard.php');
    exit;
}

// Ambil guru yang cocok dengan permintaan
$matching_teachers = get_matching_teachers($assignment);

// Set judul halaman
$page_title = 'Hasil Pencocokan Guru';

// Include header sesuai peran pengguna
if ($_SESSION['role'] === 'admin') {
    include('../templates/admin-header.php');
} else {
    include('../templates/header.php');
}
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Hasil Pencocokan Guru</h1>
        <div>
            <a href="<?php echo $_SESSION['role'] === 'admin' ? '/admin/assignments.php' : '/schools/requests.php'; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>
    
    <!-- Detail Permintaan -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0">Detail Permintaan Guru</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h4><?php echo htmlspecialchars($assignment['title']); ?></h4>
                    <p class="text-muted"><?php echo htmlspecialchars($assignment['description']); ?></p>
                    
                    <div class="mb-3">
                        <small class="text-muted">Mata Pelajaran:</small>
                        <p class="mb-1"><?php echo htmlspecialchars($assignment['subject']); ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted">Kelas:</small>
                        <p class="mb-1"><?php echo htmlspecialchars($assignment['grade']); ?></p>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <small class="text-muted">Lokasi:</small>
                        <p class="mb-1"><?php echo htmlspecialchars($assignment['location']); ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted">Periode:</small>
                        <p class="mb-1">
                            <?php echo format_date($assignment['start_date']); ?> s/d <?php echo format_date($assignment['end_date']); ?>
                            <br>
                            <?php echo format_time($assignment['start_time']); ?> - <?php echo format_time($assignment['end_time']); ?>
                        </p>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted">Jenis Penggantian:</small>
                        <p class="mb-1"><?php echo $assignment['is_permanent'] ? 'Permanen' : 'Sementara'; ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted">Budget:</small>
                        <p class="mb-1">Rp <?php echo number_format($assignment['budget'], 0, ',', '.'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Hasil Pencocokan -->
    <div class="card">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Guru yang Cocok</h5>
            <span class="badge bg-light text-dark"><?php echo count($matching_teachers); ?> guru ditemukan</span>
        </div>
        
        <?php if (empty($matching_teachers)): ?>
        <div class="card-body text-center py-5">
            <div class="mb-3">
                <i class="fas fa-search fa-3x text-muted"></i>
            </div>
            <h4>Tidak Ada Guru yang Cocok</h4>
            <p class="text-muted">
                Saat ini tidak ada guru yang cocok dengan permintaan Anda.
                Silakan coba ubah kriteria permintaan atau coba lagi nanti.
            </p>
            
            <?php if ($_SESSION['role'] === 'school'): ?>
            <div class="mt-3">
                <a href="/schools/create-request.php?edit=<?php echo $assignment_id; ?>" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Ubah Permintaan
                </a>
            </div>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="card-body p-0">
            <div class="list-group list-group-flush">
                <?php foreach ($matching_teachers as $index => $teacher): ?>
                <div class="list-group-item p-0">
                    <div class="row g-0">
                        <div class="col-md-2 text-center p-3 border-end">
                            <img src="<?php echo !empty($teacher['profile_picture']) ? $teacher['profile_picture'] : '../assets/img/default-avatar.png'; ?>" 
                                 alt="<?php echo htmlspecialchars($teacher['full_name']); ?>" 
                                 class="rounded-circle img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;">
                            <div class="mt-2">
                                <div class="d-flex justify-content-center">
                                    <?php 
                                    $rating = $teacher['rating'];
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $rating) {
                                            echo '<i class="fas fa-star text-warning"></i>';
                                        } elseif ($i - 0.5 <= $rating) {
                                            echo '<i class="fas fa-star-half-alt text-warning"></i>';
                                        } else {
                                            echo '<i class="far fa-star text-warning"></i>';
                                        }
                                    }
                                    ?>
                                </div>
                                <small class="text-muted"><?php echo number_format($teacher['rating'], 1); ?>/5.0</small>
                            </div>
                        </div>
                        
                        <div class="col-md-7 p-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="mb-0"><?php echo htmlspecialchars($teacher['full_name']); ?></h5>
                                <div>
                                    <span class="badge bg-<?php 
                                        echo $teacher['match_score'] >= 80 ? 'success' : 
                                            ($teacher['match_score'] >= 60 ? 'info' : 
                                            ($teacher['match_score'] >= 40 ? 'warning' : 'danger')); 
                                    ?>">
                                        Kecocokan <?php echo $teacher['match_score']; ?>%
                                    </span>
                                </div>
                            </div>
                            
                            <div class="mb-2">
                                <small class="text-muted">Pendidikan:</small>
                                <p class="mb-1"><?php echo htmlspecialchars($teacher['education']); ?></p>
                            </div>
                            
                            <div class="mb-2">
                                <small class="text-muted">Keahlian:</small>
                                <p class="mb-1"><?php echo htmlspecialchars($teacher['subject_expertise']); ?></p>
                            </div>
                            
                            <div class="mb-0">
                                <small class="text-muted">Alasan Kecocokan:</small>
                                <p class="mb-0"><?php echo htmlspecialchars($teacher['match_reason']); ?></p>
                            </div>
                        </div>
                        
                        <div class="col-md-3 p-3 border-start bg-light d-flex flex-column justify-content-between">
                            <div>
                                <?php
                                // Cek apakah guru ini sudah melamar
                                $query = "SELECT * FROM applications WHERE assignment_id = ? AND teacher_id = ?";
                                $stmt = $db->prepare($query);
                                $stmt->bind_param("ii", $assignment_id, $teacher['id']);
                                $stmt->execute();
                                $application_result = $stmt->get_result();
                                $has_applied = ($application_result->num_rows > 0);
                                
                                if ($has_applied) {
                                    $application = $application_result->fetch_assoc();
                                    $status = $application['status'];
                                    
                                    switch ($status) {
                                        case 'pending':
                                            echo '<div class="alert alert-info mb-3">Guru ini telah melamar dan menunggu persetujuan.</div>';
                                            break;
                                        case 'accepted':
                                            echo '<div class="alert alert-success mb-3">Guru ini telah diterima untuk permintaan ini.</div>';
                                            break;
                                        case 'rejected':
                                            echo '<div class="alert alert-danger mb-3">Lamaran guru ini telah ditolak.</div>';
                                            break;
                                        case 'withdrawn':
                                            echo '<div class="alert alert-warning mb-3">Guru ini telah menarik lamarannya.</div>';
                                            break;
                                    }
                                }
                                ?>
                                
                                <div class="mb-3">
                                    <small class="text-muted">Kualifikasi:</small>
                                    <?php 
                                    // Ambil ringkasan kualifikasi
                                    $qualification_summary = get_teacher_qualification_summary($teacher['id'], $assignment_id);
                                    ?>
                                    <p class="mb-0"><small><?php echo htmlspecialchars($qualification_summary); ?></small></p>
                                </div>
                            </div>
                            
                            <div class="mt-auto">
                                <?php if ($_SESSION['role'] === 'school' && $assignment['status'] === 'open'): ?>
                                    <?php if ($has_applied && $application['status'] === 'pending'): ?>
                                    <div class="d-grid gap-2">
                                        <a href="/applications/accept.php?id=<?php echo $application['id']; ?>&assignment_id=<?php echo $assignment_id; ?>" 
                                           class="btn btn-success btn-sm">
                                            <i class="fas fa-check"></i> Terima Guru Ini
                                        </a>
                                        <a href="/applications/reject.php?id=<?php echo $application['id']; ?>&assignment_id=<?php echo $assignment_id; ?>" 
                                           class="btn btn-outline-danger btn-sm">
                                            <i class="fas fa-times"></i> Tolak
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <div class="d-grid gap-2 mt-2">
                                    <a href="/teachers/profile.php?id=<?php echo $teacher['id']; ?>" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-user"></i> Lihat Profil Lengkap
                                    </a>
                                    
                                    <?php if ($_SESSION['role'] === 'school'): ?>
                                    <a href="/chat/view.php?teacher_id=<?php echo $teacher['id']; ?>&assignment_id=<?php echo $assignment_id; ?>" class="btn btn-outline-info btn-sm">
                                        <i class="fas fa-comments"></i> Hubungi Guru
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="card-footer">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted">
                    <small>Hasil pencocokan diurutkan berdasarkan tingkat kecocokan tertinggi</small>
                </div>
                
                <?php if ($_SESSION['role'] === 'school' && $assignment['status'] === 'open'): ?>
                <a href="/teachers/browse.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-search"></i> Cari Guru Lainnya
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
if ($_SESSION['role'] === 'admin') {
    include('../templates/admin-footer.php');
} else {
    include('../templates/footer.php');
}
?>