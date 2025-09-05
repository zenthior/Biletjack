<?php
require_once 'includes/session.php';

echo "Session bilgileri:\n";
if (isLoggedIn()) {
    echo "Kullanıcı giriş yapmış\n";
    echo "User ID: " . ($_SESSION['user_id'] ?? 'Tanımsız') . "\n";
    echo "User Type: " . ($_SESSION['user_type'] ?? 'Tanımsız') . "\n";
    echo "Username: " . ($_SESSION['username'] ?? 'Tanımsız') . "\n";
} else {
    echo "Kullanıcı giriş yapmamış\n";
}

echo "\nTüm session verileri:\n";
print_r($_SESSION);
?>