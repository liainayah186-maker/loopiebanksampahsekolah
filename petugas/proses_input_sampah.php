<?php
session_start();
// 1. Pastikan path koneksi benar. Kalau file ini di dalam folder, pakai ../koneksi.php
include '../koneksi.php'; 

if (isset($_POST['simpan'])) {
    // Sesuaikan variabel koneksi (pakai $koneksi sesuai file index kamu)
    $db = $koneksi; 

    // --- TAMBAHAN: AMBIL NAMA ADMIN DARI SESSION ---
    $id_admin = $_SESSION['adminlogin'];
    $query_admin = mysqli_query($db, "SELECT nama_lengkap FROM admin WHERE id_admin = '$id_admin'");
    $data_admin = mysqli_fetch_assoc($query_admin);
    $nama_admin_log = $data_admin['nama_lengkap'] ?? 'Admin'; // Nama cadangan jika tidak ketemu
    // -----------------------------------------------

    // Ambil data dari form
    $id_target = mysqli_real_escape_string($db, $_POST['id_user']); 
    $berat = mysqli_real_escape_string($db, $_POST['berat']); 
    $poin_baru = $berat * 10; 

    // Cek dulu apakah user dengan id tersebut ada
    $cek = mysqli_query($db, "SELECT nama_kelas FROM users WHERE id_user = '$id_target'");
    
    if (mysqli_num_rows($cek) > 0) {
        $u = mysqli_fetch_assoc($cek);
        $nama_kelas = $u['nama_kelas'];
        $tgl = date('Y-m-d H:i:s');

        $sql_update = "UPDATE users SET total_poin = total_poin + $poin_baru WHERE id_user = '$id_target'";
        $eksekusi = mysqli_query($db, $sql_update);

        if ($eksekusi) {
            mysqli_query($db, "INSERT INTO setoran_sampah (id_user, berat, kelas, tanggal) 
                               VALUES ('$id_target', '$berat', '$nama_kelas', '$tgl')");
            
            // --- TAMBAHAN: KODE LOG AKTIVITAS ADMIN ---
            $aksi_log = "Input setoran " . $berat . " KG untuk kelas " . $nama_kelas;
            mysqli_query($db, "INSERT INTO log_aktivitas (nama_admin, aksi) VALUES ('$nama_admin_log', '$aksi_log')");
            // ------------------------------------------
            
            echo "<script>alert('Poin berhasil masuk & tercatat di log!'); window.location='petugas_dashboard.php';</script>";
        } else {
            // Kalau gagal, kita tampilin errornya biar tau kenapa
            echo "Gagal Update: " . mysqli_error($db);
        }
    } else {
        echo "<script>alert('ID User $id_target tidak ditemukan!'); window.history.back();</script>";
    }
}
?>