<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Trip Report</title>
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
    <h1>Trip Report</h1>
    <div class="muted">Generated at {{ $generatedAt }}</div>
    <div class="stats">
        <span><strong>Total Trips:</strong> {{ $stats['total_trips'] ?? 0 }}</span>
        <span><strong>Completed:</strong> {{ $stats['completed_trips'] ?? 0 }}</span>
        <span><strong>In Progress:</strong> {{ $stats['in_progress_trips'] ?? 0 }}</span>
        <span><strong>With Violations:</strong> {{ $stats['trips_with_violations'] ?? 0 }}</span>
        <span><strong>Completion Rate:</strong> {{ $stats['completion_rate'] ?? 0 }}%</span>
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
                <th>Trip #</th>
                <th>Carrier</th>
                <th>Driver</th>
                <th>Vehicle</th>
                <th>Origin</th>
                <th>Destination</th>
                <th>Scheduled Start</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($trips as $trip)
                <tr>
                    <td>{{ $trip->trip_number }}</td>
                    <td>{{ $trip->carrier?->name }}</td>
                    <td>{{ $trip->driver?->full_name }}</td>
                    <td>{{ trim(($trip->vehicle?->company_unit_number ? 'Unit #' . $trip->vehicle->company_unit_number . ' - ' : '') . trim(($trip->vehicle?->make ?? '') . ' ' . ($trip->vehicle?->model ?? ''))) }}</td>
                    <td>{{ $trip->origin_address }}</td>
                    <td>{{ $trip->destination_address ?: $trip->destination }}</td>
                    <td>{{ $trip->scheduled_start_date?->format('m/d/Y h:i A') }}</td>
                    <td>{{ str($trip->status)->replace('_', ' ')->title() }}</td>
                </tr>
            @empty
                <tr><td colspan="8">No trips found.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
