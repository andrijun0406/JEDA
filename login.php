<?php
session_start();
include 'koneksi.php';

// Jika pengguna sudah login, arahkan langsung ke dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = "";

// Proses pengecekan saat tombol login ditekan
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // 1. Cek apakah ini akun Siswa
    $query_siswa = "SELECT id FROM users WHERE username = '$username' AND password = '$password'";
    $result_siswa = $conn->query($query_siswa);

    if ($result_siswa->num_rows > 0) {
        $row = $result_siswa->fetch_assoc();
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['role'] = 'student';
        header("Location: index.php");
        exit();
    } else {
        // 2. Jika bukan siswa, cek apakah ini akun Orang Tua
        $query_ortu = "SELECT id FROM parents WHERE username = '$username' AND password = '$password'";
        $result_ortu = $conn->query($query_ortu);
        
        if ($result_ortu->num_rows > 0) {
            $row_ortu = $result_ortu->fetch_assoc();
            $_SESSION['parent_id'] = $row_ortu['id'];
            $_SESSION['role'] = 'parent';
            header("Location: parent_dashboard.php");
            exit();
        } else {
            $error = "Username atau Password salah!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - JEDA Smart Card</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .login-card { max-width: 400px; width: 100%; padding: 30px; border-radius: 20px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .brand-jeda { font-weight: 900; font-size: 2.5rem; color: #0d6efd; letter-spacing: -1px; }
    </style>
</head>
<body>

<div class="card login-card bg-white">
    <div class="text-center mb-4">
        <h1 class="brand-jeda mb-0">JEDA.</h1>
        <p class="text-muted small">Pause the Impulse. Protect Your Future.</p>
    </div>

    <?php if ($error != ""): ?>
        <div class="alert alert-danger text-center rounded-3 py-2"><?= $error; ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <div class="mb-3">
            <label class="form-label text-muted small fw-bold">Username</label>
            <input type="text" name="username" class="form-control rounded-4 py-2" placeholder="Contoh: budi atau nisa atau ortu_budi atau ortu_hendra" required>
        </div>
        <div class="mb-4">
            <label class="form-label text-muted small fw-bold">Password</label>
            <input type="password" name="password" class="form-control rounded-4 py-2" placeholder="Masukkan 123" required>
        </div>
        <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold">Masuk ke Dashboard</button>
    </form>
    
    <div class="text-center mt-4 text-muted small">
        <p>Prototipe khusus SMA Asy-Syahid Boarding School</p>
    </div>
</div>

</body>
</html>