<?php
// api/moods/create.php
date_default_timezone_set('Asia/Jakarta');

require_once '../../config/database.php';
require_once '../../config/jwt_helper.php';

header("Content-Type: application/json; charset=UTF-8");
ini_set('display_errors', 0);
error_reporting(E_ALL);

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

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
    $data = json_decode(file_get_contents("php://input"));

    // Validasi input 
    if (!isset($data->mood_level)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Mood level wajib diisi"
        ]);
        exit();
    }

    // Tanggal hari ini
    $tanggal = date('Y-m-d');

    // Insert mood
    $query = "INSERT INTO moods 
        (user_id, mood_level, description, hal_bersyukur, hal_sedih, hal_perbaikan, tanggal, created_at, updated_at) 
        VALUES 
        (:user_id, :mood_level, :description, :hal_bersyukur, :hal_sedih, :hal_perbaikan, :tanggal, NOW(), NOW())";

    $stmt = $db->prepare($query);
    $stmt->bindParam(":user_id", $user_id);
    $stmt->bindParam(":mood_level", $data->mood_level);
    $stmt->bindParam(":description", $data->description);
    $stmt->bindParam(":hal_bersyukur", $data->hal_bersyukur);
    $stmt->bindParam(":hal_sedih", $data->hal_sedih);
    $stmt->bindParam(":hal_perbaikan", $data->hal_perbaikan);
    $stmt->bindParam(":tanggal", $tanggal);

    if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode([
            "success" => true,
            "message" => "Mood berhasil ditambahkan",
            "mood_id" => $db->lastInsertId()
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Gagal menambahkan mood"
        ]);
    }
}
