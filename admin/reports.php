<?php
// admin/reports.php - Sales & Orders Reports
$page_title = "Reports";
require_once __DIR__ . '/header.php';

date_default_timezone_set('Asia/Karachi');
$today = date('Y-m-d');

// --- Date range resolution ---
$range       = clean($conn, $_GET['range'] ?? 'today');
$custom_from = clean($conn, $_GET['from']  ?? '');
$custom_to   = clean($conn, $_GET['to']    ?? '');

switch ($range) {
    case 'yesterday':
        $date_from = date('Y-m-d', strtotime('-1 day'));
        $date_to   = $date_from;
        break;
    case 'last7':
        $date_from = date('Y-m-d', strtotime('-6 days'));
        $date_to   = $today;
        break;
    case 'thismonth':
        $date_from = date('Y-m-01');
        $date_to   = $today;
        break;
    case 'lastmonth':
        $date_from = date('Y-m-01', strtotime('first day of last month'));
        $date_to   = date('Y-m-t',  strtotime('last month'));
        break;
    case 'thisyear':
        $date_from = date('Y-01-01');
        $date_to   = $today;
        break;
    case 'custom':
        $date_from = (preg_match('/^\d{4}-\d{2}-\d{2}$/', $custom_from)) ? $custom_from : $today;
        $date_to   = (preg_match('/^\d{4}-\d{2}-\d{2}$/', $custom_to))   ? $custom_to   : $today;
        if ($date_from > $date_to) { $tmp = $date_from; $date_from = $date_to; $date_to = $tmp; }
        break;
    default:
        $range     = 'today';
        $date_from = $today;
        $date_to   = $today;
}

$date_cond = "DATE(o.created_at) BETWEEN '$date_from' AND '$date_to'";

// --- Stat Cards ---
$res = mysqli_query($conn, "SELECT COUNT(*) AS c FROM orders o WHERE $date_cond");
$total_orders = mysqli_fetch_assoc($res)['c'] ?? 0;

$res = mysqli_query($conn, "SELECT COALESCE(SUM(o.total_amount),0) AS rev FROM orders o WHERE $date_cond AND o.order_status != 'Cancelled'");
$total_revenue = mysqli_fetch_assoc($res)['rev'] ?? 0;

$res = mysqli_query($conn, "SELECT COALESCE(AVG(o.total_amount),0) AS avg_val FROM orders o WHERE $date_cond AND o.order_status != 'Cancelled'");
$avg_value = mysqli_fetch_assoc($res)['avg_val'] ?? 0;

$res = mysqli_query($conn, "SELECT COUNT(*) AS c FROM orders o WHERE $date_cond AND o.order_status = 'Cancelled'");
$cancelled_count = mysqli_fetch_assoc($res)['c'] ?? 0;

// --- Orders table (max 200 rows) ---
$orders_res = mysqli_query($conn,
    "SELECT o.*, da.area_name
     FROM orders o
     LEFT JOIN delivery_areas da ON o.delivery_area_id = da.id
     WHERE $date_cond
     ORDER BY o.id DESC
     LIMIT 200"
);

// --- Best-selling items ---
$best_res = mysqli_query($conn,
    "SELECT oi.item_id, oi.item_title,
            SUM(oi.quantity) AS total_qty,
            SUM(oi.quantity * oi.price) AS total_revenue
     FROM order_items oi
     JOIN orders o ON o.id = oi.order_id
     WHERE o.order_status != 'Cancelled' AND $date_cond
     GROUP BY oi.item_id, oi.item_title
     ORDER BY total_qty DESC
     LIMIT 10"
);

// --- Slow-moving items (0-sales included) ---
$slow_res = mysqli_query($conn,
    "SELECT i.id AS item_id, i.title AS item_title,
            COALESCE(SUM(oi_sub.quantity),0) AS total_qty,
            COALESCE(SUM(oi_sub.quantity * oi_sub.price),0) AS total_revenue
     FROM items i
     LEFT JOIN (
         SELECT oi.item_id, oi.quantity, oi.price
         FROM order_items oi
         JOIN orders o ON o.id = oi.order_id
         WHERE o.order_status != 'Cancelled' AND $date_cond
     ) oi_sub ON oi_sub.item_id = i.id
     WHERE i.is_available = 1
     GROUP BY i.id, i.title
     ORDER BY total_qty ASC, i.title ASC
     LIMIT 10"
);

// --- Readable label ---
$range_labels = [
    'today'     => 'Today (' . date('d M Y') . ')',
    'yesterday' => 'Yesterday (' . date('d M Y', strtotime('-1 day')) . ')',
    'last7'     => 'Last 7 Days',
    'thismonth' => 'This Month (' . date('F Y') . ')',
    'lastmonth' => 'Last Month (' . date('F Y', strtotime('last month')) . ')',
    'thisyear'  => 'This Year (' . date('Y') . ')',
    'custom'    => 'Custom: ' . date('d M Y', strtotime($date_from)) . ' - ' . date('d M Y', strtotime($date_to)),
];
$range_label = $range_labels[$range] ?? 'Today';

function report_badge(string $status): string {
    $map = [
        'Pending'          => 'badge-pending',
        'Payment Verified' => 'badge-verified',
        'Preparing'        => 'badge-preparing',
        'Out for Delivery' => 'badge-out',
        'Completed'        => 'badge-completed',
        'Cancelled'        => 'badge-cancelled',
    ];
    $cls = $map[$status] ?? 'badge-pending';
    return '<span class="badge ' . $cls . '">' . htmlspecialchars($status, ENT_QUOTES) . '</span>';
}
?>

<!-- Date Range Pill Tabs -->
<div style="display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin-bottom:20px;">
<?php
$pills = [
    'today'     => 'Today',
    'yesterday' => 'Yesterday',
    'last7'     => 'Last 7 Days',
    'thismonth' => 'This Month',
    'lastmonth' => 'Last Month',
    'thisyear'  => 'This Year',
    'custom'    => '&#128197; Custom Range',
];
foreach ($pills as $val => $label):
    $active_style = ($range === $val)
        ? 'background:var(--admin-primary);color:#fff;border-color:var(--admin-primary);'
        : 'background:#fff;color:var(--admin-text-dark);border-color:var(--admin-border);';
    $href = 'reports.php?range=' . $val;
    if ($val === 'custom') $href .= '&from=' . urlencode($custom_from) . '&to=' . urlencode($custom_to);
?>
    <a href="<?php echo $href; ?>"
       style="<?php echo $active_style; ?>border:1px solid;padding:6px 14px;border-radius:999px;font-size:0.82rem;font-weight:600;text-decoration:none;transition:all .15s;">
        <?php echo $label; ?>
    </a>
<?php endforeach; ?>
</div>

<?php if ($range === 'custom'): ?>
<div style="background:#fff;border:1px solid var(--admin-border);border-radius:var(--radius-sm);padding:14px 18px;margin-bottom:20px;">
    <form method="GET" action="reports.php" style="display:flex;flex-wrap:wrap;align-items:flex-end;gap:12px;">
        <input type="hidden" name="range" value="custom">
        <div>
            <label class="admin-form-label" style="font-size:0.8rem;margin-bottom:4px;display:block;">From</label>
            <input type="date" name="from" class="admin-form-control" style="width:170px;" value="<?php echo e($date_from); ?>">
        </div>
        <div>
            <label class="admin-form-label" style="font-size:0.8rem;margin-bottom:4px;display:block;">To</label>
            <input type="date" name="to" class="admin-form-control" style="width:170px;" value="<?php echo e($date_to); ?>">
        </div>
        <button type="submit" class="btn-admin btn-admin-primary" style="padding:8px 20px;">Apply</button>
    </form>
</div>
<?php endif; ?>

<!-- Stat Cards -->
<div class="stat-cards-grid" style="margin-bottom:24px;">
    <div class="admin-stat-card">
        <div class="admin-stat-label">Total Orders</div>
        <div class="admin-stat-value"><?php echo number_format($total_orders); ?></div>
        <div class="admin-stat-sub"><?php echo e($range_label); ?></div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-label">Total Revenue</div>
        <div class="admin-stat-value">Rs.&nbsp;<?php echo number_format($total_revenue, 0); ?></div>
        <div class="admin-stat-sub">Excl. cancelled</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-label">Avg Order Value</div>
        <div class="admin-stat-value">Rs.&nbsp;<?php echo number_format($avg_value, 0); ?></div>
        <div class="admin-stat-sub">Excl. cancelled</div>
    </div>
    <div class="admin-stat-card alert-card">
        <div class="admin-stat-label">Cancelled Orders</div>
        <div class="admin-stat-value"><?php echo number_format($cancelled_count); ?></div>
        <div class="admin-stat-sub"><?php echo e($range_label); ?></div>
    </div>
</div>

<!-- Orders Table -->
<div class="admin-card" style="padding:0;overflow:hidden;margin-bottom:28px;">
    <div class="admin-card-header" style="padding:14px 20px;border-bottom:1px solid var(--admin-border);background:#FAFAFA;">
        <span class="admin-card-title" style="font-size:0.95rem;">
            Orders &mdash; <?php echo e($range_label); ?>
            <span style="font-weight:400;color:var(--admin-text-muted);font-size:0.82rem;margin-left:8px;">
                (<?php echo $total_orders; ?> total<?php echo ($total_orders > 200) ? ', showing first 200' : ''; ?>)
            </span>
        </span>
    </div>
    <div class="admin-table-container" style="border:none;border-radius:0;">
        <table class="admin-data-table">
            <thead>
                <tr>
                    <th style="width:85px;">Order #</th>
                    <th>Customer Name</th>
                    <th>Phone</th>
                    <th>Payment</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Time Placed</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($orders_res && mysqli_num_rows($orders_res) > 0):
                while ($row = mysqli_fetch_assoc($orders_res)): ?>
                <tr class="clickable-row" data-href="order_detail.php?id=<?php echo (int)$row['id']; ?>">
                    <td style="font-weight:700;">#<?php echo (int)$row['id']; ?></td>
                    <td style="font-weight:600;"><?php echo e($row['customer_name']); ?></td>
                    <td><?php echo e($row['customer_phone']); ?></td>
                    <td>
                        <span style="text-transform:uppercase;font-size:0.78rem;font-weight:600;color:#555;">
                            <?php echo e($row['payment_method']); ?>
                        </span>
                    </td>
                    <td style="font-weight:700;color:var(--admin-text-dark);">
                        Rs. <?php echo number_format($row['total_amount'], 0); ?>
                    </td>
                    <td><?php echo report_badge($row['order_status']); ?></td>
                    <td style="color:var(--admin-text-muted);font-size:0.82rem;white-space:nowrap;">
                        <?php echo date('d M, h:i A', strtotime($row['created_at'])); ?>
                    </td>
                </tr>
            <?php endwhile; else: ?>
                <tr>
                    <td colspan="7" style="text-align:center;padding:45px;color:var(--admin-text-muted);">
                        No orders found for this period.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Best-Selling vs Slow-Moving -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:28px;">

    <div class="admin-card" style="padding:0;overflow:hidden;">
        <div class="admin-card-header" style="padding:14px 20px;border-bottom:1px solid var(--admin-border);background:#FAFAFA;">
            <span class="admin-card-title" style="font-size:0.92rem;">&#128293; Best-Selling Items</span>
            <span style="font-size:0.78rem;color:var(--admin-text-muted);margin-left:6px;">Top 10</span>
        </div>
        <div class="admin-table-container" style="border:none;border-radius:0;">
            <table class="admin-data-table">
                <thead>
                    <tr>
                        <th style="width:28px;">#</th>
                        <th>Item</th>
                        <th style="text-align:right;">Qty</th>
                        <th style="text-align:right;">Revenue</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($best_res && mysqli_num_rows($best_res) > 0):
                    $rank = 1;
                    while ($row = mysqli_fetch_assoc($best_res)): ?>
                    <tr>
                        <td style="font-weight:700;color:var(--admin-primary);"><?php echo $rank++; ?></td>
                        <td style="font-weight:600;"><?php echo e($row['item_title']); ?></td>
                        <td style="text-align:right;font-weight:700;"><?php echo number_format($row['total_qty']); ?></td>
                        <td style="text-align:right;color:var(--admin-text-muted);font-size:0.82rem;">Rs.&nbsp;<?php echo number_format($row['total_revenue'], 0); ?></td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="4" style="text-align:center;padding:30px;color:var(--admin-text-muted);font-size:0.85rem;">No sales data for this period.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="admin-card" style="padding:0;overflow:hidden;">
        <div class="admin-card-header" style="padding:14px 20px;border-bottom:1px solid var(--admin-border);background:#FAFAFA;">
            <span class="admin-card-title" style="font-size:0.92rem;">&#128034; Slow-Moving Items</span>
            <span style="font-size:0.78rem;color:var(--admin-text-muted);margin-left:6px;">Bottom 10 (incl. 0 sales)</span>
        </div>
        <div class="admin-table-container" style="border:none;border-radius:0;">
            <table class="admin-data-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th style="text-align:right;">Qty</th>
                        <th style="text-align:right;">Revenue</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($slow_res && mysqli_num_rows($slow_res) > 0):
                    while ($row = mysqli_fetch_assoc($slow_res)): ?>
                    <tr>
                        <td style="font-weight:600;"><?php echo e($row['item_title']); ?></td>
                        <td style="text-align:right;">
                            <?php if ($row['total_qty'] == 0): ?>
                                <span style="color:#bbb;font-size:0.8rem;">0</span>
                            <?php else: ?>
                                <?php echo number_format($row['total_qty']); ?>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:right;color:var(--admin-text-muted);font-size:0.82rem;">Rs.&nbsp;<?php echo number_format($row['total_revenue'], 0); ?></td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="3" style="text-align:center;padding:30px;color:var(--admin-text-muted);font-size:0.85rem;">No available items found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<style>
@media (max-width: 820px) {
    div[style*="grid-template-columns:1fr 1fr"] { grid-template-columns: 1fr !important; }
}
</style>

<?php require_once __DIR__ . '/footer.php'; ?>
