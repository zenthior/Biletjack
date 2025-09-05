<?php
require_once '../config/database.php';
require_once '../includes/email_verification.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
    exit();
}

$email = trim($_POST['email'] ?? '');

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'E-posta adresi gereklidir']);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Geçerli bir e-posta adresi giriniz']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Kullanıcıyı kontrol et
    $stmt = $db->prepare("SELECT id, first_name, last_name, email_verified, status FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Bu e-posta adresi ile kayıtlı kullanıcı bulunamadı']);
        exit();
    }
    
    if ($user['email_verified']) {
        echo json_encode(['success' => false, 'message' => 'E-posta adresiniz zaten doğrulanmış']);
        exit();
    }
    
    // Son gönderilen doğrulama e-postasını kontrol et (spam önleme)
    $stmt = $db->prepare("
        SELECT created_at FROM email_verifications 
        WHERE user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 2 MINUTE)
        ORDER BY created_at DESC LIMIT 1
    ");
    $stmt->execute([$user['id']]);
    $recentVerification = $stmt->fetch();
    
    if ($recentVerification) {
        echo json_encode([
            'success' => false, 
            'message' => 'Çok sık doğrulama e-postası gönderiyorsunuz. Lütfen 2 dakika bekleyin.'
        ]);
        exit();
    }
    
    // Yeni doğrulama e-postası gönder
    $emailVerification = new EmailVerification($db);
    $emailSent = $emailVerification->sendVerificationEmail(
        $user['id'],
        $email,
        $user['first_name'],
        $user['last_name']
    );
    
    if ($emailSent) {
        echo json_encode([
            'success' => true,
            'message' => 'Doğrulama e-postası başarıyla gönderildi. Lütfen e-posta kutunuzu kontrol edin.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Doğrulama e-postası gönderilemedi. Lütfen daha sonra tekrar deneyin.'
        ]);
    }
    
} catch (Exception $e) {
    error_log('Resend verification error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Bir hata oluştu. Lütfen daha sonra tekrar deneyin.'
    ]);
}
?>