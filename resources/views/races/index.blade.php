<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>競馬レース一覧</title>
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
        }

        header h1 {
            font-size: 20px;
            font-weight: bold;
        }

        .container {
            max-width: 960px;
            margin: 24px auto;
            padding: 0 16px;
        }

        .count {
            font-size: 14px;
            color: #666;
            margin-bottom: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 4px rgba(0,0,0,0.1);
        }

        thead th {
            background: #1a3a5c;
            color: white;
            padding: 12px 14px;
            text-align: left;
            font-size: 13px;
            font-weight: normal;
        }

        tbody tr {
            border-bottom: 1px solid #eee;
            transition: background 0.1s;
            cursor: pointer;
        }

        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: #f0f6ff; }

        td {
            padding: 11px 14px;
            font-size: 14px;
        }

        .grade {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            background: #e8c84a;
            color: #333;
        }

        .course-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 12px;
        }

        .turf    { background: #d4f0d4; color: #2a6a2a; }
        .dirt    { background: #f0e0c8; color: #7a4a10; }
        .barrier { background: #e8e0f0; color: #4a2a7a; }

        .has-result { color: #1a7a3a; font-size: 12px; }
        .no-result  { color: #999;    font-size: 12px; }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 6px;
            margin-top: 24px;
        }

        .pagination a,
        .pagination span {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 14px;
            text-decoration: none;
            background: white;
            border: 1px solid #ddd;
            color: #333;
        }

        .pagination span.active {
            background: #1a3a5c;
            color: white;
            border-color: #1a3a5c;
        }

        .empty {
            text-align: center;
            padding: 60px;
            color: #999;
        }

        .empty p { margin-top: 8px; font-size: 13px; }
    </style>
</head>
<body>

<header>
    <h1>競馬レース一覧</h1>
</header>

<div class="container">

    @if ($races->isEmpty())
        <div class="empty">
            <div>データがありません</div>
            <p>php artisan scrape:result {race_id} でデータを取得してください</p>
        </div>
    @else
        <div class="count">{{ $races->total() }} 件</div>

        <table>
            <thead>
                <tr>
                    <th>日付</th>
                    <th>競馬場</th>
                    <th>R</th>
                    <th>レース名</th>
                    <th>コース</th>
                    <th>天気 / 馬場</th>
                    <th>結果</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($races as $race)
                <tr onclick="location.href='{{ route('races.show', $race) }}'">
                    <td>{{ $race->race_date?->format('Y/m/d') ?? '-' }}</td>
                    <td>{{ $race->venue }}</td>
                    <td>{{ $race->race_number }}R</td>
                    <td>
                        {{ $race->name }}
                        @if ($race->grade)
                            <span class="grade">{{ $race->grade }}</span>
                        @endif
                    </td>
                    <td>
                        @if ($race->course && $race->distance)
                            @php
                                $badgeClass = match($race->course) {
                                    '芝'   => 'turf',
                                    'ダート' => 'dirt',
                                    default => 'barrier',
                                };
                            @endphp
                            <span class="course-badge {{ $badgeClass }}">
                                {{ $race->course }}{{ number_format($race->distance) }}m
                            </span>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if ($race->weather || $race->track_condition)
                            {{ $race->weather ?? '-' }} / {{ $race->track_condition ?? '-' }}
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if ($race->results()->exists())
                            <span class="has-result">✓ 取得済み</span>
                        @else
                            <span class="no-result">未取得</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="pagination">
            {{ $races->links('pagination::simple-bootstrap-4') }}
        </div>
    @endif

</div>

</body>
</html>
