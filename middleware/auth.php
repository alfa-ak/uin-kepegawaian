<?php

require_once __DIR__ . "/../config/database.php";

function authUser($conn)
{

    // Mengambil HTTP Header
    $authorization = '';

    if (function_exists('getallheaders')) {

        $headers = getallheaders();

        foreach ($headers as $key => $value) {

            if (strcasecmp($key, 'Authorization') === 0) {
                $authorization = $value;
                break;
            }
        }
    }

    // Alternatif mengambil header dari SERVER
    if (empty($authorization)) {
        $authorization = $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? '';
    }

    // Memeriksa apakah Bearer Token tersedia
    if (!preg_match('/^Bearer\s+(.+)$/i', trim($authorization), $matches)) {

        http_response_code(401);

        echo json_encode([
            "status" => false,
            "message" => "Bearer token wajib disertakan"
        ]);

        exit;
    }

    // Mengambil token
    $token = trim($matches[1]);

    // Hash token
    $tokenHash = hash('sha256', $token);

    // Memeriksa token ke database
    $stmt = $conn->prepare("
        SELECT
            tokens.id AS token_id,
            tokens.expires_at,
            users.id AS user_id,
            users.nama,
            users.email
        FROM tokens
        JOIN users ON users.id = tokens.user_id
        WHERE tokens.token = ?
        AND tokens.expires_at > NOW()
        LIMIT 1
    ");

    $stmt->execute([$tokenHash]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {

        http_response_code(401);

        echo json_encode([
            "status" => false,
            "message" => "Token tidak valid atau sudah kedaluwarsa"
        ]);

        exit;
    }

    return $user;
}
