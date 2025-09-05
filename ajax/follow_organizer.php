<?php
require_once '../config/database.php';
require_once '../includes/session.php';

header('Content-Type: application/json');

// Kullanıcı giriş kontrolü
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Giriş yapmanız gerekiyor']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$organizerId = isset($input['organizer_id']) ? intval($input['organizer_id']) : 0;
$userId = $_SESSION['user_id'];

if ($organizerId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz organizatör ID']);
    exit;
}

// Kendi profilini takip etmeyi engelle
if ($userId == $organizerId) {
    echo json_encode(['success' => false, 'message' => 'Kendi profilinizi takip edemezsiniz']);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Organizatörün var olup olmadığını kontrol et
    $checkOrganizerStmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND user_type = 'organizer'");
    $checkOrganizerStmt->execute([$organizerId]);
    
    if (!$checkOrganizerStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Organizatör bulunamadı']);
        exit;
    }
    
    // Zaten takip edip etmediğini kontrol et
    $checkFollowStmt = $pdo->prepare("SELECT id FROM followers WHERE user_id = ? AND organizer_id = ?");
    $checkFollowStmt->execute([$userId, $organizerId]);
    $isFollowing = $checkFollowStmt->fetch();
    
    if ($isFollowing) {
        // Takibi bırak
        $unfollowStmt = $pdo->prepare("DELETE FROM followers WHERE user_id = ? AND organizer_id = ?");
        $unfollowStmt->execute([$userId, $organizerId]);
        
        echo json_encode([
            'success' => true, 
            'action' => 'unfollowed',
            'message' => 'Takip bırakıldı',
            'button_text' => '<i class="fas fa-plus"></i> Takip Et'
        ]);
    } else {
        // Takip et
        $followStmt = $pdo->prepare("INSERT INTO followers (user_id, organizer_id) VALUES (?, ?)");
        $followStmt->execute([$userId, $organizerId]);
        
        echo json_encode([
            'success' => true, 
            'action' => 'followed',
            'message' => 'Takip edildi',
            'button_text' => '<i class="fas fa-check"></i> Takip Ediliyor'
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>