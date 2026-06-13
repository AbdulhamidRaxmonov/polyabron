<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Booking extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'booking_number', 'user_id', 'venue_id',
        'booking_date', 'start_time', 'end_time',
        'duration_hours', 'price_per_hour', 'total_amount',
        'discount_amount', 'final_amount', 'status',
        'payment_status', 'payment_method', 'transaction_id',
        'notes', 'cancel_reason',
        'confirmed_at', 'cancelled_at', 'completed_at',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'price_per_hour' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($booking) {
            $booking->booking_number = 'BK' . strtoupper(Str::random(8));
        });
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }

    // Scopes
    public function scopeUpcoming($query)
    {
        return $query->where('booking_date', '>=', now()->toDateString())
                     ->where('status', 'confirmed');
    }

    public function scopePast($query)
    {
        return $query->where('booking_date', '<', now()->toDateString())
                     ->orWhere('status', 'completed');
    }
}
