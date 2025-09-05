<?php
require_once '../includes/session.php';
require_once '../config/database.php';

// Organizatör kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'organizer') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek metodu']);
    exit;
}

$database = new Database();
$pdo = $database->getConnection();

$action = $_POST['action'] ?? '';
$reservation_id = $_POST['reservation_id'] ?? '';

if (empty($action) || empty($reservation_id)) {
    echo json_encode(['success' => false, 'message' => 'Eksik parametreler']);
    exit;
}

try {
    // Rezervasyonun organizatöre ait olduğunu kontrol et
    $checkQuery = "SELECT r.*, e.organizer_id, e.title as event_title, s.seat_number, s.row_number 
                   FROM reservations r 
                   JOIN events e ON r.event_id = e.id 
                   JOIN seats s ON r.seat_id = s.id 
                   WHERE r.id = ? AND e.organizer_id = ?";
    $checkStmt = $pdo->prepare($checkQuery);
    $checkStmt->execute([$reservation_id, $_SESSION['user_id']]);
    $reservation = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reservation) {
        echo json_encode(['success' => false, 'message' => 'Rezervasyon bulunamadı veya yetkiniz yok']);
        exit;
    }
    
    $pdo->beginTransaction();
    
    if ($action === 'approve') {
        // Koltuğun hala müsait olduğunu kontrol et
        $seatCheckQuery = "SELECT status FROM seats WHERE id = ?";
        $seatCheckStmt = $pdo->prepare($seatCheckQuery);
        $seatCheckStmt->execute([$reservation['seat_id']]);
        $seatStatus = $seatCheckStmt->fetchColumn();
        
        if ($seatStatus !== 'reserved') {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Koltuk artık müsait değil']);
            exit;
        }
        
        // Rezervasyonu onayla
        $updateReservationQuery = "UPDATE reservations SET status = 'approved', approved_at = NOW(), approved_by = ? WHERE id = ?";
        $updateReservationStmt = $pdo->prepare($updateReservationQuery);
        $updateReservationStmt->execute([$_SESSION['user_id'], $reservation_id]);
        
        // Koltuğu satıldı olarak işaretle
        $updateSeatQuery = "UPDATE seats SET status = 'sold' WHERE id = ?";
        $updateSeatStmt = $pdo->prepare($updateSeatQuery);
        $updateSeatStmt->execute([$reservation['seat_id']]);
        
        $message = 'Rezervasyon başarıyla onaylandı';
        
    } elseif ($action === 'reject') {
        // Rezervasyonu reddet
        $updateReservationQuery = "UPDATE reservations SET status = 'rejected', approved_at = NOW(), approved_by = ? WHERE id = ?";
        $updateReservationStmt = $pdo->prepare($updateReservationQuery);
        $updateReservationStmt->execute([$_SESSION['user_id'], $reservation_id]);
        
        // Koltuğu tekrar müsait yap
        $updateSeatQuery = "UPDATE seats SET status = 'available' WHERE id = ?";
        $updateSeatStmt = $pdo->prepare($updateSeatQuery);
        $updateSeatStmt->execute([$reservation['seat_id']]);
        
        $message = 'Rezervasyon reddedildi';
        
    } else {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Geçersiz işlem']);
        exit;
    }
    
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => $message]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Bir hata oluştu: ' . $e->getMessage()]);
}
?>