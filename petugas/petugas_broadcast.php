<?php
session_start();
require '../koneksi.php';

// Proteksi Admin
if (!isset($_SESSION['adminlogin'])) {
    header("Location: loginadmin.php");
    exit;
}

// --- LOGIKA HITUNG NOTIFIKASI SIDEBAR ---
$q_count_klaim = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM penukaran WHERE status = 'pending'");
$row_klaim = mysqli_fetch_assoc($q_count_klaim);
$jumlah_pending_klaim = $row_klaim['total'];

$q_count_misi = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM bukti_misi WHERE status_verifikasi = 'pending'");
$row_misi = mysqli_fetch_assoc($q_count_misi);
$jumlah_pending_misi = $row_misi['total'];

$q_stok_low = mysqli_query($koneksi, "SELECT COUNT(*) as total_low FROM rewards WHERE stok < 5");
$row_stok_low = mysqli_fetch_assoc($q_stok_low);
$jumlah_stok_low = $row_stok_low['total_low'];


// --- LOGIKA 1: KIRIM BROADCAST ---
$status_broadcast = '';
if (isset($_POST['send_broadcast'])) {
    $judul = mysqli_real_escape_string($koneksi, $_POST['judul']);
    $pesan = mysqli_real_escape_string($koneksi, $_POST['pesan']);
    $query = "INSERT INTO notifikasi (id_user, judul, pesan, is_read, created_at) VALUES (NULL, '$judul', '$pesan', 0, NOW())";
    if (mysqli_query($koneksi, $query)) {
        $status_broadcast = 'success_broadcast';
    }
}

// --- LOGIKA 2: KONFIRMASI HADIAH ---
$status_konfirmasi = '';
if (isset($_GET['konfirmasi'])) {
    $id_tukar = mysqli_real_escape_string($koneksi, $_GET['konfirmasi']);
    $cek = mysqli_query($koneksi, "SELECT p.*, r.nama_hadiah, u.id_user FROM penukaran p 
                                   JOIN rewards r ON p.id_reward = r.id_reward 
                                   JOIN users u ON p.id_user = u.id_user 
                                   WHERE p.id_penukaran = '$id_tukar'");
    
    if($d = mysqli_fetch_assoc($cek)) {
        $id_u = $d['id_user'];
        $barang = $d['nama_hadiah'];
        mysqli_query($koneksi, "UPDATE penukaran SET status = 'selesai' WHERE id_penukaran = '$id_tukar'");
        
        $j_notif = "Hadiah Siap Diambil! 🎁";
        $p_notif = "Klaim $barang kelas kamu sudah divalidasi petugas. Silahkan ambil di kantor.";
        mysqli_query($koneksi, "INSERT INTO notifikasi (id_user, judul, pesan, is_read, created_at) VALUES ('$id_u', '$j_notif', '$p_notif', 0, NOW())");
        $status_konfirmasi = 'success_konfirmasi';
    }
}

$query_antrean = mysqli_query($koneksi, "SELECT p.*, r.nama_hadiah, u.nama_kelas, u.wali_kelas 
    FROM penukaran p JOIN rewards r ON p.id_reward = r.id_reward JOIN users u ON p.id_user = u.id_user 
    WHERE p.status = 'pending' ORDER BY p.tanggal_tukar DESC");

$query_history_broadcast = mysqli_query($koneksi, "SELECT * FROM notifikasi WHERE id_user IS NULL ORDER BY created_at DESC LIMIT 3");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loopie Admin | Loopie Hub</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        :root { 
            --terra-cotta: #CA7842; --moss-green: #B2CD9C; 
            --dark-oak: #4B352A; --parchment: #F0F2BD; 
            --white: #ffffff; --soft-shadow: 0 10px 30px rgba(75, 53, 42, 0.05);
        }

        .sidebar-menu {
            flex-grow: 1; overflow-y: auto; padding: 0 20px;
            scrollbar-width: thin; scrollbar-color: var(--terra-cotta) transparent;
        }

        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background: var(--parchment); color: var(--dark-oak); 
            margin: 0; display: flex; min-height: 100vh;
        }

        .sidebar { 
            width: 280px; height: 100vh; background: var(--dark-oak); 
            position: fixed; left: 0; top: 0; display: flex; 
            flex-direction: column; z-index: 1000; padding: 40px 0;
        }
        .sidebar-brand { padding: 0 40px; margin-bottom: 30px; }
        .sidebar-brand h2 { font-weight: 800; color: white; margin: 0; }
        .sidebar-brand small { color: rgba(255,255,255,0.4); font-size: 0.65rem; letter-spacing: 2px; font-weight: 700; }
        
        .nav-link-adm { 
            color: rgba(240, 242, 189, 0.6); text-decoration: none; 
            display: flex; align-items: center; gap: 15px; padding: 14px 20px; 
            margin-bottom: 8px; font-weight: 700; border-radius: 16px; transition: 0.3s;
            position: relative;
        }

        .nav-link-adm::after {
            content: ''; position: absolute; bottom: 10px; left: 50%; width: 0;
            height: 2px; background-color: var(--terra-cotta); transition: 0.3s ease; transform: translateX(-50%);
        }
        .nav-link-adm:hover { color: white; background: rgba(255,255,255,0.05); }
        .nav-link-adm:hover::after { width: 40%; }
        .nav-link-adm.active { color: white; background: var(--terra-cotta); box-shadow: 0 4px 15px rgba(202, 120, 66, 0.3); }
        .nav-link-adm.active::after { display: none; }

        .badge-sidebar {
            font-size: 0.65rem; font-weight: 800; padding: 2px 7px; border-radius: 50px;
            position: absolute; right: 15px; top: 50%; transform: translateY(-50%);
            border: 2px solid var(--dark-oak); color: white;
        }

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

        .content { 
            margin-left: 280px; width: calc(100% - 280px); 
            padding: 60px; min-height: 100vh;
        }
        .info-tag {
            background: rgba(202, 120, 66, 0.1); color: var(--terra-cotta);
            padding: 6px 16px; border-radius: 10px; font-size: 0.7rem; font-weight: 800;
            display: inline-block; margin-bottom: 10px; text-transform: uppercase;
        }
        .card-custom { 
            background: var(--white); border-radius: 35px; padding: 35px; 
            box-shadow: var(--soft-shadow); border: none; 
        }

        .form-control-loopie {
            background: #F8F9F2; border: 2px solid #F8F9F2; border-radius: 18px;
            padding: 15px 20px; font-weight: 700; color: var(--dark-oak); transition: 0.3s;
        }
        .form-control-loopie:focus {
            background: white; border-color: var(--terra-cotta); outline: none; box-shadow: none;
        }

        .btn-send {
            background: var(--terra-cotta); color: white; border-radius: 18px;
            padding: 15px; font-weight: 800; border: none; width: 100%; transition: 0.3s;
        }
        .btn-send:hover { background: var(--dark-oak); transform: translateY(-3px); }

        .antrean-item {
            background: #FDFDFB; border-radius: 22px; padding: 20px;
            margin-bottom: 15px; border-left: 10px solid var(--moss-green);
            display: flex; justify-content: space-between; align-items: center;
        }
        .kode-v {
            background: var(--dark-oak); color: white; padding: 5px 12px;
            border-radius: 10px; font-family: monospace; font-weight: 800; font-size: 0.8rem;
        }
        .btn-validasi {
            background: var(--moss-green); color: var(--dark-oak);
            padding: 10px 20px; border-radius: 14px; font-weight: 800;
            text-decoration: none; font-size: 0.75rem; transition: 0.3s; cursor: pointer;
        }
        .btn-validasi:hover { background: var(--dark-oak); color: white; }

        @media (max-width: 992px) {
            .sidebar { width: 80px; }
            .sidebar-brand, .nav-link-adm span { display: none; }
            .content { margin-left: 80px; width: calc(100% - 80px); padding: 30px; }
        }
        
        /* SweetAlert Custom Style */
        .swal2-popup { border-radius: 30px !important; font-family: 'Plus Jakarta Sans', sans-serif !important; }
        .swal2-confirm { background-color: var(--terra-cotta) !important; border-radius: 15px !important; }
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
            <a href="petugas_dashboard.php" class="nav-link-adm"><i data-lucide="layout-grid" size="18"></i> <span>Dashboard</span></a>
            <a href="petugas_input.php" class="nav-link-adm"><i data-lucide="scale" size="18"></i> <span>Input Data</span></a>
            <a href="petugas_data_siswa.php" class="nav-link-adm"><i data-lucide="users" size="18"></i> <span>Data Siswa</span></a>
            <a href="petugas_kas_masuk.php" class="nav-link-adm"><i data-lucide="trending-up" size="18"></i> <span>Kas Masuk</span></a>
            <a href="petugas_kas_keluar.php" class="nav-link-adm"><i data-lucide="trending-down" size="18"></i> <span>Kas Keluar</span></a>
            <a href="petugas_validasi.php" class="nav-link-adm">
                <i data-lucide="check-circle" size="18"></i> <span>Validasi</span>
                <?php if($jumlah_pending_misi > 0): ?>
                    <span class="badge-sidebar" style="background: #ef4444;"><?= $jumlah_pending_misi ?></span>
                <?php endif; ?>
            </a>
            <a href="petugas_misi.php" class="nav-link-adm"><i data-lucide="target" size="18"></i> <span>Misi</span></a>
            <a href="petugas_reward.php" class="nav-link-adm">
                <i data-lucide="award" size="18"></i> <span>Hadiah</span>
                <?php if($jumlah_stok_low > 0): ?>
                    <span class="badge-sidebar" style="background: #ef4444  ;"><?= $jumlah_stok_low ?></span>
                <?php endif; ?>
            </a>
            <a href="petugas_broadcast.php" class="nav-link-adm active"><i data-lucide="megaphone" size="18"></i> <span>Pusat Pesan & Antrean</span></a>
               
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
    <div class="mb-5">
        <span class="info-tag">Pusat Manajemen</span>
        <h1 class="fw-800 m-0 display-5 mt-2">Loopie <span style="color: var(--terra-cotta)">Hub.</span></h1>
        <p class="text-muted m-0">Kirim pengumuman masal dan proses penukaran hadiah kelas.</p>
    </div>

    <div class="row g-4">
        
        <div class="col-lg-4">
            <div class="card-custom mb-4">
                <h6 class="fw-800 mb-4 opacity-50 text-uppercase" style="font-size:0.7rem; letter-spacing:1px;">Buat Siaran Baru</h6>
                <form method="POST" id="formBroadcast">
                    <div class="mb-3">
                        <label class="small fw-800 mb-2 opacity-70">JUDUL PENGUMUMAN</label>
                        <input type="text" name="judul" class="form-control form-control-loopie" required>
                    </div>
                    <div class="mb-4">
                        <label class="small fw-800 mb-2 opacity-70">ISI PESAN</label>
                        <textarea name="pesan" class="form-control form-control-loopie" rows="4" required></textarea>
                    </div>
                    <button type="button" onclick="confirmBroadcast()" class="btn-send d-flex align-items-center justify-content-center gap-2">
                        <i data-lucide="send" size="18"></i> KIRIM SIARAN
                    </button>
                    <button type="submit" name="send_broadcast" id="submitBroadcast" style="display:none;"></button>
                </form>
            </div>

           <div class="card-custom mb-4">
    <h6 class="fw-800 mb-3 opacity-50 text-uppercase" style="font-size:0.7rem; letter-spacing:1px;">Statistik Hub</h6>
    <div class="row g-2">
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between p-3 rounded-4 mb-2" style="background: rgba(59, 130, 246, 0.1);">
                <div>
                    <div class="small fw-700 opacity-70" style="color: #1e40af;">Pending Klaim</div>
                    <div class="h4 fw-800 m-0" style="color: #1e40af;"><?= $jumlah_pending_klaim ?></div>
                </div>
                <div class="p-2 rounded-3" style="background: white;">
                    <i data-lucide="clock" size="20" style="color: #3b82f6;"></i>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between p-3 rounded-4" style="background: rgba(202, 120, 66, 0.1);">
                <div>
                    <div class="small fw-700 opacity-70" style="color: var(--terra-cotta);">Stok Menipis</div>
                    <div class="h4 fw-800 m-0" style="color: var(--terra-cotta);"><?= $jumlah_stok_low ?></div>
                </div>
                <div class="p-2 rounded-3" style="background: white;">
                    <i data-lucide="alert-triangle" size="20" style="color: var(--terra-cotta);"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="mt-3">
        <a href="petugas_reward.php" class="text-decoration-none small fw-800 opacity-50 hover-opacity-100" style="font-size: 0.65rem; color: var(--dark-oak);">
            LIHAT DETAIL REWARD <i data-lucide="arrow-right" size="12"></i>
        </a>
    </div>
</div>

            <div class="card-custom">
                <h6 class="fw-800 mb-3 opacity-50 text-uppercase" style="font-size:0.65rem; letter-spacing:1px;">Siaran Terakhir</h6>
                <?php while($h = mysqli_fetch_assoc($query_history_broadcast)): ?>
                    <div class="p-3 mb-2 rounded-4" style="background: rgba(75, 53, 42, 0.03); border: 1px dashed rgba(75, 53, 42, 0.1);">
                        <small class="d-block fw-800" style="font-size: 0.65rem; color: var(--terra-cotta);"><?= date('d M Y', strtotime($h['created_at'])) ?></small>
                        <div class="fw-800 small text-dark mt-1"><?= htmlspecialchars($h['judul']) ?></div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card-custom" style="min-height: 100%;">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="fw-800 m-0 opacity-50 text-uppercase" style="font-size:0.7rem; letter-spacing:1px;">Antrean Klaim Hadiah</h6>
                    <span class="badge rounded-pill px-3 py-2" style="background: var(--parchment); color: var(--terra-cotta); font-weight: 800;">
                        <?= mysqli_num_rows($query_antrean) ?> Permintaan
                    </span>
                </div>

                <div style="max-height: 800px; overflow-y: auto; padding-right: 5px;">
                    <?php if(mysqli_num_rows($query_antrean) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($query_antrean)): ?>
                        <div class="antrean-item">
                            <div>
                                <div class="mb-2">
                                    <span class="kode-v"><?= $row['kode_klaim'] ?></span>
                                    <small class="ms-2 opacity-50 fw-700"><?= date('H:i', strtotime($row['tanggal_tukar'])) ?></small>
                                </div>
                                <h5 class="fw-800 m-0"><?= $row['nama_kelas'] ?></h5>
                                <p class="m-0 text-muted small fw-700">
                                    Wali: <?= $row['wali_kelas'] ?> | 
                                    <span class="text-terra-cotta">Hadiah: <?= $row['nama_hadiah'] ?></span>
                                </p>
                            </div>
                            <button onclick="confirmValidasi('<?= $row['id_penukaran'] ?>')" class="btn-validasi border-0">
                                VALIDASI
                            </button>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-5 opacity-30">
                            <i data-lucide="coffee" size="40"></i>
                            <p class="fw-800 mt-2">Tidak ada antrean klaim.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div> </div> ```


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    lucide.createIcons();

    // Notifikasi Sukses Setelah Action
    <?php if($status_broadcast == 'success_broadcast'): ?>
        Swal.fire({
            icon: 'success',
            title: 'Siaran Terkirim!',
            text: 'Pengumuman telah disebarkan ke semua user.',
            showConfirmButton: false,
            timer: 2000
        });
    <?php endif; ?>

    <?php if($status_konfirmasi == 'success_konfirmasi'): ?>
        Swal.fire({
            icon: 'success',
            title: 'Klaim Berhasil!',
            text: 'Hadiah telah divalidasi dan notifikasi terkirim.',
            showConfirmButton: false,
            timer: 2000
        });
    <?php endif; ?>

    // Fungsi Konfirmasi Kirim Siaran
    function confirmBroadcast() {
        Swal.fire({
            title: 'Kirim Siaran?',
            text: "Pengumuman ini akan muncul di semua aplikasi siswa.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Kirim Sekarang!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('submitBroadcast').click();
            }
        });
    }

    // Fungsi Konfirmasi Validasi Klaim
    function confirmValidasi(id) {
        Swal.fire({
            title: 'Validasi Klaim?',
            text: "Pastikan hadiah sudah diserahkan ke perwakilan kelas.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Selesaikan Klaim',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '?konfirmasi=' + id;
            }
        });
    }
</script>
</body>
</html> 