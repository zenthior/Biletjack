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
    user_type ENUM('customer', 'organizer', 'admin') DEFAULT 'customer',
    status ENUM('active', 'pending', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    email_verified BOOLEAN DEFAULT FALSE,
    profile_image VARCHAR(255) NULL
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
    approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    rejection_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);

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

-- Başlangıç Verileri

-- Admin Kullanıcısı Oluştur (şifre: admin123)
INSERT INTO users (email, password, first_name, last_name, user_type, status, email_verified) 
VALUES ('admin@biletjack.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', 'admin', 'active', TRUE);

-- Kategoriler Ekle
INSERT INTO categories (name, slug, icon, color, description) VALUES
('Konser', 'konser', '🎵', '#667eea', 'Müzik konserleri ve performansları'),
('Spor', 'spor', '⚽', '#f093fb', 'Spor müsabakaları ve etkinlikleri'),
('Tiyatro', 'tiyatro', '🎭', '#4facfe', 'Tiyatro oyunları ve sahne sanatları'),
('Festival', 'festival', '🎉', '#43e97b', 'Festivaller ve büyük etkinlikler'),
('Çocuk', 'cocuk', '🎈', '#ffecd2', 'Çocuk etkinlikleri ve gösterileri'),
('Eğlence', 'eglence', '🎪', '#fa709a', 'Eğlence ve show programları');

-- Sistem Ayarları
INSERT INTO settings (setting_key, setting_value, description) VALUES
('site_name', 'BiletJack', 'Site adı'),
('site_email', 'info@biletjack.com', 'Site e-posta adresi'),
('commission_rate', '5', 'Komisyon oranı (%)'),
('currency', 'TRY', 'Para birimi'),
('timezone', 'Europe/Istanbul', 'Zaman dilimi');


-- Users tablosuna remember_token sütunu ekle
ALTER TABLE users ADD COLUMN remember_token VARCHAR(64) NULL;

-- Index ekle
CREATE INDEX idx_remember_token ON users(remember_token);