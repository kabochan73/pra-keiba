<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payout extends Model
{
    protected $fillable = [
        'race_id',
        'bet_type',
        'combination',
        'payout',
        'popularity',
    ];

    public function race(): BelongsTo
    {
        return $this->belongsTo(Race::class);
    }
}
