<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
      public function up()
    {
        Schema::table('event_days', function (Blueprint $table) {
            // Add 'day_start_hour' as a TIME column, nullable for now
            $table->time('day_start_hour')->nullable()->after('day_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('event_days', function (Blueprint $table) {
            $table->dropColumn('day_start_hour');
        });
    }
};
