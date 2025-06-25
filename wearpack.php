<?php include 'templates/header.php'; 

// --- Logika Pencarian ---
$search_term = trim($_GET['search'] ?? '');
$model_filter = $_GET['model'] ?? '';
$warna_filter = $_GET['warna'] ?? '';

$sql_where_clause = "";
$params = [];

if (!empty($model_filter) && !empty($warna_filter)) {
    $sql_where_clause = "WHERE model = ? AND warna = ?";
    $params = [$model_filter, $warna_filter];
    if (empty($search_term)) {
        $search_term = $model_filter . ' ' . $warna_filter;
    }
} 
else if (!empty($search_term)) {
    $searchable_columns = ['model', 'warna', 'ukuran'];
    $keywords = array_filter(explode(' ', $search_term));
    if (!empty($keywords)) {
        $conditions = [];
        foreach ($keywords as $keyword) {
            $keyword_like = "%" . $keyword . "%";
            $sub_conditions = [];
            foreach ($searchable_columns as $column) {
                $sub_conditions[] = "`$column` LIKE ?";
                $params[] = $keyword_like;
            }
            $conditions[] = "(" . implode(' OR ', $sub_conditions) . ")";
        }
        $sql_where_clause = "WHERE " . implode(' AND ', $conditions);
    }
}
// --- Akhir Logika Pencarian ---

?>

<h1 class="mb-4">Manajemen Stok Wearpack</h1>

<div class="card mb-4">
    <div class="card-header"><i class="bi bi-search"></i> Pencarian Barang</div>
    <div class="card-body">
        <form action="wearpack.php" method="GET" class="d-flex">
            <input type="text" name="search" class="form-control me-2" placeholder="Ketik model, warna, atau ukuran untuk mencari..." value="<?= htmlspecialchars($search_term) ?>">
            <button type="submit" class="btn btn-primary">Cari</button>
        </form>
    </div>
</div>

<div class="d-flex justify-content-end mb-4">
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tambahModal">
        <i class="bi bi-plus-circle-fill me-2"></i>Tambah Wearpack Baru
    </button>
</div>

<?php if (isset($_SESSION['pesan'])): ?>
    <div class="alert alert-<?= $_SESSION['pesan']['tipe'] ?> alert-dismissible fade show" role="alert">
        <?= $_SESSION['pesan']['isi'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['pesan']); ?>
<?php endif; ?>

<div class="card">
    <div class="card-header"><i class="bi bi-table"></i> Daftar Stok Wearpack</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Model</th>
                        <th>Warna</th>
                        <th>Ukuran</th>
                        <th>Stok Awal</th>
                        <th>Stok Akhir</th>
                        <th style="width: 320px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // PERUBAHAN: SELECT kolom spesifik termasuk stok_awal dan stok_akhir
                    $sql = "SELECT id_wearpack, model, warna, ukuran, stok_awal, stok_akhir FROM wearpack " . $sql_where_clause . " ORDER BY model, warna, ukuran";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    $no = 1;

                    if ($stmt->rowCount() > 0):
                        while ($row = $stmt->fetch()):
                    ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($row['model']) ?></td>
                        <td><?= htmlspecialchars($row['warna']) ?></td>
                        <td><?= htmlspecialchars($row['ukuran']) ?></td>
                        <td><?= htmlspecialchars($row['stok_awal']) ?></td>
                        <td><strong><?= htmlspecialchars($row['stok_akhir']) ?></strong></td>
                        <td>
                            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#ubahStokModal" data-id="<?= $row['id_wearpack'] ?>" data-nama="<?= htmlspecialchars($row['model']) ?>" data-stok="<?= $row['stok_akhir'] ?>" data-aksi="masuk">
                                <i class="bi bi-plus-lg"></i> Masuk
                            </button>
                            <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#ubahStokModal" data-id="<?= $row['id_wearpack'] ?>" data-nama="<?= htmlspecialchars($row['model']) ?>" data-stok="<?= $row['stok_akhir'] ?>" data-aksi="keluar">
                                <i class="bi bi-dash-lg"></i> Keluar
                            </button>
                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editItemModal" 
                                data-id="<?= $row['id_wearpack'] ?>" 
                                data-model="<?= htmlspecialchars($row['model']) ?>" 
                                data-warna="<?= htmlspecialchars($row['warna']) ?>" 
                                data-ukuran="<?= htmlspecialchars($row['ukuran']) ?>">
                                <i class="bi bi-pencil-fill"></i> Edit
                            </button>
                            <a href="proses_stok.php?aksi=hapus_item&tipe_item=wearpack&id=<?= $row['id_wearpack'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Anda yakin ingin menghapus item ini? Item hanya bisa dihapus jika stoknya 0.');">
                                <i class="bi bi-trash-fill"></i> Hapus
                            </a>
                        </td>
                    </tr>
                    <?php 
                        endwhile;
                    else:
                    ?>
                        <tr>
                            <td colspan="7" class="text-center">
                                <?php if (!empty($search_term)): ?>
                                    Data tidak ditemukan untuk kata kunci "<?= htmlspecialchars($search_term) ?>".
                                <?php else: ?>
                                    Belum ada data wearpack.
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="tambahModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Tambah Wearpack Baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="proses_stok.php" method="POST">
        <div class="modal-body">
            <input type="hidden" name="tipe_item" value="wearpack">
            <input type="hidden" name="aksi" value="tambah_item_baru">
            <div class="mb-3">
                <label for="model" class="form-label">Model</label>
                <input type="text" class="form-control" name="model" required>
            </div>
            <div class="mb-3">
                <label for="warna" class="form-label">Warna</label>
                <input type="text" class="form-control" name="warna" required>
            </div>
            <div class="mb-3">
                <label for="ukuran" class="form-label">Ukuran</label>
                <input type="text" class="form-control" name="ukuran" required>
            </div>
            <div class="mb-3">
                <label for="stok_awal" class="form-label">Stok Awal</label>
                <input type="number" class="form-control" name="stok_awal" required min="0" step="1">
            </div>
             <div class="mb-3">
                <label for="keterangan" class="form-label">Keterangan</label>
                <textarea class="form-control" name="keterangan" rows="2">Stok awal item baru</textarea>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="editItemModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Detail Item</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="proses_stok.php" method="POST">
        <div class="modal-body">
            <input type="hidden" name="tipe_item" value="wearpack">
            <input type="hidden" name="aksi" value="edit_item">
            <input type="hidden" name="id_item" id="edit_id_item">
            <div class="mb-3">
                <label for="edit_model" class="form-label">Model</label>
                <input type="text" class="form-control" id="edit_model" name="model" required>
            </div>
            <div class="mb-3">
                <label for="edit_warna" class="form-label">Warna</label>
                <input type="text" class="form-control" id="edit_warna" name="warna" required>
            </div>
            <div class="mb-3">
                <label for="edit_ukuran" class="form-label">Ukuran</label>
                <input type="text" class="form-control" id="edit_ukuran" name="ukuran" required>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-warning">Simpan Perubahan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="ubahStokModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="ubahStokModalLabel">Ubah Stok</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="proses_stok.php" method="POST">
        <div class="modal-body">
            <input type="hidden" name="id_item" id="id_item_modal">
            <input type="hidden" name="tipe_item" value="wearpack">
            <input type="hidden" name="stok_sebelum" id="stok_sebelum_modal">
            <input type="hidden" name="aksi" id="aksi_modal">
            <p>Anda akan mengubah stok untuk: <strong id="nama_item_modal"></strong></p>
            <p>Stok Akhir Saat Ini: <strong id="stok_saat_ini_modal"></strong></p>
            <div class="mb-3">
                <label for="jumlah" class="form-label">Jumlah</label>
                <input type="number" class="form-control" name="jumlah" required min="1" step="1">
            </div>
            <div class="mb-3">
                <label for="keterangan" class="form-label">Keterangan</label>
                <textarea class="form-control" name="keterangan" rows="3" required></textarea>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Proses</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var ubahStokModal = document.getElementById('ubahStokModal');
    if (ubahStokModal) {
        ubahStokModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var id = button.getAttribute('data-id');
            var nama = button.getAttribute('data-nama');
            var stok = button.getAttribute('data-stok'); // Ini sekarang adalah stok_akhir
            var aksi = button.getAttribute('data-aksi');
            
            var modalTitle = ubahStokModal.querySelector('.modal-title');
            modalTitle.textContent = (aksi === 'masuk' ? 'Tambah Stok Masuk' : 'Kurangi Stok Keluar');
            
            ubahStokModal.querySelector('#nama_item_modal').textContent = nama;
            ubahStokModal.querySelector('#stok_saat_ini_modal').textContent = stok; // Menampilkan stok_akhir saat ini
            ubahStokModal.querySelector('#id_item_modal').value = id;
            ubahStokModal.querySelector('#stok_sebelum_modal').value = stok; // Mengirim stok_akhir sebagai stok_sebelum untuk laporan
            ubahStokModal.querySelector('#aksi_modal').value = aksi;
        });
    }

    var editItemModal = document.getElementById('editItemModal');
    if (editItemModal) {
        editItemModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var id = button.getAttribute('data-id');
            var model = button.getAttribute('data-model');
            var warna = button.getAttribute('data-warna');
            var ukuran = button.getAttribute('data-ukuran');
            editItemModal.querySelector('#edit_id_item').value = id;
            editItemModal.querySelector('#edit_model').value = model;
            editItemModal.querySelector('#edit_warna').value = warna;
            editItemModal.querySelector('#edit_ukuran').value = ukuran;
        });
    }
});
</script>

<?php include 'templates/footer.php'; ?>