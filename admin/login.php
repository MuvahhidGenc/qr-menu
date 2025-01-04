<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Session kontrolü
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db = new Database();
$error = '';

if(isset($_POST['login'])) {
    $username = cleanInput($_POST['username']);
    $password = $_POST['password'];
    
    try {
        $stmt = $db->query("SELECT a.*, r.id as role_id, r.slug as role_slug, r.name as role_name 
                           FROM admins a 
                           LEFT JOIN roles r ON a.role_id = r.id 
                           WHERE a.username = ?", [$username]);
        $user = $stmt->fetch();
        
        if($user && verifyPassword($password, $user['password'])) {
            // Session'ı temizle
            session_unset();
            session_destroy();
            session_start();
            session_regenerate_id(true);
            
            // Yeni session bilgilerini ata
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_name'] = $user['username'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['role_slug'] = $user['role_slug'];
            $_SESSION['logged_in'] = true;
            $_SESSION['last_activity'] = time();
            
            // Debug için session durumunu logla
            error_log("Login.php - New Session Created: " . print_r($_SESSION, true));
            
            // Session'ı kaydet
            session_write_close();
            
            // Yönlendirmeyi dashboard.php'ye değiştir
            header("Location: dashboard.php");
            exit();
        } else {
            $error = 'Kullanıcı adı veya şifre hatalı!';
        }
    } catch (Exception $e) {
        error_log("Login Error: " . $e->getMessage());
        $error = 'Sistem hatası oluştu!';
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
</head>
<body>
<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <h3>Admin Girişi</h3>
        </div>
        <div class="login-body">
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="username" class="form-label">Kullanıcı Adı</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Şifre</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                
                <button type="submit" name="login" class="btn btn-primary w-100">Giriş Yap</button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<?php if($error): ?>
<script>
    Swal.fire({
        icon: 'error',
        title: 'Giriş Başarısız!',
        text: '<?php echo $error; ?>'
    });
</script>
<?php endif; ?>

</body>
</html>