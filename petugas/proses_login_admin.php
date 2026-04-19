<?php
session_start();
require '../koneksi.php'; 

$username = mysqli_real_escape_string($koneksi, $_POST['username']);
$password = mysqli_real_escape_string($koneksi, $_POST['password']);

$query = mysqli_query($koneksi, "SELECT * FROM admins WHERE username='$username' AND password='$password'");
$data = mysqli_fetch_assoc($query);

if ($data) {
    $_SESSION['adminlogin'] = true;
    $_SESSION['admin']      = $data['username'];
    $_SESSION['id_admin']   = $data['id']; 
    $_SESSION['status']     = "login";

    // --- SESUAIKAN DENGAN TABEL log_aktivitas DI FOTO ---
    $nama_admin = $data['username']; // Mengambil nama admin dari database
    $aksi = "Melakukan login ke Sistem Loopie"; // Sesuai gaya bahasa di foto kamu
    
    // Pastikan nama kolom: nama_admin, aksi, tanggal
    mysqli_query($koneksi, "INSERT INTO log_aktivitas (nama_admin, aksi, tanggal) 
                            VALUES ('$nama_admin', '$aksi', NOW())");
    // ----------------------------------------------------

    header("Location: petugas_dashboard.php");
    exit();
} else {
    header("Location: loginadmin.php?pesan=gagal");
    exit();
}
?>