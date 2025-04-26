# Platform Guru Pengganti - GuruSinergi

Platform Guru Pengganti adalah sistem berbasis web yang dikembangkan untuk memfasilitasi penggantian guru yang sedang cuti atau berhalangan hadir. Sistem ini menghubungkan sekolah dengan guru pengganti yang tersedia, memudahkan proses pencocokan, penugasan, dan pembayaran.

## Fitur Utama

- **Sistem pendaftaran dan profil** untuk guru dan sekolah
- **Verifikasi profil** guru dan sekolah oleh admin
- **Pencocokan guru pengganti** dengan kebutuhan sekolah berdasarkan mata pelajaran, tingkat kelas, dan ketersediaan
- **Manajemen penugasan** lengkap dengan detail dan status
- **Sistem lamaran** untuk guru yang ingin mengajar di sekolah tertentu
- **Sistem pembayaran terintegrasi** dengan Tripay Payment Gateway
- **Komunikasi antara guru dan sekolah** melalui platform
- **Notifikasi** untuk berbagai aktivitas
- **Penilaian dan ulasan** untuk guru dan sekolah

## Struktur Direktori

```
/pengganti.gurusinergi.com/
  ├── config/                 # Konfigurasi aplikasi
  ├── includes/               # File fungsi dan helper
  ├── templates/              # Template header, footer, dll
  ├── assets/                 # Asset statis (CSS, JS, images)
  ├── uploads/                # File yang diunggah pengguna
  ├── admin/                  # Panel admin
  ├── pages/                  # Halaman-halaman utama
  ├── api/                    # Endpoint API (jika diperlukan)
  ├── .htaccess               # Konfigurasi Apache
  ├── index.php               # File index utama
  └── README.md               # Dokumentasi
```

## Persyaratan Sistem

- PHP 7.4 atau lebih tinggi
- MySQL 5.7 atau lebih tinggi
- Server web Apache dengan mod_rewrite diaktifkan
- Ekstensi PHP: PDO, mysqli, curl, gd, mbstring

## Instalasi

1. **Siapkan database MySQL**

   Buat database baru untuk aplikasi:
   ```sql
   CREATE DATABASE guru_pengganti;
   ```

2. **Konfigurasi aplikasi**

   Edit file `config/config.php` dan sesuaikan pengaturan database:
   ```php
   'db_host' => 'localhost',
   'db_name' => 'guru_pengganti',
   'db_user' => 'root', // Ganti dengan username database Anda
   'db_pass' => '',     // Ganti dengan password database Anda
   ```

3. **Set up Tripay Payment Gateway (opsional)**

   Jika Anda ingin menggunakan fitur pembayaran, daftar di [Tripay](https://tripay.co.id/) dan dapatkan API key:
   ```php
   'tripay_api_key' => 'YOUR_TRIPAY_API_KEY',
   'tripay_merchant_code' => 'YOUR_MERCHANT_CODE',
   ```

4. **Setup database awal**

   Akses URL berikut untuk membuat tabel-tabel yang diperlukan:
   ```
   https://pengganti.gurusinergi.com/?setup=database
   ```

5. **Login sebagai admin**

   Secara default, sistem membuat akun admin:
   - Username: admin
   - Password: admin123

   Segera ubah password ini setelah login pertama!

## Pengembangan

### Menambahkan Halaman Baru

1. Buat file PHP baru di folder pages/ atau folder yang sesuai
2. Include file-file yang diperlukan:
   ```php
   require_once 'config/config.php';
   require_once 'includes/functions.php';
   ```
3. Tambahkan logika bisnis dan konten halaman
4. Include template header dan footer:
   ```php
   include_once 'templates/header.php';
   // Konten halaman
   include_once 'templates/footer.php';
   ```

### Menambahkan Fungsi Baru

1. Tentukan file yang sesuai di folder includes/
2. Buat fungsi dengan dokumentasi yang jelas
3. Pastikan untuk menangani error dengan baik

### Extensi dan Kustomisasi

Sistem dirancang untuk memudahkan kustomisasi:
- Gunakan CSS kustom di folder assets/css/
- Tambahkan JavaScript kustom di folder assets/js/
- Buat template baru di folder templates/

## Deployment

1. Upload semua file ke server
2. Buat database dan sesuaikan konfigurasi
3. Setup database dengan mengakses /?setup=database
4. Pastikan direktori uploads/ dan subdirektorinya dapat ditulis oleh server web:
   ```
   chmod -R 775 uploads/
   ```

## Kontribusi

Silakan fork repositori, buat branch untuk fitur atau perbaikan, dan kirim pull request.

## Lisensi

Hak Cipta © 2023 GuruSinergi. Semua hak dilindungi.

## Kontak

Untuk pertanyaan atau bantuan, hubungi:
- Email: gurusinergi@gmail.com
- Telepon: +62 895 1300 5831