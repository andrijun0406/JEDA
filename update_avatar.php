<?php
session_start();
include 'koneksi.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $avatar_base = $_POST['avatar_base'];

    // Update pilihan avatar ke database
    $query = "UPDATE users SET avatar_base = '$avatar_base' WHERE id = $user_id";
    
    if ($conn->query($query) === TRUE) {
        header("Location: index.php");
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
}
?>