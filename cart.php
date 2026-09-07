<?php
// cart.php - Full Cart Overview Page (Synced with Drawer)
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/cart_functions.php';

// Handle any direct actions if triggered via standard POST/GET
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

if ($action === 'add') {
    $item_id = clean_int($_GET['id'] ?? 0);
    if ($item_id > 0) cart_add($item_id, 1);
    header("Location: cart.php");
    exit;
}

if ($action === 'remove') {
    $item_id = clean_int($_GET['id'] ?? 0);
    if ($item_id > 0) cart_remove($item_id);
    header("Location: cart.php");
    exit;
}

if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['qty']) && is_array($_POST['qty'])) {
        foreach ($_POST['qty'] as $id => $quantity) {
            cart_update(clean_int($id), clean_int($quantity));
        }
    }
    header("Location: cart.php?updated=1");
    exit;
}

if ($action === 'clear') {
    cart_clear();
    header("Location: cart.php");
    exit;
}

$page_title = "Your Cart";
require_once 'includes/header.php';

$cart_items = cart_get_items_details($conn);
$subtotal = 0.0;
foreach ($cart_items as $ci) {
    $subtotal += $ci['line_total'];
}

$min_order = defined('MIN_ORDER_AMOUNT') ? MIN_ORDER_AMOUNT : 500.0;
$meets_min_order = ($subtotal >= $min_order);
$amount_needed = max(0, $min_order - $subtotal);
?>

<div class="container" style="padding-top: 35px; padding-bottom: 70px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h1 style="font-size: 2rem; color: var(--color-black);">Your Cart</h1>
        <button type="button" class="btn btn-secondary cart-open-trigger" style="font-size: 0.88rem; padding: 8px 16px;">
            Open Cart Drawer &rarr;
        </button>
    </div>

    <?php if (empty($cart_items)): ?>
        <div style="background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 60px 20px; text-align: center;">
            <div style="font-size: 3.5rem; margin-bottom: 14px;">🛒</div>
            <h2 style="color: var(--color-black); font-size: 1.5rem; margin-bottom: 8px;">Your Cart is Empty</h2>
            <p style="color: var(--color-text-muted); margin-bottom: 24px;">Explore our authentic tawa chapli kababs and hot roghani naans!</p>
            <a href="menu.php" class="btn btn-primary">Browse Menu</a>
        </div>
    <?php else: ?>
        <div class="checkout-two-col" style="margin-top: 0;">
            <div>
                <form action="cart.php" method="POST">
                    <input type="hidden" name="action" value="update">
                    
                    <div style="background: var(--color-white); border: 1px solid var(--color-border); border-radius: var(--radius-md); overflow: hidden;">
                        <table style="width: 100%; border-collapse: collapse; text-align: left;">
                            <thead style="background: var(--color-surface); border-bottom: 1px solid var(--color-border);">
                                <tr>
                                    <th style="padding: 12px 16px; font-size: 0.85rem; text-transform: uppercase; color: var(--color-text-muted);">Dish</th>
                                    <th style="padding: 12px 16px; font-size: 0.85rem; text-transform: uppercase; color: var(--color-text-muted);">Price</th>
                                    <th style="padding: 12px 16px; font-size: 0.85rem; text-transform: uppercase; color: var(--color-text-muted); width: 120px;">Qty</th>
                                    <th style="padding: 12px 16px; font-size: 0.85rem; text-transform: uppercase; color: var(--color-text-muted);">Total</th>
                                    <th style="padding: 12px 16px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart_items as $item): ?>
                                    <tr style="border-bottom: 1px solid var(--color-border-subtle);">
                                        <td style="padding: 14px 16px;">
                                            <div style="display: flex; align-items: center; gap: 12px;">
                                                <img src="assets/uploads/items/<?php echo e($item['image_url']); ?>" alt="<?php echo e($item['title']); ?>" style="width: 50px; height: 45px; object-fit: cover; border-radius: 4px;">
                                                <span style="font-weight: 700; color: var(--color-black);"><?php echo e($item['title']); ?></span>
                                            </div>
                                        </td>
                                        <td style="padding: 14px 16px; color: var(--color-text-muted);">PKR <?php echo number_format($item['price'], 0); ?></td>
                                        <td style="padding: 14px 16px;">
                                            <input type="number" name="qty[<?php echo (int)$item['id']; ?>]" value="<?php echo (int)$item['cart_quantity']; ?>" min="1" max="50" class="form-control" style="width: 60px; padding: 6px; text-align: center; height: 34px;">
                                        </td>
                                        <td style="padding: 14px 16px; font-weight: 800; color: var(--color-primary);">PKR <?php echo number_format($item['line_total'], 0); ?></td>
                                        <td style="padding: 14px 16px; text-align: right;">
                                            <a href="cart.php?action=remove&id=<?php echo (int)$item['id']; ?>" style="color: #999; font-size: 1.2rem; font-weight: bold;" title="Remove">✕</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div style="display: flex; justify-content: space-between; margin-top: 16px;">
                        <button type="submit" class="btn btn-secondary" style="padding: 8px 16px; font-size: 0.88rem;">Update Quantities</button>
                        <a href="cart.php?action=clear" style="color: #999; font-size: 0.85rem; align-self: center;" onclick="return confirm('Empty cart?');">Empty Cart</a>
                    </div>
                </form>
            </div>

            <!-- Summary -->
            <div>
                <div class="checkout-breakdown-box">
                    <h3 style="font-size: 1.2rem; color: var(--color-black); margin-bottom: 14px;">Summary</h3>
                    <div class="checkout-breakdown-row">
                        <span>Items Subtotal</span>
                        <strong>PKR <?php echo number_format($subtotal, 0); ?></strong>
                    </div>
                    <div class="checkout-breakdown-row">
                        <span>Delivery Fee</span>
                        <span style="color: var(--color-text-muted);">Calculated at checkout</span>
                    </div>
                    <div class="checkout-breakdown-row grand-total">
                        <span>Total</span>
                        <span class="total-amount-red">PKR <?php echo number_format($subtotal, 0); ?></span>
                    </div>

                    <?php if (!$meets_min_order): ?>
                        <div class="alert alert-warning" style="margin-top: 14px; font-size: 0.82rem;">
                            ⚠️ Minimum order amount is PKR <?php echo number_format($min_order, 0); ?>. Add PKR <?php echo number_format($amount_needed, 0); ?> more.
                        </div>
                    <?php else: ?>
                        <div style="margin-top: 18px;">
                            <a href="checkout.php" class="btn btn-primary" style="width: 100%; padding: 14px; font-size: 1.05rem;">
                                Proceed to Checkout &rarr;
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
