<?php

namespace App\Http\Controllers;

use App\Models\Payout;
use App\Models\Race;
use App\Models\RaceResult;
use Illuminate\Http\Request;

class AnalysisController extends Controller
{
    public function popularity(Request $request)
    {
        // フィルター用レース名一覧
        $raceNames = Race::select('name')->distinct()->orderBy('name')->pluck('name');

        $selectedName = $request->query('race_name');

        // 対象レースを絞り込む
        $raceQuery = Race::query();
        if ($selectedName) {
            $raceQuery->where('name', $selectedName);
        }
        $targetRaceIds = $raceQuery->pluck('id');

        if ($targetRaceIds->isEmpty()) {
            return view('analysis.popularity', compact('raceNames', 'selectedName'))
                ->with('stats', collect())
                ->with('races', collect());
        }

        // 対象レースの一覧（サイドバー用）
        $races = Race::whereIn('id', $targetRaceIds)
            ->orderByDesc('race_date')
            ->get();

        // 対象レースの全結果を取得
        $results = RaceResult::whereIn('race_id', $targetRaceIds)
            ->whereNotNull('popularity')
            ->get();

        // 単勝・複勝の配当をrace_id+馬番でマップ化
        $payouts = Payout::whereIn('race_id', $targetRaceIds)
            ->whereIn('bet_type', ['単勝', '複勝'])
            ->get();

        $payoutMap = [];
        foreach ($payouts as $p) {
            $payoutMap[$p->race_id][$p->bet_type][$p->combination] = $p->payout;
        }

        // 人気別に集計
        $stats = [];
        foreach ($results as $r) {
            $pop = $r->popularity;
            if (!isset($stats[$pop])) {
                $stats[$pop] = [
                    'popularity'     => $pop,
                    'count'          => 0,
                    'wins'           => 0,
                    'top2'           => 0,
                    'top3'           => 0,
                    'win_return'     => 0,
                    'place_return'   => 0,
                ];
            }

            $stats[$pop]['count']++;

            $rank = $r->rank;
            $raceId = $r->race_id;
            $horseNum = (string) $r->horse_number;

            if ($rank === '1') {
                $stats[$pop]['wins']++;
                $stats[$pop]['top2']++;
                $stats[$pop]['top3']++;
                // 単勝払戻
                $stats[$pop]['win_return'] += $payoutMap[$raceId]['単勝'][$horseNum] ?? 0;
                // 複勝払戻
                $stats[$pop]['place_return'] += $payoutMap[$raceId]['複勝'][$horseNum] ?? 0;
            } elseif ($rank === '2') {
                $stats[$pop]['top2']++;
                $stats[$pop]['top3']++;
                $stats[$pop]['place_return'] += $payoutMap[$raceId]['複勝'][$horseNum] ?? 0;
            } elseif ($rank === '3') {
                $stats[$pop]['top3']++;
                $stats[$pop]['place_return'] += $payoutMap[$raceId]['複勝'][$horseNum] ?? 0;
            }
        }

        ksort($stats);

        // 回収率を計算（100円投資ベース）
        $stats = array_map(function ($s) {
            $count = $s['count'];
            $investment = $count * 100;

            $s['win_rate']     = $count > 0 ? round($s['wins']  / $count * 100, 1) : 0;
            $s['rentai_rate']  = $count > 0 ? round($s['top2']  / $count * 100, 1) : 0;
            $s['fukusho_rate'] = $count > 0 ? round($s['top3']  / $count * 100, 1) : 0;
            $s['win_roi']      = $investment > 0 ? round($s['win_return']   / $investment * 100, 1) : 0;
            $s['place_roi']    = $investment > 0 ? round($s['place_return'] / $investment * 100, 1) : 0;

            return $s;
        }, $stats);

        return view('analysis.popularity', compact('raceNames', 'selectedName', 'stats', 'races'));
    }
}
