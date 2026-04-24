<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Maintenance Report - {{ $carrier->name }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #1e40af; padding-bottom: 15px; }
        .header h1 { margin: 0; color: #1e40af; font-size: 24px; }
        .header p { margin: 5px 0 0; color: #666; }
        .carrier-info { background: #f8fafc; padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .carrier-info strong { color: #1e40af; }
        .stats-grid { display: table; width: 100%; margin-bottom: 20px; }
        .stat-box { display: table-cell; width: 20%; text-align: center; padding: 10px; background: #f1f5f9; border: 1px solid #e2e8f0; }
        .stat-box .value { font-size: 18px; font-weight: bold; color: #1e40af; }
        .stat-box .label { font-size: 10px; color: #64748b; text-transform: uppercase; }
        .stat-box.success .value { color: #16a34a; }
        .stat-box.warning .value { color: #ca8a04; }
        .filters { background: #fef3c7; padding: 8px 12px; margin-bottom: 15px; border-radius: 4px; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #1e40af; color: white; padding: 10px 8px; text-align: left; font-size: 11px; }
        td { padding: 8px; border-bottom: 1px solid #e2e8f0; font-size: 11px; vertical-align: middle; }
        tr:nth-child(even) td { background: #f8fafc; }
        .badge { padding: 3px 8px; border-radius: 3px; font-size: 10px; font-weight: bold; }
        .badge-completed { background: #dcfce7; color: #166534; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .cost { color: #1e40af; font-weight: bold; }
        .summary { margin-top: 15px; background: #eff6ff; padding: 10px 15px; border: 1px solid #bfdbfe; border-radius: 4px; font-size: 11px; }
        .footer { margin-top: 20px; text-align: center; font-size: 10px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Maintenance Report</h1>
        <p>Generated on {{ now()->format('F d, Y \a\t h:i A') }}</p>
    </div>

    <div class="carrier-info">
        <strong>Carrier:</strong> {{ $carrier->name }} |
        <strong>DOT:</strong> {{ $carrier->dot_number ?? 'N/A' }} |
        <strong>MC:</strong> {{ $carrier->mc_number ?? 'N/A' }} |
        <strong>Total Records:</strong> {{ $total_records }}
    </div>

    @if(!empty($filters) && array_filter(array_diff_key($filters, ['per_page' => ''])))
        <div class="filters">
            <strong>Applied Filters:</strong>
            @if(!empty($filters['search'])) Search: {{ $filters['search'] }}. @endif
            @if(!empty($filters['status'])) Status: {{ ucfirst($filters['status']) }}. @endif
            @if(!empty($filters['type'])) Type: {{ $filters['type'] }}. @endif
            @if(!empty($filters['date_from'])) From: {{ $filters['date_from'] }}. @endif
            @if(!empty($filters['date_to'])) To: {{ $filters['date_to'] }}. @endif
        </div>
    @endif

    <div class="stats-grid">
        <div class="stat-box">
            <div class="value">{{ $stats['count'] ?? 0 }}</div>
            <div class="label">Total Records</div>
        </div>
        <div class="stat-box success">
            <div class="value">{{ $stats['completed'] ?? 0 }}</div>
            <div class="label">Completed</div>
        </div>
        <div class="stat-box warning">
            <div class="value">{{ $stats['pending'] ?? 0 }}</div>
            <div class="label">Pending</div>
        </div>
        <div class="stat-box">
            <div class="value">${{ number_format($stats['total_cost'] ?? 0, 0) }}</div>
            <div class="label">Total Cost</div>
        </div>
        <div class="stat-box">
            <div class="value">${{ number_format($stats['average_cost'] ?? 0, 0) }}</div>
            <div class="label">Avg. Cost</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Vehicle</th>
                <th>Service Tasks</th>
                <th>Description</th>
                <th>Service Date</th>
                <th>Vendor / Mechanic</th>
                <th>Cost</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($maintenanceRecords as $maintenance)
            <tr>
                <td>
                    <strong>{{ $maintenance->vehicle?->company_unit_number ?? 'N/A' }}</strong>
                    <br><small style="color:#64748b;">{{ $maintenance->vehicle?->make ?? '' }} {{ $maintenance->vehicle?->model ?? '' }}</small>
                </td>
                <td><strong>{{ $maintenance->service_tasks ?? 'N/A' }}</strong></td>
                <td><small>{{ $maintenance->description ? \Illuminate\Support\Str::limit($maintenance->description, 80) : '—' }}</small></td>
                <td>{{ $maintenance->service_date ? $maintenance->service_date->format('m/d/Y') : 'N/A' }}</td>
                <td>{{ $maintenance->vendor_mechanic ?? '—' }}</td>
                <td style="text-align:right;"><span class="cost">${{ number_format($maintenance->cost ?? 0, 2) }}</span></td>
                <td>
                    @if($maintenance->status)
                        <span class="badge badge-completed">Completed</span>
                    @else
                        <span class="badge badge-pending">Pending</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center; padding: 20px; color: #94a3b8;">No maintenance records found matching the specified filters</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($maintenanceRecords->count() > 0)
    <div class="summary">
        <strong>Total Cost: <span style="color:#1e40af;">${{ number_format($stats['total_cost'] ?? 0, 2) }}</span></strong> &nbsp;&nbsp;|&nbsp;&nbsp;
        Completed: {{ $stats['completed'] ?? 0 }} &nbsp;&nbsp;|&nbsp;&nbsp;
        Pending: {{ $stats['pending'] ?? 0 }}
    </div>
    @endif

    <div class="footer">
        <p>{{ $carrier->name }} - Maintenance Report | &copy; {{ now()->format('Y') }} EFCTS. All rights reserved. | Confidential</p>
    </div>
</body>
</html>
