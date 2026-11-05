<?php
// admin/index.php — product management + category creation
include '../auth.php';
include '../config.php';
require_admin();

$message = '';
if (isset($_POST['add_category'])) {
    csrf_check();
    $cname = trim($_POST['category_name'] ?? '');
    if ($cname === '') {
        $message = "<div class='alert alert-danger'>Category name is required.</div>";
    } else {
        try {
            $stmt = mysqli_prepare($conn, 'INSERT INTO categories (name) VALUES (?)');
            mysqli_stmt_bind_param($stmt, 's', $cname);
            mysqli_stmt_execute($stmt);
            $message = "<div class='alert alert-success'>Category added.</div>";
        } catch (mysqli_sql_exception $e) {
            $message = "<div class='alert alert-danger'>Category already exists.</div>";
        }
    }
}

$result = mysqli_query($conn, 'SELECT p.id, p.name, p.price, p.url_image, c.name AS category
                               FROM products p JOIN categories c ON c.id = p.category_id
                               WHERE p.is_active = 1 ORDER BY c.name, p.name');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - kifopos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include '../navbar.php'; ?>
<div class="container mt-4">
    <?= $message ?>
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Manage Products</h4>
            <a href="create.php" class="btn btn-success btn-sm">+ Add Product</a>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-dark"><tr><th>No</th><th>Image</th><th>Name</th><th>Category</th><th>Price</th><th>Action</th></tr></thead>
                <tbody>
                <?php $no = 1; while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><img src="<?= h($row['url_image']) ?>" width="80" alt="<?= h($row['name']) ?>"></td>
                        <td><?= h($row['name']) ?></td>
                        <td><?= h($row['category']) ?></td>
                        <td><?= rupiah($row['price']) ?></td>
                        <td class="d-flex gap-1">
                            <a href="edit.php?id=<?= (int) $row['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                            <form method="POST" action="delete.php" onsubmit="return confirm('Delete this product?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                <button class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card shadow-sm" style="max-width:420px">
        <div class="card-header">Add Category</div>
        <div class="card-body">
            <form method="POST" class="d-flex gap-2">
                <?= csrf_field() ?>
                <input class="form-control" name="category_name" placeholder="e.g. Cold Drinks" required>
                <button name="add_category" class="btn btn-dark">Save</button>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
