<?php
require_once '../includes/session.php';
require_once '../config/database.php';

// Admin kontrolü
requireAdmin();

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Event ID is required']);
    exit;
}

$eventId = (int)$_GET['id'];

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Etkinlik detaylarını getir
    $query = "
        SELECT 
            e.*,
            COALESCE(od.company_name, u.email, 'Bilinmiyor') as organizer_name,
            u.email as organizer_email,
            od.phone as organizer_phone,
            od.website as organizer_website,
            od.instagram_url as organizer_instagram,
            (SELECT COUNT(*) FROM tickets t WHERE t.event_id = e.id) as ticket_count,
            (SELECT COUNT(*) FROM event_followers ef WHERE ef.event_id = e.id) as follower_count,
            (SELECT COUNT(*) FROM event_comments ec WHERE ec.event_id = e.id) as comment_count,
            (SELECT AVG(ec.rating) FROM event_comments ec WHERE ec.event_id = e.id) as avg_rating
        FROM events e 
        LEFT JOIN organizer_details od ON e.organizer_id = od.user_id 
        LEFT JOIN users u ON od.user_id = u.id
        WHERE e.id = ?
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$eventId]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        echo json_encode(['error' => 'Event not found']);
        exit;
    }
    
    // Bilet türlerini getir
    $ticketQuery = "SELECT * FROM ticket_types WHERE event_id = ? ORDER BY price ASC";
    $ticketStmt = $pdo->prepare($ticketQuery);
    $ticketStmt->execute([$eventId]);
    $tickets = $ticketStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Son yorumları getir
    $commentQuery = "
        SELECT ec.*, u.email as user_email 
        FROM event_comments ec 
        LEFT JOIN users u ON ec.user_id = u.id 
        WHERE ec.event_id = ? 
        ORDER BY ec.created_at DESC 
        LIMIT 3
    ";
    $commentStmt = $pdo->prepare($commentQuery);
    $commentStmt->execute([$eventId]);
    $comments = $commentStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Etkinlik görsellerini getir
    $images = [];
    
    // Ana etkinlik görseli varsa ekle
    if (!empty($event['image_url'])) {
        $images[] = [
            'id' => 1,
            'event_id' => $eventId,
            'image_url' => $event['image_url'],
            'is_primary' => 1,
            'created_at' => $event['created_at']
        ];
    }
    
    // Sanatçı görseli varsa ekle
    if (!empty($event['artist_image_url'])) {
        $images[] = [
            'id' => 2,
            'event_id' => $eventId,
            'image_url' => $event['artist_image_url'],
            'is_primary' => 0,
            'created_at' => $event['created_at']
        ];
    }
    
    // Galeri görselleri varsa ekle (JSON formatında)
    if (!empty($event['gallery'])) {
        $galleryImages = json_decode($event['gallery'], true);
        if (is_array($galleryImages)) {
            foreach ($galleryImages as $index => $imageUrl) {
                $images[] = [
                    'id' => $index + 3,
                    'event_id' => $eventId,
                    'image_url' => $imageUrl,
                    'is_primary' => 0,
                    'created_at' => $event['created_at']
                ];
            }
        }
    }
    
    // Etiketleri parse et
    $tags = !empty($event['tags']) ? explode(',', $event['tags']) : [];
    
    // Sanatçıları parse et
    $artists = !empty($event['artists']) ? explode(',', $event['artists']) : [];
    
    // Durum çevirisi
    $statusMap = [
        'draft' => 'Taslak',
        'published' => 'Yayınlandı',
        'cancelled' => 'İptal Edildi',
        'completed' => 'Tamamlandı'
    ];
    
    $response = [
        'event' => $event,
        'tickets' => $tickets,
        'comments' => $comments,
        'images' => $images,
        'tags' => $tags,
        'artists' => $artists,
        'status_text' => $statusMap[$event['status']] ?? 'Bilinmiyor'
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>