<?php
require_once 'config/koneksi.php';
require_once 'auth_check.php';

$backup_dir = __DIR__ . '/backups/';

// --- LOGIKA UNTUK PROSES RESTORE DAN DELETE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['backup_file'])) {
    
    $backup_file = basename($_POST['backup_file']);
    $file_path = $backup_dir . $backup_file;

    if (file_exists($file_path)) {

        // Jika aksinya adalah 'restore'
        if (isset($_POST['aksi_restore'])) {
            
            // Cek jika file backup kosong
            $sql_commands = file_get_contents($file_path);
            if (empty(trim($sql_commands))) {
                $_SESSION['pesan'] = ['tipe' => 'danger', 'isi' => 'Gagal restore: File backup "' . htmlspecialchars($backup_file) . '" kosong atau tidak valid.'];
                header('Location: restore.php');
                exit;
            }

            try {
                // =================================================================
                // --- PERUBAHAN UTAMA: TIDAK MENGGUNAKAN TRANSAKSI ---
                // $pdo->beginTransaction(); // Dihapus

                // LANGKAH A: HAPUS SEMUA ISI DATA DARI TABEL YANG ADA
                $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
                $tables_stmt = $pdo->query('SHOW TABLES');
                while ($table_row = $tables_stmt->fetch(PDO::FETCH_NUM)) {
                    $pdo->exec("TRUNCATE TABLE `{$table_row[0]}`");
                }

                // LANGKAH B: ISI KEMBALI DENGAN DATA DARI FILE BACKUP
                // Menjalankan semua perintah INSERT dari file backup
                $pdo->exec($sql_commands);

                // LANGKAH C: SELESAIKAN PROSES
                $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
                // $pdo->commit(); // Dihapus
                
                $_SESSION['pesan'] = ['tipe' => 'success', 'isi' => 'Database berhasil dikosongkan dan dipulihkan dari file: ' . htmlspecialchars($backup_file)];

            } catch (PDOException $e) {
                // Karena tidak ada transaksi, kita tidak perlu rollBack
                // Cukup aktifkan kembali foreign key checks dan tampilkan error
                try { $pdo->exec('SET FOREIGN_KEY_CHECKS = 1'); } catch(Exception $ex) {}
                
                $_SESSION['pesan'] = ['tipe' => 'danger', 'isi' => 'Gagal restore. Error: ' . $e->getMessage()];
            }
        
        // Jika aksinya adalah 'delete'
        } elseif (isset($_POST['aksi_hapus'])) {
            if (unlink($file_path)) {
                $_SESSION['pesan'] = ['tipe' => 'success', 'isi' => 'File backup berhasil dihapus: ' . htmlspecialchars($backup_file)];
            } else {
                $_SESSION['pesan'] = ['tipe' => 'danger', 'isi' => 'Gagal menghapus file backup.'];
            }
        }
    } else {
        $_SESSION['pesan'] = ['tipe' => 'warning', 'isi' => 'File backup tidak ditemukan.'];
    }

    header('Location: restore.php');
    exit;
}

include 'templates/header.php';

// --- HTML Tampilan (Tidak ada perubahan) ---
$backup_files = [];
if (is_dir($backup_dir)) {
    $files = array_diff(scandir($backup_dir, SCANDIR_SORT_DESCENDING), ['.', '..', '.htaccess']);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            $backup_files[] = $file;
        }
    }
}
?>

<h1 class="mb-4">Backup & Restore Database Server</h1>

<?php if (isset($_SESSION['pesan'])): ?>
    <div class="alert alert-<?= $_SESSION['pesan']['tipe'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($_SESSION['pesan']['isi']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['pesan']); ?>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0"><i class="bi bi-database-down me-2"></i>Buat Backup Baru (Hanya Data)</h5></div>
    <div class="card-body">
        <p>Klik tombol di bawah untuk membuat salinan ISI DATA dari database. File akan disimpan langsung di server.</p>
        <a href="backup.php" class="btn btn-primary"><i class="bi bi-plus-circle-fill me-2"></i>Mulai Proses Backup</a>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5 class="mb-0"><i class="bi bi-list-task me-2"></i>Daftar Backup Tersedia</h5></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Nama File Backup</th>
                        <th>Ukuran</th>
                        <th>Tanggal Dibuat</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($backup_files)): ?>
                        <?php foreach ($backup_files as $file): 
                            $file_path = $backup_dir . $file;
                            $file_size = file_exists($file_path) ? filesize($file_path) : 0;
                            $file_date = file_exists($file_path) ? date("d M Y, H:i:s", filemtime($file_path)) : 'N/A';
                        ?>
                        <tr>
                            <td class="fw-bold"><i class="bi bi-file-earmark-zip-fill text-muted me-2"></i><?= htmlspecialchars($file) ?></td>
                            <td><?= round($file_size / 1024, 2) ?> KB</td>
                            <td><?= $file_date ?></td>
                            <td class="text-end">
                                <form action="restore.php" method="POST" class="d-inline-block">
                                    <input type="hidden" name="backup_file" value="<?= htmlspecialchars($file) ?>">
                                    <button type="submit" name="aksi_restore" class="btn btn-success btn-sm" onclick="return confirm('PERINGATAN: Aksi ini akan MENGHAPUS SEMUA DATA LAMA, lalu mengisinya dengan data dari file ini. Anda yakin?')">
                                        <i class="bi bi-hdd-rack-fill me-2"></i>Pulihkan
                                    </button>
                                </form>
                                <form action="restore.php" method="POST" class="d-inline-block ms-2">
                                    <input type="hidden" name="backup_file" value="<?= htmlspecialchars($file) ?>">
                                    <button type="submit" name="aksi_hapus" class="btn btn-danger btn-sm" onclick="return confirm('Anda yakin ingin menghapus file backup ini secara permanen?')">
                                        <i class="bi bi-trash-fill me-2"></i>Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted p-4">Belum ada file backup yang tersimpan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>