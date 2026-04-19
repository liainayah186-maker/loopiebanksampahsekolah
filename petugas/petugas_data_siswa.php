<?php
session_start();
require '../koneksi.php'; 

// Proteksi Admin - Memastikan hanya admin yang bisa akses
if (!isset($_SESSION['adminlogin'])) {
    header("Location: loginadmin.php");
    exit;
}

// Ambil data dari tabel users
$data = mysqli_query($koneksi, "SELECT * FROM users ORDER BY nama_kelas ASC");

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
    <title>Loopie Admin | Data Siswa</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    
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
            margin: 0; display: flex; min-height: 100vh;
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
        }

        .nav-link-adm { 
            color: rgba(240, 242, 189, 0.6); text-decoration: none; 
            display: flex; align-items: center; gap: 15px; padding: 14px 20px; 
            margin-bottom: 8px; font-weight: 700; border-radius: 16px; transition: 0.3s; 
            position: relative;
        }
        .nav-link-adm:hover { color: white; background: rgba(255,255,255,0.05); }
        .nav-link-adm.active { color: white; background: var(--terra-cotta); box-shadow: 0 4px 15px rgba(202, 120, 66, 0.3); }

        .nav-link-adm::after {
            content: ''; position: absolute; bottom: 10px; left: 50%;
            width: 0; height: 2px; background-color: var(--terra-cotta);
            transition: 0.3s ease; transform: translateX(-50%);
        }
        .nav-link-adm:hover::after { width: 40%; }
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

        .sidebar-footer { padding: 20px; border-top: 1px solid rgba(255,255,255,0.05); }

        /* --- CONTENT AREA --- */
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

        /* --- TABLE STYLING --- */
        .table thead th { 
            background: transparent; color: var(--dark-oak); font-weight: 800; 
            text-transform: uppercase; font-size: 0.7rem; letter-spacing: 1.2px; 
            padding: 20px; border-bottom: 2px solid #F8F9F2; opacity: 0.5;
        }
        .table tbody td { padding: 25px 20px; vertical-align: middle; border-bottom: 1px solid #F8F9F2; }

        .badge-poin { 
            background: var(--moss-green); color: var(--dark-oak); 
            padding: 10px 18px; border-radius: 14px; font-size: 0.85rem; 
            font-weight: 800; display: inline-flex; align-items: center; gap: 6px;
        }

        .btn-submit {
            background: var(--dark-oak); color: white; border: none;
            padding: 14px 25px; border-radius: 18px; font-weight: 700;
            transition: 0.3s;
        }
        .btn-submit:hover { background: var(--terra-cotta); transform: translateY(-2px); }

        @media (max-width: 992px) {
            .sidebar { width: 80px; }
            .sidebar-brand, .nav-link-adm span { display: none; }
            .content { margin-left: 80px; width: calc(100% - 80px); padding: 30px; }
        }
        /* --- KONSISTENSI RESPONSIF (SAMA DENGAN DASHBOARD) --- */

/* --- KODE BIAR SIDEBAR JADI ICON ONLY --- */
@media (max-width: 992px) {
    /* 1. Kecilkan lebar sidebar */
    .sidebar { 
        width: 80px; 
    }

    /* 2. Sembunyikan Brand, Teks Menu, Teks Keluar, dan Badge */
    .sidebar-brand, 
    .nav-link-adm span, 
    .sidebar-footer span, 
    .badge-sidebar { 
        display: none !important; 
    }

    /* 3. Ratakan icon ke tengah */
    .nav-link-adm { 
        justify-content: center; 
        padding: 15px; 
        margin: 0 10px 8px 10px;
    }

    /* 4. Geser area konten agar tidak tertutup sidebar */
    .content { 
        margin-left: 80px; 
        width: calc(100% - 80px); 
        padding: 30px; 
    }
}

/* --- TAMBAHAN UNTUK HP (BIAR TABEL GAK PECAH) --- */
@media (max-width: 768px) {
    .content {
        padding: 20px;
    }
    
    /* Tombol 'Tambah Akun' jadi lebar penuh di HP biar gampang diklik */
    .btn-submit {
        width: 100%;
        margin-top: 15px;
    }

    .col-md-6.text-end {
        text-align: left !important;
    }
}

@media (max-width: 768px) {
    .content {
        padding: 20px;
    }

    /* Bagian Header (Judul & Tombol) */
    .mb-5 .row {
        flex-direction: column;
        gap: 20px;
    }
    .col-md-6.text-end {
        text-align: left !important;
        justify-content: flex-start !important;
    }
    .btn-submit {
        width: 100%; /* Tombol jadi full width di HP biar mudah diklik */
    }

    /* Tabel agar tidak overflow (bisa digeser kanan-kiri) */
    .card-custom {
        padding: 20px;
        border-radius: 25px;
    }
    .table-responsive {
        border-radius: 15px;
    }
}

@media (max-width: 480px) {
    .display-5 {
        font-size: 2.2rem !important;
    }
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
            <a href="petugas_data_siswa.php" class="nav-link-adm active"><i data-lucide="users" size="18"></i> <span>Data Siswa</span></a>
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
    <div class="mb-5">
        <span class="info-tag">TINJAUAN DATABASE</span>
        <h1 class="fw-800 m-0 display-5 mt-2">Data <span style="color: var(--terra-cotta)">Kelas.</span></h1>
        
        <div class="row g-4 mb-4 mt-2">
            <div class="col-md-3">
                <div class="card-custom p-3 d-flex align-items-center gap-3">
                    <div class="icon-circle bg-primary text-white p-2 rounded-3" style="--bs-bg-opacity: .1; color: #0d6efd !important;"><i data-lucide="users"></i></div>
                    <div>
                        <small class="text-muted fw-bold">TOTAL KELAS</small>
                        <h4 class="fw-800 m-0"><?= mysqli_num_rows($data) ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <?php 
                $total_poin = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT SUM(total_poin) as total FROM users"));
                ?>
                <div class="card-custom p-3 d-flex align-items-center gap-3">
                    <div class="icon-circle bg-success text-white p-2 rounded-3" style="--bs-bg-opacity: .1; color: #198754 !important;"><i data-lucide="star"></i></div>
                    <div>
                        <small class="text-muted fw-bold">SALDO POIN</small>
                        <h4 class="fw-800 m-0"><?= number_format($total_poin['total'] ?? 0) ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-6 text-end d-flex align-items-center justify-content-end">
                <button class="btn-submit px-4" onclick="bukaModalTambah()">
                    <i data-lucide="plus-circle" class="me-2" style="width:18px"></i> TAMBAH AKUN KELAS
                </button>
            </div>
        </div>
        <p class="text-muted m-0">Memantau seluruh akun kelas dan perolehan poin bank sampah hari ini.</p>
    </div>

    <div class="card-custom">
        <div class="table-responsive">
            <table class="table table-borderless align-middle m-0">
                <thead>
                    <tr>
                        <th>Kelas & Wali</th>
                        <th>Username</th>
                        <th>Total Poin</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
               <tbody>
    <?php while($row = mysqli_fetch_assoc($data)) { ?>
    <tr>
        <td>
            <div class="fw-800 text-uppercase" style="font-size: 1.1rem; color: var(--dark-oak);"><?= htmlspecialchars($row['nama_kelas']) ?></div>
            <div style="font-size: 0.8rem; font-weight: 700; opacity: 0.5;">Wali: <?= htmlspecialchars($row['wali_kelas']) ?></div>
        </td>
        <td>
            <span style="color: var(--terra-cotta); font-family: monospace; font-size: 1rem; font-weight: 800;">@<?= htmlspecialchars($row['username']) ?></span>
        </td>
        <td>
            <span class="badge-poin">
                <i data-lucide="star" size="14"></i>
                <?= number_format($row['total_poin']) ?> <small style="font-size: 0.6rem; opacity: 0.7; margin-left: 3px;">POIN</small>
            </span>
        </td>
        <td class="text-center">
            <div class="d-flex justify-content-center gap-2">
                <button class="btn btn-sm btn-light rounded-pill p-2" 
                        onclick="editSiswa('<?= $row['id_user'] ?>', '<?= addslashes($row['nama_kelas']) ?>', '<?= addslashes($row['wali_kelas']) ?>', '<?= addslashes($row['username']) ?>')">
                    <i data-lucide="edit-3" size="18" class="text-warning"></i>
                </button>
                
                <button class="btn btn-sm btn-light rounded-pill p-2" 
                        onclick="hapusSiswa('<?= $row['id_user'] ?>', '<?= addslashes($row['nama_kelas']) ?>')">
                    <i data-lucide="trash-2" size="18" class="text-danger"></i>
                </button>
            </div>
        </td>
    </tr>
    <?php } ?>
</tbody>
            </table>
        </div>
    </div>
</div>
<script src="https://unpkg.com/lucide@latest"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    lucide.createIcons();

    
    // 1. FUNGSI TAMBAH AKUN
    function bukaModalTambah() {
        Swal.fire({
            title: '<h3 class="fw-800">Tambah Akun Kelas</h3>',
            html: `
                <input id="swal-nama" class="swal2-input" placeholder="Nama Kelas (ex: XII RPL 1)" style="border-radius: 15px;">
                <input id="swal-wali" class="swal2-input" placeholder="Nama Wali Kelas" style="border-radius: 15px;">
                <input id="swal-user" class="swal2-input" placeholder="Username" style="border-radius: 15px;">
                <input id="swal-pass" type="password" class="swal2-input" placeholder="Password" style="border-radius: 15px;">
            `,
            showCancelButton: true,
            confirmButtonText: 'Simpan Akun',
            confirmButtonColor: '#CA7842',
            cancelButtonColor: '#4B352A',
            preConfirm: () => {
                const nama = document.getElementById('swal-nama').value;
                const wali = document.getElementById('swal-wali').value;
                const user = document.getElementById('swal-user').value;
                const pass = document.getElementById('swal-pass').value;
                if (!nama || !wali || !user || !pass) {
                    Swal.showValidationMessage(`Tolong isi semua bidang!`)
                }
                return { nama, wali, user, pass }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                postData('tambah', result.value);
            }
        });
    }

    // 2. FUNGSI EDIT DATA
    function editSiswa(id_user, nama_kelas, wali_kelas, username) {
        Swal.fire({
            title: '<h3 class="fw-800">Edit Data Kelas</h3>',
            html: `
                <input id="swal-nama" class="swal2-input" value="${nama_kelas}" style="border-radius: 15px;">
                <input id="swal-wali" class="swal2-input" value="${wali_kelas}" style="border-radius: 15px;">
                <input id="swal-user" class="swal2-input" value="${username}" style="border-radius: 15px;">
                <input id="swal-pass" type="password" class="swal2-input" placeholder="Kosongkan jika tidak ganti password" style="border-radius: 15px;">
            `,
            showCancelButton: true,
            confirmButtonText: 'Update Data',
            confirmButtonColor: '#CA7842',
            cancelButtonColor: '#4B352A',
            preConfirm: () => {
                return { 
                    id: id_user, // Mengirim id_user sebagai 'id' ke PHP
                    nama: document.getElementById('swal-nama').value,
                    wali: document.getElementById('swal-wali').value,
                    user: document.getElementById('swal-user').value,
                    pass: document.getElementById('swal-pass').value
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                postData('edit', result.value);
            }
        });
    }

    // 3. FUNGSI RESET PASSWORD (Khusus Admin)
    function resetPassword(id_user, nama) {
        Swal.fire({
            title: 'Reset Password',
            text: `Masukkan password baru untuk kelas ${nama}`,
            input: 'password',
            inputAttributes: {
                autocapitalize: 'off',
                autocorrect: 'off'
            },
            showCancelButton: true,
            confirmButtonText: 'Reset Sekarang',
            confirmButtonColor: '#CA7842',
            cancelButtonColor: '#4B352A',
        }).then((result) => {
            if (result.isConfirmed) {
                if (result.value.length < 6) {
                    Swal.fire('Gagal', 'Password minimal 6 karakter!', 'error');
                } else {
                    postData('reset_pass', { id: id_user, pass: result.value });
                }
            }
        });
    }

    // 4. FUNGSI HAPUS AKUN
    function hapusSiswa(id_user, nama_kelas) {
        Swal.fire({
            title: 'Hapus Akun?',
            text: "Seluruh poin kelas " + nama_kelas + " akan terhapus permanen!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#4B352A',
            confirmButtonText: 'Ya, Hapus Akun'
        }).then((result) => {
            if (result.isConfirmed) {
                // Langsung redirect ke file proses dengan parameter hapus
                window.location.href = "proses_kelas.php?hapus=" + id_user;
            }
        });
    }

    // 5. KURIR DATA (POST FORM)
    function postData(tipe, data) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'proses_kelas.php';

        const fields = { ...data, aksi: tipe };
        for (const key in fields) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = fields[key];
            form.appendChild(input);
        }
        document.body.appendChild(form);
        form.submit();
    }

    // 6. ALERT STATUS (SweetAlert otomatis dari URL)
    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status');
    if (status === 'sukses') {
        Swal.fire('Berhasil!', 'Data telah diperbarui.', 'success');
    } else if (status === 'hapus') {
        Swal.fire('Terhapus!', 'Data kelas telah dihapus.', 'success');
    } else if (status === 'duplikat') {
        Swal.fire('Gagal!', 'Username sudah digunakan.', 'error');
    }

</script>
</body>
</html>