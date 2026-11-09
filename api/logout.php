<?php

header("Content-Type: application/json");

require_once "../config/database.php";
require_once "../middleware/auth.php";

// Memeriksa token
$user = authUser($conn);

// Menghapus token yang sedang digunakan
$stmt = $conn->prepare("
    DELETE FROM tokens WHERE id = ?
");

$stmt->execute([$user['token_id']]);

http_response_code(200);

echo json_encode([
    "status" => true,
    "message" => "Logout berhasil"
]);
