<?php
require_once '../includes/session.php';
require_once '../config/database.php';

if (!isLoggedIn() || !isOrganizer()) {
    header('Location: ../index.php');
    exit();
}

$database = new Database();
$pdo = $database->getConnection();
$userId = $_SESSION['user_id'];

// Yorum durumu güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $reviewId = (int)($_POST['review_id'] ?? 0);
    $action = $_POST['action'];
    
    if (in_array($action, ['approve', 'reject']) && $reviewId > 0) {
        // Bu yorumun organizatöre ait olup olmadığını kontrol et
        $checkQuery = $pdo->prepare("
            SELECT ec.id FROM event_comments ec
            JOIN events e ON ec.event_id = e.id
            WHERE ec.id = ? AND e.organizer_id = ?
        ");
        $checkQuery->execute([$reviewId, $userId]);
        
        if ($checkQuery->fetch()) {
            $newStatus = $action === 'approve' ? 'approved' : 'rejected';
            $updateQuery = $pdo->prepare("UPDATE event_comments SET status = ? WHERE id = ?");
            $updateQuery->execute([$newStatus, $reviewId]);
            
            $message = $action === 'approve' ? 'Yorum onaylandı.' : 'Yorum reddedildi.';
        } else {
            $error = 'Bu yorumu yönetme yetkiniz yok.';
        }
    }
}

// Sayfalama
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Filtreleme
$status = $_GET['status'] ?? 'all';
$eventId = (int)($_GET['event_id'] ?? 0);

// Toplam yorum sayısı
$countQuery = "SELECT COUNT(*) FROM event_comments ec JOIN events e ON ec.event_id = e.id WHERE e.organizer_id = ?";
$countParams = [$userId];

if ($status !== 'all') {
    $countQuery .= " AND ec.status = ?";
    $countParams[] = $status;
}

if ($eventId > 0) {
    $countQuery .= " AND ec.event_id = ?";
    $countParams[] = $eventId;
}

$countStmt = $pdo->prepare($countQuery);
$countStmt->execute($countParams);
$totalReviews = $countStmt->fetchColumn();
$totalPages = ceil($totalReviews / $limit);

// Yorumları getir
$query = "
    SELECT ec.*, e.title as event_title, e.image_url as event_image,
           u.first_name, u.last_name, u.email
    FROM event_comments ec
    JOIN events e ON ec.event_id = e.id
    JOIN users u ON ec.user_id = u.id
    WHERE e.organizer_id = ?
";
$params = [$userId];

if ($status !== 'all') {
    $query .= " AND ec.status = ?";
    $params[] = $status;
}

if ($eventId > 0) {
    $query .= " AND ec.event_id = ?";
    $params[] = $eventId;
}

$query .= " ORDER BY ec.created_at DESC LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organizatörün etkinliklerini getir (filtre için)
$eventsQuery = $pdo->prepare("SELECT id, title FROM events WHERE organizer_id = ? ORDER BY title");
$eventsQuery->execute([$userId]);
$events = $eventsQuery->fetchAll(PDO::FETCH_ASSOC);

// İstatistikler
$statsQuery = $pdo->prepare("
    SELECT 
        COUNT(*) as total_reviews,
        COUNT(CASE WHEN ec.status = 'pending' THEN 1 END) as pending_reviews,
        COUNT(CASE WHEN ec.status = 'approved' THEN 1 END) as approved_reviews,
        COUNT(CASE WHEN ec.status = 'rejected' THEN 1 END) as rejected_reviews,
        AVG(CASE WHEN ec.status = 'approved' THEN rating END) as avg_rating
    FROM event_comments ec
    JOIN events e ON ec.event_id = e.id
    WHERE e.organizer_id = ?
");
$statsQuery->execute([$userId]);
$stats = $statsQuery->fetch(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="organizer-container">
    <div class="page-header">
        <h1><i class="fas fa-star"></i> Yorumlar ve Değerlendirmeler</h1>
        <p>Etkinlikleriniz için gelen yorumları yönetin</p>
    </div>

    <?php if (isset($message)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- İstatistikler -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-comments"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $stats['total_reviews']; ?></div>
                <div class="stat-label">Toplam Yorum</div>
            </div>
        </div>
        
        <div class="stat-card pending">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $stats['pending_reviews']; ?></div>
                <div class="stat-label">Bekleyen</div>
            </div>
        </div>
        
        <div class="stat-card approved">
            <div class="stat-icon">
                <i class="fas fa-check"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $stats['approved_reviews']; ?></div>
                <div class="stat-label">Onaylanan</div>
            </div>
        </div>
        
        <div class="stat-card rating">
            <div class="stat-icon">
                <i class="fas fa-star"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $stats['avg_rating'] ? number_format($stats['avg_rating'], 1) : '0.0'; ?></div>
                <div class="stat-label">Ortalama Puan</div>
            </div>
        </div>
    </div>

    <!-- Filtreler -->
    <div class="filters-section">
        <form method="GET" class="filters-form">
            <div class="filter-group">
                <label>Durum:</label>
                <select name="status">
                    <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>Tümü</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Bekleyen</option>
                    <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Onaylanan</option>
                    <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Reddedilen</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Etkinlik:</label>
                <select name="event_id">
                    <option value="0">Tüm Etkinlikler</option>
                    <?php foreach ($events as $event): ?>
                        <option value="<?php echo $event['id']; ?>" <?php echo $eventId == $event['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($event['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter"></i> Filtrele
            </button>
        </form>
    </div>

    <!-- Yorumlar Listesi -->
    <div class="reviews-section">
        <?php if (empty($reviews)): ?>
            <div class="empty-state">
                <i class="fas fa-comments"></i>
                <h3>Henüz yorum yok</h3>
                <p>Etkinlikleriniz için henüz yorum yapılmamış.</p>
            </div>
        <?php else: ?>
            <div class="reviews-list">
                <?php foreach ($reviews as $review): ?>
                    <div class="review-card status-<?php echo $review['status']; ?>">
                        <div class="review-header">
                            <div class="event-info">
                                <?php if ($review['event_image']): ?>
                                    <img src="<?php echo htmlspecialchars($review['event_image']); ?>" alt="Event" class="event-thumb">
                                <?php else: ?>
                                    <div class="event-thumb-placeholder">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="event-details">
                                    <h4><?php echo htmlspecialchars($review['event_title']); ?></h4>
                                    <div class="review-meta">
                                        <span class="reviewer"><?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?></span>
                                        <span class="review-date"><?php echo date('d.m.Y H:i', strtotime($review['created_at'])); ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="review-rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'active' : ''; ?>"></i>
                                <?php endfor; ?>
                                <span class="rating-text"><?php echo $review['rating']; ?>/5</span>
                            </div>
                        </div>
                        
                        <div class="review-content">
                            <p><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                        </div>
                        
                        <div class="review-footer">
                            <div class="review-status">
                                <span class="status-badge status-<?php echo $review['status']; ?>">
                                    <?php 
                                    switch($review['status']) {
                                        case 'pending': echo 'Bekliyor'; break;
                                        case 'approved': echo 'Onaylandı'; break;
                                        case 'rejected': echo 'Reddedildi'; break;
                                    }
                                    ?>
                                </span>
                            </div>
                            
                            <?php if ($review['status'] === 'pending'): ?>
                                <div class="review-actions">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Bu yorumu onaylamak istediğinizden emin misiniz?')">
                                            <i class="fas fa-check"></i> Onayla
                                        </button>
                                    </form>
                                    
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Bu yorumu reddetmek istediğinizden emin misiniz?')">
                                            <i class="fas fa-times"></i> Reddet
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Sayfalama -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&status=<?php echo $status; ?>&event_id=<?php echo $eventId; ?>" class="pagination-btn">
                            <i class="fas fa-chevron-left"></i> Önceki
                        </a>
                    <?php endif; ?>
                    
                    <span class="pagination-info">
                        Sayfa <?php echo $page; ?> / <?php echo $totalPages; ?>
                    </span>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&status=<?php echo $status; ?>&event_id=<?php echo $eventId; ?>" class="pagination-btn">
                            Sonraki <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 15px;
    border-left: 4px solid #e91e63;
}

.stat-card.pending {
    border-left-color: #ff9800;
}

.stat-card.approved {
    border-left-color: #4caf50;
}

.stat-card.rating {
    border-left-color: #ffc107;
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: rgba(233, 30, 99, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #e91e63;
    font-size: 20px;
}

.stat-card.pending .stat-icon {
    background: rgba(255, 152, 0, 0.1);
    color: #ff9800;
}

.stat-card.approved .stat-icon {
    background: rgba(76, 175, 80, 0.1);
    color: #4caf50;
}

.stat-card.rating .stat-icon {
    background: rgba(255, 193, 7, 0.1);
    color: #ffc107;
}

.stat-number {
    font-size: 24px;
    font-weight: 700;
    color: #2c3e50;
}

.stat-label {
    font-size: 14px;
    color: #6c757d;
}

.filters-section {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.filters-form {
    display: flex;
    gap: 20px;
    align-items: end;
    flex-wrap: wrap;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.filter-group label {
    font-weight: 600;
    color: #2c3e50;
    font-size: 14px;
}

.filter-group select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    min-width: 150px;
}

.reviews-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.review-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
    border-left: 4px solid #ddd;
}

.review-card.status-pending {
    border-left-color: #ff9800;
}

.review-card.status-approved {
    border-left-color: #4caf50;
}

.review-card.status-rejected {
    border-left-color: #f44336;
}

.review-header {
    padding: 20px;
    border-bottom: 1px solid #f0f0f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.event-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

.event-thumb {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    object-fit: cover;
}

.event-thumb-placeholder {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 20px;
}

.event-details h4 {
    margin: 0 0 5px 0;
    color: #2c3e50;
    font-size: 16px;
}

.review-meta {
    display: flex;
    gap: 15px;
    font-size: 14px;
    color: #6c757d;
}

.review-rating {
    display: flex;
    align-items: center;
    gap: 10px;
}

.review-rating .fas.fa-star {
    color: #ddd;
    font-size: 16px;
}

.review-rating .fas.fa-star.active {
    color: #ffc107;
}

.rating-text {
    font-weight: 600;
    color: #2c3e50;
}

.review-content {
    padding: 20px;
}

.review-content p {
    margin: 0;
    line-height: 1.6;
    color: #2c3e50;
}

.review-footer {
    padding: 20px;
    border-top: 1px solid #f0f0f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-badge.status-pending {
    background: rgba(255, 152, 0, 0.1);
    color: #ff9800;
}

.status-badge.status-approved {
    background: rgba(76, 175, 80, 0.1);
    color: #4caf50;
}

.status-badge.status-rejected {
    background: rgba(244, 67, 54, 0.1);
    color: #f44336;
}

.review-actions {
    display: flex;
    gap: 10px;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 12px;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #6c757d;
}

.empty-state i {
    font-size: 48px;
    margin-bottom: 20px;
    color: #ddd;
}

.empty-state h3 {
    margin: 0 0 10px 0;
    color: #2c3e50;
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 20px;
    margin-top: 30px;
}

.pagination-btn {
    padding: 10px 20px;
    background: #e91e63;
    color: white;
    text-decoration: none;
    border-radius: 6px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.pagination-btn:hover {
    background: #c2185b;
    transform: translateY(-2px);
}

.pagination-info {
    font-weight: 600;
    color: #2c3e50;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .filters-form {
        flex-direction: column;
        align-items: stretch;
    }
    
    .review-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .review-footer {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .review-actions {
        width: 100%;
        justify-content: flex-end;
    }
}
</style>

<script>
    // Yorum yönetimi AJAX fonksiyonları
    function manageReview(reviewId, action) {
        if (!confirm(action === 'approve' ? 'Bu yorumu onaylamak istediğinizden emin misiniz?' : 'Bu yorumu reddetmek istediğinizden emin misiniz?')) {
            return;
        }
        
        const formData = new FormData();
        formData.append('review_id', reviewId);
        formData.append('action', action);
        
        fetch('ajax/manage_review.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Bir hata oluştu. Lütfen tekrar deneyin.');
        });
    }
    
    // Onay ve red butonlarına event listener ekle
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.btn-approve').forEach(btn => {
            btn.addEventListener('click', function() {
                const reviewId = this.getAttribute('data-review-id');
                manageReview(reviewId, 'approve');
            });
        });
        
        document.querySelectorAll('.btn-reject').forEach(btn => {
            btn.addEventListener('click', function() {
                const reviewId = this.getAttribute('data-review-id');
                manageReview(reviewId, 'reject');
            });
        });
    });
</script>

<?php include 'includes/footer.php'; ?>