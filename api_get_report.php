<?php
// api_get_report.php

// Mengatur header agar output berupa HTML (karena akan merender baris tabel <tr>)
header('Content-Type: text/html');

// Memanggil koneksi dan penjaga keamanan
require_once __DIR__ . '/config/koneksi.php'; // Perbaikan: ganti nama file
require_once __DIR__ . '/auth_check.php';      // Penambahan: tambahkan penjaga

// Fungsi untuk merender tabel (untuk menghindari duplikasi kode)
function render_wearpack_table($pdo) {
    // Kode ini hanyalah contoh kerangka, sesuaikan dengan query Anda yang sebenarnya
    $output = '';
    // Pastikan query mengambil data yang benar
    $stmt = $pdo->query("SELECT model, warna, SUM(stok_akhir) AS total_keseluruhan FROM wearpack GROUP BY model, warna");
    if ($stmt->rowCount() > 0) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Contoh baris tabel, sesuaikan dengan kebutuhan Anda
            $output .= "<tr>";
            $output .= "<td>".htmlspecialchars($row['model'])."</td>";
            $output .= "<td>".htmlspecialchars($row['warna'])."</td>";
            $output .= "<td><strong>".htmlspecialchars($row['total_keseluruhan'])."</strong></td>";
            $output .= "</tr>";
        }
    } else {
        $output = '<tr><td colspan="3" class="text-center p-4">Tidak ada data.</td></tr>';
    }
    return $output;
}

// Lakukan hal yang sama untuk kaos, bordir, dll.
// function render_kaos_table($pdo) { /* ... */ }

$report_type = $_GET['report'] ?? '';

switch ($report_type) {
    case 'wearpack':
        echo render_wearpack_table($pdo);
        break;
    case 'kaos':
        // echo render_kaos_table($pdo); // Anda bisa membuat fungsi ini nanti
        break;
    // ... case lainnya
    default:
        http_response_code(400); // Bad Request
        echo '<tr><td colspan="8" class="text-danger text-center p-4">Error: Tipe laporan tidak valid.</td></tr>';
        break;
}
?>