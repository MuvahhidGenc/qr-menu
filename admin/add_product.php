<?php
require_once '../includes/config.php';
checkAuth();

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = cleanInput($_POST['name']);
    $description = cleanInput($_POST['description']);
    $price = floatval($_POST['price']);
    $category_id = intval($_POST['category_id']);
    
    if(isset($_FILES['image'])) {
        $image = secureUpload($_FILES['image']);
        if($image) {
            $sql = "INSERT INTO products (name, description, price, category_id, image) VALUES (?, ?, ?, ?, ?)";
            $db->query($sql, [$name, $description, $price, $category_id, $image]);
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Ürün Ekle</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label>Ürün Adı</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Açıklama</label>
                <textarea name="description" class="form-control"></textarea>
            </div>
            <div class="mb-3">
                <label>Fiyat</label>
                <input type="number" step="0.01" name="price" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Kategori</label>
                <select name="category_id" class="form-control" required>
                    <?php
                    $categories = $db->query("SELECT * FROM categories")->fetchAll();
                    foreach($categories as $category):
                    ?>
                    <option value="<?= $category['id'] ?>"><?= sanitizeOutput($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label>Resim</label>
                <input type="file" name="image" class="form-control" accept="image/*" required>
            </div>
            <button type="submit" class="btn btn-primary">Ekle</button>
        </form>
    </div>
</body>
</html>