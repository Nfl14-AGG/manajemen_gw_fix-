<?php
// Tampilkan semua error untuk debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Panggil file koneksi
require_once 'config/koneksi.php';

// Data user yang ingin kita pastikan benar
$username = 'admin';
$password = 'admin123';
$nama_lengkap = 'Administrator';

echo "<h1>Memperbaiki User 'admin'...</h1>";

try {
    // Buat HASH yang BENAR untuk password 'admin123'
    $new_password_hash = password_hash($password, PASSWORD_DEFAULT);

    // KARENA USER 'admin' SUDAH ADA, KITA PAKSA UPDATE PASSWORDNYA
    $stmt_update = $pdo->prepare("UPDATE users SET password_hash = ?, nama_lengkap = ? WHERE username = ?");
    
    // Jalankan query update
    $stmt_update->execute([$new_password_hash, $nama_lengkap, $username]);

    // Berikan konfirmasi
    echo "<h2 style='color:green;'>BERHASIL!</h2>";
    echo "<p>Password untuk user '<b>admin</b>' telah berhasil diperbaiki di database.</p>";
    echo "<p>Silakan kembalikan file login.php Anda ke versi normal dan coba login kembali.</p>";
    echo "<p style='color:red; margin-top: 20px;'><b>PENTING:</b> Setelah berhasil login, segera HAPUS file <b>create_user.php</b> ini.</p>";

} catch (PDOException $e) {
    // Jika ada error koneksi atau query, tampilkan di sini
    die("ERROR DATABASE: " . $e->getMessage());
}