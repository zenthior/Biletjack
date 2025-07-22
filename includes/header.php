<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BiletJack - Bilet Satış Platformu</title>
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
            justify-content: flex-start;
            align-items: center;
            padding: 0 2rem;
            gap: 2rem;
            position: relative;
        }

        .logo {
            flex-shrink: 0;
            margin-right: 1rem;
        }

        .header-search {
            display: flex;
            gap: 2.1rem;
            align-items: center;
            max-width: 500px;
            background: rgba(0, 0, 0, 0.9); /* Beyaz arka plan */
            border-radius: 25px;
            padding: 0.4rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            margin: 0 auto;
            margin-right: auto;
        }

        .search-field {
            flex: 1;
            min-width: 80px;
            padding: 0.7rem 0.8rem;
            border: none;
            border-radius: 15px;
            background: transparent;
            font-size: 0.9rem;
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

        .search-divider {
            width: 1px;
            height: 25px;
            background: #e0e0e0;
            margin: 0 0.5rem;
        }

        .header-search-btn {
            background: #333; /* Koyu gri buton */
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
            background: #555; /* Hover durumunda açık gri */
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

loads        .logo {
            display: flex;
            align-items: center;
            text-decoration: none;
        }

        .logo-image {
            height: 40px;
            width: auto;
        }

        .sidebar-logo {
            display: flex;
            justify-content: center;
            margin-bottom: 10px;
        }

        .sidebar-logo-image {
            height: 35px;
            width: auto;
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

        .section-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .section-title {
            color: white;
            font-size: 2.5rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .section-subtitle {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.1rem;
            margin: 0;
        }

        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .event-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .event-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 50px rgba(0,0,0,0.2);
        }

        .event-image {
            height: 200px;
            position: relative;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            padding: 1.5rem;
            color: white;
        }

        .event-category {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .event-location {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .event-content {
            padding: 1.5rem;
        }

        .event-title {
            font-size: 1.4rem;
            margin-bottom: 0.8rem;
            color: #333;
            font-weight: 600;
            line-height: 1.3;
        }

        .event-venue {
            color: #666;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .event-date {
            color: #666;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }

        .event-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .event-price {
            font-size: 1.5rem;
            font-weight: bold;
            color: #667eea;
        }

        .buy-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .buy-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
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

        /* Nav Styles - Hesap Butonu için */
        .nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
            position: relative;
        }

        /* Account Button Styles - Sağa Alındı */
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
            margin-left: auto;
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
            background-color: rgba(0, 0, 0, 0.28);
            backdrop-filter: blur(10px);
            cursor: pointer;
            /* Animasyon kaldırıldı - direkt blur */
        }

        .sidebar-content {
            position: absolute;
            top: 0;
            right: 0;
            width: 350px;
            height: 100%;
            background: linear-gradient(180deg,rgba(255, 255, 255, 0.87) 0%,rgba(24, 23, 23, 0.6) 50%,rgba(0, 0, 0, 0.61) 100%);
            box-shadow: -10px 0 30px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
            color: white;
            transform: translateX(100%);
            transition: transform 0.3s ease;
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

        .sidebar-header h2 {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 600;
            color: white;
        }

        .sidebar-close {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sidebar-close:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .sidebar-body {
            flex: 1;
            padding: 1rem;
            overflow-y: auto;
        }

        .account-options {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
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

        /* Papilet Tarzı Butonlar */
        .account-option-btn {
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

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .nav {
                flex-wrap: wrap;
                gap: 1rem;
            }
            
            .account-btn {
                margin-left: 1rem;
            }
            
            .sidebar-content {
                width: 100%;
                max-width: 320px;
            }
        }

        @media (max-width: 480px) {
            .account-btn {
                margin-left: 0;
                margin-top: 1rem;
                width: 100%;
                justify-content: center;
            }
            
            .sidebar-content {
                width: 100%;
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
            
            <form class="header-search" method="POST" action="search.php">
                <input type="text" name="keyword" class="search-field" placeholder="Sanatçı, mekan, etkinlik ara...">
                
                <button type="submit" class="header-search-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
                    </svg>
                    Ara
                </button>
            </form>
            
            <!-- Hesap Butonu - Sağa Alındı -->
            <button class="account-btn" onclick="openAccountSidebar()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                </svg>
                Hesap
            </button>
        </nav>
    </header>

    <!-- Account Sidebar -->
    <div id="accountSidebar" class="sidebar">
        <div class="sidebar-overlay" onclick="closeAccountSidebar()"></div>
        <div class="sidebar-content">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <img src="./uploads/logo.png" alt="BiletJack Logo" class="sidebar-logo-image">
                </div>
                <button class="sidebar-close" onclick="closeAccountSidebar()">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                    </svg>
                </button>
            </div>
            <div class="sidebar-body">
                <div class="account-options">
                    <button class="account-option-btn" onclick="showLoginForm()">
                        <div class="option-icon"></div>
                        <div class="option-content">
                            <h3>Giriş Yap</h3>
                            <p>Mevcut hesabınızla giriş yapın</p>
                        </div>
                        <div class="option-arrow">→</div>
                    </button>
                    
                    <button class="account-option-btn" onclick="showRegisterForm()">
                        <div class="option-icon"></div>
                        <div class="option-content">
                            <h3>Kayıt Ol</h3>
                            <p>Yeni hesap oluşturun</p>
                        </div>
                        <div class="option-arrow">→</div>
                    </button>
                    
                    <button class="account-option-btn" onclick="showOrganizerForm()">
                        <div class="option-icon"></div>
                        <div class="option-content">
                            <h3>Organizatör Kaydı</h3>
                            <p>Etkinlik düzenleyicisi olarak kayıt olun</p>
                        </div>
                        <div class="option-arrow">→</div>
                    </button>
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
    <script>
        // Kaydırmalı Slider JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            const sliderContainer = document.querySelector('.slider-container');
            const slides = document.querySelectorAll('.slide');
            const dots = document.querySelectorAll('.dot');
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
                // Here you can add functionality to filter events by category
                console.log('Selected category:', category);
                
                // Add active state
                document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
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
            // Giriş yap formunu göster
            window.location.href = 'login.php';
            closeAccountSidebar();
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

        // ESC tuşu ile sidebar kapat
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeAccountSidebar();
            }
        });
    </script>
</body>
</html>
