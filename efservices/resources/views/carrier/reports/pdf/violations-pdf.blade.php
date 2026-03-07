<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Violations Report - {{ $carrier->name }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #dc2626; padding-bottom: 15px; }
        .header h1 { margin: 0; color: #dc2626; font-size: 24px; }
        .header p { margin: 5px 0 0; color: #666; }
        .carrier-info { background: #f8fafc; padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .carrier-info strong { color: #1e40af; }
        .stats-grid { display: table; width: 100%; margin-bottom: 15px; }
        .stat-box { display: table-cell; width: 25%; text-align: center; padding: 10px; background: #f1f5f9; border: 1px solid #e2e8f0; }
        .stat-box .value { font-size: 20px; font-weight: bold; color: #1e40af; }
        .stat-box .label { font-size: 10px; color: #64748b; text-transform: uppercase; }
        .stat-box.danger .value { color: #dc2626; }
        .stat-box.success .value { color: #16a34a; }
        .stat-box.warning .value { color: #ca8a04; }
        .severity-grid { display: table; width: 100%; margin-bottom: 20px; }
        .severity-box { display: table-cell; width: 33.33%; text-align: center; padding: 8px; border: 1px solid #e2e8f0; }
        .severity-box .dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 5px; }
        .severity-box .dot.minor { background: #facc15; }
        .severity-box .dot.moderate { background: #f97316; }
        .severity-box .dot.critical { background: #ef4444; }
        .filters { background: #fef3c7; padding: 8px 12px; margin-bottom: 15px; border-radius: 4px; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #dc2626; color: white; padding: 10px 8px; text-align: left; font-size: 11px; }
        td { padding: 8px; border-bottom: 1px solid #e2e8f0; font-size: 11px; }
        tr:nth-child(even) { background: #f8fafc; }
        .severity { padding: 3px 8px; border-radius: 3px; font-size: 10px; font-weight: bold; }
        .severity-minor { background: #fef9c3; color: #854d0e; }
        .severity-moderate { background: #ffedd5; color: #9a3412; }
        .severity-critical { background: #fee2e2; color: #991b1b; }
        .status { padding: 3px 8px; border-radius: 3px; font-size: 10px; font-weight: bold; }
        .status-acknowledged { background: #dcfce7; color: #166534; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .footer { margin-top: 20px; text-align: center; font-size: 10px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>HOS Violations Report</h1>
        <p>Generated on {{ now()->format('F d, Y \a\t h:i A') }}</p>
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
        <div class="stat-box danger">
            <div class="value">{{ number_format($stats['total_violations']) }}</div>
            <div class="label">Total Violations</div>
        </div>
        <div class="stat-box success">
            <div class="value">{{ number_format($stats['acknowledged_count']) }}</div>
            <div class="label">Acknowledged</div>
        </div>
        <div class="stat-box warning">
            <div class="value">{{ number_format($stats['unacknowledged_count']) }}</div>
            <div class="label">Unacknowledged</div>
        </div>
        <div class="stat-box">
            <div class="value">{{ $stats['acknowledgment_rate'] }}%</div>
            <div class="label">Ack. Rate</div>
        </div>
    </div>

    <div class="severity-grid">
        <div class="severity-box">
            <span class="dot minor"></span>
            <strong>Minor:</strong> {{ $stats['by_severity']['minor'] ?? 0 }}
        </div>
        <div class="severity-box">
            <span class="dot moderate"></span>
            <strong>Moderate:</strong> {{ $stats['by_severity']['moderate'] ?? 0 }}
        </div>
        <div class="severity-box">
            <span class="dot critical"></span>
            <strong>Critical:</strong> {{ $stats['by_severity']['critical'] ?? 0 }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Driver</th>
                <th>Type</th>
                <th>Severity</th>
                <th>Hours Exceeded</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($violations as $violation)
                <tr>
                    <td><strong>{{ $violation->violation_date->format('M d, Y') }}</strong></td>
                    <td>{{ $violation->driver?->full_name ?? 'N/A' }}</td>
                    <td>{{ $violation->violation_type_name }}</td>
                    <td><span class="severity severity-{{ $violation->severity }}">{{ ucfirst($violation->severity) }}</span></td>
                    <td>{{ $violation->formatted_hours_exceeded }}</td>
                    <td>
                        @if($violation->acknowledged)
                            <span class="status status-acknowledged">Acknowledged</span>
                        @else
                            <span class="status status-pending">Pending</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 20px;">No violations found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>{{ $carrier->name }} - Violations Report | Page 1 | Confidential</p>
    </div>
</body>
</html>
