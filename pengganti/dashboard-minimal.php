<?php
/**
 * GuruSinergi - Dashboard Minimal
 * 
 * File untuk tes koneksi database dengan kode paling minimal
 */

// Set error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Dashboard Minimal GuruSinergi</h1>";
echo "<p>Tes koneksi database dengan kode minimal</p>";

// Kredensial database
$db_host = 'localhost';
$db_user = 'u532109326_gurupengganti';
$db_pass = 'Gurukupahlawanku99';
$db_name = 'u532109326_guru_pengganti';

echo "<h2>Informasi Kredensial Database:</h2>";
echo "<ul>";
echo "<li>Host: $db_host</li>";
echo "<li>User: $db_user</li>";
echo "<li>Database: $db_name</li>";
echo "</ul>";

// Tes koneksi mysqli langsung dengan output debug
echo "<h2>Tes Koneksi #1 (mysqli):</h2>";
try {
    echo "<p>Mencoba koneksi ke database...</p>";
    
    $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    if ($mysqli->connect_errno) {
        echo "<p style='color:red;'>Koneksi Gagal: " . $mysqli->connect_error . "</p>";
        echo "<p style='color:red;'>Error code: " . $mysqli->connect_errno . "</p>";
    } else {
        echo "<p style='color:green;'>Koneksi Berhasil!</p>";
        echo "<p>Versi MySQL: " . $mysqli->server_info . "</p>";
        echo "<p>Karakter Set: " . $mysqli->character_set_name() . "</p>";
        
        // Tes query sederhana
        echo "<h3>Tes Query:</h3>";
        $result = $mysqli->query("SHOW TABLES");
        if ($result) {
            echo "<p style='color:green;'>Query berhasil dijalankan</p>";
            echo "<p>Jumlah tabel: " . $result->num_rows . "</p>";
            echo "<ul>";
            while ($row = $result->fetch_row()) {
                echo "<li>" . $row[0] . "</li>";
            }
            echo "</ul>";
            $result->free();
        } else {
            echo "<p style='color:red;'>Query gagal: " . $mysqli->error . "</p>";
        }
        
        $mysqli->close();
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}

// Alternatif tes koneksi dengan PDO
echo "<h2>Tes Koneksi #2 (PDO):</h2>";
try {
    echo "<p>Mencoba koneksi dengan PDO...</p>";
    
    $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
    echo "<p style='color:green;'>Koneksi PDO Berhasil!</p>";
    
    // Tes query dengan PDO
    echo "<h3>Tes Query dengan PDO:</h3>";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p>Jumlah tabel dengan PDO: " . count($tables) . "</p>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>" . $table . "</li>";
    }
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<p style='color:red;'>Koneksi PDO Gagal: " . $e->getMessage() . "</p>";
}

// Informasi PHP
echo "<h2>Informasi Server:</h2>";
echo "<ul>";
echo "<li>PHP Version: " . phpversion() . "</li>";
echo "<li>Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "</li>";
echo "<li>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</li>";
echo "<li>MySQL Client Info: " . (function_exists('mysqli_get_client_info') ? mysqli_get_client_info() : 'Not available') . "</li>";
echo "</ul>";

// Tes ekstensi PHP
echo "<h2>Ekstensi PHP yang Tersedia:</h2>";
echo "<ul>";
$extensions = get_loaded_extensions();
foreach ($extensions as $extension) {
    echo "<li>" . $extension . "</li>";
}
echo "</ul>";

// Rekomendasi
echo "<h2>Kemungkinan Solusi Jika Masih Gagal:</h2>";
echo "<ol>";
echo "<li>Periksa apakah kredensial database benar-benar tepat</li>";
echo "<li>Pastikan user database memiliki hak akses yang diperlukan</li>";
echo "<li>Coba gunakan '127.0.0.1' sebagai host alih-alih 'localhost'</li>";
echo "<li>Periksa pembatasan koneksi di konfigurasi database (max_connections, host restrictions, dll)</li>";
echo "<li>Verifikasi koneksi ke database melalui phpMyAdmin atau tools lain</li>";
echo "<li>Cek apakah ada proxy atau firewall yang mungkin memblokir koneksi</li>";
echo "<li>Hubungi admin hosting untuk bantuan lebih lanjut</li>";
echo "</ol>";
?>