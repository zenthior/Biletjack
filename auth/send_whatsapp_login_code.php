<?php
require_once '../config/database.php';
require_once '../classes/User.php';
require_once '../includes/whatsapp_api.php';

header('Content-Type: application/json');

// POST verilerini al
$input = json_decode(file_get_contents('php://input'), true);
$phone = $input['phone'] ?? '';

if (empty($phone)) {
    echo json_encode(['success' => false, 'message' => 'Telefon numarası gerekli']);
    exit;
}

// Telefon numarası formatını kontrol et
if (!preg_match('/^\+90[0-9]{10}$/', $phone)) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz telefon numarası formatı']);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    $user = new User($pdo);
    
    // Kullanıcının kayıtlı olup olmadığını kontrol et
    $existingUser = $user->findByPhone($phone);
    if (!$existingUser) {
        echo json_encode(['success' => false, 'message' => 'Bu telefon numarası ile kayıtlı kullanıcı bulunamadı']);
        exit;
    }
    
    // 6 haneli doğrulama kodu oluştur
    $verificationCode = sprintf('%06d', mt_rand(0, 999999));
    
    // Güvenlik token'ı oluştur
    $token = bin2hex(random_bytes(32));
    
    // Eski kodları sil
    $stmt = $pdo->prepare("DELETE FROM whatsapp_login_verifications WHERE phone = ?");
    $stmt->execute([$phone]);
    
    // Yeni kodu kaydet
    $stmt = $pdo->prepare("
        INSERT INTO whatsapp_login_verifications (phone, code, token, expires_at, created_at) 
        VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE), NOW())
    ");
    $stmt->execute([$phone, $verificationCode, $token]);
    
    // WhatsApp API ile kod gönder
    $whatsappAPI = new WhatsAppAPI();
    $sendResult = $whatsappAPI->sendVerificationCode($phone, $verificationCode);
    
    if ($sendResult['success']) {
        echo json_encode([
            'success' => true, 
            'message' => 'Doğrulama kodu WhatsApp\'tan gönderildi',
            'token' => $token
        ]);
    } else {
        // API hatası durumunda kodu sil
        $stmt = $pdo->prepare("DELETE FROM whatsapp_login_verifications WHERE phone = ? AND token = ?");
        $stmt->execute([$phone, $token]);
        
        echo json_encode([
            'success' => false, 
            'message' => 'Kod gönderilemedi: ' . $sendResult['message']
        ]);
    }
    
} catch (Exception $e) {
    error_log('WhatsApp login code send error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Kod gönderilirken hata oluştu']);
}
?>