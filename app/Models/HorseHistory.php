<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HorseHistory extends Model
{
    protected $fillable = [
        'horse_name',
        'horse_id',
        'race_date',
        'venue',
        'weather',
        'race_number',
        'race_name',
        'horses_count',
        'gate_number',
        'horse_number',
        'win_odds',
        'popularity',
        'rank',
        'jockey',
        'burden_weight',
        'course',
        'track_condition',
        'finish_time',
        'margin',
        'corner_order',
        'pace',
        'last_3f',
        'horse_weight',
        'winner',
    ];

    protected $casts = [
        'race_date' => 'date',
    ];
}
