<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Monthly Report - {{ $carrier->name }}</title>
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

        /* Period Box */
        .period-box {
            background-color: #1e40af;
            color: #ffffff;
            text-align: center;
            padding: 10px;
            margin-bottom: 15px;
        }

        .period-label {
            font-size: 9px;
            color: #bfdbfe;
            text-transform: uppercase;
            margin-bottom: 3px;
        }

        .period-value {
            font-size: 14px;
            font-weight: bold;
        }

        /* Statistics Grid using Table */
        .stats-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .stats-table td {
            width: 25%;
            text-align: center;
            padding: 12px 5px;
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
        }

        .stat-value {
            font-size: 22px;
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

        .stat-value.red {
            color: #dc2626;
        }

        .stat-value.purple {
            color: #7c3aed;
        }

        .stat-label {
            font-size: 8px;
            color: #64748b;
            text-transform: uppercase;
            display: block;
            margin-top: 3px;
        }

        /* Section Title */
        .section-title {
            background-color: #1e40af;
            color: #ffffff;
            padding: 8px 12px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 15px;
            margin-bottom: 10px;
        }

        /* Data Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .data-table th {
            background-color: #3b82f6;
            color: #ffffff;
            font-weight: bold;
            padding: 8px 6px;
            text-align: center;
            font-size: 8px;
            text-transform: uppercase;
            border: 1px solid #3b82f6;
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

        /* Alert Section */
        .alert-section {
            margin-top: 15px;
            margin-bottom: 10px;
        }

        .alert-title {
            background-color: #dc2626;
            color: #ffffff;
            padding: 8px 12px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .alert-content {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            padding: 10px;
        }

        .alert-item {
            font-size: 9px;
            margin: 3px 0;
            padding-left: 10px;
        }

        /* Utilities */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .text-muted { color: #94a3b8; }
        .text-danger { color: #dc2626; }

        .no-data {
            text-align: center;
            padding: 20px;
            color: #94a3b8;
            font-size: 10px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
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
                <div class="brand-title">Monthly Summary Report</div>
                <div class="brand-subtitle">Comprehensive Fleet Overview</div>
            </td>
            <td class="header-right" style="width: 40%;">
                <div class="carrier-name">{{ $carrier->name }}</div>
                <div class="header-date">Generated: {{ $generated_at }}</div>
            </td>
        </tr>
    </table>
    <div class="header-line"></div>

    <!-- Period -->
    <div class="period-box">
        <div class="period-label">Report Period</div>
        <div class="period-value">{{ $period ?? now()->format('F Y') }}</div>
    </div>

    <!-- Main Statistics -->
    <table class="stats-table">
        <tr>
            <td>
                <span class="stat-value">{{ $stats['drivers']['total'] ?? 0 }}</span>
                <span class="stat-label">Total Drivers</span>
            </td>
            <td>
                <span class="stat-value">{{ $stats['vehicles']['total'] ?? 0 }}</span>
                <span class="stat-label">Total Vehicles</span>
            </td>
            <td>
                <span class="stat-value red">{{ $stats['accidents']['total'] ?? 0 }}</span>
                <span class="stat-label">Accidents</span>
            </td>
            <td>
                <span class="stat-value purple">${{ number_format($stats['maintenance']['total_cost'] ?? 0, 2) }}</span>
                <span class="stat-label">Maintenance Cost</span>
            </td>
        </tr>
    </table>

    <!-- Critical Alerts -->
    @if(!empty($alerts) && count($alerts) > 0)
    <div class="alert-section">
        <div class="alert-title">Critical Alerts Requiring Attention</div>
        <div class="alert-content">
            @foreach($alerts as $alert)
            <div class="alert-item">* {{ $alert }}</div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Driver Status -->
    <div class="section-title">Driver Status Overview</div>
    <table class="stats-table">
        <tr>
            <td>
                <span class="stat-value green">{{ $stats['drivers']['active'] ?? 0 }}</span>
                <span class="stat-label">Active Drivers</span>
            </td>
            <td>
                <span class="stat-value orange">{{ $stats['drivers']['inactive'] ?? 0 }}</span>
                <span class="stat-label">Inactive</span>
            </td>
            <td>
                <span class="stat-value orange">{{ $stats['drivers']['expiring_licenses'] ?? 0 }}</span>
                <span class="stat-label">Expiring Licenses</span>
            </td>
            <td>
                <span class="stat-value red">{{ $stats['drivers']['expiring_medical'] ?? 0 }}</span>
                <span class="stat-label">Expiring Medical</span>
            </td>
        </tr>
    </table>

    <!-- Vehicle Status -->
    <div class="section-title">Vehicle Status Overview</div>
    <table class="stats-table">
        <tr>
            <td>
                <span class="stat-value green">{{ $stats['vehicles']['active'] ?? 0 }}</span>
                <span class="stat-label">Active Vehicles</span>
            </td>
            <td>
                <span class="stat-value orange">{{ $stats['vehicles']['out_of_service'] ?? 0 }}</span>
                <span class="stat-label">Out of Service</span>
            </td>
            <td>
                <span class="stat-value orange">{{ $stats['vehicles']['expiring_registration'] ?? 0 }}</span>
                <span class="stat-label">Expiring Registration</span>
            </td>
            <td>
                <span class="stat-value">{{ $stats['vehicles']['pending_maintenance'] ?? 0 }}</span>
                <span class="stat-label">Pending Maintenance</span>
            </td>
        </tr>
    </table>

    <!-- Recent Accidents -->
    @if(!empty($recentAccidents) && count($recentAccidents) > 0)
    <div class="section-title">Recent Accidents This Period</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 15%;">Date</th>
                <th style="width: 25%;">Driver</th>
                <th style="width: 35%;">Nature</th>
                <th style="width: 12%;">Injuries</th>
                <th style="width: 13%;">Fatalities</th>
            </tr>
        </thead>
        <tbody>
            @foreach($recentAccidents as $accident)
            <tr>
                <td class="text-center">{{ $accident->accident_date ? $accident->accident_date->format('m/d/Y') : 'N/A' }}</td>
                <td class="text-left">{{ $accident->userDriverDetail->full_name ?? 'N/A' }}</td>
                <td class="text-left">{{ $accident->nature_of_accident ?? 'N/A' }}</td>
                <td class="text-center">{{ $accident->number_of_injuries ?? 0 }}</td>
                <td class="text-center {{ ($accident->number_of_fatalities ?? 0) > 0 ? 'text-danger font-bold' : '' }}">{{ $accident->number_of_fatalities ?? 0 }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div class="section-title">Recent Accidents This Period</div>
    <div class="no-data">No accidents recorded during this period</div>
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
