<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Session kontrolü
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kullanıcı giriş yapmamışsa login sayfasına yönlendir
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Veritabanı bağlantısı
$db = new Database();

// Kullanıcı bilgilerini al
$admin = $db->query("SELECT * FROM admins WHERE id = ?", [$_SESSION['admin_id']])->fetch();

if (!$admin) {
    header('Location: login.php');
    exit();
}

// Şifre değiştirme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = trim($_POST['current_password']);
    $newPassword = trim($_POST['new_password']);
    $confirmPassword = trim($_POST['confirm_password']);
    
    // Debug için
    error_log('Current Password: ' . $currentPassword);
    error_log('Admin Hashed Password: ' . $admin['password']);
    
    // Şifre doğrulama
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = 'Tüm alanları doldurunuz';
    } elseif (!password_verify($currentPassword, $admin['password'])) {
        error_log('Password verification failed');
        $error = 'Mevcut şifre yanlış';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Yeni şifreler eşleşmiyor';
    } elseif (strlen($newPassword) < 6) {
        $error = 'Yeni şifre en az 6 karakter olmalıdır';
    } else {
        try {
            // Yeni şifreyi hashle
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Şifreyi güncelle
            $result = $db->query(
                "UPDATE admins SET password = ? WHERE id = ?",
                [$hashedPassword, $_SESSION['admin_id']]
            );

            if ($result) {
                $_SESSION['success'] = 'Şifreniz başarıyla güncellendi';
                header('Location: profile.php');
                exit();
            } else {
                $error = 'Şifre güncellenirken bir hata oluştu';
            }
        } catch (Exception $e) {
            error_log('Password Update Error: ' . $e->getMessage());
            $error = 'Bir hata oluştu: ' . $e->getMessage();
        }
    }
}

// Başarı mesajını göster
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

if(isset($_POST['update_profile'])) {
   $username = cleanInput($_POST['username']);
   $email = cleanInput($_POST['email']);
   
   $db->query("UPDATE admins SET username = ?, email = ? WHERE id = ?", 
              [$username, $email, $_SESSION['admin_id']]);
              
   $_SESSION['message'] = 'Profil güncellendi.';
   $_SESSION['message_type'] = 'success';
   header('Location: profile.php');
   exit;
}

include 'navbar.php';
?>

<style>
/* Ana Container */
.container-fluid {
    padding: 2rem;
    background: #f8f9fa;
    min-height: 100vh;
}

/* Profil Kartları */
.card {
    border: none;
    border-radius: 20px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    background: white;
    margin-bottom: 2rem;
    overflow: hidden;
}

.card-header {
    background: linear-gradient(45deg, #2c3e50, #3498db);
    color: white;
    padding: 1.5rem;
    border: none;
}

.card-header h5 {
    margin: 0;
    font-size: 1.3rem;
    font-weight: 600;
}

.card-body {
    padding: 2rem;
}

/* Form Elemanları */
.form-control {
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    padding: 0.8rem 1.2rem;
    transition: all 0.3s ease;
    background: #f8f9fa;
}

.form-control:focus {
    border-color: #3498db;
    box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
    background: white;
}

/* Butonlar */
.btn {
    padding: 0.8rem 1.5rem;
    border-radius: 12px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-primary {
    background: linear-gradient(45deg, #3498db, #2980b9);
    border: none;
}

.btn-warning {
    background: linear-gradient(45deg, #f1c40f, #f39c12);
    border: none;
    color: white;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

/* Animasyonlar */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.card {
    animation: fadeIn 0.5s ease-out;
}
</style>

<div class="row">
   <div class="col-md-6">
       <div class="card mb-4">
           <div class="card-header">
               <h5 class="mb-0">Profil Bilgileri</h5>
           </div>
           <div class="card-body">
               <form method="POST">
                   <div class="mb-3">
                       <label>Kullanıcı Adı</label>
                       <input type="text" name="username" 
                              value="<?= htmlspecialchars($admin['username']) ?>" 
                              class="form-control" required>
                   </div>
                   <div class="mb-3">
                       <label>E-posta</label>
                       <input type="email" name="email" 
                              value="<?= htmlspecialchars($admin['email']) ?>" 
                              class="form-control" required>
                   </div>
                   <button type="submit" name="update_profile" class="btn btn-primary">
                       <i class="fas fa-save"></i> Güncelle
                   </button>
               </form>
           </div>
       </div>
   </div>
   
   <div class="col-md-6">
       <div class="card">
           <div class="card-header">
               <h5 class="mb-0">Şifre Değiştir</h5>
           </div>
           <div class="card-body">
               <?php if (isset($error)): ?>
                   <div class="alert alert-danger">
                       <?php echo htmlspecialchars($error); ?>
                   </div>
               <?php endif; ?>
               
               <?php if (isset($success)): ?>
                   <div class="alert alert-success">
                       <?php echo htmlspecialchars($success); ?>
                   </div>
               <?php endif; ?>

               <form method="POST" onsubmit="return validateForm()">
                   <div class="mb-3">
                       <label>Mevcut Şifre</label>
                       <input type="password" name="current_password" class="form-control" required>
                   </div>
                   <div class="mb-3">
                       <label>Yeni Şifre</label>
                       <input type="password" name="new_password" class="form-control" required minlength="6">
                   </div>
                   <div class="mb-3">
                       <label>Yeni Şifre Tekrar</label>
                       <input type="password" name="confirm_password" class="form-control" required minlength="6">
                   </div>
                   <button type="submit" name="change_password" class="btn btn-warning">
                       <i class="fas fa-key"></i> Şifre Değiştir
                   </button>
               </form>
           </div>
       </div>
   </div>
</div>

<script>
function validateForm() {
    const newPassword = document.querySelector('input[name="new_password"]').value;
    const confirmPassword = document.querySelector('input[name="confirm_password"]').value;
    
    if (newPassword !== confirmPassword) {
        alert('Yeni şifreler eşleşmiyor!');
        return false;
    }
    
    if (newPassword.length < 6) {
        alert('Yeni şifre en az 6 karakter olmalıdır!');
        return false;
    }
    
    return true;
}
</script>

</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>