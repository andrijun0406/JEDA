<?php
session_start();
include 'koneksi.php';
include 'config_baseline.php';

// Pastikan hanya orang tua yang login
if (!isset($_SESSION['parent_id']) || $_SESSION['role'] != 'parent') {
    header("Location: login.php");
    exit();
}

$parent_id = $_SESSION['parent_id'];
$child_id = isset($_GET['child_id']) ? intval($_GET['child_id']) : 0;
$selected_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

// Validasi apakah anak tersebut benar-benar anak dari orang tua yang sedang login
$check_child = $conn->prepare("SELECT * FROM users WHERE id = ? AND parent_id = ?");
$check_child->bind_param("ii", $child_id, $parent_id);
$check_child->execute();
$child_data = $check_child->get_result()->fetch_assoc();
$check_child->close();

if (!$child_data) {
    echo "Anak tidak ditemukan atau Anda tidak memiliki hak akses memantau akun ini.";
    exit();
}

$child_name = $child_data['name'];
$baseline = getRecurringBaselineAnalysis($conn, $child_id);

// Hitung total pengeluaran anak pada bulan terpilih
$q_total = $conn->prepare("SELECT SUM(amount) as total FROM dummy_transactions WHERE user_id = ? AND DATE_FORMAT(time_txn, '%Y-%m') = ?");
$q_total->bind_param("is", $child_id, $selected_month);
$q_total->execute();
$total_spending = $q_total->get_result()->fetch_assoc()['total'] ?? 0;
$q_total->close();

// Ambil data kategori untuk Donut Chart anak
$q_cat = $conn->prepare("
    SELECT category, SUM(amount) as total_cat 
    FROM dummy_transactions 
    WHERE user_id = ? AND DATE_FORMAT(time_txn, '%Y-%m') = ?
    GROUP BY category
");
$q_cat->bind_param("is", $child_id, $selected_month);
$q_cat->execute();
$cat_res = $q_cat->get_result();

$cat_labels = [];
$cat_data = [];
while ($row = $cat_res->fetch_assoc()) {
    $cat_labels[] = $row['category'];
    $cat_data[] = $row['total_cat'];
}
$q_cat->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JEDA - Analitik Detail: <?= htmlspecialchars($child_name); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #0f172a; }</style>
</head>
<body class="flex justify-center items-center min-h-screen">

    <div class="w-full max-w-[480px] bg-slate-50 min-h-screen shadow-2xl flex flex-col justify-between relative pb-24">
        
        <!-- PANGGIL HEADER KONSISTEN ORANG TUA (Dropdown Anak) -->
        <?php include 'parent_header.php'; ?>

        <!-- Konten Utama -->
        <div class="p-4 flex-1 space-y-4 overflow-y-auto">
            
            <!-- Filter Pilihan Bulan -->
            <form method="GET" action="parent_child_analytics.php" class="bg-white p-3 rounded-2xl shadow-sm border border-slate-100 flex items-center justify-between">
                <input type="hidden" name="child_id" value="<?= $child_id; ?>">
                <span class="text-xs font-bold text-slate-700">Pilih Periode:</span>
                <select name="month" onchange="this.form.submit()" class="bg-slate-100 border border-slate-200 text-xs rounded-xl px-3 py-1.5 font-bold text-slate-700 focus:outline-none focus:border-blue-500">
                    <?php
                    for ($i = 0; $i < 4; $i++) {
                        $m_value = date('Y-m', strtotime("-$i months"));
                        $m_label = date('F Y', strtotime("-$i months"));
                        $eng = ['January','February','March','April','May','June','July','August','September','October','November','December'];
                        $ind = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                        $m_label_id = str_replace($eng, $ind, $m_label);

                        $selected = ($selected_month == $m_value) ? 'selected' : '';
                        echo "<option value=\"$m_value\" $selected>$m_label_id</option>";
                    }
                    ?>
                </select>
            </form>

            <!-- Ringkasan Pengeluaran -->
            <div class="bg-gradient-to-br from-indigo-600 to-blue-700 text-white p-5 rounded-3xl shadow-md space-y-2">
                <span class="text-xs text-indigo-100 font-medium">Total Pengeluaran <?= htmlspecialchars($child_name); ?> (<?= date('F Y', strtotime($selected_month)); ?>)</span>
                <h2 class="text-2xl font-extrabold">Rp <?= number_format($total_spending, 0, ',', '.'); ?></h2>
                <p class="text-[11px] text-indigo-200">Pantauan real-time dari sistem BSI Smart Card x JEDA.</p>
            </div>

            <!-- Donut Chart Kategori Pengeluaran Anak -->
            <div class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 space-y-3">
                <div class="flex justify-between items-center">
                    <h3 class="font-bold text-slate-800 text-sm">Grafik Donut Kategori 🍩</h3>
                    <span class="text-[10px] bg-blue-50 text-blue-600 px-2 py-0.5 rounded-md font-bold">Interaktif</span>
                </div>
                
                <?php if (!empty($cat_data)): ?>
                <div class="w-full flex justify-center py-2">
                    <div class="w-[200px] h-[200px]">
                        <canvas id="childDonutChart"></canvas>
                    </div>
                </div>
                <?php else: ?>
                    <p class="text-xs text-slate-400 text-center py-6">Belum ada data transaksi pada bulan ini.</p>
                <?php endif; ?>
            </div>

            <!-- AI Spending Baseline & Restock Prediction -->
            <div class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="font-bold text-slate-800 text-sm">🤖 AI Recurring Baseline</h3>
                    <span class="text-[10px] bg-blue-50 text-blue-600 font-extrabold px-2 py-0.5 rounded-md">Siklus 30 Hari</span>
                </div>
                <p class="text-xs text-slate-500 leading-relaxed">
                    Estimasi restock kebutuhan harian milik <?= htmlspecialchars($child_name); ?>.
                </p>

                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100 space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold text-slate-800">🧴 <?= htmlspecialchars($baseline['item']); ?></span>
                        <span class="text-xs font-extrabold text-blue-600">Rp <?= number_format($baseline['avg_cost'], 0, ',', '.'); ?></span>
                    </div>
                    <div class="text-[11px] text-slate-600 space-y-1 pt-1 border-t border-slate-200/60">
                        <div class="flex justify-between">
                            <span>Siklus Pembelian:</span>
                            <span class="font-bold">Setiap 30 Hari</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Estimasi Restock Bulan Depan:</span>
                            <span class="font-bold text-indigo-600"><?= $baseline['predicted_date']; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Riwayat Transaksi Anak -->
            <div class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 space-y-3">
                <h3 class="font-bold text-slate-800 text-sm">Riwayat Transaksi Anak</h3>
                <div class="space-y-2">
                    <?php
                    $q_txn = $conn->prepare("SELECT * FROM dummy_transactions WHERE user_id = ? AND DATE_FORMAT(time_txn, '%Y-%m') = ? ORDER BY time_txn DESC");
                    $q_txn->bind_param("is", $child_id, $selected_month);
                    $q_txn->execute();
                    $res_txn = $q_txn->get_result();

                    if ($res_txn && $res_txn->num_rows > 0):
                        while($t = $res_txn->fetch_assoc()):
                    ?>
                    <div class="flex justify-between items-center bg-slate-50 p-3 rounded-2xl border border-slate-100 text-xs">
                        <div>
                            <span class="font-bold text-slate-800 block"><?= htmlspecialchars($t['item_name']); ?></span>
                            <span class="text-[10px] text-slate-400"><?= htmlspecialchars($t['category']); ?> • <?= date('d M Y', strtotime($t['time_txn'])); ?></span>
                        </div>
                        <span class="font-extrabold text-rose-600">- Rp <?= number_format($t['amount'], 0, ',', '.'); ?></span>
                    </div>
                    <?php endwhile; else: ?>
                        <p class="text-xs text-slate-400 text-center py-3">Tidak ada riwayat transaksi pada bulan ini.</p>
                    <?php 
                    endif; 
                    $q_txn->close();
                    ?>
                </div>
            </div>

        </div>

        <!-- BOTTOM NAVIGATION BAR ORANG TUA (5 Menu Konsisten) -->
        <div class="absolute bottom-0 left-0 right-0 bg-white border-t border-slate-100 py-3 px-3 flex justify-around items-center rounded-t-3xl shadow-lg z-50">
            <a href="parent_dashboard.php?child_id=<?= $child_id; ?>" class="flex flex-col items-center text-slate-400 hover:text-blue-600">
                <span class="text-xl">🏠</span>
                <span class="text-[9px] font-medium mt-1">Beranda</span>
            </a>
            <a href="parent_child_analytics.php?child_id=<?= $child_id; ?>" class="flex flex-col items-center text-blue-600">
                <span class="text-xl">📊</span>
                <span class="text-[9px] font-bold mt-1">Analitik</span>
            </a>
            <a href="parent_manage_goals.php?child_id=<?= $child_id; ?>" class="flex flex-col items-center text-slate-400 hover:text-blue-600">
                <span class="text-xl">🎯</span>
                <span class="text-[9px] font-medium mt-1">Target</span>
            </a>
            <a href="parent_leaderboard.php?child_id=<?= $child_id; ?>" class="flex flex-col items-center text-slate-400 hover:text-blue-600">
                <span class="text-xl">🏆</span>
                <span class="text-[9px] font-medium mt-1">Ranking</span>
            </a>
            <a href="parent_profile_card.php?child_id=<?= $child_id; ?>" class="flex flex-col items-center text-slate-400 hover:text-blue-600">
                <span class="text-xl">💳</span>
                <span class="text-[9px] font-medium mt-1">Profil</span>
            </a>
        </div>

    </div>

    <!-- Script Chart.js Donut Chart Per Anak -->
    <?php if (!empty($cat_data)): ?>
    <script>
        const ctx = document.getElementById('childDonutChart').getContext('2d');
        const childDonutChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($cat_labels); ?>,
                datasets: [{
                    data: <?= json_encode($cat_data); ?>,
                    backgroundColor: [
                        '#3b82f6', '#6366f1', '#ec4899', '#10b981', '#f59e0b', '#8b5cf6'
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 10,
                            font: { size: 9, family: "'Plus Jakarta Sans', sans-serif" }
                        }
                    }
                },
                cutout: '70%'
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>