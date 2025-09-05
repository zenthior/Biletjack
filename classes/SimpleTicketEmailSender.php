<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/email_verification.php';

/**
 * Basit bilet e-posta gönderim sınıfı - QR kodu olmadan, sadece bağlantı ile
 */
class SimpleTicketEmailSender {
    private $db;
    private $emailVerification;
    
    public function __construct($database) {
        $this->db = $database;
        $this->emailVerification = new EmailVerification($database);
    }
    
    /**
     * Bilet satın alma sonrası müşteriye basit e-posta gönder (QR kodu değil, biletlerim linki)
     */
    public function sendSimpleTicketEmail($orderData) {
        try {
            $customerInfo = $orderData['customerInfo'];
            $tickets = $orderData['tickets'];
            $orderNumber = $orderData['orderNumber'];
            
            $subject = 'BiletJack - Bilet Siparişiniz Tamamlandı! 🎫';
            $htmlBody = $this->getSimpleTicketEmailTemplate($customerInfo, $tickets, $orderNumber);
            $textBody = $this->getSimpleTicketEmailTextVersion($customerInfo, $tickets, $orderNumber);
            
            return $this->emailVerification->sendSMTPEmail(
                $customerInfo['email'], 
                $subject, 
                $htmlBody, 
                $textBody
            );
            
        } catch (Exception $e) {
            error_log("Basit bilet e-posta gönderme hatası: " . $e->getMessage());
            return ['success' => false, 'message' => 'E-posta gönderilemedi: ' . $e->getMessage()];
        }
    }
    
    /**
     * Basit bilet e-posta HTML şablonu
     */
    private function getSimpleTicketEmailTemplate($customerInfo, $tickets, $orderNumber) {
        $firstName = htmlspecialchars($customerInfo['firstName']);
        $lastName = htmlspecialchars($customerInfo['lastName']);
        $ticketsHtml = '';
        $totalAmount = 0;
        
        foreach ($tickets as $ticket) {
            $eventInfo = $this->getEventInfo($ticket['event_id']);
            $totalAmount += $ticket['price'];
            
            $ticketsHtml .= "
            <div style='border: 2px solid #667eea; border-radius: 15px; margin: 20px 0; padding: 25px; background: linear-gradient(135deg, #f8f9ff 0%, #ffffff 100%);'>
                <div style='flex: 1;'>
                    <h3 style='color: #667eea; margin: 0 0 15px 0; font-size: 18px;'>" . htmlspecialchars($eventInfo['title']) . "</h3>
                    <p style='margin: 5px 0; color: #666;'><strong>📅 Tarih:</strong> " . date('d.m.Y H:i', strtotime($eventInfo['date'])) . "</p>
                    <p style='margin: 5px 0; color: #666;'><strong>📍 Mekan:</strong> " . htmlspecialchars($eventInfo['location']) . "</p>
                    <p style='margin: 5px 0; color: #666;'><strong>🎫 Bilet Kodu:</strong> <span style='font-family: monospace; background: #f0f0f0; padding: 2px 8px; border-radius: 4px;'>" . htmlspecialchars($ticket['ticket_code']) . "</span></p>
                    <p style='margin: 5px 0; color: #666;'><strong>💰 Fiyat:</strong> " . number_format($ticket['price'], 2) . " TL</p>
                </div>
            </div>";
        }
        
        $myTicketsUrl = $this->getMyTicketsUrl();
        
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
                    <h1>🎫 Bilet Siparişiniz Tamamlandı!</h1>
                    <p style='margin: 10px 0 0 0; opacity: 0.9;'>Sipariş No: {$orderNumber}</p>
                </div>
                <div class='content'>
                    <h2>Merhaba {$firstName} {$lastName},</h2>
                    <p>BiletJack üzerinden yaptığınız satın alma işlemi başarıyla tamamlandı! Biletleriniz müşteri panelinde görüntülenmeye hazır.</p>
                    
                    {$ticketsHtml}
                    
                    <div class='summary-box'>
                        <h3 style='color: #667eea; margin-top: 0;'>📋 Sipariş Özeti</h3>
                        <p><strong>Sipariş Numarası:</strong> {$orderNumber}</p>
                        <p><strong>Bilet Sayısı:</strong> " . count($tickets) . " adet</p>
                        <p><strong>Toplam Tutar:</strong> " . number_format($totalAmount, 2) . " TL</p>
                        <p><strong>Satın Alma Tarihi:</strong> " . date('d.m.Y H:i') . "</p>
                    </div>
                    
                    <div style='background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 8px; padding: 15px; margin: 20px 0;'>
                        <h4 style='color: #0c5460; margin-top: 0;'>📱 Biletlerinize Ulaşın:</h4>
                        <ul style='color: #0c5460; margin: 0; padding-left: 20px;'>
                            <li>Biletlerinizi ve QR kodlarını müşteri panelinden görüntüleyebilirsiniz</li>
                            <li>QR kodlarını etkinlik giriş kapısında okutun</li>
                            <li>Bilet kodlarınızı telefonunuzda kaydedin</li>
                            <li>Etkinlik öncesi hatırlatma mesajı alacaksınız</li>
                        </ul>
                    </div>
                    
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='{$myTicketsUrl}' class='button'>
                            🎫 Biletlerimi Görüntüle
                        </a>
                    </div>
                    
                    <p>Herhangi bir sorunuz olursa bizimle iletişime geçmekten çekinmeyin.</p>
                    <p>İyi eğlenceler dileriz!<br><strong>BiletJack Ekibi</strong></p>
                </div>
                <div class='footer'>
                    <p>Bu e-posta otomatik olarak gönderilmiştir.</p>
                    <p>© 2025 BiletJack. Tüm hakları saklıdır.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Text versiyonu (HTML desteklemeyen e-posta istemcileri için)
     */
    private function getSimpleTicketEmailTextVersion($customerInfo, $tickets, $orderNumber) {
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
        
        $myTicketsUrl = $this->getMyTicketsUrl();
        
        return "
BiletJack - Bilet Siparişiniz Tamamlandı! 🎫
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

BİLETLERİNİZE ULAŞIN:
- Biletlerinizi ve QR kodlarını müşteri panelinden görüntüleyebilirsiniz
- QR kodlarını etkinlik giriş kapısında okutun  
- Bilet kodlarınızı telefonunuzda kaydedin
- Etkinlik öncesi hatırlatma mesajı alacaksınız

Müşteri paneli: {$myTicketsUrl}

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
            $stmt = $this->db->prepare("SELECT title, event_date, venue_name FROM events WHERE id = ?");
            $stmt->execute([$eventId]);
            $event = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($event) {
                return [
                    'title' => $event['title'],
                    'date' => $event['event_date'],
                    'location' => $event['venue_name']
                ];
            }
            
            return [
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
    
    private function getMyTicketsUrl() {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1:8000';
        return $scheme . '://' . $host . '/customer/tickets.php';
    }
}