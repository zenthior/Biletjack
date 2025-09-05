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
                <!-- Sabit Hero Slider İçeriği -->
                <div class="slide active" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%)">
                    <div class="slide-content">
                        <h2>BiletJack'e Hoş Geldiniz</h2>
                        <p>En iyi etkinlikleri keşfedin</p>
                        <button class="slide-btn" onclick="window.location.href='etkinlikler.php'">Etkinlikleri Gör</button>
                    </div>
                </div>
                <div class="slide" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%)">
                    <div class="slide-content">
                        <h2>Konserler, Tiyatrolar ve Daha Fazlası</h2>
                        <p>Binlerce etkinlik arasından seçim yapın</p>
                        <button class="slide-btn" onclick="window.location.href='etkinlikler.php'">Hemen Keşfet</button>
                    </div>
                </div>
                <div class="slide" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)">
                    <div class="slide-content">
                        <h2>Organizatör mü Olmak İstiyorsunuz?</h2>
                        <p>Etkinliklerinizi kolayca yönetin</p>
                        <button class="slide-btn" onclick="window.location.href='organizer-register.php'">Organizatör Ol</button>
                    </div>
                </div>
                

            </div>
        </section>

        <!-- Quick Category Buttons -->
        <section class="quick-categories">
            <div class="container">
                <div class="category-buttons">
                    <button class="category-btn" data-category="indirimler">
                        <div class="category-btn-icon"><img src="SVG/indirim.svg" alt="İndirim" class="category-icon"></div>
                        <span>İndirimler</span>
                    </button>
                    <button class="category-btn" data-category="1">
                        <div class="category-btn-icon"><img src="SVG/music.svg" alt="Konser" class="category-icon"></div>
                        <span>Konserler</span>
                    </button>
                    <button class="category-btn" data-category="2">
                        <div class="category-btn-icon"><img src="SVG/tiyatro.svg" alt="Tiyatro" class="category-icon"></div>
                        <span>Tiyatro</span>
                    </button>
                    <button class="category-btn" data-category="5">
                        <div class="category-btn-icon"><img src="SVG/mikrofon.svg" alt="Stand-Up" class="category-icon"></div>
                        <span>Stand-Up</span>
                    </button>
                    <button class="category-btn" data-category="3">
                        <div class="category-btn-icon"><img src="SVG/festival.svg" alt="Festival" class="category-icon"></div>
                        <span>Festival</span>
                    </button>
                    <button class="category-btn" data-category="4">
                        <div class="category-btn-icon"><img src="SVG/cocuk.svg" alt="Çocuk" class="category-icon"></div>
                        <span>Çocuk</span>
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
                    <?php 
                    // Giriş yapan müşterinin favorilerini al
                    $favoriteEventIds = [];
                    if (function_exists('isLoggedIn') && isLoggedIn() && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'customer') {
                        $stmtFav = $pdo->prepare("SELECT event_id FROM favorites WHERE user_id = ?");
                        $stmtFav->execute([$_SESSION['user_id']]);
                        $favoriteEventIds = array_column($stmtFav->fetchAll(PDO::FETCH_ASSOC), 'event_id');
                        $favoriteEventIds = array_fill_keys($favoriteEventIds, true);
                    }

                    // Sadece yayınlanmış etkinlikleri göster
                    $publishedEvents = array_filter($events, function($e) { return $e['status'] === 'published'; });
                    if (!empty($publishedEvents)): 
                    ?>
                        <?php foreach ($publishedEvents as $evt): 
                            $isFav = isset($favoriteEventIds[$evt['id']]);
                        ?>
                            <div class="event-card" onclick="if (event && event.target && event.target.closest && event.target.closest('.favorite-btn')) return; window.location.href='etkinlik-detay.php?id=<?php echo $evt['id']; ?>'" style="cursor: pointer;">
                                <div class="event-image" style="background: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.7)), <?php echo $evt['image_url'] ? 'url(' . $evt['image_url'] . ')' : 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'; ?>">
                                    <button class="favorite-btn pos-top-left<?php echo $isFav ? ' active' : ''; ?>" aria-label="Favorilere ekle" aria-pressed="<?php echo $isFav ? 'true' : 'false'; ?>" data-event-id="<?php echo $evt['id']; ?>">
                                        <?php echo file_get_contents(__DIR__ . '/SVG/favorites.svg'); ?>
                                    </button>
                                    <div class="event-location"><?php echo htmlspecialchars($evt['city']); ?></div>
                                </div>
                                <div class="event-content">
                                    <h3 class="event-title"><?php echo htmlspecialchars($evt['title']); ?></h3>
                                    <p class="event-venue">🏛️ <?php echo htmlspecialchars($evt['venue_name']); ?></p>
                                    <p class="event-date">📅 <?php 
                                        $months = ['Oca', 'Şub', 'Mar', 'Nis', 'May', 'Haz', 'Tem', 'Ağu', 'Eyl', 'Eki', 'Kas', 'Ara'];
                                        $date = new DateTime($evt['event_date']);
                                        echo $date->format('d') . ' ' . $months[$date->format('n') - 1] . ' ' . $date->format('Y');
                                    ?></p>
                                    <div class="event-footer">
                                        <?php if ($evt['seating_type'] === 'reservation'): ?>
                                            <span class="event-price reservation-label">Rezervasyonlu</span>
                                        <?php else: ?>
                                            <span class="event-price">₺<?php echo number_format($evt['min_price'], 0); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-events">
                            <p>Henüz yayınlanmış etkinlik bulunmuyor.</p>
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

<script>
// Dropdown işlevselliği
document.addEventListener('DOMContentLoaded', function() {
    const dropdown = document.querySelector('.dropdown');
    const dropdownBtn = document.querySelector('.dropdown-btn');
    const dropdownContent = document.querySelector('.dropdown-content');
    const sortLinks = document.querySelectorAll('[data-sort]');
    const eventsGrid = document.querySelector('.events-grid');
    const eventCards = document.querySelectorAll('.event-card');

    // Kategori butonları için event listener ekle
    const categoryButtons = document.querySelectorAll('.category-btn');
    categoryButtons.forEach(button => {
        button.addEventListener('click', function() {
            const category = this.getAttribute('data-category');
            
            if (category === 'indirimler') {
                // İndirimler sayfasına yönlendir
                window.location.href = 'indirimler.php';
            } else {
                // Etkinlikler sayfasına kategori parametresi ile yönlendir
                window.location.href = 'etkinlikler.php?category=' + category;
            }
        });
    });

    // Dropdown açma/kapama
    dropdownBtn.addEventListener('click', function(e) {
        e.preventDefault();
        dropdown.classList.toggle('active');
        dropdownContent.style.display = dropdownContent.style.display === 'block' ? 'none' : 'block';
    });

    // Dropdown dışına tıklandığında kapat
    document.addEventListener('click', function(e) {
        if (!dropdown.contains(e.target)) {
            dropdown.classList.remove('active');
            dropdownContent.style.display = 'none';
        }
    });

    // Sıralama işlevselliği
    sortLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const sortType = this.getAttribute('data-sort');
            sortEvents(sortType);
            dropdown.classList.remove('active');
            dropdownContent.style.display = 'none';
        });
    });

    function sortEvents(sortType) {
        const cards = Array.from(eventCards);
        
        switch(sortType) {
            case 'date':
                cards.sort((a, b) => {
                    const dateA = new Date(a.querySelector('.event-date').textContent.replace('📅 ', ''));
                    const dateB = new Date(b.querySelector('.event-date').textContent.replace('📅 ', ''));
                    return dateA - dateB;
                });
                break;
            case 'price':
                cards.sort((a, b) => {
                    const priceA = parseInt(a.querySelector('.event-price').textContent.replace(/[^0-9]/g, ''));
                    const priceB = parseInt(b.querySelector('.event-price').textContent.replace(/[^0-9]/g, ''));
                    return priceA - priceB;
                });
                break;
            case 'all':
            default:
                // Orijinal sıralama (varsayılan)
                break;
        }
        
        // Kartları yeniden sırala
        cards.forEach(card => {
            eventsGrid.appendChild(card);
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>