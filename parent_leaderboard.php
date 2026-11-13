<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['parent_id']) || $_SESSION['role'] != 'parent') {
    header("Location: login.php");
    exit();
}

$parent_id = $_SESSION['parent_id'];
//include 'parent_header.php'; // Mengatur $selected_child_id

// Status anonim dapat ditoggle oleh orang tua via GET
$is_anonymous = isset($_GET['anon']) ? ($_GET['anon'] == '1') : true;

$leaderboard_query = $conn->query("
    SELECT u.id, u.name, u.avatar_base, v.streak_days, v.virtual_debt 
    FROM users u 
    JOIN virtual_wallets v ON u.id = v.user_id 
    WHERE u.role = 'student'
    ORDER BY v.streak_days DESC, v.virtual_debt ASC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JEDA - Papan Peringkat (Portal Orang Tua)</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #0f172a; }</style>
</head>
<body class="flex justify-center items-center min-h-screen">

    <div class="w-full max-w-[480px] bg-slate-50 min-h-screen shadow-2xl flex flex-col justify-between relative pb-24">
        
        <?php include 'parent_header.php'; ?>

        <div class="p-4 flex-1 space-y-4 overflow-y-auto">
            
            <div class="bg-gradient-to-br from-amber-500 to-orange-600 text-white p-5 rounded-3xl shadow-md space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-xs text-amber-100 font-medium">Papan Peringkat Kedisiplinan 🏆</span>
                    <!-- Tombol Toggle Privasi untuk Orang Tua -->
                    <a href="parent_leaderboard.php?child_id=<?= $selected_child_id; ?>&anon=<?= $is_anonymous ? '0' : '1'; ?>" class="text-[10px] bg-white/20 hover:bg-white/30 text-white font-bold px-2.5 py-1 rounded-xl transition">
                        <?= $is_anonymous ? '🙈 Mode Anonim' : '👁️ Mode Publik'; ?>
                    </a>
                </div>
                <h2 class="text-xl font-extrabold">Angel & Zombie Ranking</h2>
                <p class="text-[11px] text-amber-100">Orang tua dapat meninjau peringkat kedisiplinan dan mode privasi.</p>
            </div>

            <div class="bg-white p-4 rounded-3xl shadow-sm border border-slate-100 space-y-2">
                <div class="flex justify-between items-center px-2 mb-2">
                    <h3 class="font-bold text-slate-800 text-xs">Peringkat Siswa</h3>
                    <span class="text-[10px] text-slate-400 font-bold"><?= $is_anonymous ? 'Privasi Disamarkan' : 'Tampil Publik'; ?></span>
                </div>
                
                <?php 
                $rank = 1;
                if ($leaderboard_query && $leaderboard_query->num_rows > 0):
                    while($row = $leaderboard_query->fetch_assoc()):
                        $is_target_child = ($row['id'] == $selected_child_id);
                        $is_zombie_row = ($row['virtual_debt'] > 0);
                        
                        // Logika Tampilan Nama Berdasarkan Toggle Orang Tua
                        if (!$is_anonymous || $is_target_child) {
                            $display_name = $row['name'] . ($is_target_child ? ' (Anak Anda)' : '');
                        } else {
                            $display_name = "Siswa Rahasia #" . $row['id'];
                        }
                        
                        $rank_bg = 'bg-slate-100 text-slate-700';
                        if($rank == 1) $rank_bg = 'bg-amber-100 text-amber-700 font-black';
                        if($rank == 2) $rank_bg = 'bg-slate-200 text-slate-700 font-black';
                        if($rank == 3) $rank_bg = 'bg-orange-100 text-orange-700 font-black';
                ?>
                <div class="flex items-center justify-between p-3 rounded-2xl border <?= $is_target_child ? 'bg-blue-50/70 border-blue-200' : 'bg-slate-50 border-slate-100'; ?>">
                    <div class="flex items-center gap-3">
                        <div class="w-7 h-7 rounded-xl flex items-center justify-center text-xs font-bold <?= $rank_bg; ?>">
                            <?= $rank; ?>
                        </div>
                        <div>
                            <span class="font-bold text-xs text-slate-800 block">
                                <?= htmlspecialchars($display_name); ?>
                            </span>
                            <span class="text-[10px] font-bold <?= $is_zombie_row ? 'text-rose-500' : 'text-emerald-600'; ?>">
                                <?= $is_zombie_row ? '🧟‍♂️ Zombie' : '👼 Angel'; ?>
                            </span>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="text-xs font-extrabold text-slate-700">🔥 <?= $row['streak_days']; ?> Hari</span>
                    </div>
                </div>
                <?php 
                        $rank++;
                    endwhile; 
                endif; 
                ?>
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
            <a href="parent_manage_goals.php?child_id=<?= $selected_child_id; ?>" class="flex flex-col items-center text-slate-400 hover:text-blue-600">
                <span class="text-xl">🎯</span>
                <span class="text-[9px] font-medium mt-1">Target</span>
            </a>
            <a href="parent_leaderboard.php?child_id=<?= $selected_child_id; ?>" class="flex flex-col items-center text-blue-600">
                <span class="text-xl">🏆</span>
                <span class="text-[9px] font-bold mt-1">Ranking</span>
            </a>
            <a href="parent_profile_card.php?child_id=<?= $selected_child_id; ?>" class="flex flex-col items-center text-slate-400 hover:text-blue-600">
                <span class="text-xl">💳</span>
                <span class="text-[9px] font-medium mt-1">Profil</span>
            </a>
        </div>

    </div>
</body>
</html>