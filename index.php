<?php
// Memanggil auth check dan template header
require_once 'auth_check.php';
require_once 'templates/header.php';

// =================================================================
// PENGAMBILAN DATA UNTUK KARTU RINGKASAN
// =================================================================
// Menggunakan try-catch untuk penanganan error yang lebih baik
try {
    // PERUBAHAN: Menggunakan SUM(stok_akhir)
    $stmt_wearpack_total = $pdo->query("SELECT SUM(stok_akhir) as total FROM wearpack");
    $total_wearpack = $stmt_wearpack_total->fetchColumn() ?? 0;

    // PERUBAHAN: Menggunakan SUM(stok_akhir)
    $stmt_kaos_total = $pdo->query("SELECT SUM(stok_akhir) as total FROM kaos");
    $total_kaos = $stmt_kaos_total->fetchColumn() ?? 0;

    $stmt_produk = $pdo->query("
        SELECT
            (SELECT COUNT(DISTINCT model, warna) FROM wearpack) +
            (SELECT COUNT(DISTINCT model, warna) FROM kaos) +
            (SELECT COUNT(*) FROM bordir) as total
    ");
    $total_produk_unik = $stmt_produk->fetchColumn() ?? 0;

    $stmt_material = $pdo->query("
        SELECT
            (SELECT COUNT(*) FROM material_kaos) +
            (SELECT COUNT(*) FROM material_sablon) +
            (SELECT COUNT(*) FROM material_bordir) +
            (SELECT COUNT(*) FROM material_konveksi) as total
    ");
    $total_material_unik = $stmt_material->fetchColumn() ?? 0;

    // =================================================================
    // PENGAMBILAN DATA BARU UNTUK GRAFIK DAN MODUL LAINNYA
    // =================================================================

    // 1. Data untuk Grafik Komposisi Stok
    // PERUBAHAN: Menggunakan SUM(stok_akhir)
    $stmt_komposisi = $pdo->query("
        SELECT 'Wearpack' as kategori, COALESCE(SUM(stok_akhir), 0) as total FROM wearpack
        UNION ALL
        SELECT 'Kaos' as kategori, COALESCE(SUM(stok_akhir), 0) as total FROM kaos
        UNION ALL
        SELECT 'Bordir' as kategori, COALESCE(SUM(stok_akhir), 0) as total FROM bordir
    ");
    $data_komposisi = $stmt_komposisi->fetchAll(PDO::FETCH_ASSOC);
    $labels_komposisi = [];
    $values_komposisi = [];
    foreach ($data_komposisi as $data) {
        $labels_komposisi[] = $data['kategori'];
        $values_komposisi[] = (int)$data['total'];
    }

    // 2. Data untuk Grafik Aktivitas Transaksi 7 Hari Terakhir (Tidak ada perubahan, sudah benar)
    $stmt_aktivitas = $pdo->prepare("
        SELECT 
            DATE(timestamp_laporan) as tanggal,
            jenis_transaksi,
            COUNT(*) as jumlah
        FROM laporan
        WHERE timestamp_laporan >= CURDATE() - INTERVAL 6 DAY AND timestamp_laporan < CURDATE() + INTERVAL 1 DAY
        GROUP BY tanggal, jenis_transaksi
        ORDER BY tanggal
    ");
    $stmt_aktivitas->execute();
    $data_aktivitas = $stmt_aktivitas->fetchAll(PDO::FETCH_ASSOC);

    $labels_aktivitas = [];
    $values_masuk = [];
    $values_keluar = [];
    $temp_data = [];

    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $labels_aktivitas[] = date('d M', strtotime($date));
        $temp_data[$date] = ['masuk' => 0, 'keluar' => 0];
    }

    foreach ($data_aktivitas as $data) {
        if (isset($temp_data[$data['tanggal']])) {
            if (in_array($data['jenis_transaksi'], ['masuk', 'keluar'])) {
                $temp_data[$data['tanggal']][$data['jenis_transaksi']] = (int)$data['jumlah'];
            }
        }
    }

    foreach ($temp_data as $data) {
        $values_masuk[] = $data['masuk'];
        $values_keluar[] = $data['keluar'];
    }

    // 3. Data untuk Stok Kritis (stok <= 10)
    // PERUBAHAN: Menggunakan stok_akhir untuk pengecekan
    $critical_stock_limit = 10;
    $stmt_stok_kritis = $pdo->prepare("
        (SELECT id_wearpack as id, CONCAT(model, ' - ', warna, ' (', ukuran, ')') as nama_item, 'wearpack' as tipe, stok_akhir FROM wearpack WHERE stok_akhir <= :limit1)
        UNION ALL
        (SELECT id_kaos as id, CONCAT(model, ' - ', warna, ' (', ukuran, ')') as nama_item, 'kaos' as tipe, stok_akhir FROM kaos WHERE stok_akhir <= :limit2)
        UNION ALL
        (SELECT id_bordir as id, nama_bordir as nama_item, 'bordir' as tipe, stok_akhir FROM bordir WHERE stok_akhir <= :limit3)
        ORDER BY stok_akhir ASC
        LIMIT 5
    ");
    $stmt_stok_kritis->bindValue(':limit1', $critical_stock_limit, PDO::PARAM_INT);
    $stmt_stok_kritis->bindValue(':limit2', $critical_stock_limit, PDO::PARAM_INT);
    $stmt_stok_kritis->bindValue(':limit3', $critical_stock_limit, PDO::PARAM_INT);
    $stmt_stok_kritis->execute();
    $data_stok_kritis = $stmt_stok_kritis->fetchAll(PDO::FETCH_ASSOC);

    // 4. Data untuk 5 Transaksi Terakhir (Tidak ada perubahan, sudah benar)
    $sql_laporan_terakhir = "
        SELECT
            l.jenis_transaksi, l.jumlah, l.tipe_item,
            CASE
                WHEN l.tipe_item = 'wearpack' THEN CONCAT(w.model, ' (', w.ukuran, ')')
                WHEN l.tipe_item = 'kaos' THEN CONCAT(k.model, ' (', k.ukuran, ')')
                WHEN l.tipe_item = 'bordir' THEN bd.nama_bordir
                WHEN l.tipe_item = 'material_kaos' THEN mk.nama_material
                ELSE 'Item Lain'
            END AS nama_item_ringkas
        FROM laporan l
        LEFT JOIN wearpack w ON l.id_item = w.id_wearpack AND l.tipe_item = 'wearpack'
        LEFT JOIN kaos k ON l.id_item = k.id_kaos AND l.tipe_item = 'kaos'
        LEFT JOIN bordir bd ON l.id_item = bd.id_bordir AND l.tipe_item = 'bordir'
        LEFT JOIN material_kaos mk ON l.id_item = mk.id_material AND l.tipe_item = 'material_kaos'
        ORDER BY l.timestamp_laporan DESC
        LIMIT 5
    ";
    $stmt_laporan_terakhir = $pdo->query($sql_laporan_terakhir);

} catch (PDOException $e) {
    // Tampilkan pesan error jika query gagal
    die("Error: Tidak dapat mengambil data dashboard. " . $e->getMessage());
}
?>

<div class="container-fluid pt-4 px-4">
    <h1 class="mb-4 fw-bold">Dashboard</h1>

    <div class="row">
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card shadow-sm border-start border-primary border-4 h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-primary text-uppercase">Stok Wearpack</h6>
                        <p class="card-text fs-2 fw-bold mb-0"><?= number_format($total_wearpack) ?></p>
                    </div>
                    <i class="bi bi-person-workspace fs-1 text-black-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card shadow-sm border-start border-info border-4 h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-info text-uppercase">Stok Kaos</h6>
                        <p class="card-text fs-2 fw-bold mb-0"><?= number_format($total_kaos) ?></p>
                    </div>
                    <i class="bi bi-t-shirt fs-1 text-black-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card shadow-sm border-start border-success border-4 h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-success text-uppercase">Jenis Produk</h6>
                        <p class="card-text fs-2 fw-bold mb-0"><?= number_format($total_produk_unik) ?></p>
                    </div>
                    <i class="bi bi-box-seam fs-1 text-black-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card shadow-sm border-start border-secondary border-4 h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-secondary text-uppercase">Jenis Material</h6>
                        <p class="card-text fs-2 fw-bold mb-0"><?= number_format($total_material_unik) ?></p>
                    </div>
                    <i class="bi bi-stack fs-1 text-black-50"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 fw-bold text-primary"><i class="bi bi-bar-chart-line-fill me-2"></i>Aktivitas Transaksi 7 Hari Terakhir</h6>
                </div>
                <div class="card-body">
                    <div style="height: 320px;">
                        <canvas id="aktivitasChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header py-3">
                    <h6 class="m-0 fw-bold text-primary"><i class="bi bi-pie-chart-fill me-2"></i>Komposisi Stok Produk</h6>
                </div>
                <div class="card-body d-flex justify-content-center align-items-center">
                    <div style="height: 320px; max-width: 320px; width: 100%;">
                        <canvas id="komposisiStokChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-warning py-3">
                    <h6 class="m-0 fw-bold text-dark"><i class="bi bi-exclamation-triangle-fill me-2"></i>Perhatian: Stok Kritis</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted small">Menampilkan item dengan stok terendah (<= <?= $critical_stock_limit ?>).</p>
                    <?php if (empty($data_stok_kritis)): ?>
                        <div class="alert alert-success mb-0">Kerja bagus! Tidak ada produk dengan stok kritis.</div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach($data_stok_kritis as $item): ?>
                            <a href="<?= htmlspecialchars($item['tipe']) ?>.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-0">
                                <div>
                                    <div class="fw-bold"><?= htmlspecialchars($item['nama_item']) ?></div>
                                    <small class="text-muted text-uppercase"><?= htmlspecialchars($item['tipe']) ?></small>
                                </div>
                                <span class="badge bg-danger rounded-pill fs-6"><?= $item['stok_akhir'] ?></span>
                            </a>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header py-3">
                    <h6 class="m-0 fw-bold text-primary"><i class="bi bi-clock-history me-2"></i>5 Transaksi Terakhir</h6>
                </div>
                <div class="card-body">
                    <?php if ($stmt_laporan_terakhir->rowCount() > 0): ?>
                        <?php while ($row = $stmt_laporan_terakhir->fetch(PDO::FETCH_ASSOC)):
                            $is_masuk = $row['jenis_transaksi'] == 'masuk';
                            $icon = $is_masuk ? 'bi-arrow-down-circle text-success' : 'bi-arrow-up-circle text-danger';
                            $jumlah_text = ($is_masuk ? '+' : '-') . number_format($row['jumlah']);
                        ?>
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi <?= $icon ?> fs-2 me-3"></i>
                            <div class="flex-grow-1">
                                <div class="fw-bold"><?= htmlspecialchars($row['nama_item_ringkas'] ?? 'Item Dihapus') ?></div>
                                <small class="text-muted text-uppercase"><?= htmlspecialchars($row['tipe_item']) ?></small>
                            </div>
                            <div class="fw-bold fs-5"><?= $jumlah_text ?></div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-center text-muted mb-0">Belum ada transaksi.</p>
                    <?php endif; ?>
                    <div class="text-center mt-3">
                        <a href="laporan.php" class="btn btn-outline-primary btn-sm">Lihat Semua Laporan <i class="bi bi-arrow-right-short"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Menunggu seluruh konten halaman dimuat sebelum menjalankan script
document.addEventListener('DOMContentLoaded', function() {
    // Helper untuk mengambil warna dari variabel CSS (jika ada)
    const getCssVar = (name) => getComputedStyle(document.documentElement).getPropertyValue(name).trim() || null;

    // Default warna jika variabel CSS tidak ditemukan
    const defaultColors = {
        primary: '#0d6efd',
        success: '#198754',
        danger: '#dc3545',
        info: '#0dcaf0',
        warning: '#ffc107',
        cardBg: '#ffffff'
    };
    
    // 1. Inisialisasi Grafik Komposisi Stok (Donut Chart)
    const komposisiCtx = document.getElementById('komposisiStokChart');
    if (komposisiCtx) {
        new Chart(komposisiCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($labels_komposisi) ?>,
                datasets: [{
                    label: 'Total Stok',
                    data: <?= json_encode($values_komposisi) ?>,
                    backgroundColor: [
                        getCssVar('--bs-primary') || defaultColors.primary,
                        getCssVar('--bs-info') || defaultColors.info,
                        getCssVar('--bs-warning') || defaultColors.warning,
                    ],
                    borderColor: getCssVar('--bs-body-bg') || defaultColors.cardBg,
                    borderWidth: 4,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            font: { family: "'Inter', sans-serif" }
                        }
                    }
                }
            }
        });
    }

    // 2. Inisialisasi Grafik Aktivitas (Bar Chart)
    const aktivitasCtx = document.getElementById('aktivitasChart');
    if (aktivitasCtx) {
        new Chart(aktivitasCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($labels_aktivitas) ?>,
                datasets: [
                    {
                        label: 'Barang Masuk',
                        data: <?= json_encode($values_masuk) ?>,
                        backgroundColor: 'rgba(25, 135, 84, 0.7)', // Success
                        borderColor: 'rgba(25, 135, 84, 1)',
                        borderWidth: 1,
                        borderRadius: 4
                    },
                    {
                        label: 'Barang Keluar',
                        data: <?= json_encode($values_keluar) ?>,
                        backgroundColor: 'rgba(220, 53, 69, 0.7)', // Danger
                        borderColor: 'rgba(220, 53, 69, 1)',
                        borderWidth: 1,
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0 
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
            }
        });
    }
});
</script>

<?php 
// Memanggil footer di akhir
require_once 'templates/footer.php'; 
?>