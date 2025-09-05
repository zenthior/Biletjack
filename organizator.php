<?php include 'includes/header.php'; ?>

<main>

<!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2>Jack+</h2>
                <p>Etkinlik organizasyonunda yeni bir dönem başlatın</p>
                <button class="cta-btn" onclick="openOrganizerModal()">Organizatör Ol</button>
            </div>
        </div>
    </section>
    

    <!-- Features Section -->
    <section class="organizer-features">
        <div class="container">
            <div class="section-header">
                <h2>Neden BiletJack?</h2>
                <p>Etkinlik organizasyonunda ihtiyacınız olan her şey</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                        </svg>
                    </div>
                    <h3>Kolay Etkinlik Yönetimi</h3>
                    <p>Sezgisel arayüz ile etkinliklerinizi kolayca oluşturun, düzenleyin ve yönetin.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/>
                        </svg>
                    </div>
                    <h3>Güvenli Ödeme Sistemi</h3>
                    <p>SSL sertifikalı güvenli ödeme altyapısı ile müşterilerinizin güvenliğini sağlayın.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M16 6l2.29 2.29-4.88 4.88-4-4L2 16.59 3.41 18l6-6 4 4 6.3-6.29L22 12V6z"/>
                        </svg>
                    </div>
                    <h3>Detaylı Analitik</h3>
                    <p>Satış raporları, müşteri analizleri ve performans metrikleri ile işinizi büyütün.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                        </svg>
                    </div>
                    <h3>Pazarlama Desteği</h3>
                    <p>E-posta kampanyaları, sosyal medya entegrasyonu ve promosyon araçları.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                    </div>
                    <h3>7/24 Destek</h3>
                    <p>Uzman ekibimiz her zaman yanınızda. Teknik destek ve danışmanlık hizmeti.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.94-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
                        </svg>
                    </div>
                    <h3>Mobil Uyumlu</h3>
                    <p>Mobil cihazlardan etkinlik yönetimi ve satış takibi yapabilirsiniz.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Organizatör Kayıt Modal -->
    <div id="organizerModal" class="modal">
        <div class="modal-overlay" onclick="closeModal('organizerModal')"></div>
        <div class="modal-content organizer-modal">
            <div class="modal-header">
                <h2>Organizatör Kayıt Formu</h2>
                <button class="modal-close" onclick="closeModal('organizerModal')">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <form class="organizer-form" id="organizerForm" method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="org_name">Organizasyon Adı *</label>
                            <input type="text" id="org_name" name="org_name" required>
                        </div>
                        <div class="form-group">
                            <label for="contact_person">İletişim Kişisi *</label>
                            <input type="text" id="contact_person" name="contact_person" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="org_email">E-posta *</label>
                            <input type="email" id="org_email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="org_phone">Telefon *</label>
                            <input type="tel" id="org_phone" name="phone" required>
                        </div>
                    </div>

                    <!-- Şifre alanları eklendi -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="org_password">Şifre *</label>
                            <input type="password" id="org_password" name="password" required class="pw-meter" data-require-strength="medium" data-confirm="#org_confirm_password">
                            <div class="pw-strength" style="margin-top:6px;">
                                <div class="pw-bar" style="height:6px;width:0%;background:#ddd;border-radius:4px;transition:width .2s ease;"></div>
                                <div class="pw-text" style="margin-top:6px;font-size:12px;color:#666;">Şifre gücü: - (En az orta seviye gerekir. 8+ karakter, en az iki tür: küçük/büyük/rakam)</div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="org_confirm_password">Şifre (Tekrar) *</label>
                            <input type="password" id="org_confirm_password" name="confirm_password" required>
                        </div>
                    </div>
                    <!-- Şifre alanları sonu -->

                    <div class="form-group">
                        <label for="org_address">Adres</label>
                        <textarea id="org_address" name="address" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="org_description">Organizasyon Hakkında</label>
                        <textarea id="org_description" name="description" rows="4" placeholder="Organizasyonunuz hakkında kısa bilgi..."></textarea>
                    </div>
                    <div class="form-options">
                        <label class="checkbox-label">
                            <input type="checkbox" name="org_terms" required>
                            <span class="checkmark"></span>
                            <a href="#" class="terms-link">Organizatör sözleşmesini kabul ediyorum</a>
                        </label>
                    </div>
                    <button type="submit" class="modal-btn primary">Başvuru Gönder</button>
                </form>
            </div>
        </div>
    </div>
</main>

<style>
/* Organizatör Sayfası Stilleri */
.organizer-hero {
    background: linear-gradient(135deg,rgb(89, 89, 92) 0%,rgb(11, 9, 12) 100%);
    padding: 8rem 0 6rem;
    color: white;
    text-align: center;
}

.hero-text h1 {
    font-size: 3.5rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
    line-height: 1.2;
}

.hero-subtitle {
    font-size: 1.3rem;
    margin-bottom: 3rem;
    opacity: 0.9;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

.hero-stats {
    display: flex;
    justify-content: center;
    gap: 4rem;
    margin-top: 3rem;
}

.stat-item {
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.stat-label {
    font-size: 1rem;
    opacity: 0.8;
}

.organizer-features {
    padding: 6rem 0;
    background: #f8f9fa;
}

.section-header {
    text-align: center;
    margin-bottom: 4rem;
}

.section-header h2 {
    font-size: 2.5rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: #333;
}

.section-header p {
    font-size: 1.2rem;
    color: #666;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 2rem;
}

.feature-card {
    background: white;
    padding: 2.5rem;
    border-radius: 15px;
    text-align: center;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.feature-card:hover {
    transform: translateY(-5px);
}

.feature-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg,rgb(46, 45, 45) 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
    color: white;
}

.feature-card h3 {
    font-size: 1.3rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: #333;
}

.feature-card p {
    color: #666;
    line-height: 1.6;
}

.how-it-works {
    padding: 6rem 0;
}

.steps-container {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 2rem;
    max-width: 1000px;
    margin: 0 auto;
}

.step-item {
    text-align: center;
    flex: 1;
}

.step-number {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0 auto 1.5rem;
}

.step-content h3 {
    font-size: 1.3rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: #333;
}

.step-content p {
    color: #666;
    line-height: 1.6;
}

.step-arrow {
    font-size: 2rem;
    color: #667eea;
    font-weight: bold;
}







.cta-section {
    padding: 6rem 0;
    background: linear-gradient(135deg,rgba(49, 49, 241, 0.58) 0%,rgb(39, 32, 46) 100%);
    color: white;
    text-align: center;
}

.cta-content h2 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.cta-content p {
    font-size: 1.2rem;
    margin-bottom: 2rem;
    opacity: 0.9;
}

.cta-btn {
    padding: 1.2rem 3rem;
    background: white;
    color: #667eea;
    border: none;
    border-radius: 50px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.cta-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

/* Organizatör Modal Stilleri */
.organizer-modal {
    max-width: 600px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.form-group select {
    width: 100%;
    padding: 0.8rem 1rem;
    border: 2px solid #e1e5e9;
    border-radius: 8px;
    font-size: 1rem;
    background: white;
    transition: all 0.2s ease;
}

.form-group textarea {
    width: 100%;
    padding: 0.8rem 1rem;
    border: 2px solid #e1e5e9;
    border-radius: 8px;
    font-size: 1rem;
    resize: vertical;
    min-height: 80px;
    transition: all 0.2s ease;
}

.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

/* Responsive */
@media (max-width: 768px) {
    .hero-text h1 {
        font-size: 2.5rem;
    }
    
    .hero-stats {
        flex-direction: column;
        gap: 2rem;
    }
    
    .steps-container {
        flex-direction: column;
    }
    
    .step-arrow {
        transform: rotate(90deg);
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .features-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function openOrganizerModal() {
    openModal('organizerModal');
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

// ESC tuşu ile modal kapatma
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal('organizerModal');
    }
});

// Form submit işlemi
document.getElementById('organizerForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    
    // Loading durumu
    submitBtn.textContent = 'Gönderiliyor...';
    submitBtn.disabled = true;
    
    fetch('organizer-register.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Başvurunuz başarıyla alındı! Onay sürecinden sonra size bilgi verilecektir.');
            closeModal('organizerModal');
            this.reset();
        } else {
            alert('Hata: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Bir hata oluştu. Lütfen tekrar deneyin.');
    })
    .finally(() => {
        // Loading durumunu kaldır
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
});

// Şifre gücü barını başlat (global fonksiyon varsa)
if (window.BJ_InitPwMeters) {
    BJ_InitPwMeters(document);
}
</script>

<?php include 'includes/footer.php'; ?>