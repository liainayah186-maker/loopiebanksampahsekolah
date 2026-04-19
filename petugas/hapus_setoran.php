<?php
session_start();
require '../koneksi.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // 1. Cari tahu dulu poinnya berapa dan punya siapa
    $data = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM setoran_sampah WHERE id = '$id'"));
    
    if ($data) {
        $poin = $data['poin'];
        $kelas = $data['kelas'];

        // 2. Kurangi poin user (Koreksi)
        mysqli_query($koneksi, "UPDATE users SET total_poin = total_poin - $poin WHERE nama_kelas = '$kelas'");

        // 3. Hapus riwayatnya
        mysqli_query($koneksi, "DELETE FROM setoran_sampah WHERE id = '$id'");
        header("Location: petugas_input.php?status=hapus_sukses");
    }
}
?>