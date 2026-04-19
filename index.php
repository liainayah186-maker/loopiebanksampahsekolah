
<?php
session_start();
include 'koneksi.php'; 

// --- 1. PROTEKSI HALAMAN & AMBIL ID USER ---
if (!isset($_SESSION['id_user'])) {
    header("Location: home.php");
    exit();
}


$id_user = $_SESSION['id_user'];

// --- 2. AMBIL DATA PROFIL USER (SATU KALI SAJA BIAR BERSIH) ---
$query_user = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user = '$id_user'");
$data = mysqli_fetch_assoc($query_user);


$nama_kelas = $data['nama_kelas'];
$avatar = $data['avatar'];
$current_page = basename($_SERVER['PHP_SELF']);

if (!$data) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$nama_kelas = $data['nama_kelas'];
$total_poin = $data['total_poin'];
$wali_kelas = $data['wali_kelas'];
$avatar = $data['avatar'];

if (!empty($nama_kelas)) {
    // Cari total berat di tabel setoran_sampah berdasarkan kelas user
    $query_berat = mysqli_query($koneksi, "SELECT SUM(berat) as total_berat FROM setoran_sampah WHERE kelas = '$nama_kelas'");
    if ($query_berat) {
        $data_berat = mysqli_fetch_assoc($query_berat);
        $total_berat = (float)($data_berat['total_berat'] ?? 0);
    } else {
        $total_berat = 0; 
    }
} else {
    $total_berat = 0;
}

$air_terselamatkan = $total_berat * 2.5; 
$co2_berkurang = $total_berat * 0.15;

$q_notif = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM notifikasi WHERE (id_user = '$id_user' OR id_user IS NULL) AND is_read = 0");
$unread_count = mysqli_fetch_assoc($q_notif)['total'] ?? 0;

$query_notif_list = mysqli_query($koneksi, "SELECT * FROM notifikasi WHERE (id_user = '$id_user' OR id_user IS NULL) ORDER BY created_at DESC LIMIT 4");

$query_misi_aktif = mysqli_query($koneksi, "SELECT m.judul_misi, m.id_misi, pm.status_misi 
                                            FROM partisipasi_misi pm 
                                            JOIN misi m ON pm.id_misi = m.id_misi 
                                            WHERE pm.id_user = '$id_user' AND pm.status_misi = 'berjalan'");

$query_reward = mysqli_query($koneksi, "SELECT * FROM rewards WHERE stok > 0 ORDER BY harga_poin ASC");
$query_leaderboard = mysqli_query($koneksi, "SELECT id_user, nama_kelas, total_poin FROM users ORDER BY total_poin DESC LIMIT 5");

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loopie — Eco Hero Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">    <link rel="stylesheet" href="assets/loopie.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>
<body>

<style>
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
</style>

   <nav class="navbar">
    <a href="index.php" class="logo">Loopie<span class="logo-dot">.</span></a>
    
    <div class="nav-links">
        <a href="index.php" class="nav-link active">Beranda</a>
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
    .nav-avatar-img { width: 40px; height: 40px; border-radius: 50%; border: 2px solid var(--terra-cotta); object-fit: cover; }
    .nav-avatar-initials { 
        width: 40px; height: 40px; background: var(--moss-green); color: var(--dark-oak); 
        display: flex; align-items: center; justify-content: center; border-radius: 50%; 
        font-weight: 800; border: 2px solid var(--dark-oak);
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
/* --- DASHBOARD RESPONSIVE OPTIMIZATION --- */

/* 1. Hero Section (Penyelarasan Kartu & Ilustrasi) */
@media (max-width: 992px) {
    .hero-section {
        flex-direction: column;
        gap: 30px;
        padding: 20px 5%;
    }
    .hero-card { width: 100%; text-align: center; }
    .hero-illustration { display: none; } /* Ilustrasi besar disembunyikan di tablet/HP */
    
    .hero-title { font-size: 2.2rem !important; }
}

/* 2. Quick Actions (Tombol Navigasi Cepat) */
@media (max-width: 768px) {
    .quick-actions {
        display: grid;
        grid-template-columns: repeat(2, 1fr); /* 2 kolom di HP */
        gap: 12px;
        padding: 0 5%;
    }
    
    .action-card {
        padding: 15px;
        border-radius: 20px;
    }
    
    .action-icon i { width: 24px !important; height: 24px !important; }
    .action-label { font-size: 0.8rem; }
}

/* 3. Stats Card (Sampah, Air, CO2) */
@media (max-width: 600px) {
    .simple-stats {
        flex-direction: column;
        gap: 15px;
        padding: 0 5%;
    }
    
    .stat-card {
        width: 100%;
        display: flex;
        align-items: center;
        padding: 15px 20px;
    }
    
    .stat-icon { width: 45px; height: 45px; }
}

/* 4. Katalog Hadiah (Swiper Adjustments) */
@media (max-width: 480px) {
    .section-header { padding: 0 5%; margin-bottom: 20px; }
    .section-title { font-size: 1.3rem !important; }

    /* Pastikan kartu hadiah memenuhi lebar layar di HP */
    .swiper-reward {
        padding: 10px 5% 40px !important;
    }
    
    .reward-card {
        padding: 20px;
        border-radius: 25px;
    }
    
    .reward-name { font-size: 1rem; }
    .reward-price { font-size: 1.1rem; }
}

/* 5. Leaderboard (Peringkat) */
@media (max-width: 768px) {
    .leaderboard-section {
        margin: 30px 5%;
        padding: 20px;
        border-radius: 30px;
    }
    
    .rank-item {
        padding: 12px 15px;
        font-size: 0.85rem;
    }
    
    .rank-number { width: 35px; }
}

/* 6. Footer Cleanup */
@media (max-width: 768px) {
    .footer-container {
        display: flex;
        flex-direction: column;
        gap: 40px;
        text-align: center;
    }
    
    .social-row { justify-content: center; }
    .contact-stack { align-items: center; }
    
    /* Hilangkan kolom yang tidak terlalu penting di HP jika terlalu panjang */
    .footer-links-col:last-of-type { display: none; } 
}
</style>
    <div class="dashboard-container">
        <div class="hero-section">
            <div class="hero-card">
                <span class="hero-badge">ECO HERO</span>
                <h1 class="hero-title">Kelas <?= $nama_kelas ?></h1>
                <p class="hero-subtitle">Dibimbing oleh Wali Kelas <b><?= $wali_kelas ?></b>. Setiap sampah yang Anda kumpulkan adalah kontribusi nyata untuk bumi.</p>
            </div>
            <div class="hero-illustration">
                <i data-lucide="leaf" size="140" style="color: var(--moss-green); opacity: 0.8;"></i>
                <h3 style="margin-top: 30px; color: var(--dark-oak);">Jadilah Pahlawan!</h3>
                <p style="text-align: center; color: #666; margin-top: 10px; font-weight: 400;">Kumpulkan poin, tukarkan hadiah, dan raih prestasi.</p>
            </div>
        </div>

       <?php if($query_misi_aktif && mysqli_num_rows($query_misi_aktif) > 0): ?>
   
        <div class="mission-status-section">
            <h4 class="mission-section-title">
                <i data-lucide="loader" size="22" class="spin" style="color: var(--terra-cotta);"></i> Misi Sedang Berjalan
            </h4>
            <?php while($misi = mysqli_fetch_assoc($query_misi_aktif)): ?>
            <div class="mission-strip">
                <div style="display: flex; align-items: center; gap: 18px;">
                    <div class="mission-icon-box">
                        <i data-lucide="target" size="24"></i>
                    </div>
                    <div class="mission-title-box">
                        <h5><?= $misi['judul_misi'] ?></h5>
                        <p>Status: <span style="color: var(--terra-cotta); font-weight: 600;">Sedang Dikerjakan</span></p>
                    </div>
                </div>
                <a href="misi.php?id=<?= $misi['id_misi'] ?>" class="mission-action-link">
                    Perbarui Progress →
                </a>
            </div>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>

        <div class="quick-actions">
            <a href="#katalog" class="action-card">
                <div class="action-icon"><i data-lucide="gift" size="32"></i></div>
                <span class="action-label">Tukar Poin</span>
            </a>
            <a href="riwayat.php" class="action-card">
                <div class="action-icon"><i data-lucide="history" size="32"></i></div>
                <span class="action-label">Riwayat Poin</span>
            </a>
            <a href="misi.php" class="action-card">
                <div class="action-icon"><i data-lucide="target" size="32"></i></div>
                <span class="action-label">Klaim Misi</span>
            </a>
            <a href="bantuan.php" class="action-card">
                <div class="action-icon"><i data-lucide="bot" size="32"></i></div>
                <span class="action-label">Eco-AI Chat</span>
            </a>
        </div>

       <div class="simple-stats">
    <div class="stat-card">
        <div class="stat-icon icon-trash"><i data-lucide="trash-2"></i></div>
        <div class="stat-content">
            <h3><?= number_format($total_berat, 2) ?><span style="font-size: 1.2rem; margin-left: 5px;">KG</span></h3>
            <p>Sampah Terkumpul</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon icon-water"><i data-lucide="droplets"></i></div>
        <div class="stat-content">
            <h3><?= number_format($air_terselamatkan, 1) ?><span style="font-size: 1.2rem; margin-left: 5px;">L</span></h3>
            <p>Air Terselamatkan</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon icon-co2"><i data-lucide="cloud-rain"></i></div>
        <div class="stat-content">
            <h3><?= number_format($co2_berkurang, 2) ?><span style="font-size: 1.2rem; margin-left: 5px;">KG</span></h3>
            <p>Emisi CO2 Berkurang</p>
        </div>
    </div>
</div>


 <div class="katalog-section" id="katalog"></div>
        <div class="section-header">
            <h2 class="section-title">
                <i data-lucide="award" size="32" style="color: var(--terra-cotta);"></i>
                Katalog Hadiah
            </h2>
        </div>

        <div class="swiper swiper-reward">
    <div class="swiper-wrapper">
        <?php if(mysqli_num_rows($query_reward) > 0): while($row = mysqli_fetch_assoc($query_reward)): ?>
            <?php 
                $stok_ada = ($row['stok'] > 0);
                $can_redeem = ($total_poin >= $row['harga_poin'] && $stok_ada); 
                
                // --- LOGIKA ICON (Tetap pakai yang kamu punya) ---
                $nama_hadiah = strtolower($row['nama_hadiah']);
                $icon_lucu = "package";
                if (strpos($nama_hadiah, 'tulis') !== false || strpos($nama_hadiah, 'pulpen') !== false) { $icon_lucu = "pen-tool"; }
                elseif (strpos($nama_hadiah, 'makan') !== false || strpos($nama_hadiah, 'snack') !== false) { $icon_lucu = "cookie"; }
                elseif (strpos($nama_hadiah, 'minum') !== false || strpos($nama_hadiah, 'susu') !== false) { $icon_lucu = "cup-soda"; }
                elseif (strpos($nama_hadiah, 'poin') !== false || strpos($nama_hadiah, 'voucher') !== false) { $icon_lucu = "ticket"; }
            ?>
            
            <div class="swiper-slide">
                <div class="reward-card <?= !$stok_ada ? 'sold-out' : '' ?>">
                    
                    <?php if(!$stok_ada): ?>
                        <div class="sold-out-badge">HABIS</div>
                    <?php endif; ?>

                    <div class="reward-header">
                        <div class="reward-icon"><i data-lucide="<?= $icon_lucu ?>" size="24"></i></div>
                        <div>
                            <h3 class="reward-name"><?= $row['nama_hadiah'] ?></h3>
                            <small class="text-muted">Tersedia: <?= $row['stok'] ?></small>
                        </div>
                    </div>
                    <div class="reward-body">
                        <p class="reward-desc"><?= $row['deskripsi'] ?></p>
                    </div>
                    <div class="reward-footer">
                        <div class="reward-price"><?= number_format($row['harga_poin']) ?> PTS</div>
                        <button class="redeem-btn <?= !$can_redeem ? 'disabled' : '' ?>" 
                                <?= !$can_redeem ? 'disabled' : '' ?>
                                onclick="openModal('<?= addslashes($row['nama_hadiah']) ?>', <?= $row['id_reward'] ?>)">
                            <?php 
                                if (!$stok_ada) echo "Stok Habis";
                                else echo ($can_redeem ? 'Tukar Sekarang' : 'Poin Kurang');
                            ?>
                        </button>
                    </div>
                </div>
            </div>
        <?php endwhile; endif; ?>
    </div>
    
    <div class="swiper-pagination"></div>
</div>
<br>

        <div class="leaderboard-section" id="leaderboard">
    <h2 class="leaderboard-title">
        <i data-lucide="trophy" size="36" style="color: #FFD700; margin-right: 15px;"></i>
        Peringkat Eco-Class
    </h2>
    <div class="rankings">
        <?php 
        $no = 1;
        // Kita looping datanya satu per satu
        while ($row = mysqli_fetch_assoc($query_leaderboard)): 
            // Cek: Apakah ID di baris ini sama dengan ID user yang lagi login?
            $is_me = ($row['id_user'] == $id_user) ? 'current' : '';
            
            // Variasi warna untuk Top 3 (Emas, Perak, Perunggu)
            $rank_color = "";
            if ($no == 1) $rank_color = "color: #FFD700;"; // Gold
            elseif ($no == 2) $rank_color = "color: #C0C0C0;"; // Silver
            elseif ($no == 3) $rank_color = "color: #CD7F32;"; // Bronze
            else $rank_color = "color: var(--moss-green);";
        ?>
            <div class="rank-item <?= $is_me ?>">
                <div class="rank-info">
                    <span class="rank-number" style="<?= $is_me ? '' : $rank_color ?> font-weight: 800;">#<?= $no ?></span>
                    <span>
                        <?php if($is_me): ?>
                            <i data-lucide="star" size="18" style="margin-right: 8px; color: #FFD700;"></i>
                            Kelas <?= $row['nama_kelas'] ?> (Anda)
                        <?php else: ?>
                            Kelas <?= $row['nama_kelas'] ?>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="rank-points"><?= number_format($row['total_poin']) ?> PTS</div>
            </div>
        <?php 
            $no++; 
        endwhile; 
        ?>
    </div>
</div>
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- Inisialisasi Ikon Lucide ---
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }

        // --- Inisialisasi Swiper Hadiah ---
        if (document.querySelector('.swiper-reward')) {
            new Swiper(".swiper-reward", {
                slidesPerView: 1,
                spaceBetween: 20,
                pagination: { el: ".swiper-pagination", clickable: true },
                breakpoints: {
                    768: { slidesPerView: 2 },
                    1024: { slidesPerView: 3 }
                }
            });
        }
    });

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
    // --- Fungsi Tukar Poin ---
    function openModal(nama, id) {
        if (confirm(`Apakah kamu yakin ingin menukarkan poin untuk "${nama}"?`)) {
            window.location.href = `proses_tukar.php?id=${id}`;
        }
    }
    new Swiper(".swiper-reward", {
    slidesPerView: 1.2, // Biar slide selanjutnya kelihatan dikit, mancing user buat geser
    spaceBetween: 15,
    breakpoints: {
        768: { slidesPerView: 2.5, spaceBetween: 20 },
        1024: { slidesPerView: 3.5, spaceBetween: 30 }
    },
    pagination: { el: ".swiper-pagination", clickable: true },
});
// --- Fungsi Tukar Poin Menggunakan SweetAlert2 dengan Tema Earth Tone ---
function openModal(nama, id) {
    Swal.fire({
        title: 'Konfirmasi Penukaran',
        text: `Apakah kamu yakin ingin menukarkan poin untuk "${nama}"?`,
        icon: 'question',
        
        // --- Setting Warna Sesuai Variabel Kamu ---
        background: '#F0F2BD', // Menggunakan --cream-sand untuk background pop-up
        color: '#4B352A',      // Menggunakan --dark-oak untuk warna teks
        
        showCancelButton: true,
        confirmButtonColor: '#CA7842', // Menggunakan --terra-cotta untuk tombol konfirmasi
        cancelButtonColor: '#4B352A',  // Menggunakan --dark-oak untuk tombol batal
        confirmButtonText: 'Ya, Tukar Sekarang!',
        cancelButtonText: 'Batal',
        
        // --- Custom Class untuk styling tambahan jika diperlukan ---
        customClass: {
            title: 'text-dark-oak',
            popup: 'rounded-xl' // Agar lebih modern/rounded
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Proses pengalihan ke file PHP kamu
            window.location.href = `proses_tukar.php?id=${id}`;
        }
    });
}
        

</script>
</body>
</html>