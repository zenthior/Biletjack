<?php
require_once '../includes/session.php';
require_once '../config/database.php';

// Müşteri kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit();
}

// JSON verilerini al
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['ticket_id']) || !isset($input['target_user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Eksik parametreler']);
    exit();
}

$ticket_id = (int)$input['ticket_id'];
$target_user_id = (int)$input['target_user_id'];
$current_user_id = $_SESSION['user_id'];

try {
    $pdo->beginTransaction();
    
    // Bileti kontrol et - kullanıcının sahip olduğu ve aktif olduğu bileti
    $ticket_check = $pdo->prepare("
        SELECT t.*, e.title as event_title, o.user_id as owner_id
        FROM tickets t 
        JOIN orders o ON t.order_id = o.id 
        JOIN events e ON t.event_id = e.id
        WHERE t.id = ? AND o.user_id = ? AND t.status = 'active'
    ");
    $ticket_check->execute([$ticket_id, $current_user_id]);
    $ticket = $ticket_check->fetch(PDO::FETCH_ASSOC);
    
    if (!$ticket) {
        throw new Exception('Bilet bulunamadı veya size ait değil');
    }
    
    // Hedef kullanıcıyı kontrol et
    $user_check = $pdo->prepare("SELECT id, first_name, last_name FROM users WHERE id = ? AND user_type = 'customer'");
    $user_check->execute([$target_user_id]);
    $target_user = $user_check->fetch(PDO::FETCH_ASSOC);
    
    if (!$target_user) {
        throw new Exception('Hedef kullanıcı bulunamadı veya geçerli değil');
    }
    
    // Kendi kendine aktarım kontrolü
    if ($target_user_id == $current_user_id) {
        throw new Exception('Bileti kendinize aktaramazsınız');
    }
    
    // Hedef kullanıcı için yeni sipariş oluştur
    $order_number = 'BJ' . date('Ymd') . strtoupper(substr(uniqid(), -6));
    $new_order = $pdo->prepare("
        INSERT INTO orders (user_id, order_number, event_id, total_amount, payment_status, created_at) 
        SELECT ?, ?, event_id, 0, 'transferred', NOW() 
        FROM tickets WHERE id = ?
    ");
    $new_order->execute([$target_user_id, $order_number, $ticket_id]);
    $new_order_id = $pdo->lastInsertId();
    
    // Bileti yeni siparişe aktar
    $transfer_ticket = $pdo->prepare("UPDATE tickets SET order_id = ? WHERE id = ?");
    $transfer_ticket->execute([$new_order_id, $ticket_id]);
    
    // Mevcut kullanıcı bilgilerini al
    $current_user_query = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
    $current_user_query->execute([$current_user_id]);
    $current_user = $current_user_query->fetch(PDO::FETCH_ASSOC);
    
    // Aktivite logu ekle
    $activity_log = $pdo->prepare("
        INSERT INTO activity_logs (user_id, action, description, created_at) 
        VALUES (?, 'ticket_transferred', ?, NOW())
    ");
    
    $description = sprintf(
        'Bilet aktarıldı: %s etkinliği - %s (%s) kullanıcısından %s (%s) kullanıcısına',
        $ticket['event_title'],
        $current_user['first_name'] . ' ' . $current_user['last_name'],
        $current_user_id,
        $target_user['first_name'] . ' ' . $target_user['last_name'],
        $target_user_id
    );
    
    $activity_log->execute([$current_user_id, $description]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Bilet başarıyla ' . $target_user['first_name'] . ' ' . $target_user['last_name'] . ' kullanıcısına aktarıldı'
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>