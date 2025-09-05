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

// Tarih filtreleri
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // Bu ayın başı
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d'); // Bugün
$reportType = isset($_GET['report_type']) ? $_GET['report_type'] : 'overview';

// Genel istatistikler
$totalUsers = $user->getTotalUsers();
$pendingOrganizers = $organizer->getPendingOrganizers();

// Gelir raporu
$revenueQuery = "SELECT 
    DATE(o.created_at) as date,
    COUNT(*) as order_count,
    SUM(o.total_amount) as daily_revenue
    FROM orders o
    WHERE o.payment_status = 'paid' 
    AND DATE(o.created_at) BETWEEN ? AND ?
    GROUP BY DATE(o.created_at)
    ORDER BY date ASC";
$revenueStmt = $pdo->prepare($revenueQuery);
$revenueStmt->execute([$startDate, $endDate]);
$revenueData = $revenueStmt->fetchAll(PDO::FETCH_ASSOC);

// Toplam gelir hesaplama
$totalRevenue = array_sum(array_column($revenueData, 'daily_revenue'));
$totalOrderCount = array_sum(array_column($revenueData, 'order_count'));

// Kullanıcı kayıt raporu
$userRegistrationQuery = "SELECT 
    DATE(created_at) as date,
    COUNT(*) as user_count,
    user_type
    FROM users 
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY DATE(created_at), user_type
    ORDER BY date ASC";
$userRegistrationStmt = $pdo->prepare($userRegistrationQuery);
$userRegistrationStmt->execute([$startDate, $endDate]);
$userRegistrationData = $userRegistrationStmt->fetchAll(PDO::FETCH_ASSOC);

// Etkinlik raporu
$eventQuery = "SELECT 
    e.*,
    od.company_name,
    COUNT(DISTINCT t.id) as ticket_sales,
    COALESCE(SUM(t.price), 0) as event_revenue
    FROM events e
    LEFT JOIN organizer_details od ON e.organizer_id = od.user_id
    LEFT JOIN tickets t ON e.id = t.event_id
    LEFT JOIN orders o ON t.order_id = o.id AND o.payment_status = 'paid'
    WHERE DATE(e.created_at) BETWEEN ? AND ?
    GROUP BY e.id
    ORDER BY event_revenue DESC";
$eventStmt = $pdo->prepare($eventQuery);
$eventStmt->execute([$startDate, $endDate]);
$eventData = $eventStmt->fetchAll(PDO::FETCH_ASSOC);

// En popüler etkinlik kategorileri
$categoryQuery = "SELECT 
    e.category,
    COUNT(DISTINCT e.id) as event_count,
    COUNT(DISTINCT t.id) as total_tickets,
    COALESCE(SUM(t.price), 0) as category_revenue
    FROM events e
    LEFT JOIN tickets t ON e.id = t.event_id
    LEFT JOIN orders o ON t.order_id = o.id AND o.payment_status = 'paid'
    WHERE DATE(e.created_at) BETWEEN ? AND ?
    GROUP BY e.category
    ORDER BY category_revenue DESC";
$categoryStmt = $pdo->prepare($categoryQuery);
$categoryStmt->execute([$startDate, $endDate]);
$categoryData = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

// En aktif organizatörler
$organizerQuery = "SELECT 
    od.company_name,
    CONCAT(u.first_name, ' ', u.last_name) as contact_person,
    COUNT(DISTINCT e.id) as event_count,
    COUNT(DISTINCT t.id) as total_sales,
    COALESCE(SUM(t.price), 0) as organizer_revenue
    FROM organizer_details od
    LEFT JOIN users u ON od.user_id = u.id
    LEFT JOIN events e ON od.user_id = e.organizer_id
    LEFT JOIN tickets t ON e.id = t.event_id
    LEFT JOIN orders o ON t.order_id = o.id AND o.payment_status = 'paid'
    WHERE od.approval_status = 'approved'
    AND DATE(e.created_at) BETWEEN ? AND ?
    GROUP BY od.user_id
    HAVING event_count > 0
    ORDER BY organizer_revenue DESC
    LIMIT 10";
$organizerStmt = $pdo->prepare($organizerQuery);
$organizerStmt->execute([$startDate, $endDate]);
$organizerData = $organizerStmt->fetchAll(PDO::FETCH_ASSOC);

// Ödeme durumu raporu
$paymentStatusQuery = "SELECT 
    o.payment_status,
    COUNT(*) as count,
    SUM(o.total_amount) as amount
    FROM orders o
    WHERE DATE(o.created_at) BETWEEN ? AND ?
    GROUP BY o.payment_status";
$paymentStatusStmt = $pdo->prepare($paymentStatusQuery);
$paymentStatusStmt->execute([$startDate, $endDate]);
$paymentStatusData = $paymentStatusStmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="admin-container">
    <!-- Ultra Modern Sidebar -->
    <div class="admin-sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <img src="../uploads/logo.png" alt="BiletJack Logo" style="width: 120px; height: 120px; object-fit: contain;">
            </div>
            <h2 class="sidebar-title">Raporlar</h2>
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
                <a href="reports.php" class="nav-item active">
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
        <!-- Top Header -->
        <div class="admin-header">
            <div class="header-left">
                <button class="mobile-menu-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title">
                    <i class="fas fa-file-alt"></i>
                    Raporlar
                </h1>
                <p class="page-subtitle">Detaylı sistem raporları ve analizler</p>
            </div>
            <div class="header-right">
                <button class="export-btn" onclick="exportReport()">
                    <i class="fas fa-download"></i>
                    Rapor İndir
                </button>
                <a href="../auth/logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    Çıkış
                </a>
            </div>
        </div>

        <!-- Report Filters -->
        <div class="report-filters fade-in">
            <div class="modern-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-filter"></i>
                        Rapor Filtreleri
                    </h3>
                </div>
                <div class="card-body">
                    <form method="GET" class="filter-form">
                        <div class="filter-row">
                            <div class="filter-group">
                                <label for="start_date">Başlangıç Tarihi</label>
                                <input type="date" id="start_date" name="start_date" value="<?php echo $startDate; ?>" class="form-input">
                            </div>
                            <div class="filter-group">
                                <label for="end_date">Bitiş Tarihi</label>
                                <input type="date" id="end_date" name="end_date" value="<?php echo $endDate; ?>" class="form-input">
                            </div>
                            <div class="filter-group">
                                <label for="report_type">Rapor Türü</label>
                                <select id="report_type" name="report_type" class="form-select">
                                    <option value="overview" <?php echo $reportType == 'overview' ? 'selected' : ''; ?>>Genel Bakış</option>
                                    <option value="revenue" <?php echo $reportType == 'revenue' ? 'selected' : ''; ?>>Gelir Raporu</option>
                                    <option value="users" <?php echo $reportType == 'users' ? 'selected' : ''; ?>>Kullanıcı Raporu</option>
                                    <option value="events" <?php echo $reportType == 'events' ? 'selected' : ''; ?>>Etkinlik Raporu</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                    Filtrele
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Report Summary Cards -->
        <div class="report-summary fade-in">
            <div class="summary-card revenue">
                <div class="summary-icon">
                    <i class="fas fa-lira-sign"></i>
                </div>
                <div class="summary-content">
                    <h3 class="summary-value">₺<?php echo number_format($totalRevenue, 2); ?></h3>
                    <p class="summary-label">Toplam Gelir</p>
                    <span class="summary-period"><?php echo date('d.m.Y', strtotime($startDate)) . ' - ' . date('d.m.Y', strtotime($endDate)); ?></span>
                </div>
            </div>
            
            <div class="summary-card orders">
                <div class="summary-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="summary-content">
                    <h3 class="summary-value"><?php echo number_format($totalOrderCount); ?></h3>
                    <p class="summary-label">Toplam Sipariş</p>
                    <span class="summary-period">Ödeme Tamamlanan</span>
                </div>
            </div>
            
            <div class="summary-card events">
                <div class="summary-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="summary-content">
                    <h3 class="summary-value"><?php echo count($eventData); ?></h3>
                    <p class="summary-label">Yeni Etkinlik</p>
                    <span class="summary-period">Seçilen Dönem</span>
                </div>
            </div>
            
            <div class="summary-card users">
                <div class="summary-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="summary-content">
                    <h3 class="summary-value"><?php echo array_sum(array_column($userRegistrationData, 'user_count')); ?></h3>
                    <p class="summary-label">Yeni Kullanıcı</p>
                    <span class="summary-period">Kayıt Olan</span>
                </div>
            </div>
        </div>

        <!-- Report Content -->
        <div class="report-content fade-in">
            <?php if ($reportType == 'overview' || $reportType == 'revenue'): ?>
            <!-- Revenue Chart -->
            <div class="modern-card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">
                            <i class="fas fa-chart-line"></i>
                            Günlük Gelir Analizi
                        </h3>
                        <p class="card-subtitle">Seçilen dönem için günlük gelir dağılımı</p>
                    </div>
                    <div class="card-actions">
                        <button class="card-action" onclick="toggleChartType('revenue')">
                            <i class="fas fa-exchange-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-wrapper">
                        <canvas id="revenueChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($reportType == 'overview' || $reportType == 'events'): ?>
            <!-- Top Events Table -->
            <div class="modern-card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">
                            <i class="fas fa-trophy"></i>
                            En Başarılı Etkinlikler
                        </h3>
                        <p class="card-subtitle">Gelir bazında sıralanmış etkinlikler</p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Etkinlik Adı</th>
                                    <th>Organizatör</th>
                                    <th>Kategori</th>
                                    <th>Bilet Satışı</th>
                                    <th>Gelir</th>
                                    <th>Tarih</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($eventData, 0, 10) as $event): ?>
                                <tr>
                                    <td>
                                        <div class="event-info">
                                            <strong><?php echo htmlspecialchars($event['title']); ?></strong>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($event['company_name'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="category-badge <?php echo strtolower($event['category']); ?>">
                                            <?php echo htmlspecialchars($event['category']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo number_format($event['ticket_sales']); ?></td>
                                    <td class="revenue-cell">₺<?php echo number_format($event['event_revenue'], 2); ?></td>
                                    <td><?php echo date('d.m.Y', strtotime($event['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="report-grid">
                <?php if ($reportType == 'overview'): ?>
                <!-- Category Performance -->
                <div class="modern-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-chart-pie"></i>
                            Kategori Performansı
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="chart-wrapper">
                            <canvas id="categoryChart" width="400" height="300"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Top Organizers -->
                <div class="modern-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-building"></i>
                            En Aktif Organizatörler
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="organizer-list">
                            <?php foreach (array_slice($organizerData, 0, 5) as $index => $org): ?>
                            <div class="organizer-item">
                                <div class="organizer-rank"><?php echo $index + 1; ?></div>
                                <div class="organizer-info">
                                    <h4><?php echo htmlspecialchars($org['company_name']); ?></h4>
                                    <p><?php echo htmlspecialchars($org['contact_person']); ?></p>
                                    <div class="organizer-stats">
                                        <span><?php echo $org['event_count']; ?> etkinlik</span>
                                        <span>₺<?php echo number_format($org['organizer_revenue'], 2); ?></span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Payment Status -->
                <div class="modern-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-credit-card"></i>
                            Ödeme Durumu
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="payment-stats">
                            <?php foreach ($paymentStatusData as $payment): ?>
                            <div class="payment-item <?php echo $payment['payment_status']; ?>">
                                <div class="payment-icon">
                                    <i class="fas fa-<?php echo $payment['payment_status'] == 'paid' ? 'check-circle' : ($payment['payment_status'] == 'pending' ? 'clock' : 'times-circle'); ?>"></i>
                                </div>
                                <div class="payment-info">
                                    <h4><?php echo ucfirst($payment['payment_status']); ?></h4>
                                    <p><?php echo $payment['count']; ?> sipariş</p>
                                    <span>₺<?php echo number_format($payment['amount'], 2); ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Chart.js için veri hazırlama
const revenueData = {
    labels: <?php echo json_encode(array_column($revenueData, 'date')); ?>,
    datasets: [{
        label: 'Günlük Gelir (₺)',
        data: <?php echo json_encode(array_column($revenueData, 'daily_revenue')); ?>,
        backgroundColor: 'rgba(102, 126, 234, 0.1)',
        borderColor: 'rgba(102, 126, 234, 1)',
        borderWidth: 2,
        fill: true,
        tension: 0.4
    }]
};

const categoryData = {
    labels: <?php echo json_encode(array_column($categoryData, 'category')); ?>,
    datasets: [{
        label: 'Kategori Geliri (₺)',
        data: <?php echo json_encode(array_column($categoryData, 'category_revenue')); ?>,
        backgroundColor: [
            'rgba(102, 126, 234, 0.8)',
            'rgba(34, 197, 94, 0.8)',
            'rgba(251, 191, 36, 0.8)',
            'rgba(239, 68, 68, 0.8)',
            'rgba(168, 85, 247, 0.8)'
        ],
        borderColor: [
            'rgba(102, 126, 234, 1)',
            'rgba(34, 197, 94, 1)',
            'rgba(251, 191, 36, 1)',
            'rgba(239, 68, 68, 1)',
            'rgba(168, 85, 247, 1)'
        ],
        borderWidth: 2
    }]
};

// Grafikleri başlat
document.addEventListener('DOMContentLoaded', function() {
    // Revenue Chart
    const revenueCtx = document.getElementById('revenueChart');
    if (revenueCtx) {
        new Chart(revenueCtx, {
            type: 'line',
            data: revenueData,
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
                        },
                        ticks: {
                            callback: function(value) {
                                return '₺' + value.toLocaleString();
                            }
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

    // Category Chart
    const categoryCtx = document.getElementById('categoryChart');
    if (categoryCtx) {
        new Chart(categoryCtx, {
            type: 'doughnut',
            data: categoryData,
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
    }
});

// Export function
function exportReport() {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    const reportType = document.getElementById('report_type').value;
    
    // CSV export logic burada implement edilecek
    alert('Rapor indirme özelliği yakında eklenecek!');
}

// Animation
document.addEventListener('DOMContentLoaded', function() {
    const elements = document.querySelectorAll('.fade-in');
    elements.forEach((el, index) => {
        setTimeout(() => {
            el.style.opacity = '1';
            el.style.transform = 'translateY(0)';
        }, index * 100);
    });
});
</script>

<?php include 'includes/footer.php'; ?>