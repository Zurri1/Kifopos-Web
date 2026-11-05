-- schema.sql — kifopos database (follows the ERD: roles, users, categories, products, carts, orders, order_details)
-- NOTE: if you still have the old template tables (users / product), drop the old database first:
--   DROP DATABASE kifopos;

CREATE DATABASE IF NOT EXISTS kifopos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE kifopos;

CREATE TABLE roles (
    id   TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(20) NOT NULL UNIQUE
);

CREATE TABLE users (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(50)  NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    role_id    TINYINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);

CREATE TABLE categories (
    id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE products (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    name        VARCHAR(100) NOT NULL,
    description TEXT NULL,
    price       DECIMAL(12,2) NOT NULL,
    url_image   VARCHAR(500) NOT NULL,            -- image link (http/https), not an uploaded file
    is_active   TINYINT(1) NOT NULL DEFAULT 1,   -- "delete" = soft delete so old orders stay valid
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

CREATE TABLE carts (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity   INT UNSIGNED NOT NULL DEFAULT 1,
    UNIQUE KEY uq_cart (user_id, product_id),
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE orders (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id        INT UNSIGNED NULL,              -- NULL = guest order
    customer_name  VARCHAR(100) NOT NULL,
    cashier_id     INT UNSIGNED NULL,              -- cashier assigned to validate this order
    status         ENUM('pending','validated','rejected') NOT NULL DEFAULT 'pending',
    subtotal       DECIMAL(12,2) NOT NULL,
    discount       DECIMAL(12,2) NOT NULL DEFAULT 0,
    total          DECIMAL(12,2) NOT NULL,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    validated_at   DATETIME NULL,
    FOREIGN KEY (user_id)    REFERENCES users(id),
    FOREIGN KEY (cashier_id) REFERENCES users(id)
);

CREATE TABLE order_details (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id   INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity   INT UNSIGNED NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL,             -- price paid per unit (after discount)
    FOREIGN KEY (order_id)   REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Seed data
INSERT INTO roles (name) VALUES ('admin'), ('cashier'), ('customer');

-- Default accounts: admin / admin123 (owner) and kasir1 / kasir123 (cashier). CHANGE THESE after first login.
INSERT INTO users (username, password, role_id) VALUES
('admin',  '$2y$10$CUIYQpyCEg8HG11.ruYIneS04tXOWbw9w3RmOhfz419prBPO5wZYy', 1),
('kasir1', '$2y$10$PFkvU5/xjfeVRWf65EkkauXL4mnvOvejYvWC49XHzF4M5NR230kk2', 2);

INSERT INTO categories (name) VALUES ('Coffee'), ('Non-Coffee'), ('Food');
