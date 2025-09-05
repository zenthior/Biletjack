<?php
require_once '../includes/session.php';
require_once '../config/database.php';

// Admin kontrolü
requireAdmin();

// Database bağlantısını oluştur
$database = new Database();
$pdo = $database->getConnection();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo '<div class="error">Geçersiz sipariş ID\'si.</div>';
    exit;
}

$orderId = (int)$_GET['id'];

// Sipariş detaylarını getir
$orderQuery = "SELECT o.*, u.first_name, u.last_name, u.email, u.phone
               FROM orders o 
               JOIN users u ON o.user_id = u.id 
               WHERE o.id = :id";

$stmt = $pdo->prepare($orderQuery);
$stmt->bindParam(':id', $orderId);
$stmt->execute();
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo '<div class="error">Sipariş bulunamadı.</div>';
    exit;
}

// Biletleri getir
$ticketsQuery = "SELECT t.*, e.title as event_title, e.event_date, e.venue_name as event_location
                 FROM tickets t
                 LEFT JOIN events e ON t.event_id = e.id
                 WHERE t.order_id = :order_id
                 ORDER BY t.created_at";

$ticketsStmt = $pdo->prepare($ticketsQuery);
$ticketsStmt->bindParam(':order_id', $orderId);
$ticketsStmt->execute();
$tickets = $ticketsStmt->fetchAll(PDO::FETCH_ASSOC);

// Durum etiketleri
$statusLabels = [
    'paid' => 'Ödendi',
    'pending' => 'Beklemede',
    'failed' => 'Başarısız',
    'refunded' => 'İade Edildi'
];

$statusColors = [
    'paid' => '#28a745',
    'pending' => '#ffc107',
    'failed' => '#dc3545',
    'refunded' => '#6c757d'
];
?>

<div class="order-details">
    <!-- Sipariş Bilgileri -->
    <div class="detail-section">
        <h4><i class="fas fa-shopping-cart"></i> Sipariş Bilgileri</h4>
        <div class="detail-grid">
            <div class="detail-item">
                <label>Sipariş Numarası:</label>
                <span class="order-number"><?php echo htmlspecialchars($order['order_number']); ?></span>
            </div>
            <div class="detail-item">
                <label>Toplam Tutar:</label>
                <span class="amount"><?php echo number_format($order['total_amount'], 2); ?> ₺</span>
            </div>
            <div class="detail-item">
                <label>Ödeme Durumu:</label>
                <span class="status-badge" style="background-color: <?php echo $statusColors[$order['payment_status']]; ?>">
                    <?php echo $statusLabels[$order['payment_status']] ?? $order['payment_status']; ?>
                </span>
            </div>
            <div class="detail-item">
                <label>Ödeme Yöntemi:</label>
                <span><?php echo htmlspecialchars($order['payment_method'] ?? 'Belirtilmemiş'); ?></span>
            </div>
            <div class="detail-item">
                <label>Sipariş Tarihi:</label>
                <span><?php echo date('d.m.Y H:i:s', strtotime($order['created_at'])); ?></span>
            </div>
            <?php if ($order['updated_at'] && $order['updated_at'] !== $order['created_at']): ?>
            <div class="detail-item">
                <label>Son Güncelleme:</label>
                <span><?php echo date('d.m.Y H:i:s', strtotime($order['updated_at'])); ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Müşteri Bilgileri -->
    <div class="detail-section">
        <h4><i class="fas fa-user"></i> Müşteri Bilgileri</h4>
        <div class="detail-grid">
            <div class="detail-item">
                <label>Ad Soyad:</label>
                <span><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></span>
            </div>
            <div class="detail-item">
                <label>E-posta:</label>
                <span><?php echo htmlspecialchars($order['email']); ?></span>
            </div>
            <?php if ($order['phone']): ?>
            <div class="detail-item">
                <label>Telefon:</label>
                <span><?php echo htmlspecialchars($order['phone']); ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bilet Bilgileri -->
    <div class="detail-section">
        <h4><i class="fas fa-ticket-alt"></i> Bilet Bilgileri (<?php echo count($tickets); ?> adet)</h4>
        <?php if (empty($tickets)): ?>
            <p class="no-tickets">Bu siparişe ait bilet bulunmuyor.</p>
        <?php else: ?>
            <div class="tickets-list">
                <?php foreach ($tickets as $index => $ticket): ?>
                    <div class="ticket-item">
                        <div class="ticket-header">
                            <h5>Bilet #<?php echo $index + 1; ?></h5>
                            <span class="ticket-price"><?php echo number_format($ticket['price'], 2); ?> ₺</span>
                        </div>
                        <div class="ticket-details">
                            <div class="ticket-info">
                                <label>Bilet Sahibi:</label>
                                <span><?php echo htmlspecialchars($ticket['holder_name']); ?></span>
                            </div>
                            <?php if ($ticket['event_title']): ?>
                            <div class="ticket-info">
                                <label>Etkinlik:</label>
                                <span><?php echo htmlspecialchars($ticket['event_title']); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($ticket['event_date']): ?>
                            <div class="ticket-info">
                                <label>Etkinlik Tarihi:</label>
                                <span><?php echo date('d.m.Y H:i', strtotime($ticket['event_date'])); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($ticket['event_location']): ?>
                            <div class="ticket-info">
                                <label>Konum:</label>
                                <span><?php echo htmlspecialchars($ticket['event_location']); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($ticket['seat_number']): ?>
                            <div class="ticket-info">
                                <label>Koltuk:</label>
                                <span><?php echo htmlspecialchars($ticket['seat_number']); ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="ticket-info">
                                <label>Bilet Durumu:</label>
                                <span class="ticket-status status-<?php echo $ticket['status']; ?>">
                                    <?php 
                                    $ticketStatusLabels = [
                                        'active' => 'Aktif',
                                        'used' => 'Kullanıldı',
                                        'cancelled' => 'İptal Edildi',
                                        'refunded' => 'İade Edildi'
                                    ];
                                    echo $ticketStatusLabels[$ticket['status']] ?? $ticket['status'];
                                    ?>
                                </span>
                            </div>
                            <?php if ($ticket['qr_code']): ?>
                            <div class="ticket-info">
                                <label>QR Kod:</label>
                                <span class="qr-code"><?php echo htmlspecialchars($ticket['qr_code']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- İşlem Geçmişi -->
    <?php if ($order['payment_status'] === 'refunded' || $order['updated_at'] !== $order['created_at']): ?>
    <div class="detail-section">
        <h4><i class="fas fa-history"></i> İşlem Geçmişi</h4>
        <div class="timeline">
            <div class="timeline-item">
                <div class="timeline-marker"></div>
                <div class="timeline-content">
                    <h6>Sipariş Oluşturuldu</h6>
                    <p><?php echo date('d.m.Y H:i:s', strtotime($order['created_at'])); ?></p>
                </div>
            </div>
            <?php if ($order['updated_at'] && $order['updated_at'] !== $order['created_at']): ?>
            <div class="timeline-item">
                <div class="timeline-marker"></div>
                <div class="timeline-content">
                    <h6>Durum Güncellendi</h6>
                    <p><?php echo date('d.m.Y H:i:s', strtotime($order['updated_at'])); ?></p>
                    <small>Durum: <?php echo $statusLabels[$order['payment_status']]; ?></small>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.order-details {
    max-height: 70vh;
    overflow-y: auto;
    padding: 20px;
}

.detail-section {
    margin-bottom: 30px;
    border-bottom: 1px solid #eee;
    padding-bottom: 20px;
}

.detail-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.detail-section h4 {
    color: #333;
    margin-bottom: 15px;
    font-size: 16px;
    font-weight: 600;
}

.detail-section h4 i {
    margin-right: 8px;
    color: #007bff;
}

.detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.detail-item label {
    font-weight: 600;
    color: #666;
    font-size: 14px;
}

.detail-item span {
    color: #333;
    font-size: 14px;
}

.order-number {
    font-family: 'Courier New', monospace;
    background: #f8f9fa;
    padding: 4px 8px;
    border-radius: 4px;
    font-weight: 600;
}

.amount {
    font-weight: 700;
    color: #28a745;
    font-size: 16px;
}

.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    color: white;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.tickets-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.ticket-item {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    background: #f8f9fa;
}

.ticket-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    padding-bottom: 10px;
    border-bottom: 1px solid #ddd;
}

.ticket-header h5 {
    margin: 0;
    color: #333;
    font-size: 14px;
}

.ticket-price {
    font-weight: 700;
    color: #28a745;
    font-size: 16px;
}

.ticket-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 10px;
}

.ticket-info {
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.ticket-info label {
    font-weight: 600;
    color: #666;
    font-size: 12px;
}

.ticket-info span {
    color: #333;
    font-size: 13px;
}

.ticket-status {
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.ticket-status.status-active {
    background: #d4edda;
    color: #155724;
}

.ticket-status.status-used {
    background: #cce5ff;
    color: #004085;
}

.ticket-status.status-cancelled {
    background: #f8d7da;
    color: #721c24;
}

.ticket-status.status-refunded {
    background: #e2e3e5;
    color: #383d41;
}

.qr-code {
    font-family: 'Courier New', monospace;
    background: #fff;
    padding: 4px 8px;
    border-radius: 4px;
    border: 1px solid #ddd;
    font-size: 12px;
}

.no-tickets {
    text-align: center;
    color: #666;
    font-style: italic;
    padding: 20px;
}

.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #007bff;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -23px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #007bff;
    border: 3px solid #fff;
    box-shadow: 0 0 0 2px #007bff;
}

.timeline-content h6 {
    margin: 0 0 5px 0;
    color: #333;
    font-size: 14px;
    font-weight: 600;
}

.timeline-content p {
    margin: 0;
    color: #666;
    font-size: 13px;
}

.timeline-content small {
    color: #999;
    font-size: 12px;
}
</style>