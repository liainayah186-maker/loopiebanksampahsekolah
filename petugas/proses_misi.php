<?php
session_start();
require '../koneksi.php';

if (!isset($_SESSION['adminlogin'])) {
    header("Location: loginadmin.php");
    exit;
}

// --- TAMBAHAN: AMBIL NAMA ADMIN UNTUK LOG ---
$id_admin_session = $_SESSION['adminlogin'];
$q_adm = mysqli_query($koneksi, "SELECT nama_lengkap FROM admin WHERE id_admin = '$id_admin_session'");
$d_adm = mysqli_fetch_assoc($q_adm);
$nama_admin_log = $d_adm['nama_lengkap'] ?? 'Admin';
// -----------------------------------------------

// --- TAMBAH MISI ---
if (isset($_POST['tambah_misi'])) {
    $judul     = mysqli_real_escape_string($koneksi, $_POST['judul']);
    $icon      = mysqli_real_escape_string($koneksi, $_POST['icon']);
    $tgl_mulai = $_POST['tgl_mulai'];
    $tgl_selesai = $_POST['tgl_selesai'];
    $target    = $_POST['target'];
    $bonus     = $_POST['bonus'];
    $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);

    $query = "INSERT INTO misi (judul_misi, deskripsi, icon, tgl_mulai, tgl_selesai, target_cap, poin_hadiah) 
              VALUES ('$judul', '$deskripsi', '$icon', '$tgl_mulai', '$tgl_selesai', '$target', '$bonus')";

    if (mysqli_query($koneksi, $query)) {
        // --- LOG TAMBAH MISI ---
        $aksi_log = "Membuat misi baru: " . $judul;
        mysqli_query($koneksi, "INSERT INTO log_aktivitas (nama_admin, aksi) VALUES ('$nama_admin_log', '$aksi_log')");
        
        header("Location: petugas_misi.php?pesan=berhasil");
    } else {
        echo "Error: " . mysqli_error($koneksi);
    }
}

// --- EDIT MISI ---
if (isset($_POST['edit_misi'])) {
    $id        = $_POST['id_misi'];
    $judul     = mysqli_real_escape_string($koneksi, $_POST['judul']);
    $target    = $_POST['target'];
    $bonus     = $_POST['bonus'];
    $icon      = mysqli_real_escape_string($koneksi, $_POST['icon']);

    $query = "UPDATE misi SET 
                judul_misi='$judul', 
                target_cap='$target', 
                poin_hadiah='$bonus', 
                icon='$icon' 
              WHERE id_misi='$id'";

    if (mysqli_query($koneksi, $query)) {
        // --- LOG EDIT MISI ---
        $aksi_log = "Mengubah detail misi: " . $judul;
        mysqli_query($koneksi, "INSERT INTO log_aktivitas (nama_admin, aksi) VALUES ('$nama_admin_log', '$aksi_log')");
        
        header("Location: petugas_misi.php?pesan=diupdate");
    } else {
        echo "Error: " . mysqli_error($koneksi);
    }
}

// --- HAPUS MISI ---
if (isset($_GET['hapus'])) {
    $id_misi = mysqli_real_escape_string($koneksi, $_GET['hapus']);
    
    // Ambil judul misi dulu sebelum dihapus untuk log
    $q_misi = mysqli_query($koneksi, "SELECT judul_misi FROM misi WHERE id_misi = '$id_misi'");
    $d_misi = mysqli_fetch_assoc($q_misi);
    $judul_lama = $d_misi['judul_misi'];

    mysqli_query($koneksi, "DELETE FROM bukti_misi WHERE id_misi = '$id_misi'");
    mysqli_query($koneksi, "DELETE FROM partisipasi_misi WHERE id_misi = '$id_misi'");
    
    if (mysqli_query($koneksi, "DELETE FROM misi WHERE id_misi = '$id_misi'")) {
        // --- LOG HAPUS MISI ---
        $aksi_log = "Menghapus misi: " . $judul_lama;
        mysqli_query($koneksi, "INSERT INTO log_aktivitas (nama_admin, aksi) VALUES ('$nama_admin_log', '$aksi_log')");
        
        header("Location: petugas_misi.php?pesan=terhapus");
    } else {
        echo "Error saat menghapus misi: " . mysqli_error($koneksi);
    }
}
?>