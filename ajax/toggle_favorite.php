<?php
require_once '../config/database.php';
require_once '../includes/session.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? null) !== 'customer') {
        echo json_encode(['success' => false, 'message' => 'Favori eklemek için müşteri olarak giriş yapın.']);
        exit;
    }

    $eventId = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
    if ($eventId <= 0) {
        throw new Exception('Geçersiz etkinlik.');
    }

    $db = new Database();
    $pdo = $db->getConnection();

    // Favorites tablosu yoksa oluştur
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

    // Var mı?
    $check = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND event_id = ?");
    $check->execute([$userId, $eventId]);
    $exists = $check->fetchColumn();

    if ($exists) {
        $del = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND event_id = ?");
        $del->execute([$userId, $eventId]);
        echo json_encode(['success' => true, 'favorited' => false, 'message' => 'Favorilerden kaldırıldı']);
    } else {
        // Etkinlik var mı kontrol (published)
        $ev = $pdo->prepare("SELECT id FROM events WHERE id = ? AND status = 'published'");
        $ev->execute([$eventId]);
        if (!$ev->fetchColumn()) {
            throw new Exception('Etkinlik bulunamadı.');
        }

        $ins = $pdo->prepare("INSERT INTO favorites (user_id, event_id) VALUES (?, ?)");
        $ins->execute([$userId, $eventId]);
        echo json_encode(['success' => true, 'favorited' => true, 'message' => 'Favorilere eklendi']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}