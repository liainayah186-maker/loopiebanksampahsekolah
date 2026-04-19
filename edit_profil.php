<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['id_user'])) { 
    header("Location: login.php"); 
    exit(); 
}
$id_user = $_SESSION['id_user'];

// Ambil data lama
$query = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user = '$id_user'");
$data = mysqli_fetch_assoc($query);

// Proses Update
if (isset($_POST['update'])) {
    $nama_baru = mysqli_real_escape_string($koneksi, $_POST['nama_kelas']);
    $wali_baru = mysqli_real_escape_string($koneksi, $_POST['wali_kelas']);
    $email_baru = mysqli_real_escape_string($koneksi, $_POST['email']);
    $foto_nama = $data['avatar']; 
    $status_hapus = $_POST['status_hapus_foto']; // Cek jika user klik hapus foto

    // 1. Logika Hapus Foto
    if ($status_hapus == "1") {
        if (!empty($data['avatar']) && file_exists("uploads/profil/" . $data['avatar'])) {
            unlink("uploads/profil/" . $data['avatar']); 
        }
        $foto_nama = ""; // Set kosong di database
    }

    // 2. Logika Upload Foto Baru (Jika ada file yang dipilih)
    if ($_FILES['avatar']['name'] != "") {
        $ekstensi = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
        $foto_nama = "avatar_" . $id_user . "_" . time() . "." . $ekstensi;
        $target = "uploads/profil/" . $foto_nama;

        if (!is_dir("uploads/profil/")) { 
            mkdir("uploads/profil/", 0777, true); 
        }
        
        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target)) {
            // Hapus foto lama agar storage tetap hemat
            if (!empty($data['avatar']) && file_exists("uploads/profil/" . $data['avatar'])) {
                unlink("uploads/profil/" . $data['avatar']); 
            }
        }
    }

    $update = mysqli_query($koneksi, "UPDATE users SET 
        nama_kelas = '$nama_baru', 
        wali_kelas = '$wali_baru', 
        email = '$email_baru', 
        avatar = '$foto_nama' 
        WHERE id_user = '$id_user'");

    if ($update) {
        echo "<script>alert('Profil berhasil diperbarui!'); window.location.href='profil.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil — Loopie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root { 
            --dark-oak: #4B352A;
            --terra-cotta: #CA7842;
            --moss-green: #B2CD9C; 
            --cream-sand: #F0F2BD;
        }

        body { 
            font-family: 'Inter', sans-serif; 
            background: linear-gradient(135deg, var(--dark-oak) 0%, #2D1E17 100%);
            display: flex; align-items: center; justify-content: center; 
            min-height: 100vh; margin: 0; padding: 20px;
        }

        .edit-card { 
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 40px; border-radius: 40px; 
            width: 100%; max-width: 450px; 
            box-shadow: 0 25px 50px rgba(0,0,0,0.3);
        }

        h2 { font-family: 'Poppins', sans-serif; font-weight: 800; color: var(--dark-oak); text-align: center; margin-bottom: 30px; }

        .avatar-preview-wrapper {
            position: relative; width: 110px; height: 110px; margin: 0 auto 30px;
        }

        #preview {
            width: 100%; height: 100%; border-radius: 40px; 
            object-fit: cover; background: var(--cream-sand); border: 3px solid var(--moss-green);
        }

        .upload-btn-wrapper {
            position: absolute; bottom: -5px; right: -5px;
            background: var(--terra-cotta); color: white;
            width: 36px; height: 36px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; border: 3px solid white; transition: 0.3s;
        }

        .upload-btn-wrapper:hover { transform: scale(1.1); background: var(--dark-oak); }

        label { font-weight: 700; font-size: 0.75rem; color: var(--dark-oak); opacity: 0.6; text-transform: uppercase; letter-spacing: 1px; margin-left: 5px; }

        input[type="text"], input[type="email"] { 
            width: 100%; padding: 14px 20px; margin: 8px 0 20px 0; 
            border: 2px solid #F0F2BD; border-radius: 18px; 
            background: #F9FAEF; transition: all 0.3s ease;
        }

        input:focus { outline: none; border-color: var(--terra-cotta); background: white; }

        .btn-save { 
            width: 100%; background: var(--dark-oak); color: white; 
            border: none; padding: 16px; border-radius: 18px; 
            font-weight: 700; cursor: pointer; transition: 0.3s;
        }

        /* Modal Styling */
        .modal-content { border-radius: 30px; border: none; }
        .btn-option { width: 100%; padding: 15px; border: none; background: none; font-weight: 600; color: var(--dark-oak); transition: 0.2s; }
        .btn-option:hover { background: #f8f9fa; }
        .btn-danger-soft { color: #E74C3C; }
    </style>
</head>
<body>

    <div class="edit-card">
        <h2>Edit Profil Kelas</h2>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="avatar-preview-wrapper">
                <img id="preview" src="<?= !empty($data['avatar']) ? 'uploads/profil/'.$data['avatar'] : 'https://ui-avatars.com/api/?name='.urlencode($data['nama_kelas']).'&background=B2CD9C&color=4B352A' ?>" alt="Avatar">
                
                <div class="upload-btn-wrapper" data-bs-toggle="modal" data-bs-target="#photoModal">
                    <i data-lucide="camera" size="20"></i>
                </div>

                <input type="file" id="avatar-input" name="avatar" accept="image/*" style="display: none;" onchange="previewImage(event)">
                <input type="hidden" name="status_hapus_foto" id="status_hapus_foto" value="0">
            </div>

            <div class="form-group">
                <label>Nama Kelas</label>
                <input type="text" name="nama_kelas" value="<?= $data['nama_kelas'] ?>" required>
            </div>
            
            <div class="form-group">
                <label>Wali Kelas</label>
                <input type="text" name="wali_kelas" value="<?= $data['wali_kelas'] ?>" required>
            </div>

            <div class="form-group">
                <label>Email Terdaftar</label>
                <input type="email" name="email" value="<?= $data['email'] ?>" required>
            </div>
            
            <div style="display: flex; gap: 10px; margin-top: 10px;">
                <a href="profil.php" style="flex: 1; text-align: center; padding: 16px; background: #eee; border-radius: 18px; text-decoration: none; color: var(--dark-oak); font-weight: 700;">Batal</a>
                <button type="submit" name="update" class="btn-save" style="flex: 2;">Simpan</button>
            </div>
        </form>
    </div>

    <div class="modal fade" id="photoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content shadow">
                <div class="modal-body p-0">
                    <div class="text-center p-3 border-bottom">
                        <h6 class="m-0 fw-bold">Foto Profil</h6>
                    </div>
                    <button type="button" class="btn-option border-bottom" onclick="chooseFile()">
                        <i data-lucide="image" size="18" class="me-2"></i> Pilih Foto Baru
                    </button>
                    <button type="button" class="btn-option btn-danger-soft border-bottom" onclick="removePhoto()">
                        <i data-lucide="trash-2" size="18" class="me-2"></i> Hapus Foto Saat Ini
                    </button>
                    <button type="button" class="btn-option" data-bs-dismiss="modal" style="opacity: 0.5;">Batal</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        lucide.createIcons();

        const fileInput = document.getElementById('avatar-input');
        const previewImg = document.getElementById('preview');
        const statusHapus = document.getElementById('status_hapus_foto');
        const photoModal = new bootstrap.Modal(document.getElementById('photoModal'));

        function chooseFile() {
            fileInput.click();
            photoModal.hide();
        }

        function previewImage(event) {
            const reader = new FileReader();
            reader.onload = function() {
                previewImg.src = reader.result;
                statusHapus.value = "0"; // Reset status hapus jika user pilih file baru
            }
            reader.readAsDataURL(event.target.files[0]);
        }

        function removePhoto() {
            const namaKelas = "<?= urlencode($data['nama_kelas']) ?>";
            // Kembalikan ke UI Avatars (Inisial)
            previewImg.src = `https://ui-avatars.com/api/?name=${namaKelas}&background=B2CD9C&color=4B352A`;
            
            statusHapus.value = "1"; // Tandai untuk dihapus di database
            fileInput.value = "";    // Kosongkan input file jika ada
            photoModal.hide();
        }
    </script>
</body>
</html>