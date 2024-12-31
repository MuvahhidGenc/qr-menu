
<?php
require_once '../includes/config.php';
$db = new Database();

$admin_id = $_SESSION['admin'];
$admin = $db->query("SELECT * FROM admins WHERE id = ?", [$admin_id])->fetch();

if(isset($_POST['update_profile'])) {
   $username = cleanInput($_POST['username']);
   $email = cleanInput($_POST['email']);
   
   $db->query("UPDATE admins SET username = ?, email = ? WHERE id = ?", 
              [$username, $email, $admin_id]);
              
   $_SESSION['message'] = 'Profil güncellendi.';
   $_SESSION['message_type'] = 'success';
   header('Location: profile.php');
   exit;
}

if(isset($_POST['change_password'])) {
   $current_password = $_POST['current_password'];
   $new_password = $_POST['new_password'];
   $confirm_password = $_POST['confirm_password'];
   
   if(verifyPassword($current_password, $admin['password'])) {
       if($new_password === $confirm_password) {
           $hashed_password = hashPassword($new_password);
           $db->query("UPDATE admins SET password = ? WHERE id = ?", 
                      [$hashed_password, $admin_id]);
                      
           $_SESSION['message'] = 'Şifre güncellendi.';
           $_SESSION['message_type'] = 'success';
       } else {
           $_SESSION['message'] = 'Yeni şifreler eşleşmiyor.';
           $_SESSION['message_type'] = 'danger';
       }
   } else {
       $_SESSION['message'] = 'Mevcut şifre hatalı.';
       $_SESSION['message_type'] = 'danger';
   }
   header('Location: profile.php');
   exit;
}

include 'navbar.php';
?>

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
               <form method="POST">
                   <div class="mb-3">
                       <label>Mevcut Şifre</label>
                       <input type="password" name="current_password" class="form-control" required>
                   </div>
                   <div class="mb-3">
                       <label>Yeni Şifre</label>
                       <input type="password" name="new_password" class="form-control" required>
                   </div>
                   <div class="mb-3">
                       <label>Yeni Şifre Tekrar</label>
                       <input type="password" name="confirm_password" class="form-control" required>
                   </div>
                   <button type="submit" name="change_password" class="btn btn-warning">
                       <i class="fas fa-key"></i> Şifre Değiştir
                   </button>
               </form>
           </div>
       </div>
   </div>
</div>

</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>