<?php
require_once '../config/database.php';
require_once '../includes/session.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    $location = $_GET['location'] ?? '';
    
    if (empty($location)) {
        throw new Exception('Konum parametresi eksik');
    }
    
    // Convert location to proper city name
    $cityMap = [
        'istanbul' => 'İstanbul',
        'ankara' => 'Ankara', 
        'izmir' => 'İzmir',
        'antalya' => 'Antalya'
    ];
    
    $cityName = $cityMap[strtolower($location)] ?? ucfirst($location);
    
    // Get events for the selected city
    $query = "SELECT e.*, 
                     (SELECT MIN(price) FROM ticket_types tt WHERE tt.event_id = e.id AND tt.is_active = 1) as min_price,
                     (SELECT MAX(price) FROM ticket_types tt WHERE tt.event_id = e.id AND tt.is_active = 1) as max_price
              FROM events e 
              WHERE e.status = 'published' 
              AND e.event_date > NOW() 
              AND (e.city = ? OR e.city LIKE ?)
              ORDER BY e.event_date ASC
              LIMIT 20";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$cityName, '%' . $cityName . '%']);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format events data
    $formattedEvents = [];
    foreach ($events as $event) {
        $formattedEvents[] = [
            'id' => $event['id'],
            'title' => $event['title'],
            'event_date' => $event['event_date'],
            'venue_name' => $event['venue_name'],
            'city' => $event['city'],
            'image_url' => $event['image_url'],
            'min_price' => $event['min_price'],
            'max_price' => $event['max_price']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'events' => $formattedEvents,
        'location' => $cityName
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Etkinlikler yüklenirken hata oluştu: ' . $e->getMessage(),
        'events' => []
    ]);
}
?>