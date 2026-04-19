<?php
// 1. Inisialisasi
session_start();
require 'koneksi.php';

// 2. Load PHPMailer
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (isset($_SESSION['id_user'])) { 
    header("Location: index.php"); 
    exit(); 
}

$error = "";
$success = "";

// Cari bagian proses login di login.php kamu, pastikan logikanya seperti ini:
if (isset($_POST['login_proses'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = $_POST['password']; 

    $sql = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($koneksi, $sql);

    if (mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);
        
        // --- INI KUNCI AGAR BISA LOGIN ---
        if (password_verify($password, $row['password'])) {
            $_SESSION['id_user'] = $row['id_user'];
            $_SESSION['nama_kelas'] = $row['nama_kelas'];
            header("Location: index.php");
            exit();
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Username tidak terdaftar!";
    }
}
// --- PROSES KIRIM OTP ---
if (isset($_POST['send_otp'])) {
    $input_user = mysqli_real_escape_string($koneksi, $_POST['input_lupa']);
    // Query mencari berdasarkan username atau email
    $q = mysqli_query($koneksi, "SELECT email, username, nama_kelas FROM users WHERE username = '$input_user' OR email = '$input_user'");
    
    if ($q && mysqli_num_rows($q) > 0) {
        $user = mysqli_fetch_assoc($q);
        if (empty($user['email'])) {
            $error = "Akun ini belum mendaftarkan email.";
        } else {
            $otp = (string)rand(100000, 999999);
            $_SESSION['otp_lupa'] = $otp; 
            $_SESSION['user_lupa'] = $user['username'];
            $_SESSION['waktu_otp_lupa'] = time();

            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'loopie.bank.sampah@gmail.com'; 
                $mail->Password   = 'tpvcvzxogmwbmtfm'; 
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                $mail->setFrom('loopie.bank.sampah@gmail.com', 'Loopie Security');
                $mail->addAddress($user['email']);
                $mail->isHTML(true);
                $mail->Subject = 'Kode OTP Reset Password - Loopie';
                $mail->Body    = "Halo <b>" . $user['nama_kelas'] . "</b>,<br>Kode OTP kamu adalah: <h2 style='color:#CA7842;'>$otp</h2>";

                if($mail->send()){
                    header("Location: lupa_password.php");
                    exit();
                }
            } catch (Exception $e) { $error = "Email error: " . $mail->ErrorInfo; }
        }
    } else { $error = "Akun tidak ditemukan."; }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — Loopie.</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root { --dark-oak: #4B352A; --terra-cotta: #CA7842; --cream-sand: #F0F2BD; --pure-white: #FFFFFF; }
        body { font-family: 'Inter', sans-serif; background: var(--cream-sand); display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 20px; color: var(--dark-oak); }
        .login-card { background: white; padding: 45px; border-radius: 40px; box-shadow: 0 30px 60px rgba(75, 53, 42, 0.1); width: 100%; max-width: 400px; text-align: center; }
        .logo-text { font-family: 'Poppins'; font-size: 2.5rem; font-weight: 800; letter-spacing: -2px; margin-bottom: 10px; }
        .input-group { margin-bottom: 20px; text-align: left; }
        .input-group label { display: block; font-weight: 700; font-size: 0.7rem; margin-bottom: 8px; opacity: 0.6; }
        .input-wrapper { position: relative; display: flex; align-items: center; background: #FBFBFA; border: 2px solid #F1F1F1; border-radius: 18px; transition: 0.3s; }
        .input-wrapper:focus-within { border-color: var(--terra-cotta); background: white; }
        .input-wrapper i.main-icon { margin-left: 15px; opacity: 0.4; }
        .input-neo { border: none; background: none; padding: 15px 12px; font-weight: 600; width: 100%; outline: none; }
        .btn-neo { background: var(--dark-oak); color: white; border: none; font-family: 'Poppins'; font-weight: 700; padding: 18px; width: 100%; border-radius: 20px; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .btn-neo:hover { background: var(--terra-cotta); transform: translateY(-3px); }
        .alert { padding: 12px; border-radius: 12px; margin-bottom: 20px; font-size: 13px; font-weight: 600; }
        .alert-error { background: #feebeb; color: #e74c3c; }
        #togglePassword { background: none; border: none; cursor: pointer; padding-right: 15px; display: flex; align-items: center; outline: none; }
        .forgot-link { display: block; text-align: right; margin-bottom: 15px; font-size: 0.8rem; font-weight: 700; color: var(--terra-cotta); text-decoration: none; }
    </style>
</head>
<body>

<div class="login-card">
    <div class="logo-text">Loopie<span style="color: var(--terra-cotta)">.</span></div>

    <?php if($error): ?> <div class="alert alert-error"><?= $error ?></div> <?php endif; ?>

    <?php if(isset($_GET['mode']) && $_GET['mode'] == 'lupa'): ?>
        <h3 style="margin-top:0">Reset Akun</h3>
        <form method="POST">
            <div class="input-group">
                <div class="input-wrapper">
                    <i data-lucide="mail" class="main-icon" size="18"></i>
                    <input type="text" name="input_lupa" class="input-neo" placeholder="Username / Email" required>
                </div>
            </div>
            <button type="submit" name="send_otp" class="btn-neo">KIRIM OTP</button>
            <a href="login.php" style="display:block; margin-top:15px; font-size:12px; font-weight:700; color:var(--dark-oak); text-decoration:none;">Kembali ke Login</a>
        </form>
    <?php else: ?>
        <p style="opacity:0.5; font-size: 0.9rem; margin-bottom: 25px;">Masukkan akun kelas dari petugas.</p>
        <form method="POST">
            <div class="input-group">
                <label>USERNAME</label>
                <div class="input-wrapper">
                    <i data-lucide="user" class="main-icon" size="18"></i>
                    <input type="text" name="username" class="input-neo" placeholder="Username kelas" required>
                </div>
            </div>
            <div class="input-group">
                <label>PASSWORD</label>
                <div class="input-wrapper">
                    <i data-lucide="lock" class="main-icon" size="18"></i>
                    <input type="password" name="password" id="passwordInput" class="input-neo" placeholder="••••••••" required>
                    <button type="button" id="togglePassword">
                        <i data-lucide="eye" id="eyeIcon" size="18"></i>
                    </button>
                </div>
            </div>
            <button type="submit" name="login_proses" class="btn-neo">MASUK SEKARANG</button>
        </form>
    <?php endif; ?>
</div>

<script>
    // 1. Inisialisasi awal ikon
    lucide.createIcons();

    document.addEventListener('DOMContentLoaded', function() {
        const togglePassword = document.querySelector('#togglePassword');
        const passwordInput = document.querySelector('#passwordInput');
        const eyeIcon = document.querySelector('#eyeIcon');

        // 2. Cegah error null di mode lupa password
        if (togglePassword && passwordInput) {
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);

                // Ganti nama ikon di atribut data-lucide
                if (eyeIcon) {
                    eyeIcon.setAttribute('data-lucide', type === 'text' ? 'eye-off' : 'eye');
                    lucide.createIcons(); // Refresh visual ikon
                }
            });
        }
    });
</script>
</body>
</html>