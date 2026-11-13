<?php
session_start();
include 'koneksi.php';

// Validasi sesi siswa
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];

// Status anonim dikunci otomatis aktif (true) untuk siswa
$is_anonymous = true;

// Ambil data peringkat siswa berdasarkan streak dan kedisiplinan
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
    <title>JEDA - Leaderboard Siswa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #0f172a; }</style>
</head>
<body class="flex justify-center items-center min-h-screen">

    <div class="w-full max-w-[480px] bg-slate-50 min-h-screen shadow-2xl flex flex-col justify-between relative pb-24">
        
        <!-- PANGGIL HEADER KONSISTEN SISWA -->
        <?php include 'student_header.php'; ?>

        <!-- Konten Utama Leaderboard -->
        <div class="p-4 flex-1 space-y-4 overflow-y-auto">
            
            <div class="bg-gradient-to-br from-amber-500 to-orange-600 text-white p-5 rounded-3xl shadow-md space-y-2">
                <span class="text-xs text-amber-100 font-medium">Papan Peringkat Kedisiplinan 🏆</span>
                <h2 class="text-xl font-extrabold">Angel & Zombie Ranking</h2>
                <p class="text-[11px] text-amber-100">Nama siswa disamarkan secara otomatis untuk menjaga privasi.</p>
            </div>

            <!-- Daftar Leaderboard -->
            <div class="bg-white p-4 rounded-3xl shadow-sm border border-slate-100 space-y-2">
                <div class="flex justify-between items-center px-2 mb-2">
                    <h3 class="font-bold text-slate-800 text-xs">Peringkat Siswa</h3>
                    <span class="text-[10px] text-slate-400 font-bold">🔒 Mode Privasi Aktif</span>
                </div>
                
                <?php 
                $rank = 1;
                if ($leaderboard_query && $leaderboard_query->num_rows > 0):
                    while($row = $leaderboard_query->fetch_assoc()):
                        $is_me = ($row['id'] == $current_user_id);
                        $is_zombie_row = ($row['virtual_debt'] > 0);
                        
                        // Logika Anonimisasi Otomatis (Tampilkan nama asli hanya jika itu akun milik sendiri)
                        if ($is_me) {
                            $display_name = $row['name'] . ' (Kamu)';
                        } else {
                            $display_name = "Siswa Rahasia #" . $row['id'];
                        }
                        
                        // Badge gaya peringkat
                        $rank_bg = 'bg-slate-100 text-slate-700';
                        if($rank == 1) $rank_bg = 'bg-amber-100 text-amber-700 font-black';
                        if($rank == 2) $rank_bg = 'bg-slate-200 text-slate-700 font-black';
                        if($rank == 3) $rank_bg = 'bg-orange-100 text-orange-700 font-black';
                ?>
                <div class="flex items-center justify-between p-3 rounded-2xl border <?= $is_me ? 'bg-blue-50/70 border-blue-200' : 'bg-slate-50 border-slate-100'; ?>">
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
            <a href="leaderboard.php" class="flex flex-col items-center text-blue-600">
                <span class="text-xl">🏆</span>
                <span class="text-[9px] font-bold mt-1">Ranking</span>
            </a>
            <a href="profile_card.php" class="flex flex-col items-center text-slate-400 hover:text-blue-600">
                <span class="text-xl">💳</span>
                <span class="text-[9px] font-medium mt-1">Profil</span>
            </a>
        </div>

    </div>
</body>
</html>