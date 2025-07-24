<?php 
require_once 'includes/session.php';
include 'includes/header.php'; 
?>
<link rel="stylesheet" href="css/customer.css">

<main class="payment-page">
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Ödeme</h1>
            <p class="page-subtitle">Güvenli ödeme ile biletlerinizi satın alın</p>
        </div>
        
        <div class="payment-content">
            <!-- Sol Taraf - Ödeme Formu -->
            <div class="payment-form-section">
                <form id="paymentForm" class="payment-form">
                    <!-- Kişisel Bilgiler -->
                    <div class="form-section">
                        <h3 class="section-title">Kişisel Bilgiler</h3>
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
                    
                    <!-- Kart Bilgileri -->
                    <div class="form-section">
                        <h3 class="section-title">Kart Bilgileri</h3>
                        <div class="form-group">
                            <label for="cardNumber">Kart Numarası *</label>
                            <input type="text" id="cardNumber" name="cardNumber" placeholder="1234 5678 9012 3456" maxlength="19" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="expiryDate">Son Kullanma Tarihi *</label>
                                <input type="text" id="expiryDate" name="expiryDate" placeholder="MM/YY" maxlength="5" required>
                            </div>
                            <div class="form-group">
                                <label for="cvv">CVV *</label>
                                <input type="text" id="cvv" name="cvv" placeholder="123" maxlength="3" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="cardName">Kart Üzerindeki İsim *</label>
                            <input type="text" id="cardName" name="cardName" required>
                        </div>
                    </div>
                    
                    <!-- Fatura Adresi -->
                    <div class="form-section">
                        <h3 class="section-title">Fatura Adresi</h3>
                        <div class="form-group">
                            <label for="address">Adres *</label>
                            <textarea id="address" name="address" rows="3" required></textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="city">Şehir *</label>
                                <input type="text" id="city" name="city" required>
                            </div>
                            <div class="form-group">
                                <label for="postalCode">Posta Kodu *</label>
                                <input type="text" id="postalCode" name="postalCode" required>
                            </div>
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
                        <div class="checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="emailConsent">
                                <span class="checkmark"></span>
                                E-posta ile kampanya ve duyurular almak istiyorum
                            </label>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-payment">Ödemeyi Tamamla</button>
                </form>
            </div>
            
            <!-- Sağ Taraf - Sipariş Özeti -->
            <div class="order-summary-section">
                <div class="order-summary">
                    <h3>Sipariş Özeti</h3>
                    <div id="orderItems" class="order-items">
                        <!-- Sipariş öğeleri JavaScript ile doldurulacak -->
                    </div>
                    
                    <div class="summary-totals">
                        <div class="summary-row">
                            <span>Ara Toplam:</span>
                            <span id="subtotal">₺0</span>
                        </div>
                        <div class="summary-row">
                            <span>Hizmet Bedeli:</span>
                            <span id="serviceFee">₺0</span>
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
.payment-page {
    min-height: 100vh;
    padding: 2rem 0;
    background: #f8f9fa;
}

.page-header {
    text-align: center;
    margin-bottom: 3rem;
}

.page-title {
    font-size: 2.5rem;
    color: #1a1a1a;
    margin-bottom: 1rem;
    font-weight: 700;
}

.page-subtitle {
    font-size: 1.1rem;
    color: #666;
    max-width: 600px;
    margin: 0 auto;
}

.payment-content {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 3rem;
    max-width: 1200px;
    margin: 0 auto;
}

.payment-form-section {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.form-section {
    margin-bottom: 2rem;
    padding-bottom: 2rem;
    border-bottom: 1px solid #f0f0f0;
}

.form-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.section-title {
    color: #1a1a1a;
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
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
    padding: 12px 16px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
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
    background: #00C896;
    color: white;
    border: none;
    padding: 16px;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    margin-top: 1rem;
}

.btn-payment:hover {
    background: #00b085;
    transform: translateY(-1px);
}

.btn-payment:disabled {
    background: #ccc;
    cursor: not-allowed;
    transform: none;
}

.order-summary-section {
    position: sticky;
    top: 2rem;
    height: fit-content;
}

.order-summary {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.order-summary h3 {
    color: #1a1a1a;
    margin-bottom: 1.5rem;
    font-size: 1.2rem;
    font-weight: 600;
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
    background: #e8f5e8;
    color: #00C896;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    display: inline-block;
}

.security-info p {
    font-size: 0.8rem;
    color: #666;
    margin: 0;
}

@media (max-width: 768px) {
    .payment-content {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .order-summary-section {
        position: static;
        order: -1;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .page-title {
        font-size: 2rem;
    }
    
    .payment-form-section {
        padding: 1.5rem;
    }
}
</style>

<script>
let cart = [];

// Sayfa yüklendiğinde sepeti yükle ve göster
document.addEventListener('DOMContentLoaded', function() {
    loadCart();
    displayOrderSummary();
    setupFormValidation();
    setupCardFormatting();
});

function loadCart() {
    cart = JSON.parse(localStorage.getItem('cart') || '[]');
    
    // Sepet boşsa ana sayfaya yönlendir
    if (cart.length === 0) {
        alert('Sepetinizde ürün bulunmuyor!');
        window.location.href = 'index.php';
        return;
    }
}

function displayOrderSummary() {
    const orderItemsContainer = document.getElementById('orderItems');
    
    orderItemsContainer.innerHTML = cart.map(item => `
        <div class="order-item">
            <div class="item-name">${item.eventTitle}</div>
            <div class="item-details">
                ${item.ticketName}<br>
                📅 ${item.eventDate} | 📍 ${item.eventVenue}
            </div>
            <div class="item-quantity-price">
                <span>${item.quantity} adet</span>
                <span>₺${item.total.toLocaleString('tr-TR')}</span>
            </div>
        </div>
    `).join('');
    
    updateSummary();
}

function updateSummary() {
    const subtotal = cart.reduce((sum, item) => sum + item.total, 0);
    const serviceFee = Math.round(subtotal * 0.05);
    const grandTotal = subtotal + serviceFee;
    
    document.getElementById('subtotal').textContent = '₺' + subtotal.toLocaleString('tr-TR');
    document.getElementById('serviceFee').textContent = '₺' + serviceFee.toLocaleString('tr-TR');
    document.getElementById('grandTotal').textContent = '₺' + grandTotal.toLocaleString('tr-TR');
}

function setupCardFormatting() {
    // Kart numarası formatı
    document.getElementById('cardNumber').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\s/g, '').replace(/[^0-9]/gi, '');
        let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
        e.target.value = formattedValue;
    });
    
    // Son kullanma tarihi formatı
    document.getElementById('expiryDate').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length >= 2) {
            value = value.substring(0, 2) + '/' + value.substring(2, 4);
        }
        e.target.value = value;
    });
    
    // CVV sadece rakam
    document.getElementById('cvv').addEventListener('input', function(e) {
        e.target.value = e.target.value.replace(/[^0-9]/g, '');
    });
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
        'firstName', 'lastName', 'email', 'phone',
        'cardNumber', 'expiryDate', 'cvv', 'cardName',
        'address', 'city', 'postalCode'
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
    
    // Kart numarası kontrolü (basit)
    const cardNumber = document.getElementById('cardNumber').value.replace(/\s/g, '');
    if (cardNumber.length !== 16) {
        document.getElementById('cardNumber').style.borderColor = '#dc3545';
        alert('Kart numarası 16 haneli olmalıdır.');
        isValid = false;
    }
    
    return isValid;
}

function processPayment() {
    const submitBtn = document.querySelector('.btn-payment');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Ödeme İşleniyor...';
    
    // Simüle edilmiş ödeme işlemi
    setTimeout(() => {
        // Başarılı ödeme simülasyonu
        const orderNumber = 'BJ' + Date.now();
        
        // Sipariş bilgilerini localStorage'a kaydet
        const orderData = {
            orderNumber: orderNumber,
            items: cart,
            customerInfo: {
                firstName: document.getElementById('firstName').value,
                lastName: document.getElementById('lastName').value,
                email: document.getElementById('email').value,
                phone: document.getElementById('phone').value
            },
            total: cart.reduce((sum, item) => sum + item.total, 0) * 1.05, // Hizmet bedeli dahil
            date: new Date().toISOString()
        };
        
        localStorage.setItem('lastOrder', JSON.stringify(orderData));
        
        // Sepeti temizle
        localStorage.removeItem('cart');
        
        // Başarı sayfasına yönlendir
        window.location.href = 'odeme-basarili.php?order=' + orderNumber;
        
    }, 3000);
}
</script>

<?php include 'includes/footer.php'; ?>