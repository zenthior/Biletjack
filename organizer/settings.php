<?php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../classes/User.php';

// Organizatör kontrolü
requireOrganizer();

// Organizatör onay kontrolü
if (!isOrganizerApproved()) {
    header('Location: pending.php');
    exit();
}

$currentUser = getCurrentUser();
$database = new Database();
$pdo = $database->getConnection();

// Organizatör detaylarını getir
$query = "SELECT * FROM organizer_details WHERE user_id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$organizerDetails = $stmt->fetch(PDO::FETCH_ASSOC);

$message = '';
$messageType = '';

// Kategorileri getir
$categoriesQuery = "SELECT * FROM categories ORDER BY name";
$categoriesStmt = $pdo->prepare($categoriesQuery);
$categoriesStmt->execute();
$categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $companyName = trim($_POST['company_name']);
        $description = trim($_POST['description']);
        $about = trim($_POST['about']);
        $eventTypes = isset($_POST['event_types']) ? implode(',', $_POST['event_types']) : '';
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);
        $website = trim($_POST['website']);
        $address = trim($_POST['address']);
        $facebookUrl = trim($_POST['facebook_url']);
        $instagramUrl = trim($_POST['instagram_url']);
        
        // Logo ve kapak fotoğrafı yükleme
        $logoUrl = $organizerDetails['logo_url'] ?? '';
        $coverImageUrl = $organizerDetails['cover_image_url'] ?? '';
        
        // Logo yükleme
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/organizer_logos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileExtension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $fileName = 'logo_' . $_SESSION['user_id'] . '_' . time() . '.' . $fileExtension;
            $uploadPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $uploadPath)) {
                $logoUrl = 'uploads/organizer_logos/' . $fileName;
            }
        }
        
        // Kapak fotoğrafı yükleme
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/organizer_covers/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileExtension = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
            $fileName = 'cover_' . $_SESSION['user_id'] . '_' . time() . '.' . $fileExtension;
            $uploadPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $uploadPath)) {
                $coverImageUrl = 'uploads/organizer_covers/' . $fileName;
            }
        }
        
        // Validasyon
        if (empty($companyName)) {
            $message = 'Organizatör adı boş olamaz.';
            $messageType = 'error';
        } else {
            try {
                // Organizatör detaylarını güncelle
                $updateQuery = "UPDATE organizer_details SET 
                               company_name = ?, description = ?, about = ?, event_types = ?, 
                               logo_url = ?, cover_image_url = ?, phone = ?, email = ?, 
                               website = ?, address = ?, facebook_url = ?, instagram_url = ?
                               WHERE user_id = ?";
                $updateStmt = $pdo->prepare($updateQuery);
                $updateStmt->execute([
                    $companyName, $description, $about, $eventTypes, 
                    $logoUrl, $coverImageUrl, $phone, $email, 
                    $website, $address, $facebookUrl, $instagramUrl, 
                    $_SESSION['user_id']
                ]);
                
                $message = 'Profil bilgileriniz başarıyla güncellendi.';
                $messageType = 'success';
                
                // Güncel verileri tekrar çek
                $stmt->execute([$_SESSION['user_id']]);
                $organizerDetails = $stmt->fetch(PDO::FETCH_ASSOC);
                
            } catch (Exception $e) {
                $message = 'Bir hata oluştu: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
    

}



// QR yetkili hesabını kontrol et
$stmt = $pdo->prepare("SELECT * FROM qr_staff WHERE organizer_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$qr_staff = $stmt->fetch();

include 'includes/header.php';
?>

<!-- Mobile Overlay -->
<div class="mobile-overlay" id="mobileOverlay"></div>

<!-- Floating Toggle Button -->
<div class="floating-toggle" id="floatingToggle" onclick="toggleSidebar()">
    <i class="fas fa-ticket-alt"></i>
</div>

<!-- Sol Sidebar -->
<div class="modern-sidebar" id="sidebar">
    <div class="sidebar-logo" onclick="toggleSidebar()" style="cursor: pointer;" id="sidebarLogo">
        <i class="fas fa-ticket-alt"></i>
    </div>
    
    <div class="sidebar-nav">
        <div class="nav-icon" title="Ana Sayfa" onclick="window.location.href='./index.php'" style="cursor: pointer;">
            <i class="fas fa-home"></i>
        </div>
        <div class="nav-icon" title="Etkinlikler" onclick="window.location.href='./events.php'" style="cursor: pointer;">
            <i class="fas fa-calendar-alt"></i>
        </div>
        <div class="nav-icon" title="QR Yetkili" onclick="loadQRStaffPage()" style="cursor: pointer;">
            <i class="fas fa-qrcode"></i>
        </div>
        <div class="nav-icon" title="Analitik" onclick="window.location.href='./index.php?page=analytics'" style="cursor: pointer;">
            <i class="fas fa-chart-bar"></i>
        </div>
        <div class="nav-icon active" title="Ayarlar">
            <i class="fas fa-cog"></i>
        </div>
        <!-- Ana sayfaya dön butonu (yeni) -->
        <div class="nav-icon" title="Ana Sayfaya Dön" onclick="window.location.href='../index.php'" style="cursor: pointer;">
            <i class="fas fa-arrow-left"></i>
        </div>
        <div class="nav-icon" title="Çıkış" onclick="window.location.href='../auth/logout.php'" style="cursor: pointer;">
            <i class="fas fa-sign-out-alt"></i>
        </div>
    </div>
</div>

<!-- Ana İçerik -->
<div class="main-content">
    <!-- Üst Header -->
    <div class="top-header">
        <div class="user-profile">
            <div class="user-avatar">
                <?php echo strtoupper(substr($currentUser['first_name'], 0, 1) . substr($currentUser['last_name'], 0, 1)); ?>
            </div>
            <div class="user-info">
                <h4><?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?></h4>
                <p>Organizatör</p>
            </div>
        </div>
        
        <div class="search-container">
            <i class="fas fa-search search-icon"></i>
            <input type="text" class="search-input" placeholder="Ara...">
        </div>
        
        <div class="notification-icon">
            <i class="fas fa-bell"></i>
        </div>
    </div>
    
    <!-- Ayarlar İçeriği -->
    <div class="dashboard-content" id="main-content">
        <div class="page-header">
            <h1 style="color: white;"><i class="fas fa-cog"></i> Ayarlar</h1>
            <p style="color: white;">Organizatör profil bilgilerinizi yönetin</p>
        </div>
        
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>
        
        <div class="settings-container">
            <!-- Profil Bilgileri -->
            <div class="settings-card">
                <div class="card-header">
                    <h3><i class="fas fa-user-circle"></i> Profil Bilgileri</h3>
                    <p>Organizatör profil bilgilerinizi güncelleyin</p>
                </div>
                
                <form method="POST" class="settings-form" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="company_name">Organizatör Adı</label>
                            <input type="text" id="company_name" name="company_name" 
                                   value="<?php echo htmlspecialchars($organizerDetails['company_name'] ?? ''); ?>" 
                                   required>
                            <small>Bu alan herkese görünür organizatör adınızdır ve istediğiniz zaman değiştirebilirsiniz.</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Telefon</label>
                            <input type="tel" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($organizerDetails['phone'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">E-posta</label>
                            <input type="email" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($organizerDetails['email'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="website">Web Sitesi</label>
                            <input type="url" id="website" name="website" 
                                   value="<?php echo htmlspecialchars($organizerDetails['website'] ?? ''); ?>" 
                                   placeholder="https://">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Kısa Açıklama</label>
                        <textarea id="description" name="description" rows="3" 
                                  placeholder="Organizatörünüz hakkında kısa bilgi..."><?php echo htmlspecialchars($organizerDetails['description'] ?? ''); ?></textarea>
                        <small>Bu bilgi etkinlik sayfalarında görüntülenecektir.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="about">Hakkımızda</label>
                        <textarea id="about" name="about" rows="5" 
                                  placeholder="Organizatörünüz hakkında detaylı bilgi verin..."><?php echo htmlspecialchars($organizerDetails['about'] ?? ''); ?></textarea>
                        <small>Detaylı organizatör bilgisi için kullanılır.</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Hangi Tür Etkinlikler Yapıyorsunuz?</label>
                        <div class="checkbox-grid">
                            <?php 
                            $selectedTypes = !empty($organizerDetails['event_types']) ? explode(',', $organizerDetails['event_types']) : [];
                            foreach ($categories as $category): 
                            ?>
                            <label class="checkbox-item">
                                <input type="checkbox" name="event_types[]" value="<?php echo $category['id']; ?>"
                                       <?php echo in_array($category['id'], $selectedTypes) ? 'checked' : ''; ?>>
                                <span class="checkmark"></span>
                                <i class="<?php echo htmlspecialchars($category['icon']); ?>"></i>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="logo">Organizatör Logosu</label>
                            <?php if (!empty($organizerDetails['logo_url'])): ?>
                            <div class="current-image">
                                <img src="../<?php echo htmlspecialchars($organizerDetails['logo_url']); ?>" alt="Mevcut Logo" class="preview-image">
                                <small>Mevcut logo</small>
                            </div>
                            <?php endif; ?>
                            <input type="file" id="logo" name="logo" accept="image/*">
                            <small>JPG, PNG formatında, maksimum 2MB</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="cover_image">Kapak Fotoğrafı</label>
                            <?php if (!empty($organizerDetails['cover_image_url'])): ?>
                            <div class="current-image">
                                <img src="../<?php echo htmlspecialchars($organizerDetails['cover_image_url']); ?>" alt="Mevcut Kapak" class="preview-image">
                                <small>Mevcut kapak fotoğrafı</small>
                            </div>
                            <?php endif; ?>
                            <input type="file" id="cover_image" name="cover_image" accept="image/*">
                            <small>JPG, PNG formatında, maksimum 5MB</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Adres</label>
                        <textarea id="address" name="address" rows="2" 
                                  placeholder="Organizatör adresi..."><?php echo htmlspecialchars($organizerDetails['address'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="facebook_url">Facebook</label>
                            <input type="url" id="facebook_url" name="facebook_url" 
                                   value="<?php echo htmlspecialchars($organizerDetails['facebook_url'] ?? ''); ?>" 
                                   placeholder="https://facebook.com/">
                        </div>
                        
                        <div class="form-group">
                            <label for="instagram_url">Instagram</label>
                            <input type="url" id="instagram_url" name="instagram_url" 
                                   value="<?php echo htmlspecialchars($organizerDetails['instagram_url'] ?? ''); ?>" 
                                   placeholder="https://instagram.com/">
                        </div>
                    </div>

                    
                    <div class="form-actions">
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="fas fa-save"></i> Bilgileri Güncelle
                        </button>
                    </div>
                </form>
            </div>
            

        </div>
    </div>
</div>
<style>

    
.settings-container {
    max-width: 1000px;
    margin: 0 auto;
    margin-left: -12px;
}

.settings-card {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 30px;
    margin-bottom: 30px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.card-header {
    margin-bottom: 30px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    padding-bottom: 20px;
}

.card-header h3 {
    color: white;
    font-size: 1.5rem;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
}

.card-header h3 i {
    margin-right: 10px;
    color: #4CAF50;
}

.card-header p {
    color: rgba(255, 255, 255, 0.9);
    background: rgba(0, 0, 0, 0.7);
    padding: 8px 12px;
    border-radius: 8px;
    margin: 0;
    font-size: 0.9rem;
}

.settings-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    color: white;
    font-weight: 600;
    margin-bottom: 8px;
    font-size: 0.9rem;
}

.form-group input,
.form-group textarea {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 10px;
    padding: 12px 15px;
    color: white;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #4CAF50;
    background: rgba(255, 255, 255, 0.15);
    box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
}

.form-group input::placeholder,
.form-group textarea::placeholder {
    color: rgba(255, 255, 255, 0.5);
}

.form-group small {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.8rem;
    margin-top: 5px;
}

.checkbox-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 10px;
}

.checkbox-item {
    display: flex;
    align-items: center;
    padding: 12px 15px;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
    color: white;
}

.checkbox-item:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.2);
}

.checkbox-item input[type="checkbox"] {
    display: none;
}

.checkbox-item .checkmark {
    width: 20px;
    height: 20px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 4px;
    margin-right: 10px;
    position: relative;
    transition: all 0.3s ease;
}

.checkbox-item input[type="checkbox"]:checked + .checkmark {
    background: #4CAF50;
    border-color: #4CAF50;
}

.checkbox-item input[type="checkbox"]:checked + .checkmark::after {
    content: '✓';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: white;
    font-size: 12px;
    font-weight: bold;
}

.checkbox-item i {
    margin-right: 8px;
    color: #4CAF50;
    width: 16px;
}

.current-image {
    margin-bottom: 10px;
}

.preview-image {
    max-width: 150px;
    max-height: 100px;
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    display: block;
    margin-bottom: 5px;
}

.form-group input[type="file"] {
    background: rgba(255, 255, 255, 0.05);
    border: 2px dashed rgba(255, 255, 255, 0.3);
    padding: 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
}

.form-group input[type="file"]:hover {
    border-color: #4CAF50;
    background: rgba(76, 175, 80, 0.1);
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.btn {
    padding: 12px 25px;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    font-size: 0.9rem;
}

.btn-primary {
    background: linear-gradient(135deg, #4CAF50, #45a049);
    color: white;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #45a049, #3d8b40);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(76, 175, 80, 0.3);
}

.btn-warning {
    background: linear-gradient(135deg, #ff9800, #f57c00);
    color: white;
}

.btn-warning:hover {
    background: linear-gradient(135deg, #f57c00, #ef6c00);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 152, 0, 0.3);
}

.alert {
    padding: 15px 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 500;
}

.alert-success {
    background: rgba(76, 175, 80, 0.2);
    border: 1px solid rgba(76, 175, 80, 0.3);
    color: #4CAF50;
}

.alert-error {
    background: rgba(244, 67, 54, 0.2);
    border: 1px solid rgba(244, 67, 54, 0.3);
    color: #f44336;
}

.pending-request {
    background: rgba(255, 193, 7, 0.1);
    border: 1px solid rgba(255, 193, 7, 0.3);
    border-radius: 10px;
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.request-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

.request-info i {
    color: #ffc107;
    font-size: 1.5rem;
}

.request-info h4 {
    color: white;
    margin: 0 0 5px 0;
}

.request-info p {
    color: rgba(255, 255, 255, 0.8);
    margin: 2px 0;
    font-size: 0.9rem;
}

.status-badge {
    padding: 8px 15px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.status-pending {
    background: rgba(255, 193, 7, 0.2);
    color: #ffc107;
    border: 1px solid rgba(255, 193, 7, 0.3);
}

@media (max-width: 768px) {

    .modern-sidebar {
        width: 60px;
        margin-left: -15px;
    }

    .dashboard-content {
    background: #3635b1eb;
}
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .pending-request {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
} /* Bu kapanış süslü parantezi eksikti */

.card-header p {
    color: rgba(255, 255, 255, 0.7);
    margin: 0;
}

.settings-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-group label {
    color: white;
    font-weight: 500;
    font-size: 0.9rem;
}

.form-group input,
.form-group textarea {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 10px;
    padding: 12px 16px;
    color: white;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #4f46e5;
    background: rgba(255, 255, 255, 0.15);
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.form-group input::placeholder,
.form-group textarea::placeholder {
    color: rgba(255, 255, 255, 0.5);
}

.form-group small {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.8rem;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    margin-top: 20px;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 10px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
}

.btn-primary {
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(79, 70, 229, 0.3);
}

.btn-warning {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
}

.btn-warning:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(245, 158, 11, 0.3);
}

.alert {
    padding: 16px 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 500;
}

.alert-success {
    background: rgba(34, 197, 94, 0.1);
    border: 1px solid rgba(34, 197, 94, 0.3);
    color: #22c55e;
}

.alert-error {
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: #ef4444;
}

.pending-request {
    background: rgba(245, 158, 11, 0.1);
    border: 1px solid rgba(245, 158, 11, 0.3);
    border-radius: 10px;
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.request-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

.request-info i {
    color: #f59e0b;
    font-size: 1.5rem;
}

.request-info h4 {
    color: white;
    margin: 0 0 8px 0;
    font-size: 1.1rem;
}

.request-info p {
    color: rgba(255, 255, 255, 0.8);
    margin: 4px 0;
    font-size: 0.9rem;
}

.status-badge {
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-pending {
    background: rgba(245, 158, 11, 0.2);
    color: #f59e0b;
    border: 1px solid rgba(245, 158, 11, 0.3);
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .settings-card {
        padding: 20px;
    }
    
    .pending-request {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
}

@media (max-width: 480px) {
    .settings-container {
        max-width: 100%;
        margin: 0;
        padding: 0 10px;
    }
    
    .settings-card {
        padding: 15px;
        margin: 10px 0;
        border-radius: 15px;
        overflow-x: auto;
    }
    
    .form-row {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .form-group {
        width: 100%;
        min-width: 0;
    }
    
    .form-group label {
        font-size: 14px;
    }
    
    .form-group input,
    .form-group textarea {
        padding: 10px;
        font-size: 14px;
        width: 100%;
        box-sizing: border-box;
    }
    
    .checkbox-grid {
        grid-template-columns: 1fr;
        gap: 10px;
    }
    
    .checkbox-item {
        padding: 10px;
        font-size: 14px;
    }
    
    .btn {
        padding: 10px 16px;
        font-size: 14px;
        width: 100%;
        justify-content: center;
    }
    
    .card-header h3 {
        font-size: 18px;
    }
    
    .card-header p {
        font-size: 12px;
        padding: 6px 10px;
    }
    
    .pending-request {
        padding: 15px;
        margin: 10px 0;
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
    
    .request-info h4 {
        font-size: 16px;
    }
    
    .status-badge {
        padding: 6px 12px;
        font-size: 0.7rem;
    }
    
    .form-actions {
        justify-content: center;
        margin-top: 20px;
    }
    
    .preview-image {
        max-width: 100px;
        max-height: 80px;
    }
    
    .current-image {
        text-align: center;
    }
}
</style>

<script>
// Sidebar toggle functionality
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('mobileOverlay');

// Mobile menu toggle
function toggleSidebar() {
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
}

// Close sidebar when clicking overlay
overlay.addEventListener('click', () => {
    sidebar.classList.remove('active');
    overlay.classList.remove('active');
});

// Auto-hide alerts after 5 seconds
const alerts = document.querySelectorAll('.alert');
alerts.forEach(alert => {
    setTimeout(() => {
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-10px)';
        setTimeout(() => {
            alert.remove();
        }, 300);
    }, 5000);
});
</script>

<script>
function loadQRStaffPage() {
    fetch('qr_staff_content.php')
        .then(response => response.text())
        .then(html => {
            document.getElementById('main-content').innerHTML = html;
            bindQRForms();
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('main-content').innerHTML = '<div class="error">Sayfa yüklenirken hata oluştu.</div>';
        });
}

function bindQRForms() {
    const container = document.getElementById('main-content');
    if (!container) return;

    container.querySelectorAll('form').forEach(form => {
        if (form.dataset.qrbound === '1') return;

        const isQRForm = form.querySelector('[name="create_staff"]') 
            || form.querySelector('[name="update_staff"]') 
            || form.querySelector('[name="delete_staff"]');
        if (!isQRForm) return;

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            fetch('qr_staff_content.php', {
                method: 'POST',
                body: new FormData(form)
            })
            .then(res => res.text())
            .then(html => {
                document.getElementById('main-content').innerHTML = html;
                bindQRForms();
            })
            .catch(err => console.error('QR form error:', err));
        }, { passive: false });

        form.dataset.qrbound = '1';
    });
}

function goToQRPanel() {
    <?php if ($qr_staff): ?>
    // QR yetkili hesabı varsa otomatik giriş yap
    window.open('qr_auto_login.php', '_blank');
    <?php else: ?>
    alert('Önce bir QR yetkili hesabı oluşturmalısınız!');
    <?php endif; ?>
}

// Sidebar toggle fonksiyonu - sadece mobilde çalışır
function toggleSidebar() {
    // Sadece mobil cihazlarda çalışsın
    if (window.innerWidth > 768) {
        return;
    }
    
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    const floatingToggle = document.getElementById('floatingToggle');
    
    const isOpen = sidebar.classList.contains('open');
    
    if (isOpen) {
        // Sidebar'ı kapat
        sidebar.classList.remove('open');
        sidebar.classList.add('collapsed');
        mainContent.classList.remove('collapsed');
        mainContent.classList.add('expanded');
        floatingToggle.classList.remove('hide');
        floatingToggle.classList.add('show');
    } else {
        // Sidebar'ı aç
        sidebar.classList.remove('collapsed');
        sidebar.classList.add('open');
        mainContent.classList.remove('expanded');
        mainContent.classList.add('collapsed');
        floatingToggle.classList.remove('show');
        floatingToggle.classList.add('hide');
    }
}

// Sayfa yüklendiğinde mobil kontrolü - Her sayfa değişiminde sidebar kapalı gelsin
function initializeSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    const floatingToggle = document.getElementById('floatingToggle');
    
    if (window.innerWidth <= 768) {
        // Mobilde her zaman kapalı başlasın
        sidebar.classList.remove('open');
        sidebar.classList.add('collapsed');
        mainContent.classList.remove('collapsed');
        mainContent.classList.add('expanded');
        floatingToggle.classList.remove('hide');
        floatingToggle.classList.add('show');
    } else {
        // Masaüstünde normal durum
        sidebar.classList.remove('collapsed', 'open');
        mainContent.classList.remove('expanded', 'collapsed');
        floatingToggle.classList.remove('show');
        floatingToggle.classList.add('hide');
    }
}

// Ekran boyutu değiştiğinde kontrol et
window.addEventListener('resize', function() {
    initializeSidebar();
});

// Sayfa yüklendiğinde başlat
document.addEventListener('DOMContentLoaded', function() {
    initializeSidebar();
});
</script>
<?php include 'includes/footer.php'; ?>
