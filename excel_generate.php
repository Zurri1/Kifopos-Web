<?php
// excel_generate.php — owner-only Excel export of all orders
// Outputs an HTML table with an Excel content type, so no external library is needed.
include 'auth.php';
include 'config.php';
require_admin();

$res = mysqli_query($conn, "SELECT o.id, o.created_at, o.customer_name, c.username AS cashier_name, o.status,
        (SELECT GROUP_CONCAT(CONCAT(p.name, ' x', d.quantity) SEPARATOR ', ')
           FROM order_details d JOIN products p ON p.id = d.product_id WHERE d.order_id = o.id) AS items,
        o.subtotal, o.discount, o.total
    FROM orders o LEFT JOIN users c ON c.id = o.cashier_id ORDER BY o.id DESC");

header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="kifopos-orders-' . date('Ymd-His') . '.xls"');
echo "\xEF\xBB\xBF"; // UTF-8 BOM so Excel reads the text correctly
?>
<table border="1">
    <tr><th>Order ID</th><th>Date</th><th>Customer</th><th>Cashier</th><th>Status</th><th>Items</th><th>Subtotal</th><th>Discount</th><th>Total</th></tr>
    <?php while ($r = mysqli_fetch_assoc($res)): ?>
    <tr>
        <td><?= (int) $r['id'] ?></td>
        <td><?= h($r['created_at']) ?></td>
        <td><?= h($r['customer_name']) ?></td>
        <td><?= h($r['cashier_name'] ?? '-') ?></td>
        <td><?= h($r['status']) ?></td>
        <td><?= h($r['items']) ?></td>
        <td><?= (float) $r['subtotal'] ?></td>
        <td><?= (float) $r['discount'] ?></td>
        <td><?= (float) $r['total'] ?></td>
    </tr>
    <?php endwhile; ?>
</table>
