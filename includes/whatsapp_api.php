<?php
require_once 'config/database.php';

class WhatsAppAPI {
    private $accessToken;
    private $phoneNumberId;
    private $verifyToken;
    
    public function __construct() {
        $this->loadSettings();
    }
    
    private function loadSettings() {
        try {
            $database = new Database();
            $pdo = $database->getConnection();
            
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('whatsapp_api_token', 'whatsapp_phone_number_id', 'whatsapp_verify_token')");
            $settings = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
            
            $this->accessToken = $settings['whatsapp_api_token'] ?? '';
            $this->phoneNumberId = $settings['whatsapp_phone_number_id'] ?? '';
            $this->verifyToken = $settings['whatsapp_verify_token'] ?? '';
        } catch (Exception $e) {
            error_log('WhatsApp API settings load error: ' . $e->getMessage());
        }
    }
    
    public function sendVerificationCode($toPhoneNumber, $code) {
        // API ayarları kontrol et
        if (empty($this->accessToken) || empty($this->phoneNumberId)) {
            // Geliştirme modu - konsola yazdır
            error_log("WhatsApp API ayarları eksik. Geliştirme modu aktif.");
            error_log("Doğrulama kodu: {$code} - Telefon: {$toPhoneNumber}");
            return ['success' => true, 'message' => 'Kod gönderildi (geliştirme modu)'];
        }
        
        // Telefon numarasını temizle (+ işaretini kaldır)
        $cleanPhone = str_replace('+', '', $toPhoneNumber);
        
        // WhatsApp Business API endpoint
        $url = "https://graph.facebook.com/v18.0/{$this->phoneNumberId}/messages";
        
        // Mesaj içeriği
        $message = [
            'messaging_product' => 'whatsapp',
            'to' => $cleanPhone,
            'type' => 'template',
            'template' => [
                'name' => 'verification_code', // Template adı (Facebook'ta oluşturulmalı)
                'language' => [
                    'code' => 'tr'
                ],
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => [
                            [
                                'type' => 'text',
                                'text' => $code
                            ]
                        ]
                    ]
                ]
            ]
        ];
        
        // Eğer template yoksa basit metin mesajı gönder
        $fallbackMessage = [
            'messaging_product' => 'whatsapp',
            'to' => $cleanPhone,
            'type' => 'text',
            'text' => [
                'body' => "BiletJack doğrulama kodunuz: {$code}\n\nBu kodu kimseyle paylaşmayın."
            ]
        ];
        
        // İlk olarak template ile dene
        $result = $this->sendMessage($url, $message);
        
        // Template başarısız olursa basit metin gönder
        if (!$result['success']) {
            error_log('Template mesaj başarısız, basit metin deneniyor: ' . $result['message']);
            $result = $this->sendMessage($url, $fallbackMessage);
        }
        
        return $result;
    }
    
    private function sendMessage($url, $messageData) {
        $headers = [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($messageData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            error_log('WhatsApp API cURL error: ' . $error);
            return ['success' => false, 'message' => 'Bağlantı hatası: ' . $error];
        }
        
        $responseData = json_decode($response, true);
        
        if ($httpCode === 200 && isset($responseData['messages'])) {
            return ['success' => true, 'message' => 'Mesaj başarıyla gönderildi', 'data' => $responseData];
        } else {
            $errorMessage = 'Mesaj gönderilemedi';
            if (isset($responseData['error']['message'])) {
                $errorMessage = $responseData['error']['message'];
            }
            error_log('WhatsApp API error: ' . $response);
            return ['success' => false, 'message' => $errorMessage];
        }
    }
    
    public function isConfigured() {
        return !empty($this->accessToken) && !empty($this->phoneNumberId);
    }
    
    public function getStatus() {
        if (!$this->isConfigured()) {
            return [
                'configured' => false,
                'message' => 'WhatsApp API ayarları eksik. Admin panelinden yapılandırın.'
            ];
        }
        
        return [
            'configured' => true,
            'message' => 'WhatsApp API yapılandırılmış ve kullanıma hazır.'
        ];
    }
}
?>