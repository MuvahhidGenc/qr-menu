<?php
require_once '../includes/config.php';
$db = new Database();

if(isset($_POST['login'])) {
    $username = cleanInput($_POST['username']);
    $password = $_POST['password'];
    
    $stmt = $db->query("SELECT * FROM admins WHERE username = ?", [$username]);
    $user = $stmt->fetch();
    
    if($user && verifyPassword($password, $user['password'])) {
        session_regenerate_id(true);
        
        $_SESSION['admin'] = $user['id'];
        $_SESSION['last_activity'] = time();
        $_SESSION['last_regeneration'] = time();
        
        header('Location: dashboard.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Admin Girişi</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    /* Ana Container */
    .login-container {
        min-height: 100vh;
        background: linear-gradient(135deg, #1a2a6c, #b21f1f, #fdbb2d);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem;
    }

    /* Login Kartı */
    .login-card {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 20px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        overflow: hidden;
        width: 100%;
        max-width: 400px;
        padding: 0;
    }

    /* Kart Başlığı */
    .login-header {
        background: rgba(255, 255, 255, 0.1);
        padding: 2rem;
        text-align: center;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .login-header h3 {
        color: #2c3e50;
        font-size: 1.8rem;
        font-weight: 600;
        margin: 0;
    }

    /* Form Alanı */
    .login-body {
        padding: 2rem;
    }

    /* Form Elemanları */
    .form-control {
        background: rgba(255, 255, 255, 0.9);
        border: 1px solid rgba(0, 0, 0, 0.1);
        border-radius: 12px;
        padding: 0.8rem 1.2rem;
        font-size: 1rem;
        transition: all 0.3s ease;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    }

    .form-control:focus {
        background: #fff;
        border-color: #3498db;
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.25);
    }

    .form-label {
        color: #2c3e50;
        font-weight: 500;
        margin-bottom: 0.5rem;
    }

    /* Login Butonu */
    .btn-login {
        background: linear-gradient(45deg, #3498db, #2980b9);
        border: none;
        border-radius: 12px;
        padding: 0.8rem;
        font-weight: 500;
        font-size: 1.1rem;
        color: white;
        width: 100%;
        margin-top: 1rem;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
    }

    .btn-login:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
        background: linear-gradient(45deg, #2980b9, #3498db);
    }

    /* Form İkon */
    .input-group-text {
        background: transparent;
        border: 1px solid rgba(0, 0, 0, 0.1);
        border-right: none;
        color: #7f8c8d;
        border-radius: 12px 0 0 12px;
    }

    .input-group .form-control {
        border-left: none;
        border-radius: 0 12px 12px 0;
    }

    /* Animasyonlar */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .login-card {
        animation: fadeIn 0.5s ease-out;
    }

    /* Responsive Düzenlemeler */
    @media (max-width: 576px) {
        .login-container {
            padding: 1rem;
        }
        
        .login-card {
            margin: 0;
        }
        
        .login-header {
            padding: 1.5rem;
        }
        
        .login-body {
            padding: 1.5rem;
        }
        
        .btn-login {
            font-size: 1rem;
            padding: 0.7rem;
        }
    }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <h3>Admin Girişi</h3>
        </div>
        <div class="login-body">
            <form method="POST">
                <div class="mb-4">
                    <label class="form-label">Kullanıcı Adı</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-user"></i>
                        </span>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label">Şifre</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                </div>
                <button type="submit" name="login" class="btn btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i> Giriş Yap
                </button>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>