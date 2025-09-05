<?php
require_once '../includes/session.php';
require_once '../config/database.php';

// Google OAuth callback işlemi
if (!isset($_GET['code']) || !isset($_GET['state'])) {
    header('Location: ../index.php?error=oauth_failed');
    exit();
}

$authCode = $_GET['code'];
$state = $_GET['state']; // 'login' veya 'register'

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Google OAuth ayarlarını al
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('google_oauth_client_id', 'google_oauth_client_secret')");
    $stmt->execute();
    
    $settings = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    
    $clientId = $settings['google_oauth_client_id'] ?? '';
    $clientSecret = $settings['google_oauth_client_secret'] ?? '';
    
    if (empty($clientId) || empty($clientSecret)) {
        throw new Exception('Google OAuth ayarları eksik');
    }
    
    // Access token al
    $redirectUri = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . 
                   '://' . $_SERVER['HTTP_HOST'] . '/auth/google_callback.php';
    
    $tokenUrl = 'https://oauth2.googleapis.com/token';
    $tokenData = [
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'code' => $authCode,
        'grant_type' => 'authorization_code',
        'redirect_uri' => $redirectUri
    ];
    
    $tokenOptions = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($tokenData)
        ]
    ];
    
    $tokenContext = stream_context_create($tokenOptions);
    $tokenResponse = file_get_contents($tokenUrl, false, $tokenContext);
    $tokenResult = json_decode($tokenResponse, true);
    
    if (!isset($tokenResult['access_token'])) {
        throw new Exception('Access token alınamadı');
    }
    
    // Kullanıcı bilgilerini al
    $userInfoUrl = 'https://www.googleapis.com/oauth2/v2/userinfo?access_token=' . $tokenResult['access_token'];
    $userInfoResponse = file_get_contents($userInfoUrl);
    $userInfo = json_decode($userInfoResponse, true);
    
    if (!isset($userInfo['email'])) {
        throw new Exception('Kullanıcı bilgileri alınamadı');
    }
    
    $email = $userInfo['email'];
    $firstName = $userInfo['given_name'] ?? '';
    $lastName = $userInfo['family_name'] ?? '';
    $googleId = $userInfo['id'];
    
    // Kullanıcının zaten kayıtlı olup olmadığını kontrol et
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR google_id = ?");
    $stmt->execute([$email, $googleId]);
    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingUser) {
        // Kullanıcı zaten var, giriş yap
        if (empty($existingUser['google_id'])) {
            // Google ID'yi güncelle
            $stmt = $pdo->prepare("UPDATE users SET google_id = ? WHERE id = ?");
            $stmt->execute([$googleId, $existingUser['id']]);
        }
        
        // Session başlat
        $_SESSION['user_id'] = $existingUser['id'];
        $_SESSION['user_type'] = $existingUser['user_type'];
        $_SESSION['is_logged_in'] = true;
        
        header('Location: ../index.php?login=success');
        exit();
        
    } else {
        // Yeni kullanıcı
        if ($state === 'register') {
            // Kayıt işlemi
            $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, google_id, user_type, email_verified, created_at) VALUES (?, ?, ?, ?, 'customer', 1, NOW())");
            $stmt->execute([$firstName, $lastName, $email, $googleId]);
            
            $userId = $pdo->lastInsertId();
            
            // Session başlat
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_type'] = 'customer';
            $_SESSION['is_logged_in'] = true;
            
            header('Location: ../index.php?register=success');
            exit();
            
        } else {
            // Giriş yapmaya çalışıyor ama hesap yok
            header('Location: ../index.php?error=account_not_found');
            exit();
        }
    }
    
} catch (Exception $e) {
    error_log('Google OAuth Error: ' . $e->getMessage());
    header('Location: ../index.php?error=oauth_error');
    exit();
}
?>