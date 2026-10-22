// Bristol Shoes — Shop Engine
'use strict';

// PRODUCT CATALOG
var PRODUCTS = window.BRISTOL_PRODUCTS || [];

// The cart belongs to the signed-in account and is stored in MySQL.
var CART = [];
function getCart() { return CART; }
function signInForCart(pending) {
    if (pending) sessionStorage.setItem('bristol_pending_action', JSON.stringify(pending));
    window.location.href = 'login.php?next=products.php';
}

async function cartRequest(action, details) {
    if (!window.BRISTOL_AUTH) { signInForCart(); return; }
    var response = await fetch('process_cart.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': window.BRISTOL_CSRF},
        body: JSON.stringify(Object.assign({action: action}, details || {}))
    });
    var result = await response.json();
    if (!response.ok) throw new Error(result.error || 'Could not update your cart.');
    CART = result.items;
    updateCartBadge();
    renderCartSidebar();
}

async function loadCart() {
    if (!window.BRISTOL_AUTH) return;
    try {
        var response = await fetch('process_cart.php');
        if (!response.ok) throw new Error('Could not load your cart.');
        CART = (await response.json()).items;
        updateCartBadge();
    } catch (error) { showShopError(error.message); }
}

function showShopError(message) {
    var sidebar = document.getElementById('cart-sidebar');
    var box = document.getElementById(sidebar && sidebar.classList.contains('open') ? 'cart-error' : 'shop-error');
    if (!box) return;
    box.textContent = message;
    box.hidden = false;
}

async function addToCart(productId, size) {
    if (!window.BRISTOL_AUTH) { signInForCart({action: 'add', id: productId, size: size}); return false; }
    await cartRequest('add', {id: productId, size: size});
    animateCartIcon();
    return true;
}
async function removeFromCart(key) {
    var item = CART.find(function (row) { return row.key === key; });
    if (!item) return;
    try { await cartRequest('remove', {id: item.id, size: item.size}); }
    catch (error) { showShopError(error.message); }
}
async function changeQty(key, delta) {
    var item = CART.find(function (row) { return row.key === key; });
    if (!item) return;
    try {
        await cartRequest(item.qty + delta < 1 ? 'remove' : 'set',
            {id: item.id, size: item.size, qty: Math.min(10, item.qty + delta)});
    } catch (error) { showShopError(error.message); }
}
function cartTotal() {
    return getCart().reduce(function (sum, i) { return sum + i.price * i.qty; }, 0);
}
function cartCount() {
    return getCart().reduce(function (sum, i) { return sum + i.qty; }, 0);
}
function updateCartBadge() {
    var badge = document.getElementById('cart-badge');
    if (!badge) return;
    var n = cartCount();
    badge.textContent = n;
    badge.style.display = n > 0 ? 'flex' : 'none';
}
function animateCartIcon() {
    var icon = document.getElementById('cart-icon');
    if (!icon) return;
    icon.classList.remove('cart-pop');
    void icon.offsetWidth;
    icon.classList.add('cart-pop');
}


// RENDER PRODUCT GRID
function formatPrice(n) { return '₱' + n.toLocaleString('en-PH'); }
function escapeHtml(value) {
    return String(value).replace(/[&<>"']/g, function (char) {
        return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[char];
    });
}

function renderProducts() {
    var grid = document.getElementById('product-grid');
    if (!grid) return;

    var activeTab = document.querySelector('.filter-tab.active');
    var filter = activeTab ? activeTab.dataset.filter : 'all';
    
    var searchInput = document.getElementById('product-search');
    var query = searchInput ? searchInput.value.toLowerCase().trim() : '';

    var list = filter === 'all' ? PRODUCTS : PRODUCTS.filter(function (p) { return p.category === filter; });
    
    if (query) {
        list = list.filter(function (p) {
            return p.name.toLowerCase().includes(query) || p.type.toLowerCase().includes(query);
        });
    }
    list = list.slice();
    var sort = document.getElementById('product-sort').value;
    if (sort === 'price-low') list.sort(function (a, b) { return a.price - b.price; });
    if (sort === 'price-high') list.sort(function (a, b) { return b.price - a.price; });
    if (sort === 'name') list.sort(function (a, b) { return a.name.localeCompare(b.name); });
    document.getElementById('product-count').textContent = list.length + (list.length === 1 ? ' product' : ' products');

    if (list.length === 0) {
        grid.innerHTML = '<div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: var(--text-muted);">' +
            (window.BRISTOL_CATALOG_ERROR ? 'Products are temporarily unavailable. Please try again later.' : 'No products found matching your search.') + '</div>';
        return;
    }

    grid.innerHTML = list.map(function (p) {
        var badge = p.sale ? '<span class="p-badge">SALE</span>' : '';
        var originalPrice = p.originalPrice ? '<s class="p-original">' + formatPrice(p.originalPrice) + '</s> ' : '';
        return '<div class="product" role="button" tabindex="0" aria-label="View ' + escapeHtml(p.name) + '" data-id="' + p.id + '" onclick="openModal(' + p.id + ')" onkeydown="if(event.key===\'Enter\'||event.key===\' \'){event.preventDefault();openModal(' + p.id + ')}">'
            + badge
            + '<div class="product-thumb"><img src="' + escapeHtml(p.img) + '" alt="' + escapeHtml(p.name) + '" loading="lazy" onerror="this.src=\'images/product_mens_1.png\'">'
            + '<div class="product-hover-overlay"><span>Quick View</span></div></div>'
            + '<div class="product-info">'
            + '<small>' + escapeHtml(p.type) + '</small>'
            + '<h3>' + escapeHtml(p.name) + '</h3>'
            + '<div class="p-price">' + originalPrice + '<strong>' + formatPrice(p.price) + '</strong></div>'
            + '</div></div>';
    }).join('');
}


// FILTER TABS & SEARCH
function initFilters() {
    var tabs = document.querySelectorAll('.filter-tab');
    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            tabs.forEach(function (t) { t.classList.remove('active'); });
            tabs.forEach(function (t) { t.setAttribute('aria-pressed', 'false'); });
            tab.classList.add('active');
            tab.setAttribute('aria-pressed', 'true');
            renderProducts();
        });
    });

    var searchInput = document.getElementById('product-search');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            renderProducts();
        });
    }
    document.getElementById('product-sort').addEventListener('change', renderProducts);
}


// QUICK-VIEW MODAL
var currentProduct = null;
var selectedSize = null;
var buyNowItem = null;

function openModal(id) {
    currentProduct = PRODUCTS.find(function (p) { return p.id === id; });
    if (!currentProduct) return;
    selectedSize = null;

    var modal = document.getElementById('product-modal');
    var p = currentProduct;

    // Sale badge
    var saleBadge = p.sale ? '<span class="modal-badge">SALE</span>' : '';
    var saveLine = (p.originalPrice)
        ? '<p class="modal-save">You Save: ' + formatPrice(p.originalPrice - p.price) + ' (' + Math.round((1 - p.price / p.originalPrice) * 100) + '%)</p>'
        : '';
    var originalLine = (p.originalPrice)
        ? '<s class="modal-original">' + formatPrice(p.originalPrice) + '</s> '
        : '';

    // Size buttons
    var sizeBtns = p.sizes.map(function (s) {
        var unavail = p.unavailable.indexOf(s) > -1;
        return '<button class="size-btn' + (unavail ? ' unavail' : '') + '" data-size="' + s + '"'
            + (unavail ? ' disabled title="Out of stock"' : '')
            + '>' + s + '</button>';
    }).join('');

    // Thumbnail strip
    var thumbs = p.imgs.map(function (src, i) {
        return '<img class="modal-thumb' + (i === 0 ? ' active' : '') + '" src="' + escapeHtml(src) + '" alt="" onerror="this.src=\'images/product_mens_1.png\'" onclick="switchModalImg(this, this.src)">';
    }).join('');

    modal.querySelector('.modal-body').innerHTML =
        '<div class="modal-imgs">'
        + '<img id="modal-main-img" src="' + escapeHtml(p.imgs[0]) + '" alt="' + escapeHtml(p.name) + '" onerror="this.src=\'images/product_mens_1.png\'">'
        + '<div class="modal-thumbs">' + thumbs + '</div>'
        + '</div>'
        + '<div class="modal-details">'
        + saleBadge
        + '<p class="modal-type">' + escapeHtml(p.type) + '</p>'
        + '<h2 class="modal-name">' + escapeHtml(p.name) + '</h2>'
        + '<div class="modal-price">' + originalLine + '<span class="modal-price-main">' + formatPrice(p.price) + '</span></div>'
        + saveLine
        + '<p class="modal-desc">' + escapeHtml(p.desc) + '</p>'
        + '<div class="modal-sizes"><p class="modal-size-label">EU SIZE <button type="button" class="size-guide-link" onclick="openSizeGuide()">Size guide</button></p>'
        + '<div class="size-grid">' + sizeBtns + '</div></div>'
        + '<p id="modal-size-err" class="modal-size-err"></p>'
        + '<button class="modal-atc-btn" onclick="modalAddToCart()">Add to Cart</button>'
        + '<button class="modal-buy-btn" onclick="modalBuyNow()">Buy Now</button>'
        + '</div>';

    // Attach size selector events
    modal.querySelectorAll('.size-btn:not(.unavail)').forEach(function (btn) {
        btn.addEventListener('click', function () {
            modal.querySelectorAll('.size-btn').forEach(function (b) { b.classList.remove('selected'); });
            btn.classList.add('selected');
            selectedSize = parseInt(btn.dataset.size);
            document.getElementById('modal-size-err').textContent = '';
        });
    });

    modal.classList.add('open');
    document.body.style.overflow = 'hidden';
}

function switchModalImg(thumb, src) {
    document.getElementById('modal-main-img').src = src;
    document.querySelectorAll('.modal-thumb').forEach(function (t) { t.classList.remove('active'); });
    thumb.classList.add('active');
}

function closeModal() {
    document.getElementById('product-modal').classList.remove('open');
    document.body.style.overflow = '';
}

function openSizeGuide() {
    if (!currentProduct) return;
    var sizes = currentProduct.sizes.filter(function (size) {
        return !currentProduct.unavailable.includes(size);
    }).join(', ');
    document.getElementById('size-guide-available').textContent =
        'Available EU sizes for ' + currentProduct.name + ': ' + sizes + '.';
    document.getElementById('size-guide-modal').classList.add('open');
    document.querySelector('#size-guide-modal .modal-close').focus();
}

function closeSizeGuide() {
    document.getElementById('size-guide-modal').classList.remove('open');
    var link = document.querySelector('#product-modal .size-guide-link');
    if (link) link.focus();
}

async function modalAddToCart() {
    if (!selectedSize) {
        document.getElementById('modal-size-err').textContent = 'Please select a size first.';
        return;
    }
    try {
        if (await addToCart(currentProduct.id, selectedSize)) {
            closeModal();
            openCartSidebar();
        }
    } catch (error) {
        document.getElementById('modal-size-err').textContent = error.message;
    }
}

function modalBuyNow() {
    if (!selectedSize) {
        document.getElementById('modal-size-err').textContent = 'Please select a size first.';
        return;
    }
    if (!window.BRISTOL_AUTH) {
        signInForCart({action: 'buy_now', id: currentProduct.id, size: selectedSize});
        return;
    }
    var product = PRODUCTS.find(function (p) { return p.id === currentProduct.id; });
    if (!product) return;
    
    buyNowItem = {
        id: product.id,
        name: product.name,
        type: product.type,
        price: product.price,
        size: selectedSize,
        img: product.img,
        qty: 1
    };
    
    closeModal();
    openCheckout(true);
}


// CART SIDEBAR
function openCartSidebar() {
    renderCartSidebar();
    document.getElementById('cart-sidebar').classList.add('open');
    document.getElementById('cart-overlay').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeCartSidebar() {
    document.getElementById('cart-sidebar').classList.remove('open');
    document.getElementById('cart-overlay').classList.remove('open');
    document.body.style.overflow = '';
}
function renderCartSidebar() {
    var cart = getCart();
    var list = document.getElementById('cart-items');
    var footer = document.getElementById('cart-footer');
    if (!list) return;

    if (cart.length === 0) {
        list.innerHTML = window.BRISTOL_AUTH
            ? '<div class="cart-empty"><p>Your cart is empty.</p><a href="#" onclick="closeCartSidebar()">Continue Shopping</a></div>'
            : '<div class="cart-empty"><p>Sign in to save your picks.</p><a href="login.php?next=products.php">Sign in or create an account</a></div>';
        footer.style.display = 'none';
        return;
    }

    list.innerHTML = cart.map(function (item) {
        return '<div class="cart-item">'
            + '<img src="' + escapeHtml(item.img) + '" alt="' + escapeHtml(item.name) + '" onerror="this.src=\'images/product_mens_1.png\'">'
            + '<div class="cart-item-info">'
            + '<p class="ci-name">' + escapeHtml(item.name) + '</p>'
            + '<p class="ci-size">Size: ' + item.size + '</p>'
            + '<p class="ci-price">' + formatPrice(item.price) + '</p>'
            + '</div>'
            + '<div class="cart-item-ctrl">'
            + '<button onclick="changeQty(\'' + item.key + '\', -1)">−</button>'
            + '<span>' + item.qty + '</span>'
            + '<button onclick="changeQty(\'' + item.key + '\', 1)">+</button>'
            + '<button class="ci-remove" onclick="removeFromCart(\'' + item.key + '\')" title="Remove">✕</button>'
            + '</div>'
            + '</div>';
    }).join('');

    document.getElementById('cart-total-amt').textContent = formatPrice(cartTotal());
    footer.style.display = 'block';
}


// CHECKOUT
function openCheckout(isBuyNow) {
    if (!window.BRISTOL_AUTH) { signInForCart(); return; }
    if (!isBuyNow && !getCart().length) { showShopError('Your cart is empty.'); return; }
    var itemsList = document.getElementById('checkout-items-list');
    var sub = 0;

    if (isBuyNow && buyNowItem) {
        if (itemsList) {
            itemsList.innerHTML = '<div class="summary-row"><span>' + escapeHtml(buyNowItem.name) + ' (x1) sz:' + buyNowItem.size + '</span><span>' + formatPrice(buyNowItem.price) + '</span></div>';
        }
        sub = buyNowItem.price;
    } else {
        buyNowItem = null;
        var cart = getCart();
        if (itemsList) {
            itemsList.innerHTML = cart.map(function (i) {
                return '<div class="summary-row"><span>' + escapeHtml(i.name) + ' (x' + i.qty + ') sz:' + i.size + '</span><span>' + formatPrice(i.price * i.qty) + '</span></div>';
            }).join('');
        }
        sub = cartTotal();
    }

    var subtotalEl = document.getElementById('checkout-subtotal');
    var totalEl = document.getElementById('checkout-total');
    if (subtotalEl) subtotalEl.textContent = formatPrice(sub);
    if (totalEl) totalEl.textContent = formatPrice(sub + window.BRISTOL_SHIPPING);
    closeCartSidebar();
    document.getElementById('checkout-modal').classList.add('open');
    document.body.style.overflow = 'hidden';
}

async function restorePendingAction() {
    if (!window.BRISTOL_AUTH) return;
    var saved = sessionStorage.getItem('bristol_pending_action');
    if (!saved) return;
    sessionStorage.removeItem('bristol_pending_action');
    try {
        var pending = JSON.parse(saved);
        var product = PRODUCTS.find(function (p) { return p.id === Number(pending.id); });
        var size = Number(pending.size);
        if (!product || !product.sizes.includes(size) || product.unavailable.includes(size)) return;
        if (pending.action === 'add') {
            await cartRequest('add', {id: product.id, size: size});
            openCartSidebar();
        } else if (pending.action === 'buy_now') {
            buyNowItem = {id: product.id, name: product.name, type: product.type,
                price: product.price, size: size, img: product.img, qty: 1};
            openCheckout(true);
        }
    } catch (error) { showShopError('Could not restore your selection. Please try again.'); }
}
function closeCheckout() {
    document.getElementById('checkout-modal').classList.remove('open');
    document.body.style.overflow = '';
}
function selectPayment(el) {
    document.querySelectorAll('.pay-option').forEach(function (o) { o.classList.remove('selected'); });
    el.classList.add('selected');
    el.querySelector('input[type="radio"]').checked = true;
}

function initAddressSelects() {
    var province = document.getElementById('checkout-province');
    var city = document.getElementById('checkout-city');
    if (!province || !city) return;

    province.addEventListener('change', function () {
        var choices = window.BRISTOL_LOCATIONS[province.value] || [];
        city.replaceChildren(new Option(choices.length ? 'Select city / municipality' : 'Select province first', ''));
        choices.forEach(function (name) { city.add(new Option(name, name)); });
        city.disabled = choices.length === 0;
    });
}

async function placeOrder() {
    var form = document.getElementById('checkout-form');
    var errorBox = document.getElementById('checkout-error');
    var button = form.querySelector('button[type="submit"]');
    var fields = Object.fromEntries(new FormData(form));
    var items = buyNowItem ? [buyNowItem] : getCart();
    if (!fields.payment_method || !items.length) {
        errorBox.textContent = 'Choose a payment method and add at least one item.';
        errorBox.hidden = false;
        return;
    }
    errorBox.hidden = true;
    button.disabled = true;
    button.textContent = 'Saving order…';
    try {
        var response = await fetch('process_order.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-Token': window.BRISTOL_CSRF},
            body: JSON.stringify(Object.assign(fields, {
                mode: buyNowItem ? 'buy_now' : 'cart',
                items: items.map(function (item) {
                    return {id: item.id, size: item.size, qty: item.qty};
                })
            }))
        });
        var result = await response.json();
        if (!response.ok) throw new Error(result.error || 'Could not save your order.');
        if (buyNowItem) buyNowItem = null;
        else CART = [];
        updateCartBadge();
        form.reset();
        document.getElementById('checkout-province').dispatchEvent(new Event('change'));
        document.querySelectorAll('.pay-option').forEach(function (option) { option.classList.remove('selected'); });
        closeCheckout();
        var toast = document.getElementById('order-toast');
        toast.textContent = 'Order ' + result.reference + ' received successfully.';
        toast.classList.add('show');
        setTimeout(function () { toast.classList.remove('show'); }, 6000);
    } catch (error) {
        errorBox.textContent = error.message || 'Network error. Please try again.';
        errorBox.hidden = false;
    } finally {
        button.disabled = false;
        button.textContent = 'Place Order';
    }
}


// INIT
document.addEventListener('DOMContentLoaded', function () {
    renderProducts();
    initFilters();
    initAddressSelects();
    updateCartBadge();
    loadCart().then(restorePendingAction);
    document.querySelectorAll('input[name="payment_method"]').forEach(function (input) {
        input.addEventListener('change', function () { selectPayment(input.closest('.pay-option')); });
    });

    var modal = document.getElementById('product-modal');
    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeModal();
        });
    }

    var sizeGuide = document.getElementById('size-guide-modal');
    sizeGuide.addEventListener('click', function (event) {
        if (event.target === sizeGuide) closeSizeGuide();
    });
    document.getElementById('checkout-modal').addEventListener('click', function (event) {
        if (event.target === this) closeCheckout();
    });
    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        if (sizeGuide.classList.contains('open')) closeSizeGuide();
        else if (document.getElementById('checkout-modal').classList.contains('open')) closeCheckout();
        else if (document.getElementById('cart-sidebar').classList.contains('open')) closeCartSidebar();
        else if (modal.classList.contains('open')) closeModal();
    });

    var overlay = document.getElementById('cart-overlay');
    if (overlay) overlay.addEventListener('click', closeCartSidebar);
});

