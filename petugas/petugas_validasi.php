<?php
session_start();
require '../koneksi.php'; 

if (!isset($_SESSION['adminlogin'])) {
    header("Location: loginadmin.php");
    exit;
}

$query_validasi = mysqli_query($koneksi, "SELECT 
    bukti_misi.id_bukti, 
    bukti_misi.foto_bukti, 
    bukti_misi.tgl_upload, 
    bukti_misi.status_verifikasi,
    bukti_misi.id_user,
    bukti_misi.id_misi,
    users.username, 
    users.nama_kelas, 
    misi.judul_misi, 
    misi.target_cap 
    FROM bukti_misi 
    JOIN users ON bukti_misi.id_user = users.id_user 
    JOIN misi ON bukti_misi.id_misi = misi.id_misi 
    WHERE bukti_misi.status_verifikasi = 'pending' 
    ORDER BY bukti_misi.tgl_upload ASC");
    
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
    <title>Loopie Admin | Validasi Misi</title>
    
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

    .sidebar-menu { 
        flex-grow: 1; 
        overflow-y: auto; 
        padding: 0 20px; 
        scrollbar-width: thin; 
        scrollbar-color: var(--terra-cotta) transparent;
    }

    /* KONSISTENSI NAVBAR & HOVER EFFECT */
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

    /* Menu Aktif */
    .nav-link-adm.active { 
        color: white; 
        background: var(--terra-cotta); 
        box-shadow: 0 4px 15px rgba(202, 120, 66, 0.3); 
    }
    
    .nav-link-adm.active::after { display: none; } /* Hilangkan garis di menu aktif */

    .sidebar-footer { padding: 20px; border-top: 1px solid rgba(255,255,255,0.05); }

    /* --- CONTENT & CARDS --- */
    .content { margin-left: 280px; width: calc(100% - 280px); padding: 60px; min-height: 100vh; }

    .val-card {
        background: var(--white); border-radius: 32px; overflow: hidden;
        box-shadow: var(--soft-shadow); border: 1px solid rgba(75, 53, 42, 0.05);
        transition: 0.3s; height: 100%; display: flex; flex-direction: column;
    }
    .val-card:hover { transform: translateY(-8px); box-shadow: 0 20px 40px rgba(75, 53, 42, 0.1); }

    /* Fix Rasio Foto */
    .img-container { width: 100%; aspect-ratio: 4 / 3; overflow: hidden; background: #f8f9f2; }
    .img-bukti { width: 100%; height: 100%; object-fit: cover; cursor: zoom-in; transition: 0.5s; }
    .img-bukti:hover { transform: scale(1.1); }

    .card-body-custom { padding: 24px; flex-grow: 1; display: flex; flex-direction: column; }

    .btn-acc { 
        background: #2D5A27; color: white; border: none; padding: 14px; 
        border-radius: 18px; font-weight: 800; transition: 0.3s; 
        flex-grow: 1; text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center;
    }
    .btn-reject { 
        background: #fee2e2; color: #ef4444; border: none; padding: 14px; 
        border-radius: 18px; font-weight: 800; transition: 0.3s; 
        width: 55px; text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center;
    }

    .label-kecil { font-size: 0.6rem; font-weight: 800; opacity: 0.5; text-transform: uppercase; letter-spacing: 1.5px; display: block; }
    .info-tag { background: rgba(202, 120, 66, 0.1); color: var(--terra-cotta); padding: 6px 16px; border-radius: 10px; font-size: 0.7rem; font-weight: 800; }

    @media (max-width: 992px) {
        .sidebar { width: 80px; }
        .sidebar-brand, .nav-link-adm span { display: none; }
        .content { margin-left: 80px; width: calc(100% - 80px); padding: 30px; }
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
<a href="petugas_validasi.php" class="nav-link-adm active">
    <i data-lucide="check-circle" size="18"></i> 
    <span>Validasi</span>
    
    <?php if($jumlah_pending > 0): ?>
        <span class="badge-notif"><?= $jumlah_pending ?></span>
    <?php endif; ?>            
             <a href="petugas_misi.php" class="nav-link-adm "><i data-lucide="target" size="18"></i> <span>Misi</span></a>
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
    <div class="mb-5">
        <span class="info-tag">VERIFIKASI MISI</span>
        <h1 class="fw-800 m-0 display-5 mt-2">Validasi <span style="color: var(--terra-cotta)">Bukti.</span></h1>
    </div>

    <div class="row g-4 d-flex align-items-stretch"> 
        <?php if(mysqli_num_rows($query_validasi) > 0): ?>
            <?php while($v = mysqli_fetch_assoc($query_validasi)): ?>
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="val-card">
                    <div class="img-container">
                        <img src="../uploads/misi/<?= $v['foto_bukti'] ?>" class="img-bukti" data-bs-toggle="modal" data-bs-target="#zoomImg<?= $v['id_bukti'] ?>">
                    </div>
                    
                    <div class="card-body-custom">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="fw-800 m-0"><?= htmlspecialchars($v['username']) ?></h5>
                                <span class="badge bg-light text-muted fw-700 mt-1" style="font-size: 0.7rem; border: 1px solid #eee;"><?= htmlspecialchars($v['nama_kelas']) ?></span>
                            </div>
                            <small class="text-muted fw-700" style="font-size: 0.7rem;"><?= date('d M, H:i', strtotime($v['tgl_upload'])) ?></small>
                        </div>
                        
                        <div class="p-3 rounded-4 mb-4" style="background: #F8F9F2; border-left: 4px solid var(--terra-cotta);">
                            <span class="label-kecil mb-1">Misi:</span>
                            <span class="fw-800 text-dark mb-2" style="font-size: 0.85rem; line-height: 1.2; display: block;">
                                <?= htmlspecialchars($v['judul_misi']) ?>
                            </span>
                            
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-white rounded-2 border d-flex align-items-center justify-content-center" style="width:24px; height:24px;">
                                    <i data-lucide="gem" size="12" class="text-success"></i>
                                </div>
                                <small class="fw-800 text-success">+<?= $v['target_cap'] ?> cap</small>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-auto">
                            <a href="proses_validasi.php?aksi=terima&id=<?= $v['id_bukti'] ?>&user=<?= $v['id_user'] ?>&misi=<?= $v['id_misi'] ?>" 
                               class="btn-acc shadow-sm">
                                <i data-lucide="check" size="18" class="me-2"></i> TERIMA
                            </a>
                            
                           <button type="button" 
        onclick="tolakBukti(<?= $v['id_bukti'] ?>, <?= $v['id_user'] ?>, <?= $v['id_misi'] ?>)" 
        class="btn-reject shadow-sm">
    <i data-lucide="x" size="20"></i>
</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="zoomImg<?= $v['id_bukti'] ?>" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content bg-transparent border-0">
                        <div class="modal-body p-0 text-center">
                            <img src="../uploads/misi/<?= $v['foto_bukti'] ?>" class="img-fluid rounded-5 shadow-lg border border-white border-4">
                            <div class="mt-4">
                                <button type="button" class="btn btn-dark rounded-pill px-4 fw-bold shadow-sm" data-bs-dismiss="modal">Tutup</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <div class="mb-3">
                    <i data-lucide="check-circle-2" size="48" class="text-muted opacity-20"></i>
                </div>
                <h4 class="fw-800 text-muted">Antrean Bersih!</h4>
                <p class="text-muted small">Belum ada bukti misi baru yang perlu divalidasi.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if(isset($_GET['status'])): ?>
    <script>
        const status = "<?= $_GET['status'] ?>";
        if(status === 'acc_sukses') {
            Swal.fire('Berhasil!', 'Bukti misi telah disetujui.', 'success');
        } else if(status === 'tolak_sukses') {
            Swal.fire('Ditolak!', 'Bukti misi telah ditolak dengan alasan.', 'info');
        } else if(status === 'gagal') {
            Swal.fire('Gagal!', 'Terjadi kesalahan sistem.', 'error');
        }
    </script>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    lucide.createIcons();
   function tolakBukti(id, userId, misiId) {
    Swal.fire({
        title: 'Tolak Bukti Foto?',
        text: "Berikan alasan agar siswa bisa memperbaikinya:",
        input: 'textarea',
        inputPlaceholder: 'Contoh: Foto blur, bukan foto asli...',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Ya, Tolak!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            if (result.value) {
                // Tambahkan parameter user dan misi ke URL
                window.location.href = `proses_validasi.php?aksi=tolak&id=${id}&user=${userId}&misi=${misiId}&alasan=${encodeURIComponent(result.value)}`;
            } else {
                Swal.fire('Wajib Isi!', 'Alasan harus diisi agar user tidak bingung.', 'error');
            }
        }
    })

    }
</script>
</body>
</html>