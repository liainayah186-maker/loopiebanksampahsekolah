<?php
session_start();
require 'koneksi.php';

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = $_POST['password'];

    // Ambil data user berdasarkan username
    $query = mysqli_query($koneksi, "SELECT * FROM users WHERE username = '$username'");
    
    if (mysqli_num_rows($query) === 1) {
        $data = mysqli_fetch_assoc($query);

        // Memverifikasi password hash
        if (password_verify($password, $data['password'])) {
            
            $_SESSION['id_user']    = $data['id_user'];
            $_SESSION['username']   = $data['username'];
            // SESUAIKAN: Di DB kamu 'nama_kelas', bukan 'nama_siswa'
            $_SESSION['nama_kelas'] = $data['nama_kelas']; 
            $_SESSION['total_poin'] = $data['total_poin'];
            $_SESSION['user_level'] = 'user';

            header("Location: index.php");
            exit;
        } else {
            // Password Salah
            $pesan_error = "Password kamu salah!";
            $icon = "error";
        }
    } else {
        // Username Tidak Ada
        $pesan_error = "Username tidak terdaftar!";
        $icon = "warning";
    }

    // Jika ada error, tampilkan SweetAlert
    if (isset($pesan_error)) {
        echo "
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <style>
            .swal2-popup { font-family: 'Poppins', sans-serif !important; border-radius: 25px !important; }
        </style>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Waduh!',
                    text: '$pesan_error',
                    icon: '$icon',
                    background: '#F0F2BD',
                    color: '#4B352A',
                    confirmButtonColor: '#CA7842',
                    confirmButtonText: 'Coba Lagi'
                }).then(() => { 
                    window.location.href='login.php'; 
                });
            });
        </script>";
    }
}
?>