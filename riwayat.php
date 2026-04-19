<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['id_user'])) {
    header("Location: home.php");
    exit;
}

$id_user = $_SESSION['id_user'];

// --- 1. DATA USER (Sama seperti index.php) ---
$query_user = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user = '$id_user'");
$user_data = mysqli_fetch_assoc($query_user);
$id_user = $_SESSION['id_user'];
$query_user = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user = '$id_user'");
$data = mysqli_fetch_assoc($query_user);

$nama_kelas = $data['nama_kelas'];
$avatar = $data['avatar'];
$current_page = basename($_SERVER['PHP_SELF']);

if (!$user_data) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$total_poin = $user_data['total_poin'] ?? 0;
// Menggunakan logika avatar yang sama (mengambil field 'avatar')
$avatar = (!empty($user_data['avatar'])) ? $user_data['avatar'] : 'default.png';

// --- 2. QUERY NOTIFIKASI (Sama seperti index.php) ---
$q_notif = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM notifikasi WHERE (id_user = '$id_user' OR id_user IS NULL) AND is_read = 0");
$unread_count = mysqli_fetch_assoc($q_notif)['total'] ?? 0;

$query_notif_list = mysqli_query($koneksi, "SELECT * FROM notifikasi WHERE (id_user = '$id_user' OR id_user IS NULL) ORDER BY created_at DESC LIMIT 4");

// --- 3. QUERY PENUKARAN ---
$query = mysqli_query($koneksi, "SELECT p.*, r.nama_hadiah, r.harga_poin 
                                FROM penukaran p 
                                JOIN rewards r ON p.id_reward = r.id_reward 
                                WHERE p.id_user = '$id_user' 
                                ORDER BY p.tanggal_tukar DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Klaim — Loopie.</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="assets/loopie.css">

    <style>
       <style>
    /* --- 1. ROOT & VARIABLES --- */
    :root { 
        --dark-oak: #4B352A;
        --terra-cotta: #CA7842;
        --moss-green: #B2CD9C; 
        --cream-sand: #F0F2BD;
        --pure-white: #FFFFFF;
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* --- 2. GLOBAL STYLES --- */
    body { 
        background-color: var(--cream-sand); 
        color: var(--dark-oak); 
        font-family: 'Inter', sans-serif;
        margin: 0;
        overflow-x: hidden;
    }

    h1, h2, h3, h4, .logo { 
        font-family: 'Poppins', sans-serif; 
        font-weight: 700; 
    }


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

    .nav-avatar-img { 
        width: 40px; 
        height: 40px; 
        border-radius: 50%; 
        border: 2px solid var(--terra-cotta); 
        object-fit: cover; 
    }

    /* --- 4. NOTIFIKASI DROPDOWN --- */
  /* --- NOTIFICATION DROP (FIXED) --- */
    .notif-container { position: relative; display: inline-block; }
    .notif-badge { position: absolute; top: -5px; right: -5px; background: var(--terra-cotta); color: white; font-size: 10px; font-weight: 800; padding: 2px 6px; border-radius: 10px; border: 2px solid white; }

    .notif-dropdown {
        position: absolute; top: 65px; right: 0; width: 320px; background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(10px); border-radius: 24px; box-shadow: 0 20px 40px rgba(75, 53, 42, 0.15);
        display: none; flex-direction: column; overflow: hidden; z-index: 9999;
        border: 1px solid rgba(202, 120, 66, 0.1); transform-origin: top right;
    }
    .notif-dropdown.active { display: flex; animation: slideDown 0.3s forwards; }
    @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

    .notif-header { padding: 15px 20px; background: #FBFBFA; font-weight: 700; color: var(--dark-oak); border-bottom: 1px solid #eee; font-size: 0.9rem; }
    .notif-item { padding: 15px 20px; border-bottom: 1px solid #f5f5f5; font-size: 0.85rem; color: #555; line-height: 1.4; text-decoration: none; display: block; transition: 0.2s; }
    .notif-item:hover { background: #fdfdfd; color: var(--dark-oak); }

    /* Hapus Hover Terra Cotta di Lihat Semua */
    .notif-footer { 
        padding: 12px; text-align: center; background: #fff; border-top: 1px solid #eee; 
        text-decoration: none; color: #888; font-weight: 600; font-size: 0.8rem; 
    }
    .notif-footer:hover { background: #fff; color: #888; text-decoration: none; }
    /* --- 5. PAGE HEADER & GRID --- */
    .page-header { 
        padding: 60px 10% 30px; 
    }

    .header-tag { 
        background: var(--dark-oak); 
        color: var(--cream-sand); 
        padding: 6px 18px; 
        border-radius: 20px; 
        font-size: 0.8rem; 
        font-weight: 700; 
        letter-spacing: 1.5px; 
        display: inline-block; 
        margin-bottom: 1.2rem; 
    }

    .history-grid { 
        display: grid; 
        grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); 
        gap: 25px; 
        padding: 0 10% 80px; 
    }

    /* --- 6. TICKET DESIGN --- */
    .ticket-card { 
        background: var(--pure-white); 
        border-radius: 40px; 
        overflow: hidden; 
        box-shadow: 0 15px 35px rgba(75, 53, 42, 0.05); 
        transition: var(--transition); 
        border: 1px solid rgba(0,0,0,0.02); 
    }

    .ticket-card:hover { 
        transform: translateY(-10px); 
    }

    .ticket-top { 
        padding: 30px; 
    }

    .ticket-bottom { 
        padding: 25px; 
        background: #F9FAEF; 
        border-top: 2px dashed #E0E0E0; 
        text-align: center; 
        position: relative; 
    }

    .ticket-bottom::before, 
    .ticket-bottom::after { 
        content: ''; 
        position: absolute; 
        top: -15px; 
        width: 30px; 
        height: 30px; 
        background: var(--cream-sand); 
        border-radius: 50%; 
    }

    .ticket-bottom::before { left: -15px; }
    .ticket-bottom::after { right: -15px; }

    .claim-code { 
        font-family: 'Courier New', monospace; 
        font-size: 1.6rem; 
        font-weight: 800; 
        color: var(--dark-oak); 
        letter-spacing: 5px; 
        margin: 10px 0; 
        background: white; 
        padding: 12px; 
        border-radius: 20px; 
        display: block; 
        border: 1px solid rgba(0,0,0,0.05); 
    }

    /* --- 7. STATUS PILLS --- */
    .status-pill { 
        font-size: 0.75rem; 
        font-weight: 800; 
        padding: 6px 14px; 
        border-radius: 12px; 
    }

    .status-pending { 
        background: #FFF4E6; 
        color: #D9480F; 
    }

    .status-done { 
        background: var(--moss-green); 
        color: var(--dark-oak); 
    }

    /* --- 8. RESPONSIVE DESIGN --- */
    @media (max-width: 768px) {
        .navbar { 
            padding: 1.2rem 5%; 
        }
        /* Jika ingin menu muncul kembali di HP, ubah display:none menjadi flex */
        .nav-links { 
            display: nonef; 
        }
    }
    .nav-avatar-img { 
    width: 40px; 
    height: 40px; 
    border-radius: 50%; 
    object-fit: cover; 
    border: 2px solid transparent;
}

.nav-avatar-initials { 
    width: 40px; 
    height: 40px; 
    background: var(--moss-green); 
    color: var(--dark-oak); 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    border-radius: 50%; 
    font-weight: 800; 
    font-size: 1.1rem;
    border: 2px solid var(--dark-oak);
}

.profile-active .nav-avatar-img, 
.profile-active .nav-avatar-initials {
    border-color: var(--terra-cotta);
}
</style>
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="index.php" class="logo">Loopie<span class="logo-dot">.</span></a>
        
        <div class="nav-links">
            <a href="index.php" class="nav-link">Beranda</a>
            <a href="misi.php" class="nav-link">Misi Hijau</a>
            <a href="index.php#leaderboard" class="nav-link">Peringkat</a>
            <a href="riwayat.php" class="nav-link active">Riwayat</a>
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
                <span><?= number_format($total_poin) ?> <small>PTS</small></span>
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

    <div class="page-header">
        <span class="header-tag">TUKAR HADIAH</span>
        <h1 style="font-size: 3rem; letter-spacing: -2px;">Riwayat <span style="color: var(--terra-cotta);">Klaim.</span></h1>
        <p class="text-muted fs-5">Tunjukkan kode tiket di bawah ini ke petugas untuk mengambil hadiahmu.</p>
    </div>

    <div class="history-grid">
        <?php if(mysqli_num_rows($query) > 0): ?>
            <?php while($row = mysqli_fetch_assoc($query)): ?>
                <div class="ticket-card">
                    <div class="ticket-top">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <span class="text-muted fw-bold" style="font-size: 0.7rem; text-transform: uppercase;">
                                    <?= date('d M Y', strtotime($row['tanggal_tukar'])) ?>
                                </span>
                                <h4 class="m-0 mt-1" style="font-size: 1.4rem;"><?= htmlspecialchars($row['nama_hadiah']) ?></h4>
                            </div>
                            <span class="status-pill <?= $row['status'] == 'pending' ? 'status-pending' : 'status-done' ?>">
                                <?= $row['status'] == 'pending' ? 'DIPROSES' : 'SELESAI' ?>
                            </span>
                        </div>
                        <div style="background: rgba(202, 120, 66, 0.1); padding: 5px 12px; border-radius: 10px; display: inline-flex; align-items:center; gap: 8px;">
                            <i data-lucide="zap" size="14" style="color: var(--terra-cotta); fill: var(--terra-cotta);"></i>
                            <span class="small fw-bold" style="color: var(--terra-cotta);"><?= number_format($row['harga_poin']) ?> PTS</span>
                        </div>
                    </div>
                    
                    <div class="ticket-bottom">
                        <p class="small text-muted mb-2 text-uppercase fw-bold" style="letter-spacing: 2px; font-size: 0.6rem;">KODE KLAIM UNIK</p>
                        <div class="claim-code"><?= $row['kode_klaim'] ?></div>
                        
                        <?php if($row['status'] == 'pending'): ?>
                            <div class="mt-3 py-2 px-3 rounded-4" style="background: #FFF9F0; color: #CA7842; font-size: 0.8rem; font-weight: 700;">
                                <i data-lucide="clock" size="16" class="me-1"></i> Menunggu Verifikasi
                            </div>
                        <?php else: ?>
                            <div class="mt-3 py-2 px-3 rounded-4" style="background: var(--moss-green); color: var(--dark-oak); font-size: 0.8rem; font-weight: 700;">
                                <i data-lucide="check-circle-2" size="16" class="me-1"></i> Sudah Diambil
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <div style="background: white; border-radius: 40px; padding: 60px; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
                    <i data-lucide="ticket" size="60" style="opacity: 0.2;" class="mb-3"></i>
                    <h3 class="fw-bold">Belum Ada Tiket</h3>
                    <p class="text-muted">Kamu belum pernah menukarkan poin dengan hadiah.</p>
                    <a href="index.php#katalog" class="btn mt-3" style="background: var(--dark-oak); color: white; border-radius: 15px; padding: 12px 30px; font-weight: 700; text-decoration: none;">Ke Katalog Hadiah</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
 <style>
   .social-btn {
    width: 45px;
    height: 45px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(240, 242, 189, 0.1); /* Krem transparan */
    border: 1px solid rgba(240, 242, 189, 0.2);
    border-radius: 50%; /* Membuat jadi bulat sempurna */
    color: #F0F2BD; /* Warna ikon krem */
    transition: all 0.3s ease;
}

.social-btn:hover {
    background: #CA7842; /* Warna Terra Cotta saat di-hover */
    color: white;
    transform: translateY(-5px);
    border-color: #CA7842;
}

.social-btn i {
    width: 20px;
    height: 20px;
}
</style>

<footer class="site-footer">
    <div class="footer-container">
        <div class="footer-brand-col">
            <a href="index.php" class="footer-logo">Loopie<span>.</span></a>
            <p>Platform pahlawan lingkungan yang mengubah setiap aksi kecilmu menjadi dampak besar bagi masa depan bumi. Mulai langkah hijaumu hari ini!</p>
            <div class="social-row">
                <a href="https://www.instagram.com/loopie.eco" class="social-btn"><i data-lucide="instagram"></i></a>
                <a href="https://twitter.com/loopieeco" class="social-btn"><i data-lucide="twitter"></i></a>
                <a href="https://www.youtube.com/@loopieeco" class="social-btn"><i data-lucide="youtube"></i></a>
            </div>
        </div>

        <div class="footer-links-col">
            <h4 class="footer-heading">Eksplorasi</h4>
            <ul class="footer-links-list">
                <li><a href="index.php">Beranda Utama</a></li>
                <li><a href="misi.php">Misi Hijau Aktif</a></li>
                <li><a href="index.php#katalog">Tukar Reward</a></li>
                <li><a href="index.php#leaderboard">Peringkat Eco-Class</a></li>
            </ul>
        </div>

        <div class="footer-links-col">
            <h4 class="footer-heading">Aktivitas</h4>
            <ul class="footer-links-list">
                <li><a href="profil.php">Profil Saya</a></li>
                <li><a href="riwayat.php">Riwayat Poin</a></li>
                <li><a href="bantuan.php">Tanya Loopie AI</a></li>
            </ul>
        </div>

        <div class="footer-contact-col">
            <h4 class="footer-heading">Hubungi Kami</h4>
            <div class="contact-stack">
                <div class="contact-pill">
                    <div class="contact-icon-wrapper"><i data-lucide="mail"></i></div>
                    <span>support@loopie.eco</span>
                </div>
                <div class="contact-pill">
                    <div class="contact-icon-wrapper"><i data-lucide="map-pin"></i></div>
                    <span>Lab RPL — SMKN 1, ID</span>
                </div>
            </div>
        </div>
    </div>

    <div class="footer-bottom">
        <p class="copyright-text">&copy; 2026 Loopie Indonesia. Dikembangkan dengan <i data-lucide="heart" style="color: #CA7842; fill: #CA7842;"></i> oleh Lia Inayah.</p>
        <p class="copyright-text">V2.1.0 — Stable Build</p>
    </div>
</footer>
<style>
    /* Paksa body tidak punya ruang tambahan */
    body, html {
        margin: 0 !important;
        padding: 0 !important;
        height: auto !important;
        min-height: 100% !important;
    }

    /* Pastikan footer nempel di bawah dan tidak ada elemen setelahnya */
    .site-footer {
        margin-top: 50px !important;
        margin-bottom: 0 !important;
        clear: both !important;
    }

    /* Hilangkan semua cam-container yang tidak aktif secara paksa */
    .cam-container:not(.active) {
        display: none !important;
        height: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
        border: none !important;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script src="https://unpkg.com/lucide@0.363.0/dist/umd/lucide.min.js"></script>


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