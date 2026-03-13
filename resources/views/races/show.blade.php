<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $race->name }} - 競馬</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f5f5f5;
            color: #333;
        }

        header {
            background: #1a3a5c;
            color: white;
            padding: 16px 24px;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        header a {
            color: #aac8e8;
            text-decoration: none;
            font-size: 14px;
        }

        header a:hover { color: white; }

        header h1 { font-size: 20px; }

        .container {
            max-width: 1000px;
            margin: 24px auto;
            padding: 0 16px;
        }

        /* レース情報カード */
        .race-info {
            background: white;
            border-radius: 8px;
            padding: 20px 24px;
            margin-bottom: 24px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.1);
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: center;
        }

        .race-title {
            flex: 1;
            min-width: 200px;
        }

        .race-title h2 {
            font-size: 22px;
            font-weight: bold;
        }

        .race-title .sub {
            font-size: 14px;
            color: #666;
            margin-top: 4px;
        }

        .race-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .meta-item {
            font-size: 13px;
            color: #555;
        }

        .meta-item span {
            font-weight: bold;
            color: #333;
        }

        .grade {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            background: #e8c84a;
            color: #333;
            margin-left: 8px;
        }

        .course-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 13px;
        }

        .turf    { background: #d4f0d4; color: #2a6a2a; }
        .dirt    { background: #f0e0c8; color: #7a4a10; }
        .barrier { background: #e8e0f0; color: #4a2a7a; }

        /* セクション */
        .section {
            background: white;
            border-radius: 8px;
            margin-bottom: 24px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .section-header {
            background: #1a3a5c;
            color: white;
            padding: 10px 16px;
            font-size: 15px;
            font-weight: bold;
        }

        /* テーブル共通 */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            background: #eef2f7;
            color: #555;
            padding: 10px 12px;
            text-align: center;
            font-size: 12px;
            font-weight: normal;
            border-bottom: 1px solid #ddd;
        }

        tbody td {
            padding: 10px 12px;
            font-size: 14px;
            border-bottom: 1px solid #f0f0f0;
            text-align: center;
        }

        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: #f8faff; }

        .horse-name { text-align: left; font-weight: bold; }
        .horse-name a { color: #1a3a5c; text-decoration: none; }
        .horse-name a:hover { text-decoration: underline; }

        /* 枠番色 */
        .waku-1 { background: #fff; }
        .waku-2 { background: #000; color: white; }
        .waku-3 { background: #e00; color: white; }
        .waku-4 { background: #3a3; color: white; }
        .waku-5 { background: #fff; border: 2px solid #ddd; }
        .waku-6 { background: #28a; color: white; }
        .waku-7 { background: #f80; color: white; }
        .waku-8 { background: #e0b; color: white; }

        .waku-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }

        /* 着順 */
        .rank-1 { color: #c00; font-weight: bold; font-size: 16px; }
        .rank-2 { color: #555; font-weight: bold; }
        .rank-3 { color: #a06020; font-weight: bold; }

        /* 人気 */
        .pop-1 { color: #c00; font-weight: bold; }
        .pop-2 { color: #e06010; font-weight: bold; }
        .pop-3 { color: #a08020; font-weight: bold; }

        /* 上がり上位3頭 */
        .last3f-1 { color: #c00; font-weight: bold; }
        .last3f-2 { color: #e06010; font-weight: bold; }
        .last3f-3 { color: #a08020; font-weight: bold; }

        /* 配当テーブル */
        .payout-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 0;
        }

        .payout-row {
            display: flex;
            align-items: center;
            padding: 10px 16px;
            border-bottom: 1px solid #f0f0f0;
            gap: 12px;
        }

        .payout-row:last-child { border-bottom: none; }

        .bet-type {
            width: 56px;
            font-size: 12px;
            color: #666;
            flex-shrink: 0;
        }

        .combination {
            font-weight: bold;
            font-size: 15px;
            flex: 1;
        }

        .payout-amount {
            font-weight: bold;
            color: #c00;
            font-size: 15px;
            text-align: right;
            white-space: nowrap;
        }

        .payout-ninki {
            font-size: 11px;
            color: #999;
            text-align: right;
            white-space: nowrap;
        }

        .empty-section {
            padding: 24px;
            text-align: center;
            color: #999;
            font-size: 14px;
        }
    </style>
</head>
<body>

<header>
    <a href="/">← 一覧へ</a>
    <h1>
        {{ $race->name }}
        @if ($race->grade)
            <span class="grade">{{ $race->grade }}</span>
        @endif
    </h1>
</header>

<div class="container">

    {{-- レース情報 --}}
    <div class="race-info">
        <div class="race-title">
            <h2>{{ $race->venue }} {{ $race->race_number }}R</h2>
            <div class="sub">{{ $race->race_date?->format('Y年m月d日') ?? '-' }}</div>
        </div>
        <div class="race-meta">
            @if ($race->course && $race->distance)
                @php
                    $badgeClass = match($race->course) {
                        '芝'   => 'turf',
                        'ダート' => 'dirt',
                        default => 'barrier',
                    };
                @endphp
                <div class="meta-item">
                    <span class="course-badge {{ $badgeClass }}">{{ $race->course }}{{ number_format($race->distance) }}m</span>
                </div>
            @endif
            @if ($race->direction)
                <div class="meta-item">回り: <span>{{ $race->direction }}</span></div>
            @endif
            @if ($race->weather)
                <div class="meta-item">天気: <span>{{ $race->weather }}</span></div>
            @endif
            @if ($race->track_condition)
                <div class="meta-item">馬場: <span>{{ $race->track_condition }}</span></div>
            @endif
        </div>
    </div>

    {{-- 出馬表 --}}
    <div class="section">
        <div class="section-header">出馬表</div>
        @if ($race->entries->isEmpty())
            <div class="empty-section">出馬表データなし</div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>枠</th>
                        <th>馬番</th>
                        <th style="text-align:left">馬名</th>
                        <th>性齢</th>
                        <th>斤量</th>
                        <th>騎手</th>
                        <th>調教師</th>
                        <th>馬体重</th>
                        <th>単勝</th>
                        <th>人気</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($race->entries as $entry)
                    <tr>
                        <td>
                            <span class="waku-badge waku-{{ $entry->gate_number }}">{{ $entry->gate_number }}</span>
                        </td>
                        <td>{{ $entry->horse_number }}</td>
                        <td class="horse-name">
                            <a href="{{ route('horses.show', ['name' => $entry->horse_name]) }}">{{ $entry->horse_name }}</a>
                        </td>
                        <td>{{ $entry->sex_age ?? '-' }}</td>
                        <td>{{ $entry->burden_weight ?? '-' }}</td>
                        <td>{{ $entry->jockey ?? '-' }}</td>
                        <td>{{ $entry->trainer ?? '-' }}</td>
                        <td>{{ $entry->horse_weight ?? '-' }}</td>
                        <td>{{ $entry->win_odds ? $entry->win_odds.'倍' : '-' }}</td>
                        <td class="{{ $entry->popularity <= 3 ? 'pop-'.$entry->popularity : '' }}">
                            {{ $entry->popularity ? $entry->popularity.'番人気' : '-' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- レース結果 --}}
    <div class="section">
        <div class="section-header">レース結果</div>
        @if ($race->results->isEmpty())
            <div class="empty-section">結果データなし</div>
        @else
            @php
                $top3last3f = $race->results
                    ->whereNotNull('last_3f')
                    ->sortBy('last_3f')
                    ->take(3)
                    ->pluck('last_3f')
                    ->values();
            @endphp
            <table>
                <thead>
                    <tr>
                        <th>着順</th>
                        <th>枠</th>
                        <th>馬番</th>
                        <th style="text-align:left">馬名</th>
                        <th>性齢</th>
                        <th>斤量</th>
                        <th>騎手</th>
                        <th>タイム</th>
                        <th>着差</th>
                        <th>上がり</th>
                        <th>通過順</th>
                        <th>人気</th>
                        <th>オッズ</th>
                        <th>馬体重</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($race->results as $result)
                    <tr>
                        <td class="{{ in_array($result->rank, ['1','2','3']) ? 'rank-'.$result->rank : '' }}">
                            {{ $result->rank }}
                        </td>
                        <td>
                            <span class="waku-badge waku-{{ $result->gate_number }}">{{ $result->gate_number }}</span>
                        </td>
                        <td>{{ $result->horse_number }}</td>
                        <td class="horse-name">
                            <a href="{{ route('horses.show', ['name' => $result->horse_name]) }}">{{ $result->horse_name }}</a>
                        </td>
                        <td>{{ $result->sex_age ?? '-' }}</td>
                        <td>{{ $result->burden_weight ?? '-' }}</td>
                        <td>{{ $result->jockey ?? '-' }}</td>
                        <td>{{ $result->finish_time ?? '-' }}</td>
                        <td>{{ $result->margin ?? '-' }}</td>
                        @php
                            $last3fRank = $result->last_3f ? $top3last3f->search($result->last_3f) : false;
                            $last3fClass = $last3fRank !== false ? 'last3f-'.($last3fRank + 1) : '';
                        @endphp
                        <td class="{{ $last3fClass }}">{{ $result->last_3f ?? '-' }}</td>
                        <td style="font-size:12px">{{ $result->corner_order ?? '-' }}</td>
                        <td class="{{ $result->popularity <= 3 ? 'pop-'.$result->popularity : '' }}">
                            {{ $result->popularity ? $result->popularity.'番人気' : '-' }}
                        </td>
                        <td>{{ $result->win_odds ? $result->win_odds.'倍' : '-' }}</td>
                        <td>{{ $result->horse_weight ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- 払戻金 --}}
    <div class="section">
        <div class="section-header">払戻金</div>
        @if ($race->payouts->isEmpty())
            <div class="empty-section">払戻データなし</div>
        @else
            <div class="payout-grid">
                @foreach ($race->payouts as $payout)
                <div class="payout-row">
                    <div class="bet-type">{{ $payout->bet_type }}</div>
                    <div class="combination">{{ $payout->combination }}</div>
                    <div>
                        <div class="payout-amount">{{ number_format($payout->payout) }}円</div>
                        @if ($payout->popularity)
                            <div class="payout-ninki">{{ $payout->popularity }}番人気</div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>

</div>

</body>
</html>
