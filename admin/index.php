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

// Class'ları database bağlantısı ile başlat
$user = new User($pdo);
$organizer = new Organizer($pdo);

// İstatistikler
$totalUsers = $user->getTotalUsers();
$pendingOrganizers = $organizer->getPendingOrganizers();
$pendingOrganizerCount = count($pendingOrganizers); // Bu satırı ekle
$totalEvents = 0; // Etkinlik sistemi eklendiğinde güncellenecek
$totalOrders = 0; // Sipariş sistemi eklendiğinde güncellenecek

include 'includes/header.php';
?>

<div class="admin-container">
    <!-- Ultra Modern Sidebar -->
    <div class="admin-sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <i class="fas fa-ticket-alt"></i>
            </div>
            <h2 class="sidebar-title">BiletJack</h2>
            <p class="sidebar-subtitle">Admin Dashboard</p>
        </div>
        
        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">Ana Menü</div>
                <a href="index.php" class="nav-item active">
                    <i class="fas fa-chart-pie"></i>
                    Dashboard
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
                    <?php if (count($pendingOrganizers) > 0): ?>
                        <span class="nav-badge"><?php echo count($pendingOrganizers); ?></span>
                    <?php endif; ?>
                </a>
                <a href="events.php" class="nav-item">
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
            </div>
        </nav>
    </div>
    
    <!-- Main Content -->
    <div class="admin-content">
        <!-- Modern Header -->
        <div class="content-header">
            <div class="header-left">
                <button class="mobile-menu-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div>
                    <h1 class="page-title">Dashboard</h1>
                    <p class="page-subtitle">Hoş geldiniz! İşte sistemin genel durumu</p>
                </div>
            </div>
            
            <div class="header-right">
                <div class="header-search">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="search-input" placeholder="Ara...">
                </div>
                
                <button class="header-notifications">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge"></span>
                </button>
                
                <div class="user-menu">
                    <?php $currentUser = getCurrentUser(); ?>
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($currentUser['first_name'], 0, 1)); ?>
                    </div>
                    <div class="user-info">
                        <h4><?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?></h4>
                        <p>Admin</p>
                    </div>
                    <i class="fas fa-chevron-down"></i>
                </div>
                
                <a href="../auth/logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    Çıkış
                </a>
            </div>
        </div>
        
        <!-- Dashboard Stats -->
        <div class="dashboard-stats fade-in">
            <div class="stat-card users">
                <div class="stat-header">
                    <span class="stat-title">Toplam Kullanıcı</span>
                    <div class="stat-icon users">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo number_format($totalUsers); ?></div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i>
                    +12%
                    <span class="stat-period">Bu ay</span>
                </div>
            </div>
            
            <div class="stat-card organizers">
                <div class="stat-header">
                    <span class="stat-title">Bekleyen Organizatör</span>
                    <div class="stat-icon organizers">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo number_format(count($pendingOrganizers)); ?></div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i>
                    +5%
                    <span class="stat-period">Bu hafta</span>
                </div>
            </div>
            
            <div class="stat-card events">
                <div class="stat-header">
                    <span class="stat-title">Toplam Etkinlik</span>
                    <div class="stat-icon events">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo number_format($totalEvents); ?></div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i>
                    +8%
                    <span class="stat-period">Bu ay</span>
                </div>
            </div>
            
            <div class="stat-card orders">
                <div class="stat-header">
                    <span class="stat-title">Toplam Sipariş</span>
                    <div class="stat-icon orders">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo number_format($totalOrders); ?></div>
                <div class="stat-change negative">
                    <i class="fas fa-arrow-down"></i>
                    -3%
                    <span class="stat-period">Bu hafta</span>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="dashboard-content">
            <div class="quick-actions fade-in">
                <a href="users.php?action=add" class="quick-action">
                    <div class="quick-action-icon add-user">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h3 class="quick-action-title">Kullanıcı Ekle</h3>
                </a>
                
                <a href="events.php?action=add" class="quick-action">
                    <div class="quick-action-icon add-event">
                        <i class="fas fa-plus"></i>
                    </div>
                    <h3 class="quick-action-title">Etkinlik Ekle</h3>
                </a>
                
                <a href="reports.php" class="quick-action">
                    <div class="quick-action-icon reports">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h3 class="quick-action-title">Raporlar</h3>
                </a>
                
                <a href="settings.php" class="quick-action">
                    <div class="quick-action-icon settings">
                        <i class="fas fa-cog"></i>
                    </div>
                    <h3 class="quick-action-title">Ayarlar</h3>
                </a>
            </div>
            
            <!-- Content Grid -->
            <div class="content-grid fade-in">
                <!-- Analytics Chart -->
                <div class="modern-card">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">Kullanıcı Analitikleri</h3>
                            <p class="card-subtitle">Son 30 günlük kullanıcı aktivitesi</p>
                        </div>
                        <div class="card-actions">
                            <button class="card-action">
                                <i class="fas fa-download"></i>
                            </button>
                            <button class="card-action">
                                <i class="fas fa-ellipsis-h"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <div class="chart-placeholder">
                                <i class="fas fa-chart-line"></i>
                                <h4>Grafik Alanı</h4>
                                <p>Chart.js veya benzeri bir kütüphane ile grafik eklenecek</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Pending Approvals -->
                <div class="modern-card">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">Bekleyen Onaylar</h3>
                            <p class="card-subtitle">İncelenmesi gereken başvurular</p>
                        </div>
                        <div class="card-actions">
                            <button class="card-action">
                                <i class="fas fa-refresh"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (count($pendingOrganizers) > 0): ?>
                            <div class="pending-list">
                                <?php foreach ($pendingOrganizers as $org): ?>
                                    <div class="pending-item">
                                        <div class="pending-info">
                                            <div class="pending-avatar">
                                                <?php echo strtoupper(substr($org['company_name'], 0, 1)); ?>
                                            </div>
                                            <div class="pending-details">
                                                <h4><?php echo htmlspecialchars($org['company_name']); ?></h4>
                                                <p><?php echo htmlspecialchars($org['first_name'] . ' ' . $org['last_name']); ?></p>
                                            </div>
                                        </div>
                                        <div class="pending-actions">
                                            <a href="organizers.php?action=approve&id=<?php echo $org['user_id']; ?>" class="btn btn-sm btn-success">
                                                <i class="fas fa-check"></i>
                                                Onayla
                                            </a>
                                            <a href="organizers.php?action=reject&id=<?php echo $org['user_id']; ?>" class="btn btn-sm btn-danger">
                                                <i class="fas fa-times"></i>
                                                Reddet
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-check-circle"></i>
                                <h3>Tüm başvurular incelendi</h3>
                                <p>Şu anda bekleyen organizatör başvurusu bulunmuyor.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="content-grid-full fade-in">
                <div class="modern-card">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">Son Aktiviteler</h3>
                            <p class="card-subtitle">Sistemdeki son hareketler</p>
                        </div>
                        <div class="card-actions">
                            <button class="card-action">
                                <i class="fas fa-filter"></i>
                            </button>
                            <button class="card-action">
                                <i class="fas fa-ellipsis-h"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="activity-feed">
                            <div class="activity-item">
                                <div class="activity-avatar user">
                                    <i class="fas fa-user-plus"></i>
                                </div>
                                <div class="activity-content">
                                    <h4 class="activity-title">Yeni kullanıcı kaydı</h4>
                                    <p class="activity-description">John Doe sisteme kayıt oldu ve hesabını doğruladı</p>
                                    <div class="activity-time">
                                        <i class="fas fa-clock"></i>
                                        2 saat önce
                                    </div>
                                </div>
                            </div>
                            
                            <div class="activity-item">
                                <div class="activity-avatar organizer">
                                    <i class="fas fa-building"></i>
                                </div>
                                <div class="activity-content">
                                    <h4 class="activity-title">Organizatör başvurusu</h4>
                                    <p class="activity-description">ABC Events organizatör olmak için başvuru yaptı</p>
                                    <div class="activity-time">
                                        <i class="fas fa-clock"></i>
                                        5 saat önce
                                    </div>
                                </div>
                            </div>
                            
                            <div class="activity-item">
                                <div class="activity-avatar event">
                                    <i class="fas fa-ticket-alt"></i>
                                </div>
                                <div class="activity-content">
                                    <h4 class="activity-title">Yeni etkinlik oluşturuldu</h4>
                                    <p class="activity-description">\"Konser 2024\" etkinliği sisteme eklendi</p>
                                    <div class="activity-time">
                                        <i class="fas fa-clock"></i>
                                        1 gün önce
                                    </div>
                                </div>
                            </div>
                            
                            <div class="activity-item">
                                <div class="activity-avatar user">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <div class="activity-content">
                                    <h4 class="activity-title">Yeni sipariş</h4>
                                    <p class="activity-description">Jane Smith 3 adet bilet satın aldı</p>
                                    <div class="activity-time">
                                        <i class="fas fa-clock"></i>
                                        2 gün önce
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modern-card">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">Sistem Durumu</h3>
                            <p class="card-subtitle">Sunucu ve sistem metrikleri</p>
                        </div>
                        <div class="card-actions">
                            <button class="card-action">
                                <i class="fas fa-sync"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <div class="chart-placeholder">
                                <i class="fas fa-server"></i>
                                <h4>Sistem Metrikleri</h4>
                                <p>CPU, RAM ve disk kullanımı grafikleri</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Mobile menu toggle
document.querySelector('.mobile-menu-toggle')?.addEventListener('click', function() {
    document.querySelector('.admin-sidebar').classList.toggle('mobile-open');
});

// Close mobile menu when clicking outside
document.addEventListener('click', function(e) {
    const sidebar = document.querySelector('.admin-sidebar');
    const toggle = document.querySelector('.mobile-menu-toggle');
    
    if (sidebar && toggle && !sidebar.contains(e.target) && !toggle.contains(e.target)) {
        sidebar.classList.remove('mobile-open');
    }
});

// Add fade-in animation to elements
document.addEventListener('DOMContentLoaded', function() {
    const elements = document.querySelectorAll('.fade-in');
    elements.forEach((el, index) => {
        setTimeout(() => {
            el.style.opacity = '1';
            el.style.transform = 'translateY(0)';
        }, index * 100);
    });
});

// Search functionality
document.querySelector('.search-input')?.addEventListener('input', function(e) {
    const query = e.target.value.toLowerCase();
    // Arama fonksiyonalitesi buraya eklenecek
    console.log('Searching for:', query);
});
</script>

<?php include 'includes/footer.php'; ?>