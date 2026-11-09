<?php

require_once "config/database.php";

$nama = "Administrator";
$email = "admin@gmail.com";
$password = password_hash("admin123", PASSWORD_DEFAULT);

$stmt = $conn->prepare("
    INSERT INTO users (nama, email, password)
    VALUES (?, ?, ?)
");

$stmt->execute([
    $nama,
    $email,
    $password
]);

echo "User berhasil dibuat";
