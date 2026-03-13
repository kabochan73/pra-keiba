<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Race extends Model
{
    protected $fillable = [
        'race_id',
        'name',
        'race_date',
        'venue',
        'race_number',
        'course',
        'distance',
        'direction',
        'weather',
        'track_condition',
        'grade',
    ];

    protected $casts = [
        'race_date' => 'date',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(RaceEntry::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(RaceResult::class)->orderByRaw('CAST(rank AS INTEGER)');
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }
}
