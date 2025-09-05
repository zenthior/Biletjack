<?php
require_once '../includes/session.php';
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Giriş yapmanız gerekiyor.']);
    exit;
}

if ($_SESSION['user_type'] !== 'customer') {
    echo json_encode(['success' => false, 'message' => 'Sadece müşteriler yorum yapabilir.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    $eventId = (int)($_POST['event_id'] ?? 0);
    $rating = (int)($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    $userId = $_SESSION['user_id'];
    
    // Validasyon
    if ($eventId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Geçersiz etkinlik ID.']);
        exit;
    }
    
    if ($rating < 1 || $rating > 5) {
        echo json_encode(['success' => false, 'message' => 'Puan 1-5 arasında olmalıdır.']);
        exit;
    }
    
    if (empty($comment)) {
        echo json_encode(['success' => false, 'message' => 'Yorum boş olamaz.']);
        exit;
    }
    
    // Etkinliğin var olup olmadığını kontrol et
    $eventCheck = $pdo->prepare("SELECT id FROM events WHERE id = ?");
    $eventCheck->execute([$eventId]);
    if (!$eventCheck->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Etkinlik bulunamadı.']);
        exit;
    }
    
    // Kullanıcının bu etkinlik için bilet satın alıp almadığını kontrol et
    $ticketCheck = $pdo->prepare("
        SELECT COUNT(*) FROM tickets t 
        JOIN orders o ON t.order_id = o.id 
        WHERE o.user_id = ? AND t.event_id = ? AND o.payment_status = 'completed'
    ");
    $ticketCheck->execute([$userId, $eventId]);
    $hasTicket = $ticketCheck->fetchColumn() > 0;
    
    if (!$hasTicket) {
        echo json_encode(['success' => false, 'message' => 'Bu etkinlik için bilet satın almış olmanız gerekiyor.']);
        exit;
    }
    
    // Daha önce yorum yapıp yapmadığını kontrol et
    $existingReview = $pdo->prepare("SELECT id FROM event_comments WHERE event_id = ? AND user_id = ?");
    $existingReview->execute([$eventId, $userId]);
    if ($existingReview->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Bu etkinlik için zaten yorum yapmışsınız.']);
        exit;
    }
    
    // Yorumu ekle
    $insertReview = $pdo->prepare("
        INSERT INTO event_comments (event_id, user_id, comment, rating, status, created_at) 
        VALUES (?, ?, ?, ?, 'pending', NOW())
    ");
    $insertReview->execute([$eventId, $userId, $comment, $rating]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Yorumunuz başarıyla gönderildi. Organizatör onayından sonra yayınlanacaktır.'
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Bir hata oluştu: ' . $e->getMessage()]);
}
?>