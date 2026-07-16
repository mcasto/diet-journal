<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Config extends Model
{
    protected $fillable = [
        'user_id',
        'sex',
        'height',
        'birthdate',
        'exercise',
        'target',
    ];

    protected $casts = [
        'birthdate' => 'date',
    ];

    private const DEFAULT_WEIGHT = 81.65;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function weights()
    {
        return $this->hasMany(Weight::class);
    }

    public function latestWeight()
    {
        return $this->hasOne(Weight::class)->latestOfMany();
    }

    /**
     * All logged weights except the very first, which is the default
     * assigned when the config was created (never a real reading).
     */
    public function loggedWeights()
    {
        return $this->weights()
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->skip(1)
            ->values();
    }

    /**
     * The weight in effect on a given date: the most recent logged weight
     * on or before that date, falling back to the earliest logged weight
     * if the date predates all of them, and to the default weight if
     * nothing has been logged yet at all.
     */
    public function weightAsOf(Carbon $date)
    {
        $weights = $this->loggedWeights();

        if ($weights->isEmpty()) {
            return $this->latestWeight;
        }

        return $weights->filter(fn($w) => $w->created_at->lte($date))->last() ?? $weights->first();
    }

    public function ageAsOf(Carbon $date)
    {
        return $this->birthdate ? (int) Carbon::parse($this->birthdate)->diffInYears($date) : null;
    }

    public static function forUser(int $userId): self
    {
        $config = static::firstOrCreate(['user_id' => $userId])->refresh();

        if (! $config->latestWeight) {
            $config->weights()->create(['weight' => self::DEFAULT_WEIGHT]);
            $config->unsetRelation('latestWeight');
        }

        return $config;
    }
}
