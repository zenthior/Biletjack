<?php
require_once '../config/database.php';
require_once '../classes/User.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

// Gerekli alanları kontrol et
if (!isset($input['phone']) || !isset($input['code']) || !isset($input['token']) || 
    !isset($input['first_name']) || !isset($input['last_name'])) {
    echo json_encode(['success' => false, 'message' => 'Eksik bilgiler']);
    exit;
}

$phone = $input['phone'];
$code = $input['code'];
$token = $input['token'];
$firstName = trim($input['first_name']);
$lastName = trim($input['last_name']);

// Doğrulama
if (empty($firstName) || empty($lastName)) {
    echo json_encode(['success' => false, 'message' => 'Ad ve soyad zorunludur']);
    exit;
}

if (!preg_match('/^\d{6}$/', $code)) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz doğrulama kodu']);
    exit;
}

// E-posta alanı artık kullanılmıyor

try {
    $database = new Database();
    $db = $database->getConnection();
    $user = new User($db);
    
    // Doğrulama kodunu kontrol et
    $query = "SELECT * FROM whatsapp_verifications 
              WHERE phone = :phone AND code = :code AND token = :token 
              AND expires_at > NOW() AND used = 0";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':phone', $phone);
    $stmt->bindParam(':code', $code);
    $stmt->bindParam(':token', $token);
    $stmt->execute();
    
    $verification = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$verification) {
        echo json_encode(['success' => false, 'message' => 'Geçersiz veya süresi dolmuş doğrulama kodu']);
        exit;
    }
    
    // Kullanıcının zaten kayıtlı olup olmadığını tekrar kontrol et
    $existingUser = $user->findByPhone($phone);
    if ($existingUser) {
        echo json_encode(['success' => false, 'message' => 'Bu telefon numarası zaten kayıtlı']);
        exit;
    }
    
    // Kullanıcıyı kaydet
    $userData = [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => null, // WhatsApp ile kayıt olanlarda e-posta yok
        'phone' => $phone,
        'password' => null, // WhatsApp ile kayıt olanlarda şifre yok
        'user_type' => 'customer',
        'whatsapp_verified' => true
    ];
    
    $userId = $user->registerWithWhatsApp($userData);
    
    if ($userId) {
        // Doğrulama kodunu kullanıldı olarak işaretle
        $updateQuery = "UPDATE whatsapp_verifications SET used = 1 WHERE id = :id";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bindParam(':id', $verification['id']);
        $updateStmt->execute();
        
        // Kullanıcıyı oturuma al
        session_start();
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_type'] = 'customer';
        $_SESSION['first_name'] = $firstName;
        $_SESSION['last_name'] = $lastName;
        $_SESSION['email'] = null; // WhatsApp ile kayıt olanlarda e-posta yok
        $_SESSION['phone'] = $phone;
        $_SESSION['whatsapp_verified'] = true;
        
        echo json_encode([
            'success' => true, 
            'message' => 'Kayıt başarılı',
            'user_id' => $userId
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Kayıt işlemi başarısız']);
    }
    
} catch (Exception $e) {
    error_log('WhatsApp register error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Sistem hatası oluştu']);
}
?>