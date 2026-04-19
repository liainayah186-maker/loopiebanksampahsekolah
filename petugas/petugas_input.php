<?php
session_start();
require '../koneksi.php';

// Proteksi login admin
if (!isset($_SESSION['adminlogin'])) {
    header("Location: loginadmin.php");
    exit;
}

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
    <title>Loopie Admin | Timbang Sampah</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root { 
            --terra-cotta: #CA7842; --moss-green: #B2CD9C; 
            --dark-oak: #4B352A; --parchment: #F0F2BD; 
            --white: #ffffff; --soft-shadow: 0 10px 30px rgba(75, 53, 42, 0.05);
        }

        /* Custom Scrollbar Terracotta */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: rgba(75, 53, 42, 0.05); }
        ::-webkit-scrollbar-thumb { background: var(--terra-cotta); border-radius: 10px; }

        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background: var(--parchment); color: var(--dark-oak); 
            margin: 0; display: flex; min-height: 100vh; overflow-x: hidden;
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

        /* Search & List Style */
        .search-box { 
            background: var(--white); border-radius: 20px; padding: 12px 25px; 
            border: 1px solid rgba(75, 53, 42, 0.05); display: flex; 
            align-items: center; gap: 10px; box-shadow: var(--soft-shadow);
        }
        .search-box input { border: none; outline: none; width: 100%; font-weight: 700; color: var(--dark-oak); background: transparent; }

        .user-item { 
            background: var(--white); border-radius: 24px; padding: 20px; 
            margin-bottom: 15px; display: flex; justify-content: space-between; 
            align-items: center; border: 1px solid rgba(75, 53, 42, 0.02); 
            transition: 0.3s; box-shadow: var(--soft-shadow);
        }
        .user-item:hover { border-color: var(--moss-green); transform: translateX(5px); }

        .btn-select { 
            background: #F8F9F2; color: var(--dark-oak); border: none; 
            padding: 10px 18px; border-radius: 14px; font-weight: 800; 
            font-size: 0.7rem; transition: 0.3s; letter-spacing: 1px;
        }
        .btn-select:hover { background: var(--moss-green); color: var(--dark-oak); }

        /* Form Card */
        .card-custom { 
            background: var(--white); border-radius: 28px; padding: 40px; 
            box-shadow: var(--soft-shadow); border: 1px solid rgba(75, 53, 42, 0.05);
        }

        .poin-counter-card { 
            background: var(--dark-oak); color: white; border-radius: 24px; 
            padding: 25px; margin-top: 20px; display: flex; 
            justify-content: space-between; align-items: center; 
        }
        .poin-val { font-size: 2.8rem; font-weight: 800; color: var(--moss-green); line-height: 1; }

        .form-select, .form-control {
            border-radius: 16px; padding: 15px 20px; border: 2px solid #F8F9F2;
            background: #F8F9F2; font-weight: 700; color: var(--dark-oak); transition: 0.3s;
        }
        .form-select:focus, .form-control:focus { background: white; border-color: var(--terra-cotta); box-shadow: none; }

        .btn-submit {
            background: var(--terra-cotta); color: white; border: none; padding: 18px;
            border-radius: 18px; font-weight: 800; width: 100%; transition: 0.3s;
            letter-spacing: 1px; display: flex; align-items: center; justify-content: center; gap: 10px;
        }
        .btn-submit:hover { background: var(--dark-oak); transform: translateY(-3px); box-shadow: 0 10px 20px rgba(75, 53, 42, 0.1); }

        .label-kecil { font-size: 0.65rem; font-weight: 800; opacity: 0.5; text-transform: uppercase; letter-spacing: 1.5px; display: block; }

        #formOverlay { display: none; animation: slideUp 0.4s ease; }
        @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

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
            <a href="petugas_input.php" class="nav-link-adm active"><i data-lucide="scale" size="18"></i> <span>Input Data</span></a>
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
            <span class="info-tag">PANEL TRANSAKSI</span>
            <h1 class="fw-800 m-0 display-5 mt-2">Timbang <span style="color: var(--terra-cotta)">Setoran.</span></h1>
            <?php
$rekap_hari_ini = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT SUM(berat) as total FROM setoran_sampah WHERE tanggal = CURDATE()"));
?>
<div class="mt-3 d-flex gap-3">
    <div class="badge rounded-pill px-3 py-2" style="background: var(--moss-green); color: var(--dark-oak);">
        <i data-lucide="package" size="14" class="me-1"></i> 
        Total Hari Ini: <strong><?= number_format($rekap_hari_ini['total'] ?? 0, 1) ?> KG</strong>
    </div>
</div>
            <p class="text-muted m-0">Catat berat sampah dan akumulasi poin otomatis hari ini.</p>
        </div>
        
        <div class="col-md-4 text-md-end">
            <span class="label-kecil">Tanggal Hari Ini</span>
            <h4 class="fw-800 m-0" style="color: var(--terra-cotta)"><?= date('d F Y') ?></h4>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="search-box mb-4">
                <i data-lucide="search" size="20" color="var(--terra-cotta)"></i>
                <input type="text" id="searchUser" placeholder="Cari nama kelas...">
            </div>

            <div id="userList" style="max-height: 60vh; overflow-y: auto; padding-right: 10px; scrollbar-width: thin;">
                <?php
                $users = mysqli_query($koneksi, "SELECT * FROM users ORDER BY nama_kelas ASC");
                while($u = mysqli_fetch_assoc($users)) { ?>
                    <div class="user-item" data-nama="<?= $u['nama_kelas']; ?>">
                        <div>
                            <div class="fw-800 text-uppercase" style="font-size: 0.9rem;"><?= $u['nama_kelas']; ?></div>
                            <div style="font-size: 0.75rem; font-weight: 700; opacity: 0.5;">Wali: <?= $u['wali_kelas']; ?></div>
                        </div>
                        <button class="btn-select" 
                                onclick="pilihKelas('<?= $u['nama_kelas']; ?>', '<?= $u['wali_kelas']; ?>')">
                            PILIH
                        </button>
                    </div>
                <?php } ?>
            </div>
        </div>

        <div class="col-lg-7">
            <div id="formOverlay">
                <div class="card-custom">
                    <div class="d-flex align-items-center gap-3 mb-4 p-3 shadow-sm border" style="background:var(--parchment); border-radius:20px;">
                        <div class="bg-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width:50px; height:50px;">
                            <i data-lucide="user-plus" color="var(--terra-cotta)" size="24"></i>
                        </div>
                        <div>
                            <h5 class="fw-800 m-0 text-uppercase" id="selectedNama">--</h5>
                            <small id="selectedWali" class="fw-700 opacity-50">--</small>
                        </div>
                    </div>

                    <form action="simpan_setoran.php" method="POST">
                        <input type="hidden" name="nama_kelas" id="inputNamaKelas">
                        
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="small fw-800 opacity-50 mb-2 letter-spacing-1 text-uppercase" style="font-size: 0.65rem;">Kategori Sampah</label>
                                <select name="kategori" class="form-select" required>
                                    <option value="ORGANIK">ORGANIK</option>
                                    <option value="ANORGANIK">ANORGANIK</option>
                                    <option value="B3">B3</option>
                                    <option value="RESIDU">RESIDU</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="small fw-800 opacity-50 mb-2 letter-spacing-1 text-uppercase" style="font-size: 0.65rem;">Berat Bersih (KG)</label>
                                <input type="number" step="0.1" name="berat" id="inputBerat" class="form-control" placeholder="0.0" required>
                            </div>
                        </div>

                        <div class="poin-counter-card shadow-lg">
                            <div>
                                <small class="fw-800 opacity-50 d-block text-uppercase" style="font-size: 0.65rem; letter-spacing: 1px;">Poin Akan Diterima</small>
                                <div class="poin-val" id="poinResult">0</div>
                                <input type="hidden" name="poin" id="inputPoin">
                            </div>
                            <div class="text-end">
                                <div class="bg-white rounded-circle p-2 d-inline-block mb-1">
                                    <i data-lucide="zap" fill="var(--terra-cotta)" color="var(--terra-cotta)" size="24"></i>
                                </div>
                                <div class="small fw-800 opacity-50" style="font-size: 0.6rem;">RATE: 10 Poin/Kg</div>
                            </div>
                        </div>

                        <button type="submit" name="simpan" class="btn-submit mt-4 shadow-sm">
                            <i data-lucide="save" size="20"></i> SIMPAN SETORAN
                        </button>
                    </form>
                </div>
            </div>
<hr class="my-4" style="opacity: 0.1;">
<div class="mt-4">
    <h6 class="fw-800 mb-3" style="font-size: 0.8rem; opacity: 0.6;">RIWAYAT INPUT HARI INI</h6>
   <div id="historyList">
    
    <?php
    $tgl_skrg = date('Y-m-d');
    // Sesuaikan ORDER BY dengan kolom 'id'
    $history = mysqli_query($koneksi, "SELECT * FROM setoran_sampah WHERE tanggal = '$tgl_skrg' ORDER BY id DESC LIMIT 3");

    if ($history && mysqli_num_rows($history) > 0) {
        while($h = mysqli_fetch_assoc($history)) { ?>
            <div class="d-flex justify-content-between align-items-center p-3 mb-2 rounded-4" style="background: #F8F9F2; border: 1px dashed rgba(75, 53, 42, 0.1);">
                <div>
                    <div class="fw-800" style="font-size: 0.8rem;"><?= htmlspecialchars($h['kelas']) ?></div>
                    <small class="text-muted"><?= htmlspecialchars($h['kategori']) ?> • <?= $h['berat'] ?> KG</small>
                </div>
                <div class="d-flex align-items-center gap-2">
    <span class="fw-800 text-success">+<?= $h['poin'] ?></span>
    <a href="javascript:void(0)" 
       class="text-danger btn-hapus" 
       data-id="<?= $h['id'] ?>" 
       data-kelas="<?= $h['kelas'] ?>">
       <i data-lucide="trash-2" size="14"></i>
    </a>
</div>
            </div>
        <?php } 
    } else {
        echo '<p class="text-muted small text-center py-3">Belum ada aktivitas setoran hari ini.</p>';
    }
    ?>
</div>
    </div>
</div>

            <div id="emptyState" class="text-center py-5" style="margin-top: 50px;">
                <div style="opacity: 0.15;">
                    <i data-lucide="arrow-left-to-line" size="80" class="mb-3"></i>
                </div>
                <h5 class="fw-800 opacity-50">Siap Menimbang?</h5>
                <p class="text-muted">Pilih salah satu kelas di daftar kiri<br>untuk mulai mencatat setoran sampah mereka.</p>
            </div>
        </div>
    </div>
</div>

<script>
    lucide.createIcons();

    // LIVE SEARCH
    $("#searchUser").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $(".user-item").filter(function() {
            $(this).toggle($(this).attr('data-nama').toLowerCase().indexOf(value) > -1)
        });
    });

    // PILIH KELAS
    function pilihKelas(nama, wali) {
        $('#inputNamaKelas').val(nama);
        $('#selectedNama').text(nama);
        $('#selectedWali').text("Wali: " + wali);
        
        $('#emptyState').hide();
        $('#formOverlay').fadeIn(400);
        
        $('#inputBerat').val('').focus();
        $('#poinResult').text('0');
        $('#inputPoin').val('0');
        
        lucide.createIcons();
    }

    // HITUNG POIN
    $('#inputBerat, select[name="kategori"]').on('input change', function() {
    let berat = $('#inputBerat').val();
    let kategori = $('select[name="kategori"]').val();
    let rate = 10; // default

    // Logika rate per kategori
    if(kategori == 'ORGANIK') rate = 5;
    if(kategori == 'ANORGANIK') rate = 15;
    if(kategori == 'B3') rate = 20;
    if(kategori == 'RESIDU') rate = 2;

    let poin = Math.floor(berat * rate);
    $('#poinResult').text(poin.toLocaleString('id-ID'));
    $('#inputPoin').val(poin);
    $('.small.fw-800.opacity-50').last().text('RATE: ' + rate + ' Poin/Kg');
});
// SWEETALERT NOTIFICATION
    <?php if(isset($_GET['status'])): ?>
        const status = "<?= $_GET['status'] ?>";
        if(status === 'sukses'){
            Swal.fire({ icon: 'success', title: 'Berhasil!', text: 'Setoran sudah dicatat.', confirmButtonColor: '#CA7842' });
        } else if(status === 'hapus_sukses'){
            Swal.fire({ icon: 'info', title: 'Dibatalkan', text: 'Setoran dihapus & poin dikurangi.', confirmButtonColor: '#4B352A' });
        }
        window.history.replaceState({}, document.title, "petugas_input.php");
    <?php endif; ?>
    // Konfirmasi Hapus dengan SweetAlert2
$(document).on('click', '.btn-hapus', function(e) {
    e.preventDefault();
    const idSetoran = $(this).data('id');
    const namaKelas = $(this).data('kelas');

    Swal.fire({
        title: 'Batalkan Setoran?',
        text: "Poin kelas " + namaKelas + " akan dikurangi kembali!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#CA7842', // Warna Terra Cotta
        cancelButtonColor: '#4B352A',  // Warna Dark Oak
        confirmButtonText: 'Ya, Batalkan!',
        cancelButtonText: 'Tidak jadi'
    }).then((result) => {
        if (result.isConfirmed) {
            // Jika klik Ya, arahkan ke file hapus_setoran.php
            window.location.href = "hapus_setoran.php?id=" + idSetoran;
        }
    });
});
</script>
</body>
</html>