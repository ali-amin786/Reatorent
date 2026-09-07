<?php
// admin/order_detail.php - Single-Screen Order Detail & Top Status Changer
$order_id = isset($_GET['id']) ? clean_int($_GET['id']) : 0;
if ($order_id <= 0) {
    header("Location: orders.php");
    exit;
}

$page_title = "Order #" . $order_id;
require_once __DIR__ . '/header.php';

$msg = "";
$error = "";

// 1. Handle Status Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Security token mismatch. Please try again.";
    } else {
        $new_status = clean($conn, $_POST['order_status'] ?? '');
        $allowed = ['Pending', 'Payment Verified', 'Preparing', 'Out for Delivery', 'Completed', 'Cancelled'];

        if (in_array($new_status, $allowed)) {
            $cur_q = mysqli_query($conn, "SELECT order_status FROM orders WHERE id = $order_id LIMIT 1");
            $cur_row = mysqli_fetch_assoc($cur_q);
            $old_status = $cur_row['order_status'] ?? 'Pending';

            if ($old_status !== $new_status) {
                $upd_sql = "UPDATE orders SET order_status = '$new_status' WHERE id = $order_id";
                if (mysqli_query($conn, $upd_sql)) {
                    $staff_user = clean($conn, $_SESSION['admin_username'] ?? 'Staff');
                    $log_sql = "INSERT INTO order_status_log (order_id, old_status, new_status, changed_by) 
                                VALUES ($order_id, '$old_status', '$new_status', '$staff_user')";
                    mysqli_query($conn, $log_sql);
                    $msg = "Status successfully updated to <strong>$new_status</strong>.";
                } else {
                    $error = "Failed to update status.";
                }
            }
        }
    }
}

// 2. Fetch Order Data
$sql = "SELECT o.*, da.area_name 
        FROM orders o 
        LEFT JOIN delivery_areas da ON o.delivery_area_id = da.id 
        WHERE o.id = $order_id LIMIT 1";
$res = mysqli_query($conn, $sql);
if (!$res || mysqli_num_rows($res) === 0) {
    echo "<div class='admin-card'>Order not found. <a href='orders.php'>Back to orders</a></div>";
    require_once __DIR__ . '/footer.php';
    exit;
}
$order = mysqli_fetch_assoc($res);

// 3. Fetch Items
$items_res = mysqli_query($conn, "SELECT * FROM order_items WHERE order_id = $order_id");

// 4. Fetch Audit Log
$log_res = mysqli_query($conn, "SELECT * FROM order_status_log WHERE order_id = $order_id ORDER BY id DESC");

$clean_phone = preg_replace('/[^0-9]/', '', $order['customer_phone']);
$wa_url = "https://wa.me/92" . ltrim($clean_phone, '0') . "?text=Salam%20" . urlencode($order['customer_name']) . "%2C%20regarding%20your%20Order%20%23" . $order['id'];

// Status Badge Class
$status = $order['order_status'];
$badge_class = 'badge-pending';
if ($status === 'Payment Verified') $badge_class = 'badge-verified';
elseif ($status === 'Preparing') $badge_class = 'badge-preparing';
elseif ($status === 'Out for Delivery') $badge_class = 'badge-out';
elseif ($status === 'Completed') $badge_class = 'badge-completed';
elseif ($status === 'Cancelled') $badge_class = 'badge-cancelled';

$all_statuses = ['Pending', 'Payment Verified', 'Preparing', 'Out for Delivery', 'Completed', 'Cancelled'];
?>

<!-- Breadcrumb -->
<div style="margin-bottom: 16px;">
    <a href="orders.php" style="color: var(--admin-text-muted); font-size: 0.85rem;">&larr; Back to all orders</a>
</div>

<?php if (!empty($msg)): ?>
    <div style="background: var(--admin-success-light); border: 1px solid rgba(30,142,62,0.3); color: var(--admin-success); padding: 12px 16px; border-radius: var(--radius-sm); margin-bottom: 18px; font-weight: 500;">
        <?php echo $msg; ?>
    </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div style="background: var(--admin-primary-light); border: 1px solid rgba(227,28,35,0.3); color: var(--admin-primary); padding: 12px 16px; border-radius: var(--radius-sm); margin-bottom: 18px; font-weight: 500;">
        <?php echo e($error); ?>
    </div>
<?php endif; ?>

<!-- TOP CARD: Order Header + Prominent Status Changer -->
<div class="admin-card" style="border-top: 4px solid var(--admin-primary); padding: 20px 24px;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div>
            <div style="display: flex; align-items: center; gap: 12px;">
                <h2 style="font-size: 1.4rem; margin: 0;">Order #<?php echo (int)$order['id']; ?></h2>
                <span class="badge <?php echo $badge_class; ?>" style="font-size: 0.85rem; padding: 4px 12px;">
                    <?php echo e($status); ?>
                </span>
            </div>
            <div style="font-size: 0.82rem; color: var(--admin-text-muted); margin-top: 4px;">
                Placed on <?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?>
            </div>
        </div>

        <!-- Prominent Status Changer (Impossible to Miss) -->
        <form method="POST" action="order_detail.php?id=<?php echo $order_id; ?>" style="display: flex; align-items: center; gap: 10px;">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="update_status" value="1">
            
            <label for="order_status" style="font-size: 0.85rem; font-weight: 600; color: var(--admin-text-dark); white-space: nowrap;">Change Status:</label>
            <select name="order_status" id="order_status" class="admin-form-control" style="width: auto; font-weight: 600; padding: 8px 12px;">
                <?php foreach ($all_statuses as $st): ?>
                    <option value="<?php echo e($st); ?>" <?php echo ($status === $st) ? 'selected' : ''; ?>>
                        <?php echo e($st); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-admin btn-admin-primary" style="padding: 8px 16px;">
                Update Status
            </button>
        </form>
    </div>
</div>

<!-- 2-Column Content Layout -->
<div style="display: grid; grid-template-columns: 1.3fr 1fr; gap: 24px; align-items: start;">
    <!-- Left Column: Customer Details & Ordered Items -->
    <div>
        <!-- Customer & Delivery Block -->
        <div class="admin-card">
            <h3 style="font-size: 1.05rem; margin-bottom: 14px; border-bottom: 1px solid var(--admin-border); padding-bottom: 10px;">
                👤 Customer Details
            </h3>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                <div>
                    <div style="font-size: 0.78rem; color: var(--admin-text-muted); text-transform: uppercase;">Customer Name</div>
                    <div style="font-weight: 600; font-size: 1rem; color: var(--admin-text-dark);"><?php echo e($order['customer_name']); ?></div>
                </div>

                <div>
                    <div style="font-size: 0.78rem; color: var(--admin-text-muted); text-transform: uppercase;">Phone Number</div>
                    <div style="display: flex; align-items: center; gap: 8px; margin-top: 2px;">
                        <a href="tel:<?php echo e($order['customer_phone']); ?>" style="font-weight: 600; color: inherit; text-decoration: underline;">
                            <?php echo e($order['customer_phone']); ?>
                        </a>
                        <a href="<?php echo $wa_url; ?>" target="_blank" class="btn-admin btn-admin-secondary" style="padding: 2px 8px; font-size: 0.75rem;">
                            💬 WhatsApp
                        </a>
                    </div>
                </div>
            </div>

            <div style="margin-bottom: 14px;">
                <div style="font-size: 0.78rem; color: var(--admin-text-muted); text-transform: uppercase;">Sector / Delivery Area</div>
                <div style="font-weight: 600;"><?php echo e($order['area_name'] ?? 'Karachi Sector'); ?></div>
            </div>

            <div style="margin-bottom: 14px;">
                <div style="font-size: 0.78rem; color: var(--admin-text-muted); text-transform: uppercase;">Full Delivery Address</div>
                <div style="background: var(--admin-bg); padding: 10px 12px; border-radius: var(--radius-sm); border: 1px solid var(--admin-border); margin-top: 4px; font-size: 0.9rem;">
                    <?php echo nl2br(e($order['delivery_address'])); ?>
                </div>
            </div>

            <?php if (!empty($order['order_notes'])): ?>
                <div>
                    <div style="font-size: 0.78rem; color: var(--admin-primary); font-weight: 600; text-transform: uppercase;">Cooking / Delivery Instructions</div>
                    <div style="background: var(--admin-primary-light); padding: 8px 12px; border-radius: var(--radius-sm); border: 1px solid rgba(227,28,35,0.25); color: var(--admin-text-dark); margin-top: 4px; font-size: 0.88rem;">
                        <?php echo e($order['order_notes']); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Ordered Items Table (Snapshotted at Order Time) -->
        <div class="admin-card" style="padding: 0; overflow: hidden;">
            <div style="padding: 14px 20px; background: #FAFAFA; border-bottom: 1px solid var(--admin-border); font-weight: 600; font-size: 0.95rem;">
                🍽️ Items Ordered
            </div>
            <table class="admin-data-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th style="text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($item = mysqli_fetch_assoc($items_res)): ?>
                        <tr>
                            <td style="font-weight: 600;"><?php echo e($item['item_title']); ?></td>
                            <td><?php echo (int)$item['quantity']; ?></td>
                            <td style="color: var(--admin-text-muted);">Rs. <?php echo number_format($item['price'], 0); ?></td>
                            <td style="text-align: right; font-weight: 600;">
                                Rs. <?php echo number_format($item['price'] * $item['quantity'], 0); ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <!-- Financials Summary -->
            <div style="padding: 16px 20px; background: #FAFAFA; border-top: 1px solid var(--admin-border);">
                <div style="display: flex; justify-content: space-between; font-size: 0.9rem; margin-bottom: 6px;">
                    <span style="color: var(--admin-text-muted);">Food Subtotal:</span>
                    <span>Rs. <?php echo number_format($order['subtotal'], 0); ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.9rem; margin-bottom: 10px;">
                    <span style="color: var(--admin-text-muted);">Delivery Fee:</span>
                    <span>Rs. <?php echo number_format($order['delivery_fee'], 0); ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 1.25rem; font-weight: 700; border-top: 1px solid var(--admin-border); padding-top: 10px; color: var(--admin-text-dark);">
                    <span>Grand Total:</span>
                    <span style="color: var(--admin-primary);">Rs. <?php echo number_format($order['total_amount'], 0); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Payment & Audit Trail -->
    <div>
        <!-- Payment Block -->
        <div class="admin-card">
            <h3 style="font-size: 1.05rem; margin-bottom: 14px; border-bottom: 1px solid var(--admin-border); padding-bottom: 10px;">
                💳 Payment Information
            </h3>

            <div style="margin-bottom: 12px;">
                <span style="font-size: 0.78rem; color: var(--admin-text-muted); text-transform: uppercase;">Payment Method:</span>
                <div style="font-weight: 700; text-transform: uppercase; font-size: 1rem; color: var(--admin-text-dark); margin-top: 2px;">
                    <?php echo e($order['payment_method']); ?>
                </div>
            </div>

            <?php if ($order['payment_method'] === 'cod'): ?>
                <div style="background: var(--admin-bg); padding: 12px; border-radius: var(--radius-sm); font-size: 0.88rem; color: var(--admin-text-muted);">
                    💵 Cash on Delivery — rider will collect Rs. <?php echo number_format($order['total_amount'], 0); ?> at doorstep.
                </div>
            <?php else: ?>
                <div style="margin-bottom: 14px;">
                    <span style="font-size: 0.78rem; color: var(--admin-text-muted); text-transform: uppercase;">Transaction ID / Ref:</span>
                    <div style="font-weight: 600; font-size: 1rem; color: var(--admin-primary); margin-top: 2px;">
                        <?php echo !empty($order['transaction_id']) ? e($order['transaction_id']) : '<em style="color:#888;">None provided</em>'; ?>
                    </div>
                </div>

                <div>
                    <span style="font-size: 0.78rem; color: var(--admin-text-muted); text-transform: uppercase;">Receipt Screenshot:</span>
                    <?php if (!empty($order['payment_proof'])): ?>
                        <div style="margin-top: 8px;">
                            <img src="../assets/uploads/payments/<?php echo e($order['payment_proof']); ?>" 
                                 alt="Payment Proof" 
                                 class="zoomable-receipt" 
                                 title="Click to view full image in new tab"
                                 style="max-height: 200px; border-radius: 6px; border: 1px solid var(--admin-border); cursor: zoom-in; display: block;">
                            <span style="font-size: 0.75rem; color: var(--admin-text-muted); margin-top: 4px; display: block;">
                                🔍 Click image to open full resolution
                            </span>
                        </div>
                    <?php else: ?>
                        <div style="color: #888; font-size: 0.85rem; margin-top: 4px;">No screenshot uploaded.</div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Audit Trail Block -->
        <div class="admin-card">
            <h3 style="font-size: 1.05rem; margin-bottom: 14px; border-bottom: 1px solid var(--admin-border); padding-bottom: 10px;">
                ⏱️ Status History
            </h3>

            <?php if ($log_res && mysqli_num_rows($log_res) > 0): ?>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <?php while ($log = mysqli_fetch_assoc($log_res)): ?>
                        <div style="font-size: 0.85rem; border-left: 3px solid var(--admin-primary); padding-left: 10px;">
                            <div>
                                <?php if (!empty($log['old_status'])): ?>
                                    <span style="color: var(--admin-text-muted);"><?php echo e($log['old_status']); ?> &rarr;</span>
                                <?php endif; ?>
                                <strong><?php echo e($log['new_status']); ?></strong>
                            </div>
                            <div style="font-size: 0.75rem; color: var(--admin-text-muted); margin-top: 2px;">
                                By <?php echo e($log['changed_by'] ?? 'Staff'); ?> on <?php echo date('d M, h:i A', strtotime($log['changed_at'])); ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div style="color: var(--admin-text-muted); font-size: 0.85rem;">No history log recorded yet.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
