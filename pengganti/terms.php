<?php
/**
 * GuruSinergi - Terms and Conditions Page
 * 
 * Halaman syarat dan ketentuan penggunaan platform
 */

// Include file konfigurasi
require_once 'config/config.php';

// Include file database
require_once 'config/database.php';

// Include file functions
require_once 'includes/functions.php';

// Set variabel untuk page title
$page_title = 'Syarat dan Ketentuan';

// Include header
include_once 'templates/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Syarat dan Ketentuan</h1>
    <p class="page-description">Platform GuruSinergi Program Guru Pengganti</p>
</div>

<div class="terms-container">
    <div class="card mb-4">
        <div class="card-body">
            <div class="terms-content">
                <div class="term-section">
                    <h2>1. Pendahuluan</h2>
                    <p>Selamat datang di GuruSinergi Program Guru Pengganti.</p>
                    <p>Syarat dan Ketentuan ini mengatur penggunaan dan akses Anda ke platform GuruSinergi, termasuk situs web, aplikasi, dan layanan yang kami sediakan. Dengan mengakses atau menggunakan platform kami, Anda menyetujui untuk terikat oleh Syarat dan Ketentuan ini. Jika Anda tidak setuju dengan syarat-syarat ini, harap jangan gunakan platform kami.</p>
                    <p>Platform ini dioperasikan oleh GuruSinergi ("kami", "kita", atau "milik kami"). Kami menawarkan platform ini, termasuk semua informasi, alat, dan layanan yang tersedia di platform kepada Anda, pengguna, dengan syarat Anda menerima semua persyaratan, ketentuan, kebijakan, dan pemberitahuan yang dinyatakan di sini.</p>
                </div>

                <div class="term-section">
                    <h2>2. Definisi</h2>
                    <p>Dalam Syarat dan Ketentuan ini:</p>
                    <ul>
                        <li><strong>"Platform"</strong> mengacu pada situs web, aplikasi, dan layanan yang disediakan oleh GuruSinergi.</li>
                        <li><strong>"Pengguna"</strong> mengacu pada setiap individu atau entitas yang mengakses atau menggunakan Platform.</li>
                        <li><strong>"Guru"</strong> mengacu pada pengguna yang mendaftar sebagai guru untuk menawarkan jasa mengajar melalui Platform.</li>
                        <li><strong>"Sekolah"</strong> mengacu pada institusi pendidikan atau individu yang mendaftar untuk mencari guru pengganti melalui Platform.</li>
                        <li><strong>"Penugasan"</strong> mengacu pada perjanjian antara Guru dan Sekolah untuk layanan mengajar yang difasilitasi melalui Platform.</li>
                        <li><strong>"Konten"</strong> mengacu pada semua materi yang diunggah ke Platform, termasuk teks, gambar, video, audio, dan data.</li>
                    </ul>
                </div>

                <div class="term-section">
                    <h2>3. Pendaftaran Akun</h2>
                    <p>Untuk menggunakan layanan kami, Anda harus membuat akun di Platform kami. Ketika mendaftar, Anda setuju untuk memberikan informasi yang akurat, lengkap, dan terkini tentang diri Anda. Anda bertanggung jawab penuh untuk menjaga kerahasiaan akun dan kata sandi Anda dan untuk membatasi akses ke perangkat Anda.</p>
                    <p>Anda setuju untuk menerima tanggung jawab atas semua aktivitas yang terjadi di akun Anda. Jika Anda yakin ada pelanggaran keamanan atau penggunaan yang tidak sah atas akun Anda, Anda harus segera memberi tahu kami.</p>
                    <p>Kami berhak menolak layanan, menutup akun, menghapus atau mengedit konten, atau membatalkan pesanan atas kebijakan kami sendiri.</p>
                </div>

                <div class="term-section">
                    <h2>4. Persyaratan untuk Guru</h2>
                    <p>Untuk mendaftar sebagai Guru di Platform kami, Anda harus memenuhi persyaratan berikut:</p>
                    <ul>
                        <li>Berusia minimal 18 tahun.</li>
                        <li>Memiliki kualifikasi akademik dan profesional yang sesuai.</li>
                        <li>Menyediakan dokumen identifikasi dan verifikasi yang sah.</li>
                        <li>Lulus proses verifikasi kami.</li>
                        <li>Mematuhi kode etik dan standar profesional yang ditetapkan oleh Platform.</li>
                    </ul>
                    <p>Anda bertanggung jawab untuk memastikan bahwa semua informasi yang Anda berikan dalam profil Anda akurat dan tidak menyesatkan. Kami berhak untuk menolak atau menangguhkan akun Guru yang tidak memenuhi standar kami.</p>
                </div>

                <div class="term-section">
                    <h2>5. Persyaratan untuk Sekolah</h2>
                    <p>Untuk mendaftar sebagai Sekolah di Platform kami, Anda harus memenuhi persyaratan berikut:</p>
                    <ul>
                        <li>Merupakan institusi pendidikan yang sah dan terdaftar.</li>
                        <li>Memiliki izin operasional yang valid dari otoritas pendidikan yang relevan.</li>
                        <li>Menyediakan dokumen identifikasi dan verifikasi yang sah.</li>
                        <li>Lulus proses verifikasi kami.</li>
                    </ul>
                    <p>Sekolah bertanggung jawab untuk memastikan bahwa semua informasi yang diberikan dalam profil akurat dan tidak menyesatkan. Kami berhak untuk menolak atau menangguhkan akun Sekolah yang tidak memenuhi standar kami.</p>
                </div>

                <div class="term-section">
                    <h2>6. Penugasan dan Pembayaran</h2>
                    <p><strong>Proses Penugasan:</strong> Platform kami memfasilitasi koneksi antara Guru dan Sekolah. Ketika Sekolah membuat permintaan penugasan, Guru yang memenuhi kriteria dapat melamar. Sekolah kemudian dapat memilih Guru yang sesuai.</p>
                    <p><strong>Pembayaran:</strong> Semua pembayaran untuk layanan dilakukan melalui Platform kami. Sekolah akan membayar biaya yang disepakati ditambah biaya layanan Platform. Guru akan menerima pembayaran setelah layanan selesai dan dikonfirmasi oleh Sekolah.</p>
                    <p><strong>Biaya Platform:</strong> Kami mengenakan biaya layanan sebesar 10% dari nilai penugasan, yang dibayarkan oleh Sekolah. Guru menerima 90% dari nilai penugasan yang disepakati.</p>
                    <p><strong>Pembatalan:</strong> Kebijakan pembatalan ditentukan berdasarkan waktu pembatalan. Pembatalan dalam 24 jam sebelum jadwal mengajar dapat dikenakan biaya. Silakan lihat kebijakan pembatalan kami untuk detail lebih lanjut.</p>
                </div>

                <div class="term-section">
                    <h2>7. Hak dan Tanggung Jawab</h2>
                    <p><strong>Hak dan Tanggung Jawab Guru:</strong></p>
                    <ul>
                        <li>Memberikan layanan mengajar profesional sesuai standar yang ditetapkan.</li>
                        <li>Mematuhi jadwal dan ketentuan yang disepakati dalam penugasan.</li>
                        <li>Menjaga kerahasiaan informasi sensitif tentang siswa dan sekolah.</li>
                        <li>Hak untuk menerima atau menolak penugasan.</li>
                        <li>Hak untuk menerima pembayaran tepat waktu sesuai kesepakatan.</li>
                    </ul>
                    
                    <p><strong>Hak dan Tanggung Jawab Sekolah:</strong></p>
                    <ul>
                        <li>Memberikan deskripsi penugasan yang akurat dan detail.</li>
                        <li>Menyediakan lingkungan kerja yang aman dan mendukung untuk Guru.</li>
                        <li>Melakukan pembayaran tepat waktu sesuai kesepakatan.</li>
                        <li>Hak untuk memilih Guru yang sesuai kebutuhan.</li>
                        <li>Hak untuk memberikan ulasan dan umpan balik tentang layanan Guru.</li>
                    </ul>
                    
                    <p><strong>Hak dan Tanggung Jawab Platform:</strong></p>
                    <ul>
                        <li>Memfasilitasi koneksi yang efisien antara Guru dan Sekolah.</li>
                        <li>Memverifikasi identitas dan kredensial Guru dan Sekolah.</li>
                        <li>Mengamankan pembayaran dan memastikan distribusi yang adil.</li>
                        <li>Memberikan dukungan dan resolusi perselisihan bila diperlukan.</li>
                        <li>Hak untuk menangguhkan atau mengakhiri akun yang melanggar ketentuan.</li>
                    </ul>
                </div>

                <div class="term-section">
                    <h2>8. Ulasan dan Penilaian</h2>
                    <p>Setelah penugasan selesai, Guru dan Sekolah dapat saling memberikan ulasan dan penilaian. Ulasan ini penting untuk mempertahankan standar kualitas tinggi di Platform kami.</p>
                    <p>Ulasan harus jujur, akurat, dan tidak menyesatkan. Ulasan yang tidak pantas, kasar, atau berniat jahat dapat dihapus oleh Platform.</p>
                    <p>Kami berhak untuk menampilkan, menghapus, atau menyunting ulasan sesuai kebijakan kami.</p>
                </div>

                <div class="term-section">
                    <h2>9. Privasi dan Keamanan Data</h2>
                    <p>Kami menghargai privasi Anda dan berkomitmen untuk melindungi data pribadi Anda. Penggunaan data pribadi Anda diatur oleh Kebijakan Privasi kami, yang merupakan bagian dari Syarat dan Ketentuan ini.</p>
                    <p>Anda setuju bahwa kami dapat mengumpulkan, menyimpan, menggunakan, dan membagikan informasi Anda sesuai dengan Kebijakan Privasi kami.</p>
                </div>

                <div class="term-section">
                    <h2>10. Pembatasan Tanggung Jawab</h2>
                    <p>Platform kami disediakan "sebagaimana adanya" dan "sebagaimana tersedia". Kami tidak memberikan jaminan, baik tersurat maupun tersirat, termasuk tetapi tidak terbatas pada, jaminan kelayakan untuk tujuan tertentu dan non-pelanggaran.</p>
                    <p>Kami tidak bertanggung jawab atas kerugian atau kerusakan yang timbul dari penggunaan atau ketidakmampuan menggunakan Platform kami, termasuk tetapi tidak terbatas pada, kerugian tak langsung, konsekuensial, atau insidental.</p>
                    <p>Tanggung jawab kami kepada Anda tidak akan melebihi jumlah yang Anda bayarkan kepada kami dalam 12 bulan terakhir.</p>
                </div>

                <div class="term-section">
                    <h2>11. Penyelesaian Sengketa</h2>
                    <p>Segala sengketa yang timbul dari atau berkaitan dengan Syarat dan Ketentuan ini akan diselesaikan melalui negosiasi yang dilakukan dengan itikad baik. Jika negosiasi gagal, sengketa akan diselesaikan melalui arbitrase sesuai dengan hukum yang berlaku di Indonesia.</p>
                </div>

                <div class="term-section">
                    <h2>12. Perubahan pada Syarat dan Ketentuan</h2>
                    <p>Kami berhak mengubah Syarat dan Ketentuan ini kapan saja. Perubahan akan efektif setelah diposting di Platform. Penggunaan berkelanjutan Anda atas Platform setelah perubahan merupakan penerimaan Anda terhadap Syarat dan Ketentuan yang diperbarui.</p>
                </div>

                <div class="term-section">
                    <h2>13. Hukum yang Berlaku</h2>
                    <p>Syarat dan Ketentuan ini diatur oleh dan ditafsirkan sesuai dengan hukum Republik Indonesia.</p>
                </div>

                <div class="term-section">
                    <h2>14. Hubungi Kami</h2>
                    <p>Jika Anda memiliki pertanyaan atau kekhawatiran tentang Syarat dan Ketentuan ini, silakan hubungi kami di:</p>
                    <p>Email: <?php echo config('admin_email'); ?><br>
                    Telepon: <?php echo config('support_phone'); ?><br>
                    Alamat: Jl. Umban Sari, Pekanbaru, 28265, Indonesia</p>
                </div>

                <div class="term-section">
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