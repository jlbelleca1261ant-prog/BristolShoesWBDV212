<?php
require __DIR__ . '/includes/common.php';
$csrfToken = csrf_token();
$shopper = current_user();
$locations = json_decode(file_get_contents(__DIR__ . '/data/ph-locations.json'), true)['provinces'];
$products = [];
$catalogError = false;
try {
    $rows = db()->query('SELECT * FROM products WHERE is_active = 1 ORDER BY id')->fetchAll();
    foreach ($rows as $row) {
        $products[] = product_for_browser($row);
    }
} catch (Throwable $error) {
    error_log((string)$error);
    $catalogError = true;
}
?>
<!DOCTYPE html>

<html lang="en">

<head>

<script async src="https://www.googletagmanager.com/gtag/js?id=G-ZRHDGZWT0E"></script>
    <script>
        // dataLayer = array na nag-iimbak ng lahat ng analytics events (page views, clicks, etc.)
        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); } // gtag() = function para mag-send ng data sa Google
        gtag('js', new Date());          // I-record ang oras ng page load
        gtag('config', 'G-ZRHDGZWT0E'); // I-connect sa ating Analytics account
    </script>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<meta name="description"
        content="Browse Bristol Shoes collections - handcrafted men's and women's leather shoes from Marikina.">

<title>Products - Bristol Shoes Marikina</title>

<link rel="icon" type="image/svg+xml" href="images/favicon.svg">

<link rel="stylesheet" href="css/style.css">
    
    <link rel="stylesheet" href="css/shop.css">
</head>

<body>

<nav>
        <div class="wrap">
            
            <a href="index.php" class="logo">
                <div class="logo-icon">B</div>
                <div>
                    <span class="logo-name">Bristol Shoes</span>
                    <span class="logo-sub">Marikina Branch</span>
                </div>
            </a>

<ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="products.php" class="active">Products</a></li>
                <li><a href="contact.php">Contact</a></li>
                <li><a href="account.php">Account</a></li>
            </ul>

            <div style="display: flex; align-items: center; gap: 8px;">
                
                <button id="cart-icon" class="cart-nav-btn" onclick="openCartSidebar()" aria-label="Open cart">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="21" r="1" />
                        <circle cx="20" cy="21" r="1" />
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" />
                    </svg>
                    
                    <span id="cart-badge" class="cart-badge" style="display:none">0</span>
                </button>

<button id="theme-toggle" class="theme-nav-btn" aria-label="Toggle Theme"></button>

<button class="nav-toggle" id="nav-toggle" aria-label="Menu" aria-expanded="false">
                    <span></span><span></span><span></span>
                </button>
            </div>
        </div>
    </nav>

<div class="mobile-nav-menu" id="mobile-nav-menu">
        <a href="index.php">Home</a>
        <a href="about.php">About</a>
        <a href="products.php" class="active">Products</a>
        <a href="contact.php">Contact</a>
    
        <a href="account.php">Account</a></div>

<section class="page-header">
        <div class="wrap">
            <h1>Our <em>Collections</em></h1>
            <p>Handcrafted shoes for every style and occasion.</p>
        </div>
    </section>

<section class="filter-bar-wrap">
        <div class="wrap">
            <p id="shop-error" class="shop-error" role="alert" hidden></p>
            <div class="filter-bar">
                
                <button class="filter-tab active" data-filter="all" aria-pressed="true">All</button>
                <button class="filter-tab" data-filter="men" aria-pressed="false">Men's</button>
                <button class="filter-tab" data-filter="women" aria-pressed="false">Women's</button>
            </div>

            <div class="search-bar">
                
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                
                <input type="search" id="product-search" aria-label="Search products" placeholder="Search products..." autocomplete="off">
            </div>
            <label class="shop-sort">Sort by
                <select id="product-sort">
                    <option value="featured">Featured</option>
                    <option value="price-low">Price: low to high</option>
                    <option value="price-high">Price: high to low</option>
                    <option value="name">Name: A to Z</option>
                </select>
            </label>
        </div>
    </section>

<section class="section-white shop-section">
        <div class="wrap">
            <p id="product-count" class="product-count" aria-live="polite"></p>
            <div id="product-grid" class="product-grid"></div>
        </div>
    </section>

<section class="section-navy">
        <div class="wrap">
            <h2>Try Them On <em>In Person</em></h2>
            <p>Visit our Marikina branch to see and feel our shoes before buying.</p>
            <a href="contact.php" class="btn-outline">Find Our Store</a>
        </div>
    </section>

<footer>
        <div class="wrap">
            <div class="footer-row">
                <div class="footer-wide">
                    <a href="index.php" class="logo">
                        <div class="logo-icon">B</div>
                        <div>
                            <span class="logo-name">Bristol Shoes</span>
                            <span class="logo-sub">Marikina Branch</span>
                        </div>
                    </a>
                </div>
                <div class="footer-col">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="products.php">Products</a></li>
                        <li><a href="contact.php">Contact</a></li>
                <li><a href="account.php">Account</a></li>
                    </ul>
                </div>
                <div class="footer-col footer-contact">
                    <h4>Contact</h4>
                    <p><strong>Email:</strong> bristolshoes@hotmail.com</p>
                    <p><strong>Phone:</strong> +630345027</p>
                    <p><strong>Address:</strong> #35 Caimito St., Amang Rodriguez Subdivision, Concepcion Uno, Marikina
                        City 1807.</p>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2026 Bristol Shoes &amp; Shoe Factory. All rights reserved.</p>
            </div>
        </div>
    </footer>

<div id="product-modal" class="modal-overlay">
        <div class="modal-container">
            
            <button class="modal-close" onclick="closeModal()" aria-label="Close">&times;</button>
            
            <div class="modal-body"></div>
        </div>
    </div>

<div id="size-guide-modal" class="modal-overlay size-modal-overlay">
        <div class="modal-container size-container">
            <button class="modal-close" onclick="closeSizeGuide()" aria-label="Close size guide">&times;</button>
            <h2 class="checkout-title">Size Guide</h2>
            <div class="checkout-body size-body">
                <p id="size-guide-available"></p>
                <p>For the best fit, compare the EU size with a comfortable pair you already own. Sizes can fit differently across shoe styles, so we avoid claiming an exact centimeter conversion.</p>
                <p>Between sizes? <a href="contact.php">Ask our team</a> before ordering.</p>
                <button class="place-order-btn" onclick="closeSizeGuide()">Back to product</button>
            </div>
        </div>
    </div>

<div id="cart-overlay" class="cart-overlay"></div>

    <aside id="cart-sidebar" class="cart-sidebar">
        <div class="cart-header">
            <h3>Your Cart</h3>
            
            <button onclick="closeCartSidebar()" class="cart-close-btn">&times;</button>
        </div>
        <p id="cart-error" class="shop-error" role="alert" hidden></p>
        
        <div id="cart-items" class="cart-items"></div>
        
        <div id="cart-footer" class="cart-sidebar-footer" style="display:none">
            <div class="cart-total-row">
                <span>Subtotal</span>
                
                <span id="cart-total-amt">₱0</span>
            </div>
            <p class="cart-ship-note">Delivery fee: &#8369;<?= SHIPPING_FEE ?></p>
            
            <button class="cart-checkout-btn" onclick="openCheckout()">Proceed to Checkout</button>
            <button class="cart-continue-btn" onclick="closeCartSidebar()">Continue Shopping</button>
        </div>
    </aside>

<div id="checkout-modal" class="modal-overlay checkout-modal-overlay">
        <div class="modal-container checkout-container">
            <button class="modal-close" onclick="closeCheckout()">&times;</button>
            <h2 class="checkout-title">Checkout</h2>

            <form id="checkout-form" class="checkout-body" onsubmit="event.preventDefault(); placeOrder();">
                <p id="checkout-error" role="alert" hidden></p>

<div class="checkout-section">
                    <h4>Contact Information</h4>
                    <div class="checkout-row">
                        <label class="checkout-field">First name
                            <input type="text" name="first_name" autocomplete="given-name" class="checkout-input" maxlength="80" required>
                        </label>
                        <label class="checkout-field">Last name
                            <input type="text" name="last_name" autocomplete="family-name" class="checkout-input" maxlength="80" required>
                        </label>
                    </div>
                    <label class="checkout-field full">Email address
                        <input type="email" name="email" autocomplete="email" class="checkout-input" maxlength="190" required>
                    </label>
                    <label class="checkout-field full">Phone number
                        <input type="tel" name="phone" autocomplete="tel" placeholder="09XXXXXXXXX or +639XXXXXXXXX" class="checkout-input"
                            required pattern="^(\+639|09)\d{9}$">
                    </label>
                </div>

<div class="checkout-section">
                    <h4>Delivery Address</h4>
                    <label class="checkout-field full">Street address
                        <input type="text" name="address" autocomplete="street-address" class="checkout-input" maxlength="255" required>
                    </label>
                    <div class="checkout-row">
                        <label class="checkout-field">Province / Metro Manila
                            <select id="checkout-province" name="province" autocomplete="address-level1" class="checkout-input" required>
                                <option value="">Select province</option>
                                <?php foreach (array_keys($locations) as $province): ?>
                                <option value="<?= htmlspecialchars($province, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($province, ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="checkout-field">City / Municipality
                            <select id="checkout-city" name="city" autocomplete="address-level2" class="checkout-input" required disabled>
                                <option value="">Select province first</option>
                            </select>
                        </label>
                    </div>
                    <label class="checkout-field full">ZIP code
                        <input type="text" name="postal_code" autocomplete="postal-code" inputmode="numeric" pattern="[0-9]{4}" placeholder="1800" class="checkout-input" required>
                    </label>
                </div>

<div class="checkout-section">
                    <h4>Payment Method</h4>
                    <div class="pay-options">

<div class="pay-option" onclick="selectPayment(this)">
                            <input type="radio" name="payment_method" value="card" aria-label="Credit or debit card" required>
                            <div class="pay-icon">
                                <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/><path d="M6 14h2"/>
                                </svg>
                            </div>
                            <div>
                                <strong>Credit / Debit Card</strong>
                            </div>
                        </div>

<div class="pay-option" onclick="selectPayment(this)">
                            <input type="radio" name="payment_method" value="gcash" aria-label="GCash" required>
                            <div class="pay-icon">
                                <img src="images/gcash.png" alt="GCash" style="width: 100%; height: 100%; object-fit: cover; border-radius: 6px;">
                            </div>
                            <div>
                                <strong>GCash</strong>
                            </div>
                        </div>

<div class="pay-option" onclick="selectPayment(this)">
                            <input type="radio" name="payment_method" value="maya" aria-label="Maya" required>
                            <div class="pay-icon">
                                <img src="images/maya.png" alt="Maya" style="width: 100%; height: 100%; object-fit: cover; border-radius: 6px;">
                            </div>
                            <div>
                                <strong>Maya (PayMaya)</strong>
                            </div>
                        </div>

<div class="pay-option" onclick="selectPayment(this)">
                            <input type="radio" name="payment_method" value="bank" aria-label="Bank transfer" required>
                            <div class="pay-icon">
                                <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M10 18v-7"/><path d="M11.119 2.205a2 2 0 0 1 1.762 0l7.84 3.846A.5.5 0 0 1 20.5 7h-17a.5.5 0 0 1-.22-.949z"/><path d="M14 18v-7"/><path d="M18 18v-7"/><path d="M3 22h18"/><path d="M6 18v-7"/>
                                </svg>
                            </div>
                            <div>
                                <strong>Bank Transfer</strong>
                            </div>
                        </div>

<div class="pay-option" onclick="selectPayment(this)">
                            <input type="radio" name="payment_method" value="cod" aria-label="Cash on delivery" required>
                            <div class="pay-icon">
                                <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/>
                                </svg>
                            </div>
                            <div>
                                <strong>Cash on Delivery</strong>
                                <small>Pay when it arrives</small>
                            </div>
                        </div>
                    </div>
                </div>

<div class="checkout-section order-summary">
                    <h4>Order Summary</h4>
                    
                    <div id="checkout-items-list"></div>
                    <div class="summary-row"><span>Subtotal</span><span id="checkout-subtotal">₱0</span></div>
                    <div class="summary-row"><span>Shipping</span><span>&#8369;<?= SHIPPING_FEE ?></span></div>
                    
                    <div class="summary-row summary-total"><span>Total</span><span id="checkout-total">₱0</span></div>
                </div>

<button type="submit" class="place-order-btn">Place Order</button>
            </form>
        </div>
    </div>

<div id="order-toast" class="order-toast">
        <span>✅</span> Order placed! Thank you for shopping at Bristol Shoes.
    </div>

<script src="js/theme.js"></script>
    <script src="js/scroll.js"></script>
    <script>
        window.BRISTOL_PRODUCTS = <?php echo json_encode($products, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        window.BRISTOL_LOCATIONS = <?php echo json_encode($locations, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        window.BRISTOL_CSRF = <?php echo json_encode($csrfToken); ?>;
        window.BRISTOL_SHIPPING = <?= SHIPPING_FEE ?>;
        window.BRISTOL_AUTH = <?php echo $shopper ? 'true' : 'false'; ?>;
        window.BRISTOL_CATALOG_ERROR = <?php echo $catalogError ? 'true' : 'false'; ?>;
    </script>
    <script src="js/shop.js"></script>

</body>

</html>
