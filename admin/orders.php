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

// Sipariş işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_payment_status':
                $orderId = $_POST['order_id'];
                $newStatus = $_POST['payment_status'];
                
                $updateQuery = "UPDATE orders SET payment_status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
                $updateStmt = $pdo->prepare($updateQuery);
                $updateStmt->bindParam(':status', $newStatus);
                $updateStmt->bindParam(':id', $orderId);
                
                if ($updateStmt->execute()) {
                    $message = 'Ödeme durumu başarıyla güncellendi!';
                    $messageType = 'success';
                } else {
                    $message = 'Ödeme durumu güncellenirken hata oluştu!';
                    $messageType = 'error';
                }
                break;
                
            case 'refund_order':
                $orderId = $_POST['order_id'];
                
                // Siparişi iade et
                $refundQuery = "UPDATE orders SET payment_status = 'refunded', updated_at = CURRENT_TIMESTAMP WHERE id = :id";
                $refundStmt = $pdo->prepare($refundQuery);
                $refundStmt->bindParam(':id', $orderId);
                
                // Biletleri iptal et
                $cancelTicketsQuery = "UPDATE tickets SET status = 'refunded' WHERE order_id = :order_id";
                $cancelStmt = $pdo->prepare($cancelTicketsQuery);
                $cancelStmt->bindParam(':order_id', $orderId);
                
                if ($refundStmt->execute() && $cancelStmt->execute()) {
                    $message = 'Sipariş başarıyla iade edildi!';
                    $messageType = 'success';
                } else {
                    $message = 'İade işlemi sırasında hata oluştu!';
                    $messageType = 'error';
                }
                break;
                
            case 'delete_order':
                $orderId = $_POST['order_id'];
                
                try {
                    // Transaction başlat
                    $pdo->beginTransaction();
                    
                    // Önce biletleri sil
                    $deleteTicketsQuery = "DELETE FROM tickets WHERE order_id = :order_id";
                    $deleteTicketsStmt = $pdo->prepare($deleteTicketsQuery);
                    $deleteTicketsStmt->bindParam(':order_id', $orderId);
                    $deleteTicketsStmt->execute();
                    
                    // Sonra siparişi sil
                    $deleteOrderQuery = "DELETE FROM orders WHERE id = :id";
                    $deleteOrderStmt = $pdo->prepare($deleteOrderQuery);
                    $deleteOrderStmt->bindParam(':id', $orderId);
                    $deleteOrderStmt->execute();
                    
                    // Transaction'ı commit et
                    $pdo->commit();
                    
                    $message = 'Sipariş başarıyla silindi!';
                    $messageType = 'success';
                } catch (Exception $e) {
                    // Hata durumunda rollback
                    $pdo->rollBack();
                    $message = 'Sipariş silinirken hata oluştu: ' . $e->getMessage();
                    $messageType = 'error';
                }
                break;
        }
    }
}

// Arama ve filtreleme
$search = isset($_GET['search']) ? $_GET['search'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$paymentMethodFilter = isset($_GET['payment_method']) ? $_GET['payment_method'] : '';
$dateFilter = isset($_GET['date_filter']) ? $_GET['date_filter'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Sipariş sorgusunu oluştur
$whereConditions = [];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(o.order_number LIKE :search OR u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($statusFilter)) {
    $whereConditions[] = "o.payment_status = :status";
    $params[':status'] = $statusFilter;
}

if (!empty($paymentMethodFilter)) {
    $whereConditions[] = "o.payment_method = :payment_method";
    $params[':payment_method'] = $paymentMethodFilter;
}

if (!empty($dateFilter)) {
    switch ($dateFilter) {
        case 'today':
            $whereConditions[] = "DATE(o.created_at) = CURDATE()";
            break;
        case 'week':
            $whereConditions[] = "o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $whereConditions[] = "o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
    }
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Toplam sipariş sayısı
$countQuery = "SELECT COUNT(*) as total FROM orders o JOIN users u ON o.user_id = u.id $whereClause";
$countStmt = $pdo->prepare($countQuery);
foreach ($params as $key => $value) {
    $countStmt->bindValue($key, $value);
}
$countStmt->execute();
$totalOrders = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalOrders / $limit);

// Siparişleri getir
$ordersQuery = "SELECT o.*, u.first_name, u.last_name, u.email,
                      (SELECT COUNT(*) FROM tickets WHERE order_id = o.id) as ticket_count
               FROM orders o 
               JOIN users u ON o.user_id = u.id 
               $whereClause
               ORDER BY o.created_at DESC 
               LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($ordersQuery);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// İstatistikler
$statsQuery = "SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) as paid_orders,
                SUM(CASE WHEN payment_status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                SUM(CASE WHEN payment_status = 'failed' THEN 1 ELSE 0 END) as failed_orders,
                SUM(CASE WHEN payment_status = 'refunded' THEN 1 ELSE 0 END) as refunded_orders,
                SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) as total_revenue
               FROM orders";
$statsStmt = $pdo->prepare($statsQuery);
$statsStmt->execute();
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

// Bekleyen organizatörleri al (navbar için)
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
            <h2 class="sidebar-title">Siparişler</h2>
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
                <a href="orders.php" class="nav-item active">
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
    <div class="admin-main">
        <div class="admin-header">
            <div class="header-left">
                <button class="mobile-menu-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title">
                    <i class="fas fa-shopping-cart"></i>
                    Sipariş Yönetimi
                </h1>
                <p class="page-subtitle">Tüm siparişleri görüntüleyin ve yönetin</p>
            </div>
            <div class="header-right">
                <div class="header-stats">
                    <div class="stat-item">
                        <span class="stat-value"><?php echo number_format($stats['total_orders']); ?></span>
                        <span class="stat-label">Toplam Sipariş</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value"><?php echo number_format($stats['total_revenue'], 2); ?> ₺</span>
                        <span class="stat-label">Toplam Gelir</span>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- İstatistik Kartları -->
        <div class="stats-grid">
            <div class="stat-card paid">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['paid_orders']); ?></h3>
                    <p>Ödenen Siparişler</p>
                </div>
            </div>
            
            <div class="stat-card pending">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['pending_orders']); ?></h3>
                    <p>Bekleyen Siparişler</p>
                </div>
            </div>
            
            <div class="stat-card failed">
                <div class="stat-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['failed_orders']); ?></h3>
                    <p>Başarısız Siparişler</p>
                </div>
            </div>
            
            <div class="stat-card refunded">
                <div class="stat-icon">
                    <i class="fas fa-undo"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['refunded_orders']); ?></h3>
                    <p>İade Edilen Siparişler</p>
                </div>
            </div>
        </div>

        <!-- Filtreler -->
        <div class="filters-section">
            <form method="GET" class="filters-form">
                <div class="filter-group">
                    <input type="text" name="search" placeholder="Sipariş no, müşteri adı veya e-posta ara..." 
                           value="<?php echo htmlspecialchars($search); ?>" class="filter-input">
                </div>
                
                <div class="filter-group">
                    <select name="status" class="filter-select">
                        <option value="">Tüm Durumlar</option>
                        <option value="paid" <?php echo $statusFilter === 'paid' ? 'selected' : ''; ?>>Ödendi</option>
                        <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Beklemede</option>
                        <option value="failed" <?php echo $statusFilter === 'failed' ? 'selected' : ''; ?>>Başarısız</option>
                        <option value="refunded" <?php echo $statusFilter === 'refunded' ? 'selected' : ''; ?>>İade Edildi</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <select name="payment_method" class="filter-select">
                        <option value="">Tüm Ödeme Yöntemleri</option>
                        <option value="Kredi Kartı" <?php echo $paymentMethodFilter === 'Kredi Kartı' ? 'selected' : ''; ?>>Kredi Kartı</option>
                        <option value="Banka Havalesi" <?php echo $paymentMethodFilter === 'Banka Havalesi' ? 'selected' : ''; ?>>Banka Havalesi</option>
                        <option value="PayPal" <?php echo $paymentMethodFilter === 'PayPal' ? 'selected' : ''; ?>>PayPal</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <select name="date_filter" class="filter-select">
                        <option value="">Tüm Tarihler</option>
                        <option value="today" <?php echo $dateFilter === 'today' ? 'selected' : ''; ?>>Bugün</option>
                        <option value="week" <?php echo $dateFilter === 'week' ? 'selected' : ''; ?>>Son 7 Gün</option>
                        <option value="month" <?php echo $dateFilter === 'month' ? 'selected' : ''; ?>>Son 30 Gün</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Filtrele
                </button>
                
                <a href="orders.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Temizle
                </a>
            </form>
        </div>

        <!-- Siparişler Tablosu -->
        <div class="table-container">
            <div class="table-header">
                <h3>Siparişler (<?php echo number_format($totalOrders); ?> adet)</h3>
                <div class="table-actions">
                    <button class="btn btn-success" onclick="exportOrders()">
                        <i class="fas fa-download"></i> Dışa Aktar
                    </button>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Sipariş No</th>
                            <th>Müşteri</th>
                            <th>Tutar</th>
                            <th>Ödeme Durumu</th>
                            <th>Ödeme Yöntemi</th>
                            <th>Bilet Sayısı</th>
                            <th>Tarih</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="8" class="no-data">
                                    <i class="fas fa-shopping-cart"></i>
                                    <p>Henüz sipariş bulunmuyor.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>
                                        <span class="order-number"><?php echo htmlspecialchars($order['order_number']); ?></span>
                                    </td>
                                    <td>
                                        <div class="customer-info">
                                            <strong><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></strong>
                                            <small><?php echo htmlspecialchars($order['email']); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="amount"><?php echo number_format($order['total_amount'], 2); ?> ₺</span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $order['payment_status']; ?>">
                                            <?php 
                                            $statusLabels = [
                                                'paid' => 'Ödendi',
                                                'pending' => 'Beklemede',
                                                'failed' => 'Başarısız',
                                                'refunded' => 'İade Edildi'
                                            ];
                                            echo $statusLabels[$order['payment_status']] ?? $order['payment_status'];
                                            ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($order['payment_method'] ?? 'Belirtilmemiş'); ?></td>
                                    <td>
                                        <span class="ticket-count"><?php echo $order['ticket_count']; ?> bilet</span>
                                    </td>
                                    <td>
                                        <span class="date"><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-sm btn-info" onclick="viewOrderDetails(<?php echo $order['id']; ?>)" title="Detayları Görüntüle">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            
                                            <?php if ($order['payment_status'] === 'pending'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="update_payment_status">
                                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                    <input type="hidden" name="payment_status" value="paid">
                                                    <button type="submit" class="btn btn-sm btn-success" title="Ödeme Onayla" 
                                                            onclick="return confirm('Bu siparişin ödemesini onaylamak istediğinizden emin misiniz?')">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <?php if ($order['payment_status'] === 'paid'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="refund_order">
                                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-warning" title="İade Et" 
                                                            onclick="return confirm('Bu siparişi iade etmek istediğinizden emin misiniz?')">
                                                        <i class="fas fa-undo"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <!-- Sipariş Sil Butonu -->
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="delete_order">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" title="Siparişi Sil" 
                                                        onclick="return confirm('Bu siparişi kalıcı olarak silmek istediğinizden emin misiniz? Bu işlem geri alınamaz!')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Sayfalama -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination-container">
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>&payment_method=<?php echo urlencode($paymentMethodFilter); ?>&date_filter=<?php echo urlencode($dateFilter); ?>" class="pagination-btn">
                            <i class="fas fa-chevron-left"></i> Önceki
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>&payment_method=<?php echo urlencode($paymentMethodFilter); ?>&date_filter=<?php echo urlencode($dateFilter); ?>" 
                           class="pagination-btn <?php echo $i === $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>&payment_method=<?php echo urlencode($paymentMethodFilter); ?>&date_filter=<?php echo urlencode($dateFilter); ?>" class="pagination-btn">
                            Sonraki <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="pagination-info">
                    Sayfa <?php echo $page; ?> / <?php echo $totalPages; ?> (Toplam <?php echo number_format($totalOrders); ?> sipariş)
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Sipariş Detayları Modal -->
<div id="orderDetailsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Sipariş Detayları</h3>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <div class="modal-body" id="orderDetailsContent">
            <!-- AJAX ile yüklenecek -->
        </div>
    </div>
</div>

<script>
// Sipariş detaylarını görüntüle
function viewOrderDetails(orderId) {
    const modal = document.getElementById('orderDetailsModal');
    const content = document.getElementById('orderDetailsContent');
    
    content.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Yükleniyor...</div>';
    modal.style.display = 'block';
    
    fetch('get_order_details.php?id=' + orderId)
        .then(response => response.text())
        .then(data => {
            content.innerHTML = data;
        })
        .catch(error => {
            content.innerHTML = '<div class="error">Detaylar yüklenirken hata oluştu.</div>';
        });
}

// Modal'ı kapat
function closeModal() {
    document.getElementById('orderDetailsModal').style.display = 'none';
}

// Siparişleri dışa aktar
function exportOrders() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'csv');
    window.location.href = 'orders.php?' + params.toString();
}

// Modal dışına tıklandığında kapat
window.onclick = function(event) {
    const modal = document.getElementById('orderDetailsModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}
</script>

<?php include 'includes/footer.php'; ?>