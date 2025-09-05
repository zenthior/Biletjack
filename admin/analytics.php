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

// Tarih aralığı belirleme
$timeRange = isset($_GET['range']) ? $_GET['range'] : '7d';
$endDate = date('Y-m-d');

switch($timeRange) {
    case '7d':
        $startDate = date('Y-m-d', strtotime('-7 days'));
        break;
    case '30d':
        $startDate = date('Y-m-d', strtotime('-30 days'));
        break;
    case '90d':
        $startDate = date('Y-m-d', strtotime('-90 days'));
        break;
    case '1y':
        $startDate = date('Y-m-d', strtotime('-1 year'));
        break;
    default:
        $startDate = date('Y-m-d', strtotime('-7 days'));
}

// Analytics verileri
$totalUsers = $user->getTotalUsers();
$pendingOrganizers = $organizer->getPendingOrganizers();
$approvedOrganizers = $organizer->getApprovedOrganizers();
$rejectedOrganizers = $organizer->getRejectedOrganizers();

// Gerçek aylık performans verileri
$monthlyStatsQuery = "SELECT 
    DATE_FORMAT(created_at, '%Y-%m') as month,
    COUNT(*) as user_count
    FROM users 
    WHERE created_at BETWEEN ? AND ?
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month";
$monthlyStatsStmt = $pdo->prepare($monthlyStatsQuery);
$monthlyStatsStmt->execute([$startDate, $endDate]);
$monthlyUserStats = $monthlyStatsStmt->fetchAll(PDO::FETCH_ASSOC);

// Etkinlik sayıları
$monthlyEventsQuery = "SELECT 
    DATE_FORMAT(created_at, '%Y-%m') as month,
    COUNT(*) as event_count
    FROM events 
    WHERE created_at BETWEEN ? AND ?
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month";
$monthlyEventsStmt = $pdo->prepare($monthlyEventsQuery);
$monthlyEventsStmt->execute([$startDate, $endDate]);
$monthlyEventStats = $monthlyEventsStmt->fetchAll(PDO::FETCH_ASSOC);

// Gelir verileri
$monthlyRevenueQuery = "SELECT 
    DATE_FORMAT(o.created_at, '%Y-%m') as month,
    COALESCE(SUM(t.price), 0) as revenue
    FROM orders o
    LEFT JOIN tickets t ON t.order_id = o.id
    WHERE o.payment_status = 'paid'
    AND o.created_at BETWEEN ? AND ?
    GROUP BY DATE_FORMAT(o.created_at, '%Y-%m')
    ORDER BY month";
$monthlyRevenueStmt = $pdo->prepare($monthlyRevenueQuery);
$monthlyRevenueStmt->execute([$startDate, $endDate]);
$monthlyRevenueStats = $monthlyRevenueStmt->fetchAll(PDO::FETCH_ASSOC);

// Kategori dağılımı
$categoryQuery = "SELECT 
    e.category,
    COUNT(*) as count,
    ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM events WHERE created_at BETWEEN ? AND ?)), 1) as percentage
    FROM events e
    WHERE e.created_at BETWEEN ? AND ?
    GROUP BY e.category
    ORDER BY count DESC
    LIMIT 10";
$categoryStmt = $pdo->prepare($categoryQuery);
$categoryStmt->execute([$startDate, $endDate, $startDate, $endDate]);
$topCategories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

// En başarılı organizatörler
$topOrganizersQuery = "SELECT 
    od.company_name,
    CONCAT(u.first_name, ' ', u.last_name) as contact_person,
    u.email,
    COUNT(DISTINCT e.id) as event_count,
    COUNT(DISTINCT t.id) as total_sales,
    COALESCE(SUM(t.price), 0) as revenue
    FROM organizer_details od
    LEFT JOIN users u ON od.user_id = u.id
    LEFT JOIN events e ON od.user_id = e.organizer_id
    LEFT JOIN tickets t ON e.id = t.event_id
    LEFT JOIN orders o ON t.order_id = o.id AND o.payment_status = 'paid'
    WHERE od.approval_status = 'approved'
    AND e.created_at BETWEEN ? AND ?
    GROUP BY od.user_id
    HAVING event_count > 0
    ORDER BY revenue DESC
    LIMIT 10";
$topOrganizersStmt = $pdo->prepare($topOrganizersQuery);
$topOrganizersStmt->execute([$startDate, $endDate]);
$topOrganizers = $topOrganizersStmt->fetchAll(PDO::FETCH_ASSOC);

// Toplam etkinlik sayısı
$totalEventsQuery = "SELECT COUNT(*) as total FROM events WHERE created_at BETWEEN ? AND ?";
$totalEventsStmt = $pdo->prepare($totalEventsQuery);
$totalEventsStmt->execute([$startDate, $endDate]);
$totalEvents = $totalEventsStmt->fetch(PDO::FETCH_ASSOC)['total'];

// Toplam gelir
$totalRevenueQuery = "SELECT COALESCE(SUM(t.price), 0) as total 
    FROM orders o
    LEFT JOIN tickets t ON t.order_id = o.id
    WHERE o.payment_status = 'paid'
    AND o.created_at BETWEEN ? AND ?";
$totalRevenueStmt = $pdo->prepare($totalRevenueQuery);
$totalRevenueStmt->execute([$startDate, $endDate]);
$totalRevenue = $totalRevenueStmt->fetch(PDO::FETCH_ASSOC)['total'];

$recentActivity = [
    ['action' => 'Yeni kullanıcı kaydı', 'user' => 'Ahmet Yılmaz', 'time' => '2 dakika önce'],
    ['action' => 'Etkinlik onaylandı', 'user' => 'Mehmet Kaya', 'time' => '15 dakika önce'],
    ['action' => 'Organizatör başvurusu', 'user' => 'Ayşe Demir', 'time' => '1 saat önce'],
    ['action' => 'Bilet satışı', 'user' => 'Fatma Şen', 'time' => '2 saat önce'],
    ['action' => 'Yeni etkinlik eklendi', 'user' => 'Ali Özkan', 'time' => '3 saat önce']
];

include 'includes/header.php';
?>

<div class="admin-container">
    <!-- Ultra Modern Sidebar -->
    <div class="admin-sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <img src="../uploads/logo.png" alt="BiletJack Logo" style="width: 120px; height: 120px; object-fit: contain;">
            </div>
            <h2 class="sidebar-title">Analiz</h2>
            <p class="sidebar-subtitle">Admin Dashboard</p>
        </div>
        
        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">Ana Menü</div>
                <a href="index.php" class="nav-item">
                    <i class="fas fa-chart-pie"></i>
                    Dashboard
                </a>
                <a href="analytics.php" class="nav-item active">
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
                    <h1 class="page-title">Analytics</h1>
                    <p class="page-subtitle">Detaylı sistem analitikleri ve raporlar</p>
                </div>
            </div>
            
            <div class="header-right">
                
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
        
        <!-- Analytics Content -->
        <div class="analytics-container">
            <!-- Time Range Selector -->
            <div class="analytics-controls fade-in">
                <div class="time-range-selector">
                    <button class="time-btn <?php echo $timeRange == '7d' ? 'active' : ''; ?>" data-range="7d">Son 7 Gün</button>
                    <button class="time-btn <?php echo $timeRange == '30d' ? 'active' : ''; ?>" data-range="30d">Son 30 Gün</button>
                    <button class="time-btn <?php echo $timeRange == '90d' ? 'active' : ''; ?>" data-range="90d">Son 3 Ay</button>
                    <button class="time-btn <?php echo $timeRange == '1y' ? 'active' : ''; ?>" data-range="1y">Son 1 Yıl</button>
                </div>
                
                <div class="export-controls">
                    <button class="export-btn">
                        <i class="fas fa-download"></i>
                        Rapor İndir
                    </button>
                    <button class="refresh-btn">
                        <i class="fas fa-sync-alt"></i>
                        Yenile
                    </button>
                </div>
            </div>
            
            <!-- Key Metrics -->
            <div class="analytics-metrics fade-in">
                <div class="metric-card users">
                    <div class="metric-header">
                        <div class="metric-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="metric-trend positive">
                            <i class="fas fa-arrow-up"></i>
                            +12.5%
                        </div>
                    </div>
                    <div class="metric-value"><?php echo number_format($totalUsers); ?></div>
                    <div class="metric-label">Toplam Kullanıcı</div>
                    <div class="metric-chart">
                        <canvas id="usersChart" width="100" height="40"></canvas>
                    </div>
                </div>
                
                <div class="metric-card organizers">
                    <div class="metric-header">
                        <div class="metric-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="metric-trend positive">
                            <i class="fas fa-arrow-up"></i>
                            +8.3%
                        </div>
                    </div>
                    <div class="metric-value"><?php echo number_format(count($approvedOrganizers)); ?></div>
                    <div class="metric-label">Aktif Organizatör</div>
                    <div class="metric-chart">
                        <canvas id="organizersChart" width="100" height="40"></canvas>
                    </div>
                </div>
                
                <div class="metric-card events">
                    <div class="metric-header">
                        <div class="metric-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="metric-trend positive">
                            <i class="fas fa-arrow-up"></i>
                            +<?php echo $totalEvents > 0 ? '15.2' : '0'; ?>%
                        </div>
                    </div>
                    <div class="metric-value"><?php echo number_format($totalEvents); ?></div>
                    <div class="metric-label">Toplam Etkinlik</div>
                    <div class="metric-chart">
                        <canvas id="eventsChart" width="100" height="40"></canvas>
                    </div>
                </div>
                
                <div class="metric-card revenue">
                    <div class="metric-header">
                        <div class="metric-icon">
                            <i class="fas fa-lira-sign"></i>
                        </div>
                        <div class="metric-trend positive">
                            <i class="fas fa-arrow-up"></i>
                            +<?php echo $totalRevenue > 0 ? '23.8' : '0'; ?>%
                        </div>
                    </div>
                    <div class="metric-value">₺<?php echo number_format($totalRevenue, 0, ',', '.'); ?></div>
                    <div class="metric-label">Toplam Gelir</div>
                    <div class="metric-chart">
                        <canvas id="revenueChart" width="100" height="40"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Charts Section -->
            <div class="analytics-charts">
                <!-- Main Chart -->
                <div class="chart-container main-chart fade-in">
                    <div class="chart-header">
                        <h3>Aylık Performans</h3>
                        <div class="chart-controls">
                            <button class="chart-type-btn active" data-type="users">Kullanıcılar</button>
                            <button class="chart-type-btn" data-type="events">Etkinlikler</button>
                            <button class="chart-type-btn" data-type="revenue">Gelir</button>
                        </div>
                    </div>
                    <div class="chart-content">
                        <canvas id="mainChart" width="800" height="400"></canvas>
                    </div>
                </div>
                
                <!-- Side Charts -->
                <div class="side-charts">
                    <!-- Category Distribution -->
                    <div class="chart-container category-chart fade-in">
                        <div class="chart-header">
                            <h3>Kategori Dağılımı</h3>
                        </div>
                        <div class="chart-content">
                            <canvas id="categoryChart" width="300" height="300"></canvas>
                        </div>
                        <div class="category-legend">
                            <?php 
                            $colors = ['#6366f1', '#f59e0b', '#10b981', '#ef4444', '#8b5cf6', '#06b6d4', '#f97316', '#84cc16'];
                            $colorIndex = 0;
                            foreach ($topCategories as $category): 
                            ?>
                            <div class="legend-item">
                                <div class="legend-color" style="background: <?php echo $colors[$colorIndex % count($colors)]; ?>"></div>
                                <span class="legend-label"><?php echo htmlspecialchars($category['category']); ?></span>
                                <span class="legend-value"><?php echo $category['percentage']; ?>%</span>
                            </div>
                            <?php 
                            $colorIndex++;
                            endforeach; 
                            ?>
                        </div>
                    </div>
                    
                    
                    
                </div>
            </div>
            
            <!-- Detailed Tables -->
            <div class="analytics-tables">
                <!-- Top Performers -->
                <div class="table-container fade-in">
                    <div class="table-header">
                        <h3>En Başarılı Organizatörler</h3>
                        <div class="table-controls">
                            <input type="text" class="table-search" placeholder="Ara...">
                            <button class="table-filter-btn">
                                <i class="fas fa-filter"></i>
                            </button>
                        </div>
                    </div>
                    <div class="table-content">
                        <table class="analytics-table">
                            <thead>
                                <tr>
                                    <th>Organizatör</th>
                                    <th>Etkinlik Sayısı</th>
                                    <th>Toplam Satış</th>
                                    <th>Gelir</th>
                                    <th>Durum</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($topOrganizers)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 20px; color: #666;">
                                        Seçilen tarih aralığında organizatör verisi bulunamadı.
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($topOrganizers as $organizer): ?>
                                <tr>
                                    <td>
                                        <div class="user-cell">
                                            <div class="user-avatar">
                                                <?php 
                                                $nameParts = explode(' ', $organizer['contact_person']);
                                                echo strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));
                                                ?>
                                            </div>
                                            <div class="user-info">
                                                <div class="user-name"><?php echo htmlspecialchars($organizer['company_name'] ?: $organizer['contact_person']); ?></div>
                                                <div class="user-email"><?php echo htmlspecialchars($organizer['email']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo number_format($organizer['event_count']); ?></td>
                                    <td><?php echo number_format($organizer['total_sales']); ?></td>
                                    <td>₺<?php echo number_format($organizer['revenue'], 0, ',', '.'); ?></td>
                                    <td><span class="status-badge success">Aktif</span></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Analytics JavaScript -->
<script>
// Analytics Dashboard JavaScript
class AnalyticsDashboard {
    constructor() {
        this.charts = {};
        this.currentTimeRange = '7d';
        this.currentChartType = 'users';
        this.init();
    }
    
    init() {
        this.initCharts();
        this.bindEvents();
        this.startAutoRefresh();
    }
    
    bindEvents() {
        // Time range buttons
        document.querySelectorAll('.time-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const range = e.target.dataset.range;
                window.location.href = 'analytics.php?range=' + range;
            });
        });
        
        // Chart type buttons
        document.querySelectorAll('.chart-type-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                document.querySelectorAll('.chart-type-btn').forEach(b => b.classList.remove('active'));
                e.target.classList.add('active');
                this.currentChartType = e.target.dataset.type;
                this.updateMainChart();
            });
        });
        
        // Refresh button
        document.querySelector('.refresh-btn').addEventListener('click', () => {
            this.refreshData();
        });
        
        // Export button
        document.querySelector('.export-btn').addEventListener('click', () => {
            this.exportReport();
        });
    }
    
    initCharts() {
        this.initMiniCharts();
        this.initMainChart();
        this.initCategoryChart();
    }
    
    initMiniCharts() {
        const miniChartData = {
            users: [12, 19, 15, 25, 22, 30, 28],
            organizers: [5, 8, 12, 15, 18, 22, 25],
            events: [8, 12, 18, 25, 22, 30, 35],
            revenue: [1000, 1500, 2200, 2800, 3200, 3800, 4200]
        };
        
        Object.keys(miniChartData).forEach(key => {
            const ctx = document.getElementById(key + 'Chart');
            if (ctx) {
                this.charts[key] = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: ['', '', '', '', '', '', ''],
                        datasets: [{
                            data: miniChartData[key],
                            borderColor: this.getChartColor(key),
                            backgroundColor: this.getChartColor(key, 0.1),
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            x: { display: false },
                            y: { display: false }
                        },
                        elements: {
                            point: { radius: 0 }
                        }
                    }
                });
            }
        });
    }
    
    initMainChart() {
        const ctx = document.getElementById('mainChart');
        if (ctx) {
            // PHP verilerini JavaScript'e aktar
            const monthlyUserData = <?php echo json_encode($monthlyUserStats); ?>;
            const monthlyEventData = <?php echo json_encode($monthlyEventStats); ?>;
            const monthlyRevenueData = <?php echo json_encode($monthlyRevenueStats); ?>;
            
            // Ay etiketlerini oluştur
            const labels = monthlyUserData.map(item => {
                const date = new Date(item.month + '-01');
                return date.toLocaleDateString('tr-TR', { month: 'long', year: 'numeric' });
            });
            
            // Kullanıcı verilerini oluştur
            const userData = monthlyUserData.map(item => parseInt(item.user_count));
            
            this.charts.main = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels.length > 0 ? labels : ['Veri Yok'],
                    datasets: [{
                        label: 'Kullanıcılar',
                        data: userData.length > 0 ? userData : [0],
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99, 102, 241, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        }
                    }
                }
            });
        }
    }
    
    initCategoryChart() {
        const ctx = document.getElementById('categoryChart');
        if (ctx) {
            // PHP kategori verilerini JavaScript'e aktar
            const categoryData = <?php echo json_encode($topCategories); ?>;
            const colors = ['#6366f1', '#f59e0b', '#10b981', '#ef4444', '#8b5cf6', '#06b6d4', '#f97316', '#84cc16'];
            
            const labels = categoryData.map(item => item.category);
            const data = categoryData.map(item => parseFloat(item.percentage));
            const backgroundColors = categoryData.map((item, index) => colors[index % colors.length]);
            
            this.charts.category = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels.length > 0 ? labels : ['Veri Yok'],
                    datasets: [{
                        data: data.length > 0 ? data : [100],
                        backgroundColor: backgroundColors.length > 0 ? backgroundColors : ['#e5e7eb'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    cutout: '70%'
                }
            });
        }
    }
    
    getChartColor(type, alpha = 1) {
        const colors = {
            users: `rgba(99, 102, 241, ${alpha})`,
            organizers: `rgba(245, 158, 11, ${alpha})`,
            events: `rgba(16, 185, 129, ${alpha})`,
            revenue: `rgba(239, 68, 68, ${alpha})`
        };
        return colors[type] || `rgba(99, 102, 241, ${alpha})`;
    }
    
    updateCharts() {
        // Simulate data update based on time range
        console.log('Updating charts for time range:', this.currentTimeRange);
        // Here you would fetch new data from the server
    }
    
    updateMainChart() {
        // PHP verilerini kullan
        const monthlyUserData = <?php echo json_encode($monthlyUserStats); ?>;
        const monthlyEventData = <?php echo json_encode($monthlyEventStats); ?>;
        const monthlyRevenueData = <?php echo json_encode($monthlyRevenueStats); ?>;
        
        const userData = monthlyUserData.map(item => parseInt(item.user_count));
        const eventData = monthlyEventData.map(item => parseInt(item.event_count));
        const revenueData = monthlyRevenueData.map(item => parseFloat(item.revenue));
        
        const data = {
            users: userData.length > 0 ? userData : [0],
            events: eventData.length > 0 ? eventData : [0],
            revenue: revenueData.length > 0 ? revenueData : [0]
        };
        
        this.charts.main.data.datasets[0].data = data[this.currentChartType];
        this.charts.main.data.datasets[0].label = this.getChartLabel(this.currentChartType);
        this.charts.main.data.datasets[0].borderColor = this.getChartColor(this.currentChartType);
        this.charts.main.data.datasets[0].backgroundColor = this.getChartColor(this.currentChartType, 0.1);
        this.charts.main.update();
    }
    
    getChartLabel(type) {
        const labels = {
            users: 'Kullanıcılar',
            events: 'Etkinlikler',
            revenue: 'Gelir (₺)'
        };
        return labels[type] || 'Kullanıcılar';
    }
    
    refreshData() {
        const refreshBtn = document.querySelector('.refresh-btn i');
        refreshBtn.style.animation = 'spin 1s linear infinite';
        
        setTimeout(() => {
            refreshBtn.style.animation = '';
            this.updateCharts();
        }, 1000);
    }
    
    exportReport() {
        // Simulate report export
        alert('Rapor indiriliyor...');
    }
    
    startAutoRefresh() {
        setInterval(() => {
            this.updateCharts();
        }, 300000); // 5 minutes
    }
}

// Initialize dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new AnalyticsDashboard();
    
    // Add fade-in animation
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    });
    
    document.querySelectorAll('.fade-in').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'all 0.6s ease';
        observer.observe(el);
    });
});

// Add spin animation for refresh button
const style = document.createElement('style');
style.textContent = `
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
`;
document.head.appendChild(style);
</script>

<?php include 'includes/footer.php'; ?>