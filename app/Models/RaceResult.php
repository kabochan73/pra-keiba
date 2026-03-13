<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RaceResult extends Model
{
    protected $fillable = [
        'race_id',
        'rank',
        'gate_number',
        'horse_number',
        'horse_name',
        'sex_age',
        'burden_weight',
        'jockey',
        'finish_time',
        'margin',
        'corner_order',
        'last_3f',
        'win_odds',
        'popularity',
        'horse_weight',
    ];

    public function race(): BelongsTo
    {
        return $this->belongsTo(Race::class);
    }
}
