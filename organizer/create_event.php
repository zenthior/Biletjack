<?php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../classes/User.php';
require_once '../classes/Event.php';

// Organizatör kontrolü
requireOrganizer();

// Organizatör onay kontrolü
if (!isOrganizerApproved()) {
    echo json_encode(['success' => false, 'message' => 'Organizatör hesabınız henüz onaylanmamış.']);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek metodu.']);
    exit();
}

try {
    // Database bağlantısını oluştur
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Dosya yükleme işlemi
    $imageUrl = null;
    if (isset($_FILES['event_image']) && $_FILES['event_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/events/';
        
        // Upload dizinini oluştur
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileInfo = pathinfo($_FILES['event_image']['name']);
        $fileName = uniqid() . '_' . time() . '.' . strtolower($fileInfo['extension']);
        $targetPath = $uploadDir . $fileName;
        
        // Dosya türü kontrolü
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array(strtolower($fileInfo['extension']), $allowedTypes)) {
            throw new Exception('Geçersiz dosya türü. Sadece JPG, PNG, GIF ve WebP dosyaları kabul edilir.');
        }
        
        // Dosya boyutu kontrolü (5MB)
        if ($_FILES['event_image']['size'] > 5 * 1024 * 1024) {
            throw new Exception('Dosya boyutu çok büyük. Maksimum 5MB olmalıdır.');
        }
        
        if (move_uploaded_file($_FILES['event_image']['tmp_name'], $targetPath)) {
            $imageUrl = 'uploads/events/' . $fileName;
        } else {
            throw new Exception('Dosya yüklenirken bir hata oluştu.');
        }
    }
    
    // Slug oluşturma fonksiyonu
    function createSlug($text) {
        // Türkçe karakterleri değiştir
        $text = str_replace(['ç', 'ğ', 'ı', 'ö', 'ş', 'ü', 'Ç', 'Ğ', 'I', 'İ', 'Ö', 'Ş', 'Ü'], 
                           ['c', 'g', 'i', 'o', 's', 'u', 'c', 'g', 'i', 'i', 'o', 's', 'u'], $text);
        // Küçük harfe çevir
        $text = strtolower($text);
        // Alfanumerik olmayan karakterleri tire ile değiştir
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        // Başındaki ve sonundaki tireleri kaldır
        $text = trim($text, '-');
        return $text;
    }
    
    // Benzersiz slug oluştur
    function createUniqueSlug($pdo, $title) {
        $baseSlug = createSlug($title);
        $slug = $baseSlug;
        $counter = 1;
        
        while (true) {
            $checkSql = "SELECT COUNT(*) FROM events WHERE slug = :slug";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute(['slug' => $slug]);
            
            if ($checkStmt->fetchColumn() == 0) {
                break;
            }
            
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
    
    // Etkinlik tarih ve saatini birleştir
    $eventDateTime = $_POST['event_date'] . ' ' . $_POST['event_time'];
    $endDateTime = null;
    if (!empty($_POST['end_date']) && !empty($_POST['end_time'])) {
        $endDateTime = $_POST['end_date'] . ' ' . $_POST['end_time'];
    }
    
    // Benzersiz slug oluştur
    $slug = createUniqueSlug($pdo, $_POST['title']);
    
    // Etkinlik verilerini hazırla
    $eventData = [
        'organizer_id' => $_SESSION['user_id'],
        'category_id' => $_POST['category_id'],
        'title' => trim($_POST['title']),
        'slug' => $slug,
        'description' => trim($_POST['description']),
        'short_description' => trim($_POST['short_description'] ?? ''),
        'event_date' => $eventDateTime,
        'end_date' => $endDateTime,
        'venue_name' => trim($_POST['venue_name']),
        'venue_address' => trim($_POST['venue_address'] ?? ''),
        'city' => trim($_POST['city']),
        'image_url' => $imageUrl,
        'contact_phone' => trim($_POST['contact_phone'] ?? ''),
        'contact_email' => trim($_POST['contact_email'] ?? ''),
        'instagram_url' => trim($_POST['instagram_link'] ?? ''),
        'twitter_url' => trim($_POST['twitter_link'] ?? ''),
        'facebook_url' => trim($_POST['facebook_link'] ?? ''),
        'artists' => trim($_POST['artists'] ?? ''),
        'tags' => trim($_POST['tags'] ?? ''),
        'meta_description' => trim($_POST['meta_description'] ?? ''),
        'status' => 'draft', // Varsayılan olarak taslak
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    // Etkinliği veritabanına ekle
    $sql = "INSERT INTO events (
        organizer_id, category_id, title, slug, description, short_description, 
        event_date, end_date, venue_name, venue_address, city, image_url,
        contact_phone, contact_email, instagram_url, twitter_url, 
        facebook_url, artists, tags, meta_description, 
        status, created_at
    ) VALUES (
        :organizer_id, :category_id, :title, :slug, :description, :short_description,
        :event_date, :end_date, :venue_name, :venue_address, :city, :image_url,
        :contact_phone, :contact_email, :instagram_url, :twitter_url,
        :facebook_url, :artists, :tags, :meta_description,
        :status, :created_at
    )";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($eventData);
    
    $eventId = $pdo->lastInsertId();
    
    // Bilet türlerini ekle
    if (isset($_POST['ticket_name']) && is_array($_POST['ticket_name'])) {
        $ticketSql = "INSERT INTO ticket_types (
            event_id, name, description, price, discount_price, 
            quantity, max_per_order, sale_start_date, created_at
        ) VALUES (
            :event_id, :name, :description, :price, :discount_price,
            :quantity, :max_per_order, :sale_start_date, :created_at
        )";
        
        $ticketStmt = $pdo->prepare($ticketSql);
        
        for ($i = 0; $i < count($_POST['ticket_name']); $i++) {
            if (!empty($_POST['ticket_name'][$i]) && !empty($_POST['ticket_price'][$i])) {
                $saleStartDate = null;
                if (!empty($_POST['ticket_sale_start'][$i])) {
                    $saleStartDate = $_POST['ticket_sale_start'][$i];
                }
                
                $ticketData = [
                    'event_id' => $eventId,
                    'name' => trim($_POST['ticket_name'][$i]),
                    'description' => trim($_POST['ticket_description'][$i] ?? ''),
                    'price' => floatval($_POST['ticket_price'][$i]),
                    'discount_price' => !empty($_POST['ticket_discount_price'][$i]) ? floatval($_POST['ticket_discount_price'][$i]) : null,
                    'quantity' => intval($_POST['ticket_quantity'][$i]),
                    'max_per_order' => intval($_POST['ticket_max_per_order'][$i] ?? 10),
                    'sale_start_date' => $saleStartDate,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                $ticketStmt->execute($ticketData);
            }
        }
    }
    
    // Min ve max fiyatları güncelle
    $priceUpdateSql = "UPDATE events SET 
        min_price = (SELECT MIN(price) FROM ticket_types WHERE event_id = :event_id),
        max_price = (SELECT MAX(price) FROM ticket_types WHERE event_id = :event_id)
        WHERE id = :event_id";
    
    $priceStmt = $pdo->prepare($priceUpdateSql);
    $priceStmt->execute(['event_id' => $eventId]);
    
    // Aktivite logu ekle
    $logSql = "INSERT INTO activity_logs (user_id, action, description, created_at) 
               VALUES (:user_id, :action, :description, :created_at)";
    $logStmt = $pdo->prepare($logSql);
    $logStmt->execute([
        'user_id' => $_SESSION['user_id'],
        'action' => 'event_created',
        'description' => 'Yeni etkinlik oluşturuldu: ' . $eventData['title'],
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Etkinlik başarıyla oluşturuldu!',
        'event_id' => $eventId
    ]);
    
} catch (Exception $e) {
    // Hata durumunda yüklenen dosyayı sil
    if (isset($targetPath) && file_exists($targetPath)) {
        unlink($targetPath);
    }
    
    echo json_encode([
        'success' => false, 
        'message' => 'Hata: ' . $e->getMessage()
    ]);
}
?>