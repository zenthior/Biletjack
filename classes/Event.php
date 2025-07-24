<?php

class Event {
    private $conn;
    private $table_name = "events";
    
    public $id;
    public $organizer_id;
    public $category_id;
    public $title;
    public $slug;
    public $description;
    public $short_description;
    public $event_date;
    public $end_date;
    public $venue_name;
    public $venue_address;
    public $city;
    public $min_price;
    public $max_price;
    public $total_capacity;
    public $available_tickets;
    public $image_url;
    public $status;
    public $is_featured;
    public $created_at;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Tüm etkinlikleri getir
    public function getAllEvents($limit = null, $offset = 0, $search = '', $status = '', $category = '') {
        $query = "SELECT e.*, c.name as category_name, u.first_name, u.last_name, od.company_name 
                 FROM " . $this->table_name . " e
                 LEFT JOIN categories c ON e.category_id = c.id
                 LEFT JOIN users u ON e.organizer_id = u.id
                 LEFT JOIN organizer_details od ON u.id = od.user_id
                 WHERE 1=1";
        
        $params = [];
        
        if (!empty($search)) {
            $query .= " AND (e.title LIKE ? OR e.venue_name LIKE ? OR e.city LIKE ?)";
            $searchParam = '%' . $search . '%';
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        
        if (!empty($status)) {
            $query .= " AND e.status = ?";
            $params[] = $status;
        }
        
        if (!empty($category)) {
            $query .= " AND e.category_id = ?";
            $params[] = $category;
        }
        
        $query .= " ORDER BY e.created_at DESC";
        
        if ($limit) {
            $query .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Toplam etkinlik sayısı
    public function getTotalEvents($search = '', $status = '', $category = '') {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " e WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $query .= " AND (e.title LIKE ? OR e.venue_name LIKE ? OR e.city LIKE ?)";
            $searchParam = '%' . $search . '%';
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        
        if (!empty($status)) {
            $query .= " AND e.status = ?";
            $params[] = $status;
        }
        
        if (!empty($category)) {
            $query .= " AND e.category_id = ?";
            $params[] = $category;
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }
    
    // ID'ye göre etkinlik getir
    public function getEventById($id) {
        $query = "SELECT e.*, c.name as category_name, u.first_name, u.last_name, od.company_name 
                 FROM " . $this->table_name . " e
                 LEFT JOIN categories c ON e.category_id = c.id
                 LEFT JOIN users u ON e.organizer_id = u.id
                 LEFT JOIN organizer_details od ON u.id = od.user_id
                 WHERE e.id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Etkinlik durumunu güncelle
    public function updateStatus($id, $status) {
        $query = "UPDATE " . $this->table_name . " SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$status, $id]);
    }
    
    // Etkinlik sil
    public function deleteEvent($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id]);
    }
    
    // Öne çıkarma durumunu güncelle
    public function updateFeatured($id, $is_featured) {
        $query = "UPDATE " . $this->table_name . " SET is_featured = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$is_featured, $id]);
    }
    
    // Etkinlik istatistikleri
    public function getEventStats() {
        $stats = [];
        
        // Toplam etkinlik
        $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM " . $this->table_name);
        $stmt->execute();
        $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Aktif etkinlikler
        $stmt = $this->conn->prepare("SELECT COUNT(*) as active FROM " . $this->table_name . " WHERE status = 'published'");
        $stmt->execute();
        $stats['active'] = $stmt->fetch(PDO::FETCH_ASSOC)['active'];
        
        // Taslak etkinlikler
        $stmt = $this->conn->prepare("SELECT COUNT(*) as draft FROM " . $this->table_name . " WHERE status = 'draft'");
        $stmt->execute();
        $stats['draft'] = $stmt->fetch(PDO::FETCH_ASSOC)['draft'];
        
        // Bu ay eklenen etkinlikler
        $stmt = $this->conn->prepare("SELECT COUNT(*) as this_month FROM " . $this->table_name . " WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
        $stmt->execute();
        $stats['this_month'] = $stmt->fetch(PDO::FETCH_ASSOC)['this_month'];
        
        return $stats;
    }
    
    // Kategorileri getir
    public function getCategories() {
        $query = "SELECT * FROM categories WHERE is_active = 1 ORDER BY name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Yayında olan etkinlikleri getir (ana sayfa ve etkinlikler sayfası için)
    public function getPublishedEvents($limit = null, $offset = 0, $search = '', $category = '', $city = '') {
        $query = "SELECT e.*, c.name as category_name, u.first_name, u.last_name, od.company_name 
                 FROM " . $this->table_name . " e
                 LEFT JOIN categories c ON e.category_id = c.id
                 LEFT JOIN users u ON e.organizer_id = u.id
                 LEFT JOIN organizer_details od ON u.id = od.user_id
                 WHERE e.status = 'published' AND e.event_date >= CURDATE()";
        
        $params = [];
        
        if (!empty($search)) {
            $query .= " AND (e.title LIKE ? OR e.venue_name LIKE ? OR e.city LIKE ?)";
            $searchParam = '%' . $search . '%';
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        
        if (!empty($category)) {
            $query .= " AND e.category_id = ?";
            $params[] = $category;
        }
        
        if (!empty($city)) {
            $query .= " AND e.city LIKE ?";
            $params[] = '%' . $city . '%';
        }
        
        $query .= " ORDER BY e.is_featured DESC, e.event_date ASC";
        
        if ($limit) {
            $query .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Öne çıkan etkinlikleri getir
    public function getFeaturedEvents($limit = 6) {
        $query = "SELECT e.*, c.name as category_name 
                 FROM " . $this->table_name . " e
                 LEFT JOIN categories c ON e.category_id = c.id
                 WHERE e.status = 'published' AND e.is_featured = 1 AND e.event_date >= CURDATE()
                 ORDER BY e.event_date ASC
                 LIMIT ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}