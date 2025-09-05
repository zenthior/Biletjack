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

<div class="modern-dashboard">
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="welcome-section">
            <h1 class="welcome-title">Hoş geldin, <?php echo htmlspecialchars($user['first_name']); ?></h1>
            <p class="welcome-subtitle">Bilet yönetim panelinize hoş geldiniz</p>
        </div>
        <div class="user-profile">
            <div class="user-avatar">
                <?php echo strtoupper(substr($user['first_name'], 0, 1)); ?>
            </div>
            <div class="user-info">
                <h3 class="user-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>
                <p class="user-id">Kullanıcı ID: #<?php echo $user_id; ?></p>
            </div>
        </div>
    </div>

    <!-- Dashboard Grid -->
    <div class="dashboard-grid">

        <!-- Sol Kolon -->
        <div class="left-column">
            <!-- Planlar & Kullanım -->
            <div class="section-header">
            </div>
            
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-label">Biletler</div>
                    </div>
                    <div class="stat-circle">
                        <svg class="progress-ring" width="80" height="80">
                            <circle class="progress-ring-circle" stroke="#00D4FF" stroke-width="6" fill="transparent" r="34" cx="40" cy="40" style="stroke-dasharray: 213.6; stroke-dashoffset: 160.2;"/>
                        </svg>
                        <div class="stat-percentage"><?php echo $stats['active_tickets']; ?>%</div>
                    </div>
                    <div class="stat-details">
                        <div class="stat-title">Aktif Biletler</div>
                    </div>
                </div>
            </div>
            
            <!-- Kullanım Özeti -->
            <div class="usage-summary">
                <div class="usage-item">
                    <div class="usage-icon mobile">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <div class="usage-details">
                        <div class="usage-title">Biletler</div>
                        <div class="usage-subtitle">Dijital bilet kullanımı</div>
                    </div>
                    <div class="usage-amount"><?php echo $stats['active_tickets']; ?></div>
                </div>
            </div>
        </div>
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