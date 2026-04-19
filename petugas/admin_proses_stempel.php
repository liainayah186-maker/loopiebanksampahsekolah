<?php
session_start();
require '../koneksi.php';

// Proteksi login admin
if (!isset($_SESSION['adminlogin'])) {
    header("Location: loginadmin.php");
    exit;
}

if (isset($_GET['id_user']) && isset($_GET['id_misi'])) {
    $id_user = mysqli_real_escape_string($koneksi, $_GET['id_user']);
    $id_misi = mysqli_real_escape_string($koneksi, $_GET['id_misi']);
    $tgl_sekarang = date('Y-m-d');

    // 1. Masukkan data stempel baru
    $query_stempel = "INSERT INTO progres_stempel (id_user, id_misi, tgl_diberikan) VALUES ('$id_user', '$id_misi', '$tgl_sekarang')";
    
    if (mysqli_query($koneksi, $query_stempel)) {
        
        // 2. Ambil target dan hadiah dari tabel misi
        $misi_data = mysqli_query($koneksi, "SELECT target_cap, poin_hadiah, judul_misi FROM misi WHERE id_misi = '$id_misi'");
        $m = mysqli_fetch_assoc($misi_data);
        
        $target = $m['target_cap'];
        $bonus  = $m['poin_hadiah'];
        $judul  = $m['judul_misi'];

        // 3. Hitung jumlah stempel user untuk misi ini setelah ditambah tadi
        $cek_jml = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM progres_stempel WHERE id_user = '$id_user' AND id_misi = '$id_misi'");
        $d_cek = mysqli_fetch_assoc($cek_jml);
        $total_stempel_skrg = $d_cek['total'];

        // 4. Jika jumlah stempel PAS mencapai target, berikan poin ke user
        if ($total_stempel_skrg == $target) {
            mysqli_query($koneksi, "UPDATE users SET total_poin = total_poin + $bonus WHERE id_user = '$id_user'");
            
            echo "<script>
                alert('Misi [$judul] SELESAI! Bonus $bonus poin telah masuk ke akun siswa.');
                window.location='petugas_beri_stempel.php'; 
            </script>";
        } else {
            echo "<script>
                window.location='petugas_beri_stampel.php';
            </script>";
        }
    } else {
        echo "Gagal memproses stempel: " . mysqli_error($koneksi);
    }
} else {
    header("Location: petugas_beri_stempel.php");
}
?>