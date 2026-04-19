<?php
session_start();
require '../koneksi.php';

// Proteksi login admin
if (!isset($_SESSION['adminlogin'])) {
    header("Location: loginadmin.php");
    exit;
}

// Filter Tanggal (Default: Awal bulan ini sampai hari ini)
$tgl_mulai = $_GET['tgl_mulai'] ?? date('Y-m-01');
$tgl_selesai = $_GET['tgl_selesai'] ?? date('Y-m-d');

// --- 1. RINGKASAN KAS (Audit Uang) ---
$q_masuk = mysqli_query($koneksi, "SELECT SUM(jumlah_uang) as total FROM kas_masuk WHERE tanggal BETWEEN '$tgl_mulai' AND '$tgl_selesai'");
$uang_masuk = mysqli_fetch_assoc($q_masuk)['total'] ?? 0;

$q_keluar = mysqli_query($koneksi, "SELECT SUM(total_biaya) as total FROM kas_keluar WHERE tanggal BETWEEN '$tgl_mulai' AND '$tgl_selesai'");
$uang_keluar = mysqli_fetch_assoc($q_keluar)['total'] ?? 0;

$saldo_kas = $uang_masuk - $uang_keluar;

// --- 2. RINGKASAN SAMPAH (Audit Berat) ---
$q_sampah = mysqli_query($koneksi, "SELECT SUM(berat) as total FROM setoran_sampah WHERE tanggal BETWEEN '$tgl_mulai' AND '$tgl_selesai'");
$total_berat_masuk = mysqli_fetch_assoc($q_sampah)['total'] ?? 0;

$q_jual = mysqli_query($koneksi, "SELECT SUM(berat_jual) as total FROM kas_masuk WHERE tanggal BETWEEN '$tgl_mulai' AND '$tgl_selesai'");
$total_berat_keluar = mysqli_fetch_assoc($q_jual)['total'] ?? 0;

$stok_gudang = $total_berat_masuk - $total_berat_keluar;

// --- 3. DETAIL PER KATEGORI (Revisi Pengelolaan Sampah) ---
$kategori_rekap = mysqli_query($koneksi, "SELECT kategori, SUM(berat) as total_kg FROM setoran_sampah WHERE tanggal BETWEEN '$tgl_mulai' AND '$tgl_selesai' GROUP BY kategori");

// --- 4. TREND BULANAN (Laporan Per Bulan) ---
$trend_bulanan = mysqli_query($koneksi, "SELECT DATE_FORMAT(tanggal, '%M %Y') as bulan, SUM(berat) as total_berat FROM setoran_sampah GROUP BY MONTH(tanggal), YEAR(tanggal) ORDER BY tanggal DESC LIMIT 6");

// --- TAMBAHAN LOGIKA BARU ---
$laba_bersih = $uang_masuk - $uang_keluar;
$status_laba = ($laba_bersih >= 0) ? 'text-success' : 'text-danger';

$labels = [];
$data_berat = [];
$trend_chart = mysqli_query($koneksi, "SELECT DATE_FORMAT(tanggal, '%M') as bulan, SUM(berat) as total FROM setoran_sampah GROUP BY MONTH(tanggal) ORDER BY tanggal ASC LIMIT 6");
while($row = mysqli_fetch_assoc($trend_chart)){
    $labels[] = $row['bulan'];
    $data_berat[] = $row['total'];
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
    <title>Loopie Admin | Laporan Akuntabilitas</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root { 
            --terra-cotta: #CA7842; --moss-green: #B2CD9C; 
            --dark-oak: #4B352A; --parchment: #F0F2BD; 
            --white: #ffffff; --soft-shadow: 0 10px 30px rgba(75, 53, 42, 0.05);
        }

        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: rgba(75, 53, 42, 0.05); }
        ::-webkit-scrollbar-thumb { background: var(--terra-cotta); border-radius: 10px; }

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

        .sidebar-menu { flex-grow: 1; overflow-y: auto; padding: 0 20px; }
        .nav-link-adm { 
            color: rgba(240, 242, 189, 0.6); text-decoration: none; 
            display: flex; align-items: center; gap: 15px; padding: 14px 20px; 
            margin-bottom: 8px; font-weight: 700; border-radius: 16px; transition: 0.3s; 
        }
        .nav-link-adm:hover { color: var(--parchment); background: rgba(255,255,255,0.05); }
        .nav-link-adm.active { color: white; background: var(--terra-cotta); box-shadow: 0 4px 15px rgba(202, 120, 66, 0.3); }

        .sidebar-footer { padding: 20px; border-top: 1px solid rgba(255,255,255,0.05); }
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


        .content { 
            margin-left: 280px; width: calc(100% - 280px); 
            padding: 60px; min-height: 100vh;
        }

        .info-tag {
            background: rgba(202, 120, 66, 0.1); color: var(--terra-cotta);
            padding: 6px 16px; border-radius: 10px; font-size: 0.7rem; font-weight: 800;
            display: inline-block; margin-bottom: 10px; text-transform: uppercase;
        }

        .audit-card {
            background: var(--white); border-radius: 25px; padding: 20px;
            box-shadow: var(--soft-shadow); border: none; height: 100%;
            transition: 0.3s;
        }
        .stat-label { font-size: 0.65rem; font-weight: 800; color: #aaa; letter-spacing: 1px; text-transform: uppercase; }

        .card-custom { 
            background: var(--white); border-radius: 35px; padding: 35px; 
            box-shadow: var(--soft-shadow); border: none; 
        }
        .nav-pills-neo { background: #f8f9f2; padding: 8px; border-radius: 20px; display: inline-flex; margin-bottom: 25px; }
        .nav-pills-neo .nav-link { 
            border-radius: 15px; color: var(--dark-oak); font-weight: 700; 
            padding: 10px 25px; transition: 0.3s; border: none;
        }
        .nav-pills-neo .nav-link.active { background: white; color: var(--terra-cotta); box-shadow: 0 5px 15px rgba(0,0,0,0.05); }

        /* TABLE IMPROVEMENT */
        .table-modern { border-collapse: separate; border-spacing: 0 8px; }
        .table-modern thead th { 
            background: transparent; color: var(--dark-oak); font-weight: 800; 
            text-transform: uppercase; font-size: 0.65rem; letter-spacing: 1.2px; 
            padding: 15px 20px; border: none; opacity: 0.5;
        }
        .table-modern tbody tr { background: #FDFDFD; transition: 0.2s; }
        .table-modern tbody tr:hover { transform: scale(1.01); box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
        .table-modern tbody td { 
            padding: 18px 20px; vertical-align: middle; 
            border: none; font-weight: 700; color: var(--dark-oak);
        }
        .table-modern tbody td:first-child { border-radius: 15px 0 0 15px; }
        .table-modern tbody td:last-child { border-radius: 0 15px 15px 0; }

        .filter-box {
            background: white; padding: 15px 25px; border-radius: 20px;
            display: flex; align-items: center; gap: 20px; box-shadow: var(--soft-shadow);
        }
        .filter-input { border: none; background: #f8f9f2; padding: 8px 15px; border-radius: 10px; font-weight: 700; font-size: 0.85rem; }

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
            <a href="petugas_kas_masuk.php" class="nav-link-adm"><i data-lucide="trending-up" size="18"></i> <span>Kas Masuk</span></a>
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
    <span>  Hadiah</span>
    
    <?php if($jumlah_stok_low > 0): ?>
        <span class="badge-stok-warning"><?= $jumlah_stok_low ?></span>
    <?php endif; ?>
</a>
            <a href="petugas_broadcast.php" class="nav-link-adm"><i data-lucide="megaphone" size="18"></i> <span>Pusat Pesan & Antrean</span></a>
            <a href="petugas_laporan.php" class="nav-link-adm active"><i data-lucide="file-text" size="18"></i> <span>Laporan</span></a>
        </nav>
    </div>

    <div class="sidebar-footer">
        <a href="logout_admin.php" class="nav-link-adm" style="background: rgba(202, 120, 66, 0.1); color: var(--terra-cotta);">
            <i data-lucide="log-out"></i> <span>KELUAR</span>
        </a>
    </div>
</div>

<div class="content">
    <div class="d-flex justify-content-between align-items-start mb-5">
        <div>
            <span class="info-tag">Audit Komprehensif</span>
            <h1 class="fw-800 m-0 display-5 mt-2">Laporan <span style="color: var(--terra-cotta)">Audit.</span></h1>
            <p class="text-muted m-0">Rekapitulasi transaksi kas, stok sampah per kategori, dan tren bulanan.</p>
        </div>
        <div class="d-flex gap-2">
            <button onclick="exportExcel()" class="btn btn-success fw-800 rounded-pill px-4" style="background: #2D5A27; border:none;">
                <i data-lucide="file-spreadsheet" class="me-1" size="18"></i> EXCEL
            </button>
            <button onclick="window.print()" class="btn btn-dark fw-800 rounded-pill px-4">
                <i data-lucide="printer" class="me-1" size="18"></i> CETAK
            </button>
        </div>
    </div>

    <div class="filter-box mb-5">
        <form method="GET" class="d-flex align-items-center gap-3 w-100">
            <div class="d-flex align-items-center gap-2">
                <i data-lucide="calendar" size="16" class="text-muted"></i>
                <input type="date" name="tgl_mulai" class="filter-input" value="<?= $tgl_mulai ?>">
            </div>
            <span class="fw-800 opacity-25">SAMPAI</span>
            <input type="date" name="tgl_selesai" class="filter-input" value="<?= $tgl_selesai ?>">
            <button type="submit" class="btn btn-dark rounded-pill px-4 fw-800">TAMPILKAN</button>
            <a href="petugas_laporan.php" class="btn btn-outline-secondary rounded-pill px-4 fw-800">RESET</a>
        </form>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="audit-card">
                <p class="stat-label mb-1">Kas Masuk</p>
                <h4 class="fw-800 m-0 text-success">Rp <?= number_format($uang_masuk, 0, ',', '.') ?></h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="audit-card">
                <p class="stat-label mb-1">Kas Keluar</p>
                <h4 class="fw-800 m-0 text-danger">Rp <?= number_format($uang_keluar, 0, ',', '.') ?></h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="audit-card bg-dark">
                <p class="stat-label mb-1 text-white-50">Sisa Kas (Laba/Rugi)</p>
                <h4 class="fw-800 m-0 <?= $status_laba ?>">Rp <?= number_format($laba_bersih, 0, ',', '.') ?></h4>
                <small class="text-white-50" style="font-size: 0.6rem;">Total Masuk - Keluar</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="audit-card" style="background: var(--terra-cotta);">
                <p class="stat-label mb-1 text-white-50">Stok Gudang</p>
                <h4 class="fw-800 m-0 text-white"><?= number_format($stok_gudang, 1) ?> KG</h4>
            </div>
        </div>
    </div>

    <div class="card-custom">
        <div class="nav nav-pills nav-pills-neo mb-4" id="pills-tab">
            <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-sampah">Pengelolaan Sampah</button>
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-bulanan">Laporan Bulanan</button>
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-kas">Arus Kas Detail</button>
        </div>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="tab-sampah">
                <div class="row g-5">
                    <div class="col-md-5">
                        <h6 class="fw-800 mb-4 text-uppercase opacity-50" style="font-size: 0.75rem;">Stok Per Kategori</h6>
                        <table class="table table-modern">
                            <tbody>
                                <?php while($rk = mysqli_fetch_assoc($kategori_rekap)): ?>
                                <tr>
                                    <td><span class="badge bg-light text-dark px-3 py-2 rounded-pill"><?= $rk['kategori'] ?></span></td>
                                    <td class="text-end text-primary fw-800"><?= number_format($rk['total_kg'], 1) ?> KG</td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="col-md-7 border-start ps-md-5">
                        <h6 class="fw-800 mb-4 text-uppercase opacity-50" style="font-size: 0.75rem;">History Setoran Terakhir</h6>
                        <div style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-modern" id="table-setoran">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Kelas</th>
                                        <th class="text-end">Berat</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $q_det = mysqli_query($koneksi, "SELECT * FROM setoran_sampah WHERE tanggal BETWEEN '$tgl_mulai' AND '$tgl_selesai' ORDER BY tanggal DESC");
                                    while($row = mysqli_fetch_assoc($q_det)): ?>
                                    <tr>
                                        <td class="text-muted small"><?= date('d M Y', strtotime($row['tanggal'])) ?></td>
                                        <td><?= $row['kelas'] ?></td>
                                        <td class="text-end text-primary"><?= number_format($row['berat'], 1) ?> KG</td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-bulanan">
                <h6 class="fw-800 mb-4 text-uppercase opacity-50" style="font-size: 0.75rem;">Tren Setoran 6 Bulan Terakhir</h6>
                <div class="mb-5 p-4 border rounded-4 bg-light">
                    <canvas id="trendChart" style="max-height: 280px;"></canvas>
                </div>
                <table class="table table-modern">
                    <thead><tr><th>Periode</th><th class="text-end">Total Sampah</th></tr></thead>
                    <tbody>
                        <?php while($tb = mysqli_fetch_assoc($trend_bulanan)): ?>
                        <tr>
                            <td><i data-lucide="calendar" size="14" class="me-2 text-muted"></i><?= $tb['bulan'] ?></td>
                            <td class="text-end fw-800 text-success"><?= number_format($tb['total_berat'], 1) ?> KG</td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div class="tab-pane fade" id="tab-kas">
                <div class="row g-5">
                    <div class="col-md-6">
                        <h6 class="fw-800 mb-4 text-success text-uppercase" style="font-size: 0.75rem;">Detail Pemasukan</h6>
                        <table class="table table-modern" id="table-masuk">
                            <tbody>
                                <?php 
                                $q_m = mysqli_query($koneksi, "SELECT * FROM kas_masuk WHERE tanggal BETWEEN '$tgl_mulai' AND '$tgl_selesai'");
                                while($rm = mysqli_fetch_assoc($q_m)): ?>
                                <tr>
                                    <td><?= $rm['sumber'] ?></td>
                                    <td class="text-end text-success fw-bold">Rp <?= number_format($rm['jumlah_uang'], 0, ',', '.') ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="col-md-6 border-start ps-md-5">
                        <h6 class="fw-800 mb-4 text-danger text-uppercase" style="font-size: 0.75rem;">Detail Pengeluaran</h6>
                        <table class="table table-modern" id="table-keluar">
                            <tbody>
                                <?php 
                                $q_k = mysqli_query($koneksi, "SELECT * FROM kas_keluar WHERE tanggal BETWEEN '$tgl_mulai' AND '$tgl_selesai'");
                                while($rk = mysqli_fetch_assoc($q_k)): ?>
                                <tr>
                                    <td><?= $rk['nama_barang'] ?></td>
                                    <td class="text-end text-danger fw-bold">Rp <?= number_format($rk['total_biaya'], 0, ',', '.') ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    lucide.createIcons();

    const ctx = document.getElementById('trendChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($labels) ?>,
            datasets: [{
                label: 'Sampah (KG)',
                data: <?= json_encode($data_berat) ?>,
                borderColor: '#CA7842',
                backgroundColor: 'rgba(202, 120, 66, 0.1)',
                fill: true,
                tension: 0.4,
                borderWidth: 3,
                pointRadius: 4,
                pointBackgroundColor: '#4B352A'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { grid: { display: false }, beginAtZero: true },
                x: { grid: { display: false } }
            }
        }
    });

    function exportExcel() {
        let wb = XLSX.utils.book_new();
        let data_summary = [
            ["LAPORAN AUDIT LOOPIE"],
            ["Periode:", "<?= $tgl_mulai ?> s/d <?= $tgl_selesai ?>"],
            [],
            ["KETERANGAN", "NOMINAL / BERAT"],
            ["Total Kas Masuk", "Rp <?= number_format($uang_masuk, 0, ',', '.') ?>"],
            ["Total Kas Keluar", "Rp <?= number_format($uang_keluar, 0, ',', '.') ?>"],
            ["Saldo Akhir", "Rp <?= number_format($laba_bersih, 0, ',', '.') ?>"],
            ["Total Stok Sampah", "<?= $stok_gudang ?> KG"]
        ];
        let ws1 = XLSX.utils.aoa_to_sheet(data_summary);
        XLSX.utils.book_append_sheet(wb, ws1, "RINGKASAN");
        let ws2 = XLSX.utils.table_to_sheet(document.getElementById('table-setoran'));
        XLSX.utils.book_append_sheet(wb, ws2, "DETAIL_SETORAN");
        let ws3 = XLSX.utils.table_to_sheet(document.getElementById('table-masuk'));
        XLSX.utils.book_append_sheet(wb, ws3, "PEMASUKAN");
        let ws4 = XLSX.utils.table_to_sheet(document.getElementById('table-keluar'));
        XLSX.utils.book_append_sheet(wb, ws4, "PENGELUARAN");
        XLSX.writeFile(wb, "Laporan_Audit_Loopie_<?= date('Ymd') ?>.xlsx");
    }
</script>
</body>
</html>