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
            $table->dateTime('event_cutoff')->nullable()->after('day_start_hour');
        });
    }

    public function down(): void
    {
        Schema::table('event_days', function (Blueprint $table) {
            $table->dropColumn('event_cutoff');
        });
    }
};
