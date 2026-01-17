<?php
// api/moods/search.php

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

    $date = $_GET['date'] ?? null;
    $start_date = $_GET['start_date'] ?? null;
    $end_date = $_GET['end_date'] ?? null;

    if ($date) {

        if (!isValidDate($date)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Format date harus YYYY-MM-DD"
            ]);
            exit();
        }

        $query = "SELECT * FROM moods
                  WHERE user_id = :user_id AND tanggal = :tanggal
                  ORDER BY created_at DESC";

        $stmt = $db->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":tanggal", $date);
    }

    else if ($start_date && $end_date) {

        if (!isValidDate($start_date) || !isValidDate($end_date)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Format start_date dan end_date harus YYYY-MM-DD"
            ]);
            exit();
        }

        if (strtotime($start_date) > strtotime($end_date)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "start_date tidak boleh lebih besar dari end_date"
            ]);
            exit();
        }

        $query = "SELECT * FROM moods
                  WHERE user_id = :user_id
                  AND tanggal BETWEEN :start_date AND :end_date
                  ORDER BY tanggal ASC";

        $stmt = $db->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":start_date", $start_date);
        $stmt->bindParam(":end_date", $end_date);
    }

    else {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Gunakan parameter ?date atau ?start_date & end_date"
        ]);
        exit();
    }

    $stmt->execute();
    $moods = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        "success" => true,
        "count" => count($moods),
        "data" => $moods
    ]);
}
?>
