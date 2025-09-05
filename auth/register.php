<?php
require_once '../config/database.php';
require_once '../classes/User.php';
require_once '../classes/Organizer.php';
require_once '../includes/session.php';
require_once '../includes/email_verification.php';

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
    $user->status = ($user_type === 'organizer') ? 'pending' : 'pending'; // Tüm kullanıcılar e-posta doğrulaması bekliyor
    
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
            // Normal müşteri kaydı - e-posta doğrulama gönder
            $emailVerification = new EmailVerification($db);
            $emailSent = $emailVerification->sendVerificationEmail(
                $user->id, 
                $user->email, 
                $user->first_name, 
                $user->last_name
            );
            
            if ($emailSent) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Kayıt başarılı! E-posta adresinize gönderilen doğrulama linkine tıklayarak hesabınızı aktifleştirin.',
                    'type' => 'email_verification_sent'
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'message' => 'Kayıt başarılı! E-posta sistemi şu anda çalışmıyor. Admin panelinden manuel doğrulama yapılabilir.',
                    'type' => 'email_verification_failed'
                ]);
            }
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