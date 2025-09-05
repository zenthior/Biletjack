<?php
require_once '../config/database.php';
require_once '../includes/session.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Bilet satın almak için giriş yapmanız gerekiyor.',
            'redirect' => 'login.php'
        ]);
        exit;
    }
    
    // Role kontrolü (yalnızca müşteri)
    if (($_SESSION['user_type'] ?? null) !== 'customer') {
        echo json_encode([
            'success' => false,
            'message' => 'Sadece müşteri hesapları bilet/sepet işlemi yapabilir.'
        ]);
        exit;
    }
    
    $userId = $_SESSION['user_id'];
    $ticketTypeId = $_POST['ticket_type_id'] ?? '';
    $quantity = (int)($_POST['quantity'] ?? 1);
    
    if (empty($ticketTypeId) || $quantity <= 0) {
        throw new Exception('Geçersiz bilet türü veya miktar');
    }
    
    // Get ticket type details
    $ticketQuery = "SELECT tt.*, e.title as event_title, e.event_date, e.venue_name 
                    FROM ticket_types tt 
                    JOIN events e ON tt.event_id = e.id 
                    WHERE tt.id = ? AND tt.is_active = 1 AND e.status = 'published'";
    
    $ticketStmt = $pdo->prepare($ticketQuery);
    $ticketStmt->execute([$ticketTypeId]);
    $ticketType = $ticketStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ticketType) {
        throw new Exception('Bilet türü bulunamadı');
    }
    
    // Check available quantity
    $soldQuery = "SELECT COALESCE(SUM(quantity), 0) as sold_quantity FROM tickets WHERE ticket_type_id = ?";
    $soldStmt = $pdo->prepare($soldQuery);
    $soldStmt->execute([$ticketTypeId]);
    $soldQuantity = $soldStmt->fetchColumn();
    
    $availableQuantity = $ticketType['max_quantity'] - $soldQuantity;
    
    if ($quantity > $availableQuantity) {
        throw new Exception('Yeterli bilet bulunmuyor. Mevcut: ' . $availableQuantity);
    }
    
    // Check if item already exists in cart
    $cartQuery = "SELECT id, quantity FROM cart WHERE user_id = ? AND ticket_type_id = ?";
    $cartStmt = $pdo->prepare($cartQuery);
    $cartStmt->execute([$userId, $ticketTypeId]);
    $existingCart = $cartStmt->fetch(PDO::FETCH_ASSOC);
    
    $pdo->beginTransaction();
    
    if ($existingCart) {
        // Update existing cart item
        $newQuantity = $existingCart['quantity'] + $quantity;
        if ($newQuantity > $availableQuantity) {
            throw new Exception('Sepetinizdeki miktarla birlikte yeterli bilet bulunmuyor.');
        }
        
        $updateQuery = "UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ?";
        $updateStmt = $pdo->prepare($updateQuery);
        $updateStmt->execute([$newQuantity, $existingCart['id']]);
    } else {
        // Add new cart item
        $insertQuery = "INSERT INTO cart (user_id, ticket_type_id, quantity, price, created_at, updated_at) 
                        VALUES (?, ?, ?, ?, NOW(), NOW())";
        $insertStmt = $pdo->prepare($insertQuery);
        $insertStmt->execute([$userId, $ticketTypeId, $quantity, $ticketType['price']]);
    }
    
    $pdo->commit();
    
    // Get cart count for user
    $countQuery = "SELECT SUM(quantity) as total_items FROM cart WHERE user_id = ?";
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute([$userId]);
    $cartCount = $countStmt->fetchColumn() ?: 0;
    
    echo json_encode([
        'success' => true,
        'message' => 'Biletler sepete eklendi!',
        'cart_count' => $cartCount,
        'redirect' => 'sepet.php'
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>