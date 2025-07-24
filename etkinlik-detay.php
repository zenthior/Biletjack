<?php 
require_once 'config/database.php';
require_once 'classes/Event.php';

// Etkinlik ID'sini al
$eventId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($eventId <= 0) {
    header('Location: etkinlikler.php');
    exit();
}

// Database bağlantısını oluştur
$database = new Database();
$pdo = $database->getConnection();

// Etkinlik bilgilerini getir
$query = "SELECT e.*, c.name as category_name, c.icon as category_icon,
                 od.company_name as organizer_name
          FROM events e
          LEFT JOIN categories c ON e.category_id = c.id
          LEFT JOIN organizer_details od ON e.organizer_id = od.user_id
          WHERE e.id = ? AND e.status = 'published'";
$stmt = $pdo->prepare($query);
$stmt->execute([$eventId]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    header('Location: etkinlikler.php');
    exit();
}

// Bilet türlerini getir
$ticketQuery = "SELECT * FROM ticket_types WHERE event_id = ? ORDER BY price ASC";
$ticketStmt = $pdo->prepare($ticketQuery);
$ticketStmt->execute([$eventId]);
$tickets = $ticketStmt->fetchAll(PDO::FETCH_ASSOC);

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
                        <div class="event-category-tag" id="eventCategoryTag">
                            <?php echo $event['category_icon']; ?> <?php echo htmlspecialchars($event['category_name']); ?>
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
                            <h3>Biletler</h3>
                            <div class="event-performance">
                                <div class="ticket-header-info">
                                    <div class="venue-details">
                                        <h4 class="venue-name"><?php echo htmlspecialchars($event['venue_name']); ?></h4>
                                        <div class="location-info">
                                            <span class="city-name"><?php echo htmlspecialchars($event['city']); ?></span>
                                            <span class="seat-selection">Koltuk Seçimi</span>
                                        </div>
                                        <div class="event-datetime">
                                            <span class="event-date"><?php echo date('d F Y', strtotime($event['event_date'])); ?></span>
                                            <span class="event-time"><?php echo date('H:i', strtotime($event['event_date'])); ?></span>
                                        </div>
                                    </div>
                                    <div class="ticket-price-section">
                                        <?php if (!empty($tickets)): ?>
                                            <?php $firstTicket = $tickets[0]; ?>
                                            <div class="price-display">
                                                <?php if ($firstTicket['discount_price'] && $firstTicket['discount_price'] < $firstTicket['price']): ?>
                                                    <span class="original-price">₺<?php echo number_format($firstTicket['price'], 0); ?></span>
                                                    <span class="discount-price">₺<?php echo number_format($firstTicket['discount_price'], 0); ?></span>
                                                <?php else: ?>
                                                    <span class="current-price">₺<?php echo number_format($firstTicket['price'], 0); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <button class="btn-primary btn-buy-ticket" data-ticket-id="<?php echo $firstTicket['id']; ?>">
                                                Satın Al
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Diğer Bilet Türleri -->
                                <?php if (count($tickets) > 1): ?>
                                <div class="other-ticket-types">
                                    <h5>Diğer Bilet Türleri</h5>
                                    <?php for ($i = 1; $i < count($tickets); $i++): ?>
                                        <?php $ticket = $tickets[$i]; ?>
                                        <div class="ticket-type-item">
                                            <div class="ticket-info">
                                                <h6 class="ticket-name"><?php echo htmlspecialchars($ticket['name']); ?></h6>
                                                <?php if ($ticket['description']): ?>
                                                <p class="ticket-description"><?php echo htmlspecialchars($ticket['description']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="ticket-price-action">
                                                <div class="ticket-price">
                                                    <?php if ($ticket['discount_price'] && $ticket['discount_price'] < $ticket['price']): ?>
                                                        <span class="original-price">₺<?php echo number_format($ticket['price'], 0); ?></span>
                                                        <span class="discount-price">₺<?php echo number_format($ticket['discount_price'], 0); ?></span>
                                                    <?php else: ?>
                                                        <span class="current-price">₺<?php echo number_format($ticket['price'], 0); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <button class="btn-primary btn-buy-ticket" data-ticket-id="<?php echo $ticket['id']; ?>">
                                                    Satın Al
                                                </button>
                                            </div>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Etkinlik Hakkında -->
                    <div class="event-about">
                        <div class="about-header">
                            <span class="about-icon">ℹ️</span>
                            <h3>Etkinlik Hakkında</h3>
                        </div>
                        <div class="about-content">
                            <div class="description-short">
                                <?php if ($event['short_description']): ?>
                                    <p><?php echo nl2br(htmlspecialchars($event['short_description'])); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="description-full" style="display: none;">
                                <p><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                            </div>
                            <?php if ($event['short_description'] && strlen($event['description']) > strlen($event['short_description'])): ?>
                            <button class="btn-read-more" onclick="toggleDescription()">Devamını oku <i class="fas fa-chevron-down"></i></button>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Sanatçılar -->
                    <div class="event-artists">
                        <div class="artists-header">
                            <span class="artists-icon">🎤</span>
                            <h3>Sanatçılar</h3>
                        </div>
                        <div class="artists-list">
                            <?php if (!empty($artists)): ?>
                                <?php foreach ($artists as $artist): ?>
                                <div class="artist-item">
                                    <div class="artist-avatar">
                                        <span class="artist-initial"><?php echo strtoupper(substr(trim($artist), 0, 1)); ?></span>
                                    </div>
                                    <span class="artist-name"><?php echo htmlspecialchars($artist); ?></span>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="no-artists">Sanatçı bilgisi bulunmuyor.</p>
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
                <!-- Etkinlik Kuralları kısmı kaldırıldı -->
                
                <div class="details-card">
                    <h3>Mekan Bilgileri</h3>
                    <div class="venue-info">
                        <p><strong id="venueNameSidebar"><?php echo htmlspecialchars($event['venue_name']); ?></strong></p>
                        <p class="venue-address"><?php echo htmlspecialchars($event['city']); ?></p>
                        <?php if ($event['venue_address']): ?>
                        <p class="venue-full-address"><?php echo htmlspecialchars($event['venue_address']); ?></p>
                        <?php endif; ?>
                        <button class="btn-map" onclick="toggleMap()">📍 Haritada Göster</button>
                        
                        <!-- Harita Bölümü -->
                        <div id="mapContainer" class="map-container" style="display: none;">
                            <div id="venueMap" class="venue-map">
                                <!-- Harita buraya yüklenecek -->
                                <div class="map-loading" id="mapLoading">
                                    <div class="loading-spinner"></div>
                                    <p>Harita yükleniyor...</p>
                                </div>
                            </div>
                            <div class="map-info">
                                <div class="location-details">
                                    <h4><?php echo htmlspecialchars($event['venue_name']); ?></h4>
                                    <p class="address"><?php echo htmlspecialchars($event['venue_address'] ?? $event['city']); ?></p>
                                    <div class="map-actions">
                                        <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($event['venue_name'] . ' ' . $event['city']); ?>" 
                                           target="_blank" class="btn-directions">
                                            <i class="fas fa-directions"></i> Yol Tarifi Al
                                        </a>
                                        <button onclick="shareLocation()" class="btn-share">
                                            <i class="fas fa-share"></i> Konumu Paylaş
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- İletişim Bilgileri -->
                <?php if ($event['contact_phone'] || $event['contact_email'] || !empty($socialLinks)): ?>
                <div class="details-card">
                    <h3>İletişim Bilgileri</h3>
                    <div class="contact-info">
                        <?php if ($event['contact_phone']): ?>
                        <div class="contact-item">
                            <span class="contact-icon">📞</span>
                            <a href="tel:<?php echo htmlspecialchars($event['contact_phone']); ?>"><?php echo htmlspecialchars($event['contact_phone']); ?></a>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($event['contact_email']): ?>
                        <div class="contact-item">
                            <span class="contact-icon">📧</span>
                            <a href="mailto:<?php echo htmlspecialchars($event['contact_email']); ?>"><?php echo htmlspecialchars($event['contact_email']); ?></a>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($socialLinks)): ?>
                        <div class="social-links">
                            <h4>Sosyal Medya</h4>
                            <div class="social-buttons">
                                <?php if (isset($socialLinks['instagram'])): ?>
                                <a href="<?php echo htmlspecialchars($socialLinks['instagram']); ?>" target="_blank" class="social-btn instagram">
                                    <i class="fab fa-instagram"></i>
                                </a>
                                <?php endif; ?>
                                
                                <?php if (isset($socialLinks['twitter'])): ?>
                                <a href="<?php echo htmlspecialchars($socialLinks['twitter']); ?>" target="_blank" class="social-btn twitter">
                                    <i class="fab fa-twitter"></i>
                                </a>
                                <?php endif; ?>
                                
                                <?php if (isset($socialLinks['facebook'])): ?>
                                <a href="<?php echo htmlspecialchars($socialLinks['facebook']); ?>" target="_blank" class="social-btn facebook">
                                    <i class="fab fa-facebook"></i>
                                </a>
                                <?php endif; ?>
                                
                                <?php if (isset($socialLinks['website'])): ?>
                                <a href="<?php echo htmlspecialchars($socialLinks['website']); ?>" target="_blank" class="social-btn website">
                                    <i class="fas fa-globe"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Organizatör Bilgileri -->
                <?php if ($event['organizer_name']): ?>
                <div class="details-card">
                    <h3>Organizatör</h3>
                    <div class="organizer-info">
                        <p><strong><?php echo htmlspecialchars($event['organizer_name']); ?></strong></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Etiketler -->
                <?php if (!empty($tags)): ?>
                <div class="details-card">
                    <h3>Etiketler</h3>
                    <div class="event-tags">
                        <?php foreach ($tags as $tag): ?>
                        <span class="event-tag"><?php echo htmlspecialchars($tag); ?></span>
                        <?php endforeach; ?>
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

<!-- Bilet Satın Alma Modal -->
<div id="ticketModal" class="ticket-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Bilet Satın Al</h3>
            <span class="close-modal" onclick="closeTicketModal()">&times;</span>
        </div>
        
        <!-- Sayfa 1: Etkinlik Kuralları -->
        <div id="modalPage1" class="modal-page active">
            <div class="modal-body">
                <h4>Etkinlik Kuralları</h4>
                <div class="rules-content">
                    <ul class="modal-rules-list">
                        <li>✓ Yaş sınırı yoktur</li>
                        <li>✓ E-biletiniz tarafınıza mail ve SMS olarak iletilecektir</li>
                        <li>✓ Çıktı almanıza gerek yoktur</li>
                        <li>✗ Satın alınan biletlerde iptal, iade ve değişiklik yapılmamaktadır</li>
                        <li>✓ Etkinlik girişinde bilet kontrolü yapılacaktır</li>
                        <li>✓ Etkinlik saatinden 30 dakika önce kapılar açılacaktır</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeTicketModal()">İptal</button>
                <button class="btn-primary" onclick="goToPage2()">Sonraki</button>
            </div>
        </div>
        
        <!-- Sayfa 2: Bilet Seçimi -->
        <div id="modalPage2" class="modal-page">
            <div class="modal-body">
                <div class="ticket-selection">
                    <div class="selected-ticket-info">
                        <h5 id="selectedTicketName"></h5>
                        <p id="selectedTicketPrice"></p>
                        <p id="selectedTicketDescription"></p>
                    </div>
                    
                    <div class="quantity-selector">
                        <label for="ticketQuantity">Bilet Adedi:</label>
                        <div class="quantity-controls">
                            <button type="button" onclick="decreaseQuantity()">-</button>
                            <input type="number" id="ticketQuantity" value="1" min="1" max="10" readonly>
                            <button type="button" onclick="increaseQuantity()">+</button>
                        </div>
                    </div>
                    
                    <div class="total-price">
                        <h4>Toplam: <span id="totalPrice">₺0</span></h4>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="goToPage1()">Geri</button>
                <button class="btn-primary" onclick="addToCart()">Sepete Ekle</button>
            </div>
        </div>
    </div>
</div>

<style>
/* Modal Stilleri */
.ticket-modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(5px);
}

.modal-content {
    background-color: white;
    margin: 5% auto;
    padding: 0;
    border-radius: 16px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    animation: modalSlideIn 0.3s ease;
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

.quantity-controls {
    display: flex;
    align-items: center;
    gap: 12px;
}

.quantity-controls button {
    width: 40px;
    height: 40px;
    border: 1px solid #ddd;
    background: white;
    border-radius: 8px;
    font-size: 18px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.quantity-controls button:hover {
    background: #f8f9fa;
    border-color: #667eea;
}

.quantity-controls input {
    width: 60px;
    height: 40px;
    text-align: center;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
}

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
    font-weight: 600;
    transition: all 0.3s;
}

.btn-primary:hover {
    background: #5a6fd8;
    transform: translateY(-1px);
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
</style>

<script>
// Global değişkenler
let selectedTicketData = null;
let currentQuantity = 1;

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
function openTicketModal(ticketData) {
    selectedTicketData = ticketData;
    currentQuantity = 1;
    
    document.getElementById('ticketModal').style.display = 'block';
    document.getElementById('modalPage1').classList.add('active');
    document.getElementById('modalPage2').classList.remove('active');
    
    // Sayfa 2'deki bilgileri doldur
    document.getElementById('selectedTicketName').textContent = ticketData.name;
    document.getElementById('selectedTicketPrice').textContent = '₺' + ticketData.price;
    document.getElementById('selectedTicketDescription').textContent = ticketData.description || '';
    document.getElementById('ticketQuantity').value = currentQuantity;
    updateTotalPrice();
}

function closeTicketModal() {
    document.getElementById('ticketModal').style.display = 'none';
}

function goToPage2() {
    document.getElementById('modalPage1').classList.remove('active');
    document.getElementById('modalPage2').classList.add('active');
}

function goToPage1() {
    document.getElementById('modalPage2').classList.remove('active');
    document.getElementById('modalPage1').classList.add('active');
}

function increaseQuantity() {
    if (currentQuantity < 10) {
        currentQuantity++;
        document.getElementById('ticketQuantity').value = currentQuantity;
        updateTotalPrice();
    }
}

function decreaseQuantity() {
    if (currentQuantity > 1) {
        currentQuantity--;
        document.getElementById('ticketQuantity').value = currentQuantity;
        updateTotalPrice();
    }
}

function updateTotalPrice() {
    const total = selectedTicketData.price * currentQuantity;
    document.getElementById('totalPrice').textContent = '₺' + total.toLocaleString('tr-TR');
}

function addToCart() {
    const eventData = {
        eventTitle: '<?php echo addslashes($event['title']); ?>',
        ticketName: selectedTicketData.name,
        ticketPrice: selectedTicketData.price,
        quantity: currentQuantity,
        total: selectedTicketData.price * currentQuantity,
        eventDate: '<?php echo date('d F Y - H:i', strtotime($event['event_date'])); ?>',
        eventLocation: '<?php echo addslashes($event['venue_name']); ?>'
    };
    
    // Modal'ı kapat
    closeTicketModal();
    
    // Sepet sayfasına yönlendir
    setTimeout(() => {
        window.location.href = 'sepet.php';
    }, 1000);
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
    document.querySelectorAll('.btn-buy-ticket').forEach(button => {
        button.addEventListener('click', function() {
            const ticketId = this.dataset.ticketId;
            
            // Bilet bilgilerini bul
            const ticketData = {
                id: ticketId,
                name: this.closest('.ticket-type-item')?.querySelector('.ticket-name')?.textContent || 'Standart Bilet',
                price: parseInt(this.closest('.ticket-price-section, .ticket-type-item')?.querySelector('.current-price, .discount-price')?.textContent.replace(/[^0-9]/g, '') || '0'),
                description: this.closest('.ticket-type-item')?.querySelector('.ticket-description')?.textContent || ''
            };
            
            openTicketModal(ticketData);
        });
    });
    
    // Modal dışına tıklayınca kapat
    window.onclick = function(event) {
        const modal = document.getElementById('ticketModal');
        if (event.target === modal) {
            closeTicketModal();
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
});
</script>

<?php include 'includes/footer.php'; ?>