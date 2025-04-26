<?php
/**
 * GuruSinergi - About Page
 * 
 * Halaman tentang platform GuruSinergi
 */

// Include file konfigurasi
require_once 'config/config.php';

// Include file database
require_once 'config/database.php';

// Include file functions
require_once 'includes/functions.php';

// Set variabel untuk page title
$page_title = 'Tentang Kami';

// Include header
include_once 'templates/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Tentang GuruSinergi</h1>
    <p class="page-description">Menghubungkan Guru dan Sekolah untuk Pendidikan yang Lebih Baik</p>
</div>

<div class="about-section">
    <div class="card mb-5">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h2 class="section-title">Visi & Misi</h2>
                    <div class="content-block">
                        <h3>Visi</h3>
                        <p>Menjadi platform terdepan yang menghubungkan guru dan sekolah di Indonesia, menciptakan ekosistem pendidikan yang inklusif dan berkualitas.</p>
                        
                        <h3 class="mt-4">Misi</h3>
                        <ul>
                            <li>Membantu guru perempuan menyeimbangkan tanggung jawab keluarga dan karier profesional.</li>
                            <li>Menyediakan akses cepat bagi sekolah untuk mendapatkan guru pengganti berkualitas.</li>
                            <li>Memastikan kontinuitas pendidikan berkualitas untuk siswa meski guru utama berhalangan.</li>
                            <li>Menciptakan lapangan kerja fleksibel bagi guru.</li>
                            <li>Membangun komunitas pendidikan yang saling mendukung.</li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="about-image">
                        <img src="<?php echo asset('images/about-vision.svg'); ?>" alt="Visi GuruSinergi" class="img-fluid">
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card mb-5">
        <div class="card-body">
            <h2 class="section-title text-center mb-4">Cerita Kami</h2>
            
            <div class="content-block">
                <p>GuruSinergi lahir dari pengamatan terhadap tantangan yang dihadapi oleh institusi pendidikan di Indonesia, khususnya ketika seorang guru berhalangan hadir. Sering kali, sekolah kesulitan menemukan guru pengganti yang berkualitas dalam waktu singkat, sementara banyak guru yang memiliki waktu fleksibel tidak mendapatkan kesempatan untuk mengajar.</p>
                
                <p>Didirikan pada tahun 2023 di Pekanbaru, GuruSinergi bertujuan untuk menjembatani kesenjangan ini dan menciptakan solusi yang menguntungkan semua pihak: sekolah mendapatkan guru pengganti berkualitas dengan cepat, guru mendapatkan kesempatan mengajar yang fleksibel, dan siswa tetap menerima pendidikan yang berkualitas meski guru utama mereka berhalangan.</p>
                
                <p>Di GuruSinergi, kami percaya bahwa pendidikan adalah investasi terpenting untuk masa depan. Dengan menghubungkan guru dan sekolah, kami tidak hanya menciptakan ekosistem pendidikan yang lebih efisien, tetapi juga mendukung perkembangan profesional guru dan memastikan kontinuitas pembelajaran bagi siswa.</p>
            </div>
        </div>
    </div>
    
    <div class="card mb-5">
        <div class="card-body">
            <h2 class="section-title text-center mb-4">Keunggulan Kami</h2>
            
            <div class="features-grid">
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3 class="feature-title">Verifikasi Ketat</h3>
                    <p class="feature-desc">Semua guru melewati proses verifikasi menyeluruh untuk memastikan kualitas dan profesionalisme.</p>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h3 class="feature-title">Pencocokan Cepat</h3>
                    <p class="feature-desc">Algoritma cerdas mencocokkan guru dengan kebutuhan sekolah dalam hitungan menit.</p>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-lock"></i>
                    </div>
                    <h3 class="feature-title">Keamanan Data</h3>
                    <p class="feature-desc">Perlindungan data pribadi dengan standar keamanan tinggi untuk semua pengguna.</p>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <h3 class="feature-title">Sistem Penilaian</h3>
                    <p class="feature-desc">Ulasan dan penilaian untuk memastikan kualitas layanan terus terjaga.</p>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3 class="feature-title">Fleksibilitas</h3>
                    <p class="feature-desc">Guru dapat memilih penugasan sesuai ketersediaan waktu dan minat mereka.</p>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <h3 class="feature-title">Materi Pembelajaran</h3>
                    <p class="feature-desc">Akses ke bank materi pembelajaran untuk memudahkan guru pengganti mempersiapkan kelas.</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card mb-5">
        <div class="card-body">
            <h2 class="section-title text-center mb-4">Tim Kami</h2>
            
            <div class="team-grid">
                <div class="team-member">
                    <div class="member-avatar">
                        <div class="avatar-placeholder">R</div>
                    </div>
                    <h3 class="member-name">Rahmat</h3>
                    <p class="member-role">Founder & CEO</p>
                    <p class="member-desc">Pengalaman 10+ tahun di bidang pendidikan dan teknologi.</p>
                </div>
                
                <div class="team-member">
                    <div class="member-avatar">
                        <div class="avatar-placeholder">F</div>
                    </div>
                    <h3 class="member-name">Fitri</h3>
                    <p class="member-role">Co-Founder & Education Lead</p>
                    <p class="member-desc">Mantan guru dan konsultan pendidikan dengan pengalaman 8+ tahun.</p>
                </div>
                
                <div class="team-member">
                    <div class="member-avatar">
                        <div class="avatar-placeholder">B</div>
                    </div>
                    <h3 class="member-name">Budi</h3>
                    <p class="member-role">CTO</p>
                    <p class="member-desc">Developer berpengalaman 15+ tahun dalam pengembangan aplikasi web.</p>
                </div>
                
                <div class="team-member">
                    <div class="member-avatar">
                        <div class="avatar-placeholder">S</div>
                    </div>
                    <h3 class="member-name">Siti</h3>
                    <p class="member-role">Head of Community</p>
                    <p class="member-desc">Spesialis hubungan masyarakat dan pemberdayaan komunitas.</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card mb-5">
        <div class="card-body">
            <div class="contact-section">
                <div class="row">
                    <div class="col-md-6">
                        <h2 class="section-title">Hubungi Kami</h2>
                        <div class="content-block">
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
                            
                            <div class="social-links mt-4">
                                <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                                <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                                <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                                <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="map-container">
                            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15956.713627770456!2d101.4242611!3d0.5686257!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31d5ab80396db749%3A0x8eea61lce12345!2sUmban%20Sari%2C%20Rumbai%2C%20Pekanbaru%20City%2C%20Riau!5e0!3m2!1sen!2sid!4v1618486700000!5m2!1sen!2sid" width="100%" height="350" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CTA Section -->
<section class="cta-section">
    <div class="cta-overlay"></div>
    <div class="container">
        <div class="cta-content">
            <h2 class="cta-title">Bergabunglah dengan GuruSinergi Hari Ini</h2>
            <p class="cta-description">
                Baik Anda seorang guru yang mencari fleksibilitas atau sekolah yang membutuhkan guru pengganti berkualitas, GuruSinergi hadir untuk membantu Anda.
            </p>
            <div class="cta-buttons">
                <a href="<?php echo url('register.php?type=guru'); ?>" class="cta-btn-primary">Daftar Sebagai Guru</a>
                <a href="<?php echo url('register.php?type=sekolah'); ?>" class="cta-btn-secondary">Daftar Sebagai Sekolah</a>
            </div>
        </div>
    </div>
</section>

<?php
// Include footer
include_once 'templates/footer.php';
?>