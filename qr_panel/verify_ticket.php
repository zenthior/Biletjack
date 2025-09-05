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
$ticket_code = isset($input['ticket_code']) ? trim($input['ticket_code']) : '';

if (empty($ticket_code)) {
    echo json_encode(['success' => false, 'message' => 'Bilet kodu gereklidir']);
    exit;
}

try {
    // Bilet bilgilerini getir
    $stmt = $pdo->prepare("
        SELECT 
            t.id,
            t.ticket_number,
            t.price,
            t.quantity,
            t.ticket_type,
            t.status,
            t.created_at,
            t.used_at,
            t.seat_labels,
            COALESCE(t.attendee_name, CONCAT(u.first_name, ' ', u.last_name)) as customer_name,
            COALESCE(t.attendee_email, u.email) as customer_email,
            COALESCE(t.attendee_phone, u.phone) as customer_phone,
            e.title as event_title,
            e.event_date,
            e.venue_name,
            CONCAT(ou.first_name, ' ', ou.last_name) as organizer_name,
            s.row_number,
            s.seat_number
        FROM tickets t
        JOIN events e ON t.event_id = e.id
        JOIN orders o ON t.order_id = o.id
        JOIN users u ON o.user_id = u.id
        JOIN users ou ON e.organizer_id = ou.id
        LEFT JOIN seats s ON s.id = t.seat_id
        WHERE t.ticket_number = ?
    ");
    
    $stmt->execute([$ticket_code]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ticket) {
        echo json_encode([
            'success' => false, 
            'message' => 'Bilet bulunamadı veya geçersiz'
        ]);
        exit;
    }
    
    // Bilet daha önce doğrulandı mı kontrol et
    $stmt = $pdo->prepare("
        SELECT id, verification_time, qr_staff_id 
        FROM ticket_verifications 
        WHERE ticket_id = ?
    ");
    
    $stmt->execute([$ticket['id']]);
    $verification = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $is_verified = $verification ? true : false;
    
    // Etkinlik tarihi kontrolü
    $event_date = new DateTime($ticket['event_date']);
    $today = new DateTime();
    $is_event_today = $event_date->format('Y-m-d') === $today->format('Y-m-d');
    
    // Bilet türü açıklaması
    $ticket_type_names = [
        'standard' => 'Standart Bilet',
        'vip' => 'VIP Bilet',
        'premium' => 'Premium Bilet',
        'student' => 'Öğrenci Bileti',
        'early_bird' => 'Erken Rezervasyon'
    ];
    
    $ticket_type_display = isset($ticket_type_names[$ticket['ticket_type']]) 
        ? $ticket_type_names[$ticket['ticket_type']] 
        : ucfirst($ticket['ticket_type']);
    
    // Koltuk etiketi oluşturma - çoklu koltuklar için seat_labels kullan
    $seat_display = null;
    if (!empty($ticket['seat_labels'])) {
        // Çoklu koltuk durumu - virgülleri boşlukla değiştir
        $seat_display = str_replace(',', ' ', $ticket['seat_labels']);
    } elseif (!empty($ticket['row_number']) && !empty($ticket['seat_number'])) {
        // Tekli koltuk durumu
        $seat_display = chr(64 + (int)$ticket['row_number']) . $ticket['seat_number'];
    }
    
    echo json_encode([
        'success' => true,
        'ticket' => [
            'id' => $ticket['id'],
            'ticket_number' => $ticket['ticket_number'],
            'event_title' => $ticket['event_title'],
            'event_date' => date('d.m.Y H:i', strtotime($ticket['event_date'])),
            'venue' => $ticket['venue_name'],
            'ticket_type' => $ticket_type_display,
            'quantity' => $ticket['quantity'],
            'price' => $ticket['price'],
            'purchase_date' => date('d.m.Y H:i', strtotime($ticket['created_at'])),
            'customer_name' => $ticket['customer_name'],
            'customer_email' => $ticket['customer_email'],
            'customer_phone' => $ticket['customer_phone'],
            'organizer_name' => $ticket['organizer_name'],
            'status' => $ticket['status'],
            'used_at' => $ticket['used_at'] ? date('d.m.Y H:i', strtotime($ticket['used_at'])) : null,
            'is_verified' => $is_verified,
            'verified_at' => $verification ? date('d.m.Y H:i', strtotime($verification['verification_time'])) : null,
            'is_event_today' => $is_event_today,
            'can_verify' => !$is_verified && $ticket['status'] === 'active',
            'seat_label' => $seat_display
        ]
    ]);
    
} catch (Exception $e) {
    error_log('Bilet doğrulama hatası: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Sistem hatası oluştu'
    ]);
}
?>