<?php 
require_once 'includes/session.php';
include 'includes/header.php'; 
?>
<link rel="stylesheet" href="css/customer.css">

<main class="success-page">
    <div class="container">
        <div class="success-content">
            <div class="success-icon">
                ✅
            </div>
            
            <h1 class="success-title">Ödeme Başarılı!</h1>
            <p class="success-message">Biletleriniz başarıyla satın alındı. E-posta adresinize onay mesajı gönderilmiştir.</p>
            
            <div class="order-info">
                <div class="order-number">
                    <span class="label">Sipariş Numarası:</span>
                    <span id="orderNumber" class="value">-</span>
                </div>
                <div class="order-date">
                    <span class="label">Sipariş Tarihi:</span>
                    <span id="orderDate" class="value">-</span>
                </div>
            </div>
            
            <div class="order-details">
                <h3>Sipariş Detayları</h3>
                <div id="orderItems" class="order-items">
                    <!-- Sipariş öğeleri JavaScript ile doldurulacak -->
                </div>
                
                <div class="order-summary">
                    <div class="summary-row">
                        <span>Toplam Tutar:</span>
                        <span id="totalAmount" class="total-amount">₺0</span>
                    </div>
                </div>
            </div>
            
            <div class="customer-info">
                <h3>Müşteri Bilgileri</h3>
                <div id="customerDetails" class="customer-details">
                    <!-- Müşteri bilgileri JavaScript ile doldurulacak -->
                </div>
            </div>
            
            <div class="next-steps">
                <h3>Sonraki Adımlar</h3>
                <ul class="steps-list">
                    <li>📧 E-posta adresinize bilet detayları gönderilmiştir</li>
                    <li>📱 Etkinlik günü biletlerinizi telefonunuzda gösterebilirsiniz</li>
                    <li>🎫 Etkinlik girişinde QR kodunuzu okutmanız yeterlidir</li>
                    <li>📞 Sorularınız için müşteri hizmetlerimizle iletişime geçebilirsiniz</li>
                </ul>
            </div>
            
            <div class="action-buttons">
                <a href="index.php" class="btn-primary">Ana Sayfaya Dön</a>
                <a href="etkinlikler.php" class="btn-secondary">Diğer Etkinlikleri Keşfet</a>
                <button id="downloadTickets" class="btn-download">Biletleri İndir (PDF)</button>
            </div>
        </div>
    </div>
</main>

<style>
.success-page {
    min-height: 100vh;
    padding: 2rem 0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
}

.success-content {
    max-width: 800px;
    margin: 0 auto;
    background: white;
    border-radius: 20px;
    padding: 3rem;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    text-align: center;
}

.success-icon {
    font-size: 4rem;
    margin-bottom: 1.5rem;
    animation: bounce 1s ease-in-out;
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% {
        transform: translateY(0);
    }
    40% {
        transform: translateY(-10px);
    }
    60% {
        transform: translateY(-5px);
    }
}

.success-title {
    font-size: 2.5rem;
    color: #00C896;
    margin-bottom: 1rem;
    font-weight: 700;
}

.success-message {
    font-size: 1.1rem;
    color: #666;
    margin-bottom: 2rem;
    line-height: 1.6;
}

.order-info {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.order-number,
.order-date {
    text-align: left;
}

.label {
    display: block;
    font-size: 0.9rem;
    color: #666;
    margin-bottom: 0.25rem;
}

.value {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1a1a1a;
}

.order-details,
.customer-info,
.next-steps {
    margin-bottom: 2rem;
    text-align: left;
}

.order-details h3,
.customer-info h3,
.next-steps h3 {
    color: #1a1a1a;
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 1rem;
    text-align: center;
}

.order-items {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 1rem;
}

.order-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #e9ecef;
}

.order-item:last-child {
    border-bottom: none;
}

.item-info {
    flex: 1;
}

.item-name {
    font-weight: 600;
    color: #1a1a1a;
    margin-bottom: 0.25rem;
}

.item-details {
    font-size: 0.9rem;
    color: #666;
}

.item-price {
    font-weight: 600;
    color: #00C896;
}

.order-summary {
    border-top: 2px solid #667eea;
    padding-top: 1rem;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 1.1rem;
    font-weight: 600;
}

.total-amount {
    color: #00C896;
    font-size: 1.3rem;
}

.customer-details {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 1.5rem;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.customer-field {
    display: flex;
    flex-direction: column;
}

.steps-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.steps-list li {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 0.5rem;
    font-size: 0.95rem;
    line-height: 1.5;
}

.action-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
    margin-top: 2rem;
}

.btn-primary,
.btn-secondary,
.btn-download {
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    border: none;
    transition: all 0.3s;
    font-size: 14px;
}

.btn-primary {
    background: #667eea;
    color: white;
}

.btn-primary:hover {
    background: #5a6fd8;
    transform: translateY(-1px);
    color: white;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-1px);
    color: white;
}

.btn-download {
    background: #00C896;
    color: white;
}

.btn-download:hover {
    background: #00b085;
    transform: translateY(-1px);
}

@media (max-width: 768px) {
    .success-content {
        margin: 1rem;
        padding: 2rem 1.5rem;
    }
    
    .success-title {
        font-size: 2rem;
    }
    
    .order-info,
    .customer-details {
        grid-template-columns: 1fr;
    }
    
    .action-buttons {
        flex-direction: column;
        align-items: center;
    }
    
    .btn-primary,
    .btn-secondary,
    .btn-download {
        width: 100%;
        max-width: 300px;
    }
}
</style>

<script>
// Sayfa yüklendiğinde sipariş bilgilerini göster
document.addEventListener('DOMContentLoaded', function() {
    displayOrderInfo();
    setupDownloadButton();
});

function displayOrderInfo() {
    // URL'den sipariş numarasını al
    const urlParams = new URLSearchParams(window.location.search);
    const orderNumber = urlParams.get('order');
    
    if (!orderNumber) {
        window.location.href = 'index.php';
        return;
    }
    
    // Son siparişi localStorage'dan al
    const orderData = JSON.parse(localStorage.getItem('lastOrder') || '{}');
    
    if (!orderData.orderNumber || orderData.orderNumber !== orderNumber) {
        window.location.href = 'index.php';
        return;
    }
    
    // Sipariş numarası ve tarihi
    document.getElementById('orderNumber').textContent = orderData.orderNumber;
    document.getElementById('orderDate').textContent = new Date(orderData.date).toLocaleDateString('tr-TR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
    
    // Sipariş öğeleri
    const orderItemsContainer = document.getElementById('orderItems');
    orderItemsContainer.innerHTML = orderData.items.map(item => `
        <div class="order-item">
            <div class="item-info">
                <div class="item-name">${item.eventTitle}</div>
                <div class="item-details">
                    ${item.ticketName} • ${item.quantity} adet<br>
                    📅 ${item.eventDate} | 📍 ${item.eventVenue}
                </div>
            </div>
            <div class="item-price">₺${item.total.toLocaleString('tr-TR')}</div>
        </div>
    `).join('');
    
    // Toplam tutar
    document.getElementById('totalAmount').textContent = '₺' + orderData.total.toLocaleString('tr-TR');
    
    // Müşteri bilgileri
    const customerDetailsContainer = document.getElementById('customerDetails');
    customerDetailsContainer.innerHTML = `
        <div class="customer-field">
            <span class="label">Ad Soyad:</span>
            <span class="value">${orderData.customerInfo.firstName} ${orderData.customerInfo.lastName}</span>
        </div>
        <div class="customer-field">
            <span class="label">E-posta:</span>
            <span class="value">${orderData.customerInfo.email}</span>
        </div>
        <div class="customer-field">
            <span class="label">Telefon:</span>
            <span class="value">${orderData.customerInfo.phone}</span>
        </div>
        <div class="customer-field">
            <span class="label">Sipariş Durumu:</span>
            <span class="value" style="color: #00C896; font-weight: 600;">Onaylandı</span>
        </div>
    `;
}

function setupDownloadButton() {
    document.getElementById('downloadTickets').addEventListener('click', function() {
        // Basit PDF indirme simülasyonu
        alert('Biletleriniz PDF olarak indiriliyor... (Bu özellik demo amaçlıdır)');
        
        // Gerçek uygulamada burada PDF oluşturma kodu olacak
        // Örneğin: window.open('generate-pdf.php?order=' + orderNumber, '_blank');
    });
}

// Sayfa kapatılırken sipariş verilerini temizle
window.addEventListener('beforeunload', function() {
    // 5 dakika sonra sipariş verilerini temizle
    setTimeout(() => {
        localStorage.removeItem('lastOrder');
    }, 300000);
});
</script>

<?php include 'includes/footer.php'; ?>