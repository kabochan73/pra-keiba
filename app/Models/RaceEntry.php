<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RaceEntry extends Model
{
    protected $fillable = [
        'race_id',
        'gate_number',
        'horse_number',
        'horse_name',
        'horse_url',
        'sex_age',
        'burden_weight',
        'jockey',
        'trainer',
        'horse_weight',
        'win_odds',
        'popularity',
    ];

    public function race(): BelongsTo
    {
        return $this->belongsTo(Race::class);
    }
}
