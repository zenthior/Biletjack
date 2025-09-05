<?php
require_once 'config/database.php';

$database = new Database();
$pdo = $database->getConnection();

// Sepet verilerini kontrol et
$stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = 1");
$stmt->execute();
$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Sepet verileri (user_id = 1):\n";
if (empty($cartItems)) {
    echo "Sepet boş!\n";
} else {
    foreach ($cartItems as $item) {
        echo "ID: {$item['id']}, Event: {$item['event_name']}, Ticket: {$item['ticket_name']}, Price: {$item['price']}, Quantity: {$item['quantity']}\n";
    }
}

// Tüm sepet verilerini kontrol et
$stmt = $pdo->prepare("SELECT * FROM cart");
$stmt->execute();
$allCartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "\nTüm sepet verileri:\n";
if (empty($allCartItems)) {
    echo "Hiç sepet verisi yok!\n";
} else {
    foreach ($allCartItems as $item) {
        echo "User ID: {$item['user_id']}, Event: {$item['event_name']}, Ticket: {$item['ticket_name']}, Price: {$item['price']}, Quantity: {$item['quantity']}\n";
    }
}
?>