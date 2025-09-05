<?php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../includes/whatsapp_api.php';

// Admin kontrolü
requireAdmin();

$message = '';
$messageType = '';

// Test işlemi
if ($_POST && isset($_POST['test_whatsapp'])) {
    $testPhone = $_POST['test_phone'] ?? '';
    
    if (empty($testPhone)) {
        $message = 'Test telefon numarası gerekli';
        $messageType = 'error';
    } else {
        // Telefon numarası formatını kontrol et
        if (!preg_match('/^\+90[0-9]{10}$/', $testPhone)) {
            $message = 'Geçersiz telefon numarası formatı (+90XXXXXXXXXX)';
            $messageType = 'error';
        } else {
            try {
                $whatsappAPI = new WhatsAppAPI();
                $testCode = '123456';
                $result = $whatsappAPI->sendVerificationCode($testPhone, $testCode);
                
                if ($result['success']) {
                    $message = 'Test mesajı başarıyla gönderildi!';
                    $messageType = 'success';
                } else {
                    $message = 'Test mesajı gönderilemedi: ' . $result['message'];
                    $messageType = 'error';
                }
            } catch (Exception $e) {
                $message = 'Hata: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}

// WhatsApp API durumunu kontrol et
$whatsappAPI = new WhatsAppAPI();
$apiStatus = $whatsappAPI->getStatus();

include 'includes/header.php';
?>

<div class="admin-container">
    <!-- Sidebar -->
    <div class="admin-sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <i class="fas fa-ticket-alt"></i>
            </div>
            <h2 class="sidebar-title">BiletJack</h2>
            <p class="sidebar-subtitle">Admin Dashboard</p>
        </div>
        
        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">Ana Menü</div>
                <a href="index.php" class="nav-item">
                    <i class="fas fa-chart-pie"></i>
                    Dashboard
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
                <a href="settings.php" class="nav-item">
                    <i class="fas fa-cog"></i>
                    Ayarlar
                </a>
                <a href="whatsapp_test.php" class="nav-item active">
                    <i class="fab fa-whatsapp"></i>
                    WhatsApp Test
                </a>
            </div>
        </nav>
    </div>
    
    <div class="admin-content">
        <div class="content-header">
            <h1>WhatsApp API Test</h1>
            <div class="user-info">
                <span>Hoş geldiniz, <?php echo getCurrentUser()['first_name']; ?></span>
                <a href="../auth/logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="test-container">
            <!-- API Durumu -->
            <div class="status-card">
                <h3><i class="fab fa-whatsapp"></i> WhatsApp API Durumu</h3>
                <div class="status-info">
                    <div class="status-indicator <?php echo $apiStatus['configured'] ? 'success' : 'error'; ?>">
                        <i class="fas <?php echo $apiStatus['configured'] ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                        <?php echo $apiStatus['message']; ?>
                    </div>
                    
                    <?php if (!$apiStatus['configured']): ?>
                        <div class="status-help">
                            <p><strong>WhatsApp API'yi yapılandırmak için:</strong></p>
                            <ol>
                                <li><a href="settings.php">Ayarlar</a> sayfasına gidin</li>
                                <li>"API Ayarları" bölümünde WhatsApp bilgilerini doldurun</li>
                                <li>Facebook Developers'dan aldığınız token ve telefon ID'sini girin</li>
                                <li>Ayarları kaydedin ve bu sayfaya geri dönün</li>
                            </ol>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Test Formu -->
            <?php if ($apiStatus['configured']): ?>
            <div class="test-card">
                <h3><i class="fas fa-paper-plane"></i> Test Mesajı Gönder</h3>
                <form method="POST" class="test-form">
                    <div class="form-group">
                        <label for="test_phone">Test Telefon Numarası</label>
                        <input type="tel" id="test_phone" name="test_phone" placeholder="+905XXXXXXXXX" required>
                        <small class="form-help">
                            Türkiye telefon numarası formatında girin (+90XXXXXXXXXX)
                        </small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="test_whatsapp" class="btn btn-primary">
                            <i class="fab fa-whatsapp"></i>
                            Test Mesajı Gönder
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
            
            <!-- Bilgi Kartı -->
            <div class="info-card">
                <h3><i class="fas fa-info-circle"></i> WhatsApp API Hakkında</h3>
                <div class="info-content">
                    <p><strong>Bu test sayfası WhatsApp API entegrasyonunuzun çalışıp çalışmadığını kontrol etmenizi sağlar.</strong></p>
                    
                    <h4>Önemli Notlar:</h4>
                    <ul>
                        <li>WhatsApp Business API kullanılmaktadır</li>
                        <li>Mesaj gönderebilmek için Facebook'tan onaylanmış template'ler gereklidir</li>
                        <li>Template yoksa basit metin mesajı gönderilmeye çalışılır</li>
                        <li>API ayarları eksikse geliştirme modu aktif olur (konsola yazdırır)</li>
                        <li>Üretim ortamında mutlaka gerçek API bilgilerini kullanın</li>
                    </ul>
                    
                    <h4>Sorun Giderme:</h4>
                    <ul>
                        <li>API token'ının geçerli olduğundan emin olun</li>
                        <li>Telefon numarası ID'sinin doğru olduğunu kontrol edin</li>
                        <li>Facebook Developers panelinde WhatsApp API'nin aktif olduğunu doğrulayın</li>
                        <li>Webhook URL'sinin doğru yapılandırıldığından emin olun</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.test-container {
    max-width: 800px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.status-card,
.test-card,
.info-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    padding: 30px;
}

.status-card h3,
.test-card h3,
.info-card h3 {
    margin: 0 0 20px 0;
    color: #1f2937;
    font-size: 20px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
}

.status-card h3 i {
    color: #25d366;
}

.test-card h3 i {
    color: #3b82f6;
}

.info-card h3 i {
    color: #10b981;
}

.status-indicator {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 15px;
    border-radius: 8px;
    font-weight: 500;
    margin-bottom: 15px;
}

.status-indicator.success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}

.status-indicator.error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fca5a5;
}

.status-help {
    background: #f9fafb;
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid #3b82f6;
}

.status-help ol {
    margin: 10px 0 0 0;
    padding-left: 20px;
}

.status-help a {
    color: #3b82f6;
    text-decoration: none;
    font-weight: 500;
}

.status-help a:hover {
    text-decoration: underline;
}

.test-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    margin-bottom: 8px;
    color: #374151;
    font-weight: 500;
    font-size: 14px;
}

.form-group input {
    padding: 12px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s ease;
}

.form-group input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-help {
    margin-top: 8px;
    color: #6b7280;
    font-size: 13px;
}

.form-actions {
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
    background: #25d366;
    color: white;
}

.btn-primary:hover {
    background: #128c7e;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(37, 211, 102, 0.3);
}

.info-content h4 {
    color: #374151;
    margin: 20px 0 10px 0;
    font-size: 16px;
}

.info-content ul {
    margin: 10px 0;
    padding-left: 20px;
}

.info-content li {
    margin-bottom: 5px;
    color: #6b7280;
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
    .test-container {
        margin: 0 10px;
    }
    
    .status-card,
    .test-card,
    .info-card {
        padding: 20px;
    }
}
</style>

<?php include 'includes/footer.php'; ?>