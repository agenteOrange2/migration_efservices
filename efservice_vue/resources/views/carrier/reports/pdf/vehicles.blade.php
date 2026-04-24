<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Vehicle Report - {{ $carrier->name }}</title>
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
        .stat-box.warning .value { color: #ca8a04; }
        .filters { background: #fef3c7; padding: 8px 12px; margin-bottom: 15px; border-radius: 4px; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #1e40af; color: white; padding: 10px 8px; text-align: left; font-size: 11px; }
        td { padding: 8px; border-bottom: 1px solid #e2e8f0; font-size: 11px; vertical-align: middle; }
        tr:nth-child(even) td { background: #f8fafc; }
        tr.expiring td { background: #fef9c3; }
        .badge { padding: 3px 8px; border-radius: 3px; font-size: 10px; font-weight: bold; }
        .badge-active { background: #dcfce7; color: #166534; }
        .badge-oos { background: #f1f5f9; color: #475569; }
        .badge-suspended { background: #fee2e2; color: #991b1b; }
        .expiring-label { font-size: 10px; color: #ca8a04; font-weight: bold; }
        .footer { margin-top: 20px; text-align: center; font-size: 10px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Vehicle Report</h1>
        <p>Generated on {{ now()->format('F d, Y \a\t h:i A') }}</p>
    </div>

    <div class="carrier-info">
        <strong>Carrier:</strong> {{ $carrier->name }} |
        <strong>DOT:</strong> {{ $carrier->dot_number ?? 'N/A' }} |
        <strong>MC:</strong> {{ $carrier->mc_number ?? 'N/A' }} |
        <strong>Total Records:</strong> {{ $total_vehicles }}
    </div>

    @if(!empty($filters) && array_filter(array_diff_key($filters, ['per_page' => ''])))
        <div class="filters">
            <strong>Applied Filters:</strong>
            @if(!empty($filters['search'])) Search: {{ $filters['search'] }}. @endif
            @if(!empty($filters['status'])) Status: {{ ucfirst(str_replace('_', ' ', $filters['status'])) }}. @endif
            @if(!empty($filters['date_from'])) From: {{ $filters['date_from'] }}. @endif
            @if(!empty($filters['date_to'])) To: {{ $filters['date_to'] }}. @endif
        </div>
    @endif

    <div class="stats-grid">
        <div class="stat-box">
            <div class="value">{{ $stats['total'] ?? 0 }}</div>
            <div class="label">Total Vehicles</div>
        </div>
        <div class="stat-box success">
            <div class="value">{{ $stats['active'] ?? 0 }}</div>
            <div class="label">Active</div>
        </div>
        <div class="stat-box danger">
            <div class="value">{{ $stats['out_of_service'] ?? 0 }}</div>
            <div class="label">Out of Service</div>
        </div>
        <div class="stat-box warning">
            <div class="value">{{ $stats['recent'] ?? 0 }}</div>
            <div class="label">New (30 Days)</div>
        </div>
        <div class="stat-box success">
            <div class="value">{{ $stats['percentage_active'] ?? 0 }}%</div>
            <div class="label">Active Rate</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Unit Number</th>
                <th>Make / Model</th>
                <th>VIN</th>
                <th>Registration Exp.</th>
                <th>Status</th>
                <th>Added Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($vehicles as $vehicle)
            <tr class="{{ $vehicle->has_expiring_registration ? 'expiring' : '' }}">
                <td>
                    <strong>{{ $vehicle->company_unit_number ?? 'N/A' }}</strong>
                    @if($vehicle->has_expiring_registration)
                        <br><span class="expiring-label">⚠ Registration expiring soon</span>
                    @endif
                </td>
                <td>
                    <strong>{{ $vehicle->make ?? 'N/A' }}</strong>
                    <br>{{ $vehicle->model ?? '' }} {{ $vehicle->year ?? '' }}
                </td>
                <td>{{ $vehicle->vin ?? 'N/A' }}</td>
                <td>
                    @if($vehicle->registration_expiration_date)
                        {{ $vehicle->registration_expiration_date->format('m/d/Y') }}
                    @else
                        N/A
                    @endif
                </td>
                <td>
                    @if($vehicle->out_of_service)
                        <span class="badge badge-oos">Out of Service</span>
                    @elseif($vehicle->suspended)
                        <span class="badge badge-suspended">Suspended</span>
                    @else
                        <span class="badge badge-active">Active</span>
                    @endif
                </td>
                <td>{{ $vehicle->created_at ? $vehicle->created_at->format('m/d/Y') : 'N/A' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align: center; padding: 20px; color: #94a3b8;">No vehicles found matching the specified filters</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>{{ $carrier->name }} - Vehicle Report | &copy; {{ now()->format('Y') }} EFCTS. All rights reserved. | Confidential</p>
    </div>
</body>
</html>
