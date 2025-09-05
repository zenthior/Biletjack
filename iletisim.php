<?php include 'includes/header.php'; ?>

<style>
/* İletişim Sayfası - Siyah Beyaz Tema */
body {
    background: #000;
    color: #fff;
    font-family: 'Arial', sans-serif;
    margin: 0;
    padding: 0;
    line-height: 1.6;
}

.contact-page {
    min-height: 100vh;
    background: linear-gradient(135deg, #000 0%, #1a1a1a 50%, #000 100%);
    padding: 2rem 0;
}

.contact-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
}

.contact-header {
    text-align: center;
    margin-bottom: 4rem;
    padding: 2rem 0;
}

.contact-title {
    font-size: 3.5rem;
    font-weight: 900;
    color: #fff;
    margin-bottom: 1rem;
    text-transform: uppercase;
    letter-spacing: 2px;
    position: relative;
}

.contact-title::after {
    content: '';
    position: absolute;
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
    width: 100px;
    height: 3px;
    background: #fff;
}

.contact-subtitle {
    font-size: 1.2rem;
    color: #ccc;
    max-width: 600px;
    margin: 0 auto;
    line-height: 1.8;
}

.contact-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 4rem;
    margin-bottom: 4rem;
}

.contact-form {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    padding: 3rem;
    backdrop-filter: blur(10px);
}

.form-title {
    font-size: 2rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 2rem;
    text-align: center;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    font-size: 0.9rem;
    font-weight: 600;
    color: #fff;
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.form-input,
.form-textarea {
    width: 100%;
    padding: 1rem;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 10px;
    color: #fff;
    font-size: 1rem;
    transition: all 0.3s ease;
    box-sizing: border-box;
}

.form-input:focus,
.form-textarea:focus {
    outline: none;
    border-color: #fff;
    background: rgba(255, 255, 255, 0.15);
    box-shadow: 0 0 20px rgba(255, 255, 255, 0.1);
}

.form-input::placeholder,
.form-textarea::placeholder {
    color: #999;
}

.form-textarea {
    min-height: 120px;
    resize: vertical;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.submit-btn {
    width: 100%;
    padding: 1.2rem;
    background: #fff;
    color: #000;
    border: none;
    border-radius: 10px;
    font-size: 1.1rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-top: 1rem;
}

.submit-btn:hover {
    background: #f0f0f0;
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(255, 255, 255, 0.2);
}

.contact-info {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.info-card {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    padding: 2rem;
    text-align: center;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.info-card:hover {
    transform: translateY(-5px);
    border-color: rgba(255, 255, 255, 0.3);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
}

.info-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
    display: block;
}

.info-title {
    font-size: 1.3rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 1rem;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.info-text {
    color: #ccc;
    font-size: 1rem;
    line-height: 1.6;
}

.info-text strong {
    color: #fff;
}

.contact-map {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    padding: 3rem;
    text-align: center;
    backdrop-filter: blur(10px);
}

.map-title {
    font-size: 2rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 2rem;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.map-placeholder {
    background: rgba(255, 255, 255, 0.1);
    border: 2px dashed rgba(255, 255, 255, 0.3);
    border-radius: 15px;
    height: 300px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #999;
    font-size: 1.1rem;
    font-weight: 600;
}

/* Responsive Design */
@media (max-width: 768px) {
    .contact-title {
        font-size: 2.5rem;
    }
    
    .contact-subtitle {
        font-size: 1rem;
        padding: 0 1rem;
    }
    
    .contact-content {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    
    .contact-form,
    .info-card,
    .contact-map {
        padding: 2rem;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .contact-container {
        padding: 0 0.5rem;
    }
}

@media (max-width: 480px) {
    .contact-title {
        font-size: 2rem;
    }
    
    .contact-form,
    .info-card,
    .contact-map {
        padding: 1.5rem;
    }
    
    .form-title,
    .map-title {
        font-size: 1.5rem;
    }
    
    .info-icon {
        font-size: 2.5rem;
    }
}
</style>

<main class="contact-page">
    <div class="contact-container">
        <!-- Header Section -->
        <div class="contact-header">
            <h1 class="contact-title">İletişim</h1>
            <p class="contact-subtitle">
                Sorularınız, önerileriniz veya destek talepleriniz için bizimle iletişime geçin. 
                Size yardımcı olmaktan mutluluk duyarız.
            </p>
        </div>

        <!-- Main Content -->
        <div class="contact-content">
            <!-- Contact Form -->
            <div class="contact-form">
                <h2 class="form-title">Bize Yazın</h2>
                <form action="#" method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="name">Ad Soyad *</label>
                            <input type="text" id="name" name="name" class="form-input" placeholder="Adınızı ve soyadınızı girin" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="email">E-posta *</label>
                            <input type="email" id="email" name="email" class="form-input" placeholder="E-posta adresinizi girin" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="phone">Telefon</label>
                            <input type="tel" id="phone" name="phone" class="form-input" placeholder="Telefon numaranızı girin">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="subject">Konu *</label>
                            <input type="text" id="subject" name="subject" class="form-input" placeholder="Mesajınızın konusunu girin" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="message">Mesajınız *</label>
                        <textarea id="message" name="message" class="form-textarea" placeholder="Mesajınızı buraya yazın..." required></textarea>
                    </div>
                    
                    <button type="submit" class="submit-btn">Mesajı Gönder</button>
                </form>
            </div>

            <!-- Contact Info -->
            <div class="contact-info">
                <div class="info-card">
                    <span class="info-icon">📧</span>
                    <h3 class="info-title">E-posta</h3>
                    <div class="info-text">
                        <strong>destek@biletjack.com</strong>
                    </div>
                </div>
                
                <div class="info-card">
                    <span class="info-icon">📞</span>
                    <h3 class="info-title">Telefon</h3>
                    <div class="info-text">
                        <strong>+90 545 613 42 61</strong><br>
                    </div>
                </div>
                
                <div class="info-card">
                    <span class="info-icon">📍</span>
                    <h3 class="info-title">Adres</h3>
                    <div class="info-text">
                        Söğütlü Mahallesi<br>
                        Ortaalan Caddesi<br>
                        Kardelen Sitesi C Blok
                    </div>
                </div>
                

            </div>
        </div>

        <!-- Map Section -->
        <div class="contact-map">
            <h2 class="map-title">Konum</h2>
            <div class="map-placeholder">
                <iframe 
                    src="https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d895.0836065987825!2d39.602183635847965!3d41.01014946141538!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e0!3m2!1str!2str!4v1756999386997!5m2!1str!2str" 
                    width="100%" 
                    height="300" 
                    style="border:0;border-radius:10px;" 
                    allowfullscreen="" 
                    loading="lazy" 
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>