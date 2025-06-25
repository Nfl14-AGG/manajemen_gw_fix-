<?php
require_once 'config/koneksi.php';
require_once 'auth_check.php';

include 'templates/header.php';

// Ambil data user yang sedang login untuk ditampilkan di form
$stmt = $pdo->prepare("SELECT username, nama_lengkap FROM users WHERE id_user = ?");
$stmt->execute([$_SESSION['id_user']]);
$user = $stmt->fetch();
?>

<h1 class="mb-4">Profil Pengguna</h1>

<?php if (isset($_SESSION['pesan'])): ?>
    <div class="alert alert-<?= $_SESSION['pesan']['tipe'] ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION['pesan']['isi']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['pesan']); ?>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs" id="profileTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="details-tab" data-bs-toggle="tab" data-bs-target="#details-pane" type="button" role="tab" aria-controls="details-pane" aria-selected="true">
                    <i class="bi bi-person-fill me-2"></i>Detail Profil
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#password-pane" type="button" role="tab" aria-controls="password-pane" aria-selected="false">
                    <i class="bi bi-shield-lock-fill me-2"></i>Ganti Password
                </button>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content p-3" id="profileTabsContent">
            <div class="tab-pane fade show active" id="details-pane" role="tabpanel" aria-labelledby="details-tab" tabindex="0">
                <h4 class="mb-3">Ubah Detail Profil</h4>
                <form action="proses_profil.php" method="POST">
                    <input type="hidden" name="aksi" value="edit_profil">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-fonts"></i></span>
                            <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" value="<?= htmlspecialchars($user['nama_lengkap']) ?>" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save-fill me-2"></i>Simpan Profil</button>
                </form>
            </div>
            <div class="tab-pane fade" id="password-pane" role="tabpanel" aria-labelledby="password-tab" tabindex="0">
                <h4 class="mb-3">Ubah Password Anda</h4>
                <form action="proses_profil.php" method="POST">
                    <input type="hidden" name="aksi" value="ganti_password">
                    <div class="mb-3">
                        <label for="password_lama" class="form-label">Password Lama</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-key"></i></span>
                            <input type="password" class="form-control" id="password_lama" name="password_lama" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="password_baru" class="form-label">Password Baru</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-key-fill"></i></span>
                            <input type="password" class="form-control" id="password_baru" name="password_baru" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="konfirmasi_password" class="form-label">Konfirmasi Password Baru</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-key-fill"></i></span>
                            <input type="password" class="form-control" id="konfirmasi_password" name="konfirmasi_password" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-warning"><i class="bi bi-shield-check me-2"></i>Ganti Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>