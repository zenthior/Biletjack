<?php
require_once '../config/database.php';
require_once '../includes/session.php';

header('Content-Type: application/json');

try {
    $city = isset($_POST['city']) ? trim($_POST['city']) : '';
    if ($city === '') {
        echo json_encode(['success' => false, 'message' => 'Şehir gerekli.', 'items' => []]);
        exit;
    }

    $db = new Database();
    $pdo = $db->getConnection();

    // service_providers: user_id, company_name, city ...
    // users: phone
    $sql = "
        SELECT sp.user_id, sp.company_name, u.phone
        FROM service_providers sp
        JOIN users u ON u.id = sp.user_id
        WHERE sp.city = :city
          AND (u.status IS NULL OR u.status = 'active')
        ORDER BY sp.company_name ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['city' => $city]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    echo json_encode(['success' => true, 'items' => $rows]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage(), 'items' => []]);
}