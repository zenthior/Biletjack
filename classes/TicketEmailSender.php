<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/email_verification.php';
// Composer autoload koşullu
$composerAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;

/**
 * Bilet satın alma sonrası e-posta gönderim sınıfı
 */
class TicketEmailSender {
    private $db;
    private $emailVerification;
    
    public function __construct($database) {
        $this->db = $database;
        $this->emailVerification = new EmailVerification($database);
    }
    
    /**
     * Bilet satın alma sonrası müşteriye e-posta gönder
     */
    public function sendTicketEmail($orderData) {
        try {
            $customerInfo = $orderData['customerInfo'];
            $tickets = $orderData['tickets'];
            $orderNumber = $orderData['orderNumber'];
            
            $subject = 'BiletJack - Biletleriniz Hazır! 🎫';
            $htmlBody = $this->getTicketEmailTemplate($customerInfo, $tickets, $orderNumber);
            $textBody = $this->getTicketEmailTextVersion($customerInfo, $tickets, $orderNumber);
            
            return $this->emailVerification->sendSMTPEmail(
                $customerInfo['email'], 
                $subject, 
                $htmlBody, 
                $textBody
            );
            
        } catch (Exception $e) {
            error_log("Bilet e-posta gönderme hatası: " . $e->getMessage());
            return ['success' => false, 'message' => 'E-posta gönderilemedi: ' . $e->getMessage()];
        }
    }
    
    /**
     * Bilet e-posta HTML şablonu
     */
    private function getTicketEmailTemplate($customerInfo, $tickets, $orderNumber) {
        $firstName = htmlspecialchars($customerInfo['firstName']);
        $lastName = htmlspecialchars($customerInfo['lastName']);
        $ticketsHtml = '';
        $totalAmount = 0;
        
        foreach ($tickets as $ticket) {
            $totalAmount += $ticket['price'];
            
            $ticketsHtml .= "
            <div style='border: 2px solid #667eea; border-radius: 15px; margin: 20px 0; padding: 25px; background: linear-gradient(135deg, #f8f9ff 0%, #ffffff 100%);'>
                <div style='margin-bottom: 20px;'>
                    <h3 style='color: #667eea; margin: 0 0 10px 0; font-size: 18px;'>" . htmlspecialchars($ticket['event_title']) . "</h3>
                    <p style='margin: 5px 0; color: #666;'><strong>📅 Tarih:</strong> " . date('d.m.Y H:i', strtotime($ticket['event_date'])) . "</p>
                    <p style='margin: 5px 0; color: #666;'><strong>📍 Mekan:</strong> " . htmlspecialchars($ticket['venue_name']) . "</p>
                    <p style='margin: 5px 0; color: #666;'><strong>🎫 Bilet Kodu:</strong> <span style='font-family: monospace; background: #f0f0f0; padding: 2px 8px; border-radius: 4px;'>" . htmlspecialchars($ticket['ticket_code']) . "</span></p>
                    <p style='margin: 5px 0; color: #666;'><strong>🎟️ Bilet Türü:</strong> " . htmlspecialchars($ticket['ticket_type']) . "</p>
                    <p style='margin: 5px 0; color: #666;'><strong>💰 Fiyat:</strong> " . number_format($ticket['price'], 2) . " TL</p>
                    <p style='margin: 5px 0; color: #666;'><strong>📊 Adet:</strong> " . $ticket['quantity'] . "</p>
                </div>
                <div style='background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 12px; margin-top: 15px;'>
                    <p style='margin: 0; font-size: 12px; color: #856404;'>
                        <strong>ℹ️ Giriş İçin:</strong> Bilet kodunuzu etkinlik günü giriş kapısında görevlilere gösterin.
                    </p>
                </div>
            </div>";
        }
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>BiletJack - Biletleriniz</title>
            <style>
                body { font-family: 'Segoe UI', Arial, sans-serif; margin: 0; padding: 0; background-color: #f4f4f4; }
                .container { max-width: 650px; margin: 0 auto; background-color: white; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
                .content { padding: 30px; }
                .summary-box { background: #f8f9fa; border-radius: 10px; padding: 20px; margin: 20px 0; }
                .footer { background-color: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 12px; }
                .button { display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 25px; text-decoration: none; border-radius: 25px; margin: 15px 0; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🎫 Biletleriniz Hazır!</h1>
                    <p style='margin: 10px 0 0 0; opacity: 0.9;'>Sipariş No: {$orderNumber}</p>
                </div>
                <div class='content'>
                    <h2>Merhaba {$firstName} {$lastName},</h2>
                    <p>BiletJack üzerinden yaptığınız satın alma işlemi başarıyla tamamlandı! Biletleriniz aşağıda yer almaktadır.</p>
                    
                    {$ticketsHtml}
                    
                    <div class='summary-box'>
                        <h3 style='color: #667eea; margin-top: 0;'>📋 Sipariş Özeti</h3>
                        <p><strong>Sipariş Numarası:</strong> {$orderNumber}</p>
                        <p><strong>Bilet Sayısı:</strong> " . count($tickets) . " adet</p>
                        <p><strong>Toplam Tutar:</strong> " . number_format($totalAmount, 2) . " TL</p>
                        <p><strong>Satın Alma Tarihi:</strong> " . date('d.m.Y H:i') . "</p>
                    </div>
                    
                    <div style='background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 8px; padding: 15px; margin: 20px 0;'>
                        <h4 style='color: #0c5460; margin-top: 0;'>📱 Önemli Bilgiler:</h4>
                        <ul style='color: #0c5460; margin: 0; padding-left: 20px;'>
                            <li>Biletlerinizi müşteri panelinden de görüntüleyebilirsiniz</li>
                            <li>QR kodlarını etkinlik giriş kapısında okutun</li>
                            <li>Bilet kodlarınızı telefonunuzda kaydedin</li>
                            <li>Etkinlik öncesi hatırlatma mesajı alacaksınız</li>
                        </ul>
                    </div>
                    
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='http://127.0.0.1:8000/customer/tickets.php' class='button'>
                            🎫 Biletlerimi Görüntüle
                        </a>
                    </div>
                    
                    <p>Herhangi bir sorunuz olursa bizimle iletişime geçmekten çekinmeyin.</p>
                    <p>İyi eğlenceler dileriz!<br><strong>BiletJack Ekibi</strong></p>
                </div>
                <div class='footer'>
                    <p>Bu e-posta otomatik olarak gönderilmiştir.</p>
                    <p>© 2025 BiletJack. Tüm hakları saklıdır.</p>
                    <p>QR kodlar Endroid QR Code v6.0 ile güvenli olarak oluşturulmuştur.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Text versiyonu (HTML desteklemeyen e-posta istemcileri için)
     */
    private function getTicketEmailTextVersion($customerInfo, $tickets, $orderNumber) {
        $firstName = $customerInfo['firstName'];
        $lastName = $customerInfo['lastName'];
        $ticketsText = '';
        $totalAmount = 0;
        
        foreach ($tickets as $ticket) {
            $eventInfo = $this->getEventInfo($ticket['event_id']);
            $totalAmount += $ticket['price'];
            
            $ticketsText .= "\n" . str_repeat("=", 50) . "\n";
            $ticketsText .= "🎫 " . $eventInfo['title'] . "\n";
            $ticketsText .= "📅 Tarih: " . date('d.m.Y H:i', strtotime($eventInfo['date'])) . "\n";
            $ticketsText .= "📍 Mekan: " . $eventInfo['location'] . "\n";
            $ticketsText .= "🎫 Bilet Kodu: " . $ticket['ticket_code'] . "\n";
            $ticketsText .= "💰 Fiyat: " . number_format($ticket['price'], 2) . " TL\n";
            $ticketsText .= str_repeat("=", 50) . "\n";
        }
        
        return "
BiletJack - Biletleriniz Hazır! 🎫
Sipariş No: {$orderNumber}

Merhaba {$firstName} {$lastName},

BiletJack üzerinden yaptığınız satın alma işlemi başarıyla tamamlandı! 

BİLETLERİNİZ:
{$ticketsText}

SİPARİŞ ÖZETİ:
================
Sipariş Numarası: {$orderNumber}
Bilet Sayısı: " . count($tickets) . " adet
Toplam Tutar: " . number_format($totalAmount, 2) . " TL
Satın Alma Tarihi: " . date('d.m.Y H:i') . "

ÖNEMLİ BİLGİLER:
- Biletlerinizi müşteri panelinden de görüntüleyebilirsiniz
- QR kodlarını etkinlik giriş kapısında okutun  
- Bilet kodlarınızı telefonunuzda kaydedin
- Etkinlik öncesi hatırlatma mesajı alacaksınız

Müşteri paneli: https://biletjack.com/customer/tickets.php

Herhangi bir sorunuz olursa bizimle iletişime geçmekten çekinmeyin.

İyi eğlenceler dileriz!
BiletJack Ekibi

Bu e-posta otomatik olarak gönderilmiştir.
© 2025 BiletJack. Tüm hakları saklıdır.
        ";
    }
    
    /**
     * Etkinlik bilgilerini getir
     */
    private function getEventInfo($eventId) {
        try {
            $stmt = $this->db->prepare("SELECT title, date, location FROM events WHERE id = ?");
            $stmt->execute([$eventId]);
            $event = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $event ?: [
                'title' => 'Bilinmeyen Etkinlik',
                'date' => date('Y-m-d H:i:s'),
                'location' => 'Belirtilmemiş'
            ];
            
        } catch (Exception $e) {
            error_log("Etkinlik bilgisi getirme hatası: " . $e->getMessage());
            return [
                'title' => 'Bilinmeyen Etkinlik',
                'date' => date('Y-m-d H:i:s'),
                'location' => 'Belirtilmemiş'
            ];
        }
    }

    /**
     * QR'ı base64 PNG olarak inline üret (e-posta istemcileri için güvenli)
     */
    private function makeInlinePngQr($qrDataJson = null, $qrPath = null) {
        try {
            $data = $qrDataJson;
            if (!$data && $qrPath) {
                // Dosya yolu verilmişse içeriği deneyelim (SVG string olabilir)
                $absPath = __DIR__ . '/../' . ltrim($qrPath, '/');
                if (is_file($absPath)) {
                    // SVG dosyasını doğrudan data URI olarak embed etmeyi deneyelim (her istemci desteklemeyebilir)
                    $ext = strtolower(pathinfo($absPath, PATHINFO_EXTENSION));
                    if ($ext === 'svg') {
                        $svgContent = @file_get_contents($absPath);
                        if ($svgContent !== false) {
                            return 'data:image/svg+xml;base64,' . base64_encode($svgContent);
                        }
                    }
                }
            }
            if (!$data) {
                // Yine de bir fallback JSON üretmeyelim; boşsa kullanıcı paneli linki gösterilecek
                throw new Exception('QR verisi bulunamadı');
            }

            // Endroid kütüphanesi mevcut mu?
            if (!class_exists('Endroid\\QrCode\\Builder\\Builder')) {
                throw new Exception('QR kütüphanesi yok');
            }

            // Endroid ile PNG üret
            $builder = new Builder(
                writer: new PngWriter(),
                writerOptions: [],
                validateResult: false,
                data: $data,
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::Medium,
                size: 300,
                margin: 10,
                roundBlockSizeMode: RoundBlockSizeMode::Margin
            );
            $result = $builder->build();
            $png = $result->getString();
            $base64 = base64_encode($png);
            return 'data:image/png;base64,' . $base64;
        } catch (Exception $e) {
            // Fallback: küçük placeholder
            $placeholder = base64_encode(self::smallPlaceholderPng());
            return 'data:image/png;base64,' . $placeholder;
        }
    }

    /**
     * Basit 1x1 beyaz PNG placeholder (base64 için ham veri döner)
     */
    private static function smallPlaceholderPng() {
        // 1x1 beyaz PNG binary
        return hex2bin(
            '89504E470D0A1A0A0000000D4948445200000001000000010802000000907724' .
            '0000000A49444154789C6360000002000154A24F5D0000000049454E44AE426082'
        );
    }
}