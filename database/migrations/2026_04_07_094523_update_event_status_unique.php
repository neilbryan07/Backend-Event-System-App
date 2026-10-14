<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 🔹 Drop old table safely
        Schema::dropIfExists('user_event_statuses');

        // 🔹 Recreate table without unique constraint
        Schema::create('user_event_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_day_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('status_id')->nullable();
            $table->foreign('status_id')->references('id')->on('statuses')->onDelete('set null');
            $table->timestamp('scanned_at')->nullable();
            $table->timestamps();

            // ✅ No unique constraint, multiple entries allowed
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_event_statuses');
    }
};