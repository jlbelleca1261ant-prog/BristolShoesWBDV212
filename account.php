<?php
declare(strict_types=1);
require __DIR__ . '/includes/common.php';
$user = current_user();
if (!$user) {
    header('Location: login.php');
    exit;
}
$query = db()->prepare('SELECT reference, total, status, payment_method, created_at FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT 20');
$query->execute([(int)$user['id']]);
$orders = $query->fetchAll();
$pageTitle = 'Your account';
require __DIR__ . '/includes/account_header.php';
?>
<section class="account-card">
    <p class="eyebrow">YOUR ACCOUNT</p>
    <div class="account-heading"><div><h1>Hi, <?= e($user['name']) ?></h1><p class="account-intro">Your orders and saved cart belong to this account.</p></div>
    <form method="post" action="logout.php"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><button class="text-button">Sign out</button></form></div>
    <div class="account-actions">
        <a class="account-button" href="products.php">Continue shopping</a>
        <a class="outline-button" href="profile.php">Profile &amp; settings</a>
        <?php if ($user['role'] === 'admin'): ?><a class="outline-button" href="admin.php">Admin dashboard</a><?php endif; ?>
        <?php if ($user['role'] === 'staff' || $user['role'] === 'admin'): ?><a class="outline-button" href="staff.php">Staff workspace</a><?php endif; ?>
    </div>
</section>
<section class="account-card">
    <h2>Recent orders</h2>
    <?php if (!$orders): ?>
    <p class="account-intro">No orders yet. Your first pair is waiting.</p>
    <?php else: ?>
    <div class="order-list">
        <?php foreach ($orders as $order): ?>
        <div class="order-list-row"><div><strong>#<?= e($order['reference']) ?></strong><small><?= e(date('M j, Y', strtotime($order['created_at']))) ?> · <?= e(ucfirst($order['status'])) ?></small></div><strong>₱<?= number_format((int)$order['total']) ?></strong></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/account_footer.php'; ?>
