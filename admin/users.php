<?php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../classes/User.php';
require_once '../classes/Organizer.php';

// Admin kontrolü
requireAdmin();

// Database bağlantısını oluştur
$database = new Database();
$pdo = $database->getConnection();

// Class'ları database bağlantısı ile başlat
$user = new User($pdo);
$organizer = new Organizer($pdo);

// Mesaj değişkenleri
$message = '';
$messageType = '';

// Kullanıcı işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_user':
                $user->email = $_POST['email'];
                $user->password = $_POST['password'];
                $user->first_name = $_POST['first_name'];
                $user->last_name = $_POST['last_name'];
                $user->phone = $_POST['phone'];
                $user->user_type = $_POST['user_type'];
                $user->status = 'active';
                
                if (!$user->emailExists()) {
                    if ($user->register()) {
                        $message = 'Kullanıcı başarıyla eklendi!';
                        $messageType = 'success';
                    } else {
                        $message = 'Kullanıcı eklenirken hata oluştu!';
                        $messageType = 'error';
                    }
                } else {
                    $message = 'Bu e-posta adresi zaten kullanılıyor!';
                    $messageType = 'error';
                }
                break;
                
            case 'update_status':
                $userId = $_POST['user_id'];
                $newStatus = $_POST['status'];
                
                $query = "UPDATE users SET status = :status WHERE id = :id";
                $stmt = $pdo->prepare($query);
                $stmt->bindParam(':status', $newStatus);
                $stmt->bindParam(':id', $userId);
                
                if ($stmt->execute()) {
                    $message = 'Kullanıcı durumu güncellendi!';
                    $messageType = 'success';
                } else {
                    $message = 'Durum güncellenirken hata oluştu!';
                    $messageType = 'error';
                }
                break;
                
            case 'delete_user':
                $userId = $_POST['user_id'];
                
                // Önce organizatör detaylarını sil
                $deleteOrgQuery = "DELETE FROM organizer_details WHERE user_id = :id";
                $stmt = $pdo->prepare($deleteOrgQuery);
                $stmt->bindParam(':id', $userId);
                $stmt->execute();
                
                // Sonra kullanıcıyı sil
                $deleteUserQuery = "DELETE FROM users WHERE id = :id";
                $stmt = $pdo->prepare($deleteUserQuery);
                $stmt->bindParam(':id', $userId);
                
                if ($stmt->execute()) {
                    $message = 'Kullanıcı başarıyla silindi!';
                    $messageType = 'success';
                } else {
                    $message = 'Kullanıcı silinirken hata oluştu!';
                    $messageType = 'error';
                }
                break;
        }
    }
}

// Arama ve filtreleme
$search = isset($_GET['search']) ? $_GET['search'] : '';
$userTypeFilter = isset($_GET['user_type']) ? $_GET['user_type'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$eventFilter = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Seçilen etkinlik bilgisini getir
$selectedEvent = null;
if ($eventFilter > 0) {
    $eventQuery = "SELECT title FROM events WHERE id = :event_id";
    $eventStmt = $pdo->prepare($eventQuery);
    $eventStmt->bindValue(':event_id', $eventFilter, PDO::PARAM_INT);
    $eventStmt->execute();
    $selectedEvent = $eventStmt->fetch(PDO::FETCH_ASSOC);
}

// Kullanıcıları getir
$whereConditions = [];
$params = [];
$joinClause = '';

if (!empty($search)) {
    $whereConditions[] = "(u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($userTypeFilter)) {
    $whereConditions[] = "u.user_type = :user_type";
    $params[':user_type'] = $userTypeFilter;
}

if (!empty($statusFilter)) {
    $whereConditions[] = "u.status = :status";
    $params[':status'] = $statusFilter;
}

if ($eventFilter > 0) {
    $joinClause = "INNER JOIN orders o ON u.id = o.user_id INNER JOIN tickets t ON o.id = t.order_id";
    $whereConditions[] = "t.event_id = :event_id";
    $params[':event_id'] = $eventFilter;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Toplam kullanıcı sayısı
$countQuery = "SELECT COUNT(DISTINCT u.id) as total FROM users u $joinClause $whereClause";
$countStmt = $pdo->prepare($countQuery);
foreach ($params as $key => $value) {
    $countStmt->bindValue($key, $value);
}
$countStmt->execute();
$totalUsers = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalUsers / $limit);

// Kullanıcıları getir
$query = "SELECT DISTINCT u.*, 
                 CASE 
                     WHEN u.user_type = 'organizer' THEN od.approval_status 
                     ELSE NULL 
                 END as organizer_status,
                 u.whatsapp_verified,
                 u.email_verified,
                 u.google_id
          FROM users u 
          $joinClause
          LEFT JOIN organizer_details od ON u.id = od.user_id 
          $whereClause 
          ORDER BY u.created_at DESC 
          LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// İstatistikler
$totalUsersCount = $user->getTotalUsers();
$adminCount = $user->getUserCountByType('admin');
$organizerCount = $user->getUserCountByType('organizer');
$customerCount = $user->getUserCountByType('customer');
$pendingOrganizers = $organizer->getPendingOrganizers();

include 'includes/header.php';
?>

<div class="admin-container">
    <!-- Ultra Modern Sidebar -->
    <div class="admin-sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <img src="../uploads/logo.png" alt="BiletJack Logo" style="width: 120px; height: 120px; object-fit: contain;">
            </div>
            <h2 class="sidebar-title">Kullanıcılar</h2>
            <p class="sidebar-subtitle">Admin Dashboard</p>
        </div>
        
        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">Ana Menü</div>
                <a href="index.php" class="nav-item">
                    <i class="fas fa-chart-pie"></i>
                    Dashboard
                </a>
                <a href="analytics.php" class="nav-item">
                    <i class="fas fa-chart-line"></i>
                    Analytics
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Yönetim</div>
                <a href="users.php" class="nav-item active">
                    <i class="fas fa-users"></i>
                    Kullanıcılar
                </a>
                <a href="organizers.php" class="nav-item">
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
                    <h1 class="page-title">Kullanıcı Yönetimi</h1>
                    <p class="page-subtitle">
                        <?php if ($selectedEvent): ?>
                            "<?php echo htmlspecialchars($selectedEvent['title']); ?>" etkinliği katılımcıları
                        <?php else: ?>
                            Sistem kullanıcılarını yönetin ve düzenleyin
                        <?php endif; ?>
                    </p>
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
        
        <!-- Users Content -->
        <div class="users-container">
            <!-- Message Display -->
            <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?> fade-in">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo $message; ?>
                <button class="alert-close" onclick="this.parentElement.style.display='none'">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <?php endif; ?>
            
            <!-- User Statistics -->
            <div class="user-stats fade-in">
                <div class="stat-card total">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo number_format($totalUsersCount); ?></div>
                        <div class="stat-label">Toplam Kullanıcı</div>
                    </div>
                </div>
                
                <div class="stat-card admin">
                    <div class="stat-icon">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo number_format($adminCount); ?></div>
                        <div class="stat-label">Admin</div>
                    </div>
                </div>
                
                <div class="stat-card organizer">
                    <div class="stat-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo number_format($organizerCount); ?></div>
                        <div class="stat-label">Organizatör</div>
                    </div>
                </div>
                
                <div class="stat-card customer">
                    <div class="stat-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo number_format($customerCount); ?></div>
                        <div class="stat-label">Müşteri</div>
                    </div>
                </div>
            </div>
            
            <!-- User Management Controls -->
            <div class="user-controls fade-in">
                <div class="controls-left">
                    <button class="btn btn-primary" onclick="openAddUserModal()">
                        <i class="fas fa-plus"></i>
                        Yeni Kullanıcı
                    </button>
                    
                    <div class="bulk-actions">
                        <select class="bulk-select" id="bulkAction">
                            <option value="">Toplu İşlem Seç</option>
                            <option value="activate">Aktifleştir</option>
                            <option value="deactivate">Pasifleştir</option>
                            <option value="delete">Sil</option>
                        </select>
                        <button class="btn btn-secondary" onclick="executeBulkAction()">
                            Uygula
                        </button>
                    </div>
                </div>
                
                <div class="controls-right">
                    <form method="GET" class="filter-form">
                        <?php if ($eventFilter): ?>
                            <input type="hidden" name="event_id" value="<?php echo $eventFilter; ?>">
                        <?php endif; ?>
                        
                        <div class="filter-group">
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Ad, soyad veya e-posta ara..." class="search-input">
                        </div>
                        
                        <div class="filter-group">
                            <select name="user_type" class="filter-select">
                                <option value="">Tüm Tipler</option>
                                <option value="admin" <?php echo $userTypeFilter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                <option value="organizer" <?php echo $userTypeFilter === 'organizer' ? 'selected' : ''; ?>>Organizatör</option>
                                <option value="customer" <?php echo $userTypeFilter === 'customer' ? 'selected' : ''; ?>>Müşteri</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <select name="status" class="filter-select">
                                <option value="">Tüm Durumlar</option>
                                <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Aktif</option>
                                <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Pasif</option>
                                <option value="suspended" <?php echo $statusFilter === 'suspended' ? 'selected' : ''; ?>>Askıya Alınmış</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-secondary">
                            <i class="fas fa-filter"></i>
                            Filtrele
                        </button>
                        
                        <?php if ($eventFilter): ?>
                            <a href="users.php" class="btn btn-outline">
                                <i class="fas fa-times"></i>
                                Etkinlik Filtresini Kaldır
                            </a>
                        <?php else: ?>
                            <a href="users.php" class="btn btn-outline">
                                <i class="fas fa-times"></i>
                                Temizle
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            
            <!-- Users Table -->
            <div class="users-table-container fade-in">
                <div class="table-header">
                    <h3>Kullanıcı Listesi</h3>
                    <div class="table-actions">
                        <button class="btn btn-outline" onclick="exportUsers()">
                            <i class="fas fa-download"></i>
                            Dışa Aktar
                        </button>
                        <button class="btn btn-outline" onclick="refreshTable()">
                            <i class="fas fa-sync-alt"></i>
                            Yenile
                        </button>
                    </div>
                </div>
                
                <div class="table-content">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>
                                    <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                                </th>
                                <th>Kullanıcı</th>
                                <th>E-posta</th>
                                <th>E-posta Doğrulama</th>
                                <th>Telefon</th>
                                <th>Tip</th>
                                <th>Durum</th>
                                <th>Kayıt Tarihi</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="9" class="no-data">
                                    <div class="no-data-content">
                                        <i class="fas fa-users"></i>
                                        <h3>Kullanıcı bulunamadı</h3>
                                        <p>Arama kriterlerinize uygun kullanıcı bulunmuyor.</p>
                                    </div>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($users as $userData): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" class="user-checkbox" value="<?php echo $userData['id']; ?>">
                                    </td>
                                    <td>
                                        <div class="user-cell">
                                            <div class="user-avatar">
                                                <?php echo strtoupper(substr($userData['first_name'], 0, 1) . substr($userData['last_name'], 0, 1)); ?>
                                            </div>
                                            <div class="user-info">
                                                <div class="user-name">
                                                    <?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?>
                                                    <?php if (!empty($userData['google_id'])): ?>
                                                        <span class="google-badge" title="Google ile kayıt olmuş">
                                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                                                                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                                                                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                                                                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                                                            </svg>
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($userData['whatsapp_verified']) && $userData['whatsapp_verified'] == 1): ?>
                                                        <span class="whatsapp-badge" title="WhatsApp ile kayıt olmuş">
                                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="#25D366" xmlns="http://www.w3.org/2000/svg">
                                                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.893 3.488"/>
                                                            </svg>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="user-id">ID: <?php echo $userData['id']; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($userData['email']); ?></td>
                                    <td>
                                        <?php if ($userData['email_verified']): ?>
                                            <span class="verification-badge verified" title="E-posta doğrulanmış">
                                                <i class="fas fa-check-circle"></i>
                                                Doğrulanmış
                                            </span>
                                        <?php else: ?>
                                            <span class="verification-badge not-verified" title="E-posta doğrulanmamış">
                                                <i class="fas fa-times-circle"></i>
                                                Doğrulanmamış
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($userData['phone'] ?? '-'); ?></td>
                                    <td>
                                        <td>
                                            <span class="user-type-badge <?php echo $userData['user_type']; ?>">
                                                <?php 
                                                $typeLabels = [
                                                    'admin' => 'Admin',
                                                    'organizer' => 'Organizatör',
                                                    'customer' => 'Müşteri',
                                                    'service' => 'Servis',
                                                    'ad_agency' => 'Reklam Ajansı'
                                                ];
                                                echo $typeLabels[$userData['user_type']] ?? $userData['user_type'];
                                                ?>
                                            </span>
                                            <?php if ($userData['user_type'] === 'organizer' && $userData['organizer_status']): ?>
                                                <span class="organizer-status <?php echo $userData['organizer_status']; ?>">
                                                    <?php 
                                                    $statusLabels = [
                                                        'pending' => 'Beklemede',
                                                        'approved' => 'Onaylı',
                                                        'rejected' => 'Reddedildi'
                                                    ];
                                                    echo $statusLabels[$userData['organizer_status']] ?? $userData['organizer_status'];
                                                    ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    <td>
                                        <span class="status-badge <?php echo $userData['status']; ?>">
                                            <?php 
                                            $statusLabels = [
                                                'active' => 'Aktif',
                                                'inactive' => 'Pasif',
                                                'suspended' => 'Askıya Alınmış'
                                            ];
                                            echo $statusLabels[$userData['status']] ?? $userData['status'];
                                            ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($userData['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-action edit" onclick="editUser(<?php echo $userData['id']; ?>)" title="Düzenle">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            
                                            <div class="users-dropdown">
                                                <button class="btn-action more" onclick="toggleDropdown(<?php echo $userData['id']; ?>)" title="Daha Fazla">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <div class="users-dropdown-menu" id="dropdown-<?php echo $userData['id']; ?>">
                                                    <?php if (!$userData['email_verified'] && $userData['user_type'] === 'customer'): ?>
                                                    <button onclick="verifyEmailManually(<?php echo $userData['id']; ?>)" class="verify-email">
                                                        <i class="fas fa-envelope-check"></i> E-posta Doğrula
                                                    </button>
                                                    <hr>
                                                    <?php endif; ?>
                                                    <button onclick="changeStatus(<?php echo $userData['id']; ?>, 'active')">
                                                        <i class="fas fa-check"></i> Aktifleştir
                                                    </button>
                                                    <button onclick="changeStatus(<?php echo $userData['id']; ?>, 'inactive')">
                                                        <i class="fas fa-pause"></i> Pasifleştir
                                                    </button>
                                                    <button onclick="changeStatus(<?php echo $userData['id']; ?>, 'suspended')">
                                                        <i class="fas fa-ban"></i> Askıya Al
                                                    </button>
                                                    <hr>
                                                    <button onclick="deleteUser(<?php echo $userData['id']; ?>)" class="danger">
                                                        <i class="fas fa-trash"></i> Sil
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <div class="pagination-info">
                        Toplam <?php echo number_format($totalUsers); ?> kullanıcıdan 
                        <?php echo (($page - 1) * $limit) + 1; ?>-<?php echo min($page * $limit, $totalUsers); ?> arası gösteriliyor
                    </div>
                    
                    <div class="pagination-controls">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&user_type=<?php echo urlencode($userTypeFilter); ?>&status=<?php echo urlencode($statusFilter); ?>" 
                               class="pagination-btn">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&user_type=<?php echo urlencode($userTypeFilter); ?>&status=<?php echo urlencode($statusFilter); ?>" 
                               class="pagination-btn <?php echo $i === $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&user_type=<?php echo urlencode($userTypeFilter); ?>&status=<?php echo urlencode($statusFilter); ?>" 
                               class="pagination-btn">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal" id="addUserModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Yeni Kullanıcı Ekle</h3>
            <button class="modal-close" onclick="closeAddUserModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST" class="modal-form">
            <input type="hidden" name="action" value="add_user">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">Ad</label>
                    <input type="text" id="first_name" name="first_name" required>
                </div>
                
                <div class="form-group">
                    <label for="last_name">Soyad</label>
                    <input type="text" id="last_name" name="last_name" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">E-posta</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="phone">Telefon</label>
                <input type="tel" id="phone" name="phone">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="user_type">Kullanıcı Tipi</label>
                    <select id="user_type" name="user_type" required>
                        <option value="customer">Müşteri</option>
                        <option value="organizer">Organizatör</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="password">Şifre</label>
                    <!-- Yönetici kullanıcı oluşturma şifresi -->
                    <input type="password" id="password" name="password" required minlength="6" class="pw-meter" data-require-strength="medium">
                    <div class="pw-strength" style="margin-top:6px;">
                        <div class="pw-bar" style="height:6px;width:0%;background:#ddd;border-radius:4px;transition:width .2s ease;"></div>
                        <div class="pw-text" style="margin-top:6px;font-size:12px;color:#666;">Şifre gücü: - (En az orta seviye gerekir. 8+ karakter, en az iki tür: küçük/büyük/rakam)</div>
                    </div>
                </div>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeAddUserModal()">
                    İptal
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    Kullanıcı Ekle
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function(){function lvl(p){if(!p)return'e';const L=p.length,lo=/[a-z]/.test(p),up=/[A-Z]/.test(p),di=/\d/.test(p),k=[lo,up,di].filter(Boolean).length;if(L>=10&&k>=3)return's';if(L>=8&&k>=2)return'm';if(L>0)return'w';return'e';}
function ui(c,l){const b=c.querySelector('.pw-bar'),t=c.querySelector('.pw-text');if(!b||!t)return;let w='0%',col='#ddd',tx='Şifre gücü: -';if(l==='w'){w='33%';col='#e74c3c';tx='Şifre gücü: Zayıf';}if(l==='m'){w='66%';col:'#f39c12';tx='Şifre gücü: Orta';}if(l==='s'){w='100%';col='#27ae60';tx='Şifre gücü: Güçlü';}b.style.width=w;b.style.background=col;t.textContent=tx+' (En az orta seviye gerekir. 8+ karakter, en az iki tür: küçük/büyük/rakam)';t.style.color=l==='w'?'#e74c3c':'#666';}
function attach(i){const c=i.parentElement.querySelector('.pw-strength');if(!c)return;function v(){const l=lvl(i.value||'');ui(c,l);if(i.value){if(!(l==='m'||l==='s')){i.setCustomValidity('Lütfen en az orta seviye bir şifre girin.');}else{i.setCustomValidity('');}}else{i.setCustomValidity('');}}i.addEventListener('input',v);v();}
(document.querySelectorAll('input.pw-meter')||[]).forEach(attach);})();
</script>
</div>
</div>
</div>
</div>

<!-- JavaScript -->
<script>
// User Management JavaScript
class UserManagement {
    constructor() {
        this.selectedUsers = [];
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.initTooltips();
    }
    
    bindEvents() {
        // Close dropdowns when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.users-dropdown')) {
                document.querySelectorAll('.users-dropdown-menu').forEach(menu => {
                    menu.style.display = 'none';
                });
            }
        });
        
        // Real-time search
        const searchInput = document.querySelector('.search-input');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.performSearch(e.target.value);
                }, 500);
            });
        }
    }
    
    initTooltips() {
        // Initialize tooltips for action buttons
        document.querySelectorAll('[title]').forEach(element => {
            element.addEventListener('mouseenter', this.showTooltip);
            element.addEventListener('mouseleave', this.hideTooltip);
        });
    }
    
    showTooltip(e) {
        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip';
        tooltip.textContent = e.target.getAttribute('title');
        document.body.appendChild(tooltip);
        
        const rect = e.target.getBoundingClientRect();
        tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
        tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';
        
        e.target.tooltipElement = tooltip;
    }
    
    hideTooltip(e) {
        if (e.target.tooltipElement) {
            e.target.tooltipElement.remove();
            delete e.target.tooltipElement;
        }
    }
    
    performSearch(query) {
        const url = new URL(window.location);
        url.searchParams.set('search', query);
        url.searchParams.set('page', '1');
        window.location.href = url.toString();
    }
}

// Initialize user management
const userManagement = new UserManagement();

// Modal functions
function openAddUserModal() {
    document.getElementById('addUserModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeAddUserModal() {
    document.getElementById('addUserModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// User actions
function editUser(userId) {
    // Implement edit user functionality
    alert('Kullanıcı düzenleme özelliği yakında eklenecek!');
}

function deleteUser(userId) {
    if (confirm('Bu kullanıcıyı silmek istediğinizden emin misiniz?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_user">
            <input type="hidden" name="user_id" value="${userId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function changeStatus(userId, status) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="user_id" value="${userId}">
        <input type="hidden" name="status" value="${status}">
    `;
    document.body.appendChild(form);
    form.submit();
}

function toggleDropdown(userId) {
    const dropdown = document.getElementById(`dropdown-${userId}`);
    const isVisible = dropdown.style.display === 'block';
    
    // Close all dropdowns
    document.querySelectorAll('.users-dropdown-menu').forEach(menu => {
        menu.style.display = 'none';
    });
    
    // Toggle current dropdown
    dropdown.style.display = isVisible ? 'none' : 'block';
}

function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.user-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
    
    updateSelectedUsers();
}

function updateSelectedUsers() {
    const checkboxes = document.querySelectorAll('.user-checkbox:checked');
    userManagement.selectedUsers = Array.from(checkboxes).map(cb => cb.value);
    
    const bulkActions = document.querySelector('.bulk-actions');
    bulkActions.style.display = userManagement.selectedUsers.length > 0 ? 'flex' : 'none';
}

function executeBulkAction() {
    const action = document.getElementById('bulkAction').value;
    if (!action || userManagement.selectedUsers.length === 0) {
        alert('Lütfen bir işlem seçin ve kullanıcıları işaretleyin.');
        return;
    }
    
    if (confirm(`Seçili ${userManagement.selectedUsers.length} kullanıcı için bu işlemi gerçekleştirmek istediğinizden emin misiniz?`)) {
        // Implement bulk action
        alert('Toplu işlem özelliği yakında eklenecek!');
    }
}

function exportUsers() {
    alert('Kullanıcı dışa aktarma özelliği yakında eklenecek!');
}

function refreshTable() {
    window.location.reload();
}

// Add event listeners for checkboxes
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.user-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedUsers);
    });
    
    // Fade in animation
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    });
    
    document.querySelectorAll('.fade-in').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'all 0.6s ease';
        observer.observe(el);
    });
});
</script>

<?php include 'includes/footer.php'; ?>