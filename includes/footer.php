<?php
// includes/footer.php - Shared Fast-Food Footer with Global Cart Drawer
?>
<footer class="site-footer" id="about">
    <div class="container">
        <div class="footer-grid">
            <!-- Col 1: Brand & Craftsmanship -->
            <div>
                <h3 style="font-family: var(--font-heading); font-size: 1.25rem; margin-bottom: 14px;">🔥 <?php echo SITE_NAME; ?></h3>
                <p style="font-size: 0.9rem; line-height: 1.6; margin-bottom: 14px; color: #AAA;">
                    Karachi's original authentic hand-minced beef and chicken chapli kababs seared on heavy iron tawas, served alongside blistering hot tandoori roghani naans from <strong><?php echo SUB_NAME; ?></strong>.
                </p>
                <p style="font-size: 0.88rem;">
                    <strong style="color: #FFF;">Daily Sizzle:</strong> <?php echo date('g:i A', strtotime(OPENING_TIME)); ?> - <?php echo date('g:i A', strtotime(CLOSING_TIME)); ?> Midnight
                </p>
            </div>

            <!-- Col 2: Ordering & WhatsApp -->
            <div>
                <h3 style="font-family: var(--font-heading); font-size: 1.25rem; margin-bottom: 14px;">📞 Hot Delivery</h3>
                <p style="font-size: 0.9rem; margin-bottom: 12px; color: #AAA;">
                    Fast hot delivery across Scheme 33, Safoora, Gulshan, Malir Cantt & nearby sectors.
                </p>
                <div style="margin-bottom: 14px;">
                    <a href="tel:<?php echo PHONE_NUMBER; ?>" style="color: #FFF; font-weight: 700; font-size: 1.05rem;"><?php echo PHONE_NUMBER; ?></a>
                </div>
                <div>
                    <a href="https://wa.me/<?php echo WHATSAPP_NUMBER; ?>?text=Salam%20A1%20Kabab%20I%20want%20to%20order" 
                       target="_blank" 
                       class="btn btn-whatsapp" 
                       style="padding: 10px 20px; font-size: 0.9rem;">
                        💬 Order via WhatsApp
                    </a>
                </div>
            </div>

            <!-- Col 3: Location Map -->
            <div>
                <h3 style="font-family: var(--font-heading); font-size: 1.25rem; margin-bottom: 14px;">📍 Visit Our Tawa</h3>
                <p style="font-size: 0.88rem; margin-bottom: 12px; color: #AAA;">
                    <?php echo RESTAURANT_ADDRESS; ?>
                </p>
                <div style="border-radius: var(--radius-sm); overflow: hidden; border: 1px solid #333; height: 150px;">
                    <iframe 
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d14467.971556942426!2d67.1189912!3d24.9664536!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3eb338b5bf6cf9bf%3A0x633511ea681dc238!2sGulzar-e-Hijri%20Scheme%2033%2C%20Karachi!5e0!3m2!1sen!2s!4v1700000000000!5m2!1sen!2s" 
                        width="100%" 
                        height="100%" 
                        style="border:0;" 
                        allowfullscreen="" 
                        loading="lazy" 
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
            </div>
        </div>

        <div class="footer-copy">
            &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?> (<?php echo SUB_NAME; ?>). Authentically Crafted in Karachi.
        </div>
    </div>
</footer>

<!-- Include Cart Drawer & Floating Bar Markup -->
<?php require_once __DIR__ . '/cart_drawer.php'; ?>

<script src="assets/js/main.js"></script>
</body>
</html>
