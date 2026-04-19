<?php
// 1. WAJIB PALING ATAS
session_start();
require 'koneksi.php';

// Load PHPMailer sesuai cara Register kamu
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 2. CEK APAKAH MASIH LOGIN
if (!isset($_SESSION['id_user'])) { 
    header("Location: login.php"); 
    exit(); 
}

$id_user = $_SESSION['id_user'];
$error = "";
$success = "";

// Ambil data user dari database
$q = mysqli_query($koneksi, "SELECT email, nama_kelas FROM users WHERE id_user = '$id_user'");
$user = mysqli_fetch_assoc($q);
$email_tujuan = $user['email'];

// --- PROSES 1: KIRIM OTP ---
if (isset($_POST['send_otp'])) {
    $otp = (string)rand(100000, 999999);
    $_SESSION['otp_perubahan'] = $otp; // Simpan di session dengan nama unik
    $_SESSION['waktu_otp'] = time();

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
        $mail->addAddress($email_tujuan);
        $mail->isHTML(true);
        $mail->Subject = 'Kode OTP Ganti Password';
        $mail->Body    = "Halo " . $user['nama_kelas'] . ", kode OTP kamu adalah: <b>$otp</b>";

        if($mail->send()){
            $success = "Kode OTP berhasil dikirim ke $email_tujuan";
        }
    } catch (Exception $e) {
        $error = "Gagal kirim email: " . $mail->ErrorInfo;
    }
}

// --- PROSES 2: VERIFIKASI & UPDATE ---
if (isset($_POST['update_pass'])) {
    $input_otp = trim($_POST['otp_input']);
    $pass_baru = $_POST['pass_baru'];

    // Cek apakah kode di session ada dan cocok?
    if (isset($_SESSION['otp_perubahan']) && $input_otp == $_SESSION['otp_perubahan']) {
        
        // Cek durasi (5 menit)
        if (time() - $_SESSION['waktu_otp'] > 300) {
            $error = "Kode sudah kadaluarsa, silakan kirim ulang.";
            unset($_SESSION['otp_perubahan']);
        } else {
            // BERHASIL! Update password di database
            $hashed = password_hash($pass_baru, PASSWORD_DEFAULT);
            $update = mysqli_query($koneksi, "UPDATE users SET password = '$hashed' WHERE id_user = '$id_user'");
            
            if ($update) {
                unset($_SESSION['otp_perubahan']);
                echo "<script>alert('Password Berhasil Diubah!'); window.location.href='profil.php';</script>";
                exit();
            }
        }
    } else {
        $error = "Kode verifikasi salah! Cek kembali email kamu.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ubah Password — Loopie</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root { 
            --dark-oak: #4B352A;
            --terra-cotta: #CA7842;
            --moss-green: #B2CD9C; 
            --cream-sand: #F0F2BD;
            --pure-white: #FFFFFF;
        }

        body { 
            background: linear-gradient(135deg, var(--dark-oak) 0%, #2D1E17 100%);
            font-family: 'Inter', sans-serif; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            min-height: 100vh; 
            margin: 0;
            padding: 20px;
        }

        .card { 
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 40px; 
            border-radius: 40px; 
            width: 100%; 
            max-width: 400px; 
            box-shadow: 0 25px 50px rgba(0,0,0,0.3); 
            text-align: center;
            border: 1px solid rgba(255,255,255,0.3);
        }

        .icon-circle {
            width: 70px;
            height: 70px;
            background: var(--cream-sand);
            border-radius: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: var(--terra-cotta);
        }

        h3 { 
            font-family: 'Poppins', sans-serif;
            color: var(--dark-oak); 
            font-weight: 800;
            font-size: 1.5rem;
            margin-bottom: 10px; 
            letter-spacing: -1px;
        }

        .info { 
            font-size: 14px; 
            color: #666; 
            margin-bottom: 25px; 
            line-height: 1.5;
        }

        .info b { color: var(--terra-cotta); }

        input { 
            width: 100%; 
            padding: 15px; 
            margin: 10px 0; 
            border: 2px solid #F0F2BD; 
            border-radius: 18px; 
            box-sizing: border-box; 
            font-family: inherit;
            font-weight: 600; 
            text-align: center; 
            background: #F9FAEF;
            transition: all 0.3s ease;
        }

        input:focus {
            outline: none;
            border-color: var(--terra-cotta);
            background: white;
            box-shadow: 0 5px 15px rgba(202, 120, 66, 0.1);
        }

        .btn { 
            width: 100%; 
            padding: 16px; 
            border: none; 
            border-radius: 18px; 
            font-weight: 800; 
            font-size: 15px;
            cursor: pointer; 
            transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
            margin-top: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-send { background: var(--terra-cotta); color: white; }
        .btn-verify { background: var(--dark-oak); color: white; }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            filter: brightness(1.1);
        }

        .alert-error { 
            color: #e74c3c; 
            font-size: 13px; 
            font-weight: 600; 
            background: #feebeb; 
            padding: 12px; 
            border-radius: 15px; 
            margin-bottom: 15px; 
            border: 1px solid rgba(231, 76, 60, 0.1);
        }

        .alert-success { 
            color: #27ae60; 
            font-size: 13px; 
            font-weight: 600; 
            background: #eafff1; 
            padding: 12px; 
            border-radius: 15px; 
            margin-bottom: 15px; 
            border: 1px solid rgba(39, 174, 96, 0.1);
        }

        .link-text {
            display: inline-block;
            margin-top: 20px;
            font-size: 13px;
            color: #999;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
        }

        .link-text:hover { color: var(--terra-cotta); }

        hr { margin: 25px 0; border: 0; border-top: 1px solid rgba(75, 53, 42, 0.05); }
    </style>
</head>
<body>

<div class="card">
    <div class="icon-circle">
        <i data-lucide="<?= !isset($_SESSION['otp_perubahan']) ? 'mail' : 'shield-check' ?>" size="32"></i>
    </div>

    <h3>Ubah Password</h3>
    <p class="info">Demi keamanan, masukkan kode yang kami kirim ke email <b><?= htmlspecialchars($email_tujuan) ?></b></p>

    <?php if(isset($error) && $error): ?> <div class="alert-error"><?= $error ?></div> <?php endif; ?>
    <?php if(isset($success) && $success): ?> <div class="alert-success"><?= $success ?></div> <?php endif; ?>

    <?php if(!isset($_SESSION['otp_perubahan'])): ?>
        <form method="POST">
            <button type="submit" name="send_otp" class="btn btn-send">
                <i data-lucide="send" size="18"></i> KIRIM KODE OTP
            </button>
        </form>
    <?php else: ?>
        <form method="POST">
            <input type="text" name="otp_input" placeholder="6 Digit Kode OTP" required maxlength="6" autocomplete="off">
            <input type="password" name="pass_baru" placeholder="Password Baru" required>
            <button type="submit" name="update_pass" class="btn btn-verify">
                <i data-lucide="lock" size="18"></i> KONFIRMASI GANTI
            </button>
            <p style="margin-top: 20px;">
                <a href="ganti_password.php" style="font-size: 12px; color: var(--terra-cotta); text-decoration: none; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;">Kirim Ulang / Batal</a>
            </p>
        </form>
    <?php endif; ?>

    <hr>
    <a href="profil.php" class="link-text">
        <i data-lucide="arrow-left" size="16" style="vertical-align: middle; margin-right: 5px;"></i> Kembali ke Profil
    </a>
</div>

<script>
    lucide.createIcons();
</script>

</body>
</html>