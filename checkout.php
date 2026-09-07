<?php
// checkout.php - Modern Two-Column Fast-Food Checkout Page
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/cart_functions.php';

// 1. Empty Cart Guard
$cart_items = cart_get_items_details($conn);
if (empty($cart_items)) {
    header("Location: menu.php");
    exit;
}

// 2. Subtotal & Minimum Order Check
$subtotal = 0.0;
foreach ($cart_items as $ci) {
    $subtotal += $ci['line_total'];
}

$min_order = defined('MIN_ORDER_AMOUNT') ? MIN_ORDER_AMOUNT : 500.0;
if ($subtotal < $min_order) {
    header("Location: menu.php");
    exit;
}

// 3. Operating Hours Check
$hours_info = check_restaurant_hours();
$is_open = $hours_info['is_open'];

// Fetch active delivery areas
$areas_sql = "SELECT * FROM delivery_areas WHERE is_active = 1 ORDER BY delivery_fee ASC, area_name ASC";
$areas_res = mysqli_query($conn, $areas_sql);
$delivery_areas = [];
while ($row = mysqli_fetch_assoc($areas_res)) {
    $delivery_areas[] = $row;
}
$default_area = !empty($delivery_areas) ? $delivery_areas[0] : null;
$default_fee = $default_area ? (float)$default_area['delivery_fee'] : 0.0;

$error = "";

// 4. Handle Order Placement
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request security token. Please refresh and try again.";
    } elseif (!$is_open) {
        $error = "Sorry, our kitchen is currently closed. We reopen at " . $hours_info['open_time'] . ". Orders cannot be placed right now.";
    } else {
        $customer_title = clean($conn, $_POST['customer_title'] ?? 'Mr');
        $customer_name_raw = clean($conn, $_POST['customer_name'] ?? '');
        $customer_name = $customer_title ? ($customer_title . '. ' . $customer_name_raw) : $customer_name_raw;
        $customer_phone = clean($conn, $_POST['customer_phone'] ?? '');
        $customer_phone_alt = clean($conn, $_POST['customer_phone_alt'] ?? '');
        $delivery_address = clean($conn, $_POST['delivery_address'] ?? '');
        $delivery_area_id = clean_int($_POST['delivery_area_id'] ?? 0);
        $nearest_landmark = clean($conn, $_POST['nearest_landmark'] ?? '');
        $customer_email = clean($conn, $_POST['customer_email'] ?? '');
        $delivery_notes = clean($conn, $_POST['order_notes'] ?? '');
        $cod_change = clean($conn, $_POST['cod_change'] ?? '');
        $payment_method = clean($conn, $_POST['payment_method'] ?? 'cod');
        $transaction_id = clean($conn, $_POST['transaction_id'] ?? '');

        // Form full notes
        $combined_notes = [];
        if (!empty($nearest_landmark)) $combined_notes[] = "Landmark: " . $nearest_landmark;
        if (!empty($customer_phone_alt)) $combined_notes[] = "Alt Phone: " . $customer_phone_alt;
        if (!empty($customer_email)) $combined_notes[] = "Email: " . $customer_email;
        if ($payment_method === 'cod' && !empty($cod_change)) $combined_notes[] = "Change Required For: Rs. " . $cod_change;
        if (!empty($delivery_notes)) $combined_notes[] = "Instructions: " . $delivery_notes;
        $order_notes_final = implode(" | ", $combined_notes);

        // Validation
        if (empty($customer_name_raw)) {
            $error = "Please enter your full name.";
        } elseif (empty($customer_phone) || strlen(preg_replace('/[^0-9]/', '', $customer_phone)) < 10) {
            $error = "Please provide a valid contact phone number (at least 10 digits).";
        } elseif (empty($delivery_address)) {
            $error = "Please enter your complete delivery street address.";
        } elseif ($delivery_area_id <= 0) {
            $error = "Please select your delivery area.";
        } elseif (!in_array($payment_method, ['cod', 'easypaisa', 'jazzcash', 'bank_transfer', 'online'])) {
            $error = "Please select a valid payment method.";
        } elseif ($payment_method !== 'cod' && empty($transaction_id)) {
            $error = "Please enter the Transaction ID / Ref # for your online payment.";
        } else {
            // Find delivery fee
            $delivery_fee = 0.0;
            foreach ($delivery_areas as $area) {
                if ((int)$area['id'] === $delivery_area_id) {
                    $delivery_fee = (float)$area['delivery_fee'];
                    break;
                }
            }

            $total_amount = $subtotal + $delivery_fee;

            // Handle optional payment proof upload
            $payment_proof_name = null;
            if ($payment_method !== 'cod' && isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
                $upload_res = handle_image_upload(
                    $_FILES['payment_proof'], 
                    __DIR__ . '/assets/uploads/payments/', 
                    'proof_'
                );
                if ($upload_res['success']) {
                    $payment_proof_name = clean($conn, $upload_res['filename']);
                } else {
                    $error = "Payment proof upload failed: " . $upload_res['error'];
                }
            }

            if (empty($error)) {
                $proof_val = $payment_proof_name ? "'$payment_proof_name'" : "NULL";
                $txn_val = !empty($transaction_id) ? "'$transaction_id'" : "NULL";
                $notes_val = !empty($order_notes_final) ? "'$order_notes_final'" : "NULL";
                $db_payment_method = ($payment_method === 'online') ? 'bank_transfer' : $payment_method;

                $insert_order_sql = "INSERT INTO orders (
                    customer_name, customer_phone, delivery_address, delivery_area_id, 
                    order_notes, payment_method, transaction_id, payment_proof, 
                    subtotal, delivery_fee, total_amount, order_status
                ) VALUES (
                    '$customer_name', '$customer_phone', '$delivery_address', $delivery_area_id,
                    $notes_val, '$db_payment_method', $txn_val, $proof_val,
                    $subtotal, $delivery_fee, $total_amount, 'Pending'
                )";

                if (mysqli_query($conn, $insert_order_sql)) {
                    $order_id = mysqli_insert_id($conn);

                    // Insert snapshotted order items
                    foreach ($cart_items as $item) {
                        $item_id = (int)$item['id'];
                        $item_title = clean($conn, $item['title']);
                        $qty = (int)$item['cart_quantity'];
                        $price = (float)$item['price'];

                        $insert_item_sql = "INSERT INTO order_items (order_id, item_id, item_title, quantity, price) 
                                            VALUES ($order_id, $item_id, '$item_title', $qty, $price)";
                        mysqli_query($conn, $insert_item_sql);
                    }

                    // Insert initial audit trail log
                    $log_sql = "INSERT INTO order_status_log (order_id, old_status, new_status, changed_by) 
                                VALUES ($order_id, NULL, 'Pending', 'Customer Checkout')";
                    mysqli_query($conn, $log_sql);

                    // Clear session cart
                    cart_clear();

                    // Redirect to confirmation page
                    header("Location: order_success.php?order_id=" . $order_id);
                    exit;
                } else {
                    $error = "Could not record your order. Error: " . mysqli_error($conn);
                }
            }
        }
    }
}

$page_title = "Checkout Order";
require_once 'includes/header.php';
?>

<div class="container">
    <!-- Breadcrumb & Top Bar -->
    <div style="padding-top: 24px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--color-border); padding-bottom: 16px;">
        <a href="menu.php" style="color: var(--color-text-muted); font-size: 0.9rem; font-weight: 600;">
            &larr; Back to Menu
        </a>
        <div style="font-size: 0.88rem; color: var(--color-text-muted);">
            Need Help? Call: <strong style="color: var(--color-black);"><?php echo PHONE_NUMBER; ?></strong>
        </div>
    </div>

    <!-- Operating Hours Banner if Closed -->
    <?php if (!$is_open): ?>
        <div class="alert alert-danger" style="margin-top: 20px;">
            <strong>⛔ Kitchen is Closed:</strong> Operating hours are <?php echo $hours_info['open_time']; ?> to <?php echo $hours_info['close_time']; ?>. Checkout is paused until we reopen.
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger" style="margin-top: 20px;"><?php echo e($error); ?></div>
    <?php endif; ?>

    <!-- Two-Column Checkout Layout -->
    <form id="checkout-form" action="checkout.php" method="POST" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>
        <input type="hidden" id="checkout-subtotal-val" value="<?php echo $subtotal; ?>">

        <div class="checkout-two-col">
            <!-- Left Column: Customer & Delivery Details -->
            <div>
                <div class="checkout-card">
                    <div class="checkout-header-chip">
                        <span>🛵 This is a Delivery Order</span>
                    </div>
                    <h2 style="font-size: 1.6rem; color: var(--color-black); margin-bottom: 20px; letter-spacing: -0.5px;">Delivery Details</h2>

                    <!-- Name with Title -->
                    <div style="display: grid; grid-template-columns: 100px 1fr; gap: 12px;" class="form-group">
                        <div>
                            <label for="customer_title" class="form-label">Title</label>
                            <select name="customer_title" id="customer_title" class="form-control">
                                <option value="Mr">Mr.</option>
                                <option value="Mrs">Mrs.</option>
                                <option value="Ms">Ms.</option>
                            </select>
                        </div>
                        <div>
                            <label for="customer_name" class="form-label">Full Name *</label>
                            <input type="text" id="customer_name" name="customer_name" class="form-control" required placeholder="e.g. Bilal Ahmed" value="<?php echo e($_POST['customer_name'] ?? ''); ?>">
                        </div>
                    </div>

                    <!-- Mobile Numbers -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;" class="form-group">
                        <div>
                            <label for="customer_phone" class="form-label">Mobile Number *</label>
                            <input type="tel" id="customer_phone" name="customer_phone" class="form-control" required placeholder="03001234567" value="<?php echo e($_POST['customer_phone'] ?? ''); ?>">
                        </div>
                        <div>
                            <label for="customer_phone_alt" class="form-label">Alternate Mobile (Optional)</label>
                            <input type="tel" id="customer_phone_alt" name="customer_phone_alt" class="form-control" placeholder="03211234567" value="<?php echo e($_POST['customer_phone_alt'] ?? ''); ?>">
                        </div>
                    </div>

                    <!-- Delivery Area Selector (Dynamic Fee) -->
                    <div class="form-group">
                        <label for="delivery_area_id" class="form-label">Delivery Sector / Area *</label>
                        <select name="delivery_area_id" id="delivery_area_id" class="form-control" required>
                            <option value="">-- Choose Your Karachi Sector --</option>
                            <?php foreach ($delivery_areas as $area): ?>
                                <option value="<?php echo (int)$area['id']; ?>" 
                                        data-fee="<?php echo (float)$area['delivery_fee']; ?>"
                                        <?php echo (isset($_POST['delivery_area_id']) && (int)$_POST['delivery_area_id'] === (int)$area['id']) ? 'selected' : (($area['id'] == 1) ? 'selected' : ''); ?>>
                                    <?php echo e($area['area_name']); ?> (+PKR <?php echo number_format($area['delivery_fee'], 0); ?> delivery)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Full Delivery Address -->
                    <div class="form-group">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                            <label for="delivery_address" class="form-label" style="margin-bottom: 0;">Full Delivery Address *</label>
                            <button type="button" id="btn-use-location" class="btn" style="background: none; border: none; color: var(--color-primary); font-size: 0.8rem; padding: 0; text-decoration: underline; cursor: pointer;">
                                📍 Use current location
                            </button>
                        </div>
                        <textarea id="delivery_address" name="delivery_address" class="form-control" required style="min-height: 85px;" placeholder="House/Flat #, Building/Apartment name, Street #, Block/Sector, Scheme 33"><?php echo e($_POST['delivery_address'] ?? ''); ?></textarea>
                    </div>

                    <!-- Nearest Landmark & Email -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;" class="form-group">
                        <div>
                            <label for="nearest_landmark" class="form-label">Nearest Landmark (Optional)</label>
                            <input type="text" id="nearest_landmark" name="nearest_landmark" class="form-control" placeholder="e.g. Near Kiran Hospital" value="<?php echo e($_POST['nearest_landmark'] ?? ''); ?>">
                        </div>
                        <div>
                            <label for="customer_email" class="form-label">Email Address (Optional)</label>
                            <input type="email" id="customer_email" name="customer_email" class="form-control" placeholder="bilal@example.com" value="<?php echo e($_POST['customer_email'] ?? ''); ?>">
                        </div>
                    </div>

                    <!-- Delivery Instructions -->
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="order_notes" class="form-label">Delivery Instructions (Optional)</label>
                        <textarea id="order_notes" name="order_notes" class="form-control" style="min-height: 60px;" placeholder="e.g. Ring the bell twice, leave with security, extra mint chutney"><?php echo e($_POST['order_notes'] ?? ''); ?></textarea>
                    </div>
                </div>

                <!-- Payment Information Section -->
                <div class="checkout-card">
                    <h2 style="font-size: 1.4rem; color: var(--color-black); margin-bottom: 16px; letter-spacing: -0.5px;">Payment Information</h2>

                    <div class="payment-methods-grid">
                        <!-- Card 1: Cash on Delivery -->
                        <div class="payment-card-option selected" data-method="cod">
                            <span class="payment-check-badge">✓</span>
                            <input type="radio" name="payment_method" value="cod" checked>
                            <div class="payment-card-title">
                                <span>💵</span>
                                <span>Cash on Delivery</span>
                            </div>
                            <div class="payment-card-sub">Pay rider in cash upon receipt</div>

                            <!-- COD Change Request Field -->
                            <div id="cod-change-wrap" class="cod-change-wrap">
                                <label for="cod_change" style="font-size: 0.78rem; font-weight: 600; color: var(--color-text-muted); display: block; margin-bottom: 4px;">
                                    Change Request (Optional):
                                </label>
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <span style="font-size: 0.85rem; font-weight: 700;">Rs.</span>
                                    <input type="number" id="cod_change" name="cod_change" class="form-control" style="padding: 6px 10px; height: 32px; font-size: 0.85rem;" placeholder="e.g. 2000" value="<?php echo e($_POST['cod_change'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Card 2: Online Payment -->
                        <div class="payment-card-option" data-method="online">
                            <span class="payment-check-badge">✓</span>
                            <input type="radio" name="payment_method" value="online" <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] === 'online') ? 'checked' : ''; ?>>
                            <div class="payment-card-title">
                                <span>💳</span>
                                <span>Online Payment</span>
                            </div>
                            <div class="payment-card-sub">EasyPaisa, JazzCash, Bank Transfer</div>
                        </div>
                    </div>

                    <!-- Online Payment Account Details (Shown when Online Payment chosen) -->
                    <div id="online-payment-details-wrap" style="display: none; background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-sm); padding: 20px; margin-top: 15px;">
                        <h4 style="font-size: 1rem; color: var(--color-black); margin-bottom: 12px;">Transfer Payment to A1 Peshawari Kabab</h4>

                        <div style="font-size: 0.88rem; line-height: 1.6; margin-bottom: 15px; color: var(--color-text-dark); background: #FFF; padding: 12px; border-radius: 6px; border: 1px solid #EAEAEA;">
                            <strong>EasyPaisa / JazzCash:</strong> 0300-1234567<br>
                            <strong>Account Title:</strong> A1 Peshawari Kabab (MASHAALLAH Naan)<br>
                            <strong>Bank Transfer:</strong> Meezan Bank Ltd (Scheme 33 Branch)<br>
                            <strong>IBAN:</strong> PK42MEZN0001234567890123
                        </div>

                        <div class="form-group">
                            <label for="transaction_id" class="form-label">Transaction ID / Reference Number *</label>
                            <input type="text" id="transaction_id" name="transaction_id" class="form-control" placeholder="e.g. 2938471928" value="<?php echo e($_POST['transaction_id'] ?? ''); ?>">
                        </div>

                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="payment_proof" class="form-label">Upload Payment Screenshot / Receipt (Max 2MB)</label>
                            <input type="file" id="payment_proof" name="payment_proof" class="form-control" accept="image/jpeg,image/png,image/webp">
                            <div id="receipt-preview"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Sticky Order Summary -->
            <div class="checkout-summary-sticky">
                <div class="summary-items-box">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid var(--color-border); padding-bottom: 10px;">
                        <h3 style="font-size: 1.25rem; color: var(--color-black); margin: 0;">Order Summary</h3>
                        <span style="font-size: 0.82rem; font-weight: 700; color: var(--color-primary);"><?php echo count($cart_items); ?> Item(s)</span>
                    </div>

                    <!-- Items List -->
                    <div style="margin-bottom: 14px;">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="summary-item-line">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <img src="assets/uploads/items/<?php echo e($item['image_url']); ?>" alt="<?php echo e($item['title']); ?>" style="width: 44px; height: 40px; object-fit: cover; border-radius: 4px; border: 1px solid #EAEAEA;">
                                    <div>
                                        <div class="summary-item-name"><?php echo e($item['title']); ?></div>
                                        <div style="font-size: 0.78rem; color: var(--color-text-muted);"><?php echo (int)$item['cart_quantity']; ?>x @ PKR <?php echo number_format($item['price'], 0); ?></div>
                                    </div>
                                </div>
                                <div class="summary-item-price">PKR <?php echo number_format($item['line_total'], 0); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div style="text-align: right; margin-top: 6px;">
                        <a href="menu.php" style="font-size: 0.82rem; font-weight: 600; color: var(--color-primary);">+ Continue to add more items</a>
                    </div>
                </div>

                <!-- Price Breakdown Box (Light Red/Pink Tinted) -->
                <div class="checkout-breakdown-box">
                    <div class="checkout-breakdown-row">
                        <span>Total</span>
                        <span style="font-weight: 700;">PKR <?php echo number_format($subtotal, 0); ?></span>
                    </div>
                    <div class="checkout-breakdown-row">
                        <span>Delivery Fee</span>
                        <span id="checkout-delivery-fee-display" style="font-weight: 700;">PKR <?php echo number_format($default_fee, 0); ?></span>
                    </div>
                    <div class="checkout-breakdown-row grand-total">
                        <span>Grand Total</span>
                        <span class="total-amount-red" id="checkout-grand-total-display">PKR <?php echo number_format($subtotal + $default_fee, 0); ?></span>
                    </div>
                </div>

                <!-- Sticky Place Order Button -->
                <div>
                    <?php if ($is_open): ?>
                        <button type="submit" id="place-order-submit-btn" class="btn btn-primary place-order-btn">
                            Place Order &rarr;
                        </button>
                    <?php else: ?>
                        <button type="button" class="btn place-order-btn" disabled>
                            ⛔ Kitchen Closed
                        </button>
                    <?php endif; ?>
                </div>

                <p style="font-size: 0.78rem; color: var(--color-text-muted); text-align: center; margin-top: 14px;">
                    We'll call or WhatsApp your number to confirm preparation.
                </p>
            </div>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>
