<?php
// Bu satırı kaldırın çünkü database bağlantısı constructor'da geçiliyor
// require_once '../config/database.php';

class Organizer {
    private $conn;
    private $table_name = "organizer_details";
    
    public $id;
    public $user_id;
    public $company_name;
    public $tax_number;
    public $address;
    public $city;
    public $website;
    public $description;
    public $approval_status;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Organizatör detaylarını kaydet
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET user_id=:user_id, company_name=:company_name, tax_number=:tax_number,
                      address=:address, city=:city, website=:website, description=:description";
        
        $stmt = $this->conn->prepare($query);
        
        // Verileri temizle
        $this->company_name = htmlspecialchars(strip_tags($this->company_name));
        $this->tax_number = htmlspecialchars(strip_tags($this->tax_number));
        $this->address = htmlspecialchars(strip_tags($this->address));
        $this->city = htmlspecialchars(strip_tags($this->city));
        $this->website = htmlspecialchars(strip_tags($this->website));
        $this->description = htmlspecialchars(strip_tags($this->description));
        
        // Parametreleri bağla
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":company_name", $this->company_name);
        $stmt->bindParam(":tax_number", $this->tax_number);
        $stmt->bindParam(":address", $this->address);
        $stmt->bindParam(":city", $this->city);
        $stmt->bindParam(":website", $this->website);
        $stmt->bindParam(":description", $this->description);
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Organizatör onaylama
    public function approve($organizer_id, $admin_id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET approval_status='approved', approved_by=:admin_id, approved_at=NOW() 
                  WHERE id=:organizer_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':organizer_id', $organizer_id);
        $stmt->bindParam(':admin_id', $admin_id);
        
        if($stmt->execute()) {
            // Kullanıcı durumunu aktif yap
            $this->updateUserStatus($organizer_id, 'active');
            return true;
        }
        
        return false;
    }
    
    // Organizatör reddetme
    public function reject($organizer_id, $admin_id, $reason) {
        $query = "UPDATE " . $this->table_name . " 
                  SET approval_status='rejected', approved_by=:admin_id, 
                      approved_at=NOW(), rejection_reason=:reason 
                  WHERE id=:organizer_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':organizer_id', $organizer_id);
        $stmt->bindParam(':admin_id', $admin_id);
        $stmt->bindParam(':reason', $reason);
        
        return $stmt->execute();
    }
    
    // Bekleyen organizatörleri getir
    public function getPendingOrganizers() {
        $query = "SELECT od.*, u.first_name, u.last_name, u.email, u.phone, u.created_at as user_created,
                             u.status as user_status, u.email_verified
                      FROM " . $this->table_name . " od
                      JOIN users u ON od.user_id = u.id
                      WHERE od.approval_status = 'pending'
                      ORDER BY od.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Kullanıcı durumunu güncelle
    private function updateUserStatus($organizer_id, $status) {
        $query = "UPDATE users u 
                  JOIN organizer_details od ON u.id = od.user_id 
                  SET u.status = :status 
                  WHERE od.id = :organizer_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':organizer_id', $organizer_id);
        
        return $stmt->execute();
    }
    
    /**
     * Organizatör onaylama (user_id ile)
     */
    public function approveOrganizer($user_id) {
        // Önce organizatör detayını bul
        $query = "SELECT id FROM " . $this->table_name . " WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $organizer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$organizer) {
            return false;
        }
        
        // Organizatör detayını onayla
        $query = "UPDATE " . $this->table_name . " 
                  SET approval_status='approved', updated_at=NOW() 
                  WHERE user_id=:user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        
        if($stmt->execute()) {
            // Kullanıcı durumunu approved yap ve email_verified'ı true yap
            $this->updateUserStatusByUserId($user_id, 'approved');
            $this->updateEmailVerified($user_id, true);
            return true;
        }
        
        return false;
    }
    
    /**
     * Email verified durumunu güncelle
     */
    private function updateEmailVerified($user_id, $verified) {
        $query = "UPDATE users SET email_verified = :verified WHERE id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':verified', $verified, PDO::PARAM_BOOL);
        $stmt->bindParam(':user_id', $user_id);
        return $stmt->execute();
    }
    
    /**
     * Organizatör reddetme (user_id ile)
     */
    public function rejectOrganizer($user_id, $reason = '') {
        $query = "UPDATE " . $this->table_name . " 
                  SET approval_status='rejected', updated_at=NOW(), rejection_reason=:reason 
                  WHERE user_id=:user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':reason', $reason);
        
        if($stmt->execute()) {
            // Kullanıcı durumunu rejected yap
            $this->updateUserStatusByUserId($user_id, 'rejected');
            return true;
        }
        
        return false;
    }
    
    /**
     * Onaylanan organizatörleri getir
     */
    public function getApprovedOrganizers() {
        $query = "SELECT od.*, u.first_name, u.last_name, u.email, u.phone, od.updated_at
                  FROM " . $this->table_name . " od
                  JOIN users u ON od.user_id = u.id
                  WHERE od.approval_status = 'approved'
                  ORDER BY od.updated_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Reddedilen organizatörleri getir
     */
    public function getRejectedOrganizers() {
        $query = "SELECT od.*, u.first_name, u.last_name, u.email, u.phone, od.updated_at, od.rejection_reason
                  FROM " . $this->table_name . " od
                  JOIN users u ON od.user_id = u.id
                  WHERE od.approval_status = 'rejected'
                  ORDER BY od.updated_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * User ID ile kullanıcı durumunu güncelle
     */
    private function updateUserStatusByUserId($user_id, $status) {
        $query = "UPDATE users SET status = :status WHERE id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':user_id', $user_id);
        return $stmt->execute();
    }
    
    /**
     * Organizatör detaylarını user_id ile getir
     */
    public function getOrganizerByUserId($user_id) {
        $query = "SELECT od.*, u.first_name, u.last_name, u.email, u.phone
                  FROM " . $this->table_name . " od
                  JOIN users u ON od.user_id = u.id
                  WHERE od.user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>