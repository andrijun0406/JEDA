<?php
// Konfigurasi default Laragon
$host = "localhost";
$user = "root";
$pass = "";
$db   = "jeda_db";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi Database Gagal: " . $conn->connect_error);
}
?>