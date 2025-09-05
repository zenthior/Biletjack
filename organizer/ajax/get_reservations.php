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

$status = $_GET['status'] ?? '';
$organizerId = $_SESSION['user_id'];

if (!in_array($status, ['approved', 'rejected'])) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz durum']);
    exit();
}

try {
    // Rezervasyonları getir
    $stmt = $pdo->prepare("
        SELECT r.id, r.event_id, r.seat_id, r.user_id, r.status, r.created_at, r.approved_at, r.notes,
               e.title as event_title, e.event_date,
               s.row_number, s.seat_number, sc.name as category_name,
               u.first_name, u.last_name, u.email, u.phone
        FROM reservations r
        JOIN events e ON r.event_id = e.id
        JOIN seats s ON r.seat_id = s.id
        LEFT JOIN seat_categories sc ON s.category_id = sc.id
        JOIN users u ON r.user_id = u.id
        WHERE e.organizer_id = ? AND r.status = ?
        ORDER BY r.approved_at DESC, r.created_at DESC
        LIMIT 50
    ");
    $stmt->execute([$organizerId, $status]);
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'reservations' => $reservations
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Veritabanı hatası: ' . $e->getMessage()
    ]);
}
?>