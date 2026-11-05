<?php
// admin/_product_lib.php — shared validation for create.php and edit.php

// Returns the cleaned product fields, or null and sets $error.
function product_input(string &$error): ?array {
    $name  = trim($_POST['name'] ?? '');
    $cat   = filter_var($_POST['category_id'] ?? null, FILTER_VALIDATE_INT);
    $desc  = trim($_POST['description'] ?? '');
    $price = filter_var($_POST['price'] ?? null, FILTER_VALIDATE_FLOAT);
    $image = trim($_POST['url_image'] ?? '');

    if ($name === '' || !$cat || $price === false || $price < 0) {
        $error = 'Invalid product data.';
        return null;
    }
    // The image is always an external link (http/https)
    if (!preg_match('#^https?://#i', $image) || !filter_var($image, FILTER_VALIDATE_URL)) {
        $error = 'Please enter a valid image link starting with http:// or https://';
        return null;
    }

    return ['name' => $name, 'category_id' => $cat, 'description' => $desc,
            'price' => (float) $price, 'url_image' => $image];
}
?>
