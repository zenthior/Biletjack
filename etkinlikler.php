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
    background: #f8f9fa;
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
    color: #1a1a1a;
    margin-bottom: 1rem;
    font-weight: 700;
}

.page-subtitle {
    font-size: 1.1rem;
    color: #666;
    max-width: 600px;
    margin: 0 auto;
}

.search-highlight {
    background: #e3f2fd;
    padding: 1rem;
    border-radius: 10px;
    margin-bottom: 2rem;
    text-align: center;
    color: #1565c0;
    border: 1px solid #bbdefb;
}

.filters-section {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    border: 1px solid #e0e0e0;
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
    color: #1a1a1a;
    margin-bottom: 0.5rem;
    font-weight: 600;
    font-size: 0.9rem;
}

.filter-input, .filter-select {
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 8px;
    background: white;
    color: #1a1a1a;
    font-size: 0.9rem;
}

.filter-input::placeholder {
    color: #666;
}

.filter-input:focus, .filter-select:focus {
    outline: none;
    border-color: #667eea;
    background: white;
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
    color: #666;
    border: 1px solid #ddd;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    cursor: pointer;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.clear-filters:hover {
    background: #f8f9fa;
    color: #1a1a1a;
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
    color: #1a1a1a;
    font-size: 1.1rem;
}

.sort-controls {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.sort-label {
    color: #666;
    font-size: 0.9rem;
}

.sort-select {
    padding: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 6px;
    background: white;
    color: #1a1a1a;
    font-size: 0.9rem;
}

.events-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, 280px);
    gap: 144px;
    margin-bottom: 3rem;
    justify-content: center;
}

.event-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    transition: all 0.3s ease;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    border: none;
    width: 280px;
    height: 380px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
}

.event-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
}

.event-image {
    height: 200px;
    background-size: cover;
    background-position: center;
    position: relative;
    border-radius: 16px 16px 0 0;
    flex-shrink: 0;
}

.event-category {
    position: absolute;
    top: 12px;
    left: 12px;
    background: rgba(0, 0, 0, 0.6);
    color: white;
    padding: 4px 8px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 500;
    backdrop-filter: blur(10px);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.event-location {
    position: absolute;
    right: 12px;
    background: rgba(0, 0, 0, 0.6);
    color: white;
    padding: 2px 8px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 500;
    backdrop-filter: blur(10px);
}

.event-content {
    padding: 16px;
    background: white;
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.event-title {
    color: #1a1a1a;
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 8px;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.event-venue {
    color: #8e8e93;
    font-size: 14px;
    margin-bottom: 4px;
    font-weight: 400;
    line-height: 1.3;
}

.event-date {
    color: #8e8e93;
    font-size: 14px;
    margin-bottom: 16px;
    font-weight: 400;
    line-height: 1.3;
}

.event-footer {
    margin-top: auto;
}

.event-price {
    color: #00C896;
    font-size: 18px;
    font-weight: 600;
    letter-spacing: -0.3px;
}

.no-results {
    text-align: center;
    padding: 4rem 2rem;
    color: #666;
}

.no-results-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.no-results-title {
    font-size: 1.5rem;
    color: #1a1a1a;
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
        grid-template-columns: repeat(auto-fill, 280px);
        gap: 16px;
        padding: 0 16px;
    }
    
    .event-card {
        width: 280px;
    }
}

@media (max-width: 320px) {
    .events-grid {
        grid-template-columns: 1fr;
        padding: 0 16px;
    }
    
    .event-card {
        width: 100%;
        max-width: 280px;
    }
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.5rem;
    margin-top: 3rem;
}

.pagination a, .pagination span {
    padding: 0.75rem 1rem;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
}

.pagination a {
    background: white;
    color: #1a1a1a;
    border: 1px solid #ddd;
}

.pagination a:hover {
    background: #f8f9fa;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.pagination .current {
    background: #667eea;
    color: white;
    border: 1px solid #667eea;
}

.pagination .disabled {
    background: #f8f9fa;
    color: #999;
    border: 1px solid #e0e0e0;
    cursor: not-allowed;
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
                    <p class="event-venue"><?php echo isset($event['venue_name']) ? htmlspecialchars($event['venue_name']) : 'Mekan Belirtilmemiş'; ?></p>
                    <p class="event-date"><?php echo date('d M Y', strtotime($event['event_date'])) . ' - ' . date('H:i', strtotime($event['event_date'])); ?></p>
                    <div class="event-footer">
                        <?php if ($minPrice && $minPrice > 0): ?>
                            <span class="event-price"><?php echo number_format($minPrice, 0, ',', '.'); ?>₺</span>
                        <?php else: ?>
                            <span class="event-price">Ücretsiz</span>
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