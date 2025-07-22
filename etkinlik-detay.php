<?php include 'includes/header.php'; ?>
<link rel="stylesheet" href="css/pages.css">

<main>
    <!-- Etkinlik Hero Section - Papilet Tarzı -->
    <section class="event-hero">
        <div class="container">
            <div class="event-hero-content">
                <!-- Sol Taraf: Etkinlik Görseli -->
                <div class="event-image-section">
                    <div class="event-main-image" id="eventMainImage">
                        <div class="event-category-tag" id="eventCategoryTag">Konser</div>
                    </div>
                </div>
                
                <!-- Sağ Taraf: Etkinlik Bilgileri -->
                <div class="event-info-section">
                    <h1 class="event-title" id="eventTitle">Kayseri Sancak Konseri</h1>
                    
                    <div class="event-info-grid">
                        <!-- Bilet Alma Kartı -->
                        <div class="price-section">
                            <h3>Biletler</h3>
                            <div class="event-performance">
                                <div class="performance-details">
                                    <div class="performance-location">
                                        <span class="location-icon">📍</span>
                                        <span class="location-name" id="eventLocation">Kayseri</span>
                                    </div>
                                    <div class="performance-venue">
                                        <span class="venue-icon">🏢</span>
                                        <span class="venue-name" id="eventVenue">Kültür Saçmaz</span>
                                    </div>
                                </div>
                                <div class="performance-datetime">
                                    <div class="performance-date">
                                        <span class="date-icon">📅</span>
                                        <span class="date-value" id="eventDate">24 Temmuz 2025</span>
                                    </div>
                                    <div class="performance-time">
                                        <span class="time-icon">🕒</span>
                                        <span class="time-value">20:00</span>
                                    </div>
                                </div>
                                <div class="price-info">
                                    <span class="price-value" id="eventPrice">450 ₺</span>
                                </div>
                                <div class="ticket-actions">
                                    <button class="btn-primary btn-buy-ticket">Satın Al</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Etkinlik Hakkında -->
                    <div class="event-about">
                        <div class="about-header">
                            <span class="about-icon">ℹ️</span>
                            <h3>Etkinlik Hakkında</h3>
                        </div>
                        <div class="about-content">
                            <p>Türk rap müziğinin güçlü isimlerinden Sancak, sahne enerjisi ve sevilen şarkılarıyla hayranlarıyla buluşuyor! Duygusal şarkıları ve kendine özgü tarzıyla...</p>
                            <button class="btn-read-more">Devamını oku <i class="fas fa-chevron-down"></i></button>
                        </div>
                    </div>
                    
                    <!-- Sanatçılar -->
                    <div class="event-artists">
                        <div class="artists-header">
                            <span class="artists-icon">🎤</span>
                            <h3>Sanatçılar</h3>
                        </div>
                        <div class="artists-list">
                            <div class="artist-item">
                                <div class="artist-avatar">
                                    <img src="uploads/artist-avatar.jpg" alt="Sancak" id="artistImage">
                                </div>
                                <span class="artist-name" id="artistName">Sancak</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Etkinlik Kuralları ve Mekan Bilgileri -->
    <section class="event-details-section">
        <div class="container">
            <div class="details-wrapper">
                <div class="details-card">
                    <h3>Etkinlik Kuralları</h3>
                    <ul class="rules-list">
                        <li>Yaş sınırı yoktur</li>
                        <li>E-biletiniz tarafınıza mail ve SMS olarak iletilecektir</li>
                        <li>Çıktı almanıza gerek yoktur</li>
                        <li>Satın alınan biletlerde iptal, iade ve değişiklik yapılmamaktadır</li>
                        <li>Etkinlik girişinde bilet kontrolü yapılacaktır</li>
                    </ul>
                </div>
                
                <div class="details-card">
                    <h3>Mekan Bilgileri</h3>
                    <div class="venue-info">
                        <p><strong id="venueNameSidebar">Kültür Saçmaz</strong></p>
                        <p class="venue-address">Kayseri</p>
                        <button class="btn-map">📍 Haritada Göster</button>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Benzer Etkinlikler -->
    <section class="similar-events">
        <div class="container">
            <h2>Benzer Etkinlikler</h2>
            <div class="similar-events-grid">
                
            </div>
        </div>
    </section>
</main>

<script>
// URL parametrelerinden etkinlik bilgilerini al
const urlParams = new URLSearchParams(window.location.search);
const eventData = {
    title: urlParams.get('title') || 'Etkinlik Adı',
    date: urlParams.get('date') || 'Tarih',
    venue: urlParams.get('venue') || 'Mekan',
    location: urlParams.get('location') || 'Şehir',
    price: urlParams.get('price') || 'Fiyat',
    category: urlParams.get('category') || 'Kategori',
    imageBg: urlParams.get('imageBg') || 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'
};

// Sayfa yüklendiğinde etkinlik bilgilerini güncelle
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('eventTitle').textContent = eventData.title;
    document.getElementById('eventDate').textContent = eventData.date;
    document.getElementById('eventVenue').textContent = eventData.venue;
    document.getElementById('eventLocation').textContent = eventData.location;
    document.getElementById('eventPrice').textContent = eventData.price;
    document.getElementById('eventCategoryTag').textContent = eventData.category;
    document.getElementById('venueNameSidebar').textContent = eventData.venue;
    document.getElementById('artistName').textContent = eventData.title.split(' ')[0] + ' ' + (eventData.title.split(' ')[1] || '');
    document.getElementById('eventMainImage').style.background = `linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.7)), ${eventData.imageBg}`;
    
    // Sayfa başlığını güncelle
    document.title = `${eventData.title} - BiletJack`;
    
    // Geri butonu işlevi
    document.querySelector('.btn-back').addEventListener('click', function() {
        window.history.back();
    });
});
</script>

<?php include 'includes/footer.php'; ?>