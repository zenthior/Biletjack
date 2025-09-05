<?php
require_once 'config/database.php';
require_once 'classes/Ticket.php';

$database = new Database();
$db = $database->getConnection();
$ticket = new Ticket($db);

$message = '';
$ticketData = null;

if ($_POST && isset($_POST['ticket_number'])) {
    $ticketNumber = trim($_POST['ticket_number']);
    
    if (!empty($ticketNumber)) {
        // Bilet numarasını doğrula
        $stmt = $db->prepare("\n            SELECT t.*, e.title as event_title, e.event_date as event_date, e.venue_name as event_location\n            FROM tickets t\n            JOIN events e ON t.event_id = e.id\n            WHERE t.ticket_number = ? AND t.status = 'active'\n        ");
        
        $stmt->execute([$ticketNumber]);
        $ticketData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($ticketData) {
            $message = '<div class="alert alert-success">✅ Geçerli bilet! Bilet doğrulandı.</div>';
        } else {
            $message = '<div class="alert alert-danger">❌ Geçersiz bilet numarası veya bilet kullanılmış!</div>';
        }
    } else {
        $message = '<div class="alert alert-warning">⚠️ Lütfen bilet numarasını girin!</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Kod Doğrulama - BiletJack</title>
    <?php
    // Favicon ayarını veritabanından al
    try {
        $stmt = $db->prepare("SELECT site_favicon FROM site_settings WHERE id = 1");
        $stmt->execute();
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        $favicon = $settings['site_favicon'] ?? 'assets/images/favicon.ico';
        echo '<link rel="icon" type="image/x-icon" href="' . htmlspecialchars($favicon) . '">';
    } catch (Exception $e) {
        echo '<link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">';
    }
    ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .qr-scanner {
            max-width: 500px;
            margin: 0 auto;
            padding: 20px;
        }
        .alert {
            margin-top: 20px;
        }
        .ticket-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="qr-scanner">
            <h2 class="text-center mb-4">🎫 QR Kod Doğrulama</h2>
            
            <form method="POST" class="mb-4">
                <div class="mb-3">
                    <label for="ticket_number" class="form-label">Bilet Numarası:</label>
                    <input type="text" class="form-control" id="ticket_number" name="ticket_number" 
                           placeholder="Örn: BJ2025BDB1C176" value="<?= isset($_POST['ticket_number']) ? htmlspecialchars($_POST['ticket_number']) : '' ?>">
                    <div class="form-text">QR koddan okunan bilet numarasını buraya girin</div>
                </div>
                <button type="submit" class="btn btn-primary w-100">Bileti Doğrula</button>
            </form>
            
            <?= $message ?>
            
            <?php if ($ticketData): ?>
            <div class="ticket-info">
                <h4>📋 Bilet Bilgileri</h4>
                <p><strong>Bilet ID:</strong> <?= $ticketData['id'] ?></p>
                <p><strong>Bilet Numarası:</strong> <?= $ticketData['ticket_number'] ?></p>
                <p><strong>Etkinlik:</strong> <?= $ticketData['event_title'] ?></p>
                <p><strong>Tarih:</strong> <?= date('d.m.Y H:i', strtotime($ticketData['event_date'])) ?></p>
                <p><strong>Konum:</strong> <?= $ticketData['event_location'] ?></p>
                <p><strong>Durum:</strong> <span class="badge bg-success"><?= ucfirst($ticketData['status']) ?></span></p>
                <p><strong>Satın Alma:</strong> <?= date('d.m.Y H:i', strtotime($ticketData['created_at'])) ?></p>
            </div>
            <?php endif; ?>
            
            <div class="mt-4 text-center">
                <a href="index.php" class="btn btn-secondary">Ana Sayfaya Dön</a>
            </div>
            
            <div class="mt-4">
                <h5>🔍 Test için Örnek Bilet Numaraları:</h5>
                <div class="list-group">
                    <?php
                    // Son 5 aktif bileti göster
                    $stmt = $db->query("SELECT ticket_number FROM tickets WHERE status = 'active' ORDER BY created_at DESC LIMIT 5");
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        echo '<button class="list-group-item list-group-item-action" onclick="document.getElementById(\'ticket_number\').value=\''. $row['ticket_number'] .'\'">'. $row['ticket_number'] .'</button>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>