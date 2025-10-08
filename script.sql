-- =========================
-- 1. Connexion à la base
-- =========================
-- Si la base n'existe pas encore :
-- CREATE DATABASE sound_rental_db;
\c sound_rental_db;

-- =========================
-- 2. Roles
-- =========================
CREATE TABLE IF NOT EXISTS roles (
    id_role BIGSERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
);

-- =========================
-- 3. Users
-- =========================
CREATE TABLE IF NOT EXISTS users (
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
-- 4. Categories
-- =========================
CREATE TABLE IF NOT EXISTS categories (
    id_category BIGSERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
);

-- =========================
-- 5. Products
-- =========================
CREATE TABLE IF NOT EXISTS products (
    id_product BIGSERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    daily_price NUMERIC(10,2) NOT NULL CHECK (daily_price > 0),
    replacement_cost NUMERIC(10,2) CHECK (replacement_cost IS NULL OR replacement_cost > 0),
    stock_quantity INT DEFAULT 0,
    image_url TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    id_category BIGINT REFERENCES categories(id_category)
);

-- =========================
-- 6. Bundles
-- =========================
CREATE TABLE IF NOT EXISTS bundles (
    id_bundle BIGSERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    daily_price NUMERIC(10,2) NOT NULL CHECK (daily_price > 0),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS bundle_products (
    id_bundle BIGINT NOT NULL REFERENCES bundles(id_bundle) ON DELETE CASCADE,
    id_product BIGINT NOT NULL REFERENCES products(id_product) ON DELETE CASCADE,
    quantity INT NOT NULL CHECK (quantity > 0) DEFAULT 1,
    PRIMARY KEY (id_bundle, id_product)
);

-- =========================
-- 7. Inventory
-- =========================
CREATE TABLE IF NOT EXISTS inventory (
    id_inventory BIGSERIAL PRIMARY KEY,
    id_product BIGINT NOT NULL REFERENCES products(id_product) ON DELETE CASCADE,
    serial_number VARCHAR(100) UNIQUE,
    condition VARCHAR(20) CHECK (condition IN ('new','excellent','good','fair','poor','retired')) DEFAULT 'good',
    purchase_date DATE,
    last_maintenance_date DATE,
    is_available BOOLEAN DEFAULT TRUE,
    notes TEXT
);

CREATE INDEX IF NOT EXISTS idx_inventory_product ON inventory(id_product);

-- =========================
-- 8. Inventory Movements
-- =========================
CREATE TABLE IF NOT EXISTS inventory_movements (
    id_movement BIGSERIAL PRIMARY KEY,
    id_inventory BIGINT NOT NULL REFERENCES inventory(id_inventory) ON DELETE CASCADE,
    movement_type VARCHAR(20) CHECK (movement_type IN ('entry','exit','return','loss','repair')) NOT NULL,
    quantity INT NOT NULL CHECK (quantity > 0),
    movement_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    comment TEXT
);

-- =========================
-- 9. Maintenance
-- =========================
CREATE TABLE IF NOT EXISTS maintenance (
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
-- 10. Reservations
-- =========================
CREATE TABLE IF NOT EXISTS reservations (
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
    cancellation_reason TEXT,
    reservation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_reservations_user ON reservations(id_user);
CREATE INDEX IF NOT EXISTS idx_reservations_event_date ON reservations(event_date);

-- =========================
-- 11. Reservation ↔ Inventory
-- =========================
CREATE TABLE IF NOT EXISTS reservation_inventory (
    id_reservation_inventory BIGSERIAL PRIMARY KEY,
    id_reservation BIGINT NOT NULL REFERENCES reservations(id_reservation) ON DELETE CASCADE,
    id_inventory BIGINT NOT NULL REFERENCES inventory(id_inventory) ON DELETE CASCADE,
    UNIQUE (id_reservation, id_inventory)
);

CREATE INDEX IF NOT EXISTS idx_resinv_reservation ON reservation_inventory(id_reservation);
CREATE INDEX IF NOT EXISTS idx_resinv_inventory ON reservation_inventory(id_inventory);

-- =========================
-- 12. Payments
-- =========================
CREATE TABLE IF NOT EXISTS payments (
    id_payment BIGSERIAL PRIMARY KEY,
    id_reservation BIGINT NOT NULL REFERENCES reservations(id_reservation),
    amount NUMERIC(10,2) NOT NULL CHECK (amount > 0),
    payment_method VARCHAR(20) CHECK (payment_method IN ('credit_card','bank_transfer','cash')) NOT NULL,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(20) CHECK (status IN ('pending','completed','failed','refunded')) DEFAULT 'pending',
    transaction_id VARCHAR(100)
);

CREATE INDEX IF NOT EXISTS idx_payments_reservation ON payments(id_reservation);

-- =========================
-- 13. Quotes & Invoices
-- =========================
CREATE TABLE IF NOT EXISTS quotes (
    id_quote BIGSERIAL PRIMARY KEY,
    id_reservation BIGINT NOT NULL REFERENCES reservations(id_reservation),
    total_ht NUMERIC(10,2),
    vat NUMERIC(10,2),
    total_ttc NUMERIC(10,2),
    issue_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_quotes_reservation ON quotes(id_reservation);

CREATE TABLE IF NOT EXISTS invoices (
    id_invoice BIGSERIAL PRIMARY KEY,
    id_reservation BIGINT NOT NULL REFERENCES reservations(id_reservation),
    total_amount NUMERIC(10,2),
    billing_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_invoices_reservation ON invoices(id_reservation);

-- =========================
-- 14. Insert roles
-- =========================
INSERT INTO roles (name) VALUES ('client') ON CONFLICT (name) DO NOTHING;
INSERT INTO roles (name) VALUES ('admin') ON CONFLICT (name) DO NOTHING;

-- =========================
-- 15. Trigger pour mise à jour du stock
-- =========================
CREATE OR REPLACE FUNCTION update_product_stock()
RETURNS TRIGGER AS $$
BEGIN
    UPDATE products
    SET stock_quantity = (
        SELECT COUNT(*)
        FROM inventory
        WHERE id_product = NEW.id_product
          AND is_available = TRUE
          AND condition NOT IN ('poor','retired')
    )
    WHERE id_product = NEW.id_product;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_inventory_insert ON inventory;
CREATE TRIGGER trg_inventory_insert
AFTER INSERT OR UPDATE OF is_available, id_product, condition ON inventory
FOR EACH ROW
EXECUTE FUNCTION update_product_stock();

DROP TRIGGER IF EXISTS trg_inventory_delete ON inventory;
CREATE TRIGGER trg_inventory_delete
AFTER DELETE ON inventory
FOR EACH ROW
EXECUTE FUNCTION update_product_stock();
