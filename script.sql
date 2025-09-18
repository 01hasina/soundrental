-- =========================
-- 1. Create database
-- =========================
CREATE DATABASE sound_rental_db;
\c sound_rental_db;

-- =========================
-- 2. Roles
-- =========================
CREATE TABLE roles (
    id_role BIGSERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
);

-- =========================
-- 3. Users (clients + admins)
-- =========================
CREATE TABLE users (
    id_user BIGSERIAL PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    id_role BIGINT NOT NULL REFERENCES roles(id_role),
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =========================
-- 4. Products (equipment)
-- =========================
CREATE TABLE products (
    id_product BIGSERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    daily_price NUMERIC(10,2) NOT NULL CHECK (daily_price > 0),
    replacement_cost NUMERIC(10,2) CHECK (replacement_cost IS NULL OR replacement_cost > 0),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    id_category BIGINT,
    CONSTRAINT fk_product_category FOREIGN KEY (id_category) REFERENCES categories(id_category)
);

-- =========================
-- 5. Categories (optional)
-- =========================
CREATE TABLE categories (
    id_category BIGSERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

-- =========================
-- 6. Bundles
-- =========================
CREATE TABLE bundles (
    id_bundle BIGSERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    daily_price NUMERIC(10,2) NOT NULL CHECK (daily_price > 0),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bundle ↔ Products
CREATE TABLE bundle_products (
    id_bundle BIGINT NOT NULL REFERENCES bundles(id_bundle) ON DELETE CASCADE,
    id_product BIGINT NOT NULL REFERENCES products(id_product) ON DELETE CASCADE,
    quantity INT NOT NULL CHECK (quantity > 0) DEFAULT 1,
    PRIMARY KEY (id_bundle, id_product)
);

-- =========================
-- 7. Inventory
-- =========================
CREATE TABLE inventory (
    id_inventory BIGSERIAL PRIMARY KEY,
    id_product BIGINT NOT NULL REFERENCES products(id_product) ON DELETE CASCADE,
    serial_number VARCHAR(100) UNIQUE,
    condition VARCHAR(20) CHECK (condition IN ('new','excellent','good','fair','poor','retired')) DEFAULT 'good',
    purchase_date DATE,
    last_maintenance_date DATE,
    is_available BOOLEAN DEFAULT TRUE,
    notes TEXT
);

-- =========================
-- 8. Maintenance
-- =========================
CREATE TABLE maintenance (
    id_maintenance BIGSERIAL PRIMARY KEY,
    id_inventory BIGINT NOT NULL REFERENCES inventory(id_inventory) ON DELETE RESTRICT,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    description TEXT NOT NULL,
    cost NUMERIC(10,2) CHECK (cost IS NULL OR cost >= 0),
    status VARCHAR(20) CHECK (status IN ('scheduled','in_progress','completed')) DEFAULT 'scheduled',
    CHECK (start_date <= end_date)
);

-- =========================
-- 9. Reservations
-- =========================
CREATE TABLE reservations (
    id_reservation BIGSERIAL PRIMARY KEY,
    id_user BIGINT NOT NULL REFERENCES users(id_user),
    event_date DATE NOT NULL,
    event_time TIME NOT NULL,
    duration_hours INT NOT NULL,
    location VARCHAR(255),
    status VARCHAR(20) CHECK (status IN ('pending','validated','confirmed','cancelled')) DEFAULT 'pending',
    estimated_price NUMERIC(10,2),
    final_price NUMERIC(10,2),
    order_state VARCHAR(20) CHECK (order_state IN ('not_issued','quote_sent','order_validated','order_cancelled')) DEFAULT 'not_issued',
    reservation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Reservation ↔ Products
CREATE TABLE reservation_products (
    id_reservation_product BIGSERIAL PRIMARY KEY,
    id_reservation BIGINT NOT NULL REFERENCES reservations(id_reservation) ON DELETE CASCADE,
    id_product BIGINT NOT NULL REFERENCES products(id_product) ON DELETE CASCADE,
    quantity INT NOT NULL
);

-- Reservation ↔ Bundles
CREATE TABLE reservation_bundles (
    id_reservation_bundle BIGSERIAL PRIMARY KEY,
    id_reservation BIGINT NOT NULL REFERENCES reservations(id_reservation) ON DELETE CASCADE,
    id_bundle BIGINT NOT NULL REFERENCES bundles(id_bundle) ON DELETE CASCADE,
    quantity INT NOT NULL
);

-- =========================
-- 10. Payments
-- =========================
CREATE TABLE payments (
    id_payment BIGSERIAL PRIMARY KEY,
    id_reservation BIGINT NOT NULL REFERENCES reservations(id_reservation),
    amount NUMERIC(10,2) NOT NULL CHECK (amount > 0),
    payment_method VARCHAR(20) CHECK (payment_method IN ('credit_card','bank_transfer','cash')) NOT NULL,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(20) CHECK (status IN ('pending','completed','failed','refunded')) DEFAULT 'pending',
    transaction_id VARCHAR(100)
);

-- =========================
-- 11. Quotes and Invoices
-- =========================
CREATE TABLE quotes (
    id_quote BIGSERIAL PRIMARY KEY,
    id_reservation BIGINT NOT NULL REFERENCES reservations(id_reservation),
    total_ht NUMERIC(10,2),
    vat NUMERIC(10,2),
    total_ttc NUMERIC(10,2),
    issue_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE invoices (
    id_invoice BIGSERIAL PRIMARY KEY,
    id_reservation BIGINT NOT NULL REFERENCES reservations(id_reservation),
    total_amount NUMERIC(10,2),
    billing_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =========================
-- 12. Insert roles
-- =========================
INSERT INTO roles (name) VALUES ('client'), ('admin');
