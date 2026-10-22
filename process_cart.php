<?php
declare(strict_types=1);
require __DIR__ . '/includes/common.php';

$userId = require_user_id();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // POST changes the cart: add, set quantity, remove, or clear.
    $data = read_json();
    require_csrf();
    $action = $data['action'] ?? '';

    if ($action === 'clear') {
        $query = $pdo->prepare('DELETE FROM cart_items WHERE user_id = ?');
        $query->execute([$userId]);
    } elseif ($action === 'add' || $action === 'set' || $action === 'remove') {
        $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
        $size = filter_var($data['size'] ?? null, FILTER_VALIDATE_INT);
        if (!$id || !$size) {
            json_response(['error' => 'Invalid product or size.'], 422);
        }
        if ($action === 'add') {
            // Only add sizes that the product currently offers.
            $query = $pdo->prepare('SELECT sizes_json, unavailable_json FROM products WHERE id = ? AND is_active = 1');
            $query->execute([$id]);
            $product = $query->fetch();
            if (!$product) {
                json_response(['error' => 'This product is unavailable.'], 422);
            }
            $sizes = json_decode($product['sizes_json'], true);
            $unavailable = json_decode($product['unavailable_json'], true);
            if (!in_array((int)$size, $sizes, true) || in_array((int)$size, $unavailable, true)) {
                json_response(['error' => 'This size is unavailable.'], 422);
            }
            $query = $pdo->prepare('INSERT INTO cart_items (user_id, product_id, size, quantity) VALUES (?, ?, ?, 1)
                ON DUPLICATE KEY UPDATE quantity = LEAST(10, quantity + 1)');
            $query->execute([$userId, $id, $size]);
        } elseif ($action === 'set') {
            $quantity = filter_var($data['qty'] ?? null, FILTER_VALIDATE_INT);
            if ($quantity === false || $quantity < 1 || $quantity > 10) {
                json_response(['error' => 'Quantity must be between 1 and 10.'], 422);
            }
            $query = $pdo->prepare('UPDATE cart_items SET quantity = ? WHERE user_id = ? AND product_id = ? AND size = ?');
            $query->execute([$quantity, $userId, $id, $size]);
        } else {
            $query = $pdo->prepare('DELETE FROM cart_items WHERE user_id = ? AND product_id = ? AND size = ?');
            $query->execute([$userId, $id, $size]);
        }
    } else {
        json_response(['error' => 'Invalid cart action.'], 422);
    }
} elseif ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['error' => 'Method not allowed.'], 405);
}

// GET (and a successful POST) returns the latest cart for the page to display.
$query = $pdo->prepare('SELECT c.product_id, c.size, c.quantity, p.name, p.type, p.price, p.image_url
    FROM cart_items c JOIN products p ON p.id = c.product_id
    WHERE c.user_id = ? AND p.is_active = 1 ORDER BY c.id');
$query->execute([$userId]);
$items = [];
foreach ($query->fetchAll() as $row) {
    $items[] = [
        'key' => $row['product_id'] . '-' . $row['size'],
        'id' => (int)$row['product_id'],
        'size' => (int)$row['size'],
        'qty' => (int)$row['quantity'],
        'name' => $row['name'],
        'type' => $row['type'],
        'price' => (int)$row['price'],
        'img' => $row['image_url'],
    ];
}
json_response(['items' => $items]);
