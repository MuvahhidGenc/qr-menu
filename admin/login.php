<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/ratelimit.php';
require_once '../includes/security_logger.php';

// Debug için
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    error_log('Login page accessed');
}

// Önce mevcut session'ı temizle
session_unset();
session_destroy();
session_start();

$error = '';
$remainingTime = 0;
$requireCaptcha = false;

// Veritabanı bağlantısını kontrol et
try {
    $db = new Database();
    $db->query("SELECT 1")->fetch(); // Test query
} catch (Exception $e) {
    die("Veritabanı bağlantı hatası. Lütfen daha sonra tekrar deneyin.");
}

// Rate limiting kontrolü
$rateLimit = new RateLimit();
$securityLogger = new SecurityLogger();
$clientIP = getClientIP();

// Server-side güvenlik kontrolleri
function validateRequest() {
    // User-Agent kontrolü
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if (empty($userAgent) || strlen($userAgent) < 10) {
        return "Geçersiz tarayıcı bilgisi";
    }
    
    // Şüpheli User-Agent'ları tespit et
    $suspiciousAgents = ['curl', 'wget', 'python', 'bot', 'crawler', 'scanner', 'hack'];
    foreach ($suspiciousAgents as $agent) {
        if (stripos($userAgent, $agent) !== false) {
            return "Otomatik araçlar desteklenmez";
        }
    }
    
    // Referer kontrolü (POST istekleri için)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $expectedHost = $_SERVER['HTTP_HOST'] ?? '';
        
        if (empty($referer) || strpos($referer, $expectedHost) === false) {
            return "Geçersiz istek kaynağı";
        }
    }
    
    // Content-Type kontrolü (POST için)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/x-www-form-urlencoded') === false && 
            strpos($contentType, 'multipart/form-data') === false) {
            return "Geçersiz veri türü";
        }
    }
    
    // İstek hızı kontrolü (çok hızlı istekler)
    if (isset($_SESSION['last_request_time'])) {
        $timeDiff = microtime(true) - $_SESSION['last_request_time'];
        if ($timeDiff < 1) { // 1 saniyeden hızlı istekler şüpheli
            return "İstekler çok hızlı gönderiliyor";
        }
    }
    $_SESSION['last_request_time'] = microtime(true);
    
    return null;
}

// Güvenlik kontrolü
$securityError = validateRequest();
if ($securityError) {
    // Security logger ile kaydet
    $securityLogger->logSecurityViolation('REQUEST_VALIDATION', $securityError);
    
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        error_log('Security violation from IP: ' . $clientIP . ' - ' . $securityError . ' - UA: ' . ($_SERVER['HTTP_USER_AGENT'] ?? 'none'));
    }
    
    // Güvenlik ihlali - otomatik blokla
    $rateLimit->recordAttempt($clientIP, false);
    $rateLimit->recordAttempt($clientIP, false); // 2 kez kaydet (hızlı blok)
    
    $error = "Güvenlik ihlali tespit edildi. Lütfen normal tarayıcı kullanın.";
}

// Basit CAPTCHA üretici
function generateCaptcha() {
    $num1 = rand(1, 10);
    $num2 = rand(1, 10);
    $operations = ['+', '-'];
    $operation = $operations[array_rand($operations)];
    
    if ($operation === '+') {
        $result = $num1 + $num2;
        $question = "$num1 + $num2 = ?";
    } else {
        // Negatif sonuç olmaması için büyük sayıdan küçüğü çıkar
        if ($num1 < $num2) {
            $temp = $num1;
            $num1 = $num2;
            $num2 = $temp;
        }
        $result = $num1 - $num2;
        $question = "$num1 - $num2 = ?";
    }
    
    $_SESSION['captcha'] = $result;
    return $question;
}

// CAPTCHA gerekli mi kontrol et (POST öncesi)
$attempts = $rateLimit->getAttempts($clientIP);
$requireCaptcha = ($attempts >= 3);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$securityError) {
    try {
        $username = cleanInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Ek güvenlik kontrolleri
        if (strlen($username) > 50) {
            throw new Exception('Kullanıcı adı çok uzun');
        }
        if (strlen($password) > 100) {
            throw new Exception('Şifre çok uzun');
        }
        
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log('Login attempt - Username: ' . $username . ' - IP: ' . $clientIP);
        }
        
        // CAPTCHA kontrolü (3+ başarısız denemeden sonra)
        $attempts = $rateLimit->getAttempts($clientIP);
        if ($attempts >= 3) {
            $requireCaptcha = true;
            $captchaResponse = $_POST['captcha'] ?? '';
            $expectedCaptcha = $_SESSION['captcha'] ?? '';
            
            if (empty($captchaResponse) || strtolower($captchaResponse) !== strtolower($expectedCaptcha)) {
                $error = 'CAPTCHA doğrulaması başarısız';
                $rateLimit->recordAttempt($clientIP, false);
                $securityLogger->logSuspiciousActivity('CAPTCHA_FAILED', 'CAPTCHA doğrulaması başarısız');
            } else {
                // CAPTCHA başarılı, devam et
                unset($_SESSION['captcha']);
            }
        }
        
    } catch (Exception $e) {
        $securityLogger->logSecurityViolation('SQL_INJECTION_ATTEMPT', $e->getMessage());
        $rateLimit->recordAttempt($clientIP, false);
        $rateLimit->recordAttempt($clientIP, false); // Double penalty
        $error = "Güvenlik ihlali tespit edildi.";
    }

    // Rate limiting kontrolü
    if (!$error && $rateLimit->isLimited($clientIP)) {
        $remainingTime = $rateLimit->getRemainingTime($clientIP);
        $error = "Çok fazla başarısız giriş denemesi yapıldı. " . ceil($remainingTime / 60) . " dakika sonra tekrar deneyin.";
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log('Login rate limited for IP: ' . $clientIP . ' - Remaining: ' . $remainingTime . 's');
        }
    } else {
        // Giriş validasyonu
        if (empty($username) || empty($password)) {
            $error = 'Kullanıcı adı ve şifre boş olamaz';
            $rateLimit->recordAttempt($clientIP, false);
        } elseif (strlen($username) > 50 || strlen($password) > 100) {
            $error = 'Geçersiz giriş bilgileri';
            $rateLimit->recordAttempt($clientIP, false);
        } else {
            // Login denemesi
            if (loginAdmin($username, $password)) {
                // Başarılı giriş
                $securityLogger->logLoginAttempt($username, true);
                
                if (defined('DEBUG_MODE') && DEBUG_MODE) {
                    error_log('Login successful - Session: ' . print_r($_SESSION, true));
                }
                // Başarılı giriş - rate limit sıfırla
                $rateLimit->recordAttempt($clientIP, true);
                
                // Session güvenliği için ID yenile
                session_regenerate_id(true);
                
                header('Location: dashboard.php');
                exit();
            } else {
                // Başarısız giriş
                $securityLogger->logLoginAttempt($username, false, 'Geçersiz kullanıcı adı veya şifre');
                
                if (defined('DEBUG_MODE') && DEBUG_MODE) {
                    error_log('Login failed for user: ' . $username . ' - IP: ' . $clientIP);
                }
                $rateLimit->recordAttempt($clientIP, false);
                
                // Brute force kontrolü
                $totalAttempts = $rateLimit->getAttempts($clientIP);
                if ($totalAttempts >= 5) {
                    $securityLogger->logBruteForceAttempt($totalAttempts, true);
                } elseif ($totalAttempts >= 3) {
                    $securityLogger->logBruteForceAttempt($totalAttempts, false);
                }
                
                // Rate limit kontrolü tekrar
                if ($rateLimit->isLimited($clientIP)) {
                    $remainingTime = $rateLimit->getRemainingTime($clientIP);
                    $error = "Çok fazla başarısız giriş denemesi yapıldı. " . ceil($remainingTime / 60) . " dakika sonra tekrar deneyin.";
                } else {
                    $attemptsLeft = $rateLimit->getAttemptsLeft($clientIP);
                    if ($attemptsLeft <= 2) {
                        $error = "Geçersiz kullanıcı adı veya şifre. " . $attemptsLeft . " hakkınız kaldı.";
                    } else {
                        $error = 'Geçersiz kullanıcı adı veya şifre';
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Girişi</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
    /* Modern Login Styles */
    body {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'Poppins', sans-serif;
    }

    .login-container {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 20px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        padding: 2.5rem;
        width: 100%;
        max-width: 400px;
        backdrop-filter: blur(10px);
        transition: transform 0.3s ease;
    }

    .login-container:hover {
        transform: translateY(-5px);
    }

    .login-header {
        text-align: center;
        margin-bottom: 2rem;
    }

    .login-header h1 {
        color: #2d3748;
        font-size: 2rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .login-header p {
        color: #718096;
        font-size: 0.95rem;
    }

    .form-group {
        margin-bottom: 1.5rem;
        position: relative;
    }

    .form-control {
        background: #f7fafc;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        padding: 0.75rem 1rem;
        padding-left: 3rem;
        font-size: 1rem;
        width: 100%;
        transition: all 0.3s ease;
    }

    .form-control:focus {
        border-color: #667eea;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .form-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #a0aec0;
        transition: color 0.3s ease;
    }

    .form-control:focus + .form-icon {
        color: #667eea;
    }

    .btn-login {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        border-radius: 12px;
        color: white;
        padding: 0.75rem;
        font-size: 1rem;
        font-weight: 500;
        width: 100%;
        margin-top: 1rem;
        transition: all 0.3s ease;
    }

    .btn-login:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    }

    .btn-login:disabled {
        background: #a0aec0;
        cursor: not-allowed;
        transform: none;
    }

    .alert {
        border-radius: 12px;
        padding: 1rem;
        margin-bottom: 1.5rem;
        border: none;
        background: #fff5f5;
        color: #c53030;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .alert-icon {
        font-size: 1.2rem;
    }

    /* Animasyonlar */
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .login-container {
        animation: fadeIn 0.6s ease-out;
    }

    /* Countdown Styles */
    .countdown-container {
        margin: 1.5rem 0;
        text-align: center;
    }

    .countdown-text {
        color: #e53e3e;
        font-weight: 500;
        margin-bottom: 0.75rem;
        font-size: 0.95rem;
    }

    .progress {
        height: 6px;
        background: #e2e8f0;
        border-radius: 3px;
        overflow: hidden;
    }

    .progress-bar {
        background: linear-gradient(90deg, #e53e3e 0%, #f56565 100%);
        height: 100%;
        transition: width 1s linear;
        border-radius: 3px;
    }

    /* Login Footer */
    .login-footer {
        text-align: center;
        margin-top: 1.5rem;
        padding-top: 1rem;
        border-top: 1px solid #e2e8f0;
    }

    .login-footer .text-muted {
        color: #718096 !important;
        font-size: 0.85rem;
    }

    /* CAPTCHA Styles */
    .captcha-group {
        background: #f8fafc;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        padding: 1rem;
        margin-bottom: 1.5rem;
    }

    .captcha-question {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 0.75rem 1rem;
        border-radius: 8px;
        text-align: center;
        font-size: 1rem;
        font-weight: 500;
        margin-bottom: 0.75rem;
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
    }

    .captcha-group .form-control {
        background: white;
        border: 2px solid #cbd5e0;
        text-align: center;
        font-weight: 600;
        font-size: 1.1rem;
    }

    .captcha-group .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15);
    }

    /* Security Enhancements */
    .form-control:disabled {
        background: #f1f5f9;
        border-color: #cbd5e0;
        color: #a0aec0;
        cursor: not-allowed;
    }

    .form-control:disabled + .form-icon {
        color: #cbd5e0;
    }

    /* Loading Animation */
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }

    .btn-login:disabled {
        animation: pulse 2s infinite;
    }

    /* Responsive düzenlemeler */
    @media (max-width: 480px) {
        .login-container {
            margin: 1rem;
            padding: 1.5rem;
        }
        
        .login-header h1 {
            font-size: 1.6rem;
        }
        
        .form-control {
            padding: 0.6rem 0.8rem;
            padding-left: 2.5rem;
        }
        
        .form-icon {
            left: 0.8rem;
            font-size: 0.9rem;
        }
    }
    </style>

    <!-- Font import -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
<div class="login-container">
    <div class="login-header">
        <h1>
            <i class="fas fa-shield-alt me-2"></i>
            Admin Paneli
        </h1>
        <p>Güvenli giriş yapın</p>
    </div>

    <?php if($error): ?>
    <div class="alert">
        <i class="fas fa-exclamation-triangle alert-icon"></i>
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="" id="loginForm">
        <div class="form-group">
            <input type="text" 
                   class="form-control" 
                   id="username" 
                   name="username" 
                   placeholder="Kullanıcı Adı"
                   maxlength="50"
                   required 
                   <?= ($remainingTime > 0) ? 'disabled' : '' ?>>
            <i class="fas fa-user form-icon"></i>
        </div>
        
        <div class="form-group">
            <input type="password" 
                   class="form-control" 
                   id="password" 
                   name="password" 
                   placeholder="Şifre"
                   maxlength="100"
                   required 
                   <?= ($remainingTime > 0) ? 'disabled' : '' ?>>
            <i class="fas fa-lock form-icon"></i>
        </div>

        <?php if ($requireCaptcha && $remainingTime == 0): ?>
        <div class="form-group captcha-group">
            <div class="captcha-question">
                <i class="fas fa-shield-alt me-2"></i>
                Güvenlik doğrulaması: <strong><?= generateCaptcha() ?></strong>
            </div>
            <input type="number" 
                   class="form-control" 
                   id="captcha" 
                   name="captcha" 
                   placeholder="Sonucu yazın"
                   min="0"
                   max="20"
                   required>
            <i class="fas fa-calculator form-icon"></i>
        </div>
        <?php endif; ?>


        <?php if ($remainingTime > 0): ?>
        <div class="countdown-container">
            <div class="countdown-text">
                <i class="fas fa-clock me-2"></i>
                Bekleme süresi: <span id="countdown"><?= ceil($remainingTime / 60) ?> dakika</span>
            </div>
            <div class="progress">
                <div class="progress-bar" style="width: 0%"></div>
            </div>
        </div>
        <button type="submit" class="btn-login" disabled>
            <i class="fas fa-clock me-2"></i>
            Bekleme Süresi
        </button>
        <?php else: ?>
        <button type="submit" class="btn-login" name="login">
            <i class="fas fa-sign-in-alt me-2"></i>
            Giriş Yap
        </button>
        <?php endif; ?>
    </form>

    <div class="login-footer">
        <small class="text-muted">
            <i class="fas fa-info-circle me-1"></i>
            5 başarısız denemeden sonra 10 dakika bekleme süresi
        </small>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Countdown Timer
<?php if ($remainingTime > 0): ?>
let remainingSeconds = <?= $remainingTime ?>;
const totalSeconds = 600; // 10 dakika
let countdownInterval;

function updateCountdown() {
    const minutes = Math.floor(remainingSeconds / 60);
    const seconds = remainingSeconds % 60;
    
    // Countdown text güncelle
    const countdownEl = document.getElementById('countdown');
    if (countdownEl) {
        if (remainingSeconds > 60) {
            countdownEl.textContent = `${minutes} dakika ${seconds.toString().padStart(2, '0')} saniye`;
        } else {
            countdownEl.textContent = `${remainingSeconds} saniye`;
        }
    }
    
    // Progress bar güncelle
    const progressBar = document.querySelector('.progress-bar');
    if (progressBar) {
        const progressPercent = ((totalSeconds - remainingSeconds) / totalSeconds) * 100;
        progressBar.style.width = progressPercent + '%';
    }
    
    // Süre bitti mi kontrol et
    if (remainingSeconds <= 0) {
        clearInterval(countdownInterval);
        // Sayfayı yenile
        window.location.reload();
    }
    
    remainingSeconds--;
}

// İlk çağrı
updateCountdown();

// Her saniye güncelle
countdownInterval = setInterval(updateCountdown, 1000);
<?php endif; ?>

// Form submission güvenliği
document.getElementById('loginForm').addEventListener('submit', function(e) {
    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value;
    
    if (!username || !password) {
        e.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: 'Eksik Bilgi',
            text: 'Lütfen kullanıcı adı ve şifreyi girin.'
        });
        return false;
    }
    
    if (username.length > 50 || password.length > 100) {
        e.preventDefault();
        Swal.fire({
            icon: 'error',
            title: 'Geçersiz Giriş',
            text: 'Kullanıcı adı veya şifre çok uzun.'
        });
        return false;
    }
    
    // CAPTCHA kontrolü
    const captchaInput = document.getElementById('captcha');
    if (captchaInput && captchaInput.required) {
        const captchaValue = captchaInput.value.trim();
        if (!captchaValue) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'CAPTCHA Gerekli',
                text: 'Lütfen güvenlik doğrulamasını tamamlayın.'
            });
            captchaInput.focus();
            return false;
        }
        
        const captchaNum = parseInt(captchaValue);
        if (isNaN(captchaNum) || captchaNum < 0 || captchaNum > 20) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Geçersiz CAPTCHA',
                text: 'Lütfen geçerli bir sayı girin.'
            });
            captchaInput.focus();
            return false;
        }
    }
    
    // Submit button'u disable et
    const submitBtn = document.querySelector('.btn-login');
    if (submitBtn && !submitBtn.disabled) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Giriş yapılıyor...';
        
        // 5 saniye sonra tekrar enable et (timeout için)
        setTimeout(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-sign-in-alt me-2"></i>Giriş Yap';
        }, 5000);
    }
});

// Input focus efektleri
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('.form-control');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.querySelector('.form-icon').style.color = '#667eea';
        });
        
        input.addEventListener('blur', function() {
            if (!this.value) {
                this.parentElement.querySelector('.form-icon').style.color = '#a0aec0';
            }
        });
    });
});
</script>

<?php if($error && $remainingTime <= 0): ?>
<script>
    Swal.fire({
        icon: 'error',
        title: 'Giriş Başarısız!',
        text: '<?= addslashes($error) ?>',
        confirmButtonColor: '#667eea'
    });
</script>
<?php endif; ?>

</body>
</html>