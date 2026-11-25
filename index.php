<?php
/**
 * HERITAGE COASTAL VILLA - HOMEPAGE (index.php)
 * Showcase Suites, Curated Experiences, Wellness Sanctuary & Real-Time Booking Bar
 */
define('PAGE_TITLE', 'Luxury Villa & Wellness Sanctuary');
require_once __DIR__ . '/config/db.php';

// Fetch all available rooms
$stmtRooms = $pdo->query("SELECT * FROM rooms WHERE status = 'available' ORDER BY price_per_night ASC");
$rooms = $stmtRooms->fetchAll();

// Fetch active experiences / services (excluding any Chauffeur services)
$stmtServices = $pdo->query("SELECT * FROM services WHERE status = 'active' AND service_name NOT LIKE '%Chauffeur%' ORDER BY category ASC, price ASC");
$services = $stmtServices->fetchAll();

$groupedServices = [
    'free_facility' => [
        'id' => 'cat-free',
        'title' => 'Included Privilleges',
        'subtitle' => '100% Free of charge with unlimited access for all registered villa and suite guests.',
        'icon' => 'fa-gift',
        'items' => []
    ],
    'in_house' => [
        'id' => 'cat-in_house',
        'title' => 'In-House Luxuries & Villa Dining',
        'subtitle' => 'Floating Ceylon breakfast, candlelight dinners, beach picnics & artisan food platters.',
        'icon' => 'fa-hotel',
        'items' => []
    ],
    'wellness' => [
        'id' => 'cat-wellness',
        'title' => 'Ayurvedic Wellness & Herbal Spa',
        'subtitle' => 'Traditional herbal steam baths, full-body massages & royal wellness sanctuary packages.',
        'icon' => 'fa-spa',
        'items' => []
    ],
    'marine' => [
        'id' => 'cat-marine',
        'title' => 'Marine Excursions & Ocean Safaris',
        'subtitle' => 'Mirissa dolphin & whale watching, PADI scuba diving reefs & turtle sanctuary tours.',
        'icon' => 'fa-water',
        'items' => []
    ],
    'cultural' => [
        'id' => 'cat-cultural',
        'title' => 'Cultural Heritage & Plantation Tours',
        'subtitle' => 'Traditional village clay-pot cooking masterclasses & artisan cinnamon plantation estate walks.',
        'icon' => 'fa-earth-asia',
        'items' => []
    ],
    'travel' => [
        'id' => 'cat-travel',
        'title' => 'Airport Transfers & Transport',
        'subtitle' => 'Private luxury air-conditioned highway transfer pickup & drop to Bandaranaike International Airport (BIA).',
        'icon' => 'fa-car',
        'items' => []
    ]
];

// Smart Photo Resolver helper to guarantee images always show from assets/images
function resolveServiceImage($name, $currentImg) {
    if (!empty($currentImg) && file_exists(__DIR__ . '/' . $currentImg)) {
        return $currentImg;
    }
    $n = strtolower($name);
    if (strpos($n, 'hamper') !== false) return 'assets/images/ayurvedic hamper.jpeg';
    if (strpos($n, 'bar') !== false || strpos($n, 'lounge') !== false) return 'assets/images/bar.jpeg';
    if (strpos($n, 'bag') !== false) return 'assets/images/beach bag.jpeg';
    if (strpos($n, 'pool') !== false) return 'assets/images/beach font pool.jpeg';
    if (strpos($n, 'sunbed') !== false) return 'assets/images/sunbeds.jpeg';
    if (strpos($n, 'tea') !== false) return 'assets/images/ceylon tea.jpeg';
    if (strpos($n, 'floating') !== false) return 'assets/images/floating-breakfast.jpeg';
    if (strpos($n, 'candlelight') !== false || strpos($n, 'bbq') !== false) return 'assets/images/candlelight-dinner.jpeg';
    if (strpos($n, 'picnic') !== false) return 'assets/images/beach-picnic.jpeg';
    if (strpos($n, 'platter') !== false) return 'assets/images/food-platter.jpeg';
    if (strpos($n, 'massage') !== false) return 'assets/images/full-body-massage.jpeg';
    if (strpos($n, 'steam') !== false) return 'assets/images/steam-bath.jpeg';
    if (strpos($n, 'royal') !== false) return 'assets/images/royal-wellness.jpeg';
    if (strpos($n, 'scuba') !== false) return 'assets/images/scuba-diving.jpeg';
    if (strpos($n, 'dolphin') !== false || strpos($n, 'whale') !== false) return 'assets/images/dolphins.jpeg';
    if (strpos($n, 'turtle') !== false) return 'assets/images/turtles.jpeg';
    if (strpos($n, 'cooking') !== false) return 'assets/images/village-cooking.jpeg';
    if (strpos($n, 'cinnamon') !== false) return 'assets/images/cinnamon.jpeg';
    if (strpos($n, 'cinema') !== false) return 'assets/images/starlight-cinema.jpeg';
    if (strpos($n, 'airport') !== false || strpos($n, 'transfer') !== false) return 'assets/images/airport-pickup.jpeg';
    return $currentImg;
}

// Group services dynamically from database
foreach ($services as $srv) {
    $cat = $srv['category'];
    // If category is empty or was truncated, detect free facilities by $0 price
    if (($cat === '' || empty($cat) || $cat === 'Included Privilleges') && (float)$srv['price'] <= 0) {
        $cat = 'Included Privilleges';
    }
    $srv['image_url'] = resolveServiceImage($srv['service_name'], $srv['image_url']);
    if (isset($groupedServices[$cat])) {
        $groupedServices[$cat]['items'][] = $srv;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Clean Minimal Hero Section -->
<header class="hero" style="min-height: 520px; padding: 110px 20px 80px; display: flex; align-items: center; justify-content: center; text-align: center; position: relative;">
    <div class="hero-content" style="max-width: 820px; margin: 0 auto;">
        <span class="hero-tag-pill">
            <i class="fa-solid fa-gem"></i> Mirissa &bull; Southern Province &bull; Sri Lanka
        </span>
        <h1 class="hero-title" style="font-size: 42px; margin-bottom: 16px; font-family: 'Cinzel', serif; letter-spacing: 1px;">
            Experience Coastal Elegance &amp; Wellness
        </h1>
        <p class="hero-desc" style="font-size: 15px; color: #f1f2f6; line-height: 1.8; margin-bottom: 25px;">
            Nestled between cinnamon palms and Indian Ocean shores. Private plunge pool suites, Ayurvedic herbal steam sanctuaries, and hand-tailored coastal adventures.
        </p>
        <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
            <a href="#rooms" class="btn btn-outline" style="color: #fff; border-color: rgba(255,255,255,0.7); padding: 12px 26px;">
                <i class="fa-solid fa-bed"></i> Explore Suites
            </a>
            <a href="book.php" class="btn btn-gold" style="padding: 12px 28px;">
                <i class="fa-solid fa-calendar-check"></i> Book Now
            </a>
        </div>
    </div>
</header>

<!-- Category Jump Navigation Pills (Customer Quick Filter) -->
<div class="container" style="margin-top: 30px; margin-bottom: 25px; text-align: center;">
    <div style="display: inline-flex; flex-wrap: wrap; gap: 10px; justify-content: center; background: #ffffff; padding: 10px 20px; border-radius: 35px; border: 1px solid var(--color-sand-border); box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
        
        <a href="#cat-free" style="font-size: 12.5px; text-decoration: none; padding: 6px 16px; border-radius: 20px; background: #e8f5e9; color: #2e7d32; font-weight: 700; border: 1px solid #c8e6c9; transition: all 0.2s;">
            <i class="fa-solid fa-gift" style="color:#2e7d32;"></i> Included Privileges
        </a>
        <a href="#cat-in_house" style="font-size: 12.5px; text-decoration: none; padding: 6px 16px; border-radius: 20px; background: var(--color-sand-bg); color: var(--color-primary); font-weight: 600; transition: all 0.2s;">
            <i class="fa-solid fa-hotel" style="color:var(--color-accent-gold);"></i> Curated Experiences
        </a>
        <a href="#cat-wellness" style="font-size: 12.5px; text-decoration: none; padding: 6px 16px; border-radius: 20px; background: var(--color-sand-bg); color: var(--color-primary); font-weight: 600; transition: all 0.2s;">
            <i class="fa-solid fa-spa" style="color:var(--color-olive);"></i> Wellness & Spa
        </a>
        <a href="#cat-marine" style="font-size: 12.5px; text-decoration: none; padding: 6px 16px; border-radius: 20px; background: var(--color-sand-bg); color: var(--color-primary); font-weight: 600; transition: all 0.2s;">
            <i class="fa-solid fa-water" style="color:#0984e3;"></i> Marine Safaris
        </a>
        <a href="#cat-cultural" style="font-size: 12.5px; text-decoration: none; padding: 6px 16px; border-radius: 20px; background: var(--color-sand-bg); color: var(--color-primary); font-weight: 600; transition: all 0.2s;">
            <i class="fa-solid fa-earth-asia" style="color:#d35400;"></i> Cultural Visits
        </a>
        <a href="#cat-travel" style="font-size: 12.5px; text-decoration: none; padding: 6px 16px; border-radius: 20px; background: var(--color-sand-bg); color: var(--color-primary); font-weight: 600; transition: all 0.2s;">
            <i class="fa-solid fa-car" style="color:var(--color-primary);"></i> Airport Drop & Pickup

        </a>
    </div>
</div>

<!-- Highlights Banner -->
<section style="background: var(--color-sand-bg); border-top: 1px solid var(--color-sand-border); border-bottom: 1px solid var(--color-sand-border); padding: 30px 0;">
    <div class="container" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 25px; text-align: center;">
        <div>
            <i class="fa-solid fa-water" style="font-size: 26px; color: var(--color-accent-gold); margin-bottom: 8px;"></i>
            <h4 style="font-size: 15px; margin: 0; color: var(--color-primary);">Oceanfront Sanctuary</h4>
            <p style="font-size: 12px; color: var(--color-text-muted); margin-top: 4px;">Direct beach path & private plunge pools</p>
        </div>
        <div>
            <i class="fa-solid fa-spa" style="font-size: 26px; color: var(--color-olive); margin-bottom: 8px;"></i>
            <h4 style="font-size: 15px; margin: 0; color: var(--color-primary);">Ayurvedic Herbal Spa</h4>
            <p style="font-size: 12px; color: var(--color-text-muted); margin-top: 4px;">Herbal steam bath & certified wellness therapists</p>
        </div>
        <div>
            <i class="fa-solid fa-utensils" style="font-size: 26px; color: var(--color-accent-gold); margin-bottom: 8px;"></i>
            <h4 style="font-size: 15px; margin: 0; color: var(--color-primary);">Floating Ceylon Cuisine</h4>
            <p style="font-size: 12px; color: var(--color-text-muted); margin-top: 4px;">Floating breakfast & beachfront seafood BBQs</p>
        </div>
        <div>
            <i class="fa-solid fa-tags" style="font-size: 26px; color: var(--color-olive); margin-bottom: 8px;"></i>
            <h4 style="font-size: 15px; margin: 0; color: var(--color-primary);">Long-stay Privileges</h4>
            <p style="font-size: 12px; color: var(--color-text-muted); margin-top: 4px;">5% off for 2 nights &bull; 10% off for 3+ nights</p>
        </div>
    </div>
</section>

<!-- Suites & Villas Section -->
<section id="rooms" class="container" style="margin-top: 70px;">
    <div class="section-header">
        <span style="font-size: 11px; letter-spacing: 3px; text-transform: uppercase; color: var(--color-accent-gold); font-weight: 700;">Accommodations</span>
        <h2 class="section-title">Suites & Private Villas</h2>
        <div class="batik-divider"></div>
        <p class="section-subtitle">Each suite is an artisan sanctuary crafted from native Ceylon teak, rattan textures, and expansive views.</p>
    </div>

    <div class="rooms-grid">
        <?php foreach ($rooms as $room): ?>
            <div class="room-card">
                <div class="card-carousel">
                    <img src="<?php echo e($room['image_url']); ?>" alt="<?php echo e($room['room_name']); ?>" class="carousel-img">
                    <span class="room-type-badge"><?php echo e($room['room_type']); ?></span>
                </div>
                <div class="room-card-body">
                    <div>
                        <h3 class="room-card-title"><?php echo e($room['room_name']); ?></h3>
                        
                        <!-- Clean Simple View: Price prominent, details expandable -->
                        <div style="margin: 6px 0 14px; display: flex; align-items: baseline; gap: 8px;">
                            <span style="font-size: 24px; font-weight: 700; color: var(--color-primary); font-family: 'Cinzel', serif;">
                                $<?php echo number_format($room['price_per_night'], 2); ?>
                            </span>
                            <span style="font-size: 12px; color: var(--color-text-muted);">
                                / night (&approx; Rs. <?php echo number_format($room['price_per_night'] * (defined('USD_TO_LKR') ? USD_TO_LKR : 320), 2); ?>)
                            </span>
                        </div>

                        <!-- Collapsible Details Toggle -->
                        <details style="margin-bottom: 15px; font-size: 13px; color: var(--color-text-muted); cursor: pointer;">
                            <summary style="font-weight: 600; color: var(--color-olive); outline: none; margin-bottom: 8px;">
                                <i class="fa-solid fa-circle-info"></i> View Suite Amenities &amp; Details
                            </summary>
                            <p style="margin-top: 8px; line-height: 1.6;"><?php echo e($room['description']); ?></p>
                            <div class="room-card-meta" style="margin-top: 10px; display: flex; flex-wrap: wrap; gap: 8px;">
                                <span><i class="fa-solid fa-water-ladder" style="color: #0984e3;"></i> Free Pool Access</span>
                                <span><i class="fa-solid fa-kitchen-set" style="color: var(--color-accent-gold);"></i> Kitchenette / Kitchen</span>
                                <span><i class="fa-solid fa-umbrella-beach" style="color: var(--color-olive);"></i> Front Beach View</span>
                                <span><i class="fa-solid fa-user-group"></i> Max <?php echo (int)$room['max_guests']; ?> Guests</span>
                                <span><i class="fa-solid fa-wifi"></i> High-Speed WiFi</span>
                            </div>
                        </details>
                    </div>

                    <div class="room-card-footer">
                        <a href="book.php?room_id=<?php echo (int)$room['id']; ?>" class="btn btn-gold btn-block" style="display: block; width: 100%; text-align: center; padding: 12px 18px; font-size: 13px; font-weight: 600; box-sizing: border-box;">
                            <i class="fa-solid fa-calendar-check"></i> Reserve This Suite
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Categorized Experiences & Wellness Section -->
<section id="experiences" style="background: var(--color-sand-bg); border-top: 1px solid var(--color-sand-border); border-bottom: 1px solid var(--color-sand-border); margin-top: 80px; padding: 70px 0;">
    <div class="container">
        <div class="section-header" style="margin-bottom: 45px;">
            <span style="font-size: 11px; letter-spacing: 3px; text-transform: uppercase; color: var(--color-olive); font-weight: 700;">Curated Island Living</span>
            <h2 class="section-title">Wellness, Marine & Coastal Experiences</h2>
            <div class="batik-divider"></div>
            <p class="section-subtitle">Select any signature experience to add directly to your stay. Click "View Details" to see the full itinerary.</p>
        </div>

        <?php foreach ($groupedServices as $catKey => $category): ?>
            <?php if (!empty($category['items'])): ?>
                <div id="<?php echo $category['id']; ?>" style="margin-bottom: 55px; scroll-margin-top: 80px;">
                    <!-- Category Header -->
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 22px; border-bottom: 2px solid rgba(197, 168, 105, 0.4); padding-bottom: 10px;">
                        <div>
                            <h3 style="font-family: 'Cinzel', serif; font-size: 21px; margin: 0; color: <?php echo $catKey === 'free_facility' ? '#1b5e20' : 'var(--color-primary)'; ?>;">
                                <i class="fa-solid <?php echo $category['icon']; ?>" style="color: <?php echo $catKey === 'free_facility' ? '#2e7d32' : 'var(--color-accent-gold)'; ?>; margin-right: 8px;"></i>
                                <?php echo e($category['title']); ?>
                            </h3>
                            <p style="font-size: 13px; color: var(--color-text-muted); margin: 4px 0 0;"><?php echo e($category['subtitle']); ?></p>
                        </div>
                        <span style="font-size: 12px; font-weight: 600; color: <?php echo $catKey === 'free_facility' ? '#2e7d32' : 'var(--color-olive)'; ?>; background: #ffffff; padding: 5px 14px; border-radius: 20px; border: 1px solid var(--color-sand-border);">
                            <?php echo count($category['items']); ?> Options
                        </span>
                    </div>

                    <!-- Clean Simple Cards Grid -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(290px, 1fr)); gap: 22px;">
                        <?php foreach ($category['items'] as $srv): ?>
                            <div style="background: #ffffff; border: 1px solid var(--color-sand-border); border-radius: 6px; overflow: hidden; display: flex; flex-direction: column; box-shadow: 0 4px 14px rgba(0,0,0,0.04); transition: transform 0.2s ease;">
                                <?php if (!empty($srv['image_url'])): ?>
                                    <div style="height: 250px; overflow: hidden; position: relative;">
                                        <img src="<?php echo e($srv['image_url']); ?>" alt="<?php echo e($srv['service_name']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                        <span style="position: absolute; bottom: 12px; left: 12px; background: rgba(18, 18, 18, 0.82); color: #fff; font-size: 11.5px; padding: 4px 10px; border-radius: 4px; backdrop-filter: blur(3px);">
                                            <i class="fa-solid fa-clock"></i> <?php echo e($srv['pricing_unit']); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                <div style="padding: 18px; flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                                    <div>
                                        <h4 style="font-size: 16px; min-height: 44px; display: flex; align-items: center; margin: 0 0 10px; color: var(--color-primary); font-family: 'Cinzel', serif; line-height: 1.35;">
                                            <?php echo e($srv['service_name']); ?>
                                        </h4>
                                        
                                        <!-- Clean Prominent Price Tag -->
                                        <div style="display: flex; align-items: baseline; gap: 8px; margin-bottom: 12px;">
                                            <?php if ((float)$srv['price'] <= 0): ?>
                                                <span style="font-size: 18px; font-weight: 700; color: #2e7d32; font-family: 'Cinzel', serif;">
                                                    <i class="fa-solid fa-gift"></i> Included Privilleges
                                                </span>
                                            <?php else: ?>
                                                <span style="font-size: 22px; font-weight: 700; color: var(--color-accent-gold); font-family: 'Cinzel', serif;">
                                                    $<?php echo number_format($srv['price'], 2); ?>
                                                </span>
                                                <span style="font-size: 11.5px; color: var(--color-text-muted);">
                                                    (&approx; Rs. <?php echo number_format($srv['price'] * (defined('USD_TO_LKR') ? USD_TO_LKR : 320), 2); ?>)
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Collapsible Detailed Description -->
                                        <details style="margin-bottom: 15px; font-size: 12.5px; color: var(--color-text-muted); cursor: pointer;">
                                            <summary style="font-weight: 600; color: var(--color-olive); outline: none; margin-bottom: 6px;">
                                                <i class="fa-solid fa-circle-chevron-down"></i> Read More Details
                                            </summary>
                                            <p style="margin-top: 6px; line-height: 1.5; color: var(--color-text-dark); background: var(--color-sand-bg); padding: 10px; border-radius: 4px; border-left: 3px solid var(--color-olive);">
                                                <?php echo e($srv['description']); ?>
                                            </p>
                                        </details>
                                    </div>

                                    <div style="border-top: 1px solid var(--color-sand-border); padding-top: 14px; margin-top: auto;">
                                        <?php if ((float)$srv['price'] <= 0): ?>
                                            <div style="text-align: center; font-size: 12px; font-weight: 700; color: #2e7d32; background: #e8f5e9; padding: 10px 14px; border-radius: 4px; border: 1px solid #c8e6c9;">
                                                <i class="fa-solid fa-circle-check"></i> Free with Any Suite
                                            </div>
                                        <?php else: ?>
                                            <a href="book.php" class="btn btn-gold btn-block" style="display: block; width: 100%; text-align: center; font-size: 12.5px; font-weight: 600; padding: 10px 14px; box-sizing: border-box;">
                                                <i class="fa-solid fa-plus"></i> Add to cart
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</section>

<!-- Call to Action Banner -->
<section class="container" style="margin: 80px auto; text-align: center; padding: 60px 20px; background: linear-gradient(135deg, var(--color-primary) 0%, #1a252f 100%); color: #ffffff; border-radius: 8px;">
    <span style="font-size: 11px; letter-spacing: 3px; text-transform: uppercase; color: var(--color-accent-gold); font-weight: 700;">Reservations Open</span>
    <h2 style="font-family: 'Cinzel', serif; font-size: 32px; margin: 15px 0 10px;">Ready for Your Coastal Escape?</h2>
    <p style="color: #bdc3c7; max-width: 600px; margin: 0 auto 30px; font-size: 14px;">
        Book directly online for instant room lock, complimentary wellness consultation, and guaranteed best rates.
    </p>
    <a href="book.php" class="btn btn-gold" style="padding: 14px 35px; font-size: 14px;">
        <i class="fa-solid fa-calendar-check"></i> Reserve Your Stay
    </a>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const barCi = document.getElementById('bar_ci');
    const barCo = document.getElementById('bar_co');
    if (barCi && barCo) {
        barCi.addEventListener('change', function () {
            if (!barCi.value) return;
            const d = new Date(barCi.value + 'T00:00:00');
            d.setDate(d.getDate() + 1);
            const min = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
            barCo.min = min;
            if (barCo.value && barCo.value < min) {
                barCo.value = min;
            }
        });
    }
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var KEY = 'villa_cart';
    var bar = document.getElementById('cart_bar');
    var countEl = document.getElementById('cart_count');
    var buttons = document.querySelectorAll('.add-to-cart');

    function load() {
        try { return JSON.parse(localStorage.getItem(KEY)) || []; } catch (e) { return []; }
    }
    function save(list) {
        try { localStorage.setItem(KEY, JSON.stringify(list)); } catch (e) {}
    }

    function render() {
        var cart = load();
        countEl.textContent = cart.length;
        bar.classList.toggle('show', cart.length > 0);
        buttons.forEach(function (btn) {
            var added = cart.indexOf(parseInt(btn.dataset.id, 10)) !== -1;
            btn.classList.toggle('is-added', added);
            btn.querySelector('span').textContent = added ? 'Added (click to remove)' : 'Add to Booking';
            btn.querySelector('i').className = added ? 'fa-solid fa-check' : 'fa-solid fa-plus';
        });
    }

    buttons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = parseInt(btn.dataset.id, 10);
            var cart = load();
            var pos = cart.indexOf(id);
            if (pos === -1) { cart.push(id); } else { cart.splice(pos, 1); }
            save(cart);
            render();
        });
    });

    document.getElementById('cart_clear').addEventListener('click', function () {
        save([]);
        render();
    });

    render();
});
</script>
<div class="cart-bar" id="cart_bar">
    <span><i class="fa-solid fa-cart-shopping"></i> <strong id="cart_count">0</strong> experience(s) selected</span>
    <button type="button" id="cart_clear" class="cart-clear">Clear</button>
    <a href="book.php" class="btn btn-gold btn-sm">Go to Booking <i class="fa-solid fa-arrow-right"></i></a>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
