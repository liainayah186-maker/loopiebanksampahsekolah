<?php
session_start();
require '../koneksi.php';

if (!isset($_SESSION['adminlogin'])) { 
    header("Location: login.php");
    exit; 
}

// AMBIL NAMA ADMIN UNTUK LOG
$id_admin_session = $_SESSION['adminlogin'];
$q_adm = mysqli_query($koneksi, "SELECT nama_lengkap FROM admin WHERE id_admin = '$id_admin_session'");
$d_adm = mysqli_fetch_assoc($q_adm);
$nama_admin_log = $d_adm['nama_lengkap'] ?? 'Admin';

$aksi = $_GET['aksi'] ?? '';
$id_bukti = $_GET['id'] ?? '';

if ($aksi == 'terima') {
    $id_u = $_GET['user'] ?? '';
    $id_m = $_GET['misi'] ?? '';

    $query_bukti = mysqli_query($koneksi, "UPDATE bukti_misi SET status_verifikasi = 'disetujui' WHERE id_bukti = '$id_bukti'");

    if ($query_bukti) {
        // Ambil data misi & user untuk log
        $q_info = mysqli_query($koneksi, "SELECT m.judul_misi, u.nama_kelas FROM misi m, users u WHERE m.id_misi = '$id_m' AND u.id_user = '$id_u'");
        $d_info = mysqli_fetch_assoc($q_info);
        $j_misi = $d_info['judul_misi'];
        $n_kelas = $d_info['nama_kelas'];

        // --- LOG TERIMA MISI ---
        $aksi_log = "Menyetujui bukti misi ($j_misi) dari $n_kelas";
        mysqli_query($koneksi, "INSERT INTO log_aktivitas (nama_admin, aksi) VALUES ('$nama_admin_log', '$aksi_log')");

        // [KODE UPDATE PROGRES & CEK TARGET TETAP SAMA SEPERTI MILIKMU]
        $q_cap = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM bukti_misi WHERE id_user = '$id_u' AND id_misi = '$id_m' AND status_verifikasi = 'disetujui'");
        $data_cap = mysqli_fetch_assoc($q_cap);
        $jml_cap_baru = $data_cap['total'];

        mysqli_query($koneksi, "UPDATE partisipasi_misi SET jml_cap_sekarang = '$jml_cap_baru' WHERE id_user = '$id_u' AND id_misi = '$id_m' AND status_misi = 'berjalan'");

        $q_misi = mysqli_query($koneksi, "SELECT target_cap, poin_hadiah, judul_misi FROM misi WHERE id_misi = '$id_m'");
        $data_misi = mysqli_fetch_assoc($q_misi);
        
        if ($jml_cap_baru >= $data_misi['target_cap']) {
            $poin = $data_misi['poin_hadiah'];
            $judul = $data_misi['judul_misi'];
            mysqli_query($koneksi, "UPDATE users SET total_poin = total_poin + $poin WHERE id_user = '$id_u'");
            mysqli_query($koneksi, "UPDATE partisipasi_misi SET status_misi = 'selesai' WHERE id_user = '$id_u' AND id_misi = '$id_m'");

            $pesan_sukses = "Misi '$judul' kamu sudah selesai! +$poin poin telah ditambahkan.";
            mysqli_query($koneksi, "INSERT INTO notifikasi (id_user, judul, pesan, is_read, created_at) VALUES ('$id_u', 'Misi Berhasil!', '$pesan_sukses', 0, NOW())");
        }

        header("Location: petugas_validasi.php?status=acc_sukses");
        exit;
    }

} elseif ($aksi == 'tolak') {
    $id_u = $_GET['user'] ?? ''; 
    $id_m = $_GET['misi'] ?? '';
    $alasan = mysqli_real_escape_string($koneksi, $_GET['alasan'] ?? 'Foto tidak sesuai.');

    $q_info = mysqli_query($koneksi, "SELECT m.judul_misi, u.nama_kelas FROM misi m, users u WHERE m.id_misi = '$id_m' AND u.id_user = '$id_u'");
    $d_info = mysqli_fetch_assoc($q_info);
    $j_misi = $d_info['judul_misi'] ?? 'Misi';
    $n_kelas = $d_info['nama_kelas'] ?? 'Kelas';

    $query_tolak = mysqli_query($koneksi, "UPDATE bukti_misi SET status_verifikasi = 'ditolak' WHERE id_bukti = '$id_bukti'");

    if ($query_tolak) {
        // --- LOG TOLAK MISI ---
        $aksi_log = "Menolak bukti misi ($j_misi) dari $n_kelas. Alasan: $alasan";
        mysqli_query($koneksi, "INSERT INTO log_aktivitas (nama_admin, aksi) VALUES ('$nama_admin_log', '$aksi_log')");

        $isi_pesan = "Maaf, bukti misi '$j_misi' ditolak. Alasan: " . $alasan;
        $pesan_aman = mysqli_real_escape_string($koneksi, $isi_pesan);
        mysqli_query($koneksi, "INSERT INTO notifikasi (id_user, judul, pesan, is_read, created_at) VALUES ('$id_u', 'Bukti Misi Ditolak', '$pesan_aman', 0, NOW())");
        
        header("Location: petugas_validasi.php?status=tolak_sukses");
        exit;
    }
}