<?php 
include 'templates/header.php'; 
require_once 'config/report_config.php'; 
?>

<style>
    /* CSS Kustom untuk Tampilan Elegan & Dinamis (Versi Final) */
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
    
    /* Tombol Pengubah Tampilan (Grid/Daftar) */
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

    /* Kartu Laporan dengan Desain Soft UI */
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
        <h1 class="page-title mb-0">Ringkasan Stok Interaktif</h1>
    </div>
    <div class="d-flex align-items-center gap-3">
        <div class="view-toggle btn-group btn-group-sm" role="group">
            <button type="button" class="btn active" title="Tampilan Grid" data-view="grid"><i class="bi bi-grid-fill"></i></button>
            <button type="button" class="btn" title="Tampilan Daftar" data-view="list"><i class="bi bi-list-task"></i></button>
        </div>
        <a href="cetak_laporan_ukuran.php?tampil=semua" class="btn btn-primary">Export Semua</a>
    </div>
</div>


<div id="report-grid-container" class="row grid-view mt-4">
    <?php foreach ($reports['produk'] as $key => $details): ?>
        <div class="report-item col-lg-12 mb-4">
            <div class="report-card">
                <div class="report-card-header">
                    <h5 class="report-card-title"><?= htmlspecialchars($details['title']) ?></h5>
                    <a href="cetak_laporan_ukuran.php?tampil=<?= $key ?>" class="btn btn-sm btn-outline-secondary">Export</a>
                </div>
                <div class="table-responsive">
                     <?php if ($key === 'wearpack' || $key === 'kaos'): ?>
                        <table class="table table-hover text-center mb-0">
                            <thead><tr><th class="text-start">Model</th><th class="text-start">Warna</th><th>M</th><th>L</th><th>XL</th><th>XXL</th><th>Total</th></tr></thead>
                            <tbody>
                                <?php
                                $stmt_produk = $pdo->query("SELECT model, warna, SUM(CASE WHEN UPPER(ukuran) = 'M' THEN stok_akhir ELSE 0 END), SUM(CASE WHEN UPPER(ukuran) = 'L' THEN stok_akhir ELSE 0 END), SUM(CASE WHEN UPPER(ukuran) = 'XL' THEN stok_akhir ELSE 0 END), SUM(CASE WHEN UPPER(ukuran) = 'XXL' THEN stok_akhir ELSE 0 END), SUM(stok_akhir) FROM `$key` GROUP BY model, warna ORDER BY model, warna");
                                if ($stmt_produk->rowCount() > 0): while($row = $stmt_produk->fetch(PDO::FETCH_NUM)):
                                ?>
                                <tr><td class="text-start"><?= htmlspecialchars($row[0]) ?></td><td class="text-start"><?= htmlspecialchars($row[1]) ?></td><td><?= (int)$row[2] ?></td><td><?= (int)$row[3] ?></td><td><?= (int)$row[4] ?></td><td><?= (int)$row[5] ?></td><td><strong><?= (int)$row[6] ?></strong></td></tr>
                                <?php endwhile; else: ?>
                                <tr><td colspan="7" class="text-center p-4 text-muted">Tidak ada data.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <table class="table table-hover mb-0">
                            <thead><tr><th class="text-start">Nama Bordir</th><th class="text-center">Stok Awal</th><th class="text-center">Stok Akhir</th></tr></thead>
                            <tbody>
                                <?php
                                $stmt_bordir = $pdo->query("SELECT nama_bordir, stok_awal, stok_akhir FROM bordir ORDER BY nama_bordir");
                                if ($stmt_bordir->rowCount() > 0): while($row_bordir = $stmt_bordir->fetch(PDO::FETCH_ASSOC)):
                                ?>
                                <tr><td class="text-start"><?= htmlspecialchars($row_bordir['nama_bordir']) ?></td><td class="text-center"><?= (int)$row_bordir['stok_awal'] ?></td><td class="text-center"><strong><?= (int)$row_bordir['stok_akhir'] ?></strong></td></tr>
                                <?php endwhile; else: ?>
                                <tr><td colspan="3" class="text-center p-4 text-muted">Tidak ada data.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <?php foreach ($reports['material'] as $key => $details): ?>
        <div class="report-item col-lg-6 mb-4">
             <div class="report-card">
                <div class="report-card-header">
                    <h5 class="report-card-title"><?= htmlspecialchars($details['title']) ?></h5>
                    <a href="cetak_laporan_ukuran.php?tampil=<?= $key ?>" class="btn btn-sm btn-outline-secondary">Export</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th class="text-start">Nama/Jenis</th><th class="text-start">Warna</th><th class="text-center">Stok Awal</th><th class="text-center">Stok Akhir</th><th class="text-center">Satuan</th></tr></thead>
                        <tbody>
                            <?php
                            $db_table = str_replace('-', '_', $key);
                            $name_col = ($db_table === 'material_kaos') ? 'nama_material' : 'jenis_material';
                            $stmt_material = $pdo->query("SELECT `$name_col` AS nama, warna, stok_awal, stok_akhir, satuan FROM `$db_table` ORDER BY nama, warna");
                            if ($stmt_material->rowCount() > 0): while($row_material = $stmt_material->fetch(PDO::FETCH_ASSOC)):
                            ?>
                            <tr><td class="text-start"><?= htmlspecialchars($row_material['nama']) ?></td><td class="text-start"><?= htmlspecialchars($row_material['warna']) ?></td><td class="text-center"><?= (float)$row_material['stok_awal'] ?></td><td class="text-center"><strong><?= (float)$row_material['stok_akhir'] ?></strong></td><td class="text-center"><?= htmlspecialchars($row_material['satuan']) ?></td></tr>
                            <?php endwhile; else: ?>
                            <tr><td colspan="5" class="text-center p-4 text-muted">Tidak ada data.</td></tr>
                            <?php endif; ?>
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
    
    // 1. Animasi saat scroll (Tetap dipertahankan)
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
    
    // 2. Logika untuk Toggle Tampilan Grid/List (Tetap dipertahankan)
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
                    item.classList.add(item.querySelectorAll('th').length > 5 ? 'col-lg-12' : 'col-lg-6');
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

    // Pemicu awal untuk memastikan kelas kolom yang benar diterapkan saat halaman dimuat
    document.querySelector('.view-toggle .btn.active').click();
});
</script>

<?php 
include 'templates/footer.php';
?>