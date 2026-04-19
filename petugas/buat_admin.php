<?php
require 'koneksi.php';

$password = password_hash('admin123', PASSWORD_DEFAULT);

mysqli_query($koneksi, "
    INSERT INTO admins (nama_petugas, username, password)
    VALUES ('Super Admin', 'admin', '$password')
");

echo "ADMIN BERHASIL DIBUAT";
