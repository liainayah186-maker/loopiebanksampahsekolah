<?php
session_start();
require 'koneksi.php';

// Matikan display error agar tidak merusak JSON saat upload
error_reporting(0);

// Helper untuk menampilkan SweetAlert (PHP Side)
function s_alert($title, $text, $icon, $btnColor, $location) {
    echo "
    <!DOCTYPE html>
    <html>
    <head>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head>
    <body>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: '$title',
                    text: '$text',
                    icon: '$icon',
                    background: '#F0F2BD',
                    color: '#4B352A',
                    confirmButtonColor: '$btnColor',
                    confirmButtonText: 'Oke'
                }).then(() => { window.location='$location'; });
            });
        </script>
    </body>
    </html>";
}

if (!isset($_SESSION['id_user'])) {
    if (isset($_GET['aksi']) && $_GET['aksi'] == 'upload') {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Sesi habis, silakan login ulang.']);
    } else {
        header("Location: login.php");
    }
    exit;
}

$id_u = $_SESSION['id_user'];
$aksi = $_GET['aksi'] ?? '';

// --- 1. PROSES AMBIL MISI ---
if ($aksi == 'ambil') {
    $id_m = $_GET['id'];

    // Cek apakah user punya misi aktif
    $cek = mysqli_query($koneksi, "SELECT * FROM partisipasi_misi 
                                   WHERE id_user = '$id_u' 
                                   AND status_misi = 'berjalan'");
    
    if (mysqli_num_rows($cek) == 0) {
        $query = mysqli_query($koneksi, "INSERT INTO partisipasi_misi (id_user, id_misi, status_misi, jml_cap_sekarang) 
                                       VALUES ('$id_u', '$id_m', 'berjalan', 0)");
        
        if ($query) {
            // Sukses - Pake Moss Green
            s_alert('Misi Dimulai!', 'Semangat ya, kerjakan misinya dengan jujur!', 'success', '#B2CD9C', 'misi.php');
        } else {
            // Error DB - Pake Terra Cotta
            s_alert('Waduh!', 'Gagal mengambil misi, coba lagi nanti.', 'error', '#CA7842', 'misi.php');
        }
    } else {
        // Warning - Pake Terra Cotta
        s_alert('Selesaikan Dulu!', 'Kamu masih punya misi aktif yang belum selesai.', 'warning', '#CA7842', 'misi.php');
    }
    exit;
}

// --- 2. PROSES UPLOAD BUKTI (DENGAN JSON) ---
if ($aksi == 'upload') {
    header('Content-Type: application/json');
    
    $id_m = $_POST['id_misi'] ?? '';
    $imgBase64 = $_POST['image'] ?? '';

    if (empty($imgBase64) || empty($id_m)) {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap!']);
        exit;
    }

    // Decode Base64
    $filteredData = explode(',', $imgBase64);
    $decodedImg = base64_decode($filteredData[1]);

    $namaFile = "BUKTI_" . $id_u . "_" . time() . ".png";
    $targetDir = "uploads/misi/";
    
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

    if (file_put_contents($targetDir . $namaFile, $decodedImg)) {
        $sql = "INSERT INTO bukti_misi (id_user, id_misi, foto_bukti, status_verifikasi, tgl_upload) 
                VALUES ('$id_u', '$id_m', '$namaFile', 'pending', NOW())";
        
        if (mysqli_query($koneksi, $sql)) {
            echo json_encode(['status' => 'success', 'message' => 'Bukti terkirim! Tunggu validasi admin ya.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal simpan ke DB.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal tulis file ke server.']);
    }
    exit;
}
?>