<?php 
include 'templates/header.php'; 
require_once 'config/report_config.php'; // Menggunakan config yang sama
?>

<style>
    /* Mengadopsi CSS yang sama persis dari halaman Laporan Ukuran */
    :root {
        --page-bg: #eef2f7;
        --card-bg: #eef2f7;
        --text-color-dark: #3b4d61;
        --text-color-light: #8a9cb1;
        --accent-color: var(--gw-primary, #8B4513);
        --shadow-light: #d1d9e6;
        --shadow-dark: #ffffff;
    }

    body {
        background-color: var(--page-bg);
    }
    
    .page-title {
        font-family: 'Poppins', sans-serif;
        font-weight: 700;
        color: var(--text-color-dark);
    }
    
    .view-toggle .btn {
        background: var(--card-bg);
        box-shadow: 5px 5px 10px var(--shadow-light), -5px -5px 10px var(--shadow-dark);
        border: none;
        color: var(--text-color-light);
        padding: 0.5rem 1rem;
        transition: all 0.3s ease;
    }
     .view-toggle .btn.active {
        box-shadow: inset 2px 2px 4px var(--shadow-light), inset -2px -2px 4px var(--shadow-dark);
        color: var(--accent-color);
    }

    .report-grid-container.grid-view .report-item {
        display: flex;
    }

    .report-item {
        transition: all 0.5s ease-in-out;
        opacity: 0;
        transform: translateY(30px);
    }
    .report-item.is-visible {
        opacity: 1;
        transform: translateY(0);
    }

    .report-card {
        width: 100%;
        background: var(--card-bg);
        border-radius: 15px;
        box-shadow: 8px 8px 16px var(--shadow-light), -8px -8px 16px var(--shadow-dark);
        padding: 1.5rem;
    }

    .report-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }
    
    .report-card-title {
        font-family: 'Poppins', sans-serif;
        font-weight: 600;
        font-size: 1.2rem;
        color: var(--text-color-dark);
    }

    .table thead th {
        background-color: transparent;
        border-bottom: 2px solid #d1d9e6;
        color: var(--text-color-light);
        font-weight: 600;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h1 class="page-title mb-0">Rekapitulasi Stok Interaktif</h1>
    </div>
    <div class="d-flex align-items-center gap-3">
        <div class="view-toggle btn-group btn-group-sm" role="group">
            <button type="button" class="btn active" title="Tampilan Grid" data-view="grid"><i class="bi bi-grid-fill"></i></button>
            <button type="button" class="btn" title="Tampilan Daftar" data-view="list"><i class="bi bi-list-task"></i></button>
        </div>
        <a href="cetak_laporan_rekap.php?tampil=semua" class="btn btn-primary">Export Semua Rekapan</a>
    </div>
</div>

<div id="report-grid-container" class="row grid-view mt-4">
    <?php foreach ($reports['produk'] as $key => $details): ?>
        <div class="report-item col-lg-12 mb-4" data-report-type="produk">
            <div class="report-card">
                <div class="report-card-header">
                    <h5 class="report-card-title"><?= htmlspecialchars($details['title']) ?></h5>
                    <a href="cetak_laporan_rekap.php?tampil=<?= $key ?>" class="btn btn-sm btn-outline-secondary">Export</a>
                </div>
                <div class="table-responsive">
                    <?php if ($key === 'wearpack' || $key === 'kaos'): ?>
                        <table class="table table-hover text-center mb-0">
                            <thead><tr><th class="text-start">Model</th><th class="text-start">Warna</th><th>M</th><th>L</th><th>XL</th><th>XXL</th><th>Total</th><th>Aksi</th></tr></thead>
                            <tbody>
                                <?php 
                                $stmt_produk = $pdo->query("SELECT model, warna, SUM(CASE WHEN UPPER(ukuran) = 'M' THEN stok_akhir ELSE 0 END) AS stok_M, SUM(CASE WHEN UPPER(ukuran) = 'L' THEN stok_akhir ELSE 0 END) AS stok_L, SUM(CASE WHEN UPPER(ukuran) = 'XL' THEN stok_akhir ELSE 0 END) AS stok_XL, SUM(CASE WHEN UPPER(ukuran) = 'XXL' THEN stok_akhir ELSE 0 END) AS stok_XXL, SUM(stok_akhir) AS total_keseluruhan FROM `$key` GROUP BY model, warna ORDER BY model, warna"); 
                                if ($stmt_produk->rowCount() > 0): while ($row = $stmt_produk->fetch(PDO::FETCH_ASSOC)): 
                                ?>
                                <tr>
                                    <td class="text-start"><?= htmlspecialchars($row['model']) ?></td>
                                    <td class="text-start"><?= htmlspecialchars($row['warna']) ?></td>
                                    <td><?= (int)$row['stok_M'] ?></td>
                                    <td><?= (int)$row['stok_L'] ?></td>
                                    <td><?= (int)$row['stok_XL'] ?></td>
                                    <td><?= (int)$row['stok_XXL'] ?></td>
                                    <td><strong><?= (int)$row['total_keseluruhan'] ?></strong></td>
                                    <td><a href="<?= $key ?>.php?search=<?= urlencode($row['model'].' '.$row['warna']) ?>" class="btn btn-info btn-sm">Kelola</a></td>
                                </tr>
                                <?php endwhile; else: ?>
                                <tr><td colspan="8" class="text-center p-4 text-muted">Tidak ada data rekap.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    <?php else: // bordir ?>
                        <table class="table table-hover text-center mb-0">
                            <thead><tr><th class="text-start">Nama Bordir</th><th>Total Stok</th><th>Aksi</th></tr></thead>
                            <tbody>
                                <?php 
                                $stmt_bordir = $pdo->query("SELECT id_bordir, nama_bordir, stok_akhir FROM bordir ORDER BY nama_bordir"); 
                                if ($stmt_bordir->rowCount() > 0): while ($row = $stmt_bordir->fetch(PDO::FETCH_ASSOC)): 
                                ?>
                                <tr>
                                    <td class="text-start"><?= htmlspecialchars($row['nama_bordir']) ?></td>
                                    <td><strong><?= (int)$row['stok_akhir'] ?></strong></td>
                                    <td><a href="bordir.php?search=<?= urlencode($row['nama_bordir']) ?>" class="btn btn-info btn-sm">Kelola</a></td>
                                </tr>
                                <?php endwhile; else: ?>
                                <tr><td colspan="3" class="text-center p-4 text-muted">Tidak ada data rekap.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <?php 
    $material_types_rekap = [
        'material_kaos'     => ['page' => 'material_kaos.php', 'name_col' => 'nama_material'],
        'material_sablon'   => ['page' => 'material_sablon.php', 'name_col' => 'jenis_material'],
        'material_bordir'   => ['page' => 'material_bordir.php', 'name_col' => 'jenis_material'],
        'material_konveksi' => ['page' => 'material_konveksi.php', 'name_col' => 'jenis_material'],
    ];
    foreach ($material_types_rekap as $key => $material): ?>
        <div class="report-item col-lg-6 mb-4" data-report-type="material">
             <div class="report-card">
                <div class="report-card-header">
                    <h5 class="report-card-title"><?= htmlspecialchars($reports['material'][$key]['title']) ?></h5>
                    <a href="cetak_laporan_rekap.php?tampil=<?= $key ?>" class="btn btn-sm btn-outline-secondary">Export</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Nama/Jenis</th><th>Warna</th><th class="text-center">Total Stok</th><th class="text-center">Satuan</th><th class="text-center">Aksi</th></tr></thead>
                        <tbody>
                            <?php 
                            $stmt_mat = $pdo->query("SELECT {$material['name_col']} AS nama, warna, stok_akhir, satuan FROM {$key} ORDER BY nama, warna");
                            if ($stmt_mat->rowCount() > 0): while ($row = $stmt_mat->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['nama']) ?></td>
                                <td><?= htmlspecialchars($row['warna']) ?></td>
                                <td class="text-center"><strong><?= htmlspecialchars(is_numeric($row['stok_akhir']) ? number_format((float)$row['stok_akhir'], 0, ',', '.') : $row['stok_akhir']) ?></strong></td>
                                <td class="text-center"><?= htmlspecialchars($row['satuan']) ?></td>
                                <td class="text-center"><a href="<?= $material['page'] ?>?search=<?= urlencode($row['nama'].' '.$row['warna']) ?>" class="btn btn-info btn-sm">Kelola</a></td>
                            </tr>
                            <?php endwhile; else: ?><tr><td colspan="5" class="text-center p-4 text-muted">Tidak ada data.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('report-grid-container');
    const reportItems = document.querySelectorAll('.report-item');
    
    // Animasi saat scroll
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
            }
        });
    }, {
        threshold: 0.1
    });

    reportItems.forEach(item => {
        observer.observe(item);
    });
    
    // Logika untuk Toggle Tampilan Grid/List
    const viewButtons = document.querySelectorAll('.view-toggle .btn');
    viewButtons.forEach(button => {
        button.addEventListener('click', () => {
            const viewType = button.dataset.view;
            
            viewButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            
            if (viewType === 'grid') {
                container.classList.add('grid-view');
                container.classList.remove('list-view');
                reportItems.forEach(item => {
                    item.classList.remove('col-lg-6', 'col-md-12');
                    item.classList.add(item.dataset.reportType === 'produk' ? 'col-lg-12' : 'col-lg-6');
                });
            } else { // list view
                container.classList.add('list-view');
                container.classList.remove('grid-view');
                reportItems.forEach(item => {
                    item.classList.remove('col-lg-6', 'col-lg-12');
                    item.classList.add('col-md-12');
                });
            }
        });
    });

    // Pemicu awal untuk memastikan kelas kolom yang benar diterapkan
    document.querySelector('.view-toggle .btn.active').click();
});
</script>

<?php 
include 'templates/footer.php';
?>