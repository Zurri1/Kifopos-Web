<?php
// order_history.php — a customer's own past orders
include 'auth.php';
include 'config.php';
require_role(['customer']);

$stmt = mysqli_prepare($conn, 'SELECT id, created_at, status, total FROM orders WHERE user_id = ? ORDER BY id DESC');
mysqli_stmt_bind_param($stmt, 'i', $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$orders = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
$badge = ['pending' => 'warning', 'validated' => 'success', 'rejected' => 'danger'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History - kifopos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include 'navbar.php'; ?>
<div class="container mt-5">
    <div class="card shadow-sm">
        <div class="card-header"><h4 class="mb-0">Order History</h4></div>
        <div class="card-body">
            <?php if (!$orders): ?>
                <p>You have no orders yet.</p>
            <?php else: ?>
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-dark"><tr><th>Order No.</th><th>Time</th><th>Status</th><th>Total</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td>#<?= (int) $o['id'] ?></td>
                            <td><?= h($o['created_at']) ?></td>
                            <td><span class="badge bg-<?= $badge[$o['status']] ?>"><?= h($o['status']) ?></span></td>
                            <td><?= rupiah($o['total']) ?></td>
                            <td><a class="btn btn-sm btn-outline-dark" href="confirmation_order.php?id=<?= (int) $o['id'] ?>">Details</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
