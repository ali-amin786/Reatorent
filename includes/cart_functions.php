<?php
// includes/cart_functions.php - Helpers for Session-based Shopping Cart

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Initialize cart array in session if not set
 */
function init_cart() {
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
}

/**
 * Get total quantity count of items in cart for the navbar badge
 */
function cart_get_count() {
    init_cart();
    $count = 0;
    foreach ($_SESSION['cart'] as $qty) {
        $count += (int)$qty;
    }
    return $count;
}

/**
 * Check if cart is empty
 */
function cart_is_empty() {
    init_cart();
    return empty($_SESSION['cart']);
}

/**
 * Add an item to cart
 */
function cart_add($item_id, $quantity = 1) {
    init_cart();
    $item_id = (int)$item_id;
    $quantity = max(1, (int)$quantity);

    if ($item_id > 0) {
        $_SESSION['cart'][$item_id] = ($_SESSION['cart'][$item_id] ?? 0) + $quantity;
        return true;
    }
    return false;
}

/**
 * Update item quantity in cart
 */
function cart_update($item_id, $quantity) {
    init_cart();
    $item_id = (int)$item_id;
    $quantity = (int)$quantity;

    if ($item_id > 0) {
        if ($quantity <= 0) {
            unset($_SESSION['cart'][$item_id]);
        } else {
            $_SESSION['cart'][$item_id] = $quantity;
        }
        return true;
    }
    return false;
}

/**
 * Remove an item from cart
 */
function cart_remove($item_id) {
    init_cart();
    $item_id = (int)$item_id;
    if (isset($_SESSION['cart'][$item_id])) {
        unset($_SESSION['cart'][$item_id]);
        return true;
    }
    return false;
}

/**
 * Clear the entire cart
 */
function cart_clear() {
    $_SESSION['cart'] = [];
}

/**
 * Fetch cart item details from database
 * @param mysqli $conn
 * @return array List of items with details, quantity, item_subtotal
 */
function cart_get_items_details($conn) {
    init_cart();
    if (empty($_SESSION['cart'])) {
        return [];
    }

    $item_ids = array_map('intval', array_keys($_SESSION['cart']));
    if (empty($item_ids)) {
        return [];
    }

    $id_list = implode(',', $item_ids);
    $sql = "SELECT i.*, c.name AS category_name 
            FROM items i 
            LEFT JOIN categories c ON i.category_id = c.id 
            WHERE i.id IN ($id_list)";
    
    $result = mysqli_query($conn, $sql);
    $items = [];

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $id = (int)$row['id'];
            $qty = (int)($_SESSION['cart'][$id] ?? 1);
            $price = (float)$row['price'];
            $row['cart_quantity'] = $qty;
            $row['line_total'] = $price * $qty;
            $items[] = $row;
        }
    }

    return $items;
}

/**
 * Calculate cart subtotal amount
 */
function cart_calculate_subtotal($conn) {
    $items = cart_get_items_details($conn);
    $subtotal = 0.0;
    foreach ($items as $item) {
        $subtotal += $item['line_total'];
    }
    return $subtotal;
}
?>
