<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $name }} - 過去成績</title>
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
            max-width: 1100px;
            margin: 24px auto;
            padding: 0 16px;
        }

        .summary {
            background: white;
            border-radius: 8px;
            padding: 20px 24px;
            margin-bottom: 16px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 32px;
            flex-wrap: wrap;
        }

        .summary h2 { font-size: 24px; font-weight: bold; flex: 1; min-width: 160px; }

        .stats { display: flex; gap: 24px; flex-wrap: wrap; }

        .stat { text-align: center; }
        .stat .num { font-size: 22px; font-weight: bold; color: #1a3a5c; }
        .stat .label { font-size: 11px; color: #999; margin-top: 2px; }

        .rate { font-size: 13px; color: #555; align-self: flex-end; padding-bottom: 4px; }

        /* 全成績取得バナー */
        .fetch-banner {
            background: #fffbe6;
            border: 1px solid #f0c040;
            border-radius: 8px;
            padding: 14px 20px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .fetch-banner p { font-size: 14px; color: #7a5a00; flex: 1; }

        .fetch-banner code {
            background: #f5f0d0;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 13px;
            color: #333;
        }

        .section {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .section-header {
            background: #1a3a5c;
            color: white;
            padding: 10px 16px;
            font-size: 15px;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .badge {
            font-size: 11px;
            background: #4a8aac;
            padding: 2px 8px;
            border-radius: 10px;
        }

        table { width: 100%; border-collapse: collapse; }

        thead th {
            background: #eef2f7;
            color: #555;
            padding: 9px 10px;
            text-align: center;
            font-size: 12px;
            font-weight: normal;
            border-bottom: 1px solid #ddd;
            white-space: nowrap;
        }

        tbody td {
            padding: 9px 10px;
            font-size: 13px;
            border-bottom: 1px solid #f0f0f0;
            text-align: center;
            white-space: nowrap;
        }

        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: #f8faff; }

        .race-link { text-align: left; min-width: 140px; white-space: normal; }
        .race-link a { color: #1a3a5c; text-decoration: none; }
        .race-link a:hover { text-decoration: underline; }

        .rank-1 { color: #c00; font-weight: bold; font-size: 15px; }
        .rank-2 { color: #555; font-weight: bold; }
        .rank-3 { color: #a06020; font-weight: bold; }

        .pop-1 { color: #c00; font-weight: bold; }
        .pop-2 { color: #e06010; font-weight: bold; }
        .pop-3 { color: #a08020; font-weight: bold; }

        .course-turf  { color: #2a6a2a; font-weight: bold; }
        .course-dirt  { color: #7a4a10; font-weight: bold; }
    </style>
</head>
<body>

<header>
    <a href="javascript:history.back()">← 戻る</a>
    <h1>{{ $name }}</h1>
</header>

<div class="container">

    {{-- サマリー --}}
    <div class="summary">
        <h2>{{ $name }}</h2>
        <div class="stats">
            <div class="stat">
                <div class="num">{{ $total }}</div>
                <div class="label">出走</div>
            </div>
            <div class="stat">
                <div class="num">{{ $wins }}</div>
                <div class="label">1着</div>
            </div>
            <div class="stat">
                <div class="num">{{ $top2 }}</div>
                <div class="label">2着内</div>
            </div>
            <div class="stat">
                <div class="num">{{ $top3 }}</div>
                <div class="label">3着内</div>
            </div>
        </div>
        <div class="rate">
            勝率 {{ $total > 0 ? number_format($wins / $total * 100, 1) : 0 }}%
            連対率 {{ $total > 0 ? number_format($top2 / $total * 100, 1) : 0 }}%
            複勝率 {{ $total > 0 ? number_format($top3 / $total * 100, 1) : 0 }}%
        </div>
    </div>

    {{-- 全成績取得案内（未取得の場合） --}}
    @if (!$useHistory)
    <div class="fetch-banner">
        <p>表示しているのは保存済みレースのみです。netkeiba.comから全成績を取得できます：</p>
        <code>php artisan scrape:horse "{{ $name }}"</code>
    </div>
    @endif

    {{-- 成績テーブル --}}
    <div class="section">
        <div class="section-header">
            過去成績
            <span class="badge">{{ $useHistory ? 'netkeiba 全成績' : '保存済みレースのみ' }}</span>
        </div>
        <div style="overflow-x:auto">
        <table>
            <thead>
                <tr>
                    <th>日付</th>
                    <th style="text-align:left">レース名</th>
                    <th>開催</th>
                    <th>天気</th>
                    <th>頭数</th>
                    <th>枠</th>
                    <th>馬番</th>
                    <th>着順</th>
                    <th>人気</th>
                    <th>騎手</th>
                    <th>斤量</th>
                    <th>コース</th>
                    <th>馬場</th>
                    <th>タイム</th>
                    <th>着差</th>
                    <th>通過</th>
                    <th>上がり</th>
                    <th>馬体重</th>
                    <th>オッズ</th>
                    @if ($useHistory)
                    <th style="text-align:left">勝ち馬</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach ($data as $r)
                @php
                    $rankVal = $r->rank ?? '';
                    $rankClass = in_array($rankVal, ['1','2','3']) ? 'rank-'.$rankVal : '';
                    $popVal = $r->popularity ?? 0;
                    $popClass = $popVal >= 1 && $popVal <= 3 ? 'pop-'.$popVal : '';
                    $courseStr = $r->course ?? '';
                    $courseClass = str_starts_with($courseStr, '芝') ? 'course-turf' : (str_starts_with($courseStr, 'ダ') ? 'course-dirt' : '');
                @endphp
                <tr>
                    <td>{{ $r->race_date instanceof \Carbon\Carbon ? $r->race_date->format('Y/m/d') : \Carbon\Carbon::parse($r->race_date)->format('Y/m/d') }}</td>
                    <td class="race-link">
                        @if ($useHistory)
                            {{ $r->race_name ?? '-' }}
                        @else
                            <a href="{{ route('races.show', $r->race_link) }}">{{ $r->race_name ?? '-' }}</a>
                        @endif
                    </td>
                    <td>{{ $r->venue ?? '-' }}</td>
                    <td>{{ $r->weather ?? '-' }}</td>
                    <td>{{ $r->horses_count ?? '-' }}</td>
                    <td>{{ $r->gate_number ?? '-' }}</td>
                    <td>{{ $r->horse_number ?? '-' }}</td>
                    <td class="{{ $rankClass }}">{{ $rankVal ?: '-' }}</td>
                    <td class="{{ $popClass }}">{{ $popVal ? $popVal.'人気' : '-' }}</td>
                    <td>{{ $r->jockey ?? '-' }}</td>
                    <td>{{ $r->burden_weight ?? '-' }}</td>
                    <td class="{{ $courseClass }}">{{ $courseStr ?: '-' }}</td>
                    <td>{{ $r->track_condition ?? '-' }}</td>
                    <td>{{ $r->finish_time ?? '-' }}</td>
                    <td>{{ $r->margin ?? '-' }}</td>
                    <td>{{ $r->corner_order ?? '-' }}</td>
                    <td>{{ $r->last_3f ?? '-' }}</td>
                    <td>{{ $r->horse_weight ?? '-' }}</td>
                    <td>{{ $r->win_odds ? $r->win_odds.'倍' : '-' }}</td>
                    @if ($useHistory)
                    <td class="race-link">{{ $r->winner ?? '-' }}</td>
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>

</div>
</body>
</html>
