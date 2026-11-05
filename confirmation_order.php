<?php
// confirmation_order.php — order summary shown after checkout
include 'auth.php';
include 'config.php';

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) { http_response_code(400); exit('Invalid order.'); }

$stmt = mysqli_prepare($conn, 'SELECT o.*, c.username AS cashier_name FROM orders o
                                LEFT JOIN users c ON c.id = o.cashier_id WHERE o.id = ?');
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Access: owner, the assigned cashier, the customer who placed it, or the guest session that created it
$allowed = $order && (
    is_admin() ||
    (is_cashier() && (int) $order['cashier_id'] === (int) $_SESSION['user_id']) ||
    (is_customer() && (int) $order['user_id'] === (int) $_SESSION['user_id']) ||
    in_array($id, $_SESSION['my_orders'] ?? [], true)
);
if (!$allowed) { http_response_code(404); exit('Order not found.'); }

$stmt = mysqli_prepare($conn, 'SELECT p.name, d.quantity, d.unit_price FROM order_details d
                                JOIN products p ON p.id = d.product_id WHERE d.order_id = ?');
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$lines = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);

$badge = ['pending' => 'warning', 'validated' => 'success', 'rejected' => 'danger'][$order['status']];
$label = ['pending' => 'Waiting for cashier validation', 'validated' => 'Validated', 'rejected' => 'Rejected'][$order['status']];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - kifopos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include 'navbar.php'; ?>
<div class="container mt-5" style="max-width:720px">
    <div class="card shadow-sm">
        <div class="card-header"><h4 class="mb-0">Order #<?= (int) $order['id'] ?></h4></div>
        <div class="card-body">
            <p class="mb-1">Name: <strong><?= h($order['customer_name']) ?></strong></p>
            <p class="mb-1">Time: <?= h($order['created_at']) ?></p>
            <p>Status: <span class="badge bg-<?= $badge ?>"><?= $label ?></span></p>
            <table class="table table-bordered">
                <thead><tr><th>Product</th><th>Price / Unit</th><th>Quantity</th><th>Subtotal</th></tr></thead>
                <tbody>
                <?php foreach ($lines as $l): ?>
                    <tr>
                        <td><?= h($l['name']) ?></td>
                        <td><?= rupiah($l['unit_price']) ?></td>
                        <td><?= (int) $l['quantity'] ?></td>
                        <td><?= rupiah($l['unit_price'] * $l['quantity']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div class="text-end">
                <p class="mb-1">Subtotal: <?= rupiah($order['subtotal']) ?></p>
                <p class="mb-1">Discount: <?= rupiah($order['discount']) ?></p>
                <h5>Total: <?= rupiah($order['total']) ?></h5>
            </div>
            <?php if ($order['status'] === 'pending'): ?>
                <div class="alert alert-info mt-3 mb-0">Please show this order number to the cashier for payment and validation.</div>
            <?php endif; ?>
            <a href="index.php" class="btn btn-secondary mt-3">Back to Home</a>
        </div>
    </div>
</div>
</body>
</html>
