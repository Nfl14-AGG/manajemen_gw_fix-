<?php
// Konfigurasi Database
$host = 'gateway01.eu-central-1.prod.aws.tidbcloud.com';
$dbname = 'test';
$port ='4000';
$user = '2aob43h9hiq6hQh.root'; // Default username XAMPP
$pass = 'Mp3pEXOqknkPMyhu';   // Default password XAMPP adalah kosong

// Opsi untuk koneksi PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Membuat koneksi PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, $options);
} catch (PDOException $e) {
    // Jika koneksi gagal, hentikan eksekusi dan tampilkan pesan error
    die("Koneksi ke database gagal: " . $e->getMessage());
}

// Mulai session untuk melacak status login pengguna
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
