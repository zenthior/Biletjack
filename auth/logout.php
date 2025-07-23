<?php
require_once '../includes/session.php';
require_once '../config/database.php';

// Remember token'ı temizle
if (isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    
    // Veritabanından token'ı temizle
    $stmt = $pdo->prepare("UPDATE users SET remember_token = NULL WHERE remember_token = ?");
    $stmt->execute([$token]);
    
    // Cookie'yi sil
    setcookie('remember_token', '', time() - 3600, '/', '', false, true);
}

// Session'ı temizle ve çıkış yap
logout();
?>
header('Location: /');
exit();
?>