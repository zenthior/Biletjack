<?php
/**
 * PayTR Callback URL Handler
 * 
 * Bu dosya PayTR sisteminden gelen ödeme sonuçlarını işler.
 * PayTR mağaza panelinde "Bildirim URL" olarak tanımlanmalıdır.
 */

error_reporting(E_ERROR | E_WARNING | E_PARSE);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {
    require_once 'config/database.php';
    require_once 'paytr_config.php';
    require_once 'includes/session.php';

    // Sadece POST isteklerini kabul et
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Method Not Allowed');
    }

    // POST verilerini al
    $merchant_oid = $_POST['merchant_oid'] ?? '';
    $status = $_POST['status'] ?? '';
    $total_amount = $_POST['total_amount'] ?? '';
    $hash = $_POST['hash'] ?? '';
    $failed_reason_code = $_POST['failed_reason_code'] ?? '';
    $failed_reason_msg = $_POST['failed_reason_msg'] ?? '';
    $test_mode = $_POST['test_mode'] ?? '0';
    $payment_type = $_POST['payment_type'] ?? '';
    $currency = $_POST['currency'] ?? '';
    $payment_amount = $_POST['payment_amount'] ?? '';
    $installment_count = $_POST['installment_count'] ?? '0';

    // Log callback verilerini (debug için)
    if (PAYTR_DEBUG_MODE) {
        error_log('PayTR Callback Data: ' . json_encode($_POST));
    }

    // Hash doğrulaması
    if (!verifyPayTRCallback($_POST)) {
        error_log('PayTR Callback: Invalid hash for order ' . $merchant_oid);
        http_response_code(400);
        exit('Invalid hash');
    }

    // Veritabanı bağlantısı
    $database = new Database();
    $conn = $database->getConnection();

    // Siparişi bul
    $stmt = $conn->prepare("SELECT * FROM orders WHERE order_number = ? LIMIT 1");
    $stmt->execute([$merchant_oid]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        error_log('PayTR Callback: Order not found - ' . $merchant_oid);
        echo 'OK'; // PayTR'a OK yanıtı ver (tekrar denemesin)
        exit;
    }

    // Sipariş zaten işlenmişse sadece OK döndür
    if ($order['payment_status'] === 'paid' || $order['payment_status'] === 'failed') {
        echo 'OK';
        exit;
    }

    // Transaction başlat
    $conn->beginTransaction();

    try {
        if ($status === 'success') {
            // Ödeme başarılı
            $updateStmt = $conn->prepare("
                UPDATE orders SET 
                    payment_status = 'paid',
                    payment_method = 'PayTR',
                    payment_reference = ?,
                    updated_at = NOW()
                WHERE order_number = ?
            ");
            
            // PayTR işlem bilgilerini JSON olarak hazırla
            $paymentReference = json_encode([
                'transaction_id' => $merchant_oid . '_' . time(),
                'total_amount' => floatval($total_amount) / 100,
                'installment_count' => intval($installment_count),
                'payment_type' => $payment_type,
                'processed_at' => date('Y-m-d H:i:s')
            ]);
            
            $updateStmt->execute([
                $paymentReference,
                $merchant_oid
            ]);

            // Biletlerin durumunu güncelle ve QR kodları oluştur
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
                
                // Siparişteki tüm biletleri al
                $ticketsForQR = $conn->prepare("
                    SELECT id, event_id, ticket_number, quantity, created_at 
                    FROM tickets 
                    WHERE order_id = ? AND qr_code_path IS NULL
                ");
                $ticketsForQR->execute([$order['id']]);
                $ticketsToProcess = $ticketsForQR->fetchAll(PDO::FETCH_ASSOC);
                
                // Her bilet için QR kod oluştur
                foreach ($ticketsToProcess as $ticketData) {
                    $qrData = json_encode([
                        'ticket_id' => $ticketData['id'],
                        'ticket_number' => $ticketData['ticket_number'],
                        'event_id' => $ticketData['event_id'],
                        'quantity' => $ticketData['quantity'],
                        'purchase_time' => $ticketData['created_at'],
                        'verification_code' => strtoupper(substr(md5($ticketData['id'] . $ticketData['event_id'] . time()), 0, 8)),
                        'biletjack_url' => 'https://biletjack.com/verify/' . $ticketData['ticket_number']
                    ]);
                    
                    // QR kod oluştur ve kaydet
                    $qrCodePath = $ticketManager->generateAndSaveQRCode($qrData, $ticketData['id']);
                    
                    if ($qrCodePath) {
                        $updateQRStmt = $conn->prepare("UPDATE tickets SET qr_code_path = ? WHERE id = ?");
                        $updateQRStmt->execute([$qrCodePath, $ticketData['id']]);
                    }
                }
                
                error_log('PayTR: QR codes generated for order ' . $merchant_oid);
            } catch (Exception $qrError) {
                error_log('PayTR: QR code generation error for order ' . $merchant_oid . ' - ' . $qrError->getMessage());
            }

            // Koltuklu biletler için koltuk durumunu güncelle (seats & tickets.seat_id kullanarak)
            $seatUpdateStmt = $conn->prepare("
                UPDATE seats s
                INNER JOIN tickets t ON t.seat_id = s.id
                INNER JOIN events e ON t.event_id = e.id
                SET s.status = CASE WHEN e.seating_type = 'reservation' THEN 'reserved' ELSE 'sold' END
                WHERE t.order_id = ? AND t.seat_id IS NOT NULL
            ");
            $seatUpdateStmt->execute([$order['id']]);

            // Başarılı ödeme sonrası kullanıcının sepetini temizle
            $clearCartStmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
            $clearCartStmt->execute([$order['user_id']]);

            // E-posta gönderimi için sipariş verilerini hazırla
            try {
                require_once 'classes/TicketEmailSender.php';
                
                // Sipariş bilgilerini al
                $orderInfoStmt = $conn->prepare("
                    SELECT o.*, u.first_name, u.last_name, u.email, u.phone 
                    FROM orders o 
                    JOIN users u ON o.user_id = u.id 
                    WHERE o.id = ?
                ");
                $orderInfoStmt->execute([$order['id']]);
                $orderInfo = $orderInfoStmt->fetch(PDO::FETCH_ASSOC);
                
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
                
                if ($orderInfo && $tickets) {
                    $billingInfo = json_decode($orderInfo['billing_info'], true);
                    
                    $emailData = [
                        'orderNumber' => $orderInfo['order_number'],
                        'customerInfo' => [
                            'first_name' => $orderInfo['first_name'],
                            'last_name' => $orderInfo['last_name'],
                            'email' => $orderInfo['email'],
                            'phone' => $orderInfo['phone']
                        ],
                        'tickets' => array_map(function($ticket) {
                            return [
                                'ticket_code' => $ticket['ticket_number'],
                                'event_title' => $ticket['event_title'],
                                'event_date' => $ticket['event_date'],
                                'venue_name' => $ticket['venue_name'],
                                'ticket_type' => $ticket['ticket_type_name'] ?? 'Genel',
                                'price' => $ticket['price'],
                                'quantity' => $ticket['quantity'],
                                'qr_code_path' => $ticket['qr_code_path'],
                                'seat_labels' => $ticket['seat_labels'] ?? null
                            ];
                        }, $tickets)
                    ];
                    
                    $emailSender = new TicketEmailSender($conn);
                    $emailResult = $emailSender->sendTicketEmail($emailData);
                    
                    if ($emailResult['success']) {
                        error_log('PayTR: Email sent successfully for order ' . $merchant_oid);
                    } else {
                        error_log('PayTR: Email sending failed for order ' . $merchant_oid . ' - ' . ($emailResult['message'] ?? 'Unknown error'));
                    }
                }
            } catch (Exception $emailError) {
                error_log('PayTR: Email sending error for order ' . $merchant_oid . ' - ' . $emailError->getMessage());
            }

            error_log('PayTR: Payment successful for order ' . $merchant_oid);
            
        } else {
            // Ödeme başarısız
            $updateStmt = $conn->prepare("
                UPDATE orders SET 
                    payment_status = 'failed',
                    payment_method = 'PayTR',
                    payment_reference = ?,
                    updated_at = NOW()
                WHERE order_number = ?
            ");
            
            // Başarısız ödeme bilgilerini JSON olarak hazırla
            $failedPaymentReference = json_encode([
                'failed_reason_code' => $failed_reason_code,
                'failed_reason_msg' => $failed_reason_msg,
                'failed_at' => date('Y-m-d H:i:s')
            ]);
            
            $updateStmt->execute([
                $failedPaymentReference,
                $merchant_oid
            ]);

            // Başarısız ödeme durumunda koltukları serbest bırak
            $seatStmt = $conn->prepare("
                UPDATE seats s
                INNER JOIN tickets t ON t.seat_id = s.id
                SET s.status = 'available'
                WHERE t.order_id = ? AND t.seat_id IS NOT NULL
            ");
            $seatStmt->execute([$order['id']]);

            error_log('PayTR: Payment failed for order ' . $merchant_oid . ' - Reason: ' . $failed_reason_msg);
        }

        // Transaction commit
        $conn->commit();
        
        // PayTR'a başarılı yanıt ver
        echo 'OK';
        
    } catch (Exception $e) {
        // Hata durumunda rollback
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log('PayTR Callback Error: ' . $e->getMessage());
        echo 'OK'; // Yine de OK döndür ki PayTR tekrar denemesin
    }

} catch (Throwable $e) {
    error_log('PayTR Callback Fatal Error: ' . $e->getMessage());
    echo 'OK'; // Kritik hatalarda bile OK döndür
}

?>