<?php
/**
 * GuruSinergi - FAQ Page
 * 
 * Halaman pertanyaan yang sering diajukan
 */

// Include file konfigurasi
require_once 'config/config.php';

// Include file database
require_once 'config/database.php';

// Include file functions
require_once 'includes/functions.php';

// Set variabel untuk page title
$page_title = 'FAQ';

// Include header
include_once 'templates/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Pertanyaan yang Sering Diajukan</h1>
    <p class="page-description">Temukan jawaban untuk pertanyaan umum tentang GuruSinergi</p>
</div>

<div class="faq-container">
    <div class="card mb-4">
        <div class="card-body">
            <!-- FAQ Categories Navigation -->
            <div class="faq-categories mb-5">
                <ul class="nav nav-pills justify-content-center">
                    <li class="nav-item">
                        <a class="nav-link active" href="#umum" data-toggle="pill">Umum</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#guru" data-toggle="pill">Untuk Guru</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#sekolah" data-toggle="pill">Untuk Sekolah</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#pembayaran" data-toggle="pill">Pembayaran</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#teknis" data-toggle="pill">Teknis</a>
                    </li>
                </ul>
            </div>
            
            <!-- FAQ Content -->
            <div class="tab-content">
                <!-- General FAQs -->
                <div class="tab-pane fade show active" id="umum">
                    <h2 class="faq-category-title">Pertanyaan Umum</h2>
                    
                    <div class="faq-list">
                        <div class="faq-item">
                            <div class="faq-question" data-toggle="collapse" data-target="#faq1">
                                <h3>Apa itu GuruSinergi?</h3>
                                <div class="faq-toggle">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                            <div id="faq1" class="faq-answer collapse">
                                <div class="faq-answer-content">
                                    <p>GuruSinergi adalah platform yang menghubungkan guru pengganti dengan sekolah yang membutuhkan. Kami berfokus membantu guru perempuan menyeimbangkan tanggung jawab keluarga dan karier profesional dengan menyediakan kesempatan mengajar yang fleksibel. Platform ini juga membantu sekolah mendapatkan guru berkualitas saat guru tetap mereka berhalangan hadir.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question" data-toggle="collapse" data-target="#faq2">
                                <h3>Bagaimana cara kerja platform GuruSinergi?</h3>
                                <div class="faq-toggle">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                            <div id="faq2" class="faq-answer collapse">
                                <div class="faq-answer-content">
                                    <p>GuruSinergi bekerja dalam beberapa langkah sederhana:</p>
                                    <ol>
                                        <li>Guru dan sekolah mendaftar di platform dan membuat profil.</li>
                                        <li>Setelah terverifikasi, sekolah dapat membuat permintaan penugasan guru pengganti.</li>
                                        <li>Sistem akan mencocokkan permintaan dengan guru yang tersedia berdasarkan keahlian, lokasi, dan ketersediaan.</li>
                                        <li>Guru yang cocok dapat melamar penugasan, dan sekolah memilih guru yang paling sesuai.</li>
                                        <li>Setelah penugasan selesai, sekolah melakukan pembayaran melalui platform, dan guru dan sekolah dapat saling memberikan ulasan.</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question" data-toggle="collapse" data-target="#faq3">
                                <h3>Di wilayah mana GuruSinergi beroperasi?</h3>
                                <div class="faq-toggle">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                            <div id="faq3" class="faq-answer collapse">
                                <div class="faq-answer-content">
                                    <p>Saat ini, GuruSinergi beroperasi di Pekanbaru dan sekitarnya dengan rencana perluasan ke seluruh Provinsi Riau dan kemudian seluruh Indonesia. Kami terus berupaya memperluas jangkauan kami untuk melayani lebih banyak guru dan sekolah di berbagai daerah.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question" data-toggle="collapse" data-target="#faq4">
                                <h3>Apakah platform GuruSinergi gratis?</h3>
                                <div class="faq-toggle">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                            <div id="faq4" class="faq-answer collapse">
                                <div class="faq-answer-content">
                                    <p>Pendaftaran pada platform GuruSinergi gratis. Kami mengenakan biaya layanan sebesar 10% dari nilai penugasan, yang dibayarkan oleh sekolah. Guru menerima 90% dari nilai penugasan yang telah disepakati. Tidak ada biaya bulanan atau tahunan untuk menggunakan platform.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question" data-toggle="collapse" data-target="#faq5">
                                <h3>Bagaimana cara menghubungi tim dukungan GuruSinergi?</h3>
                                <div class="faq-toggle">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                            <div id="faq5" class="faq-answer collapse">
                                <div class="faq-answer-content">
                                    <p>Anda dapat menghubungi tim dukungan kami melalui:</p>
                                    <ul>
                                        <li>Email: <?php echo config('admin_email'); ?></li>
                                        <li>Telepon/WhatsApp: <?php echo config('support_phone'); ?></li>
                                        <li>Formulir kontak di halaman <a href="<?php echo url('contact.php'); ?>">Kontak Kami</a></li>
                                    </ul>
                                    <p>Tim dukungan kami tersedia Senin-Jumat, 08.00-17.00 WIB.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Teacher FAQs -->
                <div class="tab-pane fade" id="guru">
                    <h2 class="faq-category-title">Untuk Guru</h2>
                    
                    <div class="faq-list">
                        <div class="faq-item">
                            <div class="faq-question" data-toggle="collapse" data-target="#faq-g1">
                                <h3>Siapa yang dapat mendaftar sebagai guru di GuruSinergi?</h3>
                                <div class="faq-toggle">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                            <div id="faq-g1" class="faq-answer collapse">
                                <div class="faq-answer-content">
                                    <p>Siapa pun yang memiliki kualifikasi pendidikan yang relevan dapat mendaftar sebagai guru di GuruSinergi. Kami menerima guru dengan berbagai latar belakang pendidikan dan pengalaman, termasuk mahasiswa pendidikan tingkat akhir, guru pensiun, guru paruh waktu, dan guru yang sedang mencari pekerjaan tambahan.</p>
                                    <p>Persyaratan minimum untuk menjadi guru di platform kami adalah:</p>
                                    <ul>
                                        <li>Minimal pendidikan S1/D4 di bidang terkait (atau sedang menyelesaikan tahun terakhir)</li>
                                        <li>Memiliki dokumen identitas yang valid (KTP)</li>
                                        <li>Memiliki pengalaman mengajar (diutamakan tapi tidak wajib untuk beberapa mata pelajaran)</li>
                                        <li>Lulus proses verifikasi kami</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question" data-toggle="collapse" data-target="#faq-g2">
                                <h3>Bagaimana cara mendaftar sebagai guru?</h3>
                                <div class="faq-toggle">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                            <div id="faq-g2" class="faq-answer collapse">
                                <div class="faq-answer-content">
                                    <p>Untuk mendaftar sebagai guru, ikuti langkah-langkah berikut:</p>
                                    <ol>
                                        <li>Klik tombol "Daftar Sebagai Guru" di halaman beranda.</li>
                                        <li>Isi formulir pendaftaran dengan informasi pribadi Anda.</li>
                                        <li>Lengkapi profil Anda dengan informasi pendidikan, pengalaman, keahlian, dan preferensi mengajar.</li>
                                        <li>Unggah dokumen yang diperlukan (CV, ijazah, KTP).</li>
                                        <li>Tunggu proses verifikasi dari tim kami (biasanya 1-3 hari kerja).</li>
                                        <li>Setelah diverifikasi, Anda dapat mulai mencari dan melamar penugasan.</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question" data-toggle="collapse" data-target="#faq-g3">
                                <h3>Berapa pendapatan yang bisa saya dapatkan sebagai guru pengganti?</h3>
                                <div class="faq-toggle">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                            <div id="faq-g3" class="faq-answer collapse">
                                <div class="faq-answer-content">
                                    <p>Pendapatan guru pengganti bervariasi tergantung beberapa faktor, termasuk:</p>
                                    <ul>
                                        <li>Mata pelajaran yang Anda ajarkan (beberapa mata pelajaran memiliki tarif lebih tinggi)</li>
                                        <li>Tingkat pendidikan (SD, SMP, SMA)</li>
                                        <li>Lokasi sekolah</li>
                                        <li>Durasi penugasan</li>
                                        <li>Pengalaman dan kualifikasi Anda</li>
                                    </ul>
                                    <p>Secara umum, guru pengganti di platform kami mendapatkan sekitar Rp 50.000 - Rp 200.000 per pertemuan, tergantung faktor-faktor di atas. Untuk penugasan jangka panjang, sekolah mungkin menawarkan paket gaji bulanan.</p>
                                    <p>Anda menerima 90% dari nilai penugasan, sementara 10% digunakan sebagai biaya layanan platform.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question" data-toggle="collapse" data-target="#faq-g4">
                                <h3>Bagaimana cara melamar penugasan?</h3>
                                <div class="faq-toggle">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                            <div id="faq-g4" class="faq-answer collapse">
                                <div class="faq-answer-content">
                                    <p>Setelah akun Anda terverifikasi, Anda dapat melamar penugasan dengan cara:</p>
                                    <ol>
                                        <li>Masuk ke akun Anda dan buka halaman "Cari Penugasan".</li>
                                        <li>Gunakan filter untuk menemukan penugasan yang sesuai dengan keahlian dan ketersediaan Anda.</li>
                                        <li>Klik pada penugasan untuk melihat detail lengkap.</li>
                                        <li>Jika Anda tertarik, klik tombol "Lamar Penugasan".</li>
                                        <li>Tulis pesan singkat yang menjelaskan mengapa Anda cocok untuk penugasan tersebut.</li>
                                        <li>Kirim lamaran Anda.</li>
                                    </ol>
                                    <p>Sekolah akan menerima notifikasi tentang lamaran Anda dan dapat meninjau profil Anda. Jika Anda dipilih, Anda akan menerima pemberitahuan dan dapat mengonfirmasi ketersediaan Anda.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question" data-toggle="collapse" data-target="#faq-g5">
                                <h3>Bagaimana proses pembayaran untuk guru?</h3>
                                <div class="faq-toggle">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                            <div id="faq-g5" class="faq-answer collapse">
                                <div class="faq-answer-content">
                                    <p>Proses pembayaran untuk guru adalah sebagai berikut:</p>
                                    <ol>
                                        <li>Setelah penugasan selesai, Anda menandai penugasan sebagai "Selesai" di platform.</li>
                                        <li>Sekolah mengonfirmasi penyelesaian penugasan dan melakukan pembayaran melalui platform.</li>
                                        <li>Platform memproses pembayaran dan mentransfer 90% dari jumlah tersebut ke saldo akun Anda.</li>
                                        <li>Anda dapat mencairkan saldo ke rekening bank Anda kapan saja, dengan minimal penarikan Rp 100.000.</li>
                                        <li>Pencairan dana biasanya membutuhkan waktu 1-3 hari kerja.</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- School FAQs -->
                <div class="tab-pane fade" id="sekolah">
                    <h2 class="faq-category-title">Untuk Sekolah</h2>
                    
                    <div class="faq-list">
                        <div class="faq-item">
                            <div class="faq-question" data-toggle="collapse" data-target="#faq-s1">
                                <h3>Bagaimana cara mendaftar sebagai sekolah?</h3>
                                <div class="faq-toggle">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                            <div id="faq-s1" class="faq-answer collapse">
                                <div class="faq-answer-content">
                                    <p>Untuk mendaftar sebagai sekolah di GuruSinergi, ikuti langkah-langkah berikut:</p>
                                    <ol>
                                        <li>Klik tombol "Daftar Sebagai Sekolah" di halaman beranda.</li>
                                        <li>Isi formulir pendaftaran dengan informasi kontak Anda.</li>
                                        <li>Lengkapi profil sekolah dengan informasi seperti nama sekolah, jenis sekolah, alamat, dan detail lainnya.</li>
                                        <li>Unggah dokumen NPSN atau izin operasional sekolah.</li>
                                        <li>Tunggu proses verifikasi dari tim kami (biasanya 1-3 hari kerja).</li>
                                        <li>Setelah diverifikasi, Anda dapat mulai membuat penugasan dan mencari guru pengganti.</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question" data-toggle="collapse" data-target="#faq-s2">
                                <h3>Bagaimana cara membuat permintaan penugasan guru pengganti?</h3>
                                <div class="faq-toggle">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                            <div id="faq-s2" class="faq-answer collapse">
                                <div class="faq-answer-content">
                                    <p>Setelah akun sekolah Anda terverifikasi, Anda dapat membuat permintaan penugasan dengan langkah-langkah berikut:</p>
                                    <ol>
                                        <li>Masuk ke akun sekolah Anda dan buka dashboard.</li>
                                        <li>Klik tombol "Buat Penugasan Baru".</li>
                                        <li>Isi formulir dengan detail penugasan, termasuk:
                                            <ul>
                                                <li>Judul dan deskripsi penugasan</li>
                                                <li>Mata pelajaran dan tingkat kelas</li>
                                                <li>Tanggal mulai dan selesai</li>
                                                <li>Jam mengajar</li>
                                                <li>Gaji yang ditawarkan</li>
                                                <li>Persyaratan khusus (jika ada)</li>
                                            </ul>
                                        </li>
                                        <li>Tinjau detail dan klik "Publikasikan Penugasan".</li>
                                    </ol>
                                    <p>Setelah penugasan dipublikasikan, sistem akan mulai mencocokkan dengan guru yang tersedia, dan guru yang cocok akan mendapatkan notifikasi.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question" data-toggle="collapse" data-target="#faq-s3">
                                <h3>Berapa biaya menggunakan layanan GuruSinergi?</h3>
                                <div class="faq-toggle">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                            <div id="faq-s3" class="faq-answer collapse">
                                <div class="faq-answer-content">
                                    <p>Biaya layanan GuruSinergi untuk sekolah adalah sebagai berikut:</p>
                                    <ul>
                                        <li>Pendaftaran dan pembuatan akun: <strong>Gratis</strong></li>
                                        <li>Pembuatan dan penerbitan penugasan: <strong>Gratis</strong></li>
                                        <li>Biaya layanan: <strong>10% dari nilai penugasan</strong></li>
                                    </ul>
                                    <p>Contoh: Jika Anda membuat penugasan dengan nilai Rp 500.000, total yang akan Anda bayar adalah Rp 550.000 (Rp 500.000 untuk guru + Rp 50.000 untuk biaya layanan).</p>
                                    <p>Kami tidak mengenakan biaya berlangganan bulanan atau tahunan.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question" data-toggle="collapse" data-target="#faq-s4">
                                <h3>Bagaimana proses pencocokan guru pengganti?</h3>
                                <div class="faq-toggle">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                            <div id="faq-s4" class="faq-answer collapse">
                                <div class="faq-answer-content">
                                    <p>Proses pencocokan guru pengganti di GuruSinergi menggunakan algoritma cerdas yang mempertimbangkan beberapa faktor:</p>
                                    <ul>
                                        <li>Kecocokan mata pelajaran dan tingkat kelas</li>
                                        <li>Ketersediaan guru pada tanggal dan waktu yang dibutuhkan</li>
                                        <li>Lokasi guru dan sekolah</li>
                                        <li>Kualifikasi dan pengalaman guru</li>
                                        <li>Rating dan ulasan yang diterima guru</li>
                                    </ul>
                                    <p>Setelah sistem mengidentifikasi guru yang cocok, mereka akan menerima notifikasi tentang penugasan dan dapat melamar. Anda kemudian dapat meninjau profil, kualifikasi, dan ulasan guru untuk memilih kandidat terbaik untuk kebutuhan Anda.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question" data-toggle="collapse" data-target="#faq-s5">
                                <h3>Bagaimana jika kami perlu guru pengganti dalam waktu kurang dari 24 jam?</h3>
                                <div class="faq-toggle">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                            <div id="faq-s5" class="faq-answer collapse">
                                <div class="faq-answer-content">
                                    <p>Kami memahami bahwa terkadang Anda perlu guru pengganti dengan cepat. Untuk permintaan mendesak (kurang dari 24 jam), kami menyediakan layanan "Penugasan Darurat" dengan proses sebagai berikut:</p>
                                    <ol>
                                        <li>Buat penugasan dan tandai sebagai "Darurat" atau "Prioritas".</li>
                                        <li>Biaya layanan untuk penugasan darurat adalah 15% (alih-alih 10% standar).</li>
                                        <li>Sistem kami akan memprioritaskan permintaan Anda dan memberi notifikasi langsung kepada guru yang tersedia dan cocok.</li>
                                        <li>Tim dukungan kami juga akan membantu secara aktif dalam proses pencocokan.</li>
                                    </ol>
                                    <p>Meskipun kami tidak dapat menjamin 100%, kami berusaha keras untuk menemukan guru pengganti untuk situasi darurat. Tingkat keberhasilan kami untuk penugasan darurat saat ini sekitar 85%.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Payment FAQs -->
                <div class="tab-pane fade" id="pembayaran">
                    <h2 class="faq-category-title">Pembayaran</h2>
                    
                    <div class="faq-list">
                        <div class="faq-item">
                            <div class="faq-question" data-toggle="collapse" data-target="#faq-p1">
                                <h3>Metode pembayaran apa yang diterima di GuruSinergi?</h3>
                                <div class="faq-toggle">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                            <div id="faq-p1" class="faq-answer collapse">
                                <div class="faq-answer-content">
                                    <p>GuruSinergi menerima berbagai metode pembayaran untuk memudahkan pengguna:</p>
                                    <ul>
                                        <li><strong>Transfer Bank:</strong> BCA, BNI, BRI, Mandiri</li>
                                        <li><strong>E-Wallet:</strong> DANA, GoPay, OVO, LinkAja</li>
                                        <li><strong>Virtual Account:</strong> Semua bank utama</li>
                                        <li><strong>QRIS:</strong> Untuk pembayaran dengan QR code</li>
                                        <li><strong>Kartu Kredit:</strong> Visa, Mastercard</li>
                                    </ul>
                                    <p>Semua transaksi diproses melalui gateway pembayaran Tripay yang aman dan terpercaya.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question" data-toggle="collapse" data-target="#faq-p2">
                                <h3>Kapan sekolah melakukan pembayaran?</h3>
                                <div class="faq-toggle">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                            <div id="faq-p2" class="faq-answer collapse">
                                <div class="faq-answer-content">
                                    <p>Proses pembayaran untuk sekolah bergantung pada jenis penugasan:</p>
                                    <ul>
                                        <li><strong>Penugasan Jangka Pendek (1-7 hari):</strong> Pembayaran dilakukan sebelum penugasan dimulai.</li>
                                        <li><strong>Penugasan Jangka Menengah (8-30 hari):</strong> Pembayaran dapat dilakukan dengan dua opsi:
                                            <ul>
                                                <li>Pembayaran penuh di awal (diskon 5% dari biaya layanan)</li>
                                                <li>Pembayaran 50% di awal dan 50% di tengah periode penugasan</li>
                                            </ul>
                                        </li>
                                        <li><strong>Penugasan Jangka Panjang (>30 hari):</strong> Pembayaran dapat dilakukan secara bulanan.</li>
                                    </ul>
                                    <p>Sekolah akan menerima notifikasi dan invoice ketika pembayaran jatuh tempo.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question" data-toggle="collapse" data-target="#faq-p3">
                                <h3>Kapan guru menerima pembayaran?</h3>
                                <div class="faq-toggle">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                            <div id="faq-p3" class="faq-answer collapse">
                                <div class="faq-answer-content">
                                    <p>Guru menerima pembayaran setelah penugasan selesai dan dikonfirmasi oleh sekolah. Proses lengkapnya adalah:</p>
                                    <ol>
                                        <li>Guru menandai penugasan sebagai "Selesai" di platform.</li>
                                        <li>Sekolah mengonfirmasi penyelesaian penugasan.</li>
                                        <li>Dana dilepaskan ke saldo guru dalam platform (90% dari nilai penugasan).</li>
                                        <li>Guru dapat mencairkan saldo ke rekening bank mereka dengan minimal penarikan Rp 100.000.</li>
                                        <li>Pencairan dana biasanya membutuhkan waktu 1-3 hari kerja.</li>
                                    </ol>
                                    <p>Untuk penugasan jangka panjang dengan pembayaran berkala, guru akan menerima pembayaran sesuai jadwal yang ditentukan dalam penugasan.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question" data-toggle="collapse" data-target="#faq-p4">
                                <h3>Apakah ada biaya tambahan selain biaya layanan?</h3>
                                <div class="faq-toggle">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                            <div id="faq-p4" class="faq-answer collapse">
                                <div class="faq-answer-content">
                                    <p>Secara umum, hanya ada biaya layanan 10% yang dibebankan kepada sekolah. Namun, ada beberapa biaya tambahan yang perlu diketahui:</p>
                                    <ul>
                                        <li><strong>Biaya Penugasan Darurat:</strong> 15% (alih-alih 10%) untuk penugasan yang membutuhkan guru dalam waktu kurang dari 24 jam.</li>
                                        <li><strong>Biaya Pencairan Dana:</strong> Untuk guru, pencairan dana ke rekening bank dikenakan biaya Rp 5.000 per transaksi.</li>
                                        <li><strong>Biaya Pembatalan:</strong> Pembatalan oleh sekolah dalam 24 jam sebelum penugasan dimulai dikenakan biaya 50% dari nilai penugasan.</li>
                                    </ul>
                                    <p>Selain itu, beberapa metode pembayaran mungkin mengenakan biaya transaksi tambahan dari penyedia layanan (misalnya, biaya transfer antar bank).</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question" data-toggle="collapse" data-target="#faq-p5">
                                <h3>Bagaimana cara mendapatkan invoice untuk pembayaran?</h3>
                                <div class="faq-toggle">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                            <div id="faq-p5" class="faq-answer collapse">
                                <div class="faq-answer-content">
                                    <p>Invoice untuk setiap pembayaran akan otomatis tersedia setelah pembayaran diproses. Untuk mengakses invoice:</p>
                                    <ol>
                                        <li>Masuk ke akun Anda dan buka dashboard.</li>
                                        <li>Buka menu "Riwayat Transaksi" atau "Pembayaran".</li>
                                        <li>Cari transaksi yang ingin Anda lihat invoicenya.</li>
                                        <li>Klik tombol "Lihat Invoice" atau "Unduh Invoice".</li>
                                    </ol>
                                    <p>Invoice dapat diunduh dalam format PDF dan berisi semua informasi yang diperlukan untuk keperluan akuntansi, termasuk rincian layanan, jumlah yang dibayarkan, dan biaya layanan.</p>
                                    <p>Jika Anda memerlukan invoice dengan format khusus untuk keperluan institusi, silakan hubungi tim dukungan kami.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Technical FAQs -->
                <div class="tab-pane fade" id="teknis">
                    <h2 class="faq-category-title">Teknis</h2>
                    
                    <div class="faq-list">
                        <div class="faq-item">
                            <div class="faq-question" data-toggle="collapse" data-target="#faq-t1">
                                <h3>Apakah GuruSinergi memiliki aplikasi mobile?</h3>
                                <div class="faq-toggle">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                            <div id="faq-t1" class="faq-answer collapse">
                                <div class="faq-answer-content">
                                    <p>Saat ini, GuruSinergi hadir dalam bentuk website responsif yang dapat diakses dari perangkat mobile maupun desktop. Website kami dioptimalkan untuk tampilan mobile sehingga Anda dapat dengan mudah menggunakan semua fitur dari smartphone atau tablet.</p>
                                    <p>Pengembangan aplikasi mobile untuk Android dan iOS sedang dalam proses dan direncanakan akan diluncurkan dalam beberapa bulan ke depan. Kami akan memberi tahu semua pengguna ketika aplikasi tersedia untuk diunduh.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question" data-toggle="collapse" data-target="#faq-t2">
                                <h3>Bagaimana keamanan data di GuruSinergi?</h3>
                                <div class="faq-toggle">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                            <div id="faq-t2" class="faq-answer collapse">
                                <div class="faq-answer-content">
                                    <p>Keamanan data adalah prioritas utama kami. Kami menerapkan berbagai langkah untuk melindungi informasi pengguna:</p>
                                    <ul>
                                        <li><strong>Enkripsi SSL:</strong> Semua data yang ditransfer antara browser Anda dan server kami dienkripsi menggunakan protokol SSL.</li>
                                        <li><strong>Penyimpanan Aman:</strong> Kata sandi dienkripsi menggunakan algoritma hashing yang kuat.</li>
                                        <li><strong>Pembatasan Akses:</strong> Hanya staf yang berwenang yang memiliki akses ke data pengguna, dan akses tersebut dilacak dan diaudit.</li>
                                        <li><strong>Kepatuhan Peraturan:</strong> Kami mematuhi peraturan perlindungan data yang berlaku di Indonesia.</li>
                                        <li><strong>Backup Data:</strong> Kami melakukan backup data secara berkala untuk mencegah kehilangan data.</li>
                                    </ul>
                                    <p>Untuk informasi lebih lanjut, silakan baca <a href="<?php echo url('privacy.php'); ?>">Kebijakan Privasi</a> kami.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question" data-toggle="collapse" data-target="#faq-t3">
                                <h3>Bagaimana cara mengubah kata sandi atau informasi akun?</h3>
                                <div class="faq-toggle">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                            <div id="faq-t3" class="faq-answer collapse">
                                <div class="faq-answer-content">
                                    <p>Untuk mengubah kata sandi atau informasi akun Anda, ikuti langkah-langkah berikut:</p>
                                    <ol>
                                        <li>Masuk ke akun Anda.</li>
                                        <li>Klik pada nama atau foto profil Anda di pojok kanan atas.</li>
                                        <li>Pilih "Profil" dari menu dropdown.</li>
                                        <li>Pada halaman profil, klik tombol "Edit Profil".</li>
                                        <li>Untuk mengubah kata sandi, klik tab "Keamanan" atau link "Ubah Kata Sandi".</li>
                                        <li>Masukkan kata sandi lama dan kata sandi baru Anda.</li>
                                        <li>Klik "Simpan Perubahan".</li>
                                    </ol>
                                    <p>Jika Anda lupa kata sandi, klik "Lupa Kata Sandi" di halaman login dan ikuti petunjuk untuk mengatur ulang kata sandi Anda.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question" data-toggle="collapse" data-target="#faq-t4">
                                <h3>Apa yang harus dilakukan jika menemui masalah teknis?</h3>
                                <div class="faq-toggle">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                            <div id="faq-t4" class="faq-answer collapse">
                                <div class="faq-answer-content">
                                    <p>Jika Anda mengalami masalah teknis saat menggunakan platform kami, silakan ikuti langkah-langkah berikut:</p>
                                    <ol>
                                        <li>Segarkan halaman browser Anda.</li>
                                        <li>Coba bersihkan cache dan cookie browser.</li>
                                        <li>Coba akses platform dari browser atau perangkat yang berbeda.</li>
                                        <li>Periksa koneksi internet Anda.</li>
                                        <li>Jika masalah masih berlanjut, hubungi dukungan teknis kami melalui:
                                            <ul>
                                                <li>Email: <?php echo config('admin_email'); ?></li>
                                                <li>WhatsApp: <?php echo config('support_phone'); ?></li>
                                                <li>Form bantuan di halaman "Kontak"</li>
                                            </ul>
                                        </li>
                                    </ol>
                                    <p>Saat menghubungi dukungan teknis, berikan detail lengkap tentang masalah yang Anda alami, termasuk perangkat, browser, dan langkah-langkah untuk mereproduksi masalah. Ini akan membantu kami menyelesaikan masalah dengan lebih cepat.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question" data-toggle="collapse" data-target="#faq-t5">
                                <h3>Apakah GuruSinergi tersedia dalam bahasa selain Bahasa Indonesia?</h3>
                                <div class="faq-toggle">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                            <div id="faq-t5" class="faq-answer collapse">
                                <div class="faq-answer-content">
                                    <p>Saat ini, GuruSinergi hanya tersedia dalam Bahasa Indonesia karena fokus kami pada pasar Indonesia. Kami berencana untuk menambahkan dukungan Bahasa Inggris dalam pembaruan mendatang untuk mengakomodasi pengguna internasional dan sekolah-sekolah yang menggunakan Bahasa Inggris sebagai bahasa pengantar.</p>
                                    <p>Jika Anda membutuhkan bantuan dalam bahasa selain Bahasa Indonesia, silakan hubungi tim dukungan kami, dan kami akan berusaha untuk membantu Anda.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-body">
            <div class="faq-contact">
                <h2 class="text-center mb-4">Masih Ada Pertanyaan?</h2>
                <p class="text-center">Jika Anda memiliki pertanyaan lain yang tidak tercantum di sini, jangan ragu untuk menghubungi kami.</p>
                
                <div class="contact-options">
                    <a href="<?php echo url('contact.php'); ?>" class="contact-option">
                        <div class="option-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h3>Email Kami</h3>
                        <p>Kirim pesan melalui form kontak kami</p>
                    </a>
                    
                    <a href="https://wa.me/<?php echo str_replace(['+', ' '], '', config('support_phone')); ?>" class="contact-option">
                        <div class="option-icon">
                            <i class="fab fa-whatsapp"></i>
                        </div>
                        <h3>WhatsApp</h3>
                        <p>Chat langsung dengan tim dukungan kami</p>
                    </a>
                    
                    <a href="tel:<?php echo str_replace(['+', ' '], '', config('support_phone')); ?>" class="contact-option">
                        <div class="option-icon">
                            <i class="fas fa-phone-alt"></i>
                        </div>
                        <h3>Telepon</h3>
                        <p>Hubungi kami di <?php echo config('support_phone'); ?></p>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Fungsi untuk menangani klik pada pill
    const pillLinks = document.querySelectorAll('.nav-link');
    pillLinks.forEach(function(pill) {
        pill.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Hapus kelas active dari semua pill
            pillLinks.forEach(function(p) {
                p.classList.remove('active');
            });
            
            // Tambahkan kelas active ke pill yang diklik
            this.classList.add('active');
            
            // Tampilkan konten yang sesuai
            const targetId = this.getAttribute('href').substring(1);
            const tabPanes = document.querySelectorAll('.tab-pane');
            
            tabPanes.forEach(function(pane) {
                pane.classList.remove('show', 'active');
            });
            
            document.getElementById(targetId).classList.add('show', 'active');
        });
    });
    
    // Fungsi untuk menangani klik pada pertanyaan
    const faqQuestions = document.querySelectorAll('.faq-question');
    faqQuestions.forEach(function(question) {
        question.addEventListener('click', function() {
            const answerId = this.getAttribute('data-target');
            const answer = document.querySelector(answerId);
            
            if (answer.classList.contains('show')) {
                answer.classList.remove('show');
                this.classList.remove('active');
            } else {
                answer.classList.add('show');
                this.classList.add('active');
            }
        });
    });
    
    // Buka accordion jika ada hash dalam URL
    if (window.location.hash) {
        const faqId = window.location.hash;
        const faqElement = document.querySelector(faqId);
        if (faqElement) {
            faqElement.classList.add('show');
            document.querySelector(`[data-target="${faqId}"]`).classList.add('active');
            
            // Scroll ke elemen
            setTimeout(function() {
                faqElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 100);
        }
    }
});
</script>

<?php
// Include footer
include_once 'templates/footer.php';
?>