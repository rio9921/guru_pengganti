<?php
/**
 * GuruSinergi - Create Assignment Page
 * 
 * Halaman untuk membuat penugasan baru oleh sekolah
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

// Cek login user dan pastikan user adalah sekolah
check_access('sekolah');

// Cek apakah profil sudah diverifikasi
check_profile_verified_status();

// Inisialisasi variabel
$current_user = get_app_current_user();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $judul = sanitize($_POST['judul']);
    $deskripsi = sanitize($_POST['deskripsi']);
    $mata_pelajaran = sanitize($_POST['mata_pelajaran']);
    $tingkat_kelas = sanitize($_POST['tingkat_kelas']);
    $tanggal_mulai = sanitize($_POST['tanggal_mulai']);
    $tanggal_selesai = sanitize($_POST['tanggal_selesai']);
    $jam_mulai = !empty($_POST['jam_mulai']) ? sanitize($_POST['jam_mulai']) : null;
    $jam_selesai = !empty($_POST['jam_selesai']) ? sanitize($_POST['jam_selesai']) : null;
    $is_regular = isset($_POST['is_regular']) ? 1 : 0;
    $gaji = sanitize($_POST['gaji']);
    $persyaratan = sanitize($_POST['persyaratan']);
    
    // Validasi input
    $errors = [];
    
    if (empty($judul)) {
        $errors[] = 'Judul penugasan harus diisi.';
    }
    
    if (empty($mata_pelajaran)) {
        $errors[] = 'Mata pelajaran harus dipilih.';
    }
    
    if (empty($tingkat_kelas)) {
        $errors[] = 'Tingkat kelas harus dipilih.';
    }
    
    if (empty($tanggal_mulai)) {
        $errors[] = 'Tanggal mulai harus diisi.';
    }
    
    if (empty($tanggal_selesai)) {
        $errors[] = 'Tanggal selesai harus diisi.';
    }
    
    if (!empty($tanggal_mulai) && !empty($tanggal_selesai) && $tanggal_mulai > $tanggal_selesai) {
        $errors[] = 'Tanggal mulai tidak boleh lebih besar dari tanggal selesai.';
    }
    
    if (empty($gaji)) {
        $errors[] = 'Gaji harus diisi.';
    } elseif (!is_numeric($gaji) || $gaji <= 0) {
        $errors[] = 'Gaji harus berupa angka positif.';
    }
    
    if (empty($errors)) {
        // Format gaji menjadi numerik
        $gaji = (float) str_replace(',', '', $gaji);
        
        // Data penugasan
        $assignment_data = [
            'sekolah_id' => $current_user['id'],
            'judul' => $judul,
            'deskripsi' => $deskripsi,
            'mata_pelajaran' => $mata_pelajaran,
            'tingkat_kelas' => $tingkat_kelas,
            'tanggal_mulai' => $tanggal_mulai,
            'tanggal_selesai' => $tanggal_selesai,
            'jam_mulai' => $jam_mulai,
            'jam_selesai' => $jam_selesai,
            'is_regular' => $is_regular,
            'gaji' => $gaji,
            'persyaratan' => $persyaratan
        ];
        
        // Buat penugasan baru
        $assignment_id = create_assignment($assignment_data);
        
        if ($assignment_id) {
            set_success_message('Penugasan berhasil dibuat. Guru yang cocok akan dinotifikasi.');
            redirect(url('assignments/detail.php?id=' . $assignment_id));
        } else {
            set_error_message('Terjadi kesalahan saat membuat penugasan. Silakan coba lagi.');
        }
    } else {
        // Set error message
        set_error_message(implode('<br>', $errors));
    }
}

// Set variabel untuk page title
$page_title = 'Buat Penugasan Baru';

// Include header
include_once '../templates/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Buat Penugasan Baru</h1>
    <p class="page-description">Isi formulir di bawah ini untuk membuat penugasan guru pengganti baru.</p>
</div>

<div class="card">
    <div class="card-body">
        <form method="post" action="" id="createAssignmentForm">
            <div class="form-group">
                <label for="judul" class="form-label">Judul Penugasan <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="judul" name="judul" required value="<?php echo $_POST['judul'] ?? ''; ?>">
                <small class="form-text">Contoh: Guru Matematika SMP Kelas 8 untuk Semester Genap</small>
            </div>
            
            <div class="form-group">
                <label for="deskripsi" class="form-label">Deskripsi Penugasan</label>
                <textarea class="form-control" id="deskripsi" name="deskripsi" rows="4"><?php echo $_POST['deskripsi'] ?? ''; ?></textarea>
                <small class="form-text">Jelaskan detail penugasan, materi yang akan diajarkan, dan informasi relevan lainnya.</small>
            </div>
            
            <div class="row">
                <div class="col-12 col-md-6">
                    <div class="form-group">
                        <label for="mata_pelajaran" class="form-label">Mata Pelajaran <span class="text-danger">*</span></label>
                        <select name="mata_pelajaran" id="mata_pelajaran" class="form-select" required>
                            <?php foreach (get_mata_pelajaran_options() as $value => $label): ?>
                                <option value="<?php echo $value; ?>" <?php echo (isset($_POST['mata_pelajaran']) && $_POST['mata_pelajaran'] == $value) ? 'selected' : ''; ?>><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="form-group">
                        <label for="tingkat_kelas" class="form-label">Tingkat Kelas <span class="text-danger">*</span></label>
                        <select name="tingkat_kelas" id="tingkat_kelas" class="form-select" required>
                            <?php foreach (get_tingkat_kelas_options() as $value => $label): ?>
                                <option value="<?php echo $value; ?>" <?php echo (isset($_POST['tingkat_kelas']) && $_POST['tingkat_kelas'] == $value) ? 'selected' : ''; ?>><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-12 col-md-6">
                    <div class="form-group">
                        <label for="tanggal_mulai" class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="tanggal_mulai" name="tanggal_mulai" required value="<?php echo $_POST['tanggal_mulai'] ?? ''; ?>">
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="form-group">
                        <label for="tanggal_selesai" class="form-label">Tanggal Selesai <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="tanggal_selesai" name="tanggal_selesai" required value="<?php echo $_POST['tanggal_selesai'] ?? ''; ?>">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-12 col-md-6">
                    <div class="form-group">
                        <label for="jam_mulai" class="form-label">Jam Mulai</label>
                        <input type="time" class="form-control" id="jam_mulai" name="jam_mulai" value="<?php echo $_POST['jam_mulai'] ?? ''; ?>">
                        <small class="form-text">Opsional. Format: JJ:MM (24 jam)</small>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="form-group">
                        <label for="jam_selesai" class="form-label">Jam Selesai</label>
                        <input type="time" class="form-control" id="jam_selesai" name="jam_selesai" value="<?php echo $_POST['jam_selesai'] ?? ''; ?>">
                        <small class="form-text">Opsional. Format: JJ:MM (24 jam)</small>
                    </div>
                </div>
            </div>
            
            <div class="form-check mt-3">
                <input type="checkbox" class="form-check-input" id="is_regular" name="is_regular" <?php echo (isset($_POST['is_regular']) && $_POST['is_regular']) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="is_regular">Jadwal Rutin (mengajar pada hari yang sama setiap minggu)</label>
            </div>
            
            <div class="form-group mt-4">
                <label for="gaji" class="form-label">Gaji/Honor (Rp) <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="gaji" name="gaji" required value="<?php echo $_POST['gaji'] ?? ''; ?>">
                <small class="form-text">
                    Masukkan jumlah gaji/honor untuk keseluruhan periode penugasan. 
                    Contoh: 1500000 (tanpa tanda titik atau koma)
                </small>
            </div>
            
            <div class="form-group">
                <label for="persyaratan" class="form-label">Persyaratan Tambahan</label>
                <textarea class="form-control" id="persyaratan" name="persyaratan" rows="3"><?php echo $_POST['persyaratan'] ?? ''; ?></textarea>
                <small class="form-text">
                    Persyaratan khusus untuk guru pengganti, misalnya: pengalaman minimal, keterampilan 
                    khusus, atau preferensi lainnya.
                </small>
            </div>
            
            <div class="form-group mt-4">
                <p><strong>Penting:</strong></p>
                <ul>
                    <li>Setelah penugasan dibuat, guru yang cocok akan menerima notifikasi.</li>
                    <li>Anda dapat melihat lamaran guru dan memilih guru yang paling sesuai.</li>
                    <li>Biaya platform sebesar <?php echo config('platform_fee'); ?>% akan ditambahkan ke total gaji yang Anda tentukan.</li>
                </ul>
            </div>
            
            <div class="mt-4 text-center">
                <button type="submit" class="btn btn-primary btn-lg">Buat Penugasan</button>
                <a href="<?php echo url('dashboard.php'); ?>" class="btn btn-outline">Batal</a>
            </div>
        </form>
    </div>
</div>

<!-- Script untuk validasi form -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('createAssignmentForm');
    
    form.addEventListener('submit', function(event) {
        let isValid = true;
        let errorMessages = [];
        
        // Validasi tanggal
        const tanggalMulai = document.getElementById('tanggal_mulai').value;
        const tanggalSelesai = document.getElementById('tanggal_selesai').value;
        
        if (tanggalMulai && tanggalSelesai) {
            if (new Date(tanggalMulai) > new Date(tanggalSelesai)) {
                isValid = false;
                errorMessages.push('Tanggal mulai tidak boleh lebih besar dari tanggal selesai.');
            }
            
            if (new Date(tanggalMulai) < new Date()) {
                // Tanggal mulai tidak boleh di masa lalu, tapi berikan toleransi 1 hari
                const today = new Date();
                today.setDate(today.getDate() - 1);
                
                if (new Date(tanggalMulai) < today) {
                    isValid = false;
                    errorMessages.push('Tanggal mulai tidak boleh di masa lalu.');
                }
            }
        }
        
        // Validasi jam
        const jamMulai = document.getElementById('jam_mulai').value;
        const jamSelesai = document.getElementById('jam_selesai').value;
        
        if (jamMulai && jamSelesai) {
            if (jamMulai >= jamSelesai) {
                isValid = false;
                errorMessages.push('Jam mulai harus lebih kecil dari jam selesai.');
            }
        }
        
        // Validasi gaji
        const gaji = document.getElementById('gaji').value;
        
        if (gaji) {
            const numericGaji = Number(gaji.replace(/[^\d]/g, ''));
            
            if (isNaN(numericGaji) || numericGaji <= 0) {
                isValid = false;
                errorMessages.push('Gaji harus berupa angka positif.');
            }
        }
        
        if (!isValid) {
            event.preventDefault();
            alert('Mohon perbaiki kesalahan berikut:\n' + errorMessages.join('\n'));
        }
    });
    
    // Format input gaji
    const gajiInput = document.getElementById('gaji');
    
    gajiInput.addEventListener('input', function(e) {
        // Hapus semua karakter non-digit
        let value = this.value.replace(/[^\d]/g, '');
        
        // Format dengan pemisah ribuan
        if (value !== '') {
            value = Number(value).toLocaleString('id-ID');
        }
        
        this.value = value;
    });
});
</script>

<?php
// Include footer
include_once '../templates/footer.php';
?>