<?php
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Google OAuth ayarlarını veritabanından al
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('google_oauth_client_id', 'google_oauth_client_secret')");
    $stmt->execute();
    
    $settings = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    
    // Sadece client_id'yi frontend'e gönder (güvenlik için client_secret gönderme)
    echo json_encode([
        'success' => true,
        'client_id' => $settings['google_oauth_client_id'] ?? ''
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Ayarlar alınamadı'
    ]);
}
?>