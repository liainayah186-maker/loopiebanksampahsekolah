<?php
session_start();
require '../koneksi.php';

if (!isset($_SESSION['adminlogin'])) { 
    header("Location: loginadmin.php"); 
    exit; 
}

// --- 1. AMBIL DATA ADMIN UNTUK LOG ---
$id_admin_session = $_SESSION['adminlogin'];
$q_adm = mysqli_query($koneksi, "SELECT nama_lengkap FROM admin WHERE id_admin = '$id_admin_session'");
$d_adm = mysqli_fetch_assoc($q_adm);
$nama_admin_log = $d_adm['nama_lengkap'] ?? 'Admin';


// --- 2. PROSES VALIDASI (AKSI SETUJUI) ---
if (isset($_GET['konfirmasi'])) {
    $id_tukar = mysqli_real_escape_string($koneksi, $_GET['konfirmasi']);

    // Ambil detail data sebelum di-update untuk kebutuhan Log
    $q_detail = mysqli_query($koneksi, "SELECT u.nama_kelas, r.nama_hadiah 
                FROM penukaran p 
                JOIN users u ON p.id_user = u.id_user 
                JOIN rewards r ON p.id_reward = r.id_reward 
                WHERE p.id_penukaran = '$id_tukar'");
    
    if (mysqli_num_rows($q_detail) > 0) {
        $d_detail = mysqli_fetch_assoc($q_detail);
        $kelas = $d_detail['nama_kelas'];
        $hadiah = $d_detail['nama_hadiah'];

        // Update status menjadi selesai
        $update = mysqli_query($koneksi, "UPDATE penukaran SET status = 'selesai' WHERE id_penukaran = '$id_tukar'");

        if ($update) {
            // --- SIMPAN KE LOG AKTIVITAS ---
            $aksi_log = "Memvalidasi penukaran $hadiah (Kelas: $kelas)";
            mysqli_query($koneksi, "INSERT INTO log_aktivitas (nama_admin, aksi) VALUES ('$nama_admin_log', '$aksi_log')");

            echo "<script>alert('Hadiah berhasil divalidasi!'); window.location='petugas_validasi.php';</script>";
            exit;
        }
    }
}


// --- 3. QUERY TAMPILAN HALAMAN ---

// Query untuk Antrean (Pending)
$query_pending = mysqli_query($koneksi, "SELECT p.*, u.nama_lengkap, u.nama_kelas, r.nama_hadiah 
    FROM penukaran p 
    JOIN users u ON p.id_user = u.id_user 
    JOIN rewards r ON p.id_reward = r.id_reward 
    WHERE p.status = 'pending' ORDER BY p.tanggal_tukar ASC");

// Query untuk Riwayat (Selesai)
$query_done = mysqli_query($koneksi, "SELECT p.*, u.nama_lengkap, u.nama_kelas, r.nama_hadiah 
    FROM penukaran p 
    JOIN users u ON p.id_user = u.id_user 
    JOIN rewards r ON p.id_reward = r.id_reward 
    WHERE p.status = 'selesai' ORDER BY p.tanggal_tukar DESC LIMIT 10");
?>