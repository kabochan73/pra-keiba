<?php

namespace App\Console\Commands;

use App\Services\NetkeibaScraperService;
use Illuminate\Console\Command;

class ScrapeShutuba extends Command
{
    protected $signature = 'scrape:shutuba {race_id : netkeibaのレースID (例: 202501010101)}';

    protected $description = 'netkeiba.comから出馬表を取得してDBに保存する';

    public function handle(NetkeibaScraperService $scraper): int
    {
        $raceId = $this->argument('race_id');

        if (!preg_match('/^\d{12}$/', $raceId)) {
            $this->error("レースIDは12桁の数字で指定してください。例: 202501010101");
            return self::FAILURE;
        }

        $this->info("出馬表を取得中: {$raceId}");

        try {
            $race = $scraper->scrapeShutuba($raceId);
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info("レース: {$race->name} ({$race->venue} {$race->race_number}R)");
        $this->info("日付: {$race->race_date}");

        if ($race->course && $race->distance) {
            $this->info("コース: {$race->course}{$race->distance}m");
        }

        $entries = $race->entries;
        $this->info("出走頭数: {$entries->count()}頭");
        $this->newLine();

        $rows = $entries->map(fn ($e) => [
            $e->gate_number,
            $e->horse_number,
            $e->horse_name,
            $e->sex_age ?? '-',
            $e->burden_weight ?? '-',
            $e->jockey ?? '-',
            $e->win_odds ? "{$e->win_odds}倍" : '-',
            $e->popularity ? "{$e->popularity}番人気" : '-',
        ])->toArray();

        $this->table(
            ['枠', '馬番', '馬名', '性齢', '斤量', '騎手', '単勝オッズ', '人気'],
            $rows
        );

        return self::SUCCESS;
    }
}
