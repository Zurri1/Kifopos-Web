<?php
// auth.php — include on the FIRST line of every page
session_start();

// Path prefix so shared files (navbar, images) work from the /admin folder too
$base = (basename(dirname($_SERVER['SCRIPT_NAME'])) === 'admin') ? '../' : '';

function current_role() {
    return $_SESSION['role'] ?? 'guest';   // guest | customer | cashier | admin
}
function is_admin()    { return current_role() === 'admin'; }      // admin = owner
function is_cashier()  { return current_role() === 'cashier'; }
function is_staff()    { return is_admin() || is_cashier(); }
function is_customer() { return isset($_SESSION['user_id']) && current_role() === 'customer'; }

function require_role(array $roles) {
    global $base;
    if (!in_array(current_role(), $roles, true)) {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . $base . 'login.php');
            exit;
        }
        http_response_code(403);
        die('403 - Access denied.');
    }
}
function require_admin() { require_role(['admin']); }

// CSRF helpers
function csrf_token() {
    $_SESSION['csrf'] = $_SESSION['csrf'] ?? bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field() {
    return '<input type="hidden" name="token" value="' . htmlspecialchars(csrf_token()) . '">';
}
function csrf_check() {
    $t = $_POST['token'] ?? '';
    if (!is_string($t) || !hash_equals(csrf_token(), $t)) {
        http_response_code(403);
        exit('Invalid request. Reload the page and try again.');
    }
}

function h($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }

function rupiah($amount) {
    return 'Rp ' . number_format((float) $amount, 2, ',', '.');
}

// Logged-in customers get 10% off; guests pay the normal price
function price_for($price) {
    return is_customer() ? round($price * 0.9, 2) : round((float) $price, 2);
}

?>
