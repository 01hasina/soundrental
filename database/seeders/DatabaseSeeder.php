<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Désactiver les contraintes de clés étrangères pour faire le truncate
        Schema::disableForeignKeyConstraints();

        // truncate de tous les tables pour eviter les doublons
        DB::table('maintenance')->truncate();
        DB::table('reservation_bundles')->truncate();
        DB::table('reservation_products')->truncate();
        DB::table('payments')->truncate();
        DB::table('quotes')->truncate();
        DB::table('invoices')->truncate();
        DB::table('reservations')->truncate();
        DB::table('inventory')->truncate();
        DB::table('bundle_products')->truncate();
        DB::table('bundles')->truncate();
        DB::table('products')->truncate();
        DB::table('categories')->truncate();
        DB::table('users')->truncate();
        DB::table('roles')->truncate();

        // Réactiver les contraintes avant l'insertion des données
        Schema::enableForeignKeyConstraints();

        // Roles
        DB::table('roles')->insert([
            ['name' => 'client'],
            ['name' => 'admin'],
        ]);

        // Users
        DB::table('users')->insert([
            [
                'first_name' => 'Admin',
                'last_name' => 'Sound',
                'email' => 'admin@soundrental.com',
                'password' => Hash::make('admin123'), // mot de passe hashé
                'phone' => '0321234567',
                'address' => 'Antananarivo, Madagascar',
                'id_role' => 2, // admin
                'registration_date' => now(),
            ],
            [
                'first_name' => 'Jean',
                'last_name' => 'Client',
                'email' => 'client@soundrental.com',
                'password' => Hash::make('client123'),
                'phone' => '0349876543',
                'address' => 'Toamasina, Madagascar',
                'id_role' => 1, // client
                'registration_date' => now(),
            ],
        ]);

        // Categories
        DB::table('categories')->insert([
            ['name' => 'Sonorisation'],
            ['name' => 'Éclairage'],
            ['name' => 'Accessoires'],
        ]);

        // Products
        DB::table('products')->insert([
            [
                'name' => 'Microphone Shure SM58',
                'description' => 'Microphone dynamique de haute qualité',
                'daily_price' => 20000,
                'replacement_cost' => 300000,
                'is_active' => true,
                'id_category' => 1,
                'created_at' => now(),
            ],
            [
                'name' => 'Enceinte JBL 15"',
                'description' => 'Enceinte de sonorisation puissante',
                'daily_price' => 50000,
                'replacement_cost' => 800000,
                'is_active' => true,
                'id_category' => 1,
                'created_at' => now(),
            ],
        ]);

        // Bundles
        DB::table('bundles')->insert([
            [
                'name' => 'Pack Concert',
                'description' => '2 micros + 2 enceintes',
                'daily_price' => 120000,
                'is_active' => true,
                'created_at' => now(),
            ]
        ]);

        // Bundle ↔ Products
        DB::table('bundle_products')->insert([
            ['id_bundle' => 1, 'id_product' => 1, 'quantity' => 2],
            ['id_bundle' => 1, 'id_product' => 2, 'quantity' => 2],
        ]);

        // Inventory
        DB::table('inventory')->insert([
            [
                'id_product' => 1,
                'serial_number' => 'MIC-001',
                'condition' => 'excellent',
                'purchase_date' => '2023-05-10',
                'is_available' => true,
            ],
            [
                'id_product' => 2,
                'serial_number' => 'SPK-001',
                'condition' => 'good',
                'purchase_date' => '2023-07-15',
                'is_available' => true,
            ],
        ]);

        // Reservations
        DB::table('reservations')->insert([
            [
                'id_user' => 2, // client
                'event_date' => '2025-10-10',
                'event_time' => '18:00',
                'duration_hours' => 5,
                'location' => 'Salle des fêtes - Tana',
                'status' => 'pending',
                'estimated_price' => 150000,
                'reservation_date' => now(),
            ],
        ]);

        // Reservation ↔ Products
        DB::table('reservation_products')->insert([
            ['id_reservation' => 1, 'id_product' => 1, 'quantity' => 1],
        ]);

        // Reservation ↔ Bundles
        DB::table('reservation_bundles')->insert([
            ['id_reservation' => 1, 'id_bundle' => 1, 'quantity' => 1],
        ]);

        // Payments
        DB::table('payments')->insert([
            [
                'id_reservation' => 1,
                'amount' => 150000,
                'payment_method' => 'cash',
                'payment_date' => now(),
                'status' => 'completed',
                'transaction_id' => 'TXN-001',
            ],
        ]);

        // Quotes
        DB::table('quotes')->insert([
            [
                'id_reservation' => 1,
                'total_ht' => 120000,
                'vat' => 30000,
                'total_ttc' => 150000,
                'issue_date' => now(),
            ],
        ]);

        // Invoices
        DB::table('invoices')->insert([
            [
                'id_reservation' => 1,
                'total_amount' => 150000,
                'billing_date' => now(),
            ],
        ]);

        // Maintenance
        DB::table('maintenance')->insert([
            [
                'id_inventory' => 1, // ex: Microphone
                'start_date' => '2025-09-01',
                'end_date' => '2025-09-05',
                'description' => 'Révision du microphone et remplacement du câble',
                'cost' => 15000,
                'status' => 'completed',
            ],
            [
                'id_inventory' => 2, // ex: Enceinte JBL
                'start_date' => '2025-09-10',
                'end_date' => '2025-09-15',
                'description' => 'Réparation du haut-parleur',
                'cost' => 50000,
                'status' => 'in_progress',
            ],
        ]);
        
    }
}
