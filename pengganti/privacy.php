<?php
/**
 * GuruSinergi - Privacy Policy Page
 * 
 * Halaman kebijakan privasi platform
 */

// Include file konfigurasi
require_once 'config/config.php';

// Include file database
require_once 'config/database.php';

// Include file functions
require_once 'includes/functions.php';

// Set variabel untuk page title
$page_title = 'Kebijakan Privasi';

// Include header
include_once 'templates/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Kebijakan Privasi</h1>
    <p class="page-description">Platform GuruSinergi Program Guru Pengganti</p>
</div>

<div class="privacy-container">
    <div class="card mb-4">
        <div class="card-body">
            <div class="privacy-content">
                <div class="privacy-section">
                    <h2>1. Pendahuluan</h2>
                    <p>Di GuruSinergi, kami menghargai kepercayaan yang Anda berikan kepada kami dan berkomitmen untuk melindungi privasi Anda. Kebijakan Privasi ini menjelaskan bagaimana kami mengumpulkan, menggunakan, mengungkapkan, dan melindungi informasi pribadi Anda ketika Anda menggunakan platform kami.</p>
                    <p>Dengan menggunakan Platform GuruSinergi, Anda menyetujui praktik yang dijelaskan dalam Kebijakan Privasi ini. Jika Anda tidak setuju dengan kebijakan ini, harap jangan gunakan Platform kami.</p>
                </div>

                <div class="privacy-section">
                    <h2>2. Informasi yang Kami Kumpulkan</h2>
                    <p>Kami mengumpulkan berbagai jenis informasi untuk menyediakan dan meningkatkan layanan kami:</p>
                    
                    <h3>2.1 Informasi yang Anda Berikan</h3>
                    <ul>
                        <li><strong>Informasi Pendaftaran:</strong> Nama lengkap, alamat email, nomor telepon, alamat, dan kata sandi.</li>
                        <li><strong>Informasi Profil:</strong> 
                            <ul>
                                <li>Untuk Guru: Pendidikan, pengalaman mengajar, keahlian, mata pelajaran, tingkat mengajar, dan dokumen pendukung (CV, ijazah, KTP).</li>
                                <li>Untuk Sekolah: Nama sekolah, jenis sekolah, alamat lengkap, kontak, dan dokumen pendukung (NPSN/izin operasional).</li>
                            </ul>
                        </li>
                        <li><strong>Informasi Pembayaran:</strong> Informasi kartu kredit atau metode pembayaran lainnya yang digunakan untuk transaksi.</li>
                        <li><strong>Konten Komunikasi:</strong> Pesan, ulasan, dan feedback yang Anda kirim melalui Platform.</li>
                        <li><strong>Dokumen Verifikasi:</strong> Identitas dan dokumen profesional yang digunakan untuk verifikasi.</li>
                    </ul>
                    
                    <h3>2.2 Informasi yang Dikumpulkan Secara Otomatis</h3>
                    <ul>
                        <li><strong>Informasi Perangkat:</strong> Jenis perangkat, sistem operasi, browser, pengidentifikasi perangkat, dan alamat IP.</li>
                        <li><strong>Informasi Penggunaan:</strong> Cara Anda berinteraksi dengan Platform, termasuk halaman yang dikunjungi, waktu akses, dan durasi kunjungan.</li>
                        <li><strong>Informasi Lokasi:</strong> Lokasi geografis umum berdasarkan alamat IP atau GPS (jika diizinkan).</li>
                        <li><strong>Cookies dan Teknologi Serupa:</strong> Informasi yang dikumpulkan melalui cookies dan teknologi pelacakan serupa.</li>
                    </ul>
                </div>

                <div class="privacy-section">
                    <h2>3. Bagaimana Kami Menggunakan Informasi Anda</h2>
                    <p>Kami menggunakan informasi Anda untuk tujuan berikut:</p>
                    <ul>
                        <li><strong>Menyediakan dan Mengelola Layanan:</strong> Membuat dan mengelola akun Anda, memproses transaksi, dan menyediakan dukungan pelanggan.</li>
                        <li><strong>Memfasilitasi Pencocokan:</strong> Mencocokkan Guru dengan Sekolah berdasarkan kebutuhan dan preferensi.</li>
                        <li><strong>Memverifikasi Identitas dan Kredensial:</strong> Memverifikasi identitas, kualifikasi, dan kredensial untuk menjaga keamanan dan kepercayaan.</li>
                        <li><strong>Komunikasi:</strong> Mengirimkan pemberitahuan, pembaruan, dan informasi penting tentang layanan.</li>
                        <li><strong>Pemasaran:</strong> Mengirimkan informasi tentang penawaran, acara, dan layanan baru (Anda dapat berhenti berlangganan kapan saja).</li>
                        <li><strong>Meningkatkan Layanan:</strong> Menganalisis penggunaan Platform untuk meningkatkan fitur, fungsionalitas, dan pengalaman pengguna.</li>
                        <li><strong>Keamanan:</strong> Mendeteksi, mencegah, dan mengatasi aktivitas penipuan, penyalahgunaan, dan masalah keamanan.</li>
                        <li><strong>Kepatuhan Hukum:</strong> Mematuhi kewajiban hukum dan peraturan yang berlaku.</li>
                    </ul>
                </div>

                <div class="privacy-section">
                    <h2>4. Bagaimana Kami Membagikan Informasi Anda</h2>
                    <p>Kami dapat membagikan informasi Anda dengan pihak-pihak berikut:</p>
                    <ul>
                        <li><strong>Antara Guru dan Sekolah:</strong> Informasi profil dan kontak dibagikan antara Guru dan Sekolah ketika keduanya terlibat dalam penugasan.</li>
                        <li><strong>Penyedia Layanan:</strong> Perusahaan pihak ketiga yang membantu kami menyediakan dan meningkatkan layanan (seperti pemrosesan pembayaran, hosting cloud, analitik).</li>
                        <li><strong>Mitra Bisnis:</strong> Mitra yang bekerja sama dengan kami untuk menyediakan layanan pendidikan terkait.</li>
                        <li><strong>Otoritas Pemerintah:</strong> Jika diperlukan oleh hukum, proses hukum, atau permintaan pemerintah yang sah.</li>
                        <li><strong>Pihak Lain dengan Persetujuan Anda:</strong> Entitas lain di mana Anda telah memberikan persetujuan.</li>
                    </ul>
                    <p>Kami tidak menjual informasi pribadi Anda kepada pihak ketiga.</p>
                </div>

                <div class="privacy-section">
                    <h2>5. Keamanan Data</h2>
                    <p>Kami menerapkan langkah-langkah teknis, administratif, dan fisik yang dirancang untuk melindungi informasi pribadi Anda dari akses, penggunaan, atau pengungkapan yang tidak sah. Langkah-langkah ini meliputi:</p>
                    <ul>
                        <li>Enkripsi data sensitif saat dalam pengiriman dan saat disimpan.</li>
                        <li>Pembatasan akses ke informasi pribadi hanya untuk karyawan, kontraktor, dan agen yang membutuhkannya.</li>
                        <li>Penerapan firewall, deteksi intrusi, dan teknologi keamanan lainnya.</li>
                        <li>Pemantauan sistem secara teratur untuk potensi kerentanan dan serangan.</li>
                    </ul>
                    <p>Meskipun kami berusaha keras untuk melindungi informasi Anda, tidak ada metode transmisi melalui internet atau metode penyimpanan elektronik yang 100% aman. Oleh karena itu, kami tidak dapat menjamin keamanan mutlak.</p>
                </div>

                <div class="privacy-section">
                    <h2>6. Retensi Data</h2>
                    <p>Kami menyimpan informasi pribadi Anda selama diperlukan untuk memenuhi tujuan yang diuraikan dalam Kebijakan Privasi ini, kecuali jika periode retensi yang lebih lama diperlukan atau diizinkan oleh hukum.</p>
                    <p>Faktor yang kami pertimbangkan dalam menentukan periode retensi meliputi:</p>
                    <ul>
                        <li>Berapa lama informasi diperlukan untuk menyediakan layanan kami.</li>
                        <li>Apakah kami memiliki kewajiban hukum untuk menyimpan data.</li>
                        <li>Apakah retensi diperlukan untuk melindungi hak dan kepentingan kami atau pengguna lain.</li>
                    </ul>
                </div>

                <div class="privacy-section">
                    <h2>7. Hak Privasi Anda</h2>
                    <p>Tergantung pada lokasi Anda, Anda mungkin memiliki hak-hak berikut terkait dengan informasi pribadi Anda:</p>
                    <ul>
                        <li><strong>Hak Akses:</strong> Anda dapat meminta salinan informasi pribadi yang kami simpan tentang Anda.</li>
                        <li><strong>Hak Koreksi:</strong> Anda dapat meminta pembaruan atau koreksi informasi pribadi Anda yang tidak akurat.</li>
                        <li><strong>Hak Penghapusan:</strong> Anda dapat meminta penghapusan informasi pribadi Anda dalam situasi tertentu.</li>
                        <li><strong>Hak Pembatasan:</strong> Anda dapat meminta kami membatasi pemrosesan informasi Anda dalam situasi tertentu.</li>
                        <li><strong>Hak Portabilitas Data:</strong> Anda dapat meminta salinan informasi pribadi Anda dalam format yang terstruktur dan dapat dibaca mesin.</li>
                        <li><strong>Hak Keberatan:</strong> Anda dapat keberatan terhadap pemrosesan informasi pribadi Anda dalam situasi tertentu.</li>
                        <li><strong>Hak Untuk Tidak Tunduk pada Keputusan Otomatis:</strong> Anda dapat meminta tinjauan manusia atas keputusan otomatis tertentu.</li>
                    </ul>
                    <p>Untuk menggunakan hak-hak ini, silakan hubungi kami melalui informasi kontak yang disediakan di bagian "Hubungi Kami". Kami akan menanggapi permintaan Anda sesuai dengan hukum yang berlaku.</p>
                </div>

                <div class="privacy-section">
                    <h2>8. Cookies dan Teknologi Serupa</h2>
                    <p>Platform kami menggunakan cookies dan teknologi serupa untuk mengumpulkan dan menyimpan informasi ketika Anda mengunjungi atau berinteraksi dengan layanan kami. Cookies adalah file kecil yang disimpan di perangkat Anda yang memungkinkan kami mengenali browser Anda dan mengingat informasi tertentu.</p>
                    <p>Kami menggunakan cookies untuk tujuan berikut:</p>
                    <ul>
                        <li><strong>Cookies Penting:</strong> Diperlukan untuk operasi dasar Platform, seperti mengautentikasi pengguna dan mencegah penipuan.</li>
                        <li><strong>Cookies Fungsional:</strong> Memungkinkan fitur tertentu dan mengingat preferensi Anda.</li>
                        <li><strong>Cookies Analitik:</strong> Membantu kami memahami bagaimana pengguna berinteraksi dengan Platform kami.</li>
                        <li><strong>Cookies Pemasaran:</strong> Digunakan untuk menampilkan iklan yang relevan dan melacak efektivitas kampanye pemasaran.</li>
                    </ul>
                    <p>Anda dapat mengontrol penggunaan cookies melalui pengaturan browser Anda. Namun, menolak cookies dapat memengaruhi fungsionalitas Platform kami.</p>
                </div>

                <div class="privacy-section">
                    <h2>9. Privasi Anak-anak</h2>
                    <p>Platform kami tidak ditujukan untuk anak-anak di bawah usia 18 tahun, dan kami tidak dengan sengaja mengumpulkan informasi pribadi dari anak-anak di bawah 18 tahun. Jika Anda percaya bahwa kami telah mengumpulkan informasi dari anak di bawah 18 tahun, harap hubungi kami melalui informasi yang disediakan di bagian "Hubungi Kami".</p>
                </div>

                <div class="privacy-section">
                    <h2>10. Transfer Data Internasional</h2>
                    <p>Informasi pribadi Anda dapat ditransfer ke, dan diproses di, negara-negara selain tempat Anda tinggal. Negara-negara ini mungkin memiliki undang-undang perlindungan data yang berbeda dari negara Anda.</p>
                    <p>Saat kami mentransfer informasi pribadi Anda ke negara lain, kami akan mengambil langkah-langkah untuk memastikan bahwa informasi Anda terus mendapatkan perlindungan yang memadai.</p>
                </div>

                <div class="privacy-section">
                    <h2>11. Perubahan pada Kebijakan Privasi Ini</h2>
                    <p>Kami dapat memperbarui Kebijakan Privasi ini dari waktu ke waktu untuk mencerminkan perubahan dalam praktik kami atau karena alasan operasional, hukum, atau peraturan lainnya.</p>
                    <p>Kami akan memberi tahu Anda tentang perubahan dengan memposting Kebijakan Privasi yang diperbarui di Platform kami dan, jika perubahan bersifat substansial, kami akan memberi tahu Anda melalui email atau pemberitahuan di Platform.</p>
                    <p>Kami mendorong Anda untuk secara berkala meninjau Kebijakan Privasi ini untuk tetap mendapatkan informasi tentang bagaimana kami melindungi informasi Anda.</p>
                </div>

                <div class="privacy-section">
                    <h2>12. Hubungi Kami</h2>
                    <p>Jika Anda memiliki pertanyaan, kekhawatiran, atau permintaan terkait Kebijakan Privasi ini atau praktik privasi kami, silakan hubungi kami di:</p>
                    <p>Email: <?php echo config('admin_email'); ?><br>
                    Telepon: <?php echo config('support_phone'); ?><br>
                    Alamat: Jl. Umban Sari, Pekanbaru, 28265, Indonesia</p>
                </div>

                <div class="privacy-section">
                    <p class="last-updated">Terakhir diperbarui: 15 Januari 2023</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once 'templates/footer.php';
?>