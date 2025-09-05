<?php
// Session kontrolü ekle
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Session fonksiyonlarını dahil et
require_once __DIR__ . '/session.php';

// Kullanıcı giriş durumunu kontrol et
$isLoggedIn = isLoggedIn();
$currentUser = $isLoggedIn ? getCurrentUser() : null;
$userType = $isLoggedIn ? $_SESSION['user_type'] : null;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BiletJack - Bilet Satış Platformu</title>
    
    <!-- Favicon -->
    <?php
    // Favicon ayarını veritabanından al
    try {
        require_once __DIR__ . '/../config/database.php';
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'site_favicon'");
        $stmt->execute();
        $faviconSetting = $stmt->fetchColumn();
        $faviconPath = $faviconSetting ? 'assets/images/' . $faviconSetting : 'assets/images/favicon.ico';
    } catch (Exception $e) {
        $faviconPath = 'assets/images/favicon.ico';
    }
    ?>
    <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars($faviconPath); ?>">
    
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/modal.css">
    <?php if (strpos($_SERVER['REQUEST_URI'] ?? '', '/ad_agency/') !== false): ?>
        <link rel="stylesheet" href="../css/ad_agency.css">
    <?php endif; ?>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: rgb(87 87 87 / 28%);
            min-height: 100vh;
            color: #f5f5f5; /* Açık gri metin rengi */
        }

        .header {
            background: rgba(255, 255, 255, 0.49); /* Koyu gri, hafif transparan header */
            backdrop-filter: blur(10px);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 100;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1); /* İnce beyaz çizgi */
        }

        .nav {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 1rem;
            gap: 1rem;
            position: relative;
        }

        .logo {
            flex-shrink: 0;
            display: flex;
            align-items: center;
            text-decoration: none;
        }

        .logo-image {
            height: 40px;
            width: auto;
        }

        /* Mobil Menü Butonu */
        .mobile-menu-btn {
            display: none;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 0.75rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }

        .mobile-menu-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        /* Desktop Arama ve Hesap */
        .desktop-nav {
            display: flex;
            align-items: center;
            gap: 8rem;
            flex: 1;
            justify-content: center;
        }

        .header-search {
            display: flex;
            align-items: center;
            max-width: 500px;
            width: 100%;
            background: rgba(0, 0, 0, 0.9);
            border-radius: 25px;
            padding: 0.4rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .search-field {
            flex: 1;
            min-width: 80px;
            padding: 0.7rem 0.8rem;
            border: none;
            border-radius: 15px;
            background: transparent;
            font-size: 0.9rem;
            color: white;
            transition: background 0.3s;
        }

        .search-field:focus {
            outline: none;
            background: rgba(102, 126, 234, 0.05);
        }

        .search-field::placeholder {
            color: #999;
            font-size: 0.85rem;
        }

        .header-search-btn {
            background: #333;
            color: white;
            border: none;
            padding: 0.6rem 1rem;
            border-radius: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            white-space: nowrap;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .header-search-btn:hover {
            background: #555;
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }



        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: #ffd700;
        }

        .hero {
            text-align: center;
            padding: 4rem 2rem;
            color: white;
        }

        .hero h1 {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .hero p {
            font-size: 1.2rem;
            margin-bottom: 3rem;
            opacity: 0.9;
        }

        .search-container {
            max-width: 800px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }

        .search-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            color: #333;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .form-group input, .form-group select {
            padding: 1rem;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }

        .search-btn {
            grid-column: 1 / -1;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .features {
            max-width: 1200px;
            margin: 4rem auto;
            padding: 0 2rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            color: white;
            transition: transform 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-5px);
        }

        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .feature-card h3 {
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        .popular-events {
            padding: 4rem 0;
        }

        .section-subtitle {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.1rem;
            margin: 0;
        }

        /* Section Header Düzenlemesi */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 3rem;
        }

        .section-left {
            text-align: left;
        }

        .section-title {
            color: #000000;
            font-size: 1.8rem;
            margin: 0;
            font-weight: 600;
        }

        .section-right {
            display: flex;
            align-items: center;
        }

        /* Sorting Controls */
        .sorting-controls {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        /* Dropdown Styles */
        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-btn {
            background: rgb(0 0 0 / 69%);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 0.7rem 1.2rem;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: -60px;
        }

        .dropdown-btn:hover {
            background: rgba(17, 17, 17, 0.34);
        }

        .dropdown-arrow {
            transition: transform 0.3s ease;
        }

        .dropdown.active .dropdown-arrow {
            transform: rotate(180deg);
        }

        .dropdown-content {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            min-width: 160px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            z-index: 1000;
            margin-top: 0.5rem;
        }

        .dropdown.active .dropdown-content {
            display: block;
        }

        .dropdown-content a {
            color: white;
            padding: 0.8rem 1.2rem;
            text-decoration: none;
            display: block;
            transition: background 0.3s ease;
            font-size: 0.9rem;
        }

        .dropdown-content a:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .dropdown-content a:first-child {
            border-radius: 15px 15px 0 0;
        }

        .dropdown-content a:last-child {
            border-radius: 0 0 15px 15px;
        }

        /* View Controls */
        .view-controls {
            display: flex;
            gap: 0.5rem;
        }

        .view-btn {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 0.7rem;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1.2rem;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .view-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .view-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: transparent;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        /* Sıralama Butonları */
        .sorting-buttons {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1.5rem;
            flex-wrap: wrap;
        }

        .sort-btn {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 0.6rem 1.2rem;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .sort-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: transparent;
        }

        .sort-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        /* Yeni Etkinlik Kartı Tasarımı */
        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .event-card {
            background: transparent;
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            border: none;
            box-shadow: none;
        }

        .event-card:hover {
            transform: translateY(-5px);
        }

        .event-image {
            height: 280px;
            border-radius: 15px;
            background-size: cover !important;
            background-position: center !important;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 1rem;
            color: white;
        }

        /* Favori butonu için konumlandırma */
        .event-card .event-image {
            position: relative;
        }
        .event-card .favorite-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(0, 0, 0, 0.5);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
            cursor: pointer;
            z-index: 2;
        }
        .event-card .favorite-btn:hover {
            background: rgba(0, 0, 0, 0.65);
        }
        .event-card .favorite-btn.active {
            background: rgba(0, 0, 0, 0.5);
            border-color: rgba(255, 255, 255, 0.2);
        }
        .event-card .favorite-btn svg {
            width: 20px;
            height: 20px;
            pointer-events: none;
        }

        /* Favori butonu (genel) - Detay sayfası ve diğer yerlerde çalışsın */
        .favorite-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(0, 0, 0, 0.5);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
            cursor: pointer;
            z-index: 2;
        }
        .favorite-btn:hover {
            background: rgba(0, 0, 0, 0.65);
        }
        .favorite-btn.active {
            background: rgba(0, 0, 0, 0.5);
            border-color: rgba(255, 255, 255, 0.2);
        }
        .favorite-btn svg {
            width: 20px;
            height: 20px;
            pointer-events: none;
        }
        .favorite-btn svg path,
        .favorite-btn svg {
            fill: #fff;
            stroke: #fff;
        }
        .favorite-btn.active svg path,
        .favorite-btn.active svg {
            fill: #ff4d4f;
            stroke: #ff4d4f;
        }

        /* Pozisyon sınıfları absolute yapsın */
        .favorite-btn.pos-top-left,
        .favorite-btn.pos-top-right,
        .favorite-btn.pos-bottom-right {
            position: absolute;
        }

        /* Pozisyon varyasyonları */
        .favorite-btn.pos-top-left { top: 8px; left: 8px; right: auto; bottom: auto; }
        .favorite-btn.pos-top-right { top: 8px; right: 8px; bottom: auto; left: auto; }
        .favorite-btn.pos-bottom-right { bottom: 8px; right: 8px; top: auto; left: auto; }

        /* Detay sayfası ana görseli için relative */
        .event-main-image { position: relative; }

        .event-category {
            position: absolute;
            top: 1rem;
            left: 1rem;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .event-location {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
        }

        .event-content {
            padding: 1rem 0.5rem;
            text-align: left;
        }

        .event-title {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            color: #000000;
            font-weight: 600;
            line-height: 1.3;
        }

        .event-venue, .event-date {
            color: #000000;
            margin-bottom: 0.3rem;
            font-size: 0.85rem;
        }

        .event-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 0.8rem;
        }

        .event-price {
            font-size: 1.2rem;
            font-weight: bold;
            color:rgb(24, 99, 47);
        }

        .buy-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.85rem;
        }

        .buy-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        /* Newsletter Section */
        .newsletter {
            padding: 4rem 0;
            background: rgba(255, 255, 255, 0.05);
        }

        .newsletter-content {
            text-align: center;
            max-width: 600px;
            margin: 0 auto;
        }

        .newsletter h2 {
            color: #000000;
            font-size: 2.2rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .newsletter p {
            color: #000000;
            font-size: 1.1rem;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .newsletter-form {
            display: flex;
            gap: 1rem;
            max-width: 400px;
            margin: 0 auto;
        }

        .newsletter-input {
            flex: 1;
            padding: 1rem 1.5rem;
            border: none;
            border-radius: 25px;
            font-size: 1rem;
            background: #000000;
            backdrop-filter: blur(10px);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .newsletter-input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .newsletter-input:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.4);
            background: #000000;
        }

        .newsletter-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .newsletter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .footer {
            background: rgba(0, 0, 0, 0.2);
            color: white;
            text-align: center;
            padding: 2rem;
            margin-top: 4rem;
        }

        /* New Hero Section Styles */
        .hero-content {
            text-align: center;
            max-width: 800px;
            margin: 0 auto;
        }

        .hero-stats {
            display: flex;
            justify-content: center;
            gap: 3rem;
            margin-top: 3rem;
        }

        .stat {
            text-align: center;
            color: white;
        }

        .stat-number {
            display: block;
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 1rem;
            opacity: 0.9;
        }

        /* Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        /* Categories Section */
        .categories {
            padding: 4rem 0;
            background: rgba(255, 255, 255, 0.05);
        }

        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .category-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2.5rem 2rem;
            text-align: center;
            color: white;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .category-card:hover {
            transform: translateY(-10px);
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        .category-icon {
            font-size: 3.5rem;
            margin-bottom: 0.5rem;
            display: block;
        }

        .category-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .category-card p {
            opacity: 0.9;
            line-height: 1.6;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .section-header {
                flex-direction: column;
                gap: 1.5rem;
                align-items: flex-start;
            }
            
            .section-right {
                width: 100%;
                justify-content: flex-end;
            }
            
            .sorting-controls {
                flex-wrap: wrap;
                gap: 0.8rem;
            }
        }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .nav-links {
                display: none;
            }
            
            .search-form {
                grid-template-columns: 1fr;
            }
            
            .nav {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
                justify-content: center;
            }
            
            .logo {
                position: static;
                order: -1;
            }
            
            .header-search {
                flex-wrap: wrap;
                max-width: 100%;
                padding: 0.3rem;
                gap: 0.2rem;
            }
            
            .search-field {
                min-width: 100px;
                padding: 0.6rem 0.8rem;
                font-size: 0.8rem;
            }
            
            .search-divider {
                display: none;
            }
            
            .header-search-btn {
                padding: 0.6rem 1rem;
                font-size: 0.8rem;
            }
            
            .logo-image {
                height: 32px;
            }
            
            /* Hero Stats Mobile */
            .hero-stats {
                flex-direction: column;
                gap: 2rem;
                margin-top: 2rem;
            }
            
            .stat-number {
                font-size: 2rem;
            }
            
            /* Categories Mobile */
            .categories-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .category-card {
                padding: 2rem 1.5rem;
            }
            
            .category-icon {
                font-size: 3rem;
            }
            
            /* Newsletter Mobile */
            .newsletter-form {
                flex-direction: column;
                max-width: 100%;
            }
            
            .newsletter-input {
                margin-bottom: 1rem;
            }
            
            .newsletter h2 {
                font-size: 1.8rem;
            }
        }
        
        @media (max-width: 480px) {
            .hero-stats {
                gap: 1.5rem;
            }
            
            .stat-number {
                font-size: 1.8rem;
            }
            
            .events-grid {
                grid-template-columns: 1fr;
            }
            
            .event-card {
                margin: 0 1rem;
            }
        }
        
        /* Sadece index (events-grid view-4) için: mobilde 2 sütun */
        @media (max-width: 768px) {
            .events-grid.view-4 {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem; /* mobilde aralık biraz küçülsün */
            }
            .events-grid.view-4 .event-card {
                margin: 0; /* iki sütunda taşma olmasın */
            }
        }
        
        @media (max-width: 480px) {
            .header-search {
                flex-direction: column;
                gap: 0.5rem;
                padding: 1rem;
            }
            
            .search-field {
                width: 100%;
                min-width: auto;
            }
            
            .header-search-btn {
                width: 100%;
                justify-content: center;
            }
        }

        /* Account Button */
        .account-btn {
            background: rgba(10, 10, 10, 0.88);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 0.8rem 1.2rem;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            font-size: 0.9rem;
            white-space: nowrap;
        }

        .account-btn:hover {
            background: rgba(39, 39, 39, 0.77);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            right: -100%;
            width: 100%;
            height: 100%;
            z-index: 9500; /* 1000 -> 9500: Alt barda önde, modalların (10000/20000) altında */
            transition: right 0.3s ease;
        }

        .sidebar.active {
            right: 0;
        }

        .sidebar-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
            cursor: pointer;
        }

        .sidebar-content {
            position: absolute;
            top: 0;
            right: 0;
            width: 350px;
            height: 100%;
            background: linear-gradient(180deg,rgba(255, 255, 255, 0.71) 0%,rgba(24, 23, 23, 0.6) 50%,rgba(0, 0, 0, 0.61) 100%);
            box-shadow: -10px 0 30px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
            color: white;
            transform: translateX(100%);
            color: white;
            transform: translateX(100%);
            transition: transform 0.3s ease;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior: contain;
        }

        .sidebar.active .sidebar-content {
            transform: translateX(0);
        }

        /* Sidebar aktifken arka plan kaymasını engelle */
        html.sidebar-open,
        body.sidebar-open {
            overflow: hidden;
        }

        .sidebar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 3rem 1.5rem 1rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
        }

        .sidebar-logo-img {
            height: 35px;
            width: auto;
            max-width: 195px;
            object-fit: contain;
            margin-left: 35px;
            margin-top: -18px;
        }

        /* Mobil için logo ayarları */
        @media (max-width: 768px) {
            .sidebar-logo-img {
                margin-left: 65px;
                margin-top: 5px;
            }
        }

        .cart-icon {
            color: white;
            text-decoration: none;
            padding: 0.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .cart-icon:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.1);
        }

        .sidebar-header h2 {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 600;
            color: white;
        }


        .ai-assistant-btn {
            position: absolute;
            top: 3.4rem;
            left: 1rem;
            background: linear-gradient(135deg, #091961 0%, #764ba2 100%);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 20px;
            padding: 8px 17px;
            display: flex;
            align-items: center;
            gap: 6px;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 10;
            font-size: 12px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .ai-assistant-btn:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }

        .ai-text {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        /* AI Modal Styles */
        .ai-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 10000;
        }

        .sidebar-body {
            flex: 1;
            padding: 1rem;
            overflow-y: auto;
        }

        /* Mobil Arama (Sidebar İçinde) */
        .mobile-search {
            display: none;
            margin-bottom: 2rem;
        }

        .mobile-search .header-search {
            max-width: 100%;
            margin: 0;
        }

        .account-options {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        /* Özel Giriş Yap Kartı */
        .login-card {
            background: linear-gradient(135deg,rgb(145, 148, 160) 0%,rgb(29, 20, 39) 100%);
            border-radius: 12px;
            padding: 0.5rem;
            margin-bottom: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            border: 0px solid rgba(255, 255, 255, 0);
        }

        .login-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(184, 185, 189, 0.4);
        }

        .login-card-content {
            text-align: center;
        }

        .login-card-content h3 {
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0 0 1rem 0;
            line-height: 1.3;
        }

        .login-card-btn {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 0.7rem 1.5rem;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            backdrop-filter: blur(10px);
        }

        .login-card-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
            transform: scale(1.05);
        }
        
        /* Sosyal Medya Bağlantıları */
        .sidebar-body {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
        }

        .account-options {
            margin-bottom: 20px;
        }

        .social-media-links {
            margin-top: auto;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }

        .social-media-links h3 {
            font-size: 1.2rem;
            margin-bottom: 15px;
            color: #f5f5f5;
        }

        .social-icons {
            display: flex;
            justify-content: center;
            gap: 15px;
        }

        .social-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.1);
            color: #f5f5f5;
            transition: all 0.3s ease;
        }

        .social-icon:hover {
            background-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-3px);
        }

        /* Sosyal medya ikonlarına özel renkler (isteğe bağlı) */
        .social-icon[title="Instagram"]:hover {
            background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
        }

        .social-icon[title="Facebook"]:hover {
            background: #1877f2;
        }

        .social-icon[title="Twitter/X"]:hover {
            background: #000;
        }

        /* User Welcome Styles */
        .user-welcome {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            text-align: center;
        }

        .user-welcome h3 {
            color: white;
            margin: 0 0 0.5rem 0;
            font-size: 1.1rem;
        }

        .user-type {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
            margin: 0;
        }

        /* Papilet Tarzı Butonlar */
        .account-option-btn {
            text-decoration: none;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 1rem 1.2rem;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 1rem;
            color: white;
            text-align: left;
            position: relative;
        }

        .account-option-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .logout-option {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.3);
        }

        .logout-option:hover {
            background: rgba(220, 53, 69, 0.2);
        }

        .option-icon {
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #E91E63;
            border-radius: 4px;
            flex-shrink: 0;
            font-size: 12px;
        }

        .option-content {
            flex: 1;
        }

        .option-content h3 {
            margin: 0;
            font-size: 0.95rem;
            font-weight: 500;
            color: white;
            line-height: 1.2;
        }

        .option-content p {
            display: none; /* Açıklamaları gizle */
        }

        .option-arrow {
            font-size: 1.2rem;
            opacity: 0.6;
            transition: all 0.2s ease;
            color: #E91E63;
            display: none;
        }
        
        /* Sepet Dropdown Stilleri */
        .cart-container {
            position: absolute;
            top: 2.9rem;
            right: 1.5rem;
        }
        
        .cart-icon {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 0.5rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .cart-icon:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .cart-count {
            background: #E91E63;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 11px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            position: absolute;
            top: -5px;
            right: -5px;
            min-width: 18px;
        }
        
        .cart-dropdown {
            position: fixed;
            top: 80px;
            right: 10px;
            width: 330px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            max-height: 500px;
            overflow: hidden;
        }
        
        .cart-dropdown.active {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .cart-dropdown-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .cart-dropdown-header h3 {
            margin: 0;
            color: #1a1a1a;
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .cart-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #666;
            cursor: pointer;
            padding: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .cart-close:hover {
            color: #333;
        }
        
        .cart-dropdown-items {
            max-height: 300px;
            overflow-y: auto;
            padding: 0.5rem;
        }
        
        .cart-dropdown-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            background: #f8f9fa;
            transition: all 0.3s;
        }
        
        .cart-dropdown-item:hover {
            background: #e9ecef;
        }
        
        .cart-item-info {
            flex: 1;
            min-width: 0;
        }
        
        .cart-item-name {
            font-weight: 600;
            color: #1a1a1a;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .cart-item-details {
            font-size: 0.8rem;
            color: #666;
            margin-bottom: 0.25rem;
        }
        
        .cart-item-price {
            font-weight: 600;
            color: #00C896;
            font-size: 0.9rem;
        }
        
        .cart-item-remove {
            background: #dc3545;
            color: white;
            border: none;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .cart-item-remove:hover {
            background: #c82333;
            transform: scale(1.1);
        }
        
        .cart-dropdown-footer {
            padding: 1rem;
            border-top: 1px solid #f0f0f0;
            background: #f8f9fa;
        }
        
        .cart-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            font-weight: 600;
            color: #1a1a1a;
            font-size: 1.1rem;
        }
        
        .cart-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-clear,
        .btn-cart {
            flex: 1;
            padding: 0.75rem;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
        }
        
        .btn-clear {
            background: #6c757d;
            color: white;
        }
        
        .btn-clear:hover {
            background: #5a6268;
        }
        
        .btn-cart {
            background: #667eea;
            color: white;
        }
        
        .btn-cart:hover {
            background: #5a6fd8;
            color: white;
        }
        
        .cart-empty {
            text-align: center;
            padding: 2rem 1rem;
            color: #666;
        }
        
        .cart-empty-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            opacity: 0.5;
        }
        
        .cart-empty p {
            margin: 0;
            font-size: 0.9rem;
        }

        .account-option-btn:hover .option-arrow {
            opacity: 1;
            transform: translateX(3px);
        }

        /* Özel İkonlar */
        .account-option-btn:nth-child(1) .option-icon {
            background: #E91E63;
        }

        .account-option-btn:nth-child(1) .option-icon::before {
            content: '👤';
            font-size: 12px;
        }

        .account-option-btn:nth-child(2) .option-icon {
            background: #9C27B0;
        }

        .account-option-btn:nth-child(2) .option-icon::before {
            content: '📝';
            font-size: 12px;
        }

        .account-option-btn:nth-child(3) .option-icon {
            background: #673AB7;
        }

        .account-option-btn:nth-child(3) .option-icon::before {
            content: '🎪';
            font-size: 12px;
        }

        /* Ek Menü Öğeleri */
        .menu-section {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .menu-item {
            background: transparent;
            border: none;
            color: rgba(255, 255, 255, 0.8);
            padding: 0.8rem 1.2rem;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 1rem;
            width: 100%;
            text-align: left;
            border-radius: 6px;
            font-size: 0.9rem;
        }

        .menu-item:hover {
            background: rgba(255, 255, 255, 0.05);
            color: white;
        }

        .menu-item-icon {
            width: 16px;
            height: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0.7;
        }

        .menu-section h3 {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 0.5rem;
            padding: 0 1.2rem;
        }

        .menu-item-content {
            flex: 1;
        }

        .menu-item-content span {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
        }

        .menu-item-arrow {
            color: rgba(255, 255, 255, 0.4);
            font-size: 1.2rem;
            transition: all 0.2s ease;
        }

        .menu-item:hover .menu-item-arrow {
            color: rgba(255, 255, 255, 0.8);
            transform: translateX(3px);
        }

        .menu-item:hover .menu-item-content span {
            color: white;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .mobile-menu-btn {
                display: block;
            }
            
            .desktop-nav {
                display: none;
            }
            
            /* Mobilde hesap butonunu gizle */
            .account-btn {
                display: none;
            }
            
            .nav {
                padding: 0 1rem;
                justify-content: space-between; /* Logo sol, menü sağ */
            }
            
            .logo {
                order: 1; /* Logo sola */
            }
            
            .mobile-menu-btn {
                order: 2; /* Menü butonu sağa */
                margin-left: auto;
                margin-top: -10px;
                background: #000000c9;
            }
            
            .logo-image {
                height: 35px;
            }
            
            /* Mobilde dropdown sola doğru açılsın */
            .dropdown-content {
                right: 0;
                left: auto;
                top: auto;
            }
            
            .sidebar-content {
                width: 100%;
                max-width: 350px;
            }
            
            .mobile-search {
                display: block;
            }
        }

        @media (max-width: 480px) {
            .nav {
                padding: 0 0.5rem;
                justify-content: space-between;
            }
            
            .logo-image {
                height: 32px;
                margin-bottom: -65px;
                margin-left: -180px;
            }
            
            .sidebar-content {
                width: 100%;
                max-width: 100%;
            }
            
            .sidebar-header {
                padding: 1.5rem 1rem;
            }
            
            .sidebar-body {
                padding: 1rem;
            }
        }

                /* Hero Slider Styles */
        .hero-slider {
            position: relative;
            height: 350px; /* 500px yerine 350px */
            overflow: hidden;
            margin-bottom: 2rem;
            touch-action: pan-y; /* Dokunmatik kaydırma için */
            cursor: grab; /* Kaydırılabilir olduğunu göstermek için el imleci */
        }

        .slider-container {
            position: relative;
            width: 100%;
            height: 100%;
            display: flex; /* Yatay kaydırma için */
            transition: transform 0.3s ease-out;
            will-change: transform;
        }

        .slide {
            position: relative;
            width: 100%; /* min-width yerine width */
            height: 100%;
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 1;
            flex-shrink: 0;
            flex-grow: 0;
            flex-basis: 100%; /* Tam genişlik garantisi */
        }

        /* Aktif slide stilini kaldır çünkü artık hepsi görünür olacak */
        .slide.active {
            opacity: 1;
        }

        .slide-content {
            text-align: center;
            color: white;
            z-index: 2;
        }

        .slide-content h2 {
            font-size: 3rem;
            margin-bottom: 1rem;
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }

        .slide-content p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
        }

        .slide-btn {
            background: #333;
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 1rem 2rem;
            border-radius: 25px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .slide-btn:hover {
            background: #555;
        }

        .slider-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            z-index: 3;
        }

        .nav-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            font-size: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .nav-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }

        .nav-btn.prev {
            left: 2rem;
        }

        .nav-btn.next {
            right: 2rem;
        }




        /* Quick Categories Styles */
        .quick-categories {
            padding: 2rem 0;
            background: rgba(255, 255, 255, 0.02);
        }

        .category-buttons {
            display: flex;
            justify-content: center;
            gap: 2rem;
            flex-wrap: wrap;
        }

        .category-btn {
            background: transparent;
            backdrop-filter: none;
            border: none;
            border-radius: 0;
            padding: 1.2rem;
            color: black;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: center;
            gap: 0.8rem;
            min-width: 120px;
            opacity: 1;
            transform: none;
            box-shadow: none;
        }

        .category-btn:hover {
            background: #8B5CF6;
            backdrop-filter: none;
            border: none;
            border-radius: 15px;
            padding: 1rem;
            color: white;
            gap: 0.5rem;
            min-width: 80px;
            transform: none;
            box-shadow: none;
        }

        .category-btn-icon {
            font-size: 1.5rem;
            filter: none;
            color: black;
            transition: all 0.3s ease;
        }

        .category-btn:hover .category-btn-icon {
            font-size: 1.5rem;
            color: white;
            filter: none;
        }

        .category-icon {
            width: 40px;
            height: 40px;
            transition: all 0.3s ease;
        }

        .category-btn:hover .category-icon {
            filter: brightness(0) invert(1);
        }

        .category-btn span {
            font-size: 1rem;
            font-weight: 600;
            text-shadow: none;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            color: black;
        }

        .category-btn:hover span {
            font-size: 1rem;
            font-weight: 500;
            color: white;
            text-shadow: none;
        }



        /* Hover durumunda da aynı stil */
        .category-btn[data-category="konser"]:hover .category-btn-icon {
            color: white;
            filter: none;
        }

        /* Kategori butonlarına özel ikonlar - CSS'teki ::before tanımları kaldırıldı */
        /* HTML'deki emojiler kullanılacak */

        /* Hover animasyonu için JavaScript tetikleyicisi */
        .category-btn.visible {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
        
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
        }
        
        .feature-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            background: rgba(255, 255, 255, 0.1);
        }
        
        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
        }
        
        .feature-card h3 {
            font-size: 1.3rem;
            margin-bottom: 1rem;
            color: white;
        }
        
        .feature-card p {
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.6;
        }
        
        
        
        .pricing-cards {
            display: flex;
            justify-content: center;
            gap: 2rem;
            max-width: 900px;
            margin: 0 auto;
        }
        
        .pricing-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 2.5rem 2rem;
            flex: 1;
            text-align: center;
            position: relative;
        }
        
        .pricing-card.featured {
            background: rgba(142, 45, 226, 0.15);
            border-color: rgba(142, 45, 226, 0.3);
            transform: scale(1.05);
        }
        
        .pricing-badge {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, #8E2DE2, #4A00E0);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .pricing-title {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: white;
        }
        
        .pricing-price {
            font-size: 2.5rem;
            font-weight: 700;
            color: white;
            margin-bottom: 2rem;
        }
        
        .pricing-price span {
            font-size: 1rem;
            font-weight: 400;
            opacity: 0.7;
        }
        
        .pricing-features {
            list-style: none;
            margin-bottom: 2rem;
        }
        
        .pricing-features li {
            padding: 0.8rem 0;
            color: rgba(255, 255, 255, 0.8);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .pricing-btn {
            background: linear-gradient(135deg, #8E2DE2, #4A00E0);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .pricing-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(142, 45, 226, 0.3);
        }
        
        
        .testimonial-slider {
            display: flex;
            gap: 2rem;
            overflow-x: auto;
            padding: 1rem 0;
            scrollbar-width: none;
        }
        
        .testimonial-slider::-webkit-scrollbar {
            display: none;
        }
        
        .testimonial {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 2rem;
            min-width: 300px;
            flex: 1;
        }
        
        .testimonial-content {
            font-style: italic;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }
        
        .testimonial-author {
            font-weight: 600;
            color: white;
        }
        
        
        .cta-btn {
            background: linear-gradient(135deg, #8E2DE2, #4A00E0);
            color: white;
            border: none;
            padding: 1rem 3rem;
            border-radius: 30px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .cta-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(142, 45, 226, 0.3);
        }
        
        /* Mobile Responsive for Jack+ */
        @media (max-width: 768px) {
            .feature-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .pricing-cards {
                flex-direction: column;
            }
            
            .pricing-card.featured {
                transform: scale(1);
                margin: 2rem 0;
            }
            
        }
        
        @media (max-width: 480px) {
            .feature-grid {
                grid-template-columns: 1fr;
            }
            
            .testimonial {
                min-width: 260px;
            }
        }



        /* Animasyonlar */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .hero-slider {
                height: 300px;
            }
            
            .slide-content {
                padding: 0 1rem;
                max-width: 90%;
            }
            
            .slide-content h2 {
                font-size: 1.8rem;
                line-height: 1.2;
                margin-bottom: 0.8rem;
            }
            
            .slide-content p {
                font-size: 0.9rem;
                margin-bottom: 1.5rem;
                line-height: 1.4;
            }

            .slide-btn {
                padding: 0.8rem 1.5rem;
                font-size: 0.9rem;
            }

            .nav-btn {
                width: 40px;
                height: 40px;
                font-size: 1.2rem;
            }

            .nav-btn.prev {
                left: 1rem;
            }

            .nav-btn.next {
                right: 1rem;
            }
            


            .quick-categories {
                padding: 1rem 0;
            }

            .category-buttons {
                display: flex;
                justify-content: flex-start;
                overflow-x: auto;
                overflow-y: hidden;
                gap: 1rem;
                margin-left: -25px;
                flex-wrap: nowrap;
                scroll-behavior: smooth;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: none;
                -ms-overflow-style: none;
            }

            .category-buttons::-webkit-scrollbar {
                display: none;
            }

            .category-btn {
                padding: 0.8rem 1rem;
                min-width: 120px;
                flex-shrink: 0;
                white-space: nowrap;
            }

            .category-btn-icon {
                font-size: 1.2rem;
            }

            .category-btn span {
                font-size: 0.8rem;
            }
            
            .modal-content {
                width: 95%;
                margin: 1rem;
            }
            
            .modal-body {
                padding: 1.5rem;
            }
            
            .form-options {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .hero-slider {
                height: 250px;
            }
            
            .slide-content {
                padding: 0 0.8rem;
                max-width: 95%;
            }

            .slide-content h2 {
                font-size: 1.4rem;
                line-height: 1.1;
                margin-bottom: 0.6rem;
            }
            
            .slide-content p {
                font-size: 0.8rem;
                margin-bottom: 1.2rem;
            }

            .slide-btn {
                padding: 0.7rem 1.2rem;
                font-size: 0.8rem;
            }

            .category-buttons {
                grid-template-columns: repeat(3, 1fr);
                gap: 0.5rem;
            }
            

            
            .cart-dropdown {
                top: 70px;
                right: 10px;
                left: 10px;
                width: auto;
                max-width: none;
            }
        }

        /* Google OAuth Button Styles */
        .social-divider {
            text-align: center;
            margin: 20px 0;
            position: relative;
        }

        .social-divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: rgba(255, 255, 255, 0.2);
        }

        .social-divider span {
            background: rgba(255, 255, 255, 0.9);
            padding: 0 15px;
            color: rgba(0, 0, 0, 0.7);
            font-size: 14px;
            position: relative;
            z-index: 1;
            border-radius: 20px;
        }

        .google-btn {
            background: white !important;
            color: #333 !important;
            border: 1px solid #dadce0 !important;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .google-btn:hover {
            background: #f8f9fa !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transform: translateY(-1px);
        }

        .google-btn:active {
            transform: translateY(0);
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
        }

    </style>
</head>
<body>
    <header class="header">
        <nav class="nav">
            <a href="index.php" class="logo">
                <img src="./uploads/logo.png" alt="BiletJack Logo" class="logo-image">
            </a>
            
            <!-- Desktop Navigation -->
            <div class="desktop-nav">
                <form class="header-search" method="GET" action="etkinlikler.php">
                    <input type="text" name="search" class="search-field" placeholder="Sanatçı, mekan, etkinlik ara...">
                    <button type="submit" class="header-search-btn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
                        </svg>
                        Ara
                    </button>
                </form>
                
                <!-- Desktop Account Button (sadece desktop'ta görünür) -->
                <button class="account-btn" onclick="openAccountSidebar()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                    </svg>
                    Hesap
                </button>
            </div>
            
            <!-- Mobile Menu Button (sadece mobilde görünür) -->
            <button class="mobile-menu-btn" onclick="openAccountSidebar()">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/>
                </svg>
            </button>
        </nav>
    </header>

    <!-- Account Sidebar -->
    <div id="accountSidebar" class="sidebar">
        <div class="sidebar-overlay" onclick="closeAccountSidebar()"></div>
        <div class="sidebar-content">
            <div class="sidebar-header">
                <button class="ai-assistant-btn" onclick="openAIModal()">
                    <span class="ai-text">BJ</span>
                </button>
                <div class="sidebar-logo">
                    <img src="uploads/logo.png" alt="BiletJack" class="sidebar-logo-img">
                </div>
                <?php if ($isLoggedIn && $userType === 'customer'): ?>
                <div class="cart-container">
                    <button class="cart-icon" onclick="toggleCartDropdown()" title="Sepetim">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12L8.1 13h7.45c.75 0 1.41-.41 1.75-1.03L21.7 4H5.21l-.94-2H1zm16 16c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                        </svg>
                        <span id="cartCount" class="cart-count">0</span>
                    </button>
                    
                    <!-- Sepet Dropdown -->
                    <div id="cartDropdown" class="cart-dropdown">
                        <div class="cart-dropdown-header">
                            <h3>Sepetim</h3>
                            <button class="cart-close" onclick="toggleCartDropdown()">&times;</button>
                        </div>
                        
                        <div id="cartDropdownItems" class="cart-dropdown-items">
                            <!-- Sepet öğeleri JavaScript ile doldurulacak -->
                        </div>
                        
                        <div class="cart-dropdown-footer">
                            <div class="cart-total">
                                <span>Toplam: </span>
                                <span id="cartTotal">₺0</span>
                            </div>
                            <div class="cart-actions">
                                <button onclick="clearCart()" class="btn-clear">Sepeti Temizle</button>
                                <a href="sepet.php" class="btn-cart">Sepete Git</a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <div class="sidebar-body">
                <!-- Mobile Search (sadece mobilde görünür) -->
                
                <div class="account-options">
                    <?php if ($isLoggedIn): ?>
                        <!-- Giriş yapmış kullanıcı menüsü -->
                        <div class="user-welcome">
                            <h3>Hoş geldiniz, <?php echo htmlspecialchars($currentUser['first_name']); ?>!</h3>
                            <p class="user-type"><?php 
                                switch($userType) {
                                    case 'admin': echo 'Yönetici'; break;
                                    case 'organizer': echo 'Organizatör'; break;
                                    case 'service': echo 'Servis'; break;
                                    case 'ad_agency': echo 'Reklam Ajansı'; break;
                                    case 'customer': echo 'Müşteri'; break;
                                    default: echo 'Kullanıcı';
                                }
                            ?></p>
                        </div>
                        
                        <?php if ($userType === 'admin'): ?>
                            <a href="admin/index.php" class="account-option-btn">
                                <div class="option-content">
                                    <h3>Admin Paneli</h3>
                                </div>
                                <div class="option-arrow"></div>
                            </a>
                        <?php elseif ($userType === 'organizer'): ?>
                            <?php if (isOrganizerApproved()): ?>
                                <a href="organizer/index.php" class="account-option-btn">
                                    <div class="option-content">
                                        <h3>Organizatör Paneli</h3>
                                    </div>
                                    <div class="option-arrow"></div>
                                </a>
                            <?php else: ?>
                                <a href="organizer/pending.php" class="account-option-btn">
                                    <div class="option-content">
                                        <h3>Başvuru Durumu</h3>
                                    </div>
                                    <div class="option-arrow"></div>
                                </a>
                            <?php endif; ?>
                        <?php elseif ($userType === 'service_provider' || $userType === 'service'): ?>
                            <a href="service_provider/index.php" class="account-option-btn">
                                <div class="option-content">
                                    <h3>Servis Paneli</h3>
                                </div>
                                <div class="option-arrow"></div>
                            </a>
                            <a href="service_provider/settings.php" class="account-option-btn">
                                <div class="option-content">
                                    <h3>Profilim</h3>
                                </div>
                                <div class="option-arrow"></div>
                            </a>
                        <?php elseif ($userType === 'ad_agency'): ?>
                            <a href="ad_agency/index.php" class="account-option-btn">
                                <div class="option-content">
                                    <h3>Reklam Ajansı Paneli</h3>
                                </div>
                                <div class="option-arrow"></div>
                            </a>
                            <a href="ad_agency/settings.php" class="account-option-btn">
                                <div class="option-content">
                                    <h3>Profilim</h3>
                                </div>
                                <div class="option-arrow"></div>
                            </a>
                        <?php elseif ($userType === 'customer'): ?>
                            <a href="customer/tickets.php" class="account-option-btn">
                                <div class="option-content">
                                    <h3>Biletlerim</h3>
                                </div>
                                <div class="option-arrow"></div>
                            </a>
                        <?php endif; ?>
                        
                        <!-- Ortak menü öğeleri -->
                        <a href="etkinlikler.php" class="account-option-btn">
                            <div class="option-content">
                                <h3>Etkinlikler</h3>
                            </div>
                            <div class="option-arrow"></div>
                        </a>
                        
                        <a href="auth/logout.php" class="account-option-btn logout-option">
                            <div class="option-content">
                                <h3>Çıkış Yap</h3>
                            </div>
                            <div class="option-arrow"></div>
                        </a>
                        
                    <?php else: ?>
                        <!-- Giriş yapmamış kullanıcı menüsü -->
                        <div class="login-card" onclick="showLoginForm()">
                            <div class="login-card-content">
                                <h3>Hesabınıza giriş yapın</h3>
                                <button class="login-card-btn">Giriş Yap</button>
                            </div>
                        </div>
                        
                        <a href="organizator.php" class="account-option-btn">
                            <div class="option-content">
                            <h3>Organizatör Kaydı</h3>
                            </div>
                            <div class="option-arrow"></div>
                        </a>
                    <?php endif; ?>

                    <a href="bize-katilin.php" class="account-option-btn">
                        <div class="option-content">
                        <h3>İş Birliği</h3>
                        </div>
                        <div class="option-arrow"></div>
                    </a>

                    <a href="hakkimizda.php" class="account-option-btn">
                        <div class="option-content">
                        <h3>Hakkımızda</h3>
                        </div>
                        <div class="option-arrow"></div>
                    </a>

                    <a href="iletisim.php" class="account-option-btn">
                        <div class="option-content">
                        <h3>İletişim</h3>
                        </div>
                        <div class="option-arrow"></div>
                    </a>
                </div>
                
                <!-- Sosyal Medya Bağlantıları -->
                <?php
                // Admin ayarlarından sosyal medya URL'lerini al
                $headerSocialUrls = [];
                try {
                    require_once __DIR__ . '/../config/database.php';
                    $headerDatabase = new Database();
                    $headerPdo = $headerDatabase->getConnection();
                    $headerStmt = $headerPdo->prepare("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('facebook_url', 'instagram_url', 'twitter_url')");
                    $headerStmt->execute();
                    while ($headerRow = $headerStmt->fetch(PDO::FETCH_ASSOC)) {
                        $headerSocialUrls[$headerRow['setting_key']] = $headerRow['setting_value'];
                    }
                } catch (Exception $e) {
                    // Hata durumunda boş array kullan
                }
                
                // En az bir sosyal medya URL'si varsa bölümü göster
                if (!empty($headerSocialUrls['instagram_url']) || !empty($headerSocialUrls['facebook_url']) || !empty($headerSocialUrls['twitter_url'])): ?>
                <div class="social-media-links">
                    <h3>Bizi Takip Edin</h3>
                    <div class="social-icons">
                        <?php if (!empty($headerSocialUrls['instagram_url'])): ?>
                        <a href="<?php echo htmlspecialchars($headerSocialUrls['instagram_url']); ?>" class="social-icon" title="Instagram" target="_blank" rel="noopener noreferrer">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
                            </svg>
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($headerSocialUrls['facebook_url'])): ?>
                        <a href="<?php echo htmlspecialchars($headerSocialUrls['facebook_url']); ?>" class="social-icon" title="Facebook" target="_blank" rel="noopener noreferrer">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($headerSocialUrls['twitter_url'])): ?>
                        <a href="<?php echo htmlspecialchars($headerSocialUrls['twitter_url']); ?>" class="social-icon" title="Twitter/X" target="_blank" rel="noopener noreferrer">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                            </svg>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Ticket Purchase Panel -->
            <div id="ticketPurchasePanel" class="ticket-purchase-panel" style="display: none;">
                <div class="purchase-header">
                    <button class="back-btn" onclick="closeTicketPurchase()">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
                        </svg>
                        Geri
                    </button>
                    <h3>Bilet Satın Al</h3>
                </div>
                
                <!-- Location Selection -->
                <div id="locationStep" class="purchase-step">
                    <h4>Konum Seçin</h4>
                    <div class="location-grid">
                        <button class="location-btn" onclick="selectLocation('istanbul')">
                            <span class="location-name">İstanbul</span>
                            <span class="location-count" id="istanbul-count">0 etkinlik</span>
                        </button>
                        <button class="location-btn" onclick="selectLocation('ankara')">
                            <span class="location-name">Ankara</span>
                            <span class="location-count" id="ankara-count">0 etkinlik</span>
                        </button>
                        <button class="location-btn" onclick="selectLocation('izmir')">
                            <span class="location-name">İzmir</span>
                            <span class="location-count" id="izmir-count">0 etkinlik</span>
                        </button>
                        <button class="location-btn" onclick="selectLocation('antalya')">
                            <span class="location-name">Antalya</span>
                            <span class="location-count" id="antalya-count">0 etkinlik</span>
                        </button>
                        <button class="location-btn" onclick="selectLocation('trabzon')">
                            <span class="location-name">Trabzon</span>
                            <span class="location-count" id="trabzon-count">0 etkinlik</span>
                        </button>
                    </div>
                </div>
                
                <!-- Events List -->
                <div id="eventsStep" class="purchase-step" style="display: none;">
                    <div class="step-header">
                        <button class="back-step-btn" onclick="backToLocationStep()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
                            </svg>
                        </button>
                        <h4 id="selectedLocationTitle">Etkinlikler</h4>
                    </div>
                    <div id="eventsList" class="events-list">
                        <!-- Events will be loaded here -->
                    </div>
                </div>
                
                <!-- Ticket Types -->
                <div id="ticketsStep" class="purchase-step" style="display: none;">
                    <div class="step-header">
                        <button class="back-step-btn" onclick="backToEventsStep()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
                            </svg>
                        </button>
                        <h4 id="selectedEventTitle">Bilet Türleri</h4>
                    </div>
                    <div id="ticketTypesList" class="ticket-types-list">
                        <!-- Ticket types will be loaded here -->
                    </div>
                </div>
                
                <!-- Quantity Selection -->
                <div id="quantityStep" class="purchase-step" style="display: none;">
                    <div class="step-header">
                        <button class="back-step-btn" onclick="backToTicketsStep()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
                            </svg>
                        </button>
                        <h4>Miktar Seçin</h4>
                    </div>
                    <div class="quantity-selection">
                        <div class="selected-ticket-info">
                            <h5 id="selectedTicketName"></h5>
                            <p id="selectedTicketPrice"></p>
                        </div>
                        <div class="quantity-controls">
                            <button class="quantity-btn" onclick="changeQuantity(-1)">-</button>
                            <span id="ticketQuantity">1</span>
                            <button class="quantity-btn" onclick="changeQuantity(1)">+</button>
                        </div>
                        <div class="total-price">
                            <span>Toplam: </span>
                            <span id="totalPrice">0 ₺</span>
                        </div>
                        <button class="payment-btn" onclick="proceedToPayment()">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M20 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/>
                            </svg>
                            Ödeme Yap
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- AI Assistant Modal -->
    <div id="aiModal" class="modal ai-modal">
        <div class="modal-overlay" onclick="closeAIModal()"></div>
        <div class="modal-content ai-modal-content">
            <div class="modal-header ai-modal-header">
                <div class="ai-title">
                    <img src="uploads/logo.png" alt="BiletJack" style="height: 25px; width: auto;">
                </div>
                <button class="modal-close" onclick="closeAIModal()">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body ai-modal-body">
                
                <!-- Chat Alanı -->
                <div class="ai-chat">
                    <div id="aiMessages" class="ai-messages"></div>
                    <div class="ai-input-row">
                        <input id="aiInput" type="text" placeholder="Sorunuzu yazın... (Örn: Etkinlik nasıl oluştururum?)" onkeydown="if(event.key==='Enter'){sendAIMessage();}" />
                        <button class="ai-send-btn" onclick="sendAIMessage()">Gönder</button>
                    </div>
                    <div id="aiSuggestions" class="ai-suggestions"></div>
                </div>

                <div class="ai-quick-actions">
                    <h3>Hızlı İşlemler</h3>
                    <div class="quick-action-buttons">
                        <button class="quick-action-btn" onclick="openTicketPurchase()">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M22 10V6c0-1.1-.9-2-2-2H4c-1.1 0-2 .9-2 2v4c1.1 0 2 .9 2 2s-.9 2-2 2v4c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2v-4c-1.1 0-2-.9-2-2s.9-2 2-2zm-9 7.5h-2v-2h2v2zm0-4h-2v-6h2v6z"/>
                            </svg>
                            Bilet Satın Al
                        </button>
                        <button class="quick-action-btn" onclick="handleQuickAction('tickets')">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M22 10V6c0-1.1-.9-2-2-2H4c-1.1 0-2 .9-2 2v4c1.1 0 2 .9 2 2s-.9 2-2 2v4c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2v-4c-1.1 0-2-.9-2-2s.9-2 2-2zm-9 7.5h-2v-2h2v2zm0-4h-2v-6h2v6z"/>
                            </svg>
                            Biletlerim
                        </button>
                        <button class="quick-action-btn" onclick="handleQuickAction('support')">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17h-2v-2h2v2zm2.07-7.75l-.9.92C13.45 12.9 13 13.5 13 15h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H8c0-2.21 1.79-4 4-4s4 1.79 4 4c0 .88-.36 1.68-.93 2.25z"/>
                            </svg>
                            Yardım
                        </button>
                        <button class="quick-action-btn" onclick="handleQuickAction('contact')">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                            </svg>
                            İletişim
                        </button>
                        <button class="quick-action-btn" onclick="handleQuickAction('organizer_register')">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                            </svg>
                            Organizatör Kayıt
                        </button>
                        <button class="quick-action-btn" onclick="handleQuickAction('partnership')">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm5 13l-1.41 1.41L12 12.83l-3.59 3.58L7 15l5-5 5 5z"/>
                            </svg>
                            İş Birliği
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Login Modal - Güncellenmiş form -->
    <div id="loginModal" class="modal">
        <div class="modal-overlay" onclick="closeModal('loginModal')"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h2>Giriş Yap</h2>
                <button class="modal-close" onclick="closeModal('loginModal')">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <div id="loginMessage" class="message" style="display: none;"></div>
                <form id="loginForm" class="login-form">
                    <div class="form-group">
                        <label for="login_email">E-posta</label>
                        <input type="email" id="login_email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="login_password">Şifre</label>
                        <input type="password" id="login_password" name="password" required>
                    </div>
                    <div class="form-options">
                        <label class="checkbox-label">
                            <input type="checkbox" name="remember">
                            <span class="checkmark"></span>
                            Beni hatırla
                        </label>
                        <a href="#" class="forgot-password">Şifremi unuttum</a>
                    </div>
                    <button type="submit" class="modal-btn primary">Giriş Yap</button>
                    
                    <div class="social-divider">
                        <span>veya</span>
                    </div>
                    
                    <button type="button" class="modal-btn google-btn" onclick="loginWithGoogle()">
                        <svg width="18" height="18" viewBox="0 0 24 24" style="margin-right: 8px;">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        Google ile Giriş Yap
                    </button>
                    

                </form>
                <div class="modal-footer">
                <p>Hesabınız yok mu? <a href="#" onclick="switchToRegister()">Kayıt ol</a></p>
                <p>Organizatör müsünüz? <a href="organizator.php">Organizatör Kaydı</a></p>
            </div>
            </div>
        </div>
    </div>



    <!-- Register Modal - Güncellenmiş form -->
    <div id="registerModal" class="modal">
        <div class="modal-overlay" onclick="closeModal('registerModal')"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h2>Müşteri Kayıt</h2>
                <button class="modal-close" onclick="closeModal('registerModal')">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <div id="registerMessage" class="message" style="display: none;"></div>
                <form id="registerForm" class="register-form">
                    <button type="button" class="modal-btn google-btn" onclick="registerWithGoogle()">
                        <svg width="18" height="18" viewBox="0 0 24 24" style="margin-right: 8px;">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        Google ile Kayıt Ol
                    </button>
                    

                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">Ad *</label>
                            <input type="text" id="first_name" name="first_name" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name">Soyad *</label>
                            <input type="text" id="last_name" name="last_name" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="reg_email">E-posta *</label>
                        <input type="email" id="reg_email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="reg_phone">Telefon</label>
                        <input type="tel" id="reg_phone" name="phone">
                    </div>
                    <div class="form-group">
                        <label for="reg_password">Şifre *</label>
                        <input type="password" id="reg_password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Şifre Tekrar *</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    <input type="hidden" name="user_type" value="customer">
                    <div class="form-options">
                        <label class="checkbox-label">
                            <input type="checkbox" name="terms" required>
                            <span class="checkmark"></span>
                            <a href="#" class="terms-link">Kullanım şartlarını</a> kabul ediyorum
                        </label>
                    </div>
                    <button type="submit" class="modal-btn primary">Kayıt Ol</button>
                </form>
                <div class="modal-footer">
                    <p>Zaten hesabınız var mı? <a href="#" onclick="switchToLogin()">Giriş yap</a></p>
                    <p>Organizatör müsünüz? <a href="organizator.php">Organizatör Kaydı</a></p>
                </div>
            </div>
        </div>
    </div>



    <script>

        
        // Kaydırmalı Slider JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            const sliderContainer = document.querySelector('.slider-container');
            const slides = document.querySelectorAll('.slide');
            
            // Slider elementleri yoksa çık
            if (!sliderContainer || slides.length === 0) {
                return;
            }
            
            const totalSlides = slides.length;
            
            let currentSlideIndex = 0;
            let startX;
            let currentX;
            let isDragging = false;
            let startTranslate = 0;
            
            // Dokunmatik ve fare olayları için dinleyiciler
            sliderContainer.addEventListener('mousedown', dragStart);
            sliderContainer.addEventListener('touchstart', dragStart);
            
            sliderContainer.addEventListener('mousemove', drag);
            sliderContainer.addEventListener('touchmove', drag);
            
            sliderContainer.addEventListener('mouseup', dragEnd);
            sliderContainer.addEventListener('touchend', dragEnd);
            sliderContainer.addEventListener('mouseleave', dragEnd);
            

            
            // Sürükleme başlangıcı
            function dragStart(e) {
                isDragging = true;
                startX = e.type.includes('mouse') ? e.pageX : e.touches[0].pageX;
                startTranslate = -currentSlideIndex * 100;
                sliderContainer.style.transition = 'none';
                sliderContainer.style.cursor = 'grabbing';
            }
            
            // Sürükleme
            function drag(e) {
                if (!isDragging) return;
                e.preventDefault();
                
                currentX = e.type.includes('mouse') ? e.pageX : e.touches[0].pageX;
                const diff = (currentX - startX) / sliderContainer.offsetWidth * 100;
                const translate = startTranslate + diff;
                
                sliderContainer.style.transform = `translateX(${translate}%)`;
            }
            
            // Sürükleme sonu
            function dragEnd() {
                if (!isDragging) return;
                isDragging = false;
                
                sliderContainer.style.transition = 'transform 0.3s ease-out';
                sliderContainer.style.cursor = 'grab';
                
                if (currentX === undefined) return;
                
                const diff = (currentX - startX) / sliderContainer.offsetWidth;
                
                if (diff < -0.2 && currentSlideIndex < totalSlides - 1) {
                    currentSlideIndex++;
                } else if (diff > 0.2 && currentSlideIndex > 0) {
                    currentSlideIndex--;
                }
                
                goToSlide(currentSlideIndex);
            }
            
            // Belirli bir slide'a git
            function goToSlide(index) {
                currentSlideIndex = index;
                sliderContainer.style.transform = `translateX(${-index * 100}%)`;
            }
            

            
            // Otomatik kaydırma
            setInterval(() => {
                if (!isDragging) {
                    currentSlideIndex = (currentSlideIndex + 1) % totalSlides;
                    goToSlide(currentSlideIndex);
                }
            }, 5000);
        });
        
        // Kategori butonları hover animasyonu
        document.addEventListener('DOMContentLoaded', function() {
            const categoryBtns = document.querySelectorAll('.category-btn');
            
            categoryBtns.forEach((btn, index) => {
                btn.addEventListener('mouseenter', function() {
                    this.classList.add('visible');
                });
                
                btn.addEventListener('mouseleave', function() {
                    setTimeout(() => {
                        this.classList.remove('visible');
                    }, 300);
                });
            });
        });
        
        // Category button interactions
        document.querySelectorAll('.category-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const category = this.dataset.category;
                // Etkinlikler sayfasına kategori ile yönlendir
                window.location.href = `etkinlikler.php?category=${category}`;
            });
        });

        // Sidebar Functions
        function openAccountSidebar() {
            // Sidebar'ı aç
            document.getElementById('accountSidebar').classList.add('active');

            // Mevcut scroll konumunu kaydet
            const scrollY = window.scrollY || document.documentElement.scrollTop || 0;
            document.body.dataset.scrollY = String(scrollY);

            // Body'yi sabitleyerek arkaplan kaymasını engelle
            document.body.style.position = 'fixed';
            document.body.style.top = `-${scrollY}px`;
            document.body.style.left = '0';
            document.body.style.right = '0';
            document.body.style.width = '100%';
            document.body.style.overflow = 'hidden';

            // Durum sınıfını ekle (CSS kilitleri için)
            document.body.classList.add('sidebar-open'); // sidebar açıkken body'e sınıf ekle
        }

        function closeAccountSidebar() {
            // Sidebar'ı kapat
            document.getElementById('accountSidebar').classList.remove('active');

            // Durum sınıfını kaldır
            document.body.classList.remove('sidebar-open'); // sidebar kapanınca sınıfı kaldır

            // Eski scroll konumunu al
            const scrollY = parseInt(document.body.dataset.scrollY || '0', 10) || 0;

            // Body stilini eski haline getir
            document.body.style.position = '';
            document.body.style.top = '';
            document.body.style.left = '';
            document.body.style.right = '';
            document.body.style.width = '';
            document.body.style.overflow = '';

            // Scroll'u geri yükle
            window.scrollTo(0, scrollY);
            delete document.body.dataset.scrollY;
        }

        function showLoginForm() {
    // Sidebar kapatılmıyor, modal direkt açılıyor
    openModal('loginModal');
}

        function showRegisterForm() {
            // Kayıt ol formunu göster
            window.location.href = 'register.php';
            closeAccountSidebar();
        }

        function showOrganizerForm() {
            // Organizatör kaydı formunu göster
            window.location.href = 'organizer_register.php';
            closeAccountSidebar();
        }

                // Modal Functions - Güncellenmiş
        function showLoginForm() {
            // Sidebar'ı kapatmıyoruz, modal direkt açılıyor
            openModal('loginModal');
        }

        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        function switchToRegister() {
            closeModal('loginModal');
            setTimeout(() => {
                openModal('registerModal');
            }, 300);
        }

        function switchToLogin() {
            closeModal('registerModal');
            setTimeout(() => {
                openModal('loginModal');
            }, 300);
        }

        // ESC tuşu ile modal kapatma
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal('loginModal');
                closeModal('registerModal');
            }
        });

        // ESC tuşu ile sidebar kapat
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeAccountSidebar();
                closeCartDropdown();
            }
        });
        
        // Sidebar swipe (kaydırma) ile kapatma
        let startX = 0;
        let currentX = 0;
        let isDragging = false;
        
        const sidebar = document.getElementById('accountSidebar');
        const sidebarContent = sidebar?.querySelector('.sidebar-content');
        
        if (sidebarContent) {
            sidebarContent.addEventListener('touchstart', function(e) {
                startX = e.touches[0].clientX;
                isDragging = true;
            });
            
            sidebarContent.addEventListener('touchmove', function(e) {
                if (!isDragging) return;
                currentX = e.touches[0].clientX;
                const diffX = currentX - startX;
                
                // Sağa doğru kaydırma (50px'den fazla)
                if (diffX > 50) {
                    closeAccountSidebar();
                    isDragging = false;
                }
            });
            
            sidebarContent.addEventListener('touchend', function() {
                isDragging = false;
            });
        }
        
        // Sidebar logo'ya tıklayınca sidebar'ı kapat
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarLogo = document.querySelector('.sidebar-logo-img');
            if (sidebarLogo) {
                sidebarLogo.addEventListener('click', function() {
                    closeAccountSidebar();
                });
                // Logo'yu tıklanabilir hale getir
                sidebarLogo.style.cursor = 'pointer';
            }
        });
        
        // Sepet işlevleri
        let cartDropdownOpen = false;

        window.toggleCartDropdown = function() {
            const dropdown = document.getElementById('cartDropdown');
            cartDropdownOpen = !cartDropdownOpen;
            
            if (cartDropdownOpen) {
                updateCartDropdown();
                dropdown.classList.add('active'); // açılırken görünür yap
            } else {
                dropdown.classList.remove('active');
            }
        }

        window.closeCartDropdown = function() {
            const dropdown = document.getElementById('cartDropdown');
            dropdown.classList.remove('active');
            cartDropdownOpen = false;
        }

        window.updateCartDropdown = function() {
            // Elemanlar yoksa (örn. müşteri değilse) güvenle çık
            const cartCount = document.getElementById('cartCount');
            const cartTotal = document.getElementById('cartTotal');
            const cartItems = document.getElementById('cartDropdownItems');
            if (!cartCount || !cartTotal || !cartItems) {
                return;
            }
        // ... existing code ...
        // Dropdown dışına tıklandığında kapat
        document.addEventListener('click', function(event) {
            const cartContainer = document.querySelector('.cart-container');
            if (cartDropdownOpen && cartContainer && !cartContainer.contains(event.target)) {
                closeCartDropdown();
            }
        });

            <?php if (isLoggedIn() && $userType === 'customer'): ?>
            // Veritabanından sepet verilerini çek
            fetch('ajax/cart.php?action=get')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const cart = data.items;
                    // Sepet sayısını güncelle
                    const totalItems = cart.reduce((sum, item) => sum + parseInt(item.quantity), 0);
                    cartCount.textContent = totalItems;
                    cartCount.style.display = totalItems > 0 ? 'flex' : 'none';
                    
                    // Toplam tutarı hesapla
                    const totalAmount = cart.reduce((sum, item) => sum + (parseFloat(item.price) * parseInt(item.quantity)), 0);
                    cartTotal.textContent = '₺' + totalAmount.toLocaleString('tr-TR');
                    
                    // Sepet öğelerini göster
                    if (cart.length === 0) {
                        cartItems.innerHTML = `
                            <div class="cart-empty">
                                <div class="cart-empty-icon">🛒</div>
                                <p>Sepetiniz boş</p>
                            </div>
                        `;
                    } else {
                        cartItems.innerHTML = cart.map((item) => `
                            <div class="cart-dropdown-item">
                                <div class="cart-item-info">
                                    <div class="cart-item-name">${item.event_name}</div>
                                    <div class="cart-item-details">${item.ticket_name} • ${item.quantity} adet</div>
                                    <div class="cart-item-price">₺${(parseFloat(item.price) * parseInt(item.quantity)).toLocaleString('tr-TR')}</div>
                                </div>
                                <button class="cart-item-remove" onclick="removeFromCartDropdown(${item.id})" title="Kaldır">
                                    ×
                                </button>
                            </div>
                        `).join('');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
            <?php else: ?>
            // Müşteri değilse (admin/organizator/diğer) sepet UI varsayılan boş olsun
            cartCount.textContent = '0';
            cartCount.style.display = 'none';
            cartTotal.textContent = '₺0';
            cartItems.innerHTML = `
                <div class="cart-empty">
                    <div class="cart-empty-icon">🛒</div>
                    <p>Sepetiniz boş</p>
                </div>
            `;
            <?php endif; ?>
        }
        
        window.removeFromCartDropdown = function(cartId) {
            <?php if (isLoggedIn()): ?>
            const formData = new FormData();
            formData.append('action', 'remove');
            formData.append('cart_id', cartId);
            
            fetch('ajax/cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateCartDropdown();
                } else {
                    alert('Hata: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
            <?php endif; ?>
        }
        
        window.clearCart = function() {
            <?php if (isLoggedIn()): ?>
            const formData = new FormData();
            formData.append('action', 'clear');
            
            fetch('ajax/cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateCartDropdown();
                    closeCartDropdown();
                } else {
                    alert('Hata: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
            <?php endif; ?>
        }
        
        window.addToCart = function(eventData) {
            // Giriş kontrolü
            <?php if (!isLoggedIn()): ?>
                alert('Bilet satın almak için giriş yapmanız gerekiyor!');
                // Login modalını aç
                openModal('loginModal');
                return;
            <?php endif; ?>
            
            // Veritabanına sepet öğesi ekle
            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('event_id', eventData.eventId);
            formData.append('ticket_type_id', eventData.ticketId);
            formData.append('event_name', eventData.eventName);
            formData.append('ticket_name', eventData.ticketName);
            formData.append('price', eventData.price);
            formData.append('quantity', eventData.quantity);
            
            fetch('ajax/cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateCartDropdown();
                    // Başarı mesajı göster
                    showCartNotification('Bilet sepete eklendi!');
                } else {
                    alert('Hata: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Bir hata oluştu. Lütfen tekrar deneyin.');
            });
        }
        
        function showCartNotification(message) {
            // Basit bildirim göster
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #00C896;
                color: white;
                padding: 1rem 1.5rem;
                border-radius: 8px;
                z-index: 10000;
                font-weight: 600;
                box-shadow: 0 4px 12px rgba(0, 200, 150, 0.3);
                animation: slideIn 0.3s ease;
            `;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }
        
        // Sayfa yüklendiğinde sepet sayısını güncelle
        document.addEventListener('DOMContentLoaded', function() {
            // Giriş yapmamış kullanıcıların sepetini temizle
            <?php if (!isLoggedIn()): ?>
                localStorage.removeItem('cart');
            <?php endif; ?>
            
            updateCartDropdown();
        });
        
        // Dropdown dışına tıklandığında kapat
        document.addEventListener('click', function(event) {
            const cartContainer = document.querySelector('.cart-container');
            if (cartDropdownOpen && cartContainer && !cartContainer.contains(event.target)) {
                closeCartDropdown();
            }
        });

        // Login Form Handler - Güncellenmiş
        document.getElementById('loginForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const messageDiv = document.getElementById('loginMessage');
            const submitBtn = this.querySelector('button[type="submit"]');
            
            // Loading state
            submitBtn.disabled = true;
            submitBtn.textContent = 'Giriş yapılıyor...';
            
            fetch('auth/login.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Login response:', data); // Debug için
                messageDiv.style.display = 'block';
                messageDiv.className = 'message ' + (data.success ? 'success' : 'error');
                messageDiv.textContent = data.message;
                
                if (data.success) {
                    setTimeout(() => {
                        // Ana sayfaya yönlendir ve sayfayı yenile
                        window.location.href = '/index.php';
                    }, 1500);
                } else {
                    // Reset button
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Giriş Yap';
                }
            })
            .catch(error => {
                console.error('Login error:', error); // Debug için
                messageDiv.style.display = 'block';
                messageDiv.className = 'message error';
                messageDiv.textContent = 'Bir hata oluştu. Lütfen tekrar deneyiniz.';
                
                // Reset button
                submitBtn.disabled = false;
                submitBtn.textContent = 'Giriş Yap';
            });
        });

        // Register Form Handler - Güncellenmiş
        document.getElementById('registerForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const password = document.getElementById('reg_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const messageDiv = document.getElementById('registerMessage');
            const submitBtn = this.querySelector('button[type="submit"]');
            
            if (password !== confirmPassword) {
                messageDiv.style.display = 'block';
                messageDiv.className = 'message error';
                messageDiv.textContent = 'Şifreler eşleşmiyor.';
                return;
            }
            
            if (password.length < 6) {
                messageDiv.style.display = 'block';
                messageDiv.className = 'message error';
                messageDiv.textContent = 'Şifre en az 6 karakter olmalıdır.';
                return;
            }
            
            // Loading state
            submitBtn.disabled = true;
            submitBtn.textContent = 'Kayıt yapılıyor...';
            
            const formData = new FormData(this);
            
            fetch('auth/register.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                messageDiv.style.display = 'block';
                messageDiv.className = 'message ' + (data.success ? 'success' : 'error');
                messageDiv.textContent = data.message;
                
                if (data.success) {
                    setTimeout(() => {
                        // Sayfayı yeniden yükle ki session verileri güncellensin
                        window.location.reload();
                    }, 1500);
                } else {
                    // Reset button
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Kayıt Ol';
                }
            })
            .catch(error => {
                messageDiv.style.display = 'block';
                messageDiv.className = 'message error';
                messageDiv.textContent = 'Bir hata oluştu. Lütfen tekrar deneyiniz.';
                
                // Reset button
                submitBtn.disabled = false;
                submitBtn.textContent = 'Kayıt Ol';
            });
        });

        // Google OAuth Functions
        function loginWithGoogle() {
            // Google OAuth Client ID'yi ayarlardan al
            fetch('auth/get_google_config.php')
                .then(response => response.json())
                .then(config => {
                    if (!config.client_id) {
                        alert('Google OAuth ayarları yapılmamış. Lütfen yönetici ile iletişime geçin.');
                        return;
                    }
                    
                    // Google OAuth URL'ini oluştur
                    const redirectUri = window.location.origin + '/auth/google_callback.php';
                    const scope = 'email profile';
                    const responseType = 'code';
                    
                    const googleAuthUrl = `https://accounts.google.com/o/oauth2/v2/auth?` +
                        `client_id=${config.client_id}&` +
                        `redirect_uri=${encodeURIComponent(redirectUri)}&` +
                        `scope=${encodeURIComponent(scope)}&` +
                        `response_type=${responseType}&` +
                        `state=login`;
                    
                    window.location.href = googleAuthUrl;
                })
                .catch(error => {
                    console.error('Google config error:', error);
                    alert('Google ile giriş yapılırken bir hata oluştu.');
                });
        }

        function registerWithGoogle() {
            // Google OAuth Client ID'yi ayarlardan al
            fetch('auth/get_google_config.php')
                .then(response => response.json())
                .then(config => {
                    if (!config.client_id) {
                        alert('Google OAuth ayarları yapılmamış. Lütfen yönetici ile iletişime geçin.');
                        return;
                    }
                    
                    // Google OAuth URL'ini oluştur
                    const redirectUri = window.location.origin + '/auth/google_callback.php';
                    const scope = 'email profile';
                    const responseType = 'code';
                    
                    const googleAuthUrl = `https://accounts.google.com/o/oauth2/v2/auth?` +
                        `client_id=${config.client_id}&` +
                        `redirect_uri=${encodeURIComponent(redirectUri)}&` +
                        `scope=${encodeURIComponent(scope)}&` +
                        `response_type=${responseType}&` +
                        `state=register`;
                    
                    window.location.href = googleAuthUrl;
                })
                .catch(error => {
                    console.error('Google config error:', error);
                    alert('Google ile kayıt olurken bir hata oluştu.');
                });
        }

        // WhatsApp Login Functions
        function openWhatsAppLogin() {
            closeModal('loginModal');
            openModal('whatsappLoginModal');
            resetWhatsAppLoginForm();
        }

        function resetWhatsAppLoginForm() {
            document.getElementById('whatsappLoginPhoneStep').style.display = 'block';
            document.getElementById('whatsappLoginVerificationStep').style.display = 'none';
            document.getElementById('whatsappLoginPhoneForm').reset();
            document.getElementById('whatsappLoginVerificationForm').reset();
            hideMessage('whatsappLoginMessage');
        }

        function backToLoginPhoneStep() {
            document.getElementById('whatsappLoginPhoneStep').style.display = 'block';
            document.getElementById('whatsappLoginVerificationStep').style.display = 'none';
        }

        // WhatsApp Quick Register Functions
        function openWhatsAppRegister() {
            closeModal('registerModal');
            openModal('whatsappRegisterModal');
            resetWhatsAppForm();
        }

        function resetWhatsAppForm() {
            document.getElementById('phoneStep').classList.add('active');
            document.getElementById('verificationStep').classList.remove('active');
            document.getElementById('phoneForm').reset();
            document.getElementById('verificationForm').reset();
            document.getElementById('userDetailsSection').style.display = 'none';
            document.getElementById('userDetailsSection').classList.remove('show');
            hideMessage('whatsappRegisterMessage');
        }

        function backToPhoneStep() {
            document.getElementById('phoneStep').classList.add('active');
            document.getElementById('verificationStep').classList.remove('active');
            document.getElementById('userDetailsSection').style.display = 'none';
            document.getElementById('userDetailsSection').classList.remove('show');
        }

        function verifyCode() {
            const code = document.getElementById('verification_code').value;
            
            if (!code || code.length !== 6) {
                showMessage('whatsappRegisterMessage', 'Lütfen 6 haneli doğrulama kodunu girin.', 'error');
                return;
            }
            
            // Simulate code verification (replace with actual API call)
            fetch('auth/verify_whatsapp_code.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    phone: currentPhoneNumber,
                    code: code,
                    token: verificationToken
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('whatsappRegisterMessage', 'Kod doğrulandı!', 'success');
                    document.getElementById('verifyCodeBtn').style.display = 'none';
                    document.getElementById('userDetailsSection').style.display = 'block';
                    setTimeout(() => {
                        document.getElementById('userDetailsSection').classList.add('show');
                    }, 100);
                } else {
                    showMessage('whatsappRegisterMessage', data.message || 'Doğrulama kodu hatalı.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('whatsappRegisterMessage', 'Bağlantı hatası oluştu.', 'error');
            });
        }

        let currentPhoneNumber = '';
        let verificationToken = '';
        let currentLoginPhoneNumber = '';
        let loginVerificationToken = '';

        // WhatsApp Login Phone form submission
        document.getElementById('whatsappLoginPhoneForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const phone = document.getElementById('whatsapp_login_phone').value;
            currentLoginPhoneNumber = '+90' + phone;
            
            if (!phone || phone.length < 10) {
                showMessage('whatsappLoginMessage', 'Lütfen geçerli bir telefon numarası girin.', 'error');
                return;
            }
            
            // Send verification code for login
            fetch('auth/send_whatsapp_login_code.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    phone: currentLoginPhoneNumber
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loginVerificationToken = data.token;
                    document.getElementById('whatsappLoginPhoneDisplay').textContent = 
                        `${currentLoginPhoneNumber} numarasına kod gönderildi`;
                    document.getElementById('whatsapp_login_token').value = data.token;
                    document.getElementById('whatsapp_login_phone_hidden').value = currentLoginPhoneNumber;
                    document.getElementById('whatsappLoginPhoneStep').style.display = 'none';
                    document.getElementById('whatsappLoginVerificationStep').style.display = 'block';
                    showMessage('whatsappLoginMessage', 'Doğrulama kodu WhatsApp\'tan gönderildi!', 'success');
                } else {
                    showMessage('whatsappLoginMessage', data.message || 'Kod gönderilirken hata oluştu.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('whatsappLoginMessage', 'Bağlantı hatası oluştu.', 'error');
            });
        });

        // WhatsApp Login Verification form submission
        document.getElementById('whatsappLoginVerificationForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const code = document.getElementById('whatsapp_login_verification_code').value;
            
            if (!code || code.length !== 6) {
                showMessage('whatsappLoginMessage', 'Lütfen 6 haneli doğrulama kodunu girin.', 'error');
                return;
            }
            
            // Verify code and login
            fetch('auth/verify_whatsapp_login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    phone: currentLoginPhoneNumber,
                    code: code,
                    token: loginVerificationToken
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('whatsappLoginMessage', 'Giriş başarılı! Yönlendiriliyor...', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    showMessage('whatsappLoginMessage', data.message || 'Giriş işlemi başarısız.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('whatsappLoginMessage', 'Bağlantı hatası oluştu.', 'error');
            });
        });

        function resendLoginCode() {
            if (!currentLoginPhoneNumber) {
                showMessage('whatsappLoginMessage', 'Telefon numarası bulunamadı.', 'error');
                return;
            }
            
            fetch('auth/send_whatsapp_login_code.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    phone: currentLoginPhoneNumber
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loginVerificationToken = data.token;
                    showMessage('whatsappLoginMessage', 'Yeni doğrulama kodu gönderildi!', 'success');
                } else {
                    showMessage('whatsappLoginMessage', data.message || 'Kod gönderilirken hata oluştu.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('whatsappLoginMessage', 'Bağlantı hatası oluştu.', 'error');
            });
        }

        // Phone form submission
        document.getElementById('phoneForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const phone = document.getElementById('whatsapp_phone').value;
            currentPhoneNumber = '+90' + phone;
            
            if (!phone || phone.length < 10) {
                showMessage('whatsappRegisterMessage', 'Lütfen geçerli bir telefon numarası girin.', 'error');
                return;
            }
            
            // Send verification code
            fetch('auth/send_whatsapp_code.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    phone: currentPhoneNumber
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    verificationToken = data.token;
                    document.getElementById('whatsapp_token').value = data.token;
                    document.getElementById('whatsapp_phone_hidden').value = currentPhoneNumber;
                    document.getElementById('sentToNumber').textContent = 
                        `${currentPhoneNumber} numarasına WhatsApp'tan gelen 6 haneli kodu girin`;
                    document.getElementById('phoneStep').classList.remove('active');
                    document.getElementById('verificationStep').classList.add('active');
                    showMessage('whatsappRegisterMessage', 'Doğrulama kodu WhatsApp\'tan gönderildi!', 'success');
                } else {
                    showMessage('whatsappRegisterMessage', data.message || 'Kod gönderilirken hata oluştu.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('whatsappRegisterMessage', 'Bağlantı hatası oluştu.', 'error');
            });
        });

        // Verification form submission
        document.getElementById('verificationForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const firstName = document.getElementById('quick_first_name').value;
            const lastName = document.getElementById('quick_last_name').value;
            const token = document.getElementById('whatsapp_token').value;
            const phone = document.getElementById('whatsapp_phone_hidden').value;
            const code = document.getElementById('verification_code').value;
            
            if (!firstName || !lastName) {
                showMessage('whatsappRegisterMessage', 'Ad ve soyad alanları zorunludur.', 'error');
                return;
            }
            
            // Register user
            fetch('auth/verify_whatsapp_register.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    phone: phone,
                    code: code,
                    token: token,
                    first_name: firstName,
                    last_name: lastName
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('whatsappRegisterMessage', 'Kayıt başarılı! Giriş yapılıyor...', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    showMessage('whatsappRegisterMessage', data.message || 'Kayıt işlemi başarısız.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('whatsappRegisterMessage', 'Bağlantı hatası oluştu.', 'error');
            });
        });

        // WhatsApp Login Phone form submission
        document.getElementById('whatsappLoginPhoneForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const phone = document.getElementById('whatsapp_login_phone').value;
            currentLoginPhoneNumber = '+90' + phone;
            
            if (!phone || phone.length < 10) {
                showMessage('whatsappLoginMessage', 'Lütfen geçerli bir telefon numarası girin.', 'error');
                return;
            }
            
            // Send verification code
            fetch('auth/send_whatsapp_login_code.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    phone: currentLoginPhoneNumber
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loginVerificationToken = data.token;
                    document.getElementById('loginSentToNumber').textContent = 
                        `${currentLoginPhoneNumber} numarasına WhatsApp'tan gelen 6 haneli kodu girin`;
                    document.getElementById('loginPhoneStep').classList.remove('active');
                    document.getElementById('loginVerificationStep').classList.add('active');
                    showMessage('whatsappLoginMessage', 'Doğrulama kodu WhatsApp\'tan gönderildi!', 'success');
                } else {
                    showMessage('whatsappLoginMessage', data.message || 'Kod gönderilirken hata oluştu.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('whatsappLoginMessage', 'Bağlantı hatası oluştu.', 'error');
            });
        });

        // WhatsApp Login Verification form submission
        document.getElementById('whatsappLoginVerificationForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const code = document.getElementById('whatsapp_login_verification_code').value;
            
            if (!code || code.length !== 6) {
                showMessage('whatsappLoginMessage', 'Lütfen 6 haneli doğrulama kodunu girin.', 'error');
                return;
            }
            
            // Verify code and login
            fetch('auth/verify_whatsapp_login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    phone: currentLoginPhoneNumber,
                    code: code,
                    token: loginVerificationToken
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('whatsappLoginMessage', 'Giriş başarılı! Yönlendiriliyorsunuz...', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    showMessage('whatsappLoginMessage', data.message || 'Giriş işlemi başarısız.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('whatsappLoginMessage', 'Bağlantı hatası oluştu.', 'error');
            });
        });

        function resendLoginCode() {
            if (!currentLoginPhoneNumber) {
                showMessage('whatsappLoginMessage', 'Telefon numarası bulunamadı.', 'error');
                return;
            }
            
            fetch('auth/send_whatsapp_login_code.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    phone: currentLoginPhoneNumber
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loginVerificationToken = data.token;
                    showMessage('whatsappLoginMessage', 'Yeni doğrulama kodu gönderildi!', 'success');
                } else {
                    showMessage('whatsappLoginMessage', data.message || 'Kod gönderilirken hata oluştu.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('whatsappLoginMessage', 'Bağlantı hatası oluştu.', 'error');
            });
        }

        function resendCode() {
            if (!currentPhoneNumber) {
                showMessage('whatsappRegisterMessage', 'Telefon numarası bulunamadı.', 'error');
                return;
            }
            
            fetch('auth/send_whatsapp_code.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    phone: currentPhoneNumber
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    verificationToken = data.token;
                    showMessage('whatsappRegisterMessage', 'Yeni doğrulama kodu gönderildi!', 'success');
                } else {
                    showMessage('whatsappRegisterMessage', data.message || 'Kod gönderilirken hata oluştu.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('whatsappRegisterMessage', 'Bağlantı hatası oluştu.', 'error');
            });
        }

        // AI Chat Assistant Logic
        

        function initAIChat() {
            const messages = document.getElementById('aiMessages');
            const suggestions = document.getElementById('aiSuggestions');
            if (messages && messages.childElementCount === 0) {
                appendAIMessage('Merhaba! Size yardımcı olmak için buradayım. Aşağıdaki önerilerden birini tıklayabilir veya sorunuzu yazabilirsiniz.');
            }
            if (suggestions) {
                suggestions.innerHTML = '';
                aiKB.suggestions.forEach(text => {
                    const btn = document.createElement('button');
                    btn.className = 'ai-suggestion';
                    btn.type = 'button';
                    btn.textContent = text;
                    btn.onclick = () => handleUserQuery(text);
                    suggestions.appendChild(btn);
                });
            }
        }

        function sendAIMessage() {
            const input = document.getElementById('aiInput');
            if (!input) return;
            const text = (input.value || '').trim();
            if (!text) return;
            input.value = '';
            handleUserQuery(text);
        }

        function handleUserQuery(text) {
            appendUserMessage(text);
            const reply = generateAIReply(text);
            appendAIMessage(reply.html || reply);
        }

        function appendUserMessage(text) {
            const messages = document.getElementById('aiMessages');
            if (!messages) return;
            const wrapper = document.createElement('div');
            wrapper.className = 'ai-message user';
            const inner = document.createElement('div');
            inner.className = 'ai-message-content';
            inner.textContent = text;
            wrapper.appendChild(inner);
            messages.appendChild(wrapper);
            messages.scrollTop = messages.scrollHeight;
        }

        function appendAIMessage(html) {
            const messages = document.getElementById('aiMessages');
            if (!messages) return;
            const wrapper = document.createElement('div');
            wrapper.className = 'ai-message assistant';
            const inner = document.createElement('div');
            inner.className = 'ai-message-content';
            inner.innerHTML = html;
            wrapper.appendChild(inner);
            messages.appendChild(wrapper);
            messages.scrollTop = messages.scrollHeight;
        }

        function normalize(tr) {
            return tr
                .toLowerCase()
                .replace(/ç/g, 'c')
                .replace(/ğ/g, 'g')
                .replace(/ı/g, 'i')
                .replace(/ö/g, 'o')
                .replace(/ş/g, 's')
                .replace(/ü/g, 'u');
        }

        function link(label, url) {
            return `<a href="${url}" class="ai-link">${label}</a>`;
        }

        function btn(label, action) {
            return `<button class="ai-inline-btn" onclick="${action}">${label}</button>`;
        }

        function generateAIReply(text) {
            const q = normalize(text);
            const contains = (arr) => arr.some(k => q.includes(normalize(k)));

            // Özel tarih yanıtı
            if (contains(['06.30.2007', '06 30 2007', '30.06.2007', '30 06 2007'])) {
                return { html: 'Seni hala çok seviyorum yavrum. Her zaman ne olursa olsun yanında olacağım. Güzel gözlerine iyi bak uykusuz kalma..' };
            }

            // Gülşah özel yanıtları
            if (text.toLowerCase().trim() === 'gülşah') {
                return { html: '🤍' };
            }
            
            if (contains(['gülşah kim'])) {
                return { html: 'Bazen en güzel duygular mesafelerle daha da anlamlı hale gelir. 💫' };
            }

            // Biletjack hakkında temel bilgiler
            if (contains(['biletjack', 'bilet jack', 'biletjack nedir', 'bilet jack nedir', 'site hakkında', 'platform hakkında', 'nedir bu site', 'ne işe yarar'])) {
                return {
                    html: `<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 12px; margin: 15px 0;">
                    <h3 style="margin: 0 0 15px 0; color: #fff;">🎫 Biletjack Nedir?</h3>
                    <p style="margin: 0; line-height: 1.6;">Biletjack, Türkiye'nin önde gelen etkinlik bilet satış platformudur. Konser, tiyatro, spor, festival ve daha birçok etkinlik için güvenli bilet satın alma imkanı sunar.</p>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 15px 0;">
                        <div style="background: #e8f5e8; padding: 15px; border-radius: 8px; border-left: 4px solid #28a745;">
                            <strong>🎯 Temel Özellikler:</strong><br>
                            • Güvenli bilet satın alma<br>
                            • QR kodlu dijital biletler<br>
                            • Bilet aktarma sistemi<br>
                            • JackPoint puan kazanma<br>
                            • Organizatör paneli
                        </div>
                        
                        <div style="background: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107;">
                            <strong>🎭 Etkinlik Türleri:</strong><br>
                            • Konserler ve müzik etkinlikleri<br>
                            • Tiyatro oyunları<br>
                            • Standup gösterileri<br>
                            • Spor etkinlikleri<br>
                            • Festivaller ve özel etkinlikler
                        </div>
                    </div>
                    
                    <div style="text-align: center; margin: 20px 0;">
                        ${btn('Etkinlikleri Keşfet', "window.location.href='etkinlikler.php'")}
                        ${btn('Organizatör Ol', "window.location.href='organizator.php'")}
                    </div>`
                };
            }

            if (contains(['nasıl çalışır', 'nasil calisir', 'nasıl kullanılır', 'nasil kullanilir', 'kullanım', 'kullanim'])) {
                return {
                    html: `<div style="background: #f8f9fa; padding: 20px; border-radius: 12px; margin: 15px 0;">
                    <h3 style="margin: 0 0 15px 0; color: #333;">🔄 Biletjack Nasıl Çalışır?</h3>
                    </div>
                    
                    <div style="background: #d1ecf1; padding: 15px; border-radius: 8px; border-left: 4px solid #17a2b8; margin: 15px 0;">
                        <strong>👤 Kullanıcılar İçin:</strong><br>
                        1️⃣ ${link('Etkinlikler', 'etkinlikler.php')} sayfasından istediğiniz etkinliği seçin<br>
                        2️⃣ Bilet türü ve adedini belirleyin<br>
                        3️⃣ Sepete ekleyip güvenli ödeme yapın<br>
                        4️⃣ QR kodlu biletinizi e-posta ile alın<br>
                        5️⃣ Etkinlik günü QR kodu ile giriş yapın
                    </div>
                    
                    <div style="background: #e8f5e8; padding: 15px; border-radius: 8px; border-left: 4px solid #28a745; margin: 15px 0;">
                        <strong>🎪 Organizatörler İçin:</strong><br>
                        1️⃣ ${link('Organizatör Kayıt', 'organizator.php')} ile başvuru yapın<br>
                        2️⃣ Onay sonrası etkinlik oluşturun<br>
                        3️⃣ Bilet türlerini ve fiyatlarını belirleyin<br>
                        4️⃣ Satışları takip edin ve QR kontrol yapın<br>
                        5️⃣ Gelirlerinizi yönetin
                    </div>`
                };
            }

            if (contains(['özellikler', 'ozellikler', 'neler yapabilir', 'fonksiyonlar', 'imkanlar'])) {
                return {
                    html: `<div style="background: linear-gradient(45deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 12px; margin: 15px 0;">
                    <h3 style="margin: 0 0 15px 0; color: #fff;">⚡ Biletjack Özellikleri</h3>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 15px 0;">
                        <div style="background: #e8f5e8; padding: 15px; border-radius: 8px;">
                            <strong>🎫 Bilet Sistemi:</strong><br>
                            • QR kodlu dijital biletler<br>
                            • Anında bilet teslimatı<br>
                            • Bilet aktarma özelliği<br>
                            • Mobil uyumlu biletler<br>
                            • Güvenli QR doğrulama
                        </div>
                        
                        <div style="background: #fff3cd; padding: 15px; border-radius: 8px;">
                            <strong>💳 Ödeme Sistemi:</strong><br>
                            • PayTR güvenli ödeme<br>
                            • 3D Secure desteği<br>
                            • Kredi/Banka kartı<br>
                            • Anında onay<br>
                            • SSL şifreleme
                        </div>
                        
                        <div style="background: #d1ecf1; padding: 15px; border-radius: 8px;">
                            <strong>⭐ JackPoint Sistemi:</strong><br>
                            • Her alımdan puan kazanın<br>
                            • Puanları indirim olarak kullanın<br>
                            • Sadakat programı<br>
                            • Özel kampanyalar<br>
                            • Hediye puanları
                        </div>
                        
                        <div style="background: #f8d7da; padding: 15px; border-radius: 8px;">
                            <strong>🎪 Organizatör Paneli:</strong><br>
                            • Kolay etkinlik oluşturma<br>
                            • Satış takibi ve raporlar<br>
                            • QR bilet kontrolü<br>
                            • Gelir yönetimi<br>
                            • Müşteri iletişimi
                        </div>
                    </div>`
                };
            }

            if (contains(['güvenli mi', 'guvenli mi', 'güvenlik', 'guvenlik', 'dolandırıcı', 'dolandirici', 'sahte', 'gerçek mi', 'gercek mi'])) {
                return {
                    html: `<div style="background: linear-gradient(135deg, #28a745, #20c997); color: white; padding: 20px; border-radius: 12px; margin: 15px 0;">
                    <h3 style="margin: 0 0 15px 0; color: #fff;">🔒 Biletjack Güvenlik</h3>
                    <p style="margin: 0; line-height: 1.6;">Biletjack %100 güvenli ve lisanslı bir platformdur. Kişisel verileriniz ve ödemeleriniz en üst düzeyde korunur.</p>
                    </div>
                    
                    <div style="background: #e8f5e8; padding: 15px; border-radius: 8px; border-left: 4px solid #28a745; margin: 15px 0;">
                        <strong>✅ Güvenlik Önlemleri:</strong><br>
                        • SSL şifreleme ile veri koruması<br>
                        • PayTR lisanslı ödeme altyapısı<br>
                        • 3D Secure doğrulama<br>
                        • KVKK uyumlu veri işleme<br>
                        • Düzenli güvenlik denetimleri<br>
                        • 7/24 sistem izleme
                    </div>
                    
                    <div style="background: #d1ecf1; padding: 15px; border-radius: 8px; border-left: 4px solid #17a2b8; margin: 15px 0;">
                        <strong>🛡️ Bilet Güvenliği:</strong><br>
                        • Benzersiz QR kodlar<br>
                        • Sahtecilik önleme sistemi<br>
                        • Gerçek zamanlı doğrulama<br>
                        • Bilet aktarma takibi<br>
                        • Organizatör onay sistemi
                    </div>
                    
                    <div style="text-align: center; margin: 20px 0;">
                        ${btn('Gizlilik Politikası', "window.location.href='gizlilik-politikasi.php'")}
                        ${btn('KVKK Bilgileri', "window.location.href='kvkk.php'")}
                    </div>`
                };
            }

            // Kişisel sohbet yanıtları
            if (contains(['merhaba', 'selam', 'hello', 'hi', 'hey'])) {
                const greetings = [
                    'Merhaba! Biletjack asistanınızım. Size nasıl yardımcı olabilirim? 😊',
                    'Selam! Bilet almak veya etkinlik hakkında bilgi almak için buradayım!',
                    'Merhaba! Hangi konuda yardıma ihtiyacınız var?',
                    'Selam! Size nasıl yardımcı olabilirim? Bilet alımı, etkinlik bilgileri veya başka bir konu...'
                ];
                return { html: greetings[Math.floor(Math.random() * greetings.length)] };
            }

            if (contains(['naber', 'nasılsın', 'nasil sin', 'ne haber', 'how are you'])) {
                const responses = [
                    'İyiyim, teşekkürler! Size nasıl yardımcı olabilirim? 🎫',
                    'Harika! Biletjack\'te size yardımcı olmaya hazırım. Hangi etkinlik sizi ilgilendiriyor?',
                    'Çok iyiyim! Bugün hangi etkinlik için bilet arıyorsunuz?',
                    'Süper! Size bilet konusunda nasıl yardımcı olabilirim?'
                ];
                return { html: responses[Math.floor(Math.random() * responses.length)] };
            }

            if (contains(['teşekkür', 'tesekkur', 'sağol', 'sagol', 'thanks', 'thank you'])) {
                const thanks = [
                    'Rica ederim! Başka bir sorunuz varsa çekinmeyin. 😊',
                    'Ne demek! Size yardımcı olabildiysem ne mutlu bana!',
                    'Bir şey değil! Biletjack\'te her zaman yardıma hazırım.',
                    'Memnun oldum! Başka ihtiyacınız olursa buradayım.'
                ];
                return { html: thanks[Math.floor(Math.random() * thanks.length)] };
            }

            if (contains(['günaydın', 'gunaydin', 'good morning'])) {
                return { html: 'Günaydın! Güzel bir gün etkinlik keşfetmek için harika! Size nasıl yardımcı olabilirim? ☀️' };
            }

            if (contains(['iyi akşamlar', 'iyi aksamlar', 'good evening'])) {
                return { html: 'İyi akşamlar! Akşam saatlerinde güzel etkinlikler var. Hangi tür etkinlik arıyorsunuz? 🌙' };
            }

            if (contains(['iyi geceler', 'good night'])) {
                return { html: 'İyi geceler! Yarın için güzel etkinlikler planlamayı unutmayın. Tatlı rüyalar! 🌟' };
            }

            if (contains(['nasıl gidiyor', 'nasil gidiyor', 'ne yapıyorsun', 'ne yapiyorsun'])) {
                return { html: 'Her şey harika gidiyor! Sürekli yeni etkinlikler ekleniyor ve müşterilerimize yardım ediyorum. Siz nasılsınız?' };
            }

            if (contains(['canım sıkılıyor', 'canim sikiliyor', 'sıkıldım', 'sikildim', 'bored'])) {
                return { html: `Canınız sıkılıyor mu? O zaman tam zamanı! ${link('Etkinlikler', 'etkinlikler.php')} sayfasından eğlenceli aktiviteler bulabilirsiniz. Müzik, tiyatro, standup... Ne tür etkinlik sizi mutlu eder? 🎭🎵` };
            }

            if (contains(['ne önerirsin', 'ne onerirsin', 'tavsiye', 'öneri', 'oneri'])) {
                return { html: `Size şunları önerebilirim:<br>• ${link('Popüler etkinlikler', 'etkinlikler.php')} sayfasından trend olanları keşfedin<br>• ${link('İndirimler', 'indirimler.php')} bölümünden fırsatları kaçırmayın<br>• Hangi şehirdesiniz? Size yakın etkinlikleri bulabilirim!` };
            }

            // Saat ve şehir kombinasyonları - basit yaklaşım
            if (contains(['trabzon', 'saat 8', '8 de', '8de']) && contains(['trabzon'])) {
                return { html: `Trabzon şehrinde saat 8:00 civarında başlayan etkinlikleri arıyorsunuz! 🕐<br><br>• ${link('Etkinlikler', 'etkinlikler.php')} sayfasından Trabzon ve saat filtrelerini kullanabilirsiniz<br>• Konserler genellikle 20:00-21:00 arası başlar<br>• Tiyatro oyunları çoğunlukla 19:30 veya 20:30'da<br>• Spor etkinlikleri değişken saatlerde olabilir<br><br>Hangi tür etkinlik arıyorsunuz? Konser, tiyatro, spor?` };
            }

            if (contains(['istanbul', 'saat', '20', '20:30', '21']) && contains(['istanbul'])) {
                return { html: `İstanbul şehrinde akşam saatlerinde başlayan etkinlikleri arıyorsunuz! 🕐<br><br>• ${link('Etkinlikler', 'etkinlikler.php')} sayfasından İstanbul ve saat filtrelerini kullanabilirsiniz<br>• Akşam konserleri çok popüler<br>• Tiyatro oyunları genellikle 19:30-20:30 arası<br>• Gece hayatı etkinlikleri de mevcut<br><br>Hangi tür etkinlik tercih edersiniz?` };
            }

            if (contains(['ankara', 'saat']) && contains(['ankara'])) {
                return { html: `Ankara şehrindeki etkinlikleri saat bazında arıyorsunuz! 🕐<br><br>• ${link('Etkinlikler', 'etkinlikler.php')} sayfasından Ankara ve saat filtrelerini kullanabilirsiniz<br>• Başkent'te her saatte etkinlik bulabilirsiniz<br>• Kültür merkezleri ve konser salonları aktif<br><br>Hangi saatte etkinlik arıyorsunuz?` };
            }

            if (contains(['saat 8', '8 de', '8de', 'saat 20', '20 de', '20de']) && !contains(['istanbul', 'ankara', 'izmir', 'trabzon', 'bursa'])) {
                return { html: `Belirli saatlerde başlayan etkinlikleri arıyorsunuz! 🕐<br><br>• ${link('Etkinlikler', 'etkinlikler.php')} sayfasından saat filtresini kullanabilirsiniz<br>• Hangi şehirde etkinlik arıyorsunuz?<br>• Konserler: Genellikle 20:00-21:00<br>• Tiyatro: 19:30 veya 20:30<br>• Spor: Değişken saatler<br><br>Şehir belirtirseniz daha spesifik öneriler verebilirim!` };
            }

            if (contains(['trabzon', 'istanbul', 'ankara', 'izmir', 'bursa', 'antalya']) && !contains(['saat'])) {
                const cities = ['trabzon', 'istanbul', 'ankara', 'izmir', 'bursa', 'antalya'];
                const foundCity = cities.find(city => userInput.toLowerCase().includes(city));
                if (foundCity) {
                    const cityName = foundCity.charAt(0).toUpperCase() + foundCity.slice(1);
                    return { html: `${cityName} şehrindeki etkinlikleri arıyorsunuz! 🏙️<br><br>• ${link('Etkinlikler', 'etkinlikler.php')} sayfasından ${cityName} filtresini kullanabilirsiniz<br>• Bu şehirdeki popüler mekanları görebilirsiniz<br>• Güncel etkinlik takvimini inceleyebilirsiniz<br><br>Hangi saatte veya hangi tür etkinlik arıyorsunuz?` };
                }
            }

            if (contains(['bilet nasil al', 'bilet satin', 'bilet alma', 'satin alma', 'odeme', 'sepet', 'nasil satin', 'nasil alirim', 'nasil alacagim', 'bilet almak', 'bilet satın almak', 'satın almak istiyorum', 'bilet almak istiyorum', 'bilet al', 'bilet alacağım', 'bilet alacaktım', 'bilet alıyorum', 'bilet alırım', 'bilet alabilir miyim', 'bilet alabilir', 'bilet alabilirim', 'bilet alınır', 'bilet alınıyor', 'bilet alacak', 'bilet alacaklar', 'bilet alacaksın', 'bilet alacaksınız', 'bilet alacakları', 'bilet alacağız', 'bilet alacağı', 'bilet alacağın', 'bilet alacağımız', 'bilet alacağınız', 'bilet alacağımı', 'bilet alacağını', 'bilet alacağımızı', 'bilet alacağınızı', 'bilet alacağımdan', 'bilet alacağından', 'bilet alacağımızdan', 'bilet alacağınızdan', 'bilet alacağıma', 'bilet alacağına', 'bilet alacağımıza', 'bilet alacağınıza', 'bilet alacağımla', 'bilet alacağınla', 'bilet alacağımızla', 'bilet alacağınızla', 'bilet alacağımı', 'bilet alacağını', 'bilet alacağımızı', 'bilet alacağınızı', 'bilet alacağımdan', 'bilet alacağından', 'bilet alacağımızdan', 'bilet alacağınızdan', 'bilet alacağıma', 'bilet alacağına', 'bilet alacağımıza', 'bilet alacağınıza', 'bilet alacağımla', 'bilet alacağınla', 'bilet alacağımızla', 'bilet alacağınızla', 'bilet aldım', 'bilet aldı', 'bilet aldık', 'bilet aldınız', 'bilet aldılar', 'bilet aldığım', 'bilet aldığı', 'bilet aldığımız', 'bilet aldığınız', 'bilet aldıkları', 'bilet alıyor', 'bilet alıyorsun', 'bilet alıyorsunuz', 'bilet alıyorlar', 'bilet alıyoruz', 'bilet alıyordu', 'bilet alıyordun', 'bilet alıyordunuz', 'bilet alıyorlardı', 'bilet alıyorduk', 'bilet alıyormuş', 'bilet alıyormuşsun', 'bilet alıyormuşsunuz', 'bilet alıyorlarmış', 'bilet alıyormuşuz', 'bilet alır', 'bilet alırsın', 'bilet alırsınız', 'bilet alırlar', 'bilet alırız', 'bilet alırdı', 'bilet alırdın', 'bilet alırdınız', 'bilet alırlardı', 'bilet alırdık', 'bilet alırmış', 'bilet alırmışsın', 'bilet alırmışsınız', 'bilet alırlarmış', 'bilet alırmışız', 'bilet alsın', 'bilet alsınlar', 'bilet alalım', 'bilet alın', 'bilet alsa', 'bilet alsak', 'bilet alsanız', 'bilet alsalar', 'bilet almalı', 'bilet almalıyım', 'bilet almalısın', 'bilet almalısınız', 'bilet almalılar', 'bilet almalıyız', 'bilet almalıydı', 'bilet almalıydın', 'bilet almalıydınız', 'bilet almalıydılar', 'bilet almalıydık', 'bilet almalıymış', 'bilet almalıymışsın', 'bilet almalıymışsınız', 'bilet almalılarmış', 'bilet almalıymışız', 'bilet alabilir', 'bilet alabilirsin', 'bilet alabilirsiniz', 'bilet alabilirler', 'bilet alabiliriz', 'bilet alabilirdi', 'bilet alabilirdin', 'bilet alabilirdiniz', 'bilet alabilirlerdi', 'bilet alabilirdik', 'bilet alabilirmiş', 'bilet alabilirmişsin', 'bilet alabilirmişsiniz', 'bilet alabilirlermiş', 'bilet alabilirmişiz'])) {
                return {
                    html: `Bilet satın almak için şu adımları izleyin:<br>
                    1) ${link('Etkinlikler', 'etkinlikler.php')} sayfasından bir etkinlik seçin.<br>
                    2) Etkinlik detayında bilet türünü ve adedi seçip sepete ekleyin.<br>
                    3) ${link('Sepet', 'sepet.php')} üzerinden bilgilerinizi kontrol edin ve ${link('Ödeme', 'odeme.php')} sayfasında işlemi tamamlayın.<br>
                    İsterseniz hemen ${btn('Hızlı satın alma panelini aç', 'openTicketPurchase()')} yapabilirim.`,
                };
            }

            if (contains(['bilet fiyat', 'fiyat', 'ne kadar', 'ucret', 'para', 'maliyet', 'bilet ucret', 'fiyatı', 'fiyatlar', 'fiyatları', 'ücret', 'ücreti', 'ücretler', 'ücretleri', 'kaç para', 'kaça', 'kaçtan', 'kaç lira', 'kaç tl', 'ne kadara', 'ne kadarlık', 'ne kadardan', 'maliyeti', 'maliyetler', 'maliyetleri', 'parası', 'paraları', 'bedel', 'bedeli', 'bedeller', 'bedelleri', 'tutar', 'tutarı', 'tutarlar', 'tutarları', 'değer', 'değeri', 'değerler', 'değerleri', 'bilet parası', 'bilet bedeli', 'bilet tutarı', 'bilet değeri', 'bilet maliyeti'])) {
                return {
                    html: `Bilet fiyatları etkinliğe göre değişir:<br>
                    • ${link('Etkinlikler', 'etkinlikler.php')} sayfasından fiyatları görüntüleyebilirsiniz<br>
                    • Her etkinliğin farklı bilet türleri ve fiyatları vardır<br>
                    • Erken rezervasyon indirimleri olabilir<br>
                    • İndirim kodları ile daha uygun fiyatlar yakalayabilirsiniz`,
                };
            }

            if (contains(['koltuk sec', 'koltuk secim', 'yer sec', 'oturma yeri', 'koltuk numarasi', 'hangi koltuk', 'koltuk seç', 'koltuk seçim', 'koltuk seçimi', 'koltuk seçer', 'koltuk seçerim', 'koltuk seçebilir', 'koltuk seçebilirim', 'koltuk seçiyorum', 'koltuk seçiyor', 'koltuk seçecek', 'koltuk seçeceğim', 'yer seç', 'yer seçim', 'yer seçimi', 'yer seçer', 'yer seçerim', 'yer seçebilir', 'yer seçebilirim', 'yer seçiyorum', 'yer seçiyor', 'yer seçecek', 'yer seçeceğim', 'oturma yerleri', 'oturma yerler', 'oturma yerini', 'oturma yerlerini', 'koltuk numaraları', 'koltuk numaralar', 'koltuk numarasını', 'koltuk numaralarını', 'hangi koltuğu', 'hangi koltukları', 'hangi koltuklar', 'koltuk nasıl', 'koltuk nasıl seçilir', 'koltuk nasıl seçerim', 'yer nasıl', 'yer nasıl seçilir', 'yer nasıl seçerim'])) {
                return {
                    html: `Koltuk seçimi için:<br>
                    • Etkinlik detay sayfasında salon planını görüntüleyin<br>
                    • Müsait koltuklar yeşil renkte gösterilir<br>
                    • İstediğiniz koltuğa tıklayarak seçim yapın<br>
                    • Seçtiğiniz koltuk sepete eklenir<br>
                    • Rezervasyon süresi sınırlıdır, hızlıca ödeme yapın`,
                };
            }

            if (contains(['bilet turu', 'bilet cesit', 'hangi bilet', 'bilet tip', 'bilet kategori', 'bilet türü', 'bilet türleri', 'bilet çeşit', 'bilet çeşiti', 'bilet çeşitleri', 'bilet tipi', 'bilet tipleri', 'bilet kategorisi', 'bilet kategorileri', 'hangi bileti', 'hangi biletleri', 'hangi biletler', 'bilet türü nedir', 'bilet çeşidi nedir', 'bilet tipi nedir', 'bilet kategorisi nedir', 'ne tür bilet', 'ne çeşit bilet', 'nasıl bilet', 'bilet seçenekleri', 'bilet seçeneği', 'bilet alternatifleri', 'bilet alternatifi'])) {
                return {
                    html: `Bilet türleri etkinliğe göre değişir:<br>
                    • VIP, Premium, Standart kategoriler olabilir<br>
                    • Koltuklu ve genel giriş seçenekleri<br>
                    • Öğrenci indirimi olan etkinlikler mevcut<br>
                    • Her bilet türünün farklı avantajları vardır<br>
                    • Etkinlik sayfasında tüm seçenekleri görebilirsiniz`,
                };
            }

            if (contains(['etkinlik nasil olustur', 'etkinlik olustur', 'yeni etkinlik', 'event olustur', 'etkinlik nasıl oluştur', 'etkinlik nasıl oluşturur', 'etkinlik nasıl oluştururum', 'etkinlik nasıl oluşturabilirim', 'etkinlik nasıl oluşturabiliriz', 'etkinlik oluştur', 'etkinlik oluşturmak', 'etkinlik oluşturur', 'etkinlik oluştururum', 'etkinlik oluşturacağım', 'etkinlik oluşturabilirim', 'etkinlik oluşturabilir', 'etkinlik oluşturdum', 'etkinlik oluşturdu', 'etkinlik oluşturduk', 'etkinlik oluşturdunuz', 'etkinlik oluşturdular', 'yeni etkinlikler', 'yeni etkinliği', 'yeni etkinlikleri', 'yeni etkinlik oluştur', 'yeni etkinlik oluşturmak', 'yeni etkinlik nasıl', 'event oluştur', 'event oluşturmak', 'event nasıl oluştur', 'event nasıl oluşturur', 'etkinlik ekle', 'etkinlik eklemek', 'etkinlik ekler', 'etkinlik eklerim', 'etkinlik ekliyorum', 'etkinlik ekleyeceğim', 'etkinlik ekleyebilirim', 'etkinlik ekleyebilir', 'etkinlik ekledim', 'etkinlik ekledi', 'etkinlik ekledik', 'etkinlik eklediniz', 'etkinlik eklediler'])) {
                return {
                    html: `Etkinlik oluşturmak için organizatör olmanız gerekir:<br>
                    1) ${link('Organizatör Ol', 'organizator.php')} sayfasından başvurun.<br>
                    2) Onay sonrası ${link('Organizatör Paneli', 'organizer/index.php')} içinde Etkinlikler bölümünden ‘Yeni Etkinlik’ oluşturabilirsiniz.<br>
                    3) Etkinlik bilgilerini doldurup kaydedin, durumu 'published' olduğunda sitede görünür.`,
                };
            }

            if (contains(['organiza', 'organizatör kayit', 'organizatör ol', 'organizer kayit', 'organizer ol', 'organizatör', 'organizatörler', 'organizatörü', 'organizatörleri', 'organizatörde', 'organizatörlerde', 'organizatörden', 'organizatörlerden', 'organizatöre', 'organizatörlere', 'organizatörün', 'organizatörlerin', 'organizatörle', 'organizatörlerle', 'organizatör kayıt', 'organizatör kayıtı', 'organizatör kayıtları', 'organizatör kayıt ol', 'organizatör kayıt olmak', 'organizatör olmak', 'organizatör olur', 'organizatör olurum', 'organizatör oluyorum', 'organizatör olacağım', 'organizatör olabilirim', 'organizatör olabilir', 'organizatör oldum', 'organizatör oldu', 'organizatör olduk', 'organizatör oldunuz', 'organizatör oldular', 'organizatör nasıl', 'organizatör nasıl olunur', 'organizatör nasıl olurum', 'organizatör nasıl olabilirim', 'organizer', 'organizerler', 'organizeri', 'organizerleri', 'organizerde', 'organizerlerde', 'organizerden', 'organizerlerden', 'organizere', 'organizerlere', 'organizerin', 'organizerlerin', 'organizerle', 'organizerlerle', 'organizer kayıt', 'organizer kayıtı', 'organizer kayıtları', 'organizer kayıt ol', 'organizer kayıt olmak', 'organizer olmak', 'organizer olur', 'organizer olurum', 'organizer oluyorum', 'organizer olacağım', 'organizer olabilirim', 'organizer olabilir', 'organizer oldum', 'organizer oldu', 'organizer olduk', 'organizer oldunuz', 'organizer oldular'])) {
                return {
                    html: `Organizatör olmak için ${link('Organizatör Kayıt', 'organizator.php')} sayfasındaki formu doldurun. Başvurunuz 'pending' statüsüne düşer ve onaylandığında size bilgi verilir.`,
                };
            }

            if (contains(['biletlerim', 'satin aldigim bilet', 'biletler', 'aldığım bilet', 'biletlerimi gör', 'bilet sorgula', 'biletlerimi', 'biletlerimiz', 'biletleriniz', 'biletlerin', 'satın aldığım bilet', 'satın aldığım biletler', 'satın aldığımız bilet', 'satın aldığımız biletler', 'satın aldığınız bilet', 'satın aldığınız biletler', 'aldığım biletler', 'aldığımız bilet', 'aldığımız biletler', 'aldığınız bilet', 'aldığınız biletler', 'aldıkları bilet', 'aldıkları biletler', 'biletlerimi göster', 'biletlerimi görüntüle', 'biletlerimizi gör', 'biletlerimizi göster', 'biletlerimizi görüntüle', 'biletlerinizi gör', 'biletlerinizi göster', 'biletlerinizi görüntüle', 'bilet sorgulama', 'bilet sorgusu', 'biletleri sorgula', 'biletleri sorgulama', 'biletleri sorgusu', 'bilet kontrol', 'bilet kontrolü', 'biletleri kontrol', 'biletleri kontrolü', 'bilet durumu', 'biletlerin durumu', 'biletlerim nerede', 'biletlerimiz nerede', 'biletleriniz nerede'])) {
                return { html: `Satın aldığınız biletleri ${link('Biletlerim', 'customer/tickets.php')} sayfasından görüntüleyebilirsiniz. Giriş yapmadıysanız önce giriş yapmanız istenir.` };
            }

            if (contains(['sepete ekle', 'sepet ekle', 'sepete at', 'sepete koy', 'sepete nasıl', 'sepete ekler', 'sepete eklerim', 'sepete ekleyim', 'sepete ekleyelim', 'sepete ekleyin', 'sepete eklesinler', 'sepete ekliyorum', 'sepete ekliyor', 'sepete ekliyoruz', 'sepete ekliyorsunuz', 'sepete ekliyorlar', 'sepete ekledi', 'sepete ekledim', 'sepete ekledi', 'sepete ekledik', 'sepete eklediniz', 'sepete eklediler', 'sepete ekleyeceğim', 'sepete ekleyecek', 'sepete ekleyeceğiz', 'sepete ekleyeceksiniz', 'sepete ekleyecekler', 'sepet ekler', 'sepet eklerim', 'sepet ekleyim', 'sepet ekleyelim', 'sepet ekleyin', 'sepet eklesinler', 'sepet ekliyorum', 'sepet ekliyor', 'sepet ekliyoruz', 'sepet ekliyorsunuz', 'sepet ekliyorlar', 'sepet ekledi', 'sepet ekledim', 'sepet ekledi', 'sepet ekledik', 'sepet eklediniz', 'sepet eklediler', 'sepet ekleyeceğim', 'sepet ekleyecek', 'sepet ekleyeceğiz', 'sepet ekleyeceksiniz', 'sepet ekleyecekler', 'sepete atarım', 'sepete atıyorum', 'sepete attım', 'sepete atacağım', 'sepete koyarım', 'sepete koyuyorum', 'sepete koydum', 'sepete koyacağım', 'sepete nasıl eklerim', 'sepete nasıl eklenir', 'sepete nasıl ekleyebilirim', 'sepete nasıl ekleyebilir', 'sepete nasıl ekliyorum', 'sepete nasıl ekliyor'])) {
                return {
                    html: `Sepete ekleme işlemi:<br>
                    • Etkinlik sayfasında bilet türünü seçin<br>
                    • Adet belirleyin (koltuklu etkinliklerde koltuk seçin)<br>
                    • 'Sepete Ekle' butonuna tıklayın<br>
                    • ${link('Sepet', 'sepet.php')} sayfasından kontrol edin<br>
                    • Ödeme işlemini tamamlayın`,
                };
            }

            if (contains(['ödeme nasıl', 'nasıl ödeme', 'ödeme yap', 'para öde', 'ödeme işlem', 'ödeme nasıl yapılır', 'ödeme nasıl yaparım', 'ödeme nasıl yapabilirim', 'ödeme nasıl yapıyorum', 'ödeme nasıl yapacağım', 'nasıl ödeme yaparım', 'nasıl ödeme yapabilirim', 'nasıl ödeme yapıyorum', 'nasıl ödeme yapacağım', 'nasıl ödeme yapılır', 'ödeme yaparım', 'ödeme yapıyorum', 'ödeme yapacağım', 'ödeme yapabilirim', 'ödeme yapabilir', 'ödeme yapar', 'ödeme yaptım', 'ödeme yaptı', 'ödeme yaptık', 'ödeme yaptınız', 'ödeme yaptılar', 'para öderim', 'para ödüyorum', 'para ödeyeceğim', 'para ödeyebilirim', 'para ödeyebilir', 'para öder', 'para ödedim', 'para ödedi', 'para ödedik', 'para ödediniz', 'para ödediler', 'ödeme işlemi', 'ödeme işlemleri', 'ödeme işlemini', 'ödeme işlemlerini', 'ödeme işlemi nasıl', 'ödeme işlemleri nasıl', 'ödeme işlemini nasıl', 'ödeme işlemlerini nasıl'])) {
                return {
                    html: `Ödeme işlemi için:<br>
                    • Sepetinizi kontrol edin<br>
                    • ${link('Ödeme', 'odeme.php')} sayfasına gidin<br>
                    • Kişisel bilgilerinizi doldurun<br>
                    • Kredi kartı bilgilerini girin<br>
                    • 3D Secure ile güvenli ödeme yapın<br>
                    • E-posta ile biletlerinizi alın`,
                };
            }

            if (contains(['bilet gelmiyor', 'bilet gelmedi', 'e-posta gelmiyor', 'mail gelmiyor', 'bilet nerede'])) {
                return {
                    html: `Biletiniz gelmiyorsa:<br>
                    • Spam/Gereksiz klasörünü kontrol edin<br>
                    • ${link('Biletlerim', 'customer/tickets.php')} sayfasından indirin<br>
                    • Ödeme başarılı mı kontrol edin<br>
                    • ${link('İletişim', 'iletisim.php')} üzerinden destek alın<br>
                    • Sipariş numaranızı hazır bulundurun`,
                };
            }

            if (contains(['iade', 'iptal', 'bilet iptal', 'bilet iade', 'geri iade', 'para iade', 'iadesi', 'iadeler', 'iadesini', 'iadelerini', 'iade etmek', 'iade eder', 'iade ederim', 'iade ediyorum', 'iade edeceğim', 'iade edebilirim', 'iade edebilir', 'iade ettim', 'iade etti', 'iade ettik', 'iade ettiniz', 'iade ettiler', 'iade nasıl', 'iade nasıl yapılır', 'iade nasıl yaparım', 'iade nasıl yapabilirim', 'iptal etmek', 'iptal eder', 'iptal ederim', 'iptal ediyorum', 'iptal edeceğim', 'iptal edebilirim', 'iptal edebilir', 'iptal ettim', 'iptal etti', 'iptal ettik', 'iptal ettiniz', 'iptal ettiler', 'iptal nasıl', 'iptal nasıl yapılır', 'iptal nasıl yaparım', 'iptal nasıl yapabilirim', 'bilet iptali', 'bilet iptalı', 'bilet iptalini', 'bilet iptallerini', 'bilet iadesi', 'bilet iadesini', 'bilet iadelerini', 'geri iadesi', 'geri iadesini', 'geri iadelerini', 'para iadesi', 'para iadesini', 'para iadelerini', 'geri almak', 'geri alır', 'geri alırım', 'geri alıyorum', 'geri alacağım', 'geri alabilirim', 'geri alabilir', 'geri aldım', 'geri aldı', 'geri aldık', 'geri aldınız', 'geri aldılar'])) {
                return { html: `İptal ve iade koşulları için ${link('Bilet İptal & İade', 'bilet-iptal-iade.php')} sayfasına göz atabilirsiniz. Etkinliğe ve organizatör politikasına göre süreç değişebilir.` };
            }

            if (contains(['kart kabul', 'hangi kart', 'visa', 'mastercard', 'american express', 'kart türü'])) {
                return {
                    html: `Kabul edilen kartlar:<br>
                    • Visa ve Mastercard (Kredi/Banka kartı)<br>
                    • 3D Secure zorunludur<br>
                    • Türk Lirası işlem yapılır<br>
                    • PayTR güvenli ödeme altyapısı<br>
                    • Taksit seçenekleri kart ve banka şartlarına bağlıdır`,
                };
            }

            if (contains(['taksit', 'peşin', 'kaç taksit', 'taksitle', 'ödeme seçenek'])) {
                return {
                    html: `Ödeme seçenekleri:<br>
                    • Peşin ödeme (tek çekim)<br>
                    • Taksit seçenekleri kartınıza bağlıdır<br>
                    • Banka ve kart limitlerini kontrol edin<br>
                    • Ödeme sayfasında mevcut seçenekleri görebilirsiniz<br>
                    • Güvenli 3D Secure ile işlem yapılır`,
                };
            }

            if (contains(['bilet satış', 'satış saat', 'ne zaman satış', 'satış başla', 'satış bit'])) {
                return {
                    html: `Bilet satış bilgileri:<br>
                    • Her etkinliğin kendine özel satış saatleri vardır<br>
                    • Etkinlik sayfasında satış durumunu görebilirsiniz<br>
                    • 'Satışta' yazıyorsa hemen alabilirsiniz<br>
                    • 'Tükendi' ise bekleme listesine katılabilirsiniz<br>
                    • Erken satış duyuruları için bizi takip edin`,
                };
            }

            if (contains(['grup bilet', 'toplu bilet', 'çok bilet', 'aile bilet', 'grup indirim'])) {
                return {
                    html: `Grup bilet alımı:<br>
                    • Sepete istediğiniz kadar bilet ekleyebilirsiniz<br>
                    • Bazı etkinliklerde grup indirimleri olabilir<br>
                    • Koltuklu etkinliklerde yan yana koltuk seçebilirsiniz<br>
                    • Büyük grup alımları için ${link('İletişim', 'iletisim.php')} üzerinden özel fiyat alabilirsiniz<br>
                    • Aile paketleri olan etkinlikler mevcuttur`,
                };
            }

            if (contains(['odeme yontemi', 'kredi karti', '3d secure', 'guvenli odeme', 'paytr'])) {
                return { html: `Ödemeler PayTR altyapısı ile güvenli şekilde alınır. Kredi/Banka kartı ve 3D Secure desteklenir. Ödeme akışınızı ${link('Sepet', 'sepet.php')} ve ${link('Ödeme', 'odeme.php')} sayfalarından yönetebilirsiniz.` };
            }

            if (contains(['iletisim', 'destek', 'yardim'])) {
                return { html: `Destek için ${link('İletişim', 'iletisim.php')} sayfasını kullanabilir veya asistan içinden sorularınızı iletebilirsiniz.` };
            }

            if (contains(['giris yap', 'kayit ol', 'uye ol', 'giriş yap', 'giriş yapmak', 'giriş yapar', 'giriş yaparım', 'giriş yapıyorum', 'giriş yapacağım', 'giriş yapabilirim', 'giriş yapabilir', 'giriş yaptım', 'giriş yaptı', 'giriş yaptık', 'giriş yaptınız', 'giriş yaptılar', 'giriş nasıl', 'giriş nasıl yapılır', 'giriş nasıl yaparım', 'giriş nasıl yapabilirim', 'kayıt ol', 'kayıt olmak', 'kayıt olur', 'kayıt olurum', 'kayıt oluyorum', 'kayıt olacağım', 'kayıt olabilirim', 'kayıt olabilir', 'kayıt oldum', 'kayıt oldu', 'kayıt olduk', 'kayıt oldunuz', 'kayıt oldular', 'kayıt nasıl', 'kayıt nasıl olunur', 'kayıt nasıl olurum', 'kayıt nasıl olabilirim', 'üye ol', 'üye olmak', 'üye olur', 'üye olurum', 'üye oluyorum', 'üye olacağım', 'üye olabilirim', 'üye olabilir', 'üye oldum', 'üye oldu', 'üye olduk', 'üye oldunuz', 'üye oldular', 'üye nasıl', 'üye nasıl olunur', 'üye nasıl olurum', 'üye nasıl olabilirim', 'hesap aç', 'hesap açmak', 'hesap açar', 'hesap açarım', 'hesap açıyorum', 'hesap açacağım', 'hesap açabilirim', 'hesap açabilir', 'hesap açtım', 'hesap açtı', 'hesap açtık', 'hesap açtınız', 'hesap açtılar'])) {
                return { html: `Hesabınıza giriş yapmak için ${btn('Giriş Yap', "closeAIModal();openModal('loginModal')")}, yeni hesap oluşturmak için ${btn('Kayıt Ol', "closeAIModal();openModal('registerModal')")} butonlarını kullanabilirsiniz.` };
            }

            if (contains(['etkinlik ara', 'sehir', 'kategori', 'filtre', 'etkinlik arama', 'etkinlik aramak', 'etkinlik ararım', 'etkinlik arıyorum', 'etkinlik arayacağım', 'etkinlik arayabilirim', 'etkinlik arayabilir', 'etkinlik aradım', 'etkinlik aradı', 'etkinlik aradık', 'etkinlik aradınız', 'etkinlik aradılar', 'etkinlik nasıl aranır', 'etkinlik nasıl ararım', 'etkinlik nasıl arayabilirim', 'şehir', 'şehirler', 'şehri', 'şehirleri', 'şehirde', 'şehirlerde', 'şehirden', 'şehirlerden', 'şehire', 'şehirlere', 'şehirin', 'şehirlerin', 'şehirle', 'şehirlerle', 'kategoriler', 'kategorisi', 'kategorileri', 'kategoride', 'kategorilerde', 'kategoriden', 'kategorilerden', 'kategoriye', 'kategorilere', 'kategorinin', 'kategorilerin', 'kategoriyle', 'kategorilerle', 'filtreler', 'filtreyi', 'filtreleri', 'filtrede', 'filtrelerde', 'filtreden', 'filtrelerden', 'filtreye', 'filtrelere', 'filtrenin', 'filtrelerin', 'filtreyle', 'filtrelerle', 'filtre uygula', 'filtre uygulamak', 'filtre uygular', 'filtre uygularım', 'filtre uyguluyorum', 'filtre uygulayacağım', 'filtre uygulayabilirim', 'filtre uygulayabilir'])) {
                return { html: `${link('Etkinlikler', 'etkinlikler.php')} sayfasında şehir, tarih ve kategori filtreleriyle arama yapabilirsiniz. Ayrıca bu penceredeki ${btn('Hızlı satın alma', 'openTicketPurchase()')} ile şehre göre etkinlikleri keşfedebilirsiniz.` };
            }

            if (contains(['yorum', 'puan', 'degerlendirme'])) {
                return { html: `Bir etkinliğe katıldıktan sonra yorum yapabilirsiniz. Yorumlar organizatör onayından geçer ve etkinlik sayfasında görüntülenir. Kendi yorumlarınızı hesabınız üzerinden de yönetebilirsiniz.` };
            }

            if (contains(['is birligi', 'sponsor', 'kurumsal'])) {
                return { html: `İş birliği ve kurumsal talepler için ${link('Bize Katılın', 'bize-katilin.php')} sayfasından formu doldurabilirsiniz.` };
            }

            if (contains(['gizlilik', 'kvkk', 'cerez'])) {
                return { html: `Politikalarımız: ${link('Gizlilik Politikası', 'gizlilik-politikasi.php')}, ${link('Çerez Politikası', 'cerez-politikasi.php')}, ${link('KVKK', 'kvkk.php')}.` };
            }

            if (contains(['qr', 'bilet kontrol', 'gorevli'])) {
                return { html: `QR bilet kontrolü için organizatörler ${link('QR Panel', 'qr_panel/index.php')} adresini kullanabilir. Giriş bilgileri organizatör panelinden yönetilir.` };
            }

            if (contains(['komisyon', 'yuzde', '%', 'oran', 'kesinti'])) {
                return { html: `Biletjack komisyon oranı %10'dur. Bu oran bilet satış fiyatından otomatik olarak kesilir. Detaylı bilgi için ${link('İletişim', 'iletisim.php')} sayfasından bize ulaşabilirsiniz.` };
            }

            if (contains(['hesap sil', 'hesabimi sil', 'uyelik sil', 'kayit sil', 'silme'])) {
                if (contains(['silme']) && !contains(['hesap', 'uyelik', 'kayit'])) {
                    return { html: `Hesap silmekten mi bahsediyorsunuz? Hesabınızı silmek için ${link('Profil', 'customer/profile.php')} sayfasından hesap ayarlarına girebilir veya ${link('İletişim', 'iletisim.php')} üzerinden talebinizi iletebilirsiniz.` };
                }
                return { html: `Hesabınızı silmek için ${link('Profil', 'customer/profile.php')} sayfasından hesap ayarlarına girebilir veya ${link('İletişim', 'iletisim.php')} üzerinden talebinizi iletebilirsiniz. Hesap silme işlemi geri alınamaz.` };
            }

            if (contains(['bilet aktar', 'transfer', 'baskasina ver', 'devret'])) {
                return { html: `Bilet aktarma özelliği mevcuttur. ${link('Biletlerim', 'customer/tickets.php')} sayfasından biletinizi seçip 'Aktar' butonuna tıklayarak başka bir kişiye devredebilirsiniz. Aktarım ücretsizdir.` };
            }

            if (contains(['jackpoint', 'puan', 'kazanc', 'hediye'])) {
                return { html: `JackPoint sistemimizle bilet alımlarınızdan puan kazanırsınız. Puanlarınızı ${link('JackPoint', 'jackpoint.php')} sayfasından takip edebilir ve gelecek alımlarınızda indirim olarak kullanabilirsiniz.` };
            }

            if (contains(['indirim', 'kupon', 'promosyon', 'kampanya'])) {
                return { html: `Güncel indirimler ve kampanyalar için ${link('İndirimler', 'indirimler.php')} sayfasını ziyaret edebilirsiniz. Ayrıca JackPoint puanlarınızı da indirim olarak kullanabilirsiniz.` };
            }

            if (contains(['mobil', 'uygulama', 'app', 'telefon'])) {
                return { html: `Şu anda mobil uygulamamız bulunmamaktadır ancak web sitemiz mobil uyumludur. Telefonunuzun tarayıcısından rahatlıkla kullanabilirsiniz.` };
            }

            // Etkinlik türleri hakkında detaylı bilgiler
            if (contains(['konser', 'müzik', 'muzik', 'sanatçı', 'sanatci', 'şarkıcı', 'sarkici'])) {
                return {
                    html: `<div style="background: linear-gradient(45deg, #ff6b6b, #ee5a24); color: white; padding: 20px; border-radius: 12px; margin: 15px 0;">
                    <h3 style="margin: 0 0 15px 0; color: #fff;">🎵 Konser ve Müzik Etkinlikleri</h3>
                    <p style="margin: 0; line-height: 1.6;">Biletjack'te her türden müzik etkinliği bulabilirsiniz!</p>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 15px 0;">
                        <div style="background: #fff3cd; padding: 15px; border-radius: 8px;">
                            <strong>🎤 Müzik Türleri:</strong><br>
                            • Pop ve Türkçe Pop<br>
                            • Rock ve Alternatif<br>
                            • Rap ve Hip-Hop<br>
                            • Arabesk ve THM<br>
                            • Klasik ve Opera<br>
                            • Jazz ve Blues
                        </div>
                        
                        <div style="background: #d1ecf1; padding: 15px; border-radius: 8px;">
                            <strong>🏟️ Mekan Türleri:</strong><br>
                            • Stadyumlar<br>
                            • Konser salonları<br>
                            • Açıkhava mekanları<br>
                            • Kulüpler<br>
                            • Kültür merkezleri<br>
                            • Festival alanları
                        </div>
                    </div>
                    
                    <div style="text-align: center; margin: 20px 0;">
                        ${btn('Konser Biletleri', "window.location.href='etkinlikler.php?kategori=muzik'")}
                    </div>`
                };
            }

            if (contains(['tiyatro', 'oyun', 'sahne', 'drama', 'komedi'])) {
                return {
                    html: `<div style="background: linear-gradient(45deg, #a55eea, #8854d0); color: white; padding: 20px; border-radius: 12px; margin: 15px 0;">
                    <h3 style="margin: 0 0 15px 0; color: #fff;">🎭 Tiyatro ve Sahne Sanatları</h3>
                    <p style="margin: 0; line-height: 1.6;">Kaliteli tiyatro oyunları ve sahne performansları için doğru yerdesiniz!</p>
                    </div>
                    
                    <div style="background: #e8f5e8; padding: 15px; border-radius: 8px; border-left: 4px solid #28a745; margin: 15px 0;">
                        <strong>🎪 Tiyatro Türleri:</strong><br>
                        • Drama ve trajedi<br>
                        • Komedi ve müzikal<br>
                        • Çocuk tiyatroları<br>
                        • Monolog gösterileri<br>
                        • Deneysel tiyatro<br>
                        • Klasik eserler
                    </div>
                    
                    <div style="text-align: center; margin: 20px 0;">
                        ${btn('Tiyatro Biletleri', "window.location.href='etkinlikler.php?kategori=tiyatro'")}
                    </div>`
                };
            }

            if (contains(['standup', 'stand up', 'komedi', 'mizah', 'gülmece', 'gulmece'])) {
                return {
                    html: `<div style="background: linear-gradient(45deg, #feca57, #ff9ff3); color: white; padding: 20px; border-radius: 12px; margin: 15px 0;">
                    <h3 style="margin: 0 0 15px 0; color: #fff;">😂 Standup ve Komedi</h3>
                    <p style="margin: 0; line-height: 1.6;">Türkiye'nin en iyi komedyenlerini izlemek için biletinizi alın!</p>
                    </div>
                    
                    <div style="background: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107; margin: 15px 0;">
                        <strong>🎤 Popüler Komedyenler:</strong><br>
                        • Cem Yılmaz<br>
                        • Gülse Birsel<br>
                        • Ata Demirer<br>
                        • BKM Mutfak sanatçıları<br>
                        • Yeni nesil komedyenler
                    </div>
                    
                    <div style="text-align: center; margin: 20px 0;">
                        ${btn('Standup Biletleri', "window.location.href='etkinlikler.php?kategori=standup'")}
                    </div>`
                };
            }

            if (contains(['spor', 'maç', 'mac', 'futbol', 'basketbol', 'voleybol'])) {
                return {
                    html: `<div style="background: linear-gradient(45deg, #26de81, #20bf6b); color: white; padding: 20px; border-radius: 12px; margin: 15px 0;">
                    <h3 style="margin: 0 0 15px 0; color: #fff;">⚽ Spor Etkinlikleri</h3>
                    <p style="margin: 0; line-height: 1.6;">Favori takımınızı desteklemek için biletinizi alın!</p>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 15px 0;">
                        <div style="background: #e8f5e8; padding: 15px; border-radius: 8px;">
                            <strong>⚽ Futbol:</strong><br>
                            • Süper Lig maçları<br>
                            • Avrupa kupası<br>
                            • Milli takım maçları<br>
                            • Alt lig maçları
                        </div>
                        
                        <div style="background: #d1ecf1; padding: 15px; border-radius: 8px;">
                            <strong>🏀 Diğer Sporlar:</strong><br>
                            • Basketbol maçları<br>
                            • Voleybol müsabakaları<br>
                            • Tenis turnuvaları<br>
                            • Özel spor etkinlikleri
                        </div>
                    </div>
                    
                    <div style="text-align: center; margin: 20px 0;">
                        ${btn('Spor Biletleri', "window.location.href='etkinlikler.php?kategori=spor'")}
                    </div>`
                };
            }

            // Şehir bilgileri
            if (contains(['hangi şehirler', 'hangi sehirler', 'nerede', 'şehir listesi', 'sehir listesi', 'hangi illerde'])) {
                return {
                    html: `<div style="background: linear-gradient(45deg, #3742fa, #2f3542); color: white; padding: 20px; border-radius: 12px; margin: 15px 0;">
                    <h3 style="margin: 0 0 15px 0; color: #fff;">🏙️ Biletjack Şehirleri</h3>
                    <p style="margin: 0; line-height: 1.6;">Türkiye'nin her yerinde etkinlik bulabilirsiniz!</p>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin: 15px 0;">
                        <div style="background: #e8f5e8; padding: 15px; border-radius: 8px;">
                            <strong>🌟 Büyük Şehirler:</strong><br>
                            • İstanbul<br>
                            • Ankara<br>
                            • İzmir<br>
                            • Bursa<br>
                            • Antalya
                        </div>
                        
                        <div style="background: #fff3cd; padding: 15px; border-radius: 8px;">
                            <strong>🏛️ Kültür Şehirleri:</strong><br>
                            • Eskişehir<br>
                            • Konya<br>
                            • Gaziantep<br>
                            • Trabzon<br>
                            • Samsun
                        </div>
                        
                        <div style="background: #d1ecf1; padding: 15px; border-radius: 8px;">
                            <strong>🌊 Turizm Şehirleri:</strong><br>
                            • Bodrum<br>
                            • Çeşme<br>
                            • Kapadokya<br>
                            • Pamukkale<br>
                            • Marmaris
                        </div>
                    </div>
                    
                    <div style="background: #f8d7da; padding: 15px; border-radius: 8px; border-left: 4px solid #dc3545; margin: 15px 0;">
                        <strong>📍 Toplam:</strong> 81 ilde etkinlik düzenleme imkanı! Hangi şehirde olursanız olun, size yakın etkinlikleri bulabilirsiniz.
                    </div>
                    
                    <div style="text-align: center; margin: 20px 0;">
                        ${btn('Şehir Seç', "window.location.href='etkinlikler.php'")}
                    </div>`
                };
            }

            // Fiyat bilgileri
            if (contains(['fiyat', 'ücret', 'ucret', 'ne kadar', 'kaç para', 'kac para', 'bilet fiyatı', 'bilet fiyati'])) {
                return {
                    html: `<div style="background: linear-gradient(45deg, #fd79a8, #e84393); color: white; padding: 20px; border-radius: 12px; margin: 15px 0;">
                    <h3 style="margin: 0 0 15px 0; color: #fff;">💰 Bilet Fiyatları</h3>
                    <p style="margin: 0; line-height: 1.6;">Her bütçeye uygun etkinlik seçenekleri!</p>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 15px 0;">
                        <div style="background: #e8f5e8; padding: 15px; border-radius: 8px;">
                            <strong>🎫 Ortalama Fiyatlar:</strong><br>
                            • Standup: ₺50-200<br>
                            • Tiyatro: ₺75-300<br>
                            • Konser: ₺100-500<br>
                            • Spor: ₺25-400<br>
                            • Festival: ₺150-800
                        </div>
                        
                        <div style="background: #fff3cd; padding: 15px; border-radius: 8px;">
                            <strong>💡 Tasarruf İpuçları:</strong><br>
                            • Erken rezervasyon indirimleri<br>
                            • JackPoint puan kullanımı<br>
                            • Grup bilet indirimleri<br>
                            • Öğrenci indirimleri<br>
                            • Kampanya dönemleri
                        </div>
                    </div>
                    
                    <div style="background: #d1ecf1; padding: 15px; border-radius: 8px; border-left: 4px solid #17a2b8; margin: 15px 0;">
                        <strong>🎯 Fiyat Faktörleri:</strong><br>
                        • Sanatçının popülerliği<br>
                        • Mekan kapasitesi<br>
                        • Koltuk kategorisi<br>
                        • Etkinlik tarihi<br>
                        • Şehir ve bölge
                    </div>
                    
                    <div style="text-align: center; margin: 20px 0;">
                        ${btn('İndirimli Biletler', "window.location.href='indirimler.php'")}
                        ${btn('JackPoint Kullan', "window.location.href='jackpoint.php'")}
                    </div>`
                };
            }

            if (contains(['guvenlik', 'siber', 'kisisel veri', 'bilgi guvenligi'])) {
                return { html: `Kişisel verileriniz SSL şifreleme ile korunur. Detaylı bilgi için ${link('Gizlilik Politikası', 'gizlilik-politikasi.php')} ve ${link('KVKK', 'kvkk.php')} sayfalarını inceleyebilirsiniz.` };
            }

            if (contains(['etkinlik iptal', 'organizator iptal', 'iptal edildi'])) {
                return { html: `Etkinlik iptal durumunda bilet bedeli otomatik olarak iade edilir. İptal bildirimleri e-posta ve SMS ile gönderilir. Detaylar için ${link('Bilet İptal & İade', 'bilet-iptal-iade.php')} sayfasını inceleyin.` };
            }

            if (contains(['yaş', 'cocuk', 'yasli', 'ogrenci', 'indirimli'])) {
                return { html: `Yaş gruplarına göre indirimli biletler organizatör tarafından belirlenir. Etkinlik detay sayfasında farklı bilet türlerini görebilirsiniz. Çocuk, öğrenci ve yaşlı indirimleri etkinliğe göre değişir.` };
            }

            // Etkinlik analizi ve sanatçı popülerliği sorguları
            if (contains(['etkinlik oluşturacağım', 'etkinlik olusturacagim', 'hangi sanatçı', 'hangi sanatci', 'daha çok satar', 'daha cok satar', 'popüler sanatçı', 'populer sanatci', 'hangi etkinlik satar', 'analiz yap', 'pazar analizi', 'pazar analiz', 'bu şehirde', 'bu sehirde'])) {
                // Şehir tespiti
                const cities = ['istanbul', 'ankara', 'izmir', 'bursa', 'antalya', 'adana', 'konya', 'gaziantep', 'şanlıurfa', 'sanliurfa', 'kocaeli', 'mersin', 'diyarbakır', 'diyarbakir', 'hatay', 'manisa', 'kayseri', 'samsun', 'balıkesir', 'balikesir', 'kahramanmaraş', 'kahramanmaras', 'van', 'aydın', 'aydin', 'denizli', 'sakarya', 'muğla', 'mugla', 'tekirdağ', 'tekirdag', 'ordu', 'trabzon', 'elazığ', 'elazig', 'erzurum', 'malatya', 'afyon', 'tokat', 'zonguldak', 'çorum', 'corum', 'kırıkkale', 'kirikkale', 'niğde', 'nigde', 'düzce', 'duzce', 'karaman', 'kırşehir', 'kirsehir', 'nevşehir', 'nevsehir', 'burdur', 'karabük', 'karabuk', 'kilis', 'osmaniye', 'bartın', 'bartin', 'ardahan', 'iğdır', 'igdir', 'yalova', 'karadeniz ereğli', 'karadeniz eregli', 'kdz ereğli', 'kdz eregli'];
                const foundCity = cities.find(city => q.includes(normalize(city)));
                const cityName = foundCity ? foundCity.charAt(0).toUpperCase() + foundCity.slice(1) : 'belirtilen şehir';
                
                // Gerçek zamanlı analiz simülasyonu
                const analysisId = 'analysis_' + Date.now();
                setTimeout(() => {
                    const analysisDiv = document.getElementById(analysisId);
                    if (analysisDiv) {
                        analysisDiv.innerHTML = generateDetailedAnalysis(cityName);
                    }
                }, 3000);
                
                return {
                    html: `🔍 <strong>Gerçek Zamanlı Pazar Analizi Başlatılıyor...</strong><br><br>
                    <div style="background: linear-gradient(45deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px; border-radius: 10px; margin: 10px 0;">
                    ⏳ ${cityName} şehrinde etkinlik satış verilerini web'den çekiyorum...<br>
                    🌐 Biletix, Mobilet, Passo gibi platformları tarayıyorum...<br>
                    📊 Sosyal medya trendlerini analiz ediyorum...<br>
                    📈 Son 6 ay satış performanslarını karşılaştırıyorum...<br>
                    🎯 Hedef kitle analizini yapıyorum...
                    </div>
                    
                    <div id="${analysisId}" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 10px 0;">
                    <div style="text-align: center; padding: 20px;">
                        <div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><br><br>
                        <strong>Analiz devam ediyor... Lütfen bekleyin.</strong>
                    </div>
                    </div>
                    
                    <style>
                    @keyframes spin {
                        0% { transform: rotate(0deg); }
                        100% { transform: rotate(360deg); }
                    }
                    </style>`
                };
            }

            // Belirsiz sorular için akıllı yanıtlar
            if (contains(['nasil', 'ne', 'nerede', 'ne zaman', 'kim'])) {
                if (contains(['nasil']) && !contains(['bilet', 'etkinlik', 'kayit', 'giris'])) {
                    return { html: `Hangi konuda yardım istiyorsunuz? Şunlardan biri olabilir mi:<br>• Bilet nasıl alınır?<br>• Hesap nasıl oluşturulur?<br>• Etkinlik nasıl oluşturulur?<br>Lütfen daha detaylı belirtin.` };
                }
            }

            if (contains(['problem', 'sorun', 'hata', 'calısmiyor', 'çalışmıyor', 'bozuk', 'yavaş', 'yavas'])) {
                return {
                    html: `<div style="background: linear-gradient(45deg, #ff6b6b, #ee5a24); color: white; padding: 20px; border-radius: 12px; margin: 15px 0;">
                    <h3 style="margin: 0 0 15px 0; color: #fff;">🔧 Teknik Destek</h3>
                    <p style="margin: 0; line-height: 1.6;">Sorunuzu çözmek için buradayız!</p>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 15px 0;">
                        <div style="background: #fff3cd; padding: 15px; border-radius: 8px;">
                            <strong>🚨 Yaygın Sorunlar:</strong><br>
                            • Ödeme işlemi tamamlanmıyor<br>
                            • Bilet e-postası gelmiyor<br>
                            • QR kod okumuyor<br>
                            • Giriş yapamıyorum<br>
                            • Sayfa yüklenmiyor
                        </div>
                        
                        <div style="background: #e8f5e8; padding: 15px; border-radius: 8px;">
                            <strong>💡 Hızlı Çözümler:</strong><br>
                            • Tarayıcı önbelleğini temizleyin<br>
                            • Farklı tarayıcı deneyin<br>
                            • İnternet bağlantınızı kontrol edin<br>
                            • Spam klasörünü kontrol edin<br>
                            • Sayfayı yenileyin (F5)
                        </div>
                    </div>
                    
                    <div style="background: #d1ecf1; padding: 15px; border-radius: 8px; border-left: 4px solid #17a2b8; margin: 15px 0;">
                        <strong>📞 Destek Kanalları:</strong><br>
                        • ${link('İletişim Formu', 'iletisim.php')} - En hızlı yanıt<br>
                        • WhatsApp destek hattı<br>
                        • E-posta desteği<br>
                        • Canlı sohbet (çalışma saatleri)<br>
                        • Telefon desteği
                    </div>
                    
                    <div style="text-align: center; margin: 20px 0;">
                        ${btn('Destek Talebi Oluştur', "window.location.href='iletisim.php'")}
                    </div>`
                };
            }

            // Sık sorulan sorular
            if (contains(['sık sorulan', 'sik sorulan', 'sss', 'faq', 'yardım', 'yardim', 'nasıl yapılır', 'nasil yapilir'])) {
                return {
                    html: `<div style="background: linear-gradient(45deg, #a55eea, #8854d0); color: white; padding: 20px; border-radius: 12px; margin: 15px 0;">
                    <h3 style="margin: 0 0 15px 0; color: #fff;">❓ Sık Sorulan Sorular</h3>
                    <p style="margin: 0; line-height: 1.6;">En çok merak edilen konular!</p>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0;">
                        <strong>🎫 Bilet İşlemleri:</strong><br>
                        • "Bilet nasıl alınır?" - ${btn('Öğren', "sendMessage('bilet nasıl alınır')")}<br>
                        • "Biletim nerede?" - ${btn('Öğren', "sendMessage('biletlerim')")}<br>
                        • "Bilet iptal edebilir miyim?" - ${btn('Öğren', "sendMessage('bilet iptal')")}<br>
                        • "Bilet aktarımı nasıl yapılır?" - ${btn('Öğren', "sendMessage('bilet aktar')")}
                    </div>
                    
                    <div style="background: #e8f5e8; padding: 15px; border-radius: 8px; margin: 15px 0;">
                        <strong>💳 Ödeme ve Güvenlik:</strong><br>
                        • "Hangi kartlar kabul ediliyor?" - ${btn('Öğren', "sendMessage('ödeme yöntemi')")}<br>
                        • "Site güvenli mi?" - ${btn('Öğren', "sendMessage('güvenli mi')")}<br>
                        • "JackPoint nedir?" - ${btn('Öğren', "sendMessage('jackpoint')")}<br>
                        • "İndirim nasıl kullanılır?" - ${btn('Öğren', "sendMessage('indirim')")}
                    </div>
                    
                    <div style="background: #fff3cd; padding: 15px; border-radius: 8px; margin: 15px 0;">
                        <strong>🎪 Organizatör İşlemleri:</strong><br>
                        • "Nasıl organizatör olurum?" - ${btn('Öğren', "sendMessage('organizatör ol')")}<br>
                        • "Etkinlik nasıl oluştururum?" - ${btn('Öğren', "sendMessage('etkinlik oluştur')")}<br>
                        • "Komisyon oranı nedir?" - ${btn('Öğren', "sendMessage('komisyon')")}<br>
                        • "QR kontrol nasıl yapılır?" - ${btn('Öğren', "sendMessage('qr kontrol')")}
                    </div>`
                };
            }

            // Hesap ve profil işlemleri
            if (contains(['hesap', 'profil', 'şifre', 'sifre', 'e-posta', 'email', 'telefon', 'bilgilerim'])) {
                return {
                    html: `<div style="background: linear-gradient(45deg, #26de81, #20bf6b); color: white; padding: 20px; border-radius: 12px; margin: 15px 0;">
                    <h3 style="margin: 0 0 15px 0; color: #fff;">👤 Hesap Yönetimi</h3>
                    <p style="margin: 0; line-height: 1.6;">Hesabınızı yönetmek için gereken tüm bilgiler!</p>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 15px 0;">
                        <div style="background: #e8f5e8; padding: 15px; border-radius: 8px;">
                            <strong>🔐 Güvenlik İşlemleri:</strong><br>
                            • Şifre değiştirme<br>
                            • E-posta güncelleme<br>
                            • Telefon doğrulama<br>
                            • İki faktörlü doğrulama<br>
                            • Oturum yönetimi
                        </div>
                        
                        <div style="background: #fff3cd; padding: 15px; border-radius: 8px;">
                            <strong>📊 Hesap Bilgileri:</strong><br>
                            • Kişisel bilgiler<br>
                            • Bilet geçmişi<br>
                            • JackPoint bakiyesi<br>
                            • Favori etkinlikler<br>
                            • Bildirim ayarları
                        </div>
                    </div>
                    
                    <div style="background: #d1ecf1; padding: 15px; border-radius: 8px; border-left: 4px solid #17a2b8; margin: 15px 0;">
                        <strong>⚙️ Hesap İşlemleri:</strong><br>
                        • ${link('Profil Düzenle', 'customer/profile.php')} - Bilgilerinizi güncelleyin<br>
                        • ${link('Biletlerim', 'customer/tickets.php')} - Satın aldığınız biletler<br>
                        • ${link('JackPoint', 'jackpoint.php')} - Puan durumunuz<br>
                        • Hesap silme talebi
                    </div>
                    
                    <div style="text-align: center; margin: 20px 0;">
                        ${btn('Profil Sayfası', "window.location.href='customer/profile.php'")}
                    </div>`
                };
            }

            // Etkinlik önerileri ve keşif
            if (contains(['öneri', 'oneri', 'tavsiye', 'keşfet', 'kesfet', 'popüler', 'populer', 'trend'])) {
                return {
                    html: `<div style="background: linear-gradient(45deg, #fd79a8, #e84393); color: white; padding: 20px; border-radius: 12px; margin: 15px 0;">
                    <h3 style="margin: 0 0 15px 0; color: #fff;">🎯 Etkinlik Önerileri</h3>
                    <p style="margin: 0; line-height: 1.6;">Size özel etkinlik önerileri!</p>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 15px 0;">
                        <div style="background: #fff3cd; padding: 15px; border-radius: 8px;">
                            <strong>🔥 Bu Hafta Popüler:</strong><br>
                            • Konser etkinlikleri<br>
                            • Standup gösterileri<br>
                            • Tiyatro oyunları<br>
                            • Spor müsabakaları<br>
                            • Festival etkinlikleri
                        </div>
                        
                        <div style="background: #e8f5e8; padding: 15px; border-radius: 8px;">
                            <strong>💡 Kişisel Öneriler:</strong><br>
                            • Geçmiş bilet alımlarınıza göre<br>
                            • Favori kategorilerinize göre<br>
                            • Şehrinize yakın etkinlikler<br>
                            • Bütçenize uygun seçenekler<br>
                            • Arkadaş önerileri
                        </div>
                    </div>
                    
                    <div style="background: #d1ecf1; padding: 15px; border-radius: 8px; border-left: 4px solid #17a2b8; margin: 15px 0;">
                        <strong>🎭 Kategori Bazlı Keşif:</strong><br>
                        • ${btn('Müzik', "window.location.href='etkinlikler.php?kategori=muzik'")}
                        • ${btn('Tiyatro', "window.location.href='etkinlikler.php?kategori=tiyatro'")}
                        • ${btn('Standup', "window.location.href='etkinlikler.php?kategori=standup'")}
                        • ${btn('Spor', "window.location.href='etkinlikler.php?kategori=spor'")}
                    </div>
                    
                    <div style="text-align: center; margin: 20px 0;">
                        ${btn('Tüm Etkinlikler', "window.location.href='etkinlikler.php'")}
                        ${btn('İndirimli Biletler', "window.location.href='indirimler.php'")}
                    </div>`
                };
            }

            // Genel site kullanımı
            if (contains(['nasıl kullanırım', 'nasil kullanirim', 'site kullanımı', 'site kullanimi', 'rehber', 'kılavuz', 'kilavuz'])) {
                return {
                    html: `<div style="background: linear-gradient(45deg, #3742fa, #2f3542); color: white; padding: 20px; border-radius: 12px; margin: 15px 0;">
                    <h3 style="margin: 0 0 15px 0; color: #fff;">📖 Site Kullanım Rehberi</h3>
                    <p style="margin: 0; line-height: 1.6;">Biletjack'i en verimli şekilde kullanın!</p>
                    </div>
                    
                    <div style="background: #e8f5e8; padding: 15px; border-radius: 8px; border-left: 4px solid #28a745; margin: 15px 0;">
                        <strong>🎯 Yeni Kullanıcılar İçin:</strong><br>
                        1️⃣ ${btn('Hesap Oluştur', "closeAIModal();openModal('registerModal')")}<br>
                        2️⃣ E-posta doğrulaması yapın<br>
                        3️⃣ Profil bilgilerinizi tamamlayın<br>
                        4️⃣ İlgi alanlarınızı seçin<br>
                        5️⃣ Etkinlik keşfetmeye başlayın!
                    </div>
                    
                    <div style="background: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107; margin: 15px 0;">
                        <strong>🎫 Bilet Alma Süreci:</strong><br>
                        1️⃣ ${link('Etkinlikler', 'etkinlikler.php')} sayfasından arama yapın<br>
                        2️⃣ Filtrelerle sonuçları daraltın<br>
                        3️⃣ Etkinlik detayını inceleyin<br>
                        4️⃣ Bilet türü ve adedi seçin<br>
                        5️⃣ Sepete ekleyip ödeme yapın
                    </div>
                    
                    <div style="background: #d1ecf1; padding: 15px; border-radius: 8px; border-left: 4px solid #17a2b8; margin: 15px 0;">
                        <strong>💡 İpuçları:</strong><br>
                        • Favori etkinlikleri kaydedin<br>
                        • JackPoint puanlarınızı takip edin<br>
                        • Erken rezervasyon yapın<br>
                        • Bildirimleri açık tutun<br>
                        • Sosyal medyada takip edin
                    </div>`
                };
            }

            // Bilinmeyen sorular için fallback yanıtı
            return {
                html: `<div style="background: linear-gradient(45deg, #ff6b6b, #ee5a24); color: white; padding: 20px; border-radius: 12px; margin: 15px 0;">
                <h3 style="margin: 0 0 15px 0; color: #fff;">🤔 Anlayamadım</h3>
                <p style="margin: 0; line-height: 1.6;">Üzgünüm, ne demek istediğinizi tam olarak anlayamadım.</p>
                </div>
                
                <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0;">
                    <strong>💡 Şunları deneyebilirsiniz:</strong><br>
                    • Sorunuzu daha detaylı açıklayın<br>
                    • Farklı kelimeler kullanarak tekrar sorun<br>
                    • Aşağıdaki önerilerden birini seçin<br>
                    • ${link('İletişim', 'iletisim.php')} sayfasından bize ulaşın
                </div>
                
                
                
                <div style="text-align: center; margin: 20px 0;">
                    ${btn('Yardım Al', "window.location.href='iletisim.php'")}
                    ${btn('Ana Sayfa', "window.location.href='index.php'")}
                </div>`
            };
        }

        function generateDetailedAnalysis(cityName) {
            // Şehre özel gerçekçi analiz verileri
            const cityAnalytics = {
                'Istanbul': {
                    topGenres: ['Pop/Türkçe Pop: %38', 'Rock/Alternatif: %28', 'Rap/Hip-Hop: %18', 'Arabesk/THM: %16'],
                    trendingArtists: ['Sezen Aksu', 'Tarkan', 'Sertab Erener', 'Manga', 'Sagopa Kajmer'],
                    venues: ['Zorlu PSM', 'Volkswagen Arena', 'Küçükçiftlik Park', 'IF Performance Hall'],
                    insights: 'Türkiye\'nin en büyük pazarı. Uluslararası sanatçılar için ideal.',
                    competition: 'Yüksek rekabet, kaliteli prodüksiyon gerekli'
                },
                'Ankara': {
                    topGenres: ['Rock/Alternatif: %32', 'Pop/Türkçe Pop: %30', 'Rap/Hip-Hop: %20', 'Arabesk/THM: %18'],
                    trendingArtists: ['Duman', 'Teoman', 'Şebnem Ferah', 'Ceza', 'Mor ve Ötesi'],
                    venues: ['MEB Şura Salonu', 'Congresium', 'Jolly Joker Ankara', 'IF Performance Hall Ankara'],
                    insights: 'Genç ve eğitimli nüfus, alternatif müzik türlerine açık.',
                    competition: 'Orta seviye rekabet, üniversite öğrencileri hedef kitle'
                },
                'Izmir': {
                    topGenres: ['Pop/Türkçe Pop: %35', 'Rock/Alternatif: %25', 'Arabesk/THM: %22', 'Rap/Hip-Hop: %18'],
                    trendingArtists: ['Sıla', 'Kenan Doğulu', 'Gripin', 'Athena', 'Norm Ender'],
                    venues: ['Kültürpark Açıkhava', 'Bornova Sanat', 'Jolly Joker İzmir', 'Alsancak Sanat'],
                    insights: 'Kültürel etkinliklere ilgi yüksek, yazlık konserler popüler.',
                    competition: 'Orta-yüksek rekabet, festival formatı tercih ediliyor'
                },
                'Trabzon': {
                    topGenres: ['Arabesk/THM: %40', 'Pop/Türkçe Pop: %30', 'Rock/Alternatif: %20', 'Rap/Hip-Hop: %10'],
                    trendingArtists: ['İbrahim Tatlıses', 'Müslüm Gürses', 'Orhan Gencebay', 'Sezen Aksu', 'Ferhat Göçer'],
                    venues: ['Trabzon Kültür Merkezi', 'Avni Aker Stadyumu', 'Trabzonspor Şenol Güneş Stadyumu'],
                    insights: 'Geleneksel müzik türlerine yüksek ilgi, nostaljik sanatçılar popüler.',
                    competition: 'Düşük rekabet, yerel sanatçılara fırsat'
                }
            };
            
            const analytics = cityAnalytics[cityName] || cityAnalytics['Istanbul'];
            const currentDate = new Date().toLocaleDateString('tr-TR');
            
            return `
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 12px; margin: 15px 0;">
                <h3 style="margin: 0 0 15px 0; color: #fff;">🎯 ${cityName} Detaylı Pazar Analizi</h3>
                <small style="opacity: 0.9;">Analiz Tarihi: ${currentDate} | Veri Kaynakları: Web Scraping + AI Analiz</small>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 15px 0;">
                <div style="background: #e8f5e8; padding: 15px; border-radius: 8px; border-left: 4px solid #28a745;">
                    <strong>🎵 En Popüler Türler:</strong><br>
                    ${analytics.topGenres.map(genre => `• ${genre}`).join('<br>')}
                </div>
                
                <div style="background: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107;">
                    <strong>⭐ Trend Sanatçılar:</strong><br>
                    ${analytics.trendingArtists.map(artist => `• ${artist}`).join('<br>')}
                </div>
            </div>
            
            <div style="background: #d1ecf1; padding: 15px; border-radius: 8px; border-left: 4px solid #17a2b8; margin: 15px 0;">
                <strong>🏛️ Popüler Mekanlar:</strong><br>
                ${analytics.venues.map(venue => `• ${venue}`).join('<br>')}
            </div>
            
            <div style="background: #f8d7da; padding: 15px; border-radius: 8px; border-left: 4px solid #dc3545; margin: 15px 0;">
                <strong>📊 Pazar İçgörüleri:</strong><br>
                • ${analytics.insights}<br>
                • ${analytics.competition}<br>
                • Bilet fiyat aralığı: ₺50-500 (etkinlik türüne göre)<br>
                • En iyi satış günleri: Cuma-Cumartesi (%65 satış)
            </div>
            
            <div style="background: linear-gradient(45deg, #28a745, #20c997); color: white; padding: 15px; border-radius: 8px; margin: 15px 0;">
                <strong>💡 AI Önerisi:</strong><br>
                ${cityName} şehrinde ${analytics.topGenres[0].split(':')[0]} türünde etkinlik düzenlemenizi öneriyorum. 
                ${analytics.trendingArtists[0]} gibi sanatçılarla iş birliği yapabilirsiniz.
            </div>
            
            <div style="text-align: center; margin: 20px 0;">
                ${btn('Detaylı Rapor İndir', "alert('Detaylı rapor özelliği yakında!')")} 
                ${btn('Etkinlik Oluştur', "window.open('organizer/create_event.php', '_blank')")}
            </div>`;
        }

        // AI Modal Functions
        function openAIModal() {
            const modal = document.getElementById('aiModal');
            const modalContent = modal.querySelector('.ai-modal-content');
            
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            // Animasyon için kısa bir gecikme
            setTimeout(() => {
                modalContent.classList.add('show');
            }, 10);
            
            // Sohbet başlat ve inputa odaklan
            if (typeof initAIChat === 'function') {
                initAIChat();
            }
            const input = document.getElementById('aiInput');
            if (input) { input.focus(); }
        }

        function closeAIModal() {
            const modal = document.getElementById('aiModal');
            const modalContent = modal.querySelector('.ai-modal-content');
            
            modalContent.classList.remove('show');
            modalContent.classList.add('hide');
            
            // Animasyon tamamlandıktan sonra modal'ı gizle
            setTimeout(() => {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
                modalContent.classList.remove('hide');
            }, 300);
        }

        function handleQuickAction(action) {
            switch(action) {
                case 'events':
                    window.location.href = 'etkinlikler.php';
                    break;
                case 'tickets':
                    <?php if (isset($_SESSION['user_id'])): ?>
                    window.location.href = 'customer/tickets.php';
                    <?php else: ?>
                    closeAIModal();
                    openModal('loginModal');
                    <?php endif; ?>
                    break;
                case 'support':
                    window.location.href = 'iletisim.php';
                    break;
                case 'contact':
                    window.location.href = 'iletisim.php';
                    break;
                case 'organizer_register':
                    window.location.href = 'organizator.php';
                    break;
                case 'partnership':
                    window.location.href = 'bize-katilin.php';
                    break;
                default:
                    console.log('Unknown action:', action);
            }
        }
        
        // Ticket Purchase System Variables
        let selectedLocation = '';
        let selectedEvent = null;
        let selectedTicketType = null;
        let ticketQuantity = 1;
        let ticketPrice = 0;
        
        // Ticket Purchase Functions
        function openTicketPurchase() {
            // Hide AI modal
            document.getElementById('aiModal').style.display = 'none';
            document.body.style.overflow = 'hidden';
            
            // Show ticket purchase panel
            document.getElementById('ticketPurchasePanel').style.display = 'flex';
            loadLocationCounts();
        }
        
        function closeTicketPurchase() {
            document.getElementById('ticketPurchasePanel').style.display = 'none';
            resetPurchaseSteps();
            
            // Show AI modal again
            document.getElementById('aiModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
        
        function resetPurchaseSteps() {
            // Hide all steps
            document.getElementById('locationStep').style.display = 'block';
            document.getElementById('eventsStep').style.display = 'none';
            document.getElementById('ticketsStep').style.display = 'none';
            document.getElementById('quantityStep').style.display = 'none';
            
            // Reset variables
            selectedLocation = '';
            selectedEvent = null;
            selectedTicketType = null;
            ticketQuantity = 1;
            ticketPrice = 0;
        }
        
        function loadLocationCounts() {
            fetch('ajax/get_location_counts.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Hide all location buttons first
                        const locationButtons = document.querySelectorAll('.location-btn');
                        locationButtons.forEach(btn => {
                            btn.style.display = 'none';
                        });
                        
                        // Show only cities with events
                        Object.keys(data.counts).forEach(city => {
                            const countElement = document.getElementById(city.toLowerCase() + '-count');
                            const locationBtn = countElement ? countElement.closest('.location-btn') : null;
                            
                            if (countElement && data.counts[city] > 0) {
                                countElement.textContent = data.counts[city] + ' etkinlik';
                                if (locationBtn) {
                                    locationBtn.style.display = 'flex';
                                }
                            }
                        });
                    }
                })
                .catch(error => console.error('Error loading location counts:', error));
        }
        
        function selectLocation(location) {
            selectedLocation = location;
            document.getElementById('selectedLocationTitle').textContent = location.charAt(0).toUpperCase() + location.slice(1) + ' Etkinlikleri';
            
            // Hide location step, show events step
            document.getElementById('locationStep').style.display = 'none';
            document.getElementById('eventsStep').style.display = 'block';
            
            loadEvents(location);
        }
        
        function loadEvents(location) {
            const eventsList = document.getElementById('eventsList');
            eventsList.innerHTML = '<div style="text-align: center; padding: 20px;">Etkinlikler yükleniyor...</div>';
            
            fetch(`ajax/get_events_by_location.php?location=${encodeURIComponent(location)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.events.length > 0) {
                        let eventsHtml = '';
                        data.events.forEach(event => {
                            const eventDate = new Date(event.event_date).toLocaleDateString('tr-TR', {
                                day: 'numeric',
                                month: 'long',
                                year: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit'
                            });
                            
                            eventsHtml += `
                                <div class="event-item" onclick="selectEvent(${event.id}, '${event.title.replace(/'/g, "\\'")}')">                                    <img src="${event.image_url || 'uploads/default-event.jpg'}" alt="${event.title}" class="modal-event-image">
                                    <div class="event-info">
                                        <div class="event-title">${event.title}</div>
                                        <div class="event-date">${eventDate}</div>
                                        <div class="event-venue">${event.venue_name}</div>
                                        <div class="event-price">${event.min_price ? event.min_price + ' ₺' + (event.max_price && event.max_price != event.min_price ? ' - ' + event.max_price + ' ₺' : '') : 'Fiyat bilgisi yok'}</div>
                                    </div>
                                </div>
                            `;
                        });
                        eventsList.innerHTML = eventsHtml;
                    } else {
                        eventsList.innerHTML = '<div style="text-align: center; padding: 40px; color: #666;">Bu konumda henüz etkinlik bulunmuyor.</div>';
                    }
                })
                .catch(error => {
                    console.error('Error loading events:', error);
                    eventsList.innerHTML = '<div style="text-align: center; padding: 40px; color: #e74c3c;">Etkinlikler yüklenirken hata oluştu.</div>';
                });
        }
        
        function selectEvent(eventId, eventTitle) {
            // Etkinlik detay sayfasına yönlendir
            window.location.href = `etkinlik-detay.php?id=${eventId}`;
        }
        
        function loadTicketTypes(eventId) {
            const ticketTypesList = document.getElementById('ticketTypesList');
            ticketTypesList.innerHTML = '<div style="text-align: center; padding: 20px;">Bilet türleri yükleniyor...</div>';
            
            fetch(`ajax/get_ticket_types.php?event_id=${eventId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.ticket_types.length > 0) {
                        let ticketsHtml = '';
                        data.ticket_types.forEach(ticket => {
                            const availableTickets = ticket.available_quantity;
                            if (availableTickets > 0) {
                                ticketsHtml += `
                                    <div class="ticket-type-item" onclick="selectTicketTypeModal(${ticket.id}, '${ticket.name.replace(/'/g, "\\'")}'', ${ticket.price})">
                                        <div class="ticket-type-info">
                                            <h5>${ticket.name}</h5>
                                            <p>${ticket.description || 'Bilet açıklaması'}</p>
                                            <p style="color: #28a745; font-size: 12px; margin-top: 5px;">${availableTickets} adet kaldı</p>
                                        </div>
                                        <div class="ticket-type-price">${ticket.price} ₺</div>
                                    </div>
                                `;
                            }
                        });
                        
                        if (ticketsHtml) {
                            ticketTypesList.innerHTML = ticketsHtml;
                        } else {
                            ticketTypesList.innerHTML = '<div style="text-align: center; padding: 40px; color: #666;">Bu etkinlik için müsait bilet bulunmuyor.</div>';
                        }
                    } else {
                        ticketTypesList.innerHTML = '<div style="text-align: center; padding: 40px; color: #666;">Bu etkinlik için bilet türü tanımlanmamış.</div>';
                    }
                })
                .catch(error => {
                    console.error('Error loading ticket types:', error);
                    ticketTypesList.innerHTML = '<div style="text-align: center; padding: 40px; color: #e74c3c;">Bilet türleri yüklenirken hata oluştu.</div>';
                });
        }
        
        function selectTicketTypeModal(ticketId, ticketName, price) {
            selectedTicketType = { id: ticketId, name: ticketName, price: price };
            ticketPrice = price;
            
            document.getElementById('selectedTicketName').textContent = ticketName;
            document.getElementById('selectedTicketPrice').textContent = price + ' ₺';
            document.getElementById('ticketQuantity').textContent = '1';
            document.getElementById('totalPrice').textContent = price + ' ₺';
            
            ticketQuantity = 1;
            
            // Hide tickets step, show quantity step
            document.getElementById('ticketsStep').style.display = 'none';
            document.getElementById('quantityStep').style.display = 'block';
        }
        
        function changeQuantity(change) {
            const newQuantity = ticketQuantity + change;
            if (newQuantity >= 1 && newQuantity <= 10) {
                ticketQuantity = newQuantity;
                document.getElementById('ticketQuantity').textContent = ticketQuantity;
                document.getElementById('totalPrice').textContent = (ticketPrice * ticketQuantity) + ' ₺';
            }
            
            // Update button states
            document.querySelector('.quantity-btn[onclick="changeQuantity(-1)"]').disabled = ticketQuantity <= 1;
            document.querySelector('.quantity-btn[onclick="changeQuantity(1)"]').disabled = ticketQuantity >= 10;
        }
        
        function proceedToPayment() {
            if (!selectedEvent || !selectedTicketType) {
                alert('Lütfen bir etkinlik ve bilet türü seçin.');
                return;
            }
            
            <?php if (isset($_SESSION['user_id'])): ?>
            // Add to cart and redirect to payment
            const formData = new FormData();
            formData.append('event_id', selectedEvent.id);
            formData.append('ticket_type_id', selectedTicketType.id);
            formData.append('quantity', ticketQuantity);
            formData.append('action', 'add');
            
            fetch('ajax/cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'odeme.php';
                } else {
                    alert(data.message || 'Sepete eklenirken hata oluştu.');
                }
            })
            .catch(error => {
                console.error('Error adding to cart:', error);
                alert('Bağlantı hatası oluştu.');
            });
            <?php else: ?>
            closeAIModal();
            openModal('loginModal');
            <?php endif; ?>
        }
        
        function backToLocationStep() {
            document.getElementById('eventsStep').style.display = 'none';
            document.getElementById('locationStep').style.display = 'block';
        }
        
        function backToEventsStep() {
            document.getElementById('ticketsStep').style.display = 'none';
            document.getElementById('eventsStep').style.display = 'block';
        }
        
        function backToTicketsStep() {
            document.getElementById('quantityStep').style.display = 'none';
            document.getElementById('ticketsStep').style.display = 'block';
        }

        // Close AI modal when clicking outside
        document.addEventListener('click', function(e) {
            const aiModal = document.getElementById('aiModal');
            if (e.target === aiModal) {
                closeAIModal();
            }
        });

        // Close AI modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const aiModal = document.getElementById('aiModal');
                if (aiModal && aiModal.style.display === 'flex') {
                    closeAIModal();
                }
            }
        });
    </script>
</body>
</html>
