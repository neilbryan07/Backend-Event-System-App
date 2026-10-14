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
        // Add new column
        Schema::table('branches', function (Blueprint $table) {
            $table->integer('611_id')->nullable()->after('address');
        });

        // Update existing branch records
        DB::table('branches')->where('name', 'Mabini')->update([
            '611_id' => 1
        ]);

        DB::table('branches')->where('name', 'Gensan')->update([
            '611_id' => 2
        ]);

        DB::table('branches')->where('name', 'Maa')->update([
            '611_id' => 3
        ]);

        DB::table('branches')->where('name', 'Ecoland')->update([
            '611_id' => 4
        ]);

        DB::table('branches')->where('name', 'Digos')->update([
            '611_id' => 5
        ]);

        DB::table('branches')->where('name', 'Cagayan')->update([
            '611_id' => 6
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn('611_id');
        });
    }
};
