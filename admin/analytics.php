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

// Analytics verileri (şimdilik demo veriler, gerçek veriler eklenecek)
$totalUsers = $user->getTotalUsers();
$pendingOrganizers = $organizer->getPendingOrganizers();
$approvedOrganizers = $organizer->getApprovedOrganizers();
$rejectedOrganizers = $organizer->getRejectedOrganizers();

// Demo analytics verileri
$monthlyStats = [
    'Ocak' => ['users' => 45, 'events' => 12, 'revenue' => 15000],
    'Şubat' => ['users' => 62, 'events' => 18, 'revenue' => 22000],
    'Mart' => ['users' => 78, 'events' => 25, 'revenue' => 31000],
    'Nisan' => ['users' => 95, 'events' => 32, 'revenue' => 45000],
    'Mayıs' => ['users' => 112, 'events' => 28, 'revenue' => 38000],
    'Haziran' => ['users' => 134, 'events' => 35, 'revenue' => 52000]
];

$topCategories = [
    ['name' => 'Konser', 'count' => 45, 'percentage' => 35],
    ['name' => 'Tiyatro', 'count' => 32, 'percentage' => 25],
    ['name' => 'Spor', 'count' => 28, 'percentage' => 22],
    ['name' => 'Konferans', 'count' => 23, 'percentage' => 18]
];

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
                <i class="fas fa-ticket-alt"></i>
            </div>
            <h2 class="sidebar-title">BiletJack</h2>
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
        
        <!-- Analytics Content -->
        <div class="analytics-container">
            <!-- Time Range Selector -->
            <div class="analytics-controls fade-in">
                <div class="time-range-selector">
                    <button class="time-btn active" data-range="7d">Son 7 Gün</button>
                    <button class="time-btn" data-range="30d">Son 30 Gün</button>
                    <button class="time-btn" data-range="90d">Son 3 Ay</button>
                    <button class="time-btn" data-range="1y">Son 1 Yıl</button>
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
                            +0%
                        </div>
                    </div>
                    <div class="metric-value">0</div>
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
                            +0%
                        </div>
                    </div>
                    <div class="metric-value">₺0</div>
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
                            <?php foreach ($topCategories as $category): ?>
                            <div class="legend-item">
                                <div class="legend-color" style="background: hsl(<?php echo rand(0, 360); ?>, 70%, 60%)"></div>
                                <span class="legend-label"><?php echo $category['name']; ?></span>
                                <span class="legend-value"><?php echo $category['percentage']; ?>%</span>
                            </div>
                            <?php endforeach; ?>
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
                                <tr>
                                    <td>
                                        <div class="user-cell">
                                            <div class="user-avatar">MK</div>
                                            <div class="user-info">
                                                <div class="user-name">Mehmet Kaya</div>
                                                <div class="user-email">mehmet@example.com</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>15</td>
                                    <td>1,234</td>
                                    <td>₺45,600</td>
                                    <td><span class="status-badge success">Aktif</span></td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="user-cell">
                                            <div class="user-avatar">AY</div>
                                            <div class="user-info">
                                                <div class="user-name">Ayşe Yılmaz</div>
                                                <div class="user-email">ayse@example.com</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>12</td>
                                    <td>987</td>
                                    <td>₺32,100</td>
                                    <td><span class="status-badge success">Aktif</span></td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="user-cell">
                                            <div class="user-avatar">FD</div>
                                            <div class="user-info">
                                                <div class="user-name">Fatma Demir</div>
                                                <div class="user-email">fatma@example.com</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>8</td>
                                    <td>654</td>
                                    <td>₺28,900</td>
                                    <td><span class="status-badge warning">Beklemede</span></td>
                                </tr>
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
                document.querySelectorAll('.time-btn').forEach(b => b.classList.remove('active'));
                e.target.classList.add('active');
                this.currentTimeRange = e.target.dataset.range;
                this.updateCharts();
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
            this.charts.main = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran'],
                    datasets: [{
                        label: 'Kullanıcılar',
                        data: [45, 62, 78, 95, 112, 134],
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
            this.charts.category = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Konser', 'Tiyatro', 'Spor', 'Konferans'],
                    datasets: [{
                        data: [35, 25, 22, 18],
                        backgroundColor: [
                            '#6366f1',
                            '#f59e0b',
                            '#10b981',
                            '#ef4444'
                        ],
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
        const data = {
            users: [45, 62, 78, 95, 112, 134],
            events: [12, 18, 25, 32, 28, 35],
            revenue: [15000, 22000, 31000, 45000, 38000, 52000]
        };
        
        this.charts.main.data.datasets[0].data = data[this.currentChartType];
        this.charts.main.data.datasets[0].label = this.getChartLabel(this.currentChartType);
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