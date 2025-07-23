<?php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../classes/User.php';

// Müşteri kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    header('Location: ../index.php');
    exit();
}

$user = new User($pdo);
$userDetails = $user->getUserById($_SESSION['user_id']);

$message = '';
$messageType = '';

// Profil güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $phone = trim($_POST['phone']);
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    try {
        // Şifre değişikliği kontrolü
        if (!empty($newPassword)) {
            if (empty($currentPassword)) {
                throw new Exception('Mevcut şifrenizi girmelisiniz.');
            }
            
            if (!password_verify($currentPassword, $userDetails['password'])) {
                throw new Exception('Mevcut şifre yanlış.');
            }
            
            if ($newPassword !== $confirmPassword) {
                throw new Exception('Yeni şifreler eşleşmiyor.');
            }
            
            if (strlen($newPassword) < 6) {
                throw new Exception('Yeni şifre en az 6 karakter olmalıdır.');
            }
            
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, phone = ?, password = ? WHERE id = ?");
            $stmt->execute([$first_name, $last_name, $phone, $hashedPassword, $_SESSION['user_id']]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, phone = ? WHERE id = ?");
            $stmt->execute([$first_name, $last_name, $phone, $_SESSION['user_id']]);
        }
        
        $message = 'Profil başarıyla güncellendi.';
        $messageType = 'success';
        
        // Güncellenmiş bilgileri al
        $userDetails = $user->getUserById($_SESSION['user_id']);
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

include 'includes/header.php';
?>

<div class="dashboard-container">
    <div class="content-header">
        <h1>Profilim</h1>
        <p>Hesap bilgilerinizi yönetin</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="profile-container">
        <div class="profile-card">
            <h2>Kişisel Bilgiler</h2>
            
            <form method="POST" class="profile-form">
                <div class="form-group">
                    <label for="first_name">Ad</label>
                    <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($userDetails['first_name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="last_name">Soyad</label>
                    <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($userDetails['last_name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">E-posta</label>
                    <input type="email" id="email" value="<?php echo htmlspecialchars($userDetails['email']); ?>" disabled>
                    <small>E-posta adresi değiştirilemez</small>
                </div>
                
                <div class="form-group">
                    <label for="phone">Telefon</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($userDetails['phone']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="user_type">Hesap Türü</label>
                    <input type="text" value="Müşteri" disabled>
                </div>
                
                <div class="form-group">
                    <label for="created_at">Üyelik Tarihi</label>
                    <input type="text" value="<?php echo date('d.m.Y', strtotime($userDetails['created_at'])); ?>" disabled>
                </div>
                
                <h3>Şifre Değiştir</h3>
                
                <div class="form-group">
                    <label for="current_password">Mevcut Şifre</label>
                    <input type="password" id="current_password" name="current_password">
                </div>
                
                <div class="form-group">
                    <label for="new_password">Yeni Şifre</label>
                    <input type="password" id="new_password" name="new_password">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Yeni Şifre Tekrar</label>
                    <input type="password" id="confirm_password" name="confirm_password">
                </div>
                
                <button type="submit" class="btn btn-primary">Profili Güncelle</button>
            </form>
        </div>
        
        <div class="account-actions">
            <h2>Hesap İşlemleri</h2>
            
            <div class="action-item">
                <h3>Hesap Silme</h3>
                <p>Hesabınızı kalıcı olarak silmek istiyorsanız, lütfen bizimle iletişime geçin.</p>
                <a href="../iletisim.php" class="btn btn-danger">İletişime Geç</a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>