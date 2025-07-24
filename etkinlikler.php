<?php 
require_once 'includes/session.php';
require_once 'config/database.php';
require_once 'classes/Event.php';

include 'includes/header.php'; 

// Arama parametrelerini al
$searchKeyword = isset($_GET['search']) ? $_GET['search'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'date';
$location = isset($_GET['location']) ? $_GET['location'] : '';

// Database bağlantısını oluştur
$database = new Database();
$pdo = $database->getConnection();

// Event sınıfını başlat
$event = new Event($pdo);

// Tüm yayınlanmış etkinlikleri çek
$allEvents = $event->getAllEvents(0, 0, '', 'published');

// Kategorileri çek
$categories = $event->getCategories();

// Filtreleme işlemleri
$filteredEvents = $allEvents;

// Arama kelimesine göre filtrele
if (!empty($searchKeyword)) {
    $filteredEvents = array_filter($filteredEvents, function($event) use ($searchKeyword) {
        return stripos($event['title'], $searchKeyword) !== false || 
               stripos($event['venue_name'], $searchKeyword) !== false ||
               stripos($event['city'], $searchKeyword) !== false;
    });
}

// Kategoriye göre filtrele
if (!empty($category)) {
    $filteredEvents = array_filter($filteredEvents, function($event) use ($category) {
        return $event['category_id'] == $category;
    });
}

// Lokasyona göre filtrele
if (!empty($location)) {
    $filteredEvents = array_filter($filteredEvents, function($event) use ($location) {
        return stripos($event['city'], $location) !== false;
    });
}

// Sıralama işlemleri
switch ($sortBy) {
    case 'price_asc':
        usort($filteredEvents, function($a, $b) {
            return $a['price'] - $b['price'];
        });
        break;
    case 'price_desc':
        usort($filteredEvents, function($a, $b) {
            return $b['price'] - $a['price'];
        });
        break;
    case 'date':
        usort($filteredEvents, function($a, $b) {
            return strtotime($a['event_date']) - strtotime($b['event_date']);
        });
        break;
    case 'name':
        usort($filteredEvents, function($a, $b) {
            return strcmp($a['title'], $b['title']);
        });
        break;
}

// Benzersiz şehirler listesi
$cities = array_unique(array_column($allEvents, 'city'));
sort($cities);
?>

<style>
.events-page {
    min-height: 100vh;
    padding: 2rem 0;
}

.events-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
}

.page-header {
    text-align: center;
    margin-bottom: 3rem;
}

.page-title {
    font-size: 2.5rem;
    color: white;
    margin-bottom: 1rem;
    font-weight: 700;
}

.page-subtitle {
    font-size: 1.1rem;
    color: #ccc;
    max-width: 600px;
    margin: 0 auto;
}

.search-highlight {
    background: rgba(102, 126, 234, 0.2);
    padding: 1rem;
    border-radius: 10px;
    margin-bottom: 2rem;
    text-align: center;
    color: white;
}

.filters-section {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border-radius: 15px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.filters-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    align-items: end;
}

.filter-group {
    display: flex;
    flex-direction: column;
}

.filter-label {
    color: white;
    margin-bottom: 0.5rem;
    font-weight: 600;
    font-size: 0.9rem;
}

.filter-input, .filter-select {
    padding: 0.75rem;
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    background: rgba(0, 0, 0, 0.3);
    color: white;
    font-size: 0.9rem;
}

.filter-input::placeholder {
    color: #999;
}

.filter-input:focus, .filter-select:focus {
    outline: none;
    border-color: #667eea;
    background: rgba(0, 0, 0, 0.5);
}

.filter-btn {
    background: #667eea;
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
}

.filter-btn:hover {
    background: #5a67d8;
    transform: translateY(-2px);
}

.clear-filters {
    background: transparent;
    color: #ccc;
    border: 1px solid rgba(255, 255, 255, 0.3);
    padding: 0.75rem 1rem;
    border-radius: 8px;
    cursor: pointer;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.clear-filters:hover {
    background: rgba(255, 255, 255, 0.1);
    color: white;
}

.results-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.results-count {
    color: white;
    font-size: 1.1rem;
}

.sort-controls {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.sort-label {
    color: #ccc;
    font-size: 0.9rem;
}

.sort-select {
    padding: 0.5rem;
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 6px;
    background: rgba(0, 0, 0, 0.3);
    color: white;
    font-size: 0.9rem;
}

.events-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 3rem;
}

.event-card {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(15px);
    border-radius: 15px;
    overflow: hidden;
    transition: all 0.3s ease;
    border: 1px solid rgba(255, 255, 255, 0.2);
    cursor: pointer;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
}

.event-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.4);
    border-color: rgba(255, 255, 255, 0.3);
    background: rgba(255, 255, 255, 0.2);
}

.event-image {
    height: 200px;
    background-size: cover;
    background-position: center;
    position: relative;
    display: flex;
    align-items: flex-end;
    padding: 1rem;
}

.event-category {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.event-location {
    background: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.event-content {
    padding: 1.5rem;
}

.event-title {
    color: white;
    font-size: 1.2rem;
    font-weight: 700;
    margin-bottom: 0.8rem;
    line-height: 1.3;
}

.event-venue {
    color: #e2e8f0;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 500;
}

.event-date {
    color: #e2e8f0;
    font-size: 0.9rem;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 500;
}

.event-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.event-price {
    color: #4ade80;
    font-size: 1.3rem;
    font-weight: 700;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
}

.no-results {
    text-align: center;
    padding: 4rem 2rem;
    color: #ccc;
}

.no-results-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.no-results-title {
    font-size: 1.5rem;
    color: white;
    margin-bottom: 1rem;
}

.no-results-text {
    font-size: 1rem;
    line-height: 1.6;
}

/* Responsive */
@media (max-width: 768px) {
    .page-title {
        font-size: 2rem;
    }
    
    .filters-row {
        grid-template-columns: 1fr;
    }
    
    .results-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .sort-controls {
        justify-content: center;
    }
    
    .events-grid {
        grid-template-columns: 1fr;
    }
    
    .event-card {
        margin: 0 0.5rem;
    }
}
</style>

<main class="events-page">
    <div class="events-container">
        <!-- Sayfa Başlığı -->
        <div class="page-header">
            <h1 class="page-title">Tüm Etkinlikler</h1>
            <p class="page-subtitle">Binlerce etkinlik arasından size en uygun olanını bulun</p>
        </div>

        <!-- Arama Sonucu Vurgusu -->
        <?php if (!empty($searchKeyword)): ?>
        <div class="search-highlight">
            <strong>"<?php echo htmlspecialchars($searchKeyword); ?>"</strong> için arama sonuçları gösteriliyor
        </div>
        <?php endif; ?>

        <!-- Filtreler -->
        <div class="filters-section">
            <form method="GET" action="etkinlikler.php">
                <div class="filters-row">
                    <div class="filter-group">
                        <label class="filter-label">Etkinlik Ara</label>
                        <input type="text" name="search" class="filter-input" 
                               placeholder="Etkinlik, sanatçı, mekan ara..." 
                               value="<?php echo htmlspecialchars($searchKeyword); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">Kategori</label>
                        <select name="category" class="filter-select">
                            <option value="">Tüm Kategoriler</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo ($category == $cat['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">Şehir</label>
                        <select name="location" class="filter-select">
                            <option value="">Tüm Şehirler</option>
                            <?php foreach ($cities as $city): ?>
                            <option value="<?php echo $city; ?>" <?php echo $location === $city ? 'selected' : ''; ?>>
                                <?php echo $city; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <button type="submit" class="filter-btn">Filtrele</button>
                    </div>
                    
                    <div class="filter-group">
                        <a href="etkinlikler.php" class="clear-filters">Temizle</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Sonuçlar Başlığı ve Sıralama -->
        <div class="results-header">
            <div class="results-count">
                <strong><?php echo count($filteredEvents); ?></strong> etkinlik bulundu
            </div>
            
            <div class="sort-controls">
                <span class="sort-label">Sırala:</span>
                <select class="sort-select" onchange="sortEvents(this.value)">
                    <option value="date" <?php echo $sortBy === 'date' ? 'selected' : ''; ?>>Tarihe Göre</option>
                    <option value="price_asc" <?php echo $sortBy === 'price_asc' ? 'selected' : ''; ?>>Fiyat (Düşük-Yüksek)</option>
                    <option value="price_desc" <?php echo $sortBy === 'price_desc' ? 'selected' : ''; ?>>Fiyat (Yüksek-Düşük)</option>
                    <option value="name" <?php echo $sortBy === 'name' ? 'selected' : ''; ?>>İsme Göre</option>
                </select>
            </div>
        </div>

        <!-- Etkinlikler Grid -->
        <?php if (count($filteredEvents) > 0): ?>
        <div class="events-grid">
            <?php foreach ($filteredEvents as $event): 
                // Minimum fiyatı al
                $minPrice = $event['min_price'];
                
                // Kategori adını bul
                $categoryName = '';
                foreach ($categories as $cat) {
                    if ($cat['id'] == $event['category_id']) {
                        $categoryName = $cat['name'];
                        break;
                    }
                }
            ?>
            <div class="event-card" onclick="window.location.href='etkinlik-detay.php?id=<?php echo $event['id']; ?>'">
                <div class="event-image" style="background: <?php echo !empty($event['image_url']) ? 'url(' . htmlspecialchars($event['image_url']) . ') center/cover' : 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'; ?>">
                    <div class="event-category"><?php echo htmlspecialchars($categoryName); ?></div>
                    <div class="event-location"><?php echo isset($event['city']) ? htmlspecialchars($event['city']) : 'Konum Belirtilmemiş'; ?></div>
                </div>
                <div class="event-content">
                    <h3 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h3>
                    <p class="event-venue">🏛️ <?php echo isset($event['venue_name']) ? htmlspecialchars($event['venue_name']) : 'Mekan Belirtilmemiş'; ?></p>
                    <p class="event-date">📅 <?php echo date('d M Y - H:i', strtotime($event['event_date'])); ?></p>
                    <div class="event-footer">
                        <?php if ($minPrice && $minPrice > 0): ?>
                            <span class="event-price">₺<?php echo number_format($minPrice, 0, ',', '.'); ?></span>
                        <?php else: ?>
                            <span class="event-price">Fiyat Bilgisi Yok</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <!-- Sonuç Bulunamadı -->
        <div class="no-results">
            <div class="no-results-icon">🔍</div>
            <h3 class="no-results-title">Etkinlik Bulunamadı</h3>
            <p class="no-results-text">
                Henüz yayınlanmış etkinlik bulunmuyor veya aradığınız kriterlere uygun etkinlik bulunamadı.<br>
                Lütfen farklı filtreler deneyiniz.
            </p>
        </div>
        <?php endif; ?>
    </div>
</main>

<script>
function sortEvents(sortBy) {
    const url = new URL(window.location);
    url.searchParams.set('sort', sortBy);
    window.location.href = url.toString();
}

// Kategori butonlarından gelen yönlendirmeleri yakala
if (window.location.hash) {
    const category = window.location.hash.substring(1);
    if (category) {
        const url = new URL(window.location);
        url.searchParams.set('category', category);
        url.hash = '';
        window.location.href = url.toString();
    }
}
</script>

<?php include 'includes/footer.php'; ?>