<?php
require_once '../config/database.php';
require_once '../includes/session.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    $eventId = $_GET['event_id'] ?? '';
    
    if (empty($eventId)) {
        throw new Exception('Etkinlik ID parametresi eksik');
    }
    
    // Get event details
    $eventQuery = "SELECT title, event_date, venue_name, city FROM events WHERE id = ? AND status = 'published'";
    $eventStmt = $pdo->prepare($eventQuery);
    $eventStmt->execute([$eventId]);
    $event = $eventStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        throw new Exception('Etkinlik bulunamadı');
    }
    
    // Get ticket types for the event
    $ticketQuery = "SELECT id, name, price, description, max_quantity, 
                           (max_quantity - COALESCE((SELECT SUM(quantity) FROM tickets WHERE ticket_type_id = ticket_types.id), 0)) as available_quantity
                    FROM ticket_types 
                    WHERE event_id = ? AND is_active = 1 
                    ORDER BY price ASC";
    
    $ticketStmt = $pdo->prepare($ticketQuery);
    $ticketStmt->execute([$eventId]);
    $ticketTypes = $ticketStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format ticket types data
    $formattedTickets = [];
    foreach ($ticketTypes as $ticket) {
        $formattedTickets[] = [
            'id' => $ticket['id'],
            'name' => $ticket['name'],
            'price' => number_format($ticket['price'], 2),
            'description' => $ticket['description'],
            'max_quantity' => $ticket['max_quantity'],
            'available_quantity' => max(0, $ticket['available_quantity'])
        ];
    }
    
    echo json_encode([
        'success' => true,
        'event' => $event,
        'ticket_types' => $formattedTickets
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Bilet türleri yüklenirken hata oluştu: ' . $e->getMessage(),
        'event' => null,
        'ticket_types' => []
    ]);
}
?>