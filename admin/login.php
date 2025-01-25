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

    .btn-login:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
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

    /* Responsive düzenlemeler */
    @media (max-width: 480px) {
        .login-container {
            margin: 1rem;
            padding: 1.5rem;
        }
    }
    </style>

    <!-- Font import -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
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