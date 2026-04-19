<?php
session_start();
require '../koneksi.php'; 

// AMBIL NAMA ADMIN UNTUK LOG
$id_admin_session = $_SESSION['adminlogin'];
$q_adm = mysqli_query($koneksi, "SELECT nama_lengkap FROM admin WHERE id_admin = '$id_admin_session'");
$d_adm = mysqli_fetch_assoc($q_adm);
$nama_admin_log = $d_adm['nama_lengkap'] ?? 'Admin';

// PROSES SIMPAN HADIAH
if (isset($_POST['simpan'])) {
    $nama  = mysqli_real_escape_string($koneksi, $_POST['nama_hadiah']);
    $harga = mysqli_real_escape_string($koneksi, $_POST['harga_poin']);
    $stok  = mysqli_real_escape_string($koneksi, $_POST['stok']);
    $desc  = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);

    $query = "INSERT INTO rewards (nama_hadiah, harga_poin, stok, deskripsi) 
              VALUES ('$nama', '$harga', '$stok', '$desc')";
    
    if (mysqli_query($koneksi, $query)) {
        // --- LOG TAMBAH REWARD ---
        $aksi_log = "Menambah hadiah baru: $nama";
        mysqli_query($koneksi, "INSERT INTO log_aktivitas (nama_admin, aksi) VALUES ('$nama_admin_log', '$aksi_log')");

        header("Location: petugas_reward.php?status=sukses");
        exit;
    } else {
        die("Error Simpan: " . mysqli_error($koneksi));
    }
}

// PROSES HAPUS HADIAH
if (isset($_GET['hapus'])) {
    $id = mysqli_real_escape_string($koneksi, $_GET['hapus']);
    
    // Ambil nama hadiah dulu sebelum dihapus
    $q_old = mysqli_query($koneksi, "SELECT nama_hadiah FROM rewards WHERE id_reward = '$id'");
    $d_old = mysqli_fetch_assoc($q_old);
    $nama_hadiah_lama = $d_old['nama_hadiah'];

    mysqli_query($koneksi, "DELETE FROM penukaran WHERE id_reward = '$id'");
    $query = "DELETE FROM rewards WHERE id_reward = '$id'";
    
    if (mysqli_query($koneksi, $query)) {
        // --- LOG HAPUS REWARD ---
        $aksi_log = "Menghapus hadiah: $nama_hadiah_lama";
        mysqli_query($koneksi, "INSERT INTO log_aktivitas (nama_admin, aksi) VALUES ('$nama_admin_log', '$aksi_log')");

        header("Location: petugas_reward.php?status=terhapus");
        exit;
    } else {
        header("Location: petugas_reward.php?status=error");
        exit;
    }
}

// PROSES REFILL STOK
if (isset($_POST['btn_refill'])) {
    $id = $_POST['id_reward'];
    $tambah = $_POST['tambah_stok'];
    
    // Ambil nama hadiah
    $q_name = mysqli_query($koneksi, "SELECT nama_hadiah FROM rewards WHERE id_reward = '$id'");
    $d_name = mysqli_fetch_assoc($q_name);
    $nama_h = $d_name['nama_hadiah'];

    $sql = "UPDATE rewards SET stok = stok + $tambah WHERE id_reward = '$id'";
    
    if (mysqli_query($koneksi, $sql)) {
        // --- LOG REFILL STOK ---
        $aksi_log = "Menambah stok $nama_h sebanyak $tambah unit";
        mysqli_query($koneksi, "INSERT INTO log_aktivitas (nama_admin, aksi) VALUES ('$nama_admin_log', '$aksi_log')");

        header("Location: petugas_reward.php?status=sukses");
    } else {
        header("Location: petugas_reward.php?status=error");
    }
}
?>