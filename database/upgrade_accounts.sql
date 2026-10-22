USE bristol_shoes;

-- Run once on a database created with the earlier schema.sql.
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'customer',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE cart_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    size SMALLINT UNSIGNED NOT NULL,
    quantity TINYINT UNSIGNED NOT NULL DEFAULT 1,
    UNIQUE KEY uniq_cart_item (user_id, product_id, size),
    CONSTRAINT fk_cart_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_cart_product FOREIGN KEY (product_id) REFERENCES products(id)
);

ALTER TABLE orders
    ADD COLUMN user_id BIGINT UNSIGNED NULL AFTER id,
    ADD CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id);

ALTER TABLE products MODIFY id INT UNSIGNED AUTO_INCREMENT;
