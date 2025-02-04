<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Oturum kontrolü
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Veritabanı bağlantısı
$db = new Database();

// Personel ekleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_employee'])) {
    $name = cleanInput($_POST['name']);
    $position = cleanInput($_POST['position']);
    $salary = floatval($_POST['salary']);
    $bonus = floatval($_POST['bonus']);

    $db->query("INSERT INTO employees (name, position, salary, bonus) VALUES (?, ?, ?, ?)", [$name, $position, $salary, $bonus]);
    header('Location: employees.php');
    exit();
}

// Personel listesini al
$employees = $db->query("SELECT * FROM admins WHERE role_id != 1")->fetchAll(); // Süper admin olmayanları al
include 'navbar.php';
?>

<div class="container mt-5">
    <h2 class="mb-4">Personel Yönetimi</h2>
    <button class="btn btn-primary mb-4" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">Yeni Personel Ekle</button>

    <h3 class="mb-3">Personel Listesi</h3>
    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>İsim</th>
                <th>Pozisyon</th>
                <th>Maaş</th>
                <th>Prim</th>
                <th>İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($employees as $employee): ?>
                <tr>
                    <td><?php echo $employee['id']; ?></td>
                    <td><?php echo $employee['name']; ?></td>
                    <td><?php echo $employee['role_id']; // Pozisyonu role_id ile ilişkilendirin ?></td>
                    <td><?php echo $employee['salary']; ?></td>
                    <td><?php echo $employee['bonus']; ?></td>
                    <td>
                        <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editEmployeeModal<?php echo $employee['id']; ?>">Düzenle</button>
                    </td>
                </tr>

                <!-- Düzenleme Modal -->
                <div class="modal fade" id="editEmployeeModal<?php echo $employee['id']; ?>" tabindex="-1" aria-labelledby="editEmployeeModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editEmployeeModalLabel">Personel Düzenle</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form method="POST" action="edit_employee.php">
                                    <input type="hidden" name="id" value="<?php echo $employee['id']; ?>">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">İsim</label>
                                        <input type="text" name="name" class="form-control" value="<?php echo $employee['name']; ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="role_id" class="form-label">Pozisyon</label>
                                        <select name="role_id" class="form-control" required>
                                            <option value="2" <?php echo ($employee['role_id'] == 2) ? 'selected' : ''; ?>>Yönetici</option>
                                            <option value="3" <?php echo ($employee['role_id'] == 3) ? 'selected' : ''; ?>>Mutfak</option>
                                            <option value="4" <?php echo ($employee['role_id'] == 4) ? 'selected' : ''; ?>>Garson</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="salary" class="form-label">Maaş</label>
                                        <input type="number" name="salary" class="form-control" value="<?php echo $employee['salary']; ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="bonus" class="form-label">Prim</label>
                                        <input type="number" name="bonus" class="form-control" value="<?php echo $employee['bonus']; ?>">
                                    </div>
                                    <button type="submit" class="btn btn-primary">Düzenle</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Ekleme Modal -->
<div class="modal fade" id="addEmployeeModal" tabindex="-1" aria-labelledby="addEmployeeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addEmployeeModalLabel">Yeni Personel Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="name" class="form-label">İsim</label>
                        <input type="text" name="name" class="form-control" placeholder="İsim" required>
                    </div>
                    <div class="mb-3">
                        <label for="role_id" class="form-label">Pozisyon</label>
                        <select name="role_id" class="form-control" required>
                            <option value="2">Yönetici</option>
                            <option value="3">Mutfak</option>
                            <option value="4">Garson</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="salary" class="form-label">Maaş</label>
                        <input type="number" name="salary" class="form-control" placeholder="Maaş" required>
                    </div>
                    <div class="mb-3">
                        <label for="bonus" class="form-label">Prim</label>
                        <input type="number" name="bonus" class="form-control" placeholder="Prim" value="0">
                    </div>
                    <button type="submit" name="add_employee" class="btn btn-primary">Ekle</button>
                </form>
            </div>
        </div>
    </div>
</div> 