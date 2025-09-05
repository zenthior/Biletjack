<?php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../includes/password_utils.php';

// Organizatör kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'organizer') {
    header('Location: ../index.php');
    exit();
}

$organizer_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Mevcut QR yetkili hesabını kontrol et
$stmt = $pdo->prepare("SELECT * FROM qr_staff WHERE organizer_id = ?");
$stmt->execute([$organizer_id]);
$existing_staff = $stmt->fetch();

// QR yetkili hesabı oluşturma
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_staff'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $full_name = trim($_POST['full_name']);
    
    if (empty($username) || empty($password) || empty($full_name)) {
        $error = 'Tüm alanları doldurun.';
    } elseif ($existing_staff) {
        $error = 'Zaten bir QR yetkili hesabınız bulunmaktadır.';
    } else {
        // Kullanıcı adı kontrolü
        $stmt = $pdo->prepare("SELECT id FROM qr_staff WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = 'Bu kullanıcı adı zaten kullanılmaktadır.';
        } else {
            // Hesap oluştur
            $hashed_password = bj_hash_password($password);
            $stmt = $pdo->prepare("INSERT INTO qr_staff (organizer_id, username, password, full_name) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$organizer_id, $username, $hashed_password, $full_name])) {
                $message = 'QR yetkili hesabı başarıyla oluşturuldu!';
                // Yeniden yükle
                $stmt = $pdo->prepare("SELECT * FROM qr_staff WHERE organizer_id = ?");
                $stmt->execute([$organizer_id]);
                $existing_staff = $stmt->fetch();
            } else {
                $error = 'Hesap oluşturulurken bir hata oluştu.';
            }
        }
    }
}

// QR yetkili hesabını güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_staff'])) {
    $full_name = trim($_POST['full_name']);
    $new_password = $_POST['new_password'];
    
    if (empty($full_name)) {
        $error = 'Ad Soyad alanı boş olamaz.';
    } else {
        if (!empty($new_password)) {
            $hashed_password = bj_hash_password($new_password);
            $stmt = $pdo->prepare("UPDATE qr_staff SET full_name = ?, password = ? WHERE organizer_id = ?");
            $stmt->execute([$full_name, $hashed_password, $organizer_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE qr_staff SET full_name = ? WHERE organizer_id = ?");
            $stmt->execute([$full_name, $organizer_id]);
        }
        $message = 'QR yetkili hesabı başarıyla güncellendi!';
        // Yeniden yükle
        $stmt = $pdo->prepare("SELECT * FROM qr_staff WHERE organizer_id = ?");
        $stmt->execute([$organizer_id]);
        $existing_staff = $stmt->fetch();
    }
}

// QR yetkili hesabını silme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_staff'])) {
    $stmt = $pdo->prepare("DELETE FROM qr_staff WHERE organizer_id = ?");
    if ($stmt->execute([$organizer_id])) {
        $message = 'QR yetkili hesabı başarıyla silindi!';
        $existing_staff = null;
    } else {
        $error = 'Hesap silinirken bir hata oluştu.';
    }
}

include 'includes/header.php';
?>

<div class="organizer-dashboard">
    <div class="dashboard-header">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1>QR Yetkili Hesabı Yönetimi</h1>
                <p>Etkinlik girişlerinde bilet doğrulama yapacak yetkili hesabını yönetin</p>
            </div>
            <a href="index.php" class="qr-btn qr-btn-secondary">
                <i class="fas fa-arrow-left"></i> Organizatör Paneline Dön
            </a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="dashboard-content">
        <?php if (!$existing_staff): ?>
            <!-- QR Yetkili Hesabı Oluşturma -->
            <div class="card">
                <div class="card-header">
                    <h2>QR Yetkili Hesabı Oluştur</h2>
                    <p>Etkinlik girişlerinde bilet doğrulama yapacak yetkili hesabı oluşturun</p>
                </div>
                <div class="card-body">
                    <form method="POST" class="form">
                        <div class="form-group">
                            <label for="username">Kullanıcı Adı</label>
                            <input type="text" id="username" name="username" required>
                            <small>Giriş yaparken kullanılacak kullanıcı adı</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Şifre</label>
                            <!-- Yeni personel oluşturma şifresi -->
                            <input type="password" id="password" name="password" required class="pw-meter" data-require-strength="medium">
                            <div class="pw-strength" style="margin-top:6px;">
                                <div class="pw-bar" style="height:6px;width:0%;background:#ddd;border-radius:4px;transition:width .2s ease;"></div>
                                <div class="pw-text" style="margin-top:6px;font-size:12px;color:#666;">Şifre gücü: - (En az orta seviye gerekir. 8+ karakter, en az iki tür: küçük/büyük/rakam)</div>
                            </div>
                            <small>En az 6 karakter olmalıdır</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="full_name">Ad Soyad</label>
                            <input type="text" id="full_name" name="full_name" required>
                            <small>Yetkilinin tam adı</small>
                        </div>
                        
                        <button type="submit" name="create_staff" class="qr-btn qr-btn-primary">
                            <i class="fas fa-plus"></i>
                            QR Yetkili Hesabı Oluştur
                        </button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <!-- Mevcut QR Yetkili Hesabı -->
            <div class="card">
                <div class="card-header">
                    <h2>Mevcut QR Yetkili Hesabı</h2>
                </div>
                <div class="card-body">
                    <div class="staff-info">
                        <div class="info-item">
                            <label>Kullanıcı Adı:</label>
                            <span><?php echo htmlspecialchars($existing_staff['username']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Ad Soyad:</label>
                            <span><?php echo htmlspecialchars($existing_staff['full_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Durum:</label>
                            <span class="status <?php echo $existing_staff['status']; ?>">
                                <?php echo $existing_staff['status'] === 'active' ? 'Aktif' : 'Pasif'; ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <label>Oluşturulma Tarihi:</label>
                            <span><?php echo date('d.m.Y H:i', strtotime($existing_staff['created_at'])); ?></span>
                        </div>
                    </div>
                    
                    <div class="staff-actions">
                        <button type="button" class="qr-btn qr-btn-secondary" onclick="showUpdateForm()">
                            <i class="fas fa-edit"></i>
                            Güncelle
                        </button>
                        <button type="button" class="qr-btn qr-btn-danger" onclick="confirmDelete()">
                            <i class="fas fa-trash"></i>
                            Sil
                        </button>
                        <a href="../qr_panel/login.php" class="qr-btn qr-btn-primary" target="_blank">
                            <i class="fas fa-external-link-alt"></i>
                            QR Panel'e Git
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Güncelleme Formu (Gizli) -->
            <div id="updateForm" class="card" style="display: none;">
                <div class="card-header">
                    <h2>QR Yetkili Hesabını Güncelle</h2>
                </div>
                <div class="card-body">
                    <form method="POST" class="form">
                        <div class="form-group">
                            <label for="full_name_update">Ad Soyad</label>
                            <input type="text" id="full_name_update" name="full_name" value="<?php echo htmlspecialchars($existing_staff['full_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">Yeni Şifre (İsteğe bağlı)</label>
                            <input type="password" id="new_password" name="new_password" class="pw-meter" data-require-strength="medium">
                            <div class="pw-strength" style="margin-top:6px;">
                                <div class="pw-bar" style="height:6px;width:0%;background:#ddd;border-radius:4px;transition:width .2s ease;"></div>
                                <div class="pw-text" style="margin-top:6px;font-size:12px;color:#666;">Şifre gücü: - (En az orta seviye gerekir. 8+ karakter, en az iki tür: küçük/büyük/rakam)</div>
                            </div>
                            <small>Şifreyi değiştirmek istemiyorsanız boş bırakın</small>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="update_staff" class="qr-btn qr-btn-primary">
                                <i class="fas fa-save"></i>
                                Güncelle
                            </button>
                            <button type="button" class="qr-btn qr-btn-secondary" onclick="hideUpdateForm()">
                                İptal
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Silme Formu (Gizli) -->
            <form id="deleteForm" method="POST" style="display: none;">
                <input type="hidden" name="delete_staff" value="1">
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
function showUpdateForm() {
    document.getElementById('updateForm').style.display = 'block';
}

function hideUpdateForm() {
    document.getElementById('updateForm').style.display = 'none';
}

function confirmDelete() {
    if (confirm('QR yetkili hesabını silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.')) {
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php include 'includes/footer.php'; ?>


<script>
(function(){function g(p){if(!p)return{l:'e'};const L=p.length,a=/[a-z]/.test(p),b=/[A-Z]/.test(p),c=/\d/.test(p),k=[a,b,c].filter(Boolean).length;if(L>=10&&k>=3)return{l:'s'};if(L>=8&&k>=2)return{l:'m'};if(L>0)return{l:'w'};return{l:'e'}}function u(C,l){const b=C.querySelector('.pw-bar'),t=C.querySelector('.pw-text');if(!b||!t)return;let w='0%',col='#ddd',tx='Şifre gücü: -';if(l==='w'){w='33%';col='#e74c3c';tx='Şifre gücü: Zayıf';}if(l==='m'){w='66%';col='#f39c12';tx='Şifre gücü: Orta';}if(l==='s'){w='100%';col='#27ae60';tx='Şifre gücü: Güçlü';}b.style.width=w;b.style.background=col;t.textContent=tx+' (En az orta seviye gerekir. 8+ karakter, en az iki tür: küçük/büyük/rakam)';t.style.color=l==='w'?'#e74c3c':'#666';}
function a(i){const C=i.parentElement.querySelector('.pw-strength');if(!C)return;function v(){const {l}=g(i.value||'');u(C,l);if(i.value){if(!(l==='m'||l==='s')){i.setCustomValidity('Lütfen en az orta seviye bir şifre girin.');}else{i.setCustomValidity('');}}else{i.setCustomValidity('');}}i.addEventListener('input',v);v();}
(document.querySelectorAll('input.pw-meter')||[]).forEach(a);})();
</script>