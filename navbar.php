<nav class="navbar navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="<?= $base ?>index.php">kifopos</a>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="<?= $base ?>index.php" class="btn btn-outline-light btn-sm">Home</a>
            <?php if (is_admin()): ?>
                <a href="<?= $base ?>admin/index.php" class="btn btn-outline-light btn-sm">Products</a>
                <a href="<?= $base ?>admin/users.php" class="btn btn-outline-light btn-sm">Users</a>
                <a href="<?= $base ?>view_orders.php" class="btn btn-outline-light btn-sm">All Orders</a>
            <?php elseif (is_cashier()): ?>
                <a href="<?= $base ?>view_orders.php" class="btn btn-outline-light btn-sm">My Orders</a>
            <?php else: ?>
                <a href="<?= $base ?>cart.php" class="btn btn-outline-light btn-sm">Cart</a>
                <?php if (is_customer()): ?>
                    <a href="<?= $base ?>order_history.php" class="btn btn-outline-light btn-sm">Order History</a>
                <?php endif; ?>
            <?php endif; ?>
            <?php if (isset($_SESSION['user_id'])): ?>
                <span class="text-white small">
                    <?= h($_SESSION['username']) ?>
                    <span class="badge bg-secondary"><?= h($_SESSION['role']) ?></span>
                </span>
                <a href="<?= $base ?>logout.php" class="btn btn-danger btn-sm">Logout</a>
            <?php else: ?>
                <a href="<?= $base ?>login.php" class="btn btn-outline-light btn-sm">Login</a>
                <a href="<?= $base ?>register.php" class="btn btn-success btn-sm">Register</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
