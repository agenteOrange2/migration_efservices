<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Accident Report - {{ $carrier->name }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #dc2626; padding-bottom: 15px; }
        .header h1 { margin: 0; color: #dc2626; font-size: 24px; }
        .header p { margin: 5px 0 0; color: #666; }
        .carrier-info { background: #f8fafc; padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .carrier-info strong { color: #1e40af; }
        .stats-grid { display: table; width: 100%; margin-bottom: 20px; }
        .stat-box { display: table-cell; width: 20%; text-align: center; padding: 10px; background: #f1f5f9; border: 1px solid #e2e8f0; }
        .stat-box .value { font-size: 20px; font-weight: bold; color: #1e40af; }
        .stat-box .label { font-size: 10px; color: #64748b; text-transform: uppercase; }
        .stat-box.success .value { color: #16a34a; }
        .stat-box.danger .value { color: #dc2626; }
        .stat-box.warning .value { color: #ca8a04; }
        .filters { background: #fef3c7; padding: 8px 12px; margin-bottom: 15px; border-radius: 4px; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #dc2626; color: white; padding: 10px 8px; text-align: left; font-size: 11px; }
        td { padding: 8px; border-bottom: 1px solid #e2e8f0; font-size: 11px; vertical-align: middle; }
        tr:nth-child(even) td { background: #f8fafc; }
        tr.critical td { background: #fef2f2; }
        tr.serious td { background: #fffbeb; }
        .badge { padding: 3px 8px; border-radius: 3px; font-size: 10px; font-weight: bold; }
        .badge-critical { background: #fee2e2; color: #991b1b; }
        .badge-serious { background: #fef3c7; color: #92400e; }
        .badge-minor { background: #dbeafe; color: #1e40af; }
        .footer { margin-top: 20px; text-align: center; font-size: 10px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Accident Report</h1>
        <p>Generated on {{ now()->format('F d, Y \a\t h:i A') }}</p>
    </div>

    <div class="carrier-info">
        <strong>Carrier:</strong> {{ $carrier->name }} |
        <strong>DOT:</strong> {{ $carrier->dot_number ?? 'N/A' }} |
        <strong>MC:</strong> {{ $carrier->mc_number ?? 'N/A' }} |
        <strong>Total Records:</strong> {{ $total_accidents }}
    </div>

    @if(!empty($filters) && array_filter(array_diff_key($filters, ['per_page' => ''])))
        <div class="filters">
            <strong>Applied Filters:</strong>
            @if(!empty($filters['search'])) Search: {{ $filters['search'] }}. @endif
            @if(!empty($filters['driver'])) Driver ID: {{ $filters['driver'] }}. @endif
            @if(!empty($filters['date_from'])) From: {{ $filters['date_from'] }}. @endif
            @if(!empty($filters['date_to'])) To: {{ $filters['date_to'] }}. @endif
        </div>
    @endif

    <div class="stats-grid">
        <div class="stat-box">
            <div class="value">{{ $stats['total'] ?? 0 }}</div>
            <div class="label">Total Accidents</div>
        </div>
        <div class="stat-box warning">
            <div class="value">{{ $stats['recent'] ?? 0 }}</div>
            <div class="label">Recent (30 Days)</div>
        </div>
        <div class="stat-box danger">
            <div class="value">{{ $stats['with_fatalities'] ?? 0 }}</div>
            <div class="label">With Fatalities</div>
        </div>
        <div class="stat-box warning">
            <div class="value">{{ $stats['with_injuries'] ?? 0 }}</div>
            <div class="label">With Injuries</div>
        </div>
        <div class="stat-box success">
            <div class="value">{{ $stats['without_injuries'] ?? 0 }}</div>
            <div class="label">Minor Only</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Driver</th>
                <th>Nature of Accident</th>
                <th>Severity</th>
                <th>Fatalities</th>
                <th>Injuries</th>
                <th>Comments</th>
            </tr>
        </thead>
        <tbody>
            @forelse($accidents as $accident)
            <tr class="{{ $accident->had_fatalities ? 'critical' : ($accident->had_injuries ? 'serious' : '') }}">
                <td><strong>{{ $accident->accident_date ? $accident->accident_date->format('m/d/Y') : 'N/A' }}</strong></td>
                <td>
                    @if($accident->userDriverDetail)
                        <strong>{{ $accident->userDriverDetail->full_name }}</strong>
                        @if($accident->userDriverDetail->user)
                            <br><small style="color:#64748b;">{{ $accident->userDriverDetail->user->email }}</small>
                        @endif
                    @else
                        N/A
                    @endif
                </td>
                <td>{{ $accident->nature_of_accident ?? 'N/A' }}</td>
                <td>
                    @if($accident->had_fatalities)
                        <span class="badge badge-critical">Critical</span>
                    @elseif($accident->had_injuries)
                        <span class="badge badge-serious">Serious</span>
                    @else
                        <span class="badge badge-minor">Minor</span>
                    @endif
                </td>
                <td style="text-align:center;">{{ $accident->had_fatalities ? ($accident->number_of_fatalities ?? 0) : '—' }}</td>
                <td style="text-align:center;">{{ $accident->had_injuries ? ($accident->number_of_injuries ?? 0) : '—' }}</td>
                <td><small>{{ $accident->comments ?? '—' }}</small></td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center; padding: 20px; color: #94a3b8;">No accidents found matching the specified filters</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>{{ $carrier->name }} - Accident Report | &copy; {{ now()->format('Y') }} EFCTS. All rights reserved. | Confidential</p>
    </div>
</body>
</html>
