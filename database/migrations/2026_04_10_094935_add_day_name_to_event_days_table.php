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
            $table->string('day_name')->nullable()->after('event_id');
        });
    }

    public function down()
    {
        Schema::table('event_days', function (Blueprint $table) {
            $table->dropColumn('day_name');
        });
    }
};
