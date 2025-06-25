<?php
require_once 'config/koneksi.php';
require_once 'auth_check.php';

$report_key = $_GET['report'] ?? '';

// Fungsi untuk merender tabel (re-usable)
function render_table($pdo, $sql, $headers, $colspan) {
    $stmt = $pdo->query($sql);
    $output = '<table class="table table-bordered table-striped text-center mb-0"><thead class="table-dark"><tr>';
    foreach($headers as $h) {
        $output .= "<th>$h</th>";
    }
    $output .= '</tr></thead><tbody>';

    if ($stmt->rowCount() > 0) {
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $output .= '<tr>';
            foreach($row as $cell) {
                $output .= '<td>' . htmlspecialchars($cell) . '</td>';
            }
            $output .= '</tr>';
        }
    } else {
        $output .= '<tr><td colspan="' . $colspan . '" class="text-center p-4">Tidak ada data.</td></tr>';
    }
    $output .= '</tbody></table>';
    return $output;
}


switch ($report_key) {
    case 'wearpack':
    case 'kaos':
        $sql = "SELECT model, warna, SUM(CASE WHEN UPPER(ukuran) = 'M' THEN stok_akhir ELSE 0 END), SUM(CASE WHEN UPPER(ukuran) = 'L' THEN stok_akhir ELSE 0 END), SUM(CASE WHEN UPPER(ukuran) = 'XL' THEN stok_akhir ELSE 0 END), SUM(CASE WHEN UPPER(ukuran) = 'XXL' THEN stok_akhir ELSE 0 END), SUM(stok_akhir) FROM `$report_key` GROUP BY model, warna ORDER BY model, warna";
        echo render_table($pdo, $sql, ['Model', 'Warna', 'M', 'L', 'XL', 'XXL', 'Total'], 7);
        break;
    case 'bordir':
        $sql = "SELECT nama_bordir, stok_awal, stok_akhir FROM bordir ORDER BY nama_bordir";
        echo render_table($pdo, $sql, ['Nama Bordir', 'Stok Awal', 'Stok Akhir'], 3);
        break;
    case 'material_kaos':
    case 'material_sablon':
    case 'material_bordir':
    case 'material_konveksi':
        $db_table = str_replace('-', '_', $report_key);
        $name_col = ($db_table === 'material_kaos') ? 'nama_material' : 'jenis_material';
        $sql = "SELECT `$name_col` AS nama, warna, stok_awal, stok_akhir, satuan FROM `$db_table` ORDER BY nama, warna";
        echo render_table($pdo, $sql, ['Nama/Jenis', 'Warna', 'Stok Awal', 'Stok Akhir', 'Satuan'], 5);
        break;
    default:
        echo '<p class="text-danger">Laporan tidak valid.</p>';
        break;
}