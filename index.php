<?php 
require_once 'includes/session.php';
require_once 'config/database.php';
require_once 'classes/Event.php';

// Giriş yapmış kullanıcılar da ana sayfayı görebilir
// Panel yönlendirmesi kaldırıldı - kullanıcılar istedikleri zaman panellerine gidebilir

// Database bağlantısını oluştur
$database = new Database();
$pdo = $database->getConnection();

// Event sınıfını başlat
$event = new Event($pdo);

// Yayında olan etkinlikleri çek (ana sayfa için 6 tane)
$events = $event->getAllEvents(6, 0, '', 'published');

include 'includes/header.php'; 
?>

    <main>
        <!-- Hero Slider Section -->
        <section class="hero-slider">
            <div class="slider-container">
                <div class="slide active" style="background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('https://images.unsplash.com/photo-1501386761578-eac5c94b800a?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80')">
                    <div class="slide-content">
                        <h2>Sezen Aksu Konseri</h2>
                        <p>15 Mart 2024 - Volkswagen Arena</p>
                        <button class="slide-btn">Bilet Al</button>
                    </div>
                </div>
                <div class="slide" style="background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('https://images.unsplash.com/photo-1574391884720-bbc3740c59d1?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2069&q=80')">
                    <div class="slide-content">
                        <h2>Galatasaray vs Fenerbahçe</h2>
                        <p>20 Mart 2024 - Türk Telekom Stadyumu</p>
                        <button class="slide-btn">Bilet Al</button>
                    </div>
                </div>
                <div class="slide" style="background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80')">
                    <div class="slide-content">
                        <h2>Şahsiyet Tiyatro Oyunu</h2>
                        <p>25 Mart 2024 - Devlet Tiyatrosu</p>
                        <button class="slide-btn">Bilet Al</button>
                    </div>
                </div>
                
                <!-- Slider Dots -->
                <div class="slider-dots">
                    <span class="dot active" onclick="currentSlide(1)"></span>
                    <span class="dot" onclick="currentSlide(2)"></span>
                    <span class="dot" onclick="currentSlide(3)"></span>
                </div>
            </div>
        </section>

        <!-- Quick Category Buttons -->
        <section class="quick-categories">
            <div class="container">
                <div class="category-buttons">
                    <button class="category-btn" data-category="konser">
                        <div class="category-btn-icon">🎵</div>
                        <span>Konserler</span>
                    </button>
                    <button class="category-btn" data-category="spor">
                        <div class="category-btn-icon">⚽</div>
                        <span>Spor</span>
                    </button>
                    <button class="category-btn" data-category="tiyatro">
                        <div class="category-btn-icon">🎭</div>
                        <span>Tiyatro</span>
                    </button>
                    <button class="category-btn" data-category="eglence">
                        <div class="category-btn-icon">🎪</div>
                        <span>Eğlence</span>
                    </button>
                    <button class="category-btn" data-category="cocuk">
                        <div class="category-btn-icon">🎈</div>
                        <span>Çocuk</span>
                    </button>
                    <button class="category-btn" data-category="festival">
                        <div class="category-btn-icon">🎉</div>
                        <span>Festival</span>
                    </button>
                </div>
            </div>
        </section>


     <!-- Popular Events Section -->
        <section class="popular-events">
            <div class="container">
                <div class="section-header">
                    <div class="section-left">
                        <h2 class="section-title">Tüm Etkinlikler</h2>
                    </div>
                    
                    <!-- Sıralama Butonları -->
                    <div class="section-right">
                        <div class="sorting-controls">
                            <div class="dropdown">
                                <button class="dropdown-btn">Sırala <span class="dropdown-arrow">▼</span></button>
                                <div class="dropdown-content">
                                    <a href="#" data-sort="all">Tümü</a>
                                    <a href="#" data-sort="date">Tarihe Göre</a>
                                    <a href="#" data-sort="price">Fiyata Göre</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="events-grid view-4">
                    <?php if (!empty($events)): ?>
                        <?php foreach ($events as $evt): ?>
                            <?php
                            $eventParams = http_build_query([
                                'id' => $evt['id'],
                                'title' => $evt['title'],
                                'date' => date('d M Y', strtotime($evt['event_date'])),
                                'venue' => $evt['venue_name'],
                                'location' => $evt['city'],
                                'price' => '₺' . number_format($evt['min_price'], 0),
                                'category' => $evt['category_name']
                            ]);
                            ?>
                            <div class="event-card" onclick="window.location.href='etkinlik-detay.php?<?php echo $eventParams; ?>'" style="cursor: pointer;">
                                <div class="event-image" style="background: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.7)), <?php echo $evt['image_url'] ? 'url(' . $evt['image_url'] . ')' : 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'; ?>">
                                    <div class="event-location"><?php echo htmlspecialchars($evt['city']); ?></div>
                                </div>
                                <div class="event-content">
                                    <h3 class="event-title"><?php echo htmlspecialchars($evt['title']); ?></h3>
                                    <p class="event-venue">🏛️ <?php echo htmlspecialchars($evt['venue_name']); ?></p>
                                    <p class="event-date">📅 <?php echo date('d M Y', strtotime($evt['event_date'])); ?></p>
                                    <div class="event-footer">
                                        <span class="event-price">₺<?php echo number_format($evt['min_price'], 0); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-events">
                            <p>Henüz etkinlik bulunmuyor.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Newsletter Section -->
        <section class="newsletter">
            <div class="container">
                <div class="newsletter-content">
                    <h2>Fırsatları Kaçırma!</h2>
                    <p>Yeni etkinlikler ve özel indirimlerden haberdar olmak için e-bültenimize abone ol.</p>
                    <form class="newsletter-form">
                        <input type="email" placeholder="E-posta adresinizi girin" class="newsletter-input">
                        <button type="submit" class="newsletter-btn">Abone Ol</button>
                    </form>
                </div>
            </div>
        </section>
    </main>

<?php include 'includes/footer.php'; ?>