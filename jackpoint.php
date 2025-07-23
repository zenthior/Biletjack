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
                        <p>Her ₺10 harcama için 10 JackPoint kazan</p>
                    </div>
                    <div class="method-points">+10 Puan</div>
                </div>
                
                <div class="method-item">
                    <div class="method-icon">👥</div>
                    <div class="method-content">
                        <h3>Arkadaş Davet Et</h3>
                        <p>Davet ettiğin her arkadaş için bonus puan</p>
                    </div>
                    <div class="method-points">+20 Puan</div>
                </div>
                
                
                <div class="method-item">
                    <div class="method-icon">🎂</div>
                    <div class="method-content">
                        <h3>Doğum Günü Bonusu</h3>
                        <p>Doğum günün ayında özel bonus puan</p>
                    </div>
                    <div class="method-points">+50 Puan</div>
                </div>
            </div>
        </div>


        <!-- Puan Geçmişi -->
        <div class="content-section">
            <h2>Puan Geçmişi</h2>
            <div class="points-history">
                <div class="history-item">
                    <div class="history-date"></div>
                    <div class="history-action"></div>
                    <div class="history-points"></div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>