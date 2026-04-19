<?php
session_start();
require '../koneksi.php';

// Ambil data admin sebelum session dihapus untuk dicatat di log
$id_admin = $_SESSION['adminlogin'];
$q_adm = mysqli_query($koneksi, "SELECT nama_lengkap FROM admin WHERE id_admin = '$id_admin'");
$d_adm = mysqli_fetch_assoc($q_adm);
$nama_admin = $d_adm['nama_lengkap'] ?? 'Admin';

// Catat Log Logout
mysqli_query($koneksi, "INSERT INTO log_aktivitas (nama_admin, aksi) VALUES ('$nama_admin', 'Melakukan logout dari sistem')");

// Hapus semua session
session_destroy();
header("Location: loginadmin.php");
exit();
?>