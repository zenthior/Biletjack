<?php
require_once 'includes/header.php';
?>
<div class="join-hero">
    <div class="join-hero-content">
        <h1>Hizmet Sağlayıcı Ağına Katılın</h1>
        <p>Ses, görüntü, ışık, sahne ve benzeri hizmetler sunuyorsanız; BiletJack organizatör ekosistemine katılın.
           Şehrinizde etkinlik planlayan organizatörlerle kolayca eşleşin.</p>
        <button class="join-btn" id="openJoinModal">BiletJack’e Kayıt Ol</button>
    </div>
</div>

<section class="join-info">
    <div class="join-info-grid">
        <div class="join-card">
            <h3>Organizatörlere Ulaşın</h3>
            <p>Etkinlik tarihi yaklaşan organizatörler ihtiyaç duyduğu hizmeti kolayca bulsun.</p>
        </div>
        <div class="join-card">
            <h3>Görünürlüğünüzü Artırın</h3>
            <p>Hizmetlerinizi detaylı tanımlayın, ekipmanlarınızı ve referanslarınızı paylaşın.</p>
        </div>
        <div class="join-card">
            <h3>Şehir Bazlı Eşleşme</h3>
            <p>Hizmet verdiğiniz şehir ve bölgeleri belirtin, doğru talep size gelsin.</p>
        </div>
    </div>
</section>

<!-- Kayıt Modal -->
<div id="joinModal" class="join-modal" style="display:none;">
    <div class="join-modal-content">
        <div class="join-modal-header">
            <h3>Hizmet Sağlayıcı Kaydı</h3>
            <button class="join-modal-close" id="closeJoinModal">&times;</button>
        </div>
        <div class="join-modal-body">
            <!-- Kayıt Türü Seçici -->
            <div class="register-type-toggle" style="display:flex; gap:8px; margin-bottom:12px;">
                <button type="button" class="rt-btn active" data-type="service">Ses / Işık / Stüdyo</button>
                <button type="button" class="rt-btn" data-type="ad_agency">PR Ekibi (Reklam Ajansı)</button>
            </div>

            <form id="serviceProviderForm">
                <input type="hidden" name="register_type" id="register_type" value="service">
                <h4>Firma ve İletişim</h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Firma Adı*</label>
                        <input type="text" name="company_name" required>
                    </div>
                    <div class="form-group">
                        <label>İlgili Kişi Adı*</label>
                        <input type="text" name="contact_first_name" required>
                    </div>
                    <div class="form-group">
                        <label>İlgili Kişi Soyadı*</label>
                        <input type="text" name="contact_last_name" required>
                    </div>
                    <div class="form-group">
                        <label>E-posta*</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Telefon*</label>
                        <input type="text" name="phone" required>
                    </div>
                    <div class="form-group">
                        <label>Şehir*</label>
                        <input type="text" name="city" required>
                    </div>
                    <div class="form-group">
                        <label>Hizmet Verilen Bölgeler</label>
                        <input type="text" name="regions" placeholder="İlçe/İller (virgülle ayırınız)">
                    </div>
                </div>

                <!-- Hizmet & Ekipman (sadece hizmet sağlayıcı) -->
                <h4 class="only-service">Hizmet ve Ekipman</h4>
                <div class="form-grid only-service">
                    <div class="form-group">
                        <label>Hizmet Kategorileri*</label>
                        <div class="checkbox-group">
                            <label><input type="checkbox" name="services[]" value="Ses"> Ses</label>
                            <label><input type="checkbox" name="services[]" value="Işık"> Işık</label>
                            <label><input type="checkbox" name="services[]" value="Görüntü"> Görüntü</label>
                            <label><input type="checkbox" name="services[]" value="Sahne"> Sahne</label>
                            <label><input type="checkbox" name="services[]" value="DJ"> DJ</label>
                            <label><input type="checkbox" name="services[]" value="Kiralama"> Kiralama</label>
                        </div>
                    </div>
                    <div class="form-group full">
                        <label>Ekipman Listesi*</label>
                        <textarea name="equipment_list" rows="3" required placeholder="Başlıca ekipmanlarınızı yazın"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Deneyim (Yıl)*</label>
                        <input type="number" name="experience_years" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Portfolyo/Website</label>
                        <input type="url" name="portfolio_url" placeholder="https://...">
                    </div>
                    <div class="form-group">
                        <label>Instagram</label>
                        <input type="text" name="instagram" placeholder="@kullaniciadi">
                    </div>
                </div>

                <!-- PR Ekibi (Reklam Ajansı) alanları -->
                <h4 class="only-agency" style="display:none;">Ajans Hizmetleri</h4>
                <div class="form-grid only-agency" style="display:none;">
                    <div class="form-group">
                        <label>Reklam Kanalları*</label>
                        <div class="checkbox-group">
                            <label><input type="checkbox" name="channels[]" value="Dijital Reklam"> Dijital Reklam (Google/Meta)</label>
                            <label><input type="checkbox" name="channels[]" value="Sosyal Medya Yönetimi"> Sosyal Medya Yönetimi</label>
                            <label><input type="checkbox" name="channels[]" value="Influencer"> Influencer İşbirliği</label>
                            <label><input type="checkbox" name="channels[]" value="PR & Basın"> PR & Basın</label>
                            <label><input type="checkbox" name="channels[]" value="Outdoor"> Outdoor/OOH</label>
                            <label><input type="checkbox" name="channels[]" value="Kreatif"> Kreatif/İçerik Üretimi</label>
                            <label><input type="checkbox" name="channels[]" value="Medya Planlama"> Medya Planlama/Satın Alma</label>
                            <label><input type="checkbox" name="channels[]" value="SEO/SEM"> SEO/SEM</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Ajans Websitesi</label>
                        <input type="url" name="portfolio_url" placeholder="https://...">
                    </div>
                    <div class="form-group">
                        <label>Instagram</label>
                        <input type="text" name="instagram" placeholder="@ajans">
                    </div>
                </div>

                <h4>Adres ve Vergi</h4>
                <div class="form-grid">
                    <div class="form-group full">
                        <label>Adres*</label>
                        <textarea name="address" rows="2" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Vergi Numarası*</label>
                        <input type="text" name="tax_number" required>
                    </div>
                    <div class="form-group only-service">
                        <label>7/24 Uygun</label>
                        <select name="availability_24_7">
                            <option value="0">Hayır</option>
                            <option value="1">Evet</option>
                        </select>
                    </div>
                    <div class="form-group full">
                        <label>Ek Notlar</label>
                        <textarea name="notes" rows="2" placeholder="Varsa eklemek istedikleriniz"></textarea>
                    </div>
                </div>

                <h4>Hesap Bilgileri</h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Şifre*</label>
                        <input type="password" name="password" required class="pw-meter" data-require-strength="medium" data-confirm="#password_confirm">
                        <div class="pw-strength" style="margin-top:6px;">
                            <div class="pw-bar" style="height:6px;width:0%;background:#ddd;border-radius:4px;transition:width .2s ease;"></div>
                            <div class="pw-text" style="margin-top:6px;font-size:12px;color:#666;">Şifre gücü: - (En az orta seviye gerekir. 8+ karakter, en az iki tür: küçük/büyük/rakam)</div>
                        </div>
                        <!-- Fazladan şifre tekrar alanı buradan kaldırıldı -->
                    </div>
                    <div class="form-group">
                        <label>Şifre (Tekrar)*</label>
                        <input type="password" name="password_confirm" id="password_confirm" required>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-secondary" id="cancelJoin">Vazgeç</button>
                    <button type="submit" class="btn-primary">Kaydı Tamamla</button>
                </div>
                <div id="formMessage" class="form-message" style="display:none;"></div>
            </form>
        </div>
    </div>
</div>

<style>
.join-hero { background: #07074dc7; color:#fff; padding:70px 20px; text-align:center; }
.join-hero-content h1 { font-size: 2rem; margin-bottom: 10px; }
.join-hero-content p { max-width: 800px; margin: 0 auto 20px; color:#bbb; }
.join-btn { background:#ff3d71; color:#fff; border:none; padding:12px 20px; border-radius:8px; cursor:pointer; }
.join-info { padding:40px 20px; }
.join-info-grid { display:grid; gap:16px; grid-template-columns: repeat(auto-fit, minmax(220px,1fr)); max-width:1100px; margin:0 auto; }
.join-card { background:#268b4aa3; color:#eaeaea; border-radius:10px; padding:20px; border:1px solid #27272f; }
.join-modal { position:fixed; inset:0; background:rgba(0,0,0,.6); display:flex; align-items:center; justify-content:center; z-index:1000; }
.join-modal-content { background:#232f35; color:#eee; border-radius:12px; width:95%; max-width:900px; border:1px solid #d4d4df; }
.join-modal-header { display:flex; align-items:center; justify-content:space-between; padding:16px 20px; border-bottom:1px solid #66bf48; }
.join-modal-body { padding:20px; max-height:75vh; overflow:auto; }
.join-modal-close { background:transparent; border:none; color:#aaa; font-size:24px; cursor:pointer; }
.form-grid { display:grid; gap:12px; grid-template-columns: repeat(2, 1fr); }
.form-group { display:flex; flex-direction:column; gap:6px; }
.form-group.full { grid-column: 1 / -1; }
.form-group label { font-weight: 600; margin-bottom: 0.5rem; color: #939393; }
.form-group input, .form-group textarea, .form-group select { background:#00000094; border:1px solid #000000; color:#eaeaea; border-radius:8px; padding:10px; }
.checkbox-group { display:flex; gap:12px; flex-wrap:wrap; }
.form-actions { display:flex; justify-content:flex-end; gap:10px; margin-top:10px; }
.btn-secondary { background:#2a2a35; color:#ddd; border:none; padding:10px 16px; border-radius:8px; cursor:pointer; }
.btn-primary { background:#22c55e; color:#0b0b0e; border:none; padding:10px 16px; border-radius:8px; cursor:pointer; }
.form-message { margin-top:10px; padding:10px; border-radius:8px; font-size:.95rem; }
.form-message.success { background:#102818; color:#7ce78b; border:1px solid #1f5130; }
.form-message.error { background:#2a1212; color:#ff9e9e; border:1px solid #5a1a1a; }
@media (max-width: 768px) { .form-grid { grid-template-columns: 1fr; } }

/* Toggle butonları için aktif/pasif stiller */
.rt-btn { 
    flex:1; padding:10px; border-radius:8px; 
    border:1px solid #66bf48; 
    background:#1d2630; color:#cfe3ff; 
    cursor:pointer; 
    transition: background .2s ease, color .2s ease, border-color .2s ease;
}
.rt-btn.active {
    background:#214030; color:#d6f5d6; border-color:#66bf48;
}
</style>

<script>
document.getElementById('openJoinModal').addEventListener('click', () => {
    document.getElementById('joinModal').style.display = 'flex';
});
document.getElementById('closeJoinModal').addEventListener('click', () => {
    document.getElementById('joinModal').style.display = 'none';
});
document.getElementById('cancelJoin').addEventListener('click', () => {
    document.getElementById('joinModal').style.display = 'none';
});

// Tür seçici
(function(){
    const buttons = document.querySelectorAll('.rt-btn');
    const hidden = document.getElementById('register_type');
    function setType(t){
        hidden.value = t;
        buttons.forEach(b => b.classList.toggle('active', b.dataset.type === t));
        document.querySelectorAll('.only-service').forEach(el => el.style.display = (t === 'service' ? '' : 'none'));
        document.querySelectorAll('.only-agency').forEach(el => el.style.display = (t === 'ad_agency' ? '' : 'none'));
        // Zorunlulukları dinamik ayarla
        const equip = document.querySelector('[name="equipment_list"]');
        const exp = document.querySelector('[name="experience_years"]');
        if (equip) equip.required = (t === 'service');
        if (exp) exp.required = (t === 'service');
    }
    buttons.forEach(btn => btn.addEventListener('click', () => setType(btn.dataset.type)));
    setType('service');
})();

document.getElementById('serviceProviderForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    const msg = document.getElementById('formMessage');
    msg.style.display = 'none';

    const formData = new FormData(form);
    const payload = Object.fromEntries(formData.entries());
    payload.services = formData.getAll('services[]');
    payload.channels = formData.getAll('channels[]');
    payload.register_type = formData.get('register_type') || 'service';

    if (payload.password !== payload.password_confirm) {
        msg.className = 'form-message error';
        msg.textContent = 'Şifreler uyuşmuyor.';
        msg.style.display = 'block';
        return;
    }

    // PR ajansı için en az bir kanal şartı
    if (payload.register_type === 'ad_agency' && (!payload.channels || payload.channels.length === 0)) {
        msg.className = 'form-message error';
        msg.textContent = 'Lütfen en az bir reklam kanalı seçin.';
        msg.style.display = 'block';
        return;
    }

    try {
        const res = await fetch('service_provider_register.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
            msg.className = 'form-message success';
            msg.textContent = data.message || 'Başvurunuz alınmıştır. Admin onayı sonrası hesabınız aktifleştirilecektir.';
            msg.style.display = 'block';
            form.reset();
        } else {
            msg.className = 'form-message error';
            msg.textContent = data.message || 'Kayıt sırasında bir hata oluştu.';
            msg.style.display = 'block';
        }
    } catch (err) {
        msg.className = 'form-message error';
        msg.textContent = 'Sunucu hatası. Lütfen tekrar deneyin.';
        msg.style.display = 'block';
    }
});
// Eğer bu sayfa genel header'ı kullanmıyorsa, küçük bir init ekleyelim:
(window.BJ_InitPwMeters ? BJ_InitPwMeters(document) : (function(){function getL(p){if(!p)return'e';const L=p.length,lo=/[a-z]/.test(p),up=/[A-Z]/.test(p),di=/\d/.test(p),k=[lo,up,di].filter(Boolean).length;if(L>=10&&k>=3)return's';if(L>=8&&k>=2)return'm';if(L>0)return'w';return'e';}
function upd(c,l){const b=c.querySelector('.pw-bar'),t=c.querySelector('.pw-text');if(!b||!t)return;let w='0%',col='#ddd',tx='Şifre gücü: -';if(l==='w'){w='33%';col:'#e74c3c';tx='Şifre gücü: Zayıf';}if(l==='m'){w='66%';col:'#f39c12';tx='Şifre gücü: Orta';}if(l==='s'){w='100%';col:'#27ae60';tx='Şifre gücü: Güçlü';}b.style.width=w;b.style.background=col;t.textContent=tx+' (En az orta seviye gerekir. 8+ karakter, en az iki tür: küçük/büyük/rakam)';t.style.color=l==='w'?'#e74c3c':'#666';}
function att(i){const c=i.parentElement.querySelector('.pw-strength');if(!c)return;const conf=document.querySelector('#password_confirm');function v(){const l=getL(i.value||'');upd(c,l);if(i.value){if(!(l==='m'||l==='s')){i.setCustomValidity('Lütfen en az orta seviye bir şifre girin.');}else{i.setCustomValidity('');}}else{i.setCustomValidity('');}if(conf){if(conf.value && conf.value!==i.value){conf.setCustomValidity('Şifreler eşleşmiyor.');}else{conf.setCustomValidity('');}}}i.addEventListener('input',v);if(conf)conf.addEventListener('input',v);v();}
(document.querySelectorAll('input.pw-meter')||[]).forEach(att);})());
</script>

<?php
require_once 'includes/footer.php';