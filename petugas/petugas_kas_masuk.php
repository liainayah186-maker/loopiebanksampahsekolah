<?php
session_start(); 
require '../koneksi.php';

// Proteksi Halaman
if (!isset($_SESSION['adminlogin'])) { 
    header("Location: loginadmin.php"); 
    exit; 
}

if(isset($_POST['simpan'])){
    $t = mysqli_real_escape_string($koneksi, $_POST['tgl']); 
    $s = mysqli_real_escape_string($koneksi, $_POST['sumber']); 
    $b = mysqli_real_escape_string($koneksi, $_POST['berat']); 
    $u = mysqli_real_escape_string($koneksi, $_POST['uang']);
    
    $query = mysqli_query($koneksi, "INSERT INTO kas_masuk (tanggal, sumber, berat_jual, jumlah_uang) VALUES ('$t', '$s', '$b', '$u')");
    
    if($query){
        // Menggunakan status agar dibaca oleh SweetAlert di bawah
        header("Location: petugas_kas_masuk.php?status=sukses");
        exit;
    } else {
        $error = true;
    }
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
    <title>Loopie Admin | Kas Masuk</title>
    
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

        /* Custom Scrollbar Terracotta */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: rgba(75, 53, 42, 0.05); }
        ::-webkit-scrollbar-thumb { background: var(--terra-cotta); border-radius: 10px; }

        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background: var(--parchment); color: var(--dark-oak); 
            margin: 0; display: flex; min-height: 100vh; overflow-x: hidden;
        }

        /* --- SIDEBAR (Sesuai Contoh Kamu) --- */
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

        .card-custom { 
            background: var(--white); border-radius: 28px; padding: 40px; 
            box-shadow: var(--soft-shadow); border: 1px solid rgba(75, 53, 42, 0.05);
        }

        .form-control-neo { 
            background: #F8F9F2; border: 2px solid #F8F9F2;
            border-radius: 16px; padding: 15px 20px; font-weight: 700; color: var(--dark-oak); transition: 0.3s;
        }
        .form-control-neo:focus { background: white; border-color: var(--terra-cotta); box-shadow: none; outline: none; }

        .input-group-neo { 
            background: #F8F9F2; border-radius: 16px; border: 2px solid #F8F9F2; 
            overflow: hidden; display: flex; align-items: center; transition: 0.3s;
        }
        .input-group-neo:focus-within { background: white; border-color: var(--terra-cotta); }
        .input-group-neo .form-control-neo { border: none; background: transparent; flex: 1; }
        .input-group-text-neo { padding: 0 20px; font-weight: 800; color: var(--terra-cotta); }

        .btn-submit {
            background: var(--dark-oak); color: white; border: none; padding: 18px;
            border-radius: 18px; font-weight: 800; width: 100%; transition: 0.3s;
            letter-spacing: 1px; display: flex; align-items: center; justify-content: center; gap: 10px;
        }
        .btn-submit:hover { background: var(--terra-cotta); transform: translateY(-3px); box-shadow: 0 10px 20px rgba(202, 120, 66, 0.1); color: white; }

        .label-kecil { font-size: 0.65rem; font-weight: 800; opacity: 0.5; text-transform: uppercase; letter-spacing: 1.5px; display: block; }

        .note-card {
            background: rgba(255, 255, 255, 0.4); border: 2px dashed rgba(75, 53, 42, 0.1);
            border-radius: 28px; padding: 35px; height: 100%;
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
            /* Sembunyikan scrollbar bawaan di sidebar agar tetap clean */
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
            <a href="petugas_kas_masuk.php" class="nav-link-adm active"><i data-lucide="trending-up" size="18"></i> <span>Kas Masuk</span></a>
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
            <span class="info-tag">PANEL KEUANGAN</span>
            <h1 class="fw-800 m-0 display-5 mt-2">Jual Ke <span style="color: var(--terra-cotta)">Pengepul.</span></h1>
            <p class="text-muted m-0">Konversi timbangan sampah menjadi pemasukan kas nyata.</p>
        </div>
        <div class="col-md-4 text-md-end">
            <span class="label-kecil">Tanggal Transaksi</span>
            <h4 class="fw-800 m-0" style="color: var(--terra-cotta)"><?= date('d F Y') ?></h4>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card-custom">
                <form method="POST">
                    <div class="mb-4">
                        <label class="label-kecil mb-2">Tanggal Catat</label>
                        <input type="date" name="tgl" class="form-control form-control-neo" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="mb-4">
                        <label class="label-kecil mb-2">Nama Pengepul / Pembeli</label>
                        <input type="text" name="sumber" class="form-control form-control-neo" placeholder="Cth: UD. Barokah, CV Sampah Sejahtera" required>
                    </div>

                   <div class="row">
    <div class="col-md-4 mb-4">
        <label class="label-kecil mb-2">Berat (KG)</label>
        <div class="input-group-neo">
            <input type="number" step="0.01" min="0" name="berat" id="berat" class="form-control-neo" placeholder="0.00" required>
            <span class="input-group-text-neo">KG</span>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <label class="label-kecil mb-2">Harga / KG</label>
        <div class="input-group-neo">
            <span class="input-group-text-neo">Rp</span>
            <input type="number" id="harga_satuan" class="form-control-neo" placeholder="0">
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <label class="label-kecil mb-2">Total Terima</label>
        <div class="input-group-neo">
            <span class="input-group-text-neo">Rp</span>
            <input type="number" name="uang" id="total_uang" class="form-control-neo" placeholder="0" required readonly style="background: #e9ecef;">
        </div>
    </div>
</div>
<small class="text-muted d-block mb-4" style="font-size: 0.7rem; margin-top: -15px;">*Total uang akan terhitung otomatis saat berat dan harga diisi.</small>
                    

                    <button type="submit" name="simpan" class="btn-submit mt-2">
                        <i data-lucide="banknote" size="22"></i> CATAT PEMASUKAN SEKARANG
                    </button>
                    
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="note-card">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div class="bg-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width:50px; height:50px;">
                        <i data-lucide="trending-up" color="var(--terra-cotta)" size="24"></i>
                    </div>
                    <div>
                        <h6 class="fw-800 m-0">Aliran Kas</h6>
                        <small class="fw-700 opacity-50">Sistem Pencatatan</small>
                    </div>
                </div>
                <p class="small text-muted" style="line-height: 1.8;">Pastikan nominal uang yang dimasukkan sesuai dengan nota fisik dari pengepul. Data ini akan langsung mempengaruhi saldo kas utama di Dashboard.</p>
                <hr class="my-4 opacity-5">
                <div class="p-3 rounded-4" style="background: rgba(202, 120, 66, 0.05); border: 1px solid rgba(202, 120, 66, 0.1);">
                    <small class="fw-700 text-terra-cotta d-block mb-1">Tips:</small>
                    <p class="small text-muted mb-0" style="font-style: italic;">Simpan nota fisik sebagai bukti audit di akhir bulan.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Inisialisasi Icon Lucide
    lucide.createIcons();

    // Fungsi Pop-up Notifikasi
    window.onload = function() {
        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');

        if (status === 'sukses') {
            Swal.fire({
                title: 'Pemasukan Dicatat!',
                text: 'Data penjualan sampah berhasil masuk ke kas.',
                icon: 'success',
                background: '#ffffff',
                confirmButtonColor: '#CA7842', // Warna Terracotta
                borderRadius: '20px'
            });
            // Bersihkan URL dari parameter ?status=sukses agar tidak muncul lagi saat refresh
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    };

    // Logika Kalkulator Otomatis (Opsional tapi sangat disarankan)
    const inputBerat = document.getElementById('berat');
    const inputHarga = document.getElementById('harga_satuan');
    const inputTotal = document.getElementById('total_uang');

    if(inputBerat && inputHarga) {
        [inputBerat, inputHarga].forEach(input => {
            input.addEventListener('input', function() {
                const b = parseFloat(inputBerat.value) || 0;
                const h = parseFloat(inputHarga.value) || 0;
                inputTotal.value = Math.round(b * h);
            });
        });
    }
</script>
</body>
</html>