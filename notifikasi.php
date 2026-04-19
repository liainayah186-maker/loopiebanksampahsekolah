<?php
session_start();
require 'koneksi.php';

// 1. Cek dulu apakah user sudah login
if(!isset($_SESSION['id_user'])) { 
    header("Location: home.php"); 
    exit; 
}

$id_user = $_SESSION['id_user'];
$query_user = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user = '$id_user'");
$data = mysqli_fetch_assoc($query_user);

$nama_kelas = $data['nama_kelas'];
$avatar = $data['avatar'];
$current_page = basename($_SERVER['PHP_SELF']);

// 2. Definisi variabel id_u diambil dari session
$id_u = $_SESSION['id_user']; 



// 3. BARU JALANKAN UPDATE (Sekarang $id_u sudah ada isinya)
mysqli_query($koneksi, "UPDATE notifikasi SET is_read = 1 WHERE id_user = '$id_u' AND is_read = 0");

$current_page = basename($_SERVER['PHP_SELF']); 



// Ambil Data Profil User
$q_user = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user = '$id_u'");
$data_user = mysqli_fetch_assoc($q_user);
$avatar = (!empty($data_user['avatar'])) ? $data_user['avatar'] : 'default.png';
$total_poin = $data_user['total_poin'] ?? 0;

// Hitung Notif Belum Terbaca
$unread_result = mysqli_query($koneksi, "SELECT id_notif FROM notifikasi WHERE (id_user = '$id_u' OR id_user IS NULL) AND is_read = 0");
$unread_count = mysqli_num_rows($unread_result);

// Ambil List Notif untuk Dropdown (Limit 5)
$query_notif_list = mysqli_query($koneksi, "SELECT * FROM notifikasi WHERE (id_user = '$id_u' OR id_user IS NULL) ORDER BY created_at DESC LIMIT 5");

// Logika Filter Halaman Utama Notifikasi
$filter = isset($_GET['f']) ? $_GET['f'] : 'all';
$sql = "SELECT * FROM notifikasi WHERE (id_user = '$id_u' OR id_user IS NULL)";
if($filter == 'p') $sql .= " AND id_user IS NOT NULL";
if($filter == 'u') $sql .= " AND id_user IS NULL";
$sql .= " ORDER BY created_at DESC";
$query_notif = mysqli_query($koneksi, $sql);

function time_ago($timestamp) {
    $diff = time() - strtotime($timestamp);
    if ($diff < 60) return "Baru saja";
    if ($diff < 3600) return round($diff/60) . "m lalu";
    if ($diff < 86400) return round($diff/3600) . "j lalu";
    return date('d/m', strtotime($timestamp));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesan Masuk — Loopie</title>
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

       
        /* --- NOTIF DROPDOWN (Fix Melayang) --- */
        .notif-container { position: relative; }
        
        .notif-btn.active-page {
            background: rgba(202, 120, 66, 0.1) !important;
            border-radius: 12px;
            color: var(--terra-cotta) !important;
        }

        .notif-dropdown {
            position: absolute;
            top: calc(100% + 15px);
            right: 0;
            width: 300px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(75, 53, 42, 0.15);
            display: none;
            z-index: 9999;
            overflow: hidden;
            border: 1px solid rgba(75, 53, 42, 0.05);
        }
        
        .notif-dropdown.show { display: block; animation: slideDown 0.3s ease; }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .notif-header { padding: 15px 20px; background: #f9f9f7; font-weight: 700; border-bottom: 1px solid #eee; font-size: 0.85rem; }
        .notif-item-link { padding: 12px 20px; display: block; text-decoration: none; color: #555; border-bottom: 1px solid #f5f5f5; font-size: 0.8rem; }
        .notif-footer { padding: 10px; text-align: center; display: block; background: #f9f9f7; color: var(--terra-cotta); font-weight: 700; text-decoration: none; font-size: 0.75rem; }

        .notif-badge {
            position: absolute; top: -2px; right: -2px;
            background: var(--terra-cotta); color: white;
            font-size: 9px; padding: 2px 5px; border-radius: 10px;
            border: 2px solid var(--cream-sand);
        }

        /* --- CONTENT STYLE --- */
        .content-section { padding: 60px 10% 80px; }
        .hero-badge { background: var(--dark-oak); color: var(--cream-sand); padding: 6px 18px; border-radius: 20px; font-size: 0.8rem; font-weight: 700; letter-spacing: 1.5px; display: inline-block; margin-bottom: 1.5rem; }
        .hero-title { font-size: 3.5rem; line-height: 1; letter-spacing: -2px; margin-bottom: 30px; }

        .notif-card {
            background: var(--pure-white); border-radius: 35px; padding: 25px;
            box-shadow: 0 10px 40px rgba(75, 53, 42, 0.04); transition: var(--transition);
            border: 1px solid rgba(178, 205, 156, 0.2); display: flex; gap: 20px; align-items: start; margin-bottom: 15px;
        }
        .notif-card:hover { transform: translateY(-5px); border-color: var(--terra-cotta); }
        
        .icon-box { width: 55px; height: 55px; background: rgba(178, 205, 156, 0.15); border-radius: 18px; display: flex; align-items: center; justify-content: center; color: var(--terra-cotta); flex-shrink: 0; }
        
        .date-divider { font-family: 'Poppins'; font-size: 0.75rem; font-weight: 800; color: var(--terra-cotta); text-transform: uppercase; letter-spacing: 2px; margin: 40px 0 20px; display: flex; align-items: center; gap: 15px; }
        .date-divider::after { content: ""; flex: 1; height: 1px; background: rgba(75, 53, 42, 0.1); }

        .filter-dock { position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%); background: var(--dark-oak); padding: 8px; border-radius: 50px; display: flex; gap: 5px; box-shadow: 0 20px 50px rgba(0,0,0,0.2); z-index: 1001; }
        .filter-item { padding: 10px 22px; border-radius: 40px; color: var(--cream-sand); text-decoration: none; font-size: 0.85rem; font-weight: 700; transition: 0.3s; }
        .filter-item.active { background: var(--terra-cotta); color: white; }
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
</head>
<body>
<?php include 'loading.php'; ?>

<nav class="navbar">
    <a href="index.php" class="logo">Loopie<span class="logo-dot">.</span></a>
    
    <div class="nav-links">
        <a href="index.php" class="nav-link">Beranda</a>
        <a href="misi.php" class="nav-link">Misi Hijau</a>
        <a href="index.php#leaderboard" class="nav-link">Peringkat</a>
        <a href="riwayat.php" class="nav-link">Riwayat</a>
    </div>

    <div class="nav-right" style="display: flex; align-items: center; gap: 18px;">
        <div class="notif-container">
    <a href="notifikasi.php" class="notif-btn <?= ($current_page == 'notifikasi.php') ? 'active-page' : '' ?>" 
       style="background: transparent; border: none; cursor: pointer; color: var(--dark-oak); padding: 8px; display: flex; position: relative; text-decoration: none;">
        
        <i data-lucide="<?= ($current_page == 'notifikasi.php') ? 'bell-ring' : 'bell' ?>" 
           style="<?= ($current_page == 'notifikasi.php') ? 'fill: var(--terra-cotta);' : '' ?>"></i> 
        
        <?php if($unread_count > 0): ?>
            <span class="notif-badge"><?= $unread_count ?></span>
        <?php endif; ?>
    </a>

    <?php if ($current_page !== 'notifikasi.php'): ?>
        <div id="notifDropdown" class="notif-dropdown">
            <div class="notif-header">Terbaru</div>
            <div class="notif-scroll-area">
                <?php while($nl = mysqli_fetch_assoc($query_notif_list)): ?>
                    <a href="notifikasi.php" class="notif-item-link"><?= substr($nl['pesan'], 0, 45) ?>...</a>
                <?php endwhile; ?>
            </div>
            <a href="notifikasi.php" class="notif-footer">Buka Semua Pesan</a>
        </div>
    <?php endif; ?>
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

<div class="content-section">
    <span class="hero-badge">PUSAT INFORMASI</span>
    <h1 class="hero-title">Pesan <span style="color: var(--terra-cotta);">Masuk.</span></h1>

    <div class="notif-list">
        <?php 
        $last_date = "";
        if(mysqli_num_rows($query_notif) > 0):
            while($n = mysqli_fetch_assoc($query_notif)): 
                $date = date('Y-m-d', strtotime($n['created_at']));
                if($last_date != $date){
                    $label = ($date == date('Y-m-d')) ? "Hari Ini" : (($date == date('Y-m-d', strtotime('-1 day'))) ? "Kemarin" : date('d M Y', strtotime($date)));
                    echo "<div class='date-divider'>$label</div>";
                    $last_date = $date;
                }
        ?>
            <div class="notif-card">
                <div class="icon-box">
                    <i data-lucide="<?= $n['id_user'] ? 'mail' : 'megaphone' ?>" size="26"></i>
                </div>
                <div class="notif-info">
                    <h3 style="font-family: 'Poppins'; font-size: 1.2rem; margin-bottom: 5px;"><?= $n['judul'] ?></h3>
                    <p style="font-size: 0.9rem; color: #666; margin-bottom: 12px;"><?= $n['pesan'] ?></p>
                    <div style="font-size: 0.75rem; font-weight: 700; color: #aaa; display: flex; align-items: center; gap: 5px;">
                        <i data-lucide="clock" size="12"></i> <?= time_ago($n['created_at']) ?>
                    </div>
                </div>
            </div>
        <?php endwhile; else: ?>
            <div style="text-align: center; padding: 80px 0; opacity: 0.2;">
                <i data-lucide="inbox" size="60"></i>
                <p class="mt-3 fw-bold">Belum ada pesan</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="filter-dock">
    <a href="?f=all" class="filter-item <?= $filter == 'all' ? 'active' : '' ?>">Semua</a>
    <a href="?f=p" class="filter-item <?= $filter == 'p' ? 'active' : '' ?>">Pribadi</a>
    <a href="?f=u" class="filter-item <?= $filter == 'u' ? 'active' : '' ?>">Umum</a>
</div>

<script>
    lucide.createIcons();

    function toggleNotif(e) {
        e.stopPropagation();
        document.getElementById('notifDropdown').classList.toggle('show');
    }

    window.onclick = function(event) {
        if (!event.target.closest('.notif-container')) {
            document.getElementById('notifDropdown').classList.remove('show');
        }
    }
</script>

</body>
</html>