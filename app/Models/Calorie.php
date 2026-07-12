<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Calorie extends Model
{
    protected $fillable = [
        'consumed',
        'calories',
    ];
}
