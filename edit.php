<?php
// edit.php
include 'auth.php';
include 'config.php';

require_admin();

$id = $_GET['id'];

// Load the product being edited (Make sure table name is 'product')
$stmt = mysqli_prepare($conn, "SELECT * FROM product WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$data) {
    die("Product not found.");
}

if (isset($_POST['update'])) {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $url_image = $_POST['url_image'];

    $stmt = mysqli_prepare($conn, "UPDATE product SET name = ?, price = ?, url_image = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "sisi", $name, $price, $url_image, $id);
    mysqli_stmt_execute($stmt);

    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<?php include 'navbar.php'; ?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">

            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">Edit Product Data</h5>
                </div>

                <div class="card-body">
                    <form method="POST" action="">

                        <div class="mb-3">
                            <label for="name" class="form-label">Product Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($data['name']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="price" class="form-label">Price</label>
                            <input type="number" class="form-control" id="price" name="price" value="<?= htmlspecialchars($data['price']) ?>" min="0" required>
                        </div>

                        <div class="mb-4">
                            <label for="url_image" class="form-label">Image URL</label>
                            <input type="text" class="form-control" id="url_image" name="url_image" value="<?= htmlspecialchars($data['url_image']) ?>" required>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="index.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" name="update" class="btn btn-warning">Update Data</button>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>