<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // =========================
        // 1. Roles
        // =========================
        Schema::create('roles', function (Blueprint $table) {
            $table->bigIncrements('id_role');
            $table->string('name', 50)->unique();
        });

        // =========================
        // 2. Users
        // =========================
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id_user');
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email', 150)->unique();
            $table->string('password');
            $table->string('phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->unsignedBigInteger('id_role');
            $table->timestamp('registration_date')->useCurrent();

            $table->foreign('id_role')->references('id_role')->on('roles');
        });

        // =========================
        // 3. Categories
        // =========================
        Schema::create('categories', function (Blueprint $table) {
            $table->bigIncrements('id_category');
            $table->string('name', 100)->unique();
        });

        // =========================
        // 4. Products
        // =========================
        Schema::create('products', function (Blueprint $table) {
            $table->bigIncrements('id_product');
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->decimal('daily_price', 10, 2);
            $table->decimal('replacement_cost', 10, 2)->nullable();
            $table->integer('stock_quantity')->default(0);
            $table->text('image_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->unsignedBigInteger('id_category')->nullable();

            $table->foreign('id_category')->references('id_category')->on('categories');
        });

        // =========================
        // 5. Bundles
        // =========================
        Schema::create('bundles', function (Blueprint $table) {
            $table->bigIncrements('id_bundle');
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->decimal('daily_price', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('bundle_products', function (Blueprint $table) {
            $table->unsignedBigInteger('id_bundle');
            $table->unsignedBigInteger('id_product');
            $table->unsignedInteger('quantity')->default(1);

            $table->primary(['id_bundle', 'id_product']);
            $table->foreign('id_bundle')->references('id_bundle')->on('bundles')->onDelete('cascade');
            $table->foreign('id_product')->references('id_product')->on('products')->onDelete('cascade');
        });

        // =========================
        // 6. Inventory
        // =========================
        Schema::create('inventory', function (Blueprint $table) {
            $table->bigIncrements('id_inventory');
            $table->unsignedBigInteger('id_product');
            $table->string('serial_number', 100)->unique()->nullable();
            $table->enum('condition', ['new','excellent','good','fair','poor','retired'])->default('good');
            $table->date('purchase_date')->nullable();
            $table->date('last_maintenance_date')->nullable();
            $table->boolean('is_available')->default(true);
            $table->text('notes')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->foreign('id_product')->references('id_product')->on('products')->onDelete('cascade');
            $table->index('id_product');
        });

        // =========================
        // 7. Maintenance
        // =========================
        Schema::create('maintenance', function (Blueprint $table) {
            $table->bigIncrements('id_maintenance');
            $table->unsignedBigInteger('id_inventory');
            $table->date('start_date');
            $table->date('end_date');
            $table->text('description');
            $table->decimal('cost', 10, 2)->nullable();
            $table->enum('status', ['scheduled','in_progress','completed'])->default('scheduled');

            $table->foreign('id_inventory')->references('id_inventory')->on('inventory')->onDelete('restrict');
        });

        // =========================
        // 8. Reservations
        // =========================
        Schema::create('reservations', function (Blueprint $table) {
            $table->bigIncrements('id_reservation');
            $table->unsignedBigInteger('id_user');
            $table->date('event_date');
            $table->time('event_time');
            $table->integer('duration_hours');
            $table->string('location', 255)->nullable();
            $table->enum('status', ['pending','validated','confirmed','cancelled'])->default('pending');
            $table->decimal('estimated_price', 10, 2)->nullable();
            $table->decimal('final_price', 10, 2)->nullable();
            $table->enum('order_state', ['not_issued','quote_sent','order_validated','order_cancelled'])->default('not_issued');
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('reservation_date')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->foreign('id_user')->references('id_user')->on('users');
            $table->index('id_user');
            $table->index('event_date');
        });

        // =========================
        // 9. Reservation ↔ Inventory
        // =========================
        Schema::create('reservation_inventory', function (Blueprint $table) {
            $table->bigIncrements('id_reservation_inventory');
            $table->unsignedBigInteger('id_reservation');
            $table->unsignedBigInteger('id_inventory');
            

            $table->unique(['id_reservation','id_inventory']);
            $table->foreign('id_reservation')->references('id_reservation')->on('reservations')->onDelete('cascade');
            $table->foreign('id_inventory')->references('id_inventory')->on('inventory')->onDelete('cascade');
        });

        // =========================
        // 10. Reservation ↔ Bundles
        // =========================
        Schema::create('reservation_bundles', function (Blueprint $table) {
            $table->bigIncrements('id_reservation_bundle');
            $table->unsignedBigInteger('id_reservation');
            $table->unsignedBigInteger('id_bundle');
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamp('reservation_date')->nullable();
            $table->timestamp('update_at')->nullable();

            $table->foreign('id_reservation')->references('id_reservation')->on('reservations')->onDelete('cascade');
            $table->foreign('id_bundle')->references('id_bundle')->on('bundles')->onDelete('cascade');
            $table->unique(['id_reservation', 'id_bundle']);
        });

        // =========================
        // 11. Payments
        // =========================
        Schema::create('payments', function (Blueprint $table) {
            $table->bigIncrements('id_payment');
            $table->unsignedBigInteger('id_reservation');
            $table->decimal('amount', 10, 2);
            $table->enum('payment_method', ['credit_card','bank_transfer','cash']);
            $table->timestamp('payment_date')->useCurrent();
            $table->enum('status', ['pending','completed','failed','refunded'])->default('pending');
            $table->string('transaction_id', 100)->nullable();

            $table->foreign('id_reservation')->references('id_reservation')->on('reservations');
        });

        // =========================
        // 12. Quotes & Invoices
        // =========================
        Schema::create('quotes', function (Blueprint $table) {
            $table->bigIncrements('id_quote');
            $table->unsignedBigInteger('id_reservation');
            $table->decimal('total_ht', 10, 2)->nullable();
            $table->decimal('vat', 10, 2)->nullable();
            $table->decimal('total_ttc', 10, 2)->nullable();
            $table->timestamp('issue_date')->useCurrent();

            $table->foreign('id_reservation')->references('id_reservation')->on('reservations');
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->bigIncrements('id_invoice');
            $table->unsignedBigInteger('id_reservation');
            $table->decimal('total_amount', 10, 2)->nullable();
            $table->timestamp('billing_date')->useCurrent();

            $table->foreign('id_reservation')->references('id_reservation')->on('reservations');
        });

        // =========================
        // 13. Function PostgreSQL pour réserver un bundle
        // =========================
        DB::unprepared("
            CREATE OR REPLACE FUNCTION reserve_bundle_inventory(p_reservation BIGINT, p_bundle BIGINT, p_qty INT)
            RETURNS VOID AS $$
            DECLARE
                bp RECORD;
                inv RECORD;
                total_needed INT;
            BEGIN
                FOR bp IN SELECT * FROM bundle_products WHERE id_bundle = p_bundle LOOP
                    total_needed := bp.quantity * p_qty;

                    FOR inv IN
                        SELECT * FROM inventory
                        WHERE id_product = bp.id_product
                          AND is_available = TRUE
                        LIMIT total_needed
                    LOOP
                        -- Marquer l'inventaire comme réservé
                        UPDATE inventory
                        SET is_available = FALSE
                        WHERE id_inventory = inv.id_inventory;

                        -- Insérer dans reservation_inventory
                        INSERT INTO reservation_inventory(id_reservation, id_inventory)
                        VALUES (p_reservation, inv.id_inventory);
                    END LOOP;

                    IF (SELECT COUNT(*) FROM inventory
                        WHERE id_product = bp.id_product
                        AND is_available = TRUE) < 0 THEN
                        RAISE EXCEPTION 'Produit % indisponible pour le bundle %', bp.id_product, p_bundle;
                    END IF;
                END LOOP;
            END;
            $$ LANGUAGE plpgsql;
        ");

        // =========================
        // 14. Trigger pour mise à jour du stock
        // =========================
        DB::unprepared("
            CREATE OR REPLACE FUNCTION update_product_stock()
            RETURNS TRIGGER AS $$
            BEGIN
                UPDATE products
                SET stock_quantity = (
                    SELECT COUNT(*) FROM inventory
                    WHERE id_product = NEW.id_product
                    AND is_available = TRUE
                    AND condition NOT IN ('poor','retired')
                )
                WHERE id_product = NEW.id_product;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        DB::unprepared("DROP TRIGGER IF EXISTS trg_inventory_insert ON inventory;");
        DB::unprepared("
            CREATE TRIGGER trg_inventory_insert
            AFTER INSERT OR UPDATE OF is_available, id_product, condition ON inventory
            FOR EACH ROW
            EXECUTE FUNCTION update_product_stock();
        ");

        DB::unprepared("DROP TRIGGER IF EXISTS trg_inventory_delete ON inventory;");
        DB::unprepared("
            CREATE TRIGGER trg_inventory_delete
            AFTER DELETE ON inventory
            FOR EACH ROW
            EXECUTE FUNCTION update_product_stock();
        ");
    }

    public function down(): void
    {
        DB::unprepared("DROP FUNCTION IF EXISTS reserve_bundle_inventory(BIGINT, BIGINT, INT);");
        DB::unprepared("DROP FUNCTION IF EXISTS update_product_stock();");

        Schema::dropIfExists('invoices');
        Schema::dropIfExists('quotes');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('reservation_bundles');
        Schema::dropIfExists('reservation_inventory');
        Schema::dropIfExists('reservations');
        Schema::dropIfExists('maintenance');
        Schema::dropIfExists('inventory');
        Schema::dropIfExists('bundle_products');
        Schema::dropIfExists('bundles');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');
    }
};
