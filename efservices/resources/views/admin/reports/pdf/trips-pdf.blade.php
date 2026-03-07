<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Trip Report</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: "Segoe UI", Arial, sans-serif; font-size: 12px; color: #2c3e50; margin: 40px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { font-size: 22px; margin: 0; color: #1d3557; }
        .header p { font-size: 14px; color: #6c757d; }
        .summary { display: flex; justify-content: space-between; margin-bottom: 30px; flex-wrap: wrap; gap: 15px; }
        .card { flex: 1; min-width: 150px; background-color: #f1f3f5; border: 1px solid #ced4da; border-radius: 6px; padding: 15px; }
        .card p { margin: 5px 0; font-size: 13px; }
        .card strong { display: block; color: #343a40; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #dee2e6; padding: 8px 10px; font-size: 11px; }
        th { background-color: #e9ecef; text-align: left; color: #212529; }
        tr:nth-child(even) { background-color: #f8f9fa; }
        .status-completed { background-color: #28a745; color: #fff; padding: 3px 8px; border-radius: 10px; font-size: 10px; }
        .status-in_progress { background-color: #007bff; color: #fff; padding: 3px 8px; border-radius: 10px; font-size: 10px; }
        .status-cancelled { background-color: #6c757d; color: #fff; padding: 3px 8px; border-radius: 10px; font-size: 10px; }
        .status-pending { background-color: #ffc107; color: #000; padding: 3px 8px; border-radius: 10px; font-size: 10px; }
        .footer { text-align: center; margin-top: 40px; font-size: 10px; color: #6c757d; border-top: 1px solid #dee2e6; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Trip Report</h1>
        <p>EF Services Management System</p>
    </div>

    <div class="summary">
        <div class="card">
            <p><strong>Report Date:</strong> {{ $date }}</p>
            @if(count($filters) > 0)
            <p><strong>Filters:</strong> {{ implode(', ', $filters) }}</p>
            @endif
        </div>
        <div class="card">
            <p><strong>Total Trips:</strong> {{ $stats['total_trips'] }}</p>
            <p><strong>Completed:</strong> {{ $stats['completed_trips'] }}</p>
        </div>
        <div class="card">
            <p><strong>In Progress:</strong> {{ $stats['in_progress_trips'] }}</p>
            <p><strong>Cancelled:</strong> {{ $stats['cancelled_trips'] }}</p>
        </div>
        <div class="card">
            <p><strong>With Violations:</strong> {{ $stats['trips_with_violations'] }}</p>
            <p><strong>Completion Rate:</strong> {{ $stats['completion_rate'] }}%</p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Trip #</th>
                <th>Driver</th>
                <th>Carrier</th>
                <th>Vehicle</th>
                <th>Origin</th>
                <th>Destination</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($trips as $trip)
            <tr>
                <td>{{ $trip->trip_number }}</td>
                <td>{{ $trip->driver?->full_name ?? 'N/A' }}</td>
                <td>{{ $trip->carrier?->name ?? 'N/A' }}</td>
                <td>{{ $trip->vehicle?->company_unit_number ?? 'N/A' }}</td>
                <td>{{ \Illuminate\Support\Str::limit($trip->origin_address, 20) ?? 'N/A' }}</td>
                <td>{{ \Illuminate\Support\Str::limit($trip->destination_address, 20) ?? 'N/A' }}</td>
                <td><span class="status-{{ $trip->status }}">{{ ucfirst(str_replace('_', ' ', $trip->status)) }}</span></td>
                <td>{{ $trip->scheduled_start_date?->format('m/d/Y') ?? 'N/A' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Generated: {{ $generatedAt }} | EF Services &copy; {{ date('Y') }}</p>
    </div>
</body>
</html>
