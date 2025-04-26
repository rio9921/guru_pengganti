<?php
/**
 * GuruSinergi - Konfigurasi Platform Guru Pengganti
 * 
 * File konfigurasi utama untuk platform guru pengganti
 */

// Informasi situs
$config = [
    'site_url' => 'https://pengganti.gurusinergi.com',
    'main_site_url' => 'https://gurusinergi.com',
    'site_name' => 'GuruSinergi - Program Guru Pengganti',
    'admin_email' => 'admin@gurusinergi.com',
    'support_phone' => '+62 895 1300 5831',
    
    // Database
    'db_host' => 'localhost',
    'db_name' => 'u532109326_guru_pengganti',
    'db_user' => 'u532109326_gurupengganti',
    'db_pass' => 'Gurukupahlawanku99',
    
    // Pembayaran
    'tripay_api_key' => 'YOUR_TRIPAY_API_KEY', // Ganti dengan API key Tripay Anda
    'tripay_merchant_code' => 'YOUR_MERCHANT_CODE', // Ganti dengan merchant code Tripay Anda
    'platform_fee' => 10, // Persentase biaya platform dari total transaksi
    
    // Versi platform
    'version' => '1.0.0',
    
    // Debugging
    'debug_mode' => true, // Diubah menjadi true untuk membantu debugging
];

// Definisi konstanta penting - cek dulu jika sudah didefinisikan
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__) . '/');
}
if (!defined('INCLUDE_PATH')) {
    define('INCLUDE_PATH', BASE_PATH . 'includes/');
}
if (!defined('TEMPLATE_PATH')) {
    define('TEMPLATE_PATH', BASE_PATH . 'templates/');
}
if (!defined('UPLOAD_PATH')) {
    define('UPLOAD_PATH', BASE_PATH . 'uploads/');
}
if (!defined('ASSET_PATH')) {
    define('ASSET_PATH', BASE_PATH . 'assets/');
}

// Zona waktu
date_default_timezone_set('Asia/Jakarta');

// Fungsi untuk mendapatkan nilai konfigurasi
function config($key, $default = null) {
    global $config;
    return isset($config[$key]) ? $config[$key] : $default;
}

// Fungsi untuk URL absolut
function url($path = '') {
    return rtrim(config('site_url'), '/') . '/' . ltrim($path, '/');
}

// Fungsi untuk asset URL
function asset($path = '') {
    return url('assets/' . ltrim($path, '/'));
}