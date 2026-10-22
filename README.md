# Bristol Shoes Marikina

A student e-commerce website built with PHP, MySQL/MariaDB, CSS, and JavaScript. The original design is retained; the product catalog, order submissions, and contact messages now use a PHP backend.

Originally created by Jhonrey Belleca, Wesleigh Kane Regis, Dexter Pablo, and Justine Mark Cayabyab for BSIT at OLFU Antipolo.

## Local setup

1. Install PHP 8.2+ with `pdo_mysql` and `mbstring`, plus MySQL or MariaDB. XAMPP is also fine because it includes PHP, MariaDB, and phpMyAdmin.
2. Start MySQL/MariaDB. For a **new** database, import `database/schema.sql`, then `database/seed.sql` in phpMyAdmin. If you already imported the earlier version of the project, run `database/upgrade_accounts.sql` once instead. The seed adds 12 products.
3. Copy `config/database.example.php` to `config/database.local.php`. Edit the local copy to match your database host, port, username, and password. The local file is ignored by Git.
4. From the project folder, run `php -S localhost:8000` and open `http://localhost:8000/index.php`. If using XAMPP, you may instead put the folder in `htdocs` and open it through Apache.

Do not open a `.php` file directly from File Explorer; it needs a PHP server. No Composer or Node packages are required to run this website.

New to this code? Read [`docs/PHP_WALKTHROUGH.md`](docs/PHP_WALKTHROUGH.md) for a beginner-friendly map of the PHP files and a checkout example.

## What works

- The four original pages are PHP pages; navigation stays within the PHP site.
- Visitors can browse freely. Adding to cart or placing an order requires an account. Passwords are hashed, login uses a PHP session, and the cart is stored in MySQL per account.
- Customers can edit their name/email, change their password, and see recent orders from their account.
- Products load from the database. The server verifies product, size, quantity, and **current database price** before saving an order.
- The shop has collection filters, search, sorting, quick view, and a size guide.
- A successful checkout saves an order plus its line items and returns a reference number. An error leaves the cart intact.
- The province and city/municipality fields are linked dropdowns. Their local data snapshot is in `data/ph-locations.json`, sourced from the [PSGC Cloud API](https://psgc.cloud/api-docs/v2); checkout also checks the selected pair on the server.
- Contact messages are saved to `contact_messages` in the database rather than sent through Formspree.
- Checkout and contact forms have both browser-side feedback and server-side validation. POST requests use a session CSRF token and prepared SQL statements.

## Admin dashboard

After creating an account normally, promote **your own account only** in phpMyAdmin:

```sql
UPDATE users SET role = 'admin' WHERE email = 'your-email@example.com';
```

Sign out and back in, then open `admin.php`. The dashboard lets an admin view orders, update order status, add/edit products, read contact messages, and assign the `staff` role to a registered teammate. Public registration always creates a customer account; there is no default admin password.

## Staff workspace

1. The teammate registers through `register.php`.
2. An admin opens **Admin dashboard → Staff access**, selects **Staff** for that teammate, and saves.
3. The teammate signs out and back in, then opens **Account → Staff workspace**.

`staff.php` shows order-status counts, a date-filtered report of order counts and order value, and order records whose progress staff can update. Admins can open the staff workspace too. Staff cannot edit products, assign roles, or mark a payment as paid.

## Payment scope

Checkout records the chosen method, but **payment processing has not been integrated**. No card, wallet, or bank details are collected. Every new order has `payment_status = unpaid` and `status = pending`.

## Main files

| File | Purpose |
| --- | --- |
| `products.php` | Loads the catalog and renders the shop page |
| `js/shop.js` | Product browsing, account cart, checkout UI |
| `register.php`, `login.php`, `account.php`, `profile.php` | Registration, sign-in, profile settings, order history |
| `admin.php` | Small admin dashboard |
| `staff.php` | Order records, monitoring, date-filtered reports |
| `process_cart.php` | Saves and retrieves the account cart |
| `process_order.php` | Validates and saves orders |
| `process_contact.php` | Validates and saves messages |
| `includes/common.php` | Database connection, sessions, and small shared helpers |
| `database/schema.sql` | Database structure |
| `database/seed.sql` | Demo catalog data |

## Team workflow

Work on a feature branch, test locally, and open a pull request. Do not commit `database.local.php` or real credentials. This project does not use the Bootstrap CSS framework. The payment icons are based on [Lucide](https://lucide.dev/icons/) (ISC license).

Inventory quantities and real payment processing are not implemented. Product sizes can be marked unavailable in the admin dashboard.
