<?php
require_once __DIR__ . '/../includes/password_utils.php';
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
    public $google_id;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Kullanıcı kaydı
    public function register() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET email=:email, password=:password, first_name=:first_name, 
                      last_name=:last_name, phone=:phone, user_type=:user_type, status=:status, email_verified=:email_verified";
        
        $stmt = $this->conn->prepare($query);
        
        // Verileri temizle
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->first_name = htmlspecialchars(strip_tags($this->first_name));
        $this->last_name = htmlspecialchars(strip_tags($this->last_name));
        $this->phone = htmlspecialchars(strip_tags($this->phone));
        
        // Şifreyi hashle
        $password_hash = bj_hash_password($this->password);
        
        // Service ve Ad Agency kullanıcıları için email_verified = 1, diğerleri için 0
        $email_verified = in_array($this->user_type, ['service', 'ad_agency']) ? 1 : 0;
        
        // Parametreleri bağla
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $password_hash);
        $stmt->bindParam(":first_name", $this->first_name);
        $stmt->bindParam(":last_name", $this->last_name);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":user_type", $this->user_type);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":email_verified", $email_verified);
        
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }
    
    // Giriş kontrolü
    // Giriş kontrolü
    public function login($email, $password) {
        $query = "SELECT id, email, password, first_name, last_name, user_type, status, email_verified 
                  FROM " . $this->table_name . " 
                  WHERE email = :email LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if(password_verify($password, $row['password'])) {

                // Rehash gerekiyorsa Argon2(id/i)'ye yükselt
                if (bj_password_needs_rehash($row['password'])) {
                    $newHash = bj_hash_password($password);
                    $upd = $this->conn->prepare("UPDATE " . $this->table_name . " SET password = :password WHERE id = :id");
                    $upd->bindParam(':password', $newHash);
                    $upd->bindParam(':id', $row['id']);
                    $upd->execute();
                }
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

    /**
     * Google ID ile kullanıcı ara
     */
    public function findByGoogleId($google_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE google_id = :google_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':google_id', $google_id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->email = $row['email'];
            $this->first_name = $row['first_name'];
            $this->last_name = $row['last_name'];
            $this->phone = $row['phone'];
            $this->user_type = $row['user_type'];
            $this->status = $row['status'];
            $this->google_id = $row['google_id'];
            return true;
        }
        return false;
    }

    /**
     * Google ile kullanıcı kaydı
     */
    public function registerWithGoogle() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (email, first_name, last_name, phone, user_type, status, google_id, email_verified, password) 
                  VALUES (:email, :first_name, :last_name, :phone, :user_type, :status, :google_id, :email_verified, :password)";
        
        $stmt = $this->conn->prepare($query);
        
        // Şifre alanı Google kullanıcıları için boş olabilir
        $dummy_password = bj_hash_password(uniqid());
        
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':first_name', $this->first_name);
        $stmt->bindParam(':last_name', $this->last_name);
        $stmt->bindParam(':phone', $this->phone);
        $stmt->bindParam(':user_type', $this->user_type);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':google_id', $this->google_id);
        $stmt->bindValue(':email_verified', true, PDO::PARAM_BOOL);
        $stmt->bindParam(':password', $dummy_password);
        
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    /**
     * Mevcut kullanıcıya Google hesabını bağla
     */
    public function linkGoogleAccount($user_id, $google_id) {
        $query = "UPDATE " . $this->table_name . " SET google_id = :google_id WHERE id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':google_id', $google_id);
        $stmt->bindParam(':user_id', $user_id);
        
        return $stmt->execute();
    }

    /**
     * Telefon numarası ile kullanıcı bul
     */
    public function findByPhone($phone) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE phone = :phone LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':phone', $phone);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    /**
     * WhatsApp ile kullanıcı kaydı
     */
    public function registerWithWhatsApp($userData) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (first_name, last_name, email, phone, user_type, status, whatsapp_verified, password, created_at) 
                  VALUES (:first_name, :last_name, :email, :phone, :user_type, :status, :whatsapp_verified, :password, NOW())";
        
        $stmt = $this->conn->prepare($query);
        
        // WhatsApp kullanıcıları için dummy şifre
        $dummy_password = bj_hash_password(uniqid());
        $status = 'active';
        
        $stmt->bindParam(':first_name', $userData['first_name']);
        $stmt->bindParam(':last_name', $userData['last_name']);
        $stmt->bindParam(':email', $userData['email']);
        $stmt->bindParam(':phone', $userData['phone']);
        $stmt->bindParam(':user_type', $userData['user_type']);
        $stmt->bindParam(':status', $status);
        $stmt->bindValue(':whatsapp_verified', true, PDO::PARAM_BOOL);
        $stmt->bindParam(':password', $dummy_password);
        
        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }
}
?>