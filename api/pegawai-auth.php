<?php

header("Content-Type: application/json");

require_once "../config/database.php";
require_once "../middleware/auth.php";

// echo json_encode([
//     "authorization" => $_SERVER['HTTP_AUTHORIZATION'] ?? "TIDAK TERBACA",
//     "headers" => function_exists('getallheaders')
//         ? getallheaders()
//         : []
// ]);

// exit;

// Memeriksa Bearer Token
$user = authUser($conn);

// Fungsi response JSON
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

// Membaca input JSON
function getInput()
{
    $input = json_decode(file_get_contents("php://input"), true);

    if (!is_array($input)) {
        response(false, "Format JSON tidak valid", null, 400);
    }

    return $input;
}

// Validasi data pegawai
function validasi($data)
{

    $errors = [];

    // Validasi NIP
    if (empty(trim($data['nip'] ?? ''))) {
        $errors['nip'] = "NIP wajib diisi";
    }

    // Validasi nama
    if (empty(trim($data['nama'] ?? ''))) {
        $errors['nama'] = "Nama pegawai wajib diisi";
    }

    // Validasi jabatan
    if (empty(trim($data['jabatan'] ?? ''))) {
        $errors['jabatan'] = "Jabatan wajib diisi";
    }

    // Validasi angka
    $fields = [
        'gaji_pokok' => true,
        'tunjangan' => false,
        'potongan' => false
    ];

    foreach ($fields as $field => $required) {

        $value = $data[$field] ?? null;

        if ($value === null || $value === '') {

            if ($required) {
                $errors[$field] = "$field wajib diisi";
            }

            continue;
        }

        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            $errors[$field] = "$field harus berupa bilangan bulat";
        } elseif ($value < 0) {
            $errors[$field] = "$field tidak boleh negatif";
        }
    }

    return $errors;
}


// Menentukan method HTTP
$method = $_SERVER['REQUEST_METHOD'];

$id = $_GET['id'] ?? null;


// ==================================================
// GET: MENAMPILKAN DATA PEGAWAI
// ==================================================

if ($method == "GET") {

    if ($id) {

        $stmt = $conn->prepare(
            "SELECT * FROM pegawai WHERE id = ?"
        );

        $stmt->execute([$id]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            response(false, "Data pegawai tidak ditemukan", null, 404);
        }

        response(true, "Detail pegawai", $data);
    } else {

        $stmt = $conn->query(
            "SELECT * FROM pegawai ORDER BY id DESC"
        );

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        response(true, "Data semua pegawai", $data);
    }
}


// ==================================================
// POST: MENAMBAHKAN DATA PEGAWAI
// ==================================================

elseif ($method == "POST") {

    $data = getInput();

    $errors = validasi($data);

    if (!empty($errors)) {
        response(false, "Validasi gagal", $errors, 422);
    }

    $nip = trim($data['nip']);
    $nama = trim($data['nama']);
    $jabatan = trim($data['jabatan']);

    $gaji = (int) $data['gaji_pokok'];
    $tunjangan = (int) ($data['tunjangan'] ?? 0);
    $potongan = (int) ($data['potongan'] ?? 0);

    $total = $gaji + $tunjangan - $potongan;

    try {

        $stmt = $conn->prepare(
            "INSERT INTO pegawai
            (nip, nama, jabatan, gaji_pokok,
             tunjangan, potongan, total_gaji)
            VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $nip,
            $nama,
            $jabatan,
            $gaji,
            $tunjangan,
            $potongan,
            $total
        ]);

        response(true, "Data pegawai berhasil ditambahkan", [
            "id" => $conn->lastInsertId(),
            "total_gaji" => $total
        ], 201);
    } catch (PDOException $e) {

        if ($e->getCode() == "23000") {
            response(false, "NIP sudah terdaftar", null, 409);
        }

        response(false, "Gagal menambahkan data", null, 500);
    }
}


// ==================================================
// PUT: MENGEDIT DATA PEGAWAI
// ==================================================

elseif ($method == "PUT") {

    if (!$id) {
        response(false, "ID pegawai wajib diisi", null, 400);
    }

    $data = getInput();

    $errors = validasi($data);

    if (!empty($errors)) {
        response(false, "Validasi gagal", $errors, 422);
    }

    // Periksa data pegawai
    $stmt = $conn->prepare(
        "SELECT id FROM pegawai WHERE id = ?"
    );

    $stmt->execute([$id]);

    if (!$stmt->fetch()) {
        response(false, "Data pegawai tidak ditemukan", null, 404);
    }

    $nip = trim($data['nip']);
    $nama = trim($data['nama']);
    $jabatan = trim($data['jabatan']);

    $gaji = (int) $data['gaji_pokok'];
    $tunjangan = (int) ($data['tunjangan'] ?? 0);
    $potongan = (int) ($data['potongan'] ?? 0);

    $total = $gaji + $tunjangan - $potongan;

    try {

        $stmt = $conn->prepare(
            "UPDATE pegawai SET
                nip = ?,
                nama = ?,
                jabatan = ?,
                gaji_pokok = ?,
                tunjangan = ?,
                potongan = ?,
                total_gaji = ?
             WHERE id = ?"
        );

        $stmt->execute([
            $nip,
            $nama,
            $jabatan,
            $gaji,
            $tunjangan,
            $potongan,
            $total,
            $id
        ]);

        response(true, "Data pegawai berhasil diubah", [
            "id" => $id,
            "total_gaji" => $total
        ]);
    } catch (PDOException $e) {

        if ($e->getCode() == "23000") {
            response(false, "NIP sudah digunakan pegawai lain", null, 409);
        }

        response(false, "Gagal mengubah data", null, 500);
    }
}


// ==================================================
// DELETE: MENGHAPUS DATA PEGAWAI
// ==================================================

elseif ($method == "DELETE") {

    if (!$id) {
        response(false, "ID pegawai wajib diisi", null, 400);
    }

    $stmt = $conn->prepare(
        "DELETE FROM pegawai WHERE id = ?"
    );

    $stmt->execute([$id]);

    if ($stmt->rowCount() == 0) {
        response(false, "Data pegawai tidak ditemukan", null, 404);
    }

    response(true, "Data pegawai berhasil dihapus");
}


// ==================================================
// METHOD TIDAK DIDUKUNG
// ==================================================

else {

    response(false, "Method tidak diizinkan", null, 405);
}
