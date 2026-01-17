<?php
// api/auth/register.php

require_once '../../config/database.php';
require_once '../../config/jwt_helper.php';

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    
    // Validasi input
    if (empty($data->nama) || empty($data->email) || empty($data->password)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Nama, email, dan password wajib diisi"
        ]);
        exit();
    }
    
    $query = "SELECT user_id FROM users WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":email", $data->email);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Email sudah terdaftar"
        ]);
        exit();
    }
    
    // Hash password
    $hashed_password = hash('sha256', $data->password);
    
    $query = "INSERT INTO users (nama, email, password, created_at, updated_at) 
              VALUES (:nama, :email, :password, NOW(), NOW())";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":nama", $data->nama);
    $stmt->bindParam(":email", $data->email);
    $stmt->bindParam(":password", $hashed_password);
    
    if ($stmt->execute()) {
        $user_id = $db->lastInsertId();
        
        $token = JWTHelper::generateToken($user_id, $data->email);
         $device_info = 'Unknown';
        if (isset($data->device_info) && !empty($data->device_info)) {
            $device_info = $data->device_info;
        } elseif (isset($_SERVER['HTTP_USER_AGENT'])) {
            $device_info = $_SERVER['HTTP_USER_AGENT'];
        }
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';

        $queryHistory = "INSERT INTO login_history (user_id, device_info, ip_address)
                        VALUES (:user_id, :device_info, :ip_address)";
        $stmtHistory = $db->prepare($queryHistory);
        $stmtHistory->bindParam(':user_id', $user_id);
        $stmtHistory->bindParam(':device_info', $device_info);
        $stmtHistory->bindParam(':ip_address', $ip_address);
        $stmtHistory->execute();
        http_response_code(201);
        echo json_encode([
            "success" => true,
            "message" => "Registrasi berhasil",
            "token" => $token,
            "user" => [
                "user_id" => $user_id,
                "nama" => $data->nama,
                "email" => $data->email
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Registrasi gagal"
        ]);
    }
}
?>