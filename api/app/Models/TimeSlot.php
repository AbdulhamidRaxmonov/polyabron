<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimeSlot extends Model
{
    protected $fillable = [
        'venue_id', 'date', 'start_time',
        'end_time', 'price', 'status', 'booking_id',
    ];

    protected $casts = [
        'date' => 'date',
        'price' => 'decimal:2',
    ];

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }
}
