<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['id_user'])) { 
    header("Location: home.php"); 
    exit(); 
}

$id_user = $_SESSION['id_user'];

// 1. Ambil data user lengkap
$query_user = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user = '$id_user'");
$data = mysqli_fetch_assoc($query_user);
$current_page = basename($_SERVER['PHP_SELF']);


if (!$data) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Inisialisasi variabel dari database users
$nama_kelas = $data['nama_kelas'];
$wali_kelas = $data['wali_kelas'];
$total_poin = $data['total_poin'];
$email_user = $data['email'];
$avatar = $data['avatar'];

// --- 2. QUERY NOTIFIKASI (Sama seperti index.php) ---
$q_notif = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM notifikasi WHERE (id_user = '$id_user' OR id_user IS NULL) AND is_read = 0");
$unread_count = mysqli_fetch_assoc($q_notif)['total'] ?? 0;

$query_notif_list = mysqli_query($koneksi, "SELECT * FROM notifikasi WHERE (id_user = '$id_user' OR id_user IS NULL) ORDER BY created_at DESC LIMIT 4");


// 3. Hitung total BERAT setoran (Kg) - BERDASARKAN NAMA KELAS (Sesuai tabel setoran_sampah kamu)
$total_setoran = "0 Kg";
$sql_berat = "SELECT SUM(berat) as total FROM setoran_sampah WHERE kelas = '$nama_kelas'";
$query_berat = mysqli_query($koneksi, $sql_berat);
if ($query_berat) {
    $row_berat = mysqli_fetch_assoc($query_berat);
    $total_setoran = ($row_berat['total'] > 0) ? number_format($row_berat['total'], 2) . " Kg" : "0 Kg";
}

// 4. Hitung TOTAL KALI setoran (Berapa kali transaksi)
$total_transaksi = 0;
$sql_count = "SELECT COUNT(*) as total_trx FROM setoran_sampah WHERE kelas = '$nama_kelas'";
$query_count = mysqli_query($koneksi, $sql_count);
if ($query_count) {
    $row_count = mysqli_fetch_assoc($query_count);
    $total_transaksi = $row_count['total_trx'] ?? 0;
}
// 5. Ambil Riwayat Foto Bukti Misi (Disesuaikan dengan foto database Lia)
$sql_bukti = "SELECT foto_bukti, tgl_upload FROM bukti_misi WHERE id_user = '$id_user' AND foto_bukti IS NOT NULL ORDER BY tgl_upload DESC LIMIT 6";
$query_bukti = mysqli_query($koneksi, $sql_bukti);

if (!$query_bukti) {
    die("Query Error: " . mysqli_error($koneksi));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Kelas — Loopie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="assets/loopie.css">
    
    <style>
        :root { 
            --dark-oak: #4B352A;
            --terra-cotta: #CA7842;
            --moss-green: #B2CD9C; 
            --cream-sand: #F0F2BD;
            --pure-white: #FFFFFF;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body { 
            font-family: 'Inter', sans-serif; 
            background-color: var(--cream-sand); 
            color: var(--dark-oak);
            margin: 0;
        }

        h1, h2, h3, h4, .logo { font-family: 'Poppins', sans-serif; font-weight: 700; }

        /* --- NAVBAR --- */
        .navbar {
            background: rgba(240, 242, 189, 0.9);
            backdrop-filter: blur(15px);
            padding: 1.2rem 10%;
            display: flex; justify-content: space-between; align-items: center;
            position: sticky; top: 0; z-index: 1000;
            border-bottom: 1px solid rgba(75, 53, 42, 0.1);
        }

        .logo { font-size: 1.8rem; text-decoration: none; color: var(--dark-oak); font-weight: 800; letter-spacing: -1.5px; }
        .logo-dot { color: var(--terra-cotta); }

        .nav-links { display: flex; gap: 2.5rem; }
        .nav-link { text-decoration: none; color: var(--dark-oak); font-weight: 600; opacity: 0.7; transition: var(--transition); }
        .nav-link:hover { opacity: 1; color: var(--terra-cotta); }

        .poin-pill { 
        background: var(--dark-oak); 
        color: var(--cream-sand); 
        padding: 0.7rem 1.4rem; 
        border-radius: 50px; 
        display: flex; 
        align-items: center; 
        gap: 10px; 
        font-weight: 700; 
        box-shadow: 0 5px 20px rgba(75, 53, 42, 0.2); 
    }

        .notif-badge {
            position: absolute; top: 0; right: 0; background: var(--terra-cotta); color: white; width: 18px; height: 18px; border-radius: 50%; font-size: 0.65rem; display: flex; align-items: center; justify-content: center; font-weight: 700;
        }

        .gallery-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 15px;
    margin-top: 20px;
}

.gallery-item {
    aspect-ratio: 1 / 1;
    border-radius: 20px;
    overflow: hidden;
    background: #f0f0f0;
    border: 3px solid var(--pure-white);
    box-shadow: 0 10px 20px rgba(75, 53, 42, 0.05);
    transition: var(--transition);
    position: relative;
}

.gallery-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.gallery-item:hover {
    transform: scale(1.05) rotate(2deg);
    z-index: 2;
}

.empty-gallery {
    padding: 40px;
    text-align: center;
    background: #F9FAEF;
    border-radius: 25px;
    border: 2px dashed rgba(75, 53, 42, 0.1);
}

        /* --- LAYOUT PROFIL --- */
        .profile-wrapper {
            max-width: 1200px; margin: 60px auto; padding: 0 10%;
            display: grid; grid-template-columns: 380px 1fr; gap: 40px;
        }

        .id-card {
            background: var(--pure-white); border-radius: 40px; padding: 50px 40px;
            text-align: center; box-shadow: 0 20px 50px rgba(75, 53, 42, 0.05);
            height: fit-content; position: sticky; top: 120px;
        }

        .avatar-box {
            width: 120px; height: 120px; background: var(--cream-sand);
            border-radius: 45px; display: flex; align-items: center; justify-content: center;
            margin: 0 auto 25px; color: var(--dark-oak); overflow: hidden;
        }

        .avatar-img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-initials-large { font-weight: 800; font-size: 2.5rem; color: var(--dark-oak); }

        .data-card {
            background: var(--pure-white); border-radius: 35px; padding: 35px;
            margin-bottom: 25px; box-shadow: 0 20px 50px rgba(75, 53, 42, 0.05);
        }

        .stat-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px; }

        .info-row {
            display: flex; justify-content: space-between; padding: 18px 0;
            border-bottom: 1px solid rgba(75, 53, 42, 0.05);
        }

        .security-item {
            background: #F9FAEF; padding: 22px; border-radius: 25px;
            margin-top: 15px; display: flex; justify-content: space-between;
            align-items: center; border: 1px solid rgba(178, 205, 156, 0.2);
        }

        .badge-pill {
            background: rgba(202, 120, 66, 0.1); color: var(--terra-cotta);
            padding: 8px 20px; border-radius: 50px; font-size: 0.75rem; font-weight: 800;
        }

        @media (max-width: 992px) {
            .profile-wrapper { grid-template-columns: 1fr; padding: 0 5%; }
            .nav-links { display: none; }
            .id-card { position: static; }
        }
        /* Container harus relative agar dropdown patuh pada posisi icon */
.notif-container {
    position: relative;
    display: inline-block;
}

.notif-dropdown {
    position: absolute;
    top: calc(100% + 15px); /* Muncul di bawah tombol dengan jarak 15px */
    right: 0;               /* Rata kanan dengan tombol */
    width: 320px;
    background: white;
    border-radius: 20px;
    box-shadow: 0 15px 35px rgba(75, 53, 42, 0.15);
    display: none;          /* Sembunyi secara default */
    z-index: 9999;
    overflow: hidden;
    border: 1px solid rgba(75, 53, 42, 0.05);
}

/* Class active yang dipanggil oleh fungsi toggleNotif(event) */
.notif-dropdown.active {
    display: block;
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Styling isi notifikasi agar lebih rapi */
.notif-header {
    padding: 15px 20px;
    background: #FBFBFA;
    font-weight: 700;
    color: var(--dark-oak);
    border-bottom: 1px solid #eee;
    font-size: 0.9rem;
}

.notif-item {
    padding: 15px 20px;
    border-bottom: 1px solid #f5f5f5;
    font-size: 0.85rem;
    color: #555;
    line-height: 1.4;
    transition: 0.2s;
}

.notif-item:hover {
    background: #fdfdfd;
}

.notif-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: var(--terra-cotta);
    color: white;
    font-size: 10px;
    font-weight: 800;
    padding: 2px 6px;
    border-radius: 10px;
    border: 2px solid white;
}
/* Container Link Profil */
.nav-profile-link {
    position: relative;
    display: flex;
    align-items: center;
    padding: 2px;
    transition: var(--transition);
}

/* State Aktif untuk Profil */
.nav-profile-link.profile-active .nav-avatar-img {
    border-color: var(--terra-cotta);
    box-shadow: 0 0 0 3px rgba(202, 120, 66, 0.2); /* Efek ring halus */
    transform: scale(1.05); /* Sedikit lebih besar */
}

/* Titik indikator di bawah foto */
.active-dot {
    position: absolute;
    bottom: -8px;
    left: 50%;
    transform: translateX(-50%);
    width: 5px;
    height: 5px;
    background: var(--terra-cotta);
    border-radius: 50%;
}

.nav-avatar-img { 
    width: 40px; 
    height: 40px; 
    border-radius: 50%; 
    border: 2px solid transparent; /* Defaultnya transparan */
    object-fit: cover; 
    transition: all 0.3s ease;
}
/* --- TOMBOL KEMBALI --- */
.nav-left {
    display: flex;
    align-items: center;
    gap: 15px;
}

.btn-back-circle {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: var(--pure-white);
    border: 1px solid rgba(75, 53, 42, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--dark-oak);
    text-decoration: none;
    transition: var(--transition);
    box-shadow: 0 4px 12px rgba(75, 53, 42, 0.05);
}

.btn-back-circle i {
    width: 20px;
    height: 20px;
}

.btn-back-circle:hover {
    background: var(--dark-oak);
    color: var(--pure-white);
    transform: translateX(-3px); /* Efek geser sedikit ke kiri saat di-hover */
    box-shadow: 0 6px 15px rgba(75, 53, 42, 0.15);
}

/* Responsif untuk HP */
@media (max-width: 480px) {
    .btn-back-circle {
        width: 38px;
        height: 38px;
    }
    
    .logo-text {
        font-size: 1.2rem;
    }
}
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="nav-left">
        
    <a href="index.php" class="btn-back-circle">
        <i data-lucide="chevron-left"></i>
    </a>
    
    <a href="index.php" class="logo">Loopie<span class="logo-dot">.</span></a>
    </div>  
    
    <div class="nav-links">
        <a href="index.php" class="nav-link">Beranda</a>
        <a href="misi.php" class="nav-link">Misi Hijau</a>
        <a href="index.php#leaderboard" class="nav-link">Peringkat</a>
        <a href="riwayat.php" class="nav-link">Riwayat</a>
    </div>

    <div class="nav-right" style="display: flex; align-items: center; gap: 18px;">
    
    <div class="notif-container">
                <button class="notif-btn" onclick="toggleNotif(event)" style="background: transparent; border: none; cursor: pointer; color: var(--dark-oak); padding: 5px; display: flex; position: relative;">
                    <i data-lucide="bell"></i> 
                    <?php if($unread_count > 0): ?>
                        <span class="notif-badge"><?= $unread_count ?></span>
                    <?php endif; ?>
                </button>

                <div id="notifDropdown" class="notif-dropdown">
                    <div class="notif-header">Notifikasi Terbaru</div>
                    <div class="notif-body">
                        <?php if(mysqli_num_rows($query_notif_list) > 0): ?>
                            <?php while($n = mysqli_fetch_assoc($query_notif_list)): ?>
                                <div class="notif-item">
                                    <?= htmlspecialchars($n['pesan']) ?>
                                    <div style="font-size: 0.7rem; opacity: 0.5; margin-top: 4px;"><?= date('d M, H:i', strtotime($n['created_at'])) ?></div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="p-3 text-center text-muted" style="font-size: 0.8rem;">Belum ada notifikasi</div>
                        <?php endif; ?>
                    </div>
                    <a href="notifikasi.php" style="padding: 10px; text-align: center; display: block; font-size: 0.8rem; color: var(--terra-cotta); font-weight: 600; text-decoration: none; background: #f9f9f9;">Lihat Semua</a>
                </div>
            </div>

    <div class="poin-pill">
        <i data-lucide="zap" size="18"></i>
        <span><?= number_format($data['total_poin'] ?? 0) ?> <small>PTS</small></span>
    </div>

   <a href="profil.php" class="nav-profile-link <?= ($current_page == 'profil.php') ? 'profile-active' : '' ?>">
    <?php if(!empty($avatar) && file_exists("uploads/profil/".$avatar)): ?>
        <img src="uploads/profil/<?= $avatar ?>" class="nav-avatar-img" alt="Profile">
    <?php else: ?>
        <div class="nav-avatar-initials">
            <?= strtoupper(substr($nama_kelas, 0, 1)) ?>
        </div>
    <?php endif; ?>
    
    <?php if($current_page == 'profil.php'): ?>
        <span class="active-dot"></span>
    <?php endif; ?>
</a>
    </div>
    </nav>

    <style>
<style>
    .nav-avatar-img { width: 40px; height: 40px; border-radius: 50%; border: 2px solid var(--terra-cotta); object-fit: cover; }
    .nav-avatar-initials { 
        width: 40px; height: 40px; background: var(--moss-green); color: var(--dark-oak); 
        display: flex; align-items: center; justify-content: center; border-radius: 50%; 
        font-weight: 800; border: 2px solid var(--dark-oak);
    }
</style>
        
</nav>
    <div class="profile-wrapper">
        <aside>
            <div class="id-card">
                <div class="avatar-box">
                    <?php if(!empty($avatar)): ?>
                        <img src="uploads/profil/<?= $avatar ?>" class="avatar-img">
                    <?php else: ?>
                        <div class="avatar-initials-large"><?= strtoupper(substr($nama_kelas, 0, 2)) ?></div>
                    <?php endif; ?>
                </div>
                <h2 style="margin-bottom: 5px;"><?= $nama_kelas ?></h2>
                <span class="badge-pill">ECO GUARDIAN</span>
                
                <div style="margin-top: 35px; text-align: left;">
                    <div class="info-row">
                        <span style="opacity: 0.6;">Wali Kelas</span>
                        <span style="font-weight: 700;"><?= $wali_kelas ?></span>
                    </div>
                   <div class="info-row">
    <span style="opacity: 0.6;">Email</span>
    <span style="font-weight: 700; color: var(--terra-cotta);">
        <?= !empty($email_user) ? htmlspecialchars($email_user) : '-' ?>
    </span>
</div>
                    <div class="info-row">
                        <span style="opacity: 0.6;">Total Setoran</span>
                        <span style="font-weight: 700;"><?= $total_setoran ?></span>
                    </div>
                    <div class="info-row" style="border-bottom: none;">
                        <span style="opacity: 0.6;">Saldo Poin</span>
                        <span style="font-weight: 800; color: var(--terra-cotta);"><?= number_format($total_poin) ?> PTS</span>
                    </div>
                </div>

                <div style="margin-top: 35px; display: flex; flex-direction: column; gap: 12px;">
                    <button class="btn" style="width: 100%; background: var(--dark-oak); color: white; border-radius: 18px; padding: 14px; font-weight: 700;" onclick="window.location.href='edit_profil.php'">
                        <i data-lucide="edit-3" size="18" class="me-2"></i> Edit Profil
                    </button>
                    <button class="btn" style="width: 100%; background: #FFF1F0; color: #E74C3C; border-radius: 18px; padding: 14px; font-weight: 700;" onclick="if(confirm('Yakin ingin keluar?')) window.location.href='logout.php'">
                        <i data-lucide="log-out" size="18" class="me-2"></i> Keluar
                    </button>
                </div>
            </div>
        </aside>

        <main>
            <div class="stat-grid">
                <div class="data-card" style="margin-bottom: 0; text-align: center;">
                    <i data-lucide="box" color="#B2CD9C" size="28" class="mb-2"></i>
                    <p style="margin: 0; font-size: 0.85rem; opacity: 0.6;">Total Transaksi</p>
                    <h3 style="margin: 5px 0 0 0;"><?= $total_transaksi ?> <small style="font-size: 0.9rem; opacity: 0.5;">Kali</small></h3>
                </div>
                <div class="data-card" style="margin-bottom: 0; text-align: center;">
                    <i data-lucide="leaf" color="#CA7842" size="28" class="mb-2"></i>
                    <p style="margin: 0; font-size: 0.85rem; opacity: 0.6;">Status Akun</p>
                    <h3 style="margin: 5px 0 0 0;">Aktif</h3>
                </div>
            </div>

            <div class="data-card">
                <h3 style="margin-bottom: 10px; display: flex; align-items: center; gap: 15px;">
                    <i data-lucide="shield-check" color="#CA7842" size="28"></i> Keamanan Akun
                </h3>
                <p class="text-muted" style="font-size: 0.9rem;">Kelola akses email dan kata sandi akun kelas.</p>
                
               <div class="security-item">
    <div>
        <p style="margin: 0; font-size: 0.95rem; font-weight: 800;">Email Terdaftar</p>
        <p style="margin: 3px 0 0 0; font-size: 0.9rem; color: var(--terra-cotta); font-weight: 600;">
            <?= !empty($email_user) ? htmlspecialchars($email_user) : 'Email belum diatur (Klik Edit Profil)' ?>
        </p>
    </div>
    <i data-lucide="mail" size="22" style="opacity: 0.2;"></i>
</div>

                <div class="security-item">
                    <div>
                        <p style="margin: 0; font-size: 0.95rem; font-weight: 800;">Kata Sandi</p>
                        <p style="margin: 3px 0 0 0; font-size: 0.8rem; opacity: 0.5;">Ubah berkala untuk keamanan.</p>
                    </div>
                    <button class="btn" style="background: var(--dark-oak); color: white; padding: 10px 22px; border-radius: 12px; font-weight: 700;" onclick="window.location.href='ganti_password.php'">Ubah</button>
                </div>
            </div>
        </main>
    </div>
<div class="data-card" style="margin-top: 25px;">
    <h3 style="margin-bottom: 10px; display: flex; align-items: center; gap: 15px;">
        <i data-lucide="image" color="#CA7842" size="28"></i> Riwayat Bukti Misi
    </h3>
    <p class="text-muted" style="font-size: 0.9rem;">Foto-foto aksi hijau yang pernah kamu upload.</p>

    <?php if (mysqli_num_rows($query_bukti) > 0): ?>
        <div class="gallery-grid">
            <?php while($row = mysqli_fetch_assoc($query_bukti)): ?>
                <div class="gallery-item">
                    <img src="uploads/misi/<?= $row['foto_bukti'] ?>" 
                         alt="Bukti Misi" 
                         onerror="this.src='assets/img/placeholder.png'">
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-gallery">
            <i data-lucide="camera-off" size="40" style="opacity: 0.2; margin-bottom: 10px;"></i>
            <p style="margin: 0; opacity: 0.5; font-size: 0.9rem;">Belum ada foto bukti yang diupload.</p>
        </div>
    <?php endif; ?>
</div>

    <footer style="margin-top: 60px; padding-bottom: 40px; text-align: center;">
    <div style="width: 50px; height: 2px; background: var(--terra-cotta); margin: 0 auto 20px; opacity: 0.3;"></div>
    
    <p style="margin: 0; font-family: 'Poppins', sans-serif; font-weight: 700; color: var(--dark-oak); opacity: 0.8;">
        Loopie<span style="color: var(--terra-cotta);">.</span>
    </p>
    
    <p style="margin: 5px 0 0 0; font-size: 0.75rem; color: var(--dark-oak); opacity: 0.5; font-weight: 500; letter-spacing: 0.5px;">
        &copy; 2026 Dibuat oleh Lia Inayah• Versi 1.0
    </p>
</footer>

    <script>
    lucide.createIcons();
         function toggleNotif(event) {
            event.stopPropagation();
            const dropdown = document.getElementById('notifDropdown');
            dropdown.classList.toggle('active');
        }

        window.onclick = function(event) {
            const dropdown = document.getElementById('notifDropdown');
            if (dropdown && !event.target.closest('.notif-container')) {
                dropdown.classList.remove('active');
            }
        }
    </script>
</body>
</html>