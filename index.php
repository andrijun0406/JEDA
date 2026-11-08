<?php
include 'koneksi.php';

// Ambil data user ID 1 (Budi Santoso) sebagai contoh prototipe
$user_id = 1;

$query = "SELECT u.*, v.* FROM users u 
          JOIN virtual_wallets v ON u.id = v.user_id 
          WHERE u.id = $user_id";
$result = $conn->query($query);
$data = $result->fetch_assoc();

// Kalkulasi Logika JEDA
$hard_limit = $data['daily_hard_limit']; // Rp 25.000 (Aturan SMA)
$auto_save = $data['daily_auto_save'];   // Rp 10.000 (Target Tabungan)
$debt = $data['virtual_debt'];           // Rp 5.000 (Utang jajan kemarin)

// Menghitung Soft Limit (Jatah Jajan Aman Hari Ini)
$soft_limit_awal = $hard_limit - $auto_save; // 25.000 - 10.000 = 15.000
$jatah_jajan_hari_ini = $soft_limit_awal - $debt; // 15.000 - 5.000 = 10.000

// Ambil riwayat belanja hari ini dari dummy Syahid Mart
$query_trx = "SELECT * FROM dummy_transactions WHERE user_id = $user_id ORDER BY time_txn DESC";
$trx_result = $conn->query($query_trx);
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
        <div>
            <h5 class="mb-0 fw-bold">Hai, <?= $data['name']; ?> 👋</h5>
            <small class="text-muted"><?= $data['grade']; ?> | ID: <?= $data['id_psp']; ?></small>
        </div>
        <div class="text-end">
            <span class="badge bg-warning text-dark border border-dark rounded-pill py-2 px-3">
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
    <div class="card card-jeda mb-4">
        <div class="card-body d-flex align-items-center">
            <div class="fs-1 me-3">
                <?= ($debt > 0) ? '🤒' : '😺'; ?>
            </div>
            <div>
                <h6 class="fw-bold mb-1">AI Financial Coach</h6>
                <p class="text-muted small mb-0">
                    <?= ($debt > 0) 
                        ? "Waduh, maskotmu sakit karena kamu over-budget kemarin. Ingat target beli {$data['saving_goal_name']} ya!" 
                        : "Bagus! Pertahankan jajan di bawah Rp " . number_format($jatah_jajan_hari_ini, 0, ',', '.') . " hari ini."; ?>
                </p>
            </div>
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

</body>
</html>