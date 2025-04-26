<?php
// Aktifkan tampilan error
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Informasi server dan PHP
echo '<h1>Diagnosa Server GuruSinergi</h1>';
echo '<h2>Informasi PHP</h2>';
echo '<ul>';
echo '<li>PHP Version: ' . phpversion() . '</li>';
echo '<li>Server Software: ' . $_SERVER['SERVER_SOFTWARE'] . '</li>';
echo '<li>Document Root: ' . $_SERVER['DOCUMENT_ROOT'] . '</li>';
echo '<li>Script Filename: ' . $_SERVER['SCRIPT_FILENAME'] . '</li>';
echo '</ul>';

// Cek file-file penting
echo '<h2>Pengecekan File Penting</h2>';
$files_to_check = [
    'config/config.php',
    'config/database.php',
    'includes/functions.php',
    'includes/auth-functions.php',
    'includes/notification-functions.php',
    'templates/header.php',
    'templates/footer.php',
    'complete-profile.php'
];

echo '<ul>';
foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo '<li style="color:green">' . $file . ' - ADA</li>';
        
        // Cek permission
        $perms = substr(sprintf('%o', fileperms($file)), -4);
        echo '<ul><li>Permissions: ' . $perms . '</li></ul>';
    } else {
        echo '<li style="color:red">' . $file . ' - TIDAK ADA</li>';
        
        // Coba cari dengan path lain
        $alt_path = '../' . $file;
        if (file_exists($alt_path)) {
            echo '<ul><li style="color:orange">Ditemukan di: ' . $alt_path . '</li></ul>';
        }
    }
}
echo '</ul>';

// Cek direktori upload
echo '<h2>Pengecekan Direktori Upload</h2>';
$upload_dirs = [
    'uploads',
    'uploads/guru',
    'uploads/sekolah'
];

echo '<ul>';
foreach ($upload_dirs as $dir) {
    if (is_dir($dir)) {
        echo '<li style="color:green">' . $dir . ' - ADA</li>';
        
        // Cek permission
        $perms = substr(sprintf('%o', fileperms($dir)), -4);
        echo '<ul><li>Permissions: ' . $perms . '</li>';
        
        // Cek writeable
        if (is_writable($dir)) {
            echo '<li style="color:green">Dapat ditulis</li></ul>';
        } else {
            echo '<li style="color:red">TIDAK dapat ditulis</li></ul>';
        }
    } else {
        echo '<li style="color:red">' . $dir . ' - TIDAK ADA</li>';
        
        // Coba buat direktori
        echo '<ul>';
        if (@mkdir($dir, 0755, true)) {
            echo '<li style="color:green">Berhasil membuat direktori</li>';
        } else {
            echo '<li style="color:red">GAGAL membuat direktori</li>';
        }
        echo '</ul>';
    }
}
echo '</ul>';

// Cek session
echo '<h2>Pengecekan Session</h2>';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    echo '<p style="color:orange">Session dimulai dalam file ini</p>';
} else {
    echo '<p style="color:green">Session sudah aktif</p>';
}

// Uji session
$_SESSION['test_value'] = 'Test ' . time();
echo '<p>Test value: ' . $_SESSION['test_value'] . '</p>';

// Cek koneksi database
echo '<h2>Tes Koneksi Database</h2>';
try {
    // Coba include file database.php
    if (file_exists('config/database.php')) {
        require_once 'config/database.php';
    } elseif (file_exists('../config/database.php')) {
        require_once '../config/database.php';
    } else {
        throw new Exception("File database.php tidak ditemukan");
    }
    
    // Cek apakah fungsi db_connect tersedia
    if (function_exists('db_connect')) {
        $conn = db_connect();
        if ($conn) {
            echo '<p style="color:green">Koneksi database berhasil</p>';
            
            // Cek tabel yang diperlukan
            $tables_to_check = ['users', 'profiles_guru', 'profiles_sekolah'];
            
            echo '<h3>Pengecekan Tabel</h3>';
            echo '<ul>';
            foreach ($tables_to_check as $table) {
                try {
                    $stmt = $conn->query("SELECT 1 FROM $table LIMIT 1");
                    echo '<li style="color:green">Tabel ' . $table . ' - OK</li>';
                } catch (PDOException $e) {
                    echo '<li style="color:red">Tabel ' . $table . ' - ERROR: ' . $e->getMessage() . '</li>';
                }
            }
            echo '</ul>';
        } else {
            echo '<p style="color:red">Koneksi database GAGAL</p>';
        }
    } else {
        echo '<p style="color:red">Fungsi db_connect() tidak ditemukan</p>';
        
        // Coba koneksi langsung
        echo '<h3>Mencoba koneksi langsung</h3>';
        echo '<p>Silakan isi informasi database berikut:</p>';
        echo '<form method="post">';
        echo '<div><label>Host: <input type="text" name="db_host" value="localhost"></label></div>';
        echo '<div><label>Database: <input type="text" name="db_name"></label></div>';
        echo '<div><label>Username: <input type="text" name="db_user"></label></div>';
        echo '<div><label>Password: <input type="password" name="db_pass"></label></div>';
        echo '<div><button type="submit" name="test_db">Test Koneksi</button></div>';
        echo '</form>';
        
        if (isset($_POST['test_db'])) {
            try {
                $db_host = $_POST['db_host'];
                $db_name = $_POST['db_name'];
                $db_user = $_POST['db_user'];
                $db_pass = $_POST['db_pass'];
                
                $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                echo '<p style="color:green">Koneksi langsung berhasil!</p>';
            } catch (PDOException $e) {
                echo '<p style="color:red">Koneksi langsung GAGAL: ' . $e->getMessage() . '</p>';
            }
        }
    }
} catch (Exception $e) {
    echo '<p style="color:red">ERROR: ' . $e->getMessage() . '</p>';
}

// Cek URL dan rute
echo '<h2>Pengecekan URL dan Rute</h2>';
echo '<ul>';
echo '<li>REQUEST_URI: ' . $_SERVER['REQUEST_URI'] . '</li>';
echo '<li>SCRIPT_NAME: ' . $_SERVER['SCRIPT_NAME'] . '</li>';
echo '<li>PHP_SELF: ' . $_SERVER['PHP_SELF'] . '</li>';
if (isset($_SERVER['REDIRECT_URL'])) {
    echo '<li>REDIRECT_URL: ' . $_SERVER['REDIRECT_URL'] . '</li>';
}
echo '</ul>';

// Cek isi .htaccess jika ada
echo '<h2>Pengecekan .htaccess</h2>';
if (file_exists('.htaccess')) {
    echo '<pre>' . htmlspecialchars(file_get_contents('.htaccess')) . '</pre>';
} else {
    echo '<p>File .htaccess tidak ditemukan di direktori ini</p>';
}

echo '<h2>Rekomendasi</h2>';
echo '<p>Untuk memastikan halaman complete-profile.php dapat diakses, gunakan alternatif berikut:</p>';
echo '<ol>';
echo '<li><a href="alternate-profile.php">Gunakan halaman alternate-profile.php</a> - Halaman alternatif dengan kode minimal</li>';
echo '<li><a href="profile-js.php">Gunakan halaman profile-js.php</a> - Halaman dengan JavaScript untuk menangani formulir</li>';
echo '<li>Pastikan semua file yang diperlukan ada dan dapat diakses</li>';
echo '<li>Periksa permission folder uploads</li>';
echo '<li>Pastikan fungsi-fungsi yang diperlukan tersedia</li>';
echo '</ol>';
?>