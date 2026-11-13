<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $goal_name = trim($_POST['goal_name']);
    $target_amount = floatval($_POST['target_amount']);
    $target_date = $_POST['target_date'];

    if (!empty($goal_name) && $target_amount > 0 && !empty($target_date)) {
        $stmt = $conn->prepare("INSERT INTO saving_goals (user_id, goal_name, target_amount, saved_amount, target_date, status) VALUES (?, ?, ?, 0, ?, 'ACTIVE')");
        $stmt->bind_param("isds", $user_id, $goal_name, $target_amount, $target_date);
        $stmt->execute();
    }
    
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JEDA - Tambah Target Tabungan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #0f172a; }</style>
</head>
<body class="flex justify-center items-center min-h-screen">
    <div class="w-full max-w-[480px] bg-slate-50 min-h-screen shadow-2xl flex flex-col justify-between p-5">
        <div>
            <div class="flex items-center gap-3 mb-6">
                <a href="index.php" class="w-10 h-10 bg-white rounded-2xl flex items-center justify-center shadow-sm text-slate-600 font-bold">←</a>
                <h1 class="text-lg font-bold text-slate-800">Tambah Target Baru 🎯</h1>
            </div>

            <form action="" method="POST" class="space-y-4 bg-white p-5 rounded-3xl shadow-sm border border-slate-100">
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Nama Target / Barang Impian</label>
                    <input type="text" name="goal_name" required placeholder="Contoh: Sepatu Futsal, Buku Novel" 
                        class="w-full bg-slate-50 border border-slate-200 text-xs rounded-2xl px-4 py-3 font-medium text-slate-700 focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Nominal Target (Rp)</label>
                    <input type="number" name="target_amount" required placeholder="150000" 
                        class="w-full bg-slate-50 border border-slate-200 text-xs rounded-2xl px-4 py-3 font-medium text-slate-700 focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Target Tanggal Tercapai</label>
                    <input type="date" name="target_date" required 
                        class="w-full bg-slate-50 border border-slate-200 text-xs rounded-2xl px-4 py-3 font-medium text-slate-700 focus:outline-none focus:border-blue-500">
                </div>
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 rounded-2xl text-xs transition shadow-md">
                    Simpan Target Tabungan 🚀
                </button>
            </form>
        </div>
    </div>
</body>
</html>