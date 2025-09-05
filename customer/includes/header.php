<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Müşteri Paneli - BiletJack</title>
    
    <!-- Favicon -->
    <?php
    // Favicon ayarını veritabanından al
    try {
        require_once __DIR__ . '/../../config/database.php';
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'site_favicon'");
        $stmt->execute();
        $faviconSetting = $stmt->fetchColumn();
        $faviconPath = $faviconSetting ? '../assets/images/' . $faviconSetting : '../assets/images/favicon.ico';
    } catch (Exception $e) {
        $faviconPath = '../assets/images/favicon.ico';
    }
    ?>
    <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars($faviconPath); ?>">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/customer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Desktop Sidebar -->
    <div class="modern-sidebar" id="desktopSidebar">
        <!-- Sidebar Brand -->
        <div class="sidebar-brand">
            <i class="fas fa-ticket-alt"></i>
            <span>BiletJack</span>
        </div>
        
        <!-- User Info -->
        <div class="sidebar-user-info">
            <div class="user-avatar">
                <i class="fas fa-user-circle"></i>
            </div>
            <div class="user-details">
                <h4><?php echo isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Kullanıcı'; ?></h4>
                <p>Müşteri Paneli</p>
            </div>
        </div>
        
        <!-- Navigation Menu -->
        <nav class="sidebar-nav">
            <div class="nav-section">
                <h3>Ana Menü</h3>
                <a href="index.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-pie"></i>
                    <span>Dashboard</span>
                </a>
                
                <a href="tickets.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'tickets.php' ? 'active' : ''; ?>">
                    <i class="fas fa-ticket-alt"></i>
                    <span>Biletlerim</span>
                </a>
                
                <a href="profile.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i>
                    <span>Profilim</span>
                </a>
            </div>
            
            <div class="nav-section">
                <h3>Keşfet</h3>
                <a href="../etkinlikler.php" class="nav-item">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Etkinlikler</span>
                </a>
                
                <a href="../index.php" class="nav-item">
                    <i class="fas fa-home"></i>
                    <span>Ana Sayfa</span>
                </a>
            </div>
        </nav>
        
        <!-- Logout Button -->
        <div class="sidebar-footer">
            <a href="../auth/logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Çıkış Yap</span>
            </a>
        </div>
    </div>
    
    <!-- Customer Mobile Menu Toggle -->
    <button class="customer-mobile-toggle" id="customerMobileToggle">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Mobile Optimized Sidebar -->
    <div class="mobile-sidebar" id="modernSidebar">
        <!-- Sidebar Header -->
        <div class="mobile-sidebar-header">
            <div class="sidebar-close-btn" onclick="closeSidebar()">
                <i class="fas fa-times"></i>
            </div>
            <div class="sidebar-brand-mobile">
                <i class="fas fa-ticket-alt"></i>
                <span>BiletJack</span>
            </div>
        </div>
        
        <!-- User Info -->
        <div class="mobile-user-info">
            <div class="user-avatar">
                <i class="fas fa-user-circle"></i>
            </div>
            <div class="user-details">
                <h4><?php echo isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Kullanıcı'; ?></h4>
                <p>Müşteri Paneli</p>
            </div>
        </div>
        
        <!-- Navigation Menu -->
        <nav class="mobile-nav">
            <a href="index.php" class="mobile-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-pie"></i>
                <span>Dashboard</span>
                <i class="fas fa-chevron-right"></i>
            </a>
            
            <a href="tickets.php" class="mobile-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'tickets.php' ? 'active' : ''; ?>">
                <i class="fas fa-ticket-alt"></i>
                <span>Biletlerim</span>
                <i class="fas fa-chevron-right"></i>
            </a>
            
            <a href="profile.php" class="mobile-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                <i class="fas fa-user"></i>
                <span>Profilim</span>
                <i class="fas fa-chevron-right"></i>
            </a>
            
            <div class="mobile-nav-divider"></div>
            
            <a href="../etkinlikler.php" class="mobile-nav-item">
                <i class="fas fa-calendar"></i>
                <span>Etkinlikler</span>
                <i class="fas fa-chevron-right"></i>
            </a>
            
            <a href="../index.php?from_panel=1" class="mobile-nav-item">
                <i class="fas fa-home"></i>
                <span>Ana Sayfa</span>
                <i class="fas fa-chevron-right"></i>
            </a>
        </nav>
        
        <!-- Sidebar Footer -->
        <div class="mobile-sidebar-footer">
            <a href="../auth/logout.php" class="mobile-logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Çıkış Yap</span>
            </a>
        </div>
    </div>
    
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
    
    <script>
    // Mobile Sidebar Functions
    function openSidebar() {
        const sidebar = document.getElementById('modernSidebar');
        const overlay = document.getElementById('sidebarOverlay');
        
        if (sidebar && overlay) {
            sidebar.classList.add('active');
            overlay.classList.add('active');
            document.body.classList.add('sidebar-open');
            document.body.style.overflow = 'hidden';
        }
    }
    
    function closeSidebar() {
        const sidebar = document.getElementById('modernSidebar');
        const overlay = document.getElementById('sidebarOverlay');
        
        if (sidebar && overlay) {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            document.body.classList.remove('sidebar-open');
            document.body.style.overflow = '';
        }
    }
    
    // Initialize mobile menu
    document.addEventListener('DOMContentLoaded', function() {
        const customerToggle = document.getElementById('customerMobileToggle');
        
        if (customerToggle) {
            customerToggle.addEventListener('click', function(e) {
                e.preventDefault();
                openSidebar();
            });
        }
        
        // Close sidebar on window resize if desktop
        window.addEventListener('resize', function() {
            if (window.innerWidth > 1024) {
                closeSidebar();
            }
        });
    });
    </script>