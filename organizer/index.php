<?php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../classes/User.php';

// Organizatör kontrolü
requireOrganizer();

// Organizatör onay kontrolü - session'ı da güncelle
if (!isOrganizerApproved()) {
    // Session durumunu da kontrol et ve güncelle
    $database = new Database();
    $pdo = $database->getConnection();
    
    $query = "SELECT u.status, od.approval_status FROM users u 
              LEFT JOIN organizer_details od ON u.id = od.user_id 
              WHERE u.id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && $result['approval_status'] === 'approved' && $result['status'] === 'approved') {
        // Session'ı güncelle
        $_SESSION['user_status'] = 'approved';
    } else {
        header('Location: pending.php');
        exit();
    }
}

$currentUser = getCurrentUser();
$organizer_id = $_SESSION['user_id'];

// Gerçek istatistikleri al
$database = new Database();
$pdo = $database->getConnection();

// Toplam gelir - tickets üzerinden (orders.event_id yok)
// Not: Sadece ödenmiş siparişler
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(t.price * t.quantity), 0) AS total_revenue
    FROM tickets t
    JOIN events e ON t.event_id = e.id
    JOIN orders o ON t.order_id = o.id
    WHERE e.organizer_id = ? AND o.payment_status = 'paid'
");
$stmt->execute([$organizer_id]);
$totalRevenue = $stmt->fetchColumn();

// Toplam siparişler - organizer'a ait biletleri içeren ödenmiş siparişler (distinct)
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT o.id) AS total_orders
    FROM orders o
    JOIN tickets t ON t.order_id = o.id
    JOIN events e ON t.event_id = e.id
    WHERE e.organizer_id = ? AND o.payment_status = 'paid'
");
$stmt->execute([$organizer_id]);
$totalOrders = $stmt->fetchColumn();

// Toplam biletler
$stmt = $pdo->prepare("SELECT COUNT(*) as total_tickets FROM tickets t JOIN events e ON t.event_id = e.id WHERE e.organizer_id = ?");
$stmt->execute([$organizer_id]);
$totalTickets = $stmt->fetchColumn();

// Kullanılan biletler
$stmt = $pdo->prepare("SELECT COUNT(*) as used_tickets FROM tickets t JOIN events e ON t.event_id = e.id WHERE e.organizer_id = ? AND t.status = 'used'");
$stmt->execute([$organizer_id]);
$usedTickets = $stmt->fetchColumn();

// QR yetkili hesabını kontrol et
$stmt = $pdo->prepare("SELECT * FROM qr_staff WHERE organizer_id = ?");
$stmt->execute([$organizer_id]);
$qr_staff = $stmt->fetch();

// Aktif kullanıcılar (bugün) - organizer'a ait biletleri içeren ödenmiş siparişler (distinct user)
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT o.user_id) as engaged_users
    FROM orders o
    JOIN tickets t ON t.order_id = o.id
    JOIN events e ON t.event_id = e.id
    WHERE e.organizer_id = ? AND o.payment_status = 'paid' AND DATE(o.created_at) = CURDATE()
");
$stmt->execute([$organizer_id]);
$engagedUsers = $stmt->fetchColumn() ?: 0;

// Bekleyen rezervasyonlar
$stmt = $pdo->prepare("
    SELECT r.id, r.event_id, r.seat_id, r.user_id, r.status, r.created_at,
           e.title as event_title, e.event_date,
           s.row_number, s.seat_number, sc.name as category_name,
           u.first_name, u.last_name, u.email, u.phone
    FROM reservations r
    JOIN events e ON r.event_id = e.id
    JOIN seats s ON r.seat_id = s.id
    LEFT JOIN seat_categories sc ON s.category_id = sc.id
    JOIN users u ON r.user_id = u.id
    WHERE e.organizer_id = ? AND r.status = 'pending'
    ORDER BY r.created_at DESC
    LIMIT 20
");
$stmt->execute([$organizer_id]);
$pendingReservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Rezervasyon istatistikleri
$stmt = $pdo->prepare("
    SELECT 
        COUNT(CASE WHEN r.status = 'pending' THEN 1 END) as pending_count,
        COUNT(CASE WHEN r.status = 'approved' THEN 1 END) as approved_count,
        COUNT(CASE WHEN r.status = 'rejected' THEN 1 END) as rejected_count
    FROM reservations r
    JOIN events e ON r.event_id = e.id
    WHERE e.organizer_id = ?
");
$stmt->execute([$organizer_id]);
$reservationStats = $stmt->fetch(PDO::FETCH_ASSOC);

// Aylık satış (son 12 ay) - grafik için
$stmt = $pdo->prepare("
    SELECT DATE_FORMAT(o.created_at, '%Y-%m') as month,
           COALESCE(SUM(t.price * COALESCE(t.quantity,1)), 0) as revenue
    FROM orders o
    JOIN tickets t ON t.order_id = o.id
    JOIN events e ON t.event_id = e.id
    WHERE e.organizer_id = ? AND o.payment_status = 'paid'
    GROUP BY DATE_FORMAT(o.created_at, '%Y-%m')
    ORDER BY month ASC
    LIMIT 12
");
$stmt->execute([$organizer_id]);
$overviewMonthly = $stmt->fetchAll(PDO::FETCH_ASSOC);

// En çok satın alanlar (gerçek veri)
$stmt = $pdo->prepare("
    SELECT o.user_id, u.first_name, u.last_name,
           COALESCE(SUM(t.price * COALESCE(t.quantity,1)), 0) as spent,
           COUNT(DISTINCT o.id) as orders_count
    FROM orders o
    JOIN tickets t ON t.order_id = o.id
    JOIN events e ON t.event_id = e.id
    JOIN users u ON u.id = o.user_id
    WHERE e.organizer_id = ? AND o.payment_status = 'paid'
    GROUP BY o.user_id, u.first_name, u.last_name
    ORDER BY spent DESC
    LIMIT 5
");
$stmt->execute([$organizer_id]);
$topBuyers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Sayfa parametresi
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

include 'includes/header.php';
?>

<!-- Mobile Menu Toggle -->


<!-- Mobile Overlay -->
<div class="mobile-overlay" id="mobileOverlay"></div>

<!-- Floating Toggle Button -->
<div class="floating-toggle show" id="floatingToggle" onclick="toggleSidebar()">
    <i class="fas fa-ticket-alt"></i>
</div>

<!-- Sol Sidebar -->
<div class="modern-sidebar collapsed" id="sidebar">
    <div class="sidebar-logo" onclick="toggleSidebar()" style="cursor: pointer;" id="sidebarLogo">
        <i class="fas fa-ticket-alt"></i>
    </div>
    
    <div class="sidebar-nav">
    <div class="nav-icon active" title="Ana Sayfa" onclick="window.location.href='./index.php'" style="cursor: pointer;">
        <i class="fas fa-home"></i>
    </div>
    <div class="nav-icon" title="Etkinlikler" onclick="window.location.href='./events.php'" style="cursor: pointer;">
        <i class="fas fa-calendar-alt"></i>
    </div>
    <div class="nav-icon" title="QR Yetkili" onclick="loadPage('qr_staff')" style="cursor: pointer;">
        <i class="fas fa-qrcode"></i>
    </div>
    <div class="nav-icon" title="Analitik" onclick="loadPage('analytics')" style="cursor: pointer;">
        <i class="fas fa-chart-bar"></i>
    </div>
    <div class="nav-icon" title="Yorumlar" onclick="window.location.href='./reviews.php'" style="cursor: pointer;">
        <i class="fas fa-star"></i>
    </div>
    <div class="nav-icon" title="Ayarlar" onclick="window.location.href='./settings.php'" style="cursor: pointer;">
        <i class="fas fa-cog"></i>
    </div>
    <!-- Ana sayfaya dön butonu (yeni) -->
    <div class="nav-icon" title="Ana Sayfaya Dön" onclick="window.location.href='../index.php'" style="cursor: pointer;">
        <i class="fas fa-arrow-left"></i>
    </div>
    <div class="nav-icon" title="Çıkış" onclick="window.location.href='../auth/logout.php'" style="cursor: pointer;">
        <i class="fas fa-sign-out-alt"></i>
    </div>
</div>
</div>
<!-- Ana İçerik -->
<div class="main-content expanded">
    <!-- Üst Header -->
    <div class="top-header">
        <div class="user-profile">
            <div class="user-avatar">
                <?php echo strtoupper(substr($currentUser['first_name'], 0, 1) . substr($currentUser['last_name'], 0, 1)); ?>
            </div>
            <div class="user-info">
                <h4><?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?></h4>
                <p>Organizatör</p>
            </div>
        </div>
        
        <div class="search-container">
            <i class="fas fa-search search-icon"></i>
            <input type="text" class="search-input" placeholder="Ara...">
        </div>
        
        <div class="notification-icon" id="notifBell" style="position: relative; cursor: pointer;">
            <i class="fas fa-bell"></i>
            <span id="notifBadge" style="position:absolute; top:-4px; right:-4px; background:#ef4444; color:#fff; border-radius:999px; font-size:10px; padding:2px 6px; display:none;">0</span>
            <div id="notifDropdown" style="position:absolute; right:0; top:36px; width:320px; background:#111827; border:1px solid #334155; border-radius:10px; box-shadow:0 8px 24px rgba(0,0,0,.35); display:none; z-index:1000; max-height:420px; overflow:auto;">
                <div style="display:flex; align-items:center; justify-content:space-between; padding:10px 12px; border-bottom:1px solid #334155;">
                    <strong>Bildirimler</strong>
                    <button id="markAllNotif" style="background:#4f46e5; color:#fff; border:none; border-radius:8px; padding:6px 8px; font-size:12px; cursor:pointer;">Tümünü okundu</button>
                </div>
                <div id="notifList" style="display:grid; gap:8px; padding:10px 12px;"></div>
                <div id="notifEmpty" style="padding:16px; text-align:center; color:#94a3b8; display:none;">Henüz bildiriminiz yok.</div>
            </div>
        </div>
    </div>
    
    <!-- Dashboard İçeriği -->
    <div class="dashboard-content" id="main-content">
        <!-- Üst İstatistik Kartları -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Gelir</span>
                    <div class="stat-icon revenue">
                        <i class="fas fa-lira-sign"></i>
                    </div>
                </div>
                <div class="stat-value">₺<?php echo number_format($totalRevenue); ?></div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i>
                    +%12 bu ay
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Siparişler</span>
                    <div class="stat-icon orders">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo number_format($totalOrders); ?></div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i>
                    +%8 bu ay
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Toplam Bilet</span>
                    <div class="stat-icon visits">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo number_format($totalTickets); ?></div>
                <div class="stat-change positive">
                    <i class="fas fa-check-circle"></i>
                    <?php echo number_format($usedTickets); ?> kullanıldı
                </div>
            </div>
        </div>
        
        <!-- Ana Grid -->
        <div class="main-grid">
            <!-- Rezervasyon Yönetimi -->
            <div class="analytics-card">
                <div class="card-header">
                    <h3 class="card-title">Rezervasyon Yönetimi</h3>
                    <div class="reservation-tabs">
                        <button class="tab-btn active" onclick="switchReservationTab('pending')">Bekleyen (<?php echo $reservationStats['pending_count'] ?? 0; ?>)</button>
                        <button class="tab-btn" onclick="switchReservationTab('approved')">Onaylanan (<?php echo $reservationStats['approved_count'] ?? 0; ?>)</button>
                        <button class="tab-btn" onclick="switchReservationTab('rejected')">Reddedilen (<?php echo $reservationStats['rejected_count'] ?? 0; ?>)</button>
                    </div>
                </div>
                
                <div class="reservations-container">
                    <!-- Bekleyen Rezervasyonlar -->
                    <div id="pending-reservations" class="tab-content active">
                        <?php if (empty($pendingReservations)): ?>
                            <div class="no-reservations">
                                <i class="fas fa-calendar-check"></i>
                                <p>Bekleyen rezervasyon bulunmuyor.</p>
                            </div>
                        <?php else: ?>
                            <div class="reservations-list">
                                <?php foreach ($pendingReservations as $reservation): ?>
                                    <div class="reservation-item" data-reservation-id="<?php echo $reservation['id']; ?>">
                                        <div class="reservation-info">
                                            <div class="event-title"><?php echo htmlspecialchars($reservation['event_title']); ?></div>
                                            <div class="seat-info">
                                                Koltuk: <?php echo chr(64 + $reservation['row_number']) . $reservation['seat_number']; ?>
                                                <?php if ($reservation['category_name']): ?>
                                                    - <?php echo htmlspecialchars($reservation['category_name']); ?>
                                                <?php endif; ?>
                                            </div>
                                            <div class="customer-info">
                                                <strong><?php echo htmlspecialchars($reservation['first_name'] . ' ' . $reservation['last_name']); ?></strong>
                                                <br>
                                                <small><?php echo htmlspecialchars($reservation['email']); ?></small>
                                                <?php if ($reservation['phone']): ?>
                                                    <br><small><?php echo htmlspecialchars($reservation['phone']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                            <div class="reservation-date">
                                                <?php echo date('d.m.Y H:i', strtotime($reservation['created_at'])); ?>
                                            </div>
                                        </div>
                                        <div class="reservation-actions">
                                            <button class="btn-approve" onclick="approveReservation(<?php echo $reservation['id']; ?>)">
                                                <i class="fas fa-check"></i> Onayla
                                            </button>
                                            <button class="btn-reject" onclick="rejectReservation(<?php echo $reservation['id']; ?>)">
                                                <i class="fas fa-times"></i> Reddet
                                            </button>
                                            <button class="btn-contact" onclick="contactCustomer('<?php echo $reservation['phone'] ?? $reservation['email']; ?>')">
                                                <i class="fas fa-phone"></i> İletişim
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Onaylanan Rezervasyonlar -->
                    <div id="approved-reservations" class="tab-content">
                        <div class="reservations-list" id="approved-list">
                            <!-- AJAX ile yüklenecek -->
                        </div>
                    </div>
                    
                    <!-- Reddedilen Rezervasyonlar -->
                    <div id="rejected-reservations" class="tab-content">
                        <div class="reservations-list" id="rejected-list">
                            <!-- AJAX ile yüklenecek -->
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sağ Sidebar -->
            <div class="right-sidebar">
                <!-- Engaged Users -->
                <div class="engaged-users-card">
                    <div class="engaged-title">Aktif kullanıcılar</div>
                    <div class="engaged-subtitle">Bugün</div>
                    
                    <div class="circle-chart">
                        <div class="circle-value"><?php echo $engagedUsers; ?></div>
                        <div class="circle-label">kullanıcı</div>
                    </div>
                    
                    <div class="engagement-stats">
                        <div class="engagement-item">
                            <div class="engagement-label">
                                <div class="engagement-dot blue"></div>
                                Etkinlik
                            </div>
                            <div class="engagement-value">%68</div>
                        </div>
                        <div class="engagement-item">
                            <div class="engagement-label">
                                <div class="engagement-dot purple"></div>
                                Satış
                            </div>
                            <div class="engagement-value">%22</div>
                        </div>
                        <div class="engagement-item">
                            <div class="engagement-label">
                                <div class="engagement-dot gray"></div>
                                Diğer
                            </div>
                            <div class="engagement-value">%11</div>
                        </div>
                    </div>
                </div>
                
                <!-- Top Buyers -->
                <div class="top-buyers-card">
                    <div class="card-header">
                        <h3 class="card-title">En Çok Satın Alanlar</h3>
                    </div>
                    
                    <div class="buyers-list">
                        <div class="buyer-item">
                            <div class="buyer-avatar">AK</div>
                            <div class="buyer-info">
                                <div class="buyer-name">Ahmet Kaya</div>
                                <div class="buyer-amount">₺2,450</div>
                            </div>
                            <div class="buyer-count">+12</div>
                        </div>
                        
                        <div class="buyer-item">
                            <div class="buyer-avatar">MÖ</div>
                            <div class="buyer-info">
                                <div class="buyer-name">Merve Özkan</div>
                                <div class="buyer-amount">₺1,890</div>
                            </div>
                            <div class="buyer-count">+8</div>
                        </div>
                        
                        <div class="buyer-item">
                            <div class="buyer-avatar">EY</div>
                            <div class="buyer-info">
                                <div class="buyer-name">Emre Yılmaz</div>
                                <div class="buyer-amount">₺1,650</div>
                            </div>
                            <div class="buyer-count">+5</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Mobile Menu Toggle
const mobileMenuToggle = document.getElementById('mobileMenuToggle');
const sidebar = document.getElementById('sidebar');
const mobileOverlay = document.getElementById('mobileOverlay');

function toggleMobileMenu() {
    sidebar.classList.toggle('mobile-open');
    mobileOverlay.classList.toggle('active');
    
    // Icon değiştir
    const icon = mobileMenuToggle.querySelector('i');
    if (sidebar.classList.contains('mobile-open')) {
        icon.className = 'fas fa-times';
    } else {
        icon.className = 'fas fa-bars';
    }
}

mobileMenuToggle.addEventListener('click', toggleMobileMenu);
mobileOverlay.addEventListener('click', toggleMobileMenu);

// Sidebar navigation
document.querySelectorAll('.nav-icon').forEach(icon => {
    icon.addEventListener('click', function() {
        document.querySelectorAll('.nav-icon').forEach(i => i.classList.remove('active'));
        this.classList.add('active');
        
        // Mobile'da menüyü kapat
        if (window.innerWidth <= 768) {
            toggleMobileMenu();
        }
    });
});

// Logout functionality
document.querySelector('.sidebar-logout').addEventListener('click', function() {
    if (confirm('Çıkış yapmak istediğinizden emin misiniz?')) {
        window.location.href = '../auth/logout.php';
    }
});

// Search functionality
document.querySelector('.search-input').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        // Arama fonksiyonalitesi buraya eklenecek
        console.log('Arama:', this.value);
    }
});

// Window resize handler
window.addEventListener('resize', function() {
    if (window.innerWidth > 768) {
        sidebar.classList.remove('mobile-open');
        mobileOverlay.classList.remove('active');
        mobileMenuToggle.querySelector('i').className = 'fas fa-bars';
    }
});

// Touch swipe to close menu
let startX = 0;
let currentX = 0;
let isDragging = false;

sidebar.addEventListener('touchstart', function(e) {
    startX = e.touches[0].clientX;
    isDragging = true;
});

sidebar.addEventListener('touchmove', function(e) {
    if (!isDragging) return;
    currentX = e.touches[0].clientX;
    const diffX = startX - currentX;
    
    if (diffX > 50) {
        toggleMobileMenu();
        isDragging = false;
    }
});

sidebar.addEventListener('touchend', function() {
    isDragging = false;
});

// Sayfa yükleme fonksiyonu (zaten var)
function loadPage(page) {
    const mainContent = document.getElementById('main-content');
    const navIcons = document.querySelectorAll('.nav-icon');
    
    navIcons.forEach(icon => icon.classList.remove('active'));
    event.target.closest('.nav-icon').classList.add('active');
    
    if (page === 'dashboard') {
        location.reload();
    } else if (page === 'qr_staff') {
        loadQRStaffPage();
    } else if (page === 'analytics') {
        loadAnalyticsPage();
    }
}

function loadQRStaffPage() {
    fetch('qr_staff_content.php')
        .then(response => response.text())
        .then(html => {
            document.getElementById('main-content').innerHTML = html;
            bindQRForms();
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('main-content').innerHTML = '<div class="error">Sayfa yüklenirken hata oluştu.</div>';
        });
}

function bindQRForms() {
    const container = document.getElementById('main-content');
    if (!container) return;

    container.querySelectorAll('form').forEach(form => {
        if (form.dataset.qrbound === '1') return;

        const isQRForm = form.querySelector('[name="create_staff"]') 
            || form.querySelector('[name="update_staff"]') 
            || form.querySelector('[name="delete_staff"]');
        if (!isQRForm) return;

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            fetch('qr_staff_content.php', {
                method: 'POST',
                body: new FormData(form)
            })
            .then(res => res.text())
            .then(html => {
                document.getElementById('main-content').innerHTML = html;
                bindQRForms();
            })
            .catch(err => console.error('QR form error:', err));
        }, { passive: false });

        form.dataset.qrbound = '1';
    });
}

// === Bildirimler ===
const notifBell = document.getElementById('notifBell');
const notifDropdown = document.getElementById('notifDropdown');
const notifBadge = document.getElementById('notifBadge');
const notifList = document.getElementById('notifList');
const notifEmpty = document.getElementById('notifEmpty');
const markAllNotif = document.getElementById('markAllNotif');

function toggleNotifDropdown() {
    if (!notifDropdown) return;
    const visible = notifDropdown.style.display === 'block';
    notifDropdown.style.display = visible ? 'none' : 'block';
}

async function fetchNotifications() {
    try {
        const res = await fetch('../ajax/notifications.php?action=list', { credentials: 'same-origin' });
        const data = await res.json();
        if (!data || !data.success) return;

        // Badge
        if (data.unread && data.unread > 0) {
            notifBadge.style.display = 'inline-block';
            notifBadge.textContent = data.unread > 99 ? '99+' : String(data.unread);
        } else {
            notifBadge.style.display = 'none';
        }

        // Liste
        notifList.innerHTML = '';
        if (!data.items || data.items.length === 0) {
            notifEmpty.style.display = 'block';
            return;
        }
        notifEmpty.style.display = 'none';

        data.items.forEach(n => {
            const card = document.createElement('div');
            card.style.border = n.is_read ? '1px solid #1f2937' : '1px solid #4f46e5';
            card.style.background = 'rgba(15,23,42,0.75)';
            card.style.borderRadius = '10px';
            card.style.padding = '10px';
            card.style.display = 'grid';
            card.style.gap = '6px';

            const row = document.createElement('div');
            row.style.display = 'flex';
            row.style.justifyContent = 'space-between';
            row.style.alignItems = 'center';
            const title = document.createElement('div');
            title.style.fontWeight = '700';
            title.textContent = n.title || 'Bildirim';
            const tag = document.createElement('span');
            tag.textContent = n.is_read ? 'Okundu' : 'Yeni';
            tag.style.fontSize = '11px';
            tag.style.border = '1px solid ' + (n.is_read ? '#1f2937' : '#4f46e5');
            tag.style.color = '#94a3b8';
            tag.style.padding = '2px 8px';
            tag.style.borderRadius = '999px';
            row.appendChild(title);
            row.appendChild(tag);

            const msg = document.createElement('div');
            msg.innerHTML = (n.message || '').replace(/\n/g, '<br>');

            const row2 = document.createElement('div');
            row2.style.display = 'flex';
            row2.style.justifyContent = 'space-between';
            row2.style.alignItems = 'center';
            const meta = document.createElement('div');
            meta.style.color = '#94a3b8';
            meta.style.fontSize = '12px';
            meta.textContent = (new Date(n.created_at)).toLocaleString('tr-TR');
            row2.appendChild(meta);

            if (!n.is_read) {
                const btn = document.createElement('button');
                btn.textContent = 'Okundu işaretle';
                btn.style.background = 'transparent';
                btn.style.border = '1px solid #334155';
                btn.style.color = '#e5e7eb';
                btn.style.borderRadius = '8px';
                btn.style.padding = '6px 8px';
                btn.style.cursor = 'pointer';
                btn.onclick = () => markNotificationRead(n.id, card);
                row2.appendChild(btn);
            }

            if (n.related_event_id) {
                const link = document.createElement('a');
                link.textContent = 'Etkinliği Gör';
                link.href = '../etkinlik-detay.php?id=' + encodeURIComponent(n.related_event_id);
                link.target = '_blank';
                link.style.color = '#93c5fd';
                link.style.textDecoration = 'none';
                link.style.marginLeft = '8px';
                meta.appendChild(document.createTextNode(' • '));
                meta.appendChild(link);
            }

            card.appendChild(row);
            card.appendChild(msg);
            card.appendChild(row2);
            notifList.appendChild(card);
        });
    } catch (e) {
        console.error('Bildirimler alınamadı', e);
    }
}

async function markNotificationRead(id, cardEl) {
    try {
        const res = await fetch('../ajax/notifications.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'},
            body: new URLSearchParams({action: 'mark_read', id})
        });
        const data = await res.json();
        if (data && data.success) {
            if (cardEl) {
                cardEl.style.border = '1px solid #1f2937';
                const tag = cardEl.querySelector('span');
                if (tag) {
                    tag.textContent = 'Okundu';
                    tag.style.borderColor = '#1f2937';
                }
                const btn = cardEl.querySelector('button');
                if (btn) btn.remove();
            }
            // Badge'i yeniden yükle
            fetchNotifications();
        }
    } catch (e) {
        console.error('Okundu işaretleme hatası', e);
    }
}

if (notifBell) {
    notifBell.addEventListener('click', (e) => {
        e.stopPropagation();
        toggleNotifDropdown();
    });
    document.addEventListener('click', (e) => {
        if (notifDropdown && notifDropdown.style.display === 'block') {
            // Dropdown dışında tıklama
            if (!notifBell.contains(e.target)) {
                notifDropdown.style.display = 'none';
            }
        }
    });
}
if (markAllNotif) {
    markAllNotif.addEventListener('click', async () => {
        try {
            const res = await fetch('../ajax/notifications.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'},
                body: new URLSearchParams({action: 'mark_all_read'})
            });
            const data = await res.json();
            if (data && data.success) {
                fetchNotifications();
            }
        } catch (e) {
            console.error('Tümünü okundu hatası', e);
        }
    });
}

// Periyodik olarak bildirimi yenile
fetchNotifications();
setInterval(fetchNotifications, 60000);

// Eksik olan analytics loader
function loadAnalyticsPage() {
    fetch('analytics_content.php')
        .then(r => r.text())
        .then(html => {
            document.getElementById('main-content').innerHTML = html;
        })
        .catch(() => {
            document.getElementById('main-content').innerHTML = '<div class="error">Analitik yüklenemedi.</div>';
        });
}
// Rezervasyon yönetimi fonksiyonları
function approveReservation(reservationId) {
    if (!confirm('Bu rezervasyonu onaylamak istediğinizden emin misiniz?\n\nOnaylandıktan sonra koltuk satıldı olarak işaretlenecek ve başka müşteriler tarafından alınamayacaktır.')) {
        return;
    }
    
    fetch('ajax/manage_reservation.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'approve',
            reservation_id: reservationId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Rezervasyon öğesini kaldır
            const reservationElement = document.querySelector(`[data-reservation-id="${reservationId}"]`);
            if (reservationElement) {
                reservationElement.remove();
            }
            
            // Sekme sayılarını güncelle
            updateTabCounts();
            
            // Başarı mesajı göster
            showSuccessMessage('Rezervasyon onaylandı! Koltuk artık satıldı olarak işaretlendi.');
        } else {
            alert('Hata: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Bir hata oluştu.');
    });
}

function rejectReservation(reservationId) {
    const reason = prompt('Rezervasyon reddetme sebebini belirtiniz (isteğe bağlı):\n\nReddedildikten sonra koltuk tekrar müsait hale gelecektir.');
    if (reason === null) return; // İptal edildi
    
    fetch('ajax/manage_reservation.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'reject',
            reservation_id: reservationId,
            notes: reason
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Rezervasyon öğesini kaldır
            const reservationElement = document.querySelector(`[data-reservation-id="${reservationId}"]`);
            if (reservationElement) {
                reservationElement.remove();
            }
            
            // Sekme sayılarını güncelle
            updateTabCounts();
            
            // Başarı mesajı göster
            showSuccessMessage('Rezervasyon reddedildi. Koltuk tekrar müsait hale geldi.');
        } else {
            alert('Hata: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Bir hata oluştu.');
    });
}

function contactCustomer(contact) {
    if (contact.includes('@')) {
        // Email
        window.location.href = 'mailto:' + contact;
    } else {
        // Telefon
        window.location.href = 'tel:' + contact;
    }
}

// Rezervasyon sekmelerini değiştir
function switchReservationTab(tabType) {
    // Tüm sekmeleri gizle
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Tüm butonları pasif yap
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Seçilen sekmeyi aktif yap
    document.getElementById(tabType + '-reservations').classList.add('active');
    event.target.classList.add('active');
    
    // Onaylanan veya reddedilen rezervasyonları yükle
    if (tabType === 'approved' || tabType === 'rejected') {
        loadReservations(tabType);
    }
}

// Rezervasyonları AJAX ile yükle
function loadReservations(status) {
    const listId = status + '-list';
    const listElement = document.getElementById(listId);
    
    listElement.innerHTML = '<div class="loading">Yükleniyor...</div>';
    
    fetch('ajax/get_reservations.php?status=' + status)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.reservations.length === 0) {
                    listElement.innerHTML = `
                        <div class="no-reservations">
                            <i class="fas fa-calendar-check"></i>
                            <p>${status === 'approved' ? 'Onaylanan' : 'Reddedilen'} rezervasyon bulunmuyor.</p>
                        </div>
                    `;
                } else {
                    let html = '';
                    data.reservations.forEach(reservation => {
                        const seatLabel = String.fromCharCode(64 + parseInt(reservation.row_number)) + reservation.seat_number;
                        const categoryText = reservation.category_name ? ' - ' + reservation.category_name : '';
                        const phoneText = reservation.phone ? '<br><small>' + reservation.phone + '</small>' : '';
                        const approvedDate = reservation.approved_at ? '<div class="approval-date">İşlem: ' + new Date(reservation.approved_at).toLocaleDateString('tr-TR') + ' ' + new Date(reservation.approved_at).toLocaleTimeString('tr-TR', {hour: '2-digit', minute: '2-digit'}) + '</div>' : '';
                        const notesText = reservation.notes ? '<div class="notes"><strong>Not:</strong> ' + reservation.notes + '</div>' : '';
                        
                        html += `
                            <div class="reservation-item ${status}">
                                <div class="reservation-info">
                                    <div class="event-title">${reservation.event_title}</div>
                                    <div class="seat-info">Koltuk: ${seatLabel}${categoryText}</div>
                                    <div class="customer-info">
                                        <strong>${reservation.first_name} ${reservation.last_name}</strong>
                                        <br><small>${reservation.email}</small>
                                        ${phoneText}
                                    </div>
                                    <div class="reservation-date">${new Date(reservation.created_at).toLocaleDateString('tr-TR')} ${new Date(reservation.created_at).toLocaleTimeString('tr-TR', {hour: '2-digit', minute: '2-digit'})}</div>
                                    ${approvedDate}
                                    ${notesText}
                                </div>
                                <div class="reservation-status">
                                    <span class="status-badge ${status}">
                                        <i class="fas fa-${status === 'approved' ? 'check' : 'times'}"></i>
                                        ${status === 'approved' ? 'Onaylandı' : 'Reddedildi'}
                                    </span>
                                </div>
                            </div>
                        `;
                    });
                    listElement.innerHTML = html;
                }
            } else {
                listElement.innerHTML = '<div class="error">Rezervasyonlar yüklenemedi.</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            listElement.innerHTML = '<div class="error">Bir hata oluştu.</div>';
        });
}

// Sekme sayılarını güncelle
function updateTabCounts() {
    fetch('ajax/get_reservation_counts.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Buton metinlerini güncelle
                const buttons = document.querySelectorAll('.tab-btn');
                buttons[0].textContent = `Bekleyen (${data.counts.pending})`;
                buttons[1].textContent = `Onaylanan (${data.counts.approved})`;
                buttons[2].textContent = `Reddedilen (${data.counts.rejected})`;
            }
        })
        .catch(error => console.error('Sayılar güncellenemedi:', error));
}

// Başarı mesajı göster
function showSuccessMessage(message) {
    // Basit bir alert yerine daha güzel bir bildirim sistemi
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #10b981;
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 10000;
        font-weight: 500;
        animation: slideIn 0.3s ease;
    `;
    notification.textContent = message;
    
    // CSS animasyonu ekle
    if (!document.querySelector('#notification-style')) {
        const style = document.createElement('style');
        style.id = 'notification-style';
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
        `;
        document.head.appendChild(style);
    }
    
    document.body.appendChild(notification);
    
    // 3 saniye sonra kaldır
    setTimeout(() => {
        notification.style.animation = 'slideIn 0.3s ease reverse';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// Sidebar toggle fonksiyonu - sadece mobilde çalışır
function toggleSidebar() {
    // Sadece mobil cihazlarda çalışsın
    if (window.innerWidth > 768) {
        return;
    }
    
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    const floatingToggle = document.getElementById('floatingToggle');
    
    const isOpen = sidebar.classList.contains('open');
    
    if (isOpen) {
        // Sidebar'ı kapat
        sidebar.classList.remove('open');
        sidebar.classList.add('collapsed');
        mainContent.classList.remove('collapsed');
        mainContent.classList.add('expanded');
        floatingToggle.classList.remove('hide');
        floatingToggle.classList.add('show');
    } else {
        // Sidebar'ı aç
        sidebar.classList.remove('collapsed');
        sidebar.classList.add('open');
        mainContent.classList.remove('expanded');
        mainContent.classList.add('collapsed');
        floatingToggle.classList.remove('show');
        floatingToggle.classList.add('hide');
    }
}

// Sayfa yüklendiğinde mobil kontrolü - Her sayfa değişiminde sidebar kapalı gelsin
function initializeSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    const floatingToggle = document.getElementById('floatingToggle');
    
    if (window.innerWidth <= 768) {
        // Mobilde her zaman kapalı başlasın
        sidebar.classList.remove('open');
        sidebar.classList.add('collapsed');
        mainContent.classList.remove('collapsed');
        mainContent.classList.add('expanded');
        floatingToggle.classList.remove('hide');
        floatingToggle.classList.add('show');
    } else {
        // Masaüstünde normal durum
        sidebar.classList.remove('collapsed', 'open');
        mainContent.classList.remove('expanded', 'collapsed');
        floatingToggle.classList.remove('show');
        floatingToggle.classList.add('hide');
    }
}

// Ekran boyutu değiştiğinde kontrol et
window.addEventListener('resize', function() {
    initializeSidebar();
});

// Sayfa yüklendiğinde başlat
document.addEventListener('DOMContentLoaded', function() {
    initializeSidebar();
});

</script>

<style>
/* Tema ve mobil iyileştirme */
:root {
    --accent: #6C5CE7;
    --accent-2: #00C2A8;
}

.nav-icon.active i { color: var(--accent); }
.right-sidebar .engagement-dot.blue { background: var(--accent); }
.btn-primary, .btn-success { background: var(--accent); border-color: var(--accent); }
.btn-primary:hover, .btn-success:hover { background: #5a4dd6; }

/* Rezervasyon Sekmeleri */
.reservation-tabs {
    display: flex;
    gap: 8px;
    background: blueviolet;
    margin-bottom: 16px;
}

.tab-btn {
    padding: 8px 16px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    background: rgba(255, 255, 255, 0.1);
    color: #fff;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 14px;
    font-weight: 500;
}

.tab-btn:hover {
    background: rgba(255, 255, 255, 0.2);
}

.tab-btn.active {
    background: var(--accent);
    border-color: var(--accent);
    color: #fff;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.reservation-item.approved {
    border-left: 4px solid #10b981;
}

.reservation-item.rejected {
    border-left: 4px solid #ef4444;
}

.reservation-status {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 120px;
}

.status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    color: white;
    display: flex;
    align-items: center;
    gap: 4px;
}

.status-badge.approved {
    background: #10b981;
}

.status-badge.rejected {
    background: #ef4444;
}

.approval-date {
    font-size: 12px;
    color: #6b7280;
    margin-top: 4px;
}

.notes {
    font-size: 12px;
    color: #374151;
    margin-top: 4px;
    padding: 4px 8px;
    background: rgba(0, 0, 0, 0.05);
    border-radius: 4px;
}

.loading {
    text-align: center;
    padding: 20px;
    color: #6b7280;
}

.error {
    text-align: center;
    padding: 20px;
    color: #ef4444;
}

@media (max-width: 768px) {
    /* Genel container ayarları */
    body {
        overflow-x: hidden;
    }
    
    .top-header { 
        flex-direction: column; 
        gap: 12px;
        width: 100%;
        box-sizing: border-box;
        padding: 15px;
    }
    
    .search-container { 
        width: 100%;
        box-sizing: border-box;
    }
    
    .modern-sidebar {
        width: 60px;
        margin-left: -15px;
    }
    
    .main-content {
        margin-left: 0px !important;
        padding: 10px !important;
        width: 100%;
        box-sizing: border-box;
        overflow-x: hidden;
    }
    
    .dashboard-content {
        background: #3635b1eb;
        width: 100%;
        box-sizing: border-box;
        padding: 15px;
    }
    
    /* Stats row mobil uyumluluk */
    .stats-row {
        grid-template-columns: 1fr;
        gap: 10px;
        width: 100%;
        box-sizing: border-box;
        padding: 0;
    }
    
    .stat-card {
        width: 100%;
        box-sizing: border-box;
        padding: 15px;
    }
    
    /* Main grid mobil uyumluluk */
    .main-grid {
        grid-template-columns: 1fr;
        gap: 15px;
        width: 100%;
        box-sizing: border-box;
    }
    
    /* Analytics card mobil uyumluluk */
    .analytics-card {
        width: 100%;
        box-sizing: border-box;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    /* Right sidebar mobil uyumluluk */
    .right-sidebar {
        width: 100%;
        box-sizing: border-box;
    }
    
    .reservation-tabs {
        flex-direction: column;
        gap: 4px;
        width: 100%;
    }
    
    .tab-btn {
        text-align: center;
        width: 100%;
        box-sizing: border-box;
    }
    
    /* Rezervasyon container */
    .reservations-container {
        width: 100%;
        box-sizing: border-box;
        overflow-x: hidden;
    }
    
    .reservation-item {
        width: 100%;
        box-sizing: border-box;
    }
}
</style>
</body>
</html>
