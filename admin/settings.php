<?php
require_once '../includes/session.php';
require_once '../config/database.php';

// Admin kontrolü
requireAdmin();

// Database bağlantısını oluştur
$database = new Database();
$pdo = $database->getConnection();

$message = '';
$messageType = '';

// Favicon yükleme işlemi
if ($_POST && isset($_POST['upload_favicon'])) {
    try {
        // Dosya yüklenip yüklenmediğini kontrol et
        if (!isset($_FILES['favicon']) || empty($_FILES['favicon']['name'])) {
            throw new Exception('Lütfen bir favicon dosyası seçin.');
        }
        
        // Dosya yükleme hatalarını kontrol et
        if ($_FILES['favicon']['error'] !== UPLOAD_ERR_OK) {
            switch ($_FILES['favicon']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    throw new Exception('Dosya çok büyük. Maksimum 1MB olmalıdır.');
                case UPLOAD_ERR_PARTIAL:
                    throw new Exception('Dosya kısmen yüklendi. Lütfen tekrar deneyin.');
                case UPLOAD_ERR_NO_FILE:
                    throw new Exception('Lütfen bir dosya seçin.');
                case UPLOAD_ERR_NO_TMP_DIR:
                    throw new Exception('Geçici dizin bulunamadı.');
                case UPLOAD_ERR_CANT_WRITE:
                    throw new Exception('Dosya yazılamadı.');
                default:
                    throw new Exception('Dosya yükleme hatası oluştu.');
            }
        }
        
        $uploadDir = '../assets/images/';
        
        // Upload dizinini oluştur
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $allowedTypes = ['image/x-icon', 'image/vnd.microsoft.icon', 'image/ico', 'image/icon', 'text/ico', 'application/ico', 'application/x-ico'];
        $allowedExtensions = ['ico'];
        
        $fileType = $_FILES['favicon']['type'];
        $fileName = $_FILES['favicon']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Dosya uzantısı kontrolü
        if (!in_array($fileExtension, $allowedExtensions)) {
            throw new Exception('Sadece .ico dosyaları yüklenebilir. Seçilen dosya: .' . $fileExtension);
        }
        
        // Dosya boyutu kontrolü (max 1MB)
        if ($_FILES['favicon']['size'] > 1024 * 1024) {
            throw new Exception('Favicon dosyası 1MB\'dan büyük olamaz. Dosya boyutu: ' . round($_FILES['favicon']['size'] / 1024) . 'KB');
        }
        
        // Dosya içeriğini kontrol et (basit ico header kontrolü)
        $fileContent = file_get_contents($_FILES['favicon']['tmp_name']);
        if (substr($fileContent, 0, 4) !== "\x00\x00\x01\x00") {
            throw new Exception('Geçersiz ICO dosyası formatı.');
        }
        
        // Eski favicon'u sil
        $oldFavicon = glob($uploadDir . 'favicon.*');
        foreach ($oldFavicon as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        
        // Yeni favicon'u kaydet
        $newFileName = 'favicon.ico';
        $uploadPath = $uploadDir . $newFileName;
        
        if (move_uploaded_file($_FILES['favicon']['tmp_name'], $uploadPath)) {
            // Dosya izinlerini ayarla
            chmod($uploadPath, 0644);
            
            // Veritabanına kaydet
            $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute(['site_favicon', $newFileName, $newFileName]);
            
            $message = 'Favicon başarıyla yüklendi!';
            $messageType = 'success';
        } else {
            throw new Exception('Favicon dosyası sunucuya kaydedilemedi.');
        }
    } catch (Exception $e) {
        $message = 'Favicon yüklenirken hata: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Ayarları kaydet
if ($_POST && isset($_POST['save_settings'])) {
    try {
        $settings = [
            'site_name' => $_POST['site_name'] ?? '',
            'site_description' => $_POST['site_description'] ?? '',
            'site_email' => $_POST['site_email'] ?? '',
            'site_phone' => $_POST['site_phone'] ?? '',
            'site_address' => $_POST['site_address'] ?? '',
            'google_maps_api_key' => $_POST['google_maps_api_key'] ?? '',
            'google_analytics_id' => $_POST['google_analytics_id'] ?? '',
            'google_oauth_client_id' => $_POST['google_oauth_client_id'] ?? '',
            'google_oauth_client_secret' => $_POST['google_oauth_client_secret'] ?? '',
            'whatsapp_api_token' => $_POST['whatsapp_api_token'] ?? '',
            'whatsapp_phone_number_id' => $_POST['whatsapp_phone_number_id'] ?? '',
            'whatsapp_verify_token' => $_POST['whatsapp_verify_token'] ?? '',

            // E-posta Doğrulama Ayarları
            'email_from_address' => $_POST['email_from_address'] ?? 'noreply@biletjack.com',
            'email_from_name' => $_POST['email_from_name'] ?? 'BiletJack',
            'email_smtp_host' => $_POST['email_smtp_host'] ?? 'smtp.gmail.com',
            'email_smtp_port' => $_POST['email_smtp_port'] ?? '587',
            'email_smtp_username' => $_POST['email_smtp_username'] ?? '',
            'email_smtp_password' => $_POST['email_smtp_password'] ?? '',
            'facebook_url' => $_POST['facebook_url'] ?? '',
            'instagram_url' => $_POST['instagram_url'] ?? '',
            'twitter_url' => $_POST['twitter_url'] ?? '',
            'commission_rate' => $_POST['commission_rate'] ?? '5',
            'currency' => $_POST['currency'] ?? 'TRY',
            'timezone' => $_POST['timezone'] ?? 'Europe/Istanbul',
            'maintenance_mode' => isset($_POST['maintenance_mode']) ? 1 : 0,
            'email_notifications' => isset($_POST['email_notifications']) ? 1 : 0,
            'sms_notifications' => isset($_POST['sms_notifications']) ? 1 : 0
        ];
        
        // Ayarları veritabanına kaydet
        foreach ($settings as $key => $value) {
            $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$key, $value, $value]);
        }
        
        $message = 'Ayarlar başarıyla kaydedildi.';
        $messageType = 'success';
    } catch (Exception $e) {
        $message = 'Ayarlar kaydedilirken hata oluştu: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Mevcut ayarları getir
$currentSettings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $currentSettings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    // Tablo yoksa oluştur
    $createTable = "
        CREATE TABLE IF NOT EXISTS site_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(255) UNIQUE NOT NULL,
            setting_value TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ";
    $pdo->exec($createTable);
}

include 'includes/header.php';
?>

<div class="admin-container">
    <!-- Sidebar -->
    <div class="admin-sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <img src="../uploads/logo.png" alt="BiletJack Logo" style="width: 120px; height: 120px; object-fit: contain;">
            </div>
            <h2 class="sidebar-title">Ayarlar</h2>
            <p class="sidebar-subtitle">Admin Dashboard</p>
        </div>
        
        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">Ana Menü</div>
                <a href="index.php" class="nav-item">
                    <i class="fas fa-chart-pie"></i>    
                    Gösterge Paneli
                </a>
                <a href="analytics.php" class="nav-item">
                    <i class="fas fa-chart-line"></i>
                    Analytics
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Yönetim</div>
                <a href="users.php" class="nav-item">
                    <i class="fas fa-users"></i>
                    Kullanıcılar
                </a>
                <a href="organizers.php" class="nav-item">
                    <i class="fas fa-building"></i>
                    Organizatörler
                </a>
                <a href="events.php" class="nav-item">
                    <i class="fas fa-calendar-alt"></i>
                    Etkinlikler
                </a>
                <a href="orders.php" class="nav-item">
                    <i class="fas fa-shopping-cart"></i>
                    Siparişler
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Sistem</div>
                <a href="settings.php" class="nav-item active">
                    <i class="fas fa-cog"></i>
                    Ayarlar
                </a>
                <a href="reports.php" class="nav-item">
                    <i class="fas fa-file-alt"></i>
                    Raporlar
                </a>
                <a href="../index.php" class="nav-item">
                    <i class="fas fa-home"></i>
                    Ana Sayfa
                </a>
            </div>
        </nav>
    </div>
    
    <div class="admin-content">
        <div class="content-header">
            <button class="mobile-menu-toggle">
                <i class="fas fa-bars"></i>
            </button>
            <h1>Site Ayarları</h1>
            <div class="header-right">
                <button class="header-notifications">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge"></span>
                </button>

                <a href="../auth/logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    Çıkış
                </a>
            </div>
        </div>        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="settings-container">
            <!-- Favicon Yönetimi -->
            <div class="settings-section">
                <h3><i class="fas fa-image"></i> Favicon Yönetimi</h3>
                <div class="favicon-section">
                    <div class="current-favicon">
                        <h4>Mevcut Favicon</h4>
                        <?php 
                        $currentFavicon = $currentSettings['site_favicon'] ?? 'favicon.ico';
                        $faviconPath = '../assets/images/' . $currentFavicon;
                        if (file_exists($faviconPath)): 
                        ?>
                            <div class="favicon-preview">
                                <img src="../assets/images/<?php echo htmlspecialchars($currentFavicon); ?>" alt="Current Favicon" style="width: 32px; height: 32px;">
                                <span>favicon.ico</span>
                            </div>
                        <?php else: ?>
                            <div class="no-favicon">
                                <i class="fas fa-image"></i>
                                <span>Favicon yüklenmemiş</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <form method="POST" enctype="multipart/form-data" class="favicon-upload-form">
                        <div class="form-group">
                            <label for="favicon">Yeni Favicon Yükle (.ico dosyası)</label>
                            <input type="file" id="favicon" name="favicon" accept=".ico" required>
                            <small class="form-help">
                                <strong>Favicon Gereksinimleri:</strong><br>
                                • Dosya formatı: .ico<br>
                                • Maksimum boyut: 1MB<br>
                                • Önerilen boyutlar: 16x16, 32x32, 48x48 piksel<br>
                                • Online favicon oluşturucu: <a href="https://favicon.io/" target="_blank">favicon.io</a>
                            </small>
                        </div>
                        <button type="submit" name="upload_favicon" class="btn btn-secondary">
                            <i class="fas fa-upload"></i>
                            Favicon Yükle
                        </button>
                    </form>
                </div>
            </div>
            
            <form method="POST" class="settings-form">
                
                <!-- Genel Ayarlar -->
                <div class="settings-section">
                    <h3><i class="fas fa-globe"></i> Genel Ayarlar</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="site_name">Site Adı</label>
                            <input type="text" id="site_name" name="site_name" value="<?php echo htmlspecialchars($currentSettings['site_name'] ?? 'BiletJack'); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="site_email">Site E-posta</label>
                            <input type="email" id="site_email" name="site_email" value="<?php echo htmlspecialchars($currentSettings['site_email'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="site_phone">Site Telefon</label>
                            <input type="tel" id="site_phone" name="site_phone" value="<?php echo htmlspecialchars($currentSettings['site_phone'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="currency">Para Birimi</label>
                            <select id="currency" name="currency">
                                <option value="TRY" <?php echo ($currentSettings['currency'] ?? 'TRY') === 'TRY' ? 'selected' : ''; ?>>Türk Lirası (₺)</option>
                                <option value="USD" <?php echo ($currentSettings['currency'] ?? '') === 'USD' ? 'selected' : ''; ?>>Dolar ($)</option>
                                <option value="EUR" <?php echo ($currentSettings['currency'] ?? '') === 'EUR' ? 'selected' : ''; ?>>Euro (€)</option>
                            </select>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="site_description">Site Açıklaması</label>
                            <textarea id="site_description" name="site_description" rows="3"><?php echo htmlspecialchars($currentSettings['site_description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="site_address">Site Adresi</label>
                            <textarea id="site_address" name="site_address" rows="2"><?php echo htmlspecialchars($currentSettings['site_address'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- API Ayarları -->
                <div class="settings-section">
                    <h3><i class="fas fa-key"></i> API Ayarları</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="google_maps_api_key">Google Maps API Key</label>
                            <input type="text" id="google_maps_api_key" name="google_maps_api_key" value="<?php echo htmlspecialchars($currentSettings['google_maps_api_key'] ?? ''); ?>" placeholder="AIzaSy...">
                            <small class="form-help">
                                <strong>Etkinlik detay sayfalarında harita gösterimi için gereklidir.</strong><br>
                                <strong>Kurulum Adımları:</strong><br>
                                1. <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a>'a gidin<br>
                                2. Yeni proje oluşturun veya mevcut projeyi seçin<br>
                                3. "APIs & Services" > "Library" bölümünden "Maps Embed API"'yi etkinleştirin<br>
                                4. "Credentials" bölümünden API anahtarı oluşturun<br>
                                5. API anahtarını buraya yapıştırın<br>
                                <strong>Not:</strong> API anahtarınızın "Maps Embed API" yetkisine sahip olduğundan emin olun.
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label for="google_analytics_id">Google Analytics ID</label>
                            <input type="text" id="google_analytics_id" name="google_analytics_id" value="<?php echo htmlspecialchars($currentSettings['google_analytics_id'] ?? ''); ?>" placeholder="G-XXXXXXXXXX">
                        </div>
                        
                        <div class="form-group">
                            <label for="google_oauth_client_id">Google OAuth Client ID</label>
                            <input type="text" id="google_oauth_client_id" name="google_oauth_client_id" value="<?php echo htmlspecialchars($currentSettings['google_oauth_client_id'] ?? ''); ?>" placeholder="123456789-abcdefg.apps.googleusercontent.com">
                            <small class="form-help">
                                <strong>Google ile giriş yapmak için gereklidir.</strong><br>
                                <strong>Kurulum Adımları:</strong><br>
                                1. <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a>'a gidin<br>
                                2. Yeni proje oluşturun veya mevcut projeyi seçin<br>
                                3. "APIs & Services" > "Library" bölümünden "Google+ API"'yi etkinleştirin<br>
                                4. "Credentials" bölümünden "OAuth 2.0 Client IDs" oluşturun<br>
                                5. "Web application" seçin ve authorized redirect URIs'e sitenizi ekleyin<br>
                                6. Client ID'yi buraya yapıştırın
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label for="google_oauth_client_secret">Google OAuth Client Secret</label>
                            <input type="password" id="google_oauth_client_secret" name="google_oauth_client_secret" value="<?php echo htmlspecialchars($currentSettings['google_oauth_client_secret'] ?? ''); ?>" placeholder="GOCSPX-...">
                            <small class="form-help">
                                <strong>Google OAuth Client ID ile birlikte kullanılır.</strong><br>
                                Google Cloud Console'dan aldığınız Client Secret'ı buraya yapıştırın.
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label for="whatsapp_api_token">WhatsApp API Token</label>
                            <input type="password" id="whatsapp_api_token" name="whatsapp_api_token" value="<?php echo htmlspecialchars($currentSettings['whatsapp_api_token'] ?? ''); ?>" placeholder="EAABsBCS...">
                            <small class="form-help">
                                <strong>WhatsApp ile giriş/kayıt için doğrulama kodları göndermek için gereklidir.</strong><br>
                                <strong>Kurulum Adımları:</strong><br>
                                1. <a href="https://developers.facebook.com/" target="_blank">Facebook Developers</a>'a gidin<br>
                                2. Yeni uygulama oluşturun ve "WhatsApp" ürününü ekleyin<br>
                                3. WhatsApp Business API'yi yapılandırın<br>
                                4. Geçici erişim token'ını buraya yapıştırın<br>
                                <strong>Not:</strong> Üretim için kalıcı token almanız gerekir.
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label for="whatsapp_phone_number_id">WhatsApp Telefon Numarası ID</label>
                            <input type="text" id="whatsapp_phone_number_id" name="whatsapp_phone_number_id" value="<?php echo htmlspecialchars($currentSettings['whatsapp_phone_number_id'] ?? ''); ?>" placeholder="123456789012345">
                            <small class="form-help">
                                <strong>WhatsApp Business API'den aldığınız telefon numarası ID'si.</strong><br>
                                Facebook Developers panelinden WhatsApp > API Setup bölümünde bulabilirsiniz.
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label for="whatsapp_verify_token">WhatsApp Webhook Verify Token</label>
                            <input type="text" id="whatsapp_verify_token" name="whatsapp_verify_token" value="<?php echo htmlspecialchars($currentSettings['whatsapp_verify_token'] ?? ''); ?>" placeholder="my_verify_token_123">
                            <small class="form-help">
                                <strong>Webhook doğrulama için kullanılır.</strong><br>
                                Güvenli bir token oluşturun ve Facebook Developers panelinde webhook kurulumunda kullanın.
                            </small>
                        </div>
                    </div>
                </div>
                

                
                <!-- E-posta Doğrulama Ayarları -->
                <div class="settings-section">
                    <h3><i class="fas fa-envelope"></i> E-posta Doğrulama Ayarları</h3>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>WhatsApp/SMS API'ye Alternatif:</strong> E-posta ile doğrulama kodu gönderebilirsiniz. SMTP ayarları isteğe bağlıdır.
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="email_from_address">Gönderen E-posta Adresi</label>
                            <input type="email" id="email_from_address" name="email_from_address" value="<?php echo htmlspecialchars($currentSettings['email_from_address'] ?? 'noreply@biletjack.com'); ?>" placeholder="noreply@biletjack.com">
                            <small class="form-help">
                                Doğrulama e-postalarının gönderileceği adres.
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label for="email_from_name">Gönderen Adı</label>
                            <input type="text" id="email_from_name" name="email_from_name" value="<?php echo htmlspecialchars($currentSettings['email_from_name'] ?? 'BiletJack'); ?>" placeholder="BiletJack">
                            <small class="form-help">
                                E-postalarda görünecek gönderen adı.
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label for="email_smtp_host">SMTP Host (İsteğe Bağlı)</label>
                            <input type="text" id="email_smtp_host" name="email_smtp_host" value="<?php echo htmlspecialchars($currentSettings['email_smtp_host'] ?? 'smtp.gmail.com'); ?>" placeholder="smtp.gmail.com">
                            <small class="form-help">
                                SMTP sunucu adresi. Boş bırakılırsa PHP mail() fonksiyonu kullanılır.
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label for="email_smtp_port">SMTP Port</label>
                            <input type="number" id="email_smtp_port" name="email_smtp_port" value="<?php echo htmlspecialchars($currentSettings['email_smtp_port'] ?? '587'); ?>" placeholder="587">
                            <small class="form-help">
                                SMTP port numarası (genellikle 587 veya 465).
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label for="email_smtp_username">SMTP Kullanıcı Adı</label>
                            <input type="text" id="email_smtp_username" name="email_smtp_username" value="<?php echo htmlspecialchars($currentSettings['email_smtp_username'] ?? ''); ?>" placeholder="your-email@gmail.com">
                            <small class="form-help">
                                SMTP kimlik doğrulama için kullanıcı adı.
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label for="email_smtp_password">SMTP Şifre</label>
                            <input type="password" id="email_smtp_password" name="email_smtp_password" value="<?php echo htmlspecialchars($currentSettings['email_smtp_password'] ?? ''); ?>" placeholder="Uygulama şifresi">
                            <small class="form-help">
                                SMTP kimlik doğrulama için şifre veya uygulama şifresi.
                            </small>
                        </div>
                    </div>
                </div>
                
                <!-- Sosyal Medya -->
                <div class="settings-section">
                    <h3><i class="fas fa-share-alt"></i> Sosyal Medya</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="facebook_url">Facebook URL</label>
                            <input type="url" id="facebook_url" name="facebook_url" value="<?php echo htmlspecialchars($currentSettings['facebook_url'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="instagram_url">Instagram URL</label>
                            <input type="url" id="instagram_url" name="instagram_url" value="<?php echo htmlspecialchars($currentSettings['instagram_url'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="twitter_url">Twitter URL</label>
                            <input type="url" id="twitter_url" name="twitter_url" value="<?php echo htmlspecialchars($currentSettings['twitter_url'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                
                <!-- İş Ayarları -->
                <div class="settings-section">
                    <h3><i class="fas fa-business-time"></i> İş Ayarları</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="commission_rate">Komisyon Oranı (%)</label>
                            <input type="number" id="commission_rate" name="commission_rate" value="<?php echo htmlspecialchars($currentSettings['commission_rate'] ?? '5'); ?>" min="0" max="100" step="0.1">
                        </div>
                        
                        <div class="form-group">
                            <label for="timezone">Zaman Dilimi</label>
                            <select id="timezone" name="timezone">
                                <option value="Europe/Istanbul" <?php echo ($currentSettings['timezone'] ?? 'Europe/Istanbul') === 'Europe/Istanbul' ? 'selected' : ''; ?>>İstanbul</option>
                                <option value="Europe/London" <?php echo ($currentSettings['timezone'] ?? '') === 'Europe/London' ? 'selected' : ''; ?>>Londra</option>
                                <option value="America/New_York" <?php echo ($currentSettings['timezone'] ?? '') === 'America/New_York' ? 'selected' : ''; ?>>New York</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Sistem Ayarları -->
                <div class="settings-section">
                    <h3><i class="fas fa-cogs"></i> Sistem Ayarları</h3>
                    <div class="form-grid">
                        <div class="form-group checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="maintenance_mode" <?php echo ($currentSettings['maintenance_mode'] ?? 0) ? 'checked' : ''; ?>>
                                <span class="checkmark"></span>
                                Bakım Modu
                            </label>
                            <small class="form-help">Aktif olduğunda site ziyaretçilere kapalı olur.</small>
                        </div>
                        
                        <div class="form-group checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="email_notifications" <?php echo ($currentSettings['email_notifications'] ?? 1) ? 'checked' : ''; ?>>
                                <span class="checkmark"></span>
                                E-posta Bildirimleri
                            </label>
                        </div>
                        
                        <div class="form-group checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="sms_notifications" <?php echo ($currentSettings['sms_notifications'] ?? 0) ? 'checked' : ''; ?>>
                                <span class="checkmark"></span>
                                SMS Bildirimleri
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="save_settings" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Ayarları Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.settings-container {
    max-width: 1200px;
    margin: 0 auto;
}

.settings-form {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.settings-section {
    padding: 30px;
    border-bottom: 1px solid #e5e7eb;
}

.settings-section:last-child {
    border-bottom: none;
}

.settings-section h3 {
    margin: 0 0 25px 0;
    color: #1f2937;
    font-size: 20px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
}

.settings-section h3 i {
    color: #3b82f6;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-group label {
    margin-bottom: 8px;
    color: #374151;
    font-weight: 500;
    font-size: 14px;
}

.form-group input,
.form-group select,
.form-group textarea {
    padding: 12px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s ease;
    background: white;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-help {
    margin-top: 8px;
    color: #6b7280;
    font-size: 13px;
    line-height: 1.5;
}

.form-help a {
    color: #3b82f6;
    text-decoration: none;
}

.form-help a:hover {
    text-decoration: underline;
}

.form-help strong {
    color: #374151;
}

.checkbox-group {
    flex-direction: row;
    align-items: center;
    gap: 12px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    font-weight: 500;
    color: #374151;
}

.checkbox-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
    margin: 0;
}

.form-actions {
    padding: 30px;
    background: #f9fafb;
    text-align: right;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary {
    background: #3b82f6;
    color: white;
}

.btn-primary:hover {
    background: #2563eb;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.btn-secondary {
    background: #6b7280;
    color: white;
}

.btn-secondary:hover {
    background: #4b5563;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(107, 114, 128, 0.3);
}

.favicon-section {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    align-items: start;
}

.current-favicon h4 {
    margin: 0 0 15px 0;
    color: #374151;
    font-size: 16px;
    font-weight: 600;
}

.favicon-preview {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: #f9fafb;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
}

.favicon-preview img {
    border: 1px solid #d1d5db;
    border-radius: 4px;
}

.favicon-preview span {
    color: #6b7280;
    font-size: 14px;
}

.no-favicon {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: #fef3c7;
    border: 2px solid #fbbf24;
    border-radius: 8px;
    color: #92400e;
}

.no-favicon i {
    font-size: 24px;
    opacity: 0.7;
}

.favicon-upload-form {
    background: #f9fafb;
    padding: 20px;
    border-radius: 8px;
    border: 2px solid #e5e7eb;
}

@media (max-width: 768px) {
    .favicon-section {
        grid-template-columns: 1fr;
        gap: 20px;
    }
}

.alert {
    padding: 16px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-weight: 500;
}

.alert-success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}

.alert-error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fca5a5;
}

@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .settings-section {
        padding: 20px;
    }
    
    .form-actions {
        padding: 20px;
        text-align: center;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
