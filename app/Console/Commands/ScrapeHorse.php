<?php

namespace App\Console\Commands;

use App\Services\NetkeibaScraperService;
use Illuminate\Console\Command;

class ScrapeHorse extends Command
{
    protected $signature = 'scrape:horse {horse_name : 馬名}';

    protected $description = 'netkeiba.comから馬の全成績を取得してDBに保存する';

    public function handle(NetkeibaScraperService $scraper): int
    {
        $horseName = $this->argument('horse_name');

        $this->info("「{$horseName}」の成績を取得中...");

        try {
            $result = $scraper->scrapeHorseHistory($horseName);
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info("{$result['count']} 件の成績を保存しました。");

        return self::SUCCESS;
    }
}
