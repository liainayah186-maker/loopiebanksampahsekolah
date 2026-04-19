<?php
session_start();
require 'koneksi.php';

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = "";
$success = "";

// 1. KIRIM OTP
if (isset($_POST['send_otp'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $q = mysqli_query($koneksi, "SELECT email, nama_kelas FROM users WHERE username = '$username'");
    
    if (mysqli_num_rows($q) > 0) {
        $user = mysqli_fetch_assoc($q);
        $otp = (string)rand(100000, 999999);
        $_SESSION['otp_lupa'] = $otp; 
        $_SESSION['user_lupa'] = $username; 
        $_SESSION['waktu_otp_lupa'] = time();

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'loopie.bank.sampah@gmail.com'; 
            $mail->Password = 'tpvcvzxogmwbmtfm'; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->setFrom('loopie.bank.sampah@gmail.com', 'Loopie Security');
            $mail->addAddress($user['email']);
            $mail->isHTML(true);
            $mail->Subject = 'OTP Reset Password';
            $mail->Body = "Kode OTP kamu: <h2 style='color:#CA7842;'>$otp</h2>";

            if($mail->send()) $success = "OTP dikirim ke email kamu.";
        } catch (Exception $e) { $error = "Gagal kirim email."; }
    } else { $error = "Username tidak terdaftar!"; }
}

// 2. UPDATE PASSWORD (Gunakan Password Hash)
if (isset($_POST['update_pass'])) {
    $input_otp = trim($_POST['otp_input']);
    $pass_baru = $_POST['pass_baru'];
    $username_target = $_SESSION['user_lupa'];

    if (isset($_SESSION['otp_lupa']) && $input_otp == $_SESSION['otp_lupa']) {
        // Enkripsi password baru
        $hashed = password_hash($pass_baru, PASSWORD_DEFAULT);
        $update = mysqli_query($koneksi, "UPDATE users SET password = '$hashed' WHERE username = '$username_target'");
        
        if ($update) {
            session_destroy(); 
            echo "<script>alert('Berhasil! Silakan Login.'); window.location.href='login.php';</script>";
            exit();
        }
    } else { $error = "Kode OTP salah!"; }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reset Password — Loopie.</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root { --dark-oak: #4B352A; --terra-cotta: #CA7842; --cream-sand: #F0F2BD; }
        body { background: var(--cream-sand); font-family: 'Poppins'; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .card { background: white; padding: 40px; border-radius: 30px; width: 100%; max-width: 380px; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
        .input-group { position: relative; display: flex; align-items: center; margin: 15px 0; background: #FBFBFA; border: 2px solid #F1F1F1; border-radius: 15px; }
        input { width: 100%; padding: 15px; border: none; background: none; outline: none; font-family: 'Poppins'; font-weight: 600; }
        .btn { width: 100%; padding: 16px; border: none; border-radius: 15px; font-weight: 800; cursor: pointer; color: white; margin-top: 10px; }
        #togglePass { background: none; border: none; cursor: pointer; padding-right: 15px; }
    </style>
</head>
<body>
<div class="card">
    <h2>Loopie<span style="color:var(--terra-cotta)">.</span></h2>
    <?php if($error) echo "<p style='color:red'>$error</p>"; ?>
    <?php if($success) echo "<p style='color:green'>$success</p>"; ?>

    <?php if(!isset($_SESSION['otp_lupa'])): ?>
        <form method="POST">
            <div class="input-group"><input type="text" name="username" placeholder="Username" required></div>
            <button type="submit" name="send_otp" class="btn" style="background:var(--terra-cotta)">KIRIM OTP</button>
        </form>
    <?php else: ?>
        <form method="POST">
            <div class="input-group"><input type="text" name="otp_input" placeholder="Kode OTP" required></div>
            <div class="input-group">
                <input type="password" name="pass_baru" id="passInput" placeholder="Password Baru" required>
                <button type="button" id="togglePass"><i data-lucide="eye" id="eyeIcon" size="18"></i></button>
            </div>
            <button type="submit" name="update_pass" class="btn" style="background:var(--dark-oak)">UPDATE PASSWORD</button>
        </form>
    <?php endif; ?>
    <br><a href="login.php" style="color:var(--dark-oak); text-decoration:none; font-size:12px;">Kembali ke Login</a>
</div>
<script>
    lucide.createIcons();
    const btn = document.querySelector('#togglePass');
    if(btn){
        btn.addEventListener('click', () => {
            const input = document.querySelector('#passInput');
            const icon = document.querySelector('#eyeIcon');
            const isPass = input.type === 'password';
            input.type = isPass ? 'text' : 'password';
            icon.setAttribute('data-lucide', isPass ? 'eye-off' : 'eye');
            lucide.createIcons();
        });
    }
</script>
</body>
</html>