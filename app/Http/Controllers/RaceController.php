<?php

namespace App\Http\Controllers;

use App\Models\Race;

class RaceController extends Controller
{
    public function index()
    {
        $races = Race::orderByDesc('race_date')
            ->orderBy('venue')
            ->orderBy('race_number')
            ->paginate(20);

        return view('races.index', compact('races'));
    }

    public function show(Race $race)
    {
        $race->load(['entries' => fn($q) => $q->orderBy('horse_number'), 'results', 'payouts']);

        return view('races.show', compact('race'));
    }
}
