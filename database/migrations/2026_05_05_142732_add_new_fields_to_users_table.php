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
        Schema::table('users', function (Blueprint $table) {

            $table->string('mobile_no')->nullable()->after('email');

            $table->string('status')->nullable()->after('mobile_no');

            $table->integer('age')->nullable()->after('status');

            // if you already have gender column in users table,
            // remove this line to avoid duplicate column error

            $table->date('date_hired')->nullable()->after('gender');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $table->dropColumn([
                'mobile_no',
                'status',
                'age',
                'date_hired'
            ]);
        });
    }
};
