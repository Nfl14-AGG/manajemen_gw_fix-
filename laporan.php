<?php 
include 'templates/header.php';

// --- DEFINISI FILTER ---
$valid_filters = [
    'semua' => 'Keseluruhan',
    'wearpack' => 'Wearpack', 
    'kaos' => 'Kaos', 
    'bordir' => 'Bordir', 
    'material_kaos' => 'Material Kaos', 
    'material_sablon' => 'Material Sablon', 
    'material_bordir' => 'Material Bordir', 
    'material_konveksi' => 'Material Konveksi'
];
$valid_sizes = ['M', 'L', 'XL', 'XXL'];

// --- MENGAMBIL NILAI FILTER DARI URL ---
$filter = $_GET['filter'] ?? 'semua';
$size_filter = $_GET['size_filter'] ?? 'semua';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// --- MEMBANGUN KLAUSA WHERE SQL BERDASARKAN FILTER ---
$where_clause = 'WHERE 1=1';
$params = [];
$export_link_params = [];

// Filter berdasarkan Tipe Item
if (array_key_exists($filter, $valid_filters) && $filter !== 'semua') {
    $where_clause .= " AND l.tipe_item = ?";
    $params[] = $filter;
    $export_link_params[] = "filter=$filter";
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
    $export_link_params[] = "size_filter=$size_filter";
}
// --- AKHIR BLOK YANG DIPERBAIKI ---
// =================================================================


// Filter berdasarkan Tanggal
if (!empty($start_date)) {
    $where_clause .= " AND l.timestamp_laporan >= ?";
    $params[] = $start_date;
    $export_link_params[] = "start_date=$start_date";
}
if (!empty($end_date)) {
    // Tambahkan waktu akhir hari untuk memastikan data di tanggal akhir ikut terambil
    $where_clause .= " AND l.timestamp_laporan <= ?";
    $params[] = $end_date . ' 23:59:59';
    $export_link_params[] = "end_date=$end_date";
}

// Membuat judul halaman dinamis
$judul_halaman = 'Laporan Transaksi ' . ($valid_filters[$filter] ?? 'Keseluruhan');
if ($size_filter !== 'semua' && ($filter === 'wearpack' || $filter === 'kaos')) {
    $judul_halaman .= " Ukuran $size_filter";
}
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
    <h1 id="judul-laporan"><?= $judul_halaman ?></h1>
    <a href="cetak_laporan.php?<?= implode('&', $export_link_params) ?>" class="btn btn-success">
        <i class="bi bi-file-earmark-excel-fill me-2"></i>Export ke Excel
    </a>
</div>

<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-filter-circle-fill me-2"></i>Filter Laporan
    </div>
    <div class="card-body">
        <form action="laporan.php" method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="filter" class="form-label">Tipe Item</label>
                <select id="filter" name="filter" class="form-select">
                    <?php foreach ($valid_filters as $key => $value): ?>
                        <option value="<?= $key ?>" <?= ($filter === $key) ? 'selected' : '' ?>><?= $value ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2" id="size-filter-container" style="display:none;">
                <label for="size_filter" class="form-label">Ukuran</label>
                <select id="size_filter" name="size_filter" class="form-select">
                    <option value="semua" <?= ($size_filter === 'semua') ? 'selected' : '' ?>>Semua Ukuran</option>
                    <?php foreach ($valid_sizes as $size): ?>
                        <option value="<?= $size ?>" <?= ($size_filter === $size) ? 'selected' : '' ?>><?= $size ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="start_date" class="form-label">Dari Tanggal</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
            </div>

            <div class="col-md-3">
                <label for="end_date" class="form-label">Sampai Tanggal</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
            </div>

            <div class="col-md-1">
                <button type="submit" class="btn btn-primary w-100">Cari</button>
            </div>
        </form>
    </div>
</div>


<div class="card">
    <div class="card-header">
        <i class="bi bi-list-alt me-2"></i>Hasil Laporan
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Waktu</th>
                        <th>Tipe Item</th>
                        <th>Nama Item</th>
                        <th>Warna</th>
                        <th>Ukuran</th>
                        <th>Jenis</th>
                        <th>Jumlah</th>
                        <th>Satuan</th>
                        <th>Stok Sebelum</th>
                        <th>Stok Setelah</th>
                        <th>Keterangan</th>
                        <th>User</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "
                        SELECT 
                            l.*, 
                            u.username,
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
                            COALESCE(mk.satuan, ms.satuan, mb.satuan, mc.satuan, 'Pcs') AS satuan_item
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

                    if ($stmt->rowCount() > 0):
                        while ($row = $stmt->fetch()):
                            $badge_class = ($row['jenis_transaksi'] == 'masuk') ? 'bg-success' : 'bg-danger';
                    ?>
                    <tr>
                        <td><?= date('d M Y, H:i', strtotime($row['timestamp_laporan'])) ?></td>
                        <td><?= ucwords(str_replace('_', ' ', $row['tipe_item'])) ?></td>
                        <td><?= htmlspecialchars($row['nama_item'] ?? 'Item Telah Dihapus') ?></td>
                        <td><?= htmlspecialchars($row['warna_item']) ?></td>
                        <td><?= htmlspecialchars($row['ukuran_item']) ?></td>
                        <td><span class="badge <?= $badge_class ?>"><?= ucwords($row['jenis_transaksi']) ?></span></td>
                        <td><strong class="text-nowrap"><?= ($row['jenis_transaksi'] == 'keluar' ? '<span class="text-danger">- ' : '<span class="text-success">+ ') . (float)$row['jumlah'] ?></span></strong></td>
                        <td><?= htmlspecialchars($row['satuan_item']) ?></td>
                        <td><?= (float)$row['stok_sebelum'] ?></td>
                        <td><?= (float)$row['stok_sesudah'] ?></td>
                        <td style="min-width: 200px;"><?= htmlspecialchars($row['keterangan']) ?></td>
                        <td><?= htmlspecialchars($row['username']) ?></td>
                    </tr>
                    <?php 
                        endwhile;
                    else:
                    ?>
                        <tr>
                            <td colspan="12" class="text-center">Tidak ada data laporan untuk filter yang dipilih.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeFilter = document.getElementById('filter');
    const sizeFilterContainer = document.getElementById('size-filter-container');

    function toggleSizeFilter() {
        const selectedType = typeFilter.value;
        if (selectedType === 'wearpack' || selectedType === 'kaos') {
            sizeFilterContainer.style.display = 'block';
        } else {
            sizeFilterContainer.style.display = 'none';
        }
    }

    // Panggil fungsi saat halaman dimuat untuk memeriksa kondisi awal
    toggleSizeFilter();

    // Tambahkan event listener untuk memantau perubahan
    typeFilter.addEventListener('change', toggleSizeFilter);
});
</script>


<?php 
include 'templates/footer.php';
?>