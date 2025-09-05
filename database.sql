-- BiletJack Veritabanı Yapısı
CREATE DATABASE IF NOT EXISTS biletjack;
USE biletjack;

-- Kullanıcılar Tablosu
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    user_type ENUM('customer', 'organizer', 'admin', 'service', 'ad_agency') DEFAULT 'customer',
    status ENUM('active', 'pending', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    email_verified BOOLEAN DEFAULT FALSE,
    whatsapp_verified BOOLEAN DEFAULT FALSE,
    profile_image VARCHAR(255) NULL,
    google_id VARCHAR(255) NULL UNIQUE
);

-- Organizatör Detayları Tablosu
CREATE TABLE organizer_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    company_name VARCHAR(255) NOT NULL,
    tax_number VARCHAR(50),
    address TEXT,
    city VARCHAR(100),
    website VARCHAR(255),
    description TEXT,
    about TEXT,
    event_types TEXT,
    logo_url VARCHAR(255),
    cover_image_url VARCHAR(255),
    phone VARCHAR(20),
    email VARCHAR(255),
    facebook_url VARCHAR(255),
    instagram_url VARCHAR(255),
    approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    rejection_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Sepet Tablosu
CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    event_id INT NOT NULL,
    ticket_type_id INT NOT NULL,
    event_name VARCHAR(255) NOT NULL,
    ticket_name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (ticket_type_id) REFERENCES ticket_types(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_ticket (user_id, event_id, ticket_type_id)
);

-- Organizatör ad değişiklik talepleri tablosu
CREATE TABLE organizer_name_change_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organizer_id INT NOT NULL,
    current_name VARCHAR(255) NOT NULL,
    requested_name VARCHAR(255) NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_response TEXT,
    processed_by INT NULL,
    processed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organizer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Mevcut organizatör tablosuna yeni alanlar eklemek için ALTER komutları
-- (Eğer tablo zaten varsa bu komutları çalıştırın)
/*
ALTER TABLE organizer_details ADD COLUMN about TEXT AFTER description;
ALTER TABLE organizer_details ADD COLUMN event_types TEXT AFTER about;
ALTER TABLE organizer_details ADD COLUMN logo_url VARCHAR(255) AFTER event_types;
ALTER TABLE organizer_details ADD COLUMN cover_image_url VARCHAR(255) AFTER logo_url;
ALTER TABLE organizer_details ADD COLUMN phone VARCHAR(20) AFTER cover_image_url;
ALTER TABLE organizer_details ADD COLUMN email VARCHAR(255) AFTER phone;
ALTER TABLE organizer_details ADD COLUMN facebook_url VARCHAR(255) AFTER email;
ALTER TABLE organizer_details ADD COLUMN instagram_url VARCHAR(255) AFTER facebook_url;
*/

-- Kategoriler Tablosu
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    icon VARCHAR(50),
    color VARCHAR(7),
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Etkinlikler Tablosu
CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organizer_id INT NOT NULL,
    category_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    short_description VARCHAR(500),
    event_date DATETIME NOT NULL,
    end_date DATETIME NULL,
    venue_name VARCHAR(255) NOT NULL,
    venue_address TEXT,
    city VARCHAR(100) NOT NULL,
    min_price DECIMAL(10,2),
    max_price DECIMAL(10,2),
    total_capacity INT DEFAULT 0,
    available_tickets INT DEFAULT 0,
    image_url VARCHAR(255),
    artist_image_url VARCHAR(255),
    gallery JSON NULL,
    status ENUM('draft', 'published', 'cancelled', 'completed') DEFAULT 'draft',
    is_featured BOOLEAN DEFAULT FALSE,
    meta_title VARCHAR(255),
    meta_description VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organizer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Bilet Türleri Tablosu
CREATE TABLE ticket_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL,
    sold_quantity INT DEFAULT 0,
    max_per_order INT DEFAULT 10,
    sale_start DATETIME,
    sale_end DATETIME,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
);

-- Siparişler Tablosu
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    payment_method VARCHAR(50),
    payment_reference VARCHAR(255),
    billing_info JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Biletler Tablosu
CREATE TABLE tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    event_id INT NOT NULL,
    ticket_type_id INT NOT NULL,
    ticket_number VARCHAR(50) UNIQUE NOT NULL,
    attendee_name VARCHAR(255),
    attendee_email VARCHAR(255),
    attendee_phone VARCHAR(20),
    price DECIMAL(10,2) NOT NULL,
    status ENUM('active', 'used', 'cancelled', 'refunded') DEFAULT 'active',
    qr_code VARCHAR(255),
    used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (event_id) REFERENCES events(id),
    FOREIGN KEY (ticket_type_id) REFERENCES ticket_types(id)
);

-- Sistem Ayarları Tablosu
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Aktivite Logları Tablosu
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Kategoriler Ekle
INSERT INTO categories (name, slug, icon, color, description) VALUES
('Konser', 'konser', '🎵', '#667eea', 'Müzik konserleri ve performansları'),
('Tiyatro', 'tiyatro', '🎭', '#4facfe', 'Tiyatro oyunları ve sahne sanatları'),
('Festival', 'festival', '🎉', '#43e97b', 'Festivaller ve büyük etkinlikler'),
('Çocuk', 'cocuk', '🎈', '#ffecd2', 'Çocuk etkinlikleri ve gösterileri'),
('Standup', 'standup', '🎤', '#fa709a', 'Stand-up komedi gösterileri');

-- Sistem Ayarları
INSERT INTO settings (setting_key, setting_value, description) VALUES
('site_name', 'BiletJack', 'Site adı'),
('site_email', 'info@biletjack.com', 'Site e-posta adresi'),
('commission_rate', '5', 'Komisyon oranı (%)'),
('currency', 'TRY', 'Para birimi'),
('timezone', 'Europe/Istanbul', 'Zaman dilimi');

-- Organizatör Detayları Ekle
INSERT INTO organizer_details (user_id, company_name, tax_number, address, city, website, description, approval_status, approved_by, approved_at)
VALUES (2, 'Test Event Company', '1234567890', 'Test Address', 'İstanbul', 'www.test.com', 'Test organizatör şirketi', 'approved', 1, NOW());

-- Users tablosuna remember_token sütunu ekle
ALTER TABLE users ADD COLUMN remember_token VARCHAR(64) NULL;

-- Index ekle
CREATE INDEX idx_remember_token ON users(remember_token);

-- Google OAuth için google_id sütunu ekle
ALTER TABLE users ADD COLUMN google_id VARCHAR(255) NULL UNIQUE;

-- Google ID için index ekle
CREATE INDEX idx_google_id ON users(google_id);

-- WhatsApp doğrulama tablosu
CREATE TABLE IF NOT EXISTS whatsapp_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone VARCHAR(20) NOT NULL,
    code VARCHAR(6) NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    UNIQUE KEY unique_phone (phone),
    INDEX idx_phone_code (phone, code),
    INDEX idx_token (token),
    INDEX idx_expires (expires_at)
);

-- Users tablosuna whatsapp_verified kolonu ekle
ALTER TABLE users ADD COLUMN whatsapp_verified TINYINT(1) DEFAULT 0 AFTER google_id;

-- WhatsApp verified için indeks oluştur
CREATE INDEX idx_users_whatsapp_verified ON users(whatsapp_verified);

-- Takipçi Sistemi Tablosu
CREATE TABLE followers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    follower_id INT NOT NULL,
    organizer_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (organizer_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_follow (follower_id, organizer_id)
);

-- Events tablosuna seating_type sütunu ekle (events tablosu tanımından sonra)
ALTER TABLE events ADD COLUMN seating_type ENUM('general', 'seated') DEFAULT 'general' AFTER max_price;
ALTER TABLE events ADD COLUMN event_rules TEXT AFTER seating_type;

-- Koltuk Kategorileri Tablosu
CREATE TABLE seat_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    color VARCHAR(7) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
);

-- Koltuklar Tablosu
CREATE TABLE seats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    row_number INT NOT NULL,
    seat_number INT NOT NULL,
    category_name VARCHAR(100) DEFAULT 'standard',
    category_id INT NULL,
    status ENUM('available', 'occupied', 'reserved') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES seat_categories(id) ON DELETE SET NULL,
    UNIQUE KEY unique_seat (event_id, row_number, seat_number)
);