<?php
// api/graphs/save.php
require_once '../../config/database.php';
require_once '../../config/jwt_helper.php';

header("Content-Type: application/json");
file_put_contents(
    "debug_graph.txt",
    json_encode($_POST ?: json_decode(file_get_contents("php://input"), true))
);

$database = new Database();
$db = $database->getConnection();

// validasi tanggal
function isValidDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Method not allowed"
    ]);
    exit;
}

// ======================
// VERIFY JWT
// ======================
$jwt = JWTHelper::getBearerToken();
$decoded = JWTHelper::verifyToken($jwt);

if (!$decoded || !isset($decoded['user_id'])) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized"
    ]);
    exit;
}

$user_id = (int) $decoded['user_id'];

// ======================
// GET JSON BODY
// ======================
$data = json_decode(file_get_contents("php://input"));

if (
    !isset($data->start_date) ||
    !isset($data->end_date) ||
    !isset($data->file_name)
) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "start_date, end_date, dan file_name wajib diisi"
    ]);
    exit;
}

$start_date = $data->start_date;
$end_date   = $data->end_date;
$file_name  = trim($data->file_name);

// ======================
// VALIDATION
// ======================
if (!isValidDate($start_date) || !isValidDate($end_date)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Format tanggal harus YYYY-MM-DD"
    ]);
    exit;
}

if (strtotime($start_date) > strtotime($end_date)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "start_date tidak boleh lebih besar dari end_date"
    ]);
    exit;
}

// ======================
// INSERT DATABASE
// ======================
$query = "INSERT INTO graphs 
          (user_id, start_date, end_date, file_name, downloaded_at)
          VALUES (:user_id, :start_date, :end_date, :file_name, NOW())";

$stmt = $db->prepare($query);
$stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
$stmt->bindParam(":start_date", $start_date);
$stmt->bindParam(":end_date", $end_date);
$stmt->bindParam(":file_name", $file_name);

if ($stmt->execute()) {
    http_response_code(201);
    echo json_encode([
        "success" => true,
        "message" => "Riwayat graph berhasil disimpan"
    ]);
} else {
    $error = $stmt->errorInfo();
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Gagal menyimpan riwayat graph",
        "error" => $error
    ]);
}
