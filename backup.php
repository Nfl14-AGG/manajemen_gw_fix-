<?php
require_once 'config/koneksi.php';
require_once 'auth_check.php';

// Fungsi untuk backup HANYA ISI DATA dari tabel
function backup_database_data_only($pdo, $dbname) {
    $backup_dir = __DIR__ . '/backups/';
    if (!is_dir($backup_dir)) {
        mkdir($backup_dir, 0755, true);
    }

    $backup_file_name = 'backup-data-only-' . $dbname . '-' . date("Y-m-d_H-i-s") . '.sql';
    $file_path = $backup_dir . $backup_file_name;

    try {
        $handle = fopen($file_path, 'w');
        if ($handle === false) {
            throw new Exception("Gagal membuat atau menulis ke file backup. Periksa izin folder.");
        }
        
        fwrite($handle, "-- Backup HANYA DATA untuk '{$dbname}'\n");
        fwrite($handle, "-- Dibuat pada: " . date('Y-m-d H:i:s') . "\n\n");

        $tables_stmt = $pdo->query('SHOW TABLES');
        while ($table_row = $tables_stmt->fetch(PDO::FETCH_NUM)) {
            $table = $table_row[0];
            
            fwrite($handle, "--\n-- Membuang data untuk tabel `{$table}`\n--\n");

            $data_stmt = $pdo->query("SELECT * FROM `{$table}`");
            $data_stmt->setFetchMode(PDO::FETCH_ASSOC);
            
            foreach($data_stmt as $row) {
                $fields = '`' . implode('`, `', array_keys($row)) . '`';
                $values = [];
                foreach ($row as $value) {
                    if ($value === null) {
                        $values[] = 'NULL';
                    } else {
                        $values[] = $pdo->quote($value);
                    }
                }
                fwrite($handle, "INSERT INTO `{$table}` ({$fields}) VALUES (" . implode(', ', $values) . ");\n");
            }
            fwrite($handle, "\n");
        }
        
        fclose($handle);

        $_SESSION['pesan'] = ['tipe' => 'success', 'isi' => 'Backup (hanya data) berhasil dibuat: ' . htmlspecialchars($backup_file_name)];

    } catch (Exception $e) {
        $_SESSION['pesan'] = ['tipe' => 'danger', 'isi' => 'Gagal membuat backup: ' . $e->getMessage()];
    }
}

// Jalankan fungsi backup
backup_database_data_only($pdo, $dbname);

// Alihkan kembali ke halaman restore
header('Location: restore.php');
exit;
?>