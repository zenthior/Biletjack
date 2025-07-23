<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Müşteri Paneli - BiletJack</title>
    <link rel="stylesheet" href="../css/customer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-wrapper">
        <nav class="sidebar">
            <div class="sidebar-header">
                <img src="../uploads/logo.png" alt="BiletJack" class="logo">
                <h3>Müşteri Paneli</h3>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i> Dashboard
                </a></li>
                <li><a href="tickets.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'tickets.php' ? 'active' : ''; ?>">
                    <i class="fas fa-ticket-alt"></i> Biletlerim
                </a></li>
                <li><a href="profile.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i> Profilim
                </a></li>
                <li><a href="../etkinlikler.php">
                    <i class="fas fa-calendar"></i> Etkinlikler
                </a></li>
                <li><a href="../index.php?from_panel=1">
                    <i class="fas fa-home"></i> Ana Sayfa
                </a></li>
                <li><a href="../auth/logout.php">
                    <i class="fas fa-sign-out-alt"></i> Çıkış Yap
                </a></li>
            </ul>
        </nav>
        
        <main class="main-content">