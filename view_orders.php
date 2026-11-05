<?php
// view_orders.php
// - cashier: only orders assigned to them, can validate/reject
// - admin (owner): all orders + sales charts + Excel download
include 'auth.php';
include 'config.php';
require_role(['admin', 'cashier']);

$flash = '';

// Cashier validates or rejects one of THEIR pending orders
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_role(['cashier']);
    csrf_check();
    $oid = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $action = $_POST['action'] ?? '';
    if (!$oid || !in_array($action, ['validate', 'reject'], true)) {
        http_response_code(400);
        exit('Invalid request.');
    }
    $status = $action === 'validate' ? 'validated' : 'rejected';
    $stmt = mysqli_prepare($conn, "UPDATE orders SET status = ?, validated_at = NOW()
                                   WHERE id = ? AND cashier_id = ? AND status = 'pending'");
    mysqli_stmt_bind_param($stmt, 'sii', $status, $oid, $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $flash = mysqli_stmt_affected_rows($stmt) > 0 ? "Order #$oid updated." : 'Order not found or already processed.';
}

$sql = 'SELECT o.id, o.customer_name, o.status, o.subtotal, o.discount, o.total, o.created_at, c.username AS cashier_name
        FROM orders o LEFT JOIN users c ON c.id = o.cashier_id';
if (is_cashier()) {
    $sql .= ' WHERE o.cashier_id = ? ORDER BY o.id DESC';
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $_SESSION['user_id']);
} else {
    $stmt = mysqli_prepare($conn, $sql . ' ORDER BY o.id DESC');
}
mysqli_stmt_execute($stmt);
$orders = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);

$item_stmt = mysqli_prepare($conn, 'SELECT p.name, d.quantity FROM order_details d JOIN products p ON p.id = d.product_id WHERE d.order_id = ?');
$badge = ['pending' => 'warning', 'validated' => 'success', 'rejected' => 'danger'];

// Owner-only chart data (validated orders only)
$daily = [];
$top = [];
if (is_admin()) {
    $r = mysqli_query($conn, "SELECT DATE(created_at) AS d, SUM(total) AS t FROM orders
                              WHERE status = 'validated' AND created_at >= CURDATE() - INTERVAL 29 DAY
                              GROUP BY DATE(created_at) ORDER BY d");
    $daily = mysqli_fetch_all($r, MYSQLI_ASSOC);
    $r = mysqli_query($conn, "SELECT p.name, SUM(d.quantity) AS qty FROM order_details d
                              JOIN orders o ON o.id = d.order_id AND o.status = 'validated'
                              JOIN products p ON p.id = d.product_id
                              GROUP BY p.id, p.name ORDER BY qty DESC LIMIT 5");
    $top = mysqli_fetch_all($r, MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= is_admin() ? 'All Orders' : 'My Orders' ?> - kifopos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include 'navbar.php'; ?>
<div class="container mt-4">
    <?php if ($flash): ?><div class="alert alert-info"><?= h($flash) ?></div><?php endif; ?>

    <?php if (is_admin()): ?>
        <div class="row g-4 mb-4">
            <div class="col-lg-7">
                <div class="card shadow-sm"><div class="card-header">Sales, last 30 days (validated orders)</div>
                    <div class="card-body"><canvas id="dailyChart" height="130"></canvas></div></div>
            </div>
            <div class="col-lg-5">
                <div class="card shadow-sm"><div class="card-header">Top 5 products</div>
                    <div class="card-body"><canvas id="topChart" height="190"></canvas></div></div>
            </div>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><?= is_admin() ? 'All Orders' : 'Orders Assigned to Me' ?></h5>
            <?php if (is_admin()): ?>
                <a href="excel_generate.php" class="btn btn-success btn-sm">Download Excel</a>
            <?php endif; ?>
        </div>
        <div class="card-body table-responsive">
            <?php if (!$orders): ?><p>No orders yet.</p><?php else: ?>
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-dark">
                    <tr><th>#</th><th>Time</th><th>Customer</th><th>Items</th><th>Total</th>
                        <?php if (is_admin()): ?><th>Cashier</th><?php endif; ?>
                        <th>Status</th><?php if (is_cashier()): ?><th>Action</th><?php endif; ?></tr>
                </thead>
                <tbody>
                <?php foreach ($orders as $o):
                    mysqli_stmt_bind_param($item_stmt, 'i', $o['id']);
                    mysqli_stmt_execute($item_stmt);
                    $items = mysqli_fetch_all(mysqli_stmt_get_result($item_stmt), MYSQLI_ASSOC); ?>
                    <tr>
                        <td><?= (int) $o['id'] ?></td>
                        <td><?= h($o['created_at']) ?></td>
                        <td><?= h($o['customer_name']) ?></td>
                        <td><?php foreach ($items as $i): ?><?= h($i['name']) ?> &times;<?= (int) $i['quantity'] ?><br><?php endforeach; ?></td>
                        <td><?= rupiah($o['total']) ?></td>
                        <?php if (is_admin()): ?><td><?= h($o['cashier_name'] ?? '-') ?></td><?php endif; ?>
                        <td><span class="badge bg-<?= $badge[$o['status']] ?>"><?= h($o['status']) ?></span></td>
                        <?php if (is_cashier()): ?>
                        <td>
                            <?php if ($o['status'] === 'pending'): ?>
                                <form method="POST" class="d-flex gap-1">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="order_id" value="<?= (int) $o['id'] ?>">
                                    <button name="action" value="validate" class="btn btn-success btn-sm">Validate</button>
                                    <button name="action" value="reject" class="btn btn-outline-danger btn-sm" onclick="return confirm('Reject this order?')">Reject</button>
                                </form>
                            <?php else: ?>-<?php endif; ?>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (is_admin()): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
const daily = <?= json_encode($daily, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
const top = <?= json_encode($top, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
new Chart(document.getElementById('dailyChart'), {
    type: 'line',
    data: { labels: daily.map(r => r.d), datasets: [{ label: 'Sales (Rp)', data: daily.map(r => Number(r.t)), borderColor: '#198754', tension: 0.2 }] }
});
new Chart(document.getElementById('topChart'), {
    type: 'bar',
    data: { labels: top.map(r => r.name), datasets: [{ label: 'Units sold', data: top.map(r => Number(r.qty)), backgroundColor: '#0d6efd' }] },
    options: { indexAxis: 'y' }
});
</script>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
