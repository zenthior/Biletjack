<?php 
require_once 'includes/session.php';
require_once 'config/database.php';
require_once 'classes/Event.php';

include 'includes/header.php'; 

// Database bağlantısını oluştur
$database = new Database();
$pdo = $database->getConnection();

// Event sınıfını başlat
$event = new Event($pdo);

// İndirimli etkinlikleri çek (is_featured = 1 olanlar veya özel indirim koşulları)
$discountedEvents = $event->getFeaturedEvents(50); // Öne çıkan etkinlikler indirimli kabul edilir

// Ayrıca discount_codes tablosundan aktif indirimleri de çekebiliriz
$discountQuery = "SELECT DISTINCT e.*, c.name as category_name, dc.discount_amount, dc.code as discount_code
                 FROM events e
                 LEFT JOIN categories c ON e.category_id = c.id
                 LEFT JOIN discount_codes dc ON e.id = dc.event_id
                 WHERE e.status = 'published' 
                 AND e.event_date >= CURDATE()
                 AND dc.status = 'active' 
                 ORDER BY dc.discount_amount DESC, e.event_date ASC";

$discountStmt = $pdo->prepare($discountQuery);
$discountStmt->execute();
$discountCodeEvents = $discountStmt->fetchAll(PDO::FETCH_ASSOC);

// İki listeyi birleştir ve tekrarları kaldır
$allDiscountedEvents = array_merge($discountedEvents, $discountCodeEvents);
$uniqueEvents = [];
$seenIds = [];

foreach ($allDiscountedEvents as $event) {
    if (!in_array($event['id'], $seenIds)) {
        $uniqueEvents[] = $event;
        $seenIds[] = $event['id'];
    }
}

$discountedEvents = $uniqueEvents;
?>

<style>
.discounts-page {
    min-height: 100vh;
    padding: 2rem 0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.discounts-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
}

.page-header {
    text-align: center;
    margin-bottom: 3rem;
    color: white;
}

.page-title {
    font-size: 3rem;
    font-weight: 700;
    margin-bottom: 1rem;
    background: linear-gradient(45deg, #fff, #f0f0f0);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.page-subtitle {
    font-size: 1.2rem;
    opacity: 0.9;
    margin-bottom: 2rem;
}

.discount-stats {
    display: flex;
    justify-content: center;
    gap: 2rem;
    margin-bottom: 2rem;
}

.stat-item {
    text-align: center;
    background: rgba(255, 255, 255, 0.1);
    padding: 1rem 2rem;
    border-radius: 15px;
    backdrop-filter: blur(10px);
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    display: block;
}

.stat-label {
    font-size: 0.9rem;
    opacity: 0.8;
}

.events-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 2rem;
    margin-top: 2rem;
}

.event-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    position: relative;
}

.event-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
}

.discount-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    background: linear-gradient(45deg, #ff6b6b, #ee5a24);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-weight: 700;
    font-size: 0.9rem;
    z-index: 2;
    box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
}

.event-image {
    width: 100%;
    height: 200px;
    object-fit: cover;
    position: relative;
}

.event-content {
    padding: 1.5rem;
}

.event-category {
    color: #667eea;
    font-size: 0.9rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.event-title {
    font-size: 1.3rem;
    font-weight: 700;
    margin-bottom: 1rem;
    color: #2c3e50;
    line-height: 1.4;
}

.event-details {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
}

.event-detail {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    color: #666;
}

.event-price {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
}

.price-original {
    text-decoration: line-through;
    color: #999;
    font-size: 0.9rem;
}

.price-discounted {
    font-size: 1.3rem;
    font-weight: 700;
    color: #27ae60;
}

.event-btn {
    width: 100%;
    padding: 0.8rem;
    background: linear-gradient(45deg, #667eea, #764ba2);
    color: white;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.event-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
}

.no-events {
    text-align: center;
    padding: 4rem 2rem;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    backdrop-filter: blur(10px);
    color: white;
}

.no-events-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}

@media (max-width: 768px) {
    .page-title {
        font-size: 2rem;
    }
    
    .discount-stats {
        flex-direction: column;
        gap: 1rem;
    }
    
    .events-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
}
</style>

<div class="discounts-page">
    <div class="discounts-container">
        <div class="page-header">
            <h1 class="page-title">🎟️ İndirimli Etkinlikler</h1>
            <p class="page-subtitle">En iyi fırsatları kaçırmayın! İndirimli etkinlikler burada.</p>
            
            <div class="discount-stats">
                <div class="stat-item">
                    <span class="stat-number"><?php echo count($discountedEvents); ?></span>
                    <span class="stat-label">İndirimli Etkinlik</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">%50</span>
                    <span class="stat-label">Kadar İndirim</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">24/7</span>
                    <span class="stat-label">Aktif Fırsatlar</span>
                </div>
            </div>
        </div>

        <?php if (empty($discountedEvents)): ?>
            <div class="no-events">
                <div class="no-events-icon">🎫</div>
                <h3>Şu anda aktif indirim bulunmuyor</h3>
                <p>Yeni indirimlerden haberdar olmak için bizi takip edin!</p>
            </div>
        <?php else: ?>
            <div class="events-grid">
                <?php foreach ($discountedEvents as $event): ?>
                    <div class="event-card">
                        <?php if (isset($event['discount_amount']) && $event['discount_amount'] > 0): ?>
                            <div class="discount-badge">₺<?php echo number_format($event['discount_amount'], 2); ?> İndirim</div>
                        <?php else: ?>
                            <div class="discount-badge">Özel Fırsat</div>
                        <?php endif; ?>
                        
                        <img src="<?php echo htmlspecialchars($event['image_url'] ?: 'images/default-event.jpg'); ?>" 
                             alt="<?php echo htmlspecialchars($event['title']); ?>" 
                             class="event-image">
                        
                        <div class="event-content">
                            <div class="event-category"><?php echo htmlspecialchars($event['category_name'] ?: 'Etkinlik'); ?></div>
                            <h3 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h3>
                            
                            <div class="event-details">
                                <div class="event-detail">
                                    <span>📅</span>
                                    <span><?php echo date('d.m.Y H:i', strtotime($event['event_date'])); ?></span>
                                </div>
                                <div class="event-detail">
                                    <span>📍</span>
                                    <span><?php echo htmlspecialchars($event['venue_name']); ?>, <?php echo htmlspecialchars($event['city']); ?></span>
                                </div>
                            </div>
                            
                            <div class="event-price">
                                <?php if (isset($event['discount_amount']) && $event['discount_amount'] > 0): ?>
                                    <div>
                                        <span class="price-original">₺<?php echo number_format($event['min_price'], 2); ?></span>
                                        <span class="price-discounted">₺<?php echo number_format($event['min_price'] - $event['discount_amount'], 2); ?></span>
                                    </div>
                                <?php else: ?>
                                    <div>
                                        <span class="price-discounted">₺<?php echo number_format($event['min_price'], 2); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <button class="event-btn" onclick="window.location.href='etkinlik-detay.php?id=<?php echo $event['id']; ?>'">
                                Detayları Gör
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>