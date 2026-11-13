<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['parent_id']) || $_SESSION['role'] != 'parent') {
    header("Location: login.php");
    exit();
}

$parent_id = $_SESSION['parent_id'];
//include 'parent_header.php'; // Otomatis mengatur $selected_child_id
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JEDA - Target Tabungan Anak</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #0f172a; }</style>
</head>
<body class="flex justify-center items-center min-h-screen">

    <div class="w-full max-w-[480px] bg-slate-50 min-h-screen shadow-2xl flex flex-col justify-between relative pb-24">
        
        <?php include 'parent_header.php'; ?>

        <!-- Konten Utama (Read-Only) -->
        <div class="p-4 flex-1 space-y-4 overflow-y-auto">
            
            <div class="bg-white p-4 rounded-3xl shadow-sm border border-slate-100 flex justify-between items-center">
                <div>
                    <span class="text-[10px] text-slate-400 font-bold block">Status Pantauan</span>
                    <span class="text-xs font-extrabold text-slate-700">Target Impian & Tabungan Anak</span>
                </div>
                <span class="text-xs bg-amber-50 text-amber-600 font-bold px-2.5 py-1 rounded-xl">Read-Only 👁️</span>
            </div>

            <!-- Daftar Target Anak -->
            <div class="space-y-3">
                <?php
                $goals_q = $conn->query("SELECT * FROM saving_goals WHERE user_id = $selected_child_id ORDER BY id DESC");
                if ($goals_q && $goals_q->num_rows > 0):
                    while($g = $goals_q->fetch_assoc()):
                        $pct = ($g['target_amount'] > 0) ? round(($g['saved_amount'] / $g['target_amount']) * 100) : 0;
                        if($pct > 100) $pct = 100;
                        $daily_saving = ceil(($g['target_amount'] - $g['saved_amount']) / 30);
                ?>
                <div class="bg-white p-4 rounded-3xl shadow-sm border border-slate-100 space-y-3">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="font-bold text-slate-800 text-sm block"><?= htmlspecialchars($g['goal_name']); ?></span>
                            <span class="text-[10px] text-slate-400">Tenggat: <?= date('d M Y', strtotime($g['target_date'])); ?></span>
                        </div>
                        <span class="text-xs font-extrabold text-blue-600 bg-blue-50 px-2.5 py-0.5 rounded-md"><?= $pct; ?>%</span>
                    </div>

                    <div class="space-y-1">
                        <div class="flex justify-between text-[11px]">
                            <span class="text-slate-500">Terkumpul: <b>Rp <?= number_format($g['saved_amount'], 0, ',', '.'); ?></b></span>
                            <span class="text-slate-700 font-bold">Target: Rp <?= number_format($g['target_amount'], 0, ',', '.'); ?></span>
                        </div>
                        <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                            <div class="bg-blue-600 h-full rounded-full" style="width: <?= $pct; ?>%;"></div>
                        </div>
                    </div>

                    <!-- AI Notes / Insight untuk Orang Tua -->
                    <div class="bg-indigo-50/70 border border-indigo-100 p-2.5 rounded-2xl text-[11px] text-indigo-800 flex items-start gap-2">
                        <span>🤖</span>
                        <div>
                            <span class="font-bold block text-indigo-900">AI Recommendation:</span>
                            Anak perlu menyisihkan sekitar <b class="text-indigo-950">Rp <?= number_format($daily_saving, 0, ',', '.'); ?>/hari</b> untuk mencapai target ini.
                        </div>
                    </div>
                </div>
                <?php endwhile; else: ?>
                    <div class="bg-white p-8 rounded-3xl text-center space-y-2 border border-slate-100">
                        <span class="text-2xl">🎯</span>
                        <p class="text-xs text-slate-400">Anak belum memiliki target tabungan aktif.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- BOTTOM NAVIGATION BAR ORANG TUA -->
        <div class="absolute bottom-0 left-0 right-0 bg-white border-t border-slate-100 py-3 px-3 flex justify-around items-center rounded-t-3xl shadow-lg z-50">
            <a href="parent_dashboard.php?child_id=<?= $selected_child_id; ?>" class="flex flex-col items-center text-slate-400 hover:text-blue-600">
                <span class="text-xl">🏠</span>
                <span class="text-[9px] font-medium mt-1">Beranda</span>
            </a>
            <a href="parent_child_analytics.php?child_id=<?= $selected_child_id; ?>" class="flex flex-col items-center text-slate-400 hover:text-blue-600">
                <span class="text-xl">📊</span>
                <span class="text-[9px] font-medium mt-1">Analitik</span>
            </a>
            <a href="parent_manage_goals.php?child_id=<?= $selected_child_id; ?>" class="flex flex-col items-center text-blue-600">
                <span class="text-xl">🎯</span>
                <span class="text-[9px] font-bold mt-1">Target</span>
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
</body>
</html>