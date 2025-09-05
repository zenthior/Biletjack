<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Database.php';
require_once '../classes/User.php';

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
    
    // Kullanıcıyı bul
    $userClass = new User($db);
    $user = $userClass->findByPhone($phone);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Kullanıcı bulunamadı']);
        exit;
    }
    
    // Doğrulama kodunu kullanıldı olarak işaretle
    $stmt = $db->prepare("UPDATE whatsapp_verifications SET used = 1 WHERE id = ?");
    $stmt->execute([$verification['id']]);
    
    // Kullanıcıyı oturuma al
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_type'] = $user['user_type'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['phone'] = $user['phone'];
    $_SESSION['whatsapp_verified'] = $user['whatsapp_verified'];
    
    // Son giriş tarihini güncelle
    $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Giriş başarılı',
        'user' => [
            'id' => $user['id'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'email' => $user['email'],
            'user_type' => $user['user_type']
        ]
    ]);
    
} catch (Exception $e) {
    error_log("WhatsApp login verification error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Sistem hatası oluştu']);
}
?>