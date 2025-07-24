<?php 
require_once 'includes/session.php';
include 'includes/header.php'; 
?>
<link rel="stylesheet" href="css/customer.css">

<main class="cart-page">
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Sepetim</h1>
            <p class="page-subtitle">Seçtiğiniz biletleri kontrol edin ve ödeme işlemine geçin</p>
        </div>
        
        <div class="cart-content">
            <!-- Sepet Boş Durumu -->
            <div id="emptyCart" class="empty-cart" style="display: none;">
                <div class="empty-cart-icon">🛒</div>
                <h3>Sepetiniz boş</h3>
                <p>Henüz sepetinize bilet eklemediniz. Etkinliklere göz atın ve favori biletlerinizi sepete ekleyin.</p>
                <a href="etkinlikler.php" class="btn-primary">Etkinlikleri Keşfet</a>
            </div>
            
            <!-- Sepet Dolu Durumu -->
            <div id="cartItems" class="cart-items">
                <!-- Sepet öğeleri JavaScript ile doldurulacak -->
            </div>
            
            <!-- Sepet Özeti -->
            <div id="cartSummary" class="cart-summary" style="display: none;">
                <div class="summary-card">
                    <h3>Sipariş Özeti</h3>
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
                    <button id="proceedToPayment" class="btn-primary btn-full">Ödeme Sayfasına Geç</button>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
.cart-page {
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

.cart-content {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 2rem;
    max-width: 1200px;
    margin: 0 auto;
}

.empty-cart {
    grid-column: 1 / -1;
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 16px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.empty-cart-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.empty-cart h3 {
    color: #1a1a1a;
    margin-bottom: 1rem;
    font-size: 1.5rem;
}

.empty-cart p {
    color: #666;
    margin-bottom: 2rem;
    line-height: 1.6;
}

.cart-items {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.cart-item {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.cart-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

.item-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.event-info h4 {
    color: #1a1a1a;
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.event-details {
    color: #666;
    font-size: 0.9rem;
    line-height: 1.4;
}

.remove-item {
    background: none;
    border: none;
    color: #dc3545;
    font-size: 1.2rem;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 50%;
    transition: all 0.3s;
}

.remove-item:hover {
    background: #fee;
    transform: scale(1.1);
}

.ticket-info {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 12px;
    margin-bottom: 1rem;
}

.ticket-name {
    font-weight: 600;
    color: #1a1a1a;
    margin-bottom: 0.5rem;
}

.ticket-price {
    color: #00C896;
    font-weight: 600;
}

.quantity-controls {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.quantity-selector {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.quantity-btn {
    width: 32px;
    height: 32px;
    border: 1px solid #ddd;
    background: white;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.quantity-btn:hover {
    background: #f8f9fa;
    border-color: #667eea;
}

.quantity-input {
    width: 50px;
    height: 32px;
    text-align: center;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-weight: 600;
}

.item-total {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1a1a1a;
}

.cart-summary {
    position: sticky;
    top: 2rem;
    height: fit-content;
}

.summary-card {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.summary-card h3 {
    color: #1a1a1a;
    margin-bottom: 1rem;
    font-size: 1.2rem;
    font-weight: 600;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.summary-row.total {
    border-bottom: none;
    border-top: 2px solid #667eea;
    margin-top: 1rem;
    padding-top: 1rem;
    font-weight: 700;
    font-size: 1.1rem;
    color: #1a1a1a;
}

.btn-primary {
    background: #667eea;
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-block;
    text-align: center;
}

.btn-primary:hover {
    background: #5a6fd8;
    transform: translateY(-1px);
    color: white;
}

.btn-full {
    width: 100%;
    margin-top: 1rem;
}

@media (max-width: 768px) {
    .cart-content {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .cart-summary {
        position: static;
        order: -1;
    }
    
    .page-title {
        font-size: 2rem;
    }
}
</style>

<script>
let cart = [];

// Sayfa yüklendiğinde sepeti göster
document.addEventListener('DOMContentLoaded', function() {
    loadCart();
    displayCart();
});

function loadCart() {
    cart = JSON.parse(localStorage.getItem('cart') || '[]');
}

function saveCart() {
    localStorage.setItem('cart', JSON.stringify(cart));
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
        <div class="cart-item">
            <div class="item-header">
                <div class="event-info">
                    <h4>${item.eventTitle}</h4>
                    <div class="event-details">
                        📅 ${item.eventDate}<br>
                        📍 ${item.eventVenue}
                    </div>
                </div>
                <button class="remove-item" onclick="removeItem(${index})" title="Sepetten Kaldır">
                    ×
                </button>
            </div>
            
            <div class="ticket-info">
                <div class="ticket-name">${item.ticketName}</div>
                <div class="ticket-price">₺${item.ticketPrice.toLocaleString('tr-TR')} / bilet</div>
            </div>
            
            <div class="quantity-controls">
                <div class="quantity-selector">
                    <button class="quantity-btn" onclick="decreaseQuantity(${index})">-</button>
                    <input type="number" class="quantity-input" value="${item.quantity}" readonly>
                    <button class="quantity-btn" onclick="increaseQuantity(${index})">+</button>
                </div>
                <div class="item-total">₺${item.total.toLocaleString('tr-TR')}</div>
            </div>
        </div>
    `).join('');
    
    updateSummary();
}

function removeItem(index) {
    cart.splice(index, 1);
    saveCart();
    displayCart();
}

function increaseQuantity(index) {
    if (cart[index].quantity < 10) {
        cart[index].quantity++;
        cart[index].total = cart[index].ticketPrice * cart[index].quantity;
        saveCart();
        displayCart();
    }
}

function decreaseQuantity(index) {
    if (cart[index].quantity > 1) {
        cart[index].quantity--;
        cart[index].total = cart[index].ticketPrice * cart[index].quantity;
        saveCart();
        displayCart();
    }
}

function updateSummary() {
    const subtotal = cart.reduce((sum, item) => sum + item.total, 0);
    const serviceFee = Math.round(subtotal * 0.05); // %5 hizmet bedeli
    const grandTotal = subtotal + serviceFee;
    
    document.getElementById('subtotal').textContent = '₺' + subtotal.toLocaleString('tr-TR');
    document.getElementById('serviceFee').textContent = '₺' + serviceFee.toLocaleString('tr-TR');
    document.getElementById('grandTotal').textContent = '₺' + grandTotal.toLocaleString('tr-TR');
}

// Ödeme sayfasına geçiş
document.getElementById('proceedToPayment').addEventListener('click', function() {
    if (cart.length === 0) {
        alert('Sepetinizde ürün bulunmuyor!');
        return;
    }
    
    window.location.href = 'odeme.php';
});
</script>

<?php include 'includes/footer.php'; ?>