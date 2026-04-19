<?php
session_start();
require '../koneksi.php';

// 1. Proteksi Login
if (!isset($_SESSION['adminlogin'])) {
    header("Location: loginadmin.php");
    exit;
}

// 1. Ambil Data Admin yang Sedang Login
$id_admin = $_SESSION['adminlogin'];
$query_admin = mysqli_query($koneksi, "SELECT * FROM admins WHERE id = '$id_admin'");

// Cek apakah query admin berhasil
if ($query_admin && mysqli_num_rows($query_admin) > 0) {
    $data_admin = mysqli_fetch_assoc($query_admin);
} else {
    $data_admin = ['nama_lengkap' => 'Admin']; // Nama cadangan jika data tidak ditemukan
}

/* --- LOGIKA PENGAMBILAN DATA KAS --- */
// Menghitung Total Uang Masuk
$qM = mysqli_query($koneksi, "SELECT SUM(jumlah_uang) as total FROM kas_masuk");
$m_data = ($qM) ? mysqli_fetch_assoc($qM) : null;
$totalUangMasuk = $m_data['total'] ?? 0;

// Menghitung Total Uang Keluar
$qK = mysqli_query($koneksi, "SELECT SUM(total_biaya) as total FROM kas_keluar");
$k_data = ($qK) ? mysqli_fetch_assoc($qK) : null;
$totalKeluar = $k_data['total'] ?? 0;

// Saldo Akhir
$saldoKas = $totalUangMasuk - $totalKeluar;


/* --- LOGIKA LEADERBOARD / RANKING KELAS --- */
// Mengambil 3 besar kelas berdasarkan total berat setoran
$qTop = mysqli_query($koneksi, "SELECT kelas, SUM(berat) AS total 
                                FROM setoran_sampah 
                                GROUP BY kelas 
                                ORDER BY total DESC 
                                LIMIT 3");


/* --- LOGIKA PARTISIPASI KELAS & VOLUME --- */
// Menghitung jumlah kelas yang sudah aktif menyetor
$r2 = mysqli_query($koneksi, "SELECT COUNT(DISTINCT kelas) AS total FROM setoran_sampah");
$res2 = ($r2) ? mysqli_fetch_assoc($r2) : null;
$totalAktif = $res2['total'] ?? 0;

// Menghitung total seluruh volume sampah yang terkumpul
$r1 = mysqli_query($koneksi, "SELECT SUM(berat) AS total FROM setoran_sampah");
$res1 = ($r1) ? mysqli_fetch_assoc($r1) : null;
$totalSampah = $res1['total'] ?? 0;


/* --- LOGIKA NOTIFIKASI (BADGE) --- */
// Antrean Validasi (Status Pending)
$q_pending = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM bukti_misi WHERE status_verifikasi = 'pending'");
$jumlah_pending = ($q_pending) ? mysqli_fetch_assoc($q_pending)['total'] : 0;

// Peringatan Stok Reward Rendah (Kurang dari 5)
$q_low = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM rewards WHERE stok < 5");
$jumlah_stok_low = ($q_low) ? mysqli_fetch_assoc($q_low)['total'] : 0;

/* --- LOGIKA LOG AKTIVITAS ADMIN --- */
// Mengambil 5 aktivitas terbaru dari tabel log_aktivitas
// Diasumsikan tabel memiliki kolom: nama_admin, aksi, tanggal
$query_log = mysqli_query($koneksi, "SELECT * FROM log_aktivitas ORDER BY tanggal DESC LIMIT 5");
$query_antrean = mysqli_query($koneksi, "SELECT p.*, r.nama_hadiah, u.nama_kelas, u.wali_kelas 
    FROM penukaran p JOIN rewards r ON p.id_reward = r.id_reward JOIN users u ON p.id_user = u.id_user 
    WHERE p.status = 'pending' ORDER BY p.tanggal_tukar DESC");

$query_history_broadcast = mysqli_query($koneksi, "SELECT * FROM notifikasi WHERE id_user IS NULL ORDER BY created_at DESC LIMIT 3");
?>


?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loopie Admin | Dashboard</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="icon" href="data:;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAAMklEQVR42mP8/5+hfgAJgHFQAzBgYMAAsQCfP39u/Pnz58afP39u/Pnz58YfP39uMAAAsA8K/P39uMAAAAAASUVORK5CYII=">
    <link rel="stylesheet" href="admin.css">

    <style>
        :root { 
            --terra-cotta: #CA7842; --moss-green: #B2CD9C; 
            --dark-oak: #4B352A; --parchment: #F0F2BD; 
            --white: #ffffff;
        }

        /* Custom Scrollbar Terracotta */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: rgba(75, 53, 42, 0.05); }
        ::-webkit-scrollbar-thumb { background: var(--terra-cotta); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #b06535; }

        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background: var(--parchment); color: var(--dark-oak); 
            margin: 0; display: flex; min-height: 100vh; overflow-x: hidden;
        }

        /* --- SIDEBAR DENGAN SCROLL --- */
        .sidebar { 
            width: 280px; height: 100vh; background: var(--dark-oak); 
            position: fixed; left: 0; top: 0; display: flex; 
            flex-direction: column; z-index: 1000; padding: 40px 0;
        }

        .sidebar-brand { padding: 0 40px mb-5; margin-bottom: 30px; }
        .sidebar-brand h2 { font-weight: 800; color: white; margin: 0; }
        .sidebar-brand small { color: rgba(255,255,255,0.4); font-size: 0.65rem; letter-spacing: 2px; font-weight: 700; }

        /* Area Menu yang bisa di-scroll */
        .sidebar-menu {
            flex-grow: 1;
            overflow-y: auto;
            padding: 0 20px;
            /* Sembunyikan scrollbar bawaan di sidebar agar tetap clean */
            scrollbar-width: thin;
            scrollbar-color: var(--terra-cotta) transparent;
        }

        .nav-link-adm { 
            color: rgba(240, 242, 189, 0.6); text-decoration: none; 
            display: flex; align-items: center; gap: 15px; padding: 14px 20px;
            margin-bottom: 8px; font-weight: 700; border-radius: 16px; transition: 0.3s; 
        }
        .nav-link-adm:hover { color: var(--parchment); background: rgba(255,255,255,0.05); }
        .nav-link-adm.active { color: white; background: var(--terra-cotta); box-shadow: 0 4px 15px rgba(202, 120, 66, 0.3); }

        .sidebar-footer { padding: 20px; border-top: 1px solid rgba(255,255,255,0.05); }

        /* --- CONTENT AREA --- */
        .content { 
            margin-left: 280px; 
            width: calc(100% - 280px); 
            padding: 60px;
            min-height: 100vh;
        }

        .info-tag {
            background: rgba(202, 120, 66, 0.1); color: var(--terra-cotta);
            padding: 6px 16px; border-radius: 10px; font-size: 0.7rem; font-weight: 800;
        }

        .stat-card { 
            background: var(--white); border-radius: 35px; padding: 35px; 
            box-shadow: 0 10px 30px rgba(75, 53, 42, 0.04); position: relative; 
            overflow: hidden; height: 100%; border: none;
        }
        .label-kecil { font-size: 0.65rem; font-weight: 800; opacity: 0.5; text-transform: uppercase; letter-spacing: 1.5px; display: block; }
        .angka-besar { font-size: 2.2rem; font-weight: 800; color: var(--dark-oak); margin: 5px 0; display: block; }
        
        .box-item { 
            background: var(--white); border-radius: 25px; padding: 22px; 
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.02);
        }

        .dark-box { background: var(--dark-oak); color: white; border-radius: 35px; padding: 40px; }

        @media (max-width: 992px) {
            .sidebar { width: 80px; padding: 30px 0; }
            .sidebar-brand, .nav-link-adm span { display: none; }
            .content { margin-left: 80px; width: calc(100% - 80px); padding: 30px; }
            .sidebar-menu { padding: 0 10px; }
        }
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
     
    .nav-link-adm {
    position: relative; /* Wajib agar posisi badge bisa diatur */
}

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
/* --- RESPONSIVE OPTIMIZATION --- */

@media (max-width: 992px) {
    /* Sidebar jadi lebih ramping (hanya icon) */
    .sidebar { 
        width: 80px; 
        padding: 30px 0; 
    }
    .sidebar-brand, .nav-link-adm span, .sidebar-footer span { 
        display: none; 
    }
    .nav-link-adm { 
        justify-content: center; 
        padding: 15px; 
    }
    /* Content menyesuaikan lebar sidebar baru */
    .content { 
        margin-left: 80px; 
        width: calc(100% - 80px); 
        padding: 30px; 
    }
}

@media (max-width: 768px) {
    .content {
        padding: 20px;
    }
    
    /* Judul dan Ringkasan jadi rata tengah di HP */
    .row.align-items-end {
        text-align: center;
    }
    .col-md-4.text-md-end {
        text-align: center !important;
        margin-top: 20px;
    }

    /* Card Statistik jadi satu kolom agar angka tetap besar */
    .stat-card {
        margin-bottom: 10px;
        padding: 25px;
    }
    .angka-besar {
        font-size: 1.8rem;
    }

    /* Grid Ranking dan Info Finansial jadi tumpuk (stack) */
    .col-lg-7, .col-lg-5 {
        width: 100%;
    }
}

@media (max-width: 480px) {
    /* Penyesuaian teks untuk layar sangat kecil */
    .display-5 {
        font-size: 1.8rem !important;
        font-weight: 800;
    }
    .badge-notif, .badge-stok-warning {
        right: 5px;
        font-size: 0.6rem;
    }
    .box-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    .box-item .text-end {
        text-align: left !important;
    }
}

/* Pastikan tabel (jika ada nanti) bisa di-scroll horizontal */
.table-responsive {
    border: none;
    -webkit-overflow-scrolling: touch;
}
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-brand px-4">
        <h2>Loopie<span style="color:var(--terra-cotta)">.</span></h2>
        <small>ADMIN PANEL</small>
    </div>

    <div class="sidebar-menu">
       <nav>
            <a href="petugas_dashboard.php" class="nav-link-adm active"><i data-lucide="layout-grid" size="18"></i> <span>Dashboard</span></a>
            <a href="petugas_input.php" class="nav-link-adm"><i data-lucide="scale" size="18"></i> <span>Input Data</span></a>
            <a href="petugas_data_siswa.php" class="nav-link-adm"><i data-lucide="users" size="18"></i> <span>Data Siswa</span></a>
            <a href="petugas_kas_masuk.php" class="nav-link-adm "><i data-lucide="trending-up" size="18"></i> <span>Kas Masuk</span></a>
            <a href="petugas_kas_keluar.php" class="nav-link-adm"><i data-lucide="trending-down" size="18"></i> <span>Kas Keluar</span></a>
<a href="petugas_validasi.php" class="nav-link-adm">
    <i data-lucide="check-circle" size="18"></i> 
    <span>Validasi</span>
    
    <?php if($jumlah_pending > 0): ?>
        <span class="badge-notif"><?= $jumlah_pending ?></span>
    <?php endif; ?>            
             <a href="petugas_misi.php" class="nav-link-adm"><i data-lucide="target" size="18"></i> <span>Misi</span></a>
             <a href="petugas_reward.php" class="nav-link-adm">
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
            <i data-lucide="log-out"></i> <span>KELUAR</span>
        </a>
    </div>
</div>

<div class="content">
    <div class="row align-items-end mb-5">
        <div class="col-md-8">
            <span class="info-tag">Halo, <?= htmlspecialchars($data_admin['nama_lengkap']) ?>! 👋</span>
            <h1 class="fw-800 m-0 display-5 mt-2">Ringkasan <span style="color: var(--terra-cotta)">Sistem.</span></h1>
            <p class="text-muted m-0">Informasi operasional bank sampah hari ini.</p>
        </div>
        <div class="col-md-4 text-md-end">
            <span class="label-kecil">Tanggal Hari Ini</span>
            <h4 class="fw-800 m-0" style="color: var(--terra-cotta)"><?= date('d F Y') ?></h4>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="stat-card">
                <span class="label-kecil">Total Volume Sampah</span>
                <span class="angka-besar"><?= number_format($totalSampah, 1) ?> <small class="fs-6 opacity-50">KG</small></span>
                <div style="width:50px; height:5px; background:var(--moss-green); border-radius:10px;"></div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="stat-card">
                <span class="label-kecil">Sisa Saldo Kas</span>
                <span class="angka-besar"><small class="fs-5 opacity-50">Rp</small> <?= number_format($saldoKas, 0, ',', '.') ?></span>
                <div style="width:50px; height:5px; background:var(--terra-cotta); border-radius:10px;"></div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="stat-card">
                <span class="label-kecil">Partisipasi Kelas</span>
                <span class="angka-besar"><?= $totalAktif ?> <small class="fs-6 opacity-50">KELAS</small></span>
                <div style="width:50px; height:5px; background:var(--dark-oak); border-radius:10px;"></div>
            </div>
        </div>
    </div>

    <div class="row g-5">
        <div class="col-lg-7">
            <h5 class="fw-800 mb-4 d-flex align-items-center gap-2">
                <i data-lucide="award" style="color: var(--terra-cotta)" size="20"></i> Ranking Setoran
            </h5>
            
            <?php if ($qTop && mysqli_num_rows($qTop) > 0): ?>
                <?php $no=1; while($row=mysqli_fetch_assoc($qTop)): ?>
                <div class="box-item shadow-sm">
                    <div class="d-flex align-items-center">
                        <div style="width:40px; height:40px; background:#F8F9F2; border-radius:12px; display:flex; align-items:center; justify-content:center; font-weight:800; margin-right:15px;"><?= $no++ ?></div>
                        <div>
                            <span class="label-kecil">Identitas Kelas</span>
                            <span class="fw-800 text-uppercase"><?= htmlspecialchars($row['kelas']) ?></span>
                        </div>
                    </div>
                    <div class="text-end">
                        <span class="label-kecil">Total Berat</span>
                        <span class="fw-800" style="color: var(--terra-cotta)"><?= number_format($row['total'], 1) ?> KG</span>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="box-item justify-content-center py-4 text-muted">Belum ada data setoran.</div>
            <?php endif; ?>
        </div>

        <div class="col-lg-5">
            <h5 class="fw-800 mb-4 d-flex align-items-center gap-2">
                <i data-lucide="info" style="color: var(--terra-cotta)" size="20"></i> Info Finansial
            </h5>
            <div class="dark-box shadow-sm">
                <span class="label-kecil text-white-50">Total Gross Sales</span>
                <h2 class="fw-800 m-0">Rp <?= number_format($totalUangMasuk, 0, ',', '.') ?></h2>
                <hr class="my-4 opacity-10">
                <div class="d-flex gap-3">
                    <i data-lucide="trending-up" style="color: var(--moss-green)"></i>
                    <p class="small m-0 opacity-50">Data dihitung dari akumulasi seluruh kas masuk (penjualan sampah) yang tercatat di sistem.</p>
                </div>
            </div>
        </div>
    </div>
    <div class="row g-5 mt-2">
        <div class="col-12">
            <h5 class="fw-800 mb-4 d-flex align-items-center gap-2">
                <i data-lucide="history" style="color: var(--terra-cotta)" size="20"></i> Log Aktivitas Admin
            </h5>
            <div class="stat-card p-4">
                <?php if ($query_log && mysqli_num_rows($query_log) > 0): ?>
                    <?php while($log = mysqli_fetch_assoc($query_log)): ?>
                    <div class="d-flex align-items-center justify-content-between p-3 mb-2 rounded-4" style="background: rgba(75, 53, 42, 0.02); border-left: 4px solid var(--terra-cotta);">
                        <div class="d-flex align-items-center gap-3">
                            <div class="p-2 bg-white rounded-3 shadow-sm">
                                <i data-lucide="user-check" size="16" style="color: var(--dark-oak)"></i>
                            </div>
                            <div>
                                <span class="label-kecil" style="margin:0;"><?= htmlspecialchars($log['nama_admin']) ?></span>
                                <p class="m-0 fw-700 small"><?= htmlspecialchars($log['aksi']) ?></p>
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="label-kecil">Waktu</span>
                            <span class="small fw-800 opacity-70"><?= date('H:i', strtotime($log['tanggal'])) ?></span>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center py-4 opacity-30">
                        <i data-lucide="ghost" size="30" class="mb-2"></i>
                        <p class="small fw-800 m-0">Belum ada aktivitas admin hari ini.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
</div>

<script>
    lucide.createIcons();
</script>

</body>
</html>