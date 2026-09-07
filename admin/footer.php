        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Mobile Sidebar Toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    const adminSidebar = document.getElementById('adminSidebar');
    if (sidebarToggle && adminSidebar) {
        sidebarToggle.addEventListener('click', function () {
            adminSidebar.classList.toggle('open');
        });
    }

    // 2. Entire Table Row Clickable Navigation
    document.querySelectorAll('.clickable-row').forEach(row => {
        row.addEventListener('click', function (e) {
            // Avoid triggering row navigation if clicking directly on a button, link, or switch
            if (e.target.closest('a, button, input, label, select')) {
                return;
            }
            const href = this.dataset.href;
            if (href) {
                window.location.href = href;
            }
        });
    });

    // 3. One-Click Menu Item Stock Availability Toggle
    document.querySelectorAll('.stock-toggle-input').forEach(input => {
        input.addEventListener('change', function () {
            const itemId = this.dataset.id;
            const textEl = this.closest('.stock-switch-wrap')?.querySelector('.stock-text');
            const isChecked = this.checked;

            if (textEl) {
                textEl.textContent = isChecked ? 'In Stock' : 'Sold Out';
                textEl.className = 'stock-text ' + (isChecked ? 'in-stock' : 'sold-out');
            }

            fetch('items.php?action=toggle_ajax&id=' + itemId + '&state=' + (isChecked ? '1' : '0'))
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        alert('Could not update item availability.');
                        // Revert checkbox
                        input.checked = !isChecked;
                        if (textEl) {
                            textEl.textContent = !isChecked ? 'In Stock' : 'Sold Out';
                            textEl.className = 'stock-text ' + (!isChecked ? 'in-stock' : 'sold-out');
                        }
                    }
                })
                .catch(err => {
                    console.error('Toggle error:', err);
                });
        });
    });

    // 4. Receipt Image Click to Zoom
    const zoomableImages = document.querySelectorAll('.zoomable-receipt');
    zoomableImages.forEach(img => {
        img.addEventListener('click', function () {
            window.open(this.src, '_blank');
        });
    });
});
</script>
</body>
</html>
