<?php
// File ini tidak menampilkan HTML, tapi menghasilkan file download CSV
require_once 'config/koneksi.php';
require_once 'auth_check.php';

// --- MENGAMBIL NILAI FILTER DARI URL ---
$filter = $_GET['filter'] ?? 'semua';
$size_filter = $_GET['size_filter'] ?? 'semua';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

$valid_filters = ['semua', 'wearpack', 'kaos', 'bordir', 'material_kaos', 'material_sablon', 'material_bordir', 'material_konveksi'];
$valid_sizes = ['M', 'L', 'XL', 'XXL'];

// Membuat nama file dinamis
$nama_file_suffix = $filter;
if ($size_filter !== 'semua') $nama_file_suffix .= '_ukuran_' . strtolower($size_filter);
if (!empty($start_date)) $nama_file_suffix .= '_dari_' . $start_date;
if (!empty($end_date)) $nama_file_suffix .= '_sampai_' . $end_date;
$filename = "laporan_transaksi_" . $nama_file_suffix . "_" . date('Ymd') . ".csv";

// Menyiapkan header HTTP untuk download file CSV
header('Content-Type: text/csv; charset=utf-8');
header("Content-Disposition: attachment; filename=\"$filename\"");

// --- MEMBANGUN KLAUSA WHERE SQL BERDASARKAN FILTER ---
$where_clause = 'WHERE 1=1';
$params = [];

// Filter Tipe Item
if (in_array($filter, $valid_filters) && $filter !== 'semua') {
    $where_clause .= " AND l.tipe_item = ?";
    $params[] = $filter;
}

// =================================================================
// --- BLOK YANG DIPERBAIKI ---
// Filter berdasarkan Ukuran kini hanya berlaku jika tipe item adalah wearpack atau kaos
if (in_array($size_filter, $valid_sizes) && ($filter === 'wearpack' || $filter === 'kaos')) {
    if ($filter === 'wearpack') {
        $where_clause .= " AND w.ukuran = ?";
    } elseif ($filter === 'kaos') {
        $where_clause .= " AND k.ukuran = ?";
    }
    // Baris ini dipindahkan ke dalam kondisi untuk memastikan parameter hanya ditambahkan jika '?' juga ditambahkan
    $params[] = $size_filter;
}
// --- AKHIR BLOK YANG DIPERBAIKI ---
// =================================================================


// Filter Tanggal
if (!empty($start_date)) {
    $where_clause .= " AND l.timestamp_laporan >= ?";
    $params[] = $start_date;
}
if (!empty($end_date)) {
    $where_clause .= " AND l.timestamp_laporan <= ?";
    $params[] = $end_date . ' 23:59:59';
}

// Query SQL yang sama dengan di halaman laporan.php
$sql = "
    SELECT 
        l.timestamp_laporan,
        l.tipe_item,
        CASE
            WHEN l.tipe_item = 'wearpack' THEN w.model
            WHEN l.tipe_item = 'kaos' THEN k.model
            WHEN l.tipe_item = 'bordir' THEN bd.nama_bordir
            WHEN l.tipe_item = 'material_kaos' THEN mk.nama_material
            WHEN l.tipe_item = 'material_sablon' THEN ms.jenis_material
            WHEN l.tipe_item = 'material_bordir' THEN mb.jenis_material
            WHEN l.tipe_item = 'material_konveksi' THEN mc.jenis_material
            ELSE 'N/A'
        END AS nama_item,
        COALESCE(w.warna, k.warna, mk.warna, ms.warna, mb.warna, mc.warna, '-') AS warna_item,
        COALESCE(w.ukuran, k.ukuran, '-') AS ukuran_item,
        COALESCE(mk.satuan, ms.satuan, mb.satuan, mc.satuan, 'Pcs') AS satuan_item,
        l.jenis_transaksi,
        l.jumlah,
        l.stok_sebelum,
        l.stok_sesudah,
        l.keterangan,
        u.username
    FROM laporan l
    LEFT JOIN users u ON l.id_user = u.id_user
    LEFT JOIN wearpack w ON l.id_item = w.id_wearpack AND l.tipe_item = 'wearpack'
    LEFT JOIN kaos k ON l.id_item = k.id_kaos AND l.tipe_item = 'kaos'
    LEFT JOIN bordir bd ON l.id_item = bd.id_bordir AND l.tipe_item = 'bordir'
    LEFT JOIN material_kaos mk ON l.id_item = mk.id_material AND l.tipe_item = 'material_kaos'
    LEFT JOIN material_sablon ms ON l.id_item = ms.id_sablon AND l.tipe_item = 'material_sablon'
    LEFT JOIN material_bordir mb ON l.id_item = mb.id_bordir AND l.tipe_item = 'material_bordir'
    LEFT JOIN material_konveksi mc ON l.id_item = mc.id AND l.tipe_item = 'material_konveksi'
    $where_clause
    ORDER BY l.timestamp_laporan DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$output = fopen('php://output', 'w');
// Header untuk file CSV
$header = ['Waktu', 'Tipe Item', 'Nama Item', 'Warna', 'Ukuran', 'Jenis Transaksi', 'Jumlah', 'Satuan', 'Stok Sebelum', 'Stok Setelah', 'Keterangan', 'User'];
fputcsv($output, $header);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $row_data = [
        date('d-m-Y H:i:s', strtotime($row['timestamp_laporan'])),
        ucwords(str_replace('_', ' ', $row['tipe_item'])),
        $row['nama_item'] ?? 'Item Dihapus',
        $row['warna_item'],
        $row['ukuran_item'],
        ucfirst($row['jenis_transaksi']),
        (float)$row['jumlah'],
        $row['satuan_item'],
        (float)$row['stok_sebelum'],
        (float)$row['stok_sesudah'],
        $row['keterangan'],
        $row['username']
    ];
    fputcsv($output, $row_data);
}

fclose($output);
exit;
?>