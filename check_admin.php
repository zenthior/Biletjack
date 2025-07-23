<?php
require_once 'config/database.php';

$database = new Database();
$pdo = $database->getConnection();

$email = 'admin@biletjack.com';
$password = 'admin123';

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user) {
    echo "Kullanıcı bulundu: " . $user['email'] . "\n";
    echo "Şifre doğrulama: " . (password_verify($password, $user['password']) ? 'BAŞARILI' : 'BAŞARISIZ') . "\n";
    echo "Hash: " . $user['password'] . "\n";
} else {
    echo "Kullanıcı bulunamadı!\n";
}
?>