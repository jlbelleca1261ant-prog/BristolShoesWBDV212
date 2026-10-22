<?php
declare(strict_types=1);
require __DIR__ . '/includes/common.php';

$data = read_json();
require_csrf();
$userId = require_user_id();

// 1. Read the checkout form.
$firstName = field($data, 'first_name', 80);
$lastName = field($data, 'last_name', 80);
$email = field($data, 'email', 190);
$phone = field($data, 'phone', 20);
$address = field($data, 'address', 255);
$city = field($data, 'city', 100);
$province = field($data, 'province', 100);
$postalCode = field($data, 'postal_code', 12);
$payment = field($data, 'payment_method', 30);

// 2. Check the entered details.
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['error' => 'Enter a valid email address.'], 422);
}
if (!preg_match('/^(?:\+639|09)\d{9}$/', $phone)) {
    json_response(['error' => 'Enter a valid Philippine mobile number.'], 422);
}
if (!preg_match('/^\d{4}$/', $postalCode)) {
    json_response(['error' => 'Enter a four-digit ZIP code.'], 422);
}
$paymentMethods = ['cod', 'card', 'gcash', 'maya', 'bank'];
if (!in_array($payment, $paymentMethods, true)) {
    json_response(['error' => 'Please check your contact, address, and payment details.'], 422);
}
$locations = json_decode(file_get_contents(__DIR__ . '/data/ph-locations.json'), true)['provinces'];
if (!isset($locations[$province])) {
    json_response(['error' => 'Select a valid province.'], 422);
}
if (!in_array($city, $locations[$province], true)) {
    json_response(['error' => 'Select a valid province and city.'], 422);
}

$mode = $data['mode'] ?? '';
if (!in_array($mode, ['cart', 'buy_now'], true)) {
    json_response(['error' => 'Invalid checkout mode.'], 422);
}
$items = $data['items'] ?? null;
// 3. For normal checkout, get the signed-in customer's saved cart.
if ($mode === 'cart') {
    // Never trust a browser's cart prices or quantities; read saved cart rows.
    $cartQuery = db()->prepare('SELECT product_id AS id, size, quantity AS qty FROM cart_items WHERE user_id = ?');
    $cartQuery->execute([$userId]);
    $items = $cartQuery->fetchAll();
}
if (!is_array($items) || !array_is_list($items) || count($items) < 1 || count($items) > 30) {
    json_response(['error' => 'Your cart must contain 1 to 30 items.'], 422);
}

try {
    $pdo = db();
    // Either all order rows save, or none of them save.
    $pdo->beginTransaction();
    // 4. Check each product and calculate prices from MySQL.
    $select = $pdo->prepare('SELECT id, name, price, sizes_json, unavailable_json FROM products WHERE id = ? AND is_active = 1');
    $lines = [];
    $subtotal = 0;
    foreach ($items as $item) {
        if (!is_array($item)) {
            $pdo->rollBack();
            json_response(['error' => 'Your cart contains an invalid item.'], 422);
        }

        $productId = filter_var($item['id'] ?? null, FILTER_VALIDATE_INT);
        $size = filter_var($item['size'] ?? null, FILTER_VALIDATE_INT);
        $quantity = filter_var($item['qty'] ?? null, FILTER_VALIDATE_INT);
        if (!$productId || !$size || !$quantity || $quantity > 10) {
            $pdo->rollBack();
            json_response(['error' => 'Your cart contains an invalid item.'], 422);
        }

        $select->execute([$productId]);
        $product = $select->fetch();
        if (!$product) {
            $pdo->rollBack();
            json_response(['error' => 'A product is no longer available. Refresh the page.'], 422);
        }
        $sizes = json_decode($product['sizes_json'], true);
        $unavailable = json_decode($product['unavailable_json'], true);
        if (!in_array((int)$size, $sizes, true) || in_array((int)$size, $unavailable, true)) {
            $pdo->rollBack();
            json_response(['error' => 'A product or size is no longer available. Refresh the page.'], 422);
        }

        // The database price, not the browser price, determines the total.
        $lineTotal = (int)$product['price'] * $quantity;
        $subtotal += $lineTotal;
        $lines[] = [
            'product_id' => $productId,
            'name' => $product['name'],
            'size' => $size,
            'quantity' => $quantity,
            'price' => $product['price'],
            'total' => $lineTotal,
        ];
    }

    $shipping = SHIPPING_FEE;
    $reference = strtoupper(bin2hex(random_bytes(6)));
    // 5. Save the order and its items.
    $insert = $pdo->prepare('INSERT INTO orders
        (reference, user_id, first_name, last_name, email, phone, address, city, province, postal_code,
         payment_method, payment_status, subtotal, shipping, total, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $insert->execute([$reference, $userId, $firstName, $lastName, $email, $phone, $address, $city, $province,
        $postalCode, $payment, 'unpaid', $subtotal, $shipping, $subtotal + $shipping, 'pending']);

    $orderId = (int)$pdo->lastInsertId();
    $insertItem = $pdo->prepare('INSERT INTO order_items
        (order_id, product_id, product_name, size, quantity, unit_price, line_total)
        VALUES (?, ?, ?, ?, ?, ?, ?)');
    foreach ($lines as $line) {
        $insertItem->execute([
            $orderId, $line['product_id'], $line['name'], $line['size'],
            $line['quantity'], $line['price'], $line['total']
        ]);
    }
    if ($mode === 'cart') {
        $clear = $pdo->prepare('DELETE FROM cart_items WHERE user_id = ?');
        $clear->execute([$userId]);
    }
    $pdo->commit();
    json_response(['reference' => $reference, 'total' => $subtotal + $shipping], 201);
} catch (Throwable $error) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log((string)$error);
    json_response(['error' => 'Could not save the order. Please try again later.'], 503);
}

