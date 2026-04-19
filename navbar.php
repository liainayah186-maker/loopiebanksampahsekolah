<?php
// Deteksi halaman aktif
$current_page = basename($_SERVER['PHP_SELF']);
?>

<style>
    /* CSS NAVBAR (INTERNAL) */
    :root {
        --dark-oak: #4B352A;
        --terra-cotta: #CA7842;
        --moss-green: #B2CD9C;
        --soft-cream: #F0F2BD;
        --white: #ffffff;
    }

    .navbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 8%;
        background: var(--white);
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        position: sticky;
        top: 0;
        z-index: 1000;
    }

    .logo {
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--dark-oak);
        text-decoration: none;
        font-family: 'Poppins', sans-serif;
    }

    .logo-dot { color: var(--terra-cotta); }

    .nav-links { display: flex; gap: 20px; }

    .nav-link {
        text-decoration: none;
        color: var(--dark-oak);
        font-weight: 600;
        font-size: 0.9rem;
        transition: 0.3s;
    }

    .nav-link:hover, .nav-link.active { color: var(--terra-cotta); }

    .nav-right { display: flex; align-items: center; gap: 15px; }

    .poin-pill {
        background: var(--dark-oak);
        color: var(--white);
        padding: 8px 16px;
        border-radius: 50px;
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
        font-size: 0.9rem;
    }

    .notif-wrapper { position: relative; }
    
    .notif-btn, .profile-btn {
        background: var(--soft-cream);
        border: none; padding: 10px; border-radius: 12px;
        cursor: pointer; color: var(--dark-oak);
        display: flex; align-items: center; transition: 0.3s;
        text-decoration: none;
    }

    .profile-btn { background: #eeeeee; }

    .notif-btn:hover { background: var(--moss-green); color: white; }

    .notif-badge {
        position: absolute; top: -2px; right: -2px;
        background: var(--terra-cotta); width: 10px; height: 10px;
        border-radius: 50%; border: 2px solid white;
    }

    .notif-dropdown {
        display: none;
        position: absolute;
        top: 55px;
        right: 0;
        width: 260px;
        background: white;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        border-radius: 10px;
        z-index: 2000;
        border: 1px solid #eee;
        overflow: hidden;
    }
    
    .notif-dropdown.show { display: block; }
    
    .notif-item {
        padding: 12px 15px;
        display: block;
        color: var(--dark-oak);
        text-decoration: none;
        font-size: 0.85rem;
        border-bottom: 1px solid #f5f5f5;
    }
    
    .notif-item:hover { background: #fafafa; }

    /* RESPONSIVE */
    @media (max-width: 768px) {
        .nav-links { display: none; }
        .navbar { padding: 10px 5%; }
    }
</style>

<nav class="navbar">
    <a href="index.php" class="logo">Loopie<span class="logo-dot">.</span></a>
    
    <div class="nav-links">
        <a href="index.php" class="nav-link <?= ($current_page == 'index.php') ? 'active' : ''; ?>">Beranda</a>
        <a href="misi.php" class="nav-link <?= ($current_page == 'misi.php') ? 'active' : ''; ?>">Misi Hijau</a>
        <a href="peringkat.php" class="nav-link <?= ($current_page == 'peringkat.php') ? 'active' : ''; ?>">Peringkat</a>
        <a href="riwayat.php" class="nav-link <?= ($current_page == 'riwayat.php') ? 'active' : ''; ?>">Riwayat</a>
    </div>

    <div class="nav-right">
        <div class="notif-wrapper">
            <button class="notif-btn" id="notifBtn">
                <i data-lucide="bell" size="20"></i>
                <span class="notif-badge"></span>
            </button>
            <div class="notif-dropdown" id="notifBox">
                <div style="padding: 10px 15px; font-weight: 800; font-size: 0.75rem; border-bottom: 1px solid #eee; background: #f9f9f9;">NOTIFIKASI</div>
                <a href="#" class="notif-item">Belum ada notifikasi baru</a>
                <a href="notifikasi.php" style="display:block; padding: 10px; text-align:center; font-size:0.8rem; background: var(--moss-green); color: white; text-decoration:none; font-weight:700;">Lihat Semua</a>
            </div>
        </div>

        <div class="poin-pill">
            <i data-lucide="zap" size="18" fill="#F0F2BD"></i>
            <span><?= number_format($total_poin ?? 0) ?> <small>PTS</small></span>
        </div>

        <a href="profil.php" class="profile-btn">
            <i data-lucide="user" size="22"></i>
        </a>
    </div>
</nav>

<script>
    // Script Dropdown
    const nBtn = document.getElementById('notifBtn');
    const nBox = document.getElementById('notifBox');
    
    if(nBtn) {
        nBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            nBox.classList.toggle('show');
        });
    }
    window.addEventListener('click', () => {
        if(nBox) nBox.classList.remove('show');
    });
</script>