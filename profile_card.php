<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$query_user = $conn->query("SELECT u.*, v.* FROM users u JOIN virtual_wallets v ON u.id = v.user_id WHERE u.id = $user_id");
$data = $query_user->fetch_assoc();

if (!$data) {
    echo "Data pengguna tidak ditemukan.";
    exit();
}

$user_name = isset($data['name']) ? $data['name'] : 'Siswa JEDA';
$user_role = isset($data['role']) ? $data['role'] : 'student';
$user_username = isset($data['username']) ? $data['username'] : '-';
$avatar_base = isset($data['avatar_base']) ? $data['avatar_base'] : 'boy';

$virtual_debt = isset($data['virtual_debt']) ? $data['virtual_debt'] : 0;
$is_zombie = ($virtual_debt > 0);
$avatar_status = $is_zombie ? '🧟‍♂️ Zombie Mode (Boros)' : '👼 Angel Mode (Hemat)';

$avatar_emojis = [
    'cat' => $is_zombie ? '😿🧟' : '🐱✨',
    'robot' => $is_zombie ? '🤖⚡' : '🤖💎',
    'boy' => $is_zombie ? '👦🧟' : '👼👦',
    'girl' => $is_zombie ? '👧🧟' : '👼👧',
    'fox' => $is_zombie ? '🦊🧟' : '🦊✨'
];
$display_avatar = isset($avatar_emojis[$avatar_base]) ? $avatar_emojis[$avatar_base] : ($is_zombie ? '🧟‍♂️' : '👼');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JEDA - Kartu Pelajar & Profil</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #0f172a; }</style>
</head>
<body class="flex justify-center items-center min-h-screen">

    <div class="w-full max-w-[480px] bg-slate-50 min-h-screen shadow-2xl flex flex-col justify-between relative pb-24">
        
        <!-- Header -->
        <div class="p-5 bg-white rounded-b-3xl shadow-sm">
            <h1 class="text-xl font-bold text-slate-800">Kartu & Profil 💳</h1>
            <p class="text-xs text-slate-400 font-medium">Identitas digital & informasi akun siswa</p>
        </div>

        <!-- Konten Utama -->
        <div class="p-4 flex-1 space-y-4 overflow-y-auto">
            
            <!-- Kartu Digital Siswa -->
            <div class="bg-gradient-to-tr from-blue-700 via-indigo-700 to-blue-800 text-white p-6 rounded-3xl shadow-lg relative overflow-hidden space-y-6">
                <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
                
                <div class="flex justify-between items-start">
                    <div>
                        <span class="text-[10px] uppercase tracking-widest text-blue-200 font-bold">BSI Smart Card x JEDA</span>
                        <h3 class="text-lg font-extrabold mt-0.5">SMA Asy-Syahid</h3>
                    </div>
                    <span class="text-2xl">💳</span>
                </div>

                <div class="space-y-1">
                    <p class="text-xs text-blue-200 font-medium">Nama Siswa</p>
                    <h2 class="text-xl font-bold tracking-wide"><?= htmlspecialchars($user_name); ?></h2>
                    <p class="text-xs text-blue-100 font-mono">ID Pengguna: #JEDA-<?= str_pad($user_id, 4, '0', STR_PAD_LEFT); ?></p>
                </div>

                <div class="flex justify-between items-end pt-2 border-t border-white/15">
                    <div>
                        <span class="text-[10px] text-blue-200 block font-medium">Status Akun</span>
                        <span class="text-xs font-bold text-emerald-300">Aktif & Terverifikasi</span>
                    </div>
                    <div class="bg-white/20 px-3 py-1 rounded-xl text-xs font-bold backdrop-blur-sm">
                        <?= $is_zombie ? 'Zombie' : 'Angel'; ?> Mode
                    </div>
                </div>
            </div>

            <!-- Avatar Gamifikasi Duolingo-style -->
            <div class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-3xl shadow-md <?= $is_zombie ? 'bg-rose-100 border-2 border-rose-400 animate-pulse' : 'bg-emerald-100 border-2 border-emerald-400'; ?>">
                    <?= $display_avatar; ?>
                </div>
                <div>
                    <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-md <?= $is_zombie ? 'bg-rose-100 text-rose-600' : 'bg-emerald-100 text-emerald-600'; ?>">
                        <?= $is_zombie ? 'Status: Zombie Alert ⚠️' : 'Status: Angel Streak ✨'; ?>
                    </span>
                    <h3 class="font-bold text-slate-800 text-sm mt-1"><?= $is_zombie ? 'Wah, kamu sedang boros!' : 'Hebat, pertahankan disiplinmu!'; ?></h3>
                    <p class="text-[11px] text-slate-400"><?= $is_zombie ? 'Lunasi utang virtual untuk memulihkan avatar.' : 'Avatar bersinar terang karena tabunganmu aman.'; ?></p>
                </div>
            </div>

            <!-- Form Kustomisasi Avatar -->
            <div class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 space-y-3">
                <h4 class="font-bold text-slate-800 text-sm">Kustomisasi Avatar 🎨</h4>
                <p class="text-xs text-slate-400">Pilih karakter dasar. Ekspresi berubah otomatis menjadi Angel atau Zombie!</p>
                
                <form action="update_avatar.php" method="POST" class="grid grid-cols-5 gap-2 pt-1">
                    <?php 
                    $avatars = ['boy' => '👦', 'girl' => '👧', 'cat' => '🐱', 'robot' => '🤖', 'fox' => '🦊'];
                    foreach($avatars as $key => $emoji):
                        $is_selected = ($avatar_base == $key);
                    ?>
                    <button type="submit" name="avatar_base" value="<?= $key; ?>" 
                        class="h-12 rounded-2xl flex items-center justify-center text-xl transition border <?= $is_selected ? 'bg-blue-50 border-blue-500 ring-2 ring-blue-200' : 'bg-slate-50 border-slate-200 hover:bg-slate-100'; ?>">
                        <?= $emoji; ?>
                    </button>
                    <?php endforeach; ?>
                </form>
            </div>

            <!-- Informasi Detail Akun -->
            <div class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 space-y-3">
                <h4 class="font-bold text-slate-800 text-sm">Informasi Akun</h4>
                <div class="space-y-2 text-xs">
                    <div class="flex justify-between py-1.5 border-b border-slate-50">
                        <span class="text-slate-400 font-medium">Username</span>
                        <span class="font-bold text-slate-700"><?= htmlspecialchars($user_username); ?></span>
                    </div>
                    <div class="flex justify-between py-1.5 border-b border-slate-50">
                        <span class="text-slate-400 font-medium">Role Akses</span>
                        <span class="font-bold text-blue-600 uppercase"><?= htmlspecialchars($user_role); ?></span>
                    </div>
                    <div class="flex justify-between py-1.5">
                        <span class="text-slate-400 font-medium">Status Gamifikasi</span>
                        <span class="font-bold <?= $is_zombie ? 'text-rose-600' : 'text-emerald-600'; ?>"><?= $avatar_status; ?></span>
                    </div>
                </div>
            </div>

            <!-- Tombol Keluar -->
            <div class="pt-2">
                <a href="logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar akun?')" 
                   class="w-full bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 py-3.5 rounded-2xl font-bold text-xs flex items-center justify-center gap-2 transition shadow-sm">
                    <span>🚪</span>
                    <span>Keluar Akun (Logout)</span>
                </a>
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