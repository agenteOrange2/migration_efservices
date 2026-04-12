<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Trip Report - {{ $carrier->name ?? 'N/A' }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #1e40af; padding-bottom: 15px; }
        .header h1 { margin: 0; color: #1e40af; font-size: 24px; }
        .header p { margin: 5px 0 0; color: #666; }
        .carrier-info { background: #f8fafc; padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .carrier-info strong { color: #1e40af; }
        .stats-grid { display: table; width: 100%; margin-bottom: 20px; }
        .stat-box { display: table-cell; width: 20%; text-align: center; padding: 10px; background: #f1f5f9; border: 1px solid #e2e8f0; }
        .stat-box .value { font-size: 20px; font-weight: bold; color: #1e40af; }
        .stat-box .label { font-size: 10px; color: #64748b; text-transform: uppercase; }
        .stat-box.success .value { color: #16a34a; }
        .stat-box.warning .value { color: #ca8a04; }
        .stat-box.danger .value { color: #dc2626; }
        .filters { background: #fef3c7; padding: 8px 12px; margin-bottom: 15px; border-radius: 4px; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #1e40af; color: white; padding: 10px 8px; text-align: left; font-size: 11px; }
        td { padding: 8px; border-bottom: 1px solid #e2e8f0; font-size: 11px; }
        tr:nth-child(even) { background: #f8fafc; }
        .status { padding: 3px 8px; border-radius: 3px; font-size: 10px; font-weight: bold; }
        .status-completed { background: #dcfce7; color: #166534; }
        .status-in_progress { background: #fef3c7; color: #92400e; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        .status-scheduled { background: #dbeafe; color: #1e40af; }
        .footer { margin-top: 20px; text-align: center; font-size: 10px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 10px; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Trip Report</h1>
        <p>Generated on {{ now()->format('F d, Y \a\t h:i A') }}</p>
    </div>

    <div class="carrier-info">
        <strong>Carrier:</strong> {{ $carrier->name ?? 'N/A' }} |
        <strong>DOT:</strong> {{ $carrier->dot_number ?? 'N/A' }} |
        <strong>MC:</strong> {{ $carrier->mc_number ?? 'N/A' }}
    </div>

    @if(!empty($appliedFilters))
        <div class="filters">
            <strong>Applied Filters:</strong> {{ $appliedFilters }}
        </div>
    @endif

    <div class="stats-grid">
        <div class="stat-box">
            <div class="value">{{ number_format($stats['total_trips']) }}</div>
            <div class="label">Total Trips</div>
        </div>
        <div class="stat-box success">
            <div class="value">{{ number_format($stats['completed_trips']) }}</div>
            <div class="label">Completed</div>
        </div>
        <div class="stat-box warning">
            <div class="value">{{ number_format($stats['in_progress_trips']) }}</div>
            <div class="label">In Progress</div>
        </div>
        <div class="stat-box danger">
            <div class="value">{{ number_format($stats['cancelled_trips']) }}</div>
            <div class="label">Cancelled</div>
        </div>
        <div class="stat-box">
            <div class="value">{{ number_format($stats['trips_with_violations']) }}</div>
            <div class="label">With Violations</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Trip #</th>
                <th>Driver</th>
                <th>Vehicle</th>
                <th>Origin</th>
                <th>Destination</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($trips as $trip)
                <tr>
                    <td><strong>{{ $trip->trip_number }}</strong></td>
                    <td>{{ $trip->driver?->full_name ?? 'N/A' }}</td>
                    <td>{{ $trip->vehicle?->company_unit_number ?? $trip->vehicle?->vin ?? 'N/A' }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($trip->origin_address, 30) ?? 'N/A' }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($trip->destination_address, 30) ?? 'N/A' }}</td>
                    <td><span class="status status-{{ $trip->status }}">{{ $trip->status_name }}</span></td>
                    <td>{{ $trip->scheduled_start_date?->format('M d, Y') ?? 'N/A' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 20px;">No trips found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>{{ $carrier->name ?? 'N/A' }} - Trip Report | Page 1 | Confidential</p>
    </div>
</body>
</html>
