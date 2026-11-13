<?php
// Pastikan sesi dan koneksi sudah ada sebelum memanggil file ini
if (!isset($conn) && file_exists('koneksi.php')) {
    include 'koneksi.php';
}

$current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

// Ambil data siswa & status virtual wallet untuk avatar & mode
$h_query = $conn->query("
    SELECT u.name, u.avatar_base, v.virtual_debt 
    FROM users u 
    JOIN virtual_wallets v ON u.id = v.user_id 
    WHERE u.id = $current_user_id
");
$h_data = ($h_query && $h_query->num_rows > 0) ? $h_query->fetch_assoc() : ['name' => 'Siswa', 'avatar_base' => 'boy', 'virtual_debt' => 0];

$h_name = $h_data['name'];
$h_avatar = $h_data['avatar_base'];
$h_debt = $h_data['virtual_debt'];
$h_is_zombie = ($h_debt > 0);

// Emoji Avatar sesuai referensi gambar
$avatar_emojis = [
    'cat' => $h_is_zombie ? '😿🧟' : '🐱✨',
    'robot' => $h_is_zombie ? '🤖🧟' : '🤖💎',
    'boy' => $h_is_zombie ? '👦🧟' : '👼👦',
    'girl' => $h_is_zombie ? '👧🧟' : '👼👧',
    'fox' => $h_is_zombie ? '🦊🧟' : '🦊✨'
];
$display_avatar = isset($avatar_emojis[$h_avatar]) ? $avatar_emojis[$h_avatar] : ($h_is_zombie ? '🧟‍♂️' : '👼');
?>

<!-- Header Konsisten Siswa (BSI Smart Card x JEDA) -->
<div class="p-5 bg-white rounded-b-3xl shadow-sm space-y-2">
    <div class="flex justify-between items-center">
        <div>
            <p class="text-[11px] text-slate-400 font-semibold tracking-wide">BSI Smart Card x JEDA</p>
            <h1 class="text-xl font-bold text-slate-800">Halo, <?= htmlspecialchars($h_name); ?> 👋</h1>
        </div>
        <!-- Avatar diklik mengarah ke profile_card.php -->
        <a href="profile_card.php" title="Lihat Kartu & Profil" class="w-12 h-12 rounded-2xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-2xl shadow-sm hover:scale-105 transition">
            <?= $display_avatar; ?>
        </a>
    </div>

    <!-- Badge Mode (Angel / Zombie) -->
    <div>
        <?php if ($h_is_zombie): ?>
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