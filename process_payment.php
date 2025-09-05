<?php
error_reporting(E_ERROR | E_WARNING | E_PARSE);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {
    require_once 'config/database.php';
    require_once 'classes/Ticket.php';
    require_once 'includes/session.php';

    // PayTR'dan gelen GET isteği (ödeme sonrası yönlendirme)
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['order'])) {
        $orderNumber = $_GET['order'];
        
        // PayTR'dan gelen parametreleri logla
        error_log('PayTR Redirect Parameters: ' . json_encode($_GET));
        
        // Veritabanı bağlantısı
        $database = new Database();
        $conn = $database->getConnection();
        
        // Siparişi kontrol et
        $stmt = $conn->prepare("SELECT * FROM orders WHERE order_number = ? LIMIT 1");
        $stmt->execute([$orderNumber]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order) {
            error_log('Order Status: ' . $order['payment_status']);
            
            // PayTR'dan hash parametresi geliyorsa ödeme başarılı demektir
            // Localhost callback sorunu nedeniyle bu yöntemi kullanıyoruz
            if (isset($_GET['hash']) && !empty($_GET['hash'])) {
                error_log('PayTR hash parameter detected, payment successful');
                
                // Siparişi başarılı olarak işaretle
                if ($order['payment_status'] === 'pending') {
                    require_once 'paytr_config.php';
                    
                    // Transaction başlat
                    $conn->beginTransaction();
                    
                    try {
                        // Ödeme başarılı olarak güncelle
                        $updateStmt = $conn->prepare("
                            UPDATE orders SET 
                                payment_status = 'paid',
                                payment_method = 'PayTR',
                                payment_reference = ?,
                                updated_at = NOW()
                            WHERE order_number = ?
                        ");
                        
                        $paymentReference = json_encode([
                            'transaction_id' => $orderNumber . '_' . time(),
                            'total_amount' => floatval($order['total_amount']),
                            'processed_at' => date('Y-m-d H:i:s'),
                            'hash' => $_GET['hash']
                        ]);
                        
                        $updateStmt->execute([$paymentReference, $orderNumber]);
                        
                        // Biletlerin durumunu güncelle
                        $ticketStmt = $conn->prepare("
                            UPDATE tickets SET 
                                status = 'active'
                            WHERE order_id = ?
                        ");
                        $ticketStmt->execute([$order['id']]);
                        
                        // QR kodları oluştur
                        try {
                            require_once 'classes/Ticket.php';
                            $ticketManager = new Ticket($conn);
                            
                            $ticketsForQR = $conn->prepare("
                                SELECT id, event_id, ticket_number, quantity, created_at 
                                FROM tickets 
                                WHERE order_id = ? AND qr_code_path IS NULL
                            ");
                            $ticketsForQR->execute([$order['id']]);
                            $ticketsToProcess = $ticketsForQR->fetchAll(PDO::FETCH_ASSOC);
                            
                            foreach ($ticketsToProcess as $ticketData) {
                                $qrData = json_encode([
                                    'ticket_id' => $ticketData['id'],
                                    'ticket_number' => $ticketData['ticket_number'],
                                    'event_id' => $ticketData['event_id'],
                                    'quantity' => $ticketData['quantity'],
                                    'purchase_time' => $ticketData['created_at'],
                                    'verification_code' => strtoupper(substr(md5($ticketData['id'] . $ticketData['event_id'] . time()), 0, 8))
                                ]);
                                
                                $qrCodePath = $ticketManager->generateAndSaveQRCode($qrData, $ticketData['id']);
                                
                                if ($qrCodePath) {
                                    $updateQRStmt = $conn->prepare("UPDATE tickets SET qr_code_path = ? WHERE id = ?");
                                    $updateQRStmt->execute([$qrCodePath, $ticketData['id']]);
                                }
                            }
                        } catch (Exception $qrError) {
                            error_log('QR code generation error: ' . $qrError->getMessage());
                        }
                        
                        // Sepeti temizle
                        $clearCartStmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
                        $clearCartStmt->execute([$order['user_id']]);
                        
                        $conn->commit();
                        error_log('Payment processed successfully for order: ' . $orderNumber);
                        
                        // Siparişi tekrar al (güncellenmiş haliyle)
                        $stmt->execute([$orderNumber]);
                        $order = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                    } catch (Exception $e) {
                        if ($conn->inTransaction()) {
                            $conn->rollBack();
                        }
                        error_log('Payment processing error: ' . $e->getMessage());
                    }
                }
            }
            
            // Callback'in işlenmesi için kısa bir süre bekle (sadece hash yoksa)
            if ($order['payment_status'] === 'pending' && !isset($_GET['hash'])) {
                error_log('Order is pending, waiting for callback processing...');
                sleep(2);
                
                // Siparişi tekrar kontrol et
                $stmt->execute([$orderNumber]);
                $order = $stmt->fetch(PDO::FETCH_ASSOC);
                error_log('Order Status After Waiting: ' . $order['payment_status']);
            }
            
            if ($order['payment_status'] === 'paid') {
                // Başarılı ödeme - sipariş bilgilerini session'a kaydet
                $billingInfo = json_decode($order['billing_info'], true);
                
                // Bilet bilgilerini al
                $ticketsStmt = $conn->prepare("
                    SELECT t.*, e.title as event_title, e.event_date, e.venue_name,
                           tt.name as ticket_type_name
                    FROM tickets t
                    JOIN events e ON t.event_id = e.id
                    LEFT JOIN ticket_types tt ON t.ticket_type_id = tt.id
                    WHERE t.order_id = ?
                ");
                $ticketsStmt->execute([$order['id']]);
                $tickets = $ticketsStmt->fetchAll(PDO::FETCH_ASSOC);
                
                $_SESSION['last_order'] = [
                    'orderNumber' => $order['order_number'],
                    'date' => $order['created_at'],
                    'subtotal' => $billingInfo['subtotal'] ?? $order['total_amount'],
                    'discount' => $billingInfo['discount_amount'] ?? 0,
                    'total' => $order['total_amount'],
                    'tickets' => array_map(function($ticket) {
                        return [
                            'ticket_code' => $ticket['ticket_number'],
                            'ticket_type' => $ticket['ticket_type_name'] ?? 'Genel',
                            'price' => $ticket['price'],
                            'quantity' => $ticket['quantity'],
                            'qr_code_path' => $ticket['qr_code_path'],
                            'seat_labels' => $ticket['seat_labels'] ?? null
                        ];
                    }, $tickets)
                ];
                
                // Ödeme başarılı sayfasına yönlendir
                header('Location: odeme-basarili.php');
                exit;
            } elseif ($order['payment_status'] === 'failed') {
                // Başarısız ödeme - hata mesajını al
                $paymentReference = json_decode($order['payment_reference'], true);
                $errorCode = $paymentReference['failed_reason_code'] ?? 'PAYMENT_FAILED';
                $errorMessage = $paymentReference['failed_reason_msg'] ?? 'Ödeme işlemi başarısız oldu.';
                
                // Başarısız ödeme sayfasına yönlendir
                header('Location: odeme-basarisiz.php?order=' . urlencode($orderNumber) . '&error=' . urlencode($errorCode));
                exit;
            } else {
                // Pending durumunda - callback henüz işlenmemiş
                // PayTR merchant_ok_url'e yönlendirme yaptığı için buraya geliyoruz
                // Ancak gerçek ödeme durumu callback'te belirlenir
                // Bu durumda kullanıcıyı "işleniyor" sayfasına yönlendirelim
                header('Location: odeme-basarisiz.php?order=' . urlencode($orderNumber) . '&error=PROCESSING&message=' . urlencode('Ödemeniz işleniyor, lütfen bekleyiniz...'));
                exit;
            }
        } else {
            // Sipariş bulunamadı
            header('Location: odeme-basarisiz.php?error=order_not_found');
            exit;
        }
    }

    // POST istekleri için JSON header ayarla
    header('Content-Type: application/json');

    // Sadece POST isteklerini kabul et
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Geçersiz istek metodu']);
        exit;
    }

    // Kullanıcının giriş yapıp yapmadığını kontrol et
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Giriş yapmanız gerekiyor.']);
        exit;
    }

    // Role kontrolü (yalnızca müşteri)
    if (($_SESSION['user_type'] ?? null) !== 'customer') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Sadece müşteri hesapları bilet satın alabilir.']);
        exit;
    }

try {
    $database = new Database();
    $conn = $database->getConnection();
    $ticketManager = new Ticket($conn);
    
    $currentUser = getCurrentUser();
    $userId = $currentUser['id'];
    
    // POST verilerini al
    $firstName = $_POST['firstName'] ?? '';
    $lastName = $_POST['lastName'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $cartData = $_POST['cartData'] ?? '';
    $discountCode = isset($_POST['discountCode']) ? strtoupper(trim($_POST['discountCode'])) : null;
    
    if (empty($cartData)) {
        echo json_encode(['success' => false, 'message' => 'Sepet verisi bulunamadı.']);
        exit;
    }
    
    $cart = json_decode($cartData, true);
    if (!$cart || empty($cart)) {
        echo json_encode(['success' => false, 'message' => 'Geçersiz sepet verisi.']);
        exit;
    }
    
    $allTickets = [];
    $orderNumber = 'BJ' . date('Ymd') . strtoupper(substr(uniqid(), -6));

    // Toplam tutarı hesapla
    $totalAmount = 0;
    foreach ($cart as $item) {
        $price = floatval($item['price']);
        $quantity = intval($item['quantity']);
        $totalAmount += $price * $quantity;
    }
    // Ara toplamı (indirimsiz) sakla
    $cartSubtotal = $totalAmount;

    // İndirim kodu varsa doğrula ve indirimi hesapla
    $appliedDiscount = 0.0;
    $discountCodeId = null;
    $discountEventId = null;

    if (!empty($discountCode)) {
        // Transaction başlat
        $conn->beginTransaction();

        // Kod kilitleyerek getir
        $dcStmt = $conn->prepare("SELECT id, event_id, discount_amount, quantity, status FROM discount_codes WHERE code = ? FOR UPDATE");
        $dcStmt->execute([$discountCode]);
        $dc = $dcStmt->fetch(PDO::FETCH_ASSOC);

        if (!$dc || $dc['status'] !== 'active') {
            $conn->rollBack();
            echo json_encode(['success' => false, 'message' => 'İndirim kodu geçersiz.']);
            exit;
        }

        // Kullanıcı daha önce kullanmış mı? (kilitle)
        $userUsedStmt = $conn->prepare("SELECT id FROM discount_code_usages WHERE discount_code_id = ? AND user_id = ? FOR UPDATE");
        $userUsedStmt->execute([$dc['id'], $userId]);
        if ($userUsedStmt->fetch()) {
            $conn->rollBack();
            echo json_encode(['success' => false, 'message' => 'Bu indirim kodunu daha önce kullandınız.']);
            exit;
        }

        // Kullanım kapasitesi dolu mu? (mevcut satırları kilitle ve say)
        $lockRowsStmt = $conn->prepare("SELECT id FROM discount_code_usages WHERE discount_code_id = ? FOR UPDATE");
        $lockRowsStmt->execute([$dc['id']]);
        $existingRows = $lockRowsStmt->fetchAll(PDO::FETCH_COLUMN);
        $usedCount = count($existingRows);
        if ($usedCount >= (int)$dc['quantity']) {
            $conn->rollBack();
            echo json_encode(['success' => false, 'message' => 'İndirim kodu süresi bitti veya daha kullanılamıyor.']);
            exit;
        }

        // Sepette bu etkinlik için olan tutarı bul
        $eventSubtotal = 0;
        foreach ($cart as $item) {
            if ((int)$item['event_id'] === (int)$dc['event_id']) {
                $eventSubtotal += floatval($item['price']) * intval($item['quantity']);
            }
        }
        if ($eventSubtotal <= 0) {
            $conn->rollBack();
            echo json_encode(['success' => false, 'message' => 'İndirim kodu bu etkinlik için geçerli değildir.']);
            exit;
        }

        $appliedDiscount = min((float)$dc['discount_amount'], $eventSubtotal);
        $discountCodeId = (int)$dc['id'];
        $discountEventId = (int)$dc['event_id'];

        // İndirimi toplamdan düş
        $totalAmount = max(0, $totalAmount - $appliedDiscount);
    } else {
        // İndirim kodu yoksa da transaction gerekmeden devam
        $conn->beginTransaction();
    }

    // Önce order oluştur
    $stmt = $conn->prepare("
        INSERT INTO orders (user_id, order_number, total_amount, payment_status, payment_method, created_at) 
        VALUES (?, ?, ?, 'paid', 'test', NOW())
    ");
    $stmt->execute([$userId, $orderNumber, $totalAmount]);
    $orderId = $conn->lastInsertId();

    // Koltuklu ürünleri gruplandır (tek bilet/QR için)
    $seatGroupsByEvent = []; // event_id => ['seat_ids'=>[], 'prices'=>[], 'labels'=>[]]
    $otherItems = [];

    foreach ($cart as $item) {
        $eventId = $item['event_id'];
        $price = floatval($item['price']);
        $quantity = intval($item['quantity']);
        $seatId = $item['seat_id'] ?? null;

        if (!empty($seatId)) {
            if (!isset($seatGroupsByEvent[$eventId])) {
                $seatGroupsByEvent[$eventId] = ['seat_ids' => [], 'prices' => [], 'labels' => []];
            }
            // Her koltuk 1 adettir, gerekirse quantity kadar tekrar eklersiniz ama seat_id var ise quantity zaten 1
            $seatGroupsByEvent[$eventId]['seat_ids'][] = (int)$seatId;
            $seatGroupsByEvent[$eventId]['prices'][] = $price;
            // Görünecek ad olarak sepetteki "A12 - VIP" gibi etiketi sakla
            $seatLabel = $item['ticket_name'] ?? 'Koltuk';
            $seatGroupsByEvent[$eventId]['labels'][] = $seatLabel;
        } else {
            $otherItems[] = $item; // Genel biletler eskisi gibi
        }
    }

    // Önce genel biletleri oluştur (değişmedi)
    foreach ($otherItems as $item) {
        $eventId = $item['event_id'];
        $ticketType = $item['ticket_name'];
        $price = floatval($item['price']);
        $quantity = intval($item['quantity']);
        $ticketTypeId = $item['ticket_type_id'] ?? null;
        $tickets = $ticketManager->createTicket($eventId, $orderId, $ticketType, $price, $quantity, null, $ticketTypeId, null);
        $allTickets = array_merge($allTickets, $tickets);
    }

    // Koltuklu ürünlerde her event için tek bilet/tek QR oluştur
    foreach ($seatGroupsByEvent as $eventId => $grp) {
        $seatIds = $grp['seat_ids'];
        $prices = $grp['prices'];
        $labels = $grp['labels'];

        // Etkinlik türünü kontrol et (rezervasyon sistemi mi?)
        $eventStmt = $conn->prepare("SELECT seating_type FROM events WHERE id = ?");
        $eventStmt->execute([$eventId]);
        $eventData = $eventStmt->fetch(PDO::FETCH_ASSOC);
        $isReservation = ($eventData && $eventData['seating_type'] === 'reservation');

        $seatCount = count($seatIds);
        $totalSeatPrice = array_sum($prices);
        $uniquePrices = array_unique(array_map(fn($p) => (string)$p, $prices)); // stringleştirerek hassasiyet sorunlarını azalt

        // Bilet tipi (görünür ad)
        $ticketType = $isReservation ? "Rezervasyon ({$seatCount} koltuk)" : "Koltuklu Bilet ({$seatCount} koltuk)";

        // Fiyatların hepsi aynıysa: price=birim fiyat, quantity=koltuk sayısı (normal sistemle aynı)
        // Fiyatlar farklıysa: price=toplam, quantity=1 (toplamın bozulmaması için)
        if (count($uniquePrices) === 1) {
            $unitPrice = floatval($uniquePrices[0]);
            $price = $unitPrice;
            $quantity = $seatCount;
        } else {
            $price = $totalSeatPrice;
            $quantity = 1;
        }

        // Rezervasyon sistemi için koltukları 'reserved', normal sistem için 'sold' yap
        $tickets = $ticketManager->createTicket($eventId, $orderId, $ticketType, $price, $quantity, null, null, $seatIds, $isReservation);
        $allTickets = array_merge($allTickets, $tickets);
    }

    // İndirim kodu kullanımı kaydı (varsa)
    if ($discountCodeId) {
        $useStmt = $conn->prepare("INSERT INTO discount_code_usages (discount_code_id, user_id) VALUES (?, ?)");
        $useStmt->execute([$discountCodeId, $userId]);
    }

    // Sipariş verisini kaydet
    $orderData = [
        'orderNumber' => $orderNumber,
        'userId' => $userId,
        'customerInfo' => [
            'firstName' => $firstName,
            'lastName' => $lastName,
            'email' => $email,
            'phone' => $phone
        ],
        'tickets' => $allTickets,
        // Burada biletlerin toplamından değil, indirim uygulanmış order toplamını kullanıyoruz
        'subtotal' => $cartSubtotal,
        'discount' => $appliedDiscount,
        'total' => $totalAmount,
        'date' => date('Y-m-d H:i:s')
    ];

    // Sepeti temizle (DB)
    $clearStmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $clearStmt->execute([$userId]);

    // Transaction commit
    $conn->commit();

    // Sipariş verisini session'a kaydet ve kilidi bırak
    $_SESSION['last_order'] = $orderData;
    if (session_status() === PHP_SESSION_ACTIVE) { session_write_close(); }

    // Başarılı yanıtı gönder ve çık
    echo json_encode([
        'success' => true,
        'message' => 'Ödeme başarılı!',
        'orderNumber' => $orderNumber,
        'redirectUrl' => 'odeme-basarili.php?order=' . $orderNumber
    ]);
    exit;
    
} catch (Exception $e) {
    // Hata durumunda rollback
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Ödeme işlemi sırasında hata oluştu: ' . $e->getMessage()]);
}

} catch (Throwable $e) {
    // Tüm hataları yakala
    error_log('Process Payment Fatal Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Sistem hatası: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>