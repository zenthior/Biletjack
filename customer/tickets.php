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

<div class="dashboard-container">
    <div class="content-header">
        <h1>Biletlerim</h1>
        <p>Satın aldığınız tüm biletler</p>
    </div>

    <div class="tickets-container">
        <?php if (empty($tickets)): ?>
            <div class="empty-state">
                <i class="fas fa-ticket-alt"></i>
                <h3>Henüz biletiniz yok</h3>
                <p>Etkinliklere göz atın ve ilk biletinizi satın alın!</p>
                <a href="../etkinlikler.php" class="btn btn-primary">Etkinliklere Göz At</a>
            </div>
        <?php else: ?>
            <div class="tickets-grid">
                <?php foreach ($tickets as $ticket): ?>
                    <div class="ticket-card <?php echo $ticket['status']; ?>">
                        <div class="ticket-header">
                            <h3><?php echo htmlspecialchars($ticket['event_title']); ?></h3>
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
                            <div class="detail-row">
                                <i class="fas fa-calendar"></i>
                                <span><?php echo date('d.m.Y H:i', strtotime($ticket['event_date'])); ?></span>
                            </div>
                            <div class="detail-row">
                                <i class="fas fa-clock"></i>
                                <span><?php echo date('H:i', strtotime($ticket['event_time'])); ?></span>
                            </div>
                            <div class="detail-row">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?php echo htmlspecialchars($ticket['location']); ?></span>
                            </div>
                            <div class="detail-row">
                                <i class="fas fa-barcode"></i>
                                <span>Bilet No: <?php echo $ticket['ticket_code']; ?></span>
                            </div>
                        </div>
                        
                        <div class="ticket-footer">
                            <div class="ticket-price">
                                <?php echo number_format($ticket['total_amount'], 2); ?> ₺
                            </div>
                            <?php if ($ticket['status'] === 'active'): ?>
                                <button class="btn btn-sm btn-primary" onclick="showTicketQR('<?php echo $ticket['ticket_code']; ?>')">
                                    QR Kodu Göster
                                </button>
                            <?php endif; ?>
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