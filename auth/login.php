<?php
// Session'ı başlat
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Hata raporlamayı aç (geçici)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
    // Debug: Gelen verileri logla
    error_log('Login attempt - Email: ' . $email . ', Password length: ' . strlen($password));
    
    // User nesnesini oluşturmayı test et
    $user = new User($pdo);
    
    // Debug: Veritabanından kullanıcıyı kontrol et
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $dbUser = $stmt->fetch();
    
    if ($dbUser) {
        error_log('User found in DB: ' . print_r($dbUser, true));
        error_log('Password verify result: ' . (password_verify($password, $dbUser['password']) ? 'SUCCESS' : 'FAIL'));
    } else {
        error_log('User not found in database');
    }
    
    // Login metodunu çağırmayı test et
    $loginResult = $user->login($email, $password);
    
    if ($loginResult['success']) {
        $userData = $loginResult['user'];
        
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
        
        // Session'ı ayarla
        setUserSession($userData);
        
        // Debug: Session verilerini kontrol et
        error_log('Session ayarlandı: ' . print_r($_SESSION, true));
        
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
            'redirect' => '/Biletjack/index.php', // Ana sayfaya yönlendir
            'debug_session' => $_SESSION // Debug için session verilerini gönder
        ]);
        
    } else {
        echo json_encode(['success' => false, 'message' => $loginResult['message']]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Bir hata oluştu: ' . $e->getMessage()]);
}
?>