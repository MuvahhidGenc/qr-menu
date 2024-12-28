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
</head>
<body>

<div class="main-content">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h3 class="text-center mb-4">Admin Girişi</h3>
                        <form method="POST">
                            <div class="mb-3">
                                <label>Kullanıcı Adı</label>
                                <input type="text" name="username" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Şifre</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <button type="submit" name="login" class="btn btn-primary w-100">Giriş Yap</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>