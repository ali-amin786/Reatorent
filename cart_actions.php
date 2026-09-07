<?php
// cart_actions.php - Procedural AJAX Endpoint for Shopping Cart
header('Content-Type: application/json; charset=utf-8');

require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/cart_functions.php';

// Disable HTML error display for clean JSON responses
ini_set('display_errors', 0);

$action = $_REQUEST['action'] ?? 'get_cart';
$response = ['success' => false];

function format_cart_response($conn, $extra = []) {
    $cart_items = cart_get_items_details($conn);
    $subtotal = 0.0;
    $total_count = 0;

    $formatted_items = [];
    foreach ($cart_items as $item) {
        $subtotal += $item['line_total'];
        $total_count += (int)$item['cart_quantity'];

        $formatted_items[] = [
            'id' => (int)$item['id'],
            'title' => $item['title'],
            'price' => (float)$item['price'],
            'price_formatted' => 'PKR ' . number_format($item['price'], 0),
            'quantity' => (int)$item['cart_quantity'],
            'line_total' => (float)$item['line_total'],
            'line_total_formatted' => 'PKR ' . number_format($item['line_total'], 0),
            'image_url' => $item['image_url'],
            'is_available' => (bool)$item['is_available']
        ];
    }

    $min_order = defined('MIN_ORDER_AMOUNT') ? MIN_ORDER_AMOUNT : 500.0;
    $amount_needed = max(0, $min_order - $subtotal);

    // Fetch popular upsell suggestions (e.g. Roghani Naan, Raita, Beverages)
    $upsell_sql = "SELECT id, title, price, image_url 
                   FROM items 
                   WHERE is_available = 1 AND category_id IN (2, 4, 5) 
                   ORDER BY id ASC LIMIT 4";
    $upsell_res = mysqli_query($conn, $upsell_sql);
    $upsells = [];
    if ($upsell_res) {
        while ($u = mysqli_fetch_assoc($upsell_res)) {
            $upsells[] = [
                'id' => (int)$u['id'],
                'title' => $u['title'],
                'price' => (float)$u['price'],
                'price_formatted' => 'PKR ' . number_format($u['price'], 0),
                'image_url' => $u['image_url']
            ];
        }
    }

    return array_merge([
        'success' => true,
        'count' => $total_count,
        'subtotal' => $subtotal,
        'subtotal_formatted' => 'PKR ' . number_format($subtotal, 0),
        'min_order' => $min_order,
        'meets_min_order' => ($subtotal >= $min_order),
        'amount_needed' => $amount_needed,
        'amount_needed_formatted' => 'PKR ' . number_format($amount_needed, 0),
        'items' => $formatted_items,
        'upsell_items' => $upsells
    ], $extra);
}

if ($action === 'get_cart') {
    echo json_encode(format_cart_response($conn));
    exit;
}

if ($action === 'add') {
    $item_id = clean_int($_REQUEST['id'] ?? 0);
    $quantity = max(1, clean_int($_REQUEST['quantity'] ?? 1));

    if ($item_id > 0) {
        // Fetch item title to return for the notification
        $title_res = mysqli_query($conn, "SELECT title, is_available FROM items WHERE id = $item_id LIMIT 1");
        $item_info = mysqli_fetch_assoc($title_res);

        if ($item_info && $item_info['is_available']) {
            cart_add($item_id, $quantity);
            echo json_encode(format_cart_response($conn, [
                'message' => "Added {$quantity}x " . $item_info['title'] . " to cart",
                'added_title' => $item_info['title']
            ]));
            exit;
        } else {
            echo json_encode(['success' => false, 'error' => 'This item is currently sold out.']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid item ID.']);
        exit;
    }
}

if ($action === 'update') {
    $item_id = clean_int($_REQUEST['id'] ?? 0);
    $quantity = clean_int($_REQUEST['quantity'] ?? 0);

    if ($item_id > 0) {
        cart_update($item_id, $quantity);
        echo json_encode(format_cart_response($conn, ['message' => 'Cart updated']));
        exit;
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid item ID.']);
        exit;
    }
}

if ($action === 'remove') {
    $item_id = clean_int($_REQUEST['id'] ?? 0);

    if ($item_id > 0) {
        cart_remove($item_id);
        echo json_encode(format_cart_response($conn, ['message' => 'Item removed from cart']));
        exit;
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid item ID.']);
        exit;
    }
}

if ($action === 'clear') {
    cart_clear();
    echo json_encode(format_cart_response($conn, ['message' => 'Cart cleared']));
    exit;
}

echo json_encode(['success' => false, 'error' => 'Unknown action.']);
exit;
