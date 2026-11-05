<?php
// index.php — public home page (guest, customer, and staff can all browse)
include 'auth.php';
include 'config.php';

$cat = filter_var($_GET['category'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
$q   = trim($_GET['q'] ?? '');

$categories = mysqli_query($conn, 'SELECT id, name FROM categories ORDER BY name');

$sql = 'SELECT p.id, p.name, p.description, p.price, p.url_image, c.name AS category
        FROM products p JOIN categories c ON c.id = p.category_id
        WHERE p.is_active = 1';
$types = '';
$params = [];
if ($cat > 0) { $sql .= ' AND p.category_id = ?'; $types .= 'i'; $params[] = $cat; }
if ($q !== '') {
    $sql .= ' AND (p.name LIKE ? OR p.description LIKE ?)';
    $types .= 'ss';
    $like = '%' . $q . '%';
    $params[] = $like; $params[] = $like;
}
$sql .= ' ORDER BY c.name, p.name';
$stmt = mysqli_prepare($conn, $sql);
if ($params) mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$products = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
$can_order = !is_staff();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu - kifopos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include 'navbar.php'; ?>
<div class="container mt-4">
    <div class="p-4 mb-4 bg-white rounded shadow-sm">
        <h2 class="mb-1">Welcome to kifopos</h2>
        <p class="mb-0 text-muted">
            <?php if (is_customer()): ?>
                Hi <?= h($_SESSION['username']) ?>! Your 10% customer discount is applied to every product.
            <?php elseif (is_staff()): ?>
                You are logged in as <?= h($_SESSION['role']) ?>.
            <?php else: ?>
                Coffee, non-caffeinated drinks, and light bites. <a href="register.php">Register</a> or <a href="login.php">log in</a> to get 10% off.
            <?php endif; ?>
        </p>
    </div>

    <form class="row g-2 mb-4" method="GET">
        <div class="col-md-5"><input type="text" name="q" value="<?= h($q) ?>" class="form-control" placeholder="Search products..."></div>
        <div class="col-md-4">
            <select name="category" class="form-select">
                <option value="0">All categories</option>
                <?php while ($c = mysqli_fetch_assoc($categories)): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= $cat === (int) $c['id'] ? 'selected' : '' ?>><?= h($c['name']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-3 d-grid"><button class="btn btn-dark">Filter</button></div>
    </form>

    <?php if (!$products): ?>
        <p>No products found.</p>
    <?php endif; ?>
    <div class="row g-4">
        <?php foreach ($products as $p): ?>
            <div class="col-sm-6 col-lg-4">
                <div class="card h-100 shadow-sm">
                    <img src="<?= h($p['url_image']) ?>" class="card-img-top" style="height:200px;object-fit:cover" alt="<?= h($p['name']) ?>">
                    <div class="card-body d-flex flex-column">
                        <span class="badge bg-secondary align-self-start mb-2"><?= h($p['category']) ?></span>
                        <h5 class="card-title"><?= h($p['name']) ?></h5>
                        <p class="card-text text-muted small"><?= h($p['description']) ?></p>
                        <div class="mt-auto">
                            <?php if (is_customer()): ?>
                                <del class="text-muted"><?= rupiah($p['price']) ?></del>
                                <strong><?= rupiah(price_for($p['price'])) ?></strong>
                                <span class="badge bg-success">10% off</span>
                            <?php else: ?>
                                <strong><?= rupiah($p['price']) ?></strong>
                            <?php endif; ?>
                            <?php if ($can_order): ?>
                                <form method="POST" action="cart.php" class="mt-2">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
                                    <button name="action" value="add" class="btn btn-success btn-sm w-100">Add to Cart</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
