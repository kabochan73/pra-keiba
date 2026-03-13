<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>人気別分析</title>
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

        header a { color: #aac8e8; text-decoration: none; font-size: 14px; }
        header a:hover { color: white; }
        header h1 { font-size: 20px; }

        .container {
            max-width: 1000px;
            margin: 24px auto;
            padding: 0 16px;
        }

        /* フィルター */
        .filter-card {
            background: white;
            border-radius: 8px;
            padding: 16px 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .filter-card label { font-size: 14px; color: #555; }

        .filter-card select {
            padding: 7px 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
            color: #333;
            min-width: 200px;
        }

        .filter-card button {
            padding: 7px 20px;
            background: #1a3a5c;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
        }

        .filter-card button:hover { background: #2a5a8c; }

        /* 対象レース一覧 */
        .race-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 8px;
        }

        .race-tag {
            background: #eef2f7;
            border-radius: 4px;
            padding: 3px 10px;
            font-size: 12px;
            color: #555;
        }

        /* 集計カード */
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }

        .card {
            background: white;
            border-radius: 8px;
            padding: 16px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.1);
            text-align: center;
        }

        .card .num { font-size: 24px; font-weight: bold; color: #1a3a5c; }
        .card .label { font-size: 12px; color: #999; margin-top: 4px; }

        /* 分析テーブル */
        .section {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 20px;
        }

        .section-header {
            background: #1a3a5c;
            color: white;
            padding: 10px 16px;
            font-size: 15px;
            font-weight: bold;
        }

        table { width: 100%; border-collapse: collapse; }

        thead th {
            background: #eef2f7;
            color: #555;
            padding: 10px 14px;
            text-align: center;
            font-size: 12px;
            font-weight: normal;
            border-bottom: 1px solid #ddd;
        }

        tbody td {
            padding: 10px 14px;
            font-size: 14px;
            border-bottom: 1px solid #f0f0f0;
            text-align: center;
        }

        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: #f8faff; }

        /* ROIバー */
        .roi-cell { position: relative; }

        .roi-bar {
            display: inline-block;
            height: 6px;
            border-radius: 3px;
            margin-top: 4px;
            max-width: 80px;
        }

        .roi-good { background: #2a8a4a; }
        .roi-bad  { background: #e04040; }

        .roi-value { font-weight: bold; }
        .roi-over  { color: #2a8a4a; }
        .roi-under { color: #e04040; }

        /* 人気バッジ */
        .pop-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            font-size: 13px;
            font-weight: bold;
        }

        .pop-1 { background: #c00; color: white; }
        .pop-2 { background: #e06010; color: white; }
        .pop-3 { background: #a08020; color: white; }
        .pop-other { background: #ddd; color: #555; }

        .rate-bar-wrap {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .rate-bar {
            height: 8px;
            background: #4a8aac;
            border-radius: 3px;
            min-width: 2px;
        }

        .no-data {
            text-align: center;
            padding: 48px;
            color: #999;
        }
    </style>
</head>
<body>

<header>
    <a href="/">← 一覧へ</a>
    <h1>人気別 勝率・回収率分析</h1>
</header>

<div class="container">

    {{-- フィルター --}}
    <form method="GET" action="{{ route('analysis.popularity') }}">
        <div class="filter-card">
            <label>レース名で絞り込み：</label>
            <select name="race_name">
                <option value="">すべて</option>
                @foreach ($raceNames as $name)
                    <option value="{{ $name }}" {{ $selectedName === $name ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
            <button type="submit">集計</button>
        </div>

        @if (!empty($races) && $races->isNotEmpty())
        <div style="margin-bottom:20px; padding: 0 4px;">
            <div style="font-size:13px; color:#666; margin-bottom:6px;">対象レース（{{ $races->count() }}件）：</div>
            <div class="race-tags">
                @foreach ($races as $race)
                    <span class="race-tag">{{ $race->race_date?->format('Y/m/d') }} {{ $race->name }}</span>
                @endforeach
            </div>
        </div>
        @endif
    </form>

    @if (empty($stats) || count($stats) === 0)
        <div class="no-data">データがありません</div>
    @else
        @php
            $totalRaces = $races->count();
            $total1st   = collect($stats)->sum('wins');
            $avgWinRoi  = collect($stats)->whereNotNull('win_roi')->avg('win_roi');
            $avgPlaceRoi = collect($stats)->whereNotNull('place_roi')->avg('place_roi');
        @endphp

        {{-- サマリーカード --}}
        <div class="summary-cards">
            <div class="card">
                <div class="num">{{ $totalRaces }}</div>
                <div class="label">対象レース数</div>
            </div>
            <div class="card">
                <div class="num">{{ collect($stats)->sum('count') }}</div>
                <div class="label">総出走頭数</div>
            </div>
            <div class="card">
                <div class="num" style="color: {{ $avgWinRoi >= 100 ? '#2a8a4a' : '#e04040' }}">
                    {{ number_format($avgWinRoi, 1) }}%
                </div>
                <div class="label">平均単勝回収率</div>
            </div>
            <div class="card">
                <div class="num" style="color: {{ $avgPlaceRoi >= 100 ? '#2a8a4a' : '#e04040' }}">
                    {{ number_format($avgPlaceRoi, 1) }}%
                </div>
                <div class="label">平均複勝回収率</div>
            </div>
        </div>

        {{-- 人気別テーブル --}}
        <div class="section">
            <div class="section-header">人気別 勝率・回収率</div>
            <table>
                <thead>
                    <tr>
                        <th>人気</th>
                        <th>出走数</th>
                        <th>1着</th>
                        <th>勝率</th>
                        <th>連対率</th>
                        <th>複勝率</th>
                        <th>単勝回収率</th>
                        <th>複勝回収率</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($stats as $s)
                    <tr>
                        <td>
                            <span class="pop-badge {{ $s['popularity'] <= 3 ? 'pop-'.$s['popularity'] : 'pop-other' }}">
                                {{ $s['popularity'] }}
                            </span>
                        </td>
                        <td>{{ $s['count'] }}</td>
                        <td>{{ $s['wins'] }}</td>
                        <td>
                            <div class="rate-bar-wrap">
                                <div class="rate-bar" style="width: {{ min($s['win_rate'], 100) }}px"></div>
                                <span>{{ $s['win_rate'] }}%</span>
                            </div>
                        </td>
                        <td>
                            <div class="rate-bar-wrap">
                                <div class="rate-bar" style="width: {{ min($s['rentai_rate'], 100) }}px"></div>
                                <span>{{ $s['rentai_rate'] }}%</span>
                            </div>
                        </td>
                        <td>
                            <div class="rate-bar-wrap">
                                <div class="rate-bar" style="width: {{ min($s['fukusho_rate'], 100) }}px"></div>
                                <span>{{ $s['fukusho_rate'] }}%</span>
                            </div>
                        </td>
                        <td class="roi-cell">
                            <span class="roi-value {{ $s['win_roi'] >= 100 ? 'roi-over' : 'roi-under' }}">
                                {{ $s['win_roi'] }}%
                            </span>
                            <div class="roi-bar {{ $s['win_roi'] >= 100 ? 'roi-good' : 'roi-bad' }}"
                                 style="width: {{ min($s['win_roi'] / 2, 80) }}px"></div>
                        </td>
                        <td class="roi-cell">
                            <span class="roi-value {{ $s['place_roi'] >= 100 ? 'roi-over' : 'roi-under' }}">
                                {{ $s['place_roi'] }}%
                            </span>
                            <div class="roi-bar {{ $s['place_roi'] >= 100 ? 'roi-good' : 'roi-bad' }}"
                                 style="width: {{ min($s['place_roi'] / 2, 80) }}px"></div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

</div>
</body>
</html>
