<?php
session_start();
require '../koneksi.php';

// --- TAMBAHAN: AMBIL NAMA ADMIN DARI SESSION ---
$id_admin_session = $_SESSION['adminlogin'];
$q_adm = mysqli_query($koneksi, "SELECT nama_lengkap FROM admin WHERE id_admin = '$id_admin_session'");
$d_adm = mysqli_fetch_assoc($q_adm);
$nama_admin_log = $d_adm['nama_lengkap'] ?? 'Admin';
// -----------------------------------------------

// --- 1. PROSES HAPUS ---
if (isset($_GET['hapus'])) {
    $id = mysqli_real_escape_string($koneksi, $_GET['hapus']);
    
    // Ambil nama kelas dulu buat catatan di log sebelum dihapus
    $cek_nama = mysqli_query($koneksi, "SELECT nama_kelas FROM users WHERE id_user = '$id'");
    $data_lama = mysqli_fetch_assoc($cek_nama);
    $nama_kelas_dihapus = $data_lama['nama_kelas'];

    mysqli_query($koneksi, "DELETE FROM penukaran WHERE id_user = '$id'");
    mysqli_query($koneksi, "DELETE FROM partisipasi_misi WHERE id_user = '$id'");
    mysqli_query($koneksi, "DELETE FROM bukti_misi WHERE id_user = '$id'");

    $query = mysqli_query($koneksi, "DELETE FROM users WHERE id_user = '$id'");
    
    if ($query) {
        // --- LOG HAPUS ---
        $aksi_log = "Menghapus akun siswa/kelas: " . $nama_kelas_dihapus;
        mysqli_query($koneksi, "INSERT INTO log_aktivitas (nama_admin, aksi) VALUES ('$nama_admin_log', '$aksi_log')");
        
        header("Location: petugas_data_siswa.php?status=hapus");
    } else {
        echo "Masih ada tabel lain yang mengikat! Error: " . mysqli_error($koneksi);
        die();
    }
    exit;
}

// --- 2. PROSES TAMBAH, EDIT, & RESET ---
if (isset($_POST['aksi'])) {
    $aksi = $_POST['aksi'];
    $id   = isset($_POST['id']) ? mysqli_real_escape_string($koneksi, $_POST['id']) : '';
    $nama = isset($_POST['nama']) ? mysqli_real_escape_string($koneksi, $_POST['nama']) : '';
    $wali = isset($_POST['wali']) ? mysqli_real_escape_string($koneksi, $_POST['wali']) : '';
    $user = isset($_POST['user']) ? mysqli_real_escape_string($koneksi, $_POST['user']) : '';
    $pass = isset($_POST['pass']) ? $_POST['pass'] : '';

    if ($aksi == 'tambah') {
        $cek = mysqli_query($koneksi, "SELECT * FROM users WHERE username = '$user'");
        if (mysqli_num_rows($cek) > 0) {
            header("Location: petugas_data_siswa.php?status=duplikat");
            exit;
        }

        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $query = mysqli_query($koneksi, "INSERT INTO users (nama_kelas, wali_kelas, username, password, total_poin, email) 
                                         VALUES ('$nama', '$wali', '$user', '$hash', 0, '')");
        
        // --- LOG TAMBAH ---
        if ($query) {
            $aksi_log = "Menambah data siswa baru: " . $nama;
            mysqli_query($koneksi, "INSERT INTO log_aktivitas (nama_admin, aksi) VALUES ('$nama_admin_log', '$aksi_log')");
        }
    } 
    
    elseif ($aksi == 'edit') {
        if (!empty($pass)) {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $query = mysqli_query($koneksi, "UPDATE users SET nama_kelas='$nama', wali_kelas='$wali', username='$user', password='$hash' WHERE id_user='$id'");
        } else {
            $query = mysqli_query($koneksi, "UPDATE users SET nama_kelas='$nama', wali_kelas='$wali', username='$user' WHERE id_user='$id'");
        }

        // --- LOG EDIT ---
        if ($query) {
            $aksi_log = "Mengubah identitas data siswa: " . $nama;
            mysqli_query($koneksi, "INSERT INTO log_aktivitas (nama_admin, aksi) VALUES ('$nama_admin_log', '$aksi_log')");
        }
    }

    elseif ($aksi == 'reset_pass') {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $query = mysqli_query($koneksi, "UPDATE users SET password='$hash' WHERE id_user='$id'");

        // --- LOG RESET PASSWORD ---
        if ($query) {
            $aksi_log = "Mereset password akun: " . $user;
            mysqli_query($koneksi, "INSERT INTO log_aktivitas (nama_admin, aksi) VALUES ('$nama_admin_log', '$aksi_log')");
        }
    }

    if ($query) {
        header("Location: petugas_data_siswa.php?status=sukses");
    } else {
        header("Location: petugas_data_siswa.php?status=gagal");
    }
    exit;
}
?>