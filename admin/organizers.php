<?php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../classes/Organizer.php';

// Admin kontrolü
requireAdmin();

$database = new Database();
$pdo = $database->getConnection();
$organizer = new Organizer($pdo);
$message = '';
$messageType = '';

// Organizatör onaylama/reddetme işlemleri
if (isset($_GET['action']) && isset($_GET['id'])) {
    $userId = (int)$_GET['id'];
    
    if ($_GET['action'] === 'approve') {
        if ($organizer->approveOrganizer($userId)) {
            // Debug: Onay sonrası durumu kontrol et
            $database = new Database();
            $pdo = $database->getConnection();
            $stmt = $pdo->prepare("SELECT u.status, u.email_verified, od.approval_status FROM users u JOIN organizer_details od ON u.id = od.user_id WHERE u.id = ?");
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            $message = 'Organizatör başarıyla onaylandı. Status: ' . $result['status'] . ', Email Verified: ' . $result['email_verified'] . ', Approval: ' . $result['approval_status'];
            $messageType = 'success';
            
            header('Location: organizers.php?approved=1');
            exit();
        } else {
            $message = 'Organizatör onaylanırken hata oluştu.';
            $messageType = 'error';
        }
    } elseif ($_GET['action'] === 'reject') {
        if ($organizer->rejectOrganizer($userId)) {
            $message = 'Organizatör başvurusu reddedildi.';
            $messageType = 'success';
        } else {
            $message = 'Organizatör reddedilirken hata oluştu.';
            $messageType = 'error';
        }
    }
}

// Organizatörleri getir
$pendingOrganizers = $organizer->getPendingOrganizers();
$approvedOrganizers = $organizer->getApprovedOrganizers();
$rejectedOrganizers = $organizer->getRejectedOrganizers();

include 'includes/header.php';
?>

<div class="admin-container">
    <!-- Ultra Modern Sidebar -->
    <div class="admin-sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <img src="../uploads/logo.png" alt="BiletJack Logo" style="width: 120px; height: 120px; object-fit: contain;">
            </div>
            <h2 class="sidebar-title">Organizatörler</h2>
            <p class="sidebar-subtitle">Admin Dashboard</p>
        </div>
        
        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">Ana Menü</div>
                <a href="index.php" class="nav-item">
                    <i class="fas fa-chart-pie"></i>    
                    Gösterge Paneli
                </a>
                <a href="analytics.php" class="nav-item">
                    <i class="fas fa-chart-line"></i>
                    Analytics
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Yönetim</div>
                <a href="users.php" class="nav-item">
                    <i class="fas fa-users"></i>
                    Kullanıcılar
                </a>
                <a href="organizers.php" class="nav-item active">
                    <i class="fas fa-building"></i>
                    Organizatörler
                    <?php if (count($pendingOrganizers) > 0): ?>
                        <span class="nav-badge"><?php echo count($pendingOrganizers); ?></span>
                    <?php endif; ?>
                </a>
                <a href="events.php" class="nav-item">
                    <i class="fas fa-calendar-alt"></i>
                    Etkinlikler
                </a>
                <a href="orders.php" class="nav-item">
                    <i class="fas fa-shopping-cart"></i>
                    Siparişler
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Sistem</div>
                <a href="settings.php" class="nav-item">
                    <i class="fas fa-cog"></i>
                    Ayarlar
                </a>
                <a href="reports.php" class="nav-item">
                    <i class="fas fa-file-alt"></i>
                    Raporlar
                </a>
                <a href="../index.php" class="nav-item">
                    <i class="fas fa-home"></i>
                    Ana Sayfa
                </a>
            </div>
        </nav>
    </div>
    
    <!-- Main Content -->
    <div class="admin-content">
        <!-- Modern Header -->
        <div class="content-header">
            <div class="header-left">
                <button class="mobile-menu-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div>
                    <h1 class="page-title">Organizatör Yönetimi</h1>
                    <p class="page-subtitle">Organizatör başvurularını yönetin ve onaylayın</p>
                </div>
            </div>
            
            <div class="header-right">
                <button class="header-notifications">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge"></span>
                </button>
                
                <a href="../auth/logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    Çıkış
                </a>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="tabs">
            <div class="tab-buttons">
                <button class="tab-btn active" data-tab="pending">Bekleyen (<?php echo count($pendingOrganizers); ?>)</button>
                <button class="tab-btn" data-tab="approved">Onaylanan (<?php echo count($approvedOrganizers); ?>)</button>
                <button class="tab-btn" data-tab="rejected">Reddedilen (<?php echo count($rejectedOrganizers); ?>)</button>
            </div>
            
            <div class="tab-content">
                <!-- Bekleyen Organizatörler -->
                <div class="tab-pane active" id="pending">
                    <div class="card">
                        <div class="card-header">
                            <h3>Bekleyen Organizatör Başvuruları</h3>
                        </div>
                        <div class="card-body">
                            <?php if (count($pendingOrganizers) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Şirket Adı</th>
                                                <th>İletişim Kişisi</th>
                                                <th>E-posta</th>
                                                <th>Telefon</th>
                                                <th>Başvuru Tarihi</th>
                                                <th>İşlemler</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($pendingOrganizers as $org): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($org['company_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($org['first_name'] . ' ' . $org['last_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($org['email']); ?></td>
                                                    <td><?php echo htmlspecialchars($org['phone']); ?></td>
                                                    <td><?php echo date('d.m.Y H:i', strtotime($org['created_at'])); ?></td>
                                                    <td>
                                                        <div class="action-buttons">
                                                            <button class="btn btn-sm btn-info" onclick="viewOrganizerDetails(<?php echo $org['user_id']; ?>)">Detay</button>
                                                            <a href="?action=approve&id=<?php echo $org['user_id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Bu organizatörü onaylamak istediğinizden emin misiniz?')">Onayla</a>
                                                            <a href="?action=reject&id=<?php echo $org['user_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu organizatör başvurusunu reddetmek istediğinizden emin misiniz?')">Reddet</a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="no-data">Bekleyen organizatör başvurusu bulunmuyor.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Onaylanan Organizatörler -->
                <div class="tab-pane" id="approved">
                    <div class="card">
                        <div class="card-header">
                            <h3>Onaylanan Organizatörler</h3>
                        </div>
                        <div class="card-body">
                            <?php if (count($approvedOrganizers) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Şirket Adı</th>
                                                <th>İletişim Kişisi</th>
                                                <th>E-posta</th>
                                                <th>Telefon</th>
                                                <th>Onay Tarihi</th>
                                                <th>İşlemler</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($approvedOrganizers as $org): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($org['company_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($org['first_name'] . ' ' . $org['last_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($org['email']); ?></td>
                                                    <td><?php echo htmlspecialchars($org['phone']); ?></td>
                                                    <td><?php echo date('d.m.Y H:i', strtotime($org['updated_at'])); ?></td>
                                                    <td>
                                                        <div class="action-buttons">
                                                            <button class="btn btn-sm btn-info" onclick="viewOrganizerDetails(<?php echo $org['user_id']; ?>)">Detay</button>
                                                            <button class="btn btn-sm btn-warning" onclick="suspendOrganizer(<?php echo $org['user_id']; ?>)">Askıya Al</button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="no-data">Onaylanan organizatör bulunmuyor.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Reddedilen Organizatörler -->
                <div class="tab-pane" id="rejected">
                    <div class="card">
                        <div class="card-header">
                            <h3>Reddedilen Organizatör Başvuruları</h3>
                        </div>
                        <div class="card-body">
                            <?php if (count($rejectedOrganizers) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Şirket Adı</th>
                                                <th>İletişim Kişisi</th>
                                                <th>E-posta</th>
                                                <th>Red Tarihi</th>
                                                <th>İşlemler</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($rejectedOrganizers as $org): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($org['company_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($org['first_name'] . ' ' . $org['last_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($org['email']); ?></td>
                                                    <td><?php echo date('d.m.Y H:i', strtotime($org['updated_at'])); ?></td>
                                                    <td>
                                                        <div class="action-buttons">
                                                            <button class="btn btn-sm btn-info" onclick="viewOrganizerDetails(<?php echo $org['user_id']; ?>)">Detay</button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="no-data">Reddedilen organizatör başvurusu bulunmuyor.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Organizatör Detay Modal -->
<div id="organizerDetailModal" class="modal">
    <div class="modal-overlay" onclick="closeModal('organizerDetailModal')"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h2>Organizatör Detayları</h2>
            <button class="modal-close" onclick="closeModal('organizerDetailModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body" id="organizerDetailContent">
            <!-- Detay içeriği AJAX ile yüklenecek -->
        </div>
    </div>
</div>

<script>
// Tab işlevselliği
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const tabId = this.dataset.tab;
        
        // Aktif tab butonunu güncelle
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        // Aktif tab içeriğini güncelle
        document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));
        document.getElementById(tabId).classList.add('active');
    });
});

// Organizatör detaylarını görüntüle
function viewOrganizerDetails(userId) {
    // AJAX ile organizatör detaylarını getir
    fetch(`ajax/get_organizer_details.php?id=${userId}`)
        .then(response => response.text())
        .then(data => {
            document.getElementById('organizerDetailContent').innerHTML = data;
            openModal('organizerDetailModal');
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Detaylar yüklenirken hata oluştu.');
        });
}

// Modal işlevleri
function openModal(modalId) {
    document.getElementById(modalId).classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
    document.body.style.overflow = 'auto';
}

// KALDIRILDI: Mobile menu toggle inline kodu
// document.querySelector('.mobile-menu-toggle').addEventListener('click', function() {
//     document.querySelector('.admin-sidebar').classList.toggle('mobile-open');
// });
</script>

<?php include 'includes/footer.php'; ?>