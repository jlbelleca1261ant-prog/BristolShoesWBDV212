<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — Bristol Shoes</title>
    <link rel="icon" href="images/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/account.css">
</head>
<body>
<nav>
    <div class="wrap">
        <a href="index.php" class="logo">
            <div class="logo-icon">B</div>
            <div><span class="logo-name">Bristol Shoes</span><span class="logo-sub">Marikina Branch</span></div>
        </a>
        <ul class="nav-links">
            <li><a href="index.php">Home</a></li>
            <li><a href="about.php">About</a></li>
            <li><a href="products.php">Products</a></li>
            <li><a href="contact.php">Contact</a></li>
            <li><a class="active" href="account.php">Account</a></li>
        </ul>
        <div class="account-nav-tools">
            <button id="theme-toggle" class="theme-nav-btn" aria-label="Toggle theme"></button>
            <button class="nav-toggle" id="nav-toggle" aria-label="Menu" aria-expanded="false"><span></span><span></span><span></span></button>
        </div>
    </div>
</nav>
<div class="mobile-nav-menu" id="mobile-nav-menu">
    <a href="index.php">Home</a><a href="about.php">About</a><a href="products.php">Products</a><a href="contact.php">Contact</a><a href="account.php">Account</a>
</div>
<main class="account-main wrap">

