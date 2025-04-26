<?php
/**
 * GuruSinergi - Contact Page
 * 
 * Halaman kontak untuk menghubungi tim GuruSinergi
 */

// Include file konfigurasi
require_once 'config/config.php';

// Include file database
require_once 'config/database.php';

// Include file functions
require_once 'includes/functions.php';

// Handle form submission
$message_sent = false;
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $subject = sanitize($_POST['subject']);
    $message = sanitize($_POST['message']);
    
    // Validasi input
    if (empty($name) || empty($email) || empty($message)) {
        set_error_message('Silakan lengkapi semua field yang wajib diisi.');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_error_message('Silakan masukkan alamat email yang valid.');
    } else {
        // Simpan pesan kontak ke database
        $conn = db_connect();
        $stmt = $conn->prepare("
            INSERT INTO contact_messages (name, email, phone, subject, message, status)
            VALUES (?, ?, ?, ?, ?, 'new')
        ");
        
        if ($stmt->execute([$name, $email, $phone, $subject, $message])) {
            // Kirim email notifikasi ke admin
            $admin_email = config('admin_email');
            $email_subject = "Pesan Kontak Baru: " . $subject;
            $email_body = "
                <html>
                <head>
                    <title>Pesan Kontak Baru</title>
                </head>
                <body>
                    <h2>Pesan Kontak Baru dari Website GuruSinergi</h2>
                    <p><strong>Nama:</strong> {$name}</p>
                    <p><strong>Email:</strong> {$email}</p>
                    <p><strong>Telepon:</strong> {$phone}</p>
                    <p><strong>Subjek:</strong> {$subject}</p>
                    <p><strong>Pesan:</strong><br>{$message}</p>
                    <p>Silakan login ke panel admin untuk menanggapi pesan ini.</p>
                </body>
                </html>
            ";
            
            $headers = "From: noreply@gurusinergi.com\r\n";
            $headers .= "Reply-To: {$email}\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            
            // Mencoba mengirim email
            if(mail($admin_email, $email_subject, $email_body, $headers)) {
                $message_sent = true;
                set_success_message('Pesan Anda telah berhasil dikirim. Kami akan menghubungi Anda segera.');
            } else {
                // Pesan tetap disimpan meskipun email gagal terkirim
                $message_sent = true;
                set_success_message('Pesan Anda telah berhasil dikirim, tetapi ada masalah saat mengirim notifikasi email. Tim kami akan tetap meninjau pesan Anda.');
            }
        } else {
            set_error_message('Terjadi kesalahan saat mengirim pesan. Silakan coba lagi nanti.');
        }
    }
}

// Set variabel untuk page title
$page_title = 'Kontak Kami';

// Include header
include_once 'templates/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Hubungi Kami</h1>
    <p class="page-description">Punya pertanyaan atau umpan balik? Jangan ragu untuk menghubungi kami.</p>
</div>

<div class="contact-container">
    <div class="row">
        <div class="col-md-8">
            <!-- Contact Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="card-title">Kirim Pesan</h2>
                </div>
                <div class="card-body">
                    <?php if ($message_sent): ?>
                        <div class="thank-you-message text-center py-5">
                            <div class="thank-you-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h3>Terima Kasih!</h3>
                            <p>Pesan Anda telah berhasil dikirim. Tim kami akan menghubungi Anda segera.</p>
                            <a href="<?php echo url(); ?>" class="btn btn-primary mt-3">Kembali ke Beranda</a>
                        </div>
                    <?php else: ?>
                        <form method="post" action="" data-validate="true">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="name" name="name" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="phone" class="form-label">Nomor Telepon</label>
                                        <input type="tel" class="form-control" id="phone" name="phone">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="subject" class="form-label">Subjek <span class="text-danger">*</span></label>
                                        <select class="form-select" id="subject" name="subject" required>
                                            <option value="">Pilih Subjek</option>
                                            <option value="Informasi Umum">Informasi Umum</option>
                                            <option value="Pendaftaran Guru">Pendaftaran Guru</option>
                                            <option value="Pendaftaran Sekolah">Pendaftaran Sekolah</option>
                                            <option value="Bantuan Teknis">Bantuan Teknis</option>
                                            <option value="Pembayaran">Pembayaran</option>
                                            <option value="Kerjasama">Kerjasama</option>
                                            <option value="Laporan Masalah">Laporan Masalah</option>
                                            <option value="Lainnya">Lainnya</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="message" class="form-label">Pesan <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                            </div>
                            
                            <div class="form-check mt-3">
                                <input type="checkbox" class="form-check-input" id="privacy_agreement" name="privacy_agreement" required>
                                <label class="form-check-label" for="privacy_agreement">
                                    Saya setuju bahwa data saya akan diproses sesuai dengan <a href="<?php echo url('privacy.php'); ?>" target="_blank">Kebijakan Privasi</a>.
                                </label>
                            </div>
                            
                            <div class="mt-4">
                                <button type="submit" name="send_message" class="btn btn-primary">Kirim Pesan</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Contact Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="card-title">Informasi Kontak</h2>
                </div>
                <div class="card-body">
                    <div class="contact-info">
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="contact-details">
                                <h3>Alamat</h3>
                                <p>Jl. Umban Sari<br>Pekanbaru, 28265<br>Indonesia</p>
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="contact-details">
                                <h3>Email</h3>
                                <p><a href="mailto:<?php echo config('admin_email'); ?>"><?php echo config('admin_email'); ?></a></p>
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-phone-alt"></i>
                            </div>
                            <div class="contact-details">
                                <h3>Telepon</h3>
                                <p><a href="tel:<?php echo config('support_phone'); ?>"><?php echo config('support_phone'); ?></a></p>
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fab fa-whatsapp"></i>
                            </div>
                            <div class="contact-details">
                                <h3>WhatsApp</h3>
                                <p><a href="https://wa.me/<?php echo str_replace(['+', ' '], '', config('support_phone')); ?>">Chat dengan Kami</a></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="social-links mt-4">
                        <h3>Media Sosial</h3>
                        <div class="social-icons">
                            <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                            <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                            <a href="#" class="social-icon"><i class="fab fa-linkedin-in"></i></a>
                        </div>
                    </div>
                    
                    <div class="office-hours mt-4">
                        <h3>Jam Operasional</h3>
                        <p>Senin - Jumat: 08.00 - 17.00 WIB</p>
                        <p>Sabtu: 09.00 - 13.00 WIB</p>
                        <p>Minggu & Hari Libur: Tutup</p>
                    </div>
                </div>
            </div>
            
            <!-- FAQ Quick Links -->
            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="card-title">Pertanyaan Umum</h2>
                </div>
                <div class="card-body">
                    <p>Temukan jawaban untuk pertanyaan yang sering diajukan:</p>
                    <ul class="faq-quick-links">
                        <li><a href="<?php echo url('faq.php#faq1'); ?>">Apa itu GuruSinergi?</a></li>
                        <li><a href="<?php echo url('faq.php#faq-g1'); ?>">Siapa yang dapat mendaftar sebagai guru?</a></li>
                        <li><a href="<?php echo url('faq.php#faq-s1'); ?>">Bagaimana cara mendaftar sebagai sekolah?</a></li>
                        <li><a href="<?php echo url('faq.php#faq-p1'); ?>">Metode pembayaran apa yang diterima?</a></li>
                        <li><a href="<?php echo url('faq.php'); ?>">Lihat semua FAQ</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Map Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h2 class="card-title">Lokasi Kami</h2>
        </div>
        <div class="card-body p-0">
            <div class="map-container">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15956.713627770456!2d101.4242611!3d0.5686257!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31d5ab80396db749%3A0x8eea61lce12345!2sUmban%20Sari%2C%20Rumbai%2C%20Pekanbaru%20City%2C%20Riau!5e0!3m2!1sen!2sid!4v1618486700000!5m2!1sen!2sid" width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once 'templates/footer.php';
?>