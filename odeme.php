<?php 
require_once 'includes/session.php';

// Giriş kontrolü
if (!isLoggedIn()) {
    $redirect = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/index.php';
    header('Location: auth/login-form.php?redirect=' . urlencode($redirect));
    exit();
}

// Hata mesajını kontrol et
$errorMessage = '';
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'order_creation_failed':
            $errorMessage = 'Sipariş oluşturulurken bir hata oluştu. Lütfen tekrar deneyin.';
            if (isset($_GET['debug'])) {
                $errorMessage .= ' (Hata: ' . htmlspecialchars($_GET['debug']) . ')';
            }
            break;
        case 'cart_empty':
            $errorMessage = 'Sepetiniz boş. Lütfen önce bilet seçin.';
            break;
        case 'invalid_cart':
            $errorMessage = 'Sepet verileri geçersiz. Lütfen sayfayı yenileyin.';
            break;
        case 'invalid_amount':
            $errorMessage = 'Geçersiz tutar. Lütfen tekrar deneyin.';
            break;
        case 'invalid_ticket_type':
            $errorMessage = 'Bilet türü bulunamadı. Lütfen sayfayı yenileyin.';
            break;
        case 'paytr_token_error':
            $errorMessage = 'Ödeme servisinde geçici bir sorun oluştu. Lütfen birkaç dakika sonra tekrar deneyin.';
            break;
        default:
            $errorMessage = 'Bir hata oluştu. Lütfen tekrar deneyin.';
    }
}

include 'includes/header.php'; 
?>
<link rel="stylesheet" href="css/customer.css">

<main class="payment-page">
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Ödeme</h1>
            <p class="page-subtitle">Güvenli ödeme ile biletlerinizi satın alın</p>
        </div>
        
        <?php if (!empty($errorMessage)): ?>
        <div class="error-alert" id="errorAlert">
            <div class="error-content">
                <i class="fas fa-exclamation-triangle"></i>
                <span><?php echo htmlspecialchars($errorMessage); ?></span>
                <button type="button" class="error-close" onclick="closeErrorAlert()">&times;</button>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="payment-content">
            <!-- Sol Taraf - Ödeme Formu -->
            <div class="payment-form-section">
                <form id="paymentForm" class="payment-form">
                    <!-- Kişisel Bilgiler -->
                    <div class="form-section">
                        <h3 class="section-title">Ödeme Bilgileri</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="firstName">Ad *</label>
                                <input type="text" id="firstName" name="firstName" required>
                            </div>
                            <div class="form-group">
                                <label for="lastName">Soyad *</label>
                                <input type="text" id="lastName" name="lastName" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">E-posta *</label>
                                <input type="email" id="email" name="email" required>
                            </div>
                            <div class="form-group">
                                <label for="phone">Telefon *</label>
                                <input type="tel" id="phone" name="phone" required>
                            </div>
                        </div>
                    </div>
                    
                    <!-- İndirim Kodu Bölümü - Sadece Mobil -->
                    <div class="mobile-discount-section">
                        <div class="form-section">
                            <h4 class="section-title">İndirim Kodu</h4>
                            <div class="discount-input-group">
                                <input type="text" id="mobileDiscountCode" placeholder="İndirim kodunuz varsa giriniz" maxlength="20">
                                <button type="button" id="mobileApplyDiscountBtn" class="btn-apply-discount">Uygula</button>
                            </div>
                            <div id="mobileDiscountMessage" class="discount-message"></div>
                        </div>
                    </div>

                    <!-- Sözleşme Onayı -->
                    <div class="form-section">
                        <div class="checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="termsAccept" required>
                                <span class="checkmark"></span>
                                <a href="#" class="terms-link">Kullanım Koşulları</a> ve <a href="#" class="terms-link">Gizlilik Politikası</a>'nı okudum ve kabul ediyorum *
                            </label>
                        </div>

                    </div>
                    
                    <button type="submit" class="btn-payment">Siparişi Tamamla</button>
                </form>
            </div>
            
            <!-- Sağ Taraf - Sipariş Özeti -->
            <div class="order-summary-section">
                <div class="order-summary">
                    <h3>Sipariş Özeti</h3>
                    <div id="orderItems" class="order-items">
                        <!-- Sipariş öğeleri JavaScript ile doldurulacak -->
                    </div>
                    
                    <!-- İndirim Kodu Bölümü -->
                    <div class="discount-section">
                        <div class="discount-input-group">
                            <input type="text" id="discountCode" placeholder="İndirim kodunuz varsa giriniz" maxlength="20">
                            <button type="button" id="applyDiscountBtn" class="btn-apply-discount">Uygula</button>
                        </div>
                        <div id="discountMessage" class="discount-message"></div>
                    </div>
                    
                    <div class="summary-totals">
                        <div class="summary-row">
                            <span>Ara Toplam:</span>
                            <span id="subtotal">₺0</span>
                        </div>
                        <div class="summary-row" id="discountRow" style="display: none;">
                            <span>İndirim:</span>
                            <span id="discountAmount" class="discount-text">-₺0</span>
                        </div>
                        <div class="summary-row total">
                            <span>Toplam:</span>
                            <span id="grandTotal">₺0</span>
                        </div>
                    </div>
                    
                    <div class="security-info">
                        <div class="security-badge">
                            🔒 SSL ile Güvenli Ödeme
                        </div>
                        <p>Kart bilgileriniz 256-bit SSL şifreleme ile korunmaktadır.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
body {
    margin-left: 0 !important;
    padding: 0 !important;
    background: linear-gradient(135deg, #000000 0%, #000000 100%) !important;
    min-height: 100vh;
}

.error-alert {
    max-width: 1200px;
    margin: 0 auto 2rem auto;
    padding: 0 1rem;
}

.error-content {
    background: #f8d7da;
    color: #721c24;
    padding: 1rem 1.5rem;
    border-radius: 8px;
    border: 1px solid #f5c6cb;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    position: relative;
    animation: slideDown 0.3s ease;
}

.error-content i {
    font-size: 1.2rem;
    color: #dc3545;
}

.error-close {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    font-size: 1.5rem;
    color: #721c24;
    cursor: pointer;
    padding: 0;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: background-color 0.2s;
}

.error-close:hover {
    background-color: rgba(114, 28, 36, 0.1);
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.payment-page {
    min-height: 100vh;
    padding: 2rem 1rem;
    background: #ffffffc7;
}

.page-header {
    text-align: center;
    margin-bottom: 3rem;
}

.page-title {
    font-size: 2.5rem;
    color: #000000;
    margin-bottom: 1rem;
    font-weight: 700;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
}

.page-subtitle {
    font-size: 1.1rem;
    color: rgb(30 30 30 / 69%);
    max-width: 600px;
    margin: 0 auto;
    text-shadow: 0 1px 5px rgba(0, 0, 0, 0.2);
}

.payment-content {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 3rem;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
}

.payment-form-section {
    background: rgba(255, 255, 255, 0.95);
    border-radius: 20px;
    padding: 2.5rem;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.form-section {
    margin-bottom: -1rem;
    padding-bottom: 2rem;
    border-bottom: 1px solid rgba(102, 126, 234, 0.1);
}

.form-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.section-title {
    color: #1a1a1a;
    font-size: 1.3rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
    position: relative;
    padding-bottom: 0.5rem;
}

.section-title::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 50px;
    height: 3px;
    background: linear-gradient(90deg, #667eea, #764ba2);
    border-radius: 2px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    color: #1a1a1a;
    font-weight: 500;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 14px 18px;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    font-size: 15px;
    transition: all 0.3s ease;
    background: rgba(255, 255, 255, 0.8);
    font-weight: 500;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.15);
    background: rgba(255, 255, 255, 1);
    transform: translateY(-1px);
}

.checkbox-group {
    margin-bottom: 1rem;
}

.checkbox-label {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    cursor: pointer;
    line-height: 1.5;
    color: #1a1a1a;
}

.checkbox-label input[type="checkbox"] {
    display: none;
}

.checkmark {
    width: 18px;
    height: 18px;
    border: 2px solid #ddd;
    border-radius: 4px;
    position: relative;
    flex-shrink: 0;
    margin-top: 2px;
    transition: all 0.3s;
}

.checkbox-label input[type="checkbox"]:checked + .checkmark {
    background: #667eea;
    border-color: #667eea;
}

.checkbox-label input[type="checkbox"]:checked + .checkmark::after {
    content: '✓';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: white;
    font-size: 12px;
    font-weight: bold;
}

.terms-link {
    color: #667eea;
    text-decoration: none;
}

.terms-link:hover {
    text-decoration: underline;
}

.btn-payment {
    width: 100%;
    background: linear-gradient(135deg, #00C896 0%, #00b085 100%);
    color: white;
    border: none;
    padding: 18px 24px;
    border-radius: 12px;
    font-size: 17px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-top: 1.5rem;
    box-shadow: 0 4px 15px rgba(0, 200, 150, 0.3);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.btn-payment:hover {
    background: linear-gradient(135deg, #00b085 0%, #009970 100%);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 200, 150, 0.4);
}

.btn-payment:disabled {
    background: #ccc;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.order-summary-section {
    position: sticky;
    top: 2rem;
    height: fit-content;
}

.order-summary {
    background: rgba(255, 255, 255, 0.95);
    border-radius: 20px;
    padding: 2rem;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.order-summary h3 {
    color: #1a1a1a;
    margin-bottom: 1.5rem;
    font-size: 1.4rem;
    font-weight: 700;
    position: relative;
    padding-bottom: 0.5rem;
}

.order-summary h3::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 40px;
    height: 3px;
    background: linear-gradient(90deg, #667eea, #764ba2);
    border-radius: 2px;
}

.order-item {
    padding: 1rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.order-item:last-child {
    border-bottom: none;
}

.item-name {
    font-weight: 600;
    color: #1a1a1a;
    margin-bottom: 0.25rem;
}

.item-details {
    font-size: 0.9rem;
    color: #666;
    margin-bottom: 0.5rem;
}

.item-quantity-price {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.9rem;
}

.summary-totals {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid #f0f0f0;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
}

.summary-row.total {
    border-top: 2px solid #667eea;
    margin-top: 1rem;
    padding-top: 1rem;
    font-weight: 700;
    font-size: 1.1rem;
    color: #1a1a1a;
}

.security-info {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid #f0f0f0;
    text-align: center;
}

.security-badge {
    background: linear-gradient(135deg, #e8f5e8 0%, #d4f4dd 100%);
    color: #00C896;
    padding: 0.75rem 1.25rem;
    border-radius: 25px;
    font-size: 0.9rem;
    font-weight: 700;
    margin-bottom: 0.75rem;
    display: inline-block;
    box-shadow: 0 2px 10px rgba(0, 200, 150, 0.2);
    border: 1px solid rgba(0, 200, 150, 0.1);
}

.security-info p {
    font-size: 0.85rem;
    color: #64748b;
    margin: 0;
    font-weight: 500;
}

/* İndirim Kodu Stilleri */
.discount-section {
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #f0f0f0;
}

.discount-input-group {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
}

.discount-input-group input {
    flex: 1;
    padding: 12px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s ease;
    background: rgba(255, 255, 255, 0.9);
}

.discount-input-group input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.btn-apply-discount {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 12px 20px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    white-space: nowrap;
}

.btn-apply-discount:hover {
    background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-apply-discount:disabled {
    background: #ccc;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.discount-message {
    font-size: 0.85rem;
    padding: 0.5rem 0;
    min-height: 1.2rem;
}

.discount-message.success {
    color: #00C896;
    font-weight: 600;
}

.discount-message.error {
    color: #dc3545;
    font-weight: 500;
}

.discount-text {
    color: #00C896;
    font-weight: 600;
}

/* Mobil İndirim Kodu Bölümü */
.mobile-discount-section {
    display: none;
}

@media (max-width: 768px) {
    /* Mobil hızlı erişim menüsünü gizle */
    .mobile-bottom-nav {
        display: none !important;
    }
    
    .mobile-discount-section {
        display: block;
    }
    
    .order-summary .discount-section {
        display: none !important;
    }
    
    body {
        padding: 0 !important;
        margin: 0 !important;
        padding-bottom: 0 !important; /* Alt boşluğu kaldır */
    }
    
    .payment-page {
        padding: 2rem 1rem; /* Padding'i daha da büyüt */
        min-height: 100vh;
        font-size: 1.2rem; /* Genel font boyutunu daha da büyüt */
    }
    
    .page-header {
        margin-bottom: 2.5rem;
        padding: 0 0.5rem;
    }
    
    .page-title {
        font-size: 2.5rem; /* Başlığı daha da büyüt */
        margin-bottom: 1rem;
    }
    
    .page-subtitle {
        font-size: 1.3rem; /* Alt başlığı daha da büyüt */
        padding: 0 1rem;
    }
    
    .payment-content {
        grid-template-columns: 1fr;
        gap: 1.5rem;
        padding: 0;
        max-width: 100%;
        margin: 0;
        justify-items: stretch;
    }
    
    .order-summary-section {
        position: static;
        order: 2;
    }
    
    .payment-form-section {
        order: 1;
    }
    
    .payment-form-section {
            padding: 2.5rem 1rem; /* Sol padding'i azalt */
            margin: 0;
            border-radius: 15px;
            order: 1;
            width: 100%;
            max-width: none;
        }
    
    .order-summary {
            padding: 2.5rem 1rem; /* Sol padding'i azalt */
            margin: 0;
            border-radius: 15px;
            width: 100%;
            max-width: none;
        }
    
    .form-row {
        grid-template-columns: 1fr;
        gap: 0.5rem;
    }
    
    .form-group {
        margin-bottom: 1rem;
    }
    
    .form-group input,
    .form-group textarea {
        padding: 18px 22px; /* Input padding'ini daha da büyüt */
        font-size: 19px; /* Font boyutunu daha da büyüt */
        border-radius: 12px;
        min-height: 55px; /* Minimum yükseklik artır */
    }
    
    .section-title {
        font-size: 1.6rem; /* Bölüm başlıklarını daha da büyüt */
        margin-bottom: 1.5rem;
    }
    
    .btn-payment {
        padding: 22px 26px; /* Buton padding'ini daha da büyüt */
        font-size: 19px; /* Buton font boyutunu daha da büyüt */
        border-radius: 12px;
        margin-top: 1.5rem;
        min-height: 60px; /* Minimum yükseklik artır */
    }
    
    .checkbox-label {
        font-size: 0.9rem;
        line-height: 1.4;
    }
    
    .checkmark {
        width: 16px;
        height: 16px;
        margin-top: 1px;
    }
    
    .discount-input-group {
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .discount-input-group input {
        padding: 16px 18px;
        font-size: 18px;
        min-height: 50px;
    }
    
    .btn-apply-discount {
        padding: 16px 22px;
        font-size: 18px;
        width: 100%;
        min-height: 50px;
    }
}

@media (max-width: 480px) {
    .payment-page {
        padding: 1rem 0.5rem; /* Padding'i büyüt */
        font-size: 1.05rem; /* Font boyutunu büyüt */
    }
    
    .page-title {
        font-size: 2rem; /* Başlığı büyüt */
    }
    
    .payment-form-section,
    .order-summary {
        padding: 1.75rem 1.25rem; /* Padding'i büyüt */
        margin: -5px;
    }
    
    .form-group input,
    .form-group textarea {
        padding: 14px 18px; /* Input padding'ini büyüt */
        font-size: 17px; /* Font boyutunu büyüt */
        min-height: 48px; /* Minimum yükseklik ekle */
    }
    
    .btn-payment {
        padding: 18px 22px; /* Buton padding'ini büyüt */
        font-size: 17px; /* Font boyutunu büyüt */
        min-height: 54px; /* Minimum yükseklik ekle */
    }
}

/* Ödeme Bilgilendirmesi Stilleri */
.payment-info {
    background: rgba(102, 126, 234, 0.05);
    border-radius: 16px;
    padding: 1.5rem;
    border: 1px solid rgba(102, 126, 234, 0.1);
}

.payment-method {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.method-icon {
    font-size: 2rem;
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.method-details h4 {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1a1a1a;
    margin-bottom: 0.5rem;
}

.method-details p {
    font-size: 0.9rem;
    color: #64748b;
    margin: 0;
    line-height: 1.5;
}
</style>

<script>
let cart = [];

// Sayfa yüklendiğinde sepeti yükle ve göster
document.addEventListener('DOMContentLoaded', function() {
    loadCart();
    setupFormValidation();
    setupCardFormatting();
    setupDiscountCode();
});

function loadCart() {
    // Veritabanından sepet verilerini çek
    fetch('ajax/cart.php?action=get')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            cart = data.items;
            
            // Sepet boşsa ana sayfaya yönlendir
            if (cart.length === 0) {
                alert('Sepetinizde ürün bulunmuyor!');
                window.location.href = 'index.php';
                return;
            }
            
            displayOrderSummary();
        } else {
            console.error('Sepet yüklenirken hata:', data.message);
            alert('Sepet yüklenirken hata oluştu!');
            window.location.href = 'index.php';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Bir hata oluştu!');
        window.location.href = 'index.php';
    });
}

function displayOrderSummary() {
    const orderItemsContainer = document.getElementById('orderItems');
    
    orderItemsContainer.innerHTML = cart.map(item => `
        <div class="order-item">
            <div class="item-name">${item.event_name}</div>
            <div class="item-details">
                ${item.ticket_name}<br>
            </div>
            <div class="item-quantity-price">
                <span>${item.quantity} adet</span>
                <span>₺${(parseFloat(item.price) * parseInt(item.quantity)).toLocaleString('tr-TR')}</span>
            </div>
        </div>
    `).join('');
    
    updateSummary();
}

let appliedDiscount = 0;
let discountCode = '';

function updateSummary() {
    const subtotal = cart.reduce((sum, item) => sum + (parseFloat(item.price) * parseInt(item.quantity)), 0);
    const discountedSubtotal = Math.max(0, subtotal - appliedDiscount);
    const grandTotal = discountedSubtotal;
    
    document.getElementById('subtotal').textContent = '₺' + subtotal.toLocaleString('tr-TR');
    document.getElementById('grandTotal').textContent = '₺' + grandTotal.toLocaleString('tr-TR');
    
    // İndirim satırını göster/gizle
    const discountRow = document.getElementById('discountRow');
    const discountAmount = document.getElementById('discountAmount');
    if (appliedDiscount > 0) {
        discountRow.style.display = 'flex';
        discountAmount.textContent = '-₺' + appliedDiscount.toLocaleString('tr-TR');
    } else {
        discountRow.style.display = 'none';
    }
}

function setupCardFormatting() {
    // Kart bilgileri kaldırıldığı için bu fonksiyon artık boş
    console.log('Kart formatlaması devre dışı - PayTR entegrasyonu bekliyor');
}

function setupDiscountCode() {
    // Desktop indirim kodu
    const applyBtn = document.getElementById('applyDiscountBtn');
    const discountInput = document.getElementById('discountCode');
    const messageDiv = document.getElementById('discountMessage');
    
    if (applyBtn && discountInput) {
        applyBtn.addEventListener('click', function() {
            applyDiscountCode();
        });
        
        discountInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                applyDiscountCode();
            }
        });
    }
    
    // Mobil indirim kodu
    const mobileApplyBtn = document.getElementById('mobileApplyDiscountBtn');
    const mobileDiscountInput = document.getElementById('mobileDiscountCode');
    const mobileMessageDiv = document.getElementById('mobileDiscountMessage');
    
    if (mobileApplyBtn && mobileDiscountInput) {
        mobileApplyBtn.addEventListener('click', function() {
            applyMobileDiscountCode();
        });
        
        mobileDiscountInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                applyMobileDiscountCode();
            }
        });
    }
}

function applyDiscountCode() {
    const discountInput = document.getElementById('discountCode');
    const applyBtn = document.getElementById('applyDiscountBtn');
    const messageDiv = document.getElementById('discountMessage');
    
    const code = discountInput.value.trim().toUpperCase();
    
    if (!code) {
        showDiscountMessage('Lütfen bir indirim kodu giriniz.', 'error');
        return;
    }
    
    // Buton durumunu değiştir
    applyBtn.disabled = true;
    applyBtn.textContent = 'Uygulanıyor...';
    
    // AJAX ile indirim kodunu kontrol et
    const formData = new FormData();
    formData.append('code', code);
    
    fetch('ajax/apply_discount.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            appliedDiscount = parseFloat(data.discount) || 0;
            discountCode = code;
            showDiscountMessage(`İndirim uygulandı: ₺${appliedDiscount.toLocaleString('tr-TR')}`, 'success');
            discountInput.disabled = true;
            applyBtn.textContent = 'Uygulandı';
            updateSummary();
        } else {
            showDiscountMessage(data.message || 'İndirim kodu geçersiz.', 'error');
            applyBtn.disabled = false;
            applyBtn.textContent = 'Uygula';
        }
    })
    .catch(error => {
        console.error('İndirim kodu hatası:', error);
        showDiscountMessage('Bir hata oluştu. Lütfen tekrar deneyin.', 'error');
        applyBtn.disabled = false;
        applyBtn.textContent = 'Uygula';
    });
}

function applyMobileDiscountCode() {
    const discountInput = document.getElementById('mobileDiscountCode');
    const applyBtn = document.getElementById('mobileApplyDiscountBtn');
    const messageDiv = document.getElementById('mobileDiscountMessage');
    
    const code = discountInput.value.trim().toUpperCase();
    
    if (!code) {
        showMobileDiscountMessage('Lütfen bir indirim kodu giriniz.', 'error');
        return;
    }
    
    // Buton durumunu değiştir
    applyBtn.disabled = true;
    applyBtn.textContent = 'Uygulanıyor...';
    
    // AJAX ile indirim kodunu kontrol et
    const formData = new FormData();
    formData.append('code', code);
    
    fetch('ajax/apply_discount.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            appliedDiscount = parseFloat(data.discount) || 0;
            discountCode = code;
            showMobileDiscountMessage(`İndirim uygulandı: ₺${appliedDiscount.toLocaleString('tr-TR')}`, 'success');
            discountInput.disabled = true;
            applyBtn.textContent = 'Uygulandı';
            updateSummary();
            
            // Desktop indirim kodunu da senkronize et
            const desktopInput = document.getElementById('discountCode');
            const desktopBtn = document.getElementById('applyDiscountBtn');
            if (desktopInput && desktopBtn) {
                desktopInput.value = code;
                desktopInput.disabled = true;
                desktopBtn.textContent = 'Uygulandı';
                showDiscountMessage(`İndirim uygulandı: ₺${appliedDiscount.toLocaleString('tr-TR')}`, 'success');
            }
        } else {
            showMobileDiscountMessage(data.message || 'İndirim kodu geçersiz.', 'error');
            applyBtn.disabled = false;
            applyBtn.textContent = 'Uygula';
        }
    })
    .catch(error => {
        console.error('İndirim kodu hatası:', error);
        showMobileDiscountMessage('Bir hata oluştu. Lütfen tekrar deneyin.', 'error');
        applyBtn.disabled = false;
        applyBtn.textContent = 'Uygula';
    });
}

function showDiscountMessage(message, type) {
    const messageDiv = document.getElementById('discountMessage');
    if (messageDiv) {
        messageDiv.textContent = message;
        messageDiv.className = `discount-message ${type}`;
        
        // 5 saniye sonra mesajı temizle (sadece hata mesajları için)
        if (type === 'error') {
            setTimeout(() => {
                messageDiv.textContent = '';
                messageDiv.className = 'discount-message';
            }, 5000);
        }
    }
}

function showMobileDiscountMessage(message, type) {
    const messageDiv = document.getElementById('mobileDiscountMessage');
    if (messageDiv) {
        messageDiv.textContent = message;
        messageDiv.className = `discount-message ${type}`;
        
        // 5 saniye sonra mesajı temizle (sadece hata mesajları için)
        if (type === 'error') {
            setTimeout(() => {
                messageDiv.textContent = '';
                messageDiv.className = 'discount-message';
            }, 5000);
        }
    }
}

function setupFormValidation() {
    const form = document.getElementById('paymentForm');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (validateForm()) {
            processPayment();
        }
    });
}

function validateForm() {
    const requiredFields = [
        'firstName', 'lastName', 'email', 'phone'
    ];
    
    let isValid = true;
    
    requiredFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (!field.value.trim()) {
            field.style.borderColor = '#dc3545';
            isValid = false;
        } else {
            field.style.borderColor = '#ddd';
        }
    });
    
    // Sözleşme onayı kontrolü
    const termsAccept = document.getElementById('termsAccept');
    if (!termsAccept.checked) {
        alert('Kullanım koşullarını kabul etmelisiniz.');
        isValid = false;
    }
    
    // E-posta formatı kontrolü
    const email = document.getElementById('email').value;
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        document.getElementById('email').style.borderColor = '#dc3545';
        alert('Geçerli bir e-posta adresi giriniz.');
        isValid = false;
    }
    
    return isValid;
}

function processPayment() {
    console.log('processPayment başlatıldı - PayTR entegrasyonu');
    const submitBtn = document.querySelector('.btn-payment');
    submitBtn.disabled = true;
    submitBtn.textContent = 'PayTR\'a Yönlendiriliyor...';
    
    // Form verilerini topla
    const formData = new FormData();
    formData.append('firstName', document.getElementById('firstName').value);
    formData.append('lastName', document.getElementById('lastName').value);
    formData.append('email', document.getElementById('email').value);
    formData.append('phone', document.getElementById('phone').value);
    
    // İndirim kodu varsa ekle
    if (discountCode) {
        formData.append('discountCode', discountCode);
    }
    
    // Fiyatların sayı olmasını garanti et
    const normalizedCart = cart.map(it => ({...it, price: parseFloat(it.price), quantity: parseInt(it.quantity)}));
    formData.append('cartData', JSON.stringify(normalizedCart));
    
    console.log('FormData hazırlandı:', {
        firstName: document.getElementById('firstName').value,
        lastName: document.getElementById('lastName').value,
        email: document.getElementById('email').value,
        phone: document.getElementById('phone').value,
        cartData: JSON.stringify(normalizedCart)
    });
    
    // PayTR ödeme sayfasına POST ile yönlendir
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'paytr_payment.php';
    form.style.display = 'none';
    
    // Form verilerini hidden input olarak ekle
    const fields = ['firstName', 'lastName', 'email', 'phone'];
    fields.forEach(fieldName => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = fieldName;
        input.value = document.getElementById(fieldName).value;
        form.appendChild(input);
    });
    
    // İndirim kodu varsa ekle
    if (discountCode) {
        const discountInput = document.createElement('input');
        discountInput.type = 'hidden';
        discountInput.name = 'discountCode';
        discountInput.value = discountCode;
        form.appendChild(discountInput);
    }
    
    // Sepet verilerini ekle
    const cartInput = document.createElement('input');
    cartInput.type = 'hidden';
    cartInput.name = 'cartData';
    cartInput.value = JSON.stringify(normalizedCart);
    form.appendChild(cartInput);
    
    // Formu sayfaya ekle ve submit et
    document.body.appendChild(form);
    form.submit();
}

// Hata alert'ini kapatma fonksiyonu
function closeErrorAlert() {
    const errorAlert = document.getElementById('errorAlert');
    if (errorAlert) {
        errorAlert.style.animation = 'slideUp 0.3s ease';
        setTimeout(() => {
            errorAlert.remove();
        }, 300);
    }
}

// Slide up animasyonu için CSS ekle
const style = document.createElement('style');
style.textContent = `
    @keyframes slideUp {
        from {
            opacity: 1;
            transform: translateY(0);
        }
        to {
            opacity: 0;
            transform: translateY(-20px);
        }
    }
`;
document.head.appendChild(style);
</script>

<?php include 'includes/footer.php'; ?>