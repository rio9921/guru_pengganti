<?php
/**
 * GuruSinergi - Add Review Page
 * 
 * Halaman untuk menambahkan ulasan setelah penugasan selesai
 */

// Include file konfigurasi
require_once '../config/config.php';

// Include file database
require_once '../config/database.php';

// Include file functions
require_once '../includes/functions.php';

// Include file auth functions
require_once '../includes/auth-functions.php';

// Cek login user
check_access('all');

// Inisialisasi variabel
$current_user = get_app_current_user();
$is_guru = $current_user['user_type'] == 'guru';
$is_sekolah = $current_user['user_type'] == 'sekolah';

// Ambil ID penugasan dari URL
$assignment_id = isset($_GET['assignment_id']) ? intval($_GET['assignment_id']) : 0;

if (!$assignment_id) {
    set_error_message('ID penugasan tidak valid.');
    redirect(url('assignments/my-assignments.php'));
}

// Ambil data penugasan
$conn = db_connect();
$stmt = $conn->prepare("
    SELECT a.*, u_guru.full_name as guru_name, u_sekolah.full_name as sekolah_name, s.nama_sekolah
    FROM assignments a
    LEFT JOIN users u_guru ON a.guru_id = u_guru.id
    JOIN users u_sekolah ON a.sekolah_id = u_sekolah.id
    JOIN profiles_sekolah s ON u_sekolah.id = s.user_id
    WHERE a.id = ?
");
$stmt->execute([$assignment_id]);
$assignment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$assignment) {
    set_error_message('Penugasan tidak ditemukan.');
    redirect(url('assignments/my-assignments.php'));
}

// Periksa apakah pengguna terlibat dalam penugasan
$is_involved = ($is_guru && $assignment['guru_id'] == $current_user['id']) || 
               ($is_sekolah && $assignment['sekolah_id'] == $current_user['id']);

if (!$is_involved) {
    set_error_message('Anda tidak memiliki akses ke penugasan ini.');
    redirect(url('assignments/my-assignments.php'));
}

// Periksa status penugasan (harus completed)
if ($assignment['status'] != 'completed') {
    set_error_message('Anda hanya dapat memberikan ulasan setelah penugasan selesai.');
    redirect(url('assignments/detail.php?id=' . $assignment_id));
}

// Tentukan siapa yang akan diulas
if ($is_guru) {
    $reviewer_id = $current_user['id'];
    $reviewee_id = $assignment['sekolah_id'];
    $reviewee_name = $assignment['nama_sekolah'];
} else {
    $reviewer_id = $current_user['id'];
    $reviewee_id = $assignment['guru_id'];
    $reviewee_name = $assignment['guru_name'];
}

// Periksa apakah sudah memberikan ulasan sebelumnya
$stmt = $conn->prepare("
    SELECT id FROM reviews 
    WHERE assignment_id = ? AND reviewer_id = ?
");
$stmt->execute([$assignment_id, $reviewer_id]);
$existing_review = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existing_review) {
    set_error_message('Anda sudah memberikan ulasan untuk penugasan ini.');
    redirect(url('assignments/detail.php?id=' . $assignment_id));
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
    $comment = sanitize($_POST['comment']);
    
    // Validasi input
    $errors = [];
    
    if ($rating < 1 || $rating > 5) {
        $errors[] = 'Rating harus antara 1 sampai 5.';
    }
    
    if (empty($comment)) {
        $errors[] = 'Komentar harus diisi.';
    }
    
    if (empty($errors)) {
        // Simpan ulasan ke database
        $stmt = $conn->prepare("
            INSERT INTO reviews (assignment_id, reviewer_id, reviewee_id, rating, comment)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        if ($stmt->execute([$assignment_id, $reviewer_id, $reviewee_id, $rating, $comment])) {
            // Update rating rata-rata
            if ($is_sekolah) {
                // Update rating guru
                $stmt = $conn->prepare("
                    SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews
                    FROM reviews
                    WHERE reviewee_id = ?
                ");
                $stmt->execute([$reviewee_id]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $avg_rating = round($result['avg_rating'], 2);
                $total_reviews = $result['total_reviews'];
                
                $stmt = $conn->prepare("
                    UPDATE profiles_guru
                    SET rating = ?, total_reviews = ?
                    WHERE user_id = ?
                ");
                $stmt->execute([$avg_rating, $total_reviews, $reviewee_id]);
            }
            
            set_success_message('Ulasan berhasil ditambahkan. Terima kasih atas feedback Anda!');
            redirect(url('assignments/detail.php?id=' . $assignment_id));
        } else {
            set_error_message('Terjadi kesalahan saat menyimpan ulasan. Silakan coba lagi.');
        }
    } else {
        // Set error message
        set_error_message(implode('<br>', $errors));
    }
}

// Set variabel untuk page title
$page_title = 'Tambah Ulasan';

// Include header
include_once '../templates/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Tambah Ulasan</h1>
    <p class="page-description">Berikan penilaian dan feedback untuk penugasan yang telah selesai</p>
</div>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <h2 class="card-title">Detail Penugasan</h2>
            </div>
            <div class="card-body">
                <div class="assignment-summary">
                    <h3 class="assignment-title"><?php echo $assignment['judul']; ?></h3>
                    
                    <div class="assignment-meta">
                        <div class="meta-item">
                            <i class="fas fa-calendar-alt"></i>
                            <span class="meta-label">Periode:</span>
                            <span class="meta-value">
                                <?php echo date('d M Y', strtotime($assignment['tanggal_mulai'])); ?> - 
                                <?php echo date('d M Y', strtotime($assignment['tanggal_selesai'])); ?>
                            </span>
                        </div>
                        
                        <div class="meta-item">
                            <i class="fas fa-book"></i>
                            <span class="meta-label">Mata Pelajaran:</span>
                            <span class="meta-value"><?php echo $assignment['mata_pelajaran']; ?></span>
                        </div>
                        
                        <div class="meta-item">
                            <i class="fas fa-users"></i>
                            <span class="meta-label">Tingkat Kelas:</span>
                            <span class="meta-value"><?php echo $assignment['tingkat_kelas']; ?></span>
                        </div>
                        
                        <div class="meta-item">
                            <?php if ($is_guru): ?>
                            <i class="fas fa-school"></i>
                            <span class="meta-label">Sekolah:</span>
                            <span class="meta-value"><?php echo $assignment['nama_sekolah']; ?></span>
                            <?php else: ?>
                            <i class="fas fa-user-graduate"></i>
                            <span class="meta-label">Guru:</span>
                            <span class="meta-value"><?php echo $assignment['guru_name']; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h2 class="card-title">Berikan Ulasan untuk <?php echo $reviewee_name; ?></h2>
            </div>
            <div class="card-body">
                <form method="post" action="" id="reviewForm">
                    <div class="form-group text-center">
                        <label class="form-label">Rating <span class="text-danger">*</span></label>
                        <div class="rating-selector">
                            <div class="stars">
                                <input type="radio" name="rating" id="rating-5" value="5" required>
                                <label for="rating-5"><i class="fas fa-star"></i></label>
                                
                                <input type="radio" name="rating" id="rating-4" value="4">
                                <label for="rating-4"><i class="fas fa-star"></i></label>
                                
                                <input type="radio" name="rating" id="rating-3" value="3">
                                <label for="rating-3"><i class="fas fa-star"></i></label>
                                
                                <input type="radio" name="rating" id="rating-2" value="2">
                                <label for="rating-2"><i class="fas fa-star"></i></label>
                                
                                <input type="radio" name="rating" id="rating-1" value="1">
                                <label for="rating-1"><i class="fas fa-star"></i></label>
                            </div>
                            <div class="rating-text">Pilih Rating</div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="comment" class="form-label">Komentar <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="comment" name="comment" rows="5" required
                                  placeholder="Bagikan pengalaman Anda bekerja sama dengan <?php echo $reviewee_name; ?>..."><?php echo $_POST['comment'] ?? ''; ?></textarea>
                    </div>
                    
                    <div class="form-tips">
                        <h3>Tips Menulis Ulasan yang Baik:</h3>
                        <ul>
                            <li>Jelaskan pengalaman Anda secara spesifik</li>
                            <li>Berikan komentar yang konstruktif dan objektif</li>
                            <li>Sebutkan kelebihan dan area yang dapat ditingkatkan</li>
                            <li>Hindari bahasa kasar atau tidak pantas</li>
                        </ul>
                    </div>
                    
                    <div class="mt-4 text-center">
                        <button type="submit" class="btn btn-primary btn-lg">Kirim Ulasan</button>
                        <a href="<?php echo url('assignments/detail.php?id=' . $assignment_id); ?>" class="btn btn-outline btn-lg">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
/* Assignment Summary */
.assignment-summary {
    margin-bottom: 20px;
}

.assignment-title {
    font-size: 1.3rem;
    margin-bottom: 15px;
    color: #343a40;
}

.assignment-meta {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.meta-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
}

.meta-item i {
    color: #007bff;
    width: 20px;
    text-align: center;
    margin-top: 4px;
}

.meta-label {
    font-weight: 600;
    margin-right: 5px;
    min-width: 120px;
}

/* Rating Selector */
.rating-selector {
    margin: 20px 0;
}

.stars {
    display: flex;
    justify-content: center;
    flex-direction: row-reverse;
    font-size: 2.5rem;
}

.stars input[type="radio"] {
    display: none;
}

.stars label {
    color: #ddd;
    cursor: pointer;
    padding: 0 5px;
    transition: all 0.2s ease;
}

.stars label:hover,
.stars label:hover ~ label,
.stars input[type="radio"]:checked ~ label {
    color: #ffc107;
}

.rating-text {
    margin-top: 10px;
    font-size: 1rem;
    color: #6c757d;
}

/* Form Tips */
.form-tips {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    margin-top: 20px;
}

.form-tips h3 {
    font-size: 1rem;
    margin-bottom: 10px;
    color: #495057;
}

.form-tips ul {
    padding-left: 20px;
    margin-bottom: 0;
}

.form-tips li {
    font-size: 0.9rem;
    color: #6c757d;
    margin-bottom: 5px;
}

.form-tips li:last-child {
    margin-bottom: 0;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ratingInputs = document.querySelectorAll('input[name="rating"]');
    const ratingText = document.querySelector('.rating-text');
    
    const ratingDescriptions = {
        1: 'Sangat Buruk',
        2: 'Buruk',
        3: 'Cukup',
        4: 'Baik',
        5: 'Sangat Baik'
    };
    
    ratingInputs.forEach(function(input) {
        input.addEventListener('change', function(e) {
            const rating = e.target.value;
            ratingText.textContent = ratingDescriptions[rating];
        });
    });
    
    // Form validation
    const form = document.getElementById('reviewForm');
    
    form.addEventListener('submit', function(e) {
        const selectedRating = form.querySelector('input[name="rating"]:checked');
        const comment = form.querySelector('#comment').value.trim();
        
        if (!selectedRating) {
            e.preventDefault();
            alert('Silakan pilih rating terlebih dahulu.');
            return;
        }
        
        if (!comment) {
            e.preventDefault();
            alert('Silakan isi komentar Anda.');
            return;
        }
    });
});
</script>

<?php
// Include footer
include_once '../templates/footer.php';
?>