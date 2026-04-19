<?php
session_start();
session_destroy(); // Menghapus semua data session
header("Location: home.php"); // Kembali ke halaman login
exit();
?>