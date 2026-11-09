<?php

$host = "localhost";
$db   = "db_penggajian";
$user = "root";
$pass = "alfaitech";

try {

    $conn = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass
    );

    $conn->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );
} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "status" => false,
        "message" => "Koneksi database gagal"
    ]);

    exit;
}


// CREATE TABLE users (
//     id INT AUTO_INCREMENT PRIMARY KEY,
//     nama VARCHAR(100) NOT NULL,
//     email VARCHAR(100) NOT NULL UNIQUE,
//     password VARCHAR(255) NOT NULL,
//     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
// );

// CREATE TABLE tokens (
//     id INT AUTO_INCREMENT PRIMARY KEY,
//     user_id INT NOT NULL,
//     token VARCHAR(64) NOT NULL UNIQUE,
//     expires_at DATETIME NOT NULL,
//     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

//     FOREIGN KEY (user_id)
//     REFERENCES users(id)
//     ON DELETE CASCADE
// );