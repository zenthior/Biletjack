<?php
require_once 'includes/session.php';

// Giriş/rol kontrolü (yalnızca müşteri)
requireCustomer();

require_once 'includes/header.php';
?>

<div class="modern-cart-page">
    <div class="cart-hero">
        <div class="hero-content">
            <h1 class="hero-title">Sepetim</h1>
            <p class="hero-subtitle">Seçtiğiniz biletleri gözden geçirin ve güvenle satın alın</p>
        </div>
    </div>
    
    <div class="cart-main-container">
        <div class="cart-grid">
            <!-- Sol taraf - Sepet öğeleri -->
            <div class="cart-items-section">
                <!-- Boş sepet durumu -->
                <div id="emptyCart" class="empty-cart-modern">
                    <h3 class="empty-title">Sepetiniz henüz boş</h3>
                    <p class="empty-description">Harika etkinlikler sizi bekliyor! Hemen keşfetmeye başlayın.</p>
                    <a href="etkinlikler.php" class="explore-btn">
                        <span>🎭 Etkinlikleri Keşfet</span>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                            <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                </div>
                
                <!-- Sepet öğeleri -->
                <div id="cartItems" class="cart-items-list"></div>
            </div>
            
            <!-- Sağ taraf - Sipariş özeti -->
            <div class="order-summary-section" id="cartSummary">
                <div class="summary-card-modern">
                    <div class="summary-header">
                        <h3>💳 Sipariş Özeti</h3>
                        <div class="security-badge">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                <path d="M12 22S8 18 8 13V6L12 4L16 6V13C16 18 12 22 12 22Z" stroke="#00C896" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span>Güvenli Ödeme</span>
                        </div>
                    </div>
                    
                    <div class="summary-details">
                        <div class="summary-item">
                            <span class="label">Ara Toplam</span>
                            <span class="value" id="subtotal">₺0</span>
                        </div>
                        <!-- Hizmet Bedeli satırını kaldırıldı -->
                        <div class="summary-divider"></div>
                        <div class="summary-item total">
                            <span class="label">Toplam Tutar</span>
                            <span class="value" id="total">₺0</span>
                        </div>
                    </div>
                    
                    <button class="checkout-btn" onclick="proceedToCheckout()">
                        <span>Ödemeye Geç</span>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                            <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Modern Cart Page Styles */
.modern-cart-page {
    min-height: 100vh;
    position: relative;
}

.cart-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 4rem 0 2rem;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.cart-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="%23ffffff" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>') repeat;
    opacity: 0.3;
}

.hero-content {
    position: relative;
    z-index: 2;
    max-width: 800px;
    margin: 0 auto;
    padding: 0 2rem;
}

.hero-title {
    font-size: 2.5rem;
    font-weight: 800;
    color: white;
    margin-bottom: 1rem;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
    letter-spacing: -0.02em;
}

.hero-subtitle {
    font-size: 1.3rem;
    padding-bottom: 30px;
    color: rgba(255,255,255,0.9);
    margin: 0;
    font-weight: 300;
}

.cart-main-container {
    background: #f8fafc;
    min-height: 70vh;
    padding: 3rem 0;
    position: relative;
    margin-top: -2rem;
}

.cart-grid {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 2rem;
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 3rem;
    align-items: start;
}

/* Empty Cart Modern Design */
.empty-cart-modern {
    background: #5d5c5c2e;
    padding: 4rem 2rem;
    margin-left: 450px;
    text-align: center;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    border: 1px solid rgba(102, 126, 234, 0.1);
}

.empty-illustration {
    margin-bottom: 2rem;
}

.cart-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 120px;
    height: 120px;
    background: linear-gradient(135deg, #667eea20, #764ba220);
    border-radius: 50%;
    margin: 0 auto 2rem;
}

.empty-title {
    font-size: 2rem;
    font-weight: 700;
    color: #1a202c;
    margin-bottom: 1rem;
}

.empty-description {
    font-size: 1.1rem;
    color: #64748b;
    margin-bottom: 2.5rem;
    line-height: 1.6;
    max-width: 400px;
    margin-left: auto;
    margin-right: auto;
}

.explore-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 1rem 2rem;
    border-radius: 16px;
    text-decoration: none;
    font-weight: 600;
    font-size: 1.1rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3);
}

.explore-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(102, 126, 234, 0.4);
    color: white;
}

/* Cart Items List */
.cart-items-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.cart-item-modern {
    background: white;
    border-radius: 20px;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: 1px solid rgba(102, 126, 234, 0.1);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.cart-item-modern:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 40px rgba(0,0,0,0.15);
}

.item-header-modern {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1.5rem;
}

.event-info-modern h4 {
    font-size: 1.4rem;
    font-weight: 700;
    color: #1a202c;
    margin-bottom: 0.5rem;
}

.event-details-modern {
    color: #64748b;
    font-size: 1rem;
    line-height: 1.5;
}

.remove-item-modern {
    background: #fee2e2;
    border: none;
    color: #dc2626;
    width: 40px;
    height: 40px;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.remove-item-modern:hover {
    background: #fecaca;
    transform: scale(1.1);
}

.ticket-info-modern {
    background: linear-gradient(135deg, #f8fafc, #e2e8f0);
    padding: 1.5rem;
    border-radius: 16px;
    margin-bottom: 1.5rem;
    border: 1px solid #e2e8f0;
}

.ticket-name-modern {
    font-weight: 700;
    color: #1a202c;
    margin-bottom: 0.75rem;
    font-size: 1.1rem;
}

.ticket-price-modern {
    color: #00C896;
    font-weight: 700;
    font-size: 1.2rem;
}

.quantity-controls-modern {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 1rem;
}

.quantity-selector-modern {
    display: flex;
    align-items: center;
    gap: 1rem;
    background: white;
    padding: 0.5rem;
    border-radius: 12px;
    border: 2px solid #e2e8f0;
}

.quantity-btn-modern {
    width: 40px;
    height: 40px;
    border: none;
    background: #667eea;
    color: white;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s;
}

.quantity-btn-modern:hover {
    background: #5a6fd8;
    transform: scale(1.05);
}

.quantity-input-modern {
    width: 60px;
    height: 40px;
    text-align: center;
    border: none;
    background: transparent;
    font-weight: 700;
    font-size: 1.1rem;
    color: #1a202c;
}

.item-total-modern {
    font-size: 1.4rem;
    font-weight: 800;
    color: #667eea;
}

/* Order Summary Modern Design */
.order-summary-section {
    position: sticky;
    top: 2rem;
}

.summary-card-modern {
    background: white;
    border-radius: 24px;
    padding: 2rem;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    border: 1px solid rgba(102, 126, 234, 0.1);
}

.summary-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #f1f5f9;
}

.summary-header h3 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1a202c;
    margin: 0;
}

.security-badge {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: #f0fdf4;
    padding: 0.5rem 1rem;
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: 600;
    color: #00C896;
}

.summary-details {
    margin-bottom: 2rem;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 0;
    font-size: 1rem;
}

.summary-item .label {
    color: #64748b;
    font-weight: 500;
}

.summary-item .value {
    color: #1a202c;
    font-weight: 600;
}

.summary-item.total {
    font-size: 1.3rem;
    font-weight: 700;
    color: #1a202c;
    padding-top: 1.5rem;
}

.summary-item.total .value {
    color: #667eea;
}

.summary-divider {
    height: 2px;
    background: linear-gradient(90deg, #667eea, #764ba2);
    border-radius: 1px;
    margin: 1rem 0;
}

.checkout-btn {
    width: 100%;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    padding: 1.25rem 2rem;
    border-radius: 16px;
    font-size: 1.1rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3);
    margin-bottom: 1.5rem;
}

.checkout-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(102, 126, 234, 0.4);
}

.payment-methods {
    text-align: center;
    padding-top: 1rem;
    border-top: 1px solid #f1f5f9;
}

.payment-methods p {
    font-size: 0.9rem;
    color: #64748b;
    margin-bottom: 1rem;
}

.payment-icons {
    display: flex;
    justify-content: center;
    gap: 1rem;
}

.payment-icon {
    font-size: 1.5rem;
    padding: 0.5rem;
    background: #f8fafc;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .cart-grid {
        grid-template-columns: 1fr;
        gap: 2rem;
        padding: 0 1rem;
    }
    
    .order-summary-section {
        position: static;
        order: -1;
    }
}

@media (max-width: 768px) {
    .hero-title {
        font-size: 2.5rem;
    }
    
    .hero-subtitle {
        font-size: 1.1rem;
    }
    
    .cart-main-container {
        padding: 2rem 0;
    }
    
    .cart-grid {
        padding: 0 1rem;
    }
    
    .summary-card-modern,
    .empty-cart-modern {
        padding: 1.5rem;
    }
}

@media (max-width: 480px) {
    .hero-content {
        padding: 0 1rem;
    }
    
    .hero-title {
        font-size: 2rem;
    }
    
    .cart-grid {
        padding: 0 0.5rem;
    }
    
    .empty-cart-modern {
        margin-left: 0;
        margin-right: 0;
        padding: 2rem 1rem;
    }
}
</style>

<script>
let cart = [];

// Sayfa yüklendiğinde sepeti göster
document.addEventListener('DOMContentLoaded', function() {
    loadCart();
});

function loadCart() {
    // Veritabanından sepet verilerini çek
    fetch('ajax/cart.php?action=get')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            cart = data.items;
            displayCart();
        } else {
            console.error('Sepet yüklenirken hata:', data.message);
            displayCart();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        displayCart();
    });
}

function displayCart() {
    const cartItemsContainer = document.getElementById('cartItems');
    const emptyCart = document.getElementById('emptyCart');
    const cartSummary = document.getElementById('cartSummary');
    
    if (cart.length === 0) {
        emptyCart.style.display = 'block';
        cartSummary.style.display = 'none';
        cartItemsContainer.innerHTML = '';
        return;
    }
    
    emptyCart.style.display = 'none';
    cartSummary.style.display = 'block';
    
    cartItemsContainer.innerHTML = cart.map((item, index) => `
        <div class="cart-item-modern">
            <div class="item-header-modern">
                <div class="event-info-modern">
                    <h4>${item.event_name}</h4>
                    <div class="event-details-modern">
                        📅 Etkinlik Bilgileri • 📍 Mekan
                    </div>
                </div>
                <button class="remove-item-modern" onclick="removeFromCart(${item.id})" title="Sepetten Kaldır">
                    🗑️
                </button>
            </div>
            
            <div class="ticket-info-modern">
                <div class="ticket-name-modern">🎫 ${item.ticket_name}</div>
                <div class="ticket-price-modern">₺${parseFloat(item.price).toLocaleString('tr-TR')}</div>
            </div>
            
            <div class="quantity-controls-modern">
                ${
                    item.seat_id
                    ? '<div class="quantity-selector-modern"><input type="number" class="quantity-input-modern" value="1" disabled title="Koltuklu biletlerde miktar değiştirilemez"></div>'
                    : '<div class="quantity-selector-modern">'
                        + `<button class="quantity-btn-modern" onclick="updateQuantity(${item.id}, ${parseInt(item.quantity) - 1})" ${parseInt(item.quantity) <= 1 ? 'disabled' : ''}>-</button>`
                        + `<input type="number" class="quantity-input-modern" value="${item.quantity}" min="1" max="10" onchange="updateQuantity(${item.id}, this.value)">`
                        + `<button class="quantity-btn-modern" onclick="updateQuantity(${item.id}, ${parseInt(item.quantity) + 1})" ${parseInt(item.quantity) >= 10 ? 'disabled' : ''}>+</button>`
                      + '</div>'
                }
                <div class="item-total-modern">₺${(parseFloat(item.price) * parseInt(item.quantity)).toLocaleString('tr-TR')}</div>
            </div>
        </div>
    `).join('');
    
    updateSummary();
}

function updateQuantity(cartId, newQuantity) {
    newQuantity = parseInt(newQuantity);
    if (newQuantity < 1 || newQuantity > 10) return;
    
    const formData = new FormData();
    formData.append('action', 'update');
    formData.append('cart_id', cartId);
    formData.append('quantity', newQuantity);
    
    fetch('ajax/cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadCart(); // Sepeti yeniden yükle
        } else {
            alert('Hata: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Bir hata oluştu. Lütfen tekrar deneyin.');
    });
}

function removeFromCart(cartId) {
    if (confirm('Bu ürünü sepetten kaldırmak istediğinizden emin misiniz?')) {
        const formData = new FormData();
        formData.append('action', 'remove');
        formData.append('cart_id', cartId);
        
        fetch('ajax/cart.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadCart(); // Sepeti yeniden yükle
                // Başarı mesajı göster
                showNotification('Ürün sepetten kaldırıldı! 🗑️', 'success');
            } else {
                alert('Hata: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Bir hata oluştu. Lütfen tekrar deneyin.');
        });
    }
}

function updateSummary() {
    const subtotal = cart.reduce((sum, item) => sum + (parseFloat(item.price) * parseInt(item.quantity)), 0);
    // Hizmet bedeli kaldırıldı: toplam = sadece ara toplam
    const total = subtotal;
    
    document.getElementById('subtotal').textContent = `₺${subtotal.toLocaleString('tr-TR')}`;
    // document.getElementById('serviceFee') kaldırıldığı için güncelleme yok
    document.getElementById('total').textContent = `₺${total.toLocaleString('tr-TR')}`;
}

function proceedToCheckout() {
    if (cart.length === 0) {
        showNotification('Sepetiniz boş! Önce bilet ekleyin. 🛒', 'error');
        return;
    }
    
    // Kullanıcı giriş kontrolü
    <?php if (!isset($_SESSION['user_id'])): ?>
        showNotification('Ödeme yapabilmek için önce giriş yapmalısınız! 🔐', 'warning');
        setTimeout(() => {
            window.location.href = 'auth/login.php';
        }, 2000);
        return;
    <?php endif; ?>
    
    // Ödeme sayfasına yönlendir
    window.location.href = 'odeme.php';
}

function showNotification(message, type = 'info') {
    // Mevcut bildirimleri kaldır
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => notification.remove());
    
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="notification-close">×</button>
        </div>
    `;
    
    // Notification CSS
    const style = document.createElement('style');
    style.textContent = `
        .notification {
            position: fixed;
            top: 2rem;
            right: 2rem;
            z-index: 10000;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            animation: slideIn 0.3s ease-out;
            max-width: 400px;
        }
        
        .notification-success { background: linear-gradient(135deg, #00C896, #00a085); }
        .notification-error { background: linear-gradient(135deg, #dc2626, #b91c1c); }
        .notification-warning { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .notification-info { background: linear-gradient(135deg, #667eea, #764ba2); }
        
        .notification-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }
        
        .notification-close {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0;
            line-height: 1;
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    `;
    
    document.head.appendChild(style);
    document.body.appendChild(notification);
    
    // 5 saniye sonra otomatik kaldır
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}


</script>

<?php require_once 'includes/footer.php'; ?>