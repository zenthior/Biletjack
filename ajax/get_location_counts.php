<?php
require_once '../config/database.php';
require_once '../includes/session.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Get event counts by city
    $query = "SELECT city, COUNT(*) as count 
              FROM events 
              WHERE status = 'published' 
              AND event_date > NOW() 
              GROUP BY city";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $counts = [];
    foreach ($results as $result) {
        $cityKey = strtolower(str_replace(['ı', 'ğ', 'ü', 'ş', 'ö', 'ç'], ['i', 'g', 'u', 's', 'o', 'c'], $result['city']));
        $counts[$cityKey] = $result['count'];
    }
    
    // Ensure all cities have a count (even if 0)
    $cities = ['istanbul', 'ankara', 'izmir', 'antalya', 'trabzon'];
    foreach ($cities as $city) {
        if (!isset($counts[$city])) {
            $counts[$city] = 0;
        }
    }
    
    echo json_encode([
        'success' => true,
        'counts' => $counts
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Konum sayıları yüklenirken hata oluştu: ' . $e->getMessage()
    ]);
}
?>