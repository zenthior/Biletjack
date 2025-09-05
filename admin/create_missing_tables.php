<?php
require_once '../includes/session.php';
require_once '../config/database.php';

// Admin kontrolü
requireAdmin();

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Event Comments Tablosu
    $createEventComments = "
        CREATE TABLE IF NOT EXISTS event_comments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_id INT NOT NULL,
            user_id INT NOT NULL,
            comment TEXT NOT NULL,
            rating TINYINT(1) CHECK (rating >= 1 AND rating <= 5),
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ";
    
    // Event Followers Tablosu
    $createEventFollowers = "
        CREATE TABLE IF NOT EXISTS event_followers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_id INT NOT NULL,
            user_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_event_follow (event_id, user_id)
        )
    ";
    
    // Event Images Tablosu
    $createEventImages = "
        CREATE TABLE IF NOT EXISTS event_images (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_id INT NOT NULL,
            image_path VARCHAR(255) NOT NULL,
            is_primary BOOLEAN DEFAULT FALSE,
            alt_text VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
        )
    ";
    
    // Organizers Tablosu (get_event_details.php'de kullanılan)
    $createOrganizers = "
        CREATE TABLE IF NOT EXISTS organizers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            company_name VARCHAR(255) NOT NULL,
            phone VARCHAR(20),
            website VARCHAR(255),
            instagram VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ";
    
    // Events tablosuna eksik alanları ekle
    $alterEvents = [
        "ALTER TABLE events ADD COLUMN IF NOT EXISTS views INT DEFAULT 0",
        "ALTER TABLE events ADD COLUMN IF NOT EXISTS tags TEXT",
        "ALTER TABLE events ADD COLUMN IF NOT EXISTS artists TEXT",
        "ALTER TABLE events ADD COLUMN IF NOT EXISTS capacity INT"
    ];
    
    // Tabloları oluştur
    $pdo->exec($createEventComments);
    echo "event_comments tablosu oluşturuldu.<br>";
    
    $pdo->exec($createEventFollowers);
    echo "event_followers tablosu oluşturuldu.<br>";
    
    $pdo->exec($createEventImages);
    echo "event_images tablosu oluşturuldu.<br>";
    
    // Event Views Tablosu (benzersiz görüntülenmeler için)
    $createEventViews = "
        CREATE TABLE IF NOT EXISTS event_views (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_id INT NOT NULL,
            user_id INT NULL,
            session_id VARCHAR(128) NULL,
            ip_address VARCHAR(64) NULL,
            user_agent VARCHAR(255) NULL,
            viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
            INDEX idx_event (event_id),
            INDEX idx_user (user_id),
            INDEX idx_session (session_id)
        )
    ";
    try {
        $pdo->exec($createEventViews);
        echo "event_views tablosu oluşturuldu.<br>";
    } catch (Exception $e) {
        echo "event_views oluşturma hatası: " . $e->getMessage() . "<br>";
    }

    // Events tablosunu güncelle
    foreach ($alterEvents as $alterQuery) {
        try {
            $pdo->exec($alterQuery);
            echo "Events tablosu güncellendi: " . $alterQuery . "<br>";
        } catch (Exception $e) {
            // Sütun zaten varsa hata vermez
            echo "Sütun zaten mevcut veya hata: " . $e->getMessage() . "<br>";
        }
    }
    
    // Organizer_details tablosundaki verileri organizers tablosuna kopyala
    $copyData = "
        INSERT IGNORE INTO organizers (user_id, company_name, phone, website, instagram)
        SELECT user_id, company_name, phone, website, instagram_url
        FROM organizer_details
        WHERE approval_status = 'approved'
    ";
    
    try {
        $pdo->exec($copyData);
        echo "Organizatör verileri kopyalandı.<br>";
    } catch (Exception $e) {
        echo "Veri kopyalama hatası: " . $e->getMessage() . "<br>";
    }

    // İndirim Kodları Tabloları
    $createDiscountCodes = "
        CREATE TABLE IF NOT EXISTS discount_codes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_id INT NOT NULL,
            code VARCHAR(64) NOT NULL,
            discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            quantity INT NOT NULL DEFAULT 0,
            status ENUM('active','inactive') NOT NULL DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_code (code),
            INDEX idx_event (event_id),
            FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
        )
    ";
    $pdo->exec($createDiscountCodes);
    echo "discount_codes tablosu oluşturuldu.<br>";

    $createDiscountCodeUsages = "
        CREATE TABLE IF NOT EXISTS discount_code_usages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            discount_code_id INT NOT NULL,
            user_id INT NOT NULL,
            used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_code (discount_code_id, user_id),
            INDEX idx_code (discount_code_id),
            INDEX idx_user (user_id),
            FOREIGN KEY (discount_code_id) REFERENCES discount_codes(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ";
    $pdo->exec($createDiscountCodeUsages);
    echo "discount_code_usages tablosu oluşturuldu.<br>";
    
    // Rezervasyon Sistemi: events.seating_type sütununu 'reservation' değerini içerecek şekilde güncelle
    try {
        $pdo->exec("ALTER TABLE events MODIFY COLUMN seating_type ENUM('general','seated','reservation') DEFAULT 'general'");
        echo "events.seating_type sütunu 'reservation' değeri ile güncellendi.<br>";
    } catch (Exception $e) {
        echo "seating_type güncelleme hatası: " . $e->getMessage() . "<br>";
    }

    // Rezervasyonlar Tablosu
    $createReservations = "
        CREATE TABLE IF NOT EXISTS reservations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_id INT NOT NULL,
            seat_id INT NOT NULL,
            user_id INT NOT NULL,
            status ENUM('pending','approved','rejected') DEFAULT 'pending',
            notes VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            approved_at TIMESTAMP NULL,
            approved_by INT NULL,
            FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
            FOREIGN KEY (seat_id) REFERENCES seats(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_event (event_id),
            INDEX idx_user (user_id),
            INDEX idx_status (status),
            UNIQUE KEY uniq_seat_active (seat_id, status)
        )
    ";
    try {
        $pdo->exec($createReservations);
        echo "reservations tablosu oluşturuldu.<br>";
    } catch (Exception $e) {
        echo "reservations oluşturma hatası: " . $e->getMessage() . "<br>";
    }
    
    echo "<br><strong>Tüm eksik tablolar başarıyla oluşturuldu!</strong><br>";
    echo "<a href='events.php'>Etkinlikler sayfasına dön</a>";
    
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage();
}
?>