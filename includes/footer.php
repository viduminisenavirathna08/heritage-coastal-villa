<!-- Footer -->
<footer class="footer">
    <div class="container footer-grid">
        <div>
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                <img src="assets/images/logo.jpg" alt="<?php echo SITE_NAME; ?>" style="height: 44px; width: auto; object-fit: contain; background: #fff; padding: 2px; border-radius: 4px;">
                <div class="logo-title" style="color: #fff; font-size: 18px; margin: 0;"><?php echo SITE_NAME; ?></div>
            </div>
            <p style="margin-top: 5px; color: #a4b0be; font-size: 13px;">
                <?php echo SITE_TAGLINE; ?><br>
                <?php echo SITE_LOCATION; ?>
            </p>
        </div>
        <div>
            <h4>Quick Links</h4>
            <p><a href="index.php">Home</a></p>
            <p><a href="index.php#rooms">Suites & Villas</a></p>
            <p><a href="index.php#experiences">Experiences & Spa</a></p>
            <p><a href="book.php">Reserve a Stay</a></p>
        </div>
        <div>
            <h4>Guest Services</h4>
            <p><a href="login.php">Guest Portal</a></p>
            <p><a href="register.php">Create Account</a></p>
            <p><a href="my_bookings.php">My Reservations</a></p>
            <p><a href="admin/dashboard.php">Staff / Admin</a></p>
        </div>
        <div>
            <h4>Contact & Location</h4>
            <p style="color: #a4b0be; font-size: 13px;">
                <i class="fa-solid fa-location-dot"></i> Coastal Road, Mirissa, Sri Lanka<br>
                <i class="fa-solid fa-phone"></i> +94 77 123 4567<br>
                <i class="fa-solid fa-envelope"></i> reservations@heritagevilla.lk
            </p>
        </div>
    </div>
    <div class="container footer-bottom" style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); color: #747d8c; font-size: 12px;">
        &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All Rights Reserved. Crafted for Sri Lankan Coastal Luxury.
    </div>
</footer>

<!-- Interactive Packing Guide Modal -->
<div class="modal-overlay" id="packing_guide_modal">
    <div class="modal-box" style="max-width: 620px; padding: 30px; border-radius: 8px;">
        <button type="button" class="modal-close" id="close_packing_modal" onclick="document.getElementById('packing_guide_modal').classList.remove('active');">&times;</button>
        <h3 style="margin-top:0; color:var(--color-primary); font-family: 'Cinzel', serif; font-size: 20px;">
            <i class="fa-solid fa-suitcase-rolling" style="color:var(--color-accent-gold);"></i> Tropical Sri Lanka Packing Guide
        </h3>
        <p style="font-size:13px; color:var(--color-text-muted); margin-bottom: 20px;">
            Essential items recommended for your luxury stay and adventures along the Southern Coast (Mirissa & Weligama).
        </p>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; text-align:left; font-size:13px;">
            <div style="background: var(--color-sand-bg); padding: 16px; border-radius: 6px; border-left: 3px solid var(--color-accent-gold);">
                <strong style="color: var(--color-primary);"><i class="fa-solid fa-sun" style="color:#e67e22;"></i> Coastal & Sun Care</strong>
                <ul style="padding-left:18px; margin-top:8px; color:#444; line-height: 1.6;">
                    <li>Reef-safe sunscreen (SPF 50+)</li>
                    <li>UV polarized sunglasses</li>
                    <li>Lightweight linen or cotton wear</li>
                    <li>Swimwear & quick-dry towels</li>
                    <li>Wide-brim sunhat or cap</li>
                </ul>
            </div>
            <div style="background: var(--color-sand-bg); padding: 16px; border-radius: 6px; border-left: 3px solid var(--color-olive);">
                <strong style="color: var(--color-primary);"><i class="fa-solid fa-temple" style="color:var(--color-olive);"></i> Culture & Nature</strong>
                <ul style="padding-left:18px; margin-top:8px; color:#444; line-height: 1.6;">
                    <li>Modest white clothing for temples (shoulders & knees covered)</li>
                    <li>Easy slip-on footwear (removed at temples)</li>
                    <li>Organic mosquito & bug spray</li>
                    <li>Universal adapter (Type D/G, 230V)</li>
                    <li>Dry bag for whale watching boat trips</li>
                </ul>
            </div>
        </div>

        <div style="margin-top: 22px; padding: 12px 16px; background: rgba(197, 168, 105, 0.12); border-radius: 6px; text-align: center; font-size: 12.5px; color: var(--color-primary);">
            <i class="fa-solid fa-bell-concierge" style="color: var(--color-accent-gold);"></i> 
            <strong>Villa Concierge Tip:</strong> Complimentary beach towels, organic botanical toiletries, and umbrellas are provided in every suite.
        </div>

        <div style="margin-top: 25px; text-align: center;">
            <button type="button" class="btn btn-gold" id="thank_you_close_btn" onclick="document.getElementById('packing_guide_modal').classList.remove('active');" style="padding: 11px 32px; font-size: 13px; border-radius: 4px; cursor: pointer; border: none; font-weight: 600;">
                <i class="fa-solid fa-check" style="margin-right: 6px;"></i> Thank You
            </button>
        </div>
    </div>
</div>

<script src="assets/js/packing-guide.js"></script>
</body>
</html>
