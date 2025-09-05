<?php
// Admin ayarlarından sosyal medya URL'lerini al
require_once __DIR__ . '/../config/database.php';
$database = new Database();
$pdo = $database->getConnection();

$socialUrls = [];
try {
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('facebook_url', 'instagram_url', 'twitter_url')");
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $socialUrls[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    // Hata durumunda boş array kullan
}
?>

<footer class="footer">
    <div class="footer-container">
        <div class="footer-row">
            <div class="footer-col">
                <div class="footer-logo">
                    <img src="./uploads/logo.png" alt="BiletJack Logo">
                </div>
                <p class="footer-about">BiletJack, Türkiye'nin önde gelen online bilet satış platformudur. Konser, tiyatro, festival ve daha birçok etkinlik için biletlerinizi güvenle satın alabilirsiniz.</p>
                <div class="footer-social">
                    <?php if (!empty($socialUrls['instagram_url'])): ?>
                    <a href="<?php echo htmlspecialchars($socialUrls['instagram_url']); ?>" class="footer-social-icon" title="Instagram" target="_blank" rel="noopener noreferrer">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
                        </svg>
                    </a>
                    <?php endif; ?>
                    <?php if (!empty($socialUrls['facebook_url'])): ?>
                    <a href="<?php echo htmlspecialchars($socialUrls['facebook_url']); ?>" class="footer-social-icon" title="Facebook" target="_blank" rel="noopener noreferrer">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                    </a>
                    <?php endif; ?>
                    <?php if (!empty($socialUrls['twitter_url'])): ?>
                    <a href="<?php echo htmlspecialchars($socialUrls['twitter_url']); ?>" class="footer-social-icon" title="Twitter/X" target="_blank" rel="noopener noreferrer">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                        </svg>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="footer-col">
                <h3>Hızlı Erişim</h3>
                <ul class="footer-links">
                    <li><a href="index.php">Ana Sayfa</a></li>
                    <li><a href="etkinlikler.php?category=1">Konserler</a></li>
                    <li><a href="etkinlikler.php?category=3">Tiyatrolar</a></li>
                    <li><a href="etkinlikler.php?category=6">Festivaller</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h3>Kurumsal</h3>
                <ul class="footer-links">
                    <li><a href="hakkimizda.php">Hakkımızda</a></li>
                    <li><a href="iletisim.php">İletişim</a></li>
                    <li><a href="bize-katilin.php">Bize Katılın (Hizmet Sağlayıcı)</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h3>Yardım</h3>
                <ul class="footer-links">
                    <li><a href="bilet-iptal-iade.php">Bilet İptal/İade</a></li>
                    <li><a href="gizlilik-politikasi.php">Gizlilik Politikası</a></li>
                    <li><a href="cerez-politikasi.php">Çerez Politikası</a></li>
                    <li><a href="kvkk.php">KVKK</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 BiletJack. Tüm hakları saklıdır. | Güvenli ödeme sistemi ile biletinizi hemen alın!</p>
            <div class="payment-methods">
                <svg width="40" height="25" viewBox="0 0 40 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect width="40" height="25" rx="3" fill="#1A1F71"/>
                    <text x="20" y="16" text-anchor="middle" fill="white" font-family="Arial" font-size="8" font-weight="bold">VISA</text>
                </svg>
                <svg width="40" height="25" viewBox="0 0 40 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect width="40" height="25" rx="3" fill="#EB001B"/>
                    <circle cx="15" cy="12.5" r="8" fill="#FF5F00"/>
                    <circle cx="25" cy="12.5" r="8" fill="#F79E1B"/>
                </svg>
                <svg width="40" height="25" viewBox="0 0 40 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect width="40" height="25" rx="3" fill="#00A651"/>
                    <text x="20" y="16" text-anchor="middle" fill="white" font-family="Arial" font-size="7" font-weight="bold">TROY</text>
                </svg>
            </div>
        </div>
    </div>
</footer>

<!-- Çerez Popup -->
<div id="cookieConsent" class="cookie-consent" style="display: none;">
    <div class="cookie-content">
        <div class="cookie-icon">
            🍪
        </div>
        <div class="cookie-text">
            <h3>Çerezleri Kabul Et</h3>
            <p>Web sitemizde size en iyi deneyimi sunabilmek için çerezleri kullanıyoruz. Siteyi kullanmaya devam ederek çerez kullanımını kabul etmiş olursunuz.</p>
        </div>
        <div class="cookie-buttons">
            <button onclick="acceptCookies()" class="cookie-btn accept">Kabul Et</button>
            <button onclick="declineCookies()" class="cookie-btn decline">Reddet</button>
            <a href="cerez-politikasi.php" class="cookie-link">Detaylar</a>
        </div>
    </div>
</div>

<style>
    .footer {
        background-color: #121212;
        color: #f5f5f5;
        padding: 60px 0 30px;
        margin-top: 50px;
    }

    .footer-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .footer-row {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        margin-bottom: 40px;
    }

    .footer-col {
        flex: 1;
        min-width: 200px;
        margin-bottom: 30px;
        padding-right: 20px;
    }

    .footer-logo {
        margin-bottom: 20px;
    }

    .footer-logo img {
        height: 40px;
        filter: brightness(0) invert(1);
    }

    .footer-about {
        font-size: 0.9rem;
        line-height: 1.6;
        margin-bottom: 20px;
        color: #aaa;
    }

    .footer-social {
        display: flex;
        gap: 15px;
    }

    .footer-social-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background-color: rgba(255, 255, 255, 0.1);
        color: #f5f5f5;
        transition: all 0.3s ease;
    }

    .footer-social-icon:hover {
        background-color: rgba(255, 255, 255, 0.2);
        transform: translateY(-3px);
    }

    .footer-col h3 {
        font-size: 1.2rem;
        margin-bottom: 20px;
        color: #fff;
        font-weight: 600;
    }

    .footer-links {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .footer-links li {
        margin-bottom: 10px;
    }

    .footer-links a {
        color: #aaa;
        text-decoration: none;
        font-size: 0.9rem;
        transition: color 0.3s ease;
    }

    .footer-links a:hover {
        color: #fff;
    }

    .footer-bottom {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 30px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        flex-wrap: wrap;
    }

    .footer-bottom p {
        font-size: 0.85rem;
        color: #888;
    }

    .payment-methods {
        display: flex;
        gap: 10px;
    }

    /* Çerez Popup Stilleri */
    .cookie-consent {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: rgba(0, 0, 0, 0.95);
        backdrop-filter: blur(10px);
        z-index: 10000;
        padding: 20px;
        border-top: 2px solid #E91E63;
        animation: slideUp 0.5s ease-out;
    }

    @keyframes slideUp {
        from {
            transform: translateY(100%);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .cookie-content {
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        align-items: center;
        gap: 20px;
        flex-wrap: wrap;
    }

    .cookie-icon {
        font-size: 2rem;
        flex-shrink: 0;
    }

    .cookie-text {
        flex: 1;
        min-width: 300px;
    }

    .cookie-text h3 {
        color: #fff;
        font-size: 1.2rem;
        margin: 0 0 8px 0;
        font-weight: 600;
    }

    .cookie-text p {
        color: #ccc;
        font-size: 0.9rem;
        margin: 0;
        line-height: 1.5;
    }

    .cookie-buttons {
        display: flex;
        gap: 12px;
        align-items: center;
        flex-wrap: wrap;
    }

    .cookie-btn {
        padding: 10px 20px;
        border: none;
        border-radius: 25px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 0.9rem;
    }

    .cookie-btn.accept {
        background: #E91E63;
        color: white;
    }

    .cookie-btn.accept:hover {
        background: #C2185B;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(233, 30, 99, 0.4);
    }

    .cookie-btn.decline {
        background: transparent;
        color: #ccc;
        border: 1px solid #555;
    }

    .cookie-btn.decline:hover {
        background: #333;
        color: #fff;
        border-color: #777;
    }

    .cookie-link {
        color: #E91E63;
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 500;
        transition: color 0.3s ease;
    }

    .cookie-link:hover {
        color: #F8BBD9;
        text-decoration: underline;
    }

    @media (max-width: 768px) {
        .cookie-content {
            flex-direction: column;
            text-align: center;
            gap: 15px;
        }

        .cookie-text {
            min-width: auto;
        }

        .cookie-buttons {
            justify-content: center;
            width: 100%;
        }

        .cookie-btn {
            flex: 1;
            min-width: 100px;
        }

        .footer-row {
            flex-direction: column;
        }

        .footer-col {
            width: 100%;
            padding-right: 0;
        }

        .footer-bottom {
            flex-direction: column;
            text-align: center;
            gap: 15px;
        }

        .payment-methods {
            justify-content: center;
        }
    }
</style>

<script>
    // Form validasyonu
    document.querySelector('.search-form')?.addEventListener('submit', function(e) {
        const city = document.getElementById('city')?.value;
        const eventType = document.getElementById('event-type')?.value;
        
        if (!city && !eventType) {
            e.preventDefault();
            alert('Lütfen en az şehir veya etkinlik türü seçin.');
        }
    });

    // Bilet satın alma butonları
    document.querySelectorAll('.buy-btn').forEach(button => {
        button.addEventListener('click', function() {
            alert('Bilet satın alma sayfasına yönlendiriliyorsunuz...');
            // Burada gerçek bilet satın alma sayfasına yönlendirme yapılacak
        });
    });

    // Bugünün tarihini minimum tarih olarak ayarla
    const dateInput = document.getElementById('date');
    if (dateInput) {
        dateInput.min = new Date().toISOString().split('T')[0];
    }
</script>
</body>
</html>

<?php
// Sadece genel sayfalarda alt gezinme çubuğunu göster
$__path = $_SERVER['PHP_SELF'] ?? '';
$__hideForPanels = preg_match('#/(admin|organizer|service_provider|ad_agency|customer|qr_panel)/#', $__path);
if (!$__hideForPanels):
?>
<style>
@media (max-width: 768px) {
  body {
    padding-bottom: calc(72px + env(safe-area-inset-bottom, 0px));
  }
  .mobile-bottom-nav {
    position: fixed;
    left: 12px;
    right: 12px;
    bottom: calc(10px + env(safe-area-inset-bottom, 0px));
    z-index: 9000; /* 9999 -> 9000: Sidebar (9500) üstte kalsın */
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #121330;
    border-radius: 16px;
    padding: 8px 10px;
    box-shadow: 0 8px 28px rgba(0,0,0,0.35);
    color: #fff;
  }
  .mobile-nav-item {
    flex: 1;
    background: transparent;
    border: 0;
    color: #cfd2e3;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    line-height: 1;
    cursor: pointer;
    -webkit-tap-highlight-color: transparent; /* mobil tap highlight'ı kapat */
    outline: none; /* focus outline'ı kapat */
  }
  /* İkon: inline SVG, currentColor ile renklenecek */
  .mobile-nav-item .icon svg {
    width: 22px;
    height: 22px;
    display: block;
  }
  /* İç elemanlara tıklama butona gitsin (yazıya/icon'a tıklayınca da çalışsın) */
  .mobile-nav-item .icon,
  .mobile-nav-item span {
    pointer-events: none;
  }
  .mobile-nav-item:active {
    transform: translateY(1px);
    /* Renk değiştirme yok */
  }
  .mobile-nav-item.active,
  .mobile-nav-item:hover {
    color: inherit; /* Sarı yerine mevcut rengi koru */
  }
  .mobile-nav-item:focus,
  .mobile-nav-item:focus-visible {
    outline: none;
    box-shadow: none;
  }

  .mobile-search-bar {
    position: fixed;
    left: 20px;
    right: 20px;
    bottom: calc(80px + env(safe-area-inset-bottom, 0px));
    z-index: 10000; /* Modallar 10000/20000 seviyesinde; aynı/daha üstte kalabilir */
    background: #1a1b3a;
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 12px;
    padding: 8px;
    display: none;
    box-shadow: 0 10px 30px rgba(0,0,0,0.35);
  }
  .mobile-search-bar.active { display: flex; gap: 8px; }
  .mobile-search-bar input {
    flex: 1;
    background: #0f1027;
    border: 1px solid rgba(255,255,255,0.06);
    color: #fff;
    padding: 10px 12px;
    border-radius: 10px;
    font-size: 14px;
    outline: none;
  }
  .mobile-search-bar button {
    background: #ffb84d;
    color: #121330;
    border: 0;
    padding: 10px 14px;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
  }
}
@media (min-width: 769px) {
  .mobile-bottom-nav,
  .mobile-search-bar { display: none !important; }
}
</style>

<div class="mobile-bottom-nav" id="mobileBottomNav" aria-label="Alt gezinti">
  <button class="mobile-nav-item" id="btnHome" aria-label="Ana sayfa">
    <span class="icon" aria-hidden="true"><?php echo file_get_contents(__DIR__ . '/../SVG/home.svg'); ?></span>
    <span>Ana Sayfa</span>
  </button>
  <button class="mobile-nav-item" id="btnSearch" aria-label="Ara">
    <span class="icon" aria-hidden="true"><?php echo file_get_contents(__DIR__ . '/../SVG/search.svg'); ?></span>
    <span>Ara</span>
  </button>
  <button class="mobile-nav-item" id="btnTickets" aria-label="Biletlerim">
    <span class="icon" aria-hidden="true"><?php echo file_get_contents(__DIR__ . '/../SVG/tickets.svg'); ?></span>
    <span>Biletlerim</span>
  </button>
  <button class="mobile-nav-item" id="btnFavorites" aria-label="Favorilerim">
    <span class="icon" aria-hidden="true"><?php echo file_get_contents(__DIR__ . '/../SVG/favorites.svg'); ?></span>
    <span>Favorilerim</span>
  </button>
  <button class="mobile-nav-item" id="btnProfile" aria-label="Profil">
    <span class="icon" aria-hidden="true"><?php echo file_get_contents(__DIR__ . '/../SVG/profile.svg'); ?></span>
    <span>Profil</span>
  </button>
</div>

<div class="mobile-search-bar" id="mobileSearchBar" role="search" aria-label="Etkinlik arama">
  <input type="text" id="mobileSearchInput" placeholder="Etkinlik ara..." autocomplete="off" />
  <button id="mobileSearchGo" type="button">Ara</button>
</div>

<!-- Favoriler Modal -->
<div id="favoritesModal" class="modal">
  <div class="modal-overlay" onclick="closeModal('favoritesModal')"></div>
  <div class="modal-content">
    <div class="modal-header">
      <h2>Favorilerim</h2>
      <button class="modal-close" onclick="closeModal('favoritesModal')" aria-label="Kapat">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
          <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
        </svg>
      </button>
    </div>
    <div class="modal-body" id="favoritesList">
      <div style="text-align:center; padding:20px; color:#666;">Favoriler yükleniyor...</div>
    </div>
  </div>
</div>

<script>
// Mobil alt gezinme - davranışlar
(function() {
  const isLoggedIn = <?php echo json_encode(isset($_SESSION['user_id'])); ?>;
  const userType   = <?php echo json_encode($_SESSION['user_type'] ?? null); ?>;

  const btnHome      = document.getElementById('btnHome');
  const btnSearch    = document.getElementById('btnSearch');
  const btnTickets   = document.getElementById('btnTickets');
  const btnFavorites = document.getElementById('btnFavorites');
  const btnProfile   = document.getElementById('btnProfile');

  const searchBar   = document.getElementById('mobileSearchBar');
  const searchInput = document.getElementById('mobileSearchInput');
  const searchGo    = document.getElementById('mobileSearchGo');

  function navigate(url) { window.location.href = url; }

  function toggleSearch() {
    searchBar.classList.toggle('active');
    if (searchBar.classList.contains('active')) {
      setTimeout(() => searchInput && searchInput.focus(), 50);
    }
  }

  function performSearch() {
    const q = (searchInput?.value || '').trim();
    if (!q) { searchBar.classList.remove('active'); return; }
    navigate('etkinlikler.php?search=' + encodeURIComponent(q));
  }

  function requireCustomerOrLogin(targetUrl) {
    if (!isLoggedIn || userType !== 'customer') {
      if (typeof openModal === 'function') {
        openModal('loginModal');
      } else {
        navigate('auth/login.php');
      }
      return;
    }
    navigate(targetUrl);
  }

  // Event bağlama
  btnHome?.addEventListener('click', () => navigate('index.php'));
  btnSearch?.addEventListener('click', (e) => { e.stopPropagation(); toggleSearch(); });
  searchGo?.addEventListener('click', performSearch);
  searchInput?.addEventListener('keydown', (e) => { if (e.key === 'Enter') performSearch(); });

  btnTickets?.addEventListener('click', () => requireCustomerOrLogin('customer/tickets.php'));
  btnProfile?.addEventListener('click', () => requireCustomerOrLogin('customer/index.php'));

  // Favoriler: modal aç/kapat ve listele
  btnFavorites?.addEventListener('click', async () => {
    if (!isLoggedIn || userType !== 'customer') {
      if (typeof openModal === 'function') openModal('loginModal'); else navigate('auth/login.php');
      return;
    }
    try {
      const listEl = document.getElementById('favoritesList');
      listEl.innerHTML = '<div style="text-align:center; padding:20px; color:#666;">Favoriler yükleniyor...</div>';
      if (typeof openModal === 'function') openModal('favoritesModal');
      const res = await fetch('ajax/get_favorites.php', { credentials: 'same-origin' });
      const data = await res.json();
      if (!data.success) throw new Error(data.message || 'Favoriler alınamadı');
      if (!data.items.length) {
        listEl.innerHTML = '<div style="text-align:center; padding:24px; color:#888;">Henüz favori etkinliğiniz yok.</div>';
        return;
      }
      listEl.innerHTML = data.items.map(ev => `
        <div class="event-card" style="margin-bottom:12px; cursor:pointer;" onclick="window.location.href='etkinlik-detay.php?id=${ev.id}'">
          <div class="event-image" style="height:140px; background: ${ev.image_url ? `url(${ev.image_url}) center/cover` : 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'};">
          </div>
          <div class="event-content">
            <h3 class="event-title" style="font-size:16px; margin:8px 0;">${ev.title}</h3>
            <p class="event-venue" style="margin:0; color:#777;">${ev.venue_name || ''}</p>
            <p class="event-date" style="margin:4px 0; color:#999;">${ev.event_date_human || ''}</p>
          </div>
        </div>
      `).join('');
    } catch (err) {
      alert(err.message || 'Favoriler alınırken hata oluştu');
    }
  });

  // Sayfa genelinde kalp butonu tıklaması (delegation)
  document.addEventListener('click', async (e) => {
    const favBtn = e.target.closest('.favorite-btn');
    if (!favBtn) return;
    e.preventDefault();
    e.stopPropagation();
    e.stopImmediatePropagation();

    const eventId = favBtn.getAttribute('data-event-id');
    if (!eventId) return;

    if (!isLoggedIn || userType !== 'customer') {
      if (typeof openModal === 'function') openModal('loginModal'); else navigate('auth/login.php');
      return;
    }

    try {
      const res = await fetch('ajax/toggle_favorite.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        credentials: 'same-origin',
        body: 'event_id=' + encodeURIComponent(eventId)
      });
      const data = await res.json();
      if (!data.success) throw new Error(data.message || 'İşlem başarısız');
      favBtn.classList.toggle('active', !!data.favorited);
      favBtn.setAttribute('aria-pressed', data.favorited ? 'true' : 'false');
    } catch (err) {
      alert(err.message || 'Bir hata oluştu');
    }
  });

  // Dışarı tıklanınca aramayı kapat
  document.addEventListener('click', (e) => {
    if (!searchBar?.contains(e.target) && !btnSearch?.contains(e.target)) {
      searchBar?.classList.remove('active');
    }
  });
})();
</script>
<?php endif; ?>

<!-- Çerez Popup JavaScript -->
<script>
// Çerez popup'ı kontrol et
function checkCookieConsent() {
    const cookieConsent = localStorage.getItem('cookieConsent');
    if (!cookieConsent) {
        setTimeout(() => {
            document.getElementById('cookieConsent').style.display = 'block';
        }, 1000); // 1 saniye sonra göster
    }
}

// Çerezleri kabul et
function acceptCookies() {
    localStorage.setItem('cookieConsent', 'accepted');
    localStorage.setItem('cookieConsentDate', new Date().toISOString());
    document.getElementById('cookieConsent').style.display = 'none';
    
    // Google Analytics veya diğer tracking kodları burada etkinleştirilebilir
    console.log('Çerezler kabul edildi');
}

// Çerezleri reddet
function declineCookies() {
    localStorage.setItem('cookieConsent', 'declined');
    localStorage.setItem('cookieConsentDate', new Date().toISOString());
    document.getElementById('cookieConsent').style.display = 'none';
    
    // Sadece zorunlu çerezleri kullan
    console.log('Çerezler reddedildi');
}

// Sayfa yüklendiğinde çerez popup'ını kontrol et
document.addEventListener('DOMContentLoaded', function() {
    checkCookieConsent();
});
</script>

<?php
// ... existing code ...
?>