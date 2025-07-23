<?php
$password = 'koroglumedia';
$newHash = password_hash($password, PASSWORD_DEFAULT);
echo "Yeni hash: " . $newHash . "\n";
echo "Test doğrulama: " . (password_verify($password, $newHash) ? 'BAŞARILI' : 'BAŞARISIZ') . "\n";
?>