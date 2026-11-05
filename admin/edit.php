<?php
// admin/edit.php — edit a product (change the image by editing the "Image URL" field)
include '../auth.php';
include '../config.php';
include '_product_lib.php';
require_admin();

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) { http_response_code(400); die('Invalid product.'); }

$stmt = mysqli_prepare($conn, 'SELECT * FROM products WHERE id = ? AND is_active = 1');
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$data) { http_response_code(404); die('Product not found.'); }

$error = '';
$categories = mysqli_fetch_all(mysqli_query($conn, 'SELECT id, name FROM categories ORDER BY name'), MYSQLI_ASSOC);

if (isset($_POST['save'])) {
    csrf_check();
    $in = product_input($error);
    if ($in) {
        $stmt = mysqli_prepare($conn, 'UPDATE products SET category_id = ?, name = ?, description = ?, price = ?, url_image = ? WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'issdsi', $in['category_id'], $in['name'], $in['description'], $in['price'], $in['url_image'], $id);
        mysqli_stmt_execute($stmt);
        header('Location: index.php');
        exit;
    }
    $data = array_merge($data, array_intersect_key($_POST, $data));
}

$form_title = 'Edit Product';
$header_class = 'bg-warning text-dark';
include '_form.php';
