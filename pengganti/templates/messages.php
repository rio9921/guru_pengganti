<?php
/**
 * GuruSinergi - Messages Template
 * 
 * Template untuk menampilkan pesan error dan sukses
 */

// Display error message if exists
if (isset($_SESSION['error_message']) && !empty($_SESSION['error_message'])) {
    echo '<div class="alert alert-danger">';
    echo $_SESSION['error_message'];
    echo '</div>';
    unset($_SESSION['error_message']);
}

// Display success message if exists
if (isset($_SESSION['success_message']) && !empty($_SESSION['success_message'])) {
    echo '<div class="alert alert-success">';
    echo $_SESSION['success_message'];
    echo '</div>';
    unset($_SESSION['success_message']);
}