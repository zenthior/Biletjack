<?php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../classes/User.php';

// Müşteri kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    header('Location: ../index.php');
    exit();
}

// Biletleri getir
$stmt = $pdo->prepare("
    SELECT t.*, e.title as event_title, e.event_date, e.venue_name as location, 
           o.total_amount, o.created_at as order_date
    FROM tickets t 
    JOIN events e ON t.event_id = e.id 
    JOIN orders o ON t.order_id = o.id 
    WHERE o.user_id = ? 
    ORDER BY o.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$tickets = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="modern-dashboard">
    <div class="dashboard-header">
        <div class="welcome-section">
            <h1 class="page-title">Biletlerim</h1>
            <p class="page-subtitle">Satın aldığınız tüm biletler ve etkinlik detayları</p>
        </div>
    </div>

    <div class="tickets-container">
        <?php if (empty($tickets)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <h3>Henüz biletiniz yok</h3>
                <p>Etkinliklere göz atın ve ilk biletinizi satın alın!</p>
                <a href="../etkinlikler.php" class="btn btn-primary">Etkinliklere Göz At</a>
            </div>
        <?php else: ?>
            <div class="tickets-grid">
                <?php foreach ($tickets as $ticket): ?>
                    <div class="modern-ticket-card <?php echo $ticket['status']; ?>">
                        <div class="ticket-header">
                            <div class="ticket-title">
                                <h3><?php echo htmlspecialchars($ticket['event_title']); ?></h3>
                                <span class="ticket-code">#<?php echo $ticket['ticket_code']; ?></span>
                            </div>
                            <span class="ticket-status status-<?php echo $ticket['status']; ?>">
                                <?php 
                                switch($ticket['status']) {
                                    case 'active': echo 'Aktif'; break;
                                    case 'used': echo 'Kullanıldı'; break;
                                    case 'cancelled': echo 'İptal Edildi'; break;
                                    default: echo ucfirst($ticket['status']);
                                }
                                ?>
                            </span>
                        </div>
                        
                        <div class="ticket-details">
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <i class="fas fa-calendar"></i>
                                    <div class="detail-content">
                                        <span class="detail-label">Tarih</span>
                                        <span class="detail-value"><?php echo date('d.m.Y', strtotime($ticket['event_date'])); ?></span>
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-clock"></i>
                                    <div class="detail-content">
                                        <span class="detail-label">Saat</span>
                                        <span class="detail-value"><?php echo date('H:i', strtotime($ticket['event_date'])); ?></span>
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <div class="detail-content">
                                        <span class="detail-label">Mekan</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($ticket['location']); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="ticket-footer">
                            <div class="ticket-price">
                                <span class="price-label">Tutar</span>
                                <span class="price-value"><?php echo number_format($ticket['total_amount'], 2); ?> ₺</span>
                            </div>
                            <div class="ticket-actions">
                                <?php if ($ticket['status'] === 'active'): ?>
                                    <button class="btn btn-primary" onclick="showTicketQR('<?php echo $ticket['ticket_code']; ?>')">
                                        <i class="fas fa-qrcode"></i>
                                        QR Kodu
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- QR Kod Modal -->
<div id="qrModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Bilet QR Kodu</h2>
        <div id="qrCode"></div>
        <p>Bu QR kodu etkinlik girişinde gösterin.</p>
    </div>
</div>

<script>
function showTicketQR(ticketCode) {
    document.getElementById('qrModal').style.display = 'block';
    // QR kod oluşturma kodu buraya eklenecek
    document.getElementById('qrCode').innerHTML = '<div class="qr-placeholder">QR: ' + ticketCode + '</div>';
}

// Modal kapatma
document.querySelector('.close').onclick = function() {
    document.getElementById('qrModal').style.display = 'none';
}

window.onclick = function(event) {
    if (event.target == document.getElementById('qrModal')) {
        document.getElementById('qrModal').style.display = 'none';
    }
}
</script>

<?php include 'includes/footer.php'; ?>