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
        Schema::table('campaigns', function (Blueprint $table) {
            // Drop old branch string column if exists
            if (Schema::hasColumn('campaigns', 'branch')) {
                $table->dropColumn('branch');
            }

            // Add proper branch_id foreign key
            $table->unsignedBigInteger('branch_id')->nullable()->after('om_id');
            $table->foreign('branch_id')
                  ->references('id')
                  ->on('branches')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');

            // Optionally restore old branch string column
            $table->string('branch')->nullable()->after('om_id');
        });
    }
};
