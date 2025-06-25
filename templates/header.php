<?php
// Memastikan koneksi dan sesi sudah dimulai
require_once __DIR__ . '/../config/koneksi.php';
// Memeriksa status login
require_once __DIR__ . '/../auth_check.php';

// --- LOGIKA UNTUK MENENTUKAN LINK NAVIGASI YANG AKTIF ---
$current_page = basename($_SERVER['PHP_SELF']);

// Definisikan halaman apa saja yang ada di dalam setiap grup menu
// PENAMBAHAN: 'laporan_ukuran.php' ditambahkan ke array agar menu Laporan tetap aktif.
$laporan_pages = ['laporan.php', 'laporan_rekap.php', 'laporan_ukuran.php'];
$produk_pages = ['wearpack.php', 'kaos.php', 'bordir.php'];
$material_pages = ['material_kaos.php', 'material_sablon.php', 'material_bordir.php', 'material_konveksi.php'];

// Cek apakah halaman saat ini ada di dalam salah satu grup
$is_laporan_active = in_array($current_page, $laporan_pages);
$is_produk_active = in_array($current_page, $produk_pages);
$is_material_active = in_array($current_page, $material_pages);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stok Gudang Wearpack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="config/assets/css/style.css">
    <style>
        :root {
            --gw-primary-light: rgba(139, 69, 19, 0.1);
        }
        /* Navbar atas yang lebih bersih */
        .navbar.fixed-top {
            background-color: #fff !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
        }
        /* Penyempurnaan Link Sidebar */
        .offcanvas .nav-link {
            padding: 0.8rem 1.5rem;
            font-weight: 500;
            color: #495057;
            border-left: 4px solid transparent;
            transition: all 0.2s ease-in-out;
        }
        .offcanvas .nav-link .bi {
            margin-right: 1rem;
            font-size: 1.1rem;
        }
        .offcanvas .nav-link:hover {
            background-color: var(--gw-primary-light);
            color: var(--gw-primary);
        }
        /* Style untuk link yang sedang aktif */
        .offcanvas .nav-link.active {
            background-color: var(--gw-primary-light);
            color: var(--gw-primary);
            border-left-color: var(--gw-primary);
            font-weight: 600;
        }
        /* Style untuk sub-menu */
        .collapse .nav-link {
            font-size: 0.9rem;
            padding-left: 3.5rem; /* Lebih menjorok ke dalam */
        }
        /* Animasi ikon panah sub-menu */
        .nav-link[data-bs-toggle="collapse"][aria-expanded="true"] .bi-chevron-down {
            transform: rotate(180deg);
        }
        .nav-link[data-bs-toggle="collapse"] .bi-chevron-down {
            transition: transform 0.2s ease-in-out;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-light fixed-top border-bottom">
  <div class="container-fluid">
    <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu">
        <span class="navbar-toggler-icon"></span>
    </button>
    <a class="navbar-brand mx-auto fw-bold" href="index.php">Gudang Wearpack</a>
    <div style="width: 56px;"></div> </div>
</nav>

<div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarMenu" aria-labelledby="sidebarMenuLabel">
  <div class="offcanvas-header border-bottom">
    <div>
        <h5 class="offcanvas-title" id="sidebarMenuLabel">Menu Utama</h5>
        <small class="text-muted">Selamat datang, <?= htmlspecialchars($_SESSION['username']) ?></small>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body p-0 d-flex flex-column">
    <ul class="nav flex-column flex-grow-1 pt-2">
        <li class="nav-item">
            <a class="nav-link <?= $current_page == 'index.php' ? 'active' : '' ?>" href="index.php">
                <i class="bi bi-grid-1x2-fill"></i>Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $is_laporan_active ? 'active' : '' ?>" data-bs-toggle="collapse" href="#laporanCollapse" role="button" aria-expanded="<?= $is_laporan_active ? 'true' : 'false' ?>">
                <i class="bi bi-file-earmark-bar-graph-fill"></i>Laporan <i class="bi bi-chevron-down float-end"></i>
            </a>
            <div class="collapse <?= $is_laporan_active ? 'show' : '' ?>" id="laporanCollapse">
                <ul class="nav flex-column">
                    <li class="nav-item"><a class="nav-link <?= $current_page == 'laporan.php' ? 'active' : '' ?>" href="laporan.php">Laporan Transaksi</a></li>
                    <li class="nav-item"><a class="nav-link <?= $current_page == 'laporan_rekap.php' ? 'active' : '' ?>" href="laporan_rekap.php">Laporan Rekap Stok</a></li>
                    <li class="nav-item"><a class="nav-link <?= $current_page == 'laporan_ukuran.php' ? 'active' : '' ?>" href="laporan_ukuran.php">Cetak Hasil Rekap</a></li>
                </ul>
            </div>
        </li>
        <hr class="mx-3 my-2">
        <li class="nav-item">
            <a class="nav-link <?= $is_produk_active ? 'active' : '' ?>" data-bs-toggle="collapse" href="#manajemenStokCollapse" role="button" aria-expanded="<?= $is_produk_active ? 'true' : 'false' ?>">
                <i class="bi bi-box-seam-fill"></i>Manajemen Produk <i class="bi bi-chevron-down float-end"></i>
            </a>
            <div class="collapse <?= $is_produk_active ? 'show' : '' ?>" id="manajemenStokCollapse">
                <ul class="nav flex-column">
                    <li class="nav-item"><a class="nav-link <?= $current_page == 'wearpack.php' ? 'active' : '' ?>" href="wearpack.php">Wearpack</a></li>
                    <li class="nav-item"><a class="nav-link <?= $current_page == 'kaos.php' ? 'active' : '' ?>" href="kaos.php">Kaos</a></li>
                    <li class="nav-item"><a class="nav-link <?= $current_page == 'bordir.php' ? 'active' : '' ?>" href="bordir.php">Bordir</a></li>
                </ul>
            </div>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $is_material_active ? 'active' : '' ?>" data-bs-toggle="collapse" href="#materialCollapse" role="button" aria-expanded="<?= $is_material_active ? 'true' : 'false' ?>">
                <i class="bi bi-stack"></i>Manajemen Material <i class="bi bi-chevron-down float-end"></i>
            </a>
            <div class="collapse <?= $is_material_active ? 'show' : '' ?>" id="materialCollapse">
                <ul class="nav flex-column">
                    <li class="nav-item"><a class="nav-link <?= $current_page == 'material_kaos.php' ? 'active' : '' ?>" href="material_kaos.php">Material Kaos</a></li>
                    <li class="nav-item"><a class="nav-link <?= $current_page == 'material_sablon.php' ? 'active' : '' ?>" href="material_sablon.php">Material Sablon</a></li>
                    <li class="nav-item"><a class="nav-link <?= $current_page == 'material_bordir.php' ? 'active' : '' ?>" href="material_bordir.php">Material Bordir</a></li>
                    <li class="nav-item"><a class="nav-link <?= $current_page == 'material_konveksi.php' ? 'active' : '' ?>" href="material_konveksi.php">Material Konveksi</a></li>
                </ul>
            </div>
        </li>
        <hr class="mx-3 my-2">
        <li class="nav-item">
            <a class="nav-link <?= $current_page == 'restore.php' ? 'active' : '' ?>" href="restore.php">
                <i class="bi bi-database-fill-gear"></i>Backup & Restore
            </a>
        </li>
    </ul>
    <div class="sidebar-footer p-3 border-top">
        <a class="nav-link mb-2" href="profil.php"><i class="bi bi-person-fill-gear"></i>Profil Saya</a>
        <a class="nav-link text-danger" href="logout.php"><i class="bi bi-box-arrow-right"></i>Logout</a>
    </div>
  </div>
</div>

<main class="main-content">