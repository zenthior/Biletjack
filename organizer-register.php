<?php
require_once 'config/database.php';
require_once 'classes/User.php';
require_once 'classes/Organizer.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Geçersiz istek metodu.'
    ]);
    exit();
}

// Form verilerini al
$org_name = trim($_POST['org_name'] ?? '');
$contact_person = trim($_POST['contact_person'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');
$description = trim($_POST['description'] ?? '');
$terms = isset($_POST['org_terms']);

// Validasyon
if (empty($org_name) || empty($contact_person) || empty($email) || empty($phone)) {
    echo json_encode([
        'success' => false,
        'message' => 'Lütfen tüm zorunlu alanları doldurun.'
    ]);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Geçerli bir e-posta adresi girin.'
    ]);
    exit();
}

if (!$terms) {
    echo json_encode([
        'success' => false,
        'message' => 'Organizatör sözleşmesini kabul etmelisiniz.'
    ]);
    exit();
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Transaction başlat
    $pdo->beginTransaction();
    
    $user = new User($pdo);
    $organizer = new Organizer($pdo);
    
    // E-posta kontrolü
    $user->email = $email;
    if ($user->emailExists()) {
        echo json_encode([
            'success' => false,
            'message' => 'Bu e-posta adresi zaten kullanılmaktadır.'
        ]);
        exit();
    }
    
    // İletişim kişisinin adını böl
    $name_parts = explode(' ', $contact_person, 2);
    $first_name = $name_parts[0];
    $last_name = isset($name_parts[1]) ? $name_parts[1] : '';
    
    // Geçici şifre oluştur (kullanıcı daha sonra değiştirebilir)
    $temp_password = 'temp' . rand(1000, 9999);
    
    // Kullanıcı kaydı oluştur
    $user->email = $email;
    $user->password = $temp_password;
    $user->first_name = $first_name;
    $user->last_name = $last_name;
    $user->phone = $phone;
    $user->user_type = 'organizer';
    $user->status = 'pending';
    
    if (!$user->register()) {
        throw new Exception('Kullanıcı kaydı oluşturulamadı.');
    }
    
    // Organizatör detaylarını kaydet
    $organizer->user_id = $user->id;
    $organizer->company_name = $org_name;
    $organizer->address = $address;
    $organizer->description = $description;
    $organizer->approval_status = 'pending';
    
    if (!$organizer->create()) {
        throw new Exception('Organizatör detayları kaydedilemedi.');
    }
    
    // Transaction'ı tamamla
    $pdo->commit();
    
    // Başarılı yanıt
    echo json_encode([
        'success' => true,
        'message' => 'Organizatör başvurunuz başarıyla alındı. Onay sürecinden sonra size bilgi verilecektir.',
        'temp_password' => $temp_password // Geçici şifre (e-posta ile gönderilebilir)
    ]);
    
} catch (Exception $e) {
    // Transaction'ı geri al
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Kayıt sırasında bir hata oluştu: ' . $e->getMessage()
    ]);
}
?>