<?php
// Session'ı başlat
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Hata raporlamayı kapat (JSON response için)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../classes/User.php';

// PDO bağlantısını test et
if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı bağlantısı kurulamadı']);
    exit();
}

// Zaten giriş yapmış kullanıcıları yönlendir
redirectIfLoggedIn();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
    exit();
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$remember = isset($_POST['remember']);

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'E-posta ve şifre gereklidir']);
    exit();
}

try {
    $user = new User($pdo);
    $loginResult = $user->login($email, $password);
    
    if ($loginResult['success']) {
        $userData = $loginResult['user'];
        
        // E-posta doğrulama kontrolü (sadece müşteriler için)
        if (!$userData['email_verified'] && $userData['user_type'] === 'customer') {
            echo json_encode([
                'success' => false, 
                'message' => 'E-posta adresinizi doğrulamanız gerekmektedir. Lütfen e-posta kutunuzu kontrol edin.',
                'type' => 'email_not_verified'
            ]);
            exit();
        }
        
        // Hesap durumu kontrolü
        if ($userData['status'] === 'suspended') {
            echo json_encode([
                'success' => false, 
                'message' => 'Hesabınız askıya alınmıştır. Lütfen destek ekibi ile iletişime geçin.'
            ]);
            exit();
        }
        
        if ($userData['user_type'] === 'organizer' && $userData['status'] === 'rejected') {
            echo json_encode([
                'success' => false, 
                'message' => 'Organizatör başvurunuz reddedilmiştir. Yeni bir başvuru yapabilirsiniz.'
            ]);
            exit();
        }
        
        if ($userData['status'] === 'pending' && $userData['user_type'] === 'customer') {
            echo json_encode([
                'success' => false, 
                'message' => 'Hesabınız henüz aktifleştirilmemiş. Lütfen e-posta adresinizi doğrulayın.',
                'type' => 'account_pending'
            ]);
            exit();
        }
        
        // Session'ı ayarla
        setUserSession($userData);
        
        // Remember me özelliği
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', false, true);
            
            // Token'ı veritabanına kaydet
            $stmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
            $stmt->execute([$token, $userData['id']]);
        }
        
        // Son giriş zamanını güncelle
        $user->updateLastLogin($userData['id']);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Giriş başarılı! Yönlendiriliyorsunuz...',
            'redirect' => '/index.php'
        ]);
        
    } else {
        echo json_encode(['success' => false, 'message' => $loginResult['message']]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Bir hata oluştu: ' . $e->getMessage()]);
}
?>