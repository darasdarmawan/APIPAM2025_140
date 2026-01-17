<?php
// api/auth/login.php

require_once '../../config/database.php';
require_once '../../config/jwt_helper.php';

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    
    // Validasi input
    if (empty($data->email) || empty($data->password)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Email dan password wajib diisi"
        ]);
        exit();
    }
    
    // Hash password
    $hashed_password = hash('sha256', $data->password);
    
    $query = "SELECT user_id, nama, email, foto_profil FROM users 
              WHERE email = :email AND password = :password";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":email", $data->email);
    $stmt->bindParam(":password", $hashed_password);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Generate JWT token
        $token = JWTHelper::generateToken($user['user_id'], $user['email']);
        
        $device_info = 'Unknown';
        if (isset($data->device_info) && !empty($data->device_info)) {
            $device_info = $data->device_info;
        } elseif (isset($_SERVER['HTTP_USER_AGENT'])) {
            $device_info = $_SERVER['HTTP_USER_AGENT'];
        }
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';

        $query = "INSERT INTO login_history (user_id, device_info, ip_address) VALUES (:user_id, :device_info, :ip_address)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user['user_id']);
        $stmt->bindParam(':device_info', $device_info);
        $stmt->bindParam(':ip_address', $ip_address);
        $stmt->execute();

        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Login berhasil",
            "token" => $token,
            "user" => [
                "user_id" => $user['user_id'],
                "nama" => $user['nama'],
                "email" => $user['email'],
                "foto_profil" => $user['foto_profil']
            ]
        ]);
    } else {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Email atau password salah"
        ]);
    }
}
?>