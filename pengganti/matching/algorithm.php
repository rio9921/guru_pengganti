<?php
// /matching/algorithm.php
// Halaman untuk algoritma pencocokan guru dengan kebutuhan

// Mulai sesi
session_start();

// Cek apakah pengguna sudah login dan adalah admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
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

// Set judul halaman
$page_title = 'Konfigurasi Algoritma Pencocokan';

// Proses form jika disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subjects_weight = isset($_POST['subjects_weight']) ? (int)$_POST['subjects_weight'] : 40;
    $education_weight = isset($_POST['education_weight']) ? (int)$_POST['education_weight'] : 15;
    $experience_weight = isset($_POST['experience_weight']) ? (int)$_POST['experience_weight'] : 20;
    $rating_weight = isset($_POST['rating_weight']) ? (int)$_POST['rating_weight'] : 25;
    $use_claude = isset($_POST['use_claude']) ? 1 : 0;
    
    // Validasi total bobot harus 100%
    $total_weight = $subjects_weight + $education_weight + $experience_weight + $rating_weight;
    
    if ($total_weight !== 100) {
        $error_message = "Total bobot harus 100%. Total sekarang: $total_weight%";
    } else {
        // Simpan konfigurasi ke database
        $query = "INSERT INTO algorithm_config (subjects_weight, education_weight, experience_weight, rating_weight, use_claude, modified_by)
                  VALUES (?, ?, ?, ?, ?, ?)
                  ON DUPLICATE KEY UPDATE
                  subjects_weight = VALUES(subjects_weight),
                  education_weight = VALUES(education_weight),
                  experience_weight = VALUES(experience_weight),
                  rating_weight = VALUES(rating_weight),
                  use_claude = VALUES(use_claude),
                  modified_by = VALUES(modified_by),
                  updated_at = CURRENT_TIMESTAMP";
        
        $stmt = $db->prepare($query);
        $stmt->bind_param("iiiiii", $subjects_weight, $education_weight, $experience_weight, $rating_weight, $use_claude, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $success_message = "Konfigurasi algoritma berhasil disimpan.";
        } else {
            $error_message = "Error: Gagal menyimpan konfigurasi. " . $db->error;
        }
    }
}

// Ambil konfigurasi saat ini dari database
$query = "SELECT * FROM algorithm_config ORDER BY updated_at DESC LIMIT 1";
$result = $db->query($query);

if ($result && $result->num_rows > 0) {
    $config = $result->fetch_assoc();
} else {
    // Default jika belum ada konfigurasi
    $config = [
        'subjects_weight' => 40,
        'education_weight' => 15,
        'experience_weight' => 20,
        'rating_weight' => 25,
        'use_claude' => 1
    ];
}

// Jika mode test algoritma
$test_results = [];
if (isset($_GET['test']) && isset($_GET['assignment_id'])) {
    $assignment_id = (int)$_GET['assignment_id'];
    
    // Ambil detail permintaan
    $query = "SELECT * FROM assignments WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $assignment_id);
    $stmt->execute();
    $assignment_result = $stmt->get_result();
    
    if ($assignment_result->num_rows > 0) {
        $assignment = $assignment_result->fetch_assoc();
        
        // Cari guru yang cocok
        $matching_teachers = get_matching_teachers($assignment, 10);
        
        // Format hasil untuk ditampilkan
        foreach ($matching_teachers as $teacher) {
            $teacher_info = [
                'id' => $teacher['id'],
                'name' => $teacher['full_name'],
                'education' => $teacher['education'],
                'expertise' => $teacher['subject_expertise'],
                'score' => $teacher['match_score'],
                'reason' => $teacher['match_reason']
            ];
            
            $test_results[] = $teacher_info;
        }
    } else {
        $error_message = "Permintaan dengan ID $assignment_id tidak ditemukan.";
    }
}

// Ambil daftar permintaan untuk testing
$query = "SELECT id, title, subject, grade FROM assignments WHERE status = 'open' ORDER BY created_at DESC LIMIT 20";
$assignments_result = $db->query($query);
$open_assignments = [];

if ($assignments_result && $assignments_result->num_rows > 0) {
    while ($row = $assignments_result->fetch_assoc()) {
        $open_assignments[] = $row;
    }
}

// Include header
include('../templates/admin-header.php');
?>

<div class="container-fluid py-4">
    <h1 class="h3 mb-4">Konfigurasi Algoritma Pencocokan</h1>
    
    <?php if (isset($error_message)): ?>
    <div class="alert alert-danger" role="alert">
        <?php echo $error_message; ?>
    </div>
    <?php endif; ?>
    
    <?php if (isset($success_message)): ?>
    <div class="alert alert-success" role="alert">
        <?php echo $success_message; ?>
    </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Parameter Algoritma</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Bobot Kesesuaian Mata Pelajaran</label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="subjects_weight" min="0" max="100" value="<?php echo $config['subjects_weight']; ?>" required>
                                <span class="input-group-text">%</span>
                            </div>
                            <div class="form-text">Berpengaruh pada skor ketika mata pelajaran guru sesuai dengan permintaan.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Bobot Pendidikan</label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="education_weight" min="0" max="100" value="<?php echo $config['education_weight']; ?>" required>
                                <span class="input-group-text">%</span>
                            </div>
                            <div class="form-text">Berpengaruh pada skor berdasarkan tingkat pendidikan guru.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Bobot Pengalaman</label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="experience_weight" min="0" max="100" value="<?php echo $config['experience_weight']; ?>" required>
                                <span class="input-group-text">%</span>
                            </div>
                            <div class="form-text">Berpengaruh pada skor berdasarkan pengalaman mengajar guru.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Bobot Rating</label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="rating_weight" min="0" max="100" value="<?php echo $config['rating_weight']; ?>" required>
                                <span class="input-group-text">%</span>
                            </div>
                            <div class="form-text">Berpengaruh pada skor berdasarkan rating guru dari pengguna lain.</div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="use_claude" name="use_claude" <?php echo $config['use_claude'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="use_claude">Gunakan Claude API untuk Pencocokan</label>
                            <div class="form-text">Jika diaktifkan, sistem akan menggunakan Anthropic Claude API untuk analisis pencocokan yang lebih akurat.</div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Simpan Konfigurasi</button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-muted">
                    <small>Terakhir diubah: <?php echo isset($config['updated_at']) ? format_datetime($config['updated_at']) : 'Belum pernah diubah'; ?></small>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Test Algoritma Pencocokan</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="mb-3">
                            <label class="form-label">Pilih Permintaan Guru</label>
                            <select class="form-select" name="assignment_id" required>
                                <option value="">-- Pilih Permintaan --</option>
                                <?php foreach ($open_assignments as $assignment): ?>
                                <option value="<?php echo $assignment['id']; ?>">
                                    #<?php echo $assignment['id']; ?> - <?php echo htmlspecialchars($assignment['title']); ?> 
                                    (<?php echo htmlspecialchars($assignment['subject']); ?>, <?php echo htmlspecialchars($assignment['grade']); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="d-grid">
                            <input type="hidden" name="test" value="1">
                            <button type="submit" class="btn btn-info">Jalankan Test</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <?php if (!empty($test_results)): ?>
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Hasil Pencocokan</h5>
                    <span class="badge bg-primary">ID Permintaan: <?php echo $assignment_id; ?></span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nama Guru</th>
                                    <th>Skor</th>
                                    <th>Alasan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($test_results as $result): ?>
                                <tr>
                                    <td><?php echo $result['id']; ?></td>
                                    <td>
                                        <div><?php echo htmlspecialchars($result['name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($result['education']); ?></small>
                                    </td>
                                    <td>
                                        <div class="progress">
                                            <div class="progress-bar bg-<?php 
                                                echo $result['score'] >= 80 ? 'success' : 
                                                    ($result['score'] >= 60 ? 'info' : 
                                                    ($result['score'] >= 40 ? 'warning' : 'danger')); 
                                            ?>" role="progressbar" style="width: <?php echo $result['score']; ?>%">
                                                <?php echo $result['score']; ?>%
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <small><?php echo htmlspecialchars($result['reason']); ?></small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-between">
                        <a href="/assignments/detail.php?id=<?php echo $assignment_id; ?>" class="btn btn-sm btn-outline-primary">
                            Lihat Detail Permintaan
                        </a>
                        <a href="/matching/results.php?assignment_id=<?php echo $assignment_id; ?>" class="btn btn-sm btn-outline-success">
                            Ke Halaman Hasil
                        </a>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Panduan Algoritma Pencocokan</h5>
                </div>
                <div class="card-body">
                    <h5>Cara Kerja Algoritma</h5>
                    <p>Sistem pencocokan guru dengan permintaan sekolah menggunakan kombinasi antara algoritma internal dan API Claude Anthropic untuk memberikan hasil yang akurat.</p>
                    
                    <div class="alert alert-info">
                        <h6>Algorithma Internal</h6>
                        <p>Algoritma internal menghitung skor kecocokan berdasarkan parameter berikut:</p>
                        <ul>
                            <li><strong>Kesesuaian Mata Pelajaran</strong> - Memeriksa apakah mata pelajaran yang diminta sesuai dengan keahlian guru</li>
                            <li><strong>Pendidikan</strong> - Mempertimbangkan latar belakang pendidikan guru</li>
                            <li><strong>Pengalaman</strong> - Mengevaluasi pengalaman mengajar guru</li>
                            <li><strong>Rating</strong> - Memperhitungkan penilaian dari pengguna sebelumnya</li>
                        </ul>
                    </div>
                    
                    <div class="alert alert-primary">
                        <h6>Integrasi dengan Claude API</h6>
                        <p>Ketika diaktifkan, sistem juga menggunakan AI Claude dari Anthropic untuk:</p>
                        <ul>
                            <li>Analisis semantik kecocokan guru dengan permintaan</li>
                            <li>Evaluasi mendalam terhadap pengalaman dan kualifikasi</li>
                            <li>Penyediaan alasan yang lebih komprehensif untuk skor kecocokan</li>
                            <li>Penyesuaian skor berdasarkan faktor tambahan yang mungkin terlewat oleh algoritma standar</li>
                        </ul>
                    </div>
                    
                    <h5>Tips Pengaturan</h5>
                    <ul>
                        <li>Total persentase bobot harus selalu 100%</li>
                        <li>Jika fokus pada kualitas akademik, tingkatkan bobot pendidikan</li>
                        <li>Jika fokus pada kesesuaian mata pelajaran, tingkatkan bobot mata pelajaran</li>
                        <li>Untuk guru dengan reputasi baik, tingkatkan bobot rating</li>
                        <li>Gunakan fitur test untuk melihat efektivitas konfigurasi yang telah diatur</li>
                    </ul>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Log Aktivitas Algoritma</h5>
                </div>
                <div class="card-body p-0">
                    <?php
                    // Ambil log terakhir
                    $query = "SELECT a.assignment_id, a.result_count, a.query_time, a.created_at,
                              asn.title as assignment_title, u.email as requested_by
                              FROM algorithm_logs a
                              JOIN assignments asn ON a.assignment_id = asn.id
                              JOIN users u ON a.user_id = u.id
                              ORDER BY a.created_at DESC
                              LIMIT 10";
                    $logs_result = $db->query($query);
                    ?>
                    
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Waktu</th>
                                    <th>Permintaan</th>
                                    <th>Hasil</th>
                                    <th>Durasi</th>
                                    <th>Oleh</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($logs_result && $logs_result->num_rows > 0): ?>
                                    <?php while ($log = $logs_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><small><?php echo format_datetime($log['created_at']); ?></small></td>
                                        <td>
                                            <small>
                                                <a href="/assignments/detail.php?id=<?php echo $log['assignment_id']; ?>">
                                                    #<?php echo $log['assignment_id']; ?> - <?php echo substr(htmlspecialchars($log['assignment_title']), 0, 30); ?>...
                                                </a>
                                            </small>
                                        </td>
                                        <td><small><?php echo $log['result_count']; ?> guru</small></td>
                                        <td><small><?php echo number_format($log['query_time'], 2); ?> detik</small></td>
                                        <td><small><?php echo htmlspecialchars($log['requested_by']); ?></small></td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-3">Belum ada log aktivitas</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer text-center">
                    <a href="/admin/logs.php?type=algorithm" class="btn btn-sm btn-outline-secondary">Lihat Semua Log</a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Include footer
include('../templates/admin-footer.php');
?>