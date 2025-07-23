<?php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../classes/User.php';

// Müşteri kontrolü
requireCustomer();

$database = new Database();
$pdo = $database->getConnection();
$user = new User($pdo);
$userDetails = $user->getUserById($_SESSION['user_id']);

// İstatistikler için sorgu
$stmt = $pdo->prepare("SELECT COUNT(*) as total_tickets FROM tickets t JOIN orders o ON t.order_id = o.id WHERE o.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$totalTickets = $stmt->fetch()['total_tickets'];

$stmt = $pdo->prepare("SELECT COUNT(*) as active_tickets FROM tickets t JOIN orders o ON t.order_id = o.id WHERE o.user_id = ? AND t.status = 'active'");
$stmt->execute([$_SESSION['user_id']]);
$activeTickets = $stmt->fetch()['active_tickets'];

$stmt = $pdo->prepare("SELECT SUM(total_amount) as total_spent FROM orders WHERE user_id = ? AND payment_status = 'paid'");
$stmt->execute([$_SESSION['user_id']]);
$totalSpent = $stmt->fetch()['total_spent'] ?? 0;

include 'includes/header.php';
?>

<div class="dashboard-container">
    <div class="content-header">
        <h1>Hoş Geldiniz, <?php echo htmlspecialchars($userDetails['first_name'] . ' ' . $userDetails['last_name']); ?>!</h1>
        <p>Müşteri Paneli</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-ticket-alt"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $totalTickets; ?></h3>
                <p>Toplam Biletim</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $activeTickets; ?></h3>
                <p>Aktif Biletler</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-lira-sign"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($totalSpent, 2); ?> ₺</h3>
                <p>Toplam Harcama</p>
            </div>
        </div>
    </div>

    <div class="quick-actions">
        <h2>Hızlı İşlemler</h2>
        <div class="action-buttons">
            <a href="tickets.php" class="btn btn-primary">
                <i class="fas fa-ticket-alt"></i>
                Biletlerim
            </a>
            <a href="profile.php" class="btn btn-secondary">
                <i class="fas fa-user"></i>
                Profilim
            </a>
            <a href="../etkinlikler.php" class="btn btn-success">
                <i class="fas fa-calendar"></i>
                Etkinliklere Göz At
            </a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>