-- Organizatör ad değişiklik talepleri tablosu
CREATE TABLE IF NOT EXISTS organizer_name_change_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organizer_id INT NOT NULL,
    current_name VARCHAR(255) NOT NULL,
    requested_name VARCHAR(255) NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_response TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organizer_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Index'ler
CREATE INDEX idx_organizer_id ON organizer_name_change_requests(organizer_id);
CREATE INDEX idx_status ON organizer_name_change_requests(status);
CREATE INDEX idx_created_at ON organizer_name_change_requests(created_at);