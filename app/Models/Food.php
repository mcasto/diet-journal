<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Food extends Model
{
    protected $fillable = [
        'user_id',
        'consumed',
        'consumed_at'
    ];

    protected $casts = [
        'consumed_at' => 'datetime'
    ];
}
