<?php
/**
 * PayTR Ödeme Ağ Geçidi Konfigürasyonu
 * 
 * Bu dosya PayTR API entegrasyonu için gerekli ayarları içerir.
 * Güvenlik nedeniyle bu dosyayı public erişimden korunmalıdır.
 */

// PayTR API Bilgileri
define('PAYTR_MERCHANT_ID', '425796');
define('PAYTR_MERCHANT_KEY', 'GJf9L4uURhaQPhmB');
define('PAYTR_MERCHANT_SALT', 'T7yd7RQzAmyoYq6T');

// PayTR API URL'leri
define('PAYTR_API_URL', 'https://www.paytr.com/odeme');
define('PAYTR_IFRAME_API_URL', 'https://www.paytr.com/odeme/api/get-token');

// Test Modu (1: Test, 0: Canlı)
define('PAYTR_TEST_MODE', 1);

// Para Birimi
define('PAYTR_CURRENCY', 'TL');

// Varsayılan Taksit Sayısı (0: Peşin)
define('PAYTR_DEFAULT_INSTALLMENT', 0);

// Maksimum Taksit Sayısı
define('PAYTR_MAX_INSTALLMENT', 12);

// Timeout Süresi (dakika)
define('PAYTR_TIMEOUT_LIMIT', 30);

// Debug Modu (1: Açık, 0: Kapalı)
define('PAYTR_DEBUG_MODE', 1);

// Callback URL'leri
define('PAYTR_SUCCESS_URL', 'http://127.0.0.1:8081/process_payment.php');
define('PAYTR_FAIL_URL', 'http://127.0.0.1:8081/process_payment.php');
define('PAYTR_CALLBACK_URL', 'http://127.0.0.1:8081/paytr_callback.php');

/**
 * PayTR Token Oluşturma Fonksiyonu
 * 
 * @param array $data Token oluşturmak için gerekli veriler
 * @return string Oluşturulan token
 */
function createPayTRToken($data) {
    $hashSTR = $data['merchant_id'] . 
               $data['user_ip'] . 
               $data['merchant_oid'] . 
               $data['email'] . 
               $data['payment_amount'] . 
               $data['payment_type'] . 
               $data['installment_count'] . 
               $data['currency'] . 
               $data['test_mode'] . 
               $data['non_3d'];
    
    return base64_encode(hash_hmac('sha256', $hashSTR . PAYTR_MERCHANT_SALT, PAYTR_MERCHANT_KEY, true));
}

/**
 * PayTR iFrame Token Oluşturma Fonksiyonu
 * 
 * @param array $data iFrame token oluşturmak için gerekli veriler
 * @return string Oluşturulan token
 */
function createPayTRIframeToken($data) {
    // PayTR dökümantasyon (1. ADIM) sıralaması
    $hashSTR = $data['merchant_id'] .
               $data['user_ip'] .
               $data['merchant_oid'] .
               $data['email'] .
               $data['payment_amount'] .
               $data['user_basket'] .
               $data['no_installment'] .
               $data['max_installment'] .
               $data['currency'] .
               $data['test_mode'];
    
    return base64_encode(hash_hmac('sha256', $hashSTR . PAYTR_MERCHANT_SALT, PAYTR_MERCHANT_KEY, true));
}

/**
 * Kullanıcının IP Adresini Al
 * 
 * @return string IP adresi
 */
function getUserIP() {
    $candidates = [];
    // Cloudflare / Proxy başlıkları
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $candidates[] = $_SERVER['HTTP_CF_CONNECTING_IP'];
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // İlk IP'yi al (genellikle istemci IP'si)
        $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $candidates[] = trim($parts[0]);
    }
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $candidates[] = $_SERVER['HTTP_CLIENT_IP'];
    }
    if (!empty($_SERVER['REMOTE_ADDR'])) {
        $candidates[] = $_SERVER['REMOTE_ADDR'];
    }

    foreach ($candidates as $ip) {
        $ip = trim($ip);
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
    }
    return '0.0.0.0';
}

/**
 * Sepet Verilerini PayTR Formatına Çevir
 * 
 * @param array $cartItems Sepet öğeleri
 * @return string Base64 encoded sepet verisi
 */
function formatCartForPayTR($cartItems, $discountAmount = 0) {
    $basket = [];
    
    foreach ($cartItems as $item) {
        $basket[] = [
            $item['ticket_name'] ?? 'Bilet',
            number_format($item['price'], 2, '.', ''),
            intval($item['quantity'])
        ];
    }
    
    // İndirim varsa negatif satır olarak ekle
    if ($discountAmount > 0) {
        $basket[] = [
            'İndirim',
            number_format(-1 * $discountAmount, 2, '.', ''),
            1
        ];
    }
    
    return base64_encode(json_encode($basket));
}

/**
 * PayTR Callback Hash Doğrulama
 * 
 * @param array $postData Callback'ten gelen POST verisi
 * @return bool Hash doğru mu?
 */
function verifyPayTRCallback($postData) {
    $hashSTR = $postData['merchant_oid'] . 
               PAYTR_MERCHANT_SALT . 
               $postData['status'] . 
               $postData['total_amount'];
    
    $hash = base64_encode(hash_hmac('sha256', $hashSTR, PAYTR_MERCHANT_KEY, true));
    
    return isset($postData['hash']) && $hash === $postData['hash'];
}

?>