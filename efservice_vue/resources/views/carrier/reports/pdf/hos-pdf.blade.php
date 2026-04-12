<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>HOS Driver Summary - {{ $carrier->name }}</title>
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
        .stat-box.danger .value { color: #dc2626; }
        .stat-box.info .value { color: #0284c7; }
        .stat-box.warning .value { color: #ca8a04; }
        .filters { background: #fef3c7; padding: 8px 12px; margin-bottom: 15px; border-radius: 4px; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #1e40af; color: white; padding: 10px 8px; text-align: left; font-size: 11px; }
        td { padding: 8px; border-bottom: 1px solid #e2e8f0; font-size: 11px; }
        tr:nth-child(even) { background: #f8fafc; }
        .text-center { text-align: center; }
        .badge-clean { background: #dcfce7; color: #166534; padding: 3px 8px; border-radius: 3px; font-size: 10px; font-weight: bold; }
        .badge-violation { background: #fee2e2; color: #991b1b; padding: 3px 8px; border-radius: 3px; font-size: 10px; font-weight: bold; }
        .footer { margin-top: 20px; text-align: center; font-size: 10px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>HOS Driver Summary</h1>
        <p>{{ $dateRangeLabel ?? 'All Time' }} &mdash; Generated on {{ now()->format('F d, Y \a\t h:i A') }}</p>
    </div>

    <div class="carrier-info">
        <strong>Carrier:</strong> {{ $carrier->name }} | 
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
            <div class="value">{{ $driverSummaries->count() }}</div>
            <div class="label">Drivers</div>
        </div>
        <div class="stat-box success">
            <div class="value">{{ $stats['compliance_percentage'] }}%</div>
            <div class="label">Compliance</div>
        </div>
        <div class="stat-box danger">
            <div class="value">{{ number_format($stats['logs_with_violations']) }}</div>
            <div class="label">Days w/ Violations</div>
        </div>
        <div class="stat-box info">
            <div class="value">{{ number_format($stats['average_driving_hours'], 1) }}h</div>
            <div class="label">Avg Driving/Day</div>
        </div>
        <div class="stat-box warning">
            <div class="value">{{ $stats['total_logs'] }}</div>
            <div class="label">Total Log Days</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Driver</th>
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
                    <td colspan="8" style="text-align: center; padding: 20px;">No HOS data found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>{{ $carrier->name }} - HOS Driver Summary | Confidential</p>
    </div>
</body>
</html>
