<?php
// cart_lib.php — cart storage: session for guests, "carts" table for logged-in customers

function cart_load(mysqli $conn): array {
    if (is_customer()) {
        $stmt = mysqli_prepare($conn, 'SELECT product_id, quantity FROM carts WHERE user_id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $_SESSION['user_id']);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $out = [];
        while ($r = mysqli_fetch_assoc($res)) $out[(int) $r['product_id']] = (int) $r['quantity'];
        return $out;
    }
    return $_SESSION['cart'] ?? [];
}

function cart_set(mysqli $conn, int $pid, int $qty): void {
    $qty = max(1, min(999, $qty));
    if (is_customer()) {
        $stmt = mysqli_prepare($conn, 'INSERT INTO carts (user_id, product_id, quantity) VALUES (?, ?, ?)
                                       ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)');
        mysqli_stmt_bind_param($stmt, 'iii', $_SESSION['user_id'], $pid, $qty);
        mysqli_stmt_execute($stmt);
    } else {
        $_SESSION['cart'][$pid] = $qty;
    }
}

function cart_remove(mysqli $conn, int $pid): void {
    if (is_customer()) {
        $stmt = mysqli_prepare($conn, 'DELETE FROM carts WHERE user_id = ? AND product_id = ?');
        mysqli_stmt_bind_param($stmt, 'ii', $_SESSION['user_id'], $pid);
        mysqli_stmt_execute($stmt);
    } else {
        unset($_SESSION['cart'][$pid]);
    }
}

function cart_clear(mysqli $conn): void {
    if (is_customer()) {
        $stmt = mysqli_prepare($conn, 'DELETE FROM carts WHERE user_id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $_SESSION['user_id']);
        mysqli_stmt_execute($stmt);
    } else {
        $_SESSION['cart'] = [];
    }
}

// Called right after login: move the guest cart into the customer's DB cart
function cart_merge_session(mysqli $conn, int $user_id): void {
    foreach (($_SESSION['cart'] ?? []) as $pid => $qty) {
        $pid = (int) $pid; $qty = (int) $qty;
        $stmt = mysqli_prepare($conn, 'INSERT INTO carts (user_id, product_id, quantity) VALUES (?, ?, ?)
                                       ON DUPLICATE KEY UPDATE quantity = LEAST(999, quantity + VALUES(quantity))');
        mysqli_stmt_bind_param($stmt, 'iii', $user_id, $pid, $qty);
        mysqli_stmt_execute($stmt);
    }
    $_SESSION['cart'] = [];
}

// Build cart lines with prices ALWAYS read from the database
function cart_detail(mysqli $conn): array {
    $items = [];
    $subtotal = 0.0;
    $total = 0.0;
    $stmt = mysqli_prepare($conn, 'SELECT id, name, price FROM products WHERE id = ? AND is_active = 1');
    foreach (cart_load($conn) as $pid => $qty) {
        mysqli_stmt_bind_param($stmt, 'i', $pid);
        mysqli_stmt_execute($stmt);
        $p = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if (!$p) { cart_remove($conn, (int) $pid); continue; }
        $p['quantity']   = (int) $qty;
        $p['unit_price'] = price_for($p['price']);
        $p['line_total'] = round($p['unit_price'] * $qty, 2);
        $subtotal += round($p['price'] * $qty, 2);
        $total    += $p['line_total'];
        $items[] = $p;
    }
    $subtotal = round($subtotal, 2);
    $total    = round($total, 2);
    return ['items' => $items, 'subtotal' => $subtotal, 'total' => $total, 'discount' => round($subtotal - $total, 2)];
}
?>
