<?php
require_once 'config/database.php';

$database = new Database();
$pdo = $database->getConnection();

// Events tablosundaki verileri kontrol et
$stmt = $pdo->prepare("SELECT id, title FROM events LIMIT 5");
$stmt->execute();
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Mevcut etkinlikler:\n";
if (empty($events)) {
    echo "Hiç etkinlik yok!\n";
} else {
    foreach ($events as $event) {
        echo "ID: {$event['id']}, Title: {$event['title']}\n";
    }
}

// Ticket types tablosunu da kontrol et
$stmt = $pdo->prepare("SELECT id, name FROM ticket_types LIMIT 5");
$stmt->execute();
$ticketTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "\nMevcut bilet tipleri:\n";
if (empty($ticketTypes)) {
    echo "Hiç bilet tipi yok!\n";
} else {
    foreach ($ticketTypes as $type) {
        echo "ID: {$type['id']}, Name: {$type['name']}\n";
    }
}
?>