<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Venue extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'owner_id', 'category_id', 'name', 'description',
        'address', 'latitude', 'longitude', 'city', 'district',
        'phone', 'price_per_hour', 'price_weekend',
        'min_booking_hours', 'max_booking_hours',
        'open_time', 'close_time', 'amenities',
        'status', 'is_featured', 'rating', 'reviews_count',
        'bookings_count', 'images', 'cover_image',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'price_per_hour' => 'decimal:2',
        'price_weekend' => 'decimal:2',
        'is_featured' => 'boolean',
        'rating' => 'decimal:2',
        'amenities' => 'array',
        'images' => 'array',
    ];

    // Relationships
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function timeSlots()
    {
        return $this->hasMany(TimeSlot::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeNearby($query, float $lat, float $lng, float $radius = 10)
    {
        return $query->selectRaw(
            '*, (6371 * acos(cos(radians(?)) * cos(radians(latitude))
             * cos(radians(longitude) - radians(?))
             + sin(radians(?)) * sin(radians(latitude)))) AS distance',
            [$lat, $lng, $lat]
        )->having('distance', '<', $radius)
          ->orderBy('distance');
    }

    // Helpers
    public function updateRating(): void
    {
        $avg = $this->reviews()->where('is_visible', true)->avg('rating');
        $count = $this->reviews()->where('is_visible', true)->count();
        $this->update(['rating' => round($avg, 2), 'reviews_count' => $count]);
    }
}
