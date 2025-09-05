<?php
session_start();
require_once '../config/database.php';

// QR yetkili girişi kontrolü
if (!isset($_SESSION['qr_staff_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Sadece POST istekleri kabul edilir']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$ticket_id = isset($input['ticket_id']) ? (int)$input['ticket_id'] : 0;

if ($ticket_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz bilet ID']);
    exit;
}

try {
    // Bilet var mı ve onaylanabilir durumda mı kontrol et
    $stmt = $pdo->prepare("
        SELECT 
            t.id,
            t.ticket_number,
            t.status,
            t.quantity,
            e.event_date,
            e.title as event_title
        FROM tickets t
        JOIN events e ON t.event_id = e.id
        WHERE t.id = ? AND t.status = 'active'
    ");
    
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ticket) {
        echo json_encode([
            'success' => false, 
            'message' => 'Bilet bulunamadı veya geçersiz'
        ]);
        exit;
    }
    
    // Bilet daha önce onaylandı mı kontrol et
    $stmt = $pdo->prepare("
        SELECT id FROM ticket_verifications WHERE ticket_id = ?
    ");
    
    $stmt->execute([$ticket_id]);
    $existing_verification = $stmt->fetch();
    
    if ($existing_verification) {
        echo json_encode([
            'success' => false, 
            'message' => 'Bu bilet daha önce onaylanmış'
        ]);
        exit;
    }
    
    // Etkinlik tarihi kontrolü kaldırıldı - biletler her zaman onaylanabilir
    
    // Bilet onaylama kaydını ekle
    $stmt = $pdo->prepare("
        INSERT INTO ticket_verifications 
        (ticket_id, qr_staff_id, verification_time) 
        VALUES (?, ?, NOW())
    ");
    
    $stmt->execute([$ticket_id, $_SESSION['qr_staff_id']]);
    
    // Başarılı onaylama
    echo json_encode([
        'success' => true,
        'message' => 'Bilet başarıyla onaylandı',
        'ticket_number' => $ticket['ticket_number'],
        'event_title' => $ticket['event_title'],
        'quantity' => $ticket['quantity'],
        'verified_at' => date('d.m.Y H:i')
    ]);
    
} catch (Exception $e) {
    error_log('Bilet onaylama hatası: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Sistem hatası oluştu'
    ]);
}
?>