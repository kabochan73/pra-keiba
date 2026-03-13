<?php

namespace App\Services;

use App\Models\HorseHistory;
use App\Models\Race;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DomCrawler\Crawler;

class NetkeibaScraperService
{
    private Client $client;

    // 競馬場コード -> 名前マッピング
    private const VENUE_MAP = [
        '01' => '札幌', '02' => '函館', '03' => '福島', '04' => '新潟',
        '05' => '東京', '06' => '中山', '07' => '中京', '08' => '京都',
        '09' => '阪神', '10' => '小倉',
    ];

    public function __construct()
    {
        $this->client = new Client([
            'timeout'         => 30,
            'connect_timeout' => 10,
            'headers'         => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'ja,en-US;q=0.7,en;q=0.3',
                'Accept-Encoding' => 'gzip, deflate',
            ],
        ]);
    }

    /**
     * 出馬表をスクレイピングしてDBに保存する
     */
    public function scrapeShutuba(string $raceId): Race
    {
        $url = "https://race.netkeiba.com/race/shutuba.html?race_id={$raceId}";
        $html = $this->fetchHtml($url);
        $crawler = new Crawler($html);

        $race = $this->saveRaceInfo($raceId, $crawler);

        // 既存の出馬表を削除して再取得
        $race->entries()->delete();

        $crawler->filter('table.Shutuba_Table tr')->each(function (Crawler $row) use ($race) {
            $entry = $this->parseEntryRow($row);
            if ($entry) {
                $race->entries()->create($entry);
            }
        });

        // オッズ・人気が取れなかった場合（過去レース）は結果ページから補完する
        if ($race->entries()->whereNull('win_odds')->exists()) {
            $this->fillOddsFromResult($race, $raceId);
        }

        return $race;
    }

    /**
     * レース結果をスクレイピングしてDBに保存する
     */
    public function scrapeResult(string $raceId): Race
    {
        $url = "https://race.netkeiba.com/race/result.html?race_id={$raceId}";
        $html = $this->fetchHtml($url);
        $crawler = new Crawler($html);

        $race = Race::firstOrCreate(
            ['race_id' => $raceId],
            $this->buildRaceInfoFromResult($raceId, $crawler)
        );

        // レース情報の天気・馬場状態を更新
        $this->updateRaceCondition($race, $crawler);

        // 既存の結果を削除して再取得
        $race->results()->delete();
        $race->payouts()->delete();

        // 着順結果をパース
        $crawler->filter('table.RaceTable01 tr')->each(function (Crawler $row) use ($race) {
            $result = $this->parseResultRow($row);
            if ($result) {
                $race->results()->create($result);
            }
        });

        // 配当をパース
        $this->parsePayouts($race, $crawler);

        return $race;
    }

    /**
     * 馬の全成績をスクレイピングしてDBに保存する
     */
    public function scrapeHorseHistory(string $horseName): array
    {
        // race_entriesからhorse_urlを取得
        $entry = \App\Models\RaceEntry::where('horse_name', $horseName)
            ->whereNotNull('horse_url')
            ->first();

        if (!$entry) {
            throw new \RuntimeException("「{$horseName}」のURLが見つかりません。先に出馬表を取得してください。");
        }

        // URLから馬IDを抽出 (例: https://db.netkeiba.com/horse/2020103275)
        if (!preg_match('/\/horse\/(\d+)/', $entry->horse_url, $m)) {
            throw new \RuntimeException("馬URLの形式が不正です: {$entry->horse_url}");
        }
        $horseId = $m[1];

        // AjaxエンドポイントからJSON取得
        try {
            $response = $this->client->get('https://db.netkeiba.com/horse/ajax_horse_results.html', [
                'query' => ['input' => 'UTF-8', 'output' => 'json', 'id' => $horseId],
            ]);
            $json = json_decode((string) $response->getBody(), true);
        } catch (GuzzleException $e) {
            throw new \RuntimeException("成績データの取得に失敗しました: " . $e->getMessage());
        }

        if (($json['status'] ?? '') !== 'OK') {
            throw new \RuntimeException("成績データの取得に失敗しました（status: {$json['status']}）");
        }

        $crawler = new Crawler($json['data']);

        // 既存データを削除して再取得
        HorseHistory::where('horse_name', $horseName)->delete();

        $saved = 0;
        $crawler->filter('table tr')->slice(1)->each(function (Crawler $row) use ($horseName, $horseId, &$saved) {
            $cols = $row->filter('td');
            if ($cols->count() < 20) {
                return;
            }

            $dateText = trim($cols->eq(0)->text());
            if (!preg_match('/\d{4}\/\d{2}\/\d{2}/', $dateText)) {
                return;
            }

            $rank = trim($cols->eq(11)->text());

            HorseHistory::create([
                'horse_name'     => $horseName,
                'horse_id'       => $horseId,
                'race_date'      => $dateText,
                'venue'          => trim($cols->eq(1)->text()) ?: null,
                'weather'        => trim($cols->eq(2)->text()) ?: null,
                'race_number'    => $this->toInt(trim($cols->eq(3)->text())),
                'race_name'      => trim($cols->eq(4)->text()) ?: null,
                'horses_count'   => $this->toInt(trim($cols->eq(6)->text())),
                'gate_number'    => $this->toInt(trim($cols->eq(7)->text())),
                'horse_number'   => $this->toInt(trim($cols->eq(8)->text())),
                'win_odds'       => $this->toFloat(trim($cols->eq(9)->text())),
                'popularity'     => $this->toInt(trim($cols->eq(10)->text())),
                'rank'           => $rank ?: null,
                'jockey'         => trim($cols->eq(12)->text()) ?: null,
                'burden_weight'  => $this->toFloat(trim($cols->eq(13)->text())),
                'course'         => trim($cols->eq(14)->text()) ?: null,
                'track_condition'=> trim($cols->eq(16)->text()) ?: null,
                'finish_time'    => trim($cols->eq(18)->text()) ?: null,
                'margin'         => trim($cols->eq(19)->text()) ?: null,
                'corner_order'   => trim($cols->eq(21)->text()) ?: null,
                'pace'           => trim($cols->eq(22)->text()) ?: null,
                'last_3f'        => $this->toFloat(trim($cols->eq(23)->text())),
                'horse_weight'   => trim($cols->eq(24)->text()) ?: null,
                'winner'         => trim($cols->eq(27)->text()) ?: null,
            ]);
            $saved++;
        });

        return ['horse_name' => $horseName, 'horse_id' => $horseId, 'count' => $saved];
    }

    /**
     * 結果ページからオッズ・人気を取得して race_entries に補完する
     */
    private function fillOddsFromResult(Race $race, string $raceId): void
    {
        try {
            $url     = "https://race.netkeiba.com/race/result.html?race_id={$raceId}";
            $html    = $this->fetchHtml($url);
            $crawler = new Crawler($html);

            // 結果テーブルから馬番→オッズ・人気のマップを作る
            $oddsMap = [];
            $crawler->filter('table.RaceTable01 tr')->each(function (Crawler $row) use (&$oddsMap) {
                $umabanTd = $row->filter('td.Num.Txt_C');
                if ($umabanTd->count() === 0) {
                    return;
                }
                $horseNumber = (int) trim($umabanTd->text());
                if ($horseNumber === 0) {
                    return;
                }

                $oddsTd      = $row->filter('td.Odds.Txt_R');
                $popularityTd = $row->filter('td.Odds.Txt_C');

                $oddsMap[$horseNumber] = [
                    'win_odds'   => $oddsTd->count() > 0 ? $this->toFloat(trim($oddsTd->text())) : null,
                    'popularity' => $popularityTd->count() > 0 ? $this->toInt(trim($popularityTd->text())) : null,
                ];
            });

            // race_entries を更新
            foreach ($oddsMap as $horseNumber => $data) {
                $race->entries()
                    ->where('horse_number', $horseNumber)
                    ->update($data);
            }
        } catch (\Throwable) {
            // 結果ページが取れなくても出馬表の保存は成功扱いにする
        }
    }

    /**
     * GuzzleでHTMLを取得する
     */
    private function fetchHtml(string $url): string
    {
        try {
            $response = $this->client->get($url);
            return (string) $response->getBody();
        } catch (GuzzleException $e) {
            throw new \RuntimeException("HTMLの取得に失敗しました: {$url}\n" . $e->getMessage());
        }
    }

    /**
     * レース基本情報を保存/更新する（出馬表ページから）
     */
    private function saveRaceInfo(string $raceId, Crawler $crawler): Race
    {
        $info = $this->parseRaceId($raceId);

        // レース名
        $name = $this->extractText($crawler, '.RaceName');
        if (empty($name)) {
            $name = $this->extractText($crawler, '.RaceTitle');
        }

        // レース条件（芝/ダート、距離、回りなど）
        $raceData = $this->extractText($crawler, '.RaceData01');
        $course    = null;
        $distance  = null;
        $direction = null;

        if (preg_match('/(芝|ダート|障害)\s*(\d+)m/', $raceData, $m)) {
            $course   = $m[1];
            $distance = (int) $m[2];
        }
        if (preg_match('/(右|左|直線)/', $raceData, $m)) {
            $direction = $m[1];
        }

        // グレード
        $grade = null;
        if (preg_match('/(G1|G2|G3|GⅠ|GⅡ|GⅢ)/', $name . $raceData, $m)) {
            $grade = $m[1];
        }

        return Race::updateOrCreate(
            ['race_id' => $raceId],
            [
                'name'        => $name ?: "レース{$info['race_number']}",
                'race_date'   => $info['date'],
                'venue'       => $info['venue'],
                'race_number' => $info['race_number'],
                'course'      => $course,
                'distance'    => $distance,
                'direction'   => $direction,
                'grade'       => $grade,
            ]
        );
    }

    /**
     * レース結果ページからレース基本情報を作成する
     */
    private function buildRaceInfoFromResult(string $raceId, Crawler $crawler): array
    {
        $info = $this->parseRaceId($raceId);

        $name = $this->extractText($crawler, '.RaceName');
        if (empty($name)) {
            $name = $this->extractText($crawler, '.RaceTitle');
        }

        return [
            'name'        => $name ?: "レース{$info['race_number']}",
            'race_date'   => $info['date'],
            'venue'       => $info['venue'],
            'race_number' => $info['race_number'],
        ];
    }

    /**
     * 天気・馬場状態を更新する
     */
    private function updateRaceCondition(Race $race, Crawler $crawler): void
    {
        $raceData = $this->extractText($crawler, '.RaceData01');

        $weather        = null;
        $trackCondition = null;

        if (preg_match('/天候\s*[:：]\s*(\S+)/', $raceData, $m)) {
            $weather = $m[1];
        }
        if (preg_match('/馬場\s*[:：]\s*(\S+)/', $raceData, $m)) {
            $trackCondition = $m[1];
        }

        $race->update(array_filter([
            'weather'         => $weather,
            'track_condition' => $trackCondition,
        ]));
    }

    /**
     * 出馬表の1行をパースする
     */
    private function parseEntryRow(Crawler $row): ?array
    {
        // HorseInfoクラスのtdから馬名を取得（ヘッダー行はスキップ）
        $horseInfoTd = $row->filter('td.HorseInfo');
        if ($horseInfoTd->count() === 0) {
            return null;
        }

        // 馬番：class="Umaban数字 Txt_C" のtd
        $umabanTd = $row->filter('td[class*="Umaban"]');
        if ($umabanTd->count() === 0) {
            return null;
        }
        $horseNumber = trim($umabanTd->first()->text());
        if (!is_numeric($horseNumber)) {
            return null;
        }

        // 枠番：class="Waku数字 Txt_C" のtd
        $wakuTd = $row->filter('td[class*="Waku"]');
        $gateNumber = $wakuTd->count() > 0 ? (int) trim($wakuTd->first()->text()) : null;

        // 馬名リンク
        $horseLink = $horseInfoTd->filter('a');
        $horseName = $horseLink->count() > 0 ? trim($horseLink->first()->text()) : trim($horseInfoTd->text());
        $horseUrl  = $horseLink->count() > 0 ? $horseLink->first()->attr('href') : null;

        // 性齢：class="Barei Txt_C"
        $sexAge = $this->extractText($row, 'td.Barei');

        // 斤量・騎手・調教師・馬体重
        $jockeyTd   = $row->filter('td.Jockey');
        $trainerTd  = $row->filter('td.Trainer');

        return [
            'gate_number'   => $gateNumber,
            'horse_number'  => (int) $horseNumber,
            'horse_name'    => $horseName,
            'horse_url'     => $horseUrl,
            'sex_age'       => $sexAge ?: null,
            'burden_weight' => $this->toFloat($this->extractExactClass($row, 'Txt_C')),
            'jockey'        => $jockeyTd->count() > 0 ? trim($jockeyTd->text()) : null,
            'trainer'       => $trainerTd->count() > 0 ? trim($trainerTd->text()) : null,
            'horse_weight'  => $this->extractText($row, 'td.Weight') ?: null,
            'win_odds'      => $this->toFloat($this->extractText($row, 'td.Txt_R.Popular')),
            'popularity'    => $this->toInt($this->extractText($row, 'td.Popular_Ninki')),
        ];
    }

    /**
     * 結果テーブルの1行をパースする
     */
    private function parseResultRow(Crawler $row): ?array
    {
        // 着順：class="Result_Num"
        $rankTd = $row->filter('td.Result_Num');
        if ($rankTd->count() === 0) {
            return null;
        }
        $rank = trim($rankTd->text());
        if (empty($rank)) {
            return null;
        }

        // 馬番：class="Num Txt_C"
        $umabanTd = $row->filter('td.Num.Txt_C');
        if ($umabanTd->count() === 0) {
            return null;
        }
        $horseNumber = trim($umabanTd->text());

        // 枠番：class="Num Waku数字 Txt_C"
        $wakuTd = $row->filter('td[class*="Waku"]');
        $gateNumber = $wakuTd->count() > 0 ? $this->toInt(trim($wakuTd->first()->text())) : null;

        // 馬名：class="Horse_Info"
        $horseTd   = $row->filter('td.Horse_Info');
        $horseName = $horseTd->count() > 0 ? trim($horseTd->first()->text()) : '';

        // 性齢：class="Horse_Info Txt_C"
        $sexAgeTd = $row->filter('td.Horse_Info.Txt_C');
        $sexAge   = $sexAgeTd->count() > 0 ? trim($sexAgeTd->text()) : null;

        // 斤量：class="Jockey_Info"
        $burdenTd = $row->filter('td.Jockey_Info');

        // 騎手：class="Jockey"
        $jockeyTd = $row->filter('td.Jockey');

        // タイム：class="Time" （最初のもの）
        $timeTds    = $row->filter('td.Time');
        $finishTime = $timeTds->count() > 0 ? trim($timeTds->first()->text()) : null;

        // 着差：class="Time" 2番目
        $margin = $timeTds->count() > 1 ? trim($timeTds->eq(1)->text()) : null;

        // オッズ：class="Odds Txt_R"
        $oddsTd = $row->filter('td.Odds.Txt_R');

        // 人気：class="Odds BgBlue02 Txt_C" or "Odds BgYellow Txt_C"
        $popularityTd = $row->filter('td.Odds.Txt_C');

        // 上がり：td.Time の3番目（1番目=タイム、2番目=着差、3番目=上がり）
        $last3fTd = $timeTds->count() > 2 ? $timeTds->eq(2) : null;

        // コーナー通過順：class="PassageRate"
        $cornerTd = $row->filter('td.PassageRate');

        // 馬体重：class="Weight"
        $weightTd = $row->filter('td.Weight');

        return [
            'rank'          => $rank,
            'gate_number'   => $gateNumber,
            'horse_number'  => (int) $horseNumber,
            'horse_name'    => $horseName,
            'sex_age'       => $sexAge,
            'burden_weight' => $burdenTd->count() > 0 ? $this->toFloat(trim($burdenTd->text())) : null,
            'jockey'        => $jockeyTd->count() > 0 ? trim($jockeyTd->text()) : null,
            'finish_time'   => $finishTime ?: null,
            'margin'        => $margin ?: null,
            'corner_order'  => $cornerTd->count() > 0 ? trim($cornerTd->text()) : null,
            'last_3f'       => $last3fTd ? $this->toFloat(trim($last3fTd->text())) : null,
            'win_odds'      => $oddsTd->count() > 0 ? $this->toFloat(trim($oddsTd->text())) : null,
            'popularity'    => $popularityTd->count() > 0 ? $this->toInt(trim($popularityTd->text())) : null,
            'horse_weight'  => $weightTd->count() > 0 ? trim($weightTd->text()) : null,
        ];
    }

    /**
     * 配当テーブルをパースしてDBに保存する
     */
    private function parsePayouts(Race $race, Crawler $crawler): void
    {
        $betTypeMap = [
            '単勝' => '単勝', '複勝' => '複勝', '枠連' => '枠連',
            '馬連' => '馬連', '馬単' => '馬単', 'ワイド' => 'ワイド',
            '三連複' => '三連複', '三連単' => '三連単',
            '3連複' => '三連複', '3連単' => '三連単',
        ];

        // 実際のHTML構造: <tr><th>単勝</th><td class="Result">4</td><td class="Payout">270円</td><td class="Ninki">2人気</td></tr>
        // 複勝など複数ある場合はResult/Payout/NinkiのtdにスペースまたはBRで区切られて入っている
        $crawler->filter('table.Payout_Detail_Table tr')->each(function (Crawler $row) use ($race, $betTypeMap) {
            $thNode = $row->filter('th');
            if ($thNode->count() === 0) {
                return;
            }
            $betTypeText = trim($thNode->text());

            $betType = null;
            foreach ($betTypeMap as $key => $val) {
                if (str_contains($betTypeText, $key)) {
                    $betType = $val;
                    break;
                }
            }
            if (!$betType) {
                return;
            }

            $resultTd  = $row->filter('td.Result');
            $payoutTd  = $row->filter('td.Payout');
            $ninkiTd   = $row->filter('td.Ninki');

            if ($resultTd->count() === 0 || $payoutTd->count() === 0) {
                return;
            }

            // 複数結果（複勝・ワイドなど）はスペース区切りで入っている
            $combinations = preg_split('/\s+/', trim($resultTd->text()), -1, PREG_SPLIT_NO_EMPTY);
            // 払戻金は「120円110円320円」のように円区切りで繋がっているので分割
            $payoutRaw = trim($payoutTd->text());
            $payouts   = preg_split('/(?<=円)/', $payoutRaw, -1, PREG_SPLIT_NO_EMPTY);
            // 人気は「2人気」「1人気」のような形式で複数入っている
            $popularities = $ninkiTd->count() > 0
                ? preg_split('/人気/', trim($ninkiTd->text()), -1, PREG_SPLIT_NO_EMPTY)
                : [];

            // ワイドなど組み合わせが「3 4」のように2頭で1組の場合は2つずつまとめる
            $isDouble = in_array($betType, ['馬連', '馬単', '枠連', 'ワイド']);
            $isTriple = in_array($betType, ['三連複', '三連単']);

            if ($isTriple && count($combinations) >= 3) {
                $chunks = array_chunk($combinations, 3);
            } elseif ($isDouble && count($combinations) >= 2) {
                $chunks = array_chunk($combinations, 2);
            } else {
                $chunks = array_map(fn($c) => [$c], $combinations);
            }

            foreach ($chunks as $i => $combo) {
                $combination = implode('-', $combo);
                $payoutText  = $payouts[$i] ?? '';
                $payout      = $this->parsePayout($payoutText);

                if ($combination && $payout) {
                    $ninkiText  = isset($popularities[$i]) ? trim($popularities[$i]) : null;
                    $popularity = $ninkiText !== null && $ninkiText !== '' ? $this->toInt(preg_replace('/[^\d]/', '', $ninkiText)) : null;

                    $race->payouts()->create([
                        'bet_type'    => $betType,
                        'combination' => $combination,
                        'payout'      => $payout,
                        'popularity'  => $popularity,
                    ]);
                }
            }
        });
    }

    /**
     * race_id (例: 202501010101) から年・場所・レース番号などを解析する
     * フォーマット: YYYY + 場所コード(2桁) + 回(2桁) + 日(2桁) + レース番号(2桁)
     */
    private function parseRaceId(string $raceId): array
    {
        $year        = substr($raceId, 0, 4);
        $venueCode   = substr($raceId, 4, 2);
        $raceNumber  = (int) substr($raceId, 10, 2);
        $month       = '01'; // race_idから月は取れないためデフォルト
        $day         = '01';

        $venue = self::VENUE_MAP[$venueCode] ?? "場所{$venueCode}";

        return [
            'date'        => "{$year}-{$month}-{$day}",
            'venue'       => $venue,
            'race_number' => $raceNumber,
        ];
    }

    /**
     * CSS セレクタで要素テキストを取得する（見つからない場合は空文字）
     */
    private function extractText(Crawler $crawler, string $selector): string
    {
        try {
            $node = $crawler->filter($selector);
            return $node->count() > 0 ? trim($node->first()->text()) : '';
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * class属性がちょうど指定のクラス名だけの td を探してテキストを返す
     * 例: extractExactClass($row, 'Txt_C') → class="Txt_C" のtdだけにマッチ
     */
    private function extractExactClass(Crawler $row, string $className): string
    {
        $result = '';
        $row->filter("td.{$className}")->each(function (Crawler $td) use ($className, &$result) {
            if (trim($td->attr('class') ?? '') === $className) {
                $result = trim($td->text());
            }
        });
        return $result;
    }

    private function toFloat(string $value): ?float
    {
        $value = trim(str_replace(',', '', $value));
        return is_numeric($value) ? (float) $value : null;
    }

    private function toInt(string $value): ?int
    {
        $value = trim(str_replace(',', '', $value));
        return is_numeric($value) ? (int) $value : null;
    }

    private function parsePayout(string $text): ?int
    {
        $text = preg_replace('/[^\d]/', '', $text);
        return $text !== '' ? (int) $text : null;
    }
}
