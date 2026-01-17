<?php
// api/graphs/data.php

require_once '../../config/database.php';
require_once '../../config/jwt_helper.php';

$database = new Database();
$db = $database->getConnection();

// validasi tanggal
function isValidDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    // Verify JWT
    $jwt = JWTHelper::getBearerToken();
    $decoded = JWTHelper::verifyToken($jwt);

    if (!$decoded) {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Unauthorized"
        ]);
        exit();
    }

    $user_id = $decoded['user_id'];

    $start_date = $_GET['start'] ?? null;
    $end_date   = $_GET['end'] ?? null;

    if (!$start_date || !$end_date) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Parameter start & end wajib diisi"
        ]);
        exit();
    }

    if (!isValidDate($start_date) || !isValidDate($end_date)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Format tanggal harus YYYY-MM-DD"
        ]);
        exit();
    }

    if (strtotime($start_date) > strtotime($end_date)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "start tidak boleh lebih besar dari end"
        ]);
        exit();
    }

    // Query data mood
    $query = "SELECT tanggal, mood_level
              FROM moods
              WHERE user_id = :user_id
              AND tanggal BETWEEN :start_date AND :end_date
              ORDER BY tanggal ASC";

    $stmt = $db->prepare($query);
    $stmt->bindParam(":user_id", $user_id);
    $stmt->bindParam(":start_date", $start_date);
    $stmt->bindParam(":end_date", $end_date);
    $stmt->execute();

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Response
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "start_date" => $start_date,
        "end_date" => $end_date,
        "total" => count($data),
        "data" => $data
    ]);
}
?>
