<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Weight extends Model
{
    protected $fillable = [
        'config_id',
        'weight',
    ];

    public function config()
    {
        return $this->belongsTo(Config::class);
    }
}
