<?php
require_once '../includes/session.php';
require_once '../config/database.php';

// Organizatör kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'organizer') {
    http_response_code(403);
    exit('Yetkisiz erişim');
}

$organizer_id = $_SESSION['user_id'];

// Analitik verilerini al
$database = new Database();
$pdo = $database->getConnection();

// Etkinlik listesi + sepetteki benzersiz kullanıcı sayısı alt sorgusu
$stmt = $pdo->prepare("
    SELECT e.*,
           COALESCE(ic.in_cart_users, 0) AS in_cart_users
    FROM events e
    LEFT JOIN (
        SELECT event_id, COUNT(DISTINCT user_id) AS in_cart_users
        FROM cart
        GROUP BY event_id
    ) ic ON ic.event_id = e.id
    WHERE e.organizer_id = ?
    ORDER BY e.created_at DESC
");
$stmt->execute([$organizer_id]);
$events = $stmt->fetchAll();

// Toplam istatistikler
$stmt = $pdo->prepare("SELECT COUNT(*) as total_events FROM events WHERE organizer_id = ?");
$stmt->execute([$organizer_id]);
$total_events = $stmt->fetchColumn();

// Toplam gelir (tickets üzerinden)
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(t.price * COALESCE(t.quantity, 1)), 0) as total_revenue
    FROM tickets t
    JOIN events e ON t.event_id = e.id
    JOIN orders o ON t.order_id = o.id
    WHERE e.organizer_id = ? AND o.payment_status = 'paid'
");
$stmt->execute([$organizer_id]);
$total_revenue = $stmt->fetchColumn();

// Satılan bilet adedi (quantity üzerinden)
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(COALESCE(t.quantity, 1)), 0) as total_tickets
    FROM tickets t
    JOIN events e ON t.event_id = e.id
    JOIN orders o ON t.order_id = o.id
    WHERE e.organizer_id = ? AND o.payment_status = 'paid'
");
$stmt->execute([$organizer_id]);
$total_tickets = $stmt->fetchColumn();

// Toplam görüntülenme
$stmt = $pdo->prepare("SELECT COALESCE(SUM(views), 0) FROM events WHERE organizer_id = ?");
$stmt->execute([$organizer_id]);
$total_views = $stmt->fetchColumn();

// Sepetteki benzersiz kullanıcılar (organizatörün tüm etkinlikleri için)
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT c.user_id) AS users_in_carts
    FROM cart c
    JOIN events e ON c.event_id = e.id
    WHERE e.organizer_id = ?
");
$stmt->execute([$organizer_id]);
$users_in_carts = $stmt->fetchColumn();

// Son satın alınan biletler
$stmt = $pdo->prepare("
    SELECT t.ticket_number, t.status, t.created_at, t.used_at,
           e.title as event_title, e.event_date,
           u.first_name, u.last_name, u.email,
           o.total_amount,
           tt.name AS ticket_type_name
    FROM tickets t 
    JOIN events e ON t.event_id = e.id 
    JOIN orders o ON t.order_id = o.id 
    JOIN users u ON o.user_id = u.id
    LEFT JOIN ticket_types tt ON tt.id = t.ticket_type_id
    WHERE e.organizer_id = ? 
    ORDER BY t.created_at DESC 
    LIMIT 20
");
$stmt->execute([$organizer_id]);
$recent_tickets = $stmt->fetchAll();

// Bilet türü dağılımı
$stmt = $pdo->prepare("
    SELECT tt.name AS ticket_type_name, COALESCE(SUM(COALESCE(t.quantity, 1)), 0) as count 
    FROM tickets t 
    JOIN events e ON t.event_id = e.id
    LEFT JOIN ticket_types tt ON tt.id = t.ticket_type_id
    WHERE e.organizer_id = ? 
    GROUP BY tt.name
");
$stmt->execute([$organizer_id]);
$ticket_distribution = $stmt->fetchAll();

// Aylık satış verileri (revenue)
$stmt = $pdo->prepare("
    SELECT DATE_FORMAT(o.created_at, '%Y-%m') as month, 
           COALESCE(SUM(t.price * COALESCE(t.quantity, 1)), 0) as revenue
    FROM orders o 
    JOIN tickets t ON t.order_id = o.id
    JOIN events e ON t.event_id = e.id 
    WHERE e.organizer_id = ? AND o.payment_status = 'paid'
    GROUP BY DATE_FORMAT(o.created_at, '%Y-%m')
    ORDER BY month DESC
    LIMIT 12
");
$stmt->execute([$organizer_id]);
$monthly_sales = $stmt->fetchAll();

// Bilet türlerini Türkçe'ye çevir (tt.name varsa doğrudan onu yazacağız)
function getTicketTypeName($type) {
    switch($type) {
        case 'standard': return 'Standart';
        case 'vip': return 'VIP';
        case 'student': return 'Öğrenci';
        case 'early_bird': return 'Erken Kuş';
        default: return ucfirst($type);
    }
}
?>

<div class="dashboard-header">
    <div>
        <h1>Analitik ve Raporlar</h1>
        <p>Etkinliklerinizin detaylı analiz ve satış raporları</p>
    </div>
</div>

<!-- Özet İstatistikler -->
<div class="stats-row">
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Toplam Etkinlik</span>
            <div class="stat-icon">
                <i class="fas fa-calendar-alt"></i>
            </div>
        </div>
        <div class="stat-value"><?php echo number_format($total_events); ?></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Toplam Gelir</span>
            <div class="stat-icon revenue">
                <i class="fas fa-lira-sign"></i>
            </div>
        </div>
        <div class="stat-value">₺<?php echo number_format($total_revenue, 2); ?></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Satılan Bilet</span>
            <div class="stat-icon orders">
                <i class="fas fa-ticket-alt"></i>
            </div>
        </div>
        <div class="stat-value"><?php echo number_format($total_tickets); ?></div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Toplam Görüntülenme</span>
            <div class="stat-icon visits">
                <i class="fas fa-eye"></i>
            </div>
        </div>
        <div class="stat-value"><?php echo number_format($total_views); ?></div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Sepetteki Kullanıcılar</span>
            <div class="stat-icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
        </div>
        <div class="stat-value"><?php echo number_format($users_in_carts); ?></div>
    </div>
</div>

<!-- Ana Grid -->
<div class="main-grid">
    <!-- Sol Kolon -->
    <div>
        <!-- Son Bilet Satışları -->
        <div class="analytics-card">
            <div class="card-header">
                <h3 class="card-title">Son Bilet Satışları</h3>
                <span class="date-range">Son 20 bilet</span>
            </div>
            
            <div class="table-container">
                <?php if (empty($recent_tickets)): ?>
                    <div class="empty-state">
                        <i class="fas fa-ticket-alt" style="font-size: 48px; color: #e2e8f0; margin-bottom: 16px;"></i>
                        <p>Henüz bilet satışı bulunmuyor</p>
                        <small>İlk etkinliğinizi oluşturun ve bilet satışlarını burada görün</small>
                    </div>
                <?php else: ?>
                    <table class="analytics-table">
                        <thead>
                            <tr>
                                <th>Bilet Kodu</th>
                                <th>Müşteri</th>
                                <th>Etkinlik</th>
                                <th>Tür</th>
                                <th>Durum</th>
                                <th>Tarih</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_tickets as $ticket): ?>
                            <tr>
                                <td>
                                    <code><?php echo htmlspecialchars($ticket['ticket_number']); ?></code>
                                </td>
                                <td>
                                    <div class="customer-info">
                                        <strong><?php echo htmlspecialchars($ticket['first_name'] . ' ' . $ticket['last_name']); ?></strong>
                                        <small><?php echo htmlspecialchars($ticket['email']); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div class="event-info">
                                        <strong><?php echo htmlspecialchars($ticket['event_title']); ?></strong>
                                        <small><?php echo date('d.m.Y', strtotime($ticket['event_date'])); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <span class="ticket-type"><?php echo htmlspecialchars($ticket['ticket_type_name'] ?? 'Bilet'); ?></span>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $ticket['status']; ?>">
                                        <?php 
                                        switch($ticket['status']) {
                                            case 'active': echo 'Aktif'; break;
                                            case 'used': echo 'Kullanıldı'; break;
                                            case 'cancelled': echo 'İptal'; break;
                                            default: echo ucfirst($ticket['status']);
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="date-info">
                                        <strong><?php echo date('d.m.Y', strtotime($ticket['created_at'])); ?></strong>
                                        <small><?php echo date('H:i', strtotime($ticket['created_at'])); ?></small>
                                        <?php if ($ticket['used_at']): ?>
                                            <br><small class="used-date">Kullanıldı: <?php echo date('d.m.Y H:i', strtotime($ticket['used_at'])); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Aylık Satış Grafiği -->
        <?php if (!empty($monthly_sales)): ?>
        <div class="analytics-card" style="margin-top: 24px;">
            <div class="card-header">
                <h3 class="card-title">Aylık Satış Trendi</h3>
                <span class="date-range">Son 12 ay</span>
            </div>
            
            <div class="chart-container">
                <div class="monthly-chart">
                    <?php foreach (array_reverse($monthly_sales) as $month): ?>
                    <div class="month-bar">
                        <div class="bar" style="height: <?php echo min(100, ($month['revenue'] / max(array_column($monthly_sales, 'revenue'))) * 100); ?>%;"></div>
                        <div class="month-label">
                            <strong>₺<?php echo number_format($month['revenue']); ?></strong>
                            <small><?php echo date('M Y', strtotime($month['month'] . '-01')); ?></small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Sağ Kolon -->
    <div>
        <!-- Bilet Türü Dağılımı -->
        <?php if (!empty($ticket_distribution)): ?>
        <div class="analytics-card">
            <div class="card-header">
                <h3 class="card-title">Bilet Türü Dağılımı</h3>
            </div>
            
            <div class="distribution-chart">
                <?php 
                $total_dist = array_sum(array_column($ticket_distribution, 'count'));
                $colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'];
                $i = 0;
                ?>
                <?php foreach ($ticket_distribution as $dist): ?>
                <div class="distribution-item">
                    <div class="distribution-bar">
                        <div class="bar-fill" style="width: <?php echo ($dist['count'] / $total_dist) * 100; ?>%; background-color: <?php echo $colors[$i % count($colors)]; ?>;"></div>
                    </div>
                    <div class="distribution-info">
                        <span class="type-name"><?php echo htmlspecialchars($dist['ticket_type_name'] ?? 'Bilet'); ?></span>
                        <span class="type-count"><?php echo (int)$dist['count']; ?> bilet</span>
                        <span class="type-percent"><?php echo round(($dist['count'] / $total_dist) * 100, 1); ?>%</span>
                    </div>
                </div>
                <?php $i++; endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        <div class="analytics-card" style="margin-top: 24px;">
            <div class="card-header">
                <h3 class="card-title">Etkinliklerim</h3>
                <span class="date-range"><?php echo count($events); ?> etkinlik</span>
            </div>
            
            <div class="events-list">
                <?php if (empty($events)): ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-plus" style="font-size: 32px; color: #e2e8f0; margin-bottom: 12px;"></i>
                        <p>Henüz etkinlik yok</p>
                        <small>İlk etkinliğinizi oluşturun</small>
                    <?php else: ?>
                        <?php foreach (array_slice($events, 0, 5) as $event): ?>
                        <div class="event-item">
                            <div class="event-date">
                                <span class="day"><?php echo date('d', strtotime($event['event_date'])); ?></span>
                                <span class="month"><?php echo date('M', strtotime($event['event_date'])); ?></span>
                            </div>
                            <div class="event-info">
                                <h4><?php echo htmlspecialchars($event['title']); ?></h4>
                                <p><?php echo htmlspecialchars($event['venue_name']); ?></p>
                                <small><?php echo date('H:i', strtotime($event['event_date'])); ?></small>
                                <div style="margin-top:6px; display:flex; gap:6px; flex-wrap:wrap;">
                                    <span class="status-badge" style="background:#e5e7eb; color:#111827;">
                                        <i class="fas fa-eye"></i> <?php echo (int)($event['views'] ?? 0); ?> görüntülenme
                                    </span>
                                    <span class="status-badge" style="background:#dbeafe; color:#1e3a8a;">
                                        <i class="fas fa-shopping-cart"></i> <?php echo (int)($event['in_cart_users'] ?? 0); ?> sepette
                                    </span>
                                </div>
                            </div>
                            <div class="event-status">
                                <span class="status-badge <?php echo $event['status']; ?>">
                                    <?php 
                                    switch($event['status']) {
                                        case 'active': echo 'Aktif'; break;
                                        case 'pending': echo 'Beklemede'; break;
                                        case 'cancelled': echo 'İptal'; break;
                                        default: echo ucfirst($event['status']);
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php if (count($events) > 5): ?>
                        <div class="view-all">
                            <a href="events.php" class="view-all-link">Tümünü Gör (<?php echo count($events); ?>)</a>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Ana Grid Layout */
.main-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 24px;
    margin-top: 24px;
}

.analytics-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 16px;
}

.analytics-table th,
.analytics-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
}

.analytics-table th {
    background-color: #f8fafc;
    font-weight: 600;
    color: #374151;
    font-size: 14px;
}

.customer-info strong {
    display: block;
    color: #111827;
}

.customer-info small {
    color: #6b7280;
    font-size: 12px;
}

.event-info strong {
    display: block;
    color: #111827;
}

.event-info small {
    color: #6b7280;
    font-size: 12px;
}

.ticket-type {
    background-color: #ddd6fe;
    color: #5b21b6;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}

.status-badge.active {
    background-color: #dcfce7;
    color: #166534;
}

.status-badge.used {
    background-color: #e0e7ff;
    color: #3730a3;
}

.status-badge.cancelled {
    background-color: #fee2e2;
    color: #991b1b;
}

.date-info strong {
    display: block;
    color: #111827;
}

.date-info small {
    color: #6b7280;
    font-size: 12px;
}

.used-date {
    color: #059669 !important;
    font-weight: 500;
}

.monthly-chart {
    display: flex;
    align-items: end;
    gap: 8px;
    padding: 20px;
    min-height: 200px;
}

.month-bar {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.bar {
    width: 100%;
    background: linear-gradient(to top, #3b82f6, #60a5fa);
    border-radius: 4px 4px 0 0;
    min-height: 4px;
    margin-bottom: 8px;
}

.month-label {
    text-align: center;
}

.month-label strong {
    display: block;
    font-size: 12px;
    color: #111827;
}

.month-label small {
    font-size: 10px;
    color: #6b7280;
}

.distribution-chart {
    padding: 20px;
}

.distribution-item {
    margin-bottom: 16px;
}

.distribution-bar {
    width: 100%;
    height: 8px;
    background-color: #f1f5f9;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 8px;
}

.bar-fill {
    height: 100%;
    border-radius: 4px;
}

.distribution-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.type-name {
    font-weight: 500;
    color: #111827;
}

.type-count {
    color: #6b7280;
    font-size: 14px;
}

.type-percent {
    color: #374151;
    font-weight: 500;
    font-size: 14px;
}

.events-list {
    padding: 20px;
}

.event-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 12px 0;
    border-bottom: 1px solid #f1f5f9;
}

.event-item:last-child {
    border-bottom: none;
}

.event-date {
    display: flex;
    flex-direction: column;
    align-items: center;
    background-color: #f8fafc;
    border-radius: 8px;
    padding: 8px;
    min-width: 50px;
}

.event-date .day {
    font-size: 18px;
    font-weight: 700;
    color: #111827;
    line-height: 1;
}

.event-date .month {
    font-size: 12px;
    color: #6b7280;
    text-transform: uppercase;
}

.event-info {
    flex: 1;
}

.event-info h4 {
    margin: 0 0 4px 0;
    font-size: 14px;
    font-weight: 600;
    color: #111827;
}

.event-info p {
    margin: 0 0 2px 0;
    font-size: 13px;
    color: #6b7280;
}

.event-info small {
    font-size: 12px;
    color: #9ca3af;
}

.view-all {
    text-align: center;
    padding-top: 16px;
    border-top: 1px solid #f1f5f9;
    margin-top: 16px;
}

.view-all-link {
    color: #3b82f6;
    text-decoration: none;
    font-weight: 500;
    font-size: 14px;
}

.view-all-link:hover {
    color: #2563eb;
}

.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #6b7280;
}

.table-container {
    overflow-x: auto;
}

code {
    background-color: #f1f5f9;
    color: #374151;
    padding: 2px 6px;
    border-radius: 4px;
    font-family: 'Courier New', monospace;
    font-size: 12px;
}

/* Mobil Uyumluluk */
@media (max-width: 768px) {
    .stats-row {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
    
    .stat-card {
        padding: 16px;
    }
    
    .stat-value {
        font-size: 20px;
    }
    
    .main-grid {
        grid-template-columns: 1fr !important;
        gap: 16px !important;
    }
    
    .analytics-card {
        margin: 0 !important;
    }
    
    .table-container {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .analytics-table {
        min-width: 600px;
    }
    
    .analytics-table th,
    .analytics-table td {
        padding: 8px 6px;
        font-size: 12px;
    }
    
    .customer-info strong,
    .event-info strong {
        font-size: 12px;
    }
    
    .customer-info small,
    .event-info small {
        font-size: 10px;
    }
    
    .monthly-chart {
        padding: 12px;
        min-height: 150px;
        gap: 4px;
    }
    
    .month-label strong {
        font-size: 10px;
    }
    
    .month-label small {
        font-size: 8px;
    }
    
    .distribution-chart {
        padding: 12px;
    }
    
    .distribution-item {
        margin-bottom: 12px;
    }
    
    .distribution-info {
        flex-wrap: wrap;
        gap: 4px;
    }
    
    .type-name {
        font-size: 13px;
    }
    
    .type-count,
    .type-percent {
        font-size: 12px;
    }
    
    .events-list {
        padding: 12px;
    }
    
    .event-item {
        gap: 12px;
        padding: 10px 0;
    }
    
    .event-date {
        min-width: 45px;
        padding: 6px;
    }
    
    .event-date .day {
        font-size: 16px;
    }
    
    .event-date .month {
        font-size: 10px;
    }
    
    .event-info h4 {
        font-size: 13px;
    }
    
    .event-info p {
        font-size: 12px;
    }
    
    .event-info small {
        font-size: 11px;
    }
    
    .status-badge {
        font-size: 10px;
        padding: 3px 6px;
    }
}

@media (max-width: 480px) {
    /* Genel container ayarları */
    body {
        overflow-x: hidden;
    }
    
    .dashboard-header {
        padding: 15px;
        margin-bottom: 20px;
        text-align: center;
        width: 100%;
        box-sizing: border-box;
    }

    .dashboard-content {
        width: 100%;
        box-sizing: border-box;
        padding: 10px;
        display: block;
        overflow-x: hidden;
    }
    
    .dashboard-header {
        width: 100%;
        box-sizing: border-box;
        padding: 10px;
        text-align: center;
    }
    
    .dashboard-header h1 {
        font-size: 20px;
        margin-bottom: 5px;
    }
    
    .dashboard-header p {
        font-size: 12px;
    }
    
    /* Stats row - tam genişlik */
    .stats-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-bottom: 20px;
        padding: 0 10px;
        width: 100%;
        box-sizing: border-box;
    }
    
    .stat-card {
        padding: 12px;
        text-align: center;
        width: 100%;
        box-sizing: border-box;
        border-radius: 8px;
        background: white;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .stat-header {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
        margin-bottom: 8px;
    }
    
    .stat-title {
        font-size: 10px;
        font-weight: 500;
        color: #666;
        text-align: center;
    }
    
    .stat-value {
        font-size: 18px;
        font-weight: 600;
        color: #333;
    }
    
    .stat-icon {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .stat-icon i {
        font-size: 12px;
    }
    
    /* Main grid - tek sütun - inline style override */
    .main-grid {
        display: grid !important;
        grid-template-columns: 1fr !important;
        gap: 15px !important;
        padding: 0 10px;
        width: 100%;
        box-sizing: border-box;
        max-width: 100vw;
    }
    
    .main-grid > div {
        width: 100%;
        box-sizing: border-box;
    }
    
    /* Analytics kartları */
    .analytics-card {
        margin: 0 0 16px 0;
        padding: 16px;
        border-radius: 8px;
        width: 100%;
        display: grid;
        max-width: 100%;
        box-sizing: border-box;
        background: white;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        border: 1px solid #e2e8f0;
        overflow: hidden;
    }
    
    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
        padding-bottom: 12px;
        border-bottom: 1px solid #e2e8f0;
    }
    
    .card-title {
        font-size: 16px;
        font-weight: 600;
        color: #333;
        margin: 0;
    }
    
    .date-range {
        font-size: 12px;
        color: #666;
        background: #f8f9fa;
        padding: 4px 8px;
        border-radius: 4px;
    }
    
    /* Tablo container - yatay kaydırma */
    .table-container {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    .analytics-table {
        min-width: 500px;
        font-size: 10px;
        width: 100%;
        table-layout: auto;
        border-collapse: collapse;
    }
    
    .analytics-table th,
    .analytics-table td {
        padding: 6px 4px;
        font-size: 10px;
        text-align: left;
        vertical-align: top;
    }
    
    /* Tablo sütun genişlikleri - daha kompakt */
    .analytics-table th:nth-child(1),
    .analytics-table td:nth-child(1) {
        min-width: 70px;
    }
    
    .analytics-table th:nth-child(2),
    .analytics-table td:nth-child(2) {
        min-width: 100px;
    }
    
    .analytics-table th:nth-child(3),
    .analytics-table td:nth-child(3) {
        min-width: 90px;
    }
    
    .analytics-table th:nth-child(4),
    .analytics-table td:nth-child(4) {
        min-width: 60px;
    }
    
    .analytics-table th:nth-child(5),
    .analytics-table td:nth-child(5) {
        min-width: 60px;
    }
    
    .analytics-table th:nth-child(6),
    .analytics-table td:nth-child(6) {
        min-width: 70px;
    }
    
    /* Müşteri ve etkinlik bilgileri */
    .customer-info,
    .event-info {
        display: block;
        width: 100%;
    }
    
    .customer-info strong,
    .event-info strong {
        font-size: 10px;
        display: block;
        margin-bottom: 2px;
        font-weight: 600;
    }
    
    .customer-info small,
    .event-info small {
        font-size: 9px;
        color: #666;
        display: block;
    }
    
    .date-info {
        font-size: 9px;
    }
    
    .date-info strong {
        font-size: 9px;
        display: block;
    }
    
    .date-info small {
        font-size: 8px;
        color: #666;
    }
    
    /* Badge'ler */
    .ticket-type {
        font-size: 8px;
        padding: 2px 4px;
        border-radius: 4px;
        display: inline-block;
    }
    
    .status-badge {
        font-size: 8px;
        padding: 2px 4px;
        border-radius: 4px;
        display: inline-block;
    }
    
    code {
        font-size: 8px;
        padding: 2px 4px;
        border-radius: 3px;
        display: inline-block;
        word-break: break-all;
    }
    
    /* Chart container */
    .chart-container {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    .monthly-chart {
        padding: 10px;
        min-height: 150px;
        gap: 4px;
        min-width: 400px;
        display: flex;
        align-items: end;
    }
    
    .month-label strong {
        font-size: 9px;
    }
    
    .month-label small {
        font-size: 7px;
    }
    
    /* Event items */
    .event-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
        padding: 12px;
        width: 100%;
        box-sizing: border-box;
    }
    
    .event-date,
    .event-status {
        align-self: flex-start;
        width: 100%;
    }
    
    /* Empty state */
    .empty-state {
        padding: 30px 15px;
        text-align: center;
        width: 100%;
        box-sizing: border-box;
    }
    
    .empty-state i {
        font-size: 36px !important;
    }
    
    .empty-state p {
        font-size: 14px;
    }
    
    .empty-state small {
        font-size: 12px;
    }
    
    /* Responsive table wrapper */
    .table-responsive {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        box-sizing: border-box;
    }
    
    /* Distribution chart */
    .distribution-chart {
        padding: 15px;
        width: 100%;
        box-sizing: border-box;
    }
    
    .distribution-item {
        margin-bottom: 12px;
        width: 100%;
    }
    
    .distribution-bar {
        width: 100%;
        height: 6px;
        margin-bottom: 6px;
    }
}
</style>