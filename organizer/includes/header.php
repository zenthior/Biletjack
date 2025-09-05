<?php
if (!isset($_SESSION)) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organizatör Paneli - BiletJack</title>
    <!-- Favicon -->
    <?php
    // Favicon ayarını veritabanından al
    try {
        require_once __DIR__ . '/../../config/database.php';
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'site_favicon'");
        $stmt->execute();
        $faviconSetting = $stmt->fetchColumn();
        $faviconPath = $faviconSetting ? '../assets/images/' . $faviconSetting : '../assets/images/favicon.ico';
    } catch (Exception $e) {
        $faviconPath = '../assets/images/favicon.ico';
    }
    ?>
    <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars($faviconPath); ?>">
    <link rel="stylesheet" href="../css/organizer-modern.css">
    <link rel="stylesheet" href="../css/organizer2.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="organizer-wrapper">