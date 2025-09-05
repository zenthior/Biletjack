<?php
/**
 * PayTR Ödeme Sayfası
 * 
 * Bu sayfa PayTR iFrame API kullanarak ödeme formunu gösterir.
 */

error_reporting(E_ERROR | E_WARNING | E_PARSE);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {
    require_once 'config/database.php';
    require_once 'paytr_config.php';
    require_once 'includes/session.php';

    // Kullanıcı giriş kontrolü
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }

    // Sadece müşteri hesapları
    if (($_SESSION['user_type'] ?? null) !== 'customer') {
        header('Location: index.php');
        exit;
    }

    $currentUser = getCurrentUser();
    $userId = $currentUser['id'];

    // POST verilerini kontrol et
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: odeme.php');
        exit;
    }

    // Form verilerini al
    $firstName = $_POST['firstName'] ?? '';
    $lastName = $_POST['lastName'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $cartData = $_POST['cartData'] ?? '';
    $discountCode = isset($_POST['discountCode']) ? strtoupper(trim($_POST['discountCode'])) : null;

    if (empty($cartData)) {
        header('Location: odeme.php?error=cart_empty');
        exit;
    }

    $cart = json_decode($cartData, true);
    if (!$cart || empty($cart)) {
        header('Location: odeme.php?error=invalid_cart');
        exit;
    }

    // Veritabanı bağlantısı
    $database = new Database();
    $conn = $database->getConnection();

    // Toplam tutarı hesapla
    $totalAmount = 0;
    foreach ($cart as $item) {
        $price = floatval($item['price']);
        $quantity = intval($item['quantity']);
        $totalAmount += $price * $quantity;
    }
    $cartSubtotal = $totalAmount;

    // İndirim kodu varsa doğrula ve indirimi hesapla
    $appliedDiscount = 0.0;
    $discountCodeId = null;

    if (!empty($discountCode)) {
        $conn->beginTransaction();
        
        $dcStmt = $conn->prepare("SELECT id, event_id, discount_amount, quantity, status FROM discount_codes WHERE code = ? FOR UPDATE");
        $dcStmt->execute([$discountCode]);
        $dc = $dcStmt->fetch(PDO::FETCH_ASSOC);

        if ($dc && $dc['status'] === 'active' && $dc['quantity'] > 0) {
            // İndirim kodunu kullan
            $appliedDiscount = floatval($dc['discount_amount']);
            $totalAmount -= $appliedDiscount;
            $discountCodeId = $dc['id'];
            
            // Kodu güncelle
            $updateStmt = $conn->prepare("UPDATE discount_codes SET quantity = quantity - 1 WHERE id = ?");
            $updateStmt->execute([$dc['id']]);
        }
        
        $conn->commit();
    }

    // Minimum tutar kontrolü
    if ($totalAmount < 1) {
        header('Location: odeme.php?error=invalid_amount');
        exit;
    }

    // Sipariş numarası oluştur
    $orderNumber = 'BJ' . date('Ymd') . strtoupper(substr(uniqid(), -6));

    // Siparişi veritabanına kaydet (pending durumunda)
    $conn->beginTransaction();
    
    try {
        $orderStmt = $conn->prepare("
            INSERT INTO orders (
                order_number, user_id, total_amount, payment_status, payment_method,
                billing_info, created_at, updated_at
            ) VALUES (?, ?, ?, 'pending', 'PayTR', ?, NOW(), NOW())
        ");
        
        // Billing bilgilerini JSON olarak hazırla
        $billingInfo = json_encode([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'phone' => $phone,
            'subtotal' => $cartSubtotal,
            'discount_amount' => $appliedDiscount,
            'discount_code_id' => $discountCodeId
        ]);
        
        $orderStmt->execute([
            $orderNumber, $userId, $totalAmount, $billingInfo
        ]);
        
        $orderId = $conn->lastInsertId();
        
        // Biletleri oluştur (pending durumunda)
        require_once 'classes/Ticket.php';
        $ticketManager = new Ticket($conn);
        
        foreach ($cart as $item) {
            $eventId = $item['event_id'];
            $ticketType = $item['ticket_name'];
            $price = floatval($item['price']);
            $quantity = intval($item['quantity']);
            $ticketTypeId = $item['ticket_type_id'] ?? null;
            $seatId = isset($item['seat_id']) ? (int)$item['seat_id'] : null;
            
            if ($seatId) {
                // Koltuklu bilet - PENDING statüsünde oluştur ve koltuğu rezerve et
                $tickets = $ticketManager->createTicket($eventId, $orderId, $ticketType, $price, 1, null, $ticketTypeId, $seatId, false, 'pending');
            } else {
                // Normal bilet - PENDING statüsünde oluştur
                $tickets = $ticketManager->createTicket($eventId, $orderId, $ticketType, $price, $quantity, null, $ticketTypeId, null, false, 'pending');
            }
        }
        
        // Sepeti TEMİZLEMEYİN: Başarılı ödeme callback'inde temizlenecek
        
        $conn->commit();
        
    } catch (Exception $e) {
        $conn->rollBack();
        error_log('PayTR Payment Order Creation Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
        
        // Hata türüne göre farklı yönlendirmeler
        if (strpos($e->getMessage(), 'ticket_types') !== false) {
            header('Location: odeme.php?error=invalid_ticket_type');
        } elseif (strpos($e->getMessage(), 'events') !== false) {
            header('Location: odeme.php?error=invalid_event');
        } elseif (strpos($e->getMessage(), 'cart') !== false) {
            header('Location: odeme.php?error=invalid_cart');
        } else {
            header('Location: odeme.php?error=order_creation_failed&debug=' . urlencode($e->getMessage()));
        }
        exit;
    }

    // PayTR iFrame token oluştur
    $user_ip = getUserIP();
    $payment_amount = (int) round($totalAmount * 100); // PayTR kuruş cinsinden bekler
    $user_basket = formatCartForPayTR($cart, $appliedDiscount);
    $user_name = $firstName . ' ' . $lastName;
    $user_address = 'Türkiye'; // Varsayılan adres
    $user_phone = $phone;
    $merchant_ok_url = PAYTR_SUCCESS_URL . '?order=' . $orderNumber;
    $merchant_fail_url = PAYTR_FAIL_URL . '?order=' . $orderNumber;
    $timeout_limit = PAYTR_TIMEOUT_LIMIT;
    $lang = 'tr';

    // iFrame token verilerini hazırla
    $tokenData = [
        'merchant_id' => PAYTR_MERCHANT_ID,
        'user_ip' => $user_ip,
        'merchant_oid' => $orderNumber,
        'email' => $email,
        'payment_amount' => $payment_amount,
        'currency' => PAYTR_CURRENCY,
        'user_basket' => $user_basket,
        'no_installment' => 0, // Taksit seçeneklerini göster
        'max_installment' => 0,
        'user_name' => $user_name,
        'user_address' => $user_address,
        'user_phone' => $user_phone,
        'merchant_ok_url' => $merchant_ok_url,
        'merchant_fail_url' => $merchant_fail_url,
        'timeout_limit' => $timeout_limit,
        // Hash için zorunlu alanlar
        'test_mode' => PAYTR_TEST_MODE
    ];

    // Token oluştur
    $paytr_token = createPayTRIframeToken($tokenData);

    // PayTR API'sine token isteği gönder
    $postData = array_merge($tokenData, [
        'paytr_token' => $paytr_token,
        'test_mode' => PAYTR_TEST_MODE,
        'debug_on' => PAYTR_DEBUG_MODE
    ]);

    // cURL ile API isteği
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, PAYTR_IFRAME_API_URL);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    // Production ortamında SSL doğrulaması açık olmalı
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$response) {
        error_log('PayTR API Error: HTTP ' . $httpCode . ' - ' . $response);
        header('Location: odeme.php?error=paytr_api_error');
        exit;
    }

    $result = json_decode($response, true);
    
    if (!$result || $result['status'] !== 'success') {
        error_log('PayTR Token Error: ' . $response);
        header('Location: odeme.php?error=paytr_token_error');
        exit;
    }

    $iframe_token = $result['token'];

} catch (Throwable $e) {
    error_log('PayTR Payment Fatal Error: ' . $e->getMessage());
    header('Location: odeme.php?error=system_error');
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Güvenli Ödeme - BiletJack</title>
    <link rel="stylesheet" href="css/organizer-modern.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .payment-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }
        
        .payment-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .payment-header h1 {
            color: #1a1a1a;
            margin-bottom: 0.5rem;
            font-size: 1.8rem;
        }
        
        .payment-header p {
            color: #666;
            font-size: 1rem;
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
        
        .order-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .order-detail {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
        }
        
        .order-detail strong {
            color: #1a1a1a;
        }
        
        .total-amount {
            font-size: 1.3rem;
            font-weight: bold;
            color: #00C896;
            text-align: center;
            padding: 1rem;
            background: white;
            border-radius: 8px;
            margin-top: 1rem;
        }
        
        .paytr-iframe-container {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            overflow: hidden;
            background: white;
        }
        
        .security-info {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            margin-top: 1rem;
            padding: 1rem;
            background: #e8f5e8;
            border-radius: 8px;
            color: #2d5a2d;
        }
        
        .security-info i {
            font-size: 1.2rem;
        }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        
        .loading-content {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            text-align: center;
        }
        
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #00C896;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @media (max-width: 768px) {
            .payment-container {
                margin: 1rem;
                padding: 1rem;
            }
            
            .order-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="spinner"></div>
            <p>Güvenli ödeme sayfası yükleniyor...</p>
        </div>
    </div>

    <div class="payment-container">
        <div class="payment-header">
            <h1><i class="fas fa-shield-alt"></i> Güvenli Ödeme</h1>
            <p>PayTR güvenli ödeme sistemi ile ödemenizi gerçekleştirin</p>
        </div>

        <div class="order-info">
            <h3><i class="fas fa-receipt"></i> Sipariş Bilgileri</h3>
            <div class="order-details">
                <div class="order-detail">
                    <span>Sipariş No:</span>
                    <strong><?php echo htmlspecialchars($orderNumber); ?></strong>
                </div>
                <div class="order-detail">
                    <span>Müşteri:</span>
                    <strong><?php echo htmlspecialchars($firstName . ' ' . $lastName); ?></strong>
                </div>
                <div class="order-detail">
                    <span>E-posta:</span>
                    <strong><?php echo htmlspecialchars($email); ?></strong>
                </div>
                <div class="order-detail">
                    <span>Telefon:</span>
                    <strong><?php echo htmlspecialchars($phone); ?></strong>
                </div>
            </div>
            
            <?php if ($appliedDiscount > 0): ?>
            <div class="order-detail">
                <span>Ara Toplam:</span>
                <strong><?php echo number_format($cartSubtotal, 2); ?> ₺</strong>
            </div>
            <div class="order-detail">
                <span>İndirim:</span>
                <strong style="color: #dc3545;">-<?php echo number_format($appliedDiscount, 2); ?> ₺</strong>
            </div>
            <?php endif; ?>
            
            <div class="total-amount">
                <i class="fas fa-credit-card"></i>
                Ödenecek Tutar: <?php echo number_format($totalAmount, 2); ?> ₺
            </div>
        </div>

        <div class="paytr-iframe-container">
            <script src="https://www.paytr.com/js/iframeResizer.min.js"></script>
            <iframe 
                src="https://www.paytr.com/odeme/guvenli/<?php echo $iframe_token; ?>" 
                id="paytriframe" 
                frameborder="0" 
                scrolling="no" 
                style="width: 100%; min-height: 500px;">
            </iframe>
        </div>

        <div class="security-info">
            <i class="fas fa-lock"></i>
            <span>Bu sayfa SSL sertifikası ile korunmaktadır. Kart bilgileriniz güvenle şifrelenir.</span>
        </div>
    </div>

    <script>
        // iFrame boyutlandırma
        iFrameResize({}, '#paytriframe');
        
        // Sayfa yüklendiğinde loading overlay'i gizle
        window.addEventListener('load', function() {
            setTimeout(function() {
                document.getElementById('loadingOverlay').style.display = 'none';
            }, 1000);
        });
        
        // iFrame yüklendiğinde loading'i gizle
        document.getElementById('paytriframe').addEventListener('load', function() {
            document.getElementById('loadingOverlay').style.display = 'none';
        });
    </script>
</body>
</html>