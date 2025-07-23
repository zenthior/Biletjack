<?php
require_once '../config/database.php';
require_once '../classes/User.php';
require_once '../classes/Organizer.php';
require_once '../includes/session.php';

header('Content-Type: application/json');

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    $user = new User($db);
    
    // Form verilerini al
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $user_type = $_POST['user_type'] ?? 'customer';
    
    // Validasyon
    if(empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
        echo json_encode([
            'success' => false,
            'message' => 'Tüm zorunlu alanları doldurunuz.'
        ]);
        exit();
    }
    
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'success' => false,
            'message' => 'Geçerli bir e-posta adresi giriniz.'
        ]);
        exit();
    }
    
    if(strlen($password) < 6) {
        echo json_encode([
            'success' => false,
            'message' => 'Şifre en az 6 karakter olmalıdır.'
        ]);
        exit();
    }
    
    // E-posta kontrolü
    $user->email = $email;
    if($user->emailExists()) {
        echo json_encode([
            'success' => false,
            'message' => 'Bu e-posta adresi zaten kullanılmaktadır.'
        ]);
        exit();
    }
    
    // Kullanıcı bilgilerini set et
    $user->email = $email;
    $user->password = $password;
    $user->first_name = $first_name;
    $user->last_name = $last_name;
    $user->phone = $phone;
    $user->user_type = $user_type;
    $user->status = ($user_type === 'organizer') ? 'pending' : 'active';
    
    // Kullanıcıyı kaydet
    if($user->register()) {
        // Organizatör ise ek bilgileri kaydet
        if($user_type === 'organizer') {
            $organizer = new Organizer($db);
            $organizer->user_id = $user->id;
            $organizer->company_name = $_POST['company_name'] ?? '';
            $organizer->tax_number = $_POST['tax_number'] ?? '';
            $organizer->address = $_POST['address'] ?? '';
            $organizer->city = $_POST['city'] ?? '';
            $organizer->website = $_POST['website'] ?? '';
            $organizer->description = $_POST['description'] ?? '';
            
            $organizer->create();
            
            echo json_encode([
                'success' => true,
                'message' => 'Organizatör kaydınız başarıyla oluşturuldu. Hesabınızın onaylanması için lütfen bekleyiniz.',
                'type' => 'organizer_pending'
            ]);
        } else {
            // Normal müşteri kaydı - otomatik giriş yap
            startUserSession([
                'id' => $user->id,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'user_type' => $user->user_type,
                'status' => $user->status
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Kayıt başarılı! Hoş geldiniz.',
                'redirect' => '/Biletjack/index.php'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Kayıt sırasında bir hata oluştu. Lütfen tekrar deneyiniz.'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Geçersiz istek.'
    ]);
}
?>