<?php
/**
 * GuruSinergi - Database Connection Test
 * 
 * File ini untuk menguji koneksi database secara langsung
 */

// Tampilkan semua error PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Kredensial database sesuai config
$db_host = 'localhost';
$db_user = 'u532109326_gurupengganti';
$db_pass = 'Gurukupahlawanku99';
$db_name = 'u532109326_guru_pengganti';

echo "<h1>Test Koneksi Database GuruSinergi</h1>";

// Test koneksi tanpa database
echo "<h2>Test 1: Koneksi ke server MySQL/MariaDB</h2>";
try {
    $conn = new mysqli($db_host, $db_user, $db_pass);
    if ($conn->connect_error) {
        throw new Exception("Koneksi ke server database gagal: " . $conn->connect_error);
    }
    echo "<p style='color:green;'>✅ Koneksi ke server database berhasil!</p>";
    $conn->close();
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ " . $e->getMessage() . "</p>";
    echo "<p>Tindakan: Periksa hostname, username, dan password.</p>";
}

// Test koneksi dengan database
echo "<h2>Test 2: Koneksi ke database '$db_name'</h2>";
try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        throw new Exception("Koneksi ke database gagal: " . $conn->connect_error);
    }
    echo "<p style='color:green;'>✅ Koneksi ke database berhasil!</p>";
    $conn->close();
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ " . $e->getMessage() . "</p>";
    echo "<p>Tindakan: Periksa nama database dan pastikan database tersebut ada.</p>";
}

// Test koneksi PDO (alternatif)
echo "<h2>Test 3: Koneksi menggunakan PDO</h2>";
try {
    $dsn = "mysql:host=$db_host;dbname=$db_name";
    $pdo = new PDO($dsn, $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color:green;'>✅ Koneksi PDO berhasil!</p>";
} catch (PDOException $e) {
    echo "<p style='color:red;'>❌ " . $e->getMessage() . "</p>";
    echo "<p>Tindakan: Periksa kredensial database dan pastikan ekstensi PDO tersedia.</p>";
}

// Informasi server
echo "<h2>Test 4: Informasi Server</h2>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
echo "<p><strong>Server Software:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p><strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "</p>";

// Informasi ekstensi
echo "<h2>Test 5: Ekstensi MySQL/MariaDB</h2>";
if (extension_loaded('mysqli')) {
    echo "<p style='color:green;'>✅ Ekstensi MySQLi tersedia.</p>";
} else {
    echo "<p style='color:red;'>❌ Ekstensi MySQLi tidak tersedia!</p>";
}

if (extension_loaded('pdo_mysql')) {
    echo "<p style='color:green;'>✅ Ekstensi PDO MySQL tersedia.</p>";
} else {
    echo "<p style='color:red;'>❌ Ekstensi PDO MySQL tidak tersedia!</p>";
}

// Saran perbaikan
echo "<h2>Kemungkinan Solusi:</h2>";
echo "<ol>";
echo "<li>Pastikan kredensial database sesuai dengan pengaturan cpanel atau panel hosting Anda</li>";
echo "<li>Cek apakah database sudah dibuat dan tersedia</li>";
echo "<li>Pastikan user database memiliki hak akses yang cukup</li>";
echo "<li>Periksa apakah alamat IP server web diizinkan untuk mengakses database</li>";
echo "<li>Hubungi layanan hosting jika masalah tetap berlanjut</li>";
echo "</ol>";
?>