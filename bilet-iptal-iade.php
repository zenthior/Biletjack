<?php 
require_once 'includes/session.php';
include 'includes/header.php'; 
?>

<main>
    <div class="page-container">
        <div class="page-header">
            <h1>Bilet İptal ve İade Koşulları</h1>
            <p>BiletJack bilet iptal ve iade süreçleri hakkında bilmeniz gerekenler</p>
        </div>

        <div class="content-section">
            <div class="info-card">
                <h2>📋 Genel Bilgiler</h2>
                <p>BiletJack olarak müşteri memnuniyetini ön planda tutarak, bilet iptal ve iade işlemlerinizi mümkün olan en kolay şekilde gerçekleştirmenizi sağlıyoruz.</p>
            </div>

            <div class="info-card">
                <h2>⏰ İptal Süreleri</h2>
                <ul>
                    <li><strong>Konserler:</strong> Etkinlik tarihinden en az 48 saat önce</li>
                    <li><strong>Tiyatro:</strong> Etkinlik tarihinden en az 24 saat önce</li>
                    <li><strong>Spor Etkinlikleri:</strong> Etkinlik tarihinden en az 72 saat önce</li>
                    <li><strong>Festival:</strong> Etkinlik tarihinden en az 7 gün önce</li>
                    <li><strong>Çocuk Etkinlikleri:</strong> Etkinlik tarihinden en az 24 saat önce</li>
                </ul>
            </div>

            <div class="info-card">
                <h2>💰 İade Koşulları</h2>
                <div class="refund-info">
                    <div class="refund-item">
                        <h3>Tam İade</h3>
                        <p>Etkinlik iptal edildiğinde veya ertelendiğinde bilet bedelinin %100'ü iade edilir.</p>
                    </div>
                    <div class="refund-item">
                        <h3>Kısmi İade</h3>
                        <p>Müşteri kaynaklı iptal işlemlerinde hizmet bedeli düşülerek %85 iade yapılır.</p>
                    </div>
                    <div class="refund-item">
                        <h3>İade Süresi</h3>
                        <p>İade işlemleri 5-10 iş günü içerisinde tamamlanır.</p>
                    </div>
                </div>
            </div>

            <div class="info-card">
                <h2>📝 İptal İşlemi Nasıl Yapılır?</h2>
                <div class="steps">
                    <div class="step">
                        <span class="step-number">1</span>
                        <div class="step-content">
                            <h3>Giriş Yapın</h3>
                            <p>BiletJack hesabınıza giriş yapın</p>
                        </div>
                    </div>
                    <div class="step">
                        <span class="step-number">2</span>
                        <div class="step-content">
                            <h3>Biletlerim</h3>
                            <p>"Biletlerim" bölümüne gidin</p>
                        </div>
                    </div>
                    <div class="step">
                        <span class="step-number">3</span>
                        <div class="step-content">
                            <h3>İptal Et</h3>
                            <p>İptal etmek istediğiniz bileti seçin ve "İptal Et" butonuna tıklayın</p>
                        </div>
                    </div>
                    <div class="step">
                        <span class="step-number">4</span>
                        <div class="step-content">
                            <h3>Onay</h3>
                            <p>İptal nedeninizi belirtin ve işlemi onaylayın</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="info-card">
                <h2>❌ İptal Edilemeyen Durumlar</h2>
                <ul>
                    <li>Etkinlik başladıktan sonra</li>
                    <li>Belirlenen iptal süresi geçtikten sonra</li>
                    <li>Özel indirimli biletler (kampanya koşullarına göre)</li>
                    <li>Hediye biletler (satın alan kişi tarafından iptal edilebilir)</li>
                </ul>
            </div>

            <div class="info-card">
                <h2>📞 İletişim</h2>
                <p>İptal ve iade işlemlerinizle ilgili sorularınız için:</p>
                <div class="contact-info">
                    <div class="contact-item">
                        <strong>📧 E-posta:</strong> destek@biletjack.com
                    </div>
                    <div class="contact-item">
                        <strong>📱 Telefon:</strong> 0850 123 45 67
                    </div>
                    <div class="contact-item">
                        <strong>🕐 Çalışma Saatleri:</strong> Pazartesi-Pazar 09:00-22:00
                    </div>
                </div>
            </div>

            <div class="info-card warning">
                <h2>⚠️ Önemli Uyarı</h2>
                <p>Bu sayfa bilgilendirme amaçlıdır. Güncel koşullar ve detaylı bilgi için lütfen müşteri hizmetlerimizle iletişime geçin.</p>
            </div>
        </div>
    </div>
</main>

<style>
.page-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 40px 20px;
    min-height: 80vh;
}

.page-header {
    text-align: center;
    margin-bottom: 50px;
    padding: 40px 0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 15px;
}

.page-header h1 {
    font-size: 2.5rem;
    margin-bottom: 15px;
    font-weight: 700;
}

.page-header p {
    font-size: 1.1rem;
    opacity: 0.9;
}

.content-section {
    display: flex;
    flex-direction: column;
    gap: 30px;
}

.info-card {
    background: white;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    border-left: 5px solid #667eea;
}

.info-card.warning {
    border-left-color: #f39c12;
    background: #fff8e1;
}

.info-card h2 {
    color: #333;
    margin-bottom: 20px;
    font-size: 1.5rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.info-card h3 {
    color: #555;
    margin-bottom: 10px;
    font-size: 1.2rem;
}

.info-card ul {
    list-style: none;
    padding: 0;
}

.info-card li {
    padding: 8px 0;
    border-bottom: 1px solid #eee;
    position: relative;
    padding-left: 20px;
}

.info-card li:before {
    content: "✓";
    position: absolute;
    left: 0;
    color: #667eea;
    font-weight: bold;
}

.refund-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.refund-item {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
    border: 2px solid #e9ecef;
}

.refund-item h3 {
    color: #667eea;
    margin-bottom: 10px;
}

.steps {
    display: flex;
    flex-direction: column;
    gap: 20px;
    margin-top: 20px;
}

.step {
    display: flex;
    align-items: flex-start;
    gap: 20px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 10px;
}

.step-number {
    background: #667eea;
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    flex-shrink: 0;
}

.step-content h3 {
    margin-bottom: 5px;
    color: #333;
}

.contact-info {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-top: 20px;
}

.contact-item {
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #667eea;
}

@media (max-width: 768px) {
    .page-header h1 {
        font-size: 2rem;
    }
    
    .info-card {
        padding: 20px;
    }
    
    .refund-info {
        grid-template-columns: 1fr;
    }
    
    .step {
        flex-direction: column;
        text-align: center;
    }
}
</style>

<?php include 'includes/footer.php'; ?>