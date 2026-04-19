<?php
session_start(); 
require '../koneksi.php';

// Tambahkan ini tepat di bawah require '../koneksi.php';
$query_total = mysqli_query($koneksi, "SELECT SUM(total_biaya) as akumulasi FROM kas_keluar");
$data_akumulasi = mysqli_fetch_assoc($query_total);
$total_terpakai = $data_akumulasi['akumulasi'] ?? 0;

if(isset($_POST['save'])){
    $tgl = mysqli_real_escape_string($koneksi, $_POST['tgl']);
    $total_semua = mysqli_real_escape_string($koneksi, $_POST['biaya_total']); 
    
    // Proses Upload Nota
    $nama_baru = "";
    if(!empty($_FILES['nota']['name'])){
        $ekstensi = pathinfo($_FILES['nota']['name'], PATHINFO_EXTENSION);
        $nama_baru = "NOTA_" . time() . "." . $ekstensi;
        move_uploaded_file($_FILES['nota']['tmp_name'], "../assets/img/nota/" . $nama_baru);
    }

    // Ambil data barang (Array)
    $nama_barang = $_POST['nama_barang'];
    $qty_barang = $_POST['qty_barang'];

    // Loop untuk simpan setiap barang
    for ($i = 0; $i < count($nama_barang); $i++) {
        $nama = mysqli_real_escape_string($koneksi, $nama_barang[$i]);
        $qty = mysqli_real_escape_string($koneksi, $qty_barang[$i]);
        
        // LOGIKA PENTING:
        // Nominal uang (total_biaya) cuma masuk di baris indeks ke-0 (barang pertama)
        // Baris selanjutnya diisi 0 supaya pas di-SUM tidak berlipat ganda
        $biaya_input = ($i == 0) ? $total_semua : 0;

        $sql = "INSERT INTO kas_keluar (tanggal, nama_barang, jumlah_barang, total_biaya, gambar_nota) 
                VALUES ('$tgl', '$nama', '$qty', '$biaya_input', '$nama_baru')";
        
        mysqli_query($koneksi, $sql);
    }

    header("Location: petugas_kas_keluar.php?status=sukses");
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
    <title>Loopie Admin | Kas Keluar</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>

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

        .sidebar-menu { flex-grow: 1; overflow-y: auto; padding: 0 20px; scrollbar-width: thin; scrollbar-color: var(--terra-cotta) transparent; }

        .nav-link-adm { 
            color: rgba(240, 242, 189, 0.6); text-decoration: none; 
            display: flex; align-items: center; gap: 15px; padding: 14px 20px; 
            margin-bottom: 8px; font-weight: 700; border-radius: 16px; transition: 0.3s; 
            position: relative;
        }
        .nav-link-adm::after {
            content: ''; position: absolute; bottom: 10px; left: 50%;
            width: 0; height: 2px; background-color: var(--terra-cotta);
            transition: 0.3s ease; transform: translateX(-50%);
        }
        .nav-link-adm:hover { color: white; background: rgba(255,255,255,0.05); }
        .nav-link-adm:hover::after { width: 40%; }
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
        .content { margin-left: 280px; width: calc(100% - 280px); padding: 60px; min-height: 100vh; }
        .info-tag { background: rgba(202, 120, 66, 0.1); color: var(--terra-cotta); padding: 6px 16px; border-radius: 10px; font-size: 0.7rem; font-weight: 800; }
        .card-custom { background: var(--white); border-radius: 28px; padding: 40px; box-shadow: var(--soft-shadow); border: 1px solid rgba(75, 53, 42, 0.05); }
        .label-kecil { font-size: 0.65rem; font-weight: 800; opacity: 0.5; text-transform: uppercase; letter-spacing: 1.5px; display: block; }
        .form-control-neo { background: #F8F9F2; border: 2px solid #F8F9F2; border-radius: 16px; padding: 15px 20px; font-weight: 700; color: var(--dark-oak); transition: 0.3s; }
        .form-control-neo:focus { background: white; border-color: var(--terra-cotta); box-shadow: none; outline: none; }
        .input-group-neo { background: #F8F9F2; border-radius: 16px; border: 2px solid #F8F9F2; overflow: hidden; display: flex; align-items: center; transition: 0.3s; }
        .input-group-neo:focus-within { background: white; border-color: var(--terra-cotta); }
        .input-group-neo .form-control-neo { border: none; background: transparent; flex: 1; }
        .input-group-text-neo { padding: 0 20px; font-weight: 800; color: var(--terra-cotta); }
        .btn-submit { background: var(--dark-oak); color: white; border: none; padding: 18px; border-radius: 18px; font-weight: 800; width: 100%; transition: 0.3s; letter-spacing: 1px; display: flex; align-items: center; justify-content: center; gap: 10px; }
        .btn-submit:hover { background: var(--terra-cotta); transform: translateY(-3px); box-shadow: 0 10px 20px rgba(202, 120, 66, 0.1); color: white; }
        .note-card { background: rgba(255, 255, 255, 0.4); border: 2px dashed rgba(75, 53, 42, 0.1); border-radius: 28px; padding: 35px; height: 100%; }

        @media (max-width: 992px) {
            .sidebar { width: 80px; }
            .sidebar-brand, .nav-link-adm span { display: none; }
            .content { margin-left: 80px; width: calc(100% - 80px); padding: 30px; }
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
            <a href="petugas_kas_masuk.php" class="nav-link-adm "><i data-lucide="trending-up" size="18"></i> <span>Kas Masuk</span></a>
            <a href="petugas_kas_keluar.php" class="nav-link-adm active"><i data-lucide="trending-down" size="18"></i> <span>Kas Keluar</span></a>
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
            <span class="info-tag">KENDALI ANGGARAN</span>
            <h1 class="fw-800 m-0 display-5 mt-2">Belanja <span style="color: var(--terra-cotta)">Kebutuhan.</span></h1>
            <div class="d-flex align-items-center gap-3 mt-2">
                <p class="text-muted m-0">Catat pengeluaran operasional.</p>
                <div style="width: 2px; height: 15px; background: rgba(75, 53, 42, 0.1);"></div>
                <span class="fw-800" style="color: var(--terra-cotta)">
    Total: Rp <?= number_format($total_terpakai, 0, ',', '.') ?>
</span>
            </div>
        </div>
        <div class="col-md-4 text-md-end">
            <span class="label-kecil">Tanggal Hari Ini</span>
            <h4 class="fw-800 m-0" style="color: var(--terra-cotta)"><?= date('d F Y') ?></h4>
        </div>
    </div>

   <div class="card-custom">
    <form method="POST" enctype="multipart/form-data">
        <div class="mb-4">
            <label class="label-kecil mb-2">Tanggal Belanja</label>
            <input type="date" name="tgl" class="form-control-neo" value="<?= date('Y-m-d') ?>" required>
        </div>

        <div id="container-barang">
            <div class="row item-barang mb-3">
                <div class="col-md-8">
                    <label class="label-kecil mb-2">Nama Barang 1</label>
                    <input type="text" name="nama_barang[]" class="form-control-neo" placeholder="Nama barang..." required>
                </div>
                <div class="col-md-4">
                    <label class="label-kecil mb-2">Qty</label>
                    <input type="number" name="qty_barang[]" class="form-control-neo" placeholder="0" required>
                </div>
            </div>
        </div>

        <button type="button" onclick="tambahBaris()" class="btn btn-sm mb-4" style="color: var(--terra-cotta); font-weight: 800;">
            <i data-lucide="plus-circle" size="18"></i> Tambah Barang Lain
        </button>

        <hr>

        <div class="mb-4">
            <label class="label-kecil mb-2">Total Bayar Keseluruhan (Sesuai Nota)</label>
            <div class="input-group-neo">
                <span class="ps-3 fw-bold">Rp</span>
                <input type="number" name="biaya_total" id="inputBiaya" class="form-control-neo" placeholder="0" required style="border:none;">
            </div>
        </div>

        <div class="mb-4">
            <label class="label-kecil mb-2">Upload Bukti Nota Fisik</label>
            <input type="file" name="nota" class="form-control" accept="image/*" required>
        </div>

        <button type="submit" name="save" class="btn-submit">SIMPAN SEMUA DATA</button>
    </form>
</div>
       
<script>
    // Inisialisasi Icon
    lucide.createIcons();

    // Notifikasi Sukses
    const status = new URLSearchParams(window.location.search).get('status');
    if (status === 'sukses') {
        Swal.fire({
            title: 'Tercatat!',
            text: 'Data belanja sudah masuk ke laporan.',
            icon: 'success',
            confirmButtonColor: '#CA7842'
        });
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    // FUNGSI TAMBAH BARIS (DIPERBAIKI)
    let barisCount = 1;

    function tambahBaris() {
        barisCount++;
        const container = document.getElementById('container-barang');
        const html = `
            <div class="row item-barang mb-3" id="baris_${barisCount}">
                <div class="col-md-7">
                    <label class="label-kecil mb-2">Nama Barang ${barisCount}</label>
                    <input type="text" name="nama_barang[]" class="form-control-neo" placeholder="Nama barang..." required>
                </div>
                <div class="col-md-3">
                    <label class="label-kecil mb-2">Qty</label>
                    <input type="number" name="qty_barang[]" class="form-control-neo" placeholder="0" required>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" onclick="hapusBaris(${barisCount})" class="btn btn-danger rounded-3 w-100" style="padding: 12px; height: 55px; border:none; background: #ff4d4d;">
                        <i data-lucide="trash-2"></i>
                    </button>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
        lucide.createIcons(); // Penting: supaya icon sampah muncul di baris baru
    }

    function hapusBaris(id) {
        const row = document.getElementById(`baris_${id}`);
        if(row) row.remove();
    }

    // Fungsi Cek Nominal (Hanya jalan jika ID inputBiaya ada)
    function cekNominal() {
        const input = document.getElementById('inputBiaya');
        if(!input) return;
        
        const b = input.value;
        if(!b || b == 0) return Swal.fire('Oops', 'Isi nominal harganya dulu ya.', 'warning');
        
        const format = new Intl.NumberFormat('id-ID', { 
            style: 'currency', 
            currency: 'IDR', 
            minimumFractionDigits: 0 
        }).format(b);

        Swal.fire({
            title: 'Cek Nominal',
            html: 'Total: <b style="font-size:1.5rem; color:#CA7842;">' + format + '</b>',
            icon: 'info',
            confirmButtonColor: '#CA7842'
        });
    }
</script>
</body>
</html>