<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1e293b; font-size: 11px; }
        h1 { font-size: 20px; margin: 0 0 8px; }
        p { margin: 0; }
        .muted { color: #64748b; }
        .stats { margin: 16px 0; }
        .stats span { display: inline-block; margin-right: 16px; margin-bottom: 6px; }
        .filters { margin: 12px 0 18px; padding: 10px 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e2e8f0; padding: 8px; text-align: left; vertical-align: top; }
        th { background: #eef2ff; color: #312e81; font-weight: 700; }
        tr:nth-child(even) td { background: #f8fafc; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p class="muted">Generated at {{ $generatedAt }}</p>

    @if(!empty($stats))
        <div class="stats">
            @foreach($stats as $label => $value)
                <span><strong>{{ $label }}:</strong> {{ $value }}</span>
            @endforeach
        </div>
    @endif

    @if(!empty($filters))
        <div class="filters">
            @foreach($filters as $filter)
                <div>{{ $filter }}</div>
            @endforeach
        </div>
    @endif

    <table>
        <thead>
            <tr>
                @foreach($columns as $column)
                    <th>{{ $column }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}">No data available.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
