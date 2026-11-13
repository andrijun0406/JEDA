<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $avatar_base = $_POST['avatar_base'];

    // Validasi pilihan avatar yang diizinkan
    $allowed = ['cat', 'robot', 'boy', 'girl', 'fox'];
    if (in_array($avatar_base, $allowed)) {
        $stmt = $conn->prepare("UPDATE users SET avatar_base = ? WHERE id = ?");
        $stmt->bind_param("si", $avatar_base, $user_id);
        $stmt->execute();
    }
}
header("Location: profile_card.php");
exit();
?>