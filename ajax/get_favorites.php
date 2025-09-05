<?php
require_once '../config/database.php';
require_once '../includes/session.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? null) !== 'customer') {
        echo json_encode(['success' => false, 'message' => 'Giriş yapmanız gerekiyor.', 'items' => []]);
        exit;
    }

    $db = new Database();
    $pdo = $db->getConnection();

    // Güvenlik için tablo yoksa oluştur
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS favorites (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            event_id INT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY user_event (user_id, event_id),
            INDEX idx_user (user_id),
            INDEX idx_event (event_id),
            CONSTRAINT fk_fav_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_fav_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $userId = (int)$_SESSION['user_id'];

    $stmt = $pdo->prepare("
        SELECT e.id, e.title, e.image_url, e.city, e.venue_name, e.event_date
        FROM favorites f
        JOIN events e ON e.id = f.event_id
        WHERE f.user_id = ?
        ORDER BY f.created_at DESC
    ");
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // Basit insan-okur tarih
    foreach ($rows as &$r) {
        if (!empty($r['event_date'])) {
            $dt = new DateTime($r['event_date']);
            $months = ['Oca','Şub','Mar','Nis','May','Haz','Tem','Ağu','Eyl','Eki','Kas','Ara'];
            $r['event_date_human'] = $dt->format('d') . ' ' . $months[$dt->format('n') - 1] . ' ' . $dt->format('Y') . ' - ' . $dt->format('H:i');
        } else {
            $r['event_date_human'] = '';
        }
    }

    echo json_encode(['success' => true, 'items' => $rows]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'items' => []]);
}