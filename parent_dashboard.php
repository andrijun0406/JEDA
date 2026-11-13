<?php
session_start();
include 'koneksi.php';

// Pastikan hanya orang tua yang login
if (!isset($_SESSION['parent_id']) || $_SESSION['role'] != 'parent') {
    header("Location: login.php");
    exit();
}

$parent_id = $_SESSION['parent_id'];

// Ambil daftar anak dari orang tua yang login
$children_q = $conn->prepare("SELECT id, name, avatar_base FROM users WHERE parent_id = ? AND role = 'student'");
$children_q->bind_param("i", $parent_id);
$children_q->execute();
$children_res = $children_q->get_result();

$children = [];
while ($c = $children_res->fetch_assoc()) {
    $children[] = $c;
}
$children_q->close();

// Tentukan anak yang sedang dipilih
$selected_child_id = isset($_GET['child_id']) ? intval($_GET['child_id']) : ($children[0]['id'] ?? 0);

$active_child = null;
foreach ($children as $child) {
    if ($child['id'] == $selected_child_id) {
        $active_child = $child;
        break;
    }
}
if (!$active_child && !empty($children)) {
    $active_child = $children[0];
    $selected_child_id = $active_child['id'];
}

// Proses Kirim Apresiasi atau Top-Up oleh Orang Tua
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $target_child_id = intval($_POST['target_child_id']);
    $amount = floatval($_POST['amount']);
    $note = trim($_POST['note']);

    if ($amount > 0) {
        if ($_POST['action'] == 'apresiasi') {
            // Tambah saldo & catat sebagai apresiasi
            $conn->query("UPDATE virtual_wallets SET real_balance = real_balance + $amount WHERE user_id = $target_child_id");
            $conn->query("INSERT INTO dummy_transactions (user_id, item_name, category, amount, time_txn) VALUES ($target_child_id, '🎁 Apresiasi Orang Tua: $note', 'Bonus', -$amount, NOW())");
            $success_msg = "Berhasil mengirim apresiasi sebesar Rp " . number_format($amount, 0, ',', '.') . "!";
        } elseif ($_POST['action'] == 'topup') {
            // Top-up saldo PSP anak
            $conn->query("UPDATE virtual_wallets SET real_balance = real_balance + $amount WHERE user_id = $target_child_id");
            $conn->query("INSERT INTO dummy_transactions (user_id, item_name, category, amount, time_txn) VALUES ($target_child_id, '💳 Top-Up Saldo PSP', 'TopUp', -$amount, NOW())");
            $success_msg = "Berhasil top-up saldo sebesar Rp " . number_format($amount, 0, ',', '.') . "!";
        }
    }
}

// Ambil data dompet anak aktif
$w_q = $conn->prepare("SELECT real_balance, virtual_debt, streak_days FROM virtual_wallets WHERE user_id = ?");
$w_q->bind_param("i", $selected_child_id);
$w_q->execute();
$wallet = $w_q->get_result()->fetch_assoc();
$w_q->close();

$balance = $wallet['real_balance'] ?? 0;
$debt = $wallet['virtual_debt'] ?? 0;
$streak = $wallet['streak_days'] ?? 0;
$is_zombie = ($debt > 0);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JEDA - Dasbor Orang Tua</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #0f172a; }</style>
</head>
<body class="flex justify-center items-center min-h-screen">

    <div class="w-full max-w-[480px] bg-slate-50 min-h-screen shadow-2xl flex flex-col justify-between relative pb-24">
        
        <!-- HEADER KONSISTEN ORANG TUA -->
        <?php include 'parent_header.php'; ?>

        <!-- Konten Utama Dasbor Orang Tua -->
        <div class="p-4 flex-1 space-y-4 overflow-y-auto">
            
            <?php if (isset($success_msg)): ?>
                <div class="bg-emerald-100 border border-emerald-200 text-emerald-700 px-4 py-2.5 rounded-2xl text-xs font-bold">
                    ✨ <?= $success_msg; ?>
                </div>
            <?php endif; ?>

            <!-- Ringkasan Keuangan Anak -->
            <div class="bg-gradient-to-br from-blue-600 to-indigo-700 text-white p-5 rounded-3xl shadow-lg space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-xs text-blue-100 font-medium">Saldo PSP Utama</span>
                    <span class="text-xs bg-white/20 px-2.5 py-1 rounded-xl font-bold">🔥 Streak <?= $streak; ?> Hari</span>
                </div>
                <div>
                    <h2 class="text-3xl font-black">Rp <?= number_format($balance, 0, ',', '.'); ?></h2>
                </div>
                <?php if ($is_zombie): ?>
                <div class="bg-rose-500/30 border border-rose-400/30 p-2.5 rounded-2xl text-[11px] text-rose-100 flex justify-between items-center">
                    <span>Virtual Debt: <b>Rp <?= number_format($debt, 0, ',', '.'); ?></b></span>
                    <span class="font-bold">Perlu Pelunasan</span>
                </div>
                <?php endif; ?>
            </div>

            <!-- TOMBOL AKSI CEPAT: APRESIASI & TOP-UP (Non-Aktif jika Zombie Mode) -->
            <div class="grid grid-cols-2 gap-3">
                <?php if ($is_zombie): ?>
                    <!-- Tombol Apresiasi Dikunci / Disabled karena Zombie Mode -->
                    <button disabled title="Anak sedang dalam Zombie Mode. Lunasi utang terlebih dahulu." class="bg-slate-300 text-slate-500 p-3.5 rounded-2xl text-xs font-bold cursor-not-allowed flex items-center justify-center gap-2 opacity-75">
                        <span class="text-base">🔒</span>
                        <span>Apresiasi Terkunci</span>
                    </button>
                <?php else: ?>
                    <!-- Tombol Apresiasi Aktif -->
                    <button onclick="openModal('apresiasi')" class="bg-emerald-600 hover:bg-emerald-700 text-white p-3.5 rounded-2xl text-xs font-bold transition shadow-sm flex items-center justify-center gap-2">
                        <span class="text-base">🎁</span>
                        <span>Kirim Apresiasi</span>
                    </button>
                <?php endif; ?>

                <!-- Tombol Top-Up Saldo Selalu Aktif -->
                <button onclick="openModal('topup')" class="bg-blue-600 hover:bg-blue-700 text-white p-3.5 rounded-2xl text-xs font-bold transition shadow-sm flex items-center justify-center gap-2">
                    <span class="text-base">💳</span>
                    <span>Top-Up Saldo</span>
                </button>
            </div>

            <!-- Target Tabungan Anak (Read-Only) -->
            <div class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 space-y-3">
                <div class="flex justify-between items-center">
                    <h3 class="font-bold text-slate-800 text-sm">Target Tabungan Anak 🎯</h3>
                    <a href="parent_manage_goals.php?child_id=<?= $selected_child_id; ?>" class="text-xs font-bold text-blue-600 hover:underline">Lihat Semua</a>
                </div>
                <div class="space-y-2">
                    <?php
                    $goals_q = $conn->query("SELECT * FROM saving_goals WHERE user_id = $selected_child_id LIMIT 2");
                    if ($goals_q && $goals_q->num_rows > 0):
                        while($g = $goals_q->fetch_assoc()):
                            $pct = ($g['target_amount'] > 0) ? round(($g['saved_amount'] / $g['target_amount']) * 100) : 0;
                            if($pct > 100) $pct = 100;
                    ?>
                    <div class="bg-slate-50 p-3 rounded-2xl border border-slate-100 space-y-1.5">
                        <div class="flex justify-between text-xs">
                            <span class="font-semibold text-slate-700"><?= htmlspecialchars($g['goal_name']); ?></span>
                            <span class="font-bold text-blue-600"><?= $pct; ?>%</span>
                        </div>
                        <div class="w-full bg-slate-200 h-2 rounded-full overflow-hidden">
                            <div class="bg-blue-600 h-full rounded-full" style="width: <?= $pct; ?>%;"></div>
                        </div>
                    </div>
                    <?php endwhile; else: ?>
                        <p class="text-xs text-slate-400 text-center py-2">Anak belum memiliki target tabungan.</p>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- MODAL FORM APRESIASI / TOP-UP -->
        <div id="actionModal" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
            <div class="bg-white w-full max-w-[360px] p-5 rounded-3xl space-y-4 shadow-2xl">
                <div class="flex justify-between items-center border-b border-slate-100 pb-2">
                    <h3 id="modalTitle" class="font-bold text-slate-800 text-sm">Form Aksi</h3>
                    <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600 text-xs font-bold">✕ Tutup</button>
                </div>
                <form method="POST" action="parent_dashboard.php?child_id=<?= $selected_child_id; ?>" class="space-y-3">
                    <input type="hidden" name="target_child_id" value="<?= $selected_child_id; ?>">
                    <input type="hidden" id="actionType" name="action" value="">
                    
                    <div>
                        <label class="text-[10px] text-slate-400 font-semibold block mb-1">Nominal (Rp)</label>
                        <input type="number" name="amount" placeholder="Contoh: 50000" required class="w-full bg-slate-50 border border-slate-200 text-xs rounded-xl px-3 py-2.5 text-slate-700 focus:outline-none focus:border-blue-500">
                    </div>

                    <div id="noteContainer">
                        <label class="text-[10px] text-slate-400 font-semibold block mb-1">Pesan Apresiasi / Catatan</label>
                        <input type="text" name="note" placeholder="Contoh: Karena rajin belajar & sholat" class="w-full bg-slate-50 border border-slate-200 text-xs rounded-xl px-3 py-2.5 text-slate-700 focus:outline-none focus:border-blue-500">
                    </div>

                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 rounded-xl text-xs transition shadow-sm">Kirim Sekarang</button>
                </form>
            </div>
        </div>

        <!-- BOTTOM NAVIGATION BAR ORANG TUA (5 Menu Konsisten) -->
        <div class="absolute bottom-0 left-0 right-0 bg-white border-t border-slate-100 py-3 px-3 flex justify-around items-center rounded-t-3xl shadow-lg z-50">
            <a href="parent_dashboard.php?child_id=<?= $selected_child_id; ?>" class="flex flex-col items-center text-blue-600">
                <span class="text-xl">🏠</span>
                <span class="text-[9px] font-bold mt-1">Beranda</span>
            </a>
            <a href="parent_child_analytics.php?child_id=<?= $selected_child_id; ?>" class="flex flex-col items-center text-slate-400 hover:text-blue-600">
                <span class="text-xl">📊</span>
                <span class="text-[9px] font-medium mt-1">Analitik</span>
            </a>
            <a href="parent_manage_goals.php?child_id=<?= $selected_child_id; ?>" class="flex flex-col items-center text-slate-400 hover:text-blue-600">
                <span class="text-xl">🎯</span>
                <span class="text-[9px] font-medium mt-1">Target</span>
            </a>
            <a href="parent_leaderboard.php?child_id=<?= $selected_child_id; ?>" class="flex flex-col items-center text-slate-400 hover:text-blue-600">
                <span class="text-xl">🏆</span>
                <span class="text-[9px] font-medium mt-1">Ranking</span>
            </a>
            <a href="parent_profile_card.php?child_id=<?= $selected_child_id; ?>" class="flex flex-col items-center text-slate-400 hover:text-blue-600">
                <span class="text-xl">💳</span>
                <span class="text-[9px] font-medium mt-1">Profil</span>
            </a>
        </div>

    </div>

    <!-- Script Modal JavaScript -->
    <script>
        function openModal(type) {
            const modal = document.getElementById('actionModal');
            const title = document.getElementById('modalTitle');
            const actionType = document.getElementById('actionType');
            const noteContainer = document.getElementById('noteContainer');

            modal.classList.remove('hidden');
            actionType.value = type;

            if (type === 'apresiasi') {
                title.innerText = '🎁 Kirim Apresiasi / Bonus';
                noteContainer.style.display = 'block';
            } else {
                title.innerText = '💳 Top-Up Saldo PSP Anak';
                noteContainer.style.display = 'none';
            }
        }

        function closeModal() {
            document.getElementById('actionModal').classList.add('hidden');
        }
    </script>
</body>
</html>