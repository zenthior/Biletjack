<?php
require_once '../includes/session.php';

// Organizatör kontrolü
if (!isLoggedIn() || !isOrganizer()) {
    header('Location: ../index.php');
    exit();
}

// Eğer onaylanmışsa dashboard'a yönlendir
if (isOrganizerApproved()) {
    header('Location: index.php');
    exit();
}

$currentUser = getCurrentUser();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Onay Bekleniyor - BiletJack</title>
    <link rel="stylesheet" href="../css/organizer.css">
</head>
<body>
    <div class="pending-container">
        <div class="pending-content">
            <div class="pending-icon">
                <i class="icon-clock"></i>
            </div>
            <h1>Başvurunuz İnceleniyor</h1>
            <p class="pending-message">
                Merhaba <strong><?php echo htmlspecialchars($currentUser['first_name']); ?></strong>,<br>
                Organizatör başvurunuz alınmıştır ve inceleme sürecindedir.
            </p>
            <div class="pending-info">
                <div class="info-item">
                    <strong>Başvuru Tarihi:</strong>
                    <span><?php echo date('d.m.Y H:i', strtotime($currentUser['created_at'])); ?></span>
                </div>
                <div class="info-item">
                    <strong>Durum:</strong>
                    <span class="status pending">İnceleme Aşamasında</span>
                </div>
            </div>
            <div class="pending-actions">
                <a href="../index.php" class="btn btn-primary">Ana Sayfaya Dön</a>
                <a href="../auth/logout.php" class="btn btn-outline">Çıkış Yap</a>
            </div>
            <div class="pending-note">
                <p><strong>Not:</strong> Başvurunuz genellikle 1-2 iş günü içinde değerlendirilir. Sonuç hakkında e-posta ile bilgilendirileceksiniz.</p>
            </div>
        </div>
    </div>
</body>
</html>