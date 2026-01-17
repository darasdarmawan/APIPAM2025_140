<?php
// api/users/update.php (FIXED VERSION)

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, PUT");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../../config/database.php';
require_once '../../config/jwt_helper.php';

$database = new Database();
$db = $database->getConnection();

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' || $_SERVER['REQUEST_METHOD'] == 'PUT') {
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
    $foto_profil = null;
    
    if (isset($_FILES['fotoProfil']) && $_FILES['fotoProfil']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['fotoProfil'];
        
        $allowed = ['jpg', 'jpeg', 'png'];
        $filename = $file['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Format file harus JPG atau PNG"
            ]);
            exit();
        }
        
        // Validasi ukuran file 
        if ($file['size'] > 5 * 1024 * 1024) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Ukuran file maksimal 5MB"
            ]);
            exit();
        }
    
        $newFilename = 'profile_' . $user_id . '_' . time() . '.' . $ext;
        $uploadDir = '../../uploads/profiles/';
        
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $uploadPath = $uploadDir . $newFilename;
        
        // Hapus foto lama jika ada
        $queryOld = "SELECT foto_profil FROM users WHERE user_id = :user_id";
        $stmtOld = $db->prepare($queryOld);
        $stmtOld->bindParam(":user_id", $user_id);
        $stmtOld->execute();
        $oldData = $stmtOld->fetch(PDO::FETCH_ASSOC);
        
        if ($oldData && $oldData['foto_profil']) {
            // Extract filename dari URL
            $oldFilename = basename($oldData['foto_profil']);
            $oldPath = $uploadDir . $oldFilename;
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }
        
        // Upload file baru
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        
            $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'];
            
            $foto_profil = $protocol . "://" . $host . "/moodcare-api/uploads/profiles/" . $newFilename;
            
        } else {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Gagal mengupload file"
            ]);
            exit();
        }
        
        $nama = $_POST['nama'] ?? null;
        
    } else {
        
        $data = json_decode(file_get_contents("php://input"));
        $nama = $data->nama ?? null;
        $foto_profil = $data->foto_profil ?? null;
    }
    
    if (!$nama) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Nama tidak boleh kosong"
        ]);
        exit();
    }
    
    // Update user profile
    if ($foto_profil) {
        $query = "UPDATE users SET 
                  nama = :nama,
                  foto_profil = :foto_profil,
                  updated_at = NOW()
                  WHERE user_id = :user_id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":nama", $nama);
        $stmt->bindParam(":foto_profil", $foto_profil);
        $stmt->bindParam(":user_id", $user_id);
    } else {
        $query = "UPDATE users SET 
                  nama = :nama,
                  updated_at = NOW()
                  WHERE user_id = :user_id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":nama", $nama);
        $stmt->bindParam(":user_id", $user_id);
    }
    
    if ($stmt->execute()) {
        // Get updated user data
        $queryUser = "SELECT user_id, nama, email, foto_profil FROM users WHERE user_id = :user_id";
        $stmtUser = $db->prepare($queryUser);
        $stmtUser->bindParam(":user_id", $user_id);
        $stmtUser->execute();
        $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Profil berhasil diupdate",
            "data" => $userData
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Gagal mengupdate profil"
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Method not allowed"
    ]);
}
?>