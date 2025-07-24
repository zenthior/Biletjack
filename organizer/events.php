<?php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../classes/User.php';
require_once '../classes/Event.php';

// Organizatör kontrolü
requireOrganizer();

// Organizatör onay kontrolü
if (!isOrganizerApproved()) {
    header('Location: pending.php');
    exit();
}

$currentUser = getCurrentUser();

// Database bağlantısını oluştur
$database = new Database();
$pdo = $database->getConnection();

// Event sınıfını başlat
$event = new Event($pdo);

// Organizatörün etkinliklerini getir
$organizerEvents = [];
$query = "SELECT e.*, c.name as category_name 
         FROM events e
         LEFT JOIN categories c ON e.category_id = c.id
         WHERE e.organizer_id = ?
         ORDER BY e.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$organizerEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Kategorileri getir
$categories = $event->getCategories();

// İstatistikler
$totalEvents = count($organizerEvents);
$publishedEvents = count(array_filter($organizerEvents, function($e) { return $e['status'] === 'published'; }));
$draftEvents = count(array_filter($organizerEvents, function($e) { return $e['status'] === 'draft'; }));

include 'includes/header.php';
?>

<!-- Sol Sidebar -->
<div class="modern-sidebar" id="sidebar">
    <div class="sidebar-logo">
        <i class="fas fa-ticket-alt"></i>
    </div>
    
    <div class="sidebar-nav">
        <div class="nav-icon" title="Ana Sayfa" onclick="window.location.href='./index.php'" style="cursor: pointer;">
            <i class="fas fa-home"></i>
        </div>
        <div class="nav-icon active" title="Etkinlikler">
            <i class="fas fa-calendar-alt"></i>
        </div>
        <div class="nav-icon" title="Analitik">
            <i class="fas fa-chart-bar"></i>
        </div>
        <div class="nav-icon" title="Ayarlar">
            <i class="fas fa-cog"></i>
        </div>
    </div>
    
    <div class="sidebar-logout" title="Çıkış">
        <i class="fas fa-sign-out-alt"></i>
    </div>
</div>

<!-- Ana İçerik -->
<div class="main-content">
    <!-- Üst Header -->
    <div class="top-header">
        <div class="user-profile">
            <div class="user-avatar">
                <?php echo strtoupper(substr($currentUser['first_name'], 0, 1) . substr($currentUser['last_name'], 0, 1)); ?>
            </div>
            <div class="user-info">
                <h4><?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?></h4>
                <p>Organizatör</p>
            </div>
        </div>
        
        <div class="search-container">
            <i class="fas fa-search search-icon"></i>
            <input type="text" class="search-input" placeholder="Etkinlik ara..." id="eventSearch">
        </div>
        
        <div class="notification-icon">
            <i class="fas fa-bell"></i>
        </div>
    </div>
    
    <!-- Dashboard İçeriği -->
    <div class="dashboard-content">
        <!-- Sayfa Başlığı ve Yeni Etkinlik Butonu -->
        <div class="page-header">
            <div class="page-title-section">
                <h1 class="page-title">Etkinliklerim</h1>
                <p class="page-subtitle">Etkinliklerinizi yönetin ve yeni etkinlik oluşturun</p>
            </div>
            <button class="btn-primary" onclick="openEventModal()">
                <i class="fas fa-plus"></i>
                Yeni Etkinlik
            </button>
        </div>

        <!-- İstatistik Kartları -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Toplam Etkinlik</span>
                    <div class="stat-icon total">
                        <i class="fas fa-calendar"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $totalEvents; ?></div>
                <div class="stat-change">
                    <i class="fas fa-info-circle"></i>
                    Tüm etkinlikler
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Yayında</span>
                    <div class="stat-icon published">
                        <i class="fas fa-eye"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $publishedEvents; ?></div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i>
                    Aktif etkinlikler
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Taslak</span>
                    <div class="stat-icon draft">
                        <i class="fas fa-edit"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $draftEvents; ?></div>
                <div class="stat-change">
                    <i class="fas fa-clock"></i>
                    Bekleyen etkinlikler
                </div>
            </div>
        </div>

        <!-- Etkinlik Listesi -->
        <div class="events-section">
            <div class="section-header">
                <h2>Etkinlik Listesi</h2>
                <div class="filter-buttons">
                    <button class="filter-btn active" data-filter="all">Tümü</button>
                    <button class="filter-btn" data-filter="published">Yayında</button>
                    <button class="filter-btn" data-filter="draft">Taslak</button>
                    <button class="filter-btn" data-filter="cancelled">İptal</button>
                </div>
            </div>

            <?php if (empty($organizerEvents)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-calendar-plus"></i>
                </div>
                <h3>Henüz etkinlik oluşturmadınız</h3>
                <p>İlk etkinliğinizi oluşturmak için "Yeni Etkinlik" butonuna tıklayın.</p>
                <button class="btn-primary" onclick="openEventModal()">
                    <i class="fas fa-plus"></i>
                    İlk Etkinliğimi Oluştur
                </button>
            </div>
            <?php else: ?>
            <div class="events-grid">
                <?php foreach ($organizerEvents as $evt): ?>
                <div class="event-card" data-status="<?php echo $evt['status']; ?>">
                    <div class="event-image" style="background: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.7)), <?php echo $evt['image_url'] ? 'url(' . $evt['image_url'] . ')' : 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'; ?>">
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
                            <button class="action-btn" onclick="editEvent(<?php echo $evt['id']; ?>)" title="Düzenle">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="action-btn" onclick="deleteEvent(<?php echo $evt['id']; ?>)" title="Sil">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="event-content">
                        <h3 class="event-title"><?php echo htmlspecialchars($evt['title']); ?></h3>
                        <p class="event-category"><?php echo htmlspecialchars($evt['category_name']); ?></p>
                        <p class="event-venue">
                            <i class="fas fa-map-marker-alt"></i>
                            <?php echo htmlspecialchars($evt['venue_name'] . ', ' . $evt['city']); ?>
                        </p>
                        <p class="event-date">
                            <i class="fas fa-calendar"></i>
                            <?php echo date('d M Y, H:i', strtotime($evt['event_date'])); ?>
                        </p>
                        <div class="event-footer">
                            <span class="event-price">
                                <?php if ($evt['min_price']): ?>
                                    ₺<?php echo number_format($evt['min_price'], 0); ?>
                                    <?php if ($evt['max_price'] && $evt['max_price'] != $evt['min_price']): ?>
                                        - ₺<?php echo number_format($evt['max_price'], 0); ?>
                                    <?php endif; ?>
                                <?php else: ?>
                                    Fiyat belirtilmemiş
                                <?php endif; ?>
                            </span>
                            <?php if ($evt['status'] === 'published'): ?>
                            <button class="btn-view" onclick="viewEvent(<?php echo $evt['id']; ?>)">
                                <i class="fas fa-eye"></i>
                                Görüntüle
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Etkinlik Ekleme/Düzenleme Modal -->
<div class="modal-overlay" id="eventModal">
    <div class="modal-container">
        <div class="modal-header">
            <h2 id="modalTitle">Yeni Etkinlik Oluştur</h2>
            <button class="modal-close" onclick="closeEventModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="eventForm" enctype="multipart/form-data">
            <div class="modal-content">
                <!-- Adım 1: Kategori Seçimi -->
                <div class="form-step active" id="step1">
                    <h3>Etkinlik Kategorisi</h3>
                    <div class="category-grid">
                        <?php foreach ($categories as $category): ?>
                        <div class="category-option" data-category="<?php echo $category['id']; ?>">
                            <div class="category-icon"><?php echo $category['icon']; ?></div>
                            <span class="category-name"><?php echo htmlspecialchars($category['name']); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="category_id" id="categoryId" required>
                </div>

                <!-- Adım 2: Etkinlik Detayları -->
                <div class="form-step" id="step2">
                    <h3>Etkinlik Detayları</h3>
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label for="eventTitle">Etkinlik Adı *</label>
                            <input type="text" name="title" id="eventTitle" required>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="eventDescription">Açıklama *</label>
                            <textarea name="description" id="eventDescription" rows="4" required></textarea>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="shortDescription">Kısa Açıklama</label>
                            <textarea name="short_description" id="shortDescription" rows="2" maxlength="500" placeholder="Etkinlik için kısa bir açıklama (maksimum 500 karakter)"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="eventDate">Etkinlik Tarihi *</label>
                            <input type="date" name="event_date" id="eventDate" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="eventTime">Başlangıç Saati *</label>
                            <input type="time" name="event_time" id="eventTime" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="endDate">Bitiş Tarihi</label>
                            <input type="date" name="end_date" id="endDate">
                        </div>
                        
                        <div class="form-group">
                            <label for="endTime">Bitiş Saati</label>
                            <input type="time" name="end_time" id="endTime">
                        </div>
                        
                        <div class="form-group">
                            <label for="venueName">Mekan Adı *</label>
                            <input type="text" name="venue_name" id="venueName" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="city">Şehir *</label>
                            <input type="text" name="city" id="city" required>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="venueAddress">Mekan Adresi</label>
                            <textarea name="venue_address" id="venueAddress" rows="2"></textarea>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="eventImage">Etkinlik Görseli</label>
                            <input type="file" name="event_image" id="eventImage" accept="image/*">
                            <small>JPG, PNG formatında maksimum 5MB</small>
                        </div>
                    </div>
                </div>

                <!-- Adım 3: Bilet Bilgileri -->
                <div class="form-step" id="step3">
                    <h3>Bilet Bilgileri</h3>
                    <div id="ticketTypes">
                        <div class="ticket-type">
                            <div class="ticket-header">
                                <h4>Bilet Türü 1</h4>
                                <button type="button" class="btn-remove-ticket" onclick="removeTicketType(this)" style="display: none;">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Bilet Adı *</label>
                                    <input type="text" name="ticket_name[]" required placeholder="Örn: Erken Kuş, VIP, Normal">
                                </div>
                                
                                <div class="form-group">
                                    <label>Fiyat (₺) *</label>
                                    <input type="number" name="ticket_price[]" required min="0" step="0.01">
                                </div>
                                
                                <div class="form-group">
                                    <label>İndirimli Fiyat (₺)</label>
                                    <input type="number" name="ticket_discount_price[]" min="0" step="0.01">
                                </div>
                                
                                <div class="form-group">
                                    <label>Adet *</label>
                                    <input type="number" name="ticket_quantity[]" required min="1">
                                </div>
                                
                                <div class="form-group">
                                    <label>Kişi Başı Max Adet</label>
                                    <input type="number" name="ticket_max_per_order[]" min="1" value="10">
                                </div>
                                
                                <div class="form-group">
                                    <label>Satış Başlangıç</label>
                                    <input type="datetime-local" name="ticket_sale_start[]">
                                </div>
                                
                                <div class="form-group full-width">
                                    <label>Bilet Açıklaması</label>
                                    <textarea name="ticket_description[]" rows="2" placeholder="Bu bilet türü hakkında açıklama"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="button" class="btn-add-ticket" onclick="addTicketType()">
                        <i class="fas fa-plus"></i>
                        Yeni Bilet Türü Ekle
                    </button>
                </div>

                <!-- Adım 4: İletişim Bilgileri -->
                <div class="form-step" id="step4">
                    <h3>İletişim Bilgileri</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="contactPhone">Telefon Numarası</label>
                            <input type="tel" name="contact_phone" id="contactPhone">
                        </div>
                        
                        <div class="form-group">
                            <label for="contactEmail">E-posta Adresi</label>
                            <input type="email" name="contact_email" id="contactEmail">
                        </div>
                        
                        <div class="form-group">
                            <label for="instagramLink">Instagram</label>
                            <input type="url" name="instagram_link" id="instagramLink" placeholder="https://instagram.com/...">
                        </div>
                        
                        <div class="form-group">
                            <label for="twitterLink">Twitter</label>
                            <input type="url" name="twitter_link" id="twitterLink" placeholder="https://twitter.com/...">
                        </div>
                        
                        <div class="form-group">
                            <label for="facebookLink">Facebook</label>
                            <input type="url" name="facebook_link" id="facebookLink" placeholder="https://facebook.com/...">
                        </div>
                        
                        <div class="form-group">
                            <label for="websiteLink">Website</label>
                            <input type="url" name="website_link" id="websiteLink" placeholder="https://...">
                        </div>
                    </div>
                </div>

                <!-- Adım 5: Sanatçılar ve Etiketler -->
                <div class="form-step" id="step5">
                    <h3>Sanatçılar ve Etiketler</h3>
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label for="artists">Sanatçılar</label>
                            <input type="text" name="artists" id="artists" placeholder="Sanatçı adlarını virgülle ayırın">
                            <small>Örnek: Sezen Aksu, Tarkan, Ajda Pekkan</small>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="tags">Etiketler (SEO)</label>
                            <input type="text" name="tags" id="tags" placeholder="Etiketleri virgülle ayırın">
                            <small>Arama motorlarında bulunabilmek için etiketler ekleyin</small>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="metaDescription">Meta Açıklama (SEO)</label>
                            <textarea name="meta_description" id="metaDescription" rows="3" maxlength="160" placeholder="Arama motorlarında görünecek açıklama (maksimum 160 karakter)"></textarea>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <div class="step-navigation">
                    <button type="button" class="btn-secondary" id="prevBtn" onclick="previousStep()" style="display: none;">
                        <i class="fas fa-arrow-left"></i>
                        Önceki
                    </button>
                    
                    <div class="step-indicators">
                        <span class="step-indicator active" data-step="1">1</span>
                        <span class="step-indicator" data-step="2">2</span>
                        <span class="step-indicator" data-step="3">3</span>
                        <span class="step-indicator" data-step="4">4</span>
                        <span class="step-indicator" data-step="5">5</span>
                    </div>
                    
                    <button type="button" class="btn-primary" id="nextBtn" onclick="nextStep()">
                        Sonraki
                        <i class="fas fa-arrow-right"></i>
                    </button>
                    
                    <button type="submit" class="btn-success" id="submitBtn" style="display: none;">
                        <i class="fas fa-save"></i>
                        Etkinliği Kaydet
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
/* Etkinlik Sayfası Stilleri */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.page-title-section h1 {
    color: white;
    font-size: 2rem;
    margin: 0 0 0.5rem 0;
    font-weight: 600;
}

.page-subtitle {
    color: rgba(255, 255, 255, 0.7);
    margin: 0;
    font-size: 0.9rem;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
}

/* İstatistik Kartları */
.stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card .stat-icon.total {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.stat-card .stat-icon.published {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}

.stat-card .stat-icon.draft {
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
}

/* Etkinlik Bölümü */
.events-section {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 15px;
    padding: 1.5rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.section-header h2 {
    color: white;
    margin: 0;
    font-size: 1.3rem;
}

.filter-buttons {
    display: flex;
    gap: 0.5rem;
}

.filter-btn {
    background: rgba(255, 255, 255, 0.1);
    color: rgba(255, 255, 255, 0.7);
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 0.9rem;
}

.filter-btn.active,
.filter-btn:hover {
    background: rgba(255, 255, 255, 0.2);
    color: white;
}

/* Boş Durum */
.empty-state {
    text-align: center;
    padding: 3rem;
    color: rgba(255, 255, 255, 0.7);
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.empty-state h3 {
    color: white;
    margin-bottom: 0.5rem;
}

/* Etkinlik Grid */
.events-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
}

.event-card {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s ease;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.event-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
}

.event-image {
    height: 200px;
    background-size: cover;
    background-position: center;
    position: relative;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 1rem;
}

.event-status {
    background: rgba(0, 0, 0, 0.7);
    color: white;
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.event-status.status-published {
    background: rgba(67, 233, 123, 0.9);
}

.event-status.status-draft {
    background: rgba(250, 112, 154, 0.9);
}

.event-status.status-cancelled {
    background: rgba(255, 107, 107, 0.9);
}

.event-actions {
    display: flex;
    gap: 0.5rem;
}

.action-btn {
    background: rgba(255, 255, 255, 0.9);
    color: #333;
    border: none;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.action-btn:hover {
    background: white;
    transform: scale(1.1);
}

.event-content {
    padding: 1.5rem;
}

.event-title {
    color: white;
    font-size: 1.2rem;
    font-weight: 600;
    margin: 0 0 0.5rem 0;
    line-height: 1.3;
}

.event-category {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.9rem;
    margin: 0 0 0.8rem 0;
    text-transform: uppercase;
    font-weight: 500;
}

.event-venue,
.event-date {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.9rem;
    margin: 0 0 0.5rem 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.event-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.event-price {
    color: #667eea;
    font-size: 1.1rem;
    font-weight: 600;
}

.btn-view {
    background: rgba(255, 255, 255, 0.1);
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
}

.btn-view:hover {
    background: rgba(255, 255, 255, 0.2);
}

/* Modal Stilleri */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 1000;
    padding: 1rem;
}

.modal-overlay.active {
    display: flex;
}

.modal-container {
    background: #1a1a2e;
    border-radius: 15px;
    width: 100%;
    max-width: 800px;
    max-height: 90vh;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.modal-header h2 {
    color: white;
    margin: 0;
    font-size: 1.5rem;
}

.modal-close {
    background: none;
    border: none;
    color: rgba(255, 255, 255, 0.7);
    font-size: 1.5rem;
    cursor: pointer;
    transition: color 0.3s ease;
}

.modal-close:hover {
    color: white;
}

.modal-content {
    padding: 1.5rem;
    max-height: 60vh;
    overflow-y: auto;
}

.form-step {
    display: none;
}

.form-step.active {
    display: block;
}

.form-step h3 {
    color: white;
    margin: 0 0 1.5rem 0;
    font-size: 1.3rem;
}

/* Kategori Seçimi */
.category-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
}

.category-option {
    background: rgba(255, 255, 255, 0.05);
    border: 2px solid rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    padding: 1.5rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
}

.category-option:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.3);
}

.category-option.selected {
    background: rgba(102, 126, 234, 0.2);
    border-color: #667eea;
}

.category-icon {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.category-name {
    color: white;
    font-weight: 500;
}

/* Form Stilleri */
.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-group label {
    color: white;
    margin-bottom: 0.5rem;
    font-weight: 500;
    font-size: 0.9rem;
}

.form-group input,
.form-group textarea,
.form-group select {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    padding: 0.75rem;
    color: white;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    outline: none;
    border-color: #667eea;
    background: rgba(255, 255, 255, 0.15);
}

.form-group input::placeholder,
.form-group textarea::placeholder {
    color: rgba(255, 255, 255, 0.5);
}

.form-group small {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.8rem;
    margin-top: 0.3rem;
}

/* Bilet Türleri */
.ticket-type {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 10px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.ticket-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.ticket-header h4 {
    color: white;
    margin: 0;
    font-size: 1.1rem;
}

.btn-remove-ticket {
    background: rgba(255, 107, 107, 0.2);
    color: #ff6b6b;
    border: none;
    padding: 0.5rem;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-remove-ticket:hover {
    background: rgba(255, 107, 107, 0.3);
}

.btn-add-ticket {
    background: rgba(67, 233, 123, 0.2);
    color: #43e97b;
    border: 1px solid rgba(67, 233, 123, 0.3);
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 500;
}

.btn-add-ticket:hover {
    background: rgba(67, 233, 123, 0.3);
}

/* Modal Footer */
.modal-footer {
    padding: 1.5rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.step-navigation {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.step-indicators {
    display: flex;
    gap: 0.5rem;
}

.step-indicator {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.1);
    color: rgba(255, 255, 255, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.step-indicator.active {
    background: #667eea;
    color: white;
}

.step-indicator.completed {
    background: #43e97b;
    color: white;
}

.btn-secondary {
    background: rgba(255, 255, 255, 0.1);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.2);
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 500;
}

.btn-secondary:hover {
    background: rgba(255, 255, 255, 0.2);
}

.btn-success {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
}

.btn-success:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(67, 233, 123, 0.3);
}

/* Responsive */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }
    
    .stats-row {
        grid-template-columns: 1fr;
    }
    
    .events-grid {
        grid-template-columns: 1fr;
    }
    
    .modal-container {
        margin: 0.5rem;
        max-height: 95vh;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .category-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .step-navigation {
        flex-direction: column;
        gap: 1rem;
    }
}
</style>

<script>
// Modal ve Form Yönetimi
let currentStep = 1;
let totalSteps = 5;
let ticketTypeCount = 1;

function openEventModal() {
    document.getElementById('eventModal').classList.add('active');
    document.body.style.overflow = 'hidden';
    resetForm();
}

function closeEventModal() {
    document.getElementById('eventModal').classList.remove('active');
    document.body.style.overflow = 'auto';
}

function resetForm() {
    currentStep = 1;
    document.getElementById('eventForm').reset();
    showStep(1);
    updateStepIndicators();
    
    // Kategori seçimini temizle
    document.querySelectorAll('.category-option').forEach(option => {
        option.classList.remove('selected');
    });
    
    // Bilet türlerini sıfırla
    const ticketTypes = document.getElementById('ticketTypes');
    ticketTypes.innerHTML = getInitialTicketType();
    ticketTypeCount = 1;
}

function showStep(step) {
    // Tüm adımları gizle
    document.querySelectorAll('.form-step').forEach(stepEl => {
        stepEl.classList.remove('active');
    });
    
    // Aktif adımı göster
    document.getElementById('step' + step).classList.add('active');
    
    // Buton durumlarını güncelle
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');
    
    prevBtn.style.display = step === 1 ? 'none' : 'flex';
    nextBtn.style.display = step === totalSteps ? 'none' : 'flex';
    submitBtn.style.display = step === totalSteps ? 'flex' : 'none';
}

function updateStepIndicators() {
    document.querySelectorAll('.step-indicator').forEach((indicator, index) => {
        const stepNum = index + 1;
        indicator.classList.remove('active', 'completed');
        
        if (stepNum < currentStep) {
            indicator.classList.add('completed');
        } else if (stepNum === currentStep) {
            indicator.classList.add('active');
        }
    });
}

function nextStep() {
    if (validateCurrentStep()) {
        if (currentStep < totalSteps) {
            currentStep++;
            showStep(currentStep);
            updateStepIndicators();
        }
    }
}

function previousStep() {
    if (currentStep > 1) {
        currentStep--;
        showStep(currentStep);
        updateStepIndicators();
    }
}

function validateCurrentStep() {
    const currentStepEl = document.getElementById('step' + currentStep);
    
    switch(currentStep) {
        case 1:
            // Kategori seçimi kontrolü
            const selectedCategory = document.querySelector('.category-option.selected');
            if (!selectedCategory) {
                alert('Lütfen bir kategori seçin.');
                return false;
            }
            break;
            
        case 2:
            // Zorunlu alanları kontrol et
            const requiredFields = currentStepEl.querySelectorAll('input[required], textarea[required]');
            for (let field of requiredFields) {
                if (!field.value.trim()) {
                    alert('Lütfen tüm zorunlu alanları doldurun.');
                    field.focus();
                    return false;
                }
            }
            break;
            
        case 3:
            // Bilet bilgilerini kontrol et
            const ticketNames = document.querySelectorAll('input[name="ticket_name[]"]');
            const ticketPrices = document.querySelectorAll('input[name="ticket_price[]"]');
            const ticketQuantities = document.querySelectorAll('input[name="ticket_quantity[]"]');
            
            for (let i = 0; i < ticketNames.length; i++) {
                if (!ticketNames[i].value.trim() || !ticketPrices[i].value || !ticketQuantities[i].value) {
                    alert('Lütfen tüm bilet türleri için gerekli bilgileri doldurun.');
                    return false;
                }
            }
            break;
    }
    
    return true;
}

// Kategori Seçimi
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.category-option').forEach(option => {
        option.addEventListener('click', function() {
            // Diğer seçimleri temizle
            document.querySelectorAll('.category-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            
            // Bu seçimi aktif yap
            this.classList.add('selected');
            
            // Hidden input'a değeri ata
            document.getElementById('categoryId').value = this.dataset.category;
        });
    });
});

// Bilet Türü Yönetimi
function addTicketType() {
    ticketTypeCount++;
    const ticketTypes = document.getElementById('ticketTypes');
    const newTicketType = document.createElement('div');
    newTicketType.className = 'ticket-type';
    newTicketType.innerHTML = `
        <div class="ticket-header">
            <h4>Bilet Türü ${ticketTypeCount}</h4>
            <button type="button" class="btn-remove-ticket" onclick="removeTicketType(this)">
                <i class="fas fa-trash"></i>
            </button>
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label>Bilet Adı *</label>
                <input type="text" name="ticket_name[]" required placeholder="Örn: Erken Kuş, VIP, Normal">
            </div>
            
            <div class="form-group">
                <label>Fiyat (₺) *</label>
                <input type="number" name="ticket_price[]" required min="0" step="0.01">
            </div>
            
            <div class="form-group">
                <label>İndirimli Fiyat (₺)</label>
                <input type="number" name="ticket_discount_price[]" min="0" step="0.01">
            </div>
            
            <div class="form-group">
                <label>Adet *</label>
                <input type="number" name="ticket_quantity[]" required min="1">
            </div>
            
            <div class="form-group">
                <label>Kişi Başı Max Adet</label>
                <input type="number" name="ticket_max_per_order[]" min="1" value="10">
            </div>
            
            <div class="form-group">
                <label>Satış Başlangıç</label>
                <input type="datetime-local" name="ticket_sale_start[]">
            </div>
            
            <div class="form-group full-width">
                <label>Bilet Açıklaması</label>
                <textarea name="ticket_description[]" rows="2" placeholder="Bu bilet türü hakkında açıklama"></textarea>
            </div>
        </div>
    `;
    
    ticketTypes.appendChild(newTicketType);
    
    // İlk bilet türünün silme butonunu göster
    const firstRemoveBtn = document.querySelector('.btn-remove-ticket');
    if (firstRemoveBtn) {
        firstRemoveBtn.style.display = 'flex';
    }
}

function removeTicketType(button) {
    const ticketType = button.closest('.ticket-type');
    ticketType.remove();
    ticketTypeCount--;
    
    // Bilet türü numaralarını güncelle
    document.querySelectorAll('.ticket-type').forEach((type, index) => {
        const header = type.querySelector('.ticket-header h4');
        header.textContent = `Bilet Türü ${index + 1}`;
    });
    
    // Eğer sadece bir bilet türü kaldıysa, silme butonunu gizle
    const remainingTypes = document.querySelectorAll('.ticket-type');
    if (remainingTypes.length === 1) {
        const removeBtn = remainingTypes[0].querySelector('.btn-remove-ticket');
        if (removeBtn) {
            removeBtn.style.display = 'none';
        }
    }
}

function getInitialTicketType() {
    return `
        <div class="ticket-type">
            <div class="ticket-header">
                <h4>Bilet Türü 1</h4>
                <button type="button" class="btn-remove-ticket" onclick="removeTicketType(this)" style="display: none;">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label>Bilet Adı *</label>
                    <input type="text" name="ticket_name[]" required placeholder="Örn: Erken Kuş, VIP, Normal">
                </div>
                
                <div class="form-group">
                    <label>Fiyat (₺) *</label>
                    <input type="number" name="ticket_price[]" required min="0" step="0.01">
                </div>
                
                <div class="form-group">
                    <label>İndirimli Fiyat (₺)</label>
                    <input type="number" name="ticket_discount_price[]" min="0" step="0.01">
                </div>
                
                <div class="form-group">
                    <label>Adet *</label>
                    <input type="number" name="ticket_quantity[]" required min="1">
                </div>
                
                <div class="form-group">
                    <label>Kişi Başı Max Adet</label>
                    <input type="number" name="ticket_max_per_order[]" min="1" value="10">
                </div>
                
                <div class="form-group">
                    <label>Satış Başlangıç</label>
                    <input type="datetime-local" name="ticket_sale_start[]">
                </div>
                
                <div class="form-group full-width">
                    <label>Bilet Açıklaması</label>
                    <textarea name="ticket_description[]" rows="2" placeholder="Bu bilet türü hakkında açıklama"></textarea>
                </div>
            </div>
        </div>
    `;
}

// Etkinlik Filtreleme
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        // Aktif butonu güncelle
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        const filter = this.dataset.filter;
        const eventCards = document.querySelectorAll('.event-card');
        
        eventCards.forEach(card => {
            if (filter === 'all' || card.dataset.status === filter) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    });
});

// Arama Fonksiyonu
document.getElementById('eventSearch').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const eventCards = document.querySelectorAll('.event-card');
    
    eventCards.forEach(card => {
        const title = card.querySelector('.event-title').textContent.toLowerCase();
        const venue = card.querySelector('.event-venue').textContent.toLowerCase();
        
        if (title.includes(searchTerm) || venue.includes(searchTerm)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
});

// Etkinlik İşlemleri
function editEvent(eventId) {
    // Etkinlik düzenleme modalını aç
    console.log('Etkinlik düzenle:', eventId);
    // TODO: Etkinlik verilerini yükle ve modalı aç
}

function deleteEvent(eventId) {
    if (confirm('Bu etkinliği silmek istediğinizden emin misiniz?')) {
        // TODO: Etkinlik silme işlemi
        console.log('Etkinlik sil:', eventId);
    }
}

function viewEvent(eventId) {
    // Etkinlik detay sayfasına git
    window.open('../etkinlik-detay.php?id=' + eventId, '_blank');
}

// Form Gönderimi
document.getElementById('eventForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (!validateCurrentStep()) {
        return;
    }
    
    // Form verilerini topla
    const formData = new FormData(this);
    
    // Submit butonunu devre dışı bırak
    const submitBtn = document.getElementById('submitBtn');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Kaydediliyor...';
    
    // AJAX ile form gönderimi
    fetch('create_event.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeEventModal();
            alert('Etkinlik başarıyla oluşturuldu!');
            // Sayfayı yenile
            window.location.reload();
        } else {
            alert('Hata: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Bir hata oluştu. Lütfen tekrar deneyin.');
    })
    .finally(() => {
        // Submit butonunu tekrar aktif et
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
});

// Sidebar Navigation
document.querySelectorAll('.nav-icon').forEach(icon => {
    icon.addEventListener('click', function() {
        document.querySelectorAll('.nav-icon').forEach(i => i.classList.remove('active'));
        this.classList.add('active');
    });
});

// Logout functionality
document.querySelector('.sidebar-logout').addEventListener('click', function() {
    if (confirm('Çıkış yapmak istediğinizden emin misiniz?')) {
        window.location.href = '../auth/logout.php';
    }
});

// Modal dışına tıklayınca kapat
document.getElementById('eventModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEventModal();
    }
});

// ESC tuşu ile modalı kapat
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeEventModal();
    }
});
</script>

</body>
</html>