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
$status = $_POST['status'] ?? null;

if (!$eventId || !$status) {
    echo json_encode(['success' => false, 'message' => 'Etkinlik ID ve durum gerekli']);
    exit();
}

// Geçerli durumları kontrol et
$validStatuses = ['draft', 'published', 'cancelled', 'completed'];
if (!in_array($status, $validStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz durum']);
    exit();
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Etkinliğin organizatöre ait olduğunu kontrol et
    $checkSql = "SELECT id, title, status FROM events WHERE id = :event_id AND organizer_id = :organizer_id";
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
    
    // Durumu güncelle
    $updateSql = "UPDATE events SET status = :status, updated_at = NOW() WHERE id = :event_id";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute([
        'status' => $status,
        'event_id' => $eventId
    ]);
    
    // Aktivite logu ekle
    $statusTexts = [
        'draft' => 'Taslak',
        'published' => 'Yayınlandı',
        'cancelled' => 'İptal edildi',
        'completed' => 'Tamamlandı'
    ];
    
    $logSql = "INSERT INTO activity_logs (user_id, action, description, created_at) VALUES (?, ?, ?, NOW())";
    $logStmt = $pdo->prepare($logSql);
    $logStmt->execute([
        $_SESSION['user_id'],
        'event_status_updated',
        'Etkinlik durumu güncellendi: ' . $event['title'] . ' - ' . $statusTexts[$status]
    ]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Etkinlik durumu başarıyla güncellendi',
        'new_status' => $status,
        'status_text' => $statusTexts[$status]
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>