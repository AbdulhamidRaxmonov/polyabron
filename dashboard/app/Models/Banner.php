<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $fillable = [
        'title_uz', 'title_ru', 'image', 'link',
        'type', 'venue_id', 'is_active', 'sort_order',
        'starts_at', 'ends_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }
}
