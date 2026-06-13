<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Review extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'venue_id', 'booking_id',
        'rating', 'comment', 'images',
        'is_visible', 'owner_replied_at', 'owner_reply',
    ];

    protected $casts = [
        'images' => 'array',
        'is_visible' => 'boolean',
        'owner_replied_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::created(function ($review) {
            $review->venue->updateRating();
        });

        static::updated(function ($review) {
            $review->venue->updateRating();
        });

        static::deleted(function ($review) {
            $review->venue->updateRating();
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
