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
     Schema::create('user_event_statuses', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('event_day_id')->constrained()->cascadeOnDelete();
    $table->string('status')->default('Not Registered'); // Pre-Registered, Attended, Late Registered, Cancelled
    $table->timestamp('scanned_at')->nullable();
    $table->timestamps();

    $table->unique(['user_id','event_day_id']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_event_statuses');
    }
};
