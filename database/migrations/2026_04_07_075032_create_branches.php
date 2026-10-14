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
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., Mabini
            $table->string('address'); // full address
            $table->timestamps();
        });

        // Seed branches
        DB::table('branches')->insert([
            ['name' => 'Mabini', 'address' => 'Araullo St., Near Red Corner / City High, Davao City', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Maa', 'address' => 'Luxebridge Town, Maa (Beside Sans Hotel), Davao City', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Ecoland', 'address' => 'ACC Building, Quimpo Blvd., between Chowking & Green Coffee, Ecoland, Davao City', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Gensan', 'address' => 'Joji Ilagan Building, Leon Llido Street, General Santos City, South Cotabato, 9500 PH', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Digos', 'address' => 'Purok Cattleya, 100m from Citta de Oro, Tiguman, Digos City', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Metro', 'address' => '', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Cagayan', 'address' => 'Zone 7, near Tropical Resort (Beside Sagatech Resort), Bulua, Cagayan de Oro City', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Gmall Site', 'address' => '', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
