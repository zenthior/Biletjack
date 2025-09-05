<?php
session_start();
require_once '../config/database.php';
require_once '../includes/password_utils.php';

$error = '';

// Zaten giriş yapmışsa ana sayfaya yönlendir
if (isset($_SESSION['qr_staff_id'])) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Kullanıcı adı ve şifre gereklidir.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM qr_staff WHERE username = ? AND status = 'active'");
        $stmt->execute([$username]);
        $staff = $stmt->fetch();
        
        if ($staff && password_verify($password, $staff['password'])) {

            // Rehash gerekiyorsa Argon2(id/i)'ye yükselt
            if (bj_password_needs_rehash($staff['password'])) {
                $newHash = bj_hash_password($password);
                $upd = $pdo->prepare("UPDATE qr_staff SET password = :password WHERE id = :id");
                $upd->execute([':password' => $newHash, ':id' => $staff['id']]);
            }

            $_SESSION['qr_staff_id'] = $staff['id'];
            $_SESSION['qr_staff_username'] = $staff['username'];
            $_SESSION['qr_staff_name'] = $staff['full_name'];
            $_SESSION['qr_organizer_id'] = $staff['organizer_id'];
            header('Location: index.php');
            exit();
        } else {
            $error = 'Geçersiz kullanıcı adı veya şifre.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Panel - Giriş</title>
    
    <!-- Favicon -->
    <?php
    // Favicon ayarını veritabanından al
    try {
        require_once '../config/database.php';
        $database = new Database();
        $pdo = $database->getConnection();
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'site_favicon'");
        $stmt->execute();
        $faviconSetting = $stmt->fetchColumn();
        $faviconPath = $faviconSetting ? '../assets/images/' . $faviconSetting : '../assets/images/favicon.ico';
    } catch (Exception $e) {
        $faviconPath = '../assets/images/favicon.ico';
    }
    ?>
    <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars($faviconPath); ?>">
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../css/organizer2.css" rel="stylesheet">
</head>
<body class="qr-login-body">
    <div class="qr-login-container">
        <div class="qr-login-card">
            <div class="qr-login-header">
                <div class="qr-logo">
                    <i class="fas fa-qrcode"></i>
                </div>
                <h1>QR Panel</h1>
                <p>Bilet doğrulama sistemi</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="qr-alert qr-alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="qr-login-form">
                <div class="qr-form-group">
                    <label for="username">
                        <i class="fas fa-user"></i> Kullanıcı Adı
                    </label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="qr-form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Şifre
                    </label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="qr-btn qr-btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Giriş Yap
                </button>
            </form>
            
            <div class="qr-login-footer">
                <a href="../organizer/index.php" class="qr-btn qr-btn-secondary" style="margin-bottom: 15px;">
                    <i class="fas fa-arrow-left"></i> Organizatör Paneline Dön
                </a>
                <p>© 2025 BiletJack - QR Panel</p>
                <small>Sadece yetkili personel erişebilir</small>
            </div>
        </div>
    </div>
</body>
</html>