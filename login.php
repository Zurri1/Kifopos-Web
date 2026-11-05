<?php
// login.php — one login for customer, cashier, and admin (owner)
include 'auth.php';
include 'config.php';
include 'cart_lib.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = "";

if (isset($_POST['login'])) {
    csrf_check();
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = mysqli_prepare($conn, "SELECT u.id, u.username, u.password, r.name AS role
                                   FROM users u JOIN roles r ON r.id = u.role_id
                                   WHERE u.username = ?");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true); // Prevent session fixation
        $_SESSION['user_id']  = (int) $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role']     = $user['role'];

        // Redirect by role
        if ($user['role'] === 'admin' || $user['role'] === 'cashier') {
            header("Location: view_orders.php");
        } else {
            cart_merge_session($conn, (int) $user['id']); // keep items added as guest
            header("Location: index.php");
        }
        exit;
    }
    $error = "Incorrect username or password.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - kifopos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container">
    <div class="row justify-content-center" style="margin-top: 15vh;">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white text-center"><h5 class="mb-0">kifopos Login</h5></div>
                <div class="card-body">
                    <?php if ($error): ?><div class="alert alert-danger py-2"><?= h($error) ?></div><?php endif; ?>
                    <form method="POST" action="">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required autofocus>
                        </div>
                        <div class="mb-4">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" name="login" class="btn btn-dark">Login</button>
                            <a href="register.php" class="btn btn-outline-dark mt-2">Register</a>
                            <a href="index.php" class="btn btn-link mt-1">Continue as guest</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
