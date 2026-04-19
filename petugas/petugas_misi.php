<?php
session_start();
require '../koneksi.php';

// Proteksi Login
if (!isset($_SESSION['adminlogin'])) {
    header("Location: loginadmin.php");
    exit;
}

// Ambil data misi
$misi_query = mysqli_query($koneksi, "SELECT * FROM misi ORDER BY id_misi DESC");
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
    <title>Loopie Admin | Manajemen Misi</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        :root { 
            --terra-cotta: #CA7842; --moss-green: #B2CD9C; 
            --dark-oak: #4B352A; --parchment: #F0F2BD; 
            --white: #ffffff; --soft-shadow: 0 10px 30px rgba(75, 53, 42, 0.05);
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: rgba(75, 53, 42, 0.05); }
        ::-webkit-scrollbar-thumb { background: var(--terra-cotta); border-radius: 10px; }

        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background: var(--parchment); color: var(--dark-oak); 
            display: flex; min-height: 100vh; margin: 0; overflow-x: hidden;
        }

        /* --- SIDEBAR --- */
        .sidebar { 
            width: 280px; height: 100vh; background: var(--dark-oak); 
            position: fixed; left: 0; top: 0; display: flex; 
            flex-direction: column; z-index: 1000; padding: 40px 0;
        }
        .sidebar-brand { padding: 0 40px; margin-bottom: 30px; }
        .sidebar-brand h2 { font-weight: 800; color: white; margin: 0; }
        .sidebar-brand small { color: rgba(255,255,255,0.4); font-size: 0.65rem; letter-spacing: 2px; font-weight: 700; }
        .sidebar-menu { flex-grow: 1; overflow-y: auto; padding: 0 20px; scrollbar-width: thin; }
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
        .content { margin-left: 280px; width: calc(100% - 280px); padding: 60px; min-height: 100vh; }

        .card-table { background: white; border-radius: 30px; padding: 40px; box-shadow: var(--soft-shadow); border: 1px solid rgba(0,0,0,0.02); }

        .btn-tambah { 
            background: var(--dark-oak); color: white; border: none; padding: 15px 25px; 
            border-radius: 18px; font-weight: 800; transition: 0.3s; display: flex; 
            align-items: center; gap: 10px; cursor: pointer;
        }
        .btn-tambah:hover { background: var(--terra-cotta); transform: translateY(-3px); box-shadow: 0 10px 20px rgba(202, 120, 66, 0.2); color: white; }

        .misi-item { 
            background: #F8F9F2; border-radius: 24px; padding: 25px; 
            margin-bottom: 15px; display: flex; align-items: center; 
            justify-content: space-between; transition: 0.3s; border: 1px solid transparent;
        }
        .misi-item:hover { background: white; border-color: var(--terra-cotta); transform: translateX(10px); box-shadow: var(--soft-shadow); }

        .label-kecil { font-size: 0.65rem; font-weight: 800; opacity: 0.5; text-transform: uppercase; letter-spacing: 1.5px; display: block; margin-bottom: 8px; }

        .form-control-neo { 
            background: #F8F9F2; border: 2px solid #F8F9F2; border-radius: 15px; 
            padding: 12px 18px; font-weight: 700; color: var(--dark-oak); transition: 0.3s;
        }
        .form-control-neo:focus { background: white; border-color: var(--terra-cotta); box-shadow: none; }

        .modal-content { border-radius: 30px; border: none; padding: 25px; box-shadow: 0 25px 50px rgba(0,0,0,0.1); }

        .icon-box {
            width: 55px; height: 55px; display: flex; align-items: center;
            justify-content: center; background: white; border-radius: 18px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        @media (max-width: 992px) {
            .sidebar { width: 80px; }
            .sidebar-brand, .nav-link-adm span { display: none; }
            .content { margin-left: 80px; width: calc(100% - 80px); padding: 30px; }
        }
         /* Area Menu yang bisa di-scroll */
        .sidebar-menu {
            flex-grow: 1;
            overflow-y: auto;
            padding: 0 20px;
            scrollbar-width: thin;
            scrollbar-color: var(--terra-cotta) transparent;
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
             <a href="petugas_misi.php" class="nav-link-adm active"><i data-lucide="target" size="18"></i> <span>Misi</span></a>
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
    <div class="d-flex justify-content-between align-items-end mb-5">
        <div>
            <span class="badge" style="background: rgba(202, 120, 66, 0.1); color: var(--terra-cotta); font-weight: 800; font-size: 0.7rem; padding: 8px 15px; border-radius: 10px;">MISSION CONTROL</span>
            <h1 class="fw-800 display-5 mt-2">Manajemen <span style="color: var(--terra-cotta)">Misi.</span></h1>
            <p class="text-muted m-0">Kelola tantangan aktif untuk meningkatkan partisipasi siswa.</p>
        </div>
        <button class="btn-tambah" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i data-lucide="plus-circle" size="20"></i> RILIS MISI BARU
        </button>
    </div>

    <?php if(isset($_GET['pesan'])): ?>
        <div class="alert alert-dark border-0 rounded-4 mb-4 shadow-sm py-3 px-4 d-flex align-items-center gap-3">
            <i data-lucide="info" size="20" class="text-terra-cotta"></i>
            <span class="fw-700">
                <?php 
                    if($_GET['pesan'] == 'berhasil') echo "Misi baru berhasil dirilis!";
                    if($_GET['pesan'] == 'diupdate') echo "Data misi berhasil diperbarui!";
                    if($_GET['pesan'] == 'terhapus') echo "Misi dan progres terkait berhasil dihapus!";
                ?>
            </span>
        </div>
    <?php endif; ?>

    <div class="card-table">
        <?php if(mysqli_num_rows($misi_query) > 0): ?>
            <?php while($m = mysqli_fetch_assoc($misi_query)): ?>
            <div class="misi-item">
                <div class="d-flex align-items-center gap-4">
                    <div class="icon-box">
                        <i data-lucide="<?= $m['icon'] ?: 'target' ?>" color="var(--terra-cotta)" size="24"></i>
                    </div>
                    <div>
                        <h5 class="fw-800 m-0"><?= htmlspecialchars($m['judul_misi']) ?></h5>
                        <div class="d-flex gap-3 mt-1">
                            <small class="text-muted fw-700"><i data-lucide="check-circle-2" size="12" class="me-1"></i> Target: <?= $m['target_cap'] ?>x</small>
                            <small class="text-muted fw-700"><i data-lucide="database" size="12" class="me-1"></i> Bonus: <?= number_format($m['poin_hadiah']) ?> Poin</small>
                        </div>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-white shadow-sm border rounded-4 p-3" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $m['id_misi'] ?>">
                        <i data-lucide="edit-3" size="18" class="text-dark"></i>
                    </button>
                    <a href="proses_misi.php?hapus=<?= $m['id_misi'] ?>" class="btn btn-white shadow-sm border rounded-4 p-3" onclick="return confirm('Hapus misi ini? Semua progres partisipasi siswa pada misi ini juga akan terhapus permanen.')">
                        <i data-lucide="trash-2" size="18" class="text-danger"></i>
                    </a>
                </div>
            </div>

            <div class="modal fade" id="modalEdit<?= $m['id_misi'] ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form action="proses_misi.php" method="POST">
                            <input type="hidden" name="id_misi" value="<?= $m['id_misi'] ?>">
                            <div class="d-flex align-items-center gap-3 mb-4">
                                <div class="bg-light p-3 rounded-4"><i data-lucide="edit-2" class="text-terra-cotta"></i></div>
                                <h4 class="fw-800 m-0">Edit Misi</h4>
                            </div>
                            
                            <div class="mb-3">
                                <label class="label-kecil">Judul Misi</label>
                                <input type="text" name="judul" class="form-control-neo w-100" value="<?= $m['judul_misi'] ?>" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="label-kecil">Target Aksi</label>
                                    <input type="number" name="target" class="form-control-neo w-100" value="<?= $m['target_cap'] ?>" required>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="label-kecil">Poin Hadiah</label>
                                    <input type="number" name="bonus" class="form-control-neo w-100" value="<?= $m['poin_hadiah'] ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="label-kecil">Icon (Lucide Name)</label>
                                <input type="text" name="icon" class="form-control-neo w-100" value="<?= $m['icon'] ?>">
                            </div>

                            <button type="submit" name="edit_misi" class="btn-tambah w-100 justify-content-center">SIMPAN PERUBAHAN</button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="text-center py-5">
                <i data-lucide="ghost" size="48" class="text-muted opacity-20 mb-3"></i>
                <p class="text-muted fw-700">Belum ada misi yang dirilis.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="proses_misi.php" method="POST">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div class="bg-light p-3 rounded-4"><i data-lucide="rocket" class="text-terra-cotta"></i></div>
                    <h4 class="fw-800 m-0">Misi Baru</h4>
                </div>
                
                <div class="mb-3">
                    <label class="label-kecil">Judul Misi</label>
                    <input type="text" name="judul" class="form-control-neo w-100" placeholder="Cth: Setor 5 Botol Plastik" required>
                </div>

                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="label-kecil">Target Aksi</label>
                        <input type="number" name="target" class="form-control-neo w-100" placeholder="5" required>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="label-kecil">Poin Hadiah</label>
                        <input type="number" name="bonus" class="form-control-neo w-100" placeholder="1000" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="label-kecil">Nama Icon Lucide</label>
                    <input type="text" name="icon" class="form-control-neo w-100" value="target">
                </div>

                <div class="mb-4">
                    <label class="label-kecil">Deskripsi Singkat</label>
                    <textarea name="deskripsi" class="form-control-neo w-100" rows="3" placeholder="Jelaskan cara menyelesaikan misi ini..."></textarea>
                </div>

                <input type="hidden" name="tgl_mulai" value="<?= date('Y-m-d') ?>">
                <input type="hidden" name="tgl_selesai" value="<?= date('Y-m-d', strtotime('+1 month')) ?>">
                
                <button type="submit" name="tambah_misi" class="btn-tambah w-100 justify-content-center">RILIS MISI SEKARANG</button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>lucide.createIcons();</script>
</body>
</html>