<?php
/**
 * File ini akan memulai session jika belum ada, dan mengecek apakah pengguna sudah login.
 * File ini harus dipanggil di paling atas setiap halaman yang terproteksi.
 * Pastikan koneksi.php sudah dipanggil SEBELUM file ini.
 */

// Memulai session hanya jika belum ada yang aktif.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah session 'id_user' ada atau tidak.
// Jika tidak ada, artinya pengguna belum login.
if (!isset($_SESSION['id_user'])) {
    // Alihkan paksa pengguna ke halaman login.
    header('Location: login.php');
    // Hentikan eksekusi skrip untuk mencegah konten halaman asli tampil.
    exit;
}
?>