<?php
require_once '../includes/session.php';
require_once '../config/database.php';

header('Content-Type: application/json');

// İsteğe bağlı: login zorunluluğu
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Giriş yapmanız gerekiyor']);
    exit();
}

$database = new Database();
$pdo = $database->getConnection();

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$eventId = (int)($_GET['event_id'] ?? $_POST['event_id'] ?? 0);

if ($action !== 'get' || !$eventId) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
    exit();
}

try {
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

    $stmt = $pdo->prepare("
        SELECT s.*, sc.name AS category_name, sc.color AS category_color, sc.price
        FROM seats s
        LEFT JOIN seat_categories sc ON sc.id = s.category_id
        WHERE s.event_id = ?
        ORDER BY s.row_number, s.seat_number
    ");
    $stmt->execute([$eventId]);
    $seats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'seats' => $seats]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}