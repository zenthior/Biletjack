<?php

$autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

use Endroid\QrCode\QrCode;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Writer\SvgWriter;

class Ticket {
    private $conn;
    
    public function __construct($database) {
        $this->conn = $database;
    }
    
    public function createTicket($eventId, $orderId, $ticketType, $price, $quantity = 1, $userId = null, $ticketTypeId = null, $seatId = null, $isReservation = false, $initialStatus = 'active') {
        // Transaction-safe başlangıç: aktif bir transaction yoksa başlat
        $ownTransaction = false;
        if (!$this->conn->inTransaction()) {
            $this->conn->beginTransaction();
            $ownTransaction = true;
        }

        try {
            // Eğer userId verilmemişse, order'dan al
            if ($userId === null) {
                $orderStmt = $this->conn->prepare("SELECT user_id FROM orders WHERE id = ?");
                $orderStmt->execute([$orderId]);
                $order = $orderStmt->fetch();
                $userId = $order['user_id'];
            }

            // Tek bilet oluştur, quantity bilgisini kaydet
            $ticketCode = $this->generateUniqueTicketCode();
            $purchaseTime = date('Y-m-d H:i:s');

            // Eğer ticketTypeId gönderilmediyse, genel bilet için fallback
            if ($ticketTypeId === null) {
                $fallbackStmt = $this->conn->prepare("SELECT id FROM ticket_types WHERE event_id = ? LIMIT 1");
                $fallbackStmt->execute([$eventId]);
                $fallback = $fallbackStmt->fetch();
                if ($fallback) {
                    $ticketTypeId = (int)$fallback['id'];
                }
            }

            // Koltuk durumunu belirle
            // Ödeme beklemede ise her zaman 'reserved' yap, başarıdan sonra satılmış olarak işaretlenecek
            $seatStatus = ($initialStatus === 'pending') ? 'reserved' : ($isReservation ? 'reserved' : 'sold');
            $seatIdForTicket = null;
            $seatLabelsStr = null; // Koltuk etiketleri (örn: "A1, B3")
            if ($seatId !== null) {
                if (is_array($seatId) && count($seatId) > 0) {
                    $placeholders = implode(',', array_fill(0, count($seatId), '?'));
                    $sql = "UPDATE seats SET status = '$seatStatus'
                            WHERE id IN ($placeholders) AND event_id = ? AND status IN ('available','reserved')";
                    $stmtUpd = $this->conn->prepare($sql);
                    $params = array_merge($seatId, [$eventId]);
                    $stmtUpd->execute($params);
                    if ($stmtUpd->rowCount() !== count($seatId)) {
                        throw new Exception('Seçilen koltuklardan bazıları artık müsait değil');
                    }
                    // Çoklu koltuk için tek bilet/tek QR: tickets.seat_id null bırakıyoruz
                    $seatIdForTicket = null;

                    // Koltuk etiketlerini oluştur (A1, B3 ...)
                    $ph2 = implode(',', array_fill(0, count($seatId), '?'));
                    $q = $this->conn->prepare("SELECT `row_number`, `seat_number` FROM seats WHERE id IN ($ph2) ORDER BY `row_number`, `seat_number`");
                    $q->execute($seatId);
                    $rows = $q->fetchAll(PDO::FETCH_ASSOC);
                    if ($rows) {
                        $labels = array_map(function($r) {
                            $rowLabel = chr(64 + (int)$r['row_number']);
                            return $rowLabel . $r['seat_number'];
                        }, $rows);
                        $seatLabelsStr = implode(', ', $labels);
                    }
                } else {
                    $reserveStmt = $this->conn->prepare("UPDATE seats SET status = '$seatStatus' WHERE id = ? AND event_id = ? AND status IN ('available','reserved')");
                    $reserveStmt->execute([$seatId, $eventId]);
                    if ($reserveStmt->rowCount() === 0) {
                        throw new Exception('Seçilen koltuk artık müsait değil');
                    }
                    $seatIdForTicket = $seatId;

                    // Tek koltuk etiketi
                    $q = $this->conn->prepare("SELECT `row_number`, `seat_number` FROM seats WHERE id = ? LIMIT 1");
                    $q->execute([$seatId]);
                    if ($s = $q->fetch(PDO::FETCH_ASSOC)) {
                        $rowLabel = chr(64 + (int)$s['row_number']);
                        $seatLabelsStr = $rowLabel . $s['seat_number'];
                    }
                }
            }

            // Bilet veritabanına kaydet
            $stmt = $this->conn->prepare("
                INSERT INTO tickets (user_id, order_id, ticket_number, event_id, ticket_type_id, seat_id, price, quantity, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $userId,
                $orderId,
                $ticketCode,
                $eventId,
                $ticketTypeId,
                $seatIdForTicket,
                $price,
                $quantity,
                $initialStatus,
                $purchaseTime
            ]);
            $ticketId = $this->conn->lastInsertId();
            
            // QR kod verisi oluştur
            $qrData = $this->generateTicketQRData($ticketId, $eventId, $purchaseTime, $ticketCode, $quantity);
            
            // QR kod oluştur ve kaydet (hata olursa null döner)
            $qrCodePath = $this->generateAndSaveQRCode($qrData, $ticketId);
            
            // QR kod yolunu veritabanına kaydet (null olabilir)
            if ($qrCodePath) {
                $updateStmt = $this->conn->prepare("UPDATE tickets SET qr_code_path = ? WHERE id = ?");
                $updateStmt->execute([$qrCodePath, $ticketId]);
            }

            // Koltuk etiketleri kolonunu (varsa) bilet kaydına yaz
            if (!empty($seatLabelsStr)) {
                try {
                    $upd = $this->conn->prepare("UPDATE tickets SET seat_labels = ? WHERE id = ?");
                    $upd->execute([$seatLabelsStr, $ticketId]);
                } catch (Exception $e) {
                    // seat_labels kolonu yoksa sessizce geç
                }
            }
            
            $ticket = [
                'id' => $ticketId,
                'ticket_code' => $ticketCode,
                'event_id' => $eventId,
                'ticket_type' => $ticketType,
                'price' => $price,
                'quantity' => $quantity,
                'purchase_date' => $purchaseTime,
                'qr_code_path' => $qrCodePath,
                'qr_data' => $qrData
            ];
            
            // Sadece kendi başlattıysak commit et
            if ($ownTransaction) {
                $this->conn->commit();
            }
            return [$ticket];
        } catch (Exception $e) {
            // Sadece kendi başlattıysak rollback et
            if ($ownTransaction && $this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw new Exception('Bilet oluşturma hatası: ' . $e->getMessage());
        }
    }
    
    public function generateAndSaveQRCode($data, $ticketId) {
        // Endroid QR Code kütüphanesi kurulu değilse, QR üretmeden devam et
        if (!class_exists(\Endroid\QrCode\Builder\Builder::class)) {
            error_log('QR kütüphanesi bulunamadı, QR oluşturma atlandı.');
            return null;
        }
        try {
            // Hızlı ve basit QR kod oluştur - v4.8 API
            $qrCode = QrCode::create($data)
                ->setEncoding(new Encoding('UTF-8'))
                ->setErrorCorrectionLevel(new ErrorCorrectionLevelLow())
                ->setSize(200)
                ->setMargin(10);
            
            $writer = new SvgWriter();
            $result = $writer->write($qrCode);
            
            // QR kod dosyasını kaydet
            $uploadsDir = __DIR__ . '/../uploads/qr_codes/';
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
            }
            
            $filename = 'qr_ticket_' . $ticketId . '_' . time() . '.svg';
            $filePath = $uploadsDir . $filename;
            
            file_put_contents($filePath, $result->getString());
            
            return 'uploads/qr_codes/' . $filename;
            
        } catch (\Throwable $e) {
            // QR kod oluşturma başarısızsa da devam et
            error_log('QR kod oluşturma hatası: ' . $e->getMessage());
            return null; // QR olmadan da bilet oluşturulsun
        }
    }
    

    
    private function generateTicketQRData($ticketId, $eventId, $purchaseTime, $ticketNumber, $quantity = 1) {
        // Bilet için benzersiz QR kod verisi oluştur
        $data = [
            'ticket_id' => $ticketId,
            'ticket_number' => $ticketNumber,
            'event_id' => $eventId,
            'quantity' => $quantity,
            'purchase_time' => $purchaseTime,
            'verification_code' => $this->generateVerificationCode($ticketId, $eventId),
            'biletjack_url' => 'https://biletjack.com/verify/' . $ticketNumber
        ];
        
        return json_encode($data);
    }
    
    private function generateVerificationCode($ticketId, $eventId) {
        // Benzersiz doğrulama kodu oluştur
        $string = $ticketId . $eventId . time();
        return strtoupper(substr(md5($string), 0, 8));
    }
    
    private function generateUniqueTicketCode() {
        // Benzersiz bilet kodu oluştur
        return 'BJ' . date('Y') . strtoupper(substr(uniqid(), -8));
    }
    
    public function getTicketsByUser($userId) {
        $stmt = $this->conn->prepare("
            SELECT t.*, e.title as event_title, e.event_date as event_date, e.venue_name as event_location
            FROM tickets t
            JOIN events e ON t.event_id = e.id
            WHERE t.user_id = ?
            ORDER BY t.created_at DESC
        ");
        
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getTicketById($ticketId) {
        $stmt = $this->conn->prepare("
            SELECT t.*, e.title as event_title, e.event_date as event_date, e.venue_name as event_location,
                   u.first_name, u.last_name, u.email
            FROM tickets t
            JOIN events e ON t.event_id = e.id
            JOIN users u ON t.user_id = u.id
            WHERE t.id = ?
        ");
        
        $stmt->execute([$ticketId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function verifyTicket($ticketCode) {
        $stmt = $this->conn->prepare("
            SELECT t.*, e.title as event_title, e.event_date as event_date, e.venue_name as event_location
            FROM tickets t
            JOIN events e ON t.event_id = e.id
            WHERE t.ticket_number = ? AND t.status = 'active'
        ");
        
        $stmt->execute([$ticketCode]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function markTicketAsUsed($ticketId) {
        $stmt = $this->conn->prepare("UPDATE tickets SET status = 'used', used_date = NOW() WHERE id = ?");
        return $stmt->execute([$ticketId]);
    }
}