<?php
// includes/cart_drawer.php - Global Slide-In Cart Drawer & Floating Bar
?>

<!-- Cart Drawer Backdrop -->
<div id="cart-drawer-backdrop" class="cart-drawer-backdrop"></div>

<!-- Slide-in Cart Drawer -->
<aside id="cart-drawer" class="cart-drawer" aria-label="Shopping Cart Drawer" aria-hidden="true">
    <div class="cart-drawer-inner">
        <!-- Drawer Header -->
        <div class="drawer-header">
            <div class="drawer-header-title">
                <span class="drawer-icon">🛒</span>
                <h3>Your Cart</h3>
                <span class="drawer-count-badge" id="drawer-item-count">0</span>
            </div>
            <button type="button" class="drawer-close-btn" id="close-cart-drawer-btn" aria-label="Close cart">&times;</button>
        </div>

        <!-- Drawer Content Area -->
        <div class="drawer-body">
            <!-- Empty State -->
            <div id="drawer-empty-state" class="drawer-empty-state" style="display: none;">
                <div class="empty-icon">🍽️</div>
                <h4>Your cart is empty</h4>
                <p>Add some sizzling chapli kabab, hot tandoori roghani naan, or cold drinks!</p>
                <button type="button" class="btn btn-primary close-drawer-action" style="margin-top: 15px;">Browse Menu</button>
            </div>

            <!-- Active Cart Items List -->
            <div id="drawer-items-container" class="drawer-items-list">
                <!-- Dynamically populated via main.js -->
            </div>

            <!-- Upsell Recommendations ("Popular with your order") -->
            <div id="drawer-upsell-wrapper" class="drawer-upsell-section">
                <div class="upsell-heading">🔥 Popular with your order</div>
                <div class="upsell-row" id="drawer-upsell-items">
                    <!-- Populated via AJAX -->
                </div>
            </div>
        </div>

        <!-- Drawer Footer Pinned to Bottom -->
        <div class="drawer-footer" id="drawer-footer-block">
            <div class="drawer-add-more-row">
                <button type="button" class="add-more-link close-drawer-action">&larr; Add more items</button>
            </div>

            <div class="drawer-breakdown">
                <div class="breakdown-row">
                    <span>Subtotal</span>
                    <span id="drawer-subtotal-val" class="breakdown-val">PKR 0</span>
                </div>
                <div class="breakdown-row delivery-row">
                    <span>Delivery Fee</span>
                    <span class="text-muted">Calculated at checkout</span>
                </div>
                <div class="breakdown-row grand-total-row">
                    <span>Total</span>
                    <span id="drawer-grand-total-val" class="grand-total-val">PKR 0</span>
                </div>
            </div>

            <!-- Minimum order alert -->
            <div id="drawer-min-order-alert" class="drawer-min-alert" style="display: none;">
                ⚠️ Min delivery order is <strong>PKR 500</strong>. Add <span id="drawer-min-needed">PKR 0</span> more.
            </div>

            <a href="checkout.php" id="drawer-checkout-btn" class="btn btn-primary drawer-checkout-btn">
                <span>Proceed to Checkout</span>
                <span class="btn-arrow">&rarr;</span>
            </a>
        </div>
    </div>
</aside>

<!-- Floating Mini-Cart Pill Bar (Visible when items in cart) -->
<div id="floating-cart-bar" class="floating-cart-bar" style="display: none;">
    <div class="floating-cart-content" id="floating-cart-trigger">
        <div class="floating-cart-left">
            <span class="floating-cart-badge" id="floating-cart-count">0</span>
            <div class="floating-cart-text">
                <span class="floating-label">View Order</span>
                <span class="floating-total" id="floating-cart-total">PKR 0</span>
            </div>
        </div>
        <button type="button" class="floating-cart-btn">
            View Cart &rarr;
        </button>
    </div>
</div>

<!-- Toast Notification Container -->
<div id="cart-toast" class="cart-toast" role="alert" aria-live="assertive">
    <div class="toast-content">
        <span class="toast-check">✓</span>
        <span class="toast-message" id="toast-message">Added to cart!</span>
    </div>
</div>
