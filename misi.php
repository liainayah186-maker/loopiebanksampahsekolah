<?php
session_start();
require 'koneksi.php';

if(!isset($_SESSION['id_user'])) { 
    header("Location: home.php"); 
    exit; 
}

$id_u = $_SESSION['id_user']; 
$today = date('Y-m-d');

// --- 1. DATA USER (Sama seperti index.php) ---
$q_user = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user = '$id_u'");
$data_user = mysqli_fetch_assoc($q_user);
$id_user = $_SESSION['id_user'];
$query_user = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user = '$id_user'");
$data = mysqli_fetch_assoc($query_user);

$nama_kelas = $data['nama_kelas'];
$avatar = $data['avatar'];
$current_page = basename($_SERVER['PHP_SELF']);

// Cek jika data user tidak ditemukan
if (!$data_user) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$total_poin = $data_user['total_poin'] ?? 0;
// Menggunakan variabel $avatar dan logika folder yang sama dengan index
$avatar = (!empty($data_user['avatar'])) ? $data_user['avatar'] : 'default.png';

// --- 2. CEK MISI AKTIF ---
$q_status = mysqli_query($koneksi, "SELECT * FROM partisipasi_misi WHERE id_user = '$id_u' AND status_misi = 'berjalan'");
$misi_aktif = mysqli_fetch_assoc($q_status);
$id_misi_diikuti = $misi_aktif['id_misi'] ?? null;

$misi_query = mysqli_query($koneksi, "SELECT * FROM misi WHERE tgl_selesai >= '$today' ORDER BY id_misi DESC");

// --- 3. QUERY NOTIFIKASI (Sama seperti index.php) ---
$q_notif = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM notifikasi WHERE (id_user = '$id_u' OR id_user IS NULL) AND is_read = 0");
$unread_count = mysqli_fetch_assoc($q_notif)['total'] ?? 0;

$query_notif_list = mysqli_query($koneksi, "SELECT * FROM notifikasi WHERE (id_user = '$id_u' OR id_user IS NULL) ORDER BY created_at DESC LIMIT 4");

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Misi Hijau — Loopie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/loopie.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
   <style>
    /* --- 1. ROOT VARIABLES --- */
    :root {
        --dark-oak: #4B352A;
        --terra-cotta: #CA7842;
        --moss-green: #B2CD9C;
        --cream-sand: #F0F2BD;
        --pure-white: #FFFFFF;
        --gold-leaf: #D4AF37;
        --transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* --- 2. GLOBAL STYLES --- */
    body { 
        font-family: 'Inter', sans-serif; 
        background-color: var(--cream-sand); 
        color: var(--dark-oak); 
        margin: 0; 
        overflow-x: hidden; 
    }

    h1, h2, h3, h4, .logo { 
        font-family: 'Poppins', sans-serif; 
        font-weight: 700; 
    }

    
    /* --- 4. NAVIGATION COMPONENTS --- */
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
    /* Footer notifikasi dengan warna Terra Cotta tanpa efek hover warna lain */
    .notif-footer { 
        padding: 15px; 
        text-align: center; 
        background: #fff; 
        border-top: 1px solid #f5f5f5; 
        text-decoration: none; 
        color: var(--terra-cotta); /* Warna orange khas Loopie */
        font-weight: 700; 
        font-size: 0.85rem;
        display: block;
        transition: opacity 0.2s;
    }

    .notif-footer:hover { 
        background: #fafafa; 
        color: var(--terra-cotta); /* Tetap Terra Cotta saat di-hover */
        opacity: 0.8; /* Hanya sedikit memudar saat disentuh kursor */
        text-decoration: none; 
    }

    /* --- 6. HERO & PAGE HEADERS --- */
    .hero-section { 
        padding: 60px 10% 40px; 
    }

    .hero-title { 
        font-size: 3.5rem; 
        line-height: 1; 
        letter-spacing: -2px; 
    }

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

    /* --- 7. MISSION CARDS & GRID --- */
    .mission-grid { 
        display: grid; 
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); 
        gap: 1.5rem; 
        padding: 0 10% 80px; 
    }

    .mission-card { 
        background: var(--pure-white); 
        border-radius: 35px; 
        padding: 30px; 
        box-shadow: 0 10px 40px rgba(75, 53, 42, 0.04); 
        transition: var(--transition); 
        border: 1px solid rgba(178, 205, 156, 0.15); 
        position: relative; 
        overflow: hidden; 
    }

    .mission-card:hover { 
        transform: translateY(-10px); 
        box-shadow: 0 25px 60px rgba(75, 53, 42, 0.1); 
        border-color: var(--terra-cotta); 
    }

    .pts-tag { 
        position: absolute; 
        top: 30px; 
        right: 30px; 
        background: var(--dark-oak); 
        color: var(--cream-sand); 
        padding: 6px 14px; 
        border-radius: 12px; 
        font-weight: 800; 
        font-size: 0.8rem; 
    }

    /* --- 8. CARD INNER COMPONENTS --- */
    .participant-group { 
        display: flex; 
        align-items: center; 
        margin-bottom: 20px; 
    }

    .participant-avatar { 
        width: 30px; 
        height: 30px; 
        border-radius: 50%; 
        border: 2px solid white; 
        margin-left: -10px; 
        object-fit: cover; 
        background: #eee; 
    }

    .stamp-grid { 
        display: flex; 
        gap: 10px; 
        flex-wrap: wrap; 
        margin: 20px 0; 
    }

    .stamp-slot { 
        width: 50px; 
        height: 50px; 
        border-radius: 50%; 
        border: 2px dashed #D4CFC7; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        font-weight: 800; 
        color: #D4CFC7; 
    }

    .stamp-slot.filled { 
        background: radial-gradient(circle at 30% 30%, var(--gold-leaf), #B8860B); 
        color: white; 
        border: none; 
        transform: rotate(-10deg) scale(1.1); 
        box-shadow: 0 5px 15px rgba(184, 134, 11, 0.3); 
    }

    /* --- 9. MISC FEATURES (CAMERA/PREVIEW) --- */
    .cam-preview-container { 
        display:none; 
        margin-bottom:15px; 
        border-radius:25px; 
        overflow:hidden; 
        background:#000; 
        position:relative; 
    }

    .snap-result { 
        width:100%; 
        display:none; 
        border-radius:25px; 
    }
</style>
    </style>
</head>
<body>

<nav class="navbar">
    <a href="index.php" class="logo">Loopie<span class="logo-dot">.</span></a>
    
    <div class="nav-links">
        <a href="index.php" class="nav-link">Beranda</a>
        <a href="misi.php" class="nav-link active">Misi Hijau</a>
        <a href="index.php#leaderboard" class="nav-link">Peringkat</a>
        <a href="riwayat.php" class="nav-link">Riwayat</a>
    </div>

    <div class="nav-right" style="display: flex; align-items: center; gap: 18px;">
        <div class="notif-container" style="position: relative; display: flex; align-items: center;">
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
                            <a href="notifikasi.php" class="notif-item">
                                <?= htmlspecialchars($n['pesan']) ?>
                                <div style="font-size: 0.7rem; opacity: 0.6; margin-top: 4px;">
                                    <?= date('d M, H:i', strtotime($n['created_at'])) ?>
                                </div>
                            </a>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="p-4 text-center text-muted" style="font-size: 0.8rem;">Belum ada notifikasi baru</div>
                    <?php endif; ?>
                </div>
                <a href="notifikasi.php" class="notif-footer">Lihat Semua</a>
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
    <span class="header-tag">MISI BULANAN</span>
    <h1 style="font-size: 3rem; letter-spacing: -2px;">Jelajahi <span style="color: var(--terra-cotta);">Misi.</span></h1>
    <p class="text-muted fs-5">Selesaikan misi di bawah ini dan kumpulkan poin untuk ditukarkan hadiah.</p>
</div>

<div class="mission-grid">
    <?php while($m = mysqli_fetch_assoc($misi_query)): 
        $id_m = $m['id_misi'];
        $target = $m['target_cap'];
        
        $q_progres = mysqli_query($koneksi, "SELECT jml_cap_sekarang FROM partisipasi_misi WHERE id_user = '$id_u' AND id_misi = '$id_m'");
        $data_p = mysqli_fetch_assoc($q_progres);
        $jml_sekarang = $data_p['jml_cap_sekarang'] ?? 0;
        
        $q_peserta = mysqli_query($koneksi, "SELECT u.avatar FROM partisipasi_misi pm JOIN users u ON pm.id_user = u.id_user WHERE pm.id_misi = '$id_m' LIMIT 4");
        $total_p = mysqli_num_rows(mysqli_query($koneksi, "SELECT id_user FROM partisipasi_misi WHERE id_misi = '$id_m'"));

        $is_done = ($jml_sekarang >= $target);
        $is_locked = ($id_misi_diikuti && $id_misi_diikuti != $id_m);
    ?>
    
    <div class="mission-card" style="<?= $is_locked ? 'opacity: 0.6; filter: grayscale(0.5);' : '' ?>">
        <div class="pts-tag">+<?= number_format($m['poin_hadiah']) ?> PTS</div>
        
        <?php if($is_locked): ?>
            <div style="position: absolute; top: 15px; left: 20px; color: var(--dark-oak); opacity: 0.8;">
                <i data-lucide="lock" size="16"></i> <small class="fw-bold">TERKUNCI</small>
            </div>
        <?php endif; ?>

        <div style="color: var(--terra-cotta); margin-top: 10px; margin-bottom: 15px;">
            <i data-lucide="<?= $m['icon'] ?: 'target' ?>" size="32"></i>
        </div>
        
        <h3 class="mb-1 text-uppercase" style="font-size: 1.1rem;"><?= htmlspecialchars($m['judul_misi']) ?></h3>
        
        <p style="font-size: 0.85rem; opacity: 0.7; margin-bottom: 15px; line-height: 1.4;">
            <?= htmlspecialchars($m['deskripsi'] ?? 'Selesaikan aksi nyata untuk lingkungan sekitar kita.') ?>
        </p>
        
        <div class="participant-group">
            <?php if($q_peserta): while($p = mysqli_fetch_assoc($q_peserta)): ?>
                <img src="uploads/profil/<?= $p['avatar'] ?: 'default.png' ?>" class="participant-avatar">
            <?php endwhile; endif; ?>
            <?php if($total_p > 0): ?>
                <span class="participant-count" style="font-size: 0.75rem; margin-left: 10px; font-weight: 600;"><?= $total_p ?> Mengikuti</span>
            <?php endif; ?>
        </div>

        <div class="stamp-grid">
            <?php for($i = 1; $i <= $target; $i++): ?>
                <div class="stamp-slot <?= ($i <= $jml_sekarang) ? 'filled' : '' ?>">
                    <?= ($i <= $jml_sekarang) ? '<i data-lucide="check" size="20"></i>' : $i ?>
                </div>
            <?php endfor; ?>
        </div>

        <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
            <span style="font-size: 0.8rem; font-weight: 700; color: var(--terra-cotta);"><?= $jml_sekarang ?> / <?= $target ?> SELESAI</span>
            
            <?php if($is_done): ?>
                <span class="badge bg-success rounded-pill px-3">COMPLETED</span>
            <?php elseif($id_misi_diikuti == $id_m): ?>
                <button onclick="openCam(<?= $id_m ?>)" class="btn btn-sm px-4 rounded-pill fw-bold" style="background: var(--dark-oak); color: white;">VERIFIKASI</button>
            <?php elseif(!$is_locked): ?>
                <button onclick="konfirmasiIkut(<?= $id_m ?>)" class="btn btn-sm px-4 rounded-pill fw-bold" style="background: var(--moss-green); color: var(--dark-oak);">IKUTI</button>
            <?php else: ?>
                <button class="btn btn-sm px-4 rounded-pill fw-bold disabled" style="background: #eee; color: #aaa;">LOCKED</button>
            <?php endif; ?>
        </div>

        <div id="cam-area-<?= $id_m ?>" class="mt-3 cam-preview-container">
            <video id="v-<?= $id_m ?>" autoplay playsinline style="width:100%; border-radius: 20px;"></video>
            <canvas id="c-<?= $id_m ?>" style="display:none;"></canvas>
            <img id="preview-<?= $id_m ?>" class="snap-result">
            <div class="p-3 d-flex gap-2">
                <button id="btn-snap-<?= $id_m ?>" onclick="snap(<?= $id_m ?>)" class="btn btn-danger w-100 rounded-pill fw-bold">AMBIL FOTO</button>
                <div id="btn-confirm-<?= $id_m ?>" style="display:none;" class="w-100 d-flex gap-2">
                    <button onclick="retake(<?= $id_m ?>)" class="btn btn-light border w-50 rounded-pill">ULANG</button>
                    <button onclick="uploadFoto(<?= $id_m ?>)" class="btn btn-success w-50 rounded-pill">KIRIM</button>
                </div>
            </div>
        </div>
    </div>
    <?php endwhile; ?>
</div>
<style>
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
/* --- Tombol Sosial --- */
.social-btn {
    width: 45px;
    height: 45px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(240, 242, 189, 0.1);
    border: 1px solid rgba(240, 242, 189, 0.2);
    border-radius: 50%;
    color: #F0F2BD;
    transition: all 0.3s ease;
}

.social-btn svg, 
.social-btn i {
    width: 20px !important;
    height: 20px !important;
    display: block;
}

.social-btn:hover {
    background: rgba(202, 120, 66, 0.1);
    border-color: rgba(202, 120, 66, 0.2);
    color: var(--terra-cotta);
    transform: translateY(-3px);
}

/* --- Container Kamera (DISEMBUNYIKAN TOTAL SAAT AWAL) --- */
.cam-container {
    display: none; /* INI PENTING: Agar tidak makan tempat sebelum diklik */
    width: 100%;
    max-width: 500px;
    margin: 0 auto; /* Margin 0 dulu agar tidak ada jarak kosong */
    background: #000;
    border-radius: 25px;
    overflow: hidden;
    position: relative;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    border: 3px solid var(--dark-oak);
}

/* Selector Spesifik: Hanya video yang ada di dalam cam-container */
.cam-container video {
    width: 100%;
    height: auto;
    display: block;
    object-fit: cover;
    aspect-ratio: 16 / 9;
}

/* Saat kamera aktif (dipicu JS) */
.cam-container.active {
    display: block;
    margin: 15px auto;
}

.preview-img {
    width: 100%;
    height: auto;
    display: none;
    border-radius: 20px;
    aspect-ratio: 16 / 9;
    object-fit: cover;
}

.cam-controls {
    padding: 20px;
    display: flex;
    justify-content: center;
    gap: 15px;
    background: white;
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
    // 1. LANGSUNG JALANKAN LUCIDE DI SINI (Jangan ditaruh di dalam DOMContentLoaded)
    // Ini rahasia kenapa halaman riwayat kamu berhasil!
    lucide.createIcons();

    document.addEventListener('DOMContentLoaded', function() {
        // 2. Jalankan Swiper
        if (typeof Swiper !== 'undefined' && document.querySelector('.swiper-reward')) {
            new Swiper(".swiper-reward", {
                slidesPerView: 1,
                spaceBetween: 20,
                pagination: { el: ".swiper-pagination", clickable: true },
            });
        }
    });

    

    let stream = null;

    async function openCam(id) {
        const area = document.getElementById('cam-area-' + id);
        area.style.display = 'block';
        area.scrollIntoView({ behavior: 'smooth' });
        try {
            stream = await navigator.mediaDevices.getUserMedia({ 
                video: { facingMode: "environment" } 
            });
            document.getElementById('v-' + id).srcObject = stream;
        } catch(e) {
            Swal.fire('Error', 'Akses kamera ditolak.', 'error');
        }
    }

    function snap(id) {
        const video = document.getElementById('v-' + id);
        const canvas = document.getElementById('c-' + id);
        const preview = document.getElementById('preview-' + id);
        
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0);
        
        preview.src = canvas.toDataURL('image/png');
        preview.style.display = 'block';
        video.style.display = 'none';

        document.getElementById('btn-snap-' + id).style.display = 'none';
        document.getElementById('btn-confirm-' + id).style.display = 'flex';
    }

    function uploadFoto(id) {
        const canvas = document.getElementById('c-' + id);
        const dataFoto = canvas.toDataURL('image/png');

        Swal.fire({ 
            title: 'Mengirim...', 
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading() 
        });

        fetch('proses_misi.php?aksi=upload', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id_misi=${id}&image=${encodeURIComponent(dataFoto)}`
        })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                Swal.fire('Berhasil!', data.message, 'success').then(() => location.reload());
            } else {
                Swal.fire('Gagal', data.message, 'error');
            }
        })
        .catch(err => {
            Swal.fire('Error', 'Gagal kirim data.', 'error');
        });
    }

    function retake(id) {
        document.getElementById('preview-' + id).style.display = 'none';
        document.getElementById('v-' + id).style.display = 'block';
        document.getElementById('btn-snap-' + id).style.display = 'block';
        document.getElementById('btn-confirm-' + id).style.display = 'none';
    }

    function konfirmasiIkut(id) {
    Swal.fire({
        title: 'Mulai Misi?',
        text: 'Apakah kamu yakin ingin mengambil misi ini sekarang?',
        icon: 'question',
        
        // --- Warna Earth Tone ---
        background: '#F0F2BD', // --cream-sand
        color: '#4B352A',      // --dark-oak (warna teks)
        
        showCancelButton: true,
        confirmButtonColor: '#CA7842', // --terra-cotta
        cancelButtonColor: '#4B352A',  // --dark-oak
        confirmButtonText: 'Ya, Ikuti!',
        cancelButtonText: 'Batal',
        
        // Efek visual tambahan
        backdrop: `rgba(75, 53, 42, 0.4)` // Overlay gelap transparan (dark-oak)
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `proses_misi.php?aksi=ambil&id=${id}`;
        }
    });
    
}


    function toggleNotif(e) {
        e.stopPropagation();
        document.getElementById('notifDropdown').classList.toggle('active');
    }
</script>
</body>
</html>