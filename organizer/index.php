<?php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../classes/User.php';

// Organizatör kontrolü
requireOrganizer();

// Organizatör onay kontrolü - session'ı da güncelle
if (!isOrganizerApproved()) {
    // Session durumunu da kontrol et ve güncelle
    $database = new Database();
    $pdo = $database->getConnection();
    
    $query = "SELECT u.status, od.approval_status FROM users u 
              LEFT JOIN organizer_details od ON u.id = od.user_id 
              WHERE u.id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && $result['approval_status'] === 'approved' && $result['status'] === 'approved') {
        // Session'ı güncelle
        $_SESSION['user_status'] = 'approved';
    } else {
        header('Location: pending.php');
        exit();
    }
}

$currentUser = getCurrentUser();

// İstatistikler (şimdilik sabit değerler, etkinlik sistemi eklendiğinde güncellenecek)
$totalRevenue = 13564;
$totalOrders = 2597;
$totalVisits = 1378;
$engagedUsers = 456;

include 'includes/header.php';
?>

<!-- Mobile Menu Toggle -->


<!-- Mobile Overlay -->
<div class="mobile-overlay" id="mobileOverlay"></div>

<!-- Sol Sidebar -->
<div class="modern-sidebar" id="sidebar">
    <div class="sidebar-logo">
        <i class="fas fa-ticket-alt"></i>
    </div>
    
    <div class="sidebar-nav">
        <div class="nav-icon active" title="Ana Sayfa" onclick="window.location.href='./index.php'" style="cursor: pointer;">
            <i class="fas fa-home"></i>
        </div>
        <div class="nav-icon" title="Etkinlikler">
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
            <input type="text" class="search-input" placeholder="Ara...">
        </div>
        
        <div class="notification-icon">
            <i class="fas fa-bell"></i>
        </div>
    </div>
    
    <!-- Dashboard İçeriği -->
    <div class="dashboard-content">
        <!-- Üst İstatistik Kartları -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Gelir</span>
                    <div class="stat-icon revenue">
                        <i class="fas fa-lira-sign"></i>
                    </div>
                </div>
                <div class="stat-value">₺<?php echo number_format($totalRevenue); ?></div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i>
                    +%12 bu ay
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Siparişler</span>
                    <div class="stat-icon orders">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo number_format($totalOrders); ?></div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i>
                    +%8 bu ay
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Ziyaretler</span>
                    <div class="stat-icon visits">
                        <i class="fas fa-eye"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo number_format($totalVisits); ?></div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i>
                    +%15 bu ay
                </div>
            </div>
        </div>
        
        <!-- Ana Grid -->
        <div class="main-grid">
            <!-- Analytics Overview -->
            <div class="analytics-card">
                <div class="card-header">
                    <h3 class="card-title">Analitik Genel Bakış</h3>
                    <span class="date-range">1 Ocak - 25 Mart</span>
                </div>
                
                <div class="chart-container">
                    <div class="chart-placeholder">
                        <i class="fas fa-chart-line" style="font-size: 48px; color: #e2e8f0; margin-bottom: 16px;"></i>
                        <p>Grafik verisi yükleniyor...</p>
                        <small>Etkinlik sistemi aktif olduğunda gerçek veriler gösterilecek</small>
                    </div>
                </div>
            </div>
            
            <!-- Sağ Sidebar -->
            <div class="right-sidebar">
                <!-- Engaged Users -->
                <div class="engaged-users-card">
                    <div class="engaged-title">Aktif kullanıcılar</div>
                    <div class="engaged-subtitle">Bugün</div>
                    
                    <div class="circle-chart">
                        <div class="circle-value"><?php echo $engagedUsers; ?></div>
                        <div class="circle-label">kullanıcı</div>
                    </div>
                    
                    <div class="engagement-stats">
                        <div class="engagement-item">
                            <div class="engagement-label">
                                <div class="engagement-dot blue"></div>
                                Etkinlik
                            </div>
                            <div class="engagement-value">%68</div>
                        </div>
                        <div class="engagement-item">
                            <div class="engagement-label">
                                <div class="engagement-dot purple"></div>
                                Satış
                            </div>
                            <div class="engagement-value">%22</div>
                        </div>
                        <div class="engagement-item">
                            <div class="engagement-label">
                                <div class="engagement-dot gray"></div>
                                Diğer
                            </div>
                            <div class="engagement-value">%11</div>
                        </div>
                    </div>
                </div>
                
                <!-- Top Buyers -->
                <div class="top-buyers-card">
                    <div class="card-header">
                        <h3 class="card-title">En Çok Satın Alanlar</h3>
                    </div>
                    
                    <div class="buyers-list">
                        <div class="buyer-item">
                            <div class="buyer-avatar">AK</div>
                            <div class="buyer-info">
                                <div class="buyer-name">Ahmet Kaya</div>
                                <div class="buyer-amount">₺2,450</div>
                            </div>
                            <div class="buyer-count">+12</div>
                        </div>
                        
                        <div class="buyer-item">
                            <div class="buyer-avatar">MÖ</div>
                            <div class="buyer-info">
                                <div class="buyer-name">Merve Özkan</div>
                                <div class="buyer-amount">₺1,890</div>
                            </div>
                            <div class="buyer-count">+8</div>
                        </div>
                        
                        <div class="buyer-item">
                            <div class="buyer-avatar">EY</div>
                            <div class="buyer-info">
                                <div class="buyer-name">Emre Yılmaz</div>
                                <div class="buyer-amount">₺1,650</div>
                            </div>
                            <div class="buyer-count">+5</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Mobile Menu Toggle
const mobileMenuToggle = document.getElementById('mobileMenuToggle');
const sidebar = document.getElementById('sidebar');
const mobileOverlay = document.getElementById('mobileOverlay');

function toggleMobileMenu() {
    sidebar.classList.toggle('mobile-open');
    mobileOverlay.classList.toggle('active');
    
    // Icon değiştir
    const icon = mobileMenuToggle.querySelector('i');
    if (sidebar.classList.contains('mobile-open')) {
        icon.className = 'fas fa-times';
    } else {
        icon.className = 'fas fa-bars';
    }
}

mobileMenuToggle.addEventListener('click', toggleMobileMenu);
mobileOverlay.addEventListener('click', toggleMobileMenu);

// Sidebar navigation
document.querySelectorAll('.nav-icon').forEach(icon => {
    icon.addEventListener('click', function() {
        document.querySelectorAll('.nav-icon').forEach(i => i.classList.remove('active'));
        this.classList.add('active');
        
        // Mobile'da menüyü kapat
        if (window.innerWidth <= 768) {
            toggleMobileMenu();
        }
    });
});

// Logout functionality
document.querySelector('.sidebar-logout').addEventListener('click', function() {
    if (confirm('Çıkış yapmak istediğinizden emin misiniz?')) {
        window.location.href = '../auth/logout.php';
    }
});

// Search functionality
document.querySelector('.search-input').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        // Arama fonksiyonalitesi buraya eklenecek
        console.log('Arama:', this.value);
    }
});

// Window resize handler
window.addEventListener('resize', function() {
    if (window.innerWidth > 768) {
        sidebar.classList.remove('mobile-open');
        mobileOverlay.classList.remove('active');
        mobileMenuToggle.querySelector('i').className = 'fas fa-bars';
    }
});

// Touch swipe to close menu
let startX = 0;
let currentX = 0;
let isDragging = false;

sidebar.addEventListener('touchstart', function(e) {
    startX = e.touches[0].clientX;
    isDragging = true;
});

sidebar.addEventListener('touchmove', function(e) {
    if (!isDragging) return;
    currentX = e.touches[0].clientX;
    const diffX = startX - currentX;
    
    if (diffX > 50) {
        toggleMobileMenu();
        isDragging = false;
    }
});

sidebar.addEventListener('touchend', function() {
    isDragging = false;
});
</script>

</body>
</html>