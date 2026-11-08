<?php
include 'koneksi.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = 1; // Dummy session user
    $goal_name = $_POST['goal_name'];
    $target_amount = $_POST['target_amount'];
    $daily_save = $_POST['daily_save'];

    // Update database
    $query = "UPDATE virtual_wallets SET 
              saving_goal_name = '$goal_name', 
              target_amount = '$target_amount', 
              daily_auto_save = '$daily_save' 
              WHERE user_id = $user_id";
    
    if ($conn->query($query) === TRUE) {
        // Kembali ke halaman index setelah berhasil
        header("Location: index.php");
        exit();
    } else {
        echo "Error updating record: " . $conn->error;
    }
}
?>