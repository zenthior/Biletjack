<?php
require_once '../config/database.php';
require_once '../includes/email_verification.php';
require_once '../includes/session.php';

$database = new Database();
$db = $database->getConnection();
$emailVerification = new EmailVerification($db);

$message = '';
$messageType = '';
$verified = false;

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    $verification = $emailVerification->verifyToken($token);
    
    if ($verification) {
        $verified = true;
        $message = 'E-posta adresiniz başarıyla doğrulandı! Hesabınız aktifleştirildi.';
        $messageType = 'success';
        
        // Kullanıcıyı otomatik olarak giriş yap
        startUserSession([
            'id' => $verification['user_id'],
            'email' => $verification['email'],
            'first_name' => $verification['first_name'],
            'last_name' => $verification['last_name'],
            'user_type' => 'customer', // Varsayılan olarak customer
            'status' => 'active'
        ]);
        
    } else {
        $message = 'Doğrulama linki geçersiz veya süresi dolmuş. Lütfen yeni bir doğrulama e-postası talep edin.';
        $messageType = 'error';
    }
} else {
    $message = 'Geçersiz doğrulama linki.';
    $messageType = 'error';
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-posta Doğrulama - BiletJack</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .verification-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        
        .icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }
        
        .success .icon {
            color: #28a745;
        }
        
        .error .icon {
            color: #dc3545;
        }
        
        h1 {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.8rem;
        }
        
        .message {
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
            font-size: 1.1rem;
        }
        
        .success .message {
            color: #155724;
        }
        
        .error .message {
            color: #721c24;
        }
        
        .buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 25px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn-secondary {
            background: #f8f9fa;
            color: #333;
            border: 2px solid #dee2e6;
        }
        
        .btn-secondary:hover {
            background: #e9ecef;
            transform: translateY(-2px);
        }
        
        .logo {
            margin-bottom: 30px;
        }
        
        .logo h2 {
            color: #667eea;
            font-size: 2rem;
            font-weight: bold;
        }
        
        @media (max-width: 480px) {
            .verification-container {
                padding: 30px 20px;
            }
            
            .buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="verification-container <?php echo $messageType; ?>">
        <div class="logo">
            <h2>🎫 BiletJack</h2>
        </div>
        
        <div class="icon">
            <?php if ($verified): ?>
                ✅
            <?php else: ?>
                ❌
            <?php endif; ?>
        </div>
        
        <h1>
            <?php if ($verified): ?>
                E-posta Doğrulandı!
            <?php else: ?>
                Doğrulama Başarısız
            <?php endif; ?>
        </h1>
        
        <div class="message">
            <?php echo htmlspecialchars($message); ?>
        </div>
        
        <div class="buttons">
            <?php if ($verified): ?>
                <a href="/index.php" class="btn btn-primary">Ana Sayfaya Git</a>
                <a href="/customer/profile.php" class="btn btn-secondary">Profilim</a>
            <?php else: ?>
                <a href="/index.php" class="btn btn-primary">Ana Sayfaya Git</a>
                <a href="/auth/login-form.php" class="btn btn-secondary">Giriş Yap</a>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($verified): ?>
    <script>
        // 3 saniye sonra ana sayfaya yönlendir
        setTimeout(function() {
            window.location.href = '/index.php';
        }, 3000);
    </script>
    <?php endif; ?>
</body>
</html>