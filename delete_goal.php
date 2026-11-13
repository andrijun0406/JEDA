<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $goal_id = intval($_GET['id']);
    $user_id = $_SESSION['user_id'];
    
    // Hapus hanya jika goal tersebut milik user yang sedang login
    $stmt = $conn->prepare("DELETE FROM saving_goals WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $goal_id, $user_id);
    $stmt->execute();
}

header("Location: index.php");
exit();
?>