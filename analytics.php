<?php
session_start();
include 'koneksi.php';
include 'config_baseline.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$baseline = getRecurringBaselineAnalysis($conn, $user_id);

// Ambil filter bulan yang dipilih (default: bulan saat ini atau format 'YYYY-MM')
$selected_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

// Hitung total pengeluaran berdasarkan bulan yang dipilih
$query_total = $conn->prepare("SELECT SUM(amount) as total FROM dummy_transactions WHERE user_id = ? AND DATE_FORMAT(time_txn, '%Y-%m') = ?");
$query_total->bind_param("is", $user_id, $selected_month);
$query_total->execute();
$total_spending = $query_total->get_result()->fetch_assoc()['total'] ?? 0;
$query_total->close();

// Ambil data kategori pengeluaran berdasarkan bulan yang dipilih untuk Donut Chart
$category_query = $conn->prepare("
    SELECT category, SUM(amount) as total_cat 
    FROM dummy_transactions 
    WHERE user_id = ? AND DATE_FORMAT(time_txn, '%Y-%m') = ?
    GROUP BY category
");
$category_query->bind_param("is", $user_id, $selected_month);
$category_query->execute();
$cat_result = $category_query->get_result();

$cat_labels = [];
$cat_data = [];
while ($row = $cat_result->fetch_assoc()) {
    $cat_labels[] = $row['category'];
    $cat_data[] = $row['total_cat'];
}
$category_query->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JEDA - Analitik Bulanan & Baseline</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #0f172a; }</style>
</head>
<body class="flex justify-center items-center min-h-screen">

    <div class="w-full max-w-[480px] bg-slate-50 min-h-screen shadow-2xl flex flex-col justify-between relative pb-24">
        
        <!-- PANGGIL HEADER KONSISTEN SISWA (Termasuk lencana & klik avatar ke profile_card.php) -->
        <?php include 'student_header.php'; ?>

        <!-- Konten Utama -->
        <div class="p-4 flex-1 space-y-4 overflow-y-auto">
            
            <!-- Filter Pilihan Bulan -->
            <form method="GET" action="analytics.php" class="bg-white p-3 rounded-2xl shadow-sm border border-slate-100 flex items-center justify-between">
                <span class="text-xs font-bold text-slate-700">Pilih Periode:</span>
                <select name="month" onchange="this.form.submit()" class="bg-slate-100 border border-slate-200 text-xs rounded-xl px-3 py-1.5 font-bold text-slate-700 focus:outline-none focus:border-blue-500">
                    <?php
                    // Menampilkan pilihan 4 bulan ke belakang (Bulan ini + 3 bulan sebelumnya)
                    for ($i = 0; $i < 4; $i++) {
                        $m_value = date('Y-m', strtotime("-$i months"));
                        $m_label = date('F Y', strtotime("-$i months"));
                        // Terjemahan bulan sederhana ke Indonesia
                        $eng = ['January','February','March','April','May','June','July','August','September','October','November','December'];
                        $ind = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                        $m_label_id = str_replace($eng, $ind, $m_label);

                        $selected = ($selected_month == $m_value) ? 'selected' : '';
                        echo "<option value=\"$m_value\" $selected>$m_label_id</option>";
                    }
                    ?>
                </select>
            </form>

            <!-- Ringkasan Pengeluaran Bulan Terpilih -->
            <div class="bg-gradient-to-br from-indigo-600 to-blue-700 text-white p-5 rounded-3xl shadow-md space-y-2">
                <span class="text-xs text-indigo-100 font-medium">Total Pengeluaran (<?= date('F Y', strtotime($selected_month)); ?>)</span>
                <h2 class="text-2xl font-extrabold">Rp <?= number_format($total_spending, 0, ',', '.'); ?></h2>
                <p class="text-[11px] text-indigo-200">AI mendeteksi pola konsumsi stabil pada periode ini.</p>
            </div>

            <!-- Donut Chart Kategori Pengeluaran Bulanan -->
            <div class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 space-y-3">
                <div class="flex justify-between items-center">
                    <h3 class="font-bold text-slate-800 text-sm">Grafik Kategori Donut 🍩</h3>
                    <span class="text-[10px] bg-blue-50 text-blue-600 px-2 py-0.5 rounded-md font-bold">Interaktif</span>
                </div>
                
                <?php if (!empty($cat_data)): ?>
                <div class="w-full flex justify-center py-2">
                    <div class="w-[200px] h-[200px]">
                        <canvas id="spendingDonutChart"></canvas>
                    </div>
                </div>
                <?php else: ?>
                    <p class="text-xs text-slate-400 text-center py-6">Belum ada data transaksi pada bulan yang dipilih.</p>
                <?php endif; ?>
            </div>

            <!-- FITUR UTAMA: Spending Baseline & Recurring Prediction -->
            <div class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="font-bold text-slate-800 text-sm">🤖 AI Recurring Baseline</h3>
                    <span class="text-[10px] bg-blue-50 text-blue-600 font-extrabold px-2 py-0.5 rounded-md">Siklus 30 Hari</span>
                </div>
                <p class="text-xs text-slate-500 leading-relaxed">
                    Sistem memantau konsumsi rutin seperti sabun, pasta gigi, atau paket data untuk estimasi anggaran bulan depan.
                </p>

                <!-- Kartu Prediksi Barang Habis -->
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
                            <span>Estimasi Habis & Restock:</span>
                            <span class="font-bold text-indigo-600"><?= $baseline['predicted_date']; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Riwayat Transaksi Berdasarkan Bulan Terpilih -->
            <div class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 space-y-3">
                <h3 class="font-bold text-slate-800 text-sm">Riwayat Transaksi (Bulan Ini)</h3>
                <div class="space-y-2">
                    <?php
                    $txn_q = $conn->prepare("SELECT * FROM dummy_transactions WHERE user_id = ? AND DATE_FORMAT(time_txn, '%Y-%m') = ? ORDER BY time_txn DESC");
                    $txn_q->bind_param("is", $user_id, $selected_month);
                    $txn_q->execute();
                    $txn_res = $txn_q->get_result();

                    if ($txn_res && $txn_res->num_rows > 0):
                        while($t = $txn_res->fetch_assoc()):
                    ?>
                    <div class="flex justify-between items-center bg-slate-50 p-3 rounded-2xl border border-slate-100 text-xs">
                        <div>
                            <span class="font-bold text-slate-800 block"><?= htmlspecialchars($t['item_name']); ?></span>
                            <span class="text-[10px] text-slate-400"><?= htmlspecialchars($t['category']); ?> • <?= date('d M Y', strtotime($t['time_txn'])); ?></span>
                        </div>
                        <span class="font-extrabold text-rose-600">- Rp <?= number_format($t['amount'], 0, ',', '.'); ?></span>
                    </div>
                    <?php endwhile; else: ?>
                        <p class="text-xs text-slate-400 text-center py-3">Tidak ada transaksi pada bulan ini.</p>
                    <?php 
                    endif; 
                    $txn_q->close();
                    ?>
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

    <!-- Script Inisialisasi Chart.js Donut Chart -->
    <?php if (!empty($cat_data)): ?>
    <script>
        const ctx = document.getElementById('spendingDonutChart').getContext('2d');
        const spendingDonutChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($cat_labels); ?>,
                datasets: [{
                    data: <?= json_encode($cat_data); ?>,
                    backgroundColor: [
                        '#3b82f6', // Blue
                        '#6366f1', // Indigo
                        '#ec4899', // Pink
                        '#10b981', // Emerald
                        '#f59e0b', // Amber
                        '#8b5cf6'  // Purple
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
                            font: {
                                size: 9,
                                family: "'Plus Jakarta Sans', sans-serif"
                            }
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