<?php
// Selalu mulai sesi di awal untuk dapat memanipulasinya.
session_start();

// 1. Hapus semua variabel sesi.
$_SESSION = [];

// 2. Hapus cookie sesi dari browser.
// Ini penting untuk memastikan browser tidak mengirimkan ID sesi lama pada request berikutnya.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Hancurkan sesi di server.
session_destroy();

// 4. Alihkan pengguna ke halaman login dengan pesan sukses.
header('Location: login.php?status=logout_sukses');
exit; // Pastikan tidak ada kode lain yang dieksekusi setelah redirect.