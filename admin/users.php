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
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Kullanıcıları getir
$whereConditions = [];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(first_name LIKE :search OR last_name LIKE :search OR email LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($userTypeFilter)) {
    $whereConditions[] = "user_type = :user_type";
    $params[':user_type'] = $userTypeFilter;
}

if (!empty($statusFilter)) {
    $whereConditions[] = "status = :status";
    $params[':status'] = $statusFilter;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Toplam kullanıcı sayısı
$countQuery = "SELECT COUNT(*) as total FROM users $whereClause";
$countStmt = $pdo->prepare($countQuery);
foreach ($params as $key => $value) {
    $countStmt->bindValue($key, $value);
}
$countStmt->execute();
$totalUsers = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalUsers / $limit);

// Kullanıcıları getir
$query = "SELECT u.*, 
                 CASE 
                     WHEN u.user_type = 'organizer' THEN od.approval_status 
                     ELSE NULL 
                 END as organizer_status
          FROM users u 
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
                <i class="fas fa-ticket-alt"></i>
            </div>
            <h2 class="sidebar-title">BiletJack</h2>
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
                    <p class="page-subtitle">Sistem kullanıcılarını yönetin ve düzenleyin</p>
                </div>
            </div>
            
            <div class="header-right">
                <div class="header-search">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="search-input" placeholder="Ara...">
                </div>
                
                <button class="header-notifications">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge"></span>
                </button>
                
                <div class="user-menu">
                    <?php $currentUser = getCurrentUser(); ?>
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($currentUser['first_name'], 0, 1)); ?>
                    </div>
                    <div class="user-info">
                        <h4><?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?></h4>
                        <p>Admin</p>
                    </div>
                    <i class="fas fa-chevron-down"></i>
                </div>
                
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
                        
                        <a href="users.php" class="btn btn-outline">
                            <i class="fas fa-times"></i>
                            Temizle
                        </a>
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
                                <td colspan="8" class="no-data">
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
                                                </div>
                                                <div class="user-id">ID: <?php echo $userData['id']; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($userData['email']); ?></td>
                                    <td><?php echo htmlspecialchars($userData['phone'] ?? '-'); ?></td>
                                    <td>
                                        <span class="user-type-badge <?php echo $userData['user_type']; ?>">
                                            <?php 
                                            $typeLabels = [
                                                'admin' => 'Admin',
                                                'organizer' => 'Organizatör',
                                                'customer' => 'Müşteri'
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
                                            
                                            <div class="dropdown">
                                                <button class="btn-action more" onclick="toggleDropdown(<?php echo $userData['id']; ?>)" title="Daha Fazla">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <div class="dropdown-menu" id="dropdown-<?php echo $userData['id']; ?>">
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
                    <input type="password" id="password" name="password" required minlength="6">
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
            if (!e.target.closest('.dropdown')) {
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
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
    document.querySelectorAll('.dropdown-menu').forEach(menu => {
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