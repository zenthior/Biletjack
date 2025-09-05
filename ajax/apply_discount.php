<?php
require_once '../includes/session.php';
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Giriş yapmanız gerekiyor']);
    exit;
}

// Role kontrolü (yalnızca müşteri)
if (($_SESSION['user_type'] ?? null) !== 'customer') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Sadece müşteri hesapları indirim uygulayabilir']);
    exit;
}

$code = strtoupper(trim($_POST['code'] ?? ''));
if ($code === '') {
    echo json_encode(['success' => false, 'message' => 'Kod giriniz']);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    $userId = $_SESSION['user_id'];

    // Kodu getir
    $stmt = $pdo->prepare("SELECT id, event_id, discount_amount, quantity, status FROM discount_codes WHERE code = ?");
    $stmt->execute([$code]);
    $dc = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$dc || $dc['status'] !== 'active') {
        echo json_encode(['success' => false, 'message' => 'İndirim kodu geçersiz']);
        exit;
    }

    // Kullanım dolu mu?
    $cntStmt = $pdo->prepare("SELECT COUNT(*) FROM discount_code_usages WHERE discount_code_id = ?");
    $cntStmt->execute([$dc['id']]);
    $usedCount = (int)$cntStmt->fetchColumn();
    if ($usedCount >= (int)$dc['quantity']) {
        echo json_encode(['success' => false, 'message' => 'İndirim kodu süresi bitti veya daha kullanılamıyor']);
        exit;
    }

    // Kullanıcı bu kodu kullanmış mı?
    $userUsed = $pdo->prepare("SELECT 1 FROM discount_code_usages WHERE discount_code_id = ? AND user_id = ? LIMIT 1");
    $userUsed->execute([$dc['id'], $userId]);
    if ($userUsed->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Bu indirim kodunu daha önce kullandınız']);
        exit;
    }

    // Sepette bu etkinliğe ait tutarı hesapla
    $cartStmt = $pdo->prepare("SELECT event_id, price, quantity FROM cart WHERE user_id = ?");
    $cartStmt->execute([$userId]);
    $items = $cartStmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$items) {
        echo json_encode(['success' => false, 'message' => 'Sepetiniz boş']);
        exit;
    }

    $eventSubtotal = 0;
    $totalSubtotal = 0;
    foreach ($items as $it) {
        $line = (float)$it['price'] * (int)$it['quantity'];
        $totalSubtotal += $line;
        if ((int)$it['event_id'] === (int)$dc['event_id']) {
            $eventSubtotal += $line;
        }
    }

    if ($eventSubtotal <= 0) {
        echo json_encode(['success' => false, 'message' => 'Kod yanlış etkinlik için']);
        exit;
    }

    $discount = min((float)$dc['discount_amount'], $eventSubtotal);
    $newSubtotal = max(0, $totalSubtotal - $discount);

    echo json_encode([
        'success' => true,
        'message' => 'İndirim uygulandı',
        'discount' => $discount,
        'subtotal' => $totalSubtotal,
        'newSubtotal' => $newSubtotal,
        'eventId' => (int)$dc['event_id'],
        'code' => $code
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}