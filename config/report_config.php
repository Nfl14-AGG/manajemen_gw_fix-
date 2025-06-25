<?php
// projex/gw/config/report_config.php

/**
 * File ini berisi definisi terpusat untuk semua laporan ringkasan.
 * Digunakan oleh laporan_ukuran.php dan cetak_laporan_ukuran.php
 * untuk menjaga konsistensi judul, ikon, dan detail teknis lainnya.
 */

$reports = [
    'produk' => [
        'wearpack' => ['title' => 'Stok Wearpack per Ukuran', 'icon' => 'bi-person-workspace'],
        'kaos'     => ['title' => 'Stok Kaos per Ukuran', 'icon' => 'bi-t-shirt'],
        'bordir'   => ['title' => 'Stok Bordir', 'icon' => 'bi-scissors'],
    ],
    'material' => [
        'material_kaos'     => ['title' => 'Material Kaos', 'icon' => 'bi-stack'],
        'material_sablon'   => ['title' => 'Material Sablon', 'icon' => 'bi-brush'],
        'material_bordir'   => ['title' => 'Material Bordir', 'icon' => 'bi-threads'],
        'material_konveksi' => ['title' => 'Material Konveksi', 'icon' => 'bi-rulers'],
    ]
];