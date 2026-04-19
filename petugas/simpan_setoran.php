<?php
session_start();
require '../koneksi.php';

if (isset($_POST['simpan'])) {
    $kelas    = mysqli_real_escape_string($koneksi, $_POST['nama_kelas']);
    $kategori = $_POST['kategori'];
    $berat    = $_POST['berat'];
    $poin     = $_POST['poin'];
    $tanggal  = date('Y-m-d');

    // 1. Simpan ke Riwayat
    $query_ins = "INSERT INTO setoran_sampah (kelas, kategori, berat, poin, tanggal) 
                  VALUES ('$kelas', '$kategori', '$berat', '$poin', '$tanggal')";
    
    if (mysqli_query($koneksi, $query_ins)) {
        // 2. Update Total Poin di tabel Users
        mysqli_query($koneksi, "UPDATE users SET total_poin = total_poin + $poin WHERE nama_kelas = '$kelas'");
        header("Location: petugas_input.php?status=sukses");
    } else {
        header("Location: petugas_input.php?status=gagal");
    }
}
?>