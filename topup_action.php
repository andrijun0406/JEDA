<?php
session_start();
include 'koneksi.php';

// Pastikan aksi ini dilakukan oleh orang tua
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['parent_id'])) {
    $anak_id = $_POST['anak_id'];
    $amount = $_POST['amount'];

    // Update Saldo Asli BSI / PSP Mobile siswa
    $query = "UPDATE virtual_wallets SET real_balance = real_balance + $amount WHERE user_id = $anak_id";
    
    if ($conn->query($query) === TRUE) {
        // Jika siswa memiliki utang (Zombi), top-up apresiasi bisa diatur 
        // untuk otomatis melunasi utangnya (Opsional - menunjukkan sistem cerdas)
        $conn->query("UPDATE virtual_wallets SET virtual_debt = 0 WHERE user_id = $anak_id AND virtual_debt <= $amount");

        header("Location: parent_dashboard.php");
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
}
?>