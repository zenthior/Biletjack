<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

// Organizatör kontrolü
if (!isLoggedIn() || $_SESSION['user_type'] !== 'organizer') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit();
}

$database = new Database();
$pdo = $database->getConnection();

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$reservationId = (int)($input['reservation_id'] ?? 0);
$organizerId = $_SESSION['user_id'];

if (!$reservationId || !in_array($action, ['approve', 'reject'])) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
    exit();
}

try {
    // Rezervasyonun bu organizatöre ait olduğunu kontrol et
    $checkStmt = $pdo->prepare("
        SELECT r.*, e.organizer_id, s.status as seat_status
        FROM reservations r
        JOIN events e ON r.event_id = e.id
        JOIN seats s ON r.seat_id = s.id
        WHERE r.id = ? AND e.organizer_id = ?
    ");
    $checkStmt->execute([$reservationId, $organizerId]);
    $reservation = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reservation) {
        echo json_encode(['success' => false, 'message' => 'Rezervasyon bulunamadı']);
        exit();
    }
    
    if ($reservation['status'] !== 'pending') {
        echo json_encode(['success' => false, 'message' => 'Bu rezervasyon zaten işlenmiş']);
        exit();
    }
    
    $pdo->beginTransaction();
    
    if ($action === 'approve') {
        // Koltuk durumunu kontrol et - sadece 'available' durumundaki koltuklar reddedilir
        if ($reservation['seat_status'] === 'sold' || $reservation['seat_status'] === 'occupied') {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Bu koltuk zaten satılmış']);
            exit();
        }
        
        // Rezervasyonu onayla
        $updateReservationStmt = $pdo->prepare("
            UPDATE reservations 
            SET status = 'approved', approved_at = NOW(), approved_by = ?
            WHERE id = ?
        ");
        $updateReservationStmt->execute([$organizerId, $reservationId]);
        
        // Koltuk durumunu 'sold' yap (satıldı olarak işaretle)
        $updateSeatStmt = $pdo->prepare("
            UPDATE seats 
            SET status = 'sold' 
            WHERE id = ?
        ");
        $updateSeatStmt->execute([$reservation['seat_id']]);
        
        $message = 'Rezervasyon onaylandı';
        
    } else { // reject
        $notes = $input['notes'] ?? '';
        
        // Rezervasyonu reddet
        $updateReservationStmt = $pdo->prepare("
            UPDATE reservations 
            SET status = 'rejected', approved_at = NOW(), approved_by = ?, notes = ?
            WHERE id = ?
        ");
        $updateReservationStmt->execute([$organizerId, $notes, $reservationId]);
        
        // Koltuk durumunu 'available' yap
        $updateSeatStmt = $pdo->prepare("
            UPDATE seats 
            SET status = 'available' 
            WHERE id = ?
        ");
        $updateSeatStmt->execute([$reservation['seat_id']]);
        
        $message = 'Rezervasyon reddedildi';
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => $message
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Reservation management error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'İşlem sırasında hata oluştu']);
}
?>