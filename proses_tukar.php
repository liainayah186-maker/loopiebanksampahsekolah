<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['id_user']) || !isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id_user = $_SESSION['id_user'];
$id_reward = mysqli_real_escape_string($koneksi, $_GET['id']);

// Ambil data user & reward (Nama tabel: rewards sesuai image_ff7126.png)
$user = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT total_poin FROM users WHERE id_user = '$id_user'"));
$reward = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM rewards WHERE id_reward = '$id_reward'"));

if (!$reward || $reward['stok'] <= 0 || $user['total_poin'] < $reward['harga_poin']) {
    echo "<script>alert('Poin tidak cukup atau stok habis!'); window.location.href='index.php';</script>";
    exit();
}

$harga = $reward['harga_poin'];
// Generate kode klaim unik (Contoh: LP-A1B2C)
$kode_klaim = "LP-" . strtoupper(substr(md5(time()), 0, 5)); 

mysqli_begin_transaction($koneksi);
try {
    // 1. Potong Poin
    mysqli_query($koneksi, "UPDATE users SET total_poin = total_poin - $harga WHERE id_user = '$id_user'");
    
    // 2. Catat Penukaran (Status: pending agar muncul di validasi admin)
    // Nama kolom disesuaikan dengan image_09543b.png
    mysqli_query($koneksi, "INSERT INTO penukaran (id_user, id_reward, kode_klaim, tanggal_tukar, status) 
                            VALUES ('$id_user', '$id_reward', '$kode_klaim', NOW(), 'pending')");
    
    // 3. Kurangi Stok
    mysqli_query($koneksi, "UPDATE rewards SET stok = stok - 1 WHERE id_reward = '$id_reward'");

    mysqli_commit($koneksi);
    echo "<script>alert('Sukses! Kode Klaim Anda: $kode_klaim'); window.location.href='riwayat.php';</script>";
} catch (Exception $e) {
    mysqli_rollback($koneksi);
    echo "<script>alert('Gagal memproses transaksi.'); window.location.href='index.php';</script>";
}
?>