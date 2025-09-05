<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';

// Sadece hizmet sağlayıcı kullanıcılar erişebilsin
if (!isLoggedIn() || !isService()) {
    header('Location: /index.php');
    exit();
}

$currentUser = getCurrentUser();

function h($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

function timeAgoTR($datetime) {
    $ts = strtotime($datetime);
    if (!$ts) return h($datetime);
    $diff = time() - $ts;
    if ($diff < 60) return 'az önce';
    $mins = floor($diff / 60);
    if ($mins < 60) return $mins . ' dk önce';
    $hours = floor($mins / 60);
    if ($hours < 24) return $hours . ' saat önce';
    $days = floor($hours / 24);
    if ($days < 7) return $days . ' gün önce';
    $weeks = floor($days / 7);
    if ($weeks < 5) return $weeks . ' hafta önce';
    $months = floor($days / 30);
    if ($months < 12) return $months . ' ay önce';
    $years = floor($days / 365);
    return $years . ' yıl önce';
}

// AJAX: Bildirim okundu/ hepsini okundu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    try {
        $db = new Database();
        $pdo = $db->getConnection();

        // Bildirim tablosu garanti olsun
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                related_event_id INT NULL,
                created_by INT NULL,
                is_read TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (related_event_id) REFERENCES events(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $uid = (int)($currentUser['id'] ?? 0);
        if (!$uid) {
            echo json_encode(['success' => false, 'message' => 'Oturum bulunamadı.']);
            exit;
        }

        if ($_POST['action'] === 'mark_read') {
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
                $stmt->execute([$id, $uid]);
            }
            echo json_encode(['success' => true]);
            exit;
        }

        if ($_POST['action'] === 'mark_all_read') {
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
            $stmt->execute([$uid]);
            echo json_encode(['success' => true]);
            exit;
        }

        echo json_encode(['success' => false, 'message' => 'Bilinmeyen işlem.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
    }
    exit;
}

// Sayfa verileri
$items = [];
$error = null;
try {
    $db = new Database();
    $pdo = $db->getConnection();

    // Bildirim tablosu garanti olsun (select öncesi oluştur)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            related_event_id INT NULL,
            created_by INT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (related_event_id) REFERENCES events(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $uid = (int)($currentUser['id'] ?? 0);
    $stmt = $pdo->prepare("
        SELECT id, title, message, related_event_id, is_read, created_at
        FROM notifications
        WHERE user_id = ?
        ORDER BY is_read ASC, created_at DESC
        LIMIT 200
    ");
    $stmt->execute([$uid]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>Hizmet Sağlayıcı Bildirim Paneli</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php
    // Favicon ayarını veritabanından al
    try {
        $stmt = $pdo->prepare("SELECT site_favicon FROM site_settings WHERE id = 1");
        $stmt->execute();
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        $favicon = $settings['site_favicon'] ?? '../assets/images/favicon.ico';
        echo '<link rel="icon" type="image/x-icon" href="' . htmlspecialchars($favicon) . '">';
    } catch (Exception $e) {
        echo '<link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">';
    }
    ?>
    <style>
        :root {
            --bg: #0f172a;
            --panel: #111827;
            --card: #1f2937;
            --muted: #94a3b8;
            --text: #e5e7eb;
            --primary: #4f46e5;
            --primary-700: #4338ca;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --border: #334155;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; background: linear-gradient(120deg, #0b1023, #101527); color: var(--text);
            font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; min-height: 100vh;
        }
        .wrap {
            max-width: 960px; margin: 0 auto; padding: 24px;
        }
        .header {
            background: rgba(17,24,39,0.8); backdrop-filter: blur(8px);
            border: 1px solid var(--border); border-radius: 16px; padding: 16px 20px; margin-bottom: 18px;
            display: flex; align-items: center; justify-content: space-between;
        }
        .title { font-size: 18px; font-weight: 700; letter-spacing: .3px; display:flex; gap:10px; align-items:center; }
        .title .badge { background: var(--primary); padding:2px 8px; border-radius: 999px; font-size: 12px; }
        .actions { display:flex; gap:10px; flex-wrap: wrap; }
        .btn {
            border: 1px solid var(--border); background: #0b1023; color: var(--text);
            padding: 8px 12px; border-radius: 10px; cursor: pointer; font-weight: 600; text-decoration: none; display:inline-block;
        }
        .btn:hover { border-color: var(--primary); color: #fff; }
        .btn.primary { background: var(--primary); border-color: var(--primary); }
        .btn.primary:hover { background: var(--primary-700); }
        .list {
            display: grid; gap: 12px;
        }
        .empty {
            text-align: center; color: var(--muted); padding: 40px 12px; border:1px dashed var(--border); border-radius: 12px; background: rgba(2,6,23,0.6);
        }
        .card {
            background: rgba(15,23,42,0.75); border: 1px solid var(--border);
            padding: 14px; border-radius: 14px; display: grid; gap: 8px;
        }
        .card.unread { border-color: var(--primary); }
        .row { display:flex; align-items:center; gap: 10px; justify-content: space-between; }
        .meta { color: var(--muted); font-size: 13px; display:flex; gap:8px; align-items:center; }
        .tag {
            font-size: 11px; border:1px solid var(--border); color: var(--muted);
            padding: 2px 8px; border-radius: 999px;
        }
        a.link { color: #93c5fd; text-decoration: none; }
        a.link:hover { text-decoration: underline; }
        .footer {
            margin-top: 16px; text-align: center; color: var(--muted); font-size: 12px;
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="header">
        <div class="title">
            <span>Hizmet Sağlayıcı Bildirim Paneli</span>
            <span class="badge"><?php echo count($items); ?></span>
        </div>
        <div class="actions">
            <a class="btn" href="../index.php">Ana sayfaya dön</a>
            <a class="btn" href="../auth/logout.php">Çıkış yap</a>
            <button class="btn" id="refreshBtn">Yenile</button>
            <button class="btn primary" id="markAllBtn">Tümünü okundu işaretle</button>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="empty">Hata: <?php echo h($error); ?></div>
    <?php elseif (empty($items)): ?>
        <div class="empty">Henüz bildiriminiz yok.</div>
    <?php else: ?>
        <div class="list">
            <?php foreach ($items as $n): ?>
                <div class="card <?php echo !$n['is_read'] ? 'unread' : ''; ?>" data-id="<?php echo (int)$n['id']; ?>">
                    <div class="row">
                        <div style="font-weight:700;"><?php echo h($n['title']); ?></div>
                        <?php if (!$n['is_read']): ?>
                            <span class="tag">Yeni</span>
                        <?php else: ?>
                            <span class="tag" style="border-color:#1f2937;">Okundu</span>
                        <?php endif; ?>
                    </div>
                    <div><?php echo nl2br(h($n['message'])); ?></div>
                    <div class="row">
                        <div class="meta">
                            <span><?php echo timeAgoTR($n['created_at']); ?></span>
                            <?php if ($n['related_event_id']): ?>
                                <span>•</span>
                                <a class="link" href="<?php echo '../etkinlik-detay.php?id=' . (int)$n['related_event_id']; ?>" target="_blank">Etkinliği Gör</a>
                            <?php endif; ?>
                        </div>
                        <?php if (!$n['is_read']): ?>
                            <button class="btn" onclick="markRead(<?php echo (int)$n['id']; ?>, this)">Okundu işaretle</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="footer">Sadece bildirim görüntüleme amaçlı basit panel.</div>
</div>

<script>
    function postForm(data) {
        return fetch(location.href, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'},
            body: new URLSearchParams(data).toString()
        }).then(r => r.json());
    }
    function markRead(id, btn) {
        postForm({action: 'mark_read', id: id}).then(res => {
            if (res && res.success) {
                const card = btn.closest('.card');
                if (card) {
                    card.classList.remove('unread');
                    const tag = card.querySelector('.tag');
                    if (tag) { tag.textContent = 'Okundu'; tag.style.borderColor = '#1f2937'; }
                    btn.remove();
                }
            } else {
                alert(res && res.message ? res.message : 'İşlem başarısız.');
            }
        }).catch(() => alert('Ağ hatası.'));
    }
    document.getElementById('markAllBtn').addEventListener('click', function() {
        postForm({action: 'mark_all_read'}).then(res => {
            if (res && res.success) {
                document.querySelectorAll('.card.unread').forEach(c => {
                    c.classList.remove('unread');
                    const tag = c.querySelector('.tag'); if (tag) { tag.textContent = 'Okundu'; tag.style.borderColor = '#1f2937'; }
                    const btn = c.querySelector('button.btn'); if (btn) btn.remove();
                });
            } else {
                alert(res && res.message ? res.message : 'İşlem başarısız.');
            }
        }).catch(() => alert('Ağ hatası.'));
    });
    document.getElementById('refreshBtn').addEventListener('click', () => location.reload());
</script>
</body>
</html>