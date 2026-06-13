<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsVerification extends Model
{
    protected $fillable = [
        'phone', 'code', 'type',
        'is_used', 'attempts', 'expires_at',
    ];

    protected $casts = [
        'is_used' => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isValid(string $code): bool
    {
        return !$this->is_used
            && !$this->isExpired()
            && $this->attempts < 5
            && $this->code === $code;
    }
}
