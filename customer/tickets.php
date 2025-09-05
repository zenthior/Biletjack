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
           o.total_amount, o.created_at as order_date, t.quantity,
           s.row_number, s.seat_number, t.seat_labels
    FROM tickets t 
    JOIN events e ON t.event_id = e.id 
    JOIN orders o ON t.order_id = o.id 
    LEFT JOIN seats s ON s.id = t.seat_id
    WHERE o.user_id = ? AND o.payment_status = 'paid' AND (t.status IS NULL OR t.status <> 'pending')
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
                                <span class="ticket-code">#<?php echo $ticket['ticket_number']; ?></span>
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
                                <?php 
                                    $hasSeatLabels = !empty($ticket['seat_labels']);
                                    $hasSingleSeat = !empty($ticket['row_number']) && !empty($ticket['seat_number']);
                                    $isSeatedTicket = $hasSeatLabels || $hasSingleSeat;
                                ?>
                                <?php if (!$isSeatedTicket): ?>
                                <div class="detail-item">
                                    <i class="fas fa-users"></i>
                                    <div class="detail-content">
                                        <span class="detail-label">Adet</span>
                                        <span class="detail-value"><?php echo $ticket['quantity'] ?? 1; ?> Kişi</span>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <?php if ($hasSeatLabels || $hasSingleSeat): ?>
                                <div class="detail-item">
                                    <i class="fas fa-chair"></i>
                                    <div class="detail-content">
                                        <span class="detail-label">Koltuk</span>
                                        <span class="detail-value">
                                            <?php 
                                                if (!empty($ticket['seat_labels'])) {
                                                    echo htmlspecialchars(str_replace(', ', ' ', $ticket['seat_labels']));
                                                } elseif (!empty($ticket['row_number']) && !empty($ticket['seat_number'])) {
                                                    $rowLabel = chr(64 + (int)$ticket['row_number']); 
                                                    echo htmlspecialchars($rowLabel . $ticket['seat_number']);
                                                }
                                            ?>
                                        </span>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="ticket-footer">
                            <div class="ticket-price">
                                <span class="price-label">Tutar</span>
                                <span class="price-value"><?php echo number_format($ticket['total_amount'], 2); ?> ₺</span>
                            </div>
                            <div class="ticket-actions">
                                <?php if ($ticket['status'] === 'active'): ?>
                                    <button class="btn btn-primary" onclick="showTicketQR('<?php echo $ticket['ticket_number']; ?>', '<?php echo $ticket['qr_code_path']; ?>')">
                                        <i class="fas fa-qrcode"></i>
                                        QR Kodu
                                    </button>
                                    <button class="btn btn-secondary" onclick="showTransferModal('<?php echo $ticket['id']; ?>', '<?php echo htmlspecialchars($ticket['event_title']); ?>', '<?php echo $ticket['ticket_number']; ?>')">
                                        <i class="fas fa-exchange-alt"></i>
                                        Bileti Aktar
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
        <div id="ticketCodeDisplay" style="margin: 15px 0; padding: 10px; background: #f5f5f5; border-radius: 5px; text-align: center;">
            <strong>Bilet Kodu: <span id="ticketCodeText"></span></strong>
        </div>
        <p>Bu QR kodu etkinlik girişinde gösterin.</p>
    </div>
</div>

<!-- Bilet Aktarma Modal -->
<div id="transferModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeTransferModal()">&times;</span>
        <h2>Bilet Aktarma</h2>
        <div id="transferTicketInfo"></div>
        <form id="transferForm">
            <div class="form-group">
                <label for="targetUserId">Aktarılacak Kullanıcı ID:</label>
                <input type="number" id="targetUserId" name="targetUserId" required placeholder="Örnek: 123">
                <small>Bileti aktarmak istediğiniz kullanıcının ID numarasını girin</small>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeTransferModal()">İptal</button>
                <button type="submit" class="btn btn-primary">Bileti Aktar</button>
            </div>
        </form>
        <div id="transferResult"></div>
    </div>
</div>

<script>
let currentTicketId = null;

function showTicketQR(ticketCode, qrCodePath) {
    document.getElementById('qrModal').style.display = 'block';
    
    // Bilet kodunu göster
    document.getElementById('ticketCodeText').textContent = ticketCode;

    // QR dosya yolunu çözümle (Endroid QR Code ile üretilmiş SVG dosyası)
    function resolveQrPath(path) {
        if (!path) return '';
        let p = String(path);
        // Eğer zaten uploads/ ile başlıyorsa, customer altından erişim için ../ ekle
        if (p.startsWith('uploads/')) {
            return '../' + p;
        }
        // Tam/nispi bir yol verilmişse sadece dosya adını al
        const fileName = p.split('/').pop();
        return '../uploads/qr_codes/' + fileName;
    }
    
    const finalSrc = resolveQrPath(qrCodePath);
    let qrContent = '';
    if (finalSrc) {
        // QR kod dosyası varsa göster
        qrContent = '<img src="' + finalSrc + '" alt="QR Kod" class="qr-img" />';
    } else {
        // QR kod dosyası yoksa metin göster
        qrContent = '<div class="qr-placeholder">QR Kodu Yükleniyor...</div>';
    }
    
    document.getElementById('qrCode').innerHTML = qrContent;
}

function showTransferModal(ticketId, eventTitle, ticketNumber) {
    currentTicketId = ticketId;
    document.getElementById('transferModal').style.display = 'block';
    
    // Bilet bilgilerini göster
    document.getElementById('transferTicketInfo').innerHTML = `
        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <h4>${eventTitle}</h4>
            <p><strong>Bilet Numarası:</strong> #${ticketNumber}</p>
        </div>
    `;
    
    // Formu temizle
    document.getElementById('targetUserId').value = '';
    document.getElementById('transferResult').innerHTML = '';
}

function closeTransferModal() {
    document.getElementById('transferModal').style.display = 'none';
    currentTicketId = null;
}

// Form gönderme
document.getElementById('transferForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const targetUserId = document.getElementById('targetUserId').value;
    const resultDiv = document.getElementById('transferResult');
    
    if (!currentTicketId || !targetUserId) {
        resultDiv.innerHTML = '<div class="alert alert-danger">Lütfen geçerli bir kullanıcı ID girin.</div>';
        return;
    }
    
    // Loading göster
    resultDiv.innerHTML = '<div class="alert alert-info">Bilet aktarılıyor...</div>';
    
    // AJAX ile bilet aktarma
    fetch('../ajax/transfer_ticket.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            ticket_id: currentTicketId,
            target_user_id: targetUserId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            resultDiv.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
            setTimeout(() => {
                closeTransferModal();
                location.reload(); // Sayfayı yenile
            }, 2000);
        } else {
            resultDiv.innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
        }
    })
    .catch(error => {
        resultDiv.innerHTML = '<div class="alert alert-danger">Bir hata oluştu. Lütfen tekrar deneyin.</div>';
    });
});



// Modal kapatma
document.querySelector('#qrModal .close').onclick = function() {
    document.getElementById('qrModal').style.display = 'none';
}

window.onclick = function(event) {
    if (event.target == document.getElementById('qrModal')) {
        document.getElementById('qrModal').style.display = 'none';
    }
    if (event.target == document.getElementById('transferModal')) {
        closeTransferModal();
    }
}
</script>

<style>
  .qr-img {
    max-width: 200px;
    max-height: 200px;
    margin-left: 115px;
  }

  /* Mobil ekranlar için (örnek: 600px ve altı) */
  @media (max-width: 600px) {
    .qr-img {
      margin-left: 65px;
    }
  }
</style>


<?php include 'includes/footer.php'; ?>