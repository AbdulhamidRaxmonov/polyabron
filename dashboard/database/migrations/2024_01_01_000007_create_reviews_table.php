<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('venue_id')->constrained('venues')->onDelete('cascade');
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->onDelete('set null');
            $table->tinyInteger('rating')->unsigned();
            $table->text('comment')->nullable();
            $table->json('images')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->timestamp('owner_replied_at')->nullable();
            $table->text('owner_reply')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'booking_id']);
            $table->index(['venue_id', 'is_visible']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
