<?php
session_start();
include '../koneksi.php';

// Cek login admin
if (!isset($_SESSION['adminlogin'])) {
    header("Location: loginadmin.php");
    exit;
}

if (isset($_GET['id'])) {
    // Ambil ID dan sanitasi untuk keamanan
    $id_notif = mysqli_real_escape_string($koneksi, $_GET['id']);

    // Query hapus berdasarkan id_notif (sesuai database kamu)
    $query = "DELETE FROM notifikasi WHERE id_notif = '$id_notif'";
    
    if (mysqli_query($koneksi, $query)) {
        // Jika berhasil, kembali ke halaman siaran dengan pesan sukses
        echo "<script>
                alert('Pesan berhasil dihapus!');
                window.location='petugas_broadcast.php';
              </script>";
    } else {
        // Jika gagal
        echo "<script>
                alert('Gagal menghapus pesan: " . mysqli_error($koneksi) . "');
                window.location='petugas_broadcast.php';
              </script>";
    }
} else {
    // Jika diakses tanpa ID
    header("Location: petugas_broadcast.php");
    exit;
}
?>