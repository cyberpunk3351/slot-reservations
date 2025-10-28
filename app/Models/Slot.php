<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Slot extends Model
{
    protected $fillable = ['name', 'capacity', 'remaining'];
}
