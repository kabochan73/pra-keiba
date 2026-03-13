<?php

namespace App\Http\Controllers;

use App\Models\HorseHistory;
use App\Models\RaceResult;
use Illuminate\Http\Request;

class HorseController extends Controller
{
    public function show(Request $request)
    {
        $name = $request->query('name');

        if (!$name) {
            abort(404);
        }

        // horse_historiesがあればそちらを優先、なければrace_resultsから
        $histories = HorseHistory::where('horse_name', $name)
            ->orderByDesc('race_date')
            ->get();

        $useHistory = $histories->isNotEmpty();

        if (!$useHistory) {
            $results = RaceResult::with('race')
                ->where('horse_name', $name)
                ->join('races', 'race_results.race_id', '=', 'races.id')
                ->orderByDesc('races.race_date')
                ->select('race_results.*')
                ->get();

            if ($results->isEmpty()) {
                abort(404);
            }
        } else {
            $results = collect();
        }

        $data = $useHistory ? $histories : $results->map(fn($r) => (object)[
            'race_date'      => $r->race->race_date,
            'venue'          => $r->race->venue . ' ' . $r->race->race_number . 'R',
            'weather'        => $r->race->weather,
            'race_name'      => $r->race->name,
            'race_id'        => $r->race->id,
            'horses_count'   => null,
            'gate_number'    => $r->gate_number,
            'horse_number'   => $r->horse_number,
            'win_odds'       => $r->win_odds,
            'popularity'     => $r->popularity,
            'rank'           => $r->rank,
            'jockey'         => $r->jockey,
            'burden_weight'  => $r->burden_weight,
            'course'         => ($r->race->course ?? '') . ($r->race->distance ?? ''),
            'track_condition'=> $r->race->track_condition,
            'finish_time'    => $r->finish_time,
            'margin'         => $r->margin,
            'corner_order'   => $r->corner_order,
            'last_3f'        => $r->last_3f,
            'horse_weight'   => $r->horse_weight,
            'winner'         => null,
            'race_link'      => $r->race->id,
        ]);

        $total = $data->count();
        $wins  = $data->filter(fn($r) => $r->rank === '1')->count();
        $top2  = $data->filter(fn($r) => in_array($r->rank, ['1', '2']))->count();
        $top3  = $data->filter(fn($r) => in_array($r->rank, ['1', '2', '3']))->count();

        return view('horses.show', compact('name', 'data', 'useHistory', 'total', 'wins', 'top2', 'top3'));
    }
}
