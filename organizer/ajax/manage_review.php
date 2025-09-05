<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

// Sadece organizatörler erişebilir
if (!isLoggedIn() || $_SESSION['user_type'] !== 'organizer') {
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
    exit;
}

$reviewId = filter_input(INPUT_POST, 'review_id', FILTER_VALIDATE_INT);
$action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);

if (!$reviewId || !in_array($action, ['approve', 'reject'])) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz parametreler.']);
    exit;
}

try {
    // Organizatörün bu yorumu yönetme yetkisi var mı kontrol et
    $checkQuery = $pdo->prepare("
        SELECT ec.id 
        FROM event_comments ec
        JOIN events e ON ec.event_id = e.id
        WHERE ec.id = ? AND e.organizer_id = ?
    ");
    $checkQuery->execute([$reviewId, $_SESSION['organizer_id']]);
    
    if (!$checkQuery->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Bu yorumu yönetme yetkiniz yok.']);
        exit;
    }
    
    // Yorum durumunu güncelle
    $newStatus = $action === 'approve' ? 'approved' : 'rejected';
    $updateQuery = $pdo->prepare("UPDATE event_comments SET status = ?, updated_at = NOW() WHERE id = ?");
    $updateQuery->execute([$newStatus, $reviewId]);
    
    $message = $action === 'approve' ? 'Yorum onaylandı.' : 'Yorum reddedildi.';
    echo json_encode(['success' => true, 'message' => $message]);
    
} catch (Exception $e) {
    error_log("Review management error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Bir hata oluştu.']);
}
?>