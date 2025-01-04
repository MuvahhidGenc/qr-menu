<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Debug için
error_log('Login page accessed');

// Önce mevcut session'ı temizle
session_unset();
session_destroy();
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    error_log('Login attempt - Username: ' . $username);

    if (loginAdmin($username, $password)) {
        error_log('Login successful - Session: ' . print_r($_SESSION, true));
        header('Location: dashboard.php');
        exit();
    } else {
        error_log('Login failed for user: ' . $username);
        $error = 'Geçersiz kullanıcı adı veya şifre';
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