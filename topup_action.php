<?php
session_start();
include 'koneksi.php';

// Pastikan hanya orang tua yang login
if (!isset($_SESSION['parent_id']) || $_SESSION['role'] != 'parent') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sesuaikan dengan name="child_id" yang dikirim dari form parent_dashboard.php
    $child_id = isset($_POST['child_id']) ? intval($_POST['child_id']) : 0;
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;

    if ($child_id > 0 && $amount > 0) {
        // Gunakan prepared statement untuk keamanan dan mencegah error syntax SQL
        $stmt = $conn->prepare("UPDATE virtual_wallets SET real_balance = real_balance + ? WHERE user_id = ?");
        $stmt->bind_param("di", $amount, $child_id);
        $stmt->execute();
        $stmt->close();
    }
}

header("Location: parent_dashboard.php");
exit();
?>