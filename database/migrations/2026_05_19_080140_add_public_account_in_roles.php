<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('roles')
            ->where('name', 'Public')
            ->exists();

        if (!$exists) {
            DB::table('roles')->insert([
                'name' => 'Public',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('roles')
            ->where('name', 'Public')
            ->delete();
    }
};