<?php
// ---- BARIS UNTUK DEBUGGING ----
ini_set('display_errors', 1);
error_reporting(E_ALL);
// --------------------------------

require_once 'config/koneksi.php';
require_once 'auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: profil.php');
    exit;
}

$aksi = $_POST['aksi'] ?? '';
$id_user = $_SESSION['id_user'];

// =======================================================
// Aksi untuk memperbarui profil (username, nama, dan FOTO)
// =======================================================
if ($aksi === 'edit_profil') {
    $username = trim($_POST['username']);
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $foto_profil_file = $_FILES['foto_profil'] ?? null;
    $nama_file_foto_baru = null;

    if (empty($username) || empty($nama_lengkap)) {
        $_SESSION['pesan'] = ['tipe' => 'danger', 'isi' => 'Username dan Nama Lengkap tidak boleh kosong.'];
        header('Location: profil.php');
        exit;
    }

    try {
        // --- LOGIKA UPLOAD FOTO PROFIL ---
        if ($foto_profil_file && $foto_profil_file['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/config/assets/img/profil/';
            $allowed_types = ['image/jpeg', 'image/png'];
            $max_size = 2 * 1024 * 1024; // 2 MB

            if (!in_array($foto_profil_file['type'], $allowed_types)) {
                throw new Exception("Format file tidak didukung. Harap unggah JPG atau PNG.");
            }
            if ($foto_profil_file['size'] > $max_size) {
                throw new Exception("Ukuran file terlalu besar. Maksimal 2 MB.");
            }

            // Buat nama file unik: user_{id_user}_{timestamp}.ext
            $file_extension = pathinfo($foto_profil_file['name'], PATHINFO_EXTENSION);
            $nama_file_foto_baru = 'user_' . $id_user . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $nama_file_foto_baru;

            // Pindahkan file yang diunggah
            if (!move_uploaded_file($foto_profil_file['tmp_name'], $upload_path)) {
                throw new Exception("Gagal memindahkan file yang diunggah.");
            }

            // Hapus foto lama jika ada (selain default.png)
            $stmt_old_pic = $pdo->prepare("SELECT foto_profil FROM users WHERE id_user = ?");
            $stmt_old_pic->execute([$id_user]);
            $old_pic_name = $stmt_old_pic->fetchColumn();
            if ($old_pic_name && $old_pic_name !== 'default.png' && file_exists($upload_dir . $old_pic_name)) {
                unlink($upload_dir . $old_pic_name);
            }
        }
        // --- AKHIR LOGIKA UPLOAD FOTO ---

        // Persiapkan query update (dengan atau tanpa foto baru)
        if ($nama_file_foto_baru) {
            $sql = "UPDATE users SET username = ?, nama_lengkap = ?, foto_profil = ? WHERE id_user = ?";
            $params = [$username, $nama_lengkap, $nama_file_foto_baru, $id_user];
        } else {
            $sql = "UPDATE users SET username = ?, nama_lengkap = ? WHERE id_user = ?";
            $params = [$username, $nama_lengkap, $id_user];
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $_SESSION['username'] = $username;
        $_SESSION['pesan'] = ['tipe' => 'success', 'isi' => 'Profil berhasil diperbarui.'];

    } catch (PDOException $e) {
        if ($e->getCode() == '23000') {
            $_SESSION['pesan'] = ['tipe' => 'danger', 'isi' => 'Gagal: Username tersebut sudah digunakan.'];
        } else {
            $_SESSION['pesan'] = ['tipe' => 'danger', 'isi' => 'Error database: ' . $e->getMessage()];
        }
    } catch (Exception $e) {
        $_SESSION['pesan'] = ['tipe' => 'danger', 'isi' => 'Error file: ' . $e->getMessage()];
    }
}

// =======================================================
// Aksi untuk mengganti password (TETAP SAMA)
// =======================================================
elseif ($aksi === 'ganti_password') {
    // ... (Kode ganti password Anda tidak berubah, bisa disalin dari file lama)
    $password_lama = $_POST['password_lama'];
    $password_baru = $_POST['password_baru'];
    $konfirmasi_password = $_POST['konfirmasi_password'];

    if (empty($password_lama) || empty($password_baru) || empty($konfirmasi_password)) {
        $_SESSION['pesan'] = ['tipe' => 'danger', 'isi' => 'Semua kolom password harus diisi.'];
    } 
    elseif ($password_baru !== $konfirmasi_password) {
        $_SESSION['pesan'] = ['tipe' => 'danger', 'isi' => 'Password baru dan konfirmasi tidak cocok.'];
    } else {
        try {
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id_user = ?");
            $stmt->execute([$id_user]);
            $user = $stmt->fetch();

            if ($user && password_verify($password_lama, $user['password_hash'])) {
                $new_password_hash = password_hash($password_baru, PASSWORD_DEFAULT);
                $stmt_update = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id_user = ?");
                $stmt_update->execute([$new_password_hash, $id_user]);
                $_SESSION['pesan'] = ['tipe' => 'success', 'isi' => 'Password berhasil diganti.'];
            } else {
                $_SESSION['pesan'] = ['tipe' => 'danger', 'isi' => 'Password lama yang Anda masukkan salah.'];
            }
        } catch (PDOException $e) {
            $_SESSION['pesan'] = ['tipe' => 'danger', 'isi' => 'Terjadi kesalahan pada database: ' . $e->getMessage()];
        }
    }
}

header('Location: profil.php');
exit;