<?php
// Pengaturan session (HARUS di-include SEBELUM session_start)
ini_set('session.cookie_lifetime', 60 * 60 * 24 * 7); // 7 hari
ini_set('session.gc_maxlifetime', 60 * 60 * 24 * 7); // 7 hari

// Mulai session jika belum dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}