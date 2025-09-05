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

    // ad_agencies: user_id, company_name, city ...
    // users: phone
    $sql = "
        SELECT aa.user_id, aa.company_name, u.phone
        FROM ad_agencies aa
        JOIN users u ON u.id = aa.user_id
        WHERE aa.city = :city
          AND (u.status IS NULL OR u.status = 'active')
        ORDER BY aa.company_name ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['city' => $city]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    echo json_encode(['success' => true, 'items' => $rows]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage(), 'items' => []]);
}