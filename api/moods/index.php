<?php
// api/moods/index.php

require_once '../../config/database.php';
require_once '../../config/jwt_helper.php';

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // Verify JWT token
    $jwt = JWTHelper::getBearerToken();
    $decoded = JWTHelper::verifyToken($jwt);
    
    if (!$decoded) {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Unauthorized. Token tidak valid atau expired"
        ]);
        exit();
    }
    
    $user_id = $decoded['user_id'];
    
    // Get all moods
    $query = "SELECT * FROM moods WHERE user_id = :user_id ORDER BY tanggal DESC, created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":user_id", $user_id);
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