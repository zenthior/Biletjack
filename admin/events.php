<?php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../classes/User.php';
require_once '../classes/Organizer.php';

// Admin kontrolü
requireAdmin();

// Database bağlantısını oluştur
$database = new Database();
$pdo = $database->getConnection();

// Sıralama ve filtreleme parametreleri
$sortBy = $_GET['sort'] ?? 'created_at';
$sortOrder = $_GET['order'] ?? 'DESC';
$categoryFilter = $_GET['category'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$organizerFilter = $_GET['organizer'] ?? '';
$search = $_GET['search'] ?? '';

// Geçerli sıralama seçenekleri
$validSortColumns = ['title', 'category', 'venue_name', 'event_date', 'created_at', 'organizer_name'];
if (!in_array($sortBy, $validSortColumns)) {
    $sortBy = 'created_at';
}

$validSortOrders = ['ASC', 'DESC'];
if (!in_array($sortOrder, $validSortOrders)) {
    $sortOrder = 'DESC';
}

// Etkinlikleri getir
$query = "
    SELECT 
        e.*,
        COALESCE(o.company_name, u.email, 'Bilinmiyor') as organizer_name,
        u.email as organizer_email,
        (SELECT COUNT(*) FROM tickets t WHERE t.event_id = e.id) as ticket_count,
        (SELECT COUNT(*) FROM event_followers ef WHERE ef.event_id = e.id) as follower_count
    FROM events e 
    LEFT JOIN organizers o ON e.organizer_id = o.id 
    LEFT JOIN users u ON o.user_id = u.id
    WHERE 1=1
";

$params = [];

// Filtreleme
if (!empty($search)) {
    $query .= " AND (e.title LIKE :search OR e.venue_name LIKE :search OR o.company_name LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($categoryFilter)) {
    $query .= " AND e.category = :category";
    $params[':category'] = $categoryFilter;
}

if (!empty($statusFilter)) {
    $query .= " AND e.status = :status";
    $params[':status'] = $statusFilter;
}

if (!empty($organizerFilter)) {
    $query .= " AND e.organizer_id = :organizer";
    $params[':organizer'] = $organizerFilter;
}

// Sıralama
$query .= " ORDER BY " . $sortBy . " " . $sortOrder;

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Kategorileri getir - Sabit kategoriler kullan
$categories = ['Konser', 'Festival', 'Standup', 'Tiyatro', 'Spor', 'Çocuk'];

// Veritabanından da kategorileri al
$categoryQuery = "SELECT DISTINCT category FROM events WHERE category IS NOT NULL AND category != ''";
$categoryStmt = $pdo->prepare($categoryQuery);
$categoryStmt->execute();
$dbCategories = $categoryStmt->fetchAll(PDO::FETCH_COLUMN);
$categories = array_unique(array_merge($categories, $dbCategories));

// Organizatörleri getir
$organizerQuery = "SELECT id, company_name as name FROM organizers ORDER BY company_name";
$organizerStmt = $pdo->prepare($organizerQuery);
$organizerStmt->execute();
$organizers = $organizerStmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="admin-container">
    <!-- Ultra Modern Sidebar -->
    <div class="admin-sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <img src="../uploads/logo.png" alt="BiletJack Logo" style="width: 120px; height: 120px; object-fit: contain;">
            </div>
            <h2 class="sidebar-title">Etkinlikler</h2>
            <p class="sidebar-subtitle">Admin Dashboard</p>
        </div>
        
        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">Ana Menü</div>
                <a href="index.php" class="nav-item">
                    <i class="fas fa-chart-pie"></i>    
                    Gösterge Paneli
                </a>
                <a href="analytics.php" class="nav-item">
                    <i class="fas fa-chart-line"></i>
                    Analytics
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Yönetim</div>
                <a href="users.php" class="nav-item">
                    <i class="fas fa-users"></i>
                    Kullanıcılar
                </a>
                <a href="organizers.php" class="nav-item">
                    <i class="fas fa-building"></i>
                    Organizatörler
                </a>
                <a href="events.php" class="nav-item active">
                    <i class="fas fa-calendar-alt"></i>
                    Etkinlikler
                </a>
                <a href="orders.php" class="nav-item">
                    <i class="fas fa-shopping-cart"></i>
                    Siparişler
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Sistem</div>
                <a href="settings.php" class="nav-item">
                    <i class="fas fa-cog"></i>
                    Ayarlar
                </a>
                <a href="reports.php" class="nav-item">
                    <i class="fas fa-file-alt"></i>
                    Raporlar
                </a>
                <a href="../index.php" class="nav-item">
                    <i class="fas fa-home"></i>
                    Ana Sayfa
                </a>
            </div>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="admin-main">
        <div class="admin-header">
            <div class="header-content">
                <div class="header-left">
                    <button class="mobile-menu-toggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="page-title">
                        <i class="fas fa-calendar-alt"></i>
                        Etkinlik Yönetimi
                    </h1>
                    <p class="page-subtitle">Tüm etkinlikleri görüntüleyin ve yönetin</p>
                </div>
                <div class="header-right">
                    <div class="header-stats">
                        <div class="stat-item">
                            <span class="stat-number"><?php echo count($events); ?></span>
                            <span class="stat-label">Toplam Etkinlik</span>
                        </div>
                    </div>
                    
                    <button class="header-notifications">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge"></span>
                    </button>
                    
                    <a href="../auth/logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                        Çıkış
                    </a>
                </div>
            </div>
        </div>

        <div class="admin-content">
            <!-- Filters and Search -->
            <div>
                <div class="filters-section">
                    <form method="GET" class="filters-form">
                        <div class="filter-group">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" name="search" placeholder="Etkinlik, mekan veya organizatör ara..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>
                        
                        <div class="filter-group">
                            <select name="category" class="filter-select">
                                <option value="">Tüm Kategoriler</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo htmlspecialchars($category); ?>" <?php echo $categoryFilter === $category ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <select name="organizer" class="filter-select">
                                <option value="">Tüm Organizatörler</option>
                                <?php foreach ($organizers as $organizer): ?>
                                    <option value="<?php echo $organizer['id']; ?>" <?php echo $organizerFilter == $organizer['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($organizer['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <select name="status" class="filter-select">
                                <option value="">Tüm Durumlar</option>
                                <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Aktif</option>
                                <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Beklemede</option>
                                <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>İptal</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <select name="sort" class="filter-select">
                                <option value="created_at" <?php echo $sortBy === 'created_at' ? 'selected' : ''; ?>>Oluşturma Tarihi</option>
                                <option value="title" <?php echo $sortBy === 'title' ? 'selected' : ''; ?>>Etkinlik Adı</option>
                                <option value="category" <?php echo $sortBy === 'category' ? 'selected' : ''; ?>>Kategori</option>
                                <option value="venue_name" <?php echo $sortBy === 'venue_name' ? 'selected' : ''; ?>>Mekan</option>
                                <option value="event_date" <?php echo $sortBy === 'event_date' ? 'selected' : ''; ?>>Etkinlik Tarihi</option>
                                <option value="organizer_name" <?php echo $sortBy === 'organizer_name' ? 'selected' : ''; ?>>Organizatör</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <select name="order" class="filter-select">
                                <option value="DESC" <?php echo $sortOrder === 'DESC' ? 'selected' : ''; ?>>Azalan</option>
                                <option value="ASC" <?php echo $sortOrder === 'ASC' ? 'selected' : ''; ?>>Artan</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="filter-btn">
                            <i class="fas fa-filter"></i>
                            Filtrele
                        </button>
                        
                        <a href="events.php" class="reset-btn">
                            <i class="fas fa-undo"></i>
                            Sıfırla
                        </a>
                    </form>
                </div>
            </div>

            <!-- Events Table -->
            <div class="table-container">
                <div class="modern-table">
                    <div class="table-header">
                        <div class="table-title">
                            <i class="fas fa-list"></i>
                            Etkinlik Listesi
                        </div>
                    </div>
                    
                    <?php if (empty($events)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-calendar-times"></i>
                            </div>
                            <h3>Etkinlik Bulunamadı</h3>
                            <p>Aradığınız kriterlere uygun etkinlik bulunmuyor.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-content">
                            <?php foreach ($events as $event): ?>
                                <div class="event-card" data-event-id="<?php echo $event['id']; ?>">
                                    <div class="event-main">
                                        <div class="event-image">
                                            <?php if (!empty($event['image_url'])): ?>
                                                <img src="../<?php echo htmlspecialchars($event['image_url']); ?>" alt="Event Image">
                                            <?php else: ?>
                                                <div class="event-placeholder">
                                                    <i class="fas fa-calendar-alt"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="event-info">
                                            <div class="event-header">
                                                <h3 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h3>
                                                <div class="event-status status-<?php echo $event['status']; ?>">
                                                    <?php 
                                                    switch($event['status']) {
                                                        case 'active': echo 'Aktif'; break;
                                                        case 'published': echo 'Yayında'; break;
                                                        case 'pending': echo 'Beklemede'; break;
                                                        case 'cancelled': echo 'İptal'; break;
                                                        default: echo 'Bilinmiyor';
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                            
                                            <div class="event-details">
                                                <div class="detail-item">
                                                    <i class="fas fa-tag"></i>
                                                    <span><?php echo htmlspecialchars($event['category'] ?: 'Kategori Yok'); ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                    <span><?php echo htmlspecialchars($event['venue_name']); ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <i class="fas fa-user-tie"></i>
                                                    <span><?php echo htmlspecialchars($event['organizer_name'] ?: 'Bilinmiyor'); ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <i class="fas fa-calendar"></i>
                                                    <span><?php echo date('d.m.Y H:i', strtotime($event['event_date'])); ?></span>
                                                </div>
                                            </div>
                                            
                                            <div class="event-stats">
                                                <div class="stat">
                                                    <i class="fas fa-ticket-alt"></i>
                                                    <span><?php echo $event['ticket_count']; ?> Bilet</span>
                                                </div>
                                                <div class="stat">
                                                    <i class="fas fa-heart"></i>
                                                    <span><?php echo $event['follower_count']; ?> Takipçi</span>
                                                </div>
                                                <div class="stat">
                                                    <i class="fas fa-clock"></i>
                                                    <span><?php echo date('d.m.Y', strtotime($event['created_at'])); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="event-actions">
                                        <div class="actions-dropdown">
                                            <button class="actions-btn" onclick="event.stopPropagation(); var dropdown = document.getElementById('dropdown-<?php echo $event['id']; ?>'); if(dropdown) { dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block'; }">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <div class="event-dropdown-menu" id="dropdown-<?php echo $event['id']; ?>">
                                                <a href="#" class="event-dropdown-item" onclick="showStatistics(<?php echo $event['id']; ?>)">
                                    <i class="fas fa-chart-bar"></i>
                                    İstatistikler
                                </a>
                                <a href="#" class="event-dropdown-item" onclick="showDetails(<?php echo $event['id']; ?>)">
                                    <i class="fas fa-info-circle"></i>
                                    Etkinlik Detayları
                                </a>
                                <a href="users.php?event_id=<?php echo $event['id']; ?>" class="event-dropdown-item">
                                    <i class="fas fa-users"></i>
                                    Katılımcılar
                                </a>
                                <a href="#" class="event-dropdown-item" onclick="showComments(<?php echo $event['id']; ?>)">
                                    <i class="fas fa-comments"></i>
                                    Yorumlar
                                </a>
                                <div class="dropdown-divider"></div>
                                <a href="#" class="event-dropdown-item danger" onclick="deleteEvent(<?php echo $event['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                    Sil
                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
<div id="statisticsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-chart-bar"></i> Etkinlik İstatistikleri</h3>
            <button class="modal-close" onclick="closeModal('statisticsModal')">&times;</button>
        </div>
        <div class="modal-body" id="statisticsContent">
            <!-- İstatistik içeriği buraya yüklenecek -->
        </div>
    </div>
</div>

<div id="participantsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-users"></i> Katılımcılar</h3>
            <button class="modal-close" onclick="closeModal('participantsModal')">&times;</button>
        </div>
        <div class="modal-body" id="participantsContent">
            <!-- Katılımcı içeriği buraya yüklenecek -->
        </div>
    </div>
</div>

<div id="commentsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-comments"></i> Yorumlar</h3>
            <button class="modal-close" onclick="closeModal('commentsModal')">&times;</button>
        </div>
        <div class="modal-body" id="commentsContent">
            <!-- Yorum içeriği buraya yüklenecek -->
        </div>
    </div>
</div>

<div id="detailsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-info-circle"></i> Etkinlik Detayları</h3>
            <button class="modal-close" onclick="closeModal('detailsModal')">&times;</button>
        </div>
        <div class="modal-body" id="detailsContent">
            <!-- Etkinlik detay içeriği buraya yüklenecek -->
        </div>
    </div>
</div>

<style>
/* Events Page Specific Styles */
.admin-main {
    margin-left: var(--sidebar-width);
    min-height: 100vh;
    background: var(--bg-secondary);
    width: calc(100% - var(--sidebar-width));
    max-width: none;
    padding: 0;
}

.admin-header {
    background: var(--bg-primary);
    border-bottom: 1px solid var(--gray-200);
    padding: 2rem;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    max-width: 1400px;
    margin: 0 auto;
}

.page-title {
    font-size: 2rem;
    font-weight: 700;
    color: var(--gray-900);
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
}

.page-subtitle {
    color: var(--gray-600);
    font-size: 1rem;
}

.header-stats {
    display: flex;
    gap: 2rem;
}

.stat-item {
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 2rem;
    font-weight: 700;
    color: var(--primary);
}

.stat-label {
    font-size: 0.875rem;
    color: var(--gray-600);
}

.admin-content {
    padding: 2rem;
    max-width: 1400px;
    margin: 0 auto;
}

/* Filters */
.content-header {
    margin-bottom: 7rem;
}

.filters-form {
    display: flex;
    gap: 1rem;
    align-items: center;
    flex-wrap: wrap;
    background: var(--bg-primary);
    padding: 1.5rem;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-sm);
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.search-box {
    position: relative;
    min-width: 300px;
}

.search-box i {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--gray-400);
}

.search-box input {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 2.5rem;
    border: 1px solid var(--gray-300);
    border-radius: var(--border-radius);
    font-size: 0.875rem;
    transition: all 0.2s;
}

.search-box input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.filter-select {
    padding: 0.75rem 1rem;
    border: 1px solid var(--gray-300);
    border-radius: var(--border-radius);
    font-size: 0.875rem;
    background: white;
    min-width: 150px;
    transition: all 0.2s;
}

.filter-select:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.filter-btn, .reset-btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: var(--border-radius);
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.filter-btn {
    background: var(--gradient-primary);
    color: white;
}

.filter-btn:hover {
    transform: translateY(-1px);
    box-shadow: var(--shadow-md);
}

.reset-btn {
    background: var(--gray-100);
    color: var(--gray-700);
    border: 1px solid var(--gray-300);
}

.reset-btn:hover {
    background: var(--gray-200);
    color: var(--gray-900);
}

/* Table Container */
.table-container {
    background: var(--bg-primary);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-sm);
    overflow: visible;
}

.table-header {
    padding: 1.5rem 2rem;
    border-bottom: 1px solid var(--gray-200);
    background: var(--gray-50);
}

.table-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--gray-900);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Event Cards */
.table-content {
    padding: 1rem;
}

.event-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem;
    border: 1px solid var(--gray-200);
    border-radius: var(--border-radius-lg);
    margin-bottom: 1rem;
    background: var(--bg-primary);
    transition: all 0.2s;
}

.event-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
    border-color: var(--primary-light);
}

.event-main {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    flex: 1;
}

.event-image {
    width: 80px;
    height: 80px;
    border-radius: var(--border-radius);
    overflow: hidden;
    flex-shrink: 0;
}

.event-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.event-placeholder {
    width: 100%;
    height: 100%;
    background: var(--gray-100);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--gray-400);
    font-size: 1.5rem;
}

.event-info {
    flex: 1;
}

.event-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
}

.event-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--gray-900);
    margin: 0;
}

.event-status {
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-active {
    background: #dcfce7;
    color: #166534;
}

.status-pending {
    background: #fef3c7;
    color: #92400e;
}

.status-cancelled {
    background: #fee2e2;
    color: #991b1b;
}

.event-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.detail-item {
    display: flex;
    flex-direction: row;
    justify-content: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: var(--gray-600);
}

.detail-item i {
    color: var(--primary);
    width: 16px;
}

.event-stats {
    display: flex;
    gap: 1.5rem;
}

.stat {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: var(--gray-600);
}

.stat i {
    color: var(--primary);
}

/* Actions */
.event-actions {
    position: relative;
}

.actions-dropdown {
    position: relative;
}

.actions-btn {
    width: 40px;
    height: 40px;
    border: none;
    background: var(--gray-100);
    border-radius: var(--border-radius);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--gray-600);
    transition: all 0.2s;
}

.actions-btn:hover {
    background: var(--gray-200);
    color: var(--gray-900);
}

.event-dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background: var(--bg-primary);
    border: 1px solid var(--gray-200);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-lg);
    min-width: 200px;
    z-index: 99999;
    display: none;
    overflow: hidden;
}

.event-dropdown-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    color: var(--gray-700);
    text-decoration: none;
    font-size: 0.875rem;
    transition: all 0.2s;
}

.event-dropdown-item:hover {
    background: var(--gray-50);
    color: var(--gray-900);
}

.event-dropdown-item.danger {
    color: var(--danger);
}

.event-dropdown-item.danger:hover {
    background: #fee2e2;
    color: var(--danger);
}

.dropdown-divider {
    height: 1px;
    background: var(--gray-200);
    margin: 0.5rem 0;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: var(--gray-500);
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
    color: var(--gray-300);
}

.empty-state h3 {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
    color: var(--gray-700);
}

/* Modals */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 10000;
    align-items: center;
    justify-content: center;
}

.modal.show {
    display: flex;
}

.modal-content {
    background: var(--bg-primary);
    border-radius: var(--border-radius-lg);
    max-width: 800px;
    width: 90%;
    max-height: 80vh;
    overflow: hidden;
    box-shadow: var(--shadow-xl);
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem 2rem;
    border-bottom: 1px solid var(--gray-200);
    background: var(--gray-50);
}

.modal-header h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--gray-900);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.modal-close {
    width: 32px;
    height: 32px;
    border: none;
    background: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--gray-500);
    border-radius: var(--border-radius);
    transition: all 0.2s;
}

.modal-close:hover {
    background: var(--gray-200);
    color: var(--gray-900);
}

.modal-body {
    padding: 2rem;
    max-height: 60vh;
    overflow-y: auto;
}

/* Responsive */
@media (max-width: 1024px) {
    .admin-main {
        margin-left: 0;
        width: 100%;
    }
    
    .admin-sidebar {
        transform: translateX(-100%);
    }
    
    .filters-form {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-group {
        width: 100%;
    }
    
    .search-box {
        min-width: auto;
    }
}

@media (max-width: 768px) {
    .event-main {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .event-details {
        grid-template-columns: 1fr;
    }
    
    .event-stats {
        flex-wrap: wrap;
    }
    
    .header-content {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
}

/* Modal Content Styles */
.stats-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    justify-content: space-between;
}

.stats-grid .stat-card {
    flex: 1;
    min-width: 180px;
}

.stat-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.5rem;
    background: var(--gray-50);
    border-radius: var(--border-radius-lg);
    border: 1px solid var(--gray-200);
}

.stat-icon {
    width: 48px;
    height: 48px;
    background: var(--gradient-primary);
    border-radius: var(--border-radius);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.25rem;
}

.stat-info h4 {
    margin: 0 0 0.5rem 0;
    font-size: 0.875rem;
    color: var(--gray-600);
    font-weight: 500;
}

.stat-number {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--gray-900);
}

/* Details Modal Styles */
.details-container {
    max-height: 70vh;
    overflow-y: auto;
}

.details-section {
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid var(--gray-200);
}

.details-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.details-section h3 {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0 0 1rem 0;
    font-size: 1.125rem;
    color: var(--gray-900);
    font-weight: 600;
}

.details-section h3 i {
    color: var(--primary);
}

.detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.detail-item {
    display: flex;
    gap: 0.25rem;
}

.detail-item label {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--gray-600);
}

.detail-item span {
    font-size: 0.875rem;
    color: var(--gray-900);
}

.tags {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.tag {
    padding: 0.25rem 0.75rem;
    background: var(--primary-light);
    color: var(--primary);
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
}

/* Ticket Types */
.ticket-types {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.ticket-type {
    padding: 1rem;
    background: var(--gray-50);
    border: 1px solid var(--gray-200);
    border-radius: var(--border-radius);
}

.ticket-info h4 {
    margin: 0 0 0.5rem 0;
    color: var(--gray-900);
    font-weight: 600;
}

.ticket-info p {
    margin: 0;
    color: var(--gray-600);
    font-size: 0.875rem;
}

/* Comments Summary */
.comments-summary {
    background: var(--gray-50);
    padding: 1.5rem;
    border-radius: var(--border-radius);
    border: 1px solid var(--gray-200);
}

.comment-stats {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
    align-items: center;
}

.rating {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--primary);
}

.comment-count {
    color: var(--gray-600);
    font-size: 0.875rem;
}

.recent-comments {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.comment-preview {
    padding: 0.75rem;
    background: white;
    border-radius: var(--border-radius);
    font-size: 0.875rem;
    color: var(--gray-700);
}

/* Image Gallery */
.image-gallery {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
}

.gallery-item {
    aspect-ratio: 1;
    border-radius: var(--border-radius);
    overflow: hidden;
    border: 1px solid var(--gray-200);
}

.gallery-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.2s;
}

.gallery-item:hover img {
    transform: scale(1.05);
}

/* Loading */
.loading {
    text-align: center;
    padding: 2rem;
    color: var(--gray-500);
    font-style: italic;
}

</style>

<script>
// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.actions-dropdown')) {
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                menu.classList.remove('show');
            });
        }
    });
});

// Modal functions
function showModal(modalId) {
    document.getElementById(modalId).classList.add('show');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('show');
}

// Statistics modal
function showStatistics(eventId) {
    // Close dropdown
    document.getElementById(`dropdown-${eventId}`).classList.remove('show');
    
    // Show loading
    document.getElementById('statisticsContent').innerHTML = '<div class="loading">Yükleniyor...</div>';
    showModal('statisticsModal');
    
    // TODO: Load statistics via AJAX
    setTimeout(() => {
        document.getElementById('statisticsContent').innerHTML = `
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-lira-sign"></i></div>
                    <div class="stat-info">
                        <h4>Toplam Brüt Satış</h4>
                        <p class="stat-number">₺12,450</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-ticket-alt"></i></div>
                    <div class="stat-info">
                        <h4>Satılan Biletler</h4>
                        <p class="stat-number">156</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-eye"></i></div>
                    <div class="stat-info">
                        <h4>Görüntülenme</h4>
                        <p class="stat-number">2,341</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-comments"></i></div>
                    <div class="stat-info">
                        <h4>Katılımcı Yorumları</h4>
                        <p class="stat-number">23</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
                    <div class="stat-info">
                        <h4>Sepetteki Kullanıcılar</h4>
                        <p class="stat-number">8</p>
                    </div>
                </div>
            </div>
        `;
    }, 1000);
}

// Event Details modal
function showDetails(eventId) {
    // Close dropdown
    document.getElementById(`dropdown-${eventId}`).classList.remove('show');
    
    // Show loading
    document.getElementById('detailsContent').innerHTML = '<div class="loading">Yükleniyor...</div>';
    showModal('detailsModal');
    
    // Load event details via AJAX
    fetch(`get_event_details.php?id=${eventId}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                document.getElementById('detailsContent').innerHTML = `<div class="error">Hata: ${data.error}</div>`;
                return;
            }
            
            const event = data.event;
            const tickets = data.tickets;
            const comments = data.comments;
            const images = data.images;
            const tags = data.tags;
            const artists = data.artists;
            
            // Format date
            const eventDate = new Date(event.event_date);
            const formattedDate = eventDate.toLocaleDateString('tr-TR', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            const formattedTime = eventDate.toLocaleTimeString('tr-TR', {
                hour: '2-digit',
                minute: '2-digit'
            });
            
            const createdDate = new Date(event.created_at);
            const formattedCreatedDate = createdDate.toLocaleDateString('tr-TR', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            
            // Generate tags HTML
            const tagsHtml = tags.map(tag => `<span class="tag">${tag.trim()}</span>`).join('');
            
            // Generate artists HTML
            const artistsHtml = artists.length > 0 ? artists.join(', ') : 'Belirtilmemiş';
            
            // Generate tickets HTML
            const ticketsHtml = tickets.map(ticket => `
                <div class="ticket-type">
                    <div class="ticket-info">
                        <h4>${ticket.name}</h4>
                        <p>${event.seating_type === 'reservation' ? 'Rezervasyonlu' : '₺' + ticket.price} - Stok: ${ticket.quantity}</p>
                    </div>
                </div>
            `).join('');
            
            // Generate comments HTML
            const commentsHtml = comments.length > 0 ? comments.map(comment => `
                <div class="comment-preview">
                    <strong>${comment.user_email || 'Anonim'}:</strong> "${comment.comment}"
                </div>
            `).join('') : '<div class="comment-preview">Henüz yorum yapılmamış.</div>';
            
            // Generate images HTML
            const imagesHtml = images.length > 0 ? images.map(image => `
                <div class="gallery-item">
                    <img src="../uploads/events/${image.image_path}" alt="Etkinlik Görseli" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgdmlld0JveD0iMCAwIDEwMCAxMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiBmaWxsPSIjRjNGNEY2Ii8+CjxwYXRoIGQ9Ik0zNSA0MEg2NVY2MEgzNVY0MFoiIGZpbGw9IiM5Q0EzQUYiLz4KPC9zdmc+'">
                </div>
            `).join('') : '<div class="gallery-item"><div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #9CA3AF;">Görsel yok</div></div>';
        document.getElementById('detailsContent').innerHTML = `
             <div class="details-container">
                 <div class="details-section">
                     <h3><i class="fas fa-info-circle"></i> Genel Bilgi</h3>
                     <div class="detail-grid">
                         <div class="detail-item">
                             <label>Durum:</label>
                             <span class="status-${event.status}">${data.status_text}</span>
                         </div>
                         <div class="detail-item">
                             <label>Başlık:</label>
                             <span>${event.title}</span>
                         </div>
                         <div class="detail-item">
                             <label>Organizatör:</label>
                             <span>${event.organizer_name}</span>
                         </div>
                         <div class="detail-item">
                             <label>Oluşturulma Tarihi:</label>
                             <span>${formattedCreatedDate}</span>
                         </div>
                         <div class="detail-item">
                             <label>Görüntülenme:</label>
                             <span>${event.views || 0}</span>
                         </div>
                         <div class="detail-item">
                             <label>Sanatçılar:</label>
                             <span>${artistsHtml}</span>
                         </div>
                         <div class="detail-item">
                             <label>Etiketler:</label>
                             <span class="tags">${tagsHtml}</span>
                         </div>
                     </div>
                 </div>
                
                <div class="details-section">
                     <h3><i class="fas fa-calendar-alt"></i> Etkinlik Detayları</h3>
                     <div class="detail-grid">
                         <div class="detail-item">
                             <label>Tarih:</label>
                             <span>${formattedDate}</span>
                         </div>
                         <div class="detail-item">
                             <label>Saat:</label>
                             <span>${formattedTime}</span>
                         </div>
                         <div class="detail-item">
                             <label>Mekan:</label>
                             <span>${event.venue_name}</span>
                         </div>
                         <div class="detail-item">
                             <label>Adres:</label>
                             <span>${event.venue_address || 'Belirtilmemiş'}</span>
                         </div>
                         <div class="detail-item">
                             <label>Kapasite:</label>
                             <span>${event.capacity || 'Belirtilmemiş'} kişi</span>
                         </div>
                         <div class="detail-item">
                             <label>Kategori:</label>
                             <span>${event.category}</span>
                         </div>
                     </div>
                 </div>
                
                <div class="details-section">
                     <h3><i class="fas fa-ticket-alt"></i> Bilet Detayları</h3>
                     <div class="ticket-types">
                         ${ticketsHtml || '<div class="ticket-type"><div class="ticket-info"><h4>Bilet Bulunamadı</h4><p>Bu etkinlik için henüz bilet tanımlanmamış.</p></div></div>'}
                     </div>
                 </div>
                
                <div class="details-section">
                     <h3><i class="fas fa-share-alt"></i> İletişim ve Sosyal Medya</h3>
                     <div class="detail-grid">
                         <div class="detail-item">
                             <label>E-posta:</label>
                             <span>${event.organizer_email || 'Belirtilmemiş'}</span>
                         </div>
                         <div class="detail-item">
                             <label>Telefon:</label>
                             <span>${event.organizer_phone || 'Belirtilmemiş'}</span>
                         </div>
                         <div class="detail-item">
                             <label>Website:</label>
                             <span>${event.organizer_website || 'Belirtilmemiş'}</span>
                         </div>
                         <div class="detail-item">
                             <label>Instagram:</label>
                             <span>${event.organizer_instagram ? '@' + event.organizer_instagram : 'Belirtilmemiş'}</span>
                         </div>
                     </div>
                 </div>
                
                <div class="details-section">
                     <h3><i class="fas fa-comments"></i> Yorumlar</h3>
                     <div class="comments-summary">
                         <div class="comment-stats">
                             <span class="rating">${event.avg_rating ? parseFloat(event.avg_rating).toFixed(1) + '/5' : 'Henüz değerlendirilmemiş'}</span>
                             <span class="comment-count">${event.comment_count || 0} yorum</span>
                         </div>
                         <div class="recent-comments">
                             ${commentsHtml}
                         </div>
                     </div>
                 </div>
                
                <div class="details-section">
                     <h3><i class="fas fa-images"></i> Etkinlik Görselleri</h3>
                     <div class="image-gallery">
                         ${imagesHtml}
                     </div>
                 </div>
             </div>
         `;
         })
         .catch(error => {
             console.error('Error loading event details:', error);
             document.getElementById('detailsContent').innerHTML = '<div class="error">Etkinlik detayları yüklenirken bir hata oluştu.</div>';
         });
}

// Participants modal
function showParticipants(eventId) {
    document.getElementById(`dropdown-${eventId}`).classList.remove('show');
    document.getElementById('participantsContent').innerHTML = '<div class="loading">Yükleniyor...</div>';
    showModal('participantsModal');
    
    // TODO: Load participants via AJAX
    setTimeout(() => {
        document.getElementById('participantsContent').innerHTML = `
            <div class="participants-list">
                <div class="participant-item">
                    <div class="participant-info">
                        <strong>Ahmet Yılmaz</strong>
                        <span>ahmet@example.com</span>
                    </div>
                    <div class="participant-details">
                        <span>2 Bilet</span>
                        <span>₺180</span>
                    </div>
                </div>
                <div class="participant-item">
                    <div class="participant-info">
                        <strong>Ayşe Demir</strong>
                        <span>ayse@example.com</span>
                    </div>
                    <div class="participant-details">
                        <span>1 Bilet</span>
                        <span>₺90</span>
                    </div>
                </div>
            </div>
        `;
    }, 1000);
}

// Comments modal
function showComments(eventId) {
    document.getElementById(`dropdown-${eventId}`).classList.remove('show');
    document.getElementById('commentsContent').innerHTML = '<div class="loading">Yükleniyor...</div>';
    showModal('commentsModal');
    
    // TODO: Load comments via AJAX
    setTimeout(() => {
        document.getElementById('commentsContent').innerHTML = `
            <div class="comments-list">
                <div class="comment-item">
                    <div class="comment-header">
                        <strong>Mehmet Kaya</strong>
                        <span>2 gün önce</span>
                    </div>
                    <p>Harika bir etkinlik olacak gibi görünüyor!</p>
                </div>
                <div class="comment-item">
                    <div class="comment-header">
                        <strong>Zeynep Öz</strong>
                        <span>1 hafta önce</span>
                    </div>
                    <p>Mekanı çok beğeniyorum, kesinlikle geleceğim.</p>
                </div>
            </div>
        `;
    }, 1000);
}

// Delete event
function deleteEvent(eventId) {
    document.getElementById(`dropdown-${eventId}`).classList.remove('show');
    
    if (confirm('Bu etkinliği silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.')) {
        // TODO: Delete event via AJAX
        alert('Etkinlik silme özelliği henüz aktif değil.');
    }
}

// Close modals with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal').forEach(modal => {
            modal.classList.remove('show');
        });
    }
});

// Close modals when clicking outside
document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.classList.remove('show');
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>