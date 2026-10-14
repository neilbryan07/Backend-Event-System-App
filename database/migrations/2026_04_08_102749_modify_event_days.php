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
        Schema::table('event_days', function (Blueprint $table) {
            // Add a single datetime column
            $table->dateTime('event_start')->nullable()->after('event_id');

            // Drop old columns
            $table->dropColumn(['day_date', 'day_start_hour']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_days', function (Blueprint $table) {
            $table->date('day_date')->after('event_id');
            $table->time('day_start_hour')->after('day_date');

            $table->dropColumn('event_start');
        });
    }
};
