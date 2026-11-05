<?php
// register.php — public registration, always creates a customer
include 'auth.php';
include 'config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = "";
$success = "";

if (isset($_POST['register'])) {
    csrf_check();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = "Username and password are required.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        $check = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
        mysqli_stmt_bind_param($check, "s", $username);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {
            $error = "Username is already taken.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            // Role is fixed to customer (looked up from the roles table)
            $stmt = mysqli_prepare($conn, "INSERT INTO users (username, password, role_id)
                                           SELECT ?, ?, id FROM roles WHERE name = 'customer'");
            mysqli_stmt_bind_param($stmt, "ss", $username, $hash);
            mysqli_stmt_execute($stmt);
            $success = "Registration successful! You can now <a href='login.php'>log in</a>.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - kifopos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container">
    <div class="row justify-content-center" style="margin-top: 12vh;">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white text-center"><h5 class="mb-0">Customer Registration</h5></div>
                <div class="card-body">
                    <?php if ($error): ?><div class="alert alert-danger py-2"><?= h($error) ?></div><?php endif; ?>
                    <?php if ($success): ?><div class="alert alert-success py-2"><?= $success ?></div><?php endif; ?>
                    <form method="POST" action="">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required autofocus>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" minlength="6" required>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" name="register" class="btn btn-success">Register</button>
                            <a href="index.php" class="btn btn-outline-secondary">Back to Home</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
