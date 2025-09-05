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
$pendingOrganizerCount = count($pendingOrganizers);

// Etkinlik istatistikleri
$eventsQuery = "SELECT COUNT(*) as total FROM events";
$eventsStmt = $pdo->prepare($eventsQuery);
$eventsStmt->execute();
$totalEvents = $eventsStmt->fetch(PDO::FETCH_ASSOC)['total'];

// Sipariş istatistikleri
$ordersQuery = "SELECT COUNT(*) as total FROM orders";
$ordersStmt = $pdo->prepare($ordersQuery);
$ordersStmt->execute();
$totalOrders = $ordersStmt->fetch(PDO::FETCH_ASSOC)['total'];

// Aylık sipariş geliri
$monthlyRevenueQuery = "SELECT COALESCE(SUM(total_amount), 0) as revenue FROM orders WHERE payment_status = 'paid' AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())";
$monthlyRevenueStmt = $pdo->prepare($monthlyRevenueQuery);
$monthlyRevenueStmt->execute();
$monthlyRevenue = $monthlyRevenueStmt->fetch(PDO::FETCH_ASSOC)['revenue'];

// Bekleyen siparişler
$pendingOrdersQuery = "SELECT COUNT(*) as total FROM orders WHERE payment_status = 'pending'";
$pendingOrdersStmt = $pdo->prepare($pendingOrdersQuery);
$pendingOrdersStmt->execute();
$pendingOrders = $pendingOrdersStmt->fetch(PDO::FETCH_ASSOC)['total'];

// Son aktiviteler
$activityQuery = "SELECT al.*, u.first_name, u.last_name, u.email 
                  FROM activity_logs al 
                  LEFT JOIN users u ON al.user_id = u.id 
                  ORDER BY al.created_at DESC 
                  LIMIT 10";
$activityStmt = $pdo->prepare($activityQuery);
$activityStmt->execute();
$recentActivities = $activityStmt->fetchAll(PDO::FETCH_ASSOC);

// Kullanıcı analitikleri için veriler
// Son 30 günlük kullanıcı kayıt verileri
$userAnalyticsQuery = "SELECT 
    DATE(created_at) as date,
    COUNT(*) as user_count
    FROM users 
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC";
$userAnalyticsStmt = $pdo->prepare($userAnalyticsQuery);
$userAnalyticsStmt->execute();
$userAnalyticsData = $userAnalyticsStmt->fetchAll(PDO::FETCH_ASSOC);

// Son 7 günlük sipariş verileri
$orderAnalyticsQuery = "SELECT 
    DATE(created_at) as date,
    COUNT(*) as order_count,
    SUM(total_amount) as daily_revenue
    FROM orders 
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC";
$orderAnalyticsStmt = $pdo->prepare($orderAnalyticsQuery);
$orderAnalyticsStmt->execute();
$orderAnalyticsData = $orderAnalyticsStmt->fetchAll(PDO::FETCH_ASSOC);

// Kullanıcı türü dağılımı
$userTypeQuery = "SELECT 
    user_type,
    COUNT(*) as count
    FROM users 
    GROUP BY user_type";
$userTypeStmt = $pdo->prepare($userTypeQuery);
$userTypeStmt->execute();
$userTypeData = $userTypeStmt->fetchAll(PDO::FETCH_ASSOC);

// Aylık etkinlik sayıları
$monthlyEventsQuery = "SELECT 
    MONTH(created_at) as month,
    YEAR(created_at) as year,
    COUNT(*) as event_count
    FROM events 
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY YEAR(created_at), MONTH(created_at)
    ORDER BY year ASC, month ASC";
$monthlyEventsStmt = $pdo->prepare($monthlyEventsQuery);
$monthlyEventsStmt->execute();
$monthlyEventsData = $monthlyEventsStmt->fetchAll(PDO::FETCH_ASSOC);

// JavaScript için veri hazırlama
$userAnalyticsLabels = [];
$userAnalyticsValues = [];
foreach ($userAnalyticsData as $data) {
    $userAnalyticsLabels[] = date('d M', strtotime($data['date']));
    $userAnalyticsValues[] = (int)$data['user_count'];
}

$orderAnalyticsLabels = [];
$orderAnalyticsValues = [];
$revenueAnalyticsValues = [];
foreach ($orderAnalyticsData as $data) {
    $orderAnalyticsLabels[] = date('d M', strtotime($data['date']));
    $orderAnalyticsValues[] = (int)$data['order_count'];
    $revenueAnalyticsValues[] = (float)$data['daily_revenue'];
}

$userTypeLabels = [];
$userTypeValues = [];
foreach ($userTypeData as $data) {
    $userTypeLabels[] = ucfirst($data['user_type']);
    $userTypeValues[] = (int)$data['count'];
}

// Aktivite türlerine göre ikon ve renk belirleme fonksiyonu
function getActivityIcon($action) {
    $action = strtolower($action);
    if (strpos($action, 'kullanıcı') !== false || strpos($action, 'user') !== false || strpos($action, 'kayıt') !== false) {
        return ['icon' => 'fas fa-user-plus', 'class' => 'user'];
    } elseif (strpos($action, 'organizatör') !== false || strpos($action, 'organizer') !== false) {
        return ['icon' => 'fas fa-building', 'class' => 'organizer'];
    } elseif (strpos($action, 'etkinlik') !== false || strpos($action, 'event') !== false) {
        return ['icon' => 'fas fa-calendar-alt', 'class' => 'event'];
    } elseif (strpos($action, 'sipariş') !== false || strpos($action, 'order') !== false || strpos($action, 'bilet') !== false) {
        return ['icon' => 'fas fa-shopping-cart', 'class' => 'order'];
    } elseif (strpos($action, 'aktarıldı') !== false || strpos($action, 'transfer') !== false) {
        return ['icon' => 'fas fa-exchange-alt', 'class' => 'transfer'];
    } elseif (strpos($action, 'ödeme') !== false || strpos($action, 'payment') !== false) {
        return ['icon' => 'fas fa-credit-card', 'class' => 'payment'];
    } else {
        return ['icon' => 'fas fa-info-circle', 'class' => 'info'];
    }
}

// Aktivite action'larını Türkçe'ye çevirme fonksiyonu
function translateAction($action) {
    $translations = [
        'event_created' => 'Etkinlik Oluşturuldu',
        'event_updated' => 'Etkinlik Güncellendi',
        'event_deleted' => 'Etkinlik Silindi',
        'event_status_updated' => 'Etkinlik Durumu Güncellendi',
        'user_registered' => 'Yeni Kullanıcı Kaydı',
        'user_login' => 'Kullanıcı Girişi',
        'organizer_applied' => 'Organizatör Başvurusu',
        'organizer_approved' => 'Organizatör Onaylandı',
        'organizer_rejected' => 'Organizatör Reddedildi',
        'order_created' => 'Yeni Sipariş',
        'order_paid' => 'Sipariş Ödendi',
        'order_cancelled' => 'Sipariş İptal Edildi',
        'ticket_purchased' => 'Bilet Satın Alındı',
        'ticket_used' => 'Bilet Kullanıldı',
        'ticket_transferred' => 'Bilet Aktarıldı',
        'payment_completed' => 'Ödeme Tamamlandı',
        'payment_failed' => 'Ödeme Başarısız'
    ];
    
    return isset($translations[$action]) ? $translations[$action] : ucfirst(str_replace('_', ' ', $action));
}

// Zaman farkını hesaplama fonksiyonu
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) {
        return 'Az önce';
    } elseif ($time < 3600) {
        $minutes = floor($time / 60);
        return $minutes . ' dakika önce';
    } elseif ($time < 86400) {
        $hours = floor($time / 3600);
        return $hours . ' saat önce';
    } elseif ($time < 2592000) {
        $days = floor($time / 86400);
        return $days . ' gün önce';
    } else {
        $months = floor($time / 2592000);
        return $months . ' ay önce';
    }
}

include 'includes/header.php';
?>

<div class="admin-container">
    <!-- Ultra Modern Sidebar -->
    <div class="admin-sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <img src="../uploads/logo.png" alt="BiletJack Logo" style="width: 120px; height: 120px; object-fit: contain;">
            </div>
            <h2 class="sidebar-title">Yetkili Paneli</h2>
            <p class="sidebar-subtitle">Admin Dashboard</p>
        </div>
        
        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">Ana Menü</div>
                <a href="index.php" class="nav-item active">
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
                <a href="../index.php" class="nav-item">
                    <i class="fas fa-home"></i>
                    Ana Sayfa
                </a>
            </div>

            <!-- Hesap bölümü: Bildirim ve Çıkış -->
            <div class="nav-section">
                <div class="nav-section-title">Hesap</div>
                <a href="#" class="nav-item">
                    <i class="fas fa-bell"></i>
                    Bildirimler
                    <span class="nav-badge"></span>
                </a>
                <a href="../auth/logout.php" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i>
                    Çıkış
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
                    <h1 class="page-title">Gösterge Paneli</h1>
                    <p class="page-subtitle">Hoş geldiniz! İşte BiletJack genel durumu</p>
                </div>
            </div>
            
            <div class="header-right">
                <!-- Bildirim ve Çıkış butonları sidebar'a taşındı -->
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
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i>
                    +15%
                    <span class="stat-period">Bu hafta</span>
                </div>
            </div>
            
            <div class="stat-card revenue">
                <div class="stat-header">
                    <span class="stat-title">Aylık Gelir</span>
                    <div class="stat-icon revenue">
                        <i class="fas fa-lira-sign"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo number_format($monthlyRevenue, 2); ?> ₺</div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i>
                    +22%
                    <span class="stat-period">Bu ay</span>
                </div>
            </div>
            
            <div class="stat-card pending">
                <div class="stat-header">
                    <span class="stat-title">Bekleyen Sipariş</span>
                    <div class="stat-icon pending">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo number_format($pendingOrders); ?></div>
                <div class="stat-change <?php echo $pendingOrders > 0 ? 'warning' : 'positive'; ?>">
                    <i class="fas fa-<?php echo $pendingOrders > 0 ? 'exclamation-triangle' : 'check'; ?>"></i>
                    <?php echo $pendingOrders > 0 ? 'Dikkat' : 'Temiz'; ?>
                    <span class="stat-period">Şu anda</span>
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
                            <div class="chart-tabs">
                                <button class="chart-tab active" onclick="showChart('users')">Kullanıcılar</button>
                                <button class="chart-tab" onclick="showChart('orders')">Siparişler</button>
                                <button class="chart-tab" onclick="showChart('userTypes')">Kullanıcı Türleri</button>
                            </div>
                            <div class="chart-wrapper">
                                <canvas id="analyticsChart" width="400" height="200"></canvas>
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
                            <?php if (!empty($recentActivities)): ?>
                                <?php foreach ($recentActivities as $activity): ?>
                                    <?php 
                                        $activityInfo = getActivityIcon($activity['action']);
                                        $userName = '';
                                        if ($activity['first_name'] && $activity['last_name']) {
                                            $userName = $activity['first_name'] . ' ' . $activity['last_name'];
                                        } elseif ($activity['email']) {
                                            $userName = $activity['email'];
                                        } else {
                                            $userName = 'Sistem';
                                        }
                                    ?>
                                    <div class="activity-item">
                                        <div class="activity-avatar <?php echo $activityInfo['class']; ?>">
                                            <i class="<?php echo $activityInfo['icon']; ?>"></i>
                                        </div>
                                        <div class="activity-content">
                                             <h4 class="activity-title"><?php echo htmlspecialchars(translateAction($activity['action'])); ?></h4>
                                            <p class="activity-description">
                                                <?php if ($activity['description']): ?>
                                                    <?php echo htmlspecialchars($activity['description']); ?>
                                                <?php else: ?>
                                                    <?php echo htmlspecialchars($userName); ?> tarafından gerçekleştirildi
                                                <?php endif; ?>
                                            </p>
                                            <div class="activity-time">
                                                <i class="fas fa-clock"></i>
                                                <?php echo timeAgo($activity['created_at']); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="no-data">
                                    <i class="fas fa-info-circle"></i>
                                    <h3>Henüz aktivite yok</h3>
                                    <p>Sistemde henüz kayıtlı aktivite bulunmuyor.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>

<!-- sayfa sonları -->
<script>
// Duplikasyon önlendi: Mobil menü toggle ve dışarı tıklandığında kapatma işlemleri
// admin genel dosyası js/admin.js içinde global olarak tanımlı.
// Bu sayfadaki tekrar eden iki blok kaldırıldı.

// KALDIRILDI:
// document.querySelector('.mobile-menu-toggle')?.addEventListener('click', function() {
//     document.querySelector('.admin-sidebar').classList.toggle('mobile-open');
// });
// document.addEventListener('click', function(e) {
//     const sidebar = document.querySelector('.admin-sidebar');
//     const toggle = document.querySelector('.mobile-menu-toggle');
//     if (sidebar && toggle && !sidebar.contains(e.target) && !toggle.contains(e.target)) {
//         sidebar.classList.remove('mobile-open');
//     }
// });

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
    console.log('Searching for:', query);
});

// Chart.js Analytics (initChart, showChart, vb.)
let analyticsChart;
const chartData = {
    users: {
        labels: <?php echo json_encode($userAnalyticsLabels); ?>,
        data: <?php echo json_encode($userAnalyticsValues); ?>,
        label: 'Yeni Kullanıcılar',
        backgroundColor: 'rgba(102, 126, 234, 0.1)',
        borderColor: 'rgba(102, 126, 234, 1)',
        type: 'line'
    },
    orders: {
        labels: <?php echo json_encode($orderAnalyticsLabels); ?>,
        data: <?php echo json_encode($orderAnalyticsValues); ?>,
        label: 'Günlük Siparişler',
        backgroundColor: 'rgba(34, 197, 94, 0.1)',
        borderColor: 'rgba(34, 197, 94, 1)',
        type: 'line'
    },
    userTypes: {
        labels: <?php echo json_encode($userTypeLabels); ?>,
        data: <?php echo json_encode($userTypeValues); ?>,
        label: 'Kullanıcı Türü Dağılımı',
        backgroundColor: [
            'rgba(102, 126, 234, 0.8)',
            'rgba(34, 197, 94, 0.8)',
            'rgba(251, 191, 36, 0.8)',
            'rgba(239, 68, 68, 0.8)'
        ],
        borderColor: [
            'rgba(102, 126, 234, 1)',
            'rgba(34, 197, 94, 1)',
            'rgba(251, 191, 36, 1)',
            'rgba(239, 68, 68, 1)'
        ],
        type: 'doughnut'
    }
};

function initChart() {
    const ctx = document.getElementById('analyticsChart').getContext('2d');
    
    analyticsChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.users.labels,
            datasets: [{
                label: chartData.users.label,
                data: chartData.users.data,
                backgroundColor: chartData.users.backgroundColor,
                borderColor: chartData.users.borderColor,
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    }
                }
            }
        }
    });
}

function showChart(type) {
    // Tab aktiflik durumunu güncelle
    document.querySelectorAll('.chart-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    event.target.classList.add('active');
    
    // Grafik verilerini güncelle
    const data = chartData[type];
    
    if (analyticsChart) {
        analyticsChart.destroy();
    }
    
    const ctx = document.getElementById('analyticsChart').getContext('2d');
    
    if (data.type === 'doughnut') {
        analyticsChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.labels,
                datasets: [{
                    label: data.label,
                    data: data.data,
                    backgroundColor: data.backgroundColor,
                    borderColor: data.borderColor,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'right'
                    }
                }
            }
        });
    } else {
        analyticsChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    label: data.label,
                    data: data.data,
                    backgroundColor: data.backgroundColor,
                    borderColor: data.borderColor,
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        }
                    }
                }
            }
        });
    }
}

// Sayfa yüklendiğinde grafiği başlat
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(initChart, 500);
});
</script>

<?php include 'includes/footer.php'; ?>