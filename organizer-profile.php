<?php
require_once 'config/database.php';
require_once 'includes/session.php';

// Organizatör ID'sini al
$organizerId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($organizerId <= 0) {
    header('Location: etkinlikler.php');
    exit();
}

// Database bağlantısını oluştur
$database = new Database();
$pdo = $database->getConnection();

// Organizatör bilgilerini getir
$query = "SELECT od.*, u.first_name, u.last_name, u.email as user_email, u.profile_image, u.created_at as user_created_at
          FROM organizer_details od
          LEFT JOIN users u ON od.user_id = u.id
          WHERE od.user_id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$organizerId]);
$organizer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$organizer) {
    header('Location: etkinlikler.php');
    exit();
}

// Organizatörün etkinliklerini getir
$eventsQuery = "SELECT e.*, c.name as category_name, c.icon as category_icon,
                       0 as sold_tickets
                FROM events e
                LEFT JOIN categories c ON e.category_id = c.id
                WHERE e.organizer_id = ? AND e.status = 'published'
                ORDER BY e.event_date DESC
                LIMIT 6";
$eventsStmt = $pdo->prepare($eventsQuery);
$eventsStmt->execute([$organizerId]);
$events = $eventsStmt->fetchAll(PDO::FETCH_ASSOC);

// Toplam etkinlik sayısı
$totalEventsQuery = "SELECT COUNT(*) as total FROM events WHERE organizer_id = ? AND status = 'published'";
$totalEventsStmt = $pdo->prepare($totalEventsQuery);
$totalEventsStmt->execute([$organizerId]);
$totalEvents = $totalEventsStmt->fetch(PDO::FETCH_ASSOC)['total'];

// Toplam bilet satışı (şimdilik 0 olarak ayarlandı)
$totalSales = 0;

// Takipçi sayısını getir
$followersQuery = "SELECT COUNT(*) as follower_count FROM followers WHERE organizer_id = ?";
$followersStmt = $pdo->prepare($followersQuery);
$followersStmt->execute([$organizerId]);
$followerCount = $followersStmt->fetch(PDO::FETCH_ASSOC)['follower_count'];

// Kullanıcının bu organizatörü takip edip etmediğini kontrol et
$isFollowing = false;
if (isLoggedIn()) {
    $checkFollowQuery = "SELECT id FROM followers WHERE user_id = ? AND organizer_id = ?";
    $checkFollowStmt = $pdo->prepare($checkFollowQuery);
    $checkFollowStmt->execute([$_SESSION['user_id'], $organizerId]);
    $isFollowing = $checkFollowStmt->fetch() ? true : false;
}

// Etkinlik türlerini ayır
$eventTypes = !empty($organizer['event_types']) ? explode(',', $organizer['event_types']) : [];
$eventTypes = array_map('trim', $eventTypes);

include 'includes/header.php';
?>

<link rel="stylesheet" href="css/pages.css">

<main>
    <!-- Organizatör Profil Hero Section -->
    <section class="organizer-hero">
        <div class="container">
            <!-- Kapak Resmi -->
            <?php if ($organizer['cover_image_url']): ?>
            <div class="organizer-cover-section">
                <img src="<?php echo htmlspecialchars($organizer['cover_image_url']); ?>" alt="Kapak Resmi" class="organizer-cover-image">
                <div class="cover-overlay"></div>
            </div>
            <?php endif; ?>
            
            <!-- Profil Bilgileri -->
            <div class="organizer-profile-section">
                <div class="organizer-profile-info">
                    <div class="organizer-avatar-large">
                        <?php if ($organizer['logo_url'] || $organizer['profile_image']): ?>
                            <img src="<?php echo htmlspecialchars($organizer['logo_url'] ?: $organizer['profile_image']); ?>" alt="Profil Resmi" class="organizer-avatar-img">
                        <?php else: ?>
                            <div class="organizer-avatar-placeholder-large">
                                <i class="fas fa-building"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="organizer-info-content">
                        <h1 class="organizer-title"><?php echo htmlspecialchars($organizer['company_name']); ?></h1>
                        
                        <?php if ($organizer['description']): ?>
                        <p class="organizer-subtitle"><?php echo nl2br(htmlspecialchars($organizer['description'])); ?></p>
                        <?php endif; ?>
                        
                        <div class="organizer-meta">
                            <?php if ($organizer['city']): ?>
                            <span class="meta-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <?php echo htmlspecialchars($organizer['city']); ?>
                            </span>
                            <?php endif; ?>
                            
                            <span class="meta-item">
                                <i class="fas fa-calendar-alt"></i>
                                <?php echo date('Y', strtotime($organizer['user_created_at'])); ?> yılından beri
                            </span>
                            
                            <span class="meta-item">
                                <i class="fas fa-ticket-alt"></i>
                                <?php echo number_format($totalSales); ?> bilet satışı
                            </span>
                            
                            <span class="meta-item">
                                <i class="fas fa-users"></i>
                                <?php echo number_format($followerCount); ?> takipçi
                            </span>
                        </div>
                        
                        <div class="organizer-actions-hero">
                            <?php if (isLoggedIn() && $_SESSION['user_id'] != $organizerId): ?>
                            <button class="btn-follow-large <?php echo $isFollowing ? 'following' : ''; ?>" onclick="followOrganizer(<?php echo $organizerId; ?>)">
                                <?php if ($isFollowing): ?>
                                    <i class="fas fa-check"></i> Takip Ediliyor
                                <?php else: ?>
                                    <i class="fas fa-plus"></i> Takip Et
                                <?php endif; ?>
                            </button>
                            <?php elseif (!isLoggedIn()): ?>
                            <button class="btn-follow-large" onclick="showLoginAlert()">
                                <i class="fas fa-plus"></i> Takip Et
                            </button>
                            <?php endif; ?>
                            
                            <?php if (!empty($organizer['website'])): ?>
                            <a href="<?php echo htmlspecialchars($organizer['website']); ?>" target="_blank" class="btn-website">
                                <i class="fas fa-globe"></i> Web Sitesi
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Organizatör Detayları -->
    <section class="organizer-details-section">
        <div class="container">
            <div class="organizer-content-grid">
                <!-- Sol Kolon: Hakkında ve İletişim -->
                <div class="organizer-left-column">
                    <!-- Hakkında -->
                    <?php if ($organizer['about']): ?>
                    <div class="info-card">
                        <h3><i class="fas fa-info-circle"></i> Hakkında</h3>
                        <div class="about-content">
                            <p><?php echo nl2br(htmlspecialchars($organizer['about'])); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    
                    
                    <!-- İletişim Bilgileri -->
                    <div class="info-card">
                        <h3><img src="SVG/personalcard.svg" alt="İletişim" class="icon"> İletişim Bilgileri</h3>
                        <div class="contact-info">
                            <?php if ($organizer['phone']): ?>
                            <div class="contact-item">
                                <img src="SVG/call.svg" alt="Telefon" class="icon">
                                <span><?php echo htmlspecialchars($organizer['phone']); ?></span>
                                <a href="tel:<?php echo htmlspecialchars($organizer['phone']); ?>" class="contact-action">Ara</a>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($organizer['email']): ?>
                            <div class="contact-item">
                                <img src="SVG/email.svg" alt="E-posta" class="icon">
                                <span><?php echo htmlspecialchars($organizer['email']); ?></span>
                                <a href="mailto:<?php echo htmlspecialchars($organizer['email']); ?>" class="contact-action">E-posta</a>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($organizer['address']): ?>
                            <div class="contact-item">
                                <img src="SVG/maps.svg" alt="Adres" class="icon">
                                <span><?php echo htmlspecialchars($organizer['address']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Sosyal Medya -->
                        <?php if ($organizer['facebook_url'] || $organizer['instagram_url'] || $organizer['website']): ?>
                        <div class="social-media">
                            <h4>Sosyal Medya</h4>
                            <div class="social-links">
                                <?php if ($organizer['facebook_url']): ?>
                                <a href="<?php echo htmlspecialchars($organizer['facebook_url']); ?>" target="_blank" class="social-link facebook">
                                    <i class="fab fa-facebook-f"></i>
                                </a>
                                <?php endif; ?>
                                
                                <?php if ($organizer['instagram_url']): ?>
                                <a href="<?php echo htmlspecialchars($organizer['instagram_url']); ?>" target="_blank" class="social-link instagram">
                                    <img src="SVG/instagram.svg" alt="Instagram" class="icon">
                                </a>
                                <?php endif; ?>
                                
                                <?php if (!empty($organizer['website'])): ?>
                                <a href="<?php echo htmlspecialchars($organizer['website']); ?>" target="_blank" class="social-link website">
                                    <i class="fas fa-globe"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Sağ Kolon: Etkinlikler -->
                <div class="organizer-right-column">
                    <div class="info-card">
                        <div class="card-header-with-action">
                            <h3><i class="fas fa-calendar-check"></i> Etkinlikler (<?php echo $totalEvents; ?>)</h3>
                            <?php if ($totalEvents > 6): ?>
                            <a href="etkinlikler.php?organizer=<?php echo $organizerId; ?>" class="btn-view-all">Tümünü Gör</a>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($events)): ?>
                        <div class="events-grid">
                            <?php foreach ($events as $event): ?>
                            <div class="event-card-mini">
                                <div class="event-image-mini">
                                    <?php if ($event['image_url']): ?>
                                    <img src="<?php echo htmlspecialchars($event['image_url']); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>">
                                    <?php else: ?>
                                    <div class="event-placeholder">
                                        <i class="fas fa-calendar"></i>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="event-date-badge">
                                        <span class="date-day"><?php echo date('d', strtotime($event['event_date'])); ?></span>
                                        <span class="date-month"><?php 
                                            $months = ['Oca', 'Şub', 'Mar', 'Nis', 'May', 'Haz', 'Tem', 'Ağu', 'Eyl', 'Eki', 'Kas', 'Ara'];
                                            $date = new DateTime($event['event_date']);
                                            echo $months[$date->format('n') - 1];
                                        ?></span>
                                    </div>
                                </div>
                                
                                <div class="event-info-mini">
                                    <h4 class="event-title-mini">
                                        <a href="etkinlik-detay.php?id=<?php echo $event['id']; ?>">
                                            <?php echo htmlspecialchars($event['title']); ?>
                                        </a>
                                    </h4>
                                    
                                    <div class="event-meta-mini">
                                        <span class="event-venue">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <?php echo htmlspecialchars($event['venue_name']); ?>
                                        </span>
                                        
                                        <span class="event-time">
                                            <i class="fas fa-clock"></i>
                                            <?php echo date('H:i', strtotime($event['event_date'])); ?>
                                        </span>
                                    </div>
                                    
                                    <?php if ($event['sold_tickets'] > 0): ?>
                                    <div class="event-sales">
                                        <i class="fas fa-ticket-alt"></i>
                                        <?php echo number_format($event['sold_tickets']); ?> bilet satıldı
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="no-events">
                            <i class="fas fa-calendar-times"></i>
                            <p>Henüz yayınlanmış etkinlik bulunmuyor.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<script>
// Organizatör takip etme fonksiyonu
function followOrganizer(organizerId) {
    const button = event.target.closest('.btn-follow-large');
    const originalText = button.innerHTML;
    
    // Buton durumunu geçici olarak değiştir
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> İşleniyor...';
    button.disabled = true;
    
    fetch('ajax/follow_organizer.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            organizer_id: organizerId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            button.innerHTML = data.button_text;
            if (data.action === 'followed') {
                button.classList.add('following');
            } else {
                button.classList.remove('following');
            }
            
            // Takipçi sayısını güncelle
            const followerCountElement = document.querySelector('.meta-item .fas.fa-users').parentElement;
            if (followerCountElement) {
                const currentCount = parseInt(followerCountElement.textContent.match(/\d+/)[0]);
                const newCount = data.action === 'followed' ? currentCount + 1 : currentCount - 1;
                followerCountElement.innerHTML = '<i class="fas fa-users"></i> ' + newCount.toLocaleString() + ' takipçi';
            }
            
            // Başarı mesajı göster
            showNotification(data.message, 'success');
        } else {
            button.innerHTML = originalText;
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        button.innerHTML = originalText;
        showNotification('Bir hata oluştu', 'error');
    })
    .finally(() => {
        button.disabled = false;
    });
}

// Bildirim gösterme fonksiyonu
function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 5px;
        color: white;
        font-weight: bold;
        z-index: 10000;
        animation: slideIn 0.3s ease;
        ${type === 'success' ? 'background-color: #28a745;' : 'background-color: #dc3545;'}
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

function showLoginAlert() {
    // Uyarı mesajı oluştur
    const alertDiv = document.createElement('div');
    alertDiv.innerHTML = 'Lütfen önce bir müşteri hesabına giriş yapın';
    alertDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #ff4757;
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 10000;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.3s ease;
    `;
    
    // Sayfaya ekle
    document.body.appendChild(alertDiv);
    
    // 3 saniye sonra otomatik kaldır
    setTimeout(function() {
        if (document.body.contains(alertDiv)) {
            document.body.removeChild(alertDiv);
        }
    }, 3000);
}

// Sayfa başlığını güncelle
document.addEventListener('DOMContentLoaded', function() {
    document.title = '<?php echo htmlspecialchars($organizer['company_name']); ?> - Organizatör Profili - BiletJack';
});
</script>

<?php include 'includes/footer.php'; ?>