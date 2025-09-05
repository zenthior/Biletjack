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

// Debug: image_url değerlerini kontrol et
foreach ($organizerEvents as &$evt) {
    if (empty($evt['image_url'])) {
        $evt['image_url'] = null;
    }
}

// Kategorileri getir
$categories = $event->getCategories();

// İstatistikler
$totalEvents = count($organizerEvents);
$publishedEvents = count(array_filter($organizerEvents, function($e) { return $e['status'] === 'published'; }));
$draftEvents = count(array_filter($organizerEvents, function($e) { return $e['status'] === 'draft'; }));

include 'includes/header.php';
?>

<!-- Floating Toggle Button -->
<div class="floating-toggle" id="floatingToggle" onclick="toggleSidebar()">
    <i class="fas fa-ticket-alt"></i>
</div>

<!-- Sol Sidebar -->
<div class="modern-sidebar" id="sidebar">
    <div class="sidebar-logo" onclick="toggleSidebar()" style="cursor: pointer;" id="sidebarLogo">
        <i class="fas fa-ticket-alt"></i>
    </div>
    
    <div class="sidebar-nav">
        <div class="nav-icon" title="Ana Sayfa" onclick="window.location.href='./index.php'" style="cursor: pointer;">
            <i class="fas fa-home"></i>
        </div>
        <div class="nav-icon active" title="Etkinlikler">
            <i class="fas fa-calendar-alt"></i>
        </div>
        <div class="nav-icon" title="QR Yetkili" onclick="loadQRStaffPage()" style="cursor: pointer;">
            <i class="fas fa-qrcode"></i>
        </div>
        <div class="nav-icon" title="Analitik" onclick="window.location.href='./index.php'" style="cursor: pointer;">
            <i class="fas fa-chart-bar"></i>
        </div>
        <div class="nav-icon" title="Ayarlar" onclick="window.location.href='./settings.php'" style="cursor: pointer;">
            <i class="fas fa-cog"></i>
        </div>
        <!-- Ana sayfaya dön butonu (yeni) -->
        <div class="nav-icon" title="Ana Sayfaya Dön" onclick="window.location.href='../index.php'" style="cursor: pointer;">
            <i class="fas fa-arrow-left"></i>
        </div>
        <div class="nav-icon" title="Çıkış" onclick="window.location.href='../auth/logout.php'" style="cursor: pointer;">
            <i class="fas fa-sign-out-alt"></i>
        </div>
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
    <div class="dashboard-content" id="main-content">
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
            <div class="no-events">
                <i class="fas fa-calendar-plus"></i>
                <h3>Henüz etkinlik oluşturmadınız</h3>
                <p>İlk etkinliğinizi oluşturmak için "Yeni Etkinlik" butonuna tıklayın.</p>
                <button class="btn-primary" onclick="openEventModal()">
                    <i class="fas fa-plus"></i>
                    İlk Etkinliğimi Oluştur
                </button>
            </div>
            <?php else: ?>
            <div class="events-grid">
                <!-- Etkinlikler AJAX ile yüklenecek -->
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Koltuk Yönetimi Modalı - 3 Aşamalı -->
<div class="modal-overlay" id="seatManagementModal">
    <div class="modal-container seat-management-container">
        <div class="modal-header">
            <h2>Koltuk Düzeni ve Fiyatlandırma</h2>
            <div class="seat-step-indicators">
                <span class="seat-step-indicator active" data-step="1">1</span>
                <span class="seat-step-indicator" data-step="2">2</span>
                <span class="seat-step-indicator" data-step="3">3</span>
            </div>
            <button class="modal-close" onclick="closeSeatManagementModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="modal-content">
            <!-- Aşama 1: Bilet Kategorileri ve Fiyatlandırma -->
            <div class="seat-step active" id="seatStep1">
                <h3>Aşama 1: Bilet Kategorileri ve Fiyatlandırma</h3>
                <div class="categories-header">
                    <p id="categoryDescription">Önce bilet kategorilerinizi oluşturun, renklerini ve fiyatlarını belirleyin.</p>
                    <button type="button" class="btn-primary" onclick="addSeatCategory()">
                        <i class="fas fa-plus"></i>
                        Kategori Ekle
                    </button>
                </div>
                
                <div class="seat-categories" id="seatCategories">
                    <div class="seat-category" data-category="standard">
                        <div class="category-header">
                            <div class="category-color" style="background-color: #4CAF50;" onclick="openColorPicker(this)"></div>
                            <input type="text" class="category-name" value="Standart" placeholder="Kategori Adı">
                            <input type="number" class="category-price" value="100" min="0" step="0.01" placeholder="Fiyat">
                            <span class="currency">₺</span>
                            <button type="button" class="btn-remove-category" onclick="removeSeatCategory(this)" style="display: none;">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        <div class="category-description">
                            <textarea class="category-desc" placeholder="Kategori açıklaması..."></textarea>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Aşama 2: Koltuk Düzeni Oluşturma -->
            <div class="seat-step" id="seatStep2">
                <h3>Aşama 2: Koltuk Düzeni Oluşturma</h3>
                <div class="layout-controls">
                    <div class="control-group">
                        <label>Sıra Sayısı:</label>
                        <input type="number" id="rowCount" min="1" max="50" value="10">
                    </div>
                    <div class="control-group">
                        <label>Sıra Başına Koltuk:</label>
                        <input type="number" id="seatPerRow" min="1" max="50" value="20">
                    </div>
                    <div class="control-group">
                        <label>Salon Adı:</label>
                        <input type="text" id="venueLayoutName" placeholder="Ana Salon">
                    </div>
                    <button type="button" class="btn-secondary" onclick="generateSeatingLayout()">
                        <i class="fas fa-magic"></i>
                        Koltuk Düzeni Oluştur
                    </button>
                </div>
                
                <div class="seating-layout-container">
                    <div class="stage-indicator">
                        <i class="fas fa-music"></i>
                        SAHNE
                    </div>
                    <div class="seating-grid" id="seatingGrid">
                        <!-- Koltuklar buraya dinamik olarak eklenecek -->
                    </div>
                </div>
                
                <div class="seat-assignment-info">
                    <p><strong>Koltuk Atama:</strong> Koltukları tıklayarak farklı kategorilere atayın. İlk tıklama standart (yeşil), sonraki tıklamalar diğer kategorilere geçer.</p>
                </div>
            </div>
            
            <!-- Aşama 3: Önizleme -->
            <div class="seat-step" id="seatStep3">
                <h3>Aşama 3: Önizleme</h3>
                <div class="preview-stats">
                    <div class="stat-item">
                        <span class="stat-label">Toplam Koltuk:</span>
                        <span class="stat-value" id="previewTotalSeats">0</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Kategori Sayısı:</span>
                        <span class="stat-value" id="previewCategories">0</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Ortalama Fiyat:</span>
                        <span class="stat-value" id="previewAvgPrice">₺0</span>
                    </div>
                </div>
                
                <div class="preview-seating">
                    <div class="stage-indicator">
                        <i class="fas fa-music"></i>
                        SAHNE
                    </div>
                    <div class="seating-preview" id="seatingPreview">
                        <!-- Önizleme buraya gelecek -->
                    </div>
                </div>
                
                <div class="category-legend" id="categoryLegend">
                    <!-- Kategori renkleri buraya gelecek -->
                </div>
            </div>
        </div>
        
        <div class="modal-footer">
            <button type="button" class="btn-secondary" id="seatPrevBtn" onclick="previousSeatStep()" style="display: none;">
                <i class="fas fa-arrow-left"></i>
                Önceki
            </button>
            <button type="button" class="btn-primary" id="seatNextBtn" onclick="nextSeatStep()">
                İlerle
                <i class="fas fa-arrow-right"></i>
            </button>
            <button type="button" class="btn-success" id="seatSaveBtn" onclick="saveSeatConfiguration()" style="display: none;">
                <i class="fas fa-save"></i>
                Koltuk Düzenini Kaydet
            </button>
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
                    <div class="category-list">
                        <?php foreach ($categories as $category): ?>
                        <div class="category-option" data-category="<?php echo $category['id']; ?>">
                            <div class="category-icon">
                                <?php 
                                $iconMap = [
                                    'Konser' => 'music.svg',
                                    'Tiyatro' => 'tiyatro.svg',
                                    'Festival' => 'festival.svg',
                                    'Çocuk' => 'cocuk.svg',
                                    'Stand-up' => 'standup.svg'
                                ];
                                $iconFile = isset($iconMap[$category['name']]) ? $iconMap[$category['name']] : 'music.svg';
                                ?>
                                <img src="../SVG/<?php echo $iconFile; ?>" alt="<?php echo htmlspecialchars($category['name']); ?>" style="width: 20px; height: 20px;">
                            </div>
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
                            <label for="eventDateTime">Etkinlik Başlangıç Tarihi ve Saati *</label>
                            <input type="datetime-local" name="event_datetime" id="eventDateTime" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="endDateTime">Etkinlik Bitiş Tarihi ve Saati</label>
                            <input type="datetime-local" name="end_datetime" id="endDateTime">
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

                <!-- Adım 3: Koltuk Sistemi Seçimi -->
                <div class="form-step" id="step3">
                    <h3>Bilet Sistemi</h3>
                    <div class="seating-system-selection">
                        <p class="selection-description">Etkinliğiniz için uygun sistemi seçin:</p>
                        
                        <div class="seating-options">
                            <div class="seating-option" data-type="general">
                                <div class="option-icon">
                                    <i class="fas fa-ticket-alt"></i>
                                </div>
                                <h4>Biletli Sistem</h4>
                                <p>Geleneksel bilet satışı. Müşteriler sadece bilet türü seçer, koltuk seçimi yoktur.</p>
                                <div class="option-features">
                                    <span class="feature">✓ Hızlı satış</span>
                                    <span class="feature">✓ Basit yönetim</span>
                                    <span class="feature">✓ Genel giriş</span>
                                </div>
                            </div>
                            
                            <div class="seating-option" data-type="seated">
                                <div class="option-icon">
                                    <i class="fas fa-chair"></i>
                                </div>
                                <h4>Koltuklu Sistem</h4>
                                <p>Müşteriler koltuk seçimi yapabilir. Koltuk haritası ve özel fiyatlandırma imkanı.</p>
                                <div class="option-features">
                                    <span class="feature">✓ Koltuk seçimi</span>
                                    <span class="feature">✓ Özel fiyatlandırma</span>
                                    <span class="feature">✓ Detaylı kontrol</span>
                                </div>
                            </div>
                            
                            <div class="seating-option" data-type="reservation">
                                <div class="option-icon">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <h4>Rezervasyon Sistemi</h4>
                                <p>Ücretsiz rezervasyon sistemi. Müşteriler rezervasyon yapar, organizatör onaylar.</p>
                                <div class="option-features">
                                    <span class="feature">✓ Ücretsiz rezervasyon</span>
                                    <span class="feature">✓ Organizatör onayı</span>
                                    <span class="feature">✓ Esnek yönetim</span>
                                </div>
                            </div>
                        </div>
                        
                        <input type="hidden" name="seating_type" id="seatingType" value="general">
                        
                        <div class="seating-config" id="seatingConfig" style="display: none;">
                            <div class="seating-config-header">
                                <h4>Koltuk Düzeni Ayarları</h4>
                                <button type="button" class="btn-primary" id="manageSeatingsBtn" onclick="openSeatManagementModal()">
                                    <i class="fas fa-chair"></i>
                                    Koltukları Düzenle
                                </button>
                            </div>
                            
                            <div class="seating-summary" id="seatingSummary">
                                <div class="summary-item">
                                    <span class="label">Toplam Koltuk:</span>
                                    <span class="value" id="totalSeatsCount">0</span>
                                </div>
                                <div class="summary-item">
                                    <span class="label">Koltuk Kategorileri:</span>
                                    <span class="value" id="seatCategoriesCount">0</span>
                                </div>
                                <div class="summary-item">
                                    <span class="label">Fiyat Aralığı:</span>
                                    <span class="value" id="priceRange">₺0 - ₺0</span>
                                </div>
                            </div>
                            
                            <div class="info-box">
                                <i class="fas fa-info-circle"></i>
                                <p>Koltuk haritasını ve fiyatlandırmayı "Koltukları Düzenle" butonuna tıklayarak ayarlayın. Koltuklu sistem seçildiğinde bilet türleri adımı atlanacaktır.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Adım 4: Bilet Bilgileri -->
                <div class="form-step" id="step4">
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

                    <!-- İndirim Kodları Bölümü KALDIRILDI -->
                </div>

                <!-- Adım 5: Sanatçılar ve Etiketler -->
                <div class="form-step" id="step5">
                    <h3>Sanatçılar ve Etiketler</h3>

                    <!-- İndirim Kodları Bölümü (5. Adıma TAŞINDI) -->
                    <div class="discount-codes-section" style="margin-top: 2rem;">
                        <h3>İndirim Kodları</h3>
                        <div id="discountCodesContainer"></div>
                        <button type="button" class="btn-add-ticket" onclick="addDiscountCodeRow()">
                            <i class="fas fa-plus"></i> İndirim Kodu Ekle
                        </button>
                    </div>

                    <!-- Stüdyoculara Teklif Ver ve Reklam Ajansı ile Anlaş -->
                    <div class="partner-section" style="margin-top: 2rem;">
                        <h3>İş Ortakları</h3>

                        <div class="form-group full-width" style="margin-top: 1rem;">
                            <label style="display:flex;align-items:center;gap:8px;">
                                <input type="checkbox" id="toggleStudios">
                                Stüdyoculara teklif ver (ses/ışık)
                            </label>
                            <small>Bu seçeneği açtığınızda, etkinlik şehrinizdeki stüdyolar listelenir. Anlaştığınız kişiyi seçin.</small>

                            <div id="studiosContainer" style="display:none;margin-top:12px;">
                                <div id="studiosList" class="partner-list" style="display:flex;flex-direction:column;gap:8px;"></div>
                                <input type="hidden" name="selected_service_provider_user_id" id="selectedServiceProviderUserId" value="">
                            </div>
                        </div>

                        <div class="form-group full-width" style="margin-top: 1.5rem;">
                            <label style="display:flex;align-items:center;gap:8px;">
                                <input type="checkbox" id="toggleAdAgency">
                                Reklam ajansı ile anlaş
                            </label>
                            <small>Bu seçeneği açtığınızda, etkinlik şehrinizdeki reklam ajansları listelenir. Anlaştığınız ajansı seçin.</small>

                            <div id="adAgenciesContainer" style="display:none;margin-top:12px;">
                                <div id="adAgenciesList" class="partner-list" style="display:flex;flex-direction:column;gap:8px;"></div>
                                <input type="hidden" name="selected_ad_agency_user_id" id="selectedAdAgencyUserId" value="">
                            </div>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label for="artists">Sanatçılar</label>
                            <input type="text" name="artists" id="artists" placeholder="Sanatçı adlarını virgülle ayırın">
                            <small>Örnek: Sezen Aksu, Tarkan, Ajda Pekkan</small>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="artistImage">Sanatçı Görseli</label>
                            <input type="file" name="artist_image" id="artistImage" accept="image/*">
                            <small>Sanatçı veya grup fotoğrafı (JPG, PNG formatında maksimum 5MB)</small>
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
                        
                        <div class="form-group full-width">
                            <label for="eventRules">Etkinlik Kuralları</label>
                            <textarea name="event_rules" id="eventRules" rows="6" placeholder="Etkinlik kurallarını buraya yazın. Her kural için yeni satır kullanın."></textarea>
                            <small>Bilet satın alma sırasında gösterilecek kuralları yazın</small>
                        </div>
                    </div>
                </div>

                <!-- Adım 6: İletişim Bilgileri -->
                <div class="form-step" id="step6">
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
    height: 50px;
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

/* Aksiyon Butonları */
.action-btn.publish-btn {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}

.action-btn.publish-btn:hover {
    background: linear-gradient(135deg, #38d66a 0%, #2de6c6 100%);
}

.action-btn.draft-btn {
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
}

.action-btn.draft-btn:hover {
    background: linear-gradient(135deg, #f95d89 0%, #fed12f 100%);
}

.action-btn.delete-btn {
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
}

.action-btn.delete-btn:hover {
    background: linear-gradient(135deg, #ff5252 0%, #e53935 100%);
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
    background-size: contain;
    background-position: center;
    background-repeat: no-repeat;
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
    color: #000000;
    font-size: 1.2rem;
    font-weight: 600;
    margin: 0 0 0.5rem 0;
    line-height: 1.3;
}

.event-category {
    color: rgba(9, 80, 2, 0.6);
    font-size: 0.9rem;
    margin: 0 0 0.8rem 0;
    text-transform: uppercase;
    font-weight: 500;
}

.event-description {
    color: rgba(0, 0, 0, 0.7);
    font-size: 0.85rem;
    margin: 0 0 1rem 0;
    line-height: 1.4;
    font-style: italic;
}

.event-venue,
.event-date {
    color: rgba(0, 0, 0, 0.8);
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

.event-buttons {
    display: flex;
    gap: 0.5rem;
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

.btn-publish {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
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
    font-weight: 500;
}

.btn-publish:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(67, 233, 123, 0.4);
}

/* Koltuk Sistemi Seçimi Stilleri */
.seating-system-selection {
    text-align: center;
}

.selection-description {
    color: rgba(255, 255, 255, 0.8);
    margin-bottom: 2rem;
    font-size: 1.1rem;
}

.seating-options {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.seating-option {
    background: rgba(255, 255, 255, 0.05);
    border: 2px solid rgba(255, 255, 255, 0.1);
    border-radius: 15px;
    padding: 2rem;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
}

.seating-option:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.3);
}

.seating-option.selected {
    background: rgba(102, 126, 234, 0.2);
    border-color: #667eea;
}

.option-icon {
    font-size: 3rem;
    color: #667eea;
    margin-bottom: 1rem;
}

.seating-option h4 {
    color: white;
    margin: 0 0 1rem 0;
    font-size: 1.3rem;
}

.seating-option p {
    color: rgba(255, 255, 255, 0.7);
    margin: 0 0 1.5rem 0;
    line-height: 1.4;
}

.option-features {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.feature {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.9rem;
}

.seating-config {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 10px;
    padding: 1.5rem;
    margin-top: 2rem;
}

.seating-config h4 {
    color: white;
    margin: 0 0 1.5rem 0;
    font-size: 1.2rem;
}

.info-box {
    background: rgba(102, 126, 234, 0.1);
    border: 1px solid rgba(102, 126, 234, 0.3);
    border-radius: 8px;
    padding: 1rem;
    margin-top: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.info-box i {
    color: #667eea;
}

.info-box p {
    color: rgba(255, 255, 255, 0.8);
    margin: 0;
    font-size: 0.9rem;
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
.category-list {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin: 20px 0;
    align-items: center;
}

.category-option {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    color: white;
    border-radius: 6px;
    background: transparent;
    border: none;
}

.category-option:hover {
    background: rgba(102, 126, 234, 0.2);
    color: #667eea;
}

.category-option.selected {
    background: rgba(76, 175, 80, 0.2);
    color: #4CAF50;
    font-weight: 600;
}

.category-icon {
    display: flex;
    align-items: center;
}

.category-name {
    color: inherit;
    font-weight: 500;
    font-size: 14px;
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

/* Koltuk Yönetimi Stilleri */
.seating-config-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.seating-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
    padding: 1rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 8px;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.summary-item .label {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.9rem;
}

.summary-item .value {
    color: white;
    font-weight: 600;
}

/* Koltuk Modalı Z-Index */
#seatManagementModal {
    z-index: 1100 !important;
}

.seat-management-container {
    background: #1a1a2e;
    border-radius: 15px;
    width: 100%;
    max-width: 1000px;
    max-height: 90vh;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

/* Koltuk Adım Göstergeleri */
.seat-step-indicators {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.seat-step-indicator {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    color: rgba(255, 255, 255, 0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    transition: all 0.3s ease;
}

.seat-step-indicator.active {
    background: #667eea;
    color: white;
}

.seat-step-indicator.completed {
    background: #43e97b;
    color: white;
}

/* Koltuk Adımları */
.seat-step {
    display: none;
}

.seat-step.active {
    display: block;
}

/* Koltuk Atama Bilgisi */
.seat-assignment-info {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 8px;
    padding: 1rem;
    margin-top: 1rem;
    border-left: 4px solid #667eea;
}

.seat-assignment-info p {
    color: rgba(255, 255, 255, 0.8);
    margin: 0;
    font-size: 0.9rem;
}

.layout-controls {
    display: flex;
    gap: 1rem;
    align-items: end;
    margin-bottom: 2rem;
    padding: 1rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 8px;
}

.control-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.control-group label {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.9rem;
}

.control-group input {
    padding: 0.5rem;
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 4px;
    background: rgba(255, 255, 255, 0.1);
    color: white;
}

.seating-layout-container {
    text-align: center;
}

.stage-indicator {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    font-weight: 600;
}

.seating-grid {
    display: inline-block;
    padding: 2rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 12px;
    max-height: 400px;
    overflow: auto;
}

.seat-row {
    display: flex;
    justify-content: center;
    gap: 4px;
    margin-bottom: 4px;
    align-items: center;
}

.row-label {
    width: 30px;
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.8rem;
    text-align: center;
}

.seat {
    width: 24px;
    height: 24px;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.seat.standard {
    background-color: #4CAF50;
}

.seat.vip {
    background-color: #FF9800;
}

.seat.premium {
    background-color: #9C27B0;
}

.seat.disabled {
    background-color: #666;
    cursor: not-allowed;
}

.seat:hover:not(.disabled) {
    transform: scale(1.1);
    box-shadow: 0 2px 8px rgba(255, 255, 255, 0.3);
}

.seat.selected {
    background-color: #2196F3 !important;
    transform: scale(1.1);
}

.categories-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.seat-category {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
}

.category-header {
    display: flex;
    align-items: center;
    gap: 1rem;
}

/* Renk Seçici */
.category-color {
    width: 24px;
    height: 24px;
    border-radius: 4px;
    cursor: pointer;
    border: 2px solid rgba(255, 255, 255, 0.3);
    transition: all 0.2s ease;
}

.category-color:hover {
    border-color: rgba(255, 255, 255, 0.6);
    transform: scale(1.1);
}

.category-name,
.category-price {
    padding: 0.5rem;
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 4px;
    background: rgba(255, 255, 255, 0.1);
    color: white;
}

.category-name {
    flex: 1;
}

.category-price {
    width: 100px;
}

.currency {
    color: rgba(255, 255, 255, 0.7);
}

/* Rezervasyon sistemi için fiyat alanlarını gizle */
.reservation-mode .category-price,
.reservation-mode .currency {
    display: none;
}

.category-desc {
    width: 100%;
    margin-top: 1rem;
    padding: 0.5rem;
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 4px;
    background: rgba(255, 255, 255, 0.1);
    color: white;
    resize: vertical;
    min-height: 60px;
}

.preview-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
    padding: 1rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 8px;
}

.stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.stat-label {
    color: rgba(255, 255, 255, 0.7);
}

.stat-value {
    color: black;
    font-weight: 600;
}

.category-legend {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-top: 2rem;
    padding: 1rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 8px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.legend-color {
    width: 16px;
    height: 16px;
    border-radius: 2px;
}

.legend-label {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.9rem;
}

/* Responsive */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }
        .dashboard-content {
        background: #3635b1eb;
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

    /* Yeni mobil iyileştirmeler */
    .top-header {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }

    .search-container {
        width: 100%;
    }

    .search-input {
        width: 100%;
    }

    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }

    .filter-buttons {
        width: 100%;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .filter-btn {
        flex: 1 1 calc(50% - 0.5rem);
        text-align: center;
    }

    .event-image {
        height: 160px;
        padding: 0.75rem;
    }

    .event-content {
        padding: 1rem;
    }

    .event-title {
        font-size: 1.05rem;
    }

    .event-description {
        font-size: 0.85rem;
    }

    .event-venue,
    .event-date {
        font-size: 0.85rem;
    }

    .event-actions .action-btn {
        width: 32px;
        height: 32px;
    }

    /* Koltuk yönetimi mobil düzenlemeleri */
    .seat-management-container {
        max-width: 100%;
        margin: 0.5rem;
    }

    .layout-controls {
        flex-direction: column;
        align-items: stretch;
        gap: 0.75rem;
    }

    .seating-grid {
        padding: 1rem;
        max-height: 300px;
    }

    .categories-header {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }
}

/* Mobil uyumluluk için kategori listesi */
@media (max-width: 768px) {
    .category-list {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
        justify-items: center;
    }
    
    .category-option {
        width: 100%;
        justify-content: center;
        padding: 12px 8px;
        text-align: center;
        flex-direction: column;
        gap: 6px;
    }
    
    .category-name {
        font-size: 12px;
    }
    
    /* Sistem seçimi mobil düzenlemesi */
    .seating-options {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .seating-option {
        padding: 1.5rem;
    }
    
    .seating-option h4 {
        font-size: 1.1rem;
    }
    
    .option-icon {
        font-size: 2.5rem;
    }
}

/* Çok dar ekranlar için ek ayarlar */
@media (max-width: 480px) {
    .category-grid {
        grid-template-columns: 1fr;
    }
    
    .category-list {
        grid-template-columns: 1fr;
    }
    
    .category-option {
        padding: 10px;
    }
    
    .category-name {
        font-size: 11px;
    }

    .filter-btn {
        flex: 1 1 100%;
    }

    .event-image {
        height: 140px;
    }

    .seat {
        width: 18px;
        height: 18px;
        font-size: 0.55rem;
    }

    .page-title-section h1 {
        font-size: 1.5rem;
    }
    
    /* Sistem seçimi çok küçük ekranlar için */
    .seating-option {
        padding: 1rem;
    }
    
    .seating-option h4 {
        font-size: 1rem;
    }
    
    .seating-option p {
        font-size: 0.85rem;
    }
    
    .option-icon {
        font-size: 2rem;
    }
}
</style>

<script>
// Modal ve Form Yönetimi
let currentStep = 1;
let totalSteps = 5;
let ticketTypeCount = 1;

function openEventModal(isEdit = false) {
    document.getElementById('eventModal').classList.add('active');
    document.body.style.overflow = 'hidden';
    if (isEdit) {
        // Düzenleme modunda formu sıfırlama, sadece ilk adıma dön
        currentStep = 1;
        showStep(1);
        updateStepIndicators();
    } else {
        resetForm();
    }
}

function closeEventModal() {
    document.getElementById('eventModal').classList.remove('active');
    document.body.style.overflow = 'auto';
    document.getElementById('eventForm').removeAttribute('data-edit-id');
    document.getElementById('modalTitle').textContent = 'Yeni Etkinlik Ekle';
    resetForm();
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
    
    // Koltuk sistemi seçimini temizle
    document.querySelectorAll('.seating-option').forEach(option => {
        option.classList.remove('selected');
    });
    
    // İlk seating option'ı seç (general)
    const firstSeatingOption = document.querySelector('.seating-option[data-type="general"]');
    if (firstSeatingOption) {
        firstSeatingOption.classList.add('selected');
    }
    
    // Seating config'i gizle
    const seatingConfig = document.getElementById('seatingConfig');
    if (seatingConfig) {
        seatingConfig.style.display = 'none';
    }
    
    // Bilet türlerini sıfırla
    const ticketTypes = document.getElementById('ticketTypes');
    ticketTypes.innerHTML = getInitialTicketType();
    ticketTypeCount = 1;

    // İndirim kodlarını sıfırla
    const discountCodesContainer = document.getElementById('discountCodesContainer');
    if (discountCodesContainer) {
        discountCodesContainer.innerHTML = '';
    }

    // Yeni: İş ortakları alanlarını sıfırla
    const toggleStudios = document.getElementById('toggleStudios');
    const toggleAdAgency = document.getElementById('toggleAdAgency');
    const studiosContainer = document.getElementById('studiosContainer');
    const adAgenciesContainer = document.getElementById('adAgenciesContainer');
    const studiosList = document.getElementById('studiosList');
    const adAgenciesList = document.getElementById('adAgenciesList');
    const selectedServiceProviderInput = document.getElementById('selectedServiceProviderUserId');
    const selectedAdAgencyInput = document.getElementById('selectedAdAgencyUserId');

    if (toggleStudios) toggleStudios.checked = false;
    if (toggleAdAgency) toggleAdAgency.checked = false;
    if (studiosContainer) { studiosContainer.style.display = 'none'; if (studiosList) studiosList.innerHTML = ''; }
    if (adAgenciesContainer) { adAgenciesContainer.style.display = 'none'; if (adAgenciesList) adAgenciesList.innerHTML = ''; }
    if (selectedServiceProviderInput) selectedServiceProviderInput.value = '';
    if (selectedAdAgencyInput) selectedAdAgencyInput.value = '';
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
        const seatingType = document.getElementById('seatingType').value;
        
        // Koltuklu sistem veya rezervasyon sistemi seçiliyse ve 3. adımdaysak, 5. adıma geç (bilet adımını atla)
        if (currentStep === 3 && (seatingType === 'seated' || seatingType === 'reservation')) {
            // Sadece koltuklu sistem için koltuk konfigürasyonu kontrol et
            if (seatingType === 'seated' && seatConfiguration.categories.length === 0) {
                alert('Lütfen önce koltuk düzenini ayarlayın.');
                return;
            }
            currentStep = 5; // Sanatçılar ve etiketler adımına geç
        } else if (currentStep < totalSteps) {
            currentStep++;
        }
        
        showStep(currentStep);
        updateStepIndicators();
    }
}

function previousStep() {
    if (currentStep > 1) {
        const seatingType = document.getElementById('seatingType').value;
        
        // Koltuk sistemi veya rezervasyon sistemi seçiliyse ve 5. adımdaysak (sanatçılar), 3. adıma geri dön
        if (currentStep === 5 && (seatingType === 'seated' || seatingType === 'reservation')) {
            currentStep = 3;
        } else {
            currentStep--;
        }
        
        // Koltuk sistemi veya rezervasyon sistemi seçiliyse 4. adıma (bilet türleri) geri dönüşü engelle
        if (currentStep === 4 && (seatingType === 'seated' || seatingType === 'reservation')) {
            currentStep = 3;
        }
        
        showStep(currentStep);
        updateStepIndicators();
    }
}

function validateCurrentStep(stepElement = null) {
    const currentStepEl = stepElement || document.getElementById('step' + currentStep);
    const seatingType = document.getElementById('seatingType').value;
    
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
            // Koltuk sistemi seçimi kontrolü
            const selectedSeating = document.querySelector('.seating-option.selected');
            if (!selectedSeating) {
                alert('Lütfen bir koltuk sistemi seçin.');
                return false;
            }
            
            // Koltuk sistemi seçiliyse koltuk konfigürasyonu kontrol et
            if (seatingType === 'seated') {
                if (seatConfiguration.categories.length === 0) {
                    alert('Lütfen önce koltuk düzenini ayarlayın.');
                    return false;
                }
            }
            break;
            
        case 4:
            // Koltuk sistemi veya rezervasyon sistemi seçiliyse bilet validasyonunu atla
            if (seatingType === 'seated' || seatingType === 'reservation') {
                return true;
            }
            
            // Bilet bilgilerini kontrol et (sadece genel sistem için)
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
            // Önceki seçimi kaldır
            document.querySelectorAll('.category-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            
            // Yeni seçimi işaretle
            this.classList.add('selected');
            
            // Hidden input'a değeri ata
            document.getElementById('categoryId').value = this.dataset.category;
        });
    });
});

// Koltuk sistemi seçimi
document.addEventListener('DOMContentLoaded', function() {
    const seatingOptions = document.querySelectorAll('.seating-option');
    const seatingTypeInput = document.getElementById('seatingType');
    const seatingConfig = document.getElementById('seatingConfig');
    
    seatingOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Tüm seçenekleri temizle
            seatingOptions.forEach(opt => opt.classList.remove('selected'));
            
            // Seçili olanı işaretle
            this.classList.add('selected');
            
            // Değeri güncelle
            const type = this.dataset.type;
            seatingTypeInput.value = type;
            
            // Koltuk konfigürasyonunu göster/gizle
            if (type === 'seated') {
                seatingConfig.style.display = 'block';
                seatingConfig.classList.remove('reservation-mode');
                // Bilet türleri adımındaki required alanları devre dışı bırak
                disableTicketRequiredFields();
                // Kategori açıklamasını güncelle
                const categoryDesc = document.getElementById('categoryDescription');
                if (categoryDesc) {
                    categoryDesc.textContent = 'Önce bilet kategorilerinizi oluşturun, renklerini ve fiyatlarını belirleyin.';
                }
                // Mobil cihazlarda koltuk düzeni kısmına kaydır
                setTimeout(() => {
                    if (window.innerWidth <= 768) {
                        seatingConfig.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }, 300);
            } else if (type === 'reservation') {
                seatingConfig.style.display = 'block';
                seatingConfig.classList.add('reservation-mode');
                // Rezervasyon sistemi için de koltuk düzeni gerekli
                disableTicketRequiredFields();
                // Kategori açıklamasını güncelle
                const categoryDesc = document.getElementById('categoryDescription');
                if (categoryDesc) {
                    categoryDesc.textContent = 'Rezervasyon kategorilerinizi oluşturun ve renklerini belirleyin. Fiyat bilgisi gerekmez.';
                }
                // Mobil cihazlarda koltuk düzeni kısmına kaydır
                setTimeout(() => {
                    if (window.innerWidth <= 768) {
                        seatingConfig.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }, 300);
            } else {
                seatingConfig.style.display = 'none';
                seatingConfig.classList.remove('reservation-mode');
                // Bilet türleri adımındaki required alanları etkinleştir
                enableTicketRequiredFields();
            }
        });
    });
    
    // Varsayılan seçimi ayarla
    document.querySelector('.seating-option[data-type="general"]').classList.add('selected');
});

// Bilet türleri required alanlarını devre dışı bırak
function disableTicketRequiredFields() {
    const ticketFields = document.querySelectorAll('#step4 input[required]');
    ticketFields.forEach(field => {
        field.removeAttribute('required');
        field.setAttribute('data-was-required', 'true');
    });
}

// Bilet türleri required alanlarını etkinleştir
function enableTicketRequiredFields() {
    const ticketFields = document.querySelectorAll('#step4 input[data-was-required="true"]');
    ticketFields.forEach(field => {
        field.setAttribute('required', 'required');
        field.removeAttribute('data-was-required');
    });
}

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

// İndirim Kodları Yönetimi
function addDiscountCodeRow() {
    const container = document.getElementById('discountCodesContainer');
    if (!container) return;
    const row = document.createElement('div');
    row.className = 'discount-code-row';
    row.style.marginBottom = '1rem';
    row.innerHTML = `
        <div class="form-grid">
            <div class="form-group">
                <label>Kod *</label>
                <input type="text" name="discount_code_code[]" required placeholder="Örn: INDIRIM50">
            </div>
            <div class="form-group">
                <label>İndirim Tutarı (₺) *</label>
                <input type="number" name="discount_code_amount[]" required min="0" step="0.01" placeholder="Örn: 50">
            </div>
            <div class="form-group">
                <label>Kullanım Adedi *</label>
                <input type="number" name="discount_code_quantity[]" required min="1" placeholder="Örn: 100">
            </div>
            <div class="form-group">
                <label>&nbsp;</label>
                <button type="button" class="btn-remove-ticket" onclick="this.closest('.discount-code-row').remove()">
                    <i class="fas fa-trash"></i> Kaldır
                </button>
            </div>
        </div>
    `;
    container.appendChild(row);
}

// İş Ortakları Yönetimi
document.addEventListener('DOMContentLoaded', function() {
    const toggleStudios = document.getElementById('toggleStudios');
    const toggleAdAgency = document.getElementById('toggleAdAgency');
    const cityInput = document.getElementById('city');

    if (toggleStudios) {
        toggleStudios.addEventListener('change', () => {
            if (toggleStudios.checked) {
                if (!cityInput || !cityInput.value.trim()) {
                    if (typeof showNotification === 'function') {
                        showNotification('Lütfen önce Etkinlik Detayları adımında şehir bilgisini girin.', 'warning');
                    } else {
                        alert('Lütfen önce Etkinlik Detayları adımında şehir bilgisini girin.');
                    }
                    toggleStudios.checked = false;
                    return;
                }
                loadStudiosByCity(cityInput.value.trim());
                const sc = document.getElementById('studiosContainer');
                if (sc) sc.style.display = 'block';
            } else {
                const sc = document.getElementById('studiosContainer');
                const sl = document.getElementById('studiosList');
                const sel = document.getElementById('selectedServiceProviderUserId');
                if (sc) sc.style.display = 'none';
                if (sl) sl.innerHTML = '';
                if (sel) sel.value = '';
            }
        });
    }

    if (toggleAdAgency) {
        toggleAdAgency.addEventListener('change', () => {
            if (toggleAdAgency.checked) {
                if (!cityInput || !cityInput.value.trim()) {
                    if (typeof showNotification === 'function') {
                        showNotification('Lütfen önce Etkinlik Detayları adımında şehir bilgisini girin.', 'warning');
                    } else {
                        alert('Lütfen önce Etkinlik Detayları adımında şehir bilgisini girin.');
                    }
                    toggleAdAgency.checked = false;
                    return;
                }
                loadAdAgenciesByCity(cityInput.value.trim());
                const ac = document.getElementById('adAgenciesContainer');
                if (ac) ac.style.display = 'block';
            } else {
                const ac = document.getElementById('adAgenciesContainer');
                const al = document.getElementById('adAgenciesList');
                const sel = document.getElementById('selectedAdAgencyUserId');
                if (ac) ac.style.display = 'none';
                if (al) al.innerHTML = '';
                if (sel) sel.value = '';
            }
        });
    }

    // Şehir değişirse açık olan listeleri yeniden yükle
    if (cityInput) {
        cityInput.addEventListener('change', () => {
            const city = cityInput.value.trim();
            if (city && toggleStudios && toggleStudios.checked) loadStudiosByCity(city);
            if (city && toggleAdAgency && toggleAdAgency.checked) loadAdAgenciesByCity(city);
        });
    }
});

function loadStudiosByCity(city) {
    fetch('../ajax/list_service_providers.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
        body: 'city=' + encodeURIComponent(city)
    })
    .then(r => r.json())
    .then(res => {
        const listEl = document.getElementById('studiosList');
        if (!listEl) return;
        listEl.innerHTML = '';
        if (!res.success || !Array.isArray(res.items) || res.items.length === 0) {
            listEl.innerHTML = '<div style="color:#999;">Bu şehirde kayıtlı stüdyo bulunamadı.</div>';
            return;
        }
        res.items.forEach(item => {
            const row = document.createElement('label');
            row.style.display = 'flex';
            row.style.alignItems = 'center';
            row.style.gap = '10px';
            row.style.border = '1px solid #eee';
            row.style.padding = '8px 10px';
            row.style.borderRadius = '8px';
            row.innerHTML = `
                <input type="radio" name="studio_select" value="${item.user_id}">
                <div style="display:flex;flex-direction:column;">
                    <strong>${item.company_name || 'Stüdyo'}</strong>
                    <span>Telefon: ${item.phone ? item.phone : 'Belirtilmemiş'}</span>
                </div>
                <a href="tel:${item.phone || ''}" style="margin-left:auto;" class="btn-add-ticket">Ara</a>
            `;
            const radio = row.querySelector('input[type="radio"]');
            if (radio) {
                radio.addEventListener('change', (e) => {
                    const sel = document.getElementById('selectedServiceProviderUserId');
                    if (sel) sel.value = e.target.value;
                });
            }
            listEl.appendChild(row);
        });
    })
    .catch(() => {
        if (typeof showNotification === 'function') {
            showNotification('Stüdyolar yüklenirken bir hata oluştu.', 'error');
        }
    });
}

function loadAdAgenciesByCity(city) {
    fetch('../ajax/list_ad_agencies.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
        body: 'city=' + encodeURIComponent(city)
    })
    .then(r => r.json())
    .then(res => {
        const listEl = document.getElementById('adAgenciesList');
        if (!listEl) return;
        listEl.innerHTML = '';
        if (!res.success || !Array.isArray(res.items) || res.items.length === 0) {
            listEl.innerHTML = '<div style="color:#999;">Bu şehirde kayıtlı reklam ajansı bulunamadı.</div>';
            return;
        }
        res.items.forEach(item => {
            const row = document.createElement('label');
            row.style.display = 'flex';
            row.style.alignItems = 'center';
            row.style.gap = '10px';
            row.style.border = '1px solid #eee';
            row.style.padding = '8px 10px';
            row.style.borderRadius = '8px';
            row.innerHTML = `
                <input type="radio" name="ad_agency_select" value="${item.user_id}">
                <div style="display:flex;flex-direction:column;">
                    <strong>${item.company_name || 'Reklam Ajansı'}</strong>
                    <span>Telefon: ${item.phone ? item.phone : 'Belirtilmemiş'}</span>
                </div>
                <a href="tel:${item.phone || ''}" style="margin-left:auto;" class="btn-add-ticket">Ara</a>
            `;
            const radio = row.querySelector('input[type="radio"]');
            if (radio) {
                radio.addEventListener('change', (e) => {
                    const sel = document.getElementById('selectedAdAgencyUserId');
                    if (sel) sel.value = e.target.value;
                });
            }
            listEl.appendChild(row);
        });
    })
    .catch(() => {
        if (typeof showNotification === 'function') {
            showNotification('Reklam ajansları yüklenirken bir hata oluştu.', 'error');
        }
    });
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

// Koltuk yönetimi değişkenleri
let seatConfiguration = {
    layout: {
        rows: 10,
        seatsPerRow: 20,
        venueName: 'Ana Salon'
    },
    categories: [
        {
            id: 'standard',
            name: 'Standart',
            color: '#4CAF50',
            price: 100,
            description: ''
        }
    ],
    seats: []
};

// Koltuk modalı için değişkenler
let currentSeatStep = 1;
let totalSeatSteps = 3;

// Koltuk modalı adım yönetimi
function showSeatStep(step) {
    // Tüm adımları gizle
    document.querySelectorAll('.seat-step').forEach(stepEl => {
        stepEl.classList.remove('active');
    });
    
    // Aktif adımı göster
    document.getElementById('seatStep' + step).classList.add('active');
    
    // Buton durumlarını güncelle
    const prevBtn = document.getElementById('seatPrevBtn');
    const nextBtn = document.getElementById('seatNextBtn');
    const saveBtn = document.getElementById('seatSaveBtn');
    
    prevBtn.style.display = step === 1 ? 'none' : 'flex';
    nextBtn.style.display = step === totalSeatSteps ? 'none' : 'flex';
    saveBtn.style.display = step === totalSeatSteps ? 'flex' : 'none';
    
    // Adım göstergelerini güncelle
    updateSeatStepIndicators();
}

function updateSeatStepIndicators() {
    document.querySelectorAll('.seat-step-indicator').forEach((indicator, index) => {
        const stepNum = index + 1;
        indicator.classList.remove('active', 'completed');
        
        if (stepNum < currentSeatStep) {
            indicator.classList.add('completed');
        } else if (stepNum === currentSeatStep) {
            indicator.classList.add('active');
        }
    });
}

function nextSeatStep() {
    if (validateCurrentSeatStep()) {
        if (currentSeatStep < totalSeatSteps) {
            currentSeatStep++;
            showSeatStep(currentSeatStep);
            
            // Önizleme adımına geçildiğinde güncelle
            if (currentSeatStep === 3) {
                updatePreview();
            }
        }
    }
}

function previousSeatStep() {
    if (currentSeatStep > 1) {
        currentSeatStep--;
        showSeatStep(currentSeatStep);
    }
}

function validateCurrentSeatStep() {
    switch(currentSeatStep) {
        case 1:
            // En az bir kategori olmalı
            const categories = document.querySelectorAll('.seat-category');
            if (categories.length === 0) {
                alert('En az bir bilet kategorisi oluşturmalısınız.');
                return false;
            }
            
            // Tüm kategorilerin adı ve fiyatı olmalı
            for (let category of categories) {
                const name = category.querySelector('.category-name').value;
                const price = category.querySelector('.category-price').value;
                if (!name || !price) {
                    alert('Tüm kategoriler için ad ve fiyat belirtmelisiniz.');
                    return false;
                }
            }
            break;
            
        case 2:
            // Koltuk düzeni oluşturulmuş olmalı
            const seats = document.querySelectorAll('.seat');
            if (seats.length === 0) {
                alert('Önce koltuk düzenini oluşturmalısınız.');
                return false;
            }
            break;
    }
    return true;
}

// Koltuk yönetimi modalını aç
function openSeatManagementModal() {
    currentSeatStep = 1;
    document.getElementById('seatManagementModal').classList.add('active');
    document.body.style.overflow = 'hidden';
    showSeatStep(1);
}

// Koltuk yönetimi modalını kapat
function closeSeatManagementModal() {
    document.getElementById('seatManagementModal').classList.remove('active');
    document.body.style.overflow = 'auto';
}

// Renk seçici aç
function openColorPicker(colorDiv) {
    const input = document.createElement('input');
    input.type = 'color';
    input.value = rgbToHex(colorDiv.style.backgroundColor);
    
    input.addEventListener('change', function() {
        colorDiv.style.backgroundColor = this.value;
    });
    
    input.click();
}

// RGB'yi HEX'e çevir
function rgbToHex(rgb) {
    if (rgb.startsWith('#')) return rgb;
    
    const result = rgb.match(/\d+/g);
    if (!result) return '#4CAF50';
    
    return '#' + result.map(x => {
        const hex = parseInt(x).toString(16);
        return hex.length === 1 ? '0' + hex : hex;
    }).join('');
}

// Koltuk düzeni oluştur
function generateSeatingLayout() {
    const rows = parseInt(document.getElementById('rowCount').value) || 10;
    const seatsPerRow = parseInt(document.getElementById('seatPerRow').value) || 20;
    const venueName = document.getElementById('venueLayoutName').value || 'Ana Salon';
    
    seatConfiguration.layout = { rows, seatsPerRow, venueName };
    
    const seatingGrid = document.getElementById('seatingGrid');
    seatingGrid.innerHTML = '';
    
    // Mevcut kategorileri al
    const categories = getCurrentCategories();
    
    if (categories.length === 0) {
        alert('Lütfen önce en az bir koltuk kategorisi oluşturun.');
        return;
    }
    
    for (let row = 1; row <= rows; row++) {
        const rowDiv = document.createElement('div');
        rowDiv.className = 'seat-row';
        
        // Sıra etiketi
        const rowLabel = document.createElement('div');
        rowLabel.className = 'row-label';
        rowLabel.textContent = String.fromCharCode(64 + row); // A, B, C...
        rowDiv.appendChild(rowLabel);
        
        // Koltuklar
        for (let seat = 1; seat <= seatsPerRow; seat++) {
            const seatDiv = document.createElement('div');
            seatDiv.className = 'seat category-1';
            seatDiv.dataset.row = row;
            seatDiv.dataset.seat = seat;
            seatDiv.dataset.categoryIndex = '0'; // İlk kategori
            seatDiv.textContent = seat;
            
            // İlk kategorinin rengini uygula
            const rgbColor = categories[0].color;
            const hexColor = rgbToHex(rgbColor);
            seatDiv.style.backgroundColor = hexColor;
            
            seatDiv.addEventListener('click', function() {
                cycleSeatCategory(this);
            });
            
            rowDiv.appendChild(seatDiv);
        }
        
        seatingGrid.appendChild(rowDiv);
    }
    
    updateSeatingSummary();
}

// Mevcut kategorileri al
function getCurrentCategories() {
    const categories = [];
    document.querySelectorAll('.seat-category').forEach(categoryDiv => {
        const nameInput = categoryDiv.querySelector('.category-name');
        const priceInput = categoryDiv.querySelector('.category-price');
        const colorDiv = categoryDiv.querySelector('.category-color');
        const descTextarea = categoryDiv.querySelector('.category-desc');
        
        if (nameInput && nameInput.value.trim() && priceInput && priceInput.value) {
            categories.push({
                name: nameInput.value.trim(),
                price: parseFloat(priceInput.value),
                color: colorDiv.style.backgroundColor,
                description: descTextarea ? descTextarea.value : ''
            });
        }
    });
    
    return categories;
}

// Koltuk kategorisini döngüsel olarak değiştir
function cycleSeatCategory(seatElement) {
    const categories = getCurrentCategories();
    if (categories.length === 0) return;
    
    let currentIndex = parseInt(seatElement.dataset.categoryIndex) || 0;
    currentIndex = (currentIndex + 1) % categories.length;
    
    seatElement.dataset.categoryIndex = currentIndex;
    
    // Rengi hex formatında uygula
    const rgbColor = categories[currentIndex].color;
    const hexColor = rgbToHex(rgbColor);
    seatElement.style.backgroundColor = hexColor;
    
    // Kategori class'ını güncelle
    seatElement.className = `seat category-${currentIndex + 1}`;
}

// Koltuk seçimi için gelişmiş fonksiyon
function toggleSeatSelection(seatElement) {
    const categories = Array.from(document.querySelectorAll('.seat-category')).map(cat => {
        return {
            name: cat.querySelector('.category-name').value,
            color: cat.querySelector('.category-color').style.backgroundColor
        };
    });
    
    if (categories.length === 0) return;
    
    const currentCategory = seatElement.dataset.category || 'standard';
    const currentIndex = categories.findIndex(cat => cat.name.toLowerCase() === currentCategory);
    const nextIndex = (currentIndex + 1) % categories.length;
    const nextCategory = categories[nextIndex];
    
    // Koltuk kategorisini ve rengini güncelle
    seatElement.dataset.category = nextCategory.name.toLowerCase();
    seatElement.style.backgroundColor = nextCategory.color;
    seatElement.className = `seat ${nextCategory.name.toLowerCase()}`;
}



// Koltuk kategorisi ekle
function addSeatCategory() {
    const colors = ['#FF5722', '#3F51B5', '#E91E63', '#00BCD4', '#8BC34A', '#FFC107'];
    const randomColor = colors[Math.floor(Math.random() * colors.length)];
    
    const categoryDiv = document.createElement('div');
    categoryDiv.className = 'seat-category';
    categoryDiv.innerHTML = `
        <div class="category-header">
            <div class="category-color" style="background-color: ${randomColor};" onclick="openColorPicker(this)"></div>
            <input type="text" class="category-name" placeholder="Kategori Adı">
            <input type="number" class="category-price" min="0" step="0.01" placeholder="Fiyat">
            <span class="currency">₺</span>
            <button type="button" class="btn-remove-category" onclick="removeSeatCategory(this)">
                <i class="fas fa-trash"></i>
            </button>
        </div>
        <div class="category-description">
            <textarea class="category-desc" placeholder="Kategori açıklaması..."></textarea>
        </div>
    `;
    
    document.getElementById('seatCategories').appendChild(categoryDiv);
    
    // İlk kategorinin silme butonunu göster
    const firstCategory = document.querySelector('.seat-category .btn-remove-category');
    if (firstCategory) {
        firstCategory.style.display = 'flex';
    }
}

// Koltuk kategorisini sil
function removeSeatCategory(button) {
    const category = button.closest('.seat-category');
    category.remove();
    
    // Eğer sadece bir kategori kaldıysa, silme butonunu gizle
    const remainingCategories = document.querySelectorAll('.seat-category');
    if (remainingCategories.length === 1) {
        const removeBtn = remainingCategories[0].querySelector('.btn-remove-category');
        if (removeBtn) {
            removeBtn.style.display = 'none';
        }
    }
}

// Koltuk konfigürasyonunu kaydet
function saveSeatConfiguration() {
    console.log('saveSeatConfiguration called');
    
    // Kategorileri topla - DÜZELTME: .seat-category kullan
    const categories = [];
    document.querySelectorAll('.seat-category').forEach(item => {
        const nameInput = item.querySelector('.category-name');
        const colorDiv = item.querySelector('.category-color');
        const priceInput = item.querySelector('.category-price');
        const descInput = item.querySelector('.category-desc');
        
        if (nameInput && nameInput.value.trim()) {
            // RGB rengini hex'e çevir
            const rgbColor = colorDiv.style.backgroundColor;
            const hexColor = rgbToHex(rgbColor);
            
            categories.push({
                name: nameInput.value.trim(),
                color: hexColor,
                price: parseFloat(priceInput.value) || 0,
                description: descInput ? descInput.value.trim() : ''
            });
        }
    });
    
    console.log('Categories found:', categories);
    
    if (categories.length === 0) {
        alert('Lütfen en az bir koltuk kategorisi oluşturun.');
        return;
    }
    
    seatConfiguration.categories = categories;
    
    // Koltukları topla - DÜZELTME: categoryIndex kullan
    const allSeats = [];
    document.querySelectorAll('.seat').forEach(seat => {
        const row = seat.dataset.row;
        const seatNum = seat.dataset.seat;
        const categoryIndex = parseInt(seat.dataset.categoryIndex) || 0;
        
        if (row && seatNum) {
            // DÜZELTME: toLowerCase() kaldırıldı
            const categoryName = categories[categoryIndex] ? categories[categoryIndex].name : 'standard';
            
            allSeats.push({
                row: parseInt(row),
                seat: parseInt(seatNum),
                category: categoryName
            });
        }
    });
    
    console.log('Seats found:', allSeats);
    
    if (allSeats.length === 0) {
        alert('Lütfen önce koltuk düzenini oluşturun.');
        return;
    }
    
    seatConfiguration.seats = allSeats;
    
    // Form'a hidden input olarak ekle
    const form = document.getElementById('eventForm');
    
    // Mevcut hidden inputları temizle
    const existingCategoriesInput = form.querySelector('input[name="seat_categories"]');
    const existingSeatsInput = form.querySelector('input[name="seats"]');
    
    if (existingCategoriesInput) existingCategoriesInput.remove();
    if (existingSeatsInput) existingSeatsInput.remove();
    
    // Yeni hidden inputları ekle
    const categoriesInput = document.createElement('input');
    categoriesInput.type = 'hidden';
    categoriesInput.name = 'seat_categories';
    categoriesInput.value = JSON.stringify(categories);
    form.appendChild(categoriesInput);
    
    const seatsInput = document.createElement('input');
    seatsInput.type = 'hidden';
    seatsInput.name = 'seats';
    seatsInput.value = JSON.stringify(allSeats);
    form.appendChild(seatsInput);
    
    console.log('Hidden inputs added to form');
    console.log('Categories JSON:', JSON.stringify(categories));
    console.log('Seats JSON:', JSON.stringify(allSeats));
    
    // Özeti güncelle
    updateSeatingSummary();
    
    // Modalı kapat
    closeSeatManagementModal();
    
    alert('Koltuk düzeni başarıyla kaydedildi!');
}

// Koltuk özetini güncelle
function updateSeatingSummary() {
    const totalSeats = seatConfiguration.layout.rows * seatConfiguration.layout.seatsPerRow;
    const categoriesCount = seatConfiguration.categories.length;
    
    let minPrice = Math.min(...seatConfiguration.categories.map(c => c.price));
    let maxPrice = Math.max(...seatConfiguration.categories.map(c => c.price));
    
    if (!isFinite(minPrice)) minPrice = 0;
    if (!isFinite(maxPrice)) maxPrice = 0;
    
    document.getElementById('totalSeatsCount').textContent = totalSeats;
    document.getElementById('seatCategoriesCount').textContent = categoriesCount;
    document.getElementById('priceRange').textContent = `₺${minPrice} - ₺${maxPrice}`;
}

// Önizlemeyi güncelle
function updatePreview() {
    const totalSeats = seatConfiguration.layout.rows * seatConfiguration.layout.seatsPerRow;
    const categoriesCount = seatConfiguration.categories.length;
    const avgPrice = seatConfiguration.categories.reduce((sum, cat) => sum + cat.price, 0) / categoriesCount || 0;
    
    document.getElementById('previewTotalSeats').textContent = totalSeats;
    document.getElementById('previewCategories').textContent = categoriesCount;
    document.getElementById('previewAvgPrice').textContent = `₺${avgPrice.toFixed(2)}`;
    
    // Önizleme koltuk düzenini oluştur
    generatePreviewLayout();
    
    // Kategori legendini oluştur
    generateCategoryLegend();
}

// Önizleme düzenini oluştur
function generatePreviewLayout() {
    const previewContainer = document.getElementById('seatingPreview');
    previewContainer.innerHTML = '';
    
    // Küçük koltuk düzeni oluştur
    for (let row = 1; row <= Math.min(seatConfiguration.layout.rows, 10); row++) {
        const rowDiv = document.createElement('div');
        rowDiv.className = 'seat-row';
        rowDiv.style.transform = 'scale(0.7)';
        
        for (let seat = 1; seat <= Math.min(seatConfiguration.layout.seatsPerRow, 15); seat++) {
            const seatDiv = document.createElement('div');
            seatDiv.className = 'seat standard';
            seatDiv.style.width = '16px';
            seatDiv.style.height = '16px';
            seatDiv.style.fontSize = '0.6rem';
            
            rowDiv.appendChild(seatDiv);
        }
        
        previewContainer.appendChild(rowDiv);
    }
}

// Kategori legendini oluştur
function generateCategoryLegend() {
    const legendContainer = document.getElementById('categoryLegend');
    legendContainer.innerHTML = '';
    
    seatConfiguration.categories.forEach(category => {
        const legendItem = document.createElement('div');
        legendItem.className = 'legend-item';
        legendItem.innerHTML = `
            <div class="legend-color" style="background-color: ${category.color};"></div>
            <span class="legend-label">${category.name} - ₺${category.price}</span>
        `;
        legendContainer.appendChild(legendItem);
    });
}

// Etkinlik İşlemleri
function editEvent(eventId) {
    // Modalı reset etmeden aç (edit modu)
    openEventModal(true);

    // Etkinlik verilerini yükle
    fetch(`get_event.php?id=${eventId}`)
        .then(response => response.json())
        .then(data => {
            console.log('Event data received:', data);
            if (data.success) {
                document.getElementById('eventForm').dataset.editId = eventId;
                document.getElementById('modalTitle').textContent = 'Etkinlik Düzenle';
                // Etkinlik verilerini modale yükle
                loadEventDataToModal(data.event, data.tickets);
            } else {
                alert('Etkinlik verileri yüklenirken hata oluştu: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Bir hata oluştu. Lütfen tekrar deneyin.');
        });
}

function deleteEvent(eventId) {
    if (confirm('Bu etkinliği silmek istediğinizden emin misiniz? Bu işlem geri alınamaz!')) {
        // AJAX ile etkinlik silme
        const formData = new FormData();
        formData.append('event_id', eventId);
        
        fetch('delete_event.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Etkinlik başarıyla silindi!');
                // Sayfayı yenile
                window.location.reload();
            } else {
                alert('Hata: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Bir hata oluştu. Lütfen tekrar deneyin.');
        });
    }
}

function publishEvent(eventId) {
    if (confirm('Bu etkinliği yayınlamak istediğinizden emin misiniz?')) {
        updateEventStatus(eventId, 'published');
    }
}

function unpublishEvent(eventId) {
    if (confirm('Bu etkinliği taslağa almak istediğinizden emin misiniz?')) {
        updateEventStatus(eventId, 'draft');
    }
}

function updateEventStatus(eventId, status) {
    const formData = new FormData();
    formData.append('event_id', eventId);
    formData.append('status', status);
    
    fetch('update_status.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Etkinlik durumu başarıyla güncellendi!');
            // Sayfa yenileme yerine etkinlik listesini güncelle
            loadEvents();
        } else {
            alert('Hata: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Bir hata oluştu');
    });
}

function viewEvent(eventId) {
    // Etkinlik detay sayfasına git
    window.open('../etkinlik-detay.php?id=' + eventId, '_blank');
}

// Etkinlik verilerini modale yükle
function loadEventDataToModal(event) {
    console.log('loadEventDataToModal called with:', event);
    
    // Temel bilgiler
    const titleInput = document.querySelector('input[name="title"]');
    console.log('Title input found:', titleInput, 'Setting value:', event.title);
    if (titleInput) titleInput.value = event.title || '';
    
    // Kategori (hidden input) + görsel seçim
    const categoryHidden = document.getElementById('categoryId') || document.querySelector('input[name="category_id"]');
    if (categoryHidden) categoryHidden.value = event.category_id || '';
    document.querySelectorAll('.category-option').forEach(option => {
        const isSelected = String(option.dataset.category) === String(event.category_id);
        option.classList.toggle('selected', isSelected);
    });
    
    const descriptionTextarea = document.querySelector('textarea[name="description"]');
    if (descriptionTextarea) descriptionTextarea.value = event.description || '';
    
    const shortDescriptionTextarea = document.querySelector('textarea[name="short_description"]');
    if (shortDescriptionTextarea) shortDescriptionTextarea.value = event.short_description || '';
    
    // Başlangıç tarihi ve saati - datetime-local için
    if (event.event_date) {
        const eventDate = new Date(event.event_date);
        const datetimeStr = eventDate.toISOString().slice(0, 16); // YYYY-MM-DDTHH:MM formatı
        
        const dateTimeInput = document.getElementById('eventDateTime');
        if (dateTimeInput) {
            dateTimeInput.value = datetimeStr;
        } else {
            // Fallback: eski ayrı inputlar
            const dateStr = eventDate.toISOString().split('T')[0];
            const timeStr = eventDate.toTimeString().split(' ')[0].substring(0, 5);
            
            const dateInput = document.querySelector('input[name="event_date"]');
            if (dateInput) dateInput.value = dateStr;
            
            const timeInput = document.querySelector('input[name="event_time"]');
            if (timeInput) timeInput.value = timeStr;
        }
    }

    // Bitiş tarihi ve saati - datetime-local için
    if (event.end_date) {
        const endDate = new Date(event.end_date);
        const endDatetimeStr = endDate.toISOString().slice(0, 16); // YYYY-MM-DDTHH:MM formatı

        const endDateTimeInput = document.getElementById('endDateTime');
        if (endDateTimeInput) {
            endDateTimeInput.value = endDatetimeStr;
        } else {
            // Fallback: eski ayrı inputlar
            const endDateStr = endDate.toISOString().split('T')[0];
            const endTimeStr = endDate.toTimeString().split(' ')[0].substring(0, 5);

            const endDateInput = document.querySelector('input[name="end_date"]');
            if (endDateInput) endDateInput.value = endDateStr;

            const endTimeInput = document.querySelector('input[name="end_time"]');
            if (endTimeInput) endTimeInput.value = endTimeStr;
        }
    }
    
    // Mekan bilgileri
    const venueNameInput = document.querySelector('input[name="venue_name"]');
    if (venueNameInput) venueNameInput.value = event.venue_name || '';
    
    const venueAddressTextarea = document.querySelector('textarea[name="venue_address"]');
    if (venueAddressTextarea) venueAddressTextarea.value = event.venue_address || '';
    
    const cityInput = document.querySelector('input[name="city"]');
    if (cityInput) cityInput.value = event.city || '';

    // İletişim bilgileri
    const contactEmailInput = document.querySelector('input[name="contact_email"]');
    if (contactEmailInput) contactEmailInput.value = event.contact_email || '';
    
    const contactPhoneInput = document.querySelector('input[name="contact_phone"]');
    if (contactPhoneInput) contactPhoneInput.value = event.contact_phone || '';

    // Oturma tipi (seating_type) ve UI
    const seatingTypeInput = document.getElementById('seatingType') || document.querySelector('input[name="seating_type"]');
    const seatingType = event.seating_type || 'general';
    if (seatingTypeInput) seatingTypeInput.value = seatingType;

    document.querySelectorAll('.seating-option').forEach(option => {
        option.classList.toggle('selected', option.dataset.type === seatingType);
    });

    const seatingConfig = document.getElementById('seatingConfig');
    if (seatingConfig) {
        seatingConfig.style.display = seatingType === 'seated' ? 'block' : 'none';
    }
    
    // Sosyal medya bağlantıları
    const instagramInput = document.querySelector('input[name="instagram_link"]');
    if (instagramInput) instagramInput.value = event.instagram_url || '';
    
    const twitterInput = document.querySelector('input[name="twitter_link"]');
    if (twitterInput) twitterInput.value = event.twitter_url || '';
    
    const facebookInput = document.querySelector('input[name="facebook_link"]');
    if (facebookInput) facebookInput.value = event.facebook_url || '';
    
    // Sanatçılar ve etiketler
    const artistsInput = document.querySelector('input[name="artists"]');
    if (artistsInput) artistsInput.value = event.artists || '';
    
    const tagsInput = document.querySelector('input[name="tags"]');
    if (tagsInput) tagsInput.value = event.tags || '';
    
    const metaDescriptionTextarea = document.querySelector('textarea[name="meta_description"]');
    if (metaDescriptionTextarea) metaDescriptionTextarea.value = event.meta_description || '';
}

// Form Gönderimi
document.getElementById('eventForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const submitBtn = document.querySelector('#eventForm button[type="submit"]');
    
    // Çift tıklama koruması - daha güçlü
    if (submitBtn.disabled || submitBtn.classList.contains('submitting')) {
        return false;
    }
    
    // Mevcut adım doğrulaması
    const currentStep = document.querySelector('.form-step.active');
    if (!validateCurrentStep(currentStep)) {
        return false;
    }
    
    // Koltuklu etkinlik için koltuk verilerini kaydet
    const seatingType = document.querySelector('input[name="seating_type"]:checked');
    if (seatingType && seatingType.value === 'seated') {
        saveSeatConfiguration();
    }
    
    // Form verilerini topla
    const formData = new FormData(this);
    
    // Datetime-local inputlarını ayrı tarih ve saat alanlarına dönüştür
    const eventDateTime = document.getElementById('eventDateTime')?.value;
    if (eventDateTime) {
        const eventDate = new Date(eventDateTime);
        formData.set('event_date', eventDate.toISOString().split('T')[0]);
        formData.set('event_time', eventDate.toTimeString().split(' ')[0].substring(0, 5));
    }
    
    const endDateTime = document.getElementById('endDateTime')?.value;
    if (endDateTime) {
        const endDate = new Date(endDateTime);
        formData.set('end_date', endDate.toISOString().split('T')[0]);
        formData.set('end_time', endDate.toTimeString().split(' ')[0].substring(0, 5));
    }
    
    // Düzenleme modunda mı kontrol et
    const editId = this.dataset.editId;
    if (editId) {
        formData.append('event_id', editId);
    }
    
    // Submit butonunu devre dışı bırak ve işaretleme ekle
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.classList.add('submitting');
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Kaydediliyor...';
    
    // AJAX ile form gönderimi
    const url = editId ? 'update_event.php' : 'create_event.php';
    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            closeEventModal();
            const message = editId ? 'Etkinlik başarıyla güncellendi!' : 'Etkinlik başarıyla oluşturuldu!';
            alert(message);
            // Sayfayı yenile yerine etkinlik listesini güncelle
            loadEvents();
        } else {
            alert('Hata: ' + data.message);
            // Sadece hata durumunda butonu tekrar aktif et
            submitBtn.disabled = false;
            submitBtn.classList.remove('submitting');
            submitBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Bir hata oluştu. Lütfen tekrar deneyin.');
        // Sadece hata durumunda butonu tekrar aktif et
        submitBtn.disabled = false;
        submitBtn.classList.remove('submitting');
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

// QR Yetkili sayfasını içerik olarak yükle
function loadQRStaffPage() {
    fetch('qr_staff_content.php')
        .then(response => response.text())
        .then(html => {
            const main = document.getElementById('main-content');
            if (main) {
                main.innerHTML = html;
                bindQRForms();
            } else {
                console.warn('main-content bulunamadı; QR içeriğini yükleyemiyorum.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const main = document.getElementById('main-content');
            if (main) main.innerHTML = '<div class="error">Sayfa yüklenirken hata oluştu.</div>';
        });
}
function bindQRForms() {
    const container = document.getElementById('main-content');
    if (!container) return;

    container.querySelectorAll('form').forEach(form => {
        if (form.dataset.qrbound === '1') return;

        const isQRForm = form.querySelector('[name="create_staff"]') 
            || form.querySelector('[name="update_staff"]') 
            || form.querySelector('[name="delete_staff"]');
        if (!isQRForm) return;

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            fetch('qr_staff_content.php', {
                method: 'POST',
                body: new FormData(form)
            })
            .then(res => res.text())
            .then(html => {
                const main = document.getElementById('main-content');
                if (main) {
                    main.innerHTML = html;
                    bindQRForms();
                }
            })
            .catch(err => console.error('QR form error:', err));
        }, { passive: false });

        form.dataset.qrbound = '1';
    });
}

// QR panelini yeni sekmede aç (qr_staff_content içinde kullanılan buton)
function goToQRPanel() {
    window.open('qr_auto_login.php', '_blank');
}

// Logout functionality - element handled via inline onclick in sidebar; keep fallback if element exists
const sidebarLogoutEl = document.querySelector('.sidebar-logout');
if (sidebarLogoutEl) {
    sidebarLogoutEl.addEventListener('click', function() {
        if (confirm('Çıkış yapmak istediğinizden emin misiniz?')) {
            window.location.href = '../auth/logout.php';
        }
    });
}

// Modal dışına tıklayınca kapat - KALDIRILDI
// document.getElementById('eventModal').addEventListener('click', function(e) {
//     if (e.target === this) {
//         closeEventModal();
//     }
// });

// ESC tuşu ile modalı kapat
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeEventModal();
    }
});

// Etkinlik listesini yeniden yükle
function loadEvents() {
    fetch('get_events.php')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const eventsGrid = document.querySelector('.events-grid');
            eventsGrid.innerHTML = data.html;
        }
    })
    .catch(error => {
        console.error('Error loading events:', error);
        // Fallback olarak sayfayı yenile
        window.location.reload();
    });
}

// Sayfa yüklendiğinde etkinlikleri yükle
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($organizerEvents)): ?>
    loadEvents();
    <?php endif; ?>
});

// Sidebar toggle fonksiyonu - sadece mobilde çalışır
function toggleSidebar() {
    // Sadece mobil cihazlarda çalışsın
    if (window.innerWidth > 768) {
        return;
    }
    
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    const floatingToggle = document.getElementById('floatingToggle');
    
    const isOpen = sidebar.classList.contains('open');
    
    if (isOpen) {
        // Sidebar'ı kapat
        sidebar.classList.remove('open');
        sidebar.classList.add('collapsed');
        mainContent.classList.remove('collapsed');
        mainContent.classList.add('expanded');
        floatingToggle.classList.remove('hide');
        floatingToggle.classList.add('show');
    } else {
        // Sidebar'ı aç
        sidebar.classList.remove('collapsed');
        sidebar.classList.add('open');
        mainContent.classList.remove('expanded');
        mainContent.classList.add('collapsed');
        floatingToggle.classList.remove('show');
        floatingToggle.classList.add('hide');
    }
}

// Sayfa yüklendiğinde mobil kontrolü - Her sayfa değişiminde sidebar kapalı gelsin
function initializeSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    const floatingToggle = document.getElementById('floatingToggle');
    
    if (window.innerWidth <= 768) {
        // Mobilde her zaman kapalı başlasın
        sidebar.classList.remove('open');
        sidebar.classList.add('collapsed');
        mainContent.classList.remove('collapsed');
        mainContent.classList.add('expanded');
        floatingToggle.classList.remove('hide');
        floatingToggle.classList.add('show');
    } else {
        // Masaüstünde normal durum
        sidebar.classList.remove('collapsed', 'open');
        mainContent.classList.remove('expanded', 'collapsed');
        floatingToggle.classList.remove('show');
        floatingToggle.classList.add('hide');
    }
}

// Ekran boyutu değiştiğinde kontrol et
window.addEventListener('resize', function() {
    initializeSidebar();
});

// Sayfa yüklendiğinde başlat
document.addEventListener('DOMContentLoaded', function() {
    initializeSidebar();
});
</script>

</body>
</html>
