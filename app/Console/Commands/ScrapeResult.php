<?php

namespace App\Console\Commands;

use App\Services\NetkeibaScraperService;
use Illuminate\Console\Command;

class ScrapeResult extends Command
{
    protected $signature = 'scrape:result {race_id : netkeibaのレースID (例: 202501010101)}';

    protected $description = 'netkeiba.comからレース結果を取得してDBに保存する';

    public function handle(NetkeibaScraperService $scraper): int
    {
        $raceId = $this->argument('race_id');

        if (!preg_match('/^\d{12}$/', $raceId)) {
            $this->error("レースIDは12桁の数字で指定してください。例: 202501010101");
            return self::FAILURE;
        }

        $this->info("レース結果を取得中: {$raceId}");

        try {
            $race = $scraper->scrapeResult($raceId);
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info("レース: {$race->name} ({$race->venue} {$race->race_number}R)");
        if ($race->weather || $race->track_condition) {
            $this->info("天気: {$race->weather} / 馬場: {$race->track_condition}");
        }
        $this->newLine();

        // 着順結果
        $this->info("【着順結果】");
        $results = $race->results;
        $rows = $results->map(fn ($r) => [
            $r->rank,
            $r->gate_number ?? '-',
            $r->horse_number,
            $r->horse_name,
            $r->jockey ?? '-',
            $r->finish_time ?? '-',
            $r->margin ?? '-',
            $r->last_3f ?? '-',
            $r->win_odds ? "{$r->win_odds}倍" : '-',
        ])->toArray();

        $this->table(
            ['着順', '枠', '馬番', '馬名', '騎手', 'タイム', '着差', '上がり', 'オッズ'],
            $rows
        );

        // 配当
        $payouts = $race->payouts;
        if ($payouts->count() > 0) {
            $this->newLine();
            $this->info("【払戻金】");
            $payoutRows = $payouts->map(fn ($p) => [
                $p->bet_type,
                $p->combination,
                number_format($p->payout) . '円',
                $p->popularity ? "{$p->popularity}番人気" : '-',
            ])->toArray();

            $this->table(['券種', '組み合わせ', '払戻金', '人気'], $payoutRows);
        }

        return self::SUCCESS;
    }
}
