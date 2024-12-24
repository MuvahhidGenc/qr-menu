<?php
require_once '../includes/config.php';
$db = new Database();

if(isset($_POST['create_backup'])) {
   $tables = ['categories', 'products', 'admins', 'settings'];
   $backup = '';
   
   foreach($tables as $table) {
       $rows = $db->query("SELECT * FROM $table")->fetchAll();
       
       $backup .= "DROP TABLE IF EXISTS $table;\n";
       $create = $db->query("SHOW CREATE TABLE $table")->fetch();
       $backup .= $create['Create Table'] . ";\n\n";
       
       foreach($rows as $row) {
           $backup .= "INSERT INTO $table VALUES (";
           foreach($row as $value) {
               $value = addslashes($value);
               $backup .= "'$value',";
           }
           $backup = rtrim($backup, ',');
           $backup .= ");\n";
       }
       $backup .= "\n";
   }
   
   $backup_file = '../backups/backup_' . date('Y-m-d_H-i-s') . '.sql';
   file_put_contents($backup_file, $backup);
   
   $_SESSION['message'] = 'Yedekleme oluşturuldu.';
   $_SESSION['message_type'] = 'success';
   header('Location: backup.php');
   exit;
}

$backups = glob('../backups/*.sql');
rsort($backups);

include 'navbar.php';
?>

<div class="main-content">
<div class="card">
   <div class="card-header d-flex justify-content-between align-items-center">
       <h5 class="mb-0">Yedeklemeler</h5>
       <form method="POST" style="display:inline">
           <button type="submit" name="create_backup" class="btn btn-primary">
               <i class="fas fa-download"></i> Yeni Yedek Oluştur
           </button>
       </form>
   </div>
   <div class="card-body">
       <div class="table-responsive">
           <table class="table table-hover">
               <thead>
                   <tr>
                       <th>Yedek Dosyası</th>
                       <th>Boyut</th>
                       <th>Tarih</th>
                       <th>İşlemler</th>
                   </tr>
               </thead>
               <tbody>
                   <?php foreach($backups as $backup): ?>
                       <tr>
                           <td><?= basename($backup) ?></td>
                           <td><?= formatBytes(filesize($backup)) ?></td>
                           <td><?= date('d.m.Y H:i:s', filemtime($backup)) ?></td>
                           <td>
                               <a href="download_backup.php?file=<?= basename($backup) ?>" 
                                  class="btn btn-success btn-sm">
                                   <i class="fas fa-download"></i> İndir
                               </a>
                               <a href="delete_backup.php?file=<?= basename($backup) ?>" 
                                  class="btn btn-danger btn-sm"
                                  onclick="return confirm('Bu yedeği silmek istediğinizden emin misiniz?')">
                                   <i class="fas fa-trash"></i> Sil
                               </a>
                           </td>
                       </tr>
                   <?php endforeach; ?>
               </tbody>
           </table>
       </div>
   </div>
</div>

<?php
function formatBytes($bytes) {
   $units = ['B', 'KB', 'MB', 'GB'];
   $bytes = max($bytes, 0);
   $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
   $pow = min($pow, count($units) - 1);
   return round($bytes / pow(1024, $pow), 2) . ' ' . $units[$pow];
}
?>