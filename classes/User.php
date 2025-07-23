<?php
// Bu satırı kaldırın çünkü database bağlantısı constructor'da geçiliyor
// require_once '../config/database.php';

class User {
    private $conn;
    private $table_name = "users";
    
    public $id;
    public $email;
    public $password;
    public $first_name;
    public $last_name;
    public $phone;
    public $user_type;
    public $status;
    public $created_at;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Kullanıcı kaydı
    public function register() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET email=:email, password=:password, first_name=:first_name, 
                      last_name=:last_name, phone=:phone, user_type=:user_type, status=:status";
        
        $stmt = $this->conn->prepare($query);
        
        // Verileri temizle
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->first_name = htmlspecialchars(strip_tags($this->first_name));
        $this->last_name = htmlspecialchars(strip_tags($this->last_name));
        $this->phone = htmlspecialchars(strip_tags($this->phone));
        
        // Şifreyi hashle
        $password_hash = password_hash($this->password, PASSWORD_DEFAULT);
        
        // Parametreleri bağla
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $password_hash);
        $stmt->bindParam(":first_name", $this->first_name);
        $stmt->bindParam(":last_name", $this->last_name);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":user_type", $this->user_type);
        $stmt->bindParam(":status", $this->status);
        
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }
    
    // Giriş kontrolü
    // Giriş kontrolü
    public function login($email, $password) {
        $query = "SELECT id, email, password, first_name, last_name, user_type, status 
                  FROM " . $this->table_name . " 
                  WHERE email = :email LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if(password_verify($password, $row['password'])) {
                $this->id = $row['id'];
                $this->email = $row['email'];
                $this->first_name = $row['first_name'];
                $this->last_name = $row['last_name'];
                $this->user_type = $row['user_type'];
                $this->status = $row['status'];
                
                // Son giriş zamanını güncelle - user_id parametresi ile
                $this->updateLastLogin($row['id']);
                
                return [
                    'success' => true,
                    'user' => $row
                ];
            }
        }
        
        return [
            'success' => false,
            'message' => 'E-posta veya şifre hatalı'
        ];
    }
    
    // E-posta kontrolü
    public function emailExists() {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $this->email);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
    
    // Son giriş zamanını güncelle
    public function updateLastLogin($user_id = null) {
        $id = $user_id ?? $this->id;
        $query = "UPDATE " . $this->table_name . " SET last_login = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
    }
    
    // Organizatör onay durumunu kontrol et
    public function checkOrganizerApproval() {
        if($this->user_type !== 'organizer') {
            return true; // Organizatör değilse onay gerekmiyor
        }
        
        $query = "SELECT approval_status FROM organizer_details WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $this->id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['approval_status'] === 'approved';
        }
        
        return false;
    }
    
    // Kullanıcı bilgilerini getir
    public function getUserById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        return false;
    }

    /**
     * Toplam kullanıcı sayısını getir
     */
    public function getTotalUsers() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    /**
     * Kullanıcı tipine göre sayı getir
     */
    public function getUserCountByType($type) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE user_type = :type";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':type', $type);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }
}
?>