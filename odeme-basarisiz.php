<?php
/**
 * Ödeme Başarısız Sayfası
 * 
 * PayTR ödeme işlemi başarısız olduğunda kullanıcının yönlendirildiği sayfa.
 */

require_once 'config/database.php';
require_once 'includes/session.php';

// Kullanıcı giriş kontrolü
if (!isLoggedIn()) {
    header('Location: auth/login-form.php');
    exit;
}

$orderNumber = $_GET['order'] ?? '';
$errorMessage = $_GET['error'] ?? 'Ödeme işlemi sırasında bir hata oluştu.';
$customMessage = $_GET['message'] ?? '';
$currentUser = getCurrentUser();

// Hata mesajlarını Türkçeleştir
$errorMessages = [
    'PAYMENT_FAILED' => 'Ödeme işlemi başarısız oldu. Lütfen kart bilgilerinizi kontrol edip tekrar deneyin.',
    'INSUFFICIENT_FUNDS' => 'Kartınızda yeterli bakiye bulunmamaktadır.',
    'INVALID_CARD' => 'Geçersiz kart bilgileri. Lütfen kart bilgilerinizi kontrol edin.',
    'EXPIRED_CARD' => 'Kartınızın süresi dolmuş. Lütfen başka bir kart ile deneyin.',
    'DECLINED' => 'Bankanız tarafından işlem reddedildi. Lütfen bankanızla iletişime geçin.',
    'TIMEOUT' => 'İşlem zaman aşımına uğradı. Lütfen tekrar deneyin.',
    'NETWORK_ERROR' => 'Ağ bağlantısı hatası. Lütfen internet bağlantınızı kontrol edin.',
    'SYSTEM_ERROR' => 'Sistem hatası oluştu. Lütfen daha sonra tekrar deneyin.',
    'PROCESSING' => 'Ödeme işleminiz henüz tamamlanmadı. Lütfen birkaç dakika bekleyip tekrar kontrol edin.',
    'order_not_found' => 'Sipariş bulunamadı veya erişim yetkiniz bulunmamaktadır.'
];

$displayError = !empty($customMessage) ? $customMessage : ($errorMessages[$errorMessage] ?? $errorMessage);

// Eğer sipariş numarası varsa, siparişi kontrol et
$order = null;
if (!empty($orderNumber)) {
    $database = new Database();
    $conn = $database->getConnection();
    
    $stmt = $conn->prepare("
        SELECT o.*, 
               COUNT(t.id) as ticket_count,
               GROUP_CONCAT(DISTINCT e.title SEPARATOR ', ') as event_titles
        FROM orders o
        LEFT JOIN tickets t ON o.id = t.order_id
        LEFT JOIN events e ON t.event_id = e.id
        WHERE o.order_number = ? AND o.user_id = ?
        GROUP BY o.id
    ");
    $stmt->execute([$orderNumber, $currentUser['id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ödeme Başarısız - BiletJack</title>
    <link rel="stylesheet" href="css/organizer-modern.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .error-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }
        
        .error-header {
            text-align: center;
            margin-bottom: 2rem;
            padding: 2rem;
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            border-radius: 16px;
            color: white;
        }
        
        .error-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            animation: shake 0.5s ease-in-out;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        .error-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .error-header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            border-left: 4px solid #dc3545;
        }
        
        .error-message h3 {
            margin-bottom: 0.5rem;
            font-size: 1.2rem;
        }
        
        .error-message p {
            margin: 0;
            line-height: 1.5;
        }
        
        .order-info {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
        }
        
        .order-info h3 {
            color: #1a1a1a;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            color: #666;
            font-weight: 500;
        }
        
        .info-value {
            color: #1a1a1a;
            font-weight: 600;
        }
        
        .suggestions {
            background: #d1ecf1;
            color: #0c5460;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            border-left: 4px solid #17a2b8;
        }
        
        .suggestions h3 {
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }
        
        .suggestions ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .suggestions li {
            padding: 0.5rem 0;
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
        }
        
        .suggestions li i {
            color: #17a2b8;
            width: 20px;
            margin-top: 0.2rem;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #00C896 0%, #00b085 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 200, 150, 0.3);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        
        .btn-outline {
            background: transparent;
            color: #dc3545;
            border: 2px solid #dc3545;
        }
        
        .btn-outline:hover {
            background: #dc3545;
            color: white;
        }
        
        .support-info {
            text-align: center;
            margin-top: 2rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .support-info h4 {
            color: #1a1a1a;
            margin-bottom: 0.5rem;
        }
        
        .support-info p {
            color: #666;
            margin: 0;
        }
        
        @media (max-width: 768px) {
            .error-container {
                margin: 1rem;
                padding: 1rem;
            }
            
            .error-header {
                padding: 1.5rem;
            }
            
            .error-icon {
                font-size: 3rem;
            }
            
            .error-header h1 {
                font-size: 1.5rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-header">
            <div class="error-icon">
                <i class="fas fa-times-circle"></i>
            </div>
            <h1>Ödeme Başarısız</h1>
            <p>Üzgünüz, ödeme işleminiz tamamlanamadı.</p>
        </div>

        <div class="error-message">
            <h3><i class="fas fa-exclamation-triangle"></i> Hata Detayı</h3>
            <p><?php echo htmlspecialchars($displayError); ?></p>
        </div>

        <?php if ($order): ?>
        <div class="order-info">
            <h3><i class="fas fa-info-circle"></i> Sipariş Bilgileri</h3>
            <div class="info-item">
                <span class="info-label">Sipariş No:</span>
                <span class="info-value"><?php echo htmlspecialchars($order['order_number']); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Tutar:</span>
                <span class="info-value"><?php echo number_format($order['total_amount'], 2); ?> ₺</span>
            </div>
            <div class="info-item">
                <span class="info-label">Bilet Sayısı:</span>
                <span class="info-value"><?php echo $order['ticket_count']; ?> adet</span>
            </div>
            <div class="info-item">
                <span class="info-label">Etkinlik:</span>
                <span class="info-value"><?php echo htmlspecialchars($order['event_titles']); ?></span>
            </div>
        </div>
        <?php endif; ?>

        <div class="suggestions">
            <h3><i class="fas fa-lightbulb"></i> Öneriler</h3>
            <ul>
                <li><i class="fas fa-credit-card"></i> Kart bilgilerinizi kontrol edin ve tekrar deneyin</li>
                <li><i class="fas fa-money-check-alt"></i> Kartınızda yeterli bakiye olduğundan emin olun</li>
                <li><i class="fas fa-phone"></i> Bankanızla iletişime geçerek işlem limitlerini kontrol edin</li>
                <li><i class="fas fa-clock"></i> Birkaç dakika bekleyip tekrar deneyin</li>
                <li><i class="fas fa-credit-card"></i> Farklı bir ödeme yöntemi kullanmayı deneyin</li>
            </ul>
        </div>

        <div class="action-buttons">
            <?php if ($order): ?>
            <a href="paytr_payment.php?retry=<?php echo urlencode($order['order_number']); ?>" class="btn btn-primary">
                <i class="fas fa-redo"></i> Tekrar Dene
            </a>
            <?php endif; ?>
            <a href="odeme.php" class="btn btn-secondary">
                <i class="fas fa-shopping-cart"></i> Sepete Dön
            </a>
            <a href="index.php" class="btn btn-outline">
                <i class="fas fa-home"></i> Ana Sayfaya Dön
            </a>
        </div>

        <div class="support-info">
            <h4><i class="fas fa-headset"></i> Yardıma mı ihtiyacınız var?</h4>
            <p>Sorun devam ederse müşteri hizmetlerimizle iletişime geçin: <strong>destek@biletjack.com</strong></p>
        </div>
    </div>

    <script>
        // Sayfa yüklendiğinde hata animasyonu
        document.addEventListener('DOMContentLoaded', function() {
            // 5 saniye sonra otomatik yönlendirme önerisi (opsiyonel)
            setTimeout(function() {
                const suggestion = document.createElement('div');
                suggestion.style.cssText = `
                    position: fixed;
                    bottom: 20px;
                    right: 20px;
                    background: #007bff;
                    color: white;
                    padding: 1rem;
                    border-radius: 8px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
                    z-index: 1000;
                    cursor: pointer;
                    animation: slideIn 0.3s ease;
                `;
                suggestion.innerHTML = '<i class="fas fa-info-circle"></i> Ana sayfaya dönmek ister misiniz?';
                suggestion.onclick = function() {
                    window.location.href = 'index.php';
                };
                document.body.appendChild(suggestion);
            }, 10000);
        });
    </script>
</body>
</html>