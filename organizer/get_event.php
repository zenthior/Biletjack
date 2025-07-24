<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Event.php';
require_once '../includes/session.php';

// Organizatör kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'organizer') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit;
}

// Onay kontrolü
if (!isOrganizerApproved()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Hesabınız henüz onaylanmamış']);
    exit;
}

header('Content-Type: application/json');

try {
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        throw new Exception('Etkinlik ID gerekli');
    }
    
    $eventId = (int)$_GET['id'];
    $organizerId = $_SESSION['user_id'];
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Etkinliğin bu organizatöre ait olup olmadığını kontrol et
    $query = "SELECT e.*, c.name as category_name 
              FROM events e 
              LEFT JOIN categories c ON e.category_id = c.id 
              WHERE e.id = :event_id AND e.organizer_id = :organizer_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':event_id', $eventId, PDO::PARAM_INT);
    $stmt->bindParam(':organizer_id', $organizerId, PDO::PARAM_INT);
    $stmt->execute();
    
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        throw new Exception('Etkinlik bulunamadı veya bu etkinliği düzenleme yetkiniz yok');
    }
    
    echo json_encode([
        'success' => true,
        'event' => $event
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>