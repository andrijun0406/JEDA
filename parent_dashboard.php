<?php
session_start();
include 'koneksi.php';

// Pastikan hanya orang tua yang bisa akses
if (!isset($_SESSION['parent_id']) || $_SESSION['role'] != 'parent') {
    header("Location: login.php");
    exit();
}

$parent_id = $_SESSION['parent_id'];

// Ambil data orang tua
$ortu_query = $conn->query("SELECT name FROM parents WHERE id = $parent_id");
$ortu_data = $ortu_query->fetch_assoc();

// Ambil data anak-anak yang terhubung dengan orang tua ini
$query_anak = "SELECT u.id as anak_id, u.name, u.avatar_base, u.grade, 
               v.real_balance, v.saving_goal_name, v.target_amount, v.saved_amount, v.virtual_debt, v.streak_days 
               FROM users u 
               JOIN virtual_wallets v ON u.id = v.user_id 
               WHERE u.parent_id = $parent_id";
$result_anak = $conn->query($query_anak);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Orang Tua - JEDA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body { background-color: #f4f6f9; }</style>
</head>
<body>

<div class="container py-4" style="max-width: 480px;">
    <!-- Header Ortu -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="mb-0 fw-bold">Halo, <?= $ortu_data['name']; ?> 👨‍👩‍👦</h5>
            <small class="text-muted">Pantau Finansial Anak Anda</small>
        </div>
        <a href="logout.php" class="btn btn-sm btn-outline-danger rounded-pill fw-bold">Keluar</a>
    </div>

    <h6 class="fw-bold mb-3">Daftar Anak</h6>

    <?php while($anak = $result_anak->fetch_assoc()): 
        $progress = ($anak['target_amount'] > 0) ? round(($anak['saved_amount'] / $anak['target_amount']) * 100) : 0;
        $is_zombie = ($anak['virtual_debt'] > 0);
        $avatar = ($is_zombie) ? '🧟‍♂️' : '👼';
    ?>
    
    <!-- Kartu Anak -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center">
                    <div class="display-5 me-3"><?= $avatar; ?></div>
                    <div>
                        <h6 class="fw-bold mb-0"><?= $anak['name']; ?></h6>
                        <small class="text-muted">Kartu PSP: Rp <?= number_format($anak['real_balance'], 0, ',', '.'); ?></small>
                    </div>
                </div>
                <span class="badge <?= ($is_zombie) ? 'bg-danger' : 'bg-success'; ?> rounded-pill">
                    <?= ($is_zombie) ? 'Ada Utang' : 'Disiplin'; ?>
                </span>
            </div>

            <!-- Progress Target -->
            <small class="fw-bold text-primary">Target: <?= $anak['saving_goal_name']; ?></small>
            <div class="progress mt-1 mb-2" style="height: 8px;">
                <div class="progress-bar bg-primary" style="width: <?= $progress; ?>%"></div>
            </div>
            
            <div class="d-flex justify-content-between mb-3 text-sm">
                <small class="text-muted">Terkumpul: Rp <?= number_format($anak['saved_amount'], 0, ',', '.'); ?></small>
                <small class="text-warning fw-bold">🔥 Streak: <?= $anak['streak_days']; ?> Hari</small>
            </div>

            <!-- Tombol Apresiasi memanggil Modal -->
            <button class="btn <?= ($is_zombie) ? 'btn-secondary' : 'btn-success'; ?> w-100 rounded-pill fw-bold" 
                <?= ($is_zombie) ? 'disabled' : 'data-bs-toggle="modal" data-bs-target="#topupModal' . $anak['anak_id'] . '"'; ?>>
                <?= ($is_zombie) ? '🔒 Apresiasi Terkunci (Anak Berutang)' : '🎁 Beri Apresiasi (Top Up PSP)'; ?>
            </button>
        </div>
    </div>

    <!-- Modal Top Up (Spesifik per Anak) -->
    <div class="modal fade" id="topupModal<?= $anak['anak_id']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Apresiasi untuk <?= $anak['name']; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="topup_action.php" method="POST">
                        <input type="hidden" name="anak_id" value="<?= $anak['anak_id']; ?>">
                        <div class="mb-3">
                            <label class="form-label text-muted small">Nominal Top Up (Rp)</label>
                            <input type="number" name="amount" class="form-control rounded-3" placeholder="Contoh: 50000" required>
                        </div>
                        <button type="submit" class="btn btn-success w-100 rounded-pill fw-bold py-2">Kirim ke Kartu PSP</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php endwhile; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>