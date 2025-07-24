<?php
// Session'ı başlat
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Veritabanı bağlantısını dahil et
require_once __DIR__ . '/../config/database.php';

// Global PDO bağlantısını oluştur
if (!isset($pdo)) {
    $database = new Database();
    $pdo = $database->getConnection();
}

/**
 * Kullanıcının giriş yapıp yapmadığını kontrol eder
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
}

/**
 * Kullanıcının belirli bir role sahip olup olmadığını kontrol eder
 */
function hasRole($role) {
    return isLoggedIn() && $_SESSION['user_type'] === $role;
}

/**
 * Admin kontrolü (basit)
 */
function isAdmin() {
    return hasRole('admin');
}

/**
 * Organizatör kontrolü (basit)
 */
function isOrganizer() {
    return hasRole('organizer');
}

/**
 * Müşteri kontrolü (basit)
 */
function isCustomer() {
    return hasRole('customer');
}

/**
 * Organizatör onay durumunu kontrol et
 */
function isOrganizerApproved() {
    if (!isLoggedIn() || $_SESSION['user_type'] !== 'organizer') {
        return false;
    }
    
    require_once __DIR__ . '/../config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    $query = "SELECT od.approval_status, u.email_verified, u.status 
              FROM organizer_details od 
              JOIN users u ON od.user_id = u.id 
              WHERE od.user_id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['approval_status'] === 'approved' && 
               $row['email_verified'] == 1 && 
               $row['status'] === 'active';
    }
    
    return false;
}

/**
 * Mevcut kullanıcı bilgilerini getir
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Kullanıcı session'ını başlat
 */
function startUserSession($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_type'] = $user['user_type'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_status'] = $user['status'];
}

/**
 * Admin kontrolü
 */
function requireAdmin() {
    if (!hasRole('admin')) {
        header('Location: /Biletjack/index.php');
        exit();
    }
}

/**
 * Organizatör kontrolü
 */
function requireOrganizer() {
    if (!hasRole('organizer')) {
        header('Location: /Biletjack/index.php');
        exit();
    }
    
    // Organizatör onay durumunu kontrol et
    global $pdo;
    $stmt = $pdo->prepare("SELECT status FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if ($user['status'] === 'pending') {
        header('Location: /Biletjack/organizer/pending.php');
        exit();
    } elseif ($user['status'] === 'rejected') {
        session_destroy();
        header('Location: /Biletjack/index.php?error=account_rejected');
        exit();
    } elseif ($user['status'] === 'suspended') {
        session_destroy();
        header('Location: /Biletjack/index.php?error=account_suspended');
        exit();
    }
}

/**
 * Müşteri kontrolü
 */
function requireCustomer() {
    if (!hasRole('customer')) {
        header('Location: /Biletjack/index.php');
        exit();
    }
}

/**
 * Giriş yapmış kullanıcıları ana sayfaya yönlendir
 */
function redirectIfLoggedIn() {
    if (isLoggedIn()) {
        // Tüm kullanıcı türleri için ana sayfaya yönlendir
        header('Location: /Biletjack/index.php');
        exit();
    }
}

/**
 * Kullanıcı bilgilerini session'a kaydet
 */
function setUserSession($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_type'] = $user['user_type'];
    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_status'] = $user['status'];
}

/**
 * Session'ı temizle
 */
function clearUserSession() {
    unset($_SESSION['user_id']);
    unset($_SESSION['user_type']);
    unset($_SESSION['user_name']);
    unset($_SESSION['user_email']);
    unset($_SESSION['user_status']);
}

/**
 * Kullanıcıyı rolüne göre yönlendir
 */
function redirectToDashboard() {
    if (!isLoggedIn()) {
        header('Location: /Biletjack/index.php');
        exit();
    }
    
    switch ($_SESSION['user_type']) {
        case 'admin':
            header('Location: /Biletjack/admin/index.php');
            break;
        case 'organizer':
            // Organizatör durumunu kontrol et
            global $pdo;
            $stmt = $pdo->prepare("SELECT status FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            
            if ($user['status'] === 'pending') {
                header('Location: /Biletjack/organizer/pending.php');
            } elseif ($user['status'] === 'approved') {
                header('Location: /Biletjack/organizer/index.php');
            } else {
                session_destroy();
                header('Location: /Biletjack/index.php?error=account_issue');
            }
            break;
        case 'customer':
            header('Location: /Biletjack/customer/index.php');
            break;
        default:
            header('Location: /Biletjack/index.php');
    }
    exit();
}

/**
 * Sayfa erişim kontrolü
 */
function checkPageAccess($requiredRole = null) {
    if ($requiredRole) {
        switch ($requiredRole) {
            case 'admin':
                requireAdmin();
                break;
            case 'organizer':
                requireOrganizer();
                break;
            case 'customer':
                requireCustomer();
                break;
        }
    }
}

/**
 * Güvenli çıkış
 */
function logout() {
    session_start();
    session_unset();
    session_destroy();
    header('Location: /Biletjack/index.php?message=logged_out');
    exit();
}
?>