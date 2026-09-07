<?php
// order_success.php - Fast-Food Order Confirmation & Status Tracking
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/cart_functions.php';

$order_id = isset($_GET['order_id']) ? clean_int($_GET['order_id']) : 0;
if ($order_id <= 0) {
    header("Location: index.php");
    exit;
}

// Fetch order
$order_sql = "SELECT o.*, da.area_name 
              FROM orders o 
              LEFT JOIN delivery_areas da ON o.delivery_area_id = da.id 
              WHERE o.id = $order_id LIMIT 1";
$order_res = mysqli_query($conn, $order_sql);

if (!$order_res || mysqli_num_rows($order_res) === 0) {
    header("Location: index.php");
    exit;
}

$order = mysqli_fetch_assoc($order_res);

// Fetch order items (snapshotted)
$items_sql = "SELECT * FROM order_items WHERE order_id = $order_id";
$items_res = mysqli_query($conn, $items_sql);

$page_title = "Order Confirmed #" . $order_id;
require_once 'includes/header.php';

// Prepare WhatsApp Link with Pre-filled text
$wa_text = urlencode("Salam A1 Kabab, I have placed Order #" . $order['id'] . " for PKR " . number_format($order['total_amount'], 0) . ". Please confirm!");
$wa_url = "https://wa.me/" . WHATSAPP_NUMBER . "?text=" . $wa_text;

$statuses = ['Pending', 'Payment Verified', 'Preparing', 'Out for Delivery', 'Completed'];
$current_index = array_search($order['order_status'], $statuses);
if ($current_index === false) $current_index = 0;
?>

<div class="container" style="padding-top: 35px; padding-bottom: 70px;">
    <!-- Confirmation Banner -->
    <div style="background: var(--color-white); border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 40px 20px; text-align: center; margin-bottom: 30px; box-shadow: var(--shadow-sm);">
        <div style="font-size: 3.5rem; margin-bottom: 12px;">🎉</div>
        <h1 style="font-size: 2.2rem; color: var(--color-black); margin-bottom: 6px; letter-spacing: -0.5px;">Order Confirmed!</h1>
        <p style="color: var(--color-text-muted); font-size: 1.05rem;">
            Thank you, <strong><?php echo e($order['customer_name']); ?></strong>! Your order has been placed successfully.
        </p>

        <div style="margin: 20px 0; display: inline-flex; align-items: center; gap: 12px; background: var(--color-surface); padding: 10px 24px; border-radius: var(--radius-full); border: 1px solid var(--color-border);">
            <span style="color: var(--color-text-muted); font-size: 0.9rem;">Order Number:</span>
            <strong style="color: var(--color-primary); font-size: 1.25rem;">#<?php echo (int)$order['id']; ?></strong>
            <span style="color: var(--color-border);">|</span>
            <span style="background: var(--color-primary); color: #fff; padding: 3px 10px; border-radius: var(--radius-full); font-size: 0.78rem; font-weight: 700; text-transform: uppercase;">
                <?php echo e($order['order_status']); ?>
            </span>
        </div>

        <div style="margin-top: 10px;">
            <a href="<?php echo $wa_url; ?>" target="_blank" class="btn btn-whatsapp" style="font-size: 0.95rem; padding: 12px 24px;">
                💬 Contact Restaurant on WhatsApp (#<?php echo (int)$order['id']; ?>)
            </a>
        </div>
    </div>

    <!-- Status Progress Steps -->
    <div style="background: var(--color-white); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 24px; margin-bottom: 30px; box-shadow: var(--shadow-sm);">
        <h3 style="color: var(--color-black); font-size: 1.15rem; margin-bottom: 16px;">Order Progress</h3>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 12px; text-align: center;">
            <?php foreach ($statuses as $idx => $st): ?>
                <?php 
                $is_passed = ($idx <= $current_index && $order['order_status'] !== 'Cancelled');
                $is_current = ($idx === $current_index && $order['order_status'] !== 'Cancelled');
                ?>
                <div style="background: <?php echo $is_current ? 'var(--color-primary-light)' : ($is_passed ? '#F0FFF4' : 'var(--color-surface)'); ?>; 
                            border: 1px solid <?php echo $is_current ? 'var(--color-primary)' : ($is_passed ? '#C6F6D5' : 'var(--color-border)'); ?>; 
                            padding: 12px 8px; border-radius: var(--radius-sm);">
                    <div style="font-size: 1.2rem; margin-bottom: 4px;">
                        <?php 
                        if ($order['order_status'] === 'Cancelled') echo '❌';
                        elseif ($is_passed) echo '✅'; 
                        else echo '⏳'; 
                        ?>
                    </div>
                    <div style="font-size: 0.82rem; font-weight: 700; color: <?php echo $is_current ? 'var(--color-primary)' : ($is_passed ? '#22543D' : '#888'); ?>;">
                        <?php echo e($st); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($order['order_status'] === 'Cancelled'): ?>
            <div class="alert alert-danger" style="margin-top: 16px; margin-bottom: 0;">
                This order was cancelled. If you have questions or transferred payment, please contact us on WhatsApp.
            </div>
        <?php endif; ?>
    </div>

    <!-- Order Items & Customer Details Grid -->
    <div style="display: grid; grid-template-columns: 1.8fr 1.2fr; gap: 30px;">
        <!-- Items Table -->
        <div style="background: var(--color-white); border: 1px solid var(--color-border); border-radius: var(--radius-md); overflow: hidden; height: fit-content; box-shadow: var(--shadow-sm);">
            <div style="padding: 16px 20px; background: var(--color-surface); border-bottom: 1px solid var(--color-border); font-weight: 700; color: var(--color-black);">
                Ordered Items
            </div>
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead style="border-bottom: 1px solid var(--color-border);">
                    <tr>
                        <th style="padding: 12px 16px; font-size: 0.82rem; text-transform: uppercase; color: var(--color-text-muted);">Dish</th>
                        <th style="padding: 12px 16px; font-size: 0.82rem; text-transform: uppercase; color: var(--color-text-muted);">Qty</th>
                        <th style="padding: 12px 16px; font-size: 0.82rem; text-transform: uppercase; color: var(--color-text-muted);">Price</th>
                        <th style="padding: 12px 16px; font-size: 0.82rem; text-transform: uppercase; color: var(--color-text-muted);">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($item = mysqli_fetch_assoc($items_res)): ?>
                        <tr style="border-bottom: 1px solid var(--color-border-subtle);">
                            <td style="padding: 14px 16px; font-weight: 700; color: var(--color-black);"><?php echo e($item['item_title']); ?></td>
                            <td style="padding: 14px 16px;"><?php echo (int)$item['quantity']; ?></td>
                            <td style="padding: 14px 16px; color: var(--color-text-muted);">PKR <?php echo number_format($item['price'], 0); ?></td>
                            <td style="padding: 14px 16px; font-weight: 800; color: var(--color-primary);">
                                PKR <?php echo number_format($item['price'] * $item['quantity'], 0); ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Details Summary Box -->
        <div class="checkout-breakdown-box" style="margin-bottom: 0;">
            <h3 style="font-size: 1.25rem; color: var(--color-black); margin-bottom: 16px; border-bottom: 1px solid rgba(227, 28, 35, 0.2); padding-bottom: 10px;">Delivery & Bill</h3>

            <div class="checkout-breakdown-row">
                <span>Customer:</span>
                <strong style="color: var(--color-black);"><?php echo e($order['customer_name']); ?></strong>
            </div>

            <div class="checkout-breakdown-row">
                <span>Phone:</span>
                <strong><?php echo e($order['customer_phone']); ?></strong>
            </div>

            <div class="checkout-breakdown-row">
                <span>Area:</span>
                <strong><?php echo e($order['area_name'] ?? 'Scheme 33'); ?></strong>
            </div>

            <div class="checkout-breakdown-row" style="flex-direction: column; gap: 4px;">
                <span>Address:</span>
                <div style="background: #FFF; padding: 10px; border-radius: 4px; border: 1px solid #FFD5D6; font-size: 0.88rem; color: var(--color-text-dark);">
                    <?php echo nl2br(e($order['delivery_address'])); ?>
                </div>
            </div>

            <?php if (!empty($order['order_notes'])): ?>
                <div class="checkout-breakdown-row" style="flex-direction: column; gap: 4px; margin-top: 8px;">
                    <span>Notes:</span>
                    <div style="background: #FFF; padding: 8px; border-radius: 4px; border: 1px solid #FFD5D6; font-size: 0.85rem; color: var(--color-primary);">
                        <?php echo e($order['order_notes']); ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="checkout-breakdown-row" style="border-top: 1px dashed rgba(227, 28, 35, 0.2); padding-top: 10px; margin-top: 12px;">
                <span>Payment Method:</span>
                <strong style="text-transform: uppercase; color: var(--color-primary);"><?php echo e($order['payment_method']); ?></strong>
            </div>

            <?php if (!empty($order['transaction_id'])): ?>
                <div class="checkout-breakdown-row">
                    <span>Transaction ID:</span>
                    <strong><?php echo e($order['transaction_id']); ?></strong>
                </div>
            <?php endif; ?>

            <div class="checkout-breakdown-row" style="margin-top: 10px;">
                <span>Food Subtotal:</span>
                <strong>PKR <?php echo number_format($order['subtotal'], 0); ?></strong>
            </div>

            <div class="checkout-breakdown-row">
                <span>Delivery Fee:</span>
                <strong>PKR <?php echo number_format($order['delivery_fee'], 0); ?></strong>
            </div>

            <div class="checkout-breakdown-row grand-total">
                <span>Grand Total:</span>
                <span class="total-amount-red">PKR <?php echo number_format($order['total_amount'], 0); ?></span>
            </div>

            <div style="margin-top: 20px;">
                <a href="menu.php" class="btn btn-secondary" style="width: 100%;">Order Again &rarr;</a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
