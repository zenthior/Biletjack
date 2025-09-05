<?php 
require_once 'config/database.php';
require_once 'classes/Event.php';

// Site ayarlarını getir
function getSiteSetting($key, $default = '') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['setting_value'] : $default;
    } catch (Exception $e) {
        return $default;
    }
}

// Etkinlik ID'sini al
$eventId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($eventId <= 0) {
    header('Location: etkinlikler.php');
    exit();
}

// Database bağlantısını oluştur
$database = new Database();
$pdo = $database->getConnection();

// Google Maps API anahtarını al
$googleMapsApiKey = getSiteSetting('google_maps_api_key', '');

// Etkinlik bilgilerini getir
$query = "SELECT e.*, c.name as category_name, c.icon as category_icon,
                 od.company_name as organizer_name, od.phone as organizer_phone, 
                 od.email as organizer_email, od.instagram_url as organizer_instagram,
                 od.facebook_url as organizer_facebook, od.website as organizer_website,
                 e.event_rules, e.seating_type
          FROM events e
          LEFT JOIN categories c ON e.category_id = c.id
          LEFT JOIN organizer_details od ON e.organizer_id = od.user_id
          WHERE e.id = ? AND e.status = 'published'";
$stmt = $pdo->prepare($query);
$stmt->execute([$eventId]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

// Debug: Sanatçı görseli kontrolü
// var_dump($event['artist_image_url']); // Debug için açabilirsiniz

if (!$event) {
    header('Location: etkinlikler.php');
    exit();
}

// Görüntülenme sayacı (oturum başına benzersiz)
if (!isset($_SESSION)) { session_start(); }
if (!isset($_SESSION['viewed_events'])) {
    $_SESSION['viewed_events'] = [];
}
if (empty($_SESSION['viewed_events'][$eventId])) {
    try {
        // events.views + 1
        $inc = $pdo->prepare("UPDATE events SET views = COALESCE(views, 0) + 1 WHERE id = ?");
        $inc->execute([$eventId]);
        $_SESSION['viewed_events'][$eventId] = time();
    } catch (Exception $e) {
        // yoksay
    }

    // event_views tablosuna bilgi ekle (varsa)
    try {
        $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
        $sessionId = session_id();
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;

        $ev = $pdo->prepare("INSERT INTO event_views (event_id, user_id, session_id, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
        $ev->execute([$eventId, $userId, $sessionId, $ip, $ua]);
    } catch (Exception $e) {
        // tablo yoksa hata vermez
    }
}

// Bilet türlerini getir
if ($event && ($event['seating_type'] === 'seated' || $event['seating_type'] === 'reservation')) {
    // Koltuklu etkinlik veya rezervasyon sistemi için seat_categories'den bilgi çek
    $ticketQuery = "SELECT id, name, price, 0 as discount_price, '' as description FROM seat_categories WHERE event_id = ? ORDER BY price ASC";
} else {
    // Normal etkinlik için ticket_types'dan bilgi çek
    $ticketQuery = "SELECT * FROM ticket_types WHERE event_id = ? ORDER BY price ASC";
}
$ticketStmt = $pdo->prepare($ticketQuery);
$ticketStmt->execute([$eventId]);
$tickets = $ticketStmt->fetchAll(PDO::FETCH_ASSOC);

// Koltuklu etkinlik veya rezervasyon sistemi ise koltuk bilgilerini getir
$seatCategories = [];
$seats = [];
if ($event && ($event['seating_type'] === 'seated' || $event['seating_type'] === 'reservation')) {
    // Debug: Etkinlik bilgilerini kontrol et
    error_log("Event ID: " . $eventId);
    error_log("Seating Type: " . ($event['seating_type'] ?? 'undefined'));
    
    // Koltuk kategorilerini getir
    $seatCatQuery = "SELECT * FROM seat_categories WHERE event_id = ? ORDER BY price ASC";
    $seatCatStmt = $pdo->prepare($seatCatQuery);
    $seatCatStmt->execute([$eventId]);
    $seatCategories = $seatCatStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Koltuk kategorilerini kontrol et
    error_log("Seat Categories Count: " . count($seatCategories));
    error_log("Seat Categories: " . print_r($seatCategories, true));
    
    // Koltuk bilgilerini getir - category bilgilerini de dahil et
    $seatQuery = "SELECT s.*, sc.name as category_name, sc.color as category_color, sc.price 
                  FROM seats s 
                  LEFT JOIN seat_categories sc ON s.category_id = sc.id
                  WHERE s.event_id = ? 
                  ORDER BY s.row_number, s.seat_number";
    $seatStmt = $pdo->prepare($seatQuery);
    $seatStmt->execute([$eventId]);
    $seats = $seatStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Koltuk bilgilerini kontrol et
    error_log("Seats Count: " . count($seats));
    error_log("First 3 Seats: " . print_r(array_slice($seats, 0, 3), true));
}

// Sanatçıları ayır
$artists = !empty($event['artists']) ? explode(',', $event['artists']) : [];
$artists = array_map('trim', $artists);

// Etiketleri ayır
$tags = !empty($event['tags']) ? explode(',', $event['tags']) : [];
$tags = array_map('trim', $tags);

// Sosyal medya linklerini kontrol et
$socialLinks = [
    'instagram' => isset($event['instagram_url']) ? $event['instagram_url'] : '',
    'twitter' => isset($event['twitter_url']) ? $event['twitter_url'] : '',
    'facebook' => isset($event['facebook_url']) ? $event['facebook_url'] : '',
    'website' => isset($event['website_url']) ? $event['website_url'] : ''
];
$socialLinks = array_filter($socialLinks);

include 'includes/header.php'; 
?>
<link rel="stylesheet" href="css/pages.css">

<main>
    <!-- Etkinlik Hero Section - Papilet Tarzı -->
    <section class="event-hero">
        <div class="container">
            <div class="event-hero-content">
                <!-- Sol Taraf: Etkinlik Görseli -->
                <div class="event-image-section">
                    <div class="event-main-image" id="eventMainImage">
                        <?php
                        $isFav = false;
                        if (function_exists('isLoggedIn') && isLoggedIn() && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'customer') {
                            $stmtFav = $pdo->prepare("SELECT 1 FROM favorites WHERE user_id = ? AND event_id = ? LIMIT 1");
                            $stmtFav->execute([$_SESSION['user_id'], $eventId]);
                            $isFav = (bool) $stmtFav->fetchColumn();
                        }
                        ?>
                        <button class="favorite-btn pos-top-right<?php echo $isFav ? ' active' : ''; ?>" aria-label="Favorilere ekle" aria-pressed="<?php echo $isFav ? 'true' : 'false'; ?>" data-event-id="<?php echo $eventId; ?>">
                            <?php echo file_get_contents(__DIR__ . '/SVG/favorites.svg'); ?>
                        </button>
                        <div class="event-category-tag" id="eventCategoryTag">
                            <img src="SVG/music.svg" alt="Kategori" style="width: 24px; height: 24px; margin-right: 6px; vertical-align: middle;"> <?php echo htmlspecialchars($event['category_name']); ?>
                        </div>
                        <?php if ($event['image_url']): ?>
                            <style>
                                #eventMainImage {
                                    background: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.7)), url('<?php echo htmlspecialchars($event['image_url']); ?>') !important;
                                    background-size: cover !important;
                                    background-position: center !important;
                                }
                            </style>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Sağ Taraf: Etkinlik Bilgileri -->
                <div class="event-info-section">
                    <h1 class="event-title" id="eventTitle"><?php echo htmlspecialchars($event['title']); ?></h1>
                    
                    <div class="event-info-grid">
                        <!-- Bilet Alma Kartı -->
                        <div class="price-section">
                            <h3 style="margin-top: 10px;">Biletler</h3>
                            <div class="tickets-list">
                                <?php if (!empty($tickets) || (!empty($seatCategories) && $event['seating_type'] === 'seated')): ?>
                                    <?php 
                                    // Koltuklu etkinlik ise seat_categories'den, değilse tickets'dan ilk öğeyi al
                                    if (($event['seating_type'] === 'seated' || $event['seating_type'] === 'reservation') && !empty($seatCategories)) {
                                        $firstTicket = $seatCategories[0];
                                        $firstTicket['discount_price'] = 0; // Koltuklu etkinliklerde indirim yok
                                        $firstTicket['description'] = ''; // Açıklama yok
                                    } else {
                                        $firstTicket = $tickets[0];
                                    }
                                    ?>
                                        <div class="ticket-item">
                                            <div class="ticket-details">
                                                <div class="ticket-header">
                                                    <div class="ticket-name">
                                                        <h4><?php echo htmlspecialchars($event['venue_name']); ?></h4>
                                                        <div class="time-info">
                                                            <span class="info-label">Saat:</span>
                                                            <span class="info-value"><?php echo date('d.m.Y H:i', strtotime($event['event_date'])); ?></span>
                                                        </div>
                                                    </div>
                                                    <div class="price-info-top">
                                        <?php if ($event['seating_type'] === 'reservation'): ?>
                                            <span class="reservation-label">Rezervasyonlu</span>
                                        <?php elseif (isset($firstTicket['discount_price']) && $firstTicket['discount_price'] && $firstTicket['discount_price'] < $firstTicket['price']): ?>
                                            <span class="original-price">₺<?php echo number_format($firstTicket['price'], 0); ?></span>
                                            <span class="discount-price">₺<?php echo number_format($firstTicket['discount_price'], 0); ?></span>
                                        <?php else: ?>
                                            <span class="current-price">₺<?php echo number_format($firstTicket['price'], 0); ?></span>
                                        <?php endif; ?>
                                    </div>
                                                </div>
                                                <div class="venue-info">
                                                    <span class="info-label"><?php echo ($event['seating_type'] === 'seated' || $event['seating_type'] === 'reservation') ? 'Koltuk Kategorisi:' : 'Bilet:'; ?></span>
                                                    <span class="info-value"><?php echo htmlspecialchars($firstTicket['name']); ?></span>
                                                </div>
                                                <?php if (isset($firstTicket['description']) && $firstTicket['description']): ?>
                                                <div class="ticket-description">
                                                    <p>🎫 <?php echo htmlspecialchars($firstTicket['description']); ?></p>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="ticket-price-action">
                                                <button class="btn-primary btn-buy-ticket" onclick="<?php echo ($event['seating_type'] === 'seated' || $event['seating_type'] === 'reservation') ? 'openSeatSelectionModal()' : 'openTicketModal()'; ?>">
                    <i class="fas fa-ticket-alt"></i>
                    <?php echo ($event['seating_type'] === 'seated' || $event['seating_type'] === 'reservation') ? ($event['seating_type'] === 'reservation' ? 'Rezervasyon Yap' : 'Koltuk Seç') : 'Bilet Al'; ?>
                                </button>
                                            </div>
                                        </div>
                                <?php else: ?>
                                    <div class="no-tickets">
                                        <p>Bu etkinlik için henüz bilet bulunmamaktadır.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Etkinlik Hakkında -->
                    <div class="event-about">
                        <div class="about-header" style="margin-top: 10px;">
                            <span class="about-icon">
                                <img src="SVG/bilgilendirme.svg" alt="Bilgilendirme" style="width: 24px; height: 24px;">
                            </span>
                            <h3>Etkinlik Hakkında</h3>
                        </div>
                        <div class="about-content">
                            <div class="description-short">
                                <?php if ($event['short_description']): ?>
                                    <p><?php echo nl2br(htmlspecialchars($event['short_description'])); ?></p>
                                <?php elseif ($event['description']): ?>
                                    <p><?php echo nl2br(htmlspecialchars(mb_substr($event['description'], 0, 200))); ?><?php echo mb_strlen($event['description']) > 200 ? '...' : ''; ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="description-full" style="display: none;">
                                <p><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                            </div>
                            <?php if (($event['short_description'] && strlen($event['description']) > strlen($event['short_description'])) || (!$event['short_description'] && mb_strlen($event['description']) > 200)): ?>
                            <button class="btn-read-more" onclick="toggleDescription()">Devamını oku <i class="fas fa-chevron-down"></i></button>
                            <?php endif; ?>
                        </div>
                    </div>
                    

                </div>
            </div>
        </div>
    </section>
    
    <!-- Etkinlik Kuralları ve Mekan Bilgileri -->
    <section class="event-details-section">
        <div class="container">
            <div class="details-wrapper">
                <!-- Sol Sütun -->
                <div class="left-column">
                    <div class="details-card venue-card">
                        <div class="venue-header">
                            <h3 style="margin-top: 30px;"><?php echo htmlspecialchars($event['venue_name']); ?></h3>
                            <p class="venue-location"><?php echo htmlspecialchars($event['venue_address'] ?? $event['city']); ?></p>
                        </div>
                        
                        <!-- Google Harita -->
                        <div class="venue-map-container">
                            <?php if (!empty($googleMapsApiKey)): ?>
                            <iframe 
                                src="https://www.google.com/maps/embed/v1/place?key=<?php echo htmlspecialchars($googleMapsApiKey); ?>&q=<?php echo urlencode($event['venue_name'] . ', ' . ($event['venue_address'] ?? $event['city'])); ?>"
                                width="100%" 
                                height="300" 
                                style="border:0; border-radius: 8px;" 
                                allowfullscreen=""
                                loading="lazy">
                            </iframe>
                            <?php else: ?>
                            <div class="map-placeholder">
                                <div class="map-placeholder-content">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <h4>Google Maps Yüklenemedi</h4>
                                    <p>Google Maps API anahtarı geçersiz veya yapılandırılmamış.</p>
                                    <div class="map-error-details">
                                        <p><strong>Olası Nedenler:</strong></p>
                                        <ul>
                                            <li>API anahtarı girilmemiş</li>
                                            <li>API anahtarı geçersiz</li>
                                            <li>Maps Embed API etkinleştirilmemiş</li>
                                            <li>API kotası aşılmış</li>
                                        </ul>
                                    </div>
                                    <a href="admin/settings.php" class="btn-configure" target="_blank">
                                        <i class="fas fa-cog"></i> API Ayarlarını Yapılandır
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="venue-actions">
                            <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($event['venue_name'] . ' ' . $event['city']); ?>" 
                               target="_blank" class="btn-directions">
                                <img src="SVG/maps.svg" alt="Harita" style="width: 20px; height: 20px; margin-right: 8px;"> Yol Tarifi Al
                            </a>
                            <button onclick="shareLocation()" class="btn-share">
                                <img src="SVG/paylaş.svg" alt="Paylaş" style="width: 20px; height: 20px; margin-right: 8px;"> Konumu Paylaş
                            </button>
                        </div>
                    </div>
                    
                    
                </div>
                
                <!-- Orta Sütun -->
                <div class="middle-column">
                    <!-- İletişim Bilgileri -->
                    <?php if ($event['organizer_phone'] || $event['organizer_email'] || $event['organizer_instagram']): ?>
                    <div class="details-card contact-card">
                        <h3 style="margin-top: 30px;"><img src="SVG/personalcard.svg" alt="İletişim" style="width: 20px; height: 20px; margin-right: 8px;"> İletişim</h3>
                        <div class="contact-list">
                            <?php if ($event['organizer_phone']): ?>
                            <div class="contact-item">
                                <a href="tel:<?php echo htmlspecialchars($event['organizer_phone']); ?>" class="contact-link">
                                    <img src="SVG/call.svg" alt="Telefon" class="contact-icon-img">
                                    <span class="contact-text"><?php echo htmlspecialchars($event['organizer_phone']); ?></span>
                                </a>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($event['organizer_email']): ?>
                            <div class="contact-item">
                                <a href="mailto:<?php echo htmlspecialchars($event['organizer_email']); ?>" class="contact-link">
                                    <img src="SVG/email.svg" alt="E-posta" class="contact-icon-img">
                                    <span class="contact-text"><?php echo htmlspecialchars($event['organizer_email']); ?></span>
                                </a>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($event['organizer_instagram']): ?>
                            <div class="contact-item">
                                <a href="<?php echo htmlspecialchars($event['organizer_instagram']); ?>" target="_blank" class="contact-link">
                                    <img src="SVG/instagram.svg" alt="Instagram" class="contact-icon-img">
                                    <span class="contact-text">Instagram</span>
                                </a>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($event['organizer_phone']): ?>
                            <div class="contact-item">
                                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $event['organizer_phone']); ?>" target="_blank" class="contact-link">
                                    <img src="SVG/whatsapp.svg" alt="WhatsApp" class="contact-icon-img">
                                    <span class="contact-text"><?php echo htmlspecialchars($event['organizer_phone']); ?></span>
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Sanatçılar Kartı -->
                    <?php if (!empty($artists)): ?>
                    <div class="details-card artists-card">
                        <h3 style="margin-top: 30px;"><img src="SVG/microphone.svg" alt="Mikrofon" style="width: 20px; height: 20px; margin-right: 8px;"> Sanatçılar</h3>
                        <div class="artists-list">
                            <?php foreach ($artists as $artist): ?>
                            <div class="artist-item">
                                <div class="artist-avatar">
                                    <?php if (!empty($event['artist_image_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($event['artist_image_url']); ?>" alt="<?php echo htmlspecialchars($artist); ?>" class="artist-image">
                                    <?php else: ?>
                                        <span class="artist-initial"><?php echo strtoupper(substr(trim($artist), 0, 1)); ?></span>
                                    <?php endif; ?>
                                </div>
                                <span class="artist-name"><?php echo htmlspecialchars($artist); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Mekan Bilgileri -->
                    
                </div>
                
                <!-- Sağ Sütun -->
                <div class="right-column">
                
                <!-- Organizatör Bilgileri -->
                <?php if ($event['organizer_name']): ?>
                    <?php
                    // Organizatör detaylarını getir
                    $organizerQuery = "SELECT od.*, u.profile_image 
                                     FROM organizer_details od 
                                     LEFT JOIN users u ON od.user_id = u.id 
                                     WHERE od.user_id = ?";
                    $organizerStmt = $pdo->prepare($organizerQuery);
                    $organizerStmt->execute([$event['organizer_id']]);
                    $organizer = $organizerStmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Kullanıcının bu organizatörü takip edip etmediğini kontrol et
                    $isFollowing = false;
                    if (isLoggedIn()) {
                        $checkFollowQuery = "SELECT id FROM followers WHERE user_id = ? AND organizer_id = ?";
                        $checkFollowStmt = $pdo->prepare($checkFollowQuery);
                        $checkFollowStmt->execute([$_SESSION['user_id'], $event['organizer_id']]);
                        $isFollowing = $checkFollowStmt->fetch() ? true : false;
                    }
                    ?>
                <div class="details-card organizer-card" <?php if ($organizer && $organizer['cover_image_url']): ?>style="background-image: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.6)), url('<?php echo htmlspecialchars($organizer['cover_image_url']); ?>'); background-size: cover; background-position: center;"<?php endif; ?>>
                    <div class="organizer-info">
                        <div class="organizer-profile">
                            <div class="organizer-avatar">
                                <?php if ($organizer && ($organizer['logo_url'] || $organizer['profile_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($organizer['logo_url'] ?: $organizer['profile_image']); ?>" alt="Profil Resmi" class="organizer-profile-img">
                                <?php else: ?>
                                    <div class="organizer-avatar-placeholder">
                                        <i class="fas fa-building"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="organizer-details">
                                <h4 class="organizer-name" onclick="openOrganizerProfile(<?php echo $event['organizer_id']; ?>)">
                                    <?php echo htmlspecialchars($event['organizer_name']); ?>
                                </h4>
                                <?php if ($organizer && isset($organizer['description']) && $organizer['description']): ?>
                                <p class="organizer-description"><?php echo htmlspecialchars($organizer['description']); ?></p>
                                <?php endif; ?>
                                <?php if ($organizer && $organizer['city']): ?>
                                <p class="organizer-location"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($organizer['city']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="organizer-actions">
                            <?php if (isLoggedIn() && $_SESSION['user_id'] != $event['organizer_id']): ?>
                            <button class="btn-follow <?php echo $isFollowing ? 'following' : ''; ?>" onclick="followOrganizer(<?php echo $event['organizer_id']; ?>)">
                                <?php if ($isFollowing): ?>
                                    <i class="fas fa-check"></i> Takip Ediliyor
                                <?php else: ?>
                                    <i class="fas fa-plus"></i> Takip Et
                                <?php endif; ?>
                            </button>
                            <?php elseif (!isLoggedIn()): ?>
                            <button class="btn-follow" onclick="showLoginAlert()">
                                <i class="fas fa-plus"></i> Takip Et
                            </button>
                            <?php endif; ?>
                            <a href="organizer-profile.php?id=<?php echo $event['organizer_id']; ?>" class="btn-profile">
                                <i class="fas fa-eye"></i> Profili Gör
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    
    <!-- Benzer Etkinlikler -->
    <section class="similar-events">
        <div class="container">
            <h2>Benzer Etkinlikler</h2>
            <div class="similar-events-grid">
                
            </div>
        </div>
    </section>
</main>

<!-- Bilet Satın Alma Modalı -->
<div id="ticketModal" class="ticket-modal">
    <div class="modal-content-ticket">
        <div class="modal-header-ticket">
            <h2 id="modalEventTitle">Bilet Satın Al</h2>
            <span class="close-ticket" onclick="closeTicketModal()">&times;</span>
        </div>
        <div class="modal-body-ticket">
            <!-- Sayfa 1: Etkinlik Kuralları -->
            <div class="modal-page active" id="rulesPage">
                <div class="modal-body">
                    <h4>Etkinlik Kuralları</h4>
                    <?php if (!empty($event['event_rules'])): ?>
                        <div class="event-rules-text">
                            <?php echo nl2br(htmlspecialchars($event['event_rules'])); ?>
                        </div>
                    <?php else: ?>
                        <div class="event-rules-text">
                            <ul class="modal-rules-list">
                                <li>• Etkinlik başlangıç saatinden 30 dakika önce mekan girişinde bulunmanız gerekmektedir.</li>
                                <li>• Etkinlik sırasında fotoğraf ve video çekimi yasaktır.</li>
                                <li>• Yiyecek ve içecek dışarıdan getirilemez.</li>
                                <li>• Etkinlik iptal durumunda bilet bedeli iade edilecektir.</li>
                                <li>• Biletler başkasına devredilemez.</li>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button class="btn-secondary" onclick="closeTicketModal()">İptal</button>
                    <button class="btn-primary" onclick="showTicketSelection()">İleri</button>
                </div>
            </div>

            <!-- Sayfa 2: Bilet Seçimi -->
            <div class="modal-page" id="ticketSelectionPage">
                <?php if ($event['seating_type'] === 'seated' || $event['seating_type'] === 'reservation'): ?>
                    <!-- Koltuklu Sistem -->
                    <div class="seating-selection-container">
                        <div class="seating-layout">
                            <div class="stage-indicator">
                                <i class="fas fa-music"></i>
                                SAHNE
                            </div>
                            <div class="seats-container" id="seatsContainer">
                                <!-- Koltuklar JavaScript ile oluşturulacak -->
                            </div>
                            <div class="seat-legend">
                                <div class="legend-item">
                                    <div class="seat-sample available"></div>
                                    <span>Müsait</span>
                                </div>
                                <div class="legend-item">
                                    <div class="seat-sample selected"></div>
                                    <span>Seçili</span>
                                </div>
                                <div class="legend-item">
                                    <div class="seat-sample occupied"></div>
                                    <span>Dolu</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="seat-categories-list">
                            <h4>Koltuk Kategorileri</h4>
                            <?php foreach ($seatCategories as $category): ?>
                            <div class="category-item" data-category="<?php echo $category['id']; ?>">
                                <div class="category-color" style="background-color: <?php echo $category['color']; ?>"></div>
                                <div class="category-info">
                                    <span class="category-name"><?php echo htmlspecialchars($category['name']); ?></span>
                                    <?php if ($event['seating_type'] === 'reservation'): ?>
                                        <span class="category-price reservation-label">Rezervasyon</span>
                                    <?php else: ?>
                                        <span class="category-price">₺<?php echo number_format($category['price'], 0); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="selected-seats-info" style="display: none;">
                        <h4>Seçilen Koltuklar</h4>
                        <div id="selectedSeatsList"></div>
                        <div class="total-price">
                            <h4>Toplam: <span id="seatTotalPrice">₺0</span></h4>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Geleneksel Bilet Sistemi -->
                    <div class="ticket-types-grid">
                        <?php foreach ($tickets as $ticket): ?>
                        <div class="ticket-type-card" 
                             data-ticket-id="<?php echo $ticket['id']; ?>"
                             data-ticket-name="<?php echo htmlspecialchars($ticket['name']); ?>"
                             data-ticket-price="<?php echo $ticket['discount_price'] ?: $ticket['price']; ?>"
                             data-ticket-description="<?php echo htmlspecialchars($ticket['description'] ?? ''); ?>"
                             data-max-per-order="<?php echo $ticket['max_per_order'] ?? 10; ?>">
                            <div class="ticket-type-header">
                                <h4><?php echo htmlspecialchars($ticket['name']); ?></h4>
                                <div class="ticket-type-price">
                                    <?php if ($ticket['discount_price'] && $ticket['discount_price'] < $ticket['price']): ?>
                                        <span class="original-price">₺<?php echo number_format($ticket['price'], 0); ?></span>
                                        <span class="discount-price">₺<?php echo number_format($ticket['discount_price'], 0); ?></span>
                                    <?php else: ?>
                                        <span class="current-price">₺<?php echo number_format($ticket['price'], 0); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if ($ticket['description']): ?>
                            <div class="ticket-type-description">
                                <p><?php echo htmlspecialchars($ticket['description']); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="selected-ticket-info" style="display: none;">
                        <h5 id="eventSelectedTicketName"></h5>
                        <p id="eventSelectedTicketPrice"></p>
                        <p id="eventSelectedTicketDescription"></p>
                    </div>
                    
                    <div class="quantity-selector" style="display: none;">
                        <label for="ticketQuantity">Bilet Adedi:</label>
                        <div class="quantity-controls">
                            <button type="button" class="quantity-btn minus-btn" onclick="decreaseQuantity()">-</button>
                            <input type="number" id="eventTicketQuantity" class="quantity-input" min="1" max="10" value="1" readonly>
                            <button type="button" class="quantity-btn plus-btn" onclick="increaseQuantity()">+</button>
                        </div>
                    </div>
                    
                    <div class="total-price" style="display: none;">
                        <h4>Toplam: <span id="eventTotalPrice">₺0</span></h4>
                    </div>
                <?php endif; ?>
                
                <div class="modal-footer">
                    <button class="btn-secondary" onclick="showRulesPage()">Geri</button>
                    <?php if (isLoggedIn() && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'customer'): ?>
                    <button class="btn-primary" onclick="addToCart()" disabled id="addToCartBtn">
                        <?php echo ($event['seating_type'] === 'seated' || $event['seating_type'] === 'reservation') ? ($event['seating_type'] === 'reservation' ? 'Rezervasyon Talebi Gönder' : 'Koltukları Sepete Ekle') : 'Sepete Ekle'; ?>
                    </button>
                    <?php else: ?>
                    <button class="btn-primary" disabled title="Sadece müşteri hesapları bilet alabilir">
                        <?php echo ($event['seating_type'] === 'seated' || $event['seating_type'] === 'reservation') ? ($event['seating_type'] === 'reservation' ? 'Rezervasyon Talebi Gönder' : 'Koltukları Sepete Ekle') : 'Sepete Ekle'; ?>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Yeni Modern Koltuk Seçim Modalı -->
<div id="seatSelectionModal" class="modern-seat-modal">
    <div class="modal-backdrop" onclick="closeSeatModal()"></div>
    <div class="modern-modal-content">
        <!-- Modal Header -->
        <div class="modern-modal-header">
            <div class="header-content">
                <h2 class="modal-title">
                    <i class="fas fa-chair"></i>
                    Koltuk Seçimi
                </h2>
            </div>
            <button class="modern-close-btn" onclick="closeSeatModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="modern-modal-body">
            <!-- Sahne Göstergesi -->
            <div class="modern-stage-indicator">
                <div class="stage-light"></div>
                <div class="stage-text">
                    <i class="fas fa-theater-masks"></i>
                    SAHNE
                </div>
                <div class="stage-light"></div>
            </div>

            <!-- Koltuk Haritası -->
            <div class="modern-seats-container">
                <div class="seats-wrapper" id="seatsContainer">
                    <!-- Koltuklar JavaScript ile oluşturulacak -->
                </div>
            </div>

            <!-- Koltuk Kategorileri ve Bilgiler -->
            <div class="seat-info-panel">
                <!-- Koltuk Kategorileri -->
                <div class="categories-section">
                    <h4><i class="fas fa-tags"></i> Kategoriler</h4>
                    <div class="categories-grid">
                        <?php foreach ($seatCategories as $category): ?>
                        <div class="modern-category-item">
                            <div class="category-indicator" style="background: <?php echo $category['color']; ?>"></div>
                            <div class="category-details">
                                <span class="category-name"><?php echo htmlspecialchars($category['name']); ?></span>
                                <?php if ($event['seating_type'] === 'reservation'): ?>
                                    <span class="category-price">Rezervasyon</span>
                                <?php else: ?>
                                    <span class="category-price">₺<?php echo number_format($category['price'], 0); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Koltuk Durumu Açıklaması -->
                <div class="legend-section">
                    <h4><i class="fas fa-info-circle"></i> Durum</h4>
                    <div class="legend-grid">
                        <div class="legend-item">
                            <div class="seat-sample available"></div>
                            <span>Müsait</span>
                        </div>
                        <div class="legend-item">
                            <div class="seat-sample selected"></div>
                            <span>Seçili</span>
                        </div>
                        <div class="legend-item">
                            <div class="seat-sample occupied"></div>
                            <span>Dolu</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Seçilen Koltuklar -->
            <div class="selected-seats-panel" id="selectedSeatsPanel" style="display: none;">
                <div class="panel-header">
                    <h4><i class="fas fa-check-circle"></i> Seçilen Koltuklar</h4>
                    <span class="seat-count" id="selectedSeatCount">0</span>
                </div>
                <div class="selected-seats-list" id="selectedSeatsList"></div>
                <div class="total-section">
                    <div class="total-price">
                        <span class="total-label">Toplam Tutar:</span>
                        <span class="total-amount" id="seatTotalPrice">₺0</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="modern-modal-footer">
            <button class="btn-cancel" onclick="closeSeatModal()">
                <i class="fas fa-times"></i>
                İptal
            </button>
            <?php if (isLoggedIn() && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'customer'): ?>
            <button class="btn-confirm" onclick="<?php echo $event['seating_type'] === 'reservation' ? 'submitReservation()' : 'addSeatsToCart()'; ?>" disabled id="addSeatsToCartBtn">
                <?php if ($event['seating_type'] === 'reservation'): ?>
                    <i class="fas fa-calendar-check"></i>
                    Rezerve Et
                <?php else: ?>
                    <i class="fas fa-shopping-cart"></i>
                    Sepete Ekle
                <?php endif; ?>
                <span class="btn-badge" id="confirmBadge">0</span>
            </button>
            <?php else: ?>
            <button class="btn-confirm" disabled title="Sadece müşteri hesapları bilet alabilir">
                <?php if ($event['seating_type'] === 'reservation'): ?>
                    <i class="fas fa-calendar-check"></i>
                    Rezerve Et
                <?php else: ?>
                    <i class="fas fa-shopping-cart"></i>
                    Sepete Ekle
                <?php endif; ?>
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* Modal Stilleri */
.ticket-modal {
    display: none;
    position: fixed;
    z-index: 9999 !important; /* Daha yüksek z-index */
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(5px);
}

/* Modal açık olduğunda */
.ticket-modal[style*="block"] {
    display: block !important;
}

.modal-content {
    background-color: white;
    margin: 5% auto;
    padding: 0;
    border-radius: 16px;
    width: 90%;
    max-width: 700px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    animation: modalSlideIn 0.3s ease;
}

.modal-content-ticket {
    background-color: white;
    margin: 5% auto;
    padding: 0;
    border-radius: 16px;
    width: 90%;
    max-width: 700px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    animation: modalSlideIn 0.3s ease;
    z-index: 10000 !important;
}

.modal-header-ticket {
    padding: 20px 24px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: #333;
    border-radius: 16px 16px 0 0;
}

.modal-header-ticket h2 {
    margin: 0;
    color: #1a1a1a;
    font-size: 18px;
    font-weight: 600;
}

.close-ticket {
    font-size: 24px;
    font-weight: bold;
    cursor: pointer;
    color: #999;
    transition: color 0.3s;
    background: none;
    border: none;
    padding: 5px;
    border-radius: 50%;
    width: 35px;
    height: 35px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.close-ticket:hover {
    color: #333;
}

.modal-body-ticket {
    padding: 24px;
    max-height: 70vh;
    overflow-y: auto;
}

.modal-body-ticket h4 {
    margin: 0 0 16px 0;
    color: #1a1a1a;
    font-size: 16px;
    font-weight: 600;
}

@keyframes modalSlideIn {
    from { transform: translateY(-50px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.modal-header {
    padding: 20px 24px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    color: #1a1a1a;
    font-size: 18px;
    font-weight: 600;
}

.close-modal {
    font-size: 24px;
    font-weight: bold;
    cursor: pointer;
    color: #999;
    transition: color 0.3s;
}

.close-modal:hover {
    color: #333;
}

.modal-page {
    display: none;
}

.modal-page.active {
    display: block;
}

.modal-body {
    padding: 24px;
}

.modal-body h4 {
    margin: 0 0 16px 0;
    color: #1a1a1a;
    font-size: 16px;
    font-weight: 600;
}

.modal-rules-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.modal-rules-list li {
    padding: 8px 0;
    color: #333;
    font-size: 14px;
    line-height: 1.4;
}

.event-rules-text {
    color: #000;
    font-size: 14px;
    line-height: 1.6;
    padding: 12px 0;
    background: #fff;
    border-radius: 8px;
    padding: 16px;
    border: 1px solid #e0e0e0;
}

.ticket-types-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
    margin-bottom: 20px;
}

.ticket-type-card {
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    padding: 16px;
    cursor: pointer;
    transition: all 0.3s ease;
    background: white;
}

.ticket-type-card:hover {
    border-color: #667eea;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
}

.ticket-type-card.selected {
    border-color: #667eea;
    background: #f0f8ff;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
}

.ticket-type-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 8px;
}

.ticket-type-header h5 {
    margin: 0;
    color: #1a1a1a;
    font-size: 16px;
    font-weight: 600;
}

.ticket-type-price {
    text-align: right;
}

.ticket-type-price .original-price {
    display: block;
    text-decoration: line-through;
    color: #999;
    font-size: 12px;
    margin-bottom: 2px;
}

.ticket-type-price .discount-price,
.ticket-type-price .current-price {
    display: block;
    color: #667eea;
    font-weight: 700;
    font-size: 16px;
}

.ticket-type-description {
    margin-top: 8px;
}

.ticket-type-description p {
    margin: 0;
    color: #666;
    font-size: 13px;
    line-height: 1.4;
}

.selected-ticket-info {
    background: #f8f9fa;
    padding: 16px;
    border-radius: 12px;
    margin-bottom: 20px;
}

.selected-ticket-info h5 {
    margin: 0 0 8px 0;
    color: #1a1a1a;
    font-size: 16px;
    font-weight: 600;
}

.selected-ticket-info p {
    margin: 4px 0;
    color: #666;
    font-size: 14px;
}

.quantity-selector {
    margin-bottom: 20px;
}

.quantity-selector label {
    display: block;
    margin-bottom: 8px;
    color: #1a1a1a;
    font-weight: 500;
}

/* Çakışan CSS tanımı kaldırıldı - quantity-controls aşağıda doğru şekilde tanımlanmış */

.total-price {
    text-align: center;
    padding: 16px;
    background: #f0f8ff;
    border-radius: 12px;
    border: 2px solid #667eea;
}

.total-price h4 {
    margin: 0;
    color: #667eea;
    font-size: 18px;
}

.modal-footer {
    padding: 20px 24px;
    border-top: 1px solid #eee;
    display: flex;
    gap: 12px;
    justify-content: flex-end;
}

.btn-secondary {
    padding: 12px 24px;
    border: 1px solid #ddd;
    background: white;
    color: #666;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s;
}

.btn-secondary:hover {
    background: #f8f9fa;
    border-color: #999;
}

.btn-primary {
    padding: 12px 24px;
    border: none;
    background: #667eea;
    color: white;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s;
}

.btn-primary:hover:not(:disabled) {
    background: #5a67d8;
    transform: translateY(-1px);
}

.btn-primary:disabled {
    background: #ccc;
    cursor: not-allowed;
    transform: none;
}

.reservation-label {
    color: #28a745;
    font-weight: 600;
    font-size: 14px;
    background: #d4edda;
    padding: 4px 8px;
    border-radius: 4px;
    border: 1px solid #c3e6cb;
}

.btn-primary:hover {
    background: #5a6fd8;
    transform: translateY(-1px);
}

/* Yeni Bilet Listesi Stilleri */
.tickets-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.ticket-item {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    padding: 1.5rem;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    position: relative;
}

.ticket-item:hover {
    border-color: #667eea;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.1);
    transform: translateY(-2px);
}

.ticket-details {
    margin-bottom: 1rem;
}

.ticket-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.ticket-name {
    flex: 1;
}

.ticket-name h4 {
    margin: 0 0 0.5rem 0;
    color: #1a1a1a;
    font-size: 1.2rem;
    font-weight: 600;
}

.price-info-top {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    background: #28a745;
    color: white;
    padding: 0.8rem 1rem;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
}

.venue-info, .time-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.3rem;
}

.info-label {
    font-weight: 600;
    color: #667eea;
    font-size: 0.9rem;
}

.info-value {
    color: #333;
    font-size: 0.9rem;
}

.ticket-description {
    margin-top: 0.5rem;
}

.ticket-description p {
    margin: 0;
    color: #666;
    font-size: 0.9rem;
    line-height: 1.4;
}

.ticket-price-action {
    display: flex;
    justify-content: center;
    padding-top: 1rem;
    border-top: 1px solid #f0f0f0;
}

.original-price {
    text-decoration: line-through;
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.9rem;
    margin-bottom: 0.2rem;
}

.discount-price, .current-price {
    font-weight: 700;
    color: white;
    font-size: 1.3rem;
}

.no-tickets {
    text-align: center;
    padding: 2rem;
    color: #666;
    background: #f8f9fa;
    border-radius: 12px;
}

/* Responsive tasarım */
@media (max-width: 768px) {
    .ticket-item {
        padding: 1rem;
    }
    
    .ticket-header {
        flex-direction: column;
        gap: 1rem;
    }
    
    .ticket-price-action {
        margin-top: 1rem;
    }
}

/* Genel Kart Stilleri - Tüm kartlara gölge ekleme */
.details-card, .price-section, .event-about {
    background: white;
    border-radius: 12px;
    padding-top: 0px;
    box-shadow: 0 6px 25px rgba(0,0,0,0.15), 0 2px 8px rgba(0,0,0,0.1);
    border: 1px solid rgba(0,0,0,0.05);
    transition: all 0.3s ease;
}

.details-card:hover, .price-section:hover, .event-about:hover {
    box-shadow: 0 12px 40px rgba(0,0,0,0.2), 0 4px 15px rgba(0,0,0,0.15);
    transform: translateY(-3px);
}

/* Mekan kartı stilleri */
.venue-card {
    overflow: hidden;
}

.venue-header {
    margin-bottom: 20px;
}

.venue-header h3 {
    color: #1f2937;
    font-size: 1.4rem;
    font-weight: 600;
    margin: 0 0 8px 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.venue-location {
    color: #6b7280;
    font-size: 1rem;
    margin: 0;
    line-height: 1.5;
}

.venue-map-container {
    margin: 20px 0;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.venue-actions {
    display: flex;
    gap: 12px;
    margin-top: 20px;
    flex-wrap: wrap;
}

.btn-directions, .btn-share {
    flex: 1;
    min-width: 140px;
    padding: 12px 16px;
    border: none;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 500;
    text-decoration: none;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-directions {
    background: #3b82f6;
    color: white;
}

.btn-directions:hover {
    background: #2563eb;
    transform: translateY(-1px);
}

.btn-share {
    background: #f3f4f6;
    color: #374151;
    border: 1px solid #d1d5db;
}

.btn-share:hover {
    background: #e5e7eb;
    transform: translateY(-1px);
}

@media (max-width: 768px) {
    .venue-actions {
        flex-direction: column;
    }
    
    .btn-directions, .btn-share {
        flex: none;
        width: 100%;
    }
}

/* İletişim kartı stilleri */
.contact-card {
    overflow: hidden;
}

.contact-buttons {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 12px;
    margin-bottom: 30px;
}

.contact-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 16px 12px;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 500;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    border: 2px solid transparent;
    min-height: 80px;
}

.contact-btn i {
    font-size: 1.5rem;
    margin-bottom: 8px;
}

.contact-btn span {
    font-size: 0.85rem;
}

.contact-icons {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
    justify-content: flex-start;
    align-items: center;
}

.contact-icon {
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 40px;
    display: inline-block;
}

.contact-icon:hover {
    transform: scale(1.3);
}

.phone-btn {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: white;
}

.phone-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
}

.email-btn {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
}

.email-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(239, 68, 68, 0.3);
}

.instagram-btn {
    background: linear-gradient(135deg, #e91e63, #ad1457);
    color: white;
}

.instagram-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(233, 30, 99, 0.3);
}

.whatsapp-btn {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.whatsapp-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
}

/* Sanatçılar bölümü stilleri */
.artists-section {
    border-top: 1px solid #e5e7eb;
    padding-top: 24px;
}

.artists-section h4 {
    color: #1f2937;
    font-size: 1.2rem;
    font-weight: 600;
    margin: 0 0 16px 0;
}

.artists-list {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

.artist-item {
    display: flex;
    align-items: center;
    gap: 12px;
    background: #f8fafc;
    padding: 12px 16px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    transition: all 0.3s ease;
}

.artist-item:hover {
    background: #f1f5f9;
    transform: translateY(-1px);
}

.artist-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 1.1rem;
    overflow: hidden;
}

.artist-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}

.artist-name {
    color: #374151;
    font-weight: 500;
    font-size: 0.95rem;
}

.no-artists {
    color: #6b7280;
    font-style: italic;
    margin: 0;
}

@media (max-width: 768px) {
    .contact-buttons {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .artists-list {
        flex-direction: column;
    }
    
    .artist-item {
        width: 100%;
    }
}

/* Harita stilleri */
.map-container {
    margin-top: 1rem;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    background: white;
}

.venue-map {
    width: 100%;
    height: 350px;
    border: none;
    border-radius: 12px 12px 0 0;
    position: relative;
}

.map-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    background: #f8f9fa;
    color: #666;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #e9ecef;
    border-top: 4px solid #667eea;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 1rem;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.map-info {
    padding: 1.5rem;
    background: white;
    border-top: 1px solid #e9ecef;
}

.location-details h4 {
    margin: 0 0 0.5rem 0;
    color: #333;
    font-size: 1.2rem;
}

.location-details .address {
    margin: 0 0 1rem 0;
    color: #666;
    font-size: 0.95rem;
}

.map-actions {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.btn-directions, .btn-share {
    background: #667eea;
    color: white;
    border: none;
    padding: 0.6rem 1.2rem;
    border-radius: 6px;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-directions:hover, .btn-share:hover {
    background: #5a67d8;
    transform: translateY(-1px);
    color: white;
    text-decoration: none;
}

.btn-share {
    background: #48bb78;
}

.btn-share:hover {
    background: #38a169;
}

.btn-map {
    background: #667eea;
    color: white;
    border: none;
    padding: 0.8rem 1.5rem;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s ease;
    margin-top: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Harita placeholder stilleri */
.map-placeholder {
    height: 300px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px dashed #dee2e6;
}

.map-placeholder-content {
    text-align: center;
    color: #6c757d;
    padding: 2rem;
}

.map-placeholder-content i {
    font-size: 3rem;
    color: #ffc107;
    margin-bottom: 1rem;
}

.map-error-details {
    text-align: left;
    margin: 1rem 0;
    padding: 1rem;
    background: rgba(255, 193, 7, 0.1);
    border-radius: 6px;
    border-left: 4px solid #ffc107;
}

.map-error-details p {
    margin: 0 0 0.5rem 0;
    font-weight: 600;
    color: #495057;
}

.map-error-details ul {
    margin: 0;
    padding-left: 1.2rem;
    list-style-type: disc;
}

.map-error-details li {
    margin: 0.3rem 0;
    color: #6c757d;
    font-size: 0.85rem;
}

.map-placeholder-content h4 {
    margin: 0 0 0.5rem 0;
    color: #495057;
    font-size: 1.2rem;
}

.map-placeholder-content p {
    margin: 0 0 1.5rem 0;
    color: #6c757d;
    font-size: 0.9rem;
}

.btn-configure {
    background: #007bff;
    color: white;
    text-decoration: none;
    padding: 0.75rem 1.5rem;
    border-radius: 6px;
    font-size: 0.9rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
}

.btn-configure:hover {
    background: #0056b3;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
    color: white;
    text-decoration: none;
}

.btn-map:hover {
    background: #5a67d8;
    transform: translateY(-2px);
}

.btn-map.active {
    background: #e53e3e;
}

.btn-map.active::before {
    content: "❌ ";
}

.btn-map.active::after {
    content: " Gizle";
}

@media (max-width: 768px) {
    .venue-map {
        height: 250px;
    }
}

/* Quantity Controls Stilleri */
.quantity-controls {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0;
    margin-top: 15px;
    background: #f8f9fa;
    border-radius: 10px;
    padding: 5px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    max-width: 150px;
    margin-left: auto;
    margin-right: auto;
}

.quantity-btn {
    width: 40px;
    height: 40px;
    border: none;
    background: #007bff;
    color: white;
    font-size: 16px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    border-radius: 8px;
}

.quantity-btn:hover {
    background: #0056b3;
    transform: scale(1.05);
}

.quantity-btn:active {
    transform: scale(0.95);
}

.quantity-btn.minus-btn {
    border-radius: 8px 0 0 8px;
}

.quantity-btn.plus-btn {
    border-radius: 0 8px 8px 0;
}

.quantity-input {
    width: 60px;
    height: 40px;
    border: none;
    text-align: center;
    font-size: 16px;
    font-weight: 600;
    background: white;
    color: #333;
    outline: none;
    border-radius: 0;
    -moz-appearance: textfield;
    transition: all 0.3s ease;
}

.quantity-input::-webkit-outer-spin-button,
.quantity-input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.quantity-selector label {
    font-size: 16px;
    font-weight: 600;
    color: #333;
    margin-bottom: 10px;
    display: block;
    text-align: center;
}

/* Modern Koltuk Seçim Modalı Stilleri */
.modern-seat-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 10000;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.modern-seat-modal.active {
    opacity: 1;
    visibility: visible;
}

.modal-backdrop {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(8px);
}

.modern-modal-content {
    position: relative;
    background: #ffffff;
    margin: 2vh auto;
    width: 95%;
    max-width: 1200px;
    max-height: 96vh;
    border-radius: 24px;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    transform: translateY(50px) scale(0.95);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.modern-seat-modal.active .modern-modal-content {
    transform: translateY(0) scale(1);
}

/* Modal Header */
.modern-modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 24px 32px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
    overflow: hidden;
}

.modern-modal-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="%23ffffff" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>') repeat;
    opacity: 0.3;
}

.header-content {
    position: relative;
    z-index: 1;
}

.modal-title {
    font-size: 24px;
    font-weight: 700;
    margin: 0 0 4px 0;
    display: flex;
    align-items: center;
    gap: 12px;
}

/* Modal içindeki event-title stilini düzelt */
.modern-modal-header .event-title {
    font-size: 14px;
    opacity: 0.9;
    margin: 0;
    font-weight: 400;
}

/* Ana sayfa h1 stilini geri getir */
h1.event-title {
    font-size: 2.5rem !important;
    font-weight: 700 !important;
    margin: 0 0 1rem 0 !important;
    color: #1a1a1a !important;
    opacity: 1 !important;
    line-height: 1.2 !important;
}

@media (max-width: 768px) {
    h1.event-title {
        font-size: 2rem !important;
    }
}

@media (max-width: 480px) {
    h1.event-title {
        font-size: 1.5rem !important;
    }
}

.modern-close-btn {
    background: rgba(255, 255, 255, 0.95) !important;
    border: 2px solid rgba(255, 255, 255, 0.8) !important;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    color: #333 !important;
    font-size: 20px !important;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex !important;
    align-items: center;
    justify-content: center;
    position: relative;
    z-index: 1000 !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.modern-close-btn:hover {
    background: rgba(255, 255, 255, 1) !important;
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.modern-close-btn i {
    font-size: 18px !important;
    color: #333 !important;
}

/* Modal Body */
.modern-modal-body {
    flex: 1;
    padding: 32px;
    overflow-y: auto;
    background: #fafbfc;
}

/* Sahne Göstergesi */
.modern-stage-indicator {
    background: linear-gradient(135deg, #ff6b6b, #ee5a24);
    color: white;
    padding: 16px 24px;
    border-radius: 16px;
    text-align: center;
    margin-bottom: 32px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 8px 25px rgba(238, 90, 36, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16px;
}

.stage-light {
    width: 12px;
    height: 12px;
    background: #fff;
    border-radius: 50%;
    animation: stageLightPulse 2s infinite;
}

.stage-text {
    font-size: 18px;
    font-weight: 700;
    letter-spacing: 2px;
    display: flex;
    align-items: center;
    gap: 8px;
}

@keyframes stageLightPulse {
    0%, 100% { opacity: 0.6; transform: scale(1); }
    50% { opacity: 1; transform: scale(1.2); }
}

/* Koltuk Haritası */
.modern-seats-container {
    background: white;
    border-radius: 20px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid #e8ecef;
}

.seats-wrapper {
    max-height: 400px;
    overflow-y: auto;
    overflow-x: auto;
    padding: 16px;
    border-radius: 12px;
    background: #f8f9fa;
}

.seat-row {
    display: flex;
    align-items: center;
    margin-bottom: 8px;
    gap: 4px;
    justify-content: center;
}

.row-label {
    width: 40px;
    text-align: center;
    font-weight: 700;
    color: #495057;
    font-size: 14px;
    background: #e9ecef;
    border-radius: 8px;
    padding: 8px 4px;
    margin-right: 12px;
}

.seat {
    width: 36px;
    height: 36px;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    margin: 0 2px;
    position: relative;
    overflow: hidden;
}

.seat::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
    transition: left 0.5s;
}

.seat:hover::before {
    left: 100%;
}

.seat.available {
    background: linear-gradient(135deg, #d4edda, #c3e6cb);
    border-color: #28a745;
    color: #155724;
}

.seat.available:hover {
    background: linear-gradient(135deg, #c3e6cb, #b1dfbb);
    transform: scale(1.1) rotate(2deg);
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
}

.seat.selected {
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-color: #5a6fd8;
    color: white;
    transform: scale(1.1);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    animation: seatSelected 0.3s ease;
}

.seat.occupied {
    background: linear-gradient(135deg, #f8d7da, #f5c6cb);
    border-color: #dc3545;
    color: #721c24;
    cursor: not-allowed;
    opacity: 0.7;
}
.seat.reserved {
    background: linear-gradient(135deg, #f8d7da, #f5c6cb);
    border-color: #dc3545;
    color: #721c24;
    cursor: not-allowed;
    opacity: 0.7;
}

@keyframes seatSelected {
    0% { transform: scale(1); }
    50% { transform: scale(1.2) rotate(5deg); }
    100% { transform: scale(1.1); }
}

/* Bilgi Paneli */
.seat-info-panel {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-bottom: 24px;
}

.categories-section,
.legend-section {
    background: white;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid #e8ecef;
}

.categories-section h4,
.legend-section h4 {
    margin: 0 0 16px 0;
    color: #2c3e50;
    font-size: 16px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.categories-grid,
.legend-grid {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.modern-category-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: #f8f9fa;
    border-radius: 12px;
    transition: all 0.3s ease;
}

.modern-category-item:hover {
    background: #e9ecef;
    transform: translateX(4px);
}

.category-indicator {
    width: 24px;
    height: 24px;
    border-radius: 6px;
    border: 2px solid rgba(0, 0, 0, 0.1);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.category-details {
    flex: 1;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.category-name {
    font-weight: 600;
    color: #495057;
}

.category-price {
    font-weight: 700;
    color: #667eea;
    font-size: 16px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 14px;
    color: #495057;
}

.seat-sample {
    width: 24px;
    height: 24px;
    border-radius: 6px;
    border: 2px solid;
    flex-shrink: 0;
}

.seat-sample.available {
    background: linear-gradient(135deg, #d4edda, #c3e6cb);
    border-color: #28a745;
}

.seat-sample.selected {
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-color: #5a6fd8;
}

.seat-sample.occupied {
    background: linear-gradient(135deg, #f8d7da, #f5c6cb);
    border-color: #dc3545;
}

/* Seçilen Koltuklar Paneli */
.selected-seats-panel {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border-radius: 20px;
    padding: 24px;
    box-shadow: 0 8px 30px rgba(102, 126, 234, 0.3);
    animation: slideInUp 0.3s ease;
}

@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

.panel-header h4 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.seat-count {
    background: rgba(255, 255, 255, 0.2);
    padding: 6px 12px;
    border-radius: 20px;
    font-weight: 700;
    font-size: 14px;
}

.selected-seats-list {
    margin-bottom: 20px;
}

.selected-seat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}

.selected-seat-item:last-child {
    border-bottom: none;
}

.seat-info {
    font-weight: 600;
}

.seat-price {
    font-weight: 700;
    font-size: 16px;
}

.total-section {
    border-top: 2px solid rgba(255, 255, 255, 0.3);
    padding-top: 16px;
}

.total-price {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.total-label {
    font-size: 18px;
    font-weight: 600;
}

.total-amount {
    font-size: 24px;
    font-weight: 800;
    color: #fff;
}

/* Override: Toplam alanını beyaz kart ve siyah yazı yap */
.selected-seats-panel .total-price {
    background: #fff;
    border-radius: 12px;
    padding: 12px 16px;
}
.selected-seats-panel .total-price .total-label,
.selected-seats-panel .total-price .total-amount {
    color: #000;
}
/* İsterseniz tek tek koltuk satır fiyatlarını da siyah yapabiliriz:
.selected-seats-panel .selected-seat-item .seat-price { color: #000; }
*/

/* Modal Footer */
.modern-modal-footer {
    background: #f8f9fa;
    padding: 24px 32px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 1px solid #e8ecef;
}

.btn-cancel {
    background: #6c757d;
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-cancel:hover {
    background: #5a6268;
    transform: translateY(-2px);
}

.btn-confirm {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    position: relative;
    overflow: hidden;
}

.btn-confirm:hover:not(:disabled) {
    background: linear-gradient(135deg, #218838, #1ea085);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(40, 167, 69, 0.3);
}

.btn-confirm:disabled {
    background: #6c757d;
    cursor: not-allowed;
    opacity: 0.6;
}

.btn-badge {
    background: rgba(255, 255, 255, 0.3);
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 700;
    min-width: 20px;
    text-align: center;
}

/* Mobil Uyumluluk */
@media (max-width: 768px) {
    .modern-modal-content {
        width: 100%;
        height: 100%;
        max-height: 100vh;
        margin: 0;
        border-radius: 0;
    }
    
    .modern-modal-header {
        padding: 20px 16px;
    }
    
    .modal-title {
        font-size: 20px;
    }
    
    .modern-modal-body {
        padding: 16px;
    }
    
    .seat-info-panel {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    
    .seat {
        width: 32px;
        height: 32px;
        font-size: 11px;
    }
    
    .row-label {
        width: 32px;
        margin-right: 8px;
        font-size: 12px;
    }
    
    .modern-modal-footer {
        padding: 16px;
        flex-direction: column;
        gap: 12px;
    }
    
    .btn-cancel,
    .btn-confirm {
        width: 100%;
        justify-content: center;
    }
    
    .seats-wrapper {
        max-height: 300px;
        padding: 12px;
    }
    
    .modern-stage-indicator {
        padding: 12px 16px;
        margin-bottom: 20px;
    }
    
    .stage-text {
        font-size: 16px;
    }
}

@media (max-width: 480px) {
    .seat {
        width: 28px;
        height: 28px;
        font-size: 10px;
        margin: 0 1px;
    }
    
    .row-label {
        width: 28px;
        font-size: 11px;
    }
    
    .seat-row {
        gap: 2px;
    }
}

@media (max-width: 768px) {
    .quantity-controls {
        max-width: 130px;
    }
    
    .quantity-btn {
        width: 35px;
        height: 35px;
        font-size: 14px;
    }
    
    .quantity-input {
        width: 50px;
        height: 35px;
        font-size: 14px;
    }
    
    .seating-selection-container {
        grid-template-columns: 1fr;
    }
    
    .seat {
        width: 28px;
        height: 28px;
        font-size: 0.7rem;
    }
    
    .seat-legend {
        gap: 1rem;
    }
}
</style>

<script>
// Global değişkenler
let selectedTicketData = null;
let currentQuantity = 1;

// Koltuk seçimi için değişkenler
let selectedSeats = [];
let seatCategories = <?php echo json_encode($seatCategories); ?>;
let seats = <?php echo json_encode($seats); ?>;
let isSeatedEvent = <?php echo json_encode($event['seating_type'] === 'seated' || $event['seating_type'] === 'reservation'); ?>;
    let isReservationEvent = <?php echo json_encode($event['seating_type'] === 'reservation'); ?>;

// Debug bilgisi ekle
console.log('PHP Data loaded:');
console.log('seatCategories:', seatCategories);
console.log('seats:', seats);
console.log('isSeatedEvent:', isSeatedEvent);

// Ek debug - veri tiplerini kontrol et
console.log('seats type:', typeof seats);
console.log('seats is array:', Array.isArray(seats));
console.log('seats length:', seats ? seats.length : 'undefined');

// Her koltuk için detaylı bilgi
if (seats && seats.length > 0) {
    console.log('İlk 3 koltuk:', seats.slice(0, 3));
    seats.forEach((seat, index) => {
        if (index < 3) {
            console.log(`Koltuk ${index + 1}:`, {
                id: seat.id,
                row: seat.row_number,
                seat: seat.seat_number,
                category: seat.category_name,
                color: seat.category_color,
                price: seat.price
            });
        }
    });
}

// Açıklama göster/gizle fonksiyonu
function toggleDescription() {
    const shortDesc = document.querySelector('.description-short');
    const fullDesc = document.querySelector('.description-full');
    const readMoreBtn = document.querySelector('.btn-read-more');
    
    if (fullDesc.style.display === 'none') {
        shortDesc.style.display = 'none';
        fullDesc.style.display = 'block';
        readMoreBtn.innerHTML = 'Daha az göster <i class="fas fa-chevron-up"></i>';
    } else {
        shortDesc.style.display = 'block';
        fullDesc.style.display = 'none';
        readMoreBtn.innerHTML = 'Devamını oku <i class="fas fa-chevron-down"></i>';
    }
}

// Modal fonksiyonları
function openTicketModal() {
    // Giriş kontrolü
    <?php if (!isLoggedIn()): ?>
        // Login modalını aç
        openModal('loginModal');
        return;
    <?php endif; ?>

    if (isSeatedEvent) {
        openSeatSelectionModal();
        return;
    }
    
    document.getElementById('ticketModal').style.display = 'block';
    
    // İlk sayfayı (kurallar) göster
    document.getElementById('rulesPage').classList.add('active');
    document.getElementById('ticketSelectionPage').classList.remove('active');
    
    resetTicketSelection();
}

function resetTicketSelection() {
    // Tüm bilet kartlarının seçimini kaldır
    document.querySelectorAll('.ticket-type-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    // Seçim alanlarını gizle
    const selectedInfo = document.querySelector('.selected-ticket-info');
    const quantitySelector = document.querySelector('.quantity-selector');
    const totalPrice = document.querySelector('.total-price');
    
    if (selectedInfo) selectedInfo.style.display = 'none';
    if (quantitySelector) quantitySelector.style.display = 'none';
    if (totalPrice) totalPrice.style.display = 'none';
    
    // Sepete ekle butonunu deaktif et
    const addToCartBtn = document.getElementById('addToCartBtn');
    if (addToCartBtn) addToCartBtn.disabled = true;
    
    selectedTicketData = null;
    currentQuantity = 1;
    
    // Quantity input elementini sıfırla
    const quantityInput = document.getElementById('eventTicketQuantity');
    if (quantityInput) {
        quantityInput.value = 1;
    }
}

function selectTicketType(card) {
    // Önceki seçimi kaldır
    document.querySelectorAll('.ticket-type-card').forEach(c => {
        c.classList.remove('selected');
    });
    
    // Yeni seçimi işaretle
    card.classList.add('selected');
    
    // Bilet verilerini al
    selectedTicketData = {
        id: card.dataset.ticketId,
        name: card.dataset.ticketName,
        price: parseInt(card.dataset.ticketPrice),
        description: card.dataset.ticketDescription || '',
        maxPerOrder: parseInt(card.dataset.maxPerOrder) || 10
    };
    
    // Seçim bilgilerini göster
    document.getElementById('eventSelectedTicketName').textContent = selectedTicketData.name;
    document.getElementById('eventSelectedTicketPrice').textContent = '₺' + selectedTicketData.price.toLocaleString('tr-TR');
    document.getElementById('eventSelectedTicketDescription').textContent = selectedTicketData.description || '';
    
    // Alanları göster
    document.querySelector('.selected-ticket-info').style.display = 'block';
    document.querySelector('.quantity-selector').style.display = 'block';
    document.querySelector('.total-price').style.display = 'block';
    
    // Miktarı sıfırla
    currentQuantity = 1;
    const quantityInput = document.getElementById('eventTicketQuantity');
    if (quantityInput) {
        quantityInput.max = selectedTicketData.maxPerOrder;
        quantityInput.value = currentQuantity;
    }
    
    // Toplam fiyatı güncelle
    updateTotalPrice();
    
    // Sepete ekle butonunu aktif et
    document.getElementById('addToCartBtn').disabled = false;
}

function closeTicketModal() {
    document.getElementById('ticketModal').style.display = 'none';
    
    // Sayfaları sıfırla
    document.getElementById('rulesPage').classList.add('active');
    document.getElementById('ticketSelectionPage').classList.remove('active');
    
    resetTicketSelection();
}

function showRulesPage() {
    document.getElementById('ticketSelectionPage').classList.remove('active');
    document.getElementById('rulesPage').classList.add('active');
    
    // Bilet seçimini sıfırla
    resetTicketSelection();
}

function showTicketSelection() {
    document.getElementById('rulesPage').classList.remove('active');
    document.getElementById('ticketSelectionPage').classList.add('active');
}

function increaseQuantity() {
    const maxAllowed = selectedTicketData ? selectedTicketData.maxPerOrder : 10;
    if (currentQuantity < maxAllowed) {
        currentQuantity++;
        const quantityInput = document.getElementById('eventTicketQuantity');
        if (quantityInput) {
            quantityInput.value = currentQuantity;
            
            // Animasyon efekti
            quantityInput.style.transform = 'scale(1.1)';
            quantityInput.style.background = '#e8f5e8';
            
            setTimeout(() => {
                quantityInput.style.transform = 'scale(1)';
                quantityInput.style.background = 'white';
            }, 200);
        }
        updateTotalPrice();
    }
}

// Koltuk seçim modalını aç
function openSeatSelectionModal() {
    // Giriş kontrolü
    <?php if (!isLoggedIn()): ?>
        // Login modalını aç
        openModal('loginModal');
        return;
    <?php endif; ?>

    console.log('=== KOLTUK MODAL AÇILIYOR ===');
    console.log('isSeatedEvent:', isSeatedEvent);
    console.log('seats array:', seats);
    console.log('seatCategories array:', seatCategories);
    
    if (!isSeatedEvent) {
        console.log('Etkinlik koltuklu değil, modal açılmıyor');
        return;
    }
    
    const modal = document.getElementById('seatSelectionModal');
    if (!modal) {
        console.error('seatSelectionModal element not found!');
        return;
    }
    
    // Modal animasyonu için class ekle
    modal.style.display = 'block';
    setTimeout(() => {
        modal.classList.add('active');
    }, 10);
    
    // Body scroll'unu engelle
    document.body.style.overflow = 'hidden';
    
    // Seçimleri sıfırla
    resetSeatSelection();

    // MODAL AÇILIR AÇILMAZ GÜNCEL KOLTUKLARI SUNUCUDAN ÇEK
    fetch(`ajax/seats.php?action=get&event_id=<?php echo $eventId; ?>`)
        .then(res => res.json())
        .then(data => {
            if (data.success && Array.isArray(data.seats)) {
                seats = data.seats; // Global seats dizisini güncelle
                console.log('Güncel koltuklar yüklendi:', seats);
            } else {
                console.warn('Koltuklar yüklenemedi, eski veriler kullanılacak:', data.message);
            }
        })
        .catch(err => {
            console.error('Koltuklar yüklenirken hata:', err);
        })
        .finally(() => {
            // Güncel (veya eldeki) seats ile haritayı oluştur
            generateSeatMap();
            console.log('Modal açıldı ve koltuk haritası oluşturuldu (güncel seats ile)');
        });
}

// Koltuk modalını kapat
function closeSeatModal() {
    const modal = document.getElementById('seatSelectionModal');
    if (!modal) return;
    
    // Animasyon ile kapat
    modal.classList.remove('active');
    setTimeout(() => {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }, 300);
    
    resetSeatSelection();
}

// Koltuk seçimini sıfırlama
function resetSeatSelection() {
    selectedSeats = [];
    document.querySelectorAll('.seat.selected').forEach(seat => {
        seat.classList.remove('selected');
    });
    updateSelectedSeatsDisplay();
}

// Koltuk haritasını oluştur - geliştirilmiş versiyon
function generateSeatMap() {
    console.log('=== KOLTUK HARİTASI DEBUG ===');
    console.log('generateSeatMap çağrıldı');
    console.log('isSeatedEvent:', isSeatedEvent);
    console.log('seats verisi:', seats);
    console.log('seats length:', seats ? seats.length : 'undefined');
    console.log('seatCategories verisi:', seatCategories);
    console.log('seatCategories length:', seatCategories ? seatCategories.length : 'undefined');
    
    if (!isSeatedEvent) {
        console.log('Bu etkinlik koltuklu değil - seating_type:', '<?php echo $event["seating_type"] ?? "undefined"; ?>');
        return;
    }
    
    // Yalnızca modern koltuk modalı içindeki container'ı hedefle
    const modal = document.getElementById('seatSelectionModal');
    const container = modal ? modal.querySelector('#seatsContainer') : document.getElementById('seatsContainer');
    if (!container) {
        console.error('seatsContainer bulunamadı!');
        return;
    }
    
    console.log('seatsContainer bulundu:', container);
    
    // Geliştirilmiş hata mesajı
    if (!seats || seats.length === 0) {
        container.innerHTML = `
            <div style="text-align: center; padding: 40px; color: #666;">
                <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #ffc107;"></i>
                <h3>Koltuk Bilgileri Yüklenemedi</h3>
                <p>Etkinlik ID: <?php echo $eventId; ?><br>
                Seating Type: <?php echo $event['seating_type'] ?? 'undefined'; ?><br>
                Koltuk Sayısı: ${seats ? seats.length : 'undefined'}</p>
                <button onclick="location.reload()" class="btn-primary">Sayfayı Yenile</button>
            </div>
        `;
        return;
    }
    
    console.log('Koltuk haritası oluşturuluyor...', seats.length, 'koltuk');
    
    container.innerHTML = '';
    
    // Koltukları satır ve sütuna göre grupla
    const seatsByRow = {};
    seats.forEach(seat => {
        const rowNum = seat.row_number || 1;
        if (!seatsByRow[rowNum]) {
            seatsByRow[rowNum] = [];
        }
        seatsByRow[rowNum].push(seat);
    });
    
    console.log('Satırlara göre gruplandı:', seatsByRow);
    
    // Her satır için HTML oluştur
    Object.keys(seatsByRow).sort((a, b) => parseInt(a) - parseInt(b)).forEach(rowNumber => {
        const rowDiv = document.createElement('div');
        rowDiv.className = 'seat-row';
        
        // Satır etiketi
        const rowLabel = document.createElement('div');
        rowLabel.className = 'row-label';
        rowLabel.textContent = String.fromCharCode(64 + parseInt(rowNumber)); // A, B, C...
        rowDiv.appendChild(rowLabel);
        
        // Koltukları sırala ve ekle
        seatsByRow[rowNumber].sort((a, b) => parseInt(a.seat_number || 0) - parseInt(b.seat_number || 0)).forEach(seat => {
            const seatDiv = document.createElement('div');
            const seatStatus = seat.status || 'available';
            seatDiv.className = `seat ${seatStatus}`;
            seatDiv.textContent = seat.seat_number || '?';
            seatDiv.dataset.seatId = seat.id;
            seatDiv.dataset.categoryId = seat.category_id;
            seatDiv.dataset.price = seat.price || 0;
            
            // Kategori rengini uygula
            if (seat.category_color) {
                seatDiv.style.borderColor = seat.category_color;
                if (seatStatus === 'available') {
                    seatDiv.style.background = `linear-gradient(135deg, ${seat.category_color}22, ${seat.category_color}44)`;
                }
            }
            
            // Koltuk seçim event listener
            if (seatStatus === 'available') {
                seatDiv.addEventListener('click', () => {
                    console.log('Koltuk tıklandı:', seat);
                    toggleSeatSelection(seat, seatDiv);
                });
                seatDiv.style.cursor = 'pointer';
            } else {
                seatDiv.style.cursor = 'not-allowed';
                seatDiv.style.opacity = '0.5';
            }
            
            rowDiv.appendChild(seatDiv);
        });
        
        container.appendChild(rowDiv);
    });
    
    console.log('Koltuk haritası başarıyla oluşturuldu!');
}

// Koltuk seçimini değiştir
function toggleSeatSelection(seat, seatElement) {
    const seatIndex = selectedSeats.findIndex(s => s.id === seat.id);
    
    if (seatIndex > -1) {
        selectedSeats.splice(seatIndex, 1);
        seatElement.classList.remove('selected');
    } else {
        if (selectedSeats.length >= 10) {
            alert('En fazla 10 koltuk seçebilirsiniz!');
            return;
        }
        selectedSeats.push(seat);
        seatElement.classList.add('selected');
    }
    
    updateSelectedSeatsDisplay();
}

// Seçilen koltukları göster
function updateSelectedSeatsDisplay() {
    const panel = document.getElementById('selectedSeatsPanel');
    const list = panel ? panel.querySelector('#selectedSeatsList') : document.getElementById('selectedSeatsList');
    const totalPriceEl = panel ? panel.querySelector('#seatTotalPrice') : document.getElementById('seatTotalPrice');
    const addButton = document.getElementById('addSeatsToCartBtn');
    const seatCount = document.getElementById('selectedSeatCount');
    const confirmBadge = document.getElementById('confirmBadge');
    
    if (selectedSeats.length === 0) {
        if (panel) panel.style.display = 'none';
        if (addButton) addButton.disabled = true;
        return;
    }
    
    if (panel) panel.style.display = 'block';
    if (addButton) addButton.disabled = false;
    
    // Koltuk sayısı
    if (seatCount) seatCount.textContent = selectedSeats.length;
    if (confirmBadge) confirmBadge.textContent = selectedSeats.length;
    
    // Seçilen koltukları listele ve toplamı hesapla
    if (list) {
        list.innerHTML = '';
        let total = 0;
        
        selectedSeats.forEach(seat => {
            // Rezervasyon sistemi kontrolü
            const isReservation = <?php echo json_encode($event['seating_type'] === 'reservation'); ?>;
            
            // Rezervasyon sistemi için özel gösterim
            let price = 0;
            let priceDisplay = '';
            
            if (isReservation) {
                priceDisplay = 'Rezervasyon';
            } else {
                if (seat.price !== undefined && seat.price !== null && !isNaN(Number(seat.price))) {
                    price = Number(seat.price);
                } else {
                    const cat = seatCategories.find(c => 
                        (c.id && seat.category_id && Number(c.id) === Number(seat.category_id)) ||
                        (c.name && seat.category_name && c.name.toLowerCase() === String(seat.category_name).toLowerCase())
                    );
                    price = Number((cat && cat.price) || 0);
                }
                priceDisplay = `₺${price.toLocaleString('tr-TR')}`;
                total += price;
            }
            
            const item = document.createElement('div');
            item.className = 'selected-seat-item';
            item.innerHTML = `
                <div class="seat-info">
                    <i class="fas fa-chair"></i>
                    ${String.fromCharCode(64 + parseInt(seat.row_number))}${seat.seat_number} - ${seat.category_name}
                </div>
                <div class="seat-price">${priceDisplay}</div>
            `;
            list.appendChild(item);
        });
        
        if (totalPriceEl) {
            const isReservation = <?php echo json_encode($event['seating_type'] === 'reservation'); ?>;
            if (isReservation) {
                totalPriceEl.textContent = 'Rezervasyon';
            } else {
                totalPriceEl.textContent = `₺${total.toLocaleString('tr-TR')}`;
            }
        }
    }
}

// Koltukları sepete ekle
function addSeatsToCart() {
    if (selectedSeats.length === 0) {
        alert('Lütfen en az bir koltuk seçin!');
        return;
    }
    
    // Giriş kontrolü
    <?php if (!isLoggedIn()): ?>
        // Login modalını aç
        openModal('loginModal');
        return;
    <?php endif; ?>
    
    // Her koltuk için sepete ekleme işlemi
    const promises = selectedSeats.map(seat => {
        // Fiyat seçiminde seat.price öncelikli, yoksa kategori fiyatı
        let price = 0;
        if (seat.price != null && !isNaN(Number(seat.price))) {
            price = Number(seat.price);
        } else {
            const category = seatCategories.find(c => 
                (c.id && seat.category_id && Number(c.id) === Number(seat.category_id)) ||
                (c.name && seat.category_name && c.name.toLowerCase() === String(seat.category_name).toLowerCase())
            );
            price = Number((category && category.price) || 0);
        }
        
        const formData = new FormData();
        formData.append('action', 'add_seat');
        formData.append('event_id', <?php echo $eventId; ?>);
        formData.append('seat_id', seat.id);
        formData.append('event_name', '<?php echo addslashes($event['title']); ?>');
        formData.append('seat_info', `${String.fromCharCode(64 + parseInt(seat.row_number))}${seat.seat_number} - ${seat.category_name}`);
        formData.append('price', price);
        formData.append('quantity', 1);
        
        return fetch('ajax/cart.php', {
            method: 'POST',
            body: formData
        }).then(response => response.json());
    });
    
    Promise.all(promises)
    .then(results => {
        const successes = results.filter(r => r && r.success);
        const successCount = successes.length;
        const allSuccess = successCount === results.length;
        
        if (allSuccess) {
            const addedCount = selectedSeats.length;
            closeSeatModal();
            showSuccessMessage(`${addedCount} koltuk sepete eklendi! 🎫`);
            updateCartCount();
            setTimeout(() => { window.location.href = 'sepet.php'; }, 1500);
        } else if (successCount > 0) {
            // Bazıları eklendi, bazıları artık müsait değil: sessizce bilgi ver ve devam et
            const addedCount = successCount;
            closeSeatModal();
            showSuccessMessage(`${addedCount} koltuk sepete eklendi. Bazı koltuklar artık müsait değil.`);
            updateCartCount();
            setTimeout(() => { window.location.href = 'sepet.php'; }, 1500);
        } else {
            // Hiçbiri eklenemediyse alert yerine haritayı sessizce güncelle
            fetch(`ajax/seats.php?action=get&event_id=<?php echo $eventId; ?>`)
                .then(res => res.json())
                .then(data => {
                    if (data.success && Array.isArray(data.seats)) {
                        seats = data.seats;
                        generateSeatMap();
                        // İsteğe bağlı kısa bilgi mesajı (alert yok)
                        showSuccessMessage('Seçtiğiniz koltuklar artık müsait değil. Harita güncellendi.');
                    }
                })
                .catch(() => { /* sessiz geç */ });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Bir hata oluştu. Lütfen tekrar deneyin.');
    });
}

// Rezervasyon talebi gönder
function submitReservation() {
    if (selectedSeats.length === 0) {
        alert('Lütfen en az bir koltuk seçin!');
        return;
    }
    
    // Giriş kontrolü
    <?php if (!isLoggedIn()): ?>
        // Login modalını aç
        openModal('loginModal');
        return;
    <?php endif; ?>
    
    const seatData = selectedSeats.map(seat => ({
        seat_id: seat.id,
        category_id: seat.category_id,
        row_number: seat.row_number,
        seat_number: seat.seat_number,
        category_name: seat.category_name
    }));

    fetch('ajax/reservation.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'create_reservation',
            event_id: <?php echo $eventId; ?>,
            seats: seatData
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeSeatModal();
            showSuccessMessage('Rezervasyon talebiniz başarıyla gönderildi! Organizatör sizinle iletişime geçecektir. 📞');
            // Koltuk haritasını güncelle
            setTimeout(() => {
                fetch(`ajax/seats.php?action=get&event_id=<?php echo $eventId; ?>`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.success && Array.isArray(data.seats)) {
                            seats = data.seats;
                            generateSeatMap();
                        }
                    })
                    .catch(() => { /* sessiz geç */ });
            }, 1000);
        } else {
            alert('Hata: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Bir hata oluştu. Lütfen tekrar deneyin.');
    });
}

function decreaseQuantity() {
    if (currentQuantity > 1) {
        currentQuantity--;
        const quantityInput = document.getElementById('eventTicketQuantity');
        if (quantityInput) {
            quantityInput.value = currentQuantity;
            
            // Animasyon efekti
            quantityInput.style.transform = 'scale(1.1)';
            quantityInput.style.background = '#ffe8e8';
            
            setTimeout(() => {
                quantityInput.style.transform = 'scale(1)';
                quantityInput.style.background = 'white';
            }, 200);
        }
        updateTotalPrice();
    }
}

function updateTotalPrice() {
    if (selectedTicketData && selectedTicketData.price) {
        const total = selectedTicketData.price * currentQuantity;
        document.getElementById('eventTotalPrice').textContent = '₺' + total.toLocaleString('tr-TR');
    }
}

function addToCart() {
    // Rezervasyon sistemi için özel işlem
    if (isReservationEvent) {
        submitReservation();
        return;
    }
    
    // Koltuklu sistem için özel işlem
    if (isSeatedEvent) {
        addSeatsToCart();
        return;
    }
    
    // Giriş kontrolü
    <?php if (!isLoggedIn()): ?>
        // Login modalını aç
        openModal('loginModal');
        return;
    <?php endif; ?>
    
    if (!selectedTicketData) {
        alert('Lütfen bir bilet türü seçin!');
        return;
    }
    
    // NEW: Seçilen bilet verisini kopyala (modal kapatıldığında sıfırlanmasın)
    const ticketData = { ...selectedTicketData };

    // Veritabanına sepet öğesi ekle
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('event_id', <?php echo $eventId; ?>);
    formData.append('ticket_type_id', ticketData.id);
    formData.append('event_name', '<?php echo addslashes($event['title']); ?>');
    formData.append('ticket_name', ticketData.name);
    formData.append('price', ticketData.price);
    formData.append('quantity', currentQuantity);
    
    fetch('ajax/cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Modal'ı kapatmadan önce veriyi kopyaladığımız için artık güvenli
            closeTicketModal();
            
            // Başarı mesajı göster (fallback ile)
            showSuccessMessage(`${ticketData.name || 'Bilet'} sepete eklendi! 🎫`);
            
            // Sepet sayısını güncelle
            updateCartCount();
            
            // 1.5 saniye sonra sepet sayfasına yönlendir
            setTimeout(() => {
                window.location.href = 'sepet.php';
            }, 1500);
        } else {
            alert('Hata: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Bir hata oluştu. Lütfen tekrar deneyin.');
    });
}

// Başarı mesajı gösterme fonksiyonu
function showSuccessMessage(message) {
    // Mevcut mesajları kaldır
    const existingMessages = document.querySelectorAll('.success-message');
    existingMessages.forEach(msg => msg.remove());
    
    const messageDiv = document.createElement('div');
    messageDiv.className = 'success-message';
    messageDiv.innerHTML = `
        <div class="success-content">
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="success-close">×</button>
        </div>
    `;
    
    // CSS stilleri
    const style = document.createElement('style');
    style.textContent = `
        .success-message {
            position: fixed;
            top: 2rem;
            right: 2rem;
            z-index: 10000;
            background: linear-gradient(135deg, #00C896, #00a085);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            box-shadow: 0 4px 20px rgba(0, 200, 150, 0.3);
            animation: slideInSuccess 0.3s ease-out;
            max-width: 400px;
        }
        
        .success-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }
        
        .success-close {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0;
            line-height: 1;
        }
        
        @keyframes slideInSuccess {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    `;
    
    document.head.appendChild(style);
    document.body.appendChild(messageDiv);
    
    // 4 saniye sonra otomatik kaldır
    setTimeout(() => {
        if (messageDiv.parentElement) {
            messageDiv.remove();
        }
    }, 4000);
}

// Sepet sayısını güncelleme fonksiyonu
function updateCartCount() {
    const cart = JSON.parse(localStorage.getItem('cart') || '[]');
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    
    // Header'daki sepet sayısını güncelle (eğer varsa)
    const cartCountElements = document.querySelectorAll('.cart-count, .sepet-count');
    cartCountElements.forEach(element => {
        element.textContent = totalItems;
        element.style.display = totalItems > 0 ? 'block' : 'none';
    });
}

// Harita göster/gizle fonksiyonu
function toggleMap() {
    const mapContainer = document.getElementById('mapContainer');
    const mapButton = document.querySelector('.btn-map');
    
    if (mapContainer.style.display === 'none') {
        mapContainer.style.display = 'block';
        mapButton.classList.add('active');
        mapButton.innerHTML = '❌ Haritayı Gizle';
        
        // Haritayı yükle
        loadMap();
    } else {
        mapContainer.style.display = 'none';
        mapButton.classList.remove('active');
        mapButton.innerHTML = '📍 Haritada Göster';
    }
}

// Google Maps haritasını yükle
function loadMap() {
    const venueAddress = '<?php echo htmlspecialchars($event['venue_address'] ?? $event['venue_name'] . ', ' . $event['city']); ?>';
    const mapDiv = document.getElementById('venueMap');
    const loadingDiv = document.getElementById('mapLoading');
    
    // Yükleme göstergesi
    if (loadingDiv) {
        loadingDiv.style.display = 'flex';
    }
    
    // OpenStreetMap embed haritası
    const mapQuery = encodeURIComponent(venueAddress);
    
    setTimeout(() => {
        mapDiv.innerHTML = `
            <iframe 
                src="https://www.openstreetmap.org/export/embed.html?bbox=28.8,40.9,29.2,41.2&layer=mapnik&marker=41.05,29.0" 
                width="100%" 
                height="350" 
                style="border: none;"
                allowfullscreen="" 
                loading="lazy" 
                referrerpolicy="no-referrer-when-downgrade"
                onload="hideMapLoading()">
            </iframe>
        `;
    }, 500);
}

function hideMapLoading() {
    const loadingDiv = document.getElementById('mapLoading');
    if (loadingDiv) {
        loadingDiv.style.display = 'none';
    }
}

// Konum paylaşma fonksiyonu
function shareLocation() {
    const venueAddress = '<?php echo htmlspecialchars($event['venue_name'] . ', ' . $event['city']); ?>';
    const shareUrl = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(venueAddress)}`;
    
    if (navigator.share) {
        navigator.share({
            title: '<?php echo htmlspecialchars($event['venue_name']); ?>',
            text: 'Etkinlik konumu: ' + venueAddress,
            url: shareUrl
        });
    } else {
        // Fallback: URL'yi panoya kopyala
        navigator.clipboard.writeText(shareUrl).then(() => {
            alert('Konum linki panoya kopyalandı!');
        }).catch(() => {
            // Fallback: Yeni pencerede aç
            window.open(shareUrl, '_blank');
        });
    }
}

// Bilet satın alma fonksiyonu
document.addEventListener('DOMContentLoaded', function() {
    // Satın al butonuna event listener ekle
    document.querySelectorAll('.btn-buy-ticket').forEach(button => {
        button.addEventListener('click', function() {
            <?php if (isLoggedIn()): ?>
            openTicketModal();
            <?php else: ?>
            showLoginAlert();
            <?php endif; ?>
        });
    });
    
    // Bilet türü kartlarına event listener ekle
    document.querySelectorAll('.ticket-type-card').forEach(card => {
        card.addEventListener('click', function() {
            selectTicketType(this);
        });
    });
    
    // Modal dışına tıklayınca kapat
    window.onclick = function(event) {
        const ticketModal = document.getElementById('ticketModal');
        const seatModal = document.getElementById('seatSelectionModal');
        
        if (event.target === ticketModal) {
            closeTicketModal();
        }
        if (event.target === seatModal || event.target.classList.contains('modal-backdrop')) {
            closeSeatModal();
        }
    }
    
    // Sayfa başlığını güncelle
    document.title = '<?php echo htmlspecialchars($event['title']); ?> - BiletJack';
    
    // Geri butonu işlevi
    if (document.querySelector('.btn-back')) {
        document.querySelector('.btn-back').addEventListener('click', function() {
            window.history.back();
        });
    }
    
    // Koltuklu etkinlik için özel buton işlevi
    const buyButton = document.querySelector('.btn-buy-ticket');
    if (buyButton && isSeatedEvent) {
        buyButton.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Seat selection button clicked');
            openSeatSelectionModal();
        });
    }
});

// Organizatör takip etme fonksiyonu
function followOrganizer(organizerId) {
    const button = event.target.closest('.btn-follow');
    const originalText = button.innerHTML;
    
    // Buton durumunu geçici olarak değiştir
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> İşleniyor...';
    button.disabled = true;
    
    fetch('ajax/follow_organizer.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            organizer_id: organizerId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            button.innerHTML = data.button_text;
            if (data.action === 'followed') {
                button.classList.add('following');
            } else {
                button.classList.remove('following');
            }
            
            // Başarı mesajı göster
            showNotification(data.message, 'success');
        } else {
            button.innerHTML = originalText;
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        button.innerHTML = originalText;
        showNotification('Bir hata oluştu', 'error');
    })
    .finally(() => {
        button.disabled = false;
    });
}

// Bildirim gösterme fonksiyonu
function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 5px;
        color: white;
        font-weight: bold;
        z-index: 10000;
        animation: slideIn 0.3s ease;
        ${type === 'success' ? 'background-color: #28a745;' : 'background-color: #dc3545;'}
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Organizatör profil sayfasını aç
function openOrganizerProfile(organizerId) {
    window.open('organizer-profile.php?id=' + organizerId, '_blank');
}

// Escape tuşu ile modalları kapat
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        // Ticket modal açıksa kapat
        const ticketModal = document.getElementById('ticketModal');
        if (ticketModal && ticketModal.style.display !== 'none') {
            closeTicketModal();
        }
        
        // Koltuk modalı açıksa kapat
        const seatModal = document.getElementById('seatSelectionModal');
        if (seatModal && seatModal.classList.contains('active')) {
            closeSeatModal();
        }

        // Login/Register modalları için de kapatma (header.js içindeki fonksiyon)
        if (typeof closeModal === 'function') {
            closeModal('loginModal');
            closeModal('registerModal');
        }
    }
});
</script>

<style>
/* Organizatör Kartı Stilleri */
.organizer-card {
    position: relative;
    overflow: hidden;
    min-height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    text-align: center;
}

.organizer-info {
    position: relative;
    z-index: 2;
    width: 100%;
}

.organizer-profile {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
}

.organizer-avatar {
    flex-shrink: 0;
}

.organizer-profile-img {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid rgba(255, 255, 255, 0.9);
    box-shadow: 0 4px 15px rgba(0,0,0,0.3);
}

.organizer-avatar-placeholder {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 32px;
    border: 4px solid rgba(255, 255, 255, 0.9);
    box-shadow: 0 4px 15px rgba(0,0,0,0.3);
}

.organizer-details {
    text-align: center;
}

.organizer-name {
    margin: 0 0 8px 0;
    font-size: 22px;
    font-weight: 700;
    color: white;
    cursor: pointer;
    transition: all 0.3s ease;
    text-shadow: 0 2px 4px rgba(0,0,0,0.5);
}

.organizer-name:hover {
    color:rgb(204, 194, 152);
    transform: translateY(-1px);
}

.organizer-description {
    margin: 0 0 8px 0;
    color: rgba(255, 255, 255, 0.9);
    font-size: 14px;
    line-height: 1.4;
    text-shadow: 0 1px 2px rgba(0,0,0,0.5);
}

.organizer-location {
    margin: 0;
    color: rgba(255, 255, 255, 0.8);
    font-size: 13px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    text-shadow: 0 1px 2px rgba(0,0,0,0.5);
}

.organizer-actions {
    display: flex;
    gap: 12px;
    justify-content: center;
    flex-wrap: wrap;
}

.btn-follow, .btn-profile {
    padding: 10px 20px;
    border: none;
    border-radius: 25px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    backdrop-filter: blur(10px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.btn-follow {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    border: 2px solid rgba(255, 255, 255, 0.3);
}

.btn-follow:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.3);
}

.btn-follow.following {
    background: rgba(39, 174, 96, 0.8);
    border-color: rgba(39, 174, 96, 0.9);
}

.btn-profile {
    background: rgba(255, 255, 255, 0.9);
    color: #2c3e50;
    border: 2px solid rgba(255, 255, 255, 0.9);
}

.btn-profile:hover {
    background: white;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.3);
}

/* Kapak resmi olmayan kartlar için varsayılan gradient */
.organizer-card:not([style*="background-image"]) {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

@media (max-width: 768px) {
    .organizer-profile {
        gap: 10px;
    }
    
    .organizer-name {
        font-size: 20px;
    }
    
    .organizer-actions {
        width: 100%;
    }
    
    .btn-follow, .btn-profile {
        flex: 1;
        justify-content: center;
        min-width: 120px;
    }
    
    .artists-list {
        text-align: left;
    }
}
</style>

<script>
function showLoginAlert() {
    // Uyarı mesajı oluştur
    const alertDiv = document.createElement('div');
    alertDiv.innerHTML = 'Lütfen önce bir müşteri hesabına giriş yapın';
    alertDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #ff4757;
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 10000;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.3s ease;
    `;
    
    // Sayfaya ekle
    document.body.appendChild(alertDiv);
    
    // 3 saniye sonra otomatik kaldır
    setTimeout(function() {
        if (document.body.contains(alertDiv)) {
            document.body.removeChild(alertDiv);
        }
    }, 3000);
}
</script>

<!-- Aynı Organizatöre Ait Diğer Etkinlikler -->
<?php
// Aynı organizatöre ait diğer etkinlikleri getir
$sameOrganizerQuery = "SELECT e.*, c.name as category_name, c.icon as category_icon
                      FROM events e
                      LEFT JOIN categories c ON e.category_id = c.id
                      WHERE e.organizer_id = ? AND e.id != ? AND e.status = 'published' AND e.event_date >= NOW()
                      ORDER BY e.event_date ASC
                      LIMIT 6";
$sameOrganizerStmt = $pdo->prepare($sameOrganizerQuery);
$sameOrganizerStmt->execute([$event['organizer_id'], $eventId]);
$sameOrganizerEvents = $sameOrganizerStmt->fetchAll(PDO::FETCH_ASSOC);

// Benzer etkinlikleri getir (aynı kategori, farklı organizatör)
$similarEventsQuery = "SELECT e.*, c.name as category_name, c.icon as category_icon,
                              od.company_name as organizer_name
                       FROM events e
                       LEFT JOIN categories c ON e.category_id = c.id
                       LEFT JOIN organizer_details od ON e.organizer_id = od.user_id
                       WHERE e.category_id = ? AND e.id != ? AND e.organizer_id != ? AND e.status = 'published' AND e.event_date >= NOW()
                       ORDER BY e.event_date ASC
                       LIMIT 6";
$similarEventsStmt = $pdo->prepare($similarEventsQuery);
$similarEventsStmt->execute([$event['category_id'], $eventId, $event['organizer_id']]);
$similarEvents = $similarEventsStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php if (!empty($sameOrganizerEvents)): ?>
<section class="related-events-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">
                <i class="fas fa-user-tie"></i>
                <?php echo htmlspecialchars($event['organizer_name']); ?> Organizatörünün Diğer Etkinlikleri
            </h2>
            <p class="section-subtitle">Aynı organizatörün düzenlediği diğer etkinlikleri keşfedin</p>
        </div>
        
        <div class="events-grid">
            <?php foreach ($sameOrganizerEvents as $relatedEvent): ?>
            <div class="event-card" onclick="window.location.href='etkinlik-detay.php?id=<?php echo $relatedEvent['id']; ?>'">
                <div class="event-image">
                    <?php if ($relatedEvent['image_url']): ?>
                        <img src="<?php echo htmlspecialchars($relatedEvent['image_url']); ?>" alt="<?php echo htmlspecialchars($relatedEvent['title']); ?>">
                    <?php else: ?>
                        <div class="event-placeholder">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                    <?php endif; ?>
                    <div class="event-category">
                        <i class="<?php echo htmlspecialchars($relatedEvent['category_icon'] ?? 'fas fa-music'); ?>"></i>
                        <?php echo htmlspecialchars($relatedEvent['category_name']); ?>
                    </div>
                </div>
                <div class="event-content">
                    <h3 class="event-title"><?php echo htmlspecialchars($relatedEvent['title']); ?></h3>
                    <div class="event-meta">
                        <div class="event-date">
                            <i class="fas fa-calendar"></i>
                            <?php echo date('d M Y', strtotime($relatedEvent['event_date'])); ?>
                        </div>
                        <div class="event-time">
                            <i class="fas fa-clock"></i>
                            <?php echo date('H:i', strtotime($relatedEvent['event_date'])); ?>
                        </div>
                    </div>
                    <div class="event-location">
                        <i class="fas fa-map-marker-alt"></i>
                        <?php echo htmlspecialchars($relatedEvent['venue_name']); ?>
                    </div>
                    <?php if ($relatedEvent['short_description']): ?>
                    <p class="event-description"><?php echo htmlspecialchars(mb_substr($relatedEvent['short_description'], 0, 100)); ?><?php echo mb_strlen($relatedEvent['short_description']) > 100 ? '...' : ''; ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($similarEvents)): ?>
<section class="related-events-section similar-events">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">
                <i class="fas fa-heart"></i>
                Benzer Etkinlikler
            </h2>
            <p class="section-subtitle">İlginizi çekebilecek diğer <?php echo htmlspecialchars($event['category_name']); ?> etkinlikleri</p>
        </div>
        
        <div class="events-grid">
            <?php foreach ($similarEvents as $similarEvent): ?>
            <div class="event-card" onclick="window.location.href='etkinlik-detay.php?id=<?php echo $similarEvent['id']; ?>'">
                <div class="event-image">
                    <?php if ($similarEvent['image_url']): ?>
                        <img src="<?php echo htmlspecialchars($similarEvent['image_url']); ?>" alt="<?php echo htmlspecialchars($similarEvent['title']); ?>">
                    <?php else: ?>
                        <div class="event-placeholder">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                    <?php endif; ?>
                    <div class="event-category">
                        <i class="<?php echo htmlspecialchars($similarEvent['category_icon'] ?? 'fas fa-music'); ?>"></i>
                        <?php echo htmlspecialchars($similarEvent['category_name']); ?>
                    </div>
                </div>
                <div class="event-content">
                    <h3 class="event-title"><?php echo htmlspecialchars($similarEvent['title']); ?></h3>
                    <div class="event-meta">
                        <div class="event-date">
                            <i class="fas fa-calendar"></i>
                            <?php echo date('d M Y', strtotime($similarEvent['event_date'])); ?>
                        </div>
                        <div class="event-time">
                            <i class="fas fa-clock"></i>
                            <?php echo date('H:i', strtotime($similarEvent['event_date'])); ?>
                        </div>
                    </div>
                    <div class="event-location">
                        <i class="fas fa-map-marker-alt"></i>
                        <?php echo htmlspecialchars($similarEvent['venue_name']); ?>
                    </div>
                    <div class="event-organizer">
                        <i class="fas fa-user-tie"></i>
                        <?php echo htmlspecialchars($similarEvent['organizer_name']); ?>
                    </div>
                    <?php if ($similarEvent['short_description']): ?>
                    <p class="event-description"><?php echo htmlspecialchars(mb_substr($similarEvent['short_description'], 0, 100)); ?><?php echo mb_strlen($similarEvent['short_description']) > 100 ? '...' : ''; ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<style>
.related-events-section {
    padding: 60px 0;
    background: #f8f9fa;
}

.related-events-section.similar-events {
    background: #ffffff;
    border-top: 1px solid #e9ecef;
}

.section-header {
    text-align: center;
    margin-bottom: 40px;
}

.section-title {
    font-size: 28px;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
}

.section-title i {
    color: #e91e63;
    font-size: 24px;
}

.section-subtitle {
    font-size: 16px;
    color: #6c757d;
    margin: 0;
}

.events-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 24px;
    max-width: 1200px;
    margin: 0 auto;
}

.event-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    cursor: pointer;
    border: 1px solid #e9ecef;
}

.event-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.event-image {
    position: relative;
    height: 200px;
    overflow: hidden;
}

.event-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.event-card:hover .event-image img {
    transform: scale(1.05);
}

.event-placeholder {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 48px;
}

.event-category {
    position: absolute;
    top: 12px;
    left: 12px;
    background: rgba(233, 30, 99, 0.9);
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 6px;
    backdrop-filter: blur(10px);
}

.event-content {
    padding: 20px;
}

.event-title {
    font-size: 18px;
    font-weight: 700;
    color: #2c3e50;
    margin: 0 0 12px 0;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.event-meta {
    display: flex;
    gap: 16px;
    margin-bottom: 10px;
    flex-wrap: wrap;
}

.event-date, .event-time {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 14px;
    color: #6c757d;
    font-weight: 500;
}

.event-date i, .event-time i {
    color: #e91e63;
    font-size: 12px;
}

.event-location, .event-organizer {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 14px;
    color: #6c757d;
    margin-bottom: 8px;
    font-weight: 500;
}

.event-location i, .event-organizer i {
    color: #e91e63;
    font-size: 12px;
    min-width: 12px;
}

.event-description {
    font-size: 14px;
    color: #6c757d;
    line-height: 1.5;
    margin: 12px 0 0 0;
}

@media (max-width: 768px) {
    .related-events-section {
        padding: 40px 0;
    }
    
    .section-title {
        font-size: 24px;
        flex-direction: column;
        gap: 8px;
    }
    
    .events-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .event-meta {
        flex-direction: column;
        gap: 8px;
    }
}
</style>

<!-- Yorumlar ve Değerlendirmeler Bölümü -->
<?php
// Onaylanmış yorumları getir
$reviewsQuery = $pdo->prepare("
    SELECT ec.*, u.first_name, u.last_name
    FROM event_comments ec
    JOIN users u ON ec.user_id = u.id
    WHERE ec.event_id = ? AND ec.status = 'approved'
    ORDER BY ec.created_at DESC
    LIMIT 10
");
$reviewsQuery->execute([$eventId]);
$reviews = $reviewsQuery->fetchAll(PDO::FETCH_ASSOC);

// Ortalama puan ve toplam yorum sayısı
$ratingQuery = $pdo->prepare("
    SELECT 
        AVG(rating) as avg_rating,
        COUNT(*) as total_reviews,
        COUNT(CASE WHEN rating = 5 THEN 1 END) as five_star,
        COUNT(CASE WHEN rating = 4 THEN 1 END) as four_star,
        COUNT(CASE WHEN rating = 3 THEN 1 END) as three_star,
        COUNT(CASE WHEN rating = 2 THEN 1 END) as two_star,
        COUNT(CASE WHEN rating = 1 THEN 1 END) as one_star
    FROM event_comments 
    WHERE event_id = ? AND status = 'approved'
");
$ratingQuery->execute([$eventId]);
$ratingStats = $ratingQuery->fetch(PDO::FETCH_ASSOC);

// Kullanıcının bu etkinlik için bilet satın alıp almadığını kontrol et
$canReview = false;
$hasReviewed = false;
if (isLoggedIn() && $_SESSION['user_type'] === 'customer') {
    $ticketCheckQuery = $pdo->prepare("
        SELECT COUNT(*) FROM tickets t 
        JOIN orders o ON t.order_id = o.id 
        WHERE o.user_id = ? AND t.event_id = ? AND o.payment_status = 'completed'
    ");
    $ticketCheckQuery->execute([$_SESSION['user_id'], $eventId]);
    $canReview = $ticketCheckQuery->fetchColumn() > 0;
    
    // Daha önce yorum yapıp yapmadığını kontrol et
    if ($canReview) {
        $reviewCheckQuery = $pdo->prepare("SELECT id FROM event_comments WHERE event_id = ? AND user_id = ?");
        $reviewCheckQuery->execute([$eventId, $_SESSION['user_id']]);
        $hasReviewed = $reviewCheckQuery->fetch() !== false;
    }
}
?>

<section class="reviews-section">
    <div class="container">
        <div class="reviews-header">
            <h2 class="section-title">
                <i class="fas fa-star"></i>
                Yorumlar ve Değerlendirmeler
            </h2>
            
            <?php if ($ratingStats['total_reviews'] > 0): ?>
                <div class="rating-summary">
                    <div class="overall-rating">
                        <div class="rating-score"><?php echo number_format($ratingStats['avg_rating'], 1); ?></div>
                        <div class="rating-stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?php echo $i <= round($ratingStats['avg_rating']) ? 'active' : ''; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <div class="rating-count"><?php echo $ratingStats['total_reviews']; ?> değerlendirme</div>
                    </div>
                    
                    <div class="rating-breakdown">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <?php 
                            $starCount = $ratingStats[$i === 5 ? 'five_star' : ($i === 4 ? 'four_star' : ($i === 3 ? 'three_star' : ($i === 2 ? 'two_star' : 'one_star')))];
                            $percentage = $ratingStats['total_reviews'] > 0 ? ($starCount / $ratingStats['total_reviews']) * 100 : 0;
                            ?>
                            <div class="rating-bar">
                                <span class="star-label"><?php echo $i; ?> yıldız</span>
                                <div class="bar-container">
                                    <div class="bar-fill" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                                <span class="star-count"><?php echo $starCount; ?></span>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Yorum Yapma Formu -->
        <?php if (isLoggedIn()): ?>
            <?php if ($_SESSION['user_type'] === 'customer'): ?>
                <?php if ($canReview && !$hasReviewed): ?>
                    <div class="review-form-section">
                        <h3>Değerlendirmenizi Paylaşın</h3>
                        <form id="reviewForm" class="review-form">
                            <input type="hidden" name="event_id" value="<?php echo $eventId; ?>">
                            
                            <div class="rating-input">
                                <label>Puanınız:</label>
                                <div class="star-rating">
                                    <input type="radio" name="rating" value="5" id="star5">
                                    <label for="star5"><i class="fas fa-star"></i></label>
                                    <input type="radio" name="rating" value="4" id="star4">
                                    <label for="star4"><i class="fas fa-star"></i></label>
                                    <input type="radio" name="rating" value="3" id="star3">
                                    <label for="star3"><i class="fas fa-star"></i></label>
                                    <input type="radio" name="rating" value="2" id="star2">
                                    <label for="star2"><i class="fas fa-star"></i></label>
                                    <input type="radio" name="rating" value="1" id="star1">
                                    <label for="star1"><i class="fas fa-star"></i></label>
                                </div>
                            </div>
                            
                            <div class="comment-input">
                                <label for="comment">Yorumunuz:</label>
                                <textarea name="comment" id="comment" rows="4" placeholder="Etkinlik hakkındaki düşüncelerinizi paylaşın..." required></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i>
                                Yorumu Gönder
                            </button>
                        </form>
                    </div>
                <?php elseif ($hasReviewed): ?>
                    <div class="review-notice">
                        <i class="fas fa-check-circle"></i>
                        <p>Bu etkinlik için zaten değerlendirme yapmışsınız.</p>
                    </div>
                <?php else: ?>
                    <div class="review-notice">
                        <i class="fas fa-info-circle"></i>
                        <p>Değerlendirme yapabilmek için bu etkinliğe bilet satın almış olmanız gerekiyor.</p>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="review-notice">
                    <i class="fas fa-info-circle"></i>
                    <p>Sadece müşteri hesapları değerlendirme yapabilir.</p>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="review-notice">
                <i class="fas fa-sign-in-alt"></i>
                <p>Değerlendirme yapabilmek için <a href="auth/login.php">giriş yapmanız</a> gerekiyor.</p>
            </div>
        <?php endif; ?>
        
        <!-- Mevcut Yorumlar -->
        <?php if (!empty($reviews)): ?>
            <div class="reviews-list">
                <h3>Müşteri Yorumları</h3>
                <?php foreach ($reviews as $review): ?>
                    <div class="review-item">
                        <div class="review-header">
                            <div class="reviewer-info">
                                <div class="reviewer-avatar">
                                    <?php echo strtoupper(substr($review['first_name'], 0, 1) . substr($review['last_name'], 0, 1)); ?>
                                </div>
                                <div class="reviewer-details">
                                    <h4><?php echo htmlspecialchars($review['first_name'] . ' ' . substr($review['last_name'], 0, 1) . '.'); ?></h4>
                                    <div class="review-date"><?php echo date('d.m.Y', strtotime($review['created_at'])); ?></div>
                                </div>
                            </div>
                            
                            <div class="review-rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'active' : ''; ?>"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        
                        <div class="review-content">
                            <p><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php elseif ($ratingStats['total_reviews'] == 0): ?>
            <div class="no-reviews">
                <i class="fas fa-comments"></i>
                <h3>Henüz değerlendirme yok</h3>
                <p>Bu etkinlik için henüz değerlendirme yapılmamış. İlk değerlendirmeyi siz yapın!</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
.reviews-section {
    padding: 60px 0;
    background: #f8f9fa;
}

.reviews-header {
    text-align: center;
    margin-bottom: 40px;
}

.section-title {
    font-size: 28px;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
}

.section-title i {
    color: #e91e63;
}

.rating-summary {
    display: flex;
    gap: 40px;
    justify-content: center;
    align-items: center;
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    max-width: 600px;
    margin: 0 auto;
}

.overall-rating {
    text-align: center;
}

.rating-score {
    font-size: 48px;
    font-weight: 700;
    color: #2c3e50;
    line-height: 1;
}

.rating-stars {
    margin: 10px 0;
}

.rating-stars .fas.fa-star {
    color: #ddd;
    font-size: 20px;
    margin: 0 2px;
}

.rating-stars .fas.fa-star.active {
    color: #ffc107;
}

.rating-count {
    color: #6c757d;
    font-size: 14px;
}

.rating-breakdown {
    display: flex;
    flex-direction: column;
    gap: 8px;
    min-width: 200px;
}

.rating-bar {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
}

.star-label {
    min-width: 60px;
    color: #6c757d;
}

.bar-container {
    flex: 1;
    height: 8px;
    background: #e9ecef;
    border-radius: 4px;
    overflow: hidden;
}

.bar-fill {
    height: 100%;
    background: #ffc107;
    transition: width 0.3s ease;
}

.star-count {
    min-width: 30px;
    text-align: right;
    color: #6c757d;
    font-size: 12px;
}

.review-form-section {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.review-form-section h3 {
    margin: 0 0 20px 0;
    color: #2c3e50;
    font-size: 20px;
}

.review-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.rating-input label {
    display: block;
    margin-bottom: 10px;
    font-weight: 600;
    color: #2c3e50;
}

.star-rating {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
    gap: 5px;
}

.star-rating input {
    display: none;
}

.star-rating label {
    cursor: pointer;
    font-size: 24px;
    color: #ddd;
    transition: color 0.2s ease;
    margin: 0;
}

.star-rating label:hover,
.star-rating label:hover ~ label,
.star-rating input:checked ~ label {
    color: #ffc107;
}

.comment-input {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.comment-input label {
    font-weight: 600;
    color: #2c3e50;
}

.comment-input textarea {
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-family: inherit;
    font-size: 14px;
    resize: vertical;
    min-height: 100px;
}

.comment-input textarea:focus {
    outline: none;
    border-color: #e91e63;
    box-shadow: 0 0 0 3px rgba(233, 30, 99, 0.1);
}

.review-notice {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    margin-bottom: 30px;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.review-notice i {
    color: #6c757d;
    font-size: 18px;
}

.review-notice p {
    margin: 0;
    color: #6c757d;
}

.review-notice a {
    color: #e91e63;
    text-decoration: none;
}

.review-notice a:hover {
    text-decoration: underline;
}

.reviews-list {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    overflow: hidden;
}

.reviews-list h3 {
    padding: 20px 30px;
    margin: 0;
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    color: #2c3e50;
    font-size: 18px;
}

.review-item {
    padding: 25px 30px;
    border-bottom: 1px solid #f0f0f0;
}

.review-item:last-child {
    border-bottom: none;
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.reviewer-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.reviewer-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 14px;
}

.reviewer-details h4 {
    margin: 0 0 4px 0;
    color: #2c3e50;
    font-size: 16px;
}

.review-date {
    font-size: 12px;
    color: #6c757d;
}

.review-rating .fas.fa-star {
    color: #ddd;
    font-size: 14px;
    margin: 0 1px;
}

.review-rating .fas.fa-star.active {
    color: #ffc107;
}

.review-content p {
    margin: 0;
    line-height: 1.6;
    color: #2c3e50;
}

.no-reviews {
    text-align: center;
    padding: 60px 20px;
    color: #6c757d;
}

.no-reviews i {
    font-size: 48px;
    margin-bottom: 20px;
    color: #ddd;
}

.no-reviews h3 {
    margin: 0 0 10px 0;
    color: #2c3e50;
}

.no-reviews p {
    margin: 0;
}

@media (max-width: 768px) {
    .rating-summary {
        flex-direction: column;
        gap: 20px;
    }
    
    .rating-breakdown {
        min-width: auto;
        width: 100%;
    }
    
    .review-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .review-form-section,
    .reviews-list {
        margin: 0 -20px;
        border-radius: 0;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const reviewForm = document.getElementById('reviewForm');
    
    if (reviewForm) {
        reviewForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const rating = formData.get('rating');
            const comment = formData.get('comment');
            
            if (!rating) {
                alert('Lütfen bir puan seçin.');
                return;
            }
            
            if (!comment.trim()) {
                alert('Lütfen yorumunuzu yazın.');
                return;
            }
            
            // Submit butonu
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Gönderiliyor...';
            submitBtn.disabled = true;
            
            fetch('ajax/add_review.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Bir hata oluştu. Lütfen tekrar deneyin.');
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>