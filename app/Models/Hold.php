<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Hold extends Model
{
    protected $fillable = [
        'slot_id', 'status', 'idempotency_key', 'expires_at', 'confirmed_at', 'cancelled_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function slot(): BelongsTo
    {
        return $this->belongsTo(Slot::class);
    }

    public function isExpired(): bool
    {
        return now()->greaterThan($this->expires_at);
    }
}
