<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Oturum bulunamadı.']);
    exit;
}

try {
    $db = new Database();
    $pdo = $db->getConnection();

    // Bildirim tablosu yoksa oluştur
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            related_event_id INT NULL,
            created_by INT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (related_event_id) REFERENCES events(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $uid = (int)($_SESSION['user_id'] ?? 0);
    if (!$uid) {
        echo json_encode(['success' => false, 'message' => 'Oturum bulunamadı.']);
        exit;
    }

    // GET: Listele
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'list') {
        $stmt = $pdo->prepare("
            SELECT id, title, message, related_event_id, is_read, created_at
            FROM notifications
            WHERE user_id = ?
            ORDER BY is_read ASC, created_at DESC
            LIMIT 50
        ");
        $stmt->execute([$uid]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $unreadStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $unreadStmt->execute([$uid]);
        $unread = (int)$unreadStmt->fetchColumn();

        echo json_encode(['success' => true, 'items' => $items, 'unread' => $unread]);
        exit;
    }

    // POST: Okundu işaretle / tümünü okundu
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'mark_read') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
                $stmt->execute([$id, $uid]);
            }
            echo json_encode(['success' => true]);
            exit;
        }

        if ($action === 'mark_all_read') {
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
            $stmt->execute([$uid]);
            echo json_encode(['success' => true]);
            exit;
        }

        echo json_encode(['success' => false, 'message' => 'Bilinmeyen işlem.']);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}