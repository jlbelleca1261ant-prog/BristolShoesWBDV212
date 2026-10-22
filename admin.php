<?php
declare(strict_types=1);
require __DIR__ . '/includes/common.php';

$user = current_user();
if (!$user || $user['role'] !== 'admin') {
    http_response_code(403);
    echo 'Admin access required.';
    exit;
}

$tab = $_GET['tab'] ?? 'orders';
if (!in_array($tab, ['orders', 'products', 'messages', 'staff'], true)) {
    $tab = 'orders';
}
$error = '';
$notice = isset($_GET['saved']) ? 'Changes saved.' : '';

// Turn "39, 40, 41" from the form into [39, 40, 41] for the database.
function read_sizes(string $text): array
{
    $sizes = [];
    foreach (explode(',', $text) as $part) {
        $part = trim($part);
        if ($part === '') {
            continue;
        }
        if (!ctype_digit($part) || (int)$part < 1 || (int)$part > 60) {
            return [];
        }
        $sizes[] = (int)$part;
    }
    return array_values(array_unique($sizes));
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        $error = 'Your session expired. Refresh and try again.';
    } elseif (($_POST['action'] ?? '') === 'order_status') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        $status = $_POST['status'] ?? '';
        if (!$id || !in_array($status, ['pending', 'processing', 'shipped', 'completed', 'cancelled'], true)) {
            $error = 'Invalid order status.';
        } else {
            $query = db()->prepare('UPDATE orders SET status = ? WHERE id = ?');
            $query->execute([$status, $id]);
            header('Location: admin.php?tab=orders&saved=1');
            exit;
        }
    } elseif (($_POST['action'] ?? '') === 'staff_role') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        $role = $_POST['role'] ?? '';
        if (!$id || !in_array($role, ['customer', 'staff'], true)) {
            $error = 'Choose a valid account and role.';
        } else {
            // Only customer/staff accounts can be changed here. Admins stay admins.
            $query = db()->prepare("UPDATE users SET role = ? WHERE id = ? AND role IN ('customer', 'staff')");
            $query->execute([$role, $id]);
            header('Location: admin.php?tab=staff&saved=1');
            exit;
        }
        $tab = 'staff';
    } elseif (($_POST['action'] ?? '') === 'save_product') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT) ?: null;
        $name = trim((string)($_POST['name'] ?? ''));
        $type = trim((string)($_POST['type'] ?? ''));
        $category = $_POST['category'] ?? '';
        $description = trim((string)($_POST['description'] ?? ''));
        $image = trim((string)($_POST['image_url'] ?? ''));
        $price = filter_var($_POST['price'] ?? null, FILTER_VALIDATE_INT);
        $original = null;
        if (($_POST['original_price'] ?? '') !== '') {
            $original = filter_var($_POST['original_price'], FILTER_VALIDATE_INT);
        }
        $sizes = read_sizes((string)($_POST['sizes'] ?? ''));
        $unavailableText = (string)($_POST['unavailable'] ?? '');
        $unavailable = read_sizes($unavailableText);

        if (mb_strlen($name) < 2 || mb_strlen($name) > 160) {
            $error = 'Enter a product name (2 to 160 characters).';
        } elseif (mb_strlen($type) < 2 || mb_strlen($type) > 100 || !in_array($category, ['men', 'women'], true)) {
            $error = 'Check the product type and collection.';
        } elseif (!$price || $price < 1 || $price > 999999) {
            $error = 'Enter a valid price.';
        } elseif ($original !== null && (!$original || $original < $price || $original > 999999)) {
            $error = 'Original price must be at least the sale price.';
        } elseif (mb_strlen($description) < 10) {
            $error = 'Add a description of at least 10 characters.';
        } elseif (!filter_var($image, FILTER_VALIDATE_URL) || parse_url($image, PHP_URL_SCHEME) !== 'https') {
            $error = 'Enter an HTTPS image URL.';
        } elseif (!$sizes || count($sizes) > 30 || ($unavailableText !== '' && !$unavailable) || array_diff($unavailable, $sizes)) {
            $error = 'Check the sizes. Use numbers separated by commas.';
        } else {
            $existing = null;
            if ($id) {
                $query = db()->prepare('SELECT images_json FROM products WHERE id = ?');
                $query->execute([$id]);
                $existing = $query->fetch();
            }
            $images = [];
            if ($existing) {
                $images = json_decode($existing['images_json'], true);
            }
            $images[0] = $image;
            $values = [$category, $type, $name, $price, $original, $image,
                json_encode(array_values($images)), json_encode($sizes), json_encode($unavailable),
                $description, isset($_POST['is_sale']) ? 1 : 0, isset($_POST['is_active']) ? 1 : 0];
            if ($id && $existing) {
                $query = db()->prepare('UPDATE products SET category=?, type=?, name=?, price=?, original_price=?, image_url=?,
                    images_json=?, sizes_json=?, unavailable_json=?, description=?, is_sale=?, is_active=? WHERE id=?');
                $values[] = $id;
                $query->execute($values);
            } else {
                $query = db()->prepare('INSERT INTO products (category, type, name, price, original_price, image_url,
                    images_json, sizes_json, unavailable_json, description, is_sale, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $query->execute($values);
            }
            header('Location: admin.php?tab=products&saved=1');
            exit;
        }
        $tab = 'products';
    }
}

$pdo = db();
$edit = null;
if ($tab === 'products' && isset($_GET['edit'])) {
    $query = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $query->execute([(int)$_GET['edit']]);
    $edit = $query->fetch() ?: null;
}
$orders = [];
if ($tab === 'orders') {
    $orders = $pdo->query('SELECT id, reference, first_name, last_name, email, total, status, created_at
        FROM orders ORDER BY id DESC LIMIT 100')->fetchAll();
}
$orderItems = [];
if ($orders) {
    $ids = array_column($orders, 'id');
    $places = implode(',', array_fill(0, count($ids), '?'));
    $query = $pdo->prepare("SELECT order_id, product_name, size, quantity FROM order_items WHERE order_id IN ($places)");
    $query->execute($ids);
    foreach ($query->fetchAll() as $item) {
        $orderItems[$item['order_id']][] = $item;
    }
}
$products = [];
if ($tab === 'products') {
    $products = $pdo->query('SELECT id, name, category, price, is_active FROM products ORDER BY id DESC')->fetchAll();
}
$messages = [];
if ($tab === 'messages') {
    $messages = $pdo->query('SELECT id, name, email, phone, message, created_at
        FROM contact_messages ORDER BY id DESC LIMIT 100')->fetchAll();
}
$staffAccounts = [];
if ($tab === 'staff') {
    $staffAccounts = $pdo->query("SELECT id, name, email, role FROM users
        WHERE role IN ('customer', 'staff') ORDER BY id DESC LIMIT 100")->fetchAll();
}
$pageTitle = 'Dashboard';
require __DIR__ . '/includes/account_header.php';
?>
<section class="account-card">
    <p class="eyebrow">BRISTOL SHOES</p>
    <div class="account-heading"><div><h1>Dashboard</h1><p class="account-intro">Manage the shop without editing the database directly.</p></div><a class="outline-button" href="account.php">My account</a></div>
    <div class="admin-tabs"><a class="<?= $tab === 'orders' ? 'active' : '' ?>" href="admin.php?tab=orders">Orders</a><a class="<?= $tab === 'products' ? 'active' : '' ?>" href="admin.php?tab=products">Products</a><a class="<?= $tab === 'messages' ? 'active' : '' ?>" href="admin.php?tab=messages">Messages</a><a class="<?= $tab === 'staff' ? 'active' : '' ?>" href="admin.php?tab=staff">Staff access</a><a href="staff.php">Reports</a></div>
    <?php if ($error): ?><p class="form-alert" role="alert"><?= e($error) ?></p><?php endif; ?>
    <?php if ($notice): ?><p class="form-success-note" role="status"><?= e($notice) ?></p><?php endif; ?>

    <?php if ($tab === 'orders'): ?>
    <h2>Recent orders</h2>
    <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Order</th><th>Customer</th><th>Total</th><th>Status</th></tr></thead><tbody>
        <?php foreach ($orders as $order): ?><tr>
            <td><strong>#<?= e($order['reference']) ?></strong><small><?= e(date('M j, Y', strtotime($order['created_at']))) ?></small>
                <details><summary>View items</summary><?php foreach ($orderItems[$order['id']] ?? [] as $item): ?><small><?= e($item['product_name']) ?> · Size <?= (int)$item['size'] ?> × <?= (int)$item['quantity'] ?></small><?php endforeach; ?></details>
            </td>
            <td><?= e($order['first_name'] . ' ' . $order['last_name']) ?><small><?= e($order['email']) ?></small></td>
            <td>₱<?= number_format((int)$order['total']) ?></td>
            <td><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="order_status"><input type="hidden" name="id" value="<?= (int)$order['id'] ?>"><select name="status" aria-label="Status for order <?= e($order['reference']) ?>" onchange="this.form.submit()">
                <?php foreach (['pending', 'processing', 'shipped', 'completed', 'cancelled'] as $status): ?><option value="<?= $status ?>" <?= $order['status'] === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option><?php endforeach; ?>
            </select></form></td>
        </tr><?php endforeach; ?>
    </tbody></table></div>
    <?php if (!$orders): ?><p class="account-intro">No orders yet.</p><?php endif; ?>

    <?php elseif ($tab === 'products'): ?>
    <h2><?= $edit ? 'Edit product' : 'Add a product' ?></h2>
    <form method="post" class="admin-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="save_product"><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
        <label>Product name<input name="name" maxlength="160" required value="<?= e($edit['name'] ?? '') ?>"></label>
        <label>Type<input name="type" maxlength="100" required placeholder="Oxford, Loafer…" value="<?= e($edit['type'] ?? '') ?>"></label>
        <label>Collection<select name="category"><option value="men" <?= ($edit['category'] ?? '') === 'men' ? 'selected' : '' ?>>Men</option><option value="women" <?= ($edit['category'] ?? '') === 'women' ? 'selected' : '' ?>>Women</option></select></label>
        <label>Price (₱)<input type="number" name="price" min="1" max="999999" required value="<?= e((string)($edit['price'] ?? '')) ?>"></label>
        <label>Original price (optional)<input type="number" name="original_price" min="1" max="999999" value="<?= e((string)($edit['original_price'] ?? '')) ?>"></label>
        <label>Image URL (HTTPS)<input type="url" name="image_url" required value="<?= e($edit['image_url'] ?? '') ?>"></label>
        <label>Sizes, comma-separated<input name="sizes" required placeholder="39, 40, 41, 42" value="<?= e($edit ? implode(', ', json_decode($edit['sizes_json'], true)) : '') ?>"></label>
        <label>Unavailable sizes<input name="unavailable" placeholder="44" value="<?= e($edit ? implode(', ', json_decode($edit['unavailable_json'], true)) : '') ?>"></label>
        <label class="full">Description<textarea name="description" required><?= e($edit['description'] ?? '') ?></textarea></label>
        <label><input type="checkbox" name="is_sale" <?= !empty($edit['is_sale']) ? 'checked' : '' ?>> Show sale badge</label>
        <label><input type="checkbox" name="is_active" <?= !$edit || !empty($edit['is_active']) ? 'checked' : '' ?>> Visible in shop</label>
        <button class="account-button" type="submit"><?= $edit ? 'Save product' : 'Add product' ?></button>
    </form>
    <h2 class="admin-list-heading">Catalog</h2>
    <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Product</th><th>Collection</th><th>Price</th><th>Visibility</th><th></th></tr></thead><tbody>
    <?php foreach ($products as $product): ?><tr><td><?= e($product['name']) ?></td><td><?= e(ucfirst($product['category'])) ?></td><td>₱<?= number_format((int)$product['price']) ?></td><td><?= $product['is_active'] ? 'Visible' : 'Hidden' ?></td><td><a href="admin.php?tab=products&amp;edit=<?= (int)$product['id'] ?>">Edit</a></td></tr><?php endforeach; ?>
    </tbody></table></div>

    <?php elseif ($tab === 'messages'): ?>
    <h2>Contact messages</h2>
    <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>From</th><th>Message</th><th>Received</th></tr></thead><tbody>
    <?php foreach ($messages as $message): ?><tr><td><?= e($message['name']) ?><small><?= e($message['email']) ?></small></td><td><?= nl2br(e($message['message'])) ?></td><td><?= e(date('M j, Y', strtotime($message['created_at']))) ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <?php if (!$messages): ?><p class="account-intro">No messages yet.</p><?php endif; ?>

    <?php else: ?>
    <h2>Staff access</h2>
    <p class="account-intro">A teammate must register normally first. Then choose Staff here to allow order management and reports. This page cannot change an admin account.</p>
    <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Name</th><th>Email</th><th>Role</th></tr></thead><tbody>
        <?php foreach ($staffAccounts as $account): ?>
        <tr><td><?= e($account['name']) ?></td><td><?= e($account['email']) ?></td><td>
            <form method="post" class="staff-status-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="staff_role">
                <input type="hidden" name="id" value="<?= (int)$account['id'] ?>">
                <select name="role" aria-label="Role for <?= e($account['name']) ?>">
                    <option value="customer" <?= $account['role'] === 'customer' ? 'selected' : '' ?>>Customer</option>
                    <option value="staff" <?= $account['role'] === 'staff' ? 'selected' : '' ?>>Staff</option>
                </select>
                <button type="submit">Save</button>
            </form>
        </td></tr>
        <?php endforeach; ?>
    </tbody></table></div>
    <?php if (!$staffAccounts): ?><p class="account-intro">No customer or staff accounts yet.</p><?php endif; ?>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/account_footer.php'; ?>
