<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/User.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
    exit;
}

$registerType = isset($input['register_type']) ? trim($input['register_type']) : 'service';

// Zorunlu alanlar (türe göre)
if ($registerType === 'ad_agency') {
    $required = ['company_name','contact_first_name','contact_last_name','email','phone','city','address','tax_number','password','password_confirm'];
} else {
    $required = ['company_name','contact_first_name','contact_last_name','email','phone','city','equipment_list','experience_years','address','tax_number','password','password_confirm'];
}
foreach ($required as $r) {
    if (empty($input[$r])) {
        echo json_encode(['success' => false, 'message' => 'Lütfen zorunlu alanları doldurun.']);
        exit;
    }
}
if ($input['password'] !== $input['password_confirm']) {
    echo json_encode(['success' => false, 'message' => 'Şifreler uyuşmuyor.']);
    exit;
}

// PR ajansı için kanal kontrolü
if ($registerType === 'ad_agency') {
    if (empty($input['channels']) || !is_array($input['channels']) || count($input['channels']) === 0) {
        echo json_encode(['success' => false, 'message' => 'Lütfen en az bir reklam kanalı seçin.']);
        exit;
    }
}

try {
    $db = new Database();
    $pdo = $db->getConnection();

    // Tablolar (DDL)
    if ($registerType === 'ad_agency') {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS ad_agencies (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                company_name VARCHAR(255) NOT NULL,
                channels TEXT NOT NULL,
                city VARCHAR(120) NOT NULL,
                regions VARCHAR(255) NULL,
                portfolio_url VARCHAR(255) NULL,
                instagram VARCHAR(120) NULL,
                address TEXT NOT NULL,
                tax_number VARCHAR(80) NOT NULL,
                notes TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    } else {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS service_providers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                company_name VARCHAR(255) NOT NULL,
                services TEXT NULL,
                city VARCHAR(120) NOT NULL,
                regions VARCHAR(255) NULL,
                equipment_list TEXT NOT NULL,
                experience_years INT NOT NULL DEFAULT 0,
                portfolio_url VARCHAR(255) NULL,
                instagram VARCHAR(120) NULL,
                address TEXT NOT NULL,
                tax_number VARCHAR(80) NOT NULL,
                availability_24_7 TINYINT(1) NOT NULL DEFAULT 0,
                notes TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }

    // Transaction
    $pdo->beginTransaction();

    // User oluştur
    $user = new User($pdo);
    $user->email = $input['email'];
    $user->password = $input['password'];
    $user->first_name = $input['contact_first_name'];
    $user->last_name = $input['contact_last_name'];
    $user->phone = $input['phone'];
    $user->user_type = ($registerType === 'ad_agency') ? 'ad_agency' : 'service';
    $user->status = 'inactive'; // admin onayı bekle

    if ($user->emailExists()) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Bu e-posta zaten kayıtlı.']);
        exit;
    }

    if (!$user->register()) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Kullanıcı kaydı yapılamadı.']);
        exit;
    }

    if ($registerType === 'ad_agency') {
        // PR Ajansı kaydı
        $channels = is_array($input['channels']) ? $input['channels'] : [];
        $stmt = $pdo->prepare("
            INSERT INTO ad_agencies
            (user_id, company_name, channels, city, regions, portfolio_url, instagram, address, tax_number, notes)
            VALUES
            (:user_id, :company_name, :channels, :city, :regions, :portfolio_url, :instagram, :address, :tax_number, :notes)
        ");
        $stmt->execute([
            ':user_id' => $user->id,
            ':company_name' => trim($input['company_name']),
            ':channels' => json_encode($channels, JSON_UNESCAPED_UNICODE),
            ':city' => trim($input['city']),
            ':regions' => isset($input['regions']) ? trim($input['regions']) : null,
            ':portfolio_url' => !empty($input['portfolio_url']) ? trim($input['portfolio_url']) : null,
            ':instagram' => !empty($input['instagram']) ? trim($input['instagram']) : null,
            ':address' => trim($input['address']),
            ':tax_number' => trim($input['tax_number']),
            ':notes' => !empty($input['notes']) ? trim($input['notes']) : null,
        ]);
    } else {
        // Hizmet sağlayıcı kaydı
        $services = isset($input['services']) ? $input['services'] : [];
        if (!is_array($services)) $services = [];
        $stmt = $pdo->prepare("
            INSERT INTO service_providers
            (user_id, company_name, services, city, regions, equipment_list, experience_years, portfolio_url, instagram, address, tax_number, availability_24_7, notes)
            VALUES
            (:user_id, :company_name, :services, :city, :regions, :equipment_list, :experience_years, :portfolio_url, :instagram, :address, :tax_number, :availability_24_7, :notes)
        ");
        $stmt->execute([
            ':user_id' => $user->id,
            ':company_name' => trim($input['company_name']),
            ':services' => json_encode($services, JSON_UNESCAPED_UNICODE),
            ':city' => trim($input['city']),
            ':regions' => isset($input['regions']) ? trim($input['regions']) : null,
            ':equipment_list' => trim($input['equipment_list']),
            ':experience_years' => (int)$input['experience_years'],
            ':portfolio_url' => !empty($input['portfolio_url']) ? trim($input['portfolio_url']) : null,
            ':instagram' => !empty($input['instagram']) ? trim($input['instagram']) : null,
            ':address' => trim($input['address']),
            ':tax_number' => trim($input['tax_number']),
            ':availability_24_7' => !empty($input['availability_24_7']) ? (int)$input['availability_24_7'] : 0,
            ':notes' => !empty($input['notes']) ? trim($input['notes']) : null,
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => ($registerType === 'ad_agency')
            ? 'Ajans başvurunuz alındı. Admin onayı sonrası hesabınız aktifleştirilecektir.'
            : 'Başvurunuz alındı. Admin onayı sonrası hesabınız aktifleştirilecektir.'
    ]);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error_log('Service/ad agency register error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Sistem hatası. Lütfen tekrar deneyin.']);
}