<?php
require_once '../config/database.php';
require_once '../includes/session.php';

// Admin kontrolü
requireAdmin();

try {
    // Database.php dosyasından global $pdo değişkenini kullan
    if (!isset($pdo)) {
        echo "❌ Veritabanı bağlantısı kurulamadı.";
        exit;
    }
    

    
    // E-posta doğrulama tablosu
    $createEmailTable = "
        CREATE TABLE IF NOT EXISTS email_verifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            code VARCHAR(6) NOT NULL,
            token VARCHAR(64) NOT NULL,
            used BOOLEAN DEFAULT FALSE,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_token (token),
            INDEX idx_expires (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    // Tabloları oluştur

    
    $pdo->exec($createEmailTable);
    echo "✅ E-posta doğrulama tablosu oluşturuldu/kontrol edildi.<br>";
    
    // Site ayarları tablosuna yeni alanları ekle (eğer yoksa)
    $alterSettingsTable = "
        ALTER TABLE site_settings 
        ADD COLUMN IF NOT EXISTS setting_description TEXT AFTER setting_value
    ";
    
    try {
        $pdo->exec($alterSettingsTable);
        echo "✅ Site ayarları tablosu güncellendi.<br>";
    } catch (Exception $e) {
        // Sütun zaten varsa hata vermez
        echo "ℹ️ Site ayarları tablosu zaten güncel.<br>";
    }
    
    // Varsayılan ayarları ekle
    $defaultSettings = [

        ['email_from_address', 'noreply@biletjack.com', 'E-posta gönderen adresi'],
        ['email_from_name', 'BiletJack', 'E-posta gönderen adı'],
        ['email_smtp_host', 'smtp.gmail.com', 'SMTP sunucu adresi'],
        ['email_smtp_port', '587', 'SMTP port numarası'],
        ['email_smtp_username', '', 'SMTP kullanıcı adı'],
        ['email_smtp_password', '', 'SMTP şifresi']
    ];
    
    foreach ($defaultSettings as $setting) {
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO site_settings (setting_key, setting_value, setting_description) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute($setting);
    }
    
    echo "✅ Varsayılan ayarlar eklendi.<br>";
    
    echo "<br><strong>🎉 Tüm alternatif doğrulama tabloları başarıyla oluşturuldu!</strong><br>";
    echo "<br>Artık aşağıdaki doğrulama yöntemlerini kullanabilirsiniz:<br>";
    echo "• WhatsApp API (mevcut)<br>";
    echo "• SMS API (NetGSM, İletim Merkezi, Twilio)<br>";
    echo "• E-posta Doğrulama (SMTP veya PHP mail)<br>";
    
    echo "<br><br><a href='settings.php'>⚙️ Ayarlar Sayfasına Git</a> | ";
    echo "<a href='index.php'>🏠 Ana Sayfaya Dön</a>";
    
} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage();
}
?>