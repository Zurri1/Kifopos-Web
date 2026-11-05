<?php
// checkout.php — creates the order and assigns a cashier
include 'auth.php';
include 'config.php';
include 'cart_lib.php';

if (is_staff()) {
    http_response_code(403);
    exit('Checkout is for buyers only.');
}

$cart = cart_detail($conn);
if (!$cart['items']) {
    header('Location: cart.php');
    exit;
}

$error = '';

if (isset($_POST['place_order'])) {
    csrf_check();

    if (is_customer()) {
        $name = $_SESSION['username'];
        $user_id = $_SESSION['user_id'];
    } else {
        $name = trim($_POST['customer_name'] ?? '');
        $user_id = null;
        if ($name === '') $error = 'Name is required.';
    }

    if (!$error) {
        mysqli_begin_transaction($conn);
        try {
            // Assign the cashier with the fewest pending orders (NULL if no cashier exists)
            $res = mysqli_query($conn, "SELECT u.id FROM users u JOIN roles r ON r.id = u.role_id AND r.name = 'cashier'
                                        LEFT JOIN orders o ON o.cashier_id = u.id AND o.status = 'pending'
                                        GROUP BY u.id ORDER BY COUNT(o.id) ASC, u.id ASC LIMIT 1");
            $c = mysqli_fetch_assoc($res);
            $cashier_id = $c ? (int) $c['id'] : null;

            $stmt = mysqli_prepare($conn, 'INSERT INTO orders (user_id, customer_name, cashier_id, subtotal, discount, total)
                                           VALUES (?, ?, ?, ?, ?, ?)');
            mysqli_stmt_bind_param($stmt, 'isiddd', $user_id, $name, $cashier_id,
                $cart['subtotal'], $cart['discount'], $cart['total']);
            mysqli_stmt_execute($stmt);
            $order_id = mysqli_insert_id($conn);

            $d = mysqli_prepare($conn, 'INSERT INTO order_details (order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)');
            foreach ($cart['items'] as $it) {
                mysqli_stmt_bind_param($d, 'iiid', $order_id, $it['id'], $it['quantity'], $it['unit_price']);
                mysqli_stmt_execute($d);
            }
            mysqli_commit($conn);
        } catch (mysqli_sql_exception $e) {
            mysqli_rollback($conn);
            http_response_code(500);
            exit('Could not create the order. Please try again.');
        }

        cart_clear($conn);
        $_SESSION['my_orders'][] = $order_id; // lets guests open their confirmation page
        header("Location: confirmation_order.php?id=$order_id");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - kifopos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include 'navbar.php'; ?>
<div class="container mt-5">
    <div class="card shadow-sm">
        <div class="card-header"><h4 class="mb-0">Checkout</h4></div>
        <div class="card-body">
            <?php if ($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
            <table class="table table-bordered align-middle">
                <thead><tr><th>Product</th><th>Price / Unit</th><th>Quantity</th><th>Subtotal</th></tr></thead>
                <tbody>
                <?php foreach ($cart['items'] as $it): ?>
                    <tr>
                        <td><?= h($it['name']) ?></td>
                        <td><?= rupiah($it['unit_price']) ?></td>
                        <td><?= (int) $it['quantity'] ?></td>
                        <td><?= rupiah($it['line_total']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div class="text-end mb-4">
                <p class="mb-1">Subtotal (regular price): <?= rupiah($cart['subtotal']) ?></p>
                <p class="mb-1">Discount<?= is_customer() ? ' 10%' : '' ?>: <?= rupiah($cart['discount']) ?></p>
                <h5>Total: <?= rupiah($cart['total']) ?></h5>
            </div>

            <form method="POST" action="">
                <?= csrf_field() ?>
                <?php if (is_customer()): ?>
                    <p>Ordering as <strong><?= h($_SESSION['username']) ?></strong>.</p>
                <?php else: ?>
                    <div class="alert alert-info py-2">Ordering as a guest (regular prices). <a href="login.php">Log in</a> to get 10% off.</div>
                    <div class="mb-3">
                        <label class="form-label" for="customer_name">Name</label>
                        <input class="form-control" id="customer_name" name="customer_name" required value="<?= h($_POST['customer_name'] ?? '') ?>">
                    </div>
                <?php endif; ?>
                <a href="cart.php" class="btn btn-secondary">Back</a>
                <button type="submit" name="place_order" class="btn btn-success">Place Order</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
