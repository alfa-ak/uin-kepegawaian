<?php

header("Content-Type: application/json");

require_once "../config/database.php";

function response($status, $message, $data = null, $code = 200)
{

    http_response_code($code);

    echo json_encode([
        "status" => $status,
        "message" => $message,
        "data" => $data
    ]);

    exit;
}

// Hanya menerima POST
if ($_SERVER['REQUEST_METHOD'] != "POST") {
    response(false, "Method tidak diizinkan", null, 405);
}

// Membaca JSON
$data = json_decode(file_get_contents("php://input"), true);

if (!is_array($data)) {
    response(false, "Format JSON tidak valid", null, 400);
}

// Validasi input
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

if ($email == '' || $password == '') {
    response(false, "Email dan password wajib diisi", null, 422);
}

// Mencari user
$stmt = $conn->prepare(
    "SELECT * FROM users WHERE email = ?"
);

$stmt->execute([$email]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Memeriksa password
if (!$user || !password_verify($password, $user['password'])) {
    response(false, "Email atau password salah", null, 401);
}

// Membuat token acak
$token = bin2hex(random_bytes(32));

// Simpan hash token ke database
$tokenHash = hash('sha256', $token);

// Masa berlaku token 24 jam
$expires = date('Y-m-d H:i:s', strtotime('+24 hours'));

$stmt = $conn->prepare("
    INSERT INTO tokens (user_id, token, expires_at)
    VALUES (?, ?, ?)
");

$stmt->execute([
    $user['id'],
    $tokenHash,
    $expires
]);

// Response login berhasil
response(true, "Login berhasil", [
    "user" => [
        "id" => $user['id'],
        "nama" => $user['nama'],
        "email" => $user['email']
    ],
    "token_type" => "Bearer",
    "access_token" => $token,
    "expires_at" => $expires
]);
