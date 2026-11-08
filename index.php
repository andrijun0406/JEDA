<?php
session_start();
include 'koneksi.php';

// Cek apakah pengguna sudah melewati halaman login
if (!isset($_SESSION['user_id'])) {
    // Jika belum login, tendang kembali ke halaman login
    header("Location: login.php");
    exit();
}

// Ambil ID secara dinamis dari Session
$user_id = $_SESSION['user_id'];

// AMBIL DATA DARI DATABASE
$query = "SELECT u.*, v.* FROM users u 
          JOIN virtual_wallets v ON u.id = v.user_id 
          WHERE u.id = $user_id";

$result = $conn->query($query);
$data = $result->fetch_assoc(); // <--- Variabel $data baru tercipta di sini

// 3. SETELAH $data ADA, BARU KITA BISA MELAKUKAN PERHITUNGAN
// Hitung persentase progress tabungan
$target = $data['target_amount'];
$terkumpul = $data['saved_amount'];
$progress_persen = ($target > 0) ? round(($terkumpul / $target) * 100) : 0;

// Kalkulasi Logika JEDA
$hard_limit = $data['daily_hard_limit']; // Rp 25.000 (Aturan SMA)
$auto_save = $data['daily_auto_save'];   // Rp 10.000 (Target Tabungan)
$debt = $data['virtual_debt'];           // Utang jajan kemarin

// Menghitung Soft Limit (Jatah Jajan Aman Hari Ini)
$soft_limit_awal = $hard_limit - $auto_save; 
$jatah_jajan_hari_ini = $soft_limit_awal - $debt; 

// Ambil riwayat belanja hari ini dari dummy Syahid Mart
$query_trx = "SELECT * FROM dummy_transactions WHERE user_id = $user_id ORDER BY time_txn DESC";
$trx_result = $conn->query($query_trx);

// --- LOGIKA AVATAR ANGEL VS ZOMBIE ---
$avatar_base = $data['avatar_base'];
$is_zombie = ($debt > 0); // Jika punya utang tabungan (boros) = Zombie

// Tentukan visual avatar berdasarkan pilihan dasar dan status keuangan
$avatar_display = '';
if ($is_zombie) {
    // Mode Boros (Zombie)
    if ($avatar_base == 'boy') $avatar_display = '🧟‍♂️';
    if ($avatar_base == 'girl') $avatar_display = '🧟‍♀️';
    if ($avatar_base == 'cat') $avatar_display = '😿🧟';
    if ($avatar_base == 'robot') $avatar_display = '👾☠️';
} else {
    // Mode Hemat (Angel)
    if ($avatar_base == 'boy') $avatar_display = '👼‍♂️';
    if ($avatar_base == 'girl') $avatar_display = '👼‍♀️';
    if ($avatar_base == 'cat') $avatar_display = '😻👼';
    if ($avatar_base == 'robot') $avatar_display = '🤖✨';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JEDA Smart Card Dashboard</title>
    <!-- Menggunakan Bootstrap untuk UI yang rapi dan mobile-friendly -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        .card-jeda { border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border: none; }
        .bg-soft-limit { background: linear-gradient(135deg, #0d6efd, #0dcaf0); color: white; }
    </style>
</head>
<body>

<div class="container py-4" style="max-width: 480px;"> <!-- Dibatasi lebarnya agar menyerupai layar HP -->
    
    <!-- Header Profil -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center">
            <!-- Tampilan Avatar Custom -->
            <div class="display-3 me-3" style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#avatarModal" title="Klik untuk ganti avatar">
                <?= $avatar_display; ?>
            </div>
            <div>
                <h5 class="mb-0 fw-bold">Hai, <?= $data['name']; ?></h5>
                <small class="text-muted"><?= $data['grade']; ?></small><br>
                <!-- Tombol Edit Avatar kecil -->
                <a href="#" class="text-decoration-none text-primary small" data-bs-toggle="modal" data-bs-target="#avatarModal">✏️ Custom Avatar</a>
            </div>
        </div>
        <div class="text-end">
            <a href="logout.php" class="btn btn-sm btn-outline-danger rounded-pill mb-2 px-3 fw-bold">Keluar</a>
            <br>
            <span class="badge bg-warning text-dark border border-dark rounded-pill py-1 px-2">
                🔥 Streak: <?= $data['streak_days']; ?> Hari
            </span>
        </div>
    </div>

    <!-- Peringatan Utang (Muncul jika ada utang) -->
    <?php if($debt > 0): ?>
    <div class="alert alert-danger rounded-4 text-center fw-bold text-sm mb-3">
        ⚠️ Kamu punya utang tabungan Rp <?= number_format($debt, 0, ',', '.'); ?>! Jatah hari ini otomatis dipotong.
    </div>
    <?php endif; ?>

    <!-- Kartu Soft Limit (Highlight Utama) -->
    <div class="card card-jeda bg-soft-limit mb-4">
        <div class="card-body text-center py-4">
            <p class="mb-1 text-light">Jatah Jajan Amanmu Hari Ini</p>
            <h1 class="display-5 fw-bold mb-3">Rp <?= number_format($jatah_jajan_hari_ini, 0, ',', '.'); ?></h1>
            
            <div class="d-flex justify-content-between bg-white bg-opacity-25 rounded-3 p-2 text-sm">
                <span>Saldo Asli BSI: <strong>Rp <?= number_format($data['real_balance'], 0, ',', '.'); ?></strong></span>
                <span>Terkunci Tabungan: <strong>Rp <?= number_format($auto_save, 0, ',', '.'); ?></strong></span>
            </div>
        </div>
    </div>

    <!-- Maskot Status & AI Insight -->
    <?php
    // 1. Tentukan Pesan AI Standar (Fallback)
    $ai_emoji = ($debt > 0) ? '🤒' : '😺';
    $ai_message = ($debt > 0) 
        ? "Waduh, maskotmu sakit karena kamu over-budget kemarin. Ingat target beli <strong>{$data['saving_goal_name']}</strong> ya!" 
        : "Bagus! Pertahankan jajan di bawah Rp " . number_format($jatah_jajan_hari_ini, 0, ',', '.') . " hari ini.";

    // 2. AI Membaca Riwayat Kategori Belanja di Database (Dinamis!)
    $camilan_total = 0;
    $kebutuhan_total = 0;
    
    $query_ai = "SELECT category, SUM(amount) as total_kategori FROM dummy_transactions WHERE user_id = $user_id GROUP BY category";
    $result_ai = $conn->query($query_ai);
    
    if ($result_ai) {
        while($row_ai = $result_ai->fetch_assoc()) {
            if ($row_ai['category'] == 'Camilan Malam') {
                $camilan_total = $row_ai['total_kategori'];
            }
            if ($row_ai['category'] == 'Kebutuhan') {
                $kebutuhan_total = $row_ai['total_kategori'];
            }
        }
    }

    // 3. Logika Teguran AI Jika Ada Camilan Malam
    if ($camilan_total > 0 && $debt == 0) {
        $ai_emoji = '💡';
        $ai_message = "Data Syahid Mart menunjukkan pengeluaran kebutuhanmu stabil, tapi jajan <strong>camilan malammu</strong> naik nih. Kurangi jajan malam agar target beli <strong>{$data['saving_goal_name']}</strong> cepat tercapai!";
    }
    ?>

    <!-- TAMPILAN KOTAK AI INSIGHT -->
    <div class="card card-jeda mb-4 border-info border-2 shadow-sm">
        <div class="card-body d-flex align-items-center">
            <div class="display-4 me-3"><?= $avatar_display; ?></div>
            <div>
                <h6 class="fw-bold mb-1 text-info">AI Financial Coach</h6>
                <p class="text-secondary small mb-0">
                    <?= $ai_message; ?>
                </p>
            </div>
        </div>
    </div>

    <!-- GAMIFIKASI: Progress Target Tabungan -->
    <div class="d-flex justify-content-between align-items-end mb-2 mt-4">
        <h6 class="fw-bold mb-0">🎯 Target Tabungan</h6>
        <!-- Tombol untuk membuka Modal Form -->
        <button class="btn btn-sm btn-outline-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#goalModal">
            + Ubah Target
        </button>
    </div>
    <div class="card card-jeda mb-4 border-primary border-2">
        <div class="card-body">
            <div class="d-flex justify-content-between mb-2">
                <span class="fw-bold"><?= $data['saving_goal_name']; ?></span>
                <span class="text-primary fw-bold"><?= $progress_persen; ?>%</span>
            </div>
            <div class="progress mb-2" style="height: 10px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: <?= $progress_persen; ?>%"></div>
            </div>
            <div class="d-flex justify-content-between text-sm text-muted">
                <span>Terkumpul: Rp <?= number_format($terkumpul, 0, ',', '.'); ?></span>
                <span>Target: Rp <?= number_format($target, 0, ',', '.'); ?></span>
            </div>
        </div>
    </div>

    <!-- GAMIFIKASI: Asrama Leaderboard (Dummy) -->
    <h6 class="fw-bold mb-3">🏆 Leaderboard Disiplin Asrama</h6>
    <div class="card card-jeda mb-4">
        <div class="card-body p-0">
            <ul class="list-group list-group-flush rounded-4">
                <li class="list-group-item d-flex justify-content-between align-items-center p-3 bg-light">
                    <div>
                        <span class="me-2">🥇</span> <strong>Siswa Kelas 11 (Anonim)</strong>
                    </div>
                    <span class="badge bg-success rounded-pill">Streak: 14 Hari</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center p-3">
                    <div>
                        <span class="me-2">🥈</span> <strong>Siswa Kelas 12 (Anonim)</strong>
                    </div>
                    <span class="badge bg-success rounded-pill">Streak: 10 Hari</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center p-3 border border-warning shadow-sm">
                    <div>
                        <span class="me-2"><?= ($debt > 0) ? '💀' : '🥉'; ?></span> <strong><?= $data['name']; ?> (Kamu)</strong>
                    </div>
                    <span class="badge bg-warning text-dark rounded-pill">Streak: <?= $data['streak_days']; ?> Hari</span>
                </li>
            </ul>
        </div>
    </div>

    <!-- Riwayat Transaksi Syahid Mart -->
    <h6 class="fw-bold mb-3">Riwayat Syahid Mart Hari Ini</h6>
    <div class="card card-jeda">
        <div class="card-body p-0">
            <ul class="list-group list-group-flush rounded-4">
                <?php while($trx = $trx_result->fetch_assoc()): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center p-3">
                    <div>
                        <h6 class="mb-0"><?= $trx['item_name']; ?></h6>
                        <small class="text-muted"><?= date('H:i', strtotime($trx['time_txn'])); ?> • <?= $trx['category']; ?></small>
                    </div>
                    <span class="fw-bold text-danger">- Rp <?= number_format($trx['amount'], 0, ',', '.'); ?></span>
                </li>
                <?php endwhile; ?>
            </ul>
        </div>
    </div>

</div>

<!-- MODAL FORM: Buat/Ubah Target -->
    <div class="modal fade" id="goalModal" tabindex="-1" aria-labelledby="goalModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="goalModalLabel">Tentukan Target Tabungan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="update_goal.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label text-muted small">Apa yang ingin kamu beli?</label>
                            <input type="text" name="goal_name" class="form-control rounded-3" value="<?= $data['saving_goal_name']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small">Berapa harganya? (Rp)</label>
                            <input type="number" name="target_amount" class="form-control rounded-3" value="<?= $data['target_amount']; ?>" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-muted small">Potongan Tabungan per Hari (Rp)</label>
                            <input type="number" name="daily_save" class="form-control rounded-3" value="<?= $data['daily_auto_save']; ?>" required>
                            <div class="form-text">Maksimal batas jajan harian BSI SMA adalah Rp 25.000. Sisa dari potongan ini akan menjadi Jatah Jajan Amanmu.</div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold py-2">Simpan Target</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Tambahkan Bootstrap JS (Wajib agar Modal bisa muncul) di atas </body> -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- MODAL FORM: Custom Avatar -->
    <div class="modal fade" id="avatarModal" tabindex="-1" aria-labelledby="avatarModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="avatarModalLabel">Pilih Karaktermu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="update_avatar.php" method="POST">
                        <div class="row text-center mb-4">
                            <!-- Pilihan Boy -->
                            <div class="col-6 mb-3">
                                <input type="radio" class="btn-check" name="avatar_base" id="av_boy" value="boy" <?= ($data['avatar_base'] == 'boy') ? 'checked' : ''; ?>>
                                <label class="btn btn-outline-primary w-100 p-3 rounded-4" for="av_boy">
                                    <div class="fs-1">👦</div> <small class="fw-bold">Pelajar Putra</small>
                                </label>
                            </div>
                            <!-- Pilihan Girl -->
                            <div class="col-6 mb-3">
                                <input type="radio" class="btn-check" name="avatar_base" id="av_girl" value="girl" <?= ($data['avatar_base'] == 'girl') ? 'checked' : ''; ?>>
                                <label class="btn btn-outline-primary w-100 p-3 rounded-4" for="av_girl">
                                    <div class="fs-1">👧</div> <small class="fw-bold">Pelajar Putri</small>
                                </label>
                            </div>
                            <!-- Pilihan Cat -->
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="avatar_base" id="av_cat" value="cat" <?= ($data['avatar_base'] == 'cat') ? 'checked' : ''; ?>>
                                <label class="btn btn-outline-primary w-100 p-3 rounded-4" for="av_cat">
                                    <div class="fs-1">🐱</div> <small class="fw-bold">Kucing Emas</small>
                                </label>
                            </div>
                            <!-- Pilihan Robot -->
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="avatar_base" id="av_robot" value="robot" <?= ($data['avatar_base'] == 'robot') ? 'checked' : ''; ?>>
                                <label class="btn btn-outline-primary w-100 p-3 rounded-4" for="av_robot">
                                    <div class="fs-1">🤖</div> <small class="fw-bold">Cyborg</small>
                                </label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold py-2">Simpan Avatar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>    
</body>
</html>