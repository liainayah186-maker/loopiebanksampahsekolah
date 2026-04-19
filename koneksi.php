<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "loopie_db"; // Sesuaikan dengan nama database di phpMyAdmin kamu

$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>