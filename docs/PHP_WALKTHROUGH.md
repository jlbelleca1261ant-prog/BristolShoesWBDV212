# PHP code walkthrough

Start here if you need to explain the project in class. Follow **one action at a time** rather than reading every file from top to bottom.

## Main files to know

| File | What it does | Database table |
| --- | --- | --- |
| `register.php` | Creates customer accounts | `users` |
| `login.php` | Checks passwords and signs in customers | `users` |
| `profile.php` | Changes the signed-in customer's name, email, or password | `users` |
| `process_cart.php` | Adds, changes, removes, and lists cart items | `cart_items`, `products` |
| `process_order.php` | Checks checkout details and saves an order | `orders`, `order_items` |
| `process_contact.php` | Saves the contact form | `contact_messages` |
| `admin.php` | Lets an admin view orders and edit products | `orders`, `products`, `contact_messages` |
| `staff.php` | Lets staff manage order progress and view date-filtered reports | `orders`, `order_items` |

`includes/common.php` contains short functions used by several of these files, such as `db()` (connect to MySQL), `current_user()` (find the signed-in account), and `json_response()` (reply to JavaScript).

The `process_*.php` files are the same idea as a class exercise named `process.php`: they receive data, check it, and save it. There are three because cart, order, and contact forms do different jobs. JavaScript calls them so the page does not have to reload.

## Example: registration

1. A visitor fills out the form in `register.php`.
2. PHP checks the email, name, and password.
3. `password_hash()` changes the password into a one-way hash.
4. `INSERT INTO users` saves the account.
5. `$_SESSION['user_id']` remembers which account is signed in.

The **website account** in `users` is different from the **MySQL username** in `config/database.local.php`. Do not change `password_hash` manually in phpMyAdmin.

Unlike a beginner form example that puts a password in `$_GET` and prints it, this project sends registration with `POST` and never displays the password. That is necessary even for a student project with real accounts.

## Example: checkout

1. `js/shop.js` sends the checkout form to `process_order.php`.
2. PHP checks the address, phone, payment choice, and selected sizes.
3. For cart checkout, PHP reads `cart_items` from MySQL. It reads product prices from `products` instead of trusting prices from the browser.
4. `INSERT INTO orders` saves the main order; `INSERT INTO order_items` saves each shoe in that order.
5. The cart is cleared only after the order is saved. A transaction keeps these database changes together.

The payment choice is recorded, but the site does not charge a card or wallet.

## Example: profile settings

1. `profile.php` checks the session to find the signed-in account.
2. A customer can submit new name/email details. The `UPDATE users ... WHERE id = ?` query changes only that account.
3. To change a password, PHP checks the current password with `password_verify()` and saves a new `password_hash()` value.

## Example: staff records and report

1. A teammate registers as a normal customer. An admin changes that account's role to `staff` from the **Staff access** tab.
2. `staff.php` checks the role before showing any data.
3. Staff can change an order's progress status. They cannot edit the product catalog or payment status.
4. The date fields filter orders. MySQL counts orders and adds their values for the report. The same filter is used for the order table.

## Class checklist map

| Requirement | Page |
| --- | --- |
| General: landing, login, registration | `index.php`, `login.php`, `register.php` |
| Admin: dashboard, records, transactions | `admin.php` |
| Client: home, profile/settings, transactions | `index.php`, `profile.php`, `account.php`, `products.php` |
| Staff: records, monitoring, reports | `staff.php` |

## PHP / SQL words you will see

- `$_POST`: values submitted by a form.
- `$_SESSION`: small server-side data that remembers a signed-in visitor.
- `prepare()` and `execute()`: send a SQL query with values kept separate from the SQL text.
- `fetch()`: get one row returned by a query; `fetchAll()` gets all rows.
- `json_encode()` / `json_decode()`: change between PHP arrays and JSON text.
- `password_hash()` / `password_verify()`: safely store and check a password.
- `csrf_token()`: checks that a form/request came from this website's session.

## A short explanation for a presentation

“The frontend is PHP pages, CSS, and JavaScript. PHP connects to our MySQL database. Registration saves an account in `users`. The cart is saved per account in `cart_items`. At checkout, PHP validates the details and gets the current product price from the database before saving the order. The admin page checks the account role before showing management features.”

For the staff section: “The admin changes a registered teammate's role to `staff`. The staff page checks that role, shows order counts and totals from MySQL, and lets staff update order progress. It does not process payments.”

You should test and understand each sentence above yourself. This is a study map, not a script to memorize without knowing the code.
