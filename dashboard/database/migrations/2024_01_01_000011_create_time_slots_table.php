<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained('venues')->onDelete('cascade');
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('price', 15, 2)->nullable();
            $table->enum('status', ['available', 'booked', 'blocked'])->default('available');
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->onDelete('set null');
            $table->timestamps();

            $table->index(['venue_id', 'date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_slots');
    }
};
