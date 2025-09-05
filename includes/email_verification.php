<?php
require_once __DIR__ . '/../config/database.php';

// PHPMailer kütüphanesini dahil et
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php')) {
    // Manuel include
    require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
    require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';
    require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
} else {
    define('USE_PHP_MAIL', true);
}

// Use statements
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class EmailVerification {
    private $db;
    private $smtpHost;
    private $smtpPort;
    private $smtpUsername;
    private $smtpPassword;
    private $fromEmail;
    private $fromName;
    
    public function __construct($database = null) {
        // Veritabanı bağlantısını ayarla
        if ($database) {
            $this->db = $database;
        } else {
            global $pdo;
            if (!$pdo) {
                require_once __DIR__ . '/../config/database.php';
                $database = new Database();
                $pdo = $database->getConnection();
            }
            $this->db = $pdo;
        }
        
        // Veritabanından e-posta ayarlarını yükle
        $this->loadSettings();
    }
    
    private function loadSettings() {
        try {
            $stmt = $this->db->prepare("SELECT setting_key, setting_value FROM site_settings WHERE setting_key LIKE 'email_%'");
            $stmt->execute();
            $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            $this->smtpHost = $settings['email_smtp_host'] ?? 'smtp.gmail.com';
            // Varsayılanı 587 yap, değeri int'e çevir
            $this->smtpPort = isset($settings['email_smtp_port']) ? (int)$settings['email_smtp_port'] : 587;
            $this->smtpUsername = $settings['email_smtp_username'] ?? '';
            $this->smtpPassword = $settings['email_smtp_password'] ?? '';
            $this->fromEmail = $settings['email_from_address'] ?? 'no-reply@biletjack.com';
            $this->fromName = $settings['email_from_name'] ?? 'BiletJack';
            
        } catch (Exception $e) {
            error_log("E-posta ayarları yüklenemedi: " . $e->getMessage());
        }
    }
    
    /**
     * Doğrulama tokeni oluştur ve e-posta gönder
     */
    public function sendVerificationEmail($userId, $email, $firstName, $lastName) {
        try {
            // Doğrulama tokeni oluştur
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            // Veritabanına kaydet
            $stmt = $this->db->prepare("
                INSERT INTO email_verifications (user_id, email, verification_token, expires_at) 
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                verification_token = VALUES(verification_token),
                expires_at = VALUES(expires_at),
                verified_at = NULL
            ");
            
            $stmt->execute([$userId, $email, $token, $expiresAt]);
            
            // E-posta gönder
            return $this->sendTokenEmail($email, $firstName, $lastName, $token);
            
        } catch (Exception $e) {
            error_log("Email verification error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Token ile e-posta gönderme fonksiyonu
     */
    private function sendTokenEmail($email, $firstName, $lastName, $token) {
        // Doğrulama URL'si oluştur - dinamik host kullan
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        // Localhost için özel yol, diğerleri için normal yol
        if (strpos($host, 'localhost') !== false) {
            $verificationUrl = $protocol . '://' . $host . '/Biletjack/auth/verify_email.php?token=' . $token;
        } else {
            $verificationUrl = $protocol . '://' . $host . '/auth/verify_email.php?token=' . $token;
        }
        
        $subject = 'BiletJack - E-posta Adresinizi Doğrulayın';
        $htmlBody = $this->getTokenEmailTemplate($firstName, $verificationUrl);
        $textBody = "Merhaba {$firstName},\n\nBiletJack hesabınızı aktifleştirmek için aşağıdaki linke tıklayın:\n{$verificationUrl}\n\nBu link 24 saat geçerlidir.";
        
        return $this->sendEmail($email, $subject, $htmlBody, $textBody);
    }
    
    /**
     * Token e-posta şablonu
     */
    private function getTokenEmailTemplate($firstName, $verificationUrl) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>BiletJack E-posta Doğrulama</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f4f4f4; }
                .container { max-width: 600px; margin: 0 auto; background-color: white; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
                .content { padding: 40px 30px; }
                .button { display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px; margin: 20px 0; font-weight: bold; }
                .footer { background-color: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 12px; }
                .url-box { background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px; padding: 15px; margin: 20px 0; word-break: break-all; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🎫 BiletJack'e Hoş Geldiniz!</h1>
                </div>
                <div class='content'>
                    <h2>Merhaba {$firstName},</h2>
                    <p>BiletJack'e kaydolduğunuz için teşekkür ederiz! Hesabınızı aktifleştirmek için e-posta adresinizi doğrulamanız gerekmektedir.</p>
                    <p>Aşağıdaki butona tıklayarak e-posta adresinizi doğrulayabilirsiniz:</p>
                    <div style='text-align: center;'>
                        <a href='{$verificationUrl}' class='button'>E-posta Adresimi Doğrula</a>
                    </div>
                    <p>Bu link 24 saat geçerlidir. Eğer butona tıklayamıyorsanız, aşağıdaki linki tarayıcınıza kopyalayabilirsiniz:</p>
                    <div class='url-box'>{$verificationUrl}</div>
                    <p>Eğer bu kaydı siz yapmadıysanız, bu e-postayı görmezden gelebilirsiniz.</p>
                    <p>İyi günler dileriz,<br><strong>BiletJack Ekibi</strong></p>
                </div>
                <div class='footer'>
                    <p>Bu e-posta otomatik olarak gönderilmiştir. Lütfen yanıtlamayın.</p>
                    <p>© 2024 BiletJack. Tüm hakları saklıdır.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Doğrulama tokenini kontrol et
     */
    public function verifyToken($token) {
        try {
            $stmt = $this->db->prepare("
                SELECT ev.*, u.id as user_id, u.email, u.first_name, u.last_name 
                FROM email_verifications ev 
                JOIN users u ON ev.user_id = u.id 
                WHERE ev.verification_token = ? 
                AND ev.expires_at > NOW() 
                AND ev.verified_at IS NULL
            ");
            
            $stmt->execute([$token]);
            $verification = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($verification) {
                // Doğrulama işlemini tamamla
                $updateStmt = $this->db->prepare("
                    UPDATE email_verifications 
                    SET verified_at = NOW() 
                    WHERE verification_token = ?
                ");
                $updateStmt->execute([$token]);
                
                // Kullanıcının email_verified durumunu güncelle
                $userStmt = $this->db->prepare("
                    UPDATE users 
                    SET email_verified = TRUE, status = 'active' 
                    WHERE id = ?
                ");
                $userStmt->execute([$verification['user_id']]);
                
                return $verification;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Token verification error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Doğrulama durumunu kontrol et
     */
    public function isEmailVerified($userId) {
        try {
            $stmt = $this->db->prepare("SELECT email_verified FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ? (bool)$result['email_verified'] : false;
            
        } catch (Exception $e) {
            error_log("Email verification check error: " . $e->getMessage());
            return false;
        }
    }
    
    public function sendVerificationCode($email, $code) {
        // SMTP ayarları kontrol et
        if (empty($this->smtpUsername) || empty($this->smtpPassword)) {
            // Geliştirme modu - konsola yazdır
            error_log("E-posta SMTP ayarları eksik. Geliştirme modu aktif.");
            error_log("E-posta Doğrulama kodu: {$code} - E-posta: {$email}");
            return ['success' => true, 'message' => 'E-posta gönderildi (geliştirme modu)'];
        }
        
        $subject = 'BiletJack - Doğrulama Kodu';
        $htmlBody = $this->getEmailTemplate($code);
        $textBody = "BiletJack Doğrulama Kodu: {$code}\n\nBu kodu kimseyle paylaşmayın.";
        
        return $this->sendEmail($email, $subject, $htmlBody, $textBody);
    }
    
    private function getEmailTemplate($code) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>BiletJack Doğrulama Kodu</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f4f4f4; }
                .container { max-width: 600px; margin: 0 auto; background-color: white; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
                .content { padding: 40px 30px; }
                .code-box { background-color: #f8f9fa; border: 2px dashed #667eea; border-radius: 10px; padding: 20px; text-align: center; margin: 20px 0; }
                .code { font-size: 32px; font-weight: bold; color: #667eea; letter-spacing: 5px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🔐 E-posta Doğrulama Kodu</h1>
                </div>
                <div class='content'>
                    <p>Merhaba,</p>
                    <p>Giriş işleminizi tamamlamak için aşağıdaki doğrulama kodunu kullanın:</p>
                    <div class='code-box'>
                        <div class='code'>{$code}</div>
                    </div>
                    <p>Bu kod 10 dakika geçerlidir.</p>
                    <p>İyi günler dileriz,<br><strong>BiletJack Ekibi</strong></p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    private function sendEmail($to, $subject, $htmlBody, $textBody) {
        try {
            // PHPMailer kullanmadan basit mail() fonksiyonu ile
            // Üretim ortamında PHPMailer kullanılması önerilir
            
            $headers = [
                'MIME-Version: 1.0',
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . $this->fromName . ' <' . $this->fromEmail . '>',
                'Reply-To: ' . $this->fromEmail,
                'X-Mailer: PHP/' . phpversion()
            ];
            
            $success = mail($to, $subject, $htmlBody, implode("\r\n", $headers));
            
            if ($success) {
                return ['success' => true, 'message' => 'E-posta başarıyla gönderildi'];
            } else {
                return ['success' => false, 'message' => 'E-posta gönderilemedi'];
            }
            
        } catch (Exception $e) {
            error_log("E-posta gönderme hatası: " . $e->getMessage());
            return ['success' => false, 'message' => 'E-posta gönderme hatası: ' . $e->getMessage()];
        }
    }
    
    public function sendSMTPEmail($to, $subject, $htmlBody, $textBody) {
        // PHPMailer mevcut değilse veya SMTP kimlik bilgileri yoksa PHP mail() kullan
        if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer') || defined('USE_PHP_MAIL')) {
            return $this->sendEmail($to, $subject, $htmlBody, $textBody);
        }
        if (empty($this->smtpUsername) || empty($this->smtpPassword)) {
            return $this->sendEmail($to, $subject, $htmlBody, $textBody);
        }
        
        $mail = null;
        try {
            $mail = new PHPMailer(true);
            
            // SMTP ayarları
            $mail->isSMTP();
            $mail->Host = $this->smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtpUsername;
            $mail->Password = $this->smtpPassword;

            // Port'a göre şifreleme seçimi
            $port = (int)$this->smtpPort;
            if ($port === 465) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL (Implicit TLS)
            } elseif ($port === 587) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // STARTTLS
            } elseif ($port === 25) {
                $mail->SMTPSecure = ''; // Düz SMTP
            } else {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Varsayılan dene
            }
            $mail->Port = $port;

            $mail->CharSet = 'UTF-8';
            $mail->Timeout = 20;
            $mail->SMTPKeepAlive = false;
            $mail->SMTPDebug = 0;
            $mail->Debugoutput = function ($str, $level) {
                error_log("SMTP DEBUG[$level]: " . $str);
            };
            
            // Gönderen ve alıcı
            $mail->setFrom($this->fromEmail, $this->fromName);
            $mail->addAddress($to);
            
            // İçerik
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = $textBody;
            
            $mail->send();
            return ['success' => true, 'message' => 'E-posta başarıyla gönderildi (SMTP)'];
            
        } catch (Exception $e) {
            $errorInfo = ($mail && property_exists($mail, 'ErrorInfo')) ? $mail->ErrorInfo : '';
            error_log("SMTP e-posta hatası: " . $e->getMessage() . ($errorInfo ? " | ErrorInfo=" . $errorInfo : ""));
            return ['success' => false, 'message' => 'SMTP e-posta hatası: ' . $e->getMessage()];
        }
    }
}