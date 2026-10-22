<?php
require __DIR__ . '/includes/common.php';

$user = current_user();
if (!$user || !in_array($user['role'], ['staff', 'admin'], true)) {
    http_response_code(403);
    echo 'Staff access required.';
    exit;
}

$error = '';
$notice = isset($_GET['saved']) ? 'Order status saved.' : '';
$allowedStatuses = ['pending', 'processing', 'shipped', 'completed', 'cancelled'];

// Staff can update order progress, but cannot change the payment status.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $orderId = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT);
    $status = $_POST['status'] ?? '';

    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        $error = 'Your session expired. Refresh and try again.';
    } elseif (!$orderId || !in_array($status, $allowedStatuses, true)) {
        $error = 'Choose a valid order and status.';
    } else {
        $save = db()->prepare('UPDATE orders SET status = ? WHERE id = ?');
        $save->execute([$status, $orderId]);
        header('Location: staff.php?saved=1');
        exit;
    }
}

function valid_report_date(string $date): bool
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return false;
    }
    $parts = explode('-', $date);
    return checkdate((int)$parts[1], (int)$parts[2], (int)$parts[0]);
}

$from = (string)($_GET['from'] ?? '');
$to = (string)($_GET['to'] ?? '');
if (($from !== '' && !valid_report_date($from)) || ($to !== '' && !valid_report_date($to))) {
    $error = 'Choose valid report dates.';
    $from = '';
    $to = '';
}
if ($from !== '' && $to !== '' && $from > $to) {
    $error = 'The start date must be before the end date.';
    $from = '';
    $to = '';
}

// Blank dates mean "all dates". Both report queries use the same range.
$startDate = $from === '' ? '1900-01-01' : $from;
$endDate = $to === '' ? '9999-12-31' : $to;

$report = db()->prepare('SELECT status, COUNT(*) AS order_count, COALESCE(SUM(total), 0) AS order_value
    FROM orders WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY status');
$report->execute([$startDate, $endDate]);

$counts = ['pending' => 0, 'processing' => 0, 'shipped' => 0, 'completed' => 0, 'cancelled' => 0];
$orderCount = 0;
$orderValue = 0;
foreach ($report->fetchAll() as $row) {
    $counts[$row['status']] = (int)$row['order_count'];
    $orderCount += (int)$row['order_count'];
    $orderValue += (int)$row['order_value'];
}

$list = db()->prepare('SELECT id, reference, first_name, last_name, email, total,
    payment_method, payment_status, status, created_at
    FROM orders WHERE DATE(created_at) BETWEEN ? AND ? ORDER BY id DESC LIMIT 100');
$list->execute([$startDate, $endDate]);
$orders = $list->fetchAll();

// Show what was purchased so staff can work from the order record.
$itemsByOrder = [];
$findItems = db()->prepare('SELECT product_name, size, quantity FROM order_items WHERE order_id = ?');
foreach ($orders as $order) {
    $findItems->execute([$order['id']]);
    $itemsByOrder[$order['id']] = $findItems->fetchAll();
}

$pageTitle = 'Staff workspace';
require __DIR__ . '/includes/account_header.php';
?>
<section class="account-card">
    <p class="eyebrow">STAFF WORKSPACE</p>
    <div class="account-heading"><div><h1>Orders &amp; reports</h1><p class="account-intro">Monitor orders, update their progress, and review totals by date.</p></div><a class="outline-button" href="account.php">My account</a></div>
    <?php if ($error): ?><p class="form-alert" role="alert"><?= e($error) ?></p><?php endif; ?>
    <?php if ($notice): ?><p class="form-success-note" role="status"><?= e($notice) ?></p><?php endif; ?>

    <form method="get" class="report-filter">
        <label>From<input type="date" name="from" value="<?= e($from) ?>"></label>
        <label>To<input type="date" name="to" value="<?= e($to) ?>"></label>
        <button class="account-button" type="submit">Show report</button>
        <a class="outline-button" href="staff.php">All dates</a>
    </form>
</section>

<section class="account-card">
    <h2>Monitoring</h2>
    <div class="staff-stats">
        <div><strong><?= $orderCount ?></strong><span>Total orders</span></div>
        <div><strong><?= $counts['pending'] ?></strong><span>Pending</span></div>
        <div><strong><?= $counts['processing'] ?></strong><span>Processing</span></div>
        <div><strong><?= $counts['shipped'] ?></strong><span>Shipped</span></div>
        <div><strong><?= $counts['completed'] ?></strong><span>Completed</span></div>
        <div><strong><?= $counts['cancelled'] ?></strong><span>Cancelled</span></div>
    </div>
    <p class="report-total">Total order value: <strong>&#8369;<?= number_format($orderValue) ?></strong></p>
    <p class="account-intro">This is order value, not money collected. Payment processing is not connected.</p>
</section>

<section class="account-card">
    <h2>Order records</h2>
    <p class="account-intro">Showing the latest 100 orders for the selected dates.</p>
    <?php if (!$orders): ?><p>No orders found for these dates.</p><?php endif; ?>
    <?php if ($orders): ?>
    <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Order</th><th>Customer</th><th>Items</th><th>Amount</th><th>Progress</th></tr></thead><tbody>
        <?php foreach ($orders as $order): ?>
        <tr>
            <td><strong>#<?= e($order['reference']) ?></strong><small><?= e(date('M j, Y', strtotime($order['created_at']))) ?></small></td>
            <td><?= e($order['first_name'] . ' ' . $order['last_name']) ?><small><?= e($order['email']) ?></small></td>
            <td><?php foreach ($itemsByOrder[$order['id']] as $item): ?><small><?= e($item['product_name']) ?>, size <?= (int)$item['size'] ?> × <?= (int)$item['quantity'] ?></small><?php endforeach; ?></td>
            <td>&#8369;<?= number_format((int)$order['total']) ?><small><?= e(strtoupper($order['payment_method'])) ?> · <?= e($order['payment_status']) ?></small></td>
            <td><form method="post" class="staff-status-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                <select name="status" aria-label="Status for order <?= e($order['reference']) ?>">
                    <?php foreach ($allowedStatuses as $status): ?><option value="<?= $status ?>" <?= $order['status'] === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option><?php endforeach; ?>
                </select>
                <button type="submit">Save</button>
            </form></td>
        </tr>
        <?php endforeach; ?>
    </tbody></table></div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/account_footer.php'; ?>
