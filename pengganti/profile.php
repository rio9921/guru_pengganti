<?php
/**
 * GuruSinergi - Profile Page
 * 
 * Halaman untuk melihat dan mengedit profil pengguna
 */

// Include file konfigurasi
require_once 'config/config.php';

// Include file database
require_once 'config/database.php';

// Include file functions
require_once 'includes/functions.php';

// Include file auth functions
require_once 'includes/auth-functions.php';

// Cek login user
check_access('all');

// Inisialisasi variabel
$current_user = get_app_current_user();
$edit_mode = isset($_GET['edit']) && $_GET['edit'] == 'true';

// Handle form submission untuk update profil umum
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $full_name = sanitize($_POST['full_name']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    
    // Validasi input
    if (empty($full_name) || empty($phone)) {
        set_error_message('Nama lengkap dan nomor telepon harus diisi.');
    } else {
        // Update profil
        $conn = db_connect();
        $stmt = $conn->prepare("UPDATE users SET full_name = ?, phone = ?, address = ? WHERE id = ?");
        
        if ($stmt->execute([$full_name, $phone, $address, $current_user['id']])) {
            // Upload profile image jika ada
            if (!empty($_FILES['profile_image']['name'])) {
                $upload_dir = 'uploads/profile/' . $current_user['id'] . '/';
                $profile_image = $_FILES['profile_image'];
                
                $image_upload = upload_file($profile_image, $upload_dir, ['jpg', 'jpeg', 'png']);
                if ($image_upload['status']) {
                    $image_path = $image_upload['file_path'];
                    
                    $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                    $stmt->execute([$image_path, $current_user['id']]);
                } else {
                    set_error_message($image_upload['message']);
                }
            }
            
            set_success_message('Profil berhasil diperbarui.');
            redirect(url('profile.php'));
        } else {
            set_error_message('Terjadi kesalahan saat memperbarui profil.');
        }
    }
}

// Handle form submission untuk update profil guru
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile_guru'])) {
    if ($current_user['user_type'] != 'guru') {
        set_error_message('Anda tidak memiliki akses ke fitur ini.');
        redirect(url('profile.php'));
    }
    
    $pendidikan = sanitize($_POST['pendidikan']);
    $pengalaman = sanitize($_POST['pengalaman']);
    $keahlian = sanitize($_POST['keahlian']);
    $mata_pelajaran = sanitize($_POST['mata_pelajaran']);
    $tingkat_mengajar = sanitize($_POST['tingkat_mengajar']);
    $is_available = isset($_POST['is_available']) ? 1 : 0;
    
    // Validasi input
    if (empty($pendidikan) || empty($pengalaman) || empty($keahlian) || empty($mata_pelajaran) || empty($tingkat_mengajar)) {
        set_error_message('Semua field harus diisi.');
    } else {
        // Update profil guru
        $conn = db_connect();
        $stmt = $conn->prepare("
            UPDATE profiles_guru SET 
            pendidikan = ?, 
            pengalaman = ?, 
            keahlian = ?, 
            mata_pelajaran = ?, 
            tingkat_mengajar = ?,
            is_available = ?
            WHERE user_id = ?
        ");
        
        if ($stmt->execute([
            $pendidikan, $pengalaman, $keahlian, $mata_pelajaran, 
            $tingkat_mengajar, $is_available, $current_user['id']
        ])) {
            // Upload dokumen jika ada
            $upload_dir = 'uploads/guru/' . $current_user['id'] . '/';
            
            // Upload CV
            if (!empty($_FILES['dokumen_cv']['name'])) {
                $cv_upload = upload_file($_FILES['dokumen_cv'], $upload_dir, ['pdf', 'doc', 'docx']);
                if ($cv_upload['status']) {
                    $cv_path = $cv_upload['file_path'];
                    $stmt = $conn->prepare("UPDATE profiles_guru SET dokumen_cv = ? WHERE user_id = ?");
                    $stmt->execute([$cv_path, $current_user['id']]);
                } else {
                    set_error_message($cv_upload['message']);
                }
            }
            
            // Upload Ijazah
            if (!empty($_FILES['dokumen_ijazah']['name'])) {
                $ijazah_upload = upload_file($_FILES['dokumen_ijazah'], $upload_dir, ['pdf', 'jpg', 'jpeg', 'png']);
                if ($ijazah_upload['status']) {
                    $ijazah_path = $ijazah_upload['file_path'];
                    $stmt = $conn->prepare("UPDATE profiles_guru SET dokumen_ijazah = ? WHERE user_id = ?");
                    $stmt->execute([$ijazah_path, $current_user['id']]);
                } else {
                    set_error_message($ijazah_upload['message']);
                }
            }
            
            // Upload KTP
            if (!empty($_FILES['dokumen_ktp']['name'])) {
                $ktp_upload = upload_file($_FILES['dokumen_ktp'], $upload_dir, ['jpg', 'jpeg', 'png']);
                if ($ktp_upload['status']) {
                    $ktp_path = $ktp_upload['file_path'];
                    $stmt = $conn->prepare("UPDATE profiles_guru SET dokumen_ktp = ? WHERE user_id = ?");
                    $stmt->execute([$ktp_path, $current_user['id']]);
                } else {
                    set_error_message($ktp_upload['message']);
                }
            }
            
            set_success_message('Profil guru berhasil diperbarui.');
            redirect(url('profile.php'));
        } else {
            set_error_message('Terjadi kesalahan saat memperbarui profil guru.');
        }
    }
}

// Handle form submission untuk update profil sekolah
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile_sekolah'])) {
    if ($current_user['user_type'] != 'sekolah') {
        set_error_message('Anda tidak memiliki akses ke fitur ini.');
        redirect(url('profile.php'));
    }
    
    $nama_sekolah = sanitize($_POST['nama_sekolah']);
    $jenis_sekolah = sanitize($_POST['jenis_sekolah']);
    $alamat_lengkap = sanitize($_POST['alamat_lengkap']);
    $kecamatan = sanitize($_POST['kecamatan']);
    $kota = sanitize($_POST['kota']);
    $provinsi = sanitize($_POST['provinsi']);
    $kode_pos = sanitize($_POST['kode_pos']);
    $contact_person = sanitize($_POST['contact_person']);
    $website = sanitize($_POST['website']);
    
    // Validasi input
    if (empty($nama_sekolah) || empty($jenis_sekolah) || empty($alamat_lengkap) || 
        empty($kecamatan) || empty($kota) || empty($provinsi) || 
        empty($kode_pos) || empty($contact_person)) {
        set_error_message('Semua field harus diisi kecuali website.');
    } else {
        // Update profil sekolah
        $conn = db_connect();
        $stmt = $conn->prepare("
            UPDATE profiles_sekolah SET 
            nama_sekolah = ?, 
            jenis_sekolah = ?, 
            alamat_lengkap = ?, 
            kecamatan = ?, 
            kota = ?, 
            provinsi = ?, 
            kode_pos = ?, 
            contact_person = ?, 
            website = ?
            WHERE user_id = ?
        ");
        
        if ($stmt->execute([
            $nama_sekolah, $jenis_sekolah, $alamat_lengkap, $kecamatan, 
            $kota, $provinsi, $kode_pos, $contact_person, $website, 
            $current_user['id']
        ])) {
            // Upload dokumen NPSN jika ada
            if (!empty($_FILES['dokumen_npsn']['name'])) {
                $upload_dir = 'uploads/sekolah/' . $current_user['id'] . '/';
                $npsn_upload = upload_file($_FILES['dokumen_npsn'], $upload_dir, ['pdf', 'jpg', 'jpeg', 'png']);
                
                if ($npsn_upload['status']) {
                    $npsn_path = $npsn_upload['file_path'];
                    $stmt = $conn->prepare("UPDATE profiles_sekolah SET dokumen_npsn = ? WHERE user_id = ?");
                    $stmt->execute([$npsn_path, $current_user['id']]);
                } else {
                    set_error_message($npsn_upload['message']);
                }
            }
            
            set_success_message('Profil sekolah berhasil diperbarui.');
            redirect(url('profile.php'));
        } else {
            set_error_message('Terjadi kesalahan saat memperbarui profil sekolah.');
        }
    }
}

// Ambil data profil terbaru
$conn = db_connect();
$user = $current_user;

if ($current_user['user_type'] == 'guru') {
    $stmt = $conn->prepare("SELECT * FROM profiles_guru WHERE user_id = ?");
    $stmt->execute([$current_user['id']]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    $user['profile'] = $profile;
} elseif ($current_user['user_type'] == 'sekolah') {
    $stmt = $conn->prepare("SELECT * FROM profiles_sekolah WHERE user_id = ?");
    $stmt->execute([$current_user['id']]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    $user['profile'] = $profile;
}

// Set variabel untuk page title
$page_title = $edit_mode ? 'Edit Profil' : 'Profil Saya';

// Include header
include_once 'templates/header.php';
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title"><?php echo $page_title; ?></h1>
            <p class="page-description">
                <?php if ($edit_mode): ?>
                    Edit informasi profil Anda
                <?php else: ?>
                    Lihat dan kelola informasi profil Anda
                <?php endif; ?>
            </p>
        </div>
        <div>
            <?php if ($edit_mode): ?>
                <a href="<?php echo url('profile.php'); ?>" class="btn btn-outline">Batal</a>
            <?php else: ?>
                <a href="<?php echo url('profile.php?edit=true'); ?>" class="btn btn-primary">Edit Profil</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12 col-md-4 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="profile-header text-center">
                    <div class="profile-image mb-3">
                        <?php if (!empty($user['profile_image'])): ?>
                            <img src="<?php echo $user['profile_image']; ?>" alt="<?php echo $user['full_name']; ?>" class="rounded-circle profile-picture">
                        <?php else: ?>
                            <div class="profile-picture-placeholder rounded-circle">
                                <?php echo substr($user['full_name'], 0, 1); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <h2 class="profile-name"><?php echo $user['full_name']; ?></h2>
                    <p class="profile-type">
                        <?php 
                            if ($user['user_type'] == 'guru') echo 'Guru';
                            elseif ($user['user_type'] == 'sekolah') echo 'Sekolah';
                            else echo 'Admin';
                        ?>
                    </p>
                    <div class="verification-badge 
                        <?php echo isset($user['profile']['status_verifikasi']) && $user['profile']['status_verifikasi'] == 'verified' ? 'verified' : 'unverified'; ?>">
                        <?php if (isset($user['profile']['status_verifikasi']) && $user['profile']['status_verifikasi'] == 'verified'): ?>
                            <i class="fas fa-check-circle"></i> Terverifikasi
                        <?php elseif (isset($user['profile']['status_verifikasi']) && $user['profile']['status_verifikasi'] == 'rejected'): ?>
                            <i class="fas fa-times-circle"></i> Ditolak
                        <?php else: ?>
                            <i class="fas fa-clock"></i> Menunggu Verifikasi
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="profile-info mt-4">
                    <div class="info-item">
                        <i class="fas fa-envelope"></i>
                        <span><?php echo $user['email']; ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-phone"></i>
                        <span><?php echo $user['phone']; ?></span>
                    </div>
                    <?php if (!empty($user['address'])): ?>
                    <div class="info-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span><?php echo $user['address']; ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="info-item">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Bergabung: <?php echo date('d M Y', strtotime($user['created_at'])); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-12 col-md-8">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <?php if ($edit_mode): ?>
                        Edit Profil
                    <?php else: ?>
                        Detail Profil
                    <?php endif; ?>
                </h2>
            </div>
            <div class="card-body">
                <?php if ($edit_mode): ?>
                <!-- Form Edit Profil -->
                <form method="post" action="" enctype="multipart/form-data">
                    <h3 class="mb-3">Informasi Umum</h3>
                    
                    <div class="form-group">
                        <label for="full_name" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo $user['full_name']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone" class="form-label">Nomor Telepon <span class="text-danger">*</span></label>
                        <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo $user['phone']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="address" class="form-label">Alamat</label>
                        <textarea class="form-control" id="address" name="address" rows="3"><?php echo $user['address']; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="profile_image" class="form-label">Foto Profil</label>
                        <input type="file" class="form-control" id="profile_image" name="profile_image" accept=".jpg,.jpeg,.png">
                        <small class="form-text">Format file: JPG, JPEG, atau PNG. Maksimal 5MB.</small>
                    </div>
                    
                    <div class="mt-4 text-center">
                        <button type="submit" name="update_profile" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
                
                <?php if ($user['user_type'] == 'guru'): ?>
                <!-- Form Edit Profil Guru -->
                <hr class="my-4">
                <form method="post" action="" enctype="multipart/form-data">
                    <h3 class="mb-3">Informasi Guru</h3>
                    
                    <div class="form-group">
                        <label for="pendidikan" class="form-label">Pendidikan <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="pendidikan" name="pendidikan" rows="3" required><?php echo $user['profile']['pendidikan']; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="pengalaman" class="form-label">Pengalaman Mengajar <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="pengalaman" name="pengalaman" rows="3" required><?php echo $user['profile']['pengalaman']; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="keahlian" class="form-label">Keahlian <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="keahlian" name="keahlian" rows="3" required><?php echo $user['profile']['keahlian']; ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-12 col-md-6">
                            <div class="form-group">
                                <label for="mata_pelajaran" class="form-label">Mata Pelajaran <span class="text-danger">*</span></label>
                                <select name="mata_pelajaran" id="mata_pelajaran" class="form-select" required>
                                    <?php foreach (get_mata_pelajaran_options() as $value => $label): ?>
                                        <option value="<?php echo $value; ?>" <?php echo $user['profile']['mata_pelajaran'] == $value ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="form-group">
                                <label for="tingkat_mengajar" class="form-label">Tingkat Mengajar <span class="text-danger">*</span></label>
                                <select name="tingkat_mengajar" id="tingkat_mengajar" class="form-select" required>
                                    <?php foreach (get_tingkat_kelas_options() as $value => $label): ?>
                                        <option value="<?php echo $value; ?>" <?php echo $user['profile']['tingkat_mengajar'] == $value ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-check mt-3">
                        <input type="checkbox" class="form-check-input" id="is_available" name="is_available" <?php echo $user['profile']['is_available'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_available">Saya tersedia untuk menerima penugasan</label>
                    </div>
                    
                    <div class="form-group mt-3">
                        <label for="dokumen_cv" class="form-label">Update CV</label>
                        <input type="file" class="form-control" id="dokumen_cv" name="dokumen_cv" accept=".pdf,.doc,.docx">
                        <small class="form-text">Format file: PDF, DOC, atau DOCX. Maksimal 5MB.</small>
                        <?php if (!empty($user['profile']['dokumen_cv'])): ?>
                            <div class="mt-2">
                                <a href="<?php echo $user['profile']['dokumen_cv']; ?>" target="_blank" class="btn btn-sm btn-outline">Lihat CV Saat Ini</a>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="dokumen_ijazah" class="form-label">Update Ijazah</label>
                        <input type="file" class="form-control" id="dokumen_ijazah" name="dokumen_ijazah" accept=".pdf,.jpg,.jpeg,.png">
                        <small class="form-text">Format file: PDF, JPG, JPEG, atau PNG. Maksimal 5MB.</small>
                        <?php if (!empty($user['profile']['dokumen_ijazah'])): ?>
                            <div class="mt-2">
                                <a href="<?php echo $user['profile']['dokumen_ijazah']; ?>" target="_blank" class="btn btn-sm btn-outline">Lihat Ijazah Saat Ini</a>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="dokumen_ktp" class="form-label">Update KTP</label>
                        <input type="file" class="form-control" id="dokumen_ktp" name="dokumen_ktp" accept=".jpg,.jpeg,.png">
                        <small class="form-text">Format file: JPG, JPEG, atau PNG. Maksimal 5MB.</small>
                        <?php if (!empty($user['profile']['dokumen_ktp'])): ?>
                            <div class="mt-2">
                                <a href="<?php echo $user['profile']['dokumen_ktp']; ?>" target="_blank" class="btn btn-sm btn-outline">Lihat KTP Saat Ini</a>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mt-4 text-center">
                        <button type="submit" name="update_profile_guru" class="btn btn-primary">Simpan Informasi Guru</button>
                    </div>
                </form>
                
                <?php elseif ($user['user_type'] == 'sekolah'): ?>
                <!-- Form Edit Profil Sekolah -->
                <hr class="my-4">
                <form method="post" action="" enctype="multipart/form-data">
                    <h3 class="mb-3">Informasi Sekolah</h3>
                    
                    <div class="form-group">
                        <label for="nama_sekolah" class="form-label">Nama Sekolah <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nama_sekolah" name="nama_sekolah" value="<?php echo $user['profile']['nama_sekolah']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="jenis_sekolah" class="form-label">Jenis Sekolah <span class="text-danger">*</span></label>
                        <select name="jenis_sekolah" id="jenis_sekolah" class="form-select" required>
                            <?php foreach (get_jenis_sekolah_options() as $value => $label): ?>
                                <option value="<?php echo $value; ?>" <?php echo $user['profile']['jenis_sekolah'] == $value ? 'selected' : ''; ?>><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="alamat_lengkap" class="form-label">Alamat Lengkap <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="alamat_lengkap" name="alamat_lengkap" rows="3" required><?php echo $user['profile']['alamat_lengkap']; ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-12 col-md-4">
                            <div class="form-group">
                                <label for="kecamatan" class="form-label">Kecamatan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="kecamatan" name="kecamatan" value="<?php echo $user['profile']['kecamatan']; ?>" required>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="form-group">
                                <label for="kota" class="form-label">Kota/Kabupaten <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="kota" name="kota" value="<?php echo $user['profile']['kota']; ?>" required>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="form-group">
                                <label for="provinsi" class="form-label">Provinsi <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="provinsi" name="provinsi" value="<?php echo $user['profile']['provinsi']; ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12 col-md-6">
                            <div class="form-group">
                                <label for="kode_pos" class="form-label">Kode Pos <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="kode_pos" name="kode_pos" value="<?php echo $user['profile']['kode_pos']; ?>" required>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="form-group">
                                <label for="contact_person" class="form-label">Nama Kontak <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="contact_person" name="contact_person" value="<?php echo $user['profile']['contact_person']; ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="website" class="form-label">Website Sekolah</label>
                        <input type="url" class="form-control" id="website" name="website" value="<?php echo $user['profile']['website']; ?>" placeholder="http://www.example.com">
                        <small class="form-text">Opsional. Masukkan URL lengkap dengan http:// atau https://</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="dokumen_npsn" class="form-label">Update Dokumen NPSN/Izin Operasional</label>
                        <input type="file" class="form-control" id="dokumen_npsn" name="dokumen_npsn" accept=".pdf,.jpg,.jpeg,.png">
                        <small class="form-text">Format file: PDF, JPG, JPEG, atau PNG. Maksimal 5MB.</small>
                        <?php if (!empty($user['profile']['dokumen_npsn'])): ?>
                            <div class="mt-2">
                                <a href="<?php echo $user['profile']['dokumen_npsn']; ?>" target="_blank" class="btn btn-sm btn-outline">Lihat Dokumen NPSN Saat Ini</a>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mt-4 text-center">
                        <button type="submit" name="update_profile_sekolah" class="btn btn-primary">Simpan Informasi Sekolah</button>
                    </div>
                </form>
                <?php endif; ?>
                
                <?php else: ?>
                <!-- Tampilan Detail Profil -->
                <div class="profile-section mb-4">
                    <h3 class="section-title">Informasi Umum</h3>
                    
                    <div class="info-grid">
                        <div class="info-label">Nama Lengkap</div>
                        <div class="info-value"><?php echo $user['full_name']; ?></div>
                        
                        <div class="info-label">Email</div>
                        <div class="info-value"><?php echo $user['email']; ?></div>
                        
                        <div class="info-label">Nomor Telepon</div>
                        <div class="info-value"><?php echo $user['phone']; ?></div>
                        
                        <?php if (!empty($user['address'])): ?>
                        <div class="info-label">Alamat</div>
                        <div class="info-value"><?php echo $user['address']; ?></div>
                        <?php endif; ?>
                        
                        <div class="info-label">Bergabung Sejak</div>
                        <div class="info-value"><?php echo date('d M Y', strtotime($user['created_at'])); ?></div>
                    </div>
                </div>
                
                <?php if ($user['user_type'] == 'guru'): ?>
                <!-- Detail Profil Guru -->
                <div class="profile-section mb-4">
                    <h3 class="section-title">Informasi Guru</h3>
                    
                    <div class="info-grid">
                        <div class="info-label">Status</div>
                        <div class="info-value">
                            <?php if ($user['profile']['is_available']): ?>
                                <span class="badge badge-success">Tersedia</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Tidak Tersedia</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="info-label">Status Verifikasi</div>
                        <div class="info-value">
                            <?php if ($user['profile']['status_verifikasi'] == 'verified'): ?>
                                <span class="badge badge-success">Terverifikasi</span>
                            <?php elseif ($user['profile']['status_verifikasi'] == 'rejected'): ?>
                                <span class="badge badge-danger">Ditolak</span>
                                <?php if (!empty($user['profile']['catatan_verifikasi'])): ?>
                                    <p class="text-danger mt-1"><?php echo $user['profile']['catatan_verifikasi']; ?></p>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge badge-warning">Menunggu Verifikasi</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="info-label">Pendidikan</div>
                        <div class="info-value"><?php echo $user['profile']['pendidikan']; ?></div>
                        
                        <div class="info-label">Pengalaman Mengajar</div>
                        <div class="info-value"><?php echo $user['profile']['pengalaman']; ?></div>
                        
                        <div class="info-label">Keahlian</div>
                        <div class="info-value"><?php echo $user['profile']['keahlian']; ?></div>
                        
                        <div class="info-label">Mata Pelajaran</div>
                        <div class="info-value"><?php echo $user['profile']['mata_pelajaran']; ?></div>
                        
                        <div class="info-label">Tingkat Mengajar</div>
                        <div class="info-value"><?php echo $user['profile']['tingkat_mengajar']; ?></div>
                        
                        <div class="info-label">Rating</div>
                        <div class="info-value">
                            <div class="rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?php if ($i <= floor($user['profile']['rating'])): ?>
                                        <i class="fas fa-star"></i>
                                    <?php elseif ($i - 0.5 <= $user['profile']['rating']): ?>
                                        <i class="fas fa-star-half-alt"></i>
                                    <?php else: ?>
                                        <i class="far fa-star"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                <span class="rating-value"><?php echo number_format($user['profile']['rating'], 1); ?></span>
                                <span class="text-muted">(<?php echo $user['profile']['total_reviews']; ?> ulasan)</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="document-section mt-4">
                        <h4 class="mb-3">Dokumen</h4>
                        <div class="document-list">
                            <?php if (!empty($user['profile']['dokumen_cv'])): ?>
                                <a href="<?php echo $user['profile']['dokumen_cv']; ?>" target="_blank" class="document-item">
                                    <i class="fas fa-file-pdf"></i>
                                    <span>Curriculum Vitae (CV)</span>
                                </a>
                            <?php endif; ?>
                            
                            <?php if (!empty($user['profile']['dokumen_ijazah'])): ?>
                                <a href="<?php echo $user['profile']['dokumen_ijazah']; ?>" target="_blank" class="document-item">
                                    <i class="fas fa-graduation-cap"></i>
                                    <span>Ijazah</span>
                                </a>
                            <?php endif; ?>
                            
                            <?php if (!empty($user['profile']['dokumen_ktp'])): ?>
                                <a href="<?php echo $user['profile']['dokumen_ktp']; ?>" target="_blank" class="document-item">
                                    <i class="fas fa-id-card"></i>
                                    <span>KTP</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <?php elseif ($user['user_type'] == 'sekolah'): ?>
                <!-- Detail Profil Sekolah -->
                <div class="profile-section mb-4">
                    <h3 class="section-title">Informasi Sekolah</h3>
                    
                    <div class="info-grid">
                        <div class="info-label">Nama Sekolah</div>
                        <div class="info-value"><?php echo $user['profile']['nama_sekolah']; ?></div>
                        
                        <div class="info-label">Status Verifikasi</div>
                        <div class="info-value">
                            <?php if ($user['profile']['status_verifikasi'] == 'verified'): ?>
                                <span class="badge badge-success">Terverifikasi</span>
                            <?php elseif ($user['profile']['status_verifikasi'] == 'rejected'): ?>
                                <span class="badge badge-danger">Ditolak</span>
                                <?php if (!empty($user['profile']['catatan_verifikasi'])): ?>
                                    <p class="text-danger mt-1"><?php echo $user['profile']['catatan_verifikasi']; ?></p>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge badge-warning">Menunggu Verifikasi</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="info-label">Jenis Sekolah</div>
                        <div class="info-value"><?php echo $user['profile']['jenis_sekolah']; ?></div>
                        
                        <div class="info-label">Alamat Lengkap</div>
                        <div class="info-value"><?php echo $user['profile']['alamat_lengkap']; ?></div>
                        
                        <div class="info-label">Kecamatan</div>
                        <div class="info-value"><?php echo $user['profile']['kecamatan']; ?></div>
                        
                        <div class="info-label">Kota/Kabupaten</div>
                        <div class="info-value"><?php echo $user['profile']['kota']; ?></div>
                        
                        <div class="info-label">Provinsi</div>
                        <div class="info-value"><?php echo $user['profile']['provinsi']; ?></div>
                        
                        <div class="info-label">Kode Pos</div>
                        <div class="info-value"><?php echo $user['profile']['kode_pos']; ?></div>
                        
                        <div class="info-label">Nama Kontak</div>
                        <div class="info-value"><?php echo $user['profile']['contact_person']; ?></div>
                        
                        <?php if (!empty($user['profile']['website'])): ?>
                        <div class="info-label">Website</div>
                        <div class="info-value">
                            <a href="<?php echo $user['profile']['website']; ?>" target="_blank"><?php echo $user['profile']['website']; ?></a>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="document-section mt-4">
                        <h4 class="mb-3">Dokumen</h4>
                        <div class="document-list">
                            <?php if (!empty($user['profile']['dokumen_npsn'])): ?>
                                <a href="<?php echo $user['profile']['dokumen_npsn']; ?>" target="_blank" class="document-item">
                                    <i class="fas fa-file-alt"></i>
                                    <span>Dokumen NPSN/Izin Operasional</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once 'templates/footer.php';
?>