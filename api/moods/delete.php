<?php
// api/moods/delete.php

require_once '../../config/database.php';
require_once '../../config/jwt_helper.php';

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
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
    $mood_id = isset($_GET['id']) ? $_GET['id'] : null;
    
    if (empty($mood_id)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Mood ID required"
        ]);
        exit();
    }
    
    // Delete mood 
    $query = "DELETE FROM moods WHERE mood_id = :mood_id AND user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":mood_id", $mood_id);
    $stmt->bindParam(":user_id", $user_id);
    
    if ($stmt->execute()) {
        if ($stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode([
                "success" => true,
                "message" => "Mood berhasil dihapus"
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                "success" => false,
                "message" => "Mood tidak ditemukan"
            ]);
        }
    } else {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Gagal menghapus mood"
        ]);
    }
}
?>