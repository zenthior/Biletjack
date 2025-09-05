<?php
require_once '../includes/session.php';
require_once '../config/database.php';

// JSON response için header
header('Content-Type: application/json');

// Organizatör kontrolü
if (!isLoggedIn() || $_SESSION['user_type'] !== 'organizer') {
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit();
}

// Organizatör onayı kontrolü
if (!isOrganizerApproved()) {
    echo json_encode(['success' => false, 'message' => 'Hesabınız henüz onaylanmamış']);
    exit();
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Organizatörün etkinliklerini getir
    $query = "SELECT e.*, c.name as category_name 
             FROM events e
             LEFT JOIN categories c ON e.category_id = c.id
             WHERE e.organizer_id = ?
             ORDER BY e.created_at DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$_SESSION['user_id']]);
    $organizerEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // HTML oluştur
    ob_start();
    foreach ($organizerEvents as $evt): ?>
    <div class="event-card" data-status="<?php echo $evt['status']; ?>">
        <div class="event-image" style="background-image: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.7)), <?php echo $evt['image_url'] ? 'url(../' . $evt['image_url'] . ')' : 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'; ?>">
            <div class="event-status status-<?php echo $evt['status']; ?>">
                <?php 
                switch($evt['status']) {
                    case 'published': echo 'Yayında'; break;
                    case 'draft': echo 'Taslak'; break;
                    case 'cancelled': echo 'İptal'; break;
                    case 'completed': echo 'Tamamlandı'; break;
                    default: echo ucfirst($evt['status']);
                }
                ?>
            </div>
            <div class="event-actions">
                <?php if ($evt['status'] === 'draft'): ?>
                <button class="action-btn publish-btn" onclick="publishEvent(<?php echo $evt['id']; ?>)" title="Yayınla">
                    <i class="fas fa-eye"></i>
                </button>
                <?php elseif ($evt['status'] === 'published'): ?>
                <button class="action-btn draft-btn" onclick="unpublishEvent(<?php echo $evt['id']; ?>)" title="Taslağa Al">
                    <i class="fas fa-eye-slash"></i>
                </button>
                <?php endif; ?>
                <button class="action-btn" onclick="editEvent(<?php echo $evt['id']; ?>)" title="Düzenle">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="action-btn delete-btn" onclick="deleteEvent(<?php echo $evt['id']; ?>)" title="Sil">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
        <div class="event-content">
            <div class="event-meta">
                <span class="event-category"><?php echo htmlspecialchars($evt['category_name'] ?? 'Kategori'); ?></span>
                <span class="event-date"><?php echo date('d.m.Y H:i', strtotime($evt['event_date'])); ?></span>
            </div>
            <h3 class="event-title"><?php echo htmlspecialchars($evt['title']); ?></h3>
            <p class="event-description"><?php echo htmlspecialchars(substr($evt['short_description'] ?? $evt['description'], 0, 100)) . '...'; ?></p>
            <div class="event-location">
                <i class="fas fa-map-marker-alt"></i>
                <?php echo htmlspecialchars($evt['venue_name']); ?>, <?php echo htmlspecialchars($evt['city']); ?>
            </div>
            <?php if (isset($evt['min_price']) && $evt['min_price']): ?>
            <div class="event-price">
                <?php echo number_format($evt['min_price'], 2); ?> TL'den başlayan fiyatlarla
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach;
    
    $html = ob_get_clean();
    
    echo json_encode([
        'success' => true,
        'html' => $html,
        'count' => count($organizerEvents)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Hata: ' . $e->getMessage()
    ]);
}