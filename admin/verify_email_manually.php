<?php
require_once '../config/database.php';
require_once '../includes/session.php';

// Admin kontrolü
if (!isLoggedIn() || getCurrentUser()['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
    exit();
}

$userId = intval($_POST['user_id'] ?? 0);

if ($userId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz kullanıcı ID']);
    exit();
}

try {
    // Kullanıcıyı kontrol et
    $stmt = $pdo->prepare("SELECT id, email, email_verified, user_type FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Kullanıcı bulunamadı']);
        exit();
    }
    
    if ($user['email_verified']) {
        echo json_encode(['success' => false, 'message' => 'Bu kullanıcının e-postası zaten doğrulanmış']);
        exit();
    }
    
    // E-posta doğrulamasını manuel olarak aktifleştir
    $stmt = $pdo->prepare("UPDATE users SET email_verified = 1, status = 'active' WHERE id = ?");
    $result = $stmt->execute([$userId]);
    
    if ($result) {
        // Mevcut doğrulama tokenlerini temizle
        $stmt = $pdo->prepare("DELETE FROM email_verifications WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'E-posta doğrulaması başarıyla manuel olarak aktifleştirildi'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'E-posta doğrulaması güncellenirken bir hata oluştu'
        ]);
    }
    
} catch (Exception $e) {
    error_log('Manual email verification error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Bir hata oluştu. Lütfen daha sonra tekrar deneyin.'
    ]);
}
?>