<?php
session_start();
require_once '../../config/database.php';

// Organizatör kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'organizer') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit;
}

try {
    $organizerId = $_SESSION['user_id'];
    
    // Rezervasyon sayılarını çek
    $stmt = $pdo->prepare("
        SELECT 
            status,
            COUNT(*) as count
        FROM reservations r
        JOIN events e ON r.event_id = e.id
        WHERE e.organizer_id = ?
        GROUP BY status
    ");
    
    $stmt->execute([$organizerId]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Sayıları organize et
    $counts = [
        'pending' => 0,
        'approved' => 0,
        'rejected' => 0
    ];
    
    foreach ($results as $result) {
        $counts[$result['status']] = (int)$result['count'];
    }
    
    echo json_encode([
        'success' => true,
        'counts' => $counts
    ]);
    
} catch (Exception $e) {
    error_log("Rezervasyon sayıları hatası: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Sayılar alınırken hata oluştu'
    ]);
}
?>