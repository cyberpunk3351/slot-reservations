<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IdempotencyKey extends Model
{
    protected $fillable = [
        'key', 'method', 'endpoint', 'response_code', 'hold_id', 'response_body'
    ];

    protected $casts = ['response_body' => 'array'];
}
