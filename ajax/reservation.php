<?php
require_once '../includes/session.php';
require_once '../config/database.php';

header('Content-Type: application/json');

// Giriş kontrolü
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Giriş yapmanız gerekiyor']);
    exit();
}

// Sadece müşteriler rezervasyon yapabilir
if ($_SESSION['user_type'] !== 'customer') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Sadece müşteriler rezervasyon yapabilir']);
    exit();
}

$database = new Database();
$pdo = $database->getConnection();

$action = $_POST['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true);
if ($input) {
    $action = $input['action'] ?? $action;
}

if ($action !== 'create_reservation') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz işlem']);
    exit();
}

try {
    $eventId = (int)($input['event_id'] ?? 0);
    $seats = $input['seats'] ?? [];
    $userId = $_SESSION['user_id'];
    
    if (!$eventId || empty($seats)) {
        echo json_encode(['success' => false, 'message' => 'Geçersiz veri']);
        exit();
    }
    
    // Etkinliğin rezervasyon sistemi olduğunu kontrol et
    $eventStmt = $pdo->prepare("SELECT seating_type FROM events WHERE id = ?");
    $eventStmt->execute([$eventId]);
    $event = $eventStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event || $event['seating_type'] !== 'reservation') {
        echo json_encode(['success' => false, 'message' => 'Bu etkinlik rezervasyon sistemi kullanmıyor']);
        exit();
    }
    
    $pdo->beginTransaction();
    
    $reservedSeats = [];
    $failedSeats = [];
    
    foreach ($seats as $seat) {
        $seatId = (int)$seat['seat_id'];
        
        // Koltuk durumunu kontrol et
        $seatCheckStmt = $pdo->prepare("SELECT status FROM seats WHERE id = ? AND event_id = ?");
        $seatCheckStmt->execute([$seatId, $eventId]);
        $seatStatus = $seatCheckStmt->fetchColumn();
        
        if ($seatStatus !== 'available') {
            $failedSeats[] = $seat;
            continue;
        }
        
        // Koltuk durumunu 'reserved' yap
        $updateSeatStmt = $pdo->prepare("UPDATE seats SET status = 'reserved' WHERE id = ? AND status = 'available'");
        $updateResult = $updateSeatStmt->execute([$seatId]);
        
        if ($updateSeatStmt->rowCount() === 0) {
            $failedSeats[] = $seat;
            continue;
        }
        
        // Rezervasyon kaydı oluştur
        $reservationStmt = $pdo->prepare("
            INSERT INTO reservations (event_id, seat_id, user_id, status, created_at) 
            VALUES (?, ?, ?, 'pending', NOW())
        ");
        $reservationStmt->execute([$eventId, $seatId, $userId]);
        
        $reservedSeats[] = $seat;
    }
    
    if (empty($reservedSeats)) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false, 
            'message' => 'Seçtiğiniz koltuklar artık müsait değil'
        ]);
        exit();
    }
    
    $pdo->commit();
    
    $message = count($reservedSeats) . ' koltuk için rezervasyon talebi oluşturuldu';
    if (!empty($failedSeats)) {
        $message .= '. ' . count($failedSeats) . ' koltuk artık müsait değil';
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'reserved_count' => count($reservedSeats),
        'failed_count' => count($failedSeats)
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Reservation error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Rezervasyon oluşturulurken hata oluştu']);
}
?>