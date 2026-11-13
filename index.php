<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit();
}

$user_id =$_SESSION['user_id'];

// Ambil data siswa & virtual wallet
$query =$conn->query("
    SELECT u.name, u.avatar_base, v.real_balance, v.virtual_debt, v.streak_days 
    FROM users u 
    JOIN virtual_wallets v ON u.id = v.user_id 
    WHERE u.id = $user_id
");
$data = ($query && $query->num_rows > 0) ?$query->fetch_assoc() : null;

$name = isset($data['name']) ? $data['name'] : 'Siswa';$balance = isset($data['real_balance']) ?$data['real_balance'] : 0;
$debt = isset($data['virtual_debt']) ?$data['virtual_debt'] : 0;
$streak = isset($data['streak_days']) ?$data['streak_days'] : 0;
$avatar = isset($data['avatar_base']) ?$data['avatar_base'] : 'boy';

$is_zombie = ($debt > 0);

$avatar_emojis = [
    'cat' => $is_zombie ? '😿🧟' : '🐱✨',
    'robot' => $is_zombie ? '🤖🧟' : '🤖💎',
    'boy' => $is_zombie ? '👦🧟' : '👼👦',
    'girl' => $is_zombie ? '👧🧟' : '👼👧',
    'fox' => $is_zombie ? '🦊🧟' : '🦊✨'
];
$display_avatar = isset($avatar_emojis[$avatar]) ?$avatar_emojis[$avatar] : ($is_zombie ? '🧟‍♂️' : '👼');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JEDA - Beranda Siswa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #0f172a; }</style>
</head>
<body class="flex justify-center items-center min-h-screen">

    <div class="w-full max-w-[480px] bg-slate-50 min-h-screen shadow-2xl flex flex-col justify-between relative pb-24">
        
        <!-- HEADER KONSISTEN SISWA -->
        <div class="p-5 bg-white rounded-b-3xl shadow-sm space-y-2">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-[11px] text-slate-400 font-semibold tracking-wide">BSI Smart Card x JEDA</p>
                    <h1 class="text-xl font-bold text-slate-800">Halo, <?= htmlspecialchars($name); ?> 👋</h1>
                </div>
                <a href="profile_card.php" title="Lihat Kartu & Profil" class="w-12 h-12 rounded-2xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-2xl shadow-sm hover:scale-105 transition">
                    <?= $display_avatar; ?>
                </a>
            </div>

            <div>
                <?php if ($is_zombie): ?>
                    <span class="inline-flex items-center gap-1 text-[11px] px-3 py-1 rounded-full font-bold bg-rose-100 text-rose-600 border border-rose-200">
                        <span>🧟‍♂️</span> Zombie Mode (Berutang)
                    </span>
                <?php else: ?>
                    <span class="inline-flex items-center gap-1 text-[11px] px-3 py-1 rounded-full font-bold bg-emerald-100 text-emerald-600 border border-emerald-200">
                        <span>👼</span> Angel Mode (Hemat & Disiplin)
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Konten Utama Beranda -->
        <div class="p-4 flex-1 space-y-4 overflow-y-auto">
            
            <!-- Saldo PSP & Gamifikasi -->
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
                    <span class="underline font-bold">Lunasi Segera</span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Ringkasan Target Tabungan (Tanpa Tombol Tambah/Hapus, Dilengkapi Detail Nominal) -->
            <div class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 space-y-3">
                <div class="flex justify-between items-center">
                    <h3 class="font-bold text-slate-800 text-sm">Target Tabungan 🎯</h3>
                    <a href="manage_goals.php" class="text-xs font-bold text-blue-600 hover:underline flex items-center gap-1">
                    </a>
                </div>

                <div class="space-y-2.5">
                    <?php
                    $goals_q =$conn->query("SELECT * FROM saving_goals WHERE user_id = $user_id LIMIT 2");
                    if ($goals_q &&$goals_q->num_rows > 0):
                        while($g =$goals_q->fetch_assoc()):
                            $saved =$g['saved_amount'];
                            $target =$g['target_amount'];
                            $pct = ($target > 0) ? round(($saved / $target) * 100) : 0;
                            if($pct > 100)$pct = 100;
                            $daily_saving = ceil(($target -$saved) / 30);
                    ?>
                    <div class="bg-slate-50 p-3 rounded-2xl border border-slate-100 space-y-2">
                        <div class="flex justify-between text-xs">
                            <span class="font-bold text-slate-800"><?= htmlspecialchars($g['goal_name']); ?></span>
                            <!-- Menampilkan sudah terkumpul berapa dari berapa -->
                            <span class="font-extrabold text-blue-600">Rp <?= number_format($saved, 0, ',', '.'); ?> / Rp <?= number_format($target, 0, ',', '.'); ?></span>
                        </div>
                        
                        <!-- Barchart Progress -->
                        <div class="space-y-1">
                            <div class="flex justify-between text-[10px] text-slate-400 font-medium">
                                <span>Progress</span>
                                <span><?= $pct; ?>%</span>
                            </div>
                            <div class="w-full bg-slate-200 h-2 rounded-full overflow-hidden">
                                <div class="bg-blue-600 h-full rounded-full" style="width: <?= $pct; ?>%;"></div>
                            </div>
                        </div>

                        <!-- AI Notes -->
                        <div class="bg-indigo-50/70 border border-indigo-100 p-2 rounded-xl text-[10px] text-indigo-700 flex items-start gap-1.5">
                            <span class="text-xs">🤖</span>
                            <div>
                                <span class="font-bold">AI Note:</span> Sisihkan <b class="text-indigo-900">Rp <?= number_format($daily_saving, 0, ',', '.'); ?>/hari</b> agar target tercapai.
                            </div>
                        </div>
                    </div>
                    <?php endwhile; else: ?>
                        <p class="text-xs text-slate-400 text-center py-2">Belum ada target tabungan aktif.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Aktivitas Terakhir -->
            <div class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 space-y-3">
                <h3 class="font-bold text-slate-800 text-sm">Aktivitas Terakhir</h3>
                <div class="space-y-2">
                    <?php
                    $txn_q =$conn->query("SELECT * FROM dummy_transactions WHERE user_id = $user_id ORDER BY time_txn DESC LIMIT 3");
                    if ($txn_q &&$txn_q->num_rows > 0):
                        while($t =$txn_q->fetch_assoc()):
                    ?>
                    <div class="flex justify-between items-center bg-slate-50 p-3 rounded-2xl border border-slate-100 text-xs">
                        <div>
                            <span class="font-bold text-slate-800 block"><?= htmlspecialchars($t['item_name']); ?></span>
                            <span class="text-[10px] text-slate-400"><?= htmlspecialchars($t['category']); ?> • <?= date('d M H:i', strtotime($t['time_txn'])); ?></span>
                        </div>
                        <span class="font-extrabold text-rose-600">- Rp <?= number_format($t['amount'], 0, ',', '.'); ?></span>
                    </div>
                    <?php endwhile; else: ?>
                        <p class="text-xs text-slate-400 text-center py-2">Belum ada transaksi.</p>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- BOTTOM NAVIGATION BAR (5 Menu Konsisten) -->
<div class="absolute bottom-0 left-0 right-0 bg-white border-t border-slate-100 py-3 px-3 flex justify-around items-center rounded-t-3xl shadow-lg z-50">
    <a href="index.php" class="flex flex-col items-center text-slate-400 hover:text-blue-600">
        <span class="text-xl">🏠</span>
        <span class="text-[9px] font-medium mt-1">Beranda</span>
    </a>
    <a href="analytics.php" class="flex flex-col items-center text-slate-400 hover:text-blue-600">
        <span class="text-xl">📊</span>
        <span class="text-[9px] font-medium mt-1">Analitik</span>
    </a>
    <a href="manage_goals.php" class="flex flex-col items-center text-slate-400 hover:text-blue-600">
        <span class="text-xl">🎯</span>
        <span class="text-[9px] font-medium mt-1">Target</span>
    </a>
    <a href="leaderboard.php" class="flex flex-col items-center text-slate-400 hover:text-blue-600">
        <span class="text-xl">🏆</span>
        <span class="text-[9px] font-medium mt-1">Ranking</span>
    </a>
    <a href="profile_card.php" class="flex flex-col items-center text-slate-400 hover:text-blue-600">
        <span class="text-xl">💳</span>
        <span class="text-[9px] font-medium mt-1">Profil</span>
    </a>
</div>

    </div>
</body>
</html>