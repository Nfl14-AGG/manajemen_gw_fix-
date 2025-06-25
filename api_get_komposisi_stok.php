<?php
// Mengatur header agar output berupa JSON
header('Content-Type: application/json');

// Memanggil koneksi dan penjaga keamanan
require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/auth_check.php'; // <-- TAMBAHKAN BARIS INI

try {
    // Query ini diubah total agar sesuai dengan struktur database Anda
    // dan menggunakan stok_akhir
    $query = "
        SELECT 'Wearpack' as kategori, COALESCE(SUM(stok_akhir), 0) as total FROM wearpack
        UNION ALL
        SELECT 'Kaos' as kategori, COALESCE(SUM(stok_akhir), 0) as total FROM kaos
        UNION ALL
        SELECT 'Bordir' as kategori, COALESCE(SUM(stok_akhir), 0) as total FROM bordir
    ";
    
    $stmt = $pdo->query($query);
    $stok_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Menyiapkan data untuk dikirim sebagai JSON
    $labels = [];
    $jumlah = [];

    foreach ($stok_data as $row) {
        $labels[] = $row['kategori'];
        $jumlah[] = (int)$row['total'];
    }

    $data = [
        'labels' => $labels,
        'jumlah' => $jumlah
    ];

    echo json_encode($data);

} catch (PDOException $e) {
    // Jika terjadi error, kirim pesan error dalam format JSON
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Gagal mengambil data stok: ' . $e->getMessage()]);
}
?>