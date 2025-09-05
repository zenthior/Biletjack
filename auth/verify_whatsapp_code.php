<?php
require_once '../config/database.php';
require_once '../classes/Database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['phone']) || !isset($input['code']) || !isset($input['token'])) {
    echo json_encode(['success' => false, 'message' => 'Eksik parametreler']);
    exit;
}

$phone = $input['phone'];
$code = $input['code'];
$token = $input['token'];

// Doğrulama kodu formatını kontrol et
if (!preg_match('/^[0-9]{6}$/', $code)) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz doğrulama kodu formatı']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Doğrulama kodunu kontrol et
    $stmt = $db->prepare("
        SELECT id FROM whatsapp_verifications 
        WHERE phone = ? AND code = ? AND token = ? AND expires_at > NOW() AND used = 0
    ");
    $stmt->execute([$phone, $code, $token]);
    $verification = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$verification) {
        echo json_encode(['success' => false, 'message' => 'Geçersiz veya süresi dolmuş doğrulama kodu']);
        exit;
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Doğrulama kodu başarıyla onaylandı'
    ]);
    
} catch (Exception $e) {
    error_log("WhatsApp code verification error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Sistem hatası oluştu']);
}
?>