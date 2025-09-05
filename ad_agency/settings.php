<?php
require_once __DIR__ . '/../includes/session.php';
requireAdAgency();
$currentUser = getCurrentUser();
include __DIR__ . '/../includes/header.php';
?>
<div class="aa-panel">
    <h1 class="aa-title">Profil Ayarları</h1>
    <p class="aa-subtitle">Ajans bilgilerinizi güncelleyin.</p>

    <div class="aa-card aa-section">
        <form class="aa-form" method="post" action="#">
            <div class="aa-form-group">
                <label for="agency_name">Ajans Adı</label>
                <input type="text" id="agency_name" name="agency_name" placeholder="Örn: ABC Reklam">
            </div>
            <div class="aa-form-group">
                <label for="contact_name">İlgili Kişi</label>
                <input type="text" id="contact_name" name="contact_name" placeholder="Örn: Ali Veli">
            </div>
            <div class="aa-form-group">
                <label for="email">E-posta</label>
                <input type="email" id="email" name="email" placeholder="ornek@ajans.com">
            </div>
            <div class="aa-form-group">
                <label for="phone">Telefon</label>
                <input type="tel" id="phone" name="phone" placeholder="+90 5xx xxx xx xx">
            </div>
            <div class="aa-form-group" style="grid-column: 1 / -1;">
                <label for="about">Hakkında</label>
                <textarea id="about" name="about" placeholder="Ajans hakkında kısa bilgi..."></textarea>
            </div>
            <div class="aa-actions" style="grid-column: 1 / -1;">
                <button type="submit" class="aa-btn">Kaydet</button>
                <a class="aa-btn secondary" href="index.php">Panele Dön</a>
            </div>
        </form>
    </div>

    <div class="aa-alert info">
        Bu sayfadaki alanlar mobilde tek sütun olacak şekilde otomatik uyum sağlar.
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>