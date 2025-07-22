<?php include 'includes/header.php'; ?>
<link rel="stylesheet" href="css/pages.css">

<main>
    <div class="page-container">
        <div class="page-header">
            <h1 class="page-title">Jack<span style="color: #FFD700;">Point</span></h1>
            <p class="page-subtitle">Bilet satın aldıkça puan kazan, puanlarını çeşitli avantajlara dönüştür!</p>
        </div>

        <!-- Puan Durumu -->
        <div class="content-section">
            <div class="points-dashboard">
                <div class="current-points">
                    <div class="points-icon">🏆</div>
                    <div class="points-info">
                        <h2>Mevcut Puanınız</h2>
                        <div class="points-value">1,250 <span>JackPoint</span></div>
                    </div>
                </div>
                
                <div class="points-stats">
                    <div class="stat-item">
                        <div class="stat-number">15</div>
                        <div class="stat-label">Satın Alınan Bilet</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">₺2,450</div>
                        <div class="stat-label">Toplam Harcama</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">Gold</div>
                        <div class="stat-label">Üyelik Seviyesi</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Puan Kazanma Yolları -->
        <div class="content-section">
            <h2>Puan Nasıl Kazanılır?</h2>
            <div class="earning-methods">
                <div class="method-item">
                    <div class="method-icon">🎫</div>
                    <div class="method-content">
                        <h3>Bilet Satın Al</h3>
                        <p>Her ₺10 harcama için 1 JackPoint kazan</p>
                    </div>
                    <div class="method-points">+1 Puan</div>
                </div>
                
                <div class="method-item">
                    <div class="method-icon">👥</div>
                    <div class="method-content">
                        <h3>Arkadaş Davet Et</h3>
                        <p>Davet ettiğin her arkadaş için bonus puan</p>
                    </div>
                    <div class="method-points">+50 Puan</div>
                </div>
                
                <div class="method-item">
                    <div class="method-icon">⭐</div>
                    <div class="method-content">
                        <h3>Etkinlik Değerlendir</h3>
                        <p>Katıldığın etkinlikleri değerlendirerek puan kazan</p>
                    </div>
                    <div class="method-points">+10 Puan</div>
                </div>
                
                <div class="method-item">
                    <div class="method-icon">🎂</div>
                    <div class="method-content">
                        <h3>Doğum Günü Bonusu</h3>
                        <p>Doğum günün ayında özel bonus puan</p>
                    </div>
                    <div class="method-points">+100 Puan</div>
                </div>
            </div>
        </div>

        <!-- Puan Dönüştürme -->
        <div class="content-section">
            <h2>Puanlarını Dönüştür</h2>
            <div class="rewards-grid">
                <div class="reward-item">
                    <div class="reward-image">🎟️</div>
                    <div class="reward-content">
                        <h3>₺25 İndirim Kuponu</h3>
                        <p>Sonraki bilet alımında kullan</p>
                        <div class="reward-cost">500 JackPoint</div>
                    </div>
                    <button class="redeem-btn">Dönüştür</button>
                </div>
                
                <div class="reward-item">
                    <div class="reward-image">🎁</div>
                    <div class="reward-content">
                        <h3>₺50 İndirim Kuponu</h3>
                        <p>Tüm etkinliklerde geçerli</p>
                        <div class="reward-cost">1,000 JackPoint</div>
                    </div>
                    <button class="redeem-btn">Dönüştür</button>
                </div>
                
                <div class="reward-item">
                    <div class="reward-image">🏆</div>
                    <div class="reward-content">
                        <h3>VIP Etkinlik Bileti</h3>
                        <p>Seçili etkinliklerde VIP deneyimi</p>
                        <div class="reward-cost">2,000 JackPoint</div>
                    </div>
                    <button class="redeem-btn disabled">Yetersiz Puan</button>
                </div>
                
                <div class="reward-item">
                    <div class="reward-image">🎪</div>
                    <div class="reward-content">
                        <h3>Ücretsiz Etkinlik Bileti</h3>
                        <p>Belirli etkinliklerde ücretsiz giriş</p>
                        <div class="reward-cost">1,500 JackPoint</div>
                    </div>
                    <button class="redeem-btn disabled">Yetersiz Puan</button>
                </div>
            </div>
        </div>

        <!-- Puan Geçmişi -->
        <div class="content-section">
            <h2>Puan Geçmişi</h2>
            <div class="points-history">
                <div class="history-item">
                    <div class="history-date">15 Mart 2024</div>
                    <div class="history-action">Sezen Aksu Konseri - Bilet Satın Alma</div>
                    <div class="history-points">+25 Puan</div>
                </div>
                
                <div class="history-item">
                    <div class="history-date">10 Mart 2024</div>
                    <div class="history-action">Arkadaş Daveti - Mehmet Yılmaz</div>
                    <div class="history-points">+50 Puan</div>
                </div>
                
                <div class="history-item">
                    <div class="history-date">5 Mart 2024</div>
                    <div class="history-action">Galatasaray Maçı - Bilet Satın Alma</div>
                    <div class="history-points">+18 Puan</div>
                </div>
                
                <div class="history-item">
                    <div class="history-date">1 Mart 2024</div>
                    <div class="history-action">₺25 İndirim Kuponu Kullanımı</div>
                    <div class="history-points">-500 Puan</div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>