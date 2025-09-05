<?php
require_once '../includes/session.php';
require_once '../config/database.php';

header('Content-Type: application/json');

// Sadece organizatörler erişebilir
if (!isLoggedIn() || ($_SESSION['user_type'] ?? null) !== 'organizer') {
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit;
}

// Organizatör onayı kontrolü
if (!isOrganizerApproved()) {
    echo json_encode(['success' => false, 'message' => 'Organizatör hesabınız henüz onaylanmamış']);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();

    // Etkinlik ID'sini al ve doğrula
    $eventId = isset($_POST['event_id']) ? (int)$_POST['event_id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
    if ($eventId <= 0) {
        throw new Exception('Geçersiz etkinlik ID');
    }

    // Etkinlik sahibi mi kontrolü ve mevcut verileri al
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = :id AND organizer_id = :org");
    $stmt->execute([':id' => $eventId, ':org' => $_SESSION['user_id']]);
    $existingEvent = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$existingEvent) {
        throw new Exception('Etkinlik bulunamadı veya bu etkinliği düzenleme yetkiniz yok');
    }

    // Form verileri
    $title = trim($_POST['title'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $shortDescription = trim($_POST['short_description'] ?? '');
    $eventDate = $_POST['event_date'] ?? '';
    $eventTime = $_POST['event_time'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    $endTime = $_POST['end_time'] ?? '';
    $venueName = trim($_POST['venue_name'] ?? '');
    $venueAddress = trim($_POST['venue_address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $artists = trim($_POST['artists'] ?? '');
    $tags = trim($_POST['tags'] ?? '');
    $metaDescription = trim($_POST['meta_description'] ?? '');
    $eventRules = trim($_POST['event_rules'] ?? '');
    $status = $_POST['status'] ?? ($existingEvent['status'] ?? 'draft');
    $seatingType = $_POST['seating_type'] ?? ($existingEvent['seating_type'] ?? 'general');

    // Zorunlu alan doğrulamaları
    if ($title === '') throw new Exception('Etkinlik başlığı gereklidir');
    if ($categoryId <= 0) throw new Exception('Kategori seçimi gereklidir');
    if ($description === '') throw new Exception('Etkinlik açıklaması gereklidir');
    if ($eventDate === '') throw new Exception('Etkinlik tarihi gereklidir');
    if ($eventTime === '') throw new Exception('Etkinlik saati gereklidir');
    if ($venueName === '') throw new Exception('Mekan adı gereklidir');
    if ($city === '') throw new Exception('Şehir bilgisi gereklidir');

    // Tarih saat birleştirme
    $eventDateTime = $eventDate . ' ' . $eventTime;
    $endDateTime = (!empty($endDate) && !empty($endTime)) ? ($endDate . ' ' . $endTime) : null;

    // Slug üretici
    $createSlug = function($text) {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
        $text = preg_replace('/[\s-]+/', '-', $text);
        return trim($text, '-');
    };

    // Başlık değişmişse benzersiz slug üret
    $slug = $existingEvent['slug'];
    if (strtolower($existingEvent['title']) !== strtolower($title)) {
        $baseSlug = $createSlug($title);
        $slug = $baseSlug;
        $counter = 1;
        while (true) {
            $check = $pdo->prepare('SELECT id FROM events WHERE slug = ? AND id <> ? LIMIT 1');
            $check->execute([$slug, $eventId]);
            if (!$check->fetch()) break;
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
    }

    // Görsel yükleme
    $newImagePath = null; $newArtistImagePath = null;
    $uploadsDir = '../uploads/events/';
    if (!is_dir($uploadsDir)) { @mkdir($uploadsDir, 0755, true); }

    $cleanupFiles = [];

    if (isset($_FILES['event_image']) && $_FILES['event_image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
        if (!in_array($_FILES['event_image']['type'], $allowed)) {
            throw new Exception('Etkinlik görseli formatı desteklenmiyor');
        }
        if ($_FILES['event_image']['size'] > 5*1024*1024) {
            throw new Exception('Etkinlik görseli 5MB\'ı aşmamalıdır');
        }
        $ext = pathinfo($_FILES['event_image']['name'], PATHINFO_EXTENSION);
        $fname = uniqid('evt_') . '_' . time() . '.' . $ext;
        $target = $uploadsDir . $fname;
        if (!move_uploaded_file($_FILES['event_image']['tmp_name'], $target)) {
            throw new Exception('Etkinlik görseli yüklenemedi');
        }
        $newImagePath = 'uploads/events/' . $fname;
        $cleanupFiles[] = $target; // başarısız olursa silmek için
    }

    if (isset($_FILES['artist_image']) && $_FILES['artist_image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
        if (!in_array($_FILES['artist_image']['type'], $allowed)) {
            throw new Exception('Sanatçı görseli formatı desteklenmiyor');
        }
        if ($_FILES['artist_image']['size'] > 5*1024*1024) {
            throw new Exception('Sanatçı görseli 5MB\'ı aşmamalıdır');
        }
        $ext = pathinfo($_FILES['artist_image']['name'], PATHINFO_EXTENSION);
        $fname = uniqid('art_') . '_' . time() . '.' . $ext;
        $target = $uploadsDir . $fname;
        if (!move_uploaded_file($_FILES['artist_image']['tmp_name'], $target)) {
            throw new Exception('Sanatçı görseli yüklenemedi');
        }
        $newArtistImagePath = 'uploads/events/' . $fname;
        $cleanupFiles[] = $target;
    }

    // İşlem başlat
    $pdo->beginTransaction();

    // Etkinliğin mevcut hareketleri (bilet/rezervasyon) var mı?
    $soldStmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE event_id = ? AND status IN ('active','used','pending')");
    $soldStmt->execute([$eventId]);
    $hasTickets = ((int)$soldStmt->fetchColumn()) > 0;

    $resStmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE event_id = ? AND status IN ('pending','approved')");
    try { $resStmt->execute([$eventId]); $hasReservations = ((int)$resStmt->fetchColumn()) > 0; } catch (Exception $e) { $hasReservations = false; }

    $hasMovements = $hasTickets || $hasReservations;

    // seating_type değiştirme kuralı: hareket varsa tipi değiştirilemez
    if ($hasMovements && $seatingType !== $existingEvent['seating_type']) {
        throw new Exception('Bu etkinlikte işlem bulunduğu için oturma tipi değiştirilemez');
    }

    // Event güncelleme
    $updateEventSql = "UPDATE events SET 
        category_id = :category_id,
        title = :title,
        slug = :slug,
        description = :description,
        short_description = :short_description,
        event_date = :event_date,
        end_date = :end_date,
        venue_name = :venue_name,
        venue_address = :venue_address,
        city = :city,
        artists = :artists,
        tags = :tags,
        meta_description = :meta_description,
        event_rules = :event_rules,
        seating_type = :seating_type,
        status = :status,
        updated_at = NOW()";

    $params = [
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
        'artists' => $artists,
        'tags' => $tags,
        'meta_description' => $metaDescription,
        'event_rules' => $eventRules,
        'seating_type' => $seatingType,
        'status' => $status
    ];

    if ($newImagePath) { $updateEventSql .= ", image_url = :image_url"; $params['image_url'] = $newImagePath; }
    if ($newArtistImagePath) { $updateEventSql .= ", artist_image_url = :artist_image_url"; $params['artist_image_url'] = $newArtistImagePath; }
    $updateEventSql .= " WHERE id = :id AND organizer_id = :org";
    $params['id'] = $eventId; $params['org'] = $_SESSION['user_id'];

    $upd = $pdo->prepare($updateEventSql);
    $upd->execute($params);

    // seating_type özel işlemler
    if ($seatingType === 'general') {
        // Bilet türlerini güncelle/ekle/sil (güvenli)
        $existingTickets = [];
        $stmt = $pdo->prepare("SELECT id, quantity FROM ticket_types WHERE event_id = ?");
        $stmt->execute([$eventId]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $t) { $existingTickets[(int)$t['id']] = (int)$t['quantity']; }

        $postedIds = $_POST['ticket_id'] ?? [];
        $names = $_POST['ticket_name'] ?? [];
        $descs = $_POST['ticket_description'] ?? [];
        $prices = $_POST['ticket_price'] ?? [];
        $dprices = $_POST['ticket_discount_price'] ?? [];
        $qtys = $_POST['ticket_quantity'] ?? [];
        $maxOrder = $_POST['ticket_max_per_order'] ?? [];
        $saleStarts = $_POST['ticket_sale_start'] ?? [];

        // Güncelleme/ekleme
        if (is_array($names)) {
            for ($i = 0; $i < count($names); $i++) {
                $name = trim($names[$i] ?? '');
                $price = isset($prices[$i]) ? (float)$prices[$i] : null;
                if ($name === '' || $price === null) continue;

                $tid = isset($postedIds[$i]) ? (int)$postedIds[$i] : 0;
                $desc = trim($descs[$i] ?? '');
                $dprice = isset($dprices[$i]) && $dprices[$i] !== '' ? (float)$dprices[$i] : null;
                $qty = (int)($qtys[$i] ?? 0);
                $maxPo = (int)($maxOrder[$i] ?? 10);
                $saleStart = ($saleStarts[$i] ?? '') ?: null; // datetime-local

                if ($tid > 0 && isset($existingTickets[$tid])) {
                    // Satılmış bilet sayısını hesapla (pending dahil)
                    $sold = $pdo->prepare("SELECT COALESCE(SUM(quantity),0) FROM tickets WHERE ticket_type_id = ? AND status IN ('active','used','pending')");
                    $sold->execute([$tid]);
                    $soldCount = (int)$sold->fetchColumn();
                    if ($qty < $soldCount) { $qty = $soldCount; }

                    $u = $pdo->prepare("UPDATE ticket_types SET name=:n, description=:d, price=:p, discount_price=:dp, quantity=:q, max_per_order=:m, sale_start_date=:ss WHERE id=:id AND event_id=:eid");
                    $u->execute([
                        ':n' => $name,
                        ':d' => $desc,
                        ':p' => $price,
                        ':dp' => $dprice,
                        ':q' => $qty,
                        ':m' => $maxPo,
                        ':ss' => $saleStart,
                        ':id' => $tid,
                        ':eid' => $eventId
                    ]);
                } else {
                    // Yeni ekle
                    $ins = $pdo->prepare("INSERT INTO ticket_types (
                        event_id, name, description, price, discount_price, quantity, max_per_order, sale_start_date, created_at
                    ) VALUES (
                        :event_id, :name, :description, :price, :discount_price, :quantity, :max_per_order, :sale_start_date, :created_at
                    )");
                    $ins->execute([
                        ':event_id' => $eventId,
                        ':name' => $name,
                        ':description' => $desc,
                        ':price' => $price,
                        ':discount_price' => $dprice,
                        ':quantity' => $qty,
                        ':max_per_order' => $maxPo,
                        ':sale_start_date' => $saleStart,
                        ':created_at' => date('Y-m-d H:i:s')
                    ]);
                }
            }
        }

        // Silme: formda olmayanları sadece satış yoksa sil
        $keepIds = array_filter(array_map('intval', $postedIds ?? []));
        $toCheck = $pdo->prepare("SELECT id FROM ticket_types WHERE event_id = ?");
        $toCheck->execute([$eventId]);
        foreach ($toCheck->fetchAll(PDO::FETCH_COLUMN) as $tid) {
            $tid = (int)$tid;
            if (!in_array($tid, $keepIds, true)) {
                $sold = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE ticket_type_id = ?");
                $sold->execute([$tid]);
                if ((int)$sold->fetchColumn() === 0) {
                    $del = $pdo->prepare("DELETE FROM ticket_types WHERE id = ? AND event_id = ?");
                    $del->execute([$tid, $eventId]);
                }
            }
        }

        // Min/Max fiyat güncelle
        $priceUpdateSql = "UPDATE events SET 
            min_price = (SELECT MIN(price) FROM ticket_types WHERE event_id = :event_id),
            max_price = (SELECT MAX(price) FROM ticket_types WHERE event_id = :event_id)
            WHERE id = :event_id";
        $pdo->prepare($priceUpdateSql)->execute(['event_id' => $eventId]);
    } else {
        // Koltuklu veya rezervasyonlu etkinlik
        $seatCategoriesJson = $_POST['seat_categories'] ?? null; // JSON
        $seatsJson = $_POST['seats'] ?? null; // JSON

        // Hareket yoksa komple yeniden kurulum serbest, varsa sadece fiyat/ad güncellemeleri
        if (!$hasMovements) {
            // Eski koltuk ve kategorileri sil
            $pdo->prepare("DELETE FROM seats WHERE event_id = ?")->execute([$eventId]);
            $pdo->prepare("DELETE FROM seat_categories WHERE event_id = ?")->execute([$eventId]);
        }

        // Kategoriler
        if (!empty($seatCategoriesJson)) {
            $seatCategories = json_decode($seatCategoriesJson, true);
            if (is_array($seatCategories)) {
                // Mevcut kategori ad->id eşlemesi
                $catMap = [];
                $q = $pdo->prepare("SELECT id, name FROM seat_categories WHERE event_id = ?");
                $q->execute([$eventId]);
                foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $r) { $catMap[$r['name']] = (int)$r['id']; }

                foreach ($seatCategories as $cat) {
                    $name = $cat['name'] ?? null; if (!$name) continue;
                    $color = $cat['color'] ?? '#4CAF50';
                    $price = isset($cat['price']) ? (float)$cat['price'] : 0.0;
                    $desc = $cat['description'] ?? '';

                    if (isset($catMap[$name])) {
                        // Güncelle
                        $u = $pdo->prepare("UPDATE seat_categories SET color=:c, price=:p, description=:d WHERE id=:id AND event_id=:eid");
                        $u->execute([':c'=>$color, ':p'=>$price, ':d'=>$desc, ':id'=>$catMap[$name], ':eid'=>$eventId]);
                    } else {
                        // Hareket yoksa ekle, varsa yeni kategori eklenmesine de izin verelim
                        $ins = $pdo->prepare("INSERT INTO seat_categories (event_id, name, color, price, description, created_at) VALUES (:e,:n,:c,:p,:d,:t)");
                        $ins->execute([':e'=>$eventId, ':n'=>$name, ':c'=>$color, ':p'=>$price, ':d'=>$desc, ':t'=>date('Y-m-d H:i:s')]);
                        $catMap[$name] = (int)$pdo->lastInsertId();
                    }
                }
            }
        }

        // Koltuklar
        if (!empty($seatsJson)) {
            $seats = json_decode($seatsJson, true);
            if (is_array($seats)) {
                // Kategori ad->id eşlemesini güncel çek
                $catMap = [];
                $q = $pdo->prepare("SELECT id, name FROM seat_categories WHERE event_id = ?");
                $q->execute([$eventId]);
                foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $r) { $catMap[$r['name']] = (int)$r['id']; }

                if (!$hasMovements) {
                    // Temiz kurulum: tüm koltukları yeniden ekle
                    $ins = $pdo->prepare("INSERT INTO seats (event_id, row_number, seat_number, category_id, category_name, status, created_at)
                                           VALUES (:e,:r,:s,:c,:cn,'available',:t)");
                    foreach ($seats as $seat) {
                        $catName = $seat['category'] ?? 'standard';
                        $catId = $catMap[$catName] ?? null;
                        if (!$catId) continue;
                        $ins->execute([
                            ':e'=>$eventId,
                            ':r'=>(int)($seat['row'] ?? 0),
                            ':s'=>(int)($seat['seat'] ?? 0),
                            ':c'=>$catId,
                            ':cn'=>$catName,
                            ':t'=>date('Y-m-d H:i:s')
                        ]);
                    }
                } else {
                    // Sadece mevcut koltukların kategorisini/durumunu koruyarak güncelleme yapma (silme yok)
                    // Basit yaklaşım: hiçbir koltuğu silme/ekleme, sadece kategori fiyatlarına göre min/max güncelleyeceğiz.
                }
            }
        }

        // Fiyatlar
        if ($seatingType === 'reservation') {
            $pdo->prepare("UPDATE events SET min_price = 0, max_price = 0 WHERE id = :e")
                ->execute([':e'=>$eventId]);
        } else {
            $pdo->prepare("UPDATE events SET 
                min_price = (SELECT MIN(price) FROM seat_categories WHERE event_id = :e),
                max_price = (SELECT MAX(price) FROM seat_categories WHERE event_id = :e)
            WHERE id = :e")->execute([':e'=>$eventId]);
        }
    }

    // İndirim kodları (güvenli güncelleme: silme yerine sadece güncelle/ekle)
    $codes = $_POST['discount_code_code'] ?? [];
    $ids = $_POST['discount_code_id'] ?? [];
    $amts = $_POST['discount_code_amount'] ?? [];
    $qtys = $_POST['discount_code_quantity'] ?? [];
    $stats = $_POST['discount_code_status'] ?? [];

    if (is_array($codes) && count($codes) > 0) {
        for ($i=0; $i<count($codes); $i++) {
            $codeStr = strtoupper(trim($codes[$i] ?? ''));
            if ($codeStr === '') continue;
            $dcId = isset($ids[$i]) ? (int)$ids[$i] : 0;
            $amount = isset($amts[$i]) ? (float)$amts[$i] : 0;
            $qty = isset($qtys[$i]) ? (int)$qtys[$i] : 0;
            $st = isset($stats[$i]) ? trim($stats[$i]) : 'active';
            if ($amount < 0 || $qty < 0) continue;

            if ($dcId > 0) {
                // Mevcut kodu güncelle - negatif stok veya tüketilmiş kod silme yok
                $u = $pdo->prepare("UPDATE discount_codes SET code=:c, discount_amount=:a, quantity=:q, status=:s WHERE id=:id AND event_id=:e");
                try { $u->execute([':c'=>$codeStr, ':a'=>$amount, ':q'=>$qty, ':s'=>$st, ':id'=>$dcId, ':e'=>$eventId]); } catch (Exception $e) { /* kod benzersizliği vb. */ }
            } else {
                // Yeni kod ekle
                $ins = $pdo->prepare("INSERT INTO discount_codes (event_id, code, discount_amount, quantity, status) VALUES (:e,:c,:a,:q,:s)");
                try { $ins->execute([':e'=>$eventId, ':c'=>$codeStr, ':a'=>$amount, ':q'=>$qty, ':s'=>$st]); } catch (Exception $e) { /* aynı kod varsa atla */ }
            }
        }
    }

    // Aktivite logu
    $log = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description, created_at) VALUES (:u,:a,:d,:t)");
    $log->execute([
        ':u' => $_SESSION['user_id'],
        ':a' => 'event_updated',
        ':d' => 'Etkinlik güncellendi: ' . $title . ' (#' . $eventId . ')',
        ':t' => date('Y-m-d H:i:s')
    ]);

    $pdo->commit();

    // Eski görselleri sil (başarılıysa)
    if ($newImagePath && !empty($existingEvent['image_url'])) {
        $old = dirname(__DIR__) . '/' . ltrim($existingEvent['image_url'], '/');
        if (is_file($old)) @unlink($old);
    }
    if ($newArtistImagePath && !empty($existingEvent['artist_image_url'])) {
        $old = dirname(__DIR__) . '/' . ltrim($existingEvent['artist_image_url'], '/');
        if (is_file($old)) @unlink($old);
    }

    echo json_encode(['success' => true, 'message' => 'Etkinlik başarıyla güncellendi', 'event_id' => $eventId]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Yeni yüklenen dosyaları temizle
    if (!empty($cleanupFiles)) {
        foreach ($cleanupFiles as $f) { if (is_file($f)) @unlink($f); }
    }
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}
?>