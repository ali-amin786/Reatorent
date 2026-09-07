// assets/js/main.js - A1 Peshawari Chapli Kabab Fast-Food Interactions & AJAX Cart

document.addEventListener('DOMContentLoaded', function () {
    // -------------------------------------------------------------
    // 1. Mobile Menu Toggle & Navbar Scroll Effect
    // -------------------------------------------------------------
    const navToggle = document.querySelector('.nav-toggle');
    const navLinks = document.querySelector('.nav-links');
    if (navToggle && navLinks) {
        navToggle.addEventListener('click', function () {
            navLinks.classList.toggle('active');
        });
    }

    const siteHeader = document.querySelector('.site-header');
    window.addEventListener('scroll', function () {
        if (siteHeader) {
            if (window.scrollY > 20) {
                siteHeader.classList.add('scrolled');
            } else {
                siteHeader.classList.remove('scrolled');
            }
        }
    });

    // -------------------------------------------------------------
    // 2. Global Elements: Toast & Cart Drawer
    // -------------------------------------------------------------
    const toast = document.getElementById('cart-toast');
    const toastMessage = document.getElementById('toast-message');
    let toastTimeout = null;

    function showToast(msg) {
        if (!toast || !toastMessage) return;
        toastMessage.textContent = msg || 'Added to cart!';
        toast.classList.add('show');
        clearTimeout(toastTimeout);
        toastTimeout = setTimeout(() => {
            toast.classList.remove('show');
        }, 2200);
    }

    const drawer = document.getElementById('cart-drawer');
    const drawerBackdrop = document.getElementById('cart-drawer-backdrop');
    const closeDrawerBtn = document.getElementById('close-cart-drawer-btn');
    const drawerItemsContainer = document.getElementById('drawer-items-container');
    const drawerEmptyState = document.getElementById('drawer-empty-state');
    const drawerUpsellSection = document.getElementById('drawer-upsell-wrapper');
    const drawerUpsellItems = document.getElementById('drawer-upsell-items');
    const drawerFooter = document.getElementById('drawer-footer-block');
    const drawerSubtotalVal = document.getElementById('drawer-subtotal-val');
    const drawerGrandTotalVal = document.getElementById('drawer-grand-total-val');
    const drawerCountBadge = document.getElementById('drawer-item-count');
    const drawerMinAlert = document.getElementById('drawer-min-order-alert');
    const drawerMinNeeded = document.getElementById('drawer-min-needed');
    const drawerCheckoutBtn = document.getElementById('drawer-checkout-btn');

    // Floating Bar Elements
    const floatingCartBar = document.getElementById('floating-cart-bar');
    const floatingCartCount = document.getElementById('floating-cart-count');
    const floatingCartTotal = document.getElementById('floating-cart-total');
    const floatingCartTrigger = document.getElementById('floating-cart-trigger');

    // Navbar Badge
    const navBadge = document.getElementById('cart-badge-count');

    function animateBadge(badgeElement) {
        if (!badgeElement) return;
        badgeElement.classList.remove('bounce');
        void badgeElement.offsetWidth; // trigger reflow
        badgeElement.classList.add('bounce');
    }

    // -------------------------------------------------------------
    // 3. Cart Drawer Open / Close Functions
    // -------------------------------------------------------------
    function openCartDrawer() {
        if (drawer && drawerBackdrop) {
            drawer.classList.add('active');
            drawerBackdrop.classList.add('active');
            drawer.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden'; // Prevent page scroll
            fetchAndRenderCart(); // Refresh cart data
        }
    }

    function closeCartDrawer() {
        if (drawer && drawerBackdrop) {
            drawer.classList.remove('active');
            drawerBackdrop.classList.remove('active');
            drawer.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }
    }

    // Bind triggers to open/close drawer
    document.querySelectorAll('.cart-open-trigger, #navbar-cart-btn').forEach(el => {
        el.addEventListener('click', function (e) {
            e.preventDefault();
            openCartDrawer();
        });
    });

    if (floatingCartTrigger) {
        floatingCartTrigger.addEventListener('click', function (e) {
            e.preventDefault();
            openCartDrawer();
        });
    }

    if (closeDrawerBtn) closeDrawerBtn.addEventListener('click', closeCartDrawer);
    if (drawerBackdrop) drawerBackdrop.addEventListener('click', closeCartDrawer);

    document.querySelectorAll('.close-drawer-action').forEach(btn => {
        btn.addEventListener('click', closeCartDrawer);
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && drawer && drawer.classList.contains('active')) {
            closeCartDrawer();
        }
    });

    // -------------------------------------------------------------
    // 4. Render Cart Data Inside Drawer & Update Badges
    // -------------------------------------------------------------
    function renderCartData(cart) {
        // Update Navbar Badge
        if (navBadge) {
            navBadge.textContent = cart.count || 0;
            animateBadge(navBadge);
        }

        // Update Drawer Count
        if (drawerCountBadge) {
            drawerCountBadge.textContent = cart.count || 0;
        }

        // Update Floating Mini-Cart Bar
        if (floatingCartBar) {
            if (cart.count > 0) {
                floatingCartBar.style.display = 'block';
                if (floatingCartCount) floatingCartCount.textContent = cart.count;
                if (floatingCartTotal) floatingCartTotal.textContent = cart.subtotal_formatted;
            } else {
                floatingCartBar.style.display = 'none';
            }
        }

        // Handle Empty vs Filled Cart in Drawer
        if (!cart.items || cart.items.length === 0) {
            if (drawerEmptyState) drawerEmptyState.style.display = 'block';
            if (drawerItemsContainer) drawerItemsContainer.innerHTML = '';
            if (drawerFooter) drawerFooter.style.display = 'none';
            if (drawerUpsellSection) drawerUpsellSection.style.display = 'none';
            return;
        }

        if (drawerEmptyState) drawerEmptyState.style.display = 'none';
        if (drawerFooter) drawerFooter.style.display = 'block';

        // Render Cart Items
        if (drawerItemsContainer) {
            let html = '';
            cart.items.forEach(item => {
                html += `
                    <div class="drawer-item-card" data-item-id="${item.id}">
                        <img src="assets/uploads/items/${item.image_url}" alt="${escapeHtml(item.title)}" class="drawer-item-thumb">
                        <div class="drawer-item-info">
                            <div class="drawer-item-title">${escapeHtml(item.title)}</div>
                            <div class="drawer-item-price">${item.line_total_formatted}</div>
                        </div>
                        <div class="drawer-item-actions">
                            <button type="button" class="stepper-btn stepper-minus" data-id="${item.id}" data-qty="${item.quantity - 1}">-</button>
                            <span class="stepper-val">${item.quantity}</span>
                            <button type="button" class="stepper-btn stepper-plus" data-id="${item.id}" data-qty="${item.quantity + 1}">+</button>
                            <button type="button" class="drawer-remove-btn" data-id="${item.id}" title="Remove item">&times;</button>
                        </div>
                    </div>
                `;
            });
            drawerItemsContainer.innerHTML = html;

            // Bind Steppers and Remove buttons inside drawer
            drawerItemsContainer.querySelectorAll('.stepper-minus, .stepper-plus').forEach(btn => {
                btn.addEventListener('click', function () {
                    const id = this.dataset.id;
                    const qty = parseInt(this.dataset.qty);
                    updateCartQuantity(id, qty);
                });
            });

            drawerItemsContainer.querySelectorAll('.drawer-remove-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    const id = this.dataset.id;
                    removeCartItem(id);
                });
            });
        }

        // Update Totals
        if (drawerSubtotalVal) drawerSubtotalVal.textContent = cart.subtotal_formatted;
        if (drawerGrandTotalVal) drawerGrandTotalVal.textContent = cart.subtotal_formatted;

        // Minimum Order Check
        if (drawerMinAlert && drawerCheckoutBtn) {
            if (!cart.meets_min_order) {
                drawerMinAlert.style.display = 'block';
                if (drawerMinNeeded) drawerMinNeeded.textContent = cart.amount_needed_formatted;
                drawerCheckoutBtn.classList.add('disabled');
                drawerCheckoutBtn.style.pointerEvents = 'none';
                drawerCheckoutBtn.style.opacity = '0.6';
            } else {
                drawerMinAlert.style.display = 'none';
                drawerCheckoutBtn.classList.remove('disabled');
                drawerCheckoutBtn.style.pointerEvents = 'auto';
                drawerCheckoutBtn.style.opacity = '1';
            }
        }

        // Render Upsell Items
        if (drawerUpsellSection && drawerUpsellItems && cart.upsell_items && cart.upsell_items.length > 0) {
            drawerUpsellSection.style.display = 'block';
            let upsellHtml = '';
            cart.upsell_items.forEach(u => {
                upsellHtml += `
                    <div class="upsell-card">
                        <img src="assets/uploads/items/${u.image_url}" alt="${escapeHtml(u.title)}">
                        <div class="upsell-card-title">${escapeHtml(u.title)}</div>
                        <div class="upsell-card-price">${u.price_formatted}</div>
                        <button type="button" class="upsell-add-btn" data-id="${u.id}" data-title="${escapeHtml(u.title)}">+ Add</button>
                    </div>
                `;
            });
            drawerUpsellItems.innerHTML = upsellHtml;

            drawerUpsellItems.querySelectorAll('.upsell-add-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    const id = this.dataset.id;
                    addItemToCart(id, 1, this.dataset.title);
                });
            });
        }
    }

    // Helper: Escape HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }

    // -------------------------------------------------------------
    // 5. AJAX Cart Operations (Zero-Redirect)
    // -------------------------------------------------------------
    function fetchAndRenderCart() {
        fetch('cart_actions.php?action=get_cart')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    renderCartData(data);
                }
            })
            .catch(err => console.error('Cart fetch error:', err));
    }

    function addItemToCart(id, quantity, titleFallback, buttonElement) {
        if (buttonElement) {
            buttonElement.style.transform = 'scale(0.9)';
            setTimeout(() => {
                buttonElement.style.transform = '';
            }, 150);
        }

        const formData = new FormData();
        formData.append('action', 'add');
        formData.append('id', id);
        formData.append('quantity', quantity || 1);

        fetch('cart_actions.php', {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    renderCartData(data);
                    showToast(data.message || 'Added to cart!');
                } else {
                    alert(data.error || 'Could not add item to cart.');
                }
            })
            .catch(err => {
                console.error('Add to cart error:', err);
            });
    }

    function updateCartQuantity(id, quantity) {
        const formData = new FormData();
        formData.append('action', 'update');
        formData.append('id', id);
        formData.append('quantity', quantity);

        fetch('cart_actions.php', {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    renderCartData(data);
                }
            })
            .catch(err => console.error('Update cart error:', err));
    }

    function removeCartItem(id) {
        const formData = new FormData();
        formData.append('action', 'remove');
        formData.append('id', id);

        fetch('cart_actions.php', {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    renderCartData(data);
                    showToast('Item removed from cart');
                }
            })
            .catch(err => console.error('Remove item error:', err));
    }

    // -------------------------------------------------------------
    // 6. Intercept Add to Cart on Menu and Item Pages (No Reload!)
    // -------------------------------------------------------------
    document.addEventListener('click', function (e) {
        const addBtn = e.target.closest('.btn-add-cart-ajax, .card-plus-btn');
        if (addBtn) {
            e.preventDefault();
            const itemId = addBtn.getAttribute('data-id');
            const qty = parseInt(addBtn.getAttribute('data-qty') || '1');
            const itemTitle = addBtn.getAttribute('data-title') || '';
            if (itemId) {
                addItemToCart(itemId, qty, itemTitle, addBtn);
            }
        }
    });

    // Detail page full form intercept (item.php)
    const itemDetailForm = document.getElementById('item-detail-order-form');
    if (itemDetailForm) {
        itemDetailForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const itemId = this.querySelector('input[name="id"]')?.value;
            const qtyInput = this.querySelector('input[name="quantity"]');
            const qty = qtyInput ? parseInt(qtyInput.value) || 1 : 1;
            const submitBtn = this.querySelector('button[type="submit"]');

            if (itemId) {
                addItemToCart(itemId, qty, '', submitBtn);
            }
        });
    }

    // Load initial cart status on page load to update badges
    fetchAndRenderCart();

    // -------------------------------------------------------------
    // 7. Checkout Page Interactivity (Two-Column Layout)
    // -------------------------------------------------------------
    const checkoutForm = document.getElementById('checkout-form');
    const paymentCardOptions = document.querySelectorAll('.payment-card-option');
    const onlineDetailsWrap = document.getElementById('online-payment-details-wrap');
    const codChangeWrap = document.getElementById('cod-change-wrap');
    const transactionIdInput = document.getElementById('transaction_id');
    const deliveryAreaSelect = document.getElementById('delivery_area_id');
    const displayDeliveryFee = document.getElementById('checkout-delivery-fee-display');
    const displayGrandTotal = document.getElementById('checkout-grand-total-display');
    const checkoutSubtotalHidden = document.getElementById('checkout-subtotal-val');
    const placeOrderBtn = document.getElementById('place-order-submit-btn');

    if (paymentCardOptions.length > 0) {
        function updatePaymentCards() {
            paymentCardOptions.forEach(card => {
                const radio = card.querySelector('input[type="radio"]');
                if (radio && radio.checked) {
                    card.classList.add('selected');

                    if (radio.value === 'cod') {
                        if (codChangeWrap) codChangeWrap.style.display = 'block';
                        if (onlineDetailsWrap) onlineDetailsWrap.style.display = 'none';
                        if (transactionIdInput) transactionIdInput.removeAttribute('required');
                    } else {
                        if (codChangeWrap) codChangeWrap.style.display = 'none';
                        if (onlineDetailsWrap) onlineDetailsWrap.style.display = 'block';
                        if (transactionIdInput) transactionIdInput.setAttribute('required', 'required');
                    }
                } else {
                    card.classList.remove('selected');
                }
            });
        }

        paymentCardOptions.forEach(card => {
            card.addEventListener('click', function () {
                const radio = this.querySelector('input[type="radio"]');
                if (radio) {
                    radio.checked = true;
                    updatePaymentCards();
                }
            });
        });

        // Initialize state
        updatePaymentCards();
    }

    // Dynamic Delivery Fee Update
    if (deliveryAreaSelect && displayDeliveryFee && displayGrandTotal && checkoutSubtotalHidden) {
        deliveryAreaSelect.addEventListener('change', function () {
            const selectedOpt = this.options[this.selectedIndex];
            const fee = parseFloat(selectedOpt.getAttribute('data-fee') || '0');
            const subtotal = parseFloat(checkoutSubtotalHidden.value || '0');
            const grandTotal = subtotal + fee;

            displayDeliveryFee.textContent = 'PKR ' + fee.toFixed(0);
            displayGrandTotal.textContent = 'PKR ' + grandTotal.toFixed(0);
        });
    }

    // Use Current Location Chip
    const locationChip = document.getElementById('btn-use-location');
    const addressTextarea = document.getElementById('delivery_address');
    if (locationChip && addressTextarea) {
        locationChip.addEventListener('click', function () {
            if (navigator.geolocation) {
                locationChip.textContent = 'Locating...';
                navigator.geolocation.getCurrentPosition(
                    pos => {
                        const lat = pos.coords.latitude.toFixed(5);
                        const lng = pos.coords.longitude.toFixed(5);
                        addressTextarea.value = (addressTextarea.value ? addressTextarea.value + '\n' : '') + 
                                                `GPS Coordinates: ${lat}, ${lng} (Scheme 33, Karachi)`;
                        locationChip.textContent = '✓ Location Added';
                    },
                    err => {
                        alert('Could not retrieve GPS location. Please type your street address.');
                        locationChip.textContent = '📍 Use current location';
                    }
                );
            } else {
                alert('Geolocation is not supported by your browser.');
            }
        });
    }

    // Place Order Button Loading State
    if (checkoutForm && placeOrderBtn) {
        checkoutForm.addEventListener('submit', function () {
            placeOrderBtn.disabled = true;
            placeOrderBtn.innerHTML = `
                <span style="display: inline-flex; align-items: center; gap: 8px;">
                    <span class="pulse-dot" style="background: #fff;"></span>
                    Placing Order, Please wait...
                </span>
            `;
        });
    }

    // Payment Proof Image Preview
    const receiptInput = document.getElementById('payment_proof');
    const receiptPreview = document.getElementById('receipt-preview');
    if (receiptInput && receiptPreview) {
        receiptInput.addEventListener('change', function () {
            const file = this.files[0];
            if (file) {
                if (file.size > 2 * 1024 * 1024) {
                    alert('Screenshot exceeds 2MB limit. Please upload a smaller image.');
                    this.value = '';
                    receiptPreview.innerHTML = '';
                    return;
                }
                const reader = new FileReader();
                reader.onload = function (e) {
                    receiptPreview.innerHTML = `<img src="${e.target.result}" style="max-height: 140px; border-radius: 6px; border: 1px solid #CCC; margin-top: 10px; display: block;" alt="Receipt Preview">`;
                };
                reader.readAsDataURL(file);
            } else {
                receiptPreview.innerHTML = '';
            }
        });
    }

    // Generic Stepper Buttons (+ / -) for detail pages
    document.querySelectorAll('.qty-step-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const input = this.parentElement.querySelector('.qty-input');
            if (input) {
                let val = parseInt(input.value) || 1;
                if (this.dataset.action === 'plus') {
                    val++;
                } else if (this.dataset.action === 'minus') {
                    val = Math.max(1, val - 1);
                }
                input.value = val;
            }
        });
    });
});
