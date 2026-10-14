<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        // Ensure Metro exists (if missing before)
        $metroExists = DB::table('branches')->where('name', 'Metro')->exists();

        if (!$metroExists) {
            DB::table('branches')->insert([
                'name' => 'Metro',
                'address' => '',
                '611_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Align correct 611_id mapping
        DB::table('branches')->where('name', 'Mabini')->update(['611_id' => 1]);
        DB::table('branches')->where('name', 'Gensan')->update(['611_id' => 2]);
        DB::table('branches')->where('name', 'Maa')->update(['611_id' => 3]);
        DB::table('branches')->where('name', 'Ecoland')->update(['611_id' => 4]);
        DB::table('branches')->where('name', 'Digos')->update(['611_id' => 5]);
        DB::table('branches')->where('name', 'Cagayan')->update(['611_id' => 6]);
        DB::table('branches')->where('name', 'Gmall Site')->update(['611_id' => 7]);
        DB::table('branches')->where('name', 'Metro')->update(['611_id' => 8]);

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        DB::table('branches')->update(['611_id' => null]);
    }
};