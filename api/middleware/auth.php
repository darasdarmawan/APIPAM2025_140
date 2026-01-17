<?php
require_once __DIR__ . '/../../config/jwt_helper.php';

function requireAuth() {
    $token = JWTHelper::getBearerToken();

    if (!$token) {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Authorization token required"
        ]);
        exit();
    }

    $payload = JWTHelper::verifyToken($token);

    if (!$payload) {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Invalid or expired token"
        ]);
        exit();
    }

    return $payload;
}
