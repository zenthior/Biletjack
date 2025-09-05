<?php
session_start();
require_once '../config/database.php';

// Organizatör kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'organizer') {
    http_response_code(403);
    exit('Yetkisiz erişim');
}

$organizer_id = $_SESSION['user_id'];

// Veritabanı bağlantısı
$database = new Database();
$pdo = $database->getConnection();

// QR yetkili bilgilerini al
$stmt = $pdo->prepare("SELECT * FROM qr_staff WHERE organizer_id = ? LIMIT 1");
$stmt->execute([$organizer_id]);
$qr_staff = $stmt->fetch();

if ($qr_staff) {
    // QR panel oturumunu başlat
    $_SESSION['qr_staff_id'] = $qr_staff['id'];
    $_SESSION['qr_organizer_id'] = $organizer_id;
    $_SESSION['qr_staff_name'] = $qr_staff['full_name'];
    
    // QR panele yönlendir
    header('Location: ../qr_panel/index.php');
    exit();
} else {
    // QR yetkili hesabı yoksa hata mesajı
    $_SESSION['error'] = 'QR yetkili hesabı bulunamadı. Önce bir QR yetkili hesabı oluşturun.';
    header('Location: index.php?page=qr_staff');
    exit();
}
?>