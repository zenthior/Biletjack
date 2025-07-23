<?php
session_start();
require_once '../config/database.php';

// Kullanıcı giriş kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Kullanıcı bilgilerini al
$user_query = "SELECT first_name, last_name FROM users WHERE id = ?";
$user_stmt = $pdo->prepare($user_query);
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

// İstatistikleri hesapla
$stats_query = "
    SELECT 
        COUNT(*) as total_orders,
        COALESCE(SUM(total_amount), 0) as total_spent
    FROM orders 
    WHERE user_id = ? AND payment_status = 'paid'
";
$stats_stmt = $pdo->prepare($stats_query);
$stats_stmt->execute([$user_id]);
$order_stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Bilet istatistiklerini al
$ticket_stats_query = "
    SELECT 
        COUNT(*) as total_tickets,
        COUNT(CASE WHEN t.status = 'active' THEN 1 END) as active_tickets
    FROM tickets t
    JOIN orders o ON t.order_id = o.id
    WHERE o.user_id = ? AND o.payment_status = 'paid'
";
$ticket_stats_stmt = $pdo->prepare($ticket_stats_query);
$ticket_stats_stmt->execute([$user_id]);
$ticket_stats = $ticket_stats_stmt->fetch(PDO::FETCH_ASSOC);

// İstatistikleri birleştir
$stats = [
    'total_tickets' => $ticket_stats['total_tickets'] ?? 0,
    'active_tickets' => $ticket_stats['active_tickets'] ?? 0,
    'total_spent' => $order_stats['total_spent'] ?? 0
];

// Son siparişleri al
$recent_orders_query = "
    SELECT o.*, e.title as event_title, e.event_date as event_date 
    FROM orders o
    LEFT JOIN events e ON o.event_id = e.id
    WHERE o.user_id = ? AND o.payment_status = 'paid'
    ORDER BY o.created_at DESC
    LIMIT 5
";
$recent_stmt = $pdo->prepare($recent_orders_query);
$recent_stmt->execute([$user_id]);
$recent_orders = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);

// Aylık harcama verilerini al (son 6 ay)
$monthly_query = "
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        SUM(total_amount) as amount
    FROM orders 
    WHERE user_id = ? AND payment_status = 'paid' 
        AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month
";
$monthly_stmt = $pdo->prepare($monthly_query);
$monthly_stmt->execute([$user_id]);
$monthly_data = $monthly_stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="main-content">
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="welcome-section">
            <h1>Hoş geldin, <?php echo htmlspecialchars($user['first_name']); ?></h1>
            <p>Bugün nasılsın? İşte hesabının özeti.</p>
        </div>
        <div class="user-profile">
            <div class="user-avatar">
                <?php echo strtoupper(substr($user['first_name'], 0, 1)); ?>
            </div>
            <div class="user-info">
                <h3><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>
                <p>Müşteri Hesabı</p>
            </div>
        </div>
    </div>

    <!-- İstatistik Kartları -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-header">
                <div>
                    <div class="stat-title">Toplam Bilet</div>
                    <div class="stat-value"><?php echo number_format($stats['total_tickets']); ?></div>
                </div>
                <div class="stat-icon blue">
                    <i class="fas fa-ticket-alt"></i>
                </div>
            </div>
            <div class="stat-change positive">
                <i class="fas fa-arrow-up"></i>
                +12% bu ay
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-header">
                <div>
                    <div class="stat-title">Aktif Bilet</div>
                    <div class="stat-value"><?php echo number_format($stats['active_tickets']); ?></div>
                </div>
                <div class="stat-icon green">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
            <div class="stat-change positive">
                <i class="fas fa-arrow-up"></i>
                +8% bu ay
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-header">
                <div>
                    <div class="stat-title">Toplam Harcama</div>
                    <div class="stat-value">₺<?php echo number_format($stats['total_spent'], 2); ?></div>
                </div>
                <div class="stat-icon purple">
                    <i class="fas fa-lira-sign"></i>
                </div>
            </div>
            <div class="stat-change negative">
                <i class="fas fa-arrow-down"></i>
                -3% bu ay
            </div>
        </div>
    </div>

    <!-- Kullanım Özeti -->
    <div class="usage-overview">
        <div class="section-header">
            <h2 class="section-title">Planlar & Kullanım</h2>
            <a href="#" class="view-all-btn">
                Tümünü Gör <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        
        <div class="usage-grid">
            <div class="usage-item">
                <div class="usage-percentage orange">24%</div>
                <div class="usage-label">Mobil (Lisans)</div>
                <div class="usage-sublabel">ACTIVE PLAN - 2024</div>
            </div>
            
            <div class="usage-item">
                <div class="usage-percentage orange">27%</div>
                <div class="usage-label">Ev Telefonu (Kardeş)</div>
                <div class="usage-sublabel">ACTIVE PLAN - 2024</div>
            </div>
            
            <div class="usage-item">
                <div class="usage-percentage red">₺60.00</div>
                <div class="usage-label">Ev Telefonu (Diğer Pl...)</div>
                <div class="usage-sublabel">EXPIRED PLAN - 2024</div>
            </div>
            
            <div class="usage-item">
                <div class="usage-percentage red">₺35.00</div>
                <div class="usage-label">Cep Telefonu (Metlife Pl...)</div>
                <div class="usage-sublabel">EXPIRED PLAN - 2024</div>
            </div>
        </div>
    </div>

    <!-- Faturalama ve Ödemeler -->
    <div class="billing-payments">
        <div class="section-header">
            <h2 class="section-title">Faturalama & Ödemeler</h2>
            <a href="#" class="view-all-btn">
                Tümünü Gör <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        
        <div class="billing-content">
            <div class="billing-left">
                <div class="billing-info">
                    <div class="billing-amount">₺<?php echo number_format($stats['total_spent'], 2); ?></div>
                    <div class="billing-period">Toplam Harcama</div>
                </div>
                
                <div class="billing-details">
                    <p>Nisan 2024 faturası</p>
                    <p>Son ödeme tarihi: 15 Mayıs 2024</p>
                </div>
            </div>
            
            <div class="payment-card">
                <div class="card-amount">₺35.00</div>
                <div class="card-label">SON FATURA</div>
                <a href="#" class="pay-invoice-btn">FATURA ÖDE</a>
            </div>
        </div>
    </div>

    <!-- Fatura Geçmişi Grafiği -->
    <div class="chart-container">
        <div class="section-header">
            <h2 class="section-title">Fatura Geçmişi</h2>
        </div>
        <canvas id="monthlyChart" width="400" height="200"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Aylık harcama grafiği
const ctx = document.getElementById('monthlyChart').getContext('2d');
const monthlyData = <?php echo json_encode($monthly_data); ?>;

const labels = monthlyData.map(item => {
    const date = new Date(item.month + '-01');
    return date.toLocaleDateString('tr-TR', { month: 'short' });
});

const amounts = monthlyData.map(item => parseFloat(item.amount));

const chart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Aylık Harcama (₺)',
            data: amounts,
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#3b82f6',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 6
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
            y: {
                beginAtZero: true,
                grid: {
                    color: '#f1f5f9'
                },
                ticks: {
                    color: '#64748b',
                    callback: function(value) {
                        return '₺' + value;
                    }
                }
            },
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    color: '#64748b'
                }
            }
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>