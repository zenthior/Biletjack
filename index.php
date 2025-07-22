<?php include 'includes/header.php'; ?>

    <main>
        <!-- Hero Slider Section -->
        <section class="hero-slider">
            <div class="slider-container">
                <div class="slide active" style="background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('https://images.unsplash.com/photo-1501386761578-eac5c94b800a?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80')">
                    <div class="slide-content">
                        <h2>Sezen Aksu Konseri</h2>
                        <p>15 Mart 2024 - Volkswagen Arena</p>
                        <button class="slide-btn">Bilet Al</button>
                    </div>
                </div>
                <div class="slide" style="background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('https://images.unsplash.com/photo-1574391884720-bbc3740c59d1?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2069&q=80')">
                    <div class="slide-content">
                        <h2>Galatasaray vs Fenerbahçe</h2>
                        <p>20 Mart 2024 - Türk Telekom Stadyumu</p>
                        <button class="slide-btn">Bilet Al</button>
                    </div>
                </div>
                <div class="slide" style="background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80')">
                    <div class="slide-content">
                        <h2>Şahsiyet Tiyatro Oyunu</h2>
                        <p>25 Mart 2024 - Devlet Tiyatrosu</p>
                        <button class="slide-btn">Bilet Al</button>
                    </div>
                </div>
                
                <!-- Slider Navigation kısmını tamamen kaldırın (30-33. satırlar) -->
                <!-- <div class="slider-nav">
                    <button class="nav-btn prev" onclick="changeSlide(-1)">❮</button>
                    <button class="nav-btn next" onclick="changeSlide(1)">❯</button>
                </div> -->
                
                <!-- Slider Dots -->
                <div class="slider-dots">
                    <span class="dot active" onclick="currentSlide(1)"></span>
                    <span class="dot" onclick="currentSlide(2)"></span>
                    <span class="dot" onclick="currentSlide(3)"></span>
                </div>
            </div>
        </section>

        <!-- Quick Category Buttons -->
        <section class="quick-categories">
            <div class="container">
                <div class="category-buttons">
                    <button class="category-btn" data-category="konser">
                        <div class="category-btn-icon">🎵</div>
                        <span>Konserler</span>
                    </button>
                    <button class="category-btn" data-category="spor">
                        <div class="category-btn-icon">⚽</div>
                        <span>Spor</span>
                    </button>
                    <button class="category-btn" data-category="tiyatro">
                        <div class="category-btn-icon">🎭</div>
                        <span>Tiyatro</span>
                    </button>
                    <button class="category-btn" data-category="eglence">
                        <div class="category-btn-icon">🎪</div>
                        <span>Eğlence</span>
                    </button>
                    <button class="category-btn" data-category="cocuk">
                        <div class="category-btn-icon">🎈</div>
                        <span>Çocuk</span>
                    </button>
                    <button class="category-btn" data-category="festival">
                        <div class="category-btn-icon">🎉</div>
                        <span>Festival</span>
                    </button>
                </div>
            </div>
        </section>


     <!-- Popular Events Section -->
        <section class="popular-events">
            <div class="container">
                <div class="section-header">
                    <div class="section-left">
                        <h2 class="section-title">Tüm Etkinlikler</h2>
                    </div>
                    
                    <!-- Sıralama Butonları -->
                    <div class="section-right">
                        <div class="sorting-controls">
                            <div class="dropdown">
                                <button class="dropdown-btn">Sırala <span class="dropdown-arrow">▼</span></button>
                                <div class="dropdown-content">
                                    <a href="#" data-sort="all">Tümü</a>
                                    <a href="#" data-sort="date">Tarihe Göre</a>
                                    <a href="#" data-sort="price">Fiyata Göre</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="events-grid view-4">
                    <?php
                    // Örnek etkinlik verileri
                    $events = [
                        [
                            'title' => 'Sezen Aksu Konseri',
                            'date' => '15 Mart 2024',
                            'price' => '₺250',
                            'location' => 'İstanbul',
                            'venue' => 'Volkswagen Arena',
                            'category' => 'Konser',
                            'image_bg' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'
                        ],
                        [
                            'title' => 'Galatasaray vs Fenerbahçe',
                            'date' => '20 Mart 2024',
                            'price' => '₺180',
                            'location' => 'İstanbul',
                            'venue' => 'Türk Telekom Stadyumu',
                            'category' => 'Spor',
                            'image_bg' => 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)'
                        ],
                        [
                            'title' => 'Şahsiyet Tiyatro Oyunu',
                            'date' => '25 Mart 2024',
                            'price' => '₺120',
                            'location' => 'Ankara',
                            'venue' => 'Devlet Tiyatrosu',
                            'category' => 'Tiyatro',
                            'image_bg' => 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)'
                        ],
                        [
                            'title' => 'Manga Konseri',
                            'date' => '30 Mart 2024',
                            'price' => '₺200',
                            'location' => 'İzmir',
                            'venue' => 'Kültürpark Açıkhava',
                            'category' => 'Konser',
                            'image_bg' => 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)'
                        ],
                        [
                            'title' => 'Beşiktaş vs Trabzonspor',
                            'date' => '5 Nisan 2024',
                            'price' => '₺160',
                            'location' => 'İstanbul',
                            'venue' => 'Vodafone Park',
                            'category' => 'Spor',
                            'image_bg' => 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)'
                        ],
                        [
                            'title' => 'Kenan Doğulu Konseri',
                            'date' => '10 Nisan 2024',
                            'price' => '₺280',
                            'location' => 'Bursa',
                            'venue' => 'Merinos Kültür Merkezi',
                            'category' => 'Konser',
                            'image_bg' => 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)'
                        ]
                    ];

                    foreach ($events as $index => $event) {
                        $eventParams = http_build_query([
                            'title' => $event['title'],
                            'date' => $event['date'],
                            'venue' => $event['venue'],
                            'location' => $event['location'],
                            'price' => $event['price'],
                            'category' => $event['category'],
                            'imageBg' => $event['image_bg']
                        ]);
                        
                        echo '<div class="event-card" onclick="window.location.href=\'etkinlik-detay.php?' . $eventParams . '\'" style="cursor: pointer;">';
                        echo '<div class="event-image" style="background: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.7)), ' . $event['image_bg'] . '">';
                        echo '<div class="event-location">' . $event['location'] . '</div>';
                        echo '</div>';
                        echo '<div class="event-content">';
                        echo '<h3 class="event-title">' . $event['title'] . '</h3>';
                        echo '<p class="event-venue">🏛️ ' . $event['venue'] . '</p>';
                        echo '<p class="event-date">📅 ' . $event['date'] . '</p>';
                        echo '<div class="event-footer">';
                        echo '<span class="event-price">' . $event['price'] . '</span>';
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
        </section>

        <!-- Newsletter Section -->
        <section class="newsletter">
            <div class="container">
                <div class="newsletter-content">
                    <h2>Fırsatları Kaçırma!</h2>
                    <p>Yeni etkinlikler ve özel indirimlerden haberdar olmak için e-bültenimize abone ol.</p>
                    <form class="newsletter-form">
                        <input type="email" placeholder="E-posta adresinizi girin" class="newsletter-input">
                        <button type="submit" class="newsletter-btn">Abone Ol</button>
                    </form>
                </div>
            </div>
        </section>
    </main>

<?php include 'includes/footer.php'; ?>