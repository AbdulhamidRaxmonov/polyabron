<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('address');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->string('city')->default('Toshkent');
            $table->string('district')->nullable();
            $table->string('phone', 20)->nullable();
            $table->decimal('price_per_hour', 15, 2)->default(0);
            $table->decimal('price_weekend', 15, 2)->nullable();
            $table->integer('min_booking_hours')->default(1);
            $table->integer('max_booking_hours')->default(24);
            $table->time('open_time')->default('08:00:00');
            $table->time('close_time')->default('23:00:00');
            $table->json('amenities')->nullable();
            $table->enum('status', ['pending', 'active', 'inactive', 'rejected'])->default('pending');
            $table->boolean('is_featured')->default(false);
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('reviews_count')->default(0);
            $table->integer('bookings_count')->default(0);
            $table->json('images')->nullable();
            $table->string('cover_image')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['latitude', 'longitude']);
            $table->index(['status', 'is_featured']);
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venues');
    }
};
