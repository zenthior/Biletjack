<?php include 'includes/header.php'; ?>
<link rel="stylesheet" href="css/pages.css">

<main>
    <div class="page-container">
        <div class="page-header">
            <h1 class="page-title">İletişim</h1>
            <p class="page-subtitle">Sorularınız, önerileriniz veya destek talepleriniz için bizimle iletişime geçin. Size yardımcı olmaktan mutluluk duyarız.</p>
        </div>

        <div class="contact-form">
            <h2>Bize Yazın</h2>
            <form action="#" method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Ad Soyad *</label>
                        <input type="text" id="name" name="name" placeholder="Adınızı ve soyadınızı girin" required>
                    </div>
                    <div class="form-group">
                        <label for="email">E-posta *</label>
                        <input type="email" id="email" name="email" placeholder="E-posta adresinizi girin" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Telefon</label>
                        <input type="tel" id="phone" name="phone" placeholder="Telefon numaranızı girin">
                    </div>
                    <div class="form-group">
                        <label for="subject">Konu *</label>
                        <input type="text" id="subject" name="subject" placeholder="Mesajınızın konusunu girin" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="message">Mesajınız *</label>
                    <textarea id="message" name="message" placeholder="Mesajınızı buraya yazın..." required></textarea>
                </div>
                <button type="submit" class="submit-btn">Mesajı Gönder</button>
            </form>
        </div>

        <div class="contact-info">
            <div class="contact-item">
                <span class="contact-icon">📧</span>
                <h3>E-posta</h3>
                <p>info@biletjack.com</p>
                <p>destek@biletjack.com</p>
            </div>
            <div class="contact-item">
                <span class="contact-icon">📞</span>
                <h3>Telefon</h3>
                <p>+90 212 555 0123</p>
                <p>Pazartesi - Cuma: 09:00 - 18:00</p>
            </div>
            <div class="contact-item">
                <span class="contact-icon">📍</span>
                <h3>Adres</h3>
                <p>Maslak Mahallesi<br>Büyükdere Caddesi No: 123<br>34485 Sarıyer/İstanbul</p>
            </div>
            <div class="contact-item">
                <span class="contact-icon">⏰</span>
                <h3>Çalışma Saatleri</h3>
                <p>Pazartesi - Cuma: 09:00 - 18:00</p>
                <p>Cumartesi: 10:00 - 16:00</p>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>