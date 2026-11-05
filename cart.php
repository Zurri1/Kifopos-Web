<?php
// cart.php — guests use the session cart, logged-in customers use the carts table
include 'auth.php';
include 'config.php';
include 'cart_lib.php';

if (is_staff()) {
    http_response_code(403);
    exit('The cart is for buyers only.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $id = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$id || !in_array($action, ['add', 'update', 'remove'], true)) {
        http_response_code(400);
        exit('Invalid product or action.');
    }
    if ($action === 'remove') {
        cart_remove($conn, $id);
    } else {
        $stmt = mysqli_prepare($conn, 'SELECT id FROM products WHERE id = ? AND is_active = 1');
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        if (!mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
            http_response_code(404);
            exit('Product not found.');
        }
        $current = cart_load($conn);
        if ($action === 'add') {
            cart_set($conn, $id, ($current[$id] ?? 0) + 1);
        } else {
            $quantity = filter_var($_POST['quantity'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 999]]);
            if (!$quantity || !isset($current[$id])) {
                http_response_code(400);
                exit('Quantity must be 1 to 999 and the product must be in the cart.');
            }
            cart_set($conn, $id, $quantity);
        }
    }
    header('Location: cart.php');
    exit;
}

$cart = cart_detail($conn);
$items = $cart['items'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart - kifopos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include 'navbar.php'; ?>
<div class="container mt-5">
    <div class="card shadow-sm">
        <div class="card-header"><h4 class="mb-0">Your Cart</h4></div>
        <div class="card-body">
            <?php if (!$items): ?>
                <p>Your cart is empty.</p>
            <?php else: ?>
                <p><?= is_customer() ? 'Your 10% customer discount has been applied.' : 'Regular prices. Log in as a customer to get 10% off.' ?></p>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead><tr><th>Product</th><th>Regular Price</th><th>Price / Unit</th><th>Quantity</th><th>Subtotal</th><th>Action</th></tr></thead>
                        <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?= h($item['name']) ?></td>
                                <td><?= rupiah($item['price']) ?></td>
                                <td><?= rupiah($item['unit_price']) ?></td>
                                <td>
                                    <form method="POST" action="cart.php" class="d-flex gap-2">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="product_id" value="<?= (int) $item['id'] ?>">
                                        <input type="number" name="quantity" value="<?= (int) $item['quantity'] ?>" min="1" max="999" required class="form-control" style="width:90px" aria-label="Quantity of <?= h($item['name']) ?>">
                                        <button name="action" value="update" class="btn btn-primary btn-sm">Update</button>
                                    </form>
                                </td>
                                <td><?= rupiah($item['line_total']) ?></td>
                                <td>
                                    <form method="POST" action="cart.php">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="product_id" value="<?= (int) $item['id'] ?>">
                                        <button name="action" value="remove" class="btn btn-danger btn-sm">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="text-end">
                    <p>Subtotal (regular price): <?= rupiah($cart['subtotal']) ?></p>
                    <p>Discount<?= is_customer() ? ' 10%' : '' ?>: <?= rupiah($cart['discount']) ?></p>
                    <h5>Total: <?= rupiah($cart['total']) ?></h5>
                    <a href="checkout.php" class="btn btn-success">Checkout</a>
                </div>
            <?php endif; ?>
            <a href="index.php" class="btn btn-secondary mt-2">Continue Shopping</a>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
