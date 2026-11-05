<?php
// admin/delete.php — soft delete (is_active = 0) so old orders keep their product names
include '../auth.php';
include '../config.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id) {
        $stmt = mysqli_prepare($conn, 'UPDATE products SET is_active = 0 WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        // Remove it from customers' saved carts as well
        $stmt = mysqli_prepare($conn, 'DELETE FROM carts WHERE product_id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
    }
}

header('Location: index.php');
exit;
