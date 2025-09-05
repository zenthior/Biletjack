<?php
session_start();
require_once '../config/database.php';

// QR yetkili kontrolü
if (!isset($_SESSION['qr_staff_id'])) {
    header('Location: login.php');
    exit();
}

$staff_id = $_SESSION['qr_staff_id'];
$organizer_id = $_SESSION['qr_organizer_id'];
$staff_name = $_SESSION['qr_staff_name'];

// Veritabanı bağlantısı
$database = new Database();
$pdo = $database->getConnection();

// Bilet doğrulama işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_ticket'])) {
    $ticket_code = trim($_POST['ticket_code']);
    $response = ['success' => false, 'message' => '', 'ticket_info' => null];
    
    if (!empty($ticket_code)) {
        // Bilet kodunu kontrol et
        $stmt = $pdo->prepare("
            SELECT t.*, e.title as event_title, e.event_date, e.venue_name, 
                   u.first_name, u.last_name, u.email
            FROM tickets t 
            JOIN events e ON t.event_id = e.id 
            JOIN users u ON t.user_id = u.id 
            WHERE t.ticket_number = ? AND e.organizer_id = ?
        ");
        $stmt->execute([$ticket_code, $organizer_id]);
        $ticket = $stmt->fetch();
        
        if ($ticket) {
            if ($ticket['status'] === 'used') {
                $response['message'] = 'Bu bilet daha önce kullanılmış!';
            } else {
                // Bilet bilgilerini döndür
                $response['success'] = true;
                $response['ticket_info'] = $ticket;
            }
        } else {
            $response['message'] = 'Geçersiz bilet kodu veya bu bilet size ait değil!';
        }
    } else {
        $response['message'] = 'Bilet kodu boş olamaz!';
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Bilet onaylama işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_ticket'])) {
    $ticket_id = $_POST['ticket_id'];
    $response = ['success' => false, 'message' => ''];
    
    try {
        $pdo->beginTransaction();
        
        // Bileti kullanılmış olarak işaretle
        $stmt = $pdo->prepare("UPDATE tickets SET status = 'used', used_at = NOW() WHERE id = ?");
        $stmt->execute([$ticket_id]);
        
        // Doğrulama kaydı oluştur
        $stmt = $pdo->prepare("INSERT INTO ticket_verifications (ticket_id, qr_staff_id, status) VALUES (?, ?, 'verified')");
        $stmt->execute([$ticket_id, $staff_id]);
        
        $pdo->commit();
        $response['success'] = true;
        $response['message'] = 'Bilet başarıyla onaylandı!';
    } catch (Exception $e) {
        $pdo->rollBack();
        $response['message'] = 'Bilet onaylanırken bir hata oluştu!';
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// QR yetkili bilgilerini al
$stmt = $pdo->prepare("SELECT * FROM qr_staff WHERE id = ?");
$stmt->execute([$staff_id]);
$staff = $stmt->fetch();

// Bugünkü doğrulamalar
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM ticket_verifications tv 
    JOIN tickets t ON tv.ticket_id = t.id 
    JOIN events e ON t.event_id = e.id 
    WHERE tv.qr_staff_id = ? AND DATE(tv.verification_time) = CURDATE()
");
$stmt->execute([$staff_id]);
$verified_today = $stmt->fetchColumn();

// Toplam doğrulamalar
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM ticket_verifications tv 
    JOIN tickets t ON tv.ticket_id = t.id 
    JOIN events e ON t.event_id = e.id 
    WHERE tv.qr_staff_id = ?
");
$stmt->execute([$staff_id]);
$total_verified = $stmt->fetchColumn();

// Organizatörün etkinlikleri
$stmt = $pdo->prepare("SELECT id, title, event_date FROM events WHERE organizer_id = ? AND event_date >= CURDATE() ORDER BY event_date ASC");
$stmt->execute([$organizer_id]);
$events = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Panel - Bilet Doğrulama</title>
    
    <!-- Favicon -->
    <?php
    // Favicon ayarını veritabanından al
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'site_favicon'");
        $stmt->execute();
        $faviconSetting = $stmt->fetchColumn();
        $faviconPath = $faviconSetting ? '../assets/images/' . $faviconSetting : '../assets/images/favicon.ico';
    } catch (Exception $e) {
        $faviconPath = '../assets/images/favicon.ico';
    }
    ?>
    <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars($faviconPath); ?>">
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../css/organizer2.css" rel="stylesheet">
</head>
<body class="qr-panel-body">
    <div class="qr-panel-container">
        <!-- Header -->
        <div class="qr-panel-header">
            <div class="qr-panel-logo">
                <i class="fas fa-qrcode"></i>
                <span>QR Panel</span>
            </div>
            <div class="qr-panel-user">
                <span>Hoş geldiniz, <?php echo htmlspecialchars($staff['username']); ?></span>
                <a href="../organizer/index.php" class="qr-btn qr-btn-secondary" title="Organizatör Paneline Dön" style="margin-right: 10px;">
                    <i class="fas fa-arrow-left"></i> Organizatör Paneli
                </a>
                <a href="logout.php" class="qr-logout-btn" title="Çıkış">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
        
        <!-- Ana İçerik -->
        <div class="qr-panel-content">
            <!-- İstatistikler -->
            <div class="qr-stats">
                <div class="qr-stat-card">
                    <div class="qr-stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="qr-stat-info">
                        <h3><?php echo $verified_today; ?></h3>
                        <p>Bugün Onaylanan</p>
                    </div>
                </div>
                <div class="qr-stat-card">
                    <div class="qr-stat-icon">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <div class="qr-stat-info">
                        <h3><?php echo $total_verified; ?></h3>
                        <p>Toplam Onaylanan</p>
                    </div>
                </div>
            </div>
            
            <!-- Bilet Doğrulama -->
            <div class="qr-verification-section">
                <div class="qr-section-header">
                    <h2><i class="fas fa-search"></i> Bilet Doğrulama</h2>
                    <p>Bilet kodunu girerek veya QR kod okutarak bilet doğrulaması yapabilirsiniz.</p>
                </div>
                
                <div class="qr-verification-form">
                    <div class="qr-input-group">
                        <input type="text" id="ticketCode" placeholder="Bilet kodunu girin...">
                        <button class="qr-btn qr-btn-primary" onclick="verifyTicket()">
                            <i class="fas fa-search"></i> Bileti Doğrula
                        </button>
                    </div>
                    
                    <div class="qr-camera-section">
                        <button class="qr-btn qr-btn-secondary" onclick="startCamera()">
                            <i class="fas fa-camera"></i> Kamera ile QR Kod Okut
                        </button>
                        
                        <div id="cameraContainer">
                            <video id="cameraVideo" width="400" height="300" autoplay></video><br>
                            <button class="qr-btn qr-btn-danger" onclick="stopCamera()">
                                <i class="fas fa-stop"></i> Kamerayı Durdur
                            </button>
                        </div>
                    </div>
                </div>
                
                    <div id="result"></div>
            </div>
            
            <!-- Onaylanan Biletler Listesi -->
            <div class="qr-approved-section">
                <div class="qr-section-header">
                    <h2><i class="fas fa-list"></i> Onaylanan Biletler</h2>
                    <p>Bu oturumda onaylanan biletlerin listesi</p>
                </div>
                
                <div id="approvedTicketsList" class="qr-approved-list">
                    <div class="qr-no-tickets">
                        <i class="fas fa-info-circle"></i>
                        <p>Henüz onaylanmış bilet bulunmuyor</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bilet Detay Modal -->
    <div id="ticketModal" class="qr-modal" style="display: none;">
        <div class="qr-modal-content">
            <div class="qr-modal-header">
                <h3><i class="fas fa-ticket-alt"></i> Bilet Detayları</h3>
                <button class="qr-modal-close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="qr-modal-body">
                <div id="ticketInfo" class="qr-ticket-info">
                    <!-- Bilet bilgileri buraya gelecek -->
                </div>
            </div>
            <div class="qr-modal-footer">
                <button class="qr-btn qr-btn-secondary" onclick="closeModal()">
                    <i class="fas fa-times"></i> Kapat
                </button>
                <button id="approveBtn" class="qr-btn qr-btn-success" onclick="approveTicket()" style="display: none;">
                    <i class="fas fa-check"></i> Bileti Onayla
                </button>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>
    <script>
        let currentTicket = null;
        let cameraStream = null;
        let isScanning = false;

        // QR içeriğinden bilet numarasını çıkaran yardımcı fonksiyon
        function extractTicketNumber(raw) {
            if (!raw) return null;
            let input = String(raw).trim();

            // 1) JSON ise ticket_number veya biletjack_url alanından çek
            if (input.startsWith('{') || input.startsWith('[')) {
                try {
                    const obj = JSON.parse(input);
                    if (obj && typeof obj === 'object') {
                        if (obj.ticket_number && typeof obj.ticket_number === 'string') {
                            return obj.ticket_number.trim();
                        }
                        if (obj.biletjack_url && typeof obj.biletjack_url === 'string') {
                            try {
                                const url = new URL(obj.biletjack_url);
                                const parts = url.pathname.split('/').filter(Boolean);
                                return parts.length ? parts[parts.length - 1] : null;
                            } catch (_) {
                                // geçersiz URL
                            }
                        }
                    }
                } catch (_) {
                    // JSON değilse sessizce geç
                }
            }

            // 2) /verify/ içeren bir URL ise sondaki parçayı al
            if (input.includes('/verify/')) {
                try {
                    // URL tam değilse başına http eklemeden de parçalayalım
                    const path = input.split('/');
                    const last = path[path.length - 1] || path[path.length - 2];
                    if (last) return last.trim();
                } catch (_) {}
            }

            // 3) Bilet kodu formatına benziyorsa direkt kullan (BJ ile başlıyor)
            if (/^BJ[0-9]{4}[A-Z0-9]+$/i.test(input)) {
                return input.toUpperCase();
            }

            return null;
        }

        // Bilet doğrulama fonksiyonu
        async function verifyTicket() {
            const ticketCodeInput = document.getElementById('ticketCode');
            const rawValue = ticketCodeInput.value.trim();
            const ticketCode = extractTicketNumber(rawValue) || rawValue;
            
            if (!ticketCode) {
                showResult('Lütfen bilet kodunu girin', 'error');
                return;
            }

            try {
                const response = await fetch('verify_ticket.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ ticket_code: ticketCode })
                });

                const data = await response.json();

                if (data.success) {
                    currentTicket = data.ticket;
                    showTicketModal(data.ticket);
                    ticketCodeInput.value = '';
                } else {
                    showResult(data.message, 'error');
                }
            } catch (error) {
                console.error('Hata:', error);
                showResult('Bağlantı hatası oluştu', 'error');
            }
        }

        // Bilet onaylama fonksiyonu
        async function approveTicket() {
            if (!currentTicket) return;

            try {
                const response = await fetch('approve_ticket.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ ticket_id: currentTicket.id })
                });

                const data = await response.json();

                if (data.success) {
                    showResult(`Bilet başarıyla onaylandı! (${data.ticket_number})`, 'success');
                    
                    // Onaylanan bileti listeye ekle
                    addApprovedTicket({
                        ticket_number: data.ticket_number,
                        event_title: data.event_title,
                        customer_name: currentTicket.customer_name
                    });
                    
                    closeModal();
                    
                    // İstatistikleri güncelle (sayfa yenileme yerine)
                    updateStats();
                } else {
                    showResult(data.message, 'error');
                }
            } catch (error) {
                console.error('Hata:', error);
                showResult('Onaylama sırasında hata oluştu', 'error');
            }
        }

        // Bilet modal gösterme
        function showTicketModal(ticket) {
            const modal = document.getElementById('ticketModal');
            const ticketInfo = document.getElementById('ticketInfo');
            const approveBtn = document.getElementById('approveBtn');
        
            let statusBadge = '';
            let alertMessage = '';
            
            if (ticket.status === 'used') {
                statusBadge = `<span class="status used">Bu bilet zaten kullanıldı!</span>`;
                alertMessage = `<div class=\"qr-alert qr-alert-warning\">\n                    <i class=\"fas fa-exclamation-triangle\"></i>\n                    Bu bilet ${ticket.used_at} tarihinde kullanılmıştır.\n                </div>`;
            } else if (ticket.is_verified) {
                statusBadge = `<span class="status active">Onaylanmış</span>`;
            } else if (ticket.can_verify) {
                statusBadge = `<span class="status inactive">Onay Bekliyor</span>`;
            } else {
                statusBadge = `<span class="status inactive">Onaylanamaz</span>`;
            }
        
            ticketInfo.innerHTML = `
                ${alertMessage}
                <div class="qr-ticket-header">
                    <h4>${ticket.event_title}</h4>
                    <div class="qr-ticket-code">${ticket.ticket_number}</div>
                </div>
                <div class="qr-ticket-details">
                    <div class="qr-detail-item">
                        <label>Durum:</label>
                        <span>${statusBadge}</span>
                    </div>
                    <div class="qr-detail-item">
                        <label>Etkinlik Tarihi:</label>
                        <span>${ticket.event_date}</span>
                    </div>
                    <div class="qr-detail-item">
                        <label>Mekan:</label>
                        <span>${ticket.venue_name}</span>
                    </div>
                    <div class="qr-detail-item">
                        <label>Bilet Türü:</label>
                        <span>${ticket.ticket_type}</span>
                    </div>
                    ${ticket.seat_label ? `
                    <div class="qr-detail-item">
                        <label>Koltuk:</label>
                        <span>${ticket.seat_label}</span>
                    </div>
                    ` : `
                    <div class="qr-detail-item">
                        <label>Bilet Adedi:</label>
                        <span>${ticket.quantity} adet</span>
                    </div>
                    `}
                    <div class="qr-detail-item">
                        <label>Bilet Fiyatı:</label>
                        <span>${ticket.is_reservation ? 'Rezervasyonlu' : ticket.price + ' TL'}</span>
                    </div>
                    <div class="qr-detail-item">
                        <label>Müşteri:</label>
                        <span>${ticket.customer_name}</span>
                    </div>
                    <div class="qr-detail-item">
                        <label>E-posta:</label>
                        <span>${ticket.customer_email}</span>
                    </div>
                    <div class="qr-detail-item">
                        <label>Telefon:</label>
                        <span>${ticket.customer_phone || 'Belirtilmemiş'}</span>
                    </div>
                    <div class="qr-detail-item">
                        <label>Satın Alma Tarihi:</label>
                        <span>${ticket.purchase_date}</span>
                    </div>
                    ${ticket.is_verified ? `
                    <div class="qr-detail-item">
                        <label>Onaylanma Tarihi:</label>
                        <span>${ticket.verified_at}</span>
                    </div>
                    ` : ''}
                    ${ticket.status === 'used' && ticket.used_at ? `
                    <div class="qr-detail-item">
                        <label>Kullanılma Tarihi:</label>
                        <span>${ticket.used_at}</span>
                    </div>
                    ` : ''}
                </div>
            `;
        
            // Onay butonunu göster/gizle
            if (ticket.can_verify && ticket.status === 'active') {
                approveBtn.style.display = 'inline-flex';
            } else {
                approveBtn.style.display = 'none';
            }
        
            modal.style.display = 'flex';
        }

        // Modal kapatma
        function closeModal() {
            document.getElementById('ticketModal').style.display = 'none';
            currentTicket = null;
        }

        // Sonuç gösterme
        function showResult(message, type) {
            const result = document.getElementById('result');
            result.className = `qr-result qr-result-${type}`;
            result.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                ${message}
            `;
            result.style.display = 'flex';

            setTimeout(() => {
                result.style.display = 'none';
            }, 5000);
        }

        // Kamera başlatma
        async function startCamera() {
            try {
                const container = document.getElementById('cameraContainer');
                const video = document.getElementById('cameraVideo');

                cameraStream = await navigator.mediaDevices.getUserMedia({ 
                    video: { facingMode: 'environment' } 
                });
                
                video.srcObject = cameraStream;
                container.style.display = 'block';
                isScanning = true;
                
                // QR kod tarama başlat
                scanQRCode();
                
            } catch (error) {
                console.error('Kamera hatası:', error);
                showResult('Kamera erişimi reddedildi', 'error');
            }
        }

        // Kamera durdurma
        function stopCamera() {
            if (cameraStream) {
                cameraStream.getTracks().forEach(track => track.stop());
                cameraStream = null;
            }
            
            document.getElementById('cameraContainer').style.display = 'none';
            isScanning = false;
        }

        // QR kod tarama
        function scanQRCode() {
            if (!isScanning) return;

            const video = document.getElementById('cameraVideo');
            const canvas = document.createElement('canvas');
            const context = canvas.getContext('2d');

            if (video.readyState === video.HAVE_ENOUGH_DATA) {
                canvas.height = video.videoHeight;
                canvas.width = video.videoWidth;
                context.drawImage(video, 0, 0, canvas.width, canvas.height);
                
                const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
                const code = jsQR(imageData.data, imageData.width, imageData.height);
                
                if (code) {
                    const normalized = extractTicketNumber(code.data);
                    document.getElementById('ticketCode').value = normalized || code.data;
                    if (normalized) {
                        showResult(`Bilet kodu algılandı: ${normalized}`, 'success');
                    }
                    stopCamera();
                    verifyTicket();
                    return;
                }
            }

            requestAnimationFrame(scanQRCode);
        }

        // Enter tuşu ile doğrulama
        document.getElementById('ticketCode').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                verifyTicket();
            }
        });
        
        // Onaylanan bilet sayacı
        let approvedTicketsCount = 0;
        
        // Onaylanan bileti listeye ekle
        function addApprovedTicket(ticket) {
            const approvedList = document.getElementById('approvedTicketsList');
            
            // İlk bilet ise "henüz bilet yok" mesajını kaldır
            if (approvedTicketsCount === 0) {
                approvedList.innerHTML = '';
            }
            
            approvedTicketsCount++;
            
            const ticketItem = document.createElement('div');
            ticketItem.className = 'qr-approved-item';
            ticketItem.innerHTML = `
                <div class="qr-approved-info">
                    <div class="qr-approved-number">#${approvedTicketsCount}</div>
                    <div class="qr-approved-details">
                        <h4>${ticket.event_title}</h4>
                        <p><i class="fas fa-user"></i> ${ticket.customer_name || 'Belirtilmemiş'}</p>
                        <p><i class="fas fa-ticket-alt"></i> ${ticket.ticket_number}</p>
                    </div>
                </div>
                <div class="qr-approved-status">
                    <i class="fas fa-check-circle"></i>
                    <span>Onaylandı</span>
                </div>
            `;
            
            // En üste ekle
            approvedList.insertBefore(ticketItem, approvedList.firstChild);
        }
        
        // İstatistikleri güncelle
        function updateStats() {
            // Bugünkü onaylanan bilet sayısını artır
            const todayStatElement = document.querySelector('.qr-stat-card:first-child .qr-stat-info h3');
            const totalStatElement = document.querySelector('.qr-stat-card:last-child .qr-stat-info h3');
            
            if (todayStatElement && totalStatElement) {
                const currentToday = parseInt(todayStatElement.textContent) || 0;
                const currentTotal = parseInt(totalStatElement.textContent) || 0;
                
                todayStatElement.textContent = currentToday + 1;
                totalStatElement.textContent = currentTotal + 1;
            }
        }

        // Modal dışına tıklayınca kapatma
        document.getElementById('ticketModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>