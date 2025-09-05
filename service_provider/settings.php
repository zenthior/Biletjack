<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';

if (!isLoggedIn() || !isService()) {
    header('Location: /index.php');
    exit();
}

$database = new Database();
$pdo = $database->getConnection();

$message = null; $messageType = 'success';

// Kayıt/Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name = trim($_POST['company_name'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $regions = trim($_POST['regions'] ?? '');
    $equipment_list = trim($_POST['equipment_list'] ?? '');
    $experience_years = (int)($_POST['experience_years'] ?? 0);
    $portfolio_url = trim($_POST['portfolio_url'] ?? '');
    $instagram = trim($_POST['instagram'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $tax_number = trim($_POST['tax_number'] ?? '');
    $availability_24_7 = isset($_POST['availability_24_7']) ? 1 : 0;
    $notes = trim($_POST['notes'] ?? '');
    $services = $_POST['services'] ?? [];
    if (!is_array($services)) $services = [];

    if (!$company_name || !$city || !$equipment_list || !$address || !$tax_number) {
        $message = 'Lütfen zorunlu alanları doldurun.';
        $messageType = 'error';
    } else {
        // Satır var mı?
        $check = $pdo->prepare("SELECT id FROM service_providers WHERE user_id = ?");
        $check->execute([$_SESSION['user_id']]);
        if ($check->fetchColumn()) {
            $stmt = $pdo->prepare("
                UPDATE service_providers SET
                    company_name = :company_name,
                    services = :services,
                    city = :city,
                    regions = :regions,
                    equipment_list = :equipment_list,
                    experience_years = :experience_years,
                    portfolio_url = :portfolio_url,
                    instagram = :instagram,
                    address = :address,
                    tax_number = :tax_number,
                    availability_24_7 = :availability_24_7,
                    notes = :notes
                WHERE user_id = :user_id
            ");
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO service_providers
                    (user_id, company_name, services, city, regions, equipment_list, experience_years, portfolio_url, instagram, address, tax_number, availability_24_7, notes)
                VALUES
                    (:user_id, :company_name, :services, :city, :regions, :equipment_list, :experience_years, :portfolio_url, :instagram, :address, :tax_number, :availability_24_7, :notes)
            ");
        }

        $ok = $stmt->execute([
            ':user_id' => $_SESSION['user_id'],
            ':company_name' => $company_name,
            ':services' => json_encode($services, JSON_UNESCAPED_UNICODE),
            ':city' => $city,
            ':regions' => $regions ?: null,
            ':equipment_list' => $equipment_list,
            ':experience_years' => $experience_years,
            ':portfolio_url' => $portfolio_url ?: null,
            ':instagram' => $instagram ?: null,
            ':address' => $address,
            ':tax_number' => $tax_number,
            ':availability_24_7' => $availability_24_7,
            ':notes' => $notes ?: null,
        ]);

        if ($ok) {
            $message = 'Bilgileriniz başarıyla güncellendi.';
            $messageType = 'success';
        } else {
            $message = 'Güncelleme sırasında bir hata oluştu.';
            $messageType = 'error';
        }
    }
}

// Formu doldurmak için mevcut değerleri al
$stmt = $pdo->prepare("SELECT * FROM service_providers WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$provider = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
    'company_name' => '', 'services' => '[]', 'city' => '', 'regions' => '',
    'equipment_list' => '', 'experience_years' => 0, 'portfolio_url' => '',
    'instagram' => '', 'address' => '', 'tax_number' => '', 'availability_24_7' => 0, 'notes' => ''
];
$services = json_decode($provider['services'] ?? '[]', true);
if (!is_array($services)) $services = [];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Bilgilerimi Düzenle - Hizmet Sağlayıcı</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { background:#0f172a; color:#e2e8f0; font-family: Arial, Helvetica, sans-serif; margin:0; }
        .container { max-width:900px; margin:20px auto; padding:20px; }
        .card { background:#1f2937; border:1px solid #334155; border-radius:10px; padding:20px; margin-bottom:20px; }
        .row { display:grid; grid-template-columns: repeat(2,1fr); gap:15px; margin-bottom:15px; }
        .row .full { grid-column:1 / -1; }
        label { display:block; margin-bottom:5px; font-weight:600; color:#d1d5db; }
        input, textarea, select { width:100%; padding:10px; border-radius:8px; border:1px solid #374151; background:#111827; color:#e2e8f0; box-sizing:border-box; }
        .btn { display:inline-block; padding:12px 20px; background:#22c55e; color:#111827; border-radius:8px; text-decoration:none; font-weight:600; border:none; cursor:pointer; margin-right:10px; }
        .btn-secondary { background:#6b7280; color:#fff; }
        .alert { padding:12px; border-radius:8px; margin-bottom:15px; }
        .alert-success { background:#14532d; color:#86efac; border:1px solid #166534; }
        .alert-error { background:#7f1d1d; color:#fca5a5; border:1px solid #991b1b; }
        .checkboxes { display:flex; gap:15px; flex-wrap:wrap; }
        .checkboxes label { display:flex; align-items:center; gap:5px; margin-bottom:0; font-weight:normal; }
        .checkboxes input[type="checkbox"] { width:auto; }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <h2>Bilgilerimi Düzenle</h2>
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        <form method="post">
            <div class="row">
                <div>
                    <label>Firma Adı*</label>
                    <input type="text" name="company_name" required value="<?php echo htmlspecialchars($provider['company_name']); ?>">
                </div>
                <div>
                    <label>Şehir*</label>
                    <input type="text" name="city" required value="<?php echo htmlspecialchars($provider['city']); ?>">
                </div>
            </div>
            <div class="row">
                <div>
                    <label>Bölgeler</label>
                    <input type="text" name="regions" placeholder="İlçe/İller (virgülle ayırınız)" value="<?php echo htmlspecialchars($provider['regions'] ?? ''); ?>">
                </div>
                <div>
                    <label>Deneyim (Yıl)*</label>
                    <input type="number" name="experience_years" min="0" required value="<?php echo (int)$provider['experience_years']; ?>">
                </div>
            </div>
            <div class="row">
                <div class="full">
                    <label>Ekipman Listesi*</label>
                    <textarea name="equipment_list" rows="3" required placeholder="Başlıca ekipmanlarınızı yazın"><?php echo htmlspecialchars($provider['equipment_list']); ?></textarea>
                </div>
            </div>
            <div class="row">
                <div>
                    <label>Portfolyo/Website</label>
                    <input type="url" name="portfolio_url" placeholder="https://..." value="<?php echo htmlspecialchars($provider['portfolio_url'] ?? ''); ?>">
                </div>
                <div>
                    <label>Instagram</label>
                    <input type="text" name="instagram" placeholder="@kullaniciadi" value="<?php echo htmlspecialchars($provider['instagram'] ?? ''); ?>">
                </div>
            </div>
            <div class="row">
                <div class="full">
                    <label>Adres*</label>
                    <textarea name="address" rows="2" required><?php echo htmlspecialchars($provider['address']); ?></textarea>
                </div>
            </div>
            <div class="row">
                <div>
                    <label>Vergi Numarası*</label>
                    <input type="text" name="tax_number" required value="<?php echo htmlspecialchars($provider['tax_number']); ?>">
                </div>
                <div>
                    <label>7/24 Uygun</label>
                    <select name="availability_24_7">
                        <option value="0" <?php echo !$provider['availability_24_7'] ? 'selected' : ''; ?>>Hayır</option>
                        <option value="1" <?php echo $provider['availability_24_7'] ? 'selected' : ''; ?>>Evet</option>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="full">
                    <label>Hizmet Kategorileri*</label>
                    <div class="checkboxes">
                        <?php
                        $opts = ['Ses','Işık','Görüntü','Sahne','DJ','Kiralama'];
                        foreach ($opts as $opt) {
                            echo '<label><input type="checkbox" name="services[]" value="'.htmlspecialchars($opt).'" '.(in_array($opt, $services) ? 'checked' : '').'> ' . htmlspecialchars($opt) . '</label>';
                        }
                        ?>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="full">
                    <label>Ek Notlar</label>
                    <textarea name="notes" rows="2" placeholder="Varsa eklemek istedikleriniz"><?php echo htmlspecialchars($provider['notes'] ?? ''); ?></textarea>
                </div>
            </div>
            <div style="margin-top:20px;">
                <button class="btn" type="submit">Kaydet</button>
                <a class="btn btn-secondary" href="index.php">Panele Dön</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>