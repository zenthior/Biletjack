<?php
include 'includes/header.php';
?>

<link rel="stylesheet" href="css/pages2.css">

<style>
    .kvkk-container {
        max-width: 1100px;
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

    .kvkk-header {
        text-align: center;
        margin-bottom: 3rem;
        padding-bottom: 2rem;
        border-bottom: 2px solid rgba(138, 43, 226, 0.3);
    }

    .kvkk-container h1 {
        font-size: 3.2rem;
        background: linear-gradient(135deg, #8a2be2 0%, #9370db 50%, #ba55d3 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        text-align: center;
        margin-bottom: 1rem;
        text-shadow: 0 4px 8px rgba(138, 43, 226, 0.3);
        font-weight: 700;
    }

    .kvkk-icon {
        font-size: 4rem;
        color: #8a2be2;
        margin-bottom: 1rem;
        text-shadow: 0 0 20px rgba(138, 43, 226, 0.5);
    }

    .kvkk-subtitle {
        font-size: 1.3rem;
        color: rgba(255, 255, 255, 0.8);
        margin-bottom: 2rem;
        max-width: 700px;
        margin-left: auto;
        margin-right: auto;
        line-height: 1.6;
    }

    .kvkk-navigation {
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

    .kvkk-nav-item {
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

    .kvkk-nav-item:hover {
        background: rgba(138, 43, 226, 0.3);
        border-color: rgba(138, 43, 226, 0.5);
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(138, 43, 226, 0.3);
    }

    .kvkk-nav-item.active {
        background: linear-gradient(135deg, #8a2be2 0%, #9370db 100%);
        border-color: #8a2be2;
        box-shadow: 0 8px 20px rgba(138, 43, 226, 0.4);
    }

    .kvkk-section {
        background: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(10px);
        border-radius: 15px;
        padding: 2.5rem;
        margin-bottom: 2rem;
        border: 1px solid rgba(255, 255, 255, 0.1);
        transition: all 0.3s ease;
    }

    .kvkk-section:hover {
        background: rgba(255, 255, 255, 0.08);
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    }

    .kvkk-section h2 {
        font-size: 2.2rem;
        color: #9370db;
        margin-bottom: 1.5rem;
        border-bottom: 2px solid rgba(147, 112, 219, 0.3);
        padding-bottom: 0.8rem;
        display: flex;
        align-items: center;
        gap: 0.8rem;
    }

    .kvkk-section h3 {
        font-size: 1.5rem;
        color: #ba55d3;
        margin: 2rem 0 1rem 0;
        font-weight: 600;
    }

    .kvkk-section p {
        margin-bottom: 1.5rem;
        font-size: 1.1rem;
        color: #e2e8f0;
    }

    .rights-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
        margin: 2rem 0;
    }

    .right-card {
        background: rgba(255, 255, 255, 0.08);
        border-radius: 12px;
        padding: 2rem;
        border-left: 4px solid #8a2be2;
        transition: all 0.3s ease;
    }

    .right-card:hover {
        transform: translateY(-5px);
        background: rgba(255, 255, 255, 0.12);
        box-shadow: 0 10px 25px rgba(138, 43, 226, 0.2);
    }

    .right-card h4 {
        color: #8a2be2;
        font-size: 1.3rem;
        margin-bottom: 1rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .right-card p {
        color: #d1d5db;
        font-size: 1rem;
        margin-bottom: 1rem;
    }

    .data-types {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
        margin: 2rem 0;
    }

    .data-type {
        background: rgba(138, 43, 226, 0.1);
        padding: 1rem 1.5rem;
        border-radius: 25px;
        font-size: 0.9rem;
        color: #ba55d3;
        text-align: center;
        border: 1px solid rgba(138, 43, 226, 0.3);
        transition: all 0.3s ease;
    }

    .data-type:hover {
        background: rgba(138, 43, 226, 0.2);
        transform: translateY(-2px);
    }

    .kvkk-table {
        width: 100%;
        border-collapse: collapse;
        margin: 2rem 0;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 12px;
        overflow: hidden;
    }

    .kvkk-table th,
    .kvkk-table td {
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .kvkk-table th {
        background: rgba(138, 43, 226, 0.2);
        color: #ba55d3;
        font-weight: 600;
    }

    .kvkk-table td {
        color: #e2e8f0;
    }

    .contact-section {
        background: linear-gradient(135deg, #8a2be2 0%, #9370db 100%);
        padding: 2.5rem;
        border-radius: 20px;
        text-align: center;
        margin: 3rem 0;
        box-shadow: 0 15px 35px rgba(138, 43, 226, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .contact-section h3 {
        color: white;
        font-size: 1.8rem;
        margin-bottom: 1rem;
        font-weight: 600;
    }

    .contact-section p {
        color: rgba(255, 255, 255, 0.9);
        margin-bottom: 2rem;
        font-size: 1.1rem;
    }

    .contact-buttons {
        display: flex;
        gap: 1rem;
        justify-content: center;
        flex-wrap: wrap;
    }

    .contact-btn {
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

    .contact-btn.primary {
        background: white;
        color: #8a2be2;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }

    .contact-btn.primary:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        background: #f8f9fa;
    }

    .contact-btn.secondary {
        background: rgba(255, 255, 255, 0.2);
        color: white;
        border: 2px solid rgba(255, 255, 255, 0.3);
    }

    .contact-btn.secondary:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: translateY(-3px);
    }

    .kvkk-list {
        list-style: none;
        padding: 0;
        margin: 2rem 0;
    }

    .kvkk-list li {
        background: rgba(255, 255, 255, 0.05);
        margin-bottom: 1rem;
        padding: 1.5rem;
        border-radius: 10px;
        border-left: 4px solid #8a2be2;
        transition: all 0.3s ease;
        position: relative;
    }

    .kvkk-list li:hover {
        transform: translateX(5px);
        background: rgba(255, 255, 255, 0.08);
    }

    .kvkk-list li:before {
        content: '✓';
        position: absolute;
        left: -15px;
        top: 50%;
        transform: translateY(-50%);
        background: #8a2be2;
        color: white;
        width: 25px;
        height: 25px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        font-weight: bold;
    }

    .kvkk-list li strong {
        color: #ba55d3;
        display: block;
        margin-bottom: 0.5rem;
    }

    .highlight-box {
        background: rgba(138, 43, 226, 0.1);
        border: 1px solid rgba(138, 43, 226, 0.3);
        border-radius: 12px;
        padding: 1.5rem;
        margin: 2rem 0;
        border-left: 4px solid #8a2be2;
    }

    .highlight-box h4 {
        color: #8a2be2;
        margin-bottom: 1rem;
        font-size: 1.2rem;
    }

    .highlight-box p {
        color: #e2e8f0;
        margin-bottom: 0;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .kvkk-container {
            margin: 30px 15px;
            padding: 25px;
        }

        .kvkk-container h1 {
            font-size: 2.5rem;
        }

        .kvkk-section h2 {
            font-size: 1.8rem;
        }

        .kvkk-navigation {
            flex-direction: column;
            align-items: center;
        }

        .kvkk-nav-item {
            width: 100%;
            justify-content: center;
            max-width: 300px;
        }

        .rights-grid {
            grid-template-columns: 1fr;
        }

        .contact-buttons {
            flex-direction: column;
            align-items: center;
        }

        .contact-btn {
            width: 100%;
            max-width: 250px;
            justify-content: center;
        }
    }

    @media (max-width: 480px) {
        .kvkk-container {
            margin: 20px 10px;
            padding: 20px;
        }

        .kvkk-container h1 {
            font-size: 2rem;
        }

        .kvkk-section h2 {
            font-size: 1.5rem;
        }

        .kvkk-section {
            padding: 1.5rem;
        }

        .contact-section {
            padding: 1.5rem;
        }
    }
</style>

<div class="kvkk-container">
    <div class="kvkk-header">
        <div class="kvkk-icon">🛡️</div>
        <h1>KVKK Aydınlatma Metni</h1>
        <p class="kvkk-subtitle">Kişisel Verilerin Korunması Kanunu kapsamında kişisel verilerinizin işlenmesi hakkında detaylı bilgilendirme</p>
    </div>

    <nav class="kvkk-navigation">
        <a href="#veri-sorumlusu" class="kvkk-nav-item active">
            <i class="fas fa-building"></i>
            Veri Sorumlusu
        </a>
        <a href="#kisisel-veriler" class="kvkk-nav-item">
            <i class="fas fa-database"></i>
            Kişisel Veriler
        </a>
        <a href="#isleme-amaci" class="kvkk-nav-item">
            <i class="fas fa-target"></i>
            İşleme Amacı
        </a>
        <a href="#haklariniz" class="kvkk-nav-item">
            <i class="fas fa-user-shield"></i>
            Haklarınız
        </a>
        <a href="#iletisim" class="kvkk-nav-item">
            <i class="fas fa-envelope"></i>
            İletişim
        </a>
    </nav>

    <section id="veri-sorumlusu" class="kvkk-section">
        <h2><i class="fas fa-building"></i> Veri Sorumlusu Kimliği</h2>
        <p>6698 sayılı Kişisel Verilerin Korunması Kanunu ("KVKK") uyarınca, kişisel verileriniz; veri sorumlusu olarak <strong>BiletJack</strong> tarafından aşağıda açıklanan kapsamda işlenebilecektir.</p>
        
        <div class="highlight-box">
            <h4>Şirket Bilgileri</h4>
            <p><strong>Ünvan:</strong> BiletJack<br>
            <strong>Adres:</strong> Söğütlü mahallesi ortaalan caddesi kardelen sitesi c blok, Akçaabat/Trabzon<br>
            <strong>E-posta:</strong> destek@biletjack.com<br>
            <strong>Telefon:</strong> +90 545 613 42 61</p>
        </div>
    </section>

    <section id="kisisel-veriler" class="kvkk-section">
        <h2><i class="fas fa-database"></i> İşlenen Kişisel Veri Kategorileri</h2>
        <p>BiletJack platformunu kullanırken aşağıdaki kişisel veri kategorileri işlenmektedir:</p>
        
        <div class="data-types">
            <div class="data-type">👤 Kimlik Bilgileri</div>
            <div class="data-type">📞 İletişim Bilgileri</div>
            <div class="data-type">💳 Finansal Bilgiler</div>
            <div class="data-type">🌐 Dijital İz Bilgileri</div>
            <div class="data-type">📍 Konum Bilgileri</div>
            <div class="data-type">🎯 Pazarlama Bilgileri</div>
        </div>

        <h3>Detaylı Veri Kategorileri</h3>
        <table class="kvkk-table">
            <thead>
                <tr>
                    <th>Veri Kategorisi</th>
                    <th>Veri Türleri</th>
                    <th>İşleme Amacı</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Kimlik Bilgileri</td>
                    <td>Ad, soyad, T.C. kimlik numarası, doğum tarihi</td>
                    <td>Kullanıcı kaydı, kimlik doğrulama</td>
                </tr>
                <tr>
                    <td>İletişim Bilgileri</td>
                    <td>E-posta, telefon, adres bilgileri</td>
                    <td>İletişim, bilgilendirme, teslimat</td>
                </tr>
                <tr>
                    <td>Finansal Bilgiler</td>
                    <td>Kredi kartı bilgileri, ödeme geçmişi</td>
                    <td>Ödeme işlemleri, fatura kesimi</td>
                </tr>
                <tr>
                    <td>Dijital İz</td>
                    <td>IP adresi, çerez bilgileri, tarayıcı bilgisi</td>
                    <td>Güvenlik, analiz, kişiselleştirme</td>
                </tr>
            </tbody>
        </table>
    </section>

    <section id="isleme-amaci" class="kvkk-section">
        <h2><i class="fas fa-target"></i> Kişisel Verilerin İşlenme Amaçları</h2>
        <p>Kişisel verileriniz aşağıdaki amaçlarla işlenmektedir:</p>
        
        <ul class="kvkk-list">
            <li><strong>Hizmet Sunumu:</strong> Bilet satış ve rezervasyon hizmetlerinin sunulması</li>
            <li><strong>Müşteri İlişkileri:</strong> Müşteri destek hizmetlerinin sağlanması</li>
            <li><strong>Güvenlik:</strong> Platform güvenliğinin sağlanması ve dolandırıcılığın önlenmesi</li>
            <li><strong>Yasal Yükümlülükler:</strong> Kanuni yükümlülüklerin yerine getirilmesi</li>
            <li><strong>Pazarlama:</strong> Ürün ve hizmetlerin tanıtımı (onay dahilinde)</li>
            <li><strong>Analiz:</strong> Hizmet kalitesinin artırılması için analiz çalışmaları</li>
            <li><strong>İletişim:</strong> Önemli duyuru ve bilgilendirmelerin yapılması</li>
            <li><strong>Kişiselleştirme:</strong> Kullanıcı deneyiminin kişiselleştirilmesi</li>
        </ul>

        <h3>🔒 Veri İşleme Hukuki Dayanakları</h3>
        <p>Kişisel verileriniz KVKK'nın 5. maddesinde belirtilen aşağıdaki hukuki dayanaklara göre işlenmektedir:</p>
        
        <div class="highlight-box">
            <h4>📋 Hukuki Dayanaklar</h4>
            <p>• Açık rızanızın bulunması<br>
            • Sözleşmenin kurulması veya ifası için gerekli olması<br>
            • Kanuni yükümlülüklerin yerine getirilmesi<br>
            • Meşru menfaatlerimizin bulunması</p>
        </div>
    </section>

    <section id="haklariniz" class="kvkk-section">
        <h2><i class="fas fa-user-shield"></i> KVKK Kapsamındaki Haklarınız</h2>
        <p>KVKK'nın 11. maddesi uyarınca sahip olduğunuz haklar:</p>
        
        <div class="rights-grid">
            <div class="right-card">
                <h4><i class="fas fa-info-circle"></i> Bilgi Alma Hakkı</h4>
                <p>Kişisel verilerinizin işlenip işlenmediğini öğrenme ve işleniyorsa buna ilişkin bilgi talep etme hakkınız bulunmaktadır.</p>
            </div>
            
            <div class="right-card">
                <h4><i class="fas fa-eye"></i> Erişim Hakkı</h4>
                <p>İşlenen kişisel verilerinize erişim talep etme ve bu verilerin bir kopyasını alma hakkınız vardır.</p>
            </div>
            
            <div class="right-card">
                <h4><i class="fas fa-edit"></i> Düzeltme Hakkı</h4>
                <p>Eksik veya yanlış işlenen kişisel verilerinizin düzeltilmesini talep etme hakkınız bulunmaktadır.</p>
            </div>
            
            <div class="right-card">
                <h4><i class="fas fa-trash"></i> Silme Hakkı</h4>
                <p>Belirli şartların oluşması halinde kişisel verilerinizin silinmesini talep etme hakkınız vardır.</p>
            </div>
            
            <div class="right-card">
                <h4><i class="fas fa-ban"></i> İşlemeyi Durdurma</h4>
                <p>Kişisel verilerinizin işlenmesine itiraz etme ve işlemenin durdurulmasını talep etme hakkınız bulunmaktadır.</p>
            </div>
            
            <div class="right-card">
                <h4><i class="fas fa-share"></i> Aktarım Hakkı</h4>
                <p>Kişisel verilerinizin başka bir veri sorumlusuna aktarılmasını talep etme hakkınız vardır.</p>
            </div>
        </div>

        <h3>⚖️ Hak Kullanımı Süreci</h3>
        <p>Yukarıda belirtilen haklarınızı kullanmak için:</p>
        
        <ul class="kvkk-list">
            <li><strong>Başvuru Yöntemi:</strong> Yazılı olarak veya kayıtlı elektronik posta ile başvurabilirsiniz</li>
            <li><strong>Kimlik Doğrulama:</strong> Başvurunuzda kimliğinizi doğrulayacak bilgileri eksiksiz belirtmelisiniz</li>
            <li><strong>Yanıt Süresi:</strong> Başvurunuz en geç 30 gün içinde sonuçlandırılacaktır</li>
            <li><strong>Ücret:</strong> Başvuru ücretsizdir, ancak maliyet gerektiren durumlar için ücret talep edilebilir</li>
        </ul>
    </section>

    

    <div class="highlight-box">
        <h4>📋 Önemli Notlar</h4>
        <p>Bu aydınlatma metni, KVKK'nın 10. maddesi uyarınca hazırlanmıştır. Kişisel veri işleme faaliyetlerimizde değişiklik olması durumunda bu metin güncellenecek ve size bildirilecektir. Güncel versiyona her zaman web sitemizden ulaşabilirsiniz.</p>
    </div>
</div>

<script>
// Smooth scroll for navigation
document.querySelectorAll('.kvkk-nav-item').forEach(item => {
    item.addEventListener('click', function(e) {
        e.preventDefault();
        
        // Remove active class from all items
        document.querySelectorAll('.kvkk-nav-item').forEach(nav => {
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
    const sections = document.querySelectorAll('.kvkk-section, .contact-section');
    const navItems = document.querySelectorAll('.kvkk-nav-item');
    
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

// Add animation on scroll
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver(function(entries) {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, observerOptions);

// Observe all sections
document.querySelectorAll('.kvkk-section, .contact-section').forEach(section => {
    section.style.opacity = '0';
    section.style.transform = 'translateY(20px)';
    section.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
    observer.observe(section);
});
</script>

<?php
include 'includes/footer.php';
?>