<?php
/**
 * GuruSinergi - Logout
 * 
 * Halaman untuk logout pengguna
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include file konfigurasi
require_once 'config/config.php';

// Include file database
require_once 'config/database.php';

// Include file functions
require_once 'includes/functions.php';

// Include file auth functions
require_once 'includes/auth-functions.php';

// Lakukan logout
logout();

// Redirect ke halaman utama menggunakan header() karena fungsi redirect() tidak tersedia
header('Location: ' . url());
exit; // Pastikan tidak ada kode yang dijalankan setelah redirect
?>