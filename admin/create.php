<?php
// admin/create.php — add a product (image is entered as a link in the "Image URL" field)
include '../auth.php';
include '../config.php';
include '_product_lib.php';
require_admin();

$error = '';
$data = ['name' => '', 'category_id' => 0, 'description' => '', 'price' => '', 'url_image' => ''];
$categories = mysqli_fetch_all(mysqli_query($conn, 'SELECT id, name FROM categories ORDER BY name'), MYSQLI_ASSOC);

if (isset($_POST['save'])) {
    csrf_check();
    $in = product_input($error);
    if ($in) {
        $stmt = mysqli_prepare($conn, 'INSERT INTO products (category_id, name, description, price, url_image) VALUES (?, ?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'issds', $in['category_id'], $in['name'], $in['description'], $in['price'], $in['url_image']);
        mysqli_stmt_execute($stmt);
        header('Location: index.php');
        exit;
    }
    $data = array_merge($data, array_intersect_key($_POST, $data)); // keep what the admin typed
}

$form_title = 'Add New Product';
$header_class = 'bg-success text-white';
include '_form.php';
