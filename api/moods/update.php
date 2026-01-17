<?php
// api/moods/update.php

require_once '../../config/database.php';
require_once '../../config/jwt_helper.php';

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
    // Verify JWT token
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
    if (!isset($data->mood_id)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Mood ID required"
        ]);
        exit();
    }
 
    $check_query = "SELECT mood_id FROM moods WHERE mood_id = :mood_id AND user_id = :user_id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(":mood_id", $data->mood_id);
    $check_stmt->bindParam(":user_id", $user_id);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() == 0) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "Mood tidak ditemukan"
        ]);
        exit();
    }
    
    // Update mood
    $query = "UPDATE moods SET 
              mood_level = :mood_level,
              description = :description,
              hal_bersyukur = :hal_bersyukur,
              hal_sedih = :hal_sedih,
              hal_perbaikan = :hal_perbaikan,
              updated_at = NOW()
              WHERE mood_id = :mood_id AND user_id = :user_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":mood_level", $data->mood_level);
    $stmt->bindParam(":description", $data->description);
    $stmt->bindParam(":hal_bersyukur", $data->hal_bersyukur);
    $stmt->bindParam(":hal_sedih", $data->hal_sedih);
    $stmt->bindParam(":hal_perbaikan", $data->hal_perbaikan);
    $stmt->bindParam(":mood_id", $data->mood_id);
    $stmt->bindParam(":user_id", $user_id);
    
    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Mood berhasil diupdate"
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Gagal mengupdate mood"
        ]);
    }
}
?>

