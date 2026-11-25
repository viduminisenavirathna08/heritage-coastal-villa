<?php
/**
 * HERITAGE COASTAL VILLA - BOOKING & EXPERIENCES (book.php)
 * Real-Time Dynamic Price Calculator (LKR Primary + USD Secondary) & Visual Add-on Selector
 */
define('PAGE_TITLE', 'Reserve Your Stay & Experiences');
require_once __DIR__ . '/config/db.php';

// Exchange Rate & VAT
$exchangeRate = defined('USD_TO_LKR') ? (float)USD_TO_LKR : 320.0;
$vatRate      = defined('VAT_RATE') ? (float)VAT_RATE : 0.08;
$vatPercent   = defined('VAT_PERCENT') ? (int)VAT_PERCENT : 8;

//(Base prices are stored in USD)
function showDualCurrency($amountUSD, $rate) {
    $usd = (float)$amountUSD;
    $lkr = $usd * $rate;
    return '$' . number_format($usd, 2) . ' (Rs. ' . number_format($lkr, 2) . ')';
}

// Fetch available rooms
$stmtRooms = $pdo->query("SELECT * FROM rooms WHERE status = 'available' ORDER BY price_per_night ASC");
$rooms = $stmtRooms->fetchAll();

// Fetch active services grouped by category (excluding any Chauffeur services)
$stmtServices = $pdo->query("SELECT * FROM services WHERE status = 'active' AND service_name NOT LIKE '%Chauffeur%' ORDER BY category ASC, price ASC");
$services = $stmtServices->fetchAll();

$groupedServices = [
    'in_house' => [],
    'wellness' => [],
    'marine'   => [],
    'cultural' => [],
    'travel'   => []
];
foreach ($services as $srv) {
    if (isset($groupedServices[$srv['category']])) {
        $groupedServices[$srv['category']][] = $srv;
    }
}

// Pre-fill guest details from the logged-in account
$prefillName  = $_SESSION['user_name'] ?? '';
$prefillEmail = $_SESSION['user_email'] ?? '';
$prefillPhone = $_SESSION['user_phone'] ?? '';

// Pre-fill the booking form from (1) a saved draft (after an error / login) or (2) the quick booking bar (GET)
$draft = $_SESSION['booking_draft'] ?? [];
unset($_SESSION['booking_draft']);

$selectedRoomId = (int)($draft['room_id'] ?? ($_GET['room_id'] ?? 0));
if ($selectedRoomId <= 0) {
    $selectedRoomId = $rooms[0]['id'] ?? 0;
}

$prefillCheckIn  = (string)($draft['check_in']  ?? ($_GET['check_in']  ?? ''));
$prefillCheckOut = (string)($draft['check_out'] ?? ($_GET['check_out'] ?? ''));
if (!parseDate($prefillCheckIn))  { $prefillCheckIn = ''; }
if (!parseDate($prefillCheckOut)) { $prefillCheckOut = ''; }

$prefillGuests = (int)($draft['num_guests'] ?? ($_GET['num_guests'] ?? 2));
if ($prefillGuests < 1 || $prefillGuests > 4) { $prefillGuests = 2; }

$prefillServices = array_map('intval', $draft['services'] ?? []);
$prefillRequests = (string)($draft['special_requests'] ?? '');

$todayStr = date('Y-m-d');

// Renders one add-on checkbox row
function renderAddon(array $srv, array $checkedIds, float $rate) {
    ?>
    <label class="addon-checkbox-label">
        <div class="addon-left-wrap">
            <input type="checkbox" name="services[]" value="<?php echo (int)$srv['id']; ?>" class="addon-checkbox" data-price="<?php echo e($srv['price']); ?>" <?php echo in_array((int)$srv['id'], $checkedIds, true) ? 'checked' : ''; ?>>
            <?php if (!empty($srv['image_url'])): ?>
                <img src="<?php echo e($srv['image_url']); ?>" alt="" class="addon-thumb">
            <?php endif; ?>
            <div class="addon-text-wrap">
                <span class="addon-name"><?php echo e($srv['service_name']); ?></span>
                <span class="addon-desc-snippet"><?php echo e($srv['description']); ?></span>
            </div>
        </div>
        <span class="service-price-tag">+<?php echo showDualCurrency((float)$srv['price'], $rate); ?></span>
    </label>
    <?php
}

require_once __DIR__ . '/includes/header.php';
?>
<style>
.offer-banner {
    background: linear-gradient(135deg, #1f2a30 0%, #2c3e46 100%);
    color: #fff;
    border-radius: 10px;
    padding: 20px 24px;
    margin-bottom: 18px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.12);
    border-left: 5px solid #c5a869;
}
.offer-title { font-family: 'Cinzel', serif; font-size: 17px; margin-bottom: 12px; }
.offer-title i { color: #c5a869; margin-right: 8px; }
.offer-chips { display: flex; flex-wrap: wrap; gap: 10px; }
.offer-chip {
    background: rgba(197,168,105,0.18);
    border: 1px solid #c5a869;
    color: #fff;
    padding: 6px 16px;
    border-radius: 30px;
    font-size: 13px;
}
.offer-chip strong { color: #c5a869; margin-right: 4px; }
.offer-chip-muted { background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.25); }
.offer-note { margin-top: 12px; font-size: 11.5px; color: #b8c2c8; }

.info-notice {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    background: #fbf6ea;
    border: 1px solid #ecdfc0;
    border-radius: 8px;
    padding: 14px 18px;
    margin-bottom: 25px;
    font-size: 13.5px;
    color: #5b4a1f;
    line-height: 1.6;
}
.info-notice > i { color: #c5a869; font-size: 18px; margin-top: 2px; }
.notice-text { flex: 1; }
.notice-buttons { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 12px; }
.notice-buttons .btn { padding: 9px 20px; font-size: 12.5px; text-decoration: none; }
.notice-btn-outline { color: #2c3e50; border-color: #2c3e50; background: transparent; }
.notice-btn-outline:hover { background: #2c3e50; color: #fff; }
</style>
<div class="container" style="margin-top: 40px;">
    <div class="section-header" style="margin-bottom: 25px;">
        <span style="font-size: 11px; letter-spacing: 3px; text-transform: uppercase; color: var(--color-accent-gold); font-weight: 700;">Reservations</span>
        <h2 class="section-title">Customize Your Luxury Stay</h2>
        <div class="batik-divider"></div>
        <p class="section-subtitle">Select your preferred villa suite, dates, and tailor your stay with our signature in-house wellness and coastal adventures.</p>
    </div>

    <!-- Feedback Alerts -->
    <?php if (isset($_SESSION['booking_error'])): ?>
        <div class="alert alert-danger">
            <i class="fa-solid fa-triangle-exclamation"></i> <?php echo e($_SESSION['booking_error']); unset($_SESSION['booking_error']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['booking_success'])): ?>
        <div class="alert alert-success">
            <i class="fa-solid fa-circle-check"></i> <?php echo e($_SESSION['booking_success']); unset($_SESSION['booking_success']); ?>
        </div>
    <?php endif; ?>

    <!-- Long-stay offers & Taxes -->
<div class="offer-banner">
    <div class="offer-title"><i class="fa-solid fa-tags"></i> Stay longer, save more on your suite</div>
    <div class="offer-chips">
        <span class="offer-chip"><strong>2 Nights</strong> 5% OFF</span>
        <span class="offer-chip"><strong>3 Nights</strong> 10% OFF</span>
        <span class="offer-chip"><strong>4 Nights</strong> 15% OFF</span>
        <span class="offer-chip"><strong>Long Stays</strong> 20% OFF</span>
    </div>
    <div class="offer-note">
        Prices in USD ($), converted to LKR (1 USD = Rs. <?php echo number_format($exchangeRate, 2); ?>). VAT is calculated on the net total.
    </div>
</div>

<?php if (!isLoggedIn()): ?>
    <div class="info-notice">
        <i class="fa-solid fa-circle-info"></i>
        <div class="notice-text">
            <strong>Explore prices freely.</strong>
            To confirm your reservation, sign in or create a free guest account.
            <div class="notice-buttons">
                <a href="login.php" class="btn btn-gold btn-sm">
                    <i class="fa-solid fa-right-to-bracket"></i> Sign In
                </a>
                <a href="register.php" class="btn btn-outline btn-sm notice-btn-outline">
                    <i class="fa-solid fa-user-plus"></i> Create Guest Account
                </a>
            </div>
        </div>
    </div>
<?php endif; ?>
<div class="info-notice" id="cart_notice" style="display:none;">
    <i class="fa-solid fa-cart-shopping"></i>
    <div class="notice-text">
        <strong>Experiences added from your cart:</strong>
        <div id="cart_notice_list" style="margin-top:6px;"></div>
    </div>
</div>
    <form action="process_booking.php" method="POST" id="booking_form">
        <?php echo csrfField(); ?>
        <div class="booking-grid">

            <!-- LEFT COLUMN: Booking Details & Visual Add-on Services -->
            <div class="booking-form-card">

                <!-- 1. Guest Information -->
                <h3 style="font-size: 17px; margin-bottom: 20px; color: var(--color-primary); border-bottom: 1px solid var(--color-sand-border); padding-bottom: 8px;">
                    <i class="fa-solid fa-user-pen" style="color:var(--color-accent-gold);"></i> 1. Guest Details
                </h3>

                <?php if (isLoggedIn()): ?>
                    <p style="font-size: 13px; color: var(--color-text-muted); margin-bottom: 15px;">
                        Booking as <strong><?php echo e($prefillName); ?></strong> (<?php echo e($prefillEmail); ?><?php echo $prefillPhone !== '' ? ', ' . e($prefillPhone) : ''; ?>).
                    </p>
                <?php endif; ?>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label class="form-label" for="full_name">Full Name</label>
                        <input type="text" id="full_name" class="form-control" readonly value="<?php echo e($prefillName); ?>" placeholder="Sign in to auto-fill">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="phone">Phone / WhatsApp</label>
                        <input type="tel" id="phone" class="form-control" readonly value="<?php echo e($prefillPhone); ?>" placeholder="Sign in to auto-fill">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" id="email" class="form-control" readonly value="<?php echo e($prefillEmail); ?>" placeholder="Sign in to auto-fill">
                </div>
                <!-- 2. Accommodation Selection & Dates -->
                <h3 style="font-size: 17px; margin: 30px 0 20px; color: var(--color-primary); border-bottom: 1px solid var(--color-sand-border); padding-bottom: 8px;">
                    <i class="fa-solid fa-bed" style="color:var(--color-accent-gold);"></i> 2. Room & Schedule
                </h3>

                <div class="form-group">
                    <label class="form-label" for="room_select">Select Suite / Villa *</label>
                    <select id="room_select" name="room_id" class="form-control" required>
                        <?php foreach ($rooms as $room): ?>
                            <option value="<?php echo (int)$room['id']; ?>"
                                    data-price="<?php echo e($room['price_per_night']); ?>"
                                    <?php echo $room['id'] == $selectedRoomId ? 'selected' : ''; ?>>
                                <?php echo e($room['room_name']); ?> — <?php echo showDualCurrency((float)$room['price_per_night'], $exchangeRate); ?>/night (Max <?php echo (int)$room['max_guests']; ?> Guests)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label class="form-label" for="check_in">Check-in Date *</label>
                        <input type="date" id="check_in" name="check_in" class="form-control" required min="<?php echo $todayStr; ?>" value="<?php echo e($prefillCheckIn); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="check_out">Check-out Date *</label>
                        <input type="date" id="check_out" name="check_out" class="form-control" required min="<?php echo $todayStr; ?>" value="<?php echo e($prefillCheckOut); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="num_guests">Guests</label>
                        <select id="num_guests" name="num_guests" class="form-control">
                            <?php for ($g = 1; $g <= 4; $g++): ?>
                                <option value="<?php echo $g; ?>" <?php echo $g === $prefillGuests ? 'selected' : ''; ?>><?php echo $g; ?> Guest<?php echo $g > 1 ? 's' : ''; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>

                <!-- 3. Categorized Visual Experiences & Add-ons -->
                <h3 style="font-size: 17px; margin: 30px 0 10px; color: var(--color-primary); border-bottom: 1px solid var(--color-sand-border); padding-bottom: 8px;">
                    <i class="fa-solid fa-wand-magic-sparkles" style="color:var(--color-accent-gold);"></i> 3. Add-on Experiences & Wellness
                </h3>
                <p style="font-size: 13px; color: var(--color-text-muted); margin-bottom: 20px;">
                    Tailor your stay with realistic previews. Check any experience below and watch the <strong>Live Price Calculator</strong> update instantly.
                </p>

                <!-- In-House Luxury -->
                <div class="addon-selection-group">
                    <div class="addon-group-title"><i class="fa-solid fa-hotel"></i> In-House Luxury (Villa Sanctuary)</div>
                    <?php foreach ($groupedServices['in_house'] as $srv) { renderAddon($srv, $prefillServices, $exchangeRate); } ?>
                </div>

                <!-- Complimentary & Free Facilities Notice -->
                <div style="background: #e8f5e9; border: 1px solid #c8e6c9; border-left: 4px solid #2e7d32; border-radius: 6px; padding: 14px 18px; margin-bottom: 20px;">
                    <div style="font-weight: 700; color: #1b5e20; font-size: 13.5px; margin-bottom: 6px; display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-gift" style="color: #2e7d32;"></i> Complimentary Included Facilities (100% Free with Every Suite):
                    </div>
                    <ul style="margin: 0; padding-left: 20px; font-size: 12px; color: #2e7d32; line-height: 1.6;">
                        <li><strong>Free Swimming Pool & Plunge Pool Access</strong> (Loungers & towels included)</li>
                        <li><strong>Private Suite Kitchenette & Cookware</strong> (Tea/coffee maker & cookware ready)</li>
                        <li><strong>Direct Front Beach Footpath Access & Sunset Views</strong></li>
                        <li><strong>High-Speed 100Mbps Fiber WiFi & Secure On-Site Parking</strong></li>
                    </ul>
                </div>

                <!-- Wellness, Spa & Steam Bath -->
                <div class="addon-selection-group" style="border-left: 3px solid var(--color-olive);">
                    <div class="addon-group-title" style="color:var(--color-olive);"><i class="fa-solid fa-spa"></i> Wellness & Ayurvedic Spa Treatments</div>
                    <?php foreach ($groupedServices['wellness'] as $srv) { renderAddon($srv, $prefillServices, $exchangeRate); } ?>
                </div>

                <!-- Marine Adventures -->
                <div class="addon-selection-group">
                    <div class="addon-group-title"><i class="fa-solid fa-water"></i> Marine Excursions</div>
                    <?php foreach ($groupedServices['marine'] as $srv) { renderAddon($srv, $prefillServices, $exchangeRate); } ?>
                </div>

                <!-- Cultural Heritage -->
                <div class="addon-selection-group">
                    <div class="addon-group-title"><i class="fa-solid fa-earth-asia"></i> Cultural Tours & Masterclasses</div>
                    <?php
                    foreach ($groupedServices['cultural'] as $srv) { renderAddon($srv, $prefillServices, $exchangeRate); }
                    ?>
                </div>

                <!-- Airport Transfers & Transport -->
                <div class="addon-selection-group">
                    <div class="addon-group-title"><i class="fa-solid fa-car"></i> Airport Transfers & Transport</div>
                    <?php
                    foreach ($groupedServices['travel'] as $srv) { renderAddon($srv, $prefillServices, $exchangeRate); }
                    ?>
                </div>

                <!-- 4. Special Requests & Additional Inquiries -->
                <div class="form-group" style="margin-top: 30px; background: #ffffff; border: 1px solid var(--color-sand-border); border-radius: 6px; padding: 20px;">
                    <label class="form-label" for="special_requests" style="font-size: 15px; font-weight: 700; color: var(--color-primary); margin-bottom: 6px; display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-message" style="color:var(--color-accent-gold);"></i> Special Inquiries & Additional Requests (Optional)
                    </label>
                    <p style="font-size: 12px; color: var(--color-text-muted); margin-bottom: 12px;">
                        Let us know about dietary restrictions (e.g., vegetarian, vegan, halal), special setups (anniversary, honeymoon decor), specific arrival times, or custom villa arrangements.
                    </p>
                    <textarea id="special_requests" name="special_requests" class="form-control" rows="4" maxlength="1000" placeholder="e.g. Vegetarian breakfast preference, honeymoon setup on arrival, late check-in notice after 6:00 PM..."><?php echo e($prefillRequests); ?></textarea>
                </div>

                <!-- 5. Villa Policies & House Rules Agreement Section -->
                <div class="form-group" style="margin-top: 25px; background: #ffffff; border: 1px solid var(--color-sand-border); border-radius: 6px; padding: 22px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; border-bottom: 1px solid var(--color-sand-border); padding-bottom: 10px;">
                        <h4 style="margin: 0; font-size: 16px; color: var(--color-primary); display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-scale-balanced" style="color:var(--color-accent-gold);"></i> Villa Policies &amp; House Rules
                        </h4>
                        <span style="font-size: 11px; background: rgba(197, 168, 105, 0.15); color: var(--color-primary); padding: 3px 8px; border-radius: 4px; font-weight: 600;">Required to Agree</span>
                    </div>

                    <!-- Scrollable Policies Box -->
                    <div style="max-height: 240px; overflow-y: auto; background: var(--color-sand-bg); border: 1px solid var(--color-sand-border); border-radius: 6px; padding: 15px 18px; font-size: 12.5px; line-height: 1.65; color: var(--color-primary); margin-bottom: 15px;">
                        <p style="font-weight: 700; margin-bottom: 6px; color: var(--color-primary); font-size: 13px;">
                            🕒 Check-In &amp; Check-Out
                        </p>
                        <ul style="margin: 0 0 14px 20px; padding: 0;">
                            <li><strong>Check-in Time:</strong> 2:00 PM</li>
                            <li><strong>Check-out Time:</strong> 11:00 AM</li>
                            <li style="color: var(--color-text-muted); font-style: italic;">Note: Early check-in or late check-out is strictly subject to availability and may incur additional charges.</li>
                        </ul>

                        <p style="font-weight: 700; margin-bottom: 6px; color: var(--color-primary); font-size: 13px;">
                            📜 Core House Rules
                        </p>
                        <ul style="margin: 0 0 14px 20px; padding: 0;">
                            <li><strong>🚫 No Outside Food &amp; Beverages:</strong> To maintain our high hygiene standards and preserve the environment, bringing outside food or beverages into the villa is strictly prohibited. Guests are warmly invited to enjoy our authentic local cuisine and in-villa dining services.</li>
                            <li><strong>🚫 Strictly No Pets:</strong> For the preservation of the property and its traditional elements, pets are not allowed under any circumstances.</li>
                            <li><strong>🚭 No Smoking Indoors:</strong> Smoking is strictly prohibited inside all bedrooms, bathrooms, and enclosed living spaces. Designated outdoor smoking areas are available.</li>
                            <li><strong>🤫 Quiet Hours:</strong> To ensure a tranquil atmosphere for everyone, loud music, parties, and events are not permitted. Please respect our quiet hours from 10:00 PM to 7:00 AM.</li>
                        </ul>

                        <p style="font-weight: 700; margin-bottom: 6px; color: var(--color-primary); font-size: 13px;">
                            ✨ Cleanliness, Orderliness &amp; Property Care
                        </p>
                        <ul style="margin: 0 0 14px 20px; padding: 0;">
                            <li><strong>Professional Standards:</strong> We are highly committed to providing a spotless, professionally maintained, and tranquil environment.</li>
                            <li><strong>Maintaining Orderliness:</strong> The villa is handed over to you in pristine condition. We kindly request all guests to treat the property with the utmost care, respect the traditional d&eacute;cor, and maintain the neatness and orderly condition of the premises throughout their stay.</li>
                            <li><strong>Housekeeping:</strong> While daily housekeeping is provided, guests are expected to keep their personal spaces reasonably tidy and leave the villa in a respectful condition upon departure.</li>
                        </ul>

                        <p style="font-weight: 700; margin-bottom: 6px; color: var(--color-primary); font-size: 13px;">
                            👥 Occupancy &amp; Visitors
                        </p>
                        <ul style="margin: 0 0 14px 20px; padding: 0;">
                            <li>Only registered guests are permitted on the property. For privacy and security reasons, outside visitors are not allowed.</li>
                            <li>The maximum occupancy limit (as stated in your booking confirmation) is strictly enforced.</li>
                        </ul>

                        <p style="font-weight: 700; margin-bottom: 6px; color: var(--color-primary); font-size: 13px;">
                            🏊 Safety &amp; Damages
                        </p>
                        <ul style="margin: 0 0 14px 20px; padding: 0;">
                            <li><strong>Pool Safety:</strong> The private plunge pool and swimming pool do not have a lifeguard on duty. Use the pool at your own risk. Children must be supervised by an adult at all times.</li>
                            <li><strong>Property Damage:</strong> Guests are fully responsible for any damages caused to the villa, including antique furniture, handloom/batik furnishings, and structural elements. A refundable security deposit may be collected upon check-in.</li>
                        </ul>

                        <p style="font-weight: 700; margin-bottom: 6px; color: var(--color-primary); font-size: 13px;">
                            💳 Booking, Payment &amp; Cancellation
                        </p>
                        <ul style="margin: 0 0 5px 20px; padding: 0;">
                            <li>A 50% advance payment is required to secure and confirm the reservation.</li>
                            <li>Cancellations made 14 days prior to the check-in date are eligible for a full refund. Cancellations made within 14 days of arrival are non-refundable.</li>
                        </ul>
                    </div>

                    <!-- Mandatory Agreement Checkbox -->
                    <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer; font-size: 13px; font-weight: 600; color: var(--color-primary); line-height: 1.45;">
                        <input type="checkbox" name="agree_policies" id="agree_policies" required value="1" style="accent-color: var(--color-accent-gold); width: 17px; height: 17px; margin-top: 2px; cursor: pointer;">
                        <span>
                            I have read, understood, and agree to abide by the <strong style="color:var(--color-accent-gold);">Villa Policies &amp; House Rules</strong>, including check-in/out times, quiet hours, and cancellation terms. <span style="color:#e74c3c;">*</span>
                        </span>
                    </label>
                </div>
            </div>

            <!-- RIGHT COLUMN: Sticky Real-Time Price Calculator -->
            <div>
                <div class="price-calculator-panel">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid var(--color-sand-border); padding-bottom: 10px;">
                        <h3 style="font-size: 17px; margin: 0; color: var(--color-primary);">
                            <i class="fa-solid fa-calculator" style="color:var(--color-accent-gold);"></i> Reservation Summary
                        </h3>
                    </div>

                    <!-- Currency Switcher Toggle Option -->
                    <div style="background: var(--color-sand-bg); border: 1px solid var(--color-sand-border); border-radius: 6px; padding: 10px 14px; margin-bottom: 18px; display: flex; align-items: center; justify-content: space-between;">
                        <span style="font-size: 12px; font-weight: 600; color: var(--color-primary);">
                            <i class="fa-solid fa-coins" style="color:var(--color-accent-gold);"></i> Display Currency:
                        </span>
                        <div style="display: flex; gap: 6px;">
                            <label style="font-size: 12px; font-weight: 600; cursor: pointer; padding: 4px 10px; border-radius: 4px; background: #ffffff; border: 1px solid var(--color-sand-border); display: flex; align-items: center; gap: 4px;">
                                <input type="radio" name="currency_mode" value="USD" checked style="accent-color: var(--color-accent-gold); cursor: pointer;">
                                USD ($)
                            </label>
                            <label style="font-size: 12px; font-weight: 600; cursor: pointer; padding: 4px 10px; border-radius: 4px; background: #ffffff; border: 1px solid var(--color-sand-border); display: flex; align-items: center; gap: 4px;">
                                <input type="radio" name="currency_mode" value="LKR" style="accent-color: var(--color-accent-gold); cursor: pointer;">
                                LKR (Rs.)
                            </label>
                        </div>
                    </div>

                    <div class="calc-row">
                        <span>Nightly Suite Rate:</span>
                        <strong id="calc_room_rate">$0.00</strong>
                    </div>
                    <div class="calc-row">
                        <span>Duration:</span>
                        <strong id="calc_nights">1 Night</strong>
                    </div>
                    <div class="calc-row">
                        <span>Suite Subtotal:</span>
                        <strong id="calc_room_subtotal">$0.00</strong>
                    </div>
                    <div class="calc-row" id="calc_discount_row">
                        <span id="calc_discount_label">Long-stay Discount:</span>
                        <strong id="calc_discount" style="color:var(--color-olive);">-$0.00</strong>
                    </div>
                    <div class="calc-row">
                        <span>Selected Add-ons:</span>
                        <strong id="calc_addons_subtotal" style="color:var(--color-olive);">$0.00</strong>
                    </div>
                    <div class="calc-row">
                        <span>Subtotal (Net):</span>
                        <strong id="calc_net_subtotal">$0.00</strong>
                    </div>
                    <div class="calc-row" style="color: var(--color-text-muted);">
                        <span>Government VAT (<?php echo $vatPercent; ?>%):</span>
                        <strong id="calc_vat">$0.00</strong>
                    </div>

                    <div class="calc-row calc-total">
                        <span>Grand Total:</span>
                        <span class="total-amount" id="calc_grand_total">$0.00</span>
                    </div>

                    <div id="calc_converted_note" style="text-align: right; font-size: 11.5px; color: var(--color-text-muted); margin-top: 4px;">
                        &approx; Rs. 0.00
                    </div>

                    <input type="hidden" name="client_grand_total" id="hidden_grand_total" value="0.00">

                    <div style="margin-top: 25px;">
                        <button type="submit" id="btn_submit_booking" class="btn btn-gold" style="width: 100%; padding: 14px; font-size: 13px;">
                            <i class="fa-solid fa-lock"></i> Confirm Reservation
                        </button>
                    </div>

                    <p style="font-size: 11px; color: var(--color-text-muted); margin-top: 15px; text-align: center;">
                        <i class="fa-solid fa-shield-halved"></i> No advance charge. Overlap availability verified server-side.
                    </p>
                </div>
            </div>

        </div>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const USD_RATE = <?php echo $exchangeRate; ?>;
    const roomSelect = document.getElementById('room_select');
    const ci = document.getElementById('check_in');
    const co = document.getElementById('check_out');
    const addonCheckboxes = document.querySelectorAll('.addon-checkbox');
    const currencyRadios = document.querySelectorAll('input[name="currency_mode"]');

    function getSelectedCurrency() {
        const checked = document.querySelector('input[name="currency_mode"]:checked');
        return checked ? checked.value : 'USD';
    }

    // Format single clean amount based on user selection
    function formatCurrency(usdAmount) {
        const mode = getSelectedCurrency();
        if (mode === 'LKR') {
            const lkr = usdAmount * USD_RATE;
            return 'Rs. ' + lkr.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        } else {
            return '$' + usdAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    }

    function updateCalculator() {
        const selectedOption = roomSelect.options[roomSelect.selectedIndex];
        const roomPriceUSD = parseFloat(selectedOption.getAttribute('data-price')) || 0;

        let nights = 1;
        if (ci.value && co.value) {
            const d1 = new Date(ci.value + 'T00:00:00');
            const d2 = new Date(co.value + 'T00:00:00');
            const diff = Math.round((d2 - d1) / (1000 * 60 * 60 * 24));
            if (diff > 0) {
                nights = diff;
            }
        }

        const roomSubtotalUSD = roomPriceUSD * nights;

        let discountPct = 0;
        if (nights === 2) {
            discountPct = 5;
        } else if (nights >= 3) {
            discountPct = 10;
        }

        const discountUSD = roomSubtotalUSD * (discountPct / 100);

        let addonsSubtotalUSD = 0;
        addonCheckboxes.forEach(function (cb) {
            if (cb.checked) {
                addonsSubtotalUSD += parseFloat(cb.getAttribute('data-price')) || 0;
            }
        });

        const netSubtotalUSD = (roomSubtotalUSD - discountUSD) + addonsSubtotalUSD;
        const vatUSD = netSubtotalUSD * <?php echo $vatRate; ?>;
        const grandTotalUSD = netSubtotalUSD + vatUSD;

        const mode = getSelectedCurrency();

        document.getElementById('calc_room_rate').innerText = formatCurrency(roomPriceUSD);
        document.getElementById('calc_nights').innerText = nights + (nights === 1 ? ' Night' : ' Nights');
        document.getElementById('calc_room_subtotal').innerText = formatCurrency(roomSubtotalUSD);

        document.getElementById('calc_discount_label').innerText = discountPct > 0
            ? 'Long-stay Discount (' + discountPct + '% OFF):'
            : 'Long-stay Discount:';
        document.getElementById('calc_discount').innerText = '-' + formatCurrency(discountUSD);

        document.getElementById('calc_addons_subtotal').innerText = formatCurrency(addonsSubtotalUSD);
        document.getElementById('calc_net_subtotal').innerText = formatCurrency(netSubtotalUSD);
        document.getElementById('calc_vat').innerText = formatCurrency(vatUSD);
        document.getElementById('calc_grand_total').innerText = formatCurrency(grandTotalUSD);
        document.getElementById('hidden_grand_total').value = grandTotalUSD.toFixed(2);

        // Show a subtle equivalent note at the bottom
        const noteEl = document.getElementById('calc_converted_note');
        if (noteEl) {
            if (mode === 'USD') {
                const lkrEquiv = grandTotalUSD * USD_RATE;
                noteEl.innerText = '≈ Rs. ' + lkrEquiv.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' (1 USD = Rs. ' + USD_RATE + ')';
            } else {
                noteEl.innerText = '≈ $' + grandTotalUSD.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' USD (Rate: 1 USD = Rs. ' + USD_RATE + ')';
            }
        }
    }

    function syncMin() {
        if (!ci.value) { return; }
        var d = new Date(ci.value + 'T00:00:00');
        d.setDate(d.getDate() + 1);
        var min = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
        co.min = min;
        if (co.value && co.value < min) {
            co.value = min;
        }
        updateCalculator();
    }

    currencyRadios.forEach(function (radio) {
        radio.addEventListener('change', updateCalculator);
    });
    roomSelect.addEventListener('change', updateCalculator);
    ci.addEventListener('change', syncMin);
    co.addEventListener('change', updateCalculator);
    addonCheckboxes.forEach(function (cb) {
        cb.addEventListener('change', updateCalculator);
    });

    // Client-side validation for Policies Agreement
    const bookingForm = document.getElementById('booking_form');
    const agreePolicies = document.getElementById('agree_policies');
    if (bookingForm && agreePolicies) {
        bookingForm.addEventListener('submit', function (e) {
            if (!agreePolicies.checked) {
                e.preventDefault();
                alert('Please read and agree to the Villa Policies & House Rules before confirming your reservation.');
                agreePolicies.focus();
                agreePolicies.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    }

    // Tick the experiences added from the home page cart
try {
    var cart = JSON.parse(localStorage.getItem('villa_cart')) || [];
    var names = [];
    addonCheckboxes.forEach(function (cb) {
        if (cart.indexOf(parseInt(cb.value, 10)) !== -1) {
            cb.checked = true;
            var nameEl = cb.closest('label').querySelector('.addon-name');
            if (nameEl) { names.push(nameEl.textContent.trim()); }
        }
    });
    if (names.length > 0) {
        var notice = document.getElementById('cart_notice');
        var list = document.getElementById('cart_notice_list');
        list.innerHTML = '';
        names.forEach(function (n) {
            var chip = document.createElement('span');
            chip.className = 'offer-chip';
            chip.style.cssText = 'display:inline-block; margin:0 6px 6px 0; background:#fff; color:#5b4a1f;';
            chip.textContent = n;
            list.appendChild(chip);
        });
        notice.style.display = 'flex';
    }
} catch (e) {}

syncMin();
updateCalculator();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
