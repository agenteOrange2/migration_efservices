<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Violations Report</title>
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
    <h1>Violations Report</h1>
    <div class="muted">Generated at {{ $generatedAt }}</div>
    <div class="stats">
        <span><strong>Total Violations:</strong> {{ $stats['total_violations'] ?? 0 }}</span>
        <span><strong>Acknowledged:</strong> {{ $stats['acknowledged_count'] ?? 0 }}</span>
        <span><strong>Unacknowledged:</strong> {{ $stats['unacknowledged_count'] ?? 0 }}</span>
        <span><strong>Acknowledgment Rate:</strong> {{ $stats['acknowledgment_rate'] ?? 0 }}%</span>
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
                <th>Trip</th>
                <th>Violation Type</th>
                <th>Severity</th>
                <th>Date</th>
                <th>Hours Exceeded</th>
                <th>Acknowledged</th>
            </tr>
        </thead>
        <tbody>
            @forelse($violations as $violation)
                <tr>
                    <td>{{ $violation->driver?->full_name }}</td>
                    <td>{{ $violation->carrier?->name }}</td>
                    <td>{{ $violation->trip?->trip_number }}</td>
                    <td>{{ $violation->violation_type_name }}</td>
                    <td>{{ $violation->severity_name }}</td>
                    <td>{{ $violation->violation_date?->format('m/d/Y') }}</td>
                    <td>{{ $violation->hours_exceeded }}</td>
                    <td>{{ $violation->acknowledged ? 'Yes' : 'No' }}</td>
                </tr>
            @empty
                <tr><td colspan="8">No violations found.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
