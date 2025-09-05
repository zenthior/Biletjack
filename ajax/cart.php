<?php
require_once '../includes/session.php';
require_once '../config/database.php';

// Giriş kontrolü
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Giriş yapmanız gerekiyor']);
    exit();
}

// Role kontrolü (yalnızca müşteri)
if (($_SESSION['user_type'] ?? null) !== 'customer') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Sadece müşteri hesapları sepet işlemi yapabilir']);
    exit();
}

$database = new Database();
$pdo = $database->getConnection();
$userId = $_SESSION['user_id'];

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'add':
        $eventId = $_POST['event_id'];
        $ticketTypeId = $_POST['ticket_type_id'];
        $eventName = $_POST['event_name'];
        $ticketName = $_POST['ticket_name'];
        $price = $_POST['price'];
        $quantity = $_POST['quantity'] ?? 1;
        
        try {
            // Mevcut sepet öğesini kontrol et
            $stmt = $pdo->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND event_id = ? AND ticket_type_id = ?");
            $stmt->execute([$userId, $eventId, $ticketTypeId]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Mevcut öğeyi güncelle (maksimum 10 bilet)
                $newQuantity = min($existing['quantity'] + $quantity, 10);
                $stmt = $pdo->prepare("UPDATE cart SET quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$newQuantity, $existing['id']]);
            } else {
                // Yeni öğe ekle
                $stmt = $pdo->prepare("INSERT INTO cart (user_id, event_id, ticket_type_id, event_name, ticket_name, price, quantity) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$userId, $eventId, $ticketTypeId, $eventName, $ticketName, $price, $quantity]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Bilet sepete eklendi']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Hata oluştu: ' . $e->getMessage()]);
        }
        break;
        
    case 'get':
        try {
            $stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->execute([$userId]);
            $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'items' => $cartItems]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Hata oluştu: ' . $e->getMessage()]);
        }
        break;
        
    case 'update':
        $cartId = $_POST['cart_id'];
        $quantity = $_POST['quantity'];
        
        try {
            // Koltuklu biletlerde miktar değiştirilemez
            $check = $pdo->prepare("SELECT seat_id FROM cart WHERE id = ? AND user_id = ?");
            $check->execute([$cartId, $userId]);
            $row = $check->fetch(PDO::FETCH_ASSOC);
            if ($row && !empty($row['seat_id'])) {
                echo json_encode(['success' => false, 'message' => 'Koltuklu biletlerde miktar değiştirilemez']);
                break;
            }

            $stmt = $pdo->prepare("UPDATE cart SET quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?");
            $stmt->execute([$quantity, $cartId, $userId]);
            
            echo json_encode(['success' => true, 'message' => 'Miktar güncellendi']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Hata oluştu: ' . $e->getMessage()]);
        }
        break;
        
    case 'remove':
        $cartId = $_POST['cart_id'];
        
        try {
            // İlgili sepet öğesinin seat_id ve event_id'sini al
            $stmt = $pdo->prepare("SELECT seat_id, event_id FROM cart WHERE id = ? AND user_id = ?");
            $stmt->execute([$cartId, $userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            $pdo->beginTransaction();

            if ($row && !empty($row['seat_id'])) {
                // Reserved olan koltuğu geri müsait yap
                $relStmt = $pdo->prepare("UPDATE seats SET status = 'available' WHERE id = ? AND status = 'reserved'");
                $relStmt->execute([$row['seat_id']]);
            }

            // Sepet öğesini sil
            $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
            $stmt->execute([$cartId, $userId]);

            $pdo->commit();
            
            echo json_encode(['success' => true, 'message' => 'Ürün sepetten kaldırıldı']);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Hata oluştu: ' . $e->getMessage()]);
        }
        break;
        
    case 'clear':
        try {
            // Kullanıcının sepetteki rezerve koltuklarını topla
            $stmt = $pdo->prepare("SELECT seat_id FROM cart WHERE user_id = ? AND seat_id IS NOT NULL");
            $stmt->execute([$userId]);
            $seatsToRelease = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $pdo->beginTransaction();

            if (!empty($seatsToRelease)) {
                // Dinamik placeholder
                $placeholders = implode(',', array_fill(0, count($seatsToRelease), '?'));
                $relStmt = $pdo->prepare("UPDATE seats SET status = 'available' WHERE id IN ($placeholders) AND status = 'reserved'");
                $relStmt->execute($seatsToRelease);
            }

            // Sepeti temizle
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$userId]);

            $pdo->commit();
            
            echo json_encode(['success' => true, 'message' => 'Sepet temizlendi']);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Hata oluştu: ' . $e->getMessage()]);
        }
        break;
        
    case 'count':
        try {
            $stmt = $pdo->prepare("SELECT SUM(quantity) as total_items, SUM(price * quantity) as total_amount FROM cart WHERE user_id = ?");
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            echo json_encode([
                'success' => true, 
                'count' => $result['total_items'] ?? 0,
                'total' => $result['total_amount'] ?? 0
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Hata oluştu: ' . $e->getMessage()]);
        }
        break;
        
    case 'add_seat':
        $eventId = (int)($_POST['event_id'] ?? 0);
        $seatId = (int)($_POST['seat_id'] ?? 0);
        $eventName = $_POST['event_name'] ?? '';
        $seatInfo = $_POST['seat_info'] ?? '';
        $quantity = 1; // koltuklar için her zaman 1

        if (!$eventId || !$seatId) {
            echo json_encode(['success' => false, 'message' => 'Eksik parametre']);
            break;
        }

        try {
            // Koltuğu ve kategori fiyatını doğrula
            $stmt = $pdo->prepare("
                SELECT s.id, s.event_id, s.status, s.category_id, s.row_number, s.seat_number,
                       sc.name AS category_name, sc.price
                FROM seats s
                LEFT JOIN seat_categories sc ON sc.id = s.category_id
                WHERE s.id = ? AND s.event_id = ?
            ");
            $stmt->execute([$seatId, $eventId]);
            $seat = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$seat) {
                echo json_encode(['success' => false, 'message' => 'Koltuk bulunamadı']);
                break;
            }

            // Aynı koltuk zaten sepette mi?
            $stmt = $pdo->prepare("SELECT id FROM cart WHERE user_id = ? AND seat_id = ?");
            $stmt->execute([$userId, $seatId]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Koltuk zaten sepette']);
                break;
            }

            // Rezerve süresi dolmuş (örn. 15 dk) koltukları otomatik serbest bırak
            $holdMinutes = 15; // İstediğiniz süreyi buradan ayarlayabilirsiniz
            $cleanupSql = "
                UPDATE seats s
                LEFT JOIN cart c
                    ON c.seat_id = s.id
                    AND c.updated_at > DATE_SUB(NOW(), INTERVAL $holdMinutes MINUTE)
                SET s.status = 'available'
                WHERE s.event_id = ?
                  AND s.status = 'reserved'
                  AND c.id IS NULL
            ";
            $cleanup = $pdo->prepare($cleanupSql);
            $cleanup->execute([$eventId]);

            $pdo->beginTransaction();

            // Koltuğu atomik olarak rezerve et
            $lock = $pdo->prepare("UPDATE seats SET status = 'reserved' WHERE id = ? AND event_id = ? AND status = 'available'");
            $lock->execute([$seatId, $eventId]);
            if ($lock->rowCount() === 0) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Koltuk artık müsait değil']);
                break;
            }

            // Fiyatı DB’den al
            $price = (float)$seat['price'];

            // Seat info boşsa oluştur
            if (!$seatInfo) {
                $rowLabel = chr(64 + (int)$seat['row_number']); // A, B, C...
                $seatInfo = $rowLabel . $seat['seat_number'] . ' - ' . ($seat['category_name'] ?? 'Kategori');
            }

            // Sepete ekle (ticket_type_id NULL, seat alanları dolu)
            $stmt = $pdo->prepare("
                INSERT INTO cart (user_id, event_id, ticket_type_id, event_name, ticket_name, price, quantity, seat_id)
                VALUES (?, ?, NULL, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $userId,
                $eventId,
                $eventName,
                $seatInfo,
                $price,
                1,
                $seatId
            ]);

            $pdo->commit();

            echo json_encode(['success' => true, 'message' => 'Koltuk sepete eklendi']);
            break;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Hata oluştu: ' . $e->getMessage()]);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Geçersiz işlem']);
        break;
}
?>