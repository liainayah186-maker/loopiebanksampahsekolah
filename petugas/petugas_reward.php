<?php
session_start();
require '../koneksi.php'; 

// Proteksi Admin
if (!isset($_SESSION['adminlogin'])) {
    header("Location: loginadmin.php");
    exit;
}

// Ambil data dari tabel rewards
$hadiah = mysqli_query($koneksi, "SELECT * FROM rewards ORDER BY id_reward DESC");
/* --- LOGIKA NOTIFIKASI (BADGE) --- */
// Antrean Validasi (Status Pending)
$q_pending = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM bukti_misi WHERE status_verifikasi = 'pending'");
$jumlah_pending = ($q_pending) ? mysqli_fetch_assoc($q_pending)['total'] : 0;

// Peringatan Stok Reward Rendah (Kurang dari 5)
$q_low = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM rewards WHERE stok < 5");
$jumlah_stok_low = ($q_low) ? mysqli_fetch_assoc($q_low)['total'] : 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loopie Admin | Manajemen Reward</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root { 
            --terra-cotta: #CA7842; --moss-green: #B2CD9C; 
            --dark-oak: #4B352A; --parchment: #F0F2BD; 
            --white: #ffffff; --soft-shadow: 0 10px 30px rgba(75, 53, 42, 0.05);
        }
 /* Area Menu yang bisa di-scroll */
        .sidebar-menu {
            flex-grow: 1;
            overflow-y: auto;
            padding: 0 20px;
            /* Sembunyikan scrollbar bawaan di sidebar agar tetap clean */
            scrollbar-width: thin;
            scrollbar-color: var(--terra-cotta) transparent;
        }


        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background: var(--parchment); color: var(--dark-oak); 
            margin: 0; display: flex; min-height: 100vh;
        }

        /* --- SIDEBAR --- */
        .sidebar { 
            width: 280px; height: 100vh; background: var(--dark-oak); 
            position: fixed; left: 0; top: 0; display: flex; 
            flex-direction: column; z-index: 1000; padding: 40px 0;
        }
        .sidebar-brand { padding: 0 40px; margin-bottom: 30px; color: white; }
        .sidebar-menu { flex-grow: 1; overflow-y: auto; padding: 0 20px; }
        .nav-link-adm { 
            color: rgba(240, 242, 189, 0.6); text-decoration: none; 
            display: flex; align-items: center; gap: 15px; padding: 14px 20px;
            margin-bottom: 8px; font-weight: 700; border-radius: 16px; transition: 0.3s; 
        }
        .nav-link-adm:hover { color: var(--parchment); background: rgba(255,255,255,0.05); }
        .nav-link-adm.active { color: white; background: var(--terra-cotta); box-shadow: 0 4px 15px rgba(202, 120, 66, 0.3); }
        .sidebar-footer { padding: 20px; border-top: 1px solid rgba(255,255,255,0.05); }

           .badge-notif {
    background-color: #ef4444; /* Warna merah cerah agar mencolok */
    color: white;
    font-size: 0.65rem;
    font-weight: 800;
    padding: 2px 6px;
    border-radius: 50px;
    position: absolute;
    right: 15px; /* Sesuaikan dengan padding sidebar kamu */
    top: 50%;
    transform: translateY(-50%);
    box-shadow: 0 2px 10px rgba(239, 68, 68, 0.3);
    border: 2px solid var(--dark-oak); /* Biar ada pemisah dengan warna sidebar */
}
.badge-stok-warning {
    background-color: #ef4444; /* Warna merah cerah agar mencolok */
    color: white;
    font-size: 0.65rem;
    font-weight: 800;
    padding: 2px 6px;
    border-radius: 50px;
    position: absolute;
    right: 15px; /* Sesuaikan dengan padding sidebar kamu */
    top: 50%;
    transform: translateY(-50%);
    box-shadow: 0 2px 10px rgba(239, 68, 68, 0.3);
    border: 2px solid var(--dark-oak); /* Biar ada pemisah dengan warna sidebar */
}

        /* --- CONTENT --- */
        .content { margin-left: 280px; padding: 60px; width: calc(100% - 280px); }

        .reward-card { 
            background: var(--white); border-radius: 30px; padding: 30px;
            box-shadow: var(--soft-shadow); border: 1px solid rgba(0,0,0,0.02);
            height: 100%; transition: 0.4s; position: relative; overflow: hidden;
        }
        .reward-card:hover { transform: translateY(-10px); box-shadow: 0 20px 40px rgba(75, 53, 42, 0.1); }

        .stok-badge {
            padding: 8px 16px; border-radius: 14px; font-size: 0.7rem; font-weight: 800;
            display: flex; align-items: center; gap: 6px;
        }
        .stok-ada { background: #E8F5E9; color: #2E7D32; }
        .stok-habis { background: #FFEBEE; color: #C62828; animation: pulse 2s infinite; }

        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.6; } 100% { opacity: 1; } }

        .btn-action {
            width: 45px; height: 45px; border-radius: 14px; border: none;
            display: flex; align-items: center; justify-content: center;
            transition: 0.3s; background: #F8F9F4; color: var(--dark-oak);
        }
        .btn-action:hover { background: var(--terra-cotta); color: white; transform: rotate(15deg); }

        .reward-name { font-size: 1.3rem; font-weight: 800; margin: 20px 0 10px; color: var(--dark-oak); }
        .point-tag {
            background: rgba(202, 120, 66, 0.1); color: var(--terra-cotta);
            padding: 8px 16px; border-radius: 14px; font-weight: 800; font-size: 0.9rem;
            display: inline-flex; align-items: center; gap: 8px;
        }

        .btn-main {
            background: var(--dark-oak); color: white; border: none;
            padding: 15px 30px; border-radius: 20px; font-weight: 800;
            transition: 0.3s; box-shadow: 0 4px 15px rgba(75, 53, 42, 0.2);
        }
        .btn-main:hover { background: var(--terra-cotta); transform: translateY(-3px); color: white; }

        /* Modal Styling */
        .modal-content { border-radius: 35px; border: none; padding: 20px; box-shadow: 0 25px 50px rgba(0,0,0,0.1); }
        .form-input-neo {
            background: #F8F9F2; border: 2px solid #F8F9F2;
            padding: 15px 20px; border-radius: 18px; font-weight: 700; transition: 0.3s;
        }
        .form-input-neo:focus { border-color: var(--terra-cotta); background: white; box-shadow: none; }
        .label-custom { font-size: 0.65rem; font-weight: 800; opacity: 0.5; letter-spacing: 1.5px; margin-bottom: 8px; display: block; text-transform: uppercase; }
         .nav-link-adm { 
        color: rgba(240, 242, 189, 0.6); 
        text-decoration: none; 
        display: flex; 
        align-items: center; 
        gap: 15px; 
        padding: 14px 20px; 
        margin-bottom: 8px; 
        font-weight: 700; 
        border-radius: 16px; 
        transition: 0.3s;
        position: relative; /* Wajib untuk garis bawah */
    }

    /* Garis bawah yang muncul dari tengah */
    .nav-link-adm::after {
        content: '';
        position: absolute;
        bottom: 10px;
        left: 50%;
        width: 0;
        height: 2px;
        background-color: var(--terra-cotta);
        transition: 0.3s ease;
        transform: translateX(-50%);
    }

    .nav-link-adm:hover { 
        color: white; 
        background: rgba(255,255,255,0.05); 
    }

    .nav-link-adm:hover::after {
        width: 40%; /* Lebar garis saat hover */
    }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-brand px-4">
        <h2 class="fw-800 m-0">Loopie<span style="color:var(--terra-cotta)">.</span></h2>
        <small style="opacity: 0.4; font-size: 0.65rem; letter-spacing: 2px; font-weight: 700;">ADMIN PANEL</small>
    </div>

    <div class="sidebar-menu">
        <nav>
            <a href="petugas_dashboard.php" class="nav-link-adm "><i data-lucide="layout-grid" size="18"></i> <span>Dashboard</span></a>
            <a href="petugas_input.php" class="nav-link-adm"><i data-lucide="scale" size="18"></i> <span>Input Data</span></a>
            <a href="petugas_data_siswa.php" class="nav-link-adm"><i data-lucide="users" size="18"></i> <span>Data Siswa</span></a>
            <a href="petugas_kas_masuk.php" class="nav-link-adm"><i data-lucide="trending-up" size="18"></i> <span>Kas Masuk</span></a>
            <a href="petugas_kas_keluar.php" class="nav-link-adm"><i data-lucide="trending-down" size="18"></i> <span>Kas Keluar</span></a>
<a href="petugas_validasi.php" class="nav-link-adm">
    <i data-lucide="check-circle" size="18"></i> 
    <span>Validasi</span>
    
    <?php if($jumlah_pending > 0): ?>
        <span class="badge-notif"><?= $jumlah_pending ?></span>
    <?php endif; ?>            
             <a href="petugas_misi.php" class="nav-link-adm"><i data-lucide="target" size="18"></i> <span>Misi</span></a>
             <a href="petugas_reward.php" class="nav-link-adm active">
    <i data-lucide="award" size="18"></i> 
    <span>Hadiah</span>
    
    <?php if($jumlah_stok_low > 0): ?>
        <span class="badge-stok-warning"><?= $jumlah_stok_low ?></span>
    <?php endif; ?>
</a>
            <a href="petugas_broadcast.php" class="nav-link-adm"><i data-lucide="megaphone" size="18"></i> <span>Pusat Pesan & Antrean</span></a>
            <a href="petugas_laporan.php" class="nav-link-adm"><i data-lucide="file-text" size="18"></i> <span>Laporan</span></a>
        </nav>
    </div>

    <div class="sidebar-footer">
        <a href="logout_admin.php" class="nav-link-adm" style="background: rgba(202, 120, 66, 0.1); color: var(--terra-cotta);">
            <i data-lucide="log-out" size="18"></i> <span>KELUAR</span>
        </a>
    </div>
</div>

<div class="content">
    <div class="d-flex justify-content-between align-items-end mb-5">
        <div>
            <span class="badge" style="background: rgba(202, 120, 66, 0.1); color: var(--terra-cotta); font-weight: 800; font-size: 0.7rem; padding: 8px 15px; border-radius: 10px;">INVENTORY</span>
            <h1 class="fw-800 display-5 mt-2">Manajemen <span style="color: var(--terra-cotta)">Hadiah.</span></h1>
            <p class="text-muted m-0">Pantau ketersediaan stok dan kelola item penukaran poin.</p>
        </div>
        <button class="btn-main d-flex align-items-center gap-3" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i data-lucide="package-plus" size="22"></i> TAMBAH ITEM BARU
        </button>
    </div>

    <div class="row g-4">
        <?php while($h = mysqli_fetch_assoc($hadiah)): 
            $habis = ($h['stok'] <= 0);
        ?>
        <div class="col-md-6 col-xl-4">
            <div class="reward-card">
                <div class="d-flex justify-content-between align-items-start">
                    <span class="stok-badge <?= $habis ? 'stok-habis' : 'stok-ada' ?>">
                        <i data-lucide="<?= $habis ? 'alert-circle' : 'check-circle-2' ?>" size="14"></i>
                        STOK: <?= $h['stok'] ?>
                    </span>
                    <div class="d-flex gap-2">
                        <button class="btn-action" onclick="openRefill(<?= $h['id_reward'] ?>, '<?= addslashes($h['nama_hadiah']) ?>')" title="Refill Stok">
                            <i data-lucide="refresh-cw" size="18"></i>
                        </button>
                        <a href="proses_reward.php?hapus=<?= $h['id_reward'] ?>" class="btn-action text-danger" onclick="return confirm('Hapus item reward ini?')" title="Hapus">
                            <i data-lucide="trash-2" size="18"></i>
                        </a>
                    </div>
                </div>
                
                <h3 class="reward-name"><?= htmlspecialchars($h['nama_hadiah']) ?></h3>
                <div class="point-tag mb-3">
                    <i data-lucide="zap" size="16"></i> <?= number_format($h['harga_poin']) ?> POIN
                </div>
                <p class="text-muted small mb-0" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                    <?= htmlspecialchars($h['deskripsi']) ?>
                </p>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<div class="modal fade" id="modalRefill" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="proses_reward.php" method="POST">
                <div class="text-center mb-4">
                    <div class="bg-light d-inline-flex p-4 rounded-circle mb-3">
                        <i data-lucide="box" size="32" class="text-terra-cotta"></i>
                    </div>
                    <h4 class="fw-800 m-0">Refill Stok</h4>
                    <p class="text-muted" id="namaItemRefill"></p>
                </div>
                
                <div class="modal-body p-0">
                    <input type="hidden" name="id_reward" id="idItemRefill">
                    <label class="label-custom text-center w-100">Jumlah Stok Tambahan</label>
                    <input type="number" name="tambah_stok" class="form-input-neo w-100 text-center fs-4" placeholder="0" required min="1">
                </div>
                
                <div class="mt-4">
                    <button name="btn_refill" class="btn-main w-100 py-3">UPDATE STOK SEKARANG</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="bg-light p-3 rounded-4 text-terra-cotta"><i data-lucide="plus-square"></i></div>
                <h4 class="fw-800 m-0">Tambah Reward</h4>
            </div>
            <form action="proses_reward.php" method="POST">
                <div class="mb-3">
                    <label class="label-custom">Nama Hadiah</label>
                    <input name="nama_hadiah" class="form-input-neo w-100" placeholder="Cth: Voucher Kantin Rp10k" required>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="label-custom">Harga Poin</label>
                        <input name="harga_poin" type="number" class="form-input-neo w-100" placeholder="500" required>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="label-custom">Stok Awal</label>
                        <input name="stok" type="number" class="form-input-neo w-100" placeholder="50" required>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="label-custom">Deskripsi Singkat</label>
                    <textarea name="deskripsi" class="form-input-neo w-100" rows="3" placeholder="Tuliskan detail reward..."></textarea>
                </div>
                <button name="simpan" class="btn-main w-100 py-3">RILIS KE KATALOG</button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    lucide.createIcons();
    function openRefill(id, nama) {
        document.getElementById('idItemRefill').value = id;
        document.getElementById('namaItemRefill').innerText = nama;
        new bootstrap.Modal(document.getElementById('modalRefill')).show();
    }
</script>
</body>
</html>