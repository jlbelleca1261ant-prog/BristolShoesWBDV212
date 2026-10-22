<?php require __DIR__ . '/includes/common.php'; $csrfToken = csrf_token(); ?>
<!DOCTYPE html>

<html lang="en">

<head>

<script async src="https://www.googletagmanager.com/gtag/js?id=G-ZRHDGZWT0E"></script>
    <script>
        // Ini-initialize ang Google Analytics.
        // dataLayer = array na nag-iimbak ng lahat ng events (clicks, page loads, etc.)
        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); } // gtag() = function para mag-send ng data sa Google
        gtag('js', new Date());          // I-record ang oras ng page load
        gtag('config', 'G-ZRHDGZWT0E'); // I-connect sa ating Analytics account
    </script>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<meta name="description" content="Contact Bristol Shoes Marikina - visit our store or send us a message.">

<title>Contact Us - Bristol Shoes Marikina</title>

<link rel="icon" type="image/svg+xml" href="images/favicon.svg">

<link rel="stylesheet" href="css/style.css">
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
                <li><a href="products.php">Products</a></li>
                <li><a href="contact.php" class="active">Contact</a></li>
                <li><a href="account.php">Account</a></li>
            </ul>

<div style="display: flex; align-items: center; gap: 8px;">
                
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
        <a href="products.php">Products</a>
        <a href="contact.php" class="active">Contact</a>
    
        <a href="account.php">Account</a></div>

<section class="page-header">
        <div class="wrap">
            
            <h1>Contact <em>Us</em></h1>
            <p>We'd love to hear from you. Visit our store or send us a message.</p>
        </div>
    </section>

<section class="section-white">
        <div class="wrap">
            <div class="contact-row">

<div>
                    <h3>Send Us a Message</h3>

<div id="form-success" class="form-success" style="display:none;">
                        <span class="success-icon">✅</span>
                        <h3>Message Received!</h3>
                        <p>Thank you. Your message has been saved.</p>
                    </div>

<form id="contact-form">
                        <p id="contact-error" role="alert" hidden></p>

<div class="form-group">
                            <label for="name">Your Name</label>
                            
                            <input type="text" id="name" name="name" autocomplete="name" maxlength="160" placeholder="Juan Dela Cruz" required>
                            
                            <span class="field-error"></span>
                        </div>

<div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" autocomplete="email" maxlength="190" placeholder="juan@email.com" required>
                            <span class="field-error"></span>
                        </div>

<div class="form-group">
                            <label for="phone">Phone Number <span class="optional">(Optional)</span></label>
                            
                            <input type="tel" id="phone" name="phone" autocomplete="tel" maxlength="20" placeholder="09XXXXXXXXX or +639XXXXXXXXX">
                            <span class="field-error"></span>
                        </div>

<div class="form-group">
                            <label for="message">Your Message</label>
                            
                            <textarea id="message" name="message" placeholder="How can we help you?"
                                maxlength="5000" required></textarea>
                            <span class="field-error"></span>
                        </div>

<button type="submit" class="btn">Send Message</button>
                    </form>
                </div>

<div>
                    <h3>Store Information</h3>

<div class="info-item">
                        <div class="info-icon">📍</div>
                        <div>
                            <h4>Address</h4>
                            <p>#35 Caimito St., Amang Rodriguez Subdivision, Concepcion Uno, Marikina City, Philippines
                                1807</p>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon">📞</div>
                        <div>
                            <h4>Phone</h4>
                            <p>+630345027</p>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon">✉️</div>
                        <div>
                            <h4>Email</h4>
                            <p>bristolshoes@hotmail.com</p>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon">🕐</div>
                        <div>
                            <h4>Store Hours</h4>
                            <p>Mon–Sat: 9:00 AM – 7:00 PM</p>
                            <p>Sunday: 10:00 AM – 6:00 PM</p>
                        </div>
                    </div>
                </div>

            </div>

<div class="map">
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3860.5!2d121.1134!3d14.6407!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397b9f!2sConcepcion%20Uno%2C%20Marikina!5e0!3m2!1sen!2sph!4v1"
                    allowfullscreen="" loading="lazy" title="Bristol Shoes Marikina on Google Maps">
                </iframe>
            </div>
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
                        City, Philippines 1807.</p>
                </div>
            </div>

<div class="footer-bottom">
                <p>&copy; 2026 Bristol Shoes &amp; Shoe Factory. All rights reserved.</p>
            </div>
        </div>
    </footer>

<script>window.BRISTOL_CSRF = <?php echo json_encode($csrfToken); ?>;</script>
    <script src="js/form.js"></script>

    <script src="js/theme.js"></script>
    <script src="js/scroll.js"></script>

</body>

</html>
