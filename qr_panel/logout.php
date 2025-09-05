<?php
session_start();

// QR staff session'ını temizle
unset($_SESSION['qr_staff_id']);
unset($_SESSION['qr_staff_username']);
unset($_SESSION['qr_staff_name']);
unset($_SESSION['qr_organizer_id']);

// Session'ı tamamen yok et
session_destroy();

// Login sayfasına yönlendir
header('Location: login.php');
exit();
?>