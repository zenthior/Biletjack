<?php
require_once '../includes/session.php';
require_once '../config/database.php';

// JSON response için header
header('Content-Type: application/json');

// Sadece organizatörler erişebilir
if (!isLoggedIn() || $_SESSION['user_type'] !== 'organizer') {
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit();
}
// Organizatör onayı kontrolü
$database = new Database();
$pdo = $database->getConnection();

$checkApproval = "SELECT approval_status FROM organizer_details WHERE user_id = ?";
$stmt = $pdo->prepare($checkApproval);
$stmt->execute([$_SESSION['user_id']]);
$organizer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$organizer || $organizer['approval_status'] !== 'approved') {
    echo json_encode(['success' => false, 'message' => 'Organizatör hesabınız henüz onaylanmamış']);
    exit();
}

// Dosyanın başına, try bloğundan önce ekle (yaklaşık 25. satır)

// Çift oluşturma koruması - daha güçlü kontrol
$duplicateCheck = "SELECT id FROM events WHERE organizer_id = ? AND (title = ? OR (title = ? AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)))";
$duplicateStmt = $pdo->prepare($duplicateCheck);
$duplicateStmt->execute([$_SESSION['user_id'], trim($_POST['title'] ?? ''), trim($_POST['title'] ?? '')]);
if ($duplicateStmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Bu etkinlik zaten oluşturulmuş veya çok yakın zamanda benzer bir etkinlik oluşturdunuz. Lütfen birkaç dakika bekleyip tekrar deneyin.']);
    exit();
}

// Session tabanlı ek koruma
if (isset($_SESSION['last_event_creation']) && (time() - $_SESSION['last_event_creation']) < 10) {
    echo json_encode(['success' => false, 'message' => 'Çok hızlı etkinlik oluşturuyorsunuz. Lütfen 10 saniye bekleyip tekrar deneyin.']);
    exit();
}

try {
    // Form verilerini al ve doğrula
    $title = trim($_POST['title'] ?? '');
    $categoryId = intval($_POST['category_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $shortDescription = trim($_POST['short_description'] ?? '');
    $eventDate = $_POST['event_date'] ?? '';
    $eventTime = $_POST['event_time'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    $endTime = $_POST['end_time'] ?? '';
    $venueName = trim($_POST['venue_name'] ?? '');
    $venueAddress = trim($_POST['venue_address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    // İletişim bilgileri değişkenleri kaldırıldı
    $artists = trim($_POST['artists'] ?? '');
    $tags = trim($_POST['tags'] ?? '');
    $metaDescription = trim($_POST['meta_description'] ?? '');
    $eventRules = trim($_POST['event_rules'] ?? '');
    $status = $_POST['status'] ?? 'draft';

    // Zorunlu alanları kontrol et
    if (empty($title)) {
        throw new Exception('Etkinlik başlığı gereklidir');
    }
    if ($categoryId <= 0) {
        throw new Exception('Kategori seçimi gereklidir');
    }
    if (empty($description)) {
        throw new Exception('Etkinlik açıklaması gereklidir');
    }
    if (empty($eventDate)) {
        throw new Exception('Etkinlik tarihi gereklidir');
    }
    if (empty($eventTime)) {
        throw new Exception('Etkinlik saati gereklidir');
    }
    if (empty($venueName)) {
        throw new Exception('Mekan adı gereklidir');
    }
    if (empty($city)) {
        throw new Exception('Şehir bilgisi gereklidir');
    }
    // İletişim bilgileri doğrulamaları tamamen kaldırıldı

    // Tarih ve saat birleştirme
    $eventDateTime = $eventDate . ' ' . $eventTime;
    $endDateTime = null;
    if (!empty($endDate) && !empty($endTime)) {
        $endDateTime = $endDate . ' ' . $endTime;
    }

    // Görsel yükleme işlemi
    $imageUrl = null;
    if (isset($_FILES['event_image']) && $_FILES['event_image']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($_FILES['event_image']['type'], $allowedTypes)) {
            throw new Exception('Sadece JPEG, PNG, GIF ve WebP formatları desteklenir');
        }
        
        if ($_FILES['event_image']['size'] > $maxSize) {
            throw new Exception('Görsel boyutu 5MB\'dan küçük olmalıdır');
        }
        
        $uploadDir = '../uploads/events/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileExtension = pathinfo($_FILES['event_image']['name'], PATHINFO_EXTENSION);
        $fileName = uniqid() . '_' . time() . '.' . $fileExtension;
        $targetPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['event_image']['tmp_name'], $targetPath)) {
            $imageUrl = 'uploads/events/' . $fileName;
        } else {
            throw new Exception('Görsel yüklenirken bir hata oluştu');
        }
    }

    // Sanatçı görseli yükleme işlemi
    $artistImageUrl = null;
    if (isset($_FILES['artist_image']) && $_FILES['artist_image']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($_FILES['artist_image']['type'], $allowedTypes)) {
            throw new Exception('Sadece JPEG, PNG, GIF ve WebP formatları desteklenir');
        }
        
        if ($_FILES['artist_image']['size'] > $maxSize) {
            throw new Exception('Sanatçı görseli boyutu 5MB\'dan küçük olmalıdır');
        }
        
        $uploadDir = '../uploads/events/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileExtension = pathinfo($_FILES['artist_image']['name'], PATHINFO_EXTENSION);
        $fileName = 'artist_' . uniqid() . '_' . time() . '.' . $fileExtension;
        $targetPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['artist_image']['tmp_name'], $targetPath)) {
            $artistImageUrl = 'uploads/events/' . $fileName;
        } else {
            throw new Exception('Sanatçı görseli yüklenirken bir hata oluştu');
        }
    }

    // URL slug oluşturma fonksiyonu
    function createSlug($text) {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
        $text = preg_replace('/[\s-]+/', '-', $text);
        $text = trim($text, '-');
        return $text;
    }

    // Benzersiz slug oluştur
    $baseSlug = createSlug($title);
    $slug = $baseSlug;
    $counter = 1;
    
    while (true) {
        $checkSlug = "SELECT id FROM events WHERE slug = ?";
        $stmt = $pdo->prepare($checkSlug);
        $stmt->execute([$slug]);
        
        if (!$stmt->fetch()) {
            break;
        }
        
        $slug = $baseSlug . '-' . $counter;
        $counter++;
    }

    // Etkinlik verilerini hazırla
    $eventData = [
        'organizer_id' => $_SESSION['user_id'],
        'category_id' => $categoryId,
        'title' => $title,
        'slug' => $slug,
        'description' => $description,
        'short_description' => $shortDescription,
        'event_date' => $eventDateTime,
        'end_date' => $endDateTime,
        'venue_name' => $venueName,
        'venue_address' => $venueAddress,
        'city' => $city,
        'image_url' => $imageUrl,
        'artist_image_url' => $artistImageUrl,
        'artists' => $artists,
        'tags' => $tags,
        'meta_description' => $metaDescription,
        'event_rules' => $eventRules,
        'seating_type' => $_POST['seating_type'] ?? 'general',
        'status' => $status,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    // Etkinliği veritabanına ekle
    $sql = "INSERT INTO events (
        organizer_id, category_id, title, slug, description, short_description,
        event_date, end_date, venue_name, venue_address, city, image_url, artist_image_url,
        artists, tags, meta_description, event_rules, seating_type,
        status, created_at
    ) VALUES (
        :organizer_id, :category_id, :title, :slug, :description, :short_description,
        :event_date, :end_date, :venue_name, :venue_address, :city, :image_url, :artist_image_url,
        :artists, :tags, :meta_description, :event_rules, :seating_type,
        :status, :created_at
    )";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($eventData);
    
    $eventId = $pdo->lastInsertId();
    
    // Koltuklu etkinlik için seat_categories ve seats kaydetme kodu
    if (isset($_POST['seating_type']) && ($_POST['seating_type'] === 'seated' || $_POST['seating_type'] === 'reservation')) {
        // Debug: POST verilerini kontrol et
        error_log("Seating Type: " . $_POST['seating_type']);
        error_log("Seat Categories: " . (isset($_POST['seat_categories']) ? $_POST['seat_categories'] : 'NOT SET'));
        error_log("Seats: " . (isset($_POST['seats']) ? $_POST['seats'] : 'NOT SET'));
        
        // Koltuk kategorilerini kaydet
        if (isset($_POST['seat_categories']) && !empty($_POST['seat_categories'])) {
            $seatCategories = json_decode($_POST['seat_categories'], true);
            error_log("Decoded Categories: " . print_r($seatCategories, true));
            
            $categorySql = "INSERT INTO seat_categories (
                event_id, name, color, price, description, created_at
            ) VALUES (
                :event_id, :name, :color, :price, :description, :created_at
            )";
            
            $categoryStmt = $pdo->prepare($categorySql);
            
            foreach ($seatCategories as $category) {
                // Rezervasyon sistemi için de fiyatları kaydet (görüntüleme için)
                $price = floatval($category['price']);
                
                $categoryData = [
                    'event_id' => $eventId,
                    'name' => $category['name'],
                    'color' => $category['color'],
                    'price' => $price,
                    'description' => $category['description'] ?? '',
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                $categoryStmt->execute($categoryData);
            }
        }
        
        // Koltukları kaydet
        if (isset($_POST['seats']) && !empty($_POST['seats'])) {
            $seats = json_decode($_POST['seats'], true);
            error_log("Decoded Seats: " . print_r($seats, true));
            
            // Önce kategori adlarından ID'leri al
            $categoryMap = [];
            $categoryMapSql = "SELECT id, name FROM seat_categories WHERE event_id = ?";
            $categoryMapStmt = $pdo->prepare($categoryMapSql);
            $categoryMapStmt->execute([$eventId]);
            $categoryResults = $categoryMapStmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($categoryResults as $cat) {
                $categoryMap[$cat['name']] = $cat['id'];
            }
            
            $seatSql = "INSERT INTO seats (
                event_id, row_number, seat_number, category_id, category_name, status, created_at
            ) VALUES (
                :event_id, :row_number, :seat_number, :category_id, :category_name, :status, :created_at
            )";
            
            $seatStmt = $pdo->prepare($seatSql);
            
            foreach ($seats as $seat) {
                $categoryName = $seat['category'] ?? 'standard';
                $categoryId = $categoryMap[$categoryName] ?? null;
                
                if ($categoryId === null) {
                    error_log("Warning: Category not found for seat: " . $categoryName);
                    continue;
                }
                
                $seatData = [
                    'event_id' => $eventId,
                    'row_number' => intval($seat['row']),
                    'seat_number' => intval($seat['seat']),
                    'category_id' => $categoryId,
                    'category_name' => $categoryName, // EKLENEN ALAN
                    'status' => 'available',
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                $seatStmt->execute($seatData);
            }
        }
        
        // Koltuklu etkinlik için min/max fiyatları seat_categories'den al
        if ($_POST['seating_type'] === 'reservation') {
            // Rezervasyon sistemi için fiyatları 0 olarak ayarla
            $priceUpdateSql = "UPDATE events SET min_price = 0, max_price = 0 WHERE id = :event_id";
        } else {
            // Normal koltuklu etkinlik için fiyatları seat_categories'den al
            $priceUpdateSql = "UPDATE events SET 
                min_price = (SELECT MIN(price) FROM seat_categories WHERE event_id = :event_id),
                max_price = (SELECT MAX(price) FROM seat_categories WHERE event_id = :event_id)
                WHERE id = :event_id";
        }
    } else {
        // Normal etkinlik için bilet türlerini ekle
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
                    
                    $quantity = intval($_POST['ticket_quantity'][$i]);
                    $ticketData = [
                        'event_id' => $eventId,
                        'name' => trim($_POST['ticket_name'][$i]),
                        'description' => trim($_POST['ticket_description'][$i] ?? ''),
                        'price' => floatval($_POST['ticket_price'][$i]),
                        'discount_price' => !empty($_POST['ticket_discount_price'][$i]) ? floatval($_POST['ticket_discount_price'][$i]) : null,
                        'quantity' => $quantity,
                        'max_per_order' => intval($_POST['ticket_max_per_order'][$i] ?? 10),
                        'sale_start_date' => $saleStartDate,
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    
                    $ticketStmt->execute($ticketData);
                }
            }
        }
        
        // Normal etkinlik için min/max fiyatları ticket_types'dan al
        $priceUpdateSql = "UPDATE events SET 
            min_price = (SELECT MIN(price) FROM ticket_types WHERE event_id = :event_id),
            max_price = (SELECT MAX(price) FROM ticket_types WHERE event_id = :event_id)
            WHERE id = :event_id";
    }
    
    $priceStmt = $pdo->prepare($priceUpdateSql);
    $priceStmt->execute(['event_id' => $eventId]);
    
    // İNDİRİM KODLARI: Etkinlik kaydından sonra indirim kodlarını ekle
    if (!empty($_POST['discount_code_code']) && is_array($_POST['discount_code_code'])) {
        $codes = $_POST['discount_code_code'];
        $amounts = $_POST['discount_code_amount'] ?? [];
        $quantities = $_POST['discount_code_quantity'] ?? [];
        $insSql = "INSERT INTO discount_codes (event_id, code, discount_amount, quantity, status) 
                   VALUES (?, ?, ?, ?, 'active')";
        $insStmt = $pdo->prepare($insSql);
    
        foreach ($codes as $i => $codeStr) {
            $code = trim($codeStr);
            if ($code === '') continue;
    
            $amount = isset($amounts[$i]) ? floatval($amounts[$i]) : 0;
            $qty    = isset($quantities[$i]) ? intval($quantities[$i]) : 0;
    
            if ($amount < 0 || $qty < 1) continue;
    
            try {
                $insStmt->execute([$eventId, strtoupper($code), $amount, $qty]);
            } catch (Exception $e) {
                // Aynı kod tekrar ekleniyorsa veya başka bir hata olursa loglayıp atla
                error_log("Discount code insert error: " . $e->getMessage());
            }
        }
    }

    // Yeni: İş ortaklarına bildirim gönder
    try {
        // Bildirim tablosunu oluştur (yoksa)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                related_event_id INT NULL,
                created_by INT NULL,
                is_read TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (related_event_id) REFERENCES events(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $eventTitle = $eventData['title'];
        $eventCity  = $eventData['city'] ?? '';
        $eventDate  = $eventData['event_date'] ?? '';
        $creatorId  = (int)$_SESSION['user_id'];

        $notifyStmt = $pdo->prepare("
            INSERT INTO notifications (user_id, title, message, related_event_id, created_by)
            VALUES (:user_id, :title, :message, :related_event_id, :created_by)
        ");

        // Seçilen stüdyo (service provider)
        if (!empty($_POST['selected_service_provider_user_id'])) {
            $spUserId = (int)$_POST['selected_service_provider_user_id'];
            $title = 'Yeni İş Ataması (Stüdyo)';
            $message = 'Bu etkinlikte iş aldınız. '
                . 'Etkinlik: ' . $eventTitle
                . ($eventCity ? ' - ' . $eventCity : '')
                . ($eventDate ? ' - ' . $eventDate : '');
            $notifyStmt->execute([
                'user_id' => $spUserId,
                'title' => $title,
                'message' => $message,
                'related_event_id' => $eventId,
                'created_by' => $creatorId
            ]);
        }

        // Seçilen reklam ajansı (ad agency)
        if (!empty($_POST['selected_ad_agency_user_id'])) {
            $aaUserId = (int)$_POST['selected_ad_agency_user_id'];
            $title = 'Yeni İş Ataması (Reklam Ajansı)';
            $message = 'Bu etkinlikte iş aldınız (PR). '
                . 'Etkinlik: ' . $eventTitle
                . ($eventCity ? ' - ' . $eventCity : '')
                . ($eventDate ? ' - ' . $eventDate : '');
            $notifyStmt->execute([
                'user_id' => $aaUserId,
                'title' => $title,
                'message' => $message,
                'related_event_id' => $eventId,
                'created_by' => $creatorId
            ]);
        }
    } catch (Exception $e) {
        error_log('Notification insert failed: ' . $e->getMessage());
    }

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
    
    // Başarılı oluşturma sonrası session ayarı
    $_SESSION['last_event_creation'] = time();
    
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
