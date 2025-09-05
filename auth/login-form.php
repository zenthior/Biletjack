<?php
require_once '../includes/session.php';

// Zaten giriş yapmış kullanıcıları ana sayfaya yönlendir
if (isLoggedIn()) {
    header('Location: ../index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap - BiletJack</title>
    <?php
    // Favicon ayarını veritabanından al
    require_once '../config/database.php';
    try {
        $db = new Database();
        $pdo = $db->getConnection();
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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
        }

        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .login-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .login-header p {
            opacity: 0.9;
            font-size: 1rem;
        }

        .login-form {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 600;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-checkbox {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .form-checkbox input {
            width: auto;
        }

        .login-btn {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 0.75rem;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .login-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .login-links {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e1e5e9;
        }

        .login-links a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }

        .login-links a:hover {
            text-decoration: underline;
        }

        .alert {
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .alert-error {
            background: #fee;
            color: #c53030;
            border: 1px solid #fed7d7;
        }

        .alert-success {
            background: #000000;
            color: #ffffff;
            border: 1px solid #000000;
        }

        .loading {
            display: none;
            text-align: center;
            margin-top: 1rem;
        }

        .spinner {
            border: 2px solid #f3f3f3;
            border-top: 2px solid #667eea;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            display: inline-block;
            margin-right: 0.5rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Giriş Yap</h1>
            <p>Hesabınıza giriş yapın</p>
        </div>
        
        <form class="login-form" id="loginForm">
            <div id="alertContainer"></div>
            
            <div class="form-group">
                <label for="email" class="form-label">E-posta Adresi</label>
                <input type="email" id="email" name="email" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">Şifre</label>
                <input type="password" id="password" name="password" class="form-input" required>
            </div>
            
            <div class="form-checkbox">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember">Beni hatırla</label>
            </div>
            
            <button type="submit" class="login-btn" id="loginBtn">
                Giriş Yap
            </button>
            
            <div class="loading" id="loading">
                <div class="spinner"></div>
                Giriş yapılıyor...
            </div>
        </form>
        
        <div class="login-links">
            <p>Hesabınız yok mu? <a href="register.php">Kayıt olun</a></p>
            <p><a href="../index.php">Ana Sayfaya Dön</a></p>
        </div>
    </div>

    <script>
        const urlParams = new URLSearchParams(window.location.search);
        const rawRedirect = urlParams.get('redirect');
        const getSafeRedirect = (r) => {
            try {
                if (!r) return null;
                const url = new URL(r, window.location.origin);
                if (url.origin !== window.location.origin) return null;
                if (!url.pathname.startsWith('/')) return null;
                return url.pathname + (url.search || '') + (url.hash || '');
            } catch (e) {
                return null;
            }
        };
        const returnTo = getSafeRedirect(rawRedirect);
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const loginBtn = document.getElementById('loginBtn');
            const loading = document.getElementById('loading');
            const alertContainer = document.getElementById('alertContainer');
            
            // UI'yi güncelle
            loginBtn.disabled = true;
            loading.style.display = 'block';
            alertContainer.innerHTML = '';
            
            try {
                const response = await fetch('login.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alertContainer.innerHTML = `
                        <div class="alert alert-success">
                            ${result.message}
                        </div>
                    `;
                    
                    // Başarılı giriş sonrası yönlendirme
                    setTimeout(() => {
                        const target = returnTo || result.redirect || '../index.php';
                        window.location.href = target;
                    }, 800);
                } else {
                    alertContainer.innerHTML = `
                        <div class="alert alert-error">
                            ${result.message}
                        </div>
                    `;
                }
            } catch (error) {
                alertContainer.innerHTML = `
                    <div class="alert alert-error">
                        Bir hata oluştu. Lütfen tekrar deneyin.
                    </div>
                `;
            } finally {
                loginBtn.disabled = false;
                loading.style.display = 'none';
            }
        });
    </script>
</body>
</html>