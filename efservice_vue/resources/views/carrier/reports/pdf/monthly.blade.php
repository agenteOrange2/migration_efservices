<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Monthly Summary Report - {{ $carrier->name }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #1e40af; padding-bottom: 15px; }
        .header h1 { margin: 0; color: #1e40af; font-size: 24px; }
        .header p { margin: 5px 0 0; color: #666; }
        .carrier-info { background: #f8fafc; padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .carrier-info strong { color: #1e40af; }
        .period-bar { background: #1e40af; color: #fff; text-align: center; padding: 10px; margin-bottom: 15px; border-radius: 4px; font-size: 13px; font-weight: bold; }
        .stats-grid { display: table; width: 100%; margin-bottom: 20px; }
        .stat-box { display: table-cell; width: 20%; text-align: center; padding: 10px; background: #f1f5f9; border: 1px solid #e2e8f0; }
        .stat-box .value { font-size: 18px; font-weight: bold; color: #1e40af; }
        .stat-box .label { font-size: 10px; color: #64748b; text-transform: uppercase; }
        .stat-box.danger .value { color: #dc2626; }
        .section-title { background: #1e40af; color: #fff; padding: 8px 12px; font-size: 12px; font-weight: bold; text-transform: uppercase; margin: 15px 0 8px; border-radius: 3px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #3b82f6; color: white; padding: 8px; text-align: center; font-size: 11px; }
        td { padding: 7px 8px; border-bottom: 1px solid #e2e8f0; font-size: 11px; text-align: center; }
        tr:nth-child(even) td { background: #f8fafc; }
        td.month-name { text-align: left; font-weight: bold; color: #1e40af; }
        .footer { margin-top: 20px; text-align: center; font-size: 10px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Monthly Summary Report</h1>
        <p>Generated on {{ now()->format('F d, Y \a\t h:i A') }}</p>
    </div>

    <div class="carrier-info">
        <strong>Carrier:</strong> {{ $carrier->name }} |
        <strong>DOT:</strong> {{ $carrier->dot_number ?? 'N/A' }} |
        <strong>MC:</strong> {{ $carrier->mc_number ?? 'N/A' }}
    </div>

    <div class="period-bar">
        Report Period: {{ \Carbon\Carbon::parse($startDate)->format('F Y') }} &mdash; {{ \Carbon\Carbon::parse($endDate)->format('F Y') }}
    </div>

    <div class="stats-grid">
        <div class="stat-box">
            <div class="value">{{ $totalDrivers ?? 0 }}</div>
            <div class="label">New Drivers</div>
        </div>
        <div class="stat-box">
            <div class="value">{{ $totalVehicles ?? 0 }}</div>
            <div class="label">New Vehicles</div>
        </div>
        <div class="stat-box danger">
            <div class="value">{{ $totalAccidents ?? 0 }}</div>
            <div class="label">Total Accidents</div>
        </div>
        <div class="stat-box">
            <div class="value">${{ number_format($totalMaintenanceCost ?? 0, 0) }}</div>
            <div class="label">Maintenance Cost</div>
        </div>
        <div class="stat-box">
            <div class="value">${{ number_format($totalRepairCost ?? 0, 0) }}</div>
            <div class="label">Repair Cost</div>
        </div>
    </div>

    <div class="section-title">Monthly Breakdown</div>
    <table>
        <thead>
            <tr>
                <th style="text-align:left;">Month</th>
                <th>New Drivers</th>
                <th>New Vehicles</th>
                <th>Accidents</th>
                <th>Maintenance Records</th>
                <th>Maintenance Cost</th>
                <th>Repair Records</th>
                <th>Repair Cost</th>
            </tr>
        </thead>
        <tbody>
            @forelse($monthlyData as $month)
            <tr>
                <td class="month-name">{{ $month['month_name'] }}</td>
                <td>{{ $month['drivers'] ?? 0 }}</td>
                <td>{{ $month['vehicles'] ?? 0 }}</td>
                <td>{{ $month['accidents'] ?? 0 }}</td>
                <td>{{ $month['maintenance']['count'] ?? 0 }}</td>
                <td>${{ number_format($month['maintenance']['total_cost'] ?? 0, 2) }}</td>
                <td>{{ $month['repairs']['count'] ?? 0 }}</td>
                <td>${{ number_format($month['repairs']['total_cost'] ?? 0, 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align: center; padding: 20px; color: #94a3b8;">No monthly data available</td>
            </tr>
            @endforelse
        </tbody>
        @if(count($monthlyData) > 0)
        <tfoot>
            <tr style="background:#eff6ff; font-weight:bold;">
                <td style="text-align:left; color:#1e40af;">TOTAL</td>
                <td>{{ $totalDrivers ?? 0 }}</td>
                <td>{{ $totalVehicles ?? 0 }}</td>
                <td>{{ $totalAccidents ?? 0 }}</td>
                <td>{{ collect($monthlyData)->sum('maintenance.count') }}</td>
                <td>${{ number_format($totalMaintenanceCost ?? 0, 2) }}</td>
                <td>{{ collect($monthlyData)->sum('repairs.count') }}</td>
                <td>${{ number_format($totalRepairCost ?? 0, 2) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="footer">
        <p>{{ $carrier->name }} - Monthly Summary Report | &copy; {{ now()->format('Y') }} EFCTS. All rights reserved. | Confidential</p>
    </div>
</body>
</html>
