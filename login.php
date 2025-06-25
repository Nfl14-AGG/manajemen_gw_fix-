<?php
// Selalu mulai sesi di awal
session_start();

// Jika pengguna sudah login, langsung alihkan ke halaman utama
if (isset($_SESSION['id_user'])) {
    header("Location: index.php");
    exit;
}

// Sertakan file koneksi database. File ini sudah menyediakan variabel $pdo.
require_once 'config/koneksi.php';

// Inisialisasi variabel untuk pesan error
$username_err = $password_err = $login_err = "";
$username_value = "";

// Proses data formulir saat formulir disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Validasi Input Username
    if (empty(trim($_POST["username"]))) {
        $username_err = "Mohon masukkan username.";
    } else {
        $username = trim($_POST["username"]);
        $username_value = $username; // Simpan nilai untuk ditampilkan kembali di form
    }

    // 2. Validasi Input Password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Mohon masukkan password Anda.";
    } else {
        $password = trim($_POST["password"]);
    }

    // 3. Proses Validasi Kredensial dengan Database (Menggunakan PDO)
    if (empty($username_err) && empty($password_err)) {
        
        // PERUBAHAN: Menghapus kolom 'level' dari query
        $sql = "SELECT id_user, username, password_hash FROM users WHERE username = :username";

        if ($stmt = $pdo->prepare($sql)) {
            // Bind parameter
            $stmt->bindParam(":username", $username, PDO::PARAM_STR);

            // Coba jalankan statement
            if ($stmt->execute()) {
                // Cek apakah username ditemukan
                if ($stmt->rowCount() == 1) {
                    $user = $stmt->fetch();
                    $id_user = $user['id_user'];
                    $hashed_password = $user['password_hash'];

                    // Verifikasi password
                    if (password_verify($password, $hashed_password)) {
                        // Password benar, mulai sesi baru
                        session_regenerate_id(); // Keamanan tambahan
                        
                        // PERUBAHAN: Menghapus 'level' dari sesi
                        $_SESSION["id_user"] = $id_user;
                        $_SESSION["username"] = $username;

                        // Alihkan pengguna ke halaman dashboard
                        header("location: index.php");
                        exit;
                    } else {
                        // Password tidak valid
                        $login_err = "Username atau password yang Anda masukkan salah.";
                    }
                } else {
                    // Username tidak ditemukan
                    $login_err = "Username atau password yang Anda masukkan salah.";
                }
            } else {
                $login_err = "Terjadi kesalahan. Silakan coba lagi nanti.";
            }

            // Tutup statement
            unset($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Gudang Wearpack</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="config/assets/css/style.css"> <link rel="stylesheet" href="config/assets/css/login.css"> </head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-form-wrapper">
                <div class="login-header">
                    <h1 class="login-title">Gudang Wearpack</h1>
                    <p class="login-subtitle">Selamat datang kembali! Silakan masuk.</p>
                </div>

                <?php if (!empty($login_err)): ?>
                    <div class="alert-login alert-danger"><?= htmlspecialchars($login_err) ?></div>
                <?php endif; ?>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" novalidate>
                    <div class="input-group">
                        <label for="username">Username</label>
                        <div class="input-with-icon">
                            <i class="bi bi-person-fill"></i>
                            <input type="text" id="username" name="username" placeholder="Masukkan username Anda" value="<?= htmlspecialchars($username_value) ?>" class="<?= (!empty($username_err)) ? 'is-invalid' : ''; ?>">
                        </div>
                        <?php if(!empty($username_err)): ?><span class="invalid-feedback"><?= $username_err; ?></span><?php endif; ?>
                    </div>

                    <div class="input-group">
                        <label for="password">Password</label>
                        <div class="input-with-icon">
                             <i class="bi bi-shield-lock-fill"></i>
                            <input type="password" id="password" name="password" placeholder="Masukkan password Anda" class="<?= (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                        </div>
                         <?php if(!empty($password_err)): ?><span class="invalid-feedback"><?= $password_err; ?></span><?php endif; ?>
                    </div>

                    <button type="submit" class="btn-login">Login</button>
                </form>
            </div>

            <div class="login-image-wrapper">
                <img src="config/assets/images/login_side_image.jpg" alt="Wearpack Gudang" class="login-side-image">
            </div>
        </div>
    </div>
</body>
</html>