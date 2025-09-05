<?php
require_once '../config/database.php';
require_once '../classes/User.php';
require_once '../includes/email_verification.php';

header('Content-Type: application/json');

// POST verilerini al
$input = json_decode(file_get_contents('php://input'), true);
$email = $input['email'] ?? '';

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'E-posta adresi gerekli']);
    exit;
}

// E-posta formatını kontrol et
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz e-posta adresi formatı']);
    exit;
}

try {
    // Database.php dosyasından global $pdo değişkenini kullan
    if (!isset($pdo)) {
        echo json_encode(['success' => false, 'message' => 'Veritabanı bağlantısı kurulamadı']);
        exit;
    }
    
    // Eski kodları temizle
    $stmt = $pdo->prepare("DELETE FROM email_verifications WHERE email = ? OR created_at < DATE_SUB(NOW(), INTERVAL 10 MINUTE)");
    $stmt->execute([$email]);
    
    // Yeni doğrulama kodu oluştur
    $verificationCode = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $token = bin2hex(random_bytes(32));
    
    // Veritabanına kaydet
    $stmt = $pdo->prepare("
        INSERT INTO email_verifications (email, code, token, expires_at, created_at) 
        VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE), NOW())
    ");
    $stmt->execute([$email, $verificationCode, $token]);
    
    // E-posta API ile kod gönder
    $emailVerification = new EmailVerification();
    $sendResult = $emailVerification->sendVerificationCode($email, $verificationCode);
    
    if ($sendResult['success']) {
        echo json_encode([
            'success' => true, 
            'message' => 'Doğrulama kodu e-posta ile gönderildi',
            'token' => $token
        ]);
    } else {
        // API hatası durumunda kodu sil
        $stmt = $pdo->prepare("DELETE FROM email_verifications WHERE email = ? AND token = ?");
        $stmt->execute([$email, $token]);
        
        echo json_encode([
            'success' => false, 
            'message' => 'E-posta gönderilemedi: ' . $sendResult['message']
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Sunucu hatası: ' . $e->getMessage()]);
}
?>