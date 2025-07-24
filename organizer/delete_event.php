<?php
require_once '../includes/session.php';
require_once '../config/database.php';

// Organizatör kontrolü
requireOrganizer();

// JSON response için header
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
    exit();
}

$eventId = $_POST['event_id'] ?? null;

if (!$eventId) {
    echo json_encode(['success' => false, 'message' => 'Etkinlik ID gerekli']);
    exit();
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Etkinliğin organizatöre ait olduğunu kontrol et
    $checkSql = "SELECT id, image_url FROM events WHERE id = :event_id AND organizer_id = :organizer_id";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([
        'event_id' => $eventId,
        'organizer_id' => $_SESSION['user_id']
    ]);
    
    $event = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        echo json_encode(['success' => false, 'message' => 'Etkinlik bulunamadı veya yetkiniz yok']);
        exit();
    }
    
    // Transaction başlat
    $pdo->beginTransaction();
    
    // Önce bilet türlerini sil
    $deleteTicketsSql = "DELETE FROM ticket_types WHERE event_id = :event_id";
    $deleteTicketsStmt = $pdo->prepare($deleteTicketsSql);
    $deleteTicketsStmt->execute(['event_id' => $eventId]);
    
    // Etkinliği sil
    $deleteEventSql = "DELETE FROM events WHERE id = :event_id";
    $deleteEventStmt = $pdo->prepare($deleteEventSql);
    $deleteEventStmt->execute(['event_id' => $eventId]);
    
    // Etkinlik görselini sil (varsa)
    if ($event['image_url'] && file_exists('../' . $event['image_url'])) {
        unlink('../' . $event['image_url']);
    }
    
    // Aktivite logu ekle
    $logSql = "INSERT INTO activity_logs (user_id, action, description, created_at) VALUES (?, ?, ?, NOW())";
    $logStmt = $pdo->prepare($logSql);
    $logStmt->execute([
        $_SESSION['user_id'],
        'event_deleted',
        'Etkinlik silindi: ID ' . $eventId
    ]);
    
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => 'Etkinlik başarıyla silindi']);
    
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>