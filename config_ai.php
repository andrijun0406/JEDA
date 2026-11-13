<?php
// config_ai.php - Modul Simulasi AI Financial Coach & Baseline Detector

/**
 * Menghitung prediksi kapan target menabung tercapai berdasarkan saldo saat ini dan rata-rata menabung harian.
 */
function getTargetPrediction($saved_amount, $target_amount, $avg_daily_saving = 5000) {
    if ($saved_amount >= $target_amount) {
        return "🎉 Selamat! Target menabungmu sudah tercapai!";
    }
    
    $remaining = $target_amount - $saved_amount;
    if ($avg_daily_saving <= 0) $avg_daily_saving = 5000; // Default minimal menabung
    
    $days_needed = ceil($remaining / $avg_daily_saving);
    $target_date = date('d M Y', strtotime("+$days_needed days"));
    
    return "💡 Berdasarkan kecepatan menabungmu (Rp " . number_format($avg_daily_saving, 0, ',', '.') . "/hari), targetmu diperkirakan akan tercapai pada **$target_date** (sekitar $days_needed hari lagi).";
}

/**
 * Mengecek apakah pembelian suatu barang harian tergolong anomali (terlalu cepat beli lagi)
 */
function checkItemBaselineAnomaly($conn, $user_id, $item_name, $category_name) {
    // 1. Ambil data baseline normal durasi hari dari database
    $stmt = $conn->prepare("SELECT normal_duration_days FROM item_baselines WHERE item_name = ? AND category_name = ?");
    $stmt->bind_param("ss", $item_name, $category_name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return ['is_anomaly' => false, 'message' => 'Barang umum (tidak ada baseline khusus).'];
    }
    
    $baseline = $result->fetch_assoc();
    $normal_days = $baseline['normal_duration_days'];
    
    // 2. Cek kapan terakhir kali user membeli barang yang sama
    $stmt_txn = $conn->prepare("SELECT time_txn FROM dummy_transactions WHERE user_id = ? AND item_name = ? ORDER BY time_txn DESC LIMIT 1 OFFSET 1");
    $stmt_txn->bind_param("is", $user_id, $item_name);
    $stmt_txn->execute();
    $txn_result = $stmt_txn->get_result();
    
    if ($txn_result->num_rows > 0) {
        $last_txn = $txn_result->fetch_assoc();
        $last_date = new DateTime($last_txn['time_txn']);
        $now = new DateTime();
        $interval = $last_date->diff($now);
        $days_passed = $interval->days;
        
        if ($days_passed < $normal_days) {
            return [
                'is_anomaly' => true,
                'message' => "⚠️ **Anomali Terdeteksi:** Kamu membeli $item_name setelah $days_passed hari (wajar: $normal_days hari). Pengeluaran ini dinilai terlalu cepat dan berpotensi boros!"
            ];
        }
    }
    
    return ['is_anomaly' => false, 'message' => "✅ Pembelian $item_name masih dalam batas wajar pemakaian."];
}

/**
 * Template Placeholder untuk koneksi Live Gemini API di masa depan
 */
function callGeminiAIAPI($prompt_text) {
    $api_key = "MASUKKAN_GEMINI_API_KEY_DI_SINI"; // Opsional untuk masa depan
    
    // Jika API key belum diisi, kembalikan mode simulasi lokal
    if ($api_key == "MASUKKAN_GEMINI_API_KEY_DI_SINI") {
        return null; 
    }
    
    // Logika curl ke endpoint Gemini API bisa ditaruh di sini nantinya.
    return "Respons dari Gemini API live.";
}

/**
 * Mengevaluasi status target (On Target / Perlu Tambah Harian) berdasarkan target tanggal
 */
function evaluateGoalStatus($saved_amount, $target_amount, $target_date, $avg_daily_saving = 5000) {
    if ($saved_amount >= $target_amount) {
        return ['status' => 'success', 'message' => '🎉 Target ini sudah tercapai sepenuhnya!'];
    }
    
    $today = new DateTime();
    $target_dt = new DateTime($target_date);
    $interval = $today->diff($target_dt);
    $days_remaining = (int)$interval->format('%r%a'); // sisa hari (bisa minus jika lewat)
    
    if ($days_remaining <= 0) {
        return ['status' => 'danger', 'message' => '⚠️ Tenggat waktu target sudah lewat. Yuk kejar kekurangannya!'];
    }
    
    $remaining_amount = $target_amount - $saved_amount;
    $required_daily = ceil($remaining_amount / $days_remaining);
    
    if ($avg_daily_saving >= $required_daily) {
        return [
            'status' => 'on-target', 
            'message' => "✅ **On Target!** Dengan menabung Rp " . number_format($avg_daily_saving, 0, ',', '.') . "/hari, targetmu akan tercapai tepat waktu ($days_remaining hari lagi)."
        ];
    } else {
        return [
            'status' => 'warning', 
            'message' => "⚡ **Perlu Tambah Harian!** Agar tercapai dalam $days_remaining hari, kamu perlu menyisihkan **Rp " . number_format($required_daily, 0, ',', '.') . "/hari** (saat ini rata-rata Rp " . number_format($avg_daily_saving, 0, ',', '.') . "/hari)."
        ];
    }
}
/**
 * Mengubah format **teks** menjadi tag HTML <strong>teks</strong>
 */
function parseMarkdownBold($text) {
    return preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $text);
}
?>