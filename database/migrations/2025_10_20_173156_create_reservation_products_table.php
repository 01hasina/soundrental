<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reservation_products', function (Blueprint $table) {
            $table->id('id_reservation_product'); // clé primaire
            $table->foreignId('id_reservation')
                ->constrained('reservations', 'id_reservation')
                ->onDelete('cascade');
            $table->foreignId('id_product')
                ->constrained('products', 'id_product') // si nécessaire
                ->onDelete('cascade');
            $table->integer('quantity');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservation_products');
    }
};
