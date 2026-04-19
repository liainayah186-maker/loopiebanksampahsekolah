<?php
session_start();
require 'koneksi.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id_user'])) {
    echo json_encode(['total' => 0, 'pesan_terakhir' => '']);
    exit;
}

$id_user = $_SESSION['id_user'];
// Ambil jumlah notif belum dibaca dan judul pesan terbaru
$q = mysqli_query($koneksi, "SELECT COUNT(*) as total, judul FROM notifikasi 
                             WHERE (id_user = '$id_user' OR id_user IS NULL) 
                             AND is_read = 0 
                             ORDER BY created_at DESC LIMIT 1");
$data = mysqli_fetch_assoc($q);

echo json_encode([
    'total' => (int)$data['total'],
    'judul' => $data['judul'] ?? ''
]);
?>