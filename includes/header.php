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
    <link rel="stylesheet" href="css/sidebar.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background:rgba(87, 87, 87, 0.61); /* Koyu siyah arka plan */
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
            color: white;
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
            background: rgba(255, 255, 255, 0.1);
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
        }

        .dropdown-btn:hover {
            background: rgba(255, 255, 255, 0.2);
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
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            color: white;
            font-weight: 600;
            line-height: 1.3;
        }

        .event-venue, .event-date {
            color: rgba(255, 255, 255, 0.7);
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
            color: white;
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
            color: white;
            font-size: 2.2rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .newsletter p {
            color: rgba(255, 255, 255, 0.8);
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
            background: rgba(255, 255, 255, 0.1);
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
            background: rgba(255, 255, 255, 0.15);
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
            margin-bottom: 1.5rem;
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
            z-index: 1000;
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
            transition: transform 0.3s ease;
            overflow-y: auto;
        }

        .sidebar.active .sidebar-content {
            transform: translateX(0);
        }

        .sidebar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 2rem 1.5rem 1rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
        }

        .sidebar-logo-img {
            height: 35px;
            width: auto;
            max-width: 160px;
            object-fit: contain;
        }

        .sidebar-header h2 {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 600;
            color: white;
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
            }
            
            .logo-image {
                height: 35px;
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
                margin-left: -185px;
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
            position: relative; /* absolute yerine relative */
            min-width: 100%; /* width yerine min-width */
            height: 100%;
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 1; /* Tüm slide'lar görünür */
            flex-shrink: 0; /* Sıkıştırılmaması için */
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

        .slider-dots {
            position: absolute;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 1rem;
            z-index: 3;
        }

        .dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .dot.active {
            background: white;
            transform: scale(1.2);
        }

        /* Quick Categories Styles */
        .quick-categories {
            padding: 2rem 0;
            background: rgba(255, 255, 255, 0.02);
        }

        .category-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .category-btn {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 1rem 1.5rem;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            min-width: 100px;
        }

        .category-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .category-btn-icon {
            font-size: 1.5rem;
        }

        .category-btn span {
            font-size: 0.9rem;
            font-weight: 500;
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
        
        
        .section-title {
            text-align: center;
            font-size: 2.2rem;
            margin-bottom: 3rem;
            color: white;
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

        /* Modal Stilleri */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 10000;
            animation: fadeIn 0.3s ease;
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            position: relative;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.95) 0%, rgba(240, 240, 240, 0.9) 100%);
            border-radius: 15px;
            width: 90%;
            max-width: 450px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.3s ease;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 2rem 1rem 2rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
        }

        .modal-close {
            background: rgba(0, 0, 0, 0.1);
            border: none;
            color: #666;
            width: 35px;
            height: 35px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-close:hover {
            background: rgba(0, 0, 0, 0.2);
            color: #333;
        }

        .modal-body {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
            font-size: 0.9rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.2s ease;
            background: white;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .form-row .form-group {
            margin-bottom: 0;
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            cursor: pointer;
            font-size: 0.9rem;
            color: #666;
        }

        .checkbox-label input[type="checkbox"] {
            display: none;
        }

        .checkmark {
            width: 18px;
            height: 18px;
            border: 2px solid #ddd;
            border-radius: 4px;
            margin-right: 0.5rem;
            position: relative;
            transition: all 0.2s ease;
        }

        .checkbox-label input[type="checkbox"]:checked + .checkmark {
            background: #667eea;
            border-color: #667eea;
        }

        .checkbox-label input[type="checkbox"]:checked + .checkmark::after {
            content: '✓';
            position: absolute;
            top: -2px;
            left: 2px;
            color: white;
            font-size: 12px;
            font-weight: bold;
        }

        .forgot-password, .terms-link {
            color: #667eea;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.2s ease;
        }

        .forgot-password:hover, .terms-link:hover {
            color: #5a6fd8;
            text-decoration: underline;
        }

        .modal-btn {
            width: 100%;
            padding: 1rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .modal-btn.primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .modal-btn.primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .modal-footer {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
        }

        .modal-footer p {
            margin: 0 0 0.5rem 0;
            color: #666;
            font-size: 0.9rem;
        }

        .modal-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }

        .modal-footer a:hover {
            text-decoration: underline;
        }

        .message {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 14px;
            font-weight: 500;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .message.warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .message.info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
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
            
            .slide-content h2 {
                font-size: 2rem;
            }
            
            .slide-content p {
                font-size: 1rem;
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

            .category-buttons {
                gap: 0.5rem;
            }

            .category-btn {
                padding: 0.8rem 1rem;
                min-width: 80px;
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

            .slide-content h2 {
                font-size: 1.5rem;
            }

            .slide-btn {
                padding: 0.8rem 1.5rem;
                font-size: 0.9rem;
            }

            .category-buttons {
                grid-template-columns: repeat(3, 1fr);
                gap: 0.5rem;
            }
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
                <div class="sidebar-logo">
                    <img src="uploads/logo.png" alt="BiletJack" class="sidebar-logo-img">
                </div>
            </div>
            <div class="sidebar-body">
                <!-- Mobile Search (sadece mobilde görünür) -->
                <div class="mobile-search">
                    <form class="header-search" method="GET" action="etkinlikler.php">
                        <input type="text" name="search" class="search-field" placeholder="Sanatçı, mekan, etkinlik ara...">
                        <button type="submit" class="header-search-btn">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
                            </svg>
                            Ara
                        </button>
                    </form>
                </div>
                <div class="account-options">
                    <?php if ($isLoggedIn): ?>
                        <!-- Giriş yapmış kullanıcı menüsü -->
                        <div class="user-welcome">
                            <h3>Hoş geldiniz, <?php echo htmlspecialchars($currentUser['first_name']); ?>!</h3>
                            <p class="user-type"><?php 
                                switch($userType) {
                                    case 'admin': echo 'Yönetici'; break;
                                    case 'organizer': echo 'Organizatör'; break;
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
                        <?php elseif ($userType === 'customer'): ?>
                            <a href="customer/tickets.php" class="account-option-btn">
                                <div class="option-content">
                                    <h3>Biletlerim</h3>
                                </div>
                                <div class="option-arrow"></div>
                            </a>
                            <a href="customer/profile.php" class="account-option-btn">
                                <div class="option-content">
                                    <h3>Profilim</h3>
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

                    <a href="jackpoint.php" class="account-option-btn">
                        <div class="option-content">
                        <h3>JackPoint</h3>
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
                <div class="social-media-links">
                    <h3>Bizi Takip Edin</h3>
                    <div class="social-icons">
                        <a href="#" class="social-icon" title="Instagram">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
                            </svg>
                        </a>
                        <a href="#" class="social-icon" title="Facebook">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                        </a>
                        <a href="#" class="social-icon" title="Twitter/X">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                            </svg>
                        </a>
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
            const dots = document.querySelectorAll('.dot');
            
            // Slider elementleri yoksa çık
            if (!sliderContainer || slides.length === 0 || dots.length === 0) {
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
            
            // Nokta göstergelerini güncelle
            function updateDots() {
                dots.forEach((dot, index) => {
                    dot.classList.toggle('active', index === currentSlideIndex);
                });
            }
            
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
                updateDots();
            }
            
            // Belirli bir slide'a git
            function goToSlide(index) {
                currentSlideIndex = index;
                sliderContainer.style.transform = `translateX(${-index * 100}%)`;
            }
            
            // Nokta tıklamaları için
            dots.forEach((dot, index) => {
                dot.addEventListener('click', () => {
                    goToSlide(index);
                    updateDots();
                });
            });
            
            // Otomatik kaydırma
            setInterval(() => {
                if (!isDragging) {
                    currentSlideIndex = (currentSlideIndex + 1) % totalSlides;
                    goToSlide(currentSlideIndex);
                    updateDots();
                }
            }, 5000);
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
            document.getElementById('accountSidebar').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeAccountSidebar() {
            document.getElementById('accountSidebar').classList.remove('active');
            document.body.style.overflow = 'auto';
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
            }
        });

        // Login Form Handler - Güncellenmiş
        document.getElementById('loginForm').addEventListener('submit', function(e) {
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
                        window.location.href = '/Biletjack/index.php';
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
        document.getElementById('registerForm').addEventListener('submit', function(e) {
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
    </script>
</body>
</html>
