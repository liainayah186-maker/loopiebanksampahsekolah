<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['id_user'])) {
    header("Location: home.php");
    exit;
}

$id_user = $_SESSION['id_user'];
$current_page = basename($_SERVER['PHP_SELF']); 

// --- 1. DATA USER ---
$query_user = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user = '$id_user'");
$user_data = mysqli_fetch_assoc($query_user);

if (!$user_data) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$nama_kelas = $user_data['nama_kelas'];
$total_poin = $user_data['total_poin'] ?? 0;
$avatar = (!empty($user_data['avatar'])) ? $user_data['avatar'] : 'default.png';

// --- 2. QUERY NOTIFIKASI ---
$q_notif = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM notifikasi WHERE (id_user = '$id_user' OR id_user IS NULL) AND is_read = 0");
$unread_count = mysqli_fetch_assoc($q_notif)['total'] ?? 0;
$query_notif_list = mysqli_query($koneksi, "SELECT * FROM notifikasi WHERE (id_user = '$id_user' OR id_user IS NULL) ORDER BY created_at DESC LIMIT 4");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Support — Loopie.</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>

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
            background-color: var(--cream-sand); 
            color: var(--dark-oak); 
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding-bottom: 220px; 
            overflow-x: hidden;
        }

        /* --- NAVBAR --- */
        .navbar {
            background: rgba(240, 242, 189, 0.9);
            backdrop-filter: blur(15px);
            padding: 1.2rem 10%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid rgba(75, 53, 42, 0.1);
        }

        .nav-left { display: flex; align-items: center; gap: 20px; }
        
        .btn-back-circle {
            width: 45px; height: 45px; border-radius: 50%;
            background: white; border: 1px solid rgba(75,53,42,0.1);
            display: flex; align-items: center; justify-content: center;
            color: var(--dark-oak); transition: var(--transition); text-decoration: none;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        .btn-back-circle:hover { background: var(--dark-oak); color: white; transform: translateX(-5px); }

        .logo-text { font-family: 'Poppins', sans-serif; font-size: 1.6rem; font-weight: 800; color: var(--dark-oak); letter-spacing: -1px; text-decoration: none; }
        .logo-dot { color: var(--terra-cotta); }
        .support-label { font-size: 0.6rem; font-weight: 800; color: var(--terra-cotta); letter-spacing: 2px; margin-top: -6px; margin-left: 2px; }

        .nav-right { display: flex; align-items: center; gap: 18px; }

        /* --- NOTIFIKASI DROPDOWN (PREMIUM) --- */
        .notif-container { position: relative; display: inline-block; }
        .notif-badge { position: absolute; top: -5px; right: -5px; background: var(--terra-cotta); color: white; font-size: 10px; font-weight: 800; padding: 2px 6px; border-radius: 10px; border: 2px solid white; box-shadow: 0 3px 10px rgba(202,120,66,0.3); }

        .notif-dropdown {
            position: absolute; top: 65px; right: 0; width: 320px; background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px);
            border-radius: 28px; box-shadow: 0 20px 50px rgba(75, 53, 42, 0.15);
            display: none; flex-direction: column; overflow: hidden; z-index: 9999;
            border: 1px solid rgba(202, 120, 66, 0.1); transform-origin: top right;
        }
        .notif-dropdown.active { display: flex; animation: dropdownPop 0.35s cubic-bezier(0.34, 1.56, 0.64, 1) forwards; }
        
        @keyframes dropdownPop { 
            from { opacity: 0; transform: scale(0.9) translateY(-15px); } 
            to { opacity: 1; transform: scale(1) translateY(0); } 
        }

        .notif-header { padding: 18px 22px; background: #FBFBFA; font-weight: 800; color: var(--dark-oak); border-bottom: 1px solid rgba(0,0,0,0.04); font-size: 0.95rem; }
        .notif-body { max-height: 350px; overflow-y: auto; padding: 10px; }
        
      

        .notif-item { padding: 15px 18px; margin-bottom: 5px; border-radius: 20px; transition: 0.2s; text-decoration: none !important; display: block; }
        .notif-item:hover { background: #FBFBFA; transform: translateY(-2px); }
        .notif-item-text { font-size: 0.85rem; color: #555; line-height: 1.4; display: block; }
        .notif-item-time { font-size: 0.7rem; color: var(--terra-cotta); font-weight: 600; margin-top: 5px; opacity: 0.7; display: block; }

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
        /* --- PROFILE & POIN --- */
        .poin-pill {
            background: var(--dark-oak); color: var(--cream-sand); padding: 0.7rem 1.4rem; border-radius: 50px;
            display: flex; align-items: center; gap: 10px; font-weight: 700; box-shadow: 0 5px 20px rgba(75, 53, 42, 0.2);
        }
        .nav-avatar-img { width: 40px; height: 40px; border-radius: 50%; border: 2px solid transparent; object-fit: cover; }
        .nav-avatar-initials { width: 40px; height: 40px; background: var(--moss-green); color: var(--dark-oak); display: flex; align-items: center; justify-content: center; border-radius: 50%; font-weight: 800; font-size: 1.1rem; border: 2px solid var(--dark-oak); }
        .nav-profile-link.profile-active .nav-avatar-img, .nav-profile-link.profile-active .nav-avatar-initials { border-color: var(--terra-cotta); }

        /* --- CHAT DESIGN --- */
        .chat-container { max-width: 800px; margin: 40px auto; padding: 0 20px; display: flex; flex-direction: column; gap: 20px; }
        .msg-container { display: flex; width: 100%; animation: fadeInUp 0.4s ease forwards; }
        .bubble { max-width: 75%; padding: 18px 24px; border-radius: 28px; font-size: 0.95rem; line-height: 1.6; box-shadow: 0 10px 25px rgba(75, 53, 42, 0.05); }
        .bot-bubble { background: white; color: var(--dark-oak); border-bottom-left-radius: 5px; }
        .user-bubble { background: var(--terra-cotta); color: white; border-bottom-right-radius: 5px; margin-left: auto; }

        /* --- BOTTOM INPUT --- */
        .bottom-bar { position: fixed; bottom: 0; left: 0; right: 0; padding: 20px 5% 40px; background: linear-gradient(transparent, var(--cream-sand) 40%); z-index: 999; }
        .quick-chips { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 15px; justify-content: center; scrollbar-width: none; }
        .chip { background: white; border: 1.5px solid rgba(75, 53, 42, 0.1); padding: 10px 20px; border-radius: 100px; font-size: 0.8rem; font-weight: 700; cursor: pointer; transition: 0.3s; white-space: nowrap; }
        .chip:hover { background: var(--dark-oak); color: white; transform: translateY(-3px); }
        
        .input-wrapper { max-width: 750px; margin: 0 auto; background: white; padding: 8px 8px 8px 25px; border-radius: 25px; display: flex; align-items: center; box-shadow: 0 15px 40px rgba(75, 53, 42, 0.15); }
        .input-wrapper input { flex: 1; border: none; outline: none; font-size: 1rem; }
        .btn-send { width: 48px; height: 48px; border-radius: 18px; background: var(--dark-oak); color: white; border: none; display: flex; align-items: center; justify-content: center; transition: 0.3s; }
        .btn-send:hover { background: var(--terra-cotta); transform: scale(1.1); }

        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        @media (max-width: 768px) { .navbar { padding: 1.2rem 5%; } .logo-text { display: none; } .quick-chips { justify-content: flex-start; } }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="nav-left">
            <a href="index.php" class="btn-back-circle">
                <i data-lucide="arrow-left"></i>
            </a>
            <div style="display: flex; flex-direction: column;">
                <a href="index.php" class="logo-text">Loopie<span class="logo-dot">.</span></a>
                <span class="support-label">SMART SUPPORT</span>
            </div>
        </div>

        <div class="nav-right">
            <div class="notif-container">
                <button onclick="toggleNotif(event)" style="background: transparent; border: none; cursor: pointer; color: var(--dark-oak); padding: 5px; display: flex; position: relative;">
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
                                    <span class="notif-item-text"><?= htmlspecialchars($n['pesan']) ?></span>
                                    <span class="notif-item-time"><?= date('d M, H:i', strtotime($n['created_at'])) ?></span>
                                </a>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="p-4 text-center text-muted" style="font-size: 0.8rem;">Belum ada pesan masuk</div>
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
                    <div class="nav-avatar-initials"><?= strtoupper(substr($nama_kelas, 0, 1)) ?></div>
                <?php endif; ?>
            </a>
        </div>
    </nav>

    <main class="chat-container" id="chat-list">
        <div class="msg-container">
            <div class="bubble bot-bubble">
                Halo, <b><?= htmlspecialchars($user_data['nama_lengkap'] ?? 'Pahlawan Lingkungan') ?></b>! ✨ <br>Ada yang bisa aku bantu seputar setoran sampah atau poinmu hari ini?
            </div>
        </div>
    </main>

    <div class="bottom-bar">
        <div class="quick-chips">
            <div class="chip" onclick="ask('Cara dapet poin?')">💰 CARA POIN</div>
            <div class="chip" onclick="ask('Jadwal buka?')">📅 JADWAL</div>
            <div class="chip" onclick="ask('Cara tukar hadiah?')">🎁 HADIAH</div>
            <div class="chip" onclick="ask('Jenis sampah?')">♻️ JENIS</div>
        </div>
        
        <form class="input-wrapper" onsubmit="handleChat(event)">
            <input type="text" id="user-input" placeholder="Tanya sesuatu ke Eco AI..." autocomplete="off">
            <button type="submit" class="btn-send">
                <i data-lucide="send-horizontal"></i>
            </button>
        </form>
    </div>

    <script>
    lucide.createIcons();
    const chatList = document.getElementById('chat-list');
    const input = document.getElementById('user-input');

    const brain = [
        { keys: ['poin', 'hitung', 'saldo'], reply: 'Poinmu didapat dari sektor sampah atau menyelesaikan <b>Misi Bulanan</b>. Setiap sampah plastik dan plastik punya nilai poin berbeda!' },
        { keys: ['jadwal', 'buka', 'jam'], reply: 'Loopie Bank Sampah buka setiap <b>Hari Sekolah</b> jam <b>09.30</b> di Gazebo Eco. Ditunggu ya!' },
        { keys: ['hadiah', 'tukar', 'reward'], reply: 'Kamu bisa tukar poin di halaman <b>Katalog</b>. Pilih hadiahnya, klik tukar, lalu tunjukkan kodenya di halaman <b>Riwayat</b> ke petugas.' },
        { keys: ['jenis', 'sampah', 'terima'], reply: 'Kami menerima <b>Plastik</b> (botol/gelas), <b>Kertas</b> (buku/kardus),Pastikan sudah bersih ya!' },
        { keys: ['halo', 'hi', 'pagi'], reply: 'Halo! Aku Eco AI. Ada yang bisa aku bantu untuk mempermudah aksi hijau kamu hari ini?' }
    ];

    function handleChat(e) {
        if (e) e.preventDefault();
        const text = input.value.trim();
        if(text) { render('user', text); input.value = ''; botResponse(text); }
    }

    function ask(t) { render('user', t); botResponse(t); }

    function botResponse(text) {
        const loading = document.createElement('div');
        loading.className = 'msg-container';
        loading.innerHTML = `<div class="bubble bot-bubble" id="typing">...</div>`;
        chatList.appendChild(loading);
        window.scrollTo(0, document.body.scrollHeight);

        setTimeout(() => {
            if(document.getElementById('typing')) document.getElementById('typing').parentElement.remove();
            const low = text.toLowerCase();
            const find = brain.find(b => b.keys.some(k => low.includes(k)));
            const reply = find ? find.reply : "Wah, aku belum paham maksudmu. Coba tanya soal <b>Poin, Jadwal,</b> atau <b>Jenis Sampah</b>.";
            render('bot', reply);
            window.scrollTo(0, document.body.scrollHeight);
        }, 800);
    }

    function render(sender, text) {
        const div = document.createElement('div');
        div.className = 'msg-container';
        div.innerHTML = `<div class="bubble ${sender}-bubble">${text}</div>`;
        chatList.appendChild(div);
        lucide.createIcons();
    }

    // TOGGLE NOTIF
    function toggleNotif(e) {
        e.stopPropagation();
        document.getElementById('notifDropdown').classList.toggle('active');
    }

    window.onclick = (e) => {
        const drop = document.getElementById('notifDropdown');
        if (drop && !e.target.closest('.notif-container')) {
            drop.classList.remove('active');
        }
    };
    </script>
</body>
</html>