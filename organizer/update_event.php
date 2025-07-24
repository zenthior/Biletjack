<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Event.php';
require_once '../includes/session.php';

// Organizatör kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'organizer') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit;
}

// Onay kontrolü
if (!isOrganizerApproved()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Hesabınız henüz onaylanmamış']);
    exit;
}

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Sadece POST istekleri kabul edilir');
    }
    
    if (!isset($_POST['event_id']) || empty($_POST['event_id'])) {
        throw new Exception('Etkinlik ID gerekli');
    }
    
    $eventId = (int)$_POST['event_id'];
    $organizerId = $_SESSION['user_id'];
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Etkinliğin bu organizatöre ait olup olmadığını kontrol et
    $checkQuery = "SELECT id FROM events WHERE id = :event_id AND organizer_id = :organizer_id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':event_id', $eventId, PDO::PARAM_INT);
    $checkStmt->bindParam(':organizer_id', $organizerId, PDO::PARAM_INT);
    $checkStmt->execute();
    
    if (!$checkStmt->fetch()) {
        throw new Exception('Etkinlik bulunamadı veya bu etkinliği düzenleme yetkiniz yok');
    }
    
    // Form verilerini al ve doğrula
    $title = trim($_POST['title'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $eventDate = $_POST['event_date'] ?? '';
    $eventTime = $_POST['event_time'] ?? '';
    $venueName = trim($_POST['venue_name'] ?? '');
    $venueAddress = trim($_POST['venue_address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $minPrice = floatval($_POST['min_price'] ?? 0);
    $maxPrice = floatval($_POST['max_price'] ?? 0);
    $contactEmail = trim($_POST['contact_email'] ?? '');
    $contactPhone = trim($_POST['contact_phone'] ?? '');
    $website = trim($_POST['website'] ?? '');
    
    // Zorunlu alanları kontrol et
    if (empty($title)) {
        throw new Exception('Etkinlik başlığı gerekli');
    }
    
    if ($categoryId <= 0) {
        throw new Exception('Geçerli bir kategori seçin');
    }
    
    if (empty($description)) {
        throw new Exception('Etkinlik açıklaması gerekli');
    }
    
    if (empty($eventDate)) {
        throw new Exception('Etkinlik tarihi gerekli');
    }
    
    if (empty($eventTime)) {
        throw new Exception('Etkinlik saati gerekli');
    }
    
    if (empty($venueName)) {
        throw new Exception('Mekan adı gerekli');
    }
    
    if (empty($city)) {
        throw new Exception('Şehir gerekli');
    }
    
    // Tarih ve saati birleştir
    $eventDateTime = $eventDate . ' ' . $eventTime . ':00';
    
    // Etkinliği güncelle
    $updateQuery = "UPDATE events SET 
                    title = :title,
                    category_id = :category_id,
                    description = :description,
                    event_date = :event_date,
                    venue_name = :venue_name,
                    venue_address = :venue_address,
                    city = :city,
                    min_price = :min_price,
                    max_price = :max_price,
                    contact_email = :contact_email,
                    contact_phone = :contact_phone,
                    website = :website,
                    updated_at = NOW()
                    WHERE id = :event_id AND organizer_id = :organizer_id";
    
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->bindParam(':title', $title);
    $updateStmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
    $updateStmt->bindParam(':description', $description);
    $updateStmt->bindParam(':event_date', $eventDateTime);
    $updateStmt->bindParam(':venue_name', $venueName);
    $updateStmt->bindParam(':venue_address', $venueAddress);
    $updateStmt->bindParam(':city', $city);
    $updateStmt->bindParam(':min_price', $minPrice);
    $updateStmt->bindParam(':max_price', $maxPrice);
    $updateStmt->bindParam(':contact_email', $contactEmail);
    $updateStmt->bindParam(':contact_phone', $contactPhone);
    $updateStmt->bindParam(':website', $website);
    $updateStmt->bindParam(':event_id', $eventId, PDO::PARAM_INT);
    $updateStmt->bindParam(':organizer_id', $organizerId, PDO::PARAM_INT);
    
    if ($updateStmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Etkinlik başarıyla güncellendi'
        ]);
    } else {
        throw new Exception('Etkinlik güncellenirken bir hata oluştu');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>