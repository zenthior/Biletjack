<?php 
require_once 'includes/session.php';
require_once 'config/database.php';
// require_once 'classes/Ticket.php'; // Bu sayfada QR üretimi yapılmıyor, gereksiz
// require_once 'classes/SimpleTicketEmailSender.php'; // İlk renderda yüklemeyi ertele

// Giriş kontrolü
if (!isLoggedIn()) {
    header('Location: index.php');
    exit();
}

// Sipariş bilgilerini al
$orderData = null;
if (isset($_SESSION['last_order'])) {
    $orderData = $_SESSION['last_order'];
} else {
    header('Location: index.php');
    exit();
}

// AJAX endpoint: E-postayı asenkron tetikle
if (isset($_GET['send_email'])) {
    header('Content-Type: application/json');

    if (!$orderData) {
        echo json_encode(['success' => false, 'message' => 'Sipariş bilgisi bulunamadı.']);
        exit;
    }

    // Zaten başarılı gönderildiyse tekrar göndermeyelim
    if (isset($_SESSION['last_order_email_sent']) && $_SESSION['last_order_email_sent'] === ($orderData['orderNumber'] ?? null)) {
        echo json_encode(['success' => true, 'message' => 'E-posta daha önce gönderildi.']);
        exit;
    }

    // Aynı anda birden fazla tetiklemeyi engelle (in-progress flag)
    if (isset($_SESSION['last_order_email_sending']) && $_SESSION['last_order_email_sending'] === ($orderData['orderNumber'] ?? null)) {
        echo json_encode(['success' => true, 'message' => 'E-posta gönderimi devam ediyor.']);
        exit;
    }

    try {
        // Gönderim devam ediyor bayrağını set et ve oturumu serbest bırak
        $_SESSION['last_order_email_sending'] = $orderData['orderNumber'];
        session_write_close();

        // Ağır sınıfı burada yükle
        require_once 'classes/SimpleTicketEmailSender.php';
        
        $database = new Database();
        $pdoMail = $database->getConnection();
        $simpleSender = new SimpleTicketEmailSender($pdoMail);
        $emailResult = $simpleSender->sendSimpleTicketEmail($orderData);

        // E-posta gönderimi sonrası oturumu tekrar açıp bayrakları güncelle
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        // Gönderim devam ediyor bayrağını kaldır
        unset($_SESSION['last_order_email_sending']);

        if (!empty($emailResult['success'])) {
            // Başarılı gönderim bayrağını set et
            $_SESSION['last_order_email_sent'] = $orderData['orderNumber'];
            session_write_close();
            echo json_encode(['success' => true, 'message' => 'E-posta gönderildi.']);
        } else {
            session_write_close();
            echo json_encode(['success' => false, 'message' => ($emailResult['message'] ?? 'E-posta gönderilemedi')]);
        }
    } catch (Throwable $e) {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        unset($_SESSION['last_order_email_sending']);
        session_write_close();
        echo json_encode(['success' => false, 'message' => 'E-posta hatası: ' . $e->getMessage()]);
    }
    exit;
}

include 'includes/header.php'; 
?>
<link rel="stylesheet" href="css/customer.css">

<main class="success-page">
    <div class="success-container">
        <!-- Sol Panel - Başarı Mesajı ve Animasyon -->
        <div class="success-left-panel">
            <div class="success-animation">
                <div class="success-circle">
                    <img src="SVG/tamamlandı.svg" alt="Başarılı" class="success-icon">
                </div>
                <div class="success-waves">
                    <div class="wave wave1"></div>
                    <div class="wave wave2"></div>
                    <div class="wave wave3"></div>
                </div>
            </div>
            
            <h1 class="success-title">Ödeme Başarılı!</h1>
            <p class="success-subtitle">Biletleriniz başarıyla satın alındı</p>
            
            
            <div class="action-buttons">
                <a href="index.php" class="btn-primary">
                    <span class="btn-icon"></span>
                    Ana Sayfaya Dön
                </a>
                <a href="customer/tickets.php" class="btn-secondary">
                    <span class="btn-icon"></span>
                    Biletlerim
                </a>
                <a href="etkinlikler.php" class="btn-secondary">
                    <span class="btn-icon"></span>
                    Diğer Etkinlikler
                </a>
            </div>
        </div>
        
        <!-- Sağ Panel - Sipariş Detayları -->
        <div class="success-right-panel">
            <div class="order-card">
                <div class="order-header">
                    <h2>Sipariş Detayları</h2>
                    <div class="order-status">
                        <span class="status-badge">Onaylandı</span>
                    </div>
                </div>
                
                <div class="order-info-grid">
                    <div class="info-item">
                        <span class="info-label">Sipariş No</span>
                        <span id="orderNumber" class="info-value"><?php echo htmlspecialchars($orderData['orderNumber'] ?? '-'); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Tarih</span>
                        <span id="orderDate" class="info-value"><?php echo date('d.m.Y H:i', strtotime($orderData['date'] ?? 'now')); ?></span>
                    </div>
                </div>
                
                <div class="order-items-section">
                    <h3>Satın Alınan Biletler</h3>
                    <div id="orderItems" class="order-items">
                        <?php if ($orderData && isset($orderData['tickets'])): ?>
                            <?php foreach ($orderData['tickets'] as $ticket): ?>
                                <div class="ticket-item">
                                    <div class="ticket-info">
                                        <h4><?php echo htmlspecialchars($ticket['ticket_type']); ?></h4>
                                        <p class="ticket-code">Bilet Kodu: <?php echo htmlspecialchars($ticket['ticket_code']); ?></p>
                                        <p class="ticket-price">₺<?php echo number_format($ticket['price'], 2); ?></p>
                                    </div>
                                    <div class="ticket-qr">
                                        <?php 
                                        // QR kod kontrolü - Endroid QR Code ile oluşturulmuş SVG dosyası
                                        if (isset($ticket['qr_code_path']) && !empty($ticket['qr_code_path'])): 
                                            $qrFilePath = $ticket['qr_code_path'];
                                            // uploads/ ile başlamıyorsa ekle (PHP 7 uyumluluğu için substr kullan)
                                            if (substr($qrFilePath, 0, 8) !== 'uploads/') {
                                                $qrFilePath = 'uploads/qr_codes/' . basename($qrFilePath);
                                            }
                                            
                                            if (file_exists($qrFilePath)): ?>
                                                <div class="qr-code-container">
                                                    <object data="<?php echo htmlspecialchars($qrFilePath); ?>" type="image/svg+xml" class="qr-code-image" title="Endroid QR Code v6.0 ile oluşturuldu">
                                                        <img src="<?php echo htmlspecialchars($qrFilePath); ?>" alt="QR Kod" class="qr-code-image" title="Endroid QR Code v6.0 ile oluşturuldu">
                                                    </object>
                                                    <p class="qr-info">Bilet QR Kodu</p>
                                                </div>
                                            <?php else: ?>
                                                <div class="qr-placeholder">
                                                    <div class="qr-loading">⚠️</div>
                                                    <p>QR Kod dosyası bulunamadı</p>
                                                    <small>Dosya: <?php echo htmlspecialchars($qrFilePath); ?></small>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div class="qr-placeholder">
                                                <div class="qr-loading">🔄</div>
                                                <p>QR Kod Oluşturuluyor...</p>
                                                <small>Endroid QR Code v6.0</small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="order-total">
                    <?php if (!empty($orderData['discount'])): ?>
                    <div class="total-row">
                        <span class="total-label">Ara Toplam</span>
                        <span class="total-amount">₺<?php echo number_format($orderData['subtotal'] ?? 0, 2); ?></span>
                    </div>
                    <div class="total-row">
                        <span class="total-label">İndirim</span>
                        <span class="total-amount">-₺<?php echo number_format($orderData['discount'] ?? 0, 2); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="total-row">
                        <span class="total-label"><?php echo !empty($orderData['discount']) ? 'Ödenen Tutar' : 'Toplam Tutar'; ?></span>
                        <span id="totalAmount" class="total-amount">₺<?php echo number_format($orderData['total'] ?? 0, 2); ?></span>
                    </div>
                </div>
                
                <div class="customer-section">
                    <h3>Müşteri Bilgileri</h3>
                    <div id="customerDetails" class="customer-grid">
                        <?php if ($orderData && isset($orderData['customerInfo'])): ?>
                            <div class="customer-info">
                                <p><strong>Ad Soyad:</strong> <?php echo htmlspecialchars($orderData['customerInfo']['firstName'] . ' ' . $orderData['customerInfo']['lastName']); ?></p>
                                <p><strong>E-posta:</strong> <?php echo htmlspecialchars($orderData['customerInfo']['email']); ?></p>
                                <p><strong>Telefon:</strong> <?php echo htmlspecialchars($orderData['customerInfo']['phone']); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="download-section">
                    <button onclick="downloadTickets()" class="btn-download">
                        <span class="btn-icon">📄</span>
                        Biletleri PDF Olarak İndir
                    </button>
                </div>
            </div>
            
            <!-- Sonraki Adımlar -->
            
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Ödeme sonrası e-postayı bir kez tetikle
    try { localStorage.removeItem('cart'); } catch (e) {}
    fetch('odeme-basarili.php?send_email=1', { credentials: 'same-origin', keepalive: true, cache: 'no-store' })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            console.log('Bilet e-postası sonucu:', data);
            // İsterseniz burada kullanıcıya bildirim gösterebilirsiniz
        })
        .catch(function (err) {
            console.error('Bilet e-postası gönderilemedi:', err);
        });
});
</script>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

.success-page {
    min-height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.success-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    min-height: 100vh;
    max-width: 100%;
    margin: 0;
}

/* Sol Panel */
.success-left-panel {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding: 3rem;
    color: white;
    position: relative;
    overflow: hidden;
}

.success-animation {
    position: relative;
    margin-bottom: 3rem;
}

.success-circle {
    width: 120px;
    height: 120px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255, 255, 255, 0.3);
    animation: pulse 2s infinite;
    position: relative;
    z-index: 2;
}

.success-icon {
    width: 60px;
    height: 60px;
    filter: brightness(0) invert(1);
}

.success-waves {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

.wave {
    position: absolute;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    animation: wave-animation 3s infinite;
}

.wave1 {
    width: 140px;
    height: 140px;
    animation-delay: 0s;
}

.wave2 {
    width: 180px;
    height: 180px;
    animation-delay: 1s;
}

.wave3 {
    width: 220px;
    height: 220px;
    animation-delay: 2s;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

@keyframes wave-animation {
    0% {
        transform: translate(-50%, -50%) scale(0.5);
        opacity: 1;
    }
    100% {
        transform: translate(-50%, -50%) scale(1.2);
        opacity: 0;
    }
}

.success-title {
    font-size: 3rem;
    font-weight: 700;
    margin-bottom: 1rem;
    text-align: center;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.success-subtitle {
    font-size: 1.2rem;
    margin-bottom: 3rem;
    text-align: center;
    opacity: 0.9;
}

.success-features {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-bottom: 3rem;
}

.feature-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    font-size: 1.1rem;
    padding: 0.75rem 1.5rem;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50px;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.feature-icon {
    font-size: 1.5rem;
}

.action-buttons {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    justify-content: center;
}

.btn-primary,
.btn-secondary {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem 2rem;
    border-radius: 50px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s;
    border: 2px solid transparent;
}

.btn-primary {
    background: white;
    color: #667eea;
}

.btn-primary:hover {
    background: rgba(255, 255, 255, 0.9);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    color: #667eea;
}

.btn-secondary {
    background: transparent;
    color: white;
    border-color: rgba(255, 255, 255, 0.5);
}

.btn-secondary:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: white;
    transform: translateY(-2px);
    color: white;
}

/* Sağ Panel */
.success-right-panel {
    background: #f8f9fa;
    padding: 2rem;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.order-card,
.next-steps-card {
    background: white;
    border-radius: 20px;
    padding: 2rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    border: 1px solid #e9ecef;
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #f1f3f4;
}

.order-header h2 {
    color: #1a1a1a;
    font-size: 1.5rem;
    font-weight: 600;
}

.status-badge {
    background: linear-gradient(135deg, #00C896, #00b085);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 600;
}

.order-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.info-item {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 12px;
    border-left: 4px solid #667eea;
}

.info-label {
    display: block;
    font-size: 0.9rem;
    color: #666;
    margin-bottom: 0.5rem;
}

.info-value {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1a1a1a;
}

.order-items-section h3,
.customer-section h3,
.next-steps-card h3 {
    color: #1a1a1a;
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

.order-items {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-bottom: 2rem;
}

.order-item {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 1.5rem;
    border-left: 4px solid #00C896;
}

.ticket-item {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 1.5rem;
    border-left: 4px solid #00C896;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
}

.ticket-info {
    flex: 1;
}

.ticket-info h4 {
    margin: 0 0 0.5rem 0;
    color: #1a1a1a;
    font-size: 1.1rem;
    font-weight: 600;
}

.ticket-code {
    margin: 0.25rem 0;
    color: #666;
    font-size: 0.9rem;
    font-family: monospace;
    background: #e9ecef;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    display: inline-block;
}

.ticket-price {
    margin: 0.5rem 0 0 0;
    color: #00C896;
    font-weight: 700;
    font-size: 1.2rem;
}

.ticket-qr {
    flex-shrink: 0;
}

.qr-code-image {
    width: 100px;
    height: 100px;
    border: 2px solid #ddd;
    border-radius: 8px;
    background: white;
}

.qr-placeholder {
    width: 100px;
    height: 100px;
    border: 2px dashed #ddd;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    color: #666;
    font-size: 0.8rem;
    text-align: center;
}

.item-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.5rem;
}

.item-name {
    font-weight: 600;
    color: #1a1a1a;
    font-size: 1.1rem;
}

.item-price {
    font-weight: 700;
    color: #00C896;
    font-size: 1.2rem;
}

.item-details {
    color: #666;
    font-size: 0.95rem;
    line-height: 1.5;
}

.order-total {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 1.5rem;
    border-radius: 12px;
    margin-bottom: 2rem;
}

.total-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.total-label {
    font-size: 1.1rem;
    font-weight: 500;
}

.total-amount {
    font-size: 1.8rem;
    font-weight: 700;
}

.customer-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 2rem;
}

.customer-field {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
}

.download-section {
    text-align: center;
}

.btn-download {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: linear-gradient(135deg, #00C896, #00b085);
    color: white;
    border: none;
    padding: 1rem 2rem;
    border-radius: 50px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    font-size: 1rem;
}

.btn-download:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 200, 150, 0.3);
}

.btn-icon {
    font-size: 1.2rem;
}

/* Timeline Steps */
.steps-timeline {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.step-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
}

.step-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    flex-shrink: 0;
    background: #e9ecef;
    color: #666;
}

.step-item.completed .step-icon {
    background: linear-gradient(135deg, #00C896, #00b085);
    color: white;
}

.step-content h4 {
    color: #1a1a1a;
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.step-content p {
    color: #666;
    font-size: 0.9rem;
    line-height: 1.4;
}

/* Responsive */
@media (max-width: 1024px) {
    .success-container {
        grid-template-columns: 1fr;
    }
    
    .success-left-panel {
        min-height: 50vh;
        padding: 2rem;
    }
    
    .success-title {
        font-size: 2.5rem;
    }
    
    .order-info-grid,
    .customer-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .success-left-panel {
        padding: 1.5rem;
    }
    
    .success-right-panel {
        padding: 1rem;
    }
    
    .order-card,
    .next-steps-card {
        padding: 1.5rem;
    }
    
    .success-title {
        font-size: 2rem;
    }
    
    .action-buttons {
        flex-direction: column;
        width: 100%;
    }
    
    .btn-primary,
    .btn-secondary {
        width: 100%;
        justify-content: center;
    }
}
</style>

<script>
// Bilet indirme işlevi
function downloadTickets() {
    alert('Biletleriniz e-posta adresinize gönderilecektir.');
}
</script>

<?php include 'includes/footer.php'; ?>