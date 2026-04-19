<?php
include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $wali_kelas = mysqli_real_escape_string($koneksi, $_POST['wali_kelas']);
    $new_password = $_POST['new_password']; // Ambil password baru

    // Cek apakah username dan wali kelas cocok
    $query = mysqli_query($koneksi, "SELECT * FROM users WHERE username = '$username' AND wali_kelas = '$wali_kelas'");
    
    if (mysqli_num_rows($query) > 0) {
        // Jika cocok, update password (langsung simpan tanpa hash jika kamu belum pakai password_hash)
        $update = mysqli_query($koneksi, "UPDATE users SET password = '$new_password' WHERE username = '$username'");
        
        if ($update) {
            echo "<script>alert('Password berhasil diperbarui! Silakan login.'); window.location.href='login.php';</script>";
        } else {
            echo "<script>alert('Gagal memperbarui password.'); window.history.back();</script>";
        }
    } else {
        // Jika data tidak cocok
        echo "<script>alert('Data tidak cocok! Pastikan Username dan Wali Kelas benar.'); window.history.back();</script>";
    }
}
?>