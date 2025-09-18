<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // 1. Roles
        Schema::create('roles', function (Blueprint $table) {
            $table->id('id_role');
            $table->string('name',50)->unique();
            $table->timestamps();
        });

        // 2. Users
        Schema::create('users', function (Blueprint $table) {
            $table->id('id_user');
            $table->string('first_name',100);
            $table->string('last_name',100);
            $table->string('email',150)->unique();
            $table->string('password');
            $table->string('phone',20)->nullable();
            $table->text('address')->nullable();
            $table->foreignId('id_role')->constrained('roles','id_role');
            $table->timestamp('registration_date')->useCurrent();
            $table->timestamps();
        });

        // 3. Categories
        Schema::create('categories', function(Blueprint $table){
            $table->id('id_category');
            $table->string('name',100);
            $table->timestamps();
        });

        // 4. Products
        Schema::create('products', function(Blueprint $table){
            $table->id('id_product');
            $table->string('name',100);
            $table->text('description')->nullable();
            $table->decimal('daily_price',10,2);
            $table->decimal('replacement_cost',10,2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('id_category')->nullable()->constrained('categories','id_category');
            $table->timestamps();
        });

        // 5. Bundles
        Schema::create('bundles', function(Blueprint $table){
            $table->id('id_bundle');
            $table->string('name',100);
            $table->text('description')->nullable();
            $table->decimal('daily_price',10,2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 6. Bundle_Products
        Schema::create('bundle_products', function(Blueprint $table){
            $table->foreignId('id_bundle')->constrained('bundles','id_bundle')->onDelete('cascade');
            $table->foreignId('id_product')->constrained('products','id_product')->onDelete('cascade');
            $table->integer('quantity')->default(1);
            $table->primary(['id_bundle','id_product']);
        });

        // 7. Inventory
        Schema::create('inventory', function(Blueprint $table){
            $table->id('id_inventory');
            $table->foreignId('id_product')->constrained('products','id_product')->onDelete('cascade');
            $table->string('serial_number',100)->unique()->nullable();
            $table->enum('condition',['new','excellent','good','fair','poor','retired'])->default('good');
            $table->date('purchase_date')->nullable();
            $table->date('last_maintenance_date')->nullable();
            $table->boolean('is_available')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 8. Maintenance
        Schema::create('maintenance', function(Blueprint $table){
            $table->id('id_maintenance');
            $table->foreignId('id_inventory')->constrained('inventory','id_inventory')->onDelete('restrict');
            $table->date('start_date');
            $table->date('end_date');
            $table->text('description');
            $table->decimal('cost',10,2)->nullable();
            $table->enum('status',['scheduled','in_progress','completed'])->default('scheduled');
        });

        // 9. Reservations
        Schema::create('reservations', function(Blueprint $table){
            $table->id('id_reservation');
            $table->foreignId('id_user')->constrained('users','id_user');
            $table->date('event_date');
            $table->time('event_time');
            $table->integer('duration_hours');
            $table->string('location')->nullable();
            $table->enum('status',['pending','validated','confirmed','cancelled'])->default('pending');
            $table->decimal('estimated_price',10,2)->nullable();
            $table->decimal('final_price',10,2)->nullable();
            $table->enum('order_state',['not_issued','quote_sent','order_validated','order_cancelled'])->default('not_issued');
            $table->timestamp('reservation_date')->useCurrent();
            $table->timestamps();
        });

        // 10. Reservation_Products
        Schema::create('reservation_products', function(Blueprint $table){
            $table->id('id_reservation_product');
            $table->foreignId('id_reservation')->constrained('reservations','id_reservation')->onDelete('cascade');
            $table->foreignId('id_product')->constrained('products','id_product')->onDelete('cascade');
            $table->integer('quantity');
            $table->timestamps();
        });

        // 11. Reservation_Bundles
        Schema::create('reservation_bundles', function(Blueprint $table){
            $table->id('id_reservation_bundle');
            $table->foreignId('id_reservation')->constrained('reservations','id_reservation')->onDelete('cascade');
            $table->foreignId('id_bundle')->constrained('bundles','id_bundle')->onDelete('cascade');
            $table->integer('quantity');
            $table->timestamps();
        });

        // 12. Payments
        Schema::create('payments', function(Blueprint $table){
            $table->id('id_payment');
            $table->foreignId('id_reservation')->constrained('reservations','id_reservation');
            $table->decimal('amount',10,2);
            $table->enum('payment_method',['credit_card','bank_transfer','cash']);
            $table->timestamp('payment_date')->useCurrent();
            $table->enum('status',['pending','completed','failed','refunded'])->default('pending');
            $table->string('transaction_id',100)->nullable();
            $table->timestamps();
        });

        // 13. Quotes
        Schema::create('quotes', function(Blueprint $table){
            $table->id('id_quote');
            $table->foreignId('id_reservation')->constrained('reservations','id_reservation');
            $table->decimal('total_ht',10,2)->nullable();
            $table->decimal('vat',10,2)->nullable();
            $table->decimal('total_ttc',10,2)->nullable();
            $table->timestamp('issue_date')->useCurrent();
            $table->timestamps();
        });

        // 14. Invoices
        Schema::create('invoices', function(Blueprint $table){
            $table->id('id_invoice');
            $table->foreignId('id_reservation')->constrained('reservations','id_reservation');
            $table->decimal('total_amount',10,2)->nullable();
            $table->timestamp('billing_date')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('quotes');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('reservation_bundles');
        Schema::dropIfExists('reservation_products');
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

