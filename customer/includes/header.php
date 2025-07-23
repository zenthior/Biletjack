<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Müşteri Paneli - BiletJack</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/customer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="modern-sidebar">
        <div class="sidebar-brand">
            <div class="brand-logo">
                <i class="fas fa-ticket-alt"></i>
            </div>
            <h2 class="brand-title">BiletJack</h2>
            <p class="brand-subtitle">Müşteri Paneli</p>
        </div>
        
        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">Ana Menü</div>
                <a href="index.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-pie"></i>
                    Dashboard
                </a>
                <a href="tickets.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'tickets.php' ? 'active' : ''; ?>">
                    <i class="fas fa-ticket-alt"></i>
                    Biletlerim
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Hesap</div>
                <a href="profile.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i>
                    Profilim
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Keşfet</div>
                <a href="../etkinlikler.php" class="nav-item">
                    <i class="fas fa-calendar"></i>
                    Etkinlikler
                </a>
                <a href="../index.php?from_panel=1" class="nav-item">
                    <i class="fas fa-home"></i>
                    Ana Sayfa
                </a>
            </div>
        </nav>
        
        <div class="sidebar-footer">
            <a href="../auth/logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                Çıkış Yap
            </a>
        </div>
    </div>