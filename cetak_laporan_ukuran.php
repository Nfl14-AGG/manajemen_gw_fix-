<?php
require_once 'config/koneksi.php';
require_once 'auth_check.php';
// Mengambil definisi laporan dari file konfigurasi terpusat
require_once 'config/report_config.php';

// Gabungkan semua laporan menjadi satu array untuk kemudahan akses
$semua_reports = array_merge($reports['produk'], $reports['material']);

// Menangkap filter 'tampil' dari URL
$tampil = $_GET['tampil'] ?? 'semua';

// --- NAMA FILE DINAMIS YANG LEBIH KONSISTEN ---
$nama_file_suffix = 'ringkasan_stok_total'; // Default untuk 'semua'
if ($tampil !== 'semua' && isset($semua_reports[$tampil])) {
    // Gunakan judul dari config, ubah spasi menjadi underscore dan buat huruf kecil
    $nama_file_suffix = str_replace(' ', '_', strtolower($semua_reports[$tampil]['title']));
}
$filename = "laporan_" . $nama_file_suffix . "_" . date('Y-m-d') . ".csv";


// Menyiapkan header HTTP untuk download file CSV
header('Content-Type: text/csv; charset=utf-8');
header("Content-Disposition: attachment; filename=\"$filename\"");

// Membuka stream output PHP untuk menulis file CSV
$output = fopen('php://output', 'w');


// --- FUNGSI UNTUK SPASI ANTAR BAGIAN ---
function fput_separator($output, $lines = 2) {
    for ($i=0; $i < $lines; $i++) { 
        fputcsv($output, []);
    }
}

// --- FUNGSI UNTUK GENERATE LAPORAN ---
function generate_report($pdo, $output, $report_key, $report_details) {
    // Menggunakan judul dari variabel $report_details yang diambil dari config
    fputcsv($output, [$report_details['title']]);

    switch ($report_key) {
        case 'wearpack':
        case 'kaos':
            $header = ['Model', 'Warna', 'Stok M', 'Stok L', 'Stok XL', 'Stok XXL', 'Total'];
            fputcsv($output, $header);
            $sql = "SELECT model, warna, SUM(CASE WHEN UPPER(ukuran) = 'M' THEN stok_akhir ELSE 0 END), SUM(CASE WHEN UPPER(ukuran) = 'L' THEN stok_akhir ELSE 0 END), SUM(CASE WHEN UPPER(ukuran) = 'XL' THEN stok_akhir ELSE 0 END), SUM(CASE WHEN UPPER(ukuran) = 'XXL' THEN stok_akhir ELSE 0 END), SUM(stok_akhir) FROM `$report_key` GROUP BY model, warna ORDER BY model, warna";
            $stmt = $pdo->query($sql);
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) { fputcsv($output, $row); }
            break;
        case 'bordir':
            $header = ['Nama Bordir', 'Stok Awal', 'Stok Akhir'];
            fputcsv($output, $header);
            $sql = "SELECT nama_bordir, stok_awal, stok_akhir FROM bordir ORDER BY nama_bordir";
            $stmt = $pdo->query($sql);
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) { fputcsv($output, $row); }
            break;
        case 'material_kaos':
        case 'material_sablon':
        case 'material_bordir':
        case 'material_konveksi':
            $db_table = str_replace('-', '_', $report_key);
            $name_col = ($db_table === 'material_kaos') ? 'nama_material' : 'jenis_material';
            $header = [ucwords(str_replace('_', ' ', $name_col)), 'Warna', 'Stok Awal', 'Stok Akhir', 'Satuan'];
            fputcsv($output, $header);
            $sql = "SELECT {$name_col}, warna, stok_awal, stok_akhir, satuan FROM {$db_table} ORDER BY {$name_col}, warna";
            $stmt = $pdo->query($sql);
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) { fputcsv($output, $row); }
            break;
    }
}

// --- LOGIKA UTAMA ---
if ($tampil === 'semua') {
    // Jika 'tampil=semua', cetak semua laporan secara berurutan
    foreach ($semua_reports as $key => $details) {
        generate_report($pdo, $output, $key, $details);
        fput_separator($output);
    }
} else {
    // Jika tidak, cetak hanya laporan yang spesifik
    if (isset($semua_reports[$tampil])) {
        generate_report($pdo, $output, $tampil, $semua_reports[$tampil]);
    }
}

fclose($output);
exit;
?>