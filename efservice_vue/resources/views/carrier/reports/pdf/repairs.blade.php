<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Repairs Report - {{ $carrier->name }}</title>
    <style>
        @page {
            margin: 20px 25px 60px 25px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #1e293b;
        }

        /* Header */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            background-color: #1e40af;
        }

        .header-table td {
            padding: 15px 20px;
            vertical-align: middle;
        }

        .brand-logo {
            background-color: #ffffff;
            color: #1e40af;
            font-size: 22px;
            font-weight: bold;
            padding: 8px 12px;
            display: inline-block;
            letter-spacing: 2px;
        }

        .brand-title {
            color: #ffffff;
            font-size: 18px;
            font-weight: bold;
            margin-top: 8px;
        }

        .brand-subtitle {
            color: #bfdbfe;
            font-size: 10px;
            margin-top: 3px;
        }

        .header-right {
            text-align: right;
            color: #ffffff;
        }

        .carrier-name {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .header-date {
            font-size: 9px;
            color: #bfdbfe;
        }

        .header-line {
            height: 4px;
            background-color: #3b82f6;
            margin-bottom: 15px;
        }

        /* Statistics */
        .stats-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .stats-table td {
            width: 20%;
            text-align: center;
            padding: 10px 5px;
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
        }

        .stat-value {
            font-size: 18px;
            font-weight: bold;
            color: #1e40af;
            display: block;
        }

        .stat-value.green {
            color: #059669;
        }

        .stat-value.orange {
            color: #d97706;
        }

        .stat-value.purple {
            color: #1e40af;
        }

        .stat-label {
            font-size: 8px;
            color: #64748b;
            text-transform: uppercase;
            display: block;
            margin-top: 3px;
        }

        /* Filters */
        .filters-box {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            padding: 10px 12px;
            margin-bottom: 12px;
        }

        .filters-title {
            color: #1e40af;
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .filters-box p {
            font-size: 9px;
            color: #475569;
            margin: 2px 0;
        }

        /* Data Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .data-table th {
            background-color: #1e40af;
            color: #ffffff;
            font-weight: bold;
            padding: 8px 6px;
            text-align: center;
            font-size: 8px;
            text-transform: uppercase;
            border: 1px solid #1e40af;
        }

        .data-table td {
            padding: 6px;
            border: 1px solid #e2e8f0;
            font-size: 8px;
            vertical-align: middle;
        }

        .data-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-completed {
            background-color: #dcfce7;
            color: #166534;
        }

        .badge-pending {
            background-color: #fef3c7;
            color: #92400e;
        }

        .badge-critical {
            background-color: #fee2e2;
            color: #991b1b;
        }

        /* Summary Box */
        .summary-box {
            margin-top: 15px;
            padding: 10px 15px;
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
        }

        .summary-box table {
            width: 100%;
        }

        .summary-box td {
            font-size: 10px;
            color: #1e293b;
        }

        .summary-value {
            font-weight: bold;
            color: #1e40af;
        }

        /* Utilities */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .text-muted { color: #94a3b8; }

        .vehicle-unit {
            font-weight: bold;
            color: #1e293b;
        }

        .vehicle-details {
            font-size: 7px;
            color: #64748b;
        }

        .cost-value {
            color: #1e40af;
            font-weight: bold;
        }

        .no-data {
            text-align: center;
            padding: 30px;
            color: #94a3b8;
            font-size: 11px;
        }

        /* Footer */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 40px;
            border-top: 2px solid #3b82f6;
            padding-top: 8px;
            background-color: #f8fafc;
        }

        .footer-table {
            width: 100%;
        }

        .footer-brand {
            font-size: 10px;
            color: #1e40af;
            font-weight: bold;
        }

        .footer-info {
            font-size: 8px;
            color: #64748b;
            text-align: right;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <table class="header-table">
        <tr>
            <td style="width: 60%;">
                <span class="brand-logo">EFCTS</span>
                <div class="brand-title">Repairs Report</div>
                <div class="brand-subtitle">Vehicle Repair Management</div>
            </td>
            <td class="header-right" style="width: 40%;">
                <div class="carrier-name">{{ $carrier->name }}</div>
                <div class="header-date">Generated: {{ $generated_at }}</div>
                <div class="header-date">Total Records: {{ $total_records }}</div>
            </td>
        </tr>
    </table>
    <div class="header-line"></div>

    <!-- Statistics -->
    <table class="stats-table">
        <tr>
            <td>
                <span class="stat-value">{{ $stats['count'] ?? 0 }}</span>
                <span class="stat-label">Total Repairs</span>
            </td>
            <td>
                <span class="stat-value green">{{ $stats['completed'] ?? 0 }}</span>
                <span class="stat-label">Completed</span>
            </td>
            <td>
                <span class="stat-value orange">{{ $stats['pending'] ?? 0 }}</span>
                <span class="stat-label">Pending</span>
            </td>
            <td>
                <span class="stat-value purple">${{ number_format($stats['total_cost'] ?? 0, 2) }}</span>
                <span class="stat-label">Total Cost</span>
            </td>
            <td>
                <span class="stat-value purple">${{ number_format($stats['average_cost'] ?? 0, 2) }}</span>
                <span class="stat-label">Avg. Cost</span>
            </td>
        </tr>
    </table>

    <!-- Applied Filters -->
    @if(!empty($filters) && array_filter($filters))
    <div class="filters-box">
        <div class="filters-title">Applied Filters</div>
        @if(!empty($filters['search']))
            <p><strong>Search:</strong> {{ $filters['search'] }}</p>
        @endif
        @if(!empty($filters['status']))
            <p><strong>Status:</strong> {{ ucfirst($filters['status']) }}</p>
        @endif
        @if(!empty($filters['vehicle']))
            <p><strong>Vehicle ID:</strong> {{ $filters['vehicle'] }}</p>
        @endif
        @if(!empty($filters['date_from']))
            <p><strong>From Date:</strong> {{ $filters['date_from'] }}</p>
        @endif
        @if(!empty($filters['date_to']))
            <p><strong>To Date:</strong> {{ $filters['date_to'] }}</p>
        @endif
        @if(empty($filters['date_from']) && empty($filters['date_to']))
            <p><strong>Date Range:</strong> Last 30 days (default)</p>
        @endif
    </div>
    @endif

    <!-- Repairs Table -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 12%;">Vehicle</th>
                <th style="width: 18%;">Repair Name</th>
                <th style="width: 22%;">Description</th>
                <th style="width: 10%;">Repair Date</th>
                <th style="width: 15%;">Notes</th>
                <th style="width: 10%;">Cost</th>
                <th style="width: 8%;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($repairs as $repair)
            <tr>
                <td class="text-left">
                    <span class="vehicle-unit">{{ $repair->vehicle->company_unit_number ?? 'N/A' }}</span>
                    <br><span class="vehicle-details">{{ $repair->vehicle->make ?? '' }} {{ $repair->vehicle->model ?? '' }}</span>
                    @if($repair->vehicle && $repair->vehicle->vin)
                        <br><span class="vehicle-details">VIN: ...{{ substr($repair->vehicle->vin, -6) }}</span>
                    @endif
                </td>
                <td class="text-left font-bold">
                    {{ $repair->repair_name ?? 'N/A' }}
                </td>
                <td class="text-left" style="font-size: 7px;">
                    {{ $repair->description ? \Illuminate\Support\Str::limit($repair->description, 80) : 'N/A' }}
                </td>
                <td class="text-center">
                    {{ $repair->repair_date ? $repair->repair_date->format('m/d/Y') : 'N/A' }}
                </td>
                <td class="text-left">
                    {{ $repair->notes ? \Illuminate\Support\Str::limit($repair->notes, 40) : 'N/A' }}
                </td>
                <td class="text-right">
                    <span class="cost-value">${{ number_format($repair->cost ?? 0, 2) }}</span>
                </td>
                <td class="text-center">
                    @if($repair->status === 'completed')
                        <span class="badge badge-completed">Completed</span>
                    @elseif($repair->status === 'pending')
                        <span class="badge badge-pending">Pending</span>
                    @elseif($repair->status === 'critical')
                        <span class="badge badge-critical">Critical</span>
                    @else
                        <span class="badge badge-pending">{{ ucfirst($repair->status ?? 'Pending') }}</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="no-data">
                    No repair records found matching the specified filters
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($repairs->count() > 0)
    <div class="summary-box">
        <table>
            <tr>
                <td><strong>Summary:</strong> {{ $repairs->count() }} repair record(s)</td>
                <td class="text-right"><strong>Total Cost:</strong> <span class="summary-value">${{ number_format($stats['total_cost'] ?? 0, 2) }}</span></td>
            </tr>
        </table>
    </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <table class="footer-table">
            <tr>
                <td class="footer-brand">EFCTS Fleet Management</td>
                <td class="footer-info">{{ $carrier->name }} | © {{ now()->format('Y') }} EFCTS. All rights reserved.</td>
            </tr>
        </table>
    </div>
</body>
</html>
