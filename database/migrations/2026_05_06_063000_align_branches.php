<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration
{
    /**
     * Run the migrations.
     */
     public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        // Drop and recreate table
        Schema::dropIfExists('branches');

        Schema::create('branches', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('address')->nullable();
            $table->timestamps();
        });

        // Fixed ID + real data seeding
        DB::table('branches')->insert([
            [
                'id' => 1,
                'name' => 'Mabini',
                'address' => 'Araullo St., Near Red Corner / City High, Davao City',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'Gensan',
                'address' => 'Joji Ilagan Building, Leon Llido Street, General Santos City, South Cotabato, 9500 PH',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'name' => 'Maa',
                'address' => 'Luxebridge Town, Maa (Beside Sans Hotel), Davao City',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'name' => 'Ecoland',
                'address' => 'ACC Building, Quimpo Blvd., between Chowking & Green Coffee, Ecoland, Davao City',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 5,
                'name' => 'Digos',
                'address' => 'Purok Cattleya, 100m from Citta de Oro, Tiguman, Digos City',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 6,
                'name' => 'Cagayan',
                'address' => 'Zone 7, near Tropical Resort (Beside Sagatech Resort), Bulua, Cagayan de Oro City',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 7,
                'name' => 'Gmall Site',
                'address' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 8,
                'name' => 'Metro',
                'address' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::statement("ALTER TABLE branches AUTO_INCREMENT = 9");

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
