<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>HOS Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1e293b; font-size: 11px; }
        h1 { margin: 0 0 8px; font-size: 20px; }
        .muted { color: #64748b; margin-bottom: 10px; }
        .stats span { display: inline-block; margin-right: 16px; margin-bottom: 6px; }
        .filters { margin: 12px 0 18px; padding: 10px 12px; background: #f8fafc; border: 1px solid #e2e8f0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e2e8f0; padding: 7px; text-align: left; vertical-align: top; }
        th { background: #eef2ff; color: #312e81; }
    </style>
</head>
<body>
    <h1>HOS Report</h1>
    <div class="muted">Generated at {{ $generatedAt }} | {{ $dateRangeLabel }}</div>
    <div class="stats">
        <span><strong>Total Logs:</strong> {{ $stats['total_logs'] ?? 0 }}</span>
        <span><strong>Logs with Violations:</strong> {{ $stats['logs_with_violations'] ?? 0 }}</span>
        <span><strong>Compliance:</strong> {{ $stats['compliance_percentage'] ?? 0 }}%</span>
        <span><strong>Avg Driving:</strong> {{ $stats['average_driving_hours'] ?? 0 }} hrs</span>
        <span><strong>Avg On Duty:</strong> {{ $stats['average_on_duty_hours'] ?? 0 }} hrs</span>
    </div>
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
                <th>Driver</th>
                <th>Carrier</th>
                <th>Total Days</th>
                <th>Driving Hours</th>
                <th>On Duty Hours</th>
                <th>Violation Days</th>
                <th>First Log</th>
                <th>Last Log</th>
            </tr>
        </thead>
        <tbody>
            @forelse($driverSummaries as $summary)
                <tr>
                    <td>{{ $summary->driver?->full_name }}</td>
                    <td>{{ $summary->carrier?->name }}</td>
                    <td>{{ $summary->total_days }}</td>
                    <td>{{ round(($summary->total_driving_minutes ?? 0) / 60, 2) }}</td>
                    <td>{{ round(($summary->total_on_duty_minutes ?? 0) / 60, 2) }}</td>
                    <td>{{ $summary->days_with_violations }}</td>
                    <td>{{ $summary->first_log_date ? \Carbon\Carbon::parse($summary->first_log_date)->format('m/d/Y') : '' }}</td>
                    <td>{{ $summary->last_log_date ? \Carbon\Carbon::parse($summary->last_log_date)->format('m/d/Y') : '' }}</td>
                </tr>
            @empty
                <tr><td colspan="8">No HOS summaries found.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
