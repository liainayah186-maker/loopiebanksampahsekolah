<?php
session_start();
require '../koneksi.php';

// Cek login user
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['user_id'];

// 1. Ambil Data Poin User Terbaru
$qUser = mysqli_query($koneksi, "SELECT poin, nama_kelas FROM users WHERE id_user = '$id_user'");
$userData = mysqli_fetch_assoc($qUser);
$poinUser = $userData['poin'] ?? 0;

// 2. Ambil Daftar Hadiah yang Stoknya > 0
$qHadiah = mysqli_query($koneksi, "SELECT * FROM rewards WHERE stok > 0 ORDER BY harga_poin ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loopie | Tukar Poin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root { 
            --terra-cotta: #CA7842; --moss-green: #B2CD9C; 
            --dark-oak: #4B352A; --parchment: #F8F9F2; 
        }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--parchment); color: var(--dark-oak); padding-bottom: 100px; }
        
        .header-section {
            background: var(--dark-oak); color: white;
            padding: 40px 20px; border-radius: 0 0 40px 40px;
            margin-bottom: 30px;
        }

        .poin-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            padding: 20px; border-radius: 24px;
            display: flex; align-items: center; justify-content: space-between;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .reward-card {
            background: white; border-radius: 30px; padding: 25px;
            border: 1px solid rgba(0,0,0,0.05); height: 100%;
            display: flex; flex-direction: column; transition: 0.3s;
        }
        .reward-card:hover { transform: translateY(-8px); border-color: var(--terra-cotta); }

        .price-tag {
            background: #FFF9F5; color: var(--terra-cotta);
            padding: 8px 16px; border-radius: 12px;
            font-weight: 800; font-size: 1.1rem; display: inline-flex; align-items: center; gap: 8px;
        }

        .btn-tukar {
            background: var(--dark-oak); color: white; border: none;
            padding: 12px; border-radius: 16px; font-weight: 700;
            width: 100%; transition: 0.3s;
        }
        .btn-tukar:hover:not(:disabled) { background: var(--terra-cotta); }
        .btn-tukar:disabled { background: #e0e0e0; color: #a0a0a0; cursor: not-allowed; }

        .stok-label { font-size: 0.75rem; font-weight: 800; opacity: 0.5; text-transform: uppercase; }
    </style>
</head>
<body>

<div class="header-section">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="user_dashboard.php" class="text-white text-decoration-none">
                <i data-lucide="arrow-left"></i>
            </a>
            <h5 class="m-0 fw-800">Katalog Hadiah</h5>
            <div style="width: 24px;"></div>
        </div>
        
        <div class="poin-card">
            <div>
                <p class="m-0 opacity-75 small fw-700">Poin Kelas <?= $userData['nama_kelas'] ?></p>
                <h2 class="m-0 fw-800"><?= number_format($poinUser) ?> <span class="fs-6 opacity-75">PTS</span></h2>
            </div>
            <div class="bg-white p-3 rounded-circle text-dark">
                <i data-lucide="wallet" size="28" color="var(--terra-cotta)"></i>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="row g-4">
        <?php while($h = mysqli_fetch_assoc($qHadiah)): 
            $bisa_tukar = ($poinUser >= $h['harga_poin']);
        ?>
        <div class="col-6 col-md-4">
            <div class="reward-card">
                <div class="mb-3">
                    <span class="stok-label">Tersedia: <?= $h['stok'] ?></span>
                    <h5 class="fw-800 mt-1 mb-3 text-truncate"><?= $h['nama_hadiah'] ?></h5>
                    <div class="price-tag mb-3">
                        <i data-lucide="zap" size="18" fill="var(--terra-cotta)"></i>
                        <?= number_format($h['harga_poin']) ?>
                    </div>
                </div>

                <div class="mt-auto">
                    <button class="btn-tukar shadow-sm" 
                            data-bs-toggle="modal" 
                            data-bs-target="#modalKonfirmasi"
                            data-id="<?= $h['id_reward'] ?>"
                            data-nama="<?= $h['nama_hadiah'] ?>"
                            data-harga="<?= $h['harga_poin'] ?>"
                            <?= !$bisa_tukar ? 'disabled' : '' ?>>
                        <?= $bisa_tukar ? 'Tukar Sekarang' : 'Poin Kurang' ?>
                    </button>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<div class="modal fade" id="modalKonfirmasi" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered px-4">
        <div class="modal-content border-0" style="border-radius: 30px; overflow: hidden;">
            <div class="modal-body p-4 text-center">
                <div class="p-3 d-inline-block rounded-circle mb-3" style="background: #FFF9F5;">
                    <i data-lucide="gift" size="40" color="var(--terra-cotta)"></i>
                </div>
                <h4 class="fw-800 mb-2">Konfirmasi Penukaran</h4>
                <p class="text-muted">Apakah Anda yakin ingin menukar poin kelas dengan <strong id="txtNamaHadiah" class="text-dark"></strong>?</p>
                
                <div class="bg-light p-3 rounded-4 mb-4">
                    <span class="small fw-700 opacity-50">BIAYA POIN</span>
                    <h3 class="fw-800 m-0 text-danger">- <span id="txtHargaHadiah"></span> PTS</h3>
                </div>

                <form action="proses_tukar.php" method="POST">
                    <input type="hidden" name="id_reward" id="inpIdReward">
                    <div class="d-flex gap-2">
                        <button type="button" class="btn w-50 fw-700 py-3 rounded-4" data-bs-dismiss="modal" style="background: #eee;">Batal</button>
                        <button type="submit" name="tukar" class="btn w-50 fw-800 py-3 rounded-4 text-white" style="background: var(--terra-cotta);">Ya, Tukar!</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    lucide.createIcons();

    // Script untuk memindahkan data hadiah ke modal
    const modalKonfirmasi = document.getElementById('modalKonfirmasi')
    modalKonfirmasi.addEventListener('show.bs.modal', event => {
        const btn = event.relatedTarget
        document.getElementById('txtNamaHadiah').innerText = btn.getAttribute('data-nama')
        document.getElementById('txtHargaHadiah').innerText = btn.getAttribute('data-harga')
        document.getElementById('inpIdReward').value = btn.getAttribute('data-id')
    })
</script>
</body>
</html>