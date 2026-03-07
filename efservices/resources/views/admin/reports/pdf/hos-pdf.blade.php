<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>HOS Report - Driver Summary</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: "Segoe UI", Arial, sans-serif; font-size: 12px; color: #2c3e50; margin: 40px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { font-size: 22px; margin: 0; color: #1d3557; }
        .header p { font-size: 14px; color: #6c757d; }
        .summary { display: flex; justify-content: space-between; margin-bottom: 30px; flex-wrap: wrap; gap: 15px; }
        .card { flex: 1; min-width: 120px; background-color: #f1f3f5; border: 1px solid #ced4da; border-radius: 6px; padding: 12px; text-align: center; }
        .card .value { font-size: 20px; font-weight: bold; color: #1d3557; }
        .card .label { font-size: 10px; color: #6c757d; text-transform: uppercase; margin-top: 4px; }
        .card.success .value { color: #28a745; }
        .card.danger .value { color: #dc3545; }
        .card.info .value { color: #0284c7; }
        .card.warning .value { color: #ca8a04; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #dee2e6; padding: 8px 10px; font-size: 11px; }
        th { background-color: #e9ecef; text-align: left; color: #212529; }
        tr:nth-child(even) { background-color: #f8f9fa; }
        .text-center { text-align: center; }
        .badge-clean { background-color: #28a745; color: #fff; padding: 3px 8px; border-radius: 10px; font-size: 10px; }
        .badge-violation { background-color: #dc3545; color: #fff; padding: 3px 8px; border-radius: 10px; font-size: 10px; }
        .footer { text-align: center; margin-top: 40px; font-size: 10px; color: #6c757d; border-top: 1px solid #dee2e6; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Hours of Service (HOS) &mdash; Driver Summary</h1>
        <p>EFCTS Management System &mdash; {{ $dateRangeLabel ?? 'All Time' }}</p>
    </div>

    <div class="summary">
        <div class="card">
            <div class="value">{{ $driverSummaries->count() }}</div>
            <div class="label">Drivers</div>
        </div>
        <div class="card success">
            <div class="value">{{ $stats['compliance_percentage'] }}%</div>
            <div class="label">Compliance</div>
        </div>
        <div class="card danger">
            <div class="value">{{ $stats['logs_with_violations'] }}</div>
            <div class="label">Days w/ Violations</div>
        </div>
        <div class="card info">
            <div class="value">{{ number_format($stats['average_driving_hours'], 1) }}h</div>
            <div class="label">Avg Driving/Day</div>
        </div>
        <div class="card warning">
            <div class="value">{{ $stats['total_logs'] }}</div>
            <div class="label">Total Log Days</div>
        </div>
    </div>

    @if(count($filters) > 0)
        <p style="font-size: 11px; color: #6c757d; margin-bottom: 10px;"><strong>Filters:</strong> {{ implode(', ', $filters) }}</p>
    @endif

    <table>
        <thead>
            <tr>
                <th>Driver</th>
                <th>Carrier</th>
                <th class="text-center">Days</th>
                <th>Total Driving</th>
                <th>Avg Driving/Day</th>
                <th>Total On-Duty</th>
                <th>Total Off-Duty</th>
                <th class="text-center">Violations</th>
                <th>Period</th>
            </tr>
        </thead>
        <tbody>
            @forelse($driverSummaries as $summary)
                @php
                    $totalDrivingH = floor($summary->total_driving_minutes / 60);
                    $totalDrivingM = $summary->total_driving_minutes % 60;
                    $avgDrivingH = floor($summary->avg_driving_minutes / 60);
                    $avgDrivingM = round($summary->avg_driving_minutes % 60);
                    $totalOnDutyH = floor($summary->total_on_duty_minutes / 60);
                    $totalOnDutyM = $summary->total_on_duty_minutes % 60;
                    $totalOffDutyH = floor($summary->total_off_duty_minutes / 60);
                    $totalOffDutyM = $summary->total_off_duty_minutes % 60;
                @endphp
                <tr>
                    <td><strong>{{ $summary->driver->full_name ?? 'N/A' }}</strong></td>
                    <td>{{ $summary->carrier->name ?? 'N/A' }}</td>
                    <td class="text-center">{{ $summary->total_days }}</td>
                    <td>{{ $totalDrivingH }}h {{ $totalDrivingM }}m</td>
                    <td>{{ $avgDrivingH }}h {{ $avgDrivingM }}m</td>
                    <td>{{ $totalOnDutyH }}h {{ $totalOnDutyM }}m</td>
                    <td>{{ $totalOffDutyH }}h {{ $totalOffDutyM }}m</td>
                    <td class="text-center">
                        @if($summary->days_with_violations > 0)
                            <span class="badge-violation">{{ $summary->days_with_violations }} day{{ $summary->days_with_violations > 1 ? 's' : '' }}</span>
                        @else
                            <span class="badge-clean">Clean</span>
                        @endif
                    </td>
                    <td>{{ \Carbon\Carbon::parse($summary->first_log_date)->format('m/d/Y') }} - {{ \Carbon\Carbon::parse($summary->last_log_date)->format('m/d/Y') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center" style="padding: 20px;">No HOS data found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Generated: {{ $generatedAt }} | EFCTS &copy; {{ date('Y') }}</p>
    </div>
</body>
</html>
