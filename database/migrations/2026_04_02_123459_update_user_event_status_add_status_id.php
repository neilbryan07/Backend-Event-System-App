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
        Schema::table('user_event_statuses', function (Blueprint $table) {
            // Drop old status column if it exists
            if (Schema::hasColumn('user_event_statuses', 'status')) {
                $table->dropColumn('status');
            }

            // Add foreign key to statuses table if not exists
            if (!Schema::hasColumn('user_event_statuses', 'status_id')) {
                $table->unsignedBigInteger('status_id')->nullable()->after('id');
                $table->foreign('status_id')->references('id')->on('statuses')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_event_statuses', function (Blueprint $table) {
            // Recreate the old status column
            if (!Schema::hasColumn('user_event_statuses', 'status')) {
                $table->string('status')->nullable()->after('id');
            }

            // Drop foreign key and status_id column
            if (Schema::hasColumn('user_event_statuses', 'status_id')) {
                $table->dropForeign(['status_id']);
                $table->dropColumn('status_id');
            }
        });
    }
};