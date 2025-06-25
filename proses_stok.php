<?php
require_once 'auth_check.php';
require_once 'config/koneksi.php';

// Daftar tabel yang diizinkan dan nama primary key-nya
$allowed_tables = [
    'wearpack' => 'id_wearpack',
    'kaos' => 'id_kaos',
    'material_kaos' => 'id_material',
    'material_sablon' => 'id_sablon',
    'material_bordir' => 'id_bordir',
    'material_konveksi' => 'id',
    'bordir' => 'id_bordir'
];

$id_user = $_SESSION['id_user'];

// --- AKSI HAPUS ITEM ---
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['aksi']) && $_GET['aksi'] === 'hapus_item') {
    $tipe_item = $_GET['tipe_item'] ?? '';
    $id_item = $_GET['id'] ?? 0;

    if (array_key_exists($tipe_item, $allowed_tables) && $id_item > 0) {
        $primary_key = $allowed_tables[$tipe_item];
        
        try {
            // PERUBAHAN: Cek stok_akhir, bukan stok_saat_ini
            $stmt_cek = $pdo->prepare("SELECT stok_akhir FROM `$tipe_item` WHERE `$primary_key` = ?");
            $stmt_cek->execute([$id_item]);
            $item = $stmt_cek->fetch();

            // PERUBAHAN: Cek kolom stok_akhir
            if ($item && (float)$item['stok_akhir'] == 0) {
                $stmt_hapus = $pdo->prepare("DELETE FROM `$tipe_item` WHERE `$primary_key` = ?");
                $stmt_hapus->execute([$id_item]);
                $_SESSION['pesan'] = ['tipe' => 'success', 'isi' => 'Item berhasil dihapus.'];
            } else {
                $_SESSION['pesan'] = ['tipe' => 'danger', 'isi' => 'Gagal menghapus. Item tidak ditemukan atau stok akhir belum 0. Kosongkan stok terlebih dahulu.'];
            }
        } catch (Exception $e) {
            $_SESSION['pesan'] = ['tipe' => 'danger', 'isi' => 'Terjadi kesalahan: ' . $e->getMessage()];
        }
    } else {
        $_SESSION['pesan'] = ['tipe' => 'danger', 'isi' => 'Permintaan tidak valid.'];
    }
    header("Location: {$tipe_item}.php");
    exit;
}

// --- AKSI DARI FORM (METHOD POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipe_item = $_POST['tipe_item'] ?? '';
    $aksi = $_POST['aksi'] ?? '';
    
    if (!array_key_exists($tipe_item, $allowed_tables)) {
        $_SESSION['pesan'] = ['tipe' => 'danger', 'isi' => 'Tipe item tidak valid.'];
        header("Location: index.php"); 
        exit;
    }
    
    $primary_key = $allowed_tables[$tipe_item];
    $pdo->beginTransaction();
    try {
        // --- AKSI STOK MASUK / KELUAR ---
        if ($aksi === 'masuk' || $aksi === 'keluar') {
            $id_item = $_POST['id_item'];
            $jumlah = (float)$_POST['jumlah'];
            $stok_sebelum = (float)$_POST['stok_sebelum']; // Ini adalah stok_akhir saat ini
            $keterangan = trim($_POST['keterangan']);

            if ($jumlah <= 0) {
                 throw new Exception("Jumlah harus lebih dari nol.");
            }

            if ($aksi === 'masuk') {
                $stok_sesudah = $stok_sebelum + $jumlah;
                $operator = '+';
            } else { 
                if ($jumlah > $stok_sebelum) {
                    throw new Exception("Stok tidak mencukupi untuk dikurangi.");
                }
                $stok_sesudah = $stok_sebelum - $jumlah;
                $operator = '-';
            }

            // PERUBAHAN: Update kolom stok_akhir, bukan stok_saat_ini
            $sql_update = "UPDATE `$tipe_item` SET stok_akhir = stok_akhir $operator ? WHERE $primary_key = ?";
            $stmt_update = $pdo->prepare($sql_update);
            $stmt_update->execute([$jumlah, $id_item]);

            // Laporan tidak perlu diubah karena sudah menggunakan stok_sebelum dan stok_sesudah
            $sql_laporan = "INSERT INTO laporan (id_item, tipe_item, jenis_transaksi, jumlah, stok_sebelum, stok_sesudah, keterangan, id_user) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_laporan = $pdo->prepare($sql_laporan);
            $stmt_laporan->execute([$id_item, $tipe_item, $aksi, $jumlah, $stok_sebelum, $stok_sesudah, $keterangan, $id_user]);
            $_SESSION['pesan'] = ['tipe' => 'success', 'isi' => 'Stok berhasil diupdate.'];

        // --- AKSI TAMBAH ITEM BARU ---
        } elseif ($aksi === 'tambah_item_baru') {
            $stok_awal_form = (float)$_POST['stok_awal'];
            $keterangan = trim($_POST['keterangan']);

            if ($tipe_item === 'wearpack' || $tipe_item === 'kaos') {
                $ukuran = strtoupper(trim($_POST['ukuran'])); 
                // PERUBAHAN: Insert ke kolom stok_awal dan stok_akhir
                $sql_insert_item = "INSERT INTO `$tipe_item` (model, warna, ukuran, stok_awal, stok_akhir) VALUES (?, ?, ?, ?, ?)";
                $params_insert = [trim($_POST['model']), trim($_POST['warna']), $ukuran, $stok_awal_form, $stok_awal_form];
            
            } else if($tipe_item === 'bordir'){
                // PERUBAHAN: Insert ke stok_akhir
                $sql_insert_item = "INSERT INTO `bordir` (nama_bordir, stok_awal, stok_akhir) VALUES (?, ?, ?)";
                $params_insert = [trim($_POST['nama_bordir']), $stok_awal_form, $stok_awal_form];

            } else { // Untuk semua jenis material
                $nama_kolom_material = ($tipe_item === 'material_kaos') ? 'nama_material' : 'jenis_material';
                $nama_material = trim($_POST[$nama_kolom_material]);
                // PERUBAHAN: Insert ke kolom stok_awal dan stok_akhir
                $sql_insert_item = "INSERT INTO `$tipe_item` ($nama_kolom_material, warna, satuan, stok_awal, stok_akhir) VALUES (?, ?, ?, ?, ?)";
                $params_insert = [$nama_material, trim($_POST['warna']), trim($_POST['satuan']), $stok_awal_form, $stok_awal_form];
            }
            
            $stmt_insert = $pdo->prepare($sql_insert_item);
            $stmt_insert->execute($params_insert);
            $id_item_baru = $pdo->lastInsertId();

            // Buat laporan jika stok awal lebih dari 0
            if ($stok_awal_form > 0) {
                $sql_laporan = "INSERT INTO laporan (id_item, tipe_item, jenis_transaksi, jumlah, stok_sebelum, stok_sesudah, keterangan, id_user) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt_laporan = $pdo->prepare($sql_laporan);
                $stmt_laporan->execute([$id_item_baru, $tipe_item, 'masuk', $stok_awal_form, 0, $stok_awal_form, $keterangan, $id_user]);
            }
            $_SESSION['pesan'] = ['tipe' => 'success', 'isi' => 'Item baru berhasil ditambahkan.'];

        // --- AKSI EDIT DETAIL ITEM ---
        } elseif ($aksi === 'edit_item') {
            $id_item = $_POST['id_item'];
            
            if ($tipe_item === 'wearpack' || $tipe_item === 'kaos') {
                $ukuran = strtoupper(trim($_POST['ukuran']));
                // Query ini tidak mengubah stok, jadi tetap aman
                $sql_update = "UPDATE `$tipe_item` SET model = ?, warna = ?, ukuran = ? WHERE $primary_key = ?";
                $params_update = [trim($_POST['model']), trim($_POST['warna']), $ukuran, $id_item];
            
            } else if($tipe_item === 'bordir'){
                // Query ini mengizinkan perubahan stok awal
                $sql_update = "UPDATE `bordir` SET nama_bordir = ?, stok_awal = ? WHERE id_bordir = ?";
                $params_update = [trim($_POST['nama_bordir']), (int)$_POST['stok_awal'], $id_item];

            } else { // Untuk semua material
                $nama_kolom_material = ($tipe_item === 'material_kaos') ? 'nama_material' : 'jenis_material';
                $nama_material = trim($_POST[$nama_kolom_material]);
                // Query ini tidak mengubah stok, jadi tetap aman
                $sql_update = "UPDATE `$tipe_item` SET $nama_kolom_material = ?, warna = ?, satuan = ? WHERE $primary_key = ?";
                $params_update = [$nama_material, trim($_POST['warna']), trim($_POST['satuan']), $id_item];
            }

            $stmt_update = $pdo->prepare($sql_update);
            $stmt_update->execute($params_update);
            $_SESSION['pesan'] = ['tipe' => 'success', 'isi' => 'Detail item berhasil diperbarui.'];
        }
        
        $pdo->commit();

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['pesan'] = ['tipe' => 'danger', 'isi' => 'Terjadi kesalahan: ' . $e->getMessage()];
    }

    header("Location: {$tipe_item}.php");
    exit;
}

// Redirect jika tidak ada aksi yang valid
header('Location: index.php');
exit;
?>