<?php
include 'includes/header.php';
?>

<link rel="stylesheet" href="css/pages2.css">

<style>
    .cookie-container {
        max-width: 1000px;
        margin: 50px auto;
        padding: 40px;
        background: linear-gradient(135deg, rgba(42, 42, 42, 0.95) 0%, rgba(58, 58, 58, 0.95) 100%);
        backdrop-filter: blur(20px);
        border-radius: 20px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
        color: #e0e0e0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        line-height: 1.8;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .cookie-header {
        text-align: center;
        margin-bottom: 3rem;
        padding-bottom: 2rem;
        border-bottom: 2px solid rgba(138, 43, 226, 0.3);
    }

    .cookie-container h1 {
        font-size: 3rem;
        background: linear-gradient(135deg, #8a2be2 0%, #9370db 50%, #ba55d3 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        text-align: center;
        margin-bottom: 1rem;
        text-shadow: 0 4px 8px rgba(138, 43, 226, 0.3);
        font-weight: 700;
    }

    .cookie-icon {
        font-size: 4rem;
        color: #8a2be2;
        margin-bottom: 1rem;
        text-shadow: 0 0 20px rgba(138, 43, 226, 0.5);
    }

    .cookie-subtitle {
        font-size: 1.2rem;
        color: rgba(255, 255, 255, 0.8);
        margin-bottom: 2rem;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
    }

    .cookie-navigation {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        margin: 2rem 0;
        padding: 1.5rem;
        background: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(15px);
        border-radius: 15px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        justify-content: center;
    }

    .cookie-nav-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.8rem 1.5rem;
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 25px;
        color: white;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 0.9rem;
        font-weight: 500;
        text-decoration: none;
    }

    .cookie-nav-item:hover {
        background: rgba(138, 43, 226, 0.3);
        border-color: rgba(138, 43, 226, 0.5);
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(138, 43, 226, 0.3);
    }

    .cookie-nav-item.active {
        background: linear-gradient(135deg, #8a2be2 0%, #9370db 100%);
        border-color: #8a2be2;
        box-shadow: 0 8px 20px rgba(138, 43, 226, 0.4);
    }

    .cookie-section {
        background: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(10px);
        border-radius: 15px;
        padding: 2.5rem;
        margin-bottom: 2rem;
        border: 1px solid rgba(255, 255, 255, 0.1);
        transition: all 0.3s ease;
    }

    .cookie-section:hover {
        background: rgba(255, 255, 255, 0.08);
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    }

    .cookie-section h2 {
        font-size: 2.2rem;
        color: #9370db;
        margin-bottom: 1.5rem;
        border-bottom: 2px solid rgba(147, 112, 219, 0.3);
        padding-bottom: 0.8rem;
        display: flex;
        align-items: center;
        gap: 0.8rem;
    }

    .cookie-section h3 {
        font-size: 1.5rem;
        color: #ba55d3;
        margin: 2rem 0 1rem 0;
        font-weight: 600;
    }

    .cookie-section p {
        margin-bottom: 1.5rem;
        font-size: 1.1rem;
        color: #e2e8f0;
    }

    .cookie-types {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
        margin: 2rem 0;
    }

    .cookie-type-card {
        background: rgba(255, 255, 255, 0.08);
        border-radius: 12px;
        padding: 2rem;
        border-left: 4px solid #8a2be2;
        transition: all 0.3s ease;
    }

    .cookie-type-card:hover {
        transform: translateY(-5px);
        background: rgba(255, 255, 255, 0.12);
        box-shadow: 0 10px 25px rgba(138, 43, 226, 0.2);
    }

    .cookie-type-card h4 {
        color: #8a2be2;
        font-size: 1.3rem;
        margin-bottom: 1rem;
        font-weight: 600;
    }

    .cookie-type-card p {
        color: #d1d5db;
        font-size: 1rem;
        margin-bottom: 1rem;
    }

    .cookie-purpose {
        background: rgba(138, 43, 226, 0.1);
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.85rem;
        color: #ba55d3;
        display: inline-block;
        margin-bottom: 0.5rem;
        margin-right: 0.5rem;
    }

    .cookie-table {
        width: 100%;
        border-collapse: collapse;
        margin: 2rem 0;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 12px;
        overflow: hidden;
    }

    .cookie-table th,
    .cookie-table td {
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .cookie-table th {
        background: rgba(138, 43, 226, 0.2);
        color: #ba55d3;
        font-weight: 600;
    }

    .cookie-table td {
        color: #e2e8f0;
    }

    .cookie-controls {
        background: linear-gradient(135deg, #8a2be2 0%, #9370db 100%);
        padding: 2.5rem;
        border-radius: 20px;
        text-align: center;
        margin: 3rem 0;
        box-shadow: 0 15px 35px rgba(138, 43, 226, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .cookie-controls h3 {
        color: white;
        font-size: 1.8rem;
        margin-bottom: 1rem;
        font-weight: 600;
    }

    .cookie-controls p {
        color: rgba(255, 255, 255, 0.9);
        margin-bottom: 2rem;
        font-size: 1.1rem;
    }

    .cookie-buttons {
        display: flex;
        gap: 1rem;
        justify-content: center;
        flex-wrap: wrap;
    }

    .cookie-btn {
        padding: 1rem 2rem;
        border: none;
        border-radius: 25px;
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .cookie-btn.accept {
        background: white;
        color: #8a2be2;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }

    .cookie-btn.accept:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        background: #f8f9fa;
    }

    .cookie-btn.settings {
        background: rgba(255, 255, 255, 0.2);
        color: white;
        border: 2px solid rgba(255, 255, 255, 0.3);
    }

    .cookie-btn.settings:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: translateY(-3px);
    }

    .cookie-list {
        list-style: none;
        padding: 0;
        margin: 2rem 0;
    }

    .cookie-list li {
        background: rgba(255, 255, 255, 0.05);
        margin-bottom: 1rem;
        padding: 1.5rem;
        border-radius: 10px;
        border-left: 4px solid #8a2be2;
        transition: all 0.3s ease;
    }

    .cookie-list li:hover {
        transform: translateX(5px);
        background: rgba(255, 255, 255, 0.08);
    }

    .cookie-list li strong {
        color: #ba55d3;
        display: block;
        margin-bottom: 0.5rem;
    }

    .contact-info {
        background: rgba(255, 255, 255, 0.05);
        padding: 2rem;
        border-radius: 15px;
        text-align: center;
        margin-top: 3rem;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .contact-info h3 {
        color: #8a2be2;
        margin-bottom: 1rem;
    }

    .contact-info a {
        color: #ba55d3;
        text-decoration: none;
        font-weight: 600;
        transition: color 0.3s ease;
    }

    .contact-info a:hover {
        color: #8a2be2;
        text-decoration: underline;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .cookie-container {
            margin: 30px 15px;
            padding: 25px;
        }

        .cookie-container h1 {
            font-size: 2.2rem;
        }

        .cookie-section h2 {
            font-size: 1.8rem;
        }

        .cookie-navigation {
            flex-direction: column;
            align-items: center;
        }

        .cookie-nav-item {
            width: 100%;
            justify-content: center;
            max-width: 300px;
        }

        .cookie-types {
            grid-template-columns: 1fr;
        }

        .cookie-buttons {
            flex-direction: column;
            align-items: center;
        }

        .cookie-btn {
            width: 100%;
            max-width: 250px;
            justify-content: center;
        }
    }

    @media (max-width: 480px) {
        .cookie-container {
            margin: 20px 10px;
            padding: 20px;
        }

        .cookie-container h1 {
            font-size: 1.8rem;
        }

        .cookie-section h2 {
            font-size: 1.5rem;
        }

        .cookie-section {
            padding: 1.5rem;
        }

        .cookie-controls {
            padding: 1.5rem;
        }
    }
</style>

<div class="cookie-container">
    <div class="cookie-header">
        <div class="cookie-icon">🍪</div>
        <h1>Çerez Politikası</h1>
        <p class="cookie-subtitle">BiletJack olarak çerezleri nasıl kullandığımız ve kişisel verilerinizi nasıl koruduğumuz hakkında detaylı bilgiler</p>
    </div>

    <nav class="cookie-navigation">
        <a href="#genel-bilgiler" class="cookie-nav-item active">
            <i class="fas fa-info-circle"></i>
            Genel Bilgiler
        </a>
        <a href="#cerez-turleri" class="cookie-nav-item">
            <i class="fas fa-list"></i>
            Çerez Türleri
        </a>
        <a href="#kullanim-amaci" class="cookie-nav-item">
            <i class="fas fa-target"></i>
            Kullanım Amacı
        </a>
        <a href="#yonetim" class="cookie-nav-item">
            <i class="fas fa-cog"></i>
            Çerez Yönetimi
        </a>
    </nav>

    <section id="genel-bilgiler" class="cookie-section">
        <h2><i class="fas fa-info-circle"></i> Çerez Nedir?</h2>
        <p>Çerezler, web sitelerinin kullanıcıların cihazlarında (bilgisayar, tablet, telefon) sakladığı küçük metin dosyalarıdır. Bu dosyalar, web sitesinin daha iyi çalışmasını sağlar ve kullanıcı deneyimini geliştirir.</p>
        
        <p>BiletJack olarak, platformumuzun işlevselliğini artırmak, kullanıcı deneyimini kişiselleştirmek ve hizmetlerimizi geliştirmek amacıyla çerezleri kullanmaktayız.</p>
        
        <h3>Çerezlerin Faydaları</h3>
        <ul class="cookie-list">
            <li><strong>Kullanıcı Deneyimi:</strong> Tercihlerinizi hatırlayarak daha kişisel bir deneyim sunar</li>
            <li><strong>Güvenlik:</strong> Hesabınızın güvenliğini sağlar ve yetkisiz erişimi önler</li>
            <li><strong>Performans:</strong> Site hızını artırır ve daha verimli çalışmasını sağlar</li>
            <li><strong>Analitik:</strong> Site kullanımını analiz ederek hizmetlerimizi geliştirir</li>
        </ul>
    </section>

    <section id="cerez-turleri" class="cookie-section">
        <h2><i class="fas fa-list"></i> Kullandığımız Çerez Türleri</h2>
        
        <div class="cookie-types">
            <div class="cookie-type-card">
                <h4>🔒 Zorunlu Çerezler</h4>
                <p>Web sitesinin temel işlevlerini yerine getirmesi için gerekli olan çerezlerdir. Bu çerezler olmadan site düzgün çalışmaz.</p>
                <div class="cookie-purpose">Güvenlik</div>
                <div class="cookie-purpose">Oturum Yönetimi</div>
                <div class="cookie-purpose">Temel İşlevler</div>
            </div>
            
            <div class="cookie-type-card">
                <h4>⚡ Performans Çerezleri</h4>
                <p>Web sitesinin performansını ölçmek ve kullanıcı deneyimini iyileştirmek için kullanılan çerezlerdir.</p>
                <div class="cookie-purpose">Sayfa Yükleme</div>
                <div class="cookie-purpose">Hız Optimizasyonu</div>
                <div class="cookie-purpose">Hata Takibi</div>
            </div>
            
            <div class="cookie-type-card">
                <h4>🎯 İşlevsel Çerezler</h4>
                <p>Kullanıcı tercihlerini hatırlayarak kişiselleştirilmiş bir deneyim sunmak için kullanılan çerezlerdir.</p>
                <div class="cookie-purpose">Dil Tercihi</div>
                <div class="cookie-purpose">Tema Ayarları</div>
                <div class="cookie-purpose">Konum Bilgisi</div>
            </div>
            
            <div class="cookie-type-card">
                <h4>📊 Analitik Çerezler</h4>
                <p>Web sitesi kullanımını analiz etmek ve hizmetlerimizi geliştirmek için kullanılan çerezlerdir.</p>
                <div class="cookie-purpose">Ziyaretçi Analizi</div>
                <div class="cookie-purpose">Davranış Takibi</div>
                <div class="cookie-purpose">İstatistikler</div>
            </div>
        </div>
    </section>

    <section id="kullanim-amaci" class="cookie-section">
        <h2><i class="fas fa-target"></i> Çerezleri Neden Kullanıyoruz?</h2>
        
        <h3>🎫 Bilet İşlemleri</h3>
        <p>Bilet satın alma sürecinde sepetinizi korumak, ödeme bilgilerinizi güvenli tutmak ve işlem geçmişinizi saklamak için çerezleri kullanırız.</p>
        
        <h3>👤 Kullanıcı Hesapları</h3>
        <p>Giriş durumunuzu korumak, hesap ayarlarınızı hatırlamak ve kişiselleştirilmiş içerik sunmak için çerezleri kullanırız.</p>
        
        <h3>📈 Site Geliştirme</h3>
        <p>Hangi sayfaların daha çok ziyaret edildiğini, kullanıcıların nasıl gezindiğini analiz ederek hizmetlerimizi geliştiririz.</p>
        
        <h3>🔐 Güvenlik</h3>
        <p>Hesabınızın güvenliğini sağlamak, şüpheli aktiviteleri tespit etmek ve spam saldırılarını önlemek için çerezleri kullanırız.</p>
    </section>

    <section id="yonetim" class="cookie-section">
        <h2><i class="fas fa-cog"></i> Çerez Yönetimi</h2>
        
        <p>Çerez tercihlerinizi istediğiniz zaman değiştirebilirsiniz. Aşağıdaki seçenekleri kullanarak çerezleri yönetebilirsiniz:</p>
        
        <h3>🌐 Tarayıcı Ayarları</h3>
        <p>Çoğu web tarayıcısı çerezleri otomatik olarak kabul eder, ancak tarayıcı ayarlarınızdan çerezleri devre dışı bırakabilir veya çerez uyarıları alabilirsiniz.</p>
        
        <table class="cookie-table">
            <thead>
                <tr>
                    <th>Tarayıcı</th>
                    <th>Çerez Ayarları</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Google Chrome</td>
                    <td>Ayarlar > Gelişmiş > Gizlilik ve güvenlik > Site ayarları > Çerezler</td>
                </tr>
                <tr>
                    <td>Mozilla Firefox</td>
                    <td>Seçenekler > Gizlilik ve Güvenlik > Çerezler ve Site Verileri</td>
                </tr>
                <tr>
                    <td>Safari</td>
                    <td>Tercihler > Gizlilik > Çerezler ve web sitesi verileri</td>
                </tr>
                <tr>
                    <td>Microsoft Edge</td>
                    <td>Ayarlar > Site izinleri > Çerezler ve site verileri</td>
                </tr>
            </tbody>
        </table>
        
        <h3>⚠️ Önemli Not</h3>
        <p>Çerezleri tamamen devre dışı bırakırsanız, web sitemizin bazı özellikleri düzgün çalışmayabilir. Özellikle bilet satın alma işlemleri ve kullanıcı hesabı işlevleri etkilenebilir.</p>
    </section>

    <div class="cookie-controls">
        <i class="fas fa-cookie-bite" style="font-size: 3rem; margin-bottom: 1rem;"></i>
        <h3>Çerez Tercihlerinizi Yönetin</h3>
        <p>Çerez kullanımımızı kabul ediyor musunuz? Tercihlerinizi aşağıdaki butonlarla belirtebilirsiniz.</p>
        
        <div class="cookie-buttons">
            <button class="cookie-btn accept" onclick="acceptCookies()">
                <i class="fas fa-check"></i>
                Tümünü Kabul Et
            </button>
            <button class="cookie-btn settings" onclick="openCookieSettings()">
                <i class="fas fa-cog"></i>
                Ayarları Özelleştir
            </button>
        </div>
    </div>

    <div class="contact-info">
        <h3>Sorularınız mı var?</h3>
        <p>Çerez politikamız hakkında herhangi bir sorunuz varsa, lütfen <a href="iletisim.php">bizimle iletişime geçin</a>.</p>
        <p><strong>Son güncelleme:</strong> <?php echo date('d.m.Y'); ?></p>
    </div>
</div>

<script>
function acceptCookies() {
    localStorage.setItem('cookiesAccepted', 'true');
    alert('Çerez tercihleri kaydedildi!');
}

function openCookieSettings() {
    alert('Çerez ayarları sayfası yakında eklenecek!');
}

// Smooth scroll for navigation
document.querySelectorAll('.cookie-nav-item').forEach(item => {
    item.addEventListener('click', function(e) {
        e.preventDefault();
        
        // Remove active class from all items
        document.querySelectorAll('.cookie-nav-item').forEach(nav => {
            nav.classList.remove('active');
        });
        
        // Add active class to clicked item
        this.classList.add('active');
        
        // Smooth scroll to target
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Update active navigation on scroll
window.addEventListener('scroll', function() {
    const sections = document.querySelectorAll('.cookie-section');
    const navItems = document.querySelectorAll('.cookie-nav-item');
    
    let current = '';
    sections.forEach(section => {
        const sectionTop = section.offsetTop;
        const sectionHeight = section.clientHeight;
        if (scrollY >= (sectionTop - 200)) {
            current = section.getAttribute('id');
        }
    });
    
    navItems.forEach(item => {
        item.classList.remove('active');
        if (item.getAttribute('href') === '#' + current) {
            item.classList.add('active');
        }
    });
});
</script>

<?php
include 'includes/footer.php';
?>