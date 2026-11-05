<?php
// admin/_form.php — shared product form. Expects: $form_title, $data, $categories, $error, $header_class
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($form_title) ?> - kifopos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include '../navbar.php'; ?>
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header <?= h($header_class) ?>"><h5 class="mb-0"><?= h($form_title) ?></h5></div>
                <div class="card-body">
                    <?php if ($error): ?><div class="alert alert-danger py-2"><?= h($error) ?></div><?php endif; ?>
                    <form method="POST" action="">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label for="name" class="form-label">Product Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?= h($data['name']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="category_id" class="form-label">Category</label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?= (int) $c['id'] ?>" <?= (int) $data['category_id'] === (int) $c['id'] ? 'selected' : '' ?>><?= h($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?= h($data['description']) ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="price" class="form-label">Price (Rp)</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="price" name="price" value="<?= h($data['price']) ?>" required>
                        </div>
                        <div class="mb-4">
                            <label for="url_image" class="form-label">Image URL</label>
                            <input type="url" class="form-control" id="url_image" name="url_image" value="<?= h($data['url_image']) ?>" placeholder="https://example.com/image.jpg" required>
                        </div>
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="index.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" name="save" class="btn btn-dark">Save Product</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
