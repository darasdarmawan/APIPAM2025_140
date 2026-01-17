<?php
// index.php

header("Content-Type: application/json; charset=UTF-8");

echo json_encode([
    "success" => true,
    "message" => "Welcome to Mood Care API",
    "version" => "1.0",
    "endpoints" => [
        "auth" => [
            "POST /api/auth/register" => "Register user baru",
            "POST /api/auth/login" => "Login user"
        ],
        "moods" => [
            "GET /api/moods/index.php" => "Get semua mood (need token)",
            "GET /api/moods/get.php?id={id}" => "Get mood by ID (need token)",
            "POST /api/moods/create.php" => "Tambah mood baru (need token)",
            "PUT /api/moods/update.php" => "Update mood (need token)",
            "DELETE /api/moods/delete.php?id={id}" => "Delete mood (need token)",
            "GET /api/moods/search.php?date={date}" => "Search mood by date (need token)",
            "GET /api/moods/search.php?start_date={start}&end_date={end}" => "Search mood by range (need token)"
        ],
        "users" => [
            "GET /api/users/profile.php" => "Get user profile (need token)",
            "PUT /api/users/update.php" => "Update user profile (need token)"
        ]
    ],
    "note" => "Semua endpoint (kecuali auth) membutuhkan JWT token di header: Authorization: Bearer {token}"
]);
?>
