<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['parent_id']) || $_SESSION['role'] != 'parent') {
    header("Location: login.php");
    exit();
}

$parent_id = $_SESSION['parent_id'];
//include 'parent_header.php'; // Mengatur $selected_child_id

// Ambil detail kartu & profil anak aktif
$user_q = $conn->prepare("
    SELECT u.name, u.avatar_base, v.real_balance, v.virtual_debt, v.streak_days 
    FROM users u 
    JOIN virtual_wallets v ON u.id = v.user_id 
    WHERE u.id = ?
");
$user_q->bind_param("i", $selected_child_id);
$user_q->execute();
$child_profile = $user_q->get_result()->fetch_assoc();
$user_q->close();

$c_name = $child_profile['name'] ?? 'Siswa';
$c_balance = $child_profile['real_balance'] ?? 0;
$c_debt = $child_profile['virtual_debt'] ?? 0;
$c_streak = $child_profile['streak_days'] ?? 0;
$c_is_zombie = ($c_debt > 0);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JEDA - Kartu & Profil Anak</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #0f172a; }</style>
</head>
<body class="flex justify-center items-center min-h-screen">

    <div class="w-full max-w-[480px] bg-slate-50 min-h-screen shadow-2xl flex flex-col justify-between relative pb-24">
        
        <?php include 'parent_header.php'; ?>

        <!-- Konten Kartu Digital (Read-Only) -->
        <div class="p-4 flex-1 space-y-4 overflow-y-auto">
            
            <div class="bg-gradient-to-br from-indigo-900 via-slate-900 to-blue-900 text-white p-6 rounded-3xl shadow-xl space-y-6 relative overflow-hidden">
                <div class="flex justify-between items-start">
                    <div>
                        <span class="text-[10px] text-blue-300 font-bold uppercase tracking-wider">BSI Smart Card x JEDA</span>
                        <h2 class="text-lg font-black tracking-wide mt-0.5">STUDENT ID CARD</h2>
                    </div>
                    <span class="text-xs bg-white/20 px-2.5 py-1 rounded-xl font-bold">🔒 Read-Only</span>
                </div>

                <div class="space-y-1">
                    <span class="text-[10px] text-slate-400 block">Nama Lengkap Siswa</span>
                    <h3 class="text-xl font-bold"><?= htmlspecialchars($c_name); ?></h3>
                </div>

                <div class="flex justify-between items-end pt-2 border-t border-white/10 text-xs">
                    <div>
                        <span class="text-[10px] text-slate-400 block">Status Kedisiplinan</span>
                        <span class="font-extrabold <?= $c_is_zombie ? 'text-rose-400' : 'text-emerald-400'; ?>">
                            <?= $c_is_zombie ? '🧟‍♂️ Zombie Mode' : '👼 Angel Mode'; ?>
                        </span>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] text-slate-400 block">Streak Kedisiplinan</span>
                        <span class="font-extrabold text-amber-300">🔥 <?= $c_streak; ?> Hari</span>
                    </div>
                </div>
            </div>

            <div class="bg-white p-4 rounded-3xl shadow-sm border border-slate-100 text-center space-y-2">
                <p class="text-xs text-slate-500">Kartu pintar terintegrasi dengan sistem pemantauan orang tua secara real-time.</p>
                <a href="logout.php" class="inline-block bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold px-4 py-2 rounded-xl text-xs transition">
                    Keluar Portal Orang Tua (Logout)
                </a>
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
            <a href="parent_leaderboard.php?child_id=<?= $selected_child_id; ?>" class="flex flex-col items-center text-slate-400 hover:text-blue-600">
                <span class="text-xl">🏆</span>
                <span class="text-[9px] font-medium mt-1">Ranking</span>
            </a>
            <a href="parent_profile_card.php?child_id=<?= $selected_child_id; ?>" class="flex flex-col items-center text-blue-600">
                <span class="text-xl">💳</span>
                <span class="text-[9px] font-bold mt-1">Profil</span>
            </a>
        </div>

    </div>
</body>
</html>