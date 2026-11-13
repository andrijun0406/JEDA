<?php
session_start();
include 'koneksi.php';

// Validasi ketat sesi login siswa
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil Saldo Utama & Status Debt (Zombie Mode) Siswa
$wallet_q = $conn->query("SELECT real_balance, virtual_debt FROM virtual_wallets WHERE user_id = $user_id");
$wallet_data = $wallet_q->fetch_assoc();
$current_balance = $wallet_data['real_balance'] ?? 0;
$virtual_debt = $wallet_data['virtual_debt'] ?? 0;
$is_zombie = ($virtual_debt > 0);

// 1. Proses Tambah Target Tabungan Baru
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_goal') {
    $goal_name = trim($_POST['goal_name']);
    $target_amount = floatval($_POST['target_amount']);
    $target_date = $_POST['target_date'];

    if (!empty($goal_name) && $target_amount > 0) {
        $stmt = $conn->prepare("INSERT INTO saving_goals (user_id, goal_name, target_amount, saved_amount, target_date, status) VALUES (?, ?, ?, 0, ?, 'ACTIVE')");
        $stmt->bind_param("isds", $user_id, $goal_name, $target_amount, $target_date);
        $stmt->execute();
        $stmt->close();
        
        header("Location: manage_goals.php?success=1");
        exit();
    }
}

// 2. Proses Alokasi / Top-Up Saldo ke Target (Dicegah jika Zombie Mode)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'allocate_funds') {
    if ($is_zombie) {
        $error_msg = "Aksi ditolak! Anda sedang dalam Zombie Mode (memiliki utang). Lunasi utang terlebih dahulu!";
    } else {
        $goal_id = intval($_POST['goal_id']);
        $alloc_amount = floatval($_POST['alloc_amount']);

        if ($alloc_amount > 0) {
            if ($alloc_amount <= $current_balance) {
                // Kurangi saldo utama di virtual_wallets & tambahkan ke saved_amount di saving_goals
                $conn->query("UPDATE virtual_wallets SET real_balance = real_balance - $alloc_amount WHERE user_id = $user_id");
                $conn->query("UPDATE saving_goals SET saved_amount = saved_amount + $alloc_amount WHERE id = $goal_id AND user_id = $user_id");
                
                header("Location: manage_goals.php?allocated=1");
                exit();
            } else {
                $error_msg = "Saldo PSP utama tidak mencukupi untuk alokasi ini!";
            }
        }
    }
}

// 3. Proses Hapus Target Tabungan
if (isset($_GET['delete_goal'])) {
    $goal_id = intval($_GET['delete_goal']);
    $stmt = $conn->prepare("DELETE FROM saving_goals WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $goal_id, $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_goals.php?deleted=1");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JEDA - Kelola Target Tabungan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #0f172a; }</style>
</head>
<body class="flex justify-center items-center min-h-screen">

    <div class="w-full max-w-[480px] bg-slate-50 min-h-screen shadow-2xl flex flex-col justify-between relative pb-24">
        
        <!-- PANGGIL HEADER KONSISTEN SISWA -->
        <?php include 'student_header.php'; ?>

        <!-- Konten Utama -->
        <div class="p-4 flex-1 space-y-4 overflow-y-auto">
            
            <?php if (isset($error_msg)): ?>
                <div class="bg-rose-100 border border-rose-200 text-rose-600 px-4 py-2.5 rounded-2xl text-xs font-bold">
                    ⚠️ <?= $error_msg; ?>
                </div>
            <?php endif; ?>

            <!-- Peringatan Zombie Mode jika Aktif -->
            <?php if ($is_zombie): ?>
                <div class="bg-rose-500 text-white p-3.5 rounded-2xl text-xs space-y-1 shadow-sm">
                    <div class="flex items-center gap-1.5 font-bold">
                        <span>🧟‍♂️</span>
                        <span>Fitur Alokasi Terkunci (Zombie Mode)</span>
                    </div>
                    <p class="text-[11px] text-rose-100 leading-relaxed">
                        Anda memiliki virtual debt sebesar <b>Rp <?= number_format($virtual_debt, 0, ',', '.'); ?></b>. Selesaikan kewajiban finansial Anda untuk mengaktifkan kembali fitur alokasi tabungan!
                    </p>
                </div>
            <?php endif; ?>

            <!-- Informasi Saldo Tersedia -->
            <div class="bg-white p-4 rounded-3xl shadow-sm border border-slate-100 flex justify-between items-center">
                <div>
                    <span class="text-[10px] text-slate-400 font-bold block">Saldo Utama PSP</span>
                    <span class="text-base font-extrabold text-blue-600">Rp <?= number_format($current_balance, 0, ',', '.'); ?></span>
                </div>
                <span class="text-xs bg-blue-50 text-blue-600 font-bold px-3 py-1 rounded-xl">Impian & Target</span>
            </div>

            <!-- Daftar Target Aktif, AI Notes, & Fitur Alokasi Dana -->
            <div class="space-y-3">
                <h3 class="font-bold text-slate-800 text-xs px-1">Daftar Impian & Alokasi Dana</h3>
                
                <?php
                $goals_q = $conn->query("SELECT * FROM saving_goals WHERE user_id = $user_id ORDER BY id DESC");
                if ($goals_q && $goals_q->num_rows > 0):
                    while($g = $goals_q->fetch_assoc()):
                        $g_id = $g['id'];
                        $pct = ($g['target_amount'] > 0) ? round(($g['saved_amount'] / $g['target_amount']) * 100) : 0;
                        if($pct > 100) $pct = 100;

                        $sisa_dana = $g['target_amount'] - $g['saved_amount'];
                        $daily_saving = ($sisa_dana > 0) ? ceil($sisa_dana / 30) : 0;
                ?>
                <div class="bg-white p-4 rounded-3xl shadow-sm border border-slate-100 space-y-3">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="font-bold text-slate-800 text-sm block"><?= htmlspecialchars($g['goal_name']); ?></span>
                            <span class="text-[10px] text-slate-400">Tenggat: <?= date('d M Y', strtotime($g['target_date'])); ?></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-extrabold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-md"><?= $pct; ?>%</span>
                            <a href="manage_goals.php?delete_goal=<?= $g_id; ?>" onclick="return confirm('Hapus target tabungan ini?')" class="w-7 h-7 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-500 flex items-center justify-center font-bold text-xs transition">✕</a>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="space-y-1">
                        <div class="flex justify-between text-[11px]">
                            <span class="text-slate-500">Terkumpul: <b>Rp <?= number_format($g['saved_amount'], 0, ',', '.'); ?></b></span>
                            <span class="text-slate-700 font-bold">Target: Rp <?= number_format($g['target_amount'], 0, ',', '.'); ?></span>
                        </div>
                        <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                            <div class="bg-blue-600 h-full rounded-full transition-all duration-500" style="width: <?= $pct; ?>%;"></div>
                        </div>
                    </div>

                    <!-- AI Notes -->
                    <div class="bg-indigo-50/70 border border-indigo-100 p-2.5 rounded-2xl text-[11px] text-indigo-800 flex items-start gap-2">
                        <span class="text-base">🤖</span>
                        <div>
                            <span class="font-bold block text-indigo-900">AI Insight & Rekomendasi:</span>
                            <?php if ($pct >= 100): ?>
                                Selamat! Target impian ini sudah tercapai sepenuhnya! 🎉
                            <?php else: ?>
                                Sisihkan <b class="text-indigo-950">Rp <?= number_format($daily_saving, 0, ',', '.'); ?>/hari</b> untuk mencapai target impian ini.
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- FITUR ALOKASI / TOP-UP DANA KE TARGET (NON-AKTIF JIKA ZOMBIE MODE) -->
                    <?php if ($pct < 100): ?>
                    <form action="manage_goals.php" method="POST" class="pt-2 border-t border-slate-100 flex gap-2">
                        <input type="hidden" name="action" value="allocate_funds">
                        <input type="hidden" name="goal_id" value="<?= $g_id; ?>">
                        
                        <?php if ($is_zombie): ?>
                            <!-- Input & Tombol Disabled / Non-Aktif -->
                            <input type="number" placeholder="Alokasi Dikunci (Zombie Mode)" disabled 
                                class="flex-1 bg-slate-100 border border-slate-200 text-xs rounded-xl px-3 py-2 text-slate-400 cursor-not-allowed font-medium">
                            <button type="button" disabled 
                                class="bg-slate-300 text-slate-500 font-bold px-3.5 py-2 rounded-xl text-xs cursor-not-allowed flex items-center gap-1">
                                <span>🔒</span>
                                <span>Terkunci</span>
                            </button>
                        <?php else: ?>
                            <!-- Input & Tombol Aktif (Angel Mode) -->
                            <input type="number" name="alloc_amount" placeholder="Nominal Alokasi (Rp)" min="1000" max="<?= $current_balance; ?>" required 
                                class="flex-1 bg-slate-50 border border-slate-200 text-xs rounded-xl px-3 py-2 text-slate-700 focus:outline-none focus:border-blue-500 font-medium">
                            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-3.5 py-2 rounded-xl text-xs transition shadow-sm flex items-center gap-1">
                                <span>💰</span>
                                <span>Alokasikan</span>
                            </button>
                        <?php endif; ?>
                    </form>
                    <?php endif; ?>

                </div>
                <?php endwhile; else: ?>
                    <div class="bg-white p-8 rounded-3xl text-center space-y-2 border border-slate-100">
                        <span class="text-2xl">🎯</span>
                        <p class="text-xs text-slate-400">Belum ada target tabungan. Buat target impian Anda melalui tombol di bawah!</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- TOMBOL BUAT TARGET BARU DI TARUH PALING BAWAH -->
            <div class="pt-2">
                <button onclick="toggleGoalForm()" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-2xl text-xs transition shadow-sm flex items-center justify-center gap-1.5">
                    <span>➕</span>
                    <span>Buat Target Tabungan Baru</span>
                </button>
            </div>
            
            <!-- Form Tambah Target (Hidden secara default) -->
            <div id="goalFormContainer" class="hidden bg-white p-4 rounded-3xl shadow-sm border border-slate-100 space-y-3 transition-all">
                <div class="flex justify-between items-center border-b border-slate-100 pb-2">
                    <span class="text-xs font-bold text-slate-800">Form Target Baru</span>
                    <button onclick="toggleGoalForm()" class="text-slate-400 hover:text-slate-600 text-xs font-bold">✕ Tutup</button>
                </div>
                <form action="manage_goals.php" method="POST" class="space-y-3">
                    <input type="hidden" name="action" value="add_goal">
                    <div>
                        <label class="text-[10px] text-slate-400 font-semibold block mb-1">Nama Barang / Impian</label>
                        <input type="text" name="goal_name" placeholder="Contoh: Sepatu Futsal Baru" required class="w-full bg-slate-50 border border-slate-200 text-xs rounded-xl px-3 py-2.5 text-slate-700 focus:outline-none focus:border-blue-500">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-[10px] text-slate-400 font-semibold block mb-1">Target Nominal (Rp)</label>
                            <input type="number" name="target_amount" placeholder="150000" required class="w-full bg-slate-50 border border-slate-200 text-xs rounded-xl px-3 py-2.5 text-slate-700 focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="text-[10px] text-slate-400 font-semibold block mb-1">Tenggat Waktu</label>
                            <input type="date" name="target_date" required class="w-full bg-slate-50 border border-slate-200 text-xs rounded-xl px-3 py-2.5 text-slate-700 focus:outline-none focus:border-blue-500">
                        </div>
                    </div>
                    <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 rounded-xl text-xs transition shadow-sm">Simpan Target</button>
                </form>
            </div>

        </div>

        <!-- BOTTOM NAVIGATION BAR (5 MENU KONSISTEN) -->
        <div class="absolute bottom-0 left-0 right-0 bg-white border-t border-slate-100 py-3 px-3 flex justify-around items-center rounded-t-3xl shadow-lg z-50">
            <a href="index.php" class="flex flex-col items-center text-slate-400 hover:text-blue-600">
                <span class="text-xl">🏠</span>
                <span class="text-[9px] font-medium mt-1">Beranda</span>
            </a>
            <a href="analytics.php" class="flex flex-col items-center text-slate-400 hover:text-blue-600">
                <span class="text-xl">📊</span>
                <span class="text-[9px] font-medium mt-1">Analitik</span>
            </a>
            <a href="manage_goals.php" class="flex flex-col items-center text-blue-600">
                <span class="text-xl">🎯</span>
                <span class="text-[9px] font-bold mt-1">Target</span>
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

    <!-- Script Toggle Form -->
    <script>
        function toggleGoalForm() {
            const formContainer = document.getElementById('goalFormContainer');
            formContainer.classList.toggle('hidden');
        }
    </script>
</body>
</html>