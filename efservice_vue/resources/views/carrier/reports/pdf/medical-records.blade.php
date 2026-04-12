<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Medical Records Report - {{ $carrier->name }}</title>
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
            font-size: 20px;
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

        .data-table tr.expired td {
            background-color: #fee2e2;
        }

        .data-table tr.expiring td {
            background-color: #fef3c7;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-valid {
            background-color: #dcfce7;
            color: #166534;
        }

        .badge-expiring {
            background-color: #fef3c7;
            color: #92400e;
        }

        .badge-expired {
            background-color: #fee2e2;
            color: #991b1b;
        }

        /* Utilities */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .text-muted { color: #94a3b8; }

        .alert-icon {
            color: #f59e0b;
            font-weight: bold;
        }

        .danger-icon {
            color: #dc2626;
            font-weight: bold;
        }

        .driver-name {
            font-weight: bold;
            color: #1e293b;
        }

        .driver-email {
            font-size: 7px;
            color: #64748b;
        }

        .expiring-text {
            color: #f59e0b;
            font-size: 7px;
            font-weight: bold;
        }

        .expired-text {
            color: #dc2626;
            font-size: 7px;
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
                <div class="brand-title">Medical Records Report</div>
                <div class="brand-subtitle">Driver Medical Qualification</div>
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
                <span class="stat-value">{{ $stats['total'] ?? 0 }}</span>
                <span class="stat-label">Total Records</span>
            </td>
            <td>
                <span class="stat-value green">{{ $stats['valid'] ?? 0 }}</span>
                <span class="stat-label">Valid Certs</span>
            </td>
            <td>
                <span class="stat-value orange">{{ $stats['expiring_soon'] ?? 0 }}</span>
                <span class="stat-label">Expiring (30 days)</span>
            </td>
            <td>
                <span class="stat-value red">{{ $stats['expired'] ?? 0 }}</span>
                <span class="stat-label">Expired</span>
            </td>
            <td>
                <span class="stat-value green">{{ $stats['percentage_valid'] ?? 0 }}%</span>
                <span class="stat-label">Compliance Rate</span>
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
        @if(!empty($filters['date_from']))
            <p><strong>From Date:</strong> {{ $filters['date_from'] }}</p>
        @endif
        @if(!empty($filters['date_to']))
            <p><strong>To Date:</strong> {{ $filters['date_to'] }}</p>
        @endif
        @if(empty($filters['date_from']) && empty($filters['date_to']))
            <p><strong>Date Range:</strong> All records</p>
        @endif
    </div>
    @endif

    <!-- Medical Records Table -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 18%;">Driver Name</th>
                <th style="width: 15%;">Examiner Name</th>
                <th style="width: 15%;">National Registry #</th>
                <th style="width: 12%;">Exam Date</th>
                <th style="width: 12%;">Exp. Date</th>                
                <th style="width: 10%;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($medicalRecords as $record)
            @php
                $isExpired = $record->medical_card_expiration_date && $record->medical_card_expiration_date->isPast();
                $daysUntilExpiration = $record->medical_card_expiration_date ? (int) now()->diffInDays($record->medical_card_expiration_date, false) : null;
                $isExpiring = !$isExpired && $record->medical_card_expiration_date && $daysUntilExpiration !== null && $daysUntilExpiration >= 0 && $daysUntilExpiration <= 30;
            @endphp
            <tr class="{{ $isExpired ? 'expired' : ($isExpiring ? 'expiring' : '') }}">
                <td class="text-left">
                    @if($record->userDriverDetail)
                        <span class="driver-name">
                            @if($isExpired)
                                <span class="danger-icon">⛔</span>
                            @elseif($isExpiring)
                                <span class="alert-icon">⚠</span>
                            @endif
                            {{ $record->userDriverDetail->full_name }}
                        </span>
                        @if($record->userDriverDetail->user)
                            <br><span class="driver-email">{{ $record->userDriverDetail->user->email }}</span>
                        @endif
                    @else
                        <span class="text-muted">N/A</span>
                    @endif
                </td>
                <td class="text-left">
                    {{ $record->medical_examiner_name ?? 'N/A' }}
                </td>
                <td class="text-center">
                    {{ $record->medical_examiner_registry_number ?? 'N/A' }}
                </td>
                <td class="text-center">
                    {{ $record->created_at ? $record->created_at->format('m/d/Y') : 'N/A' }}
                </td>
                <td class="text-center">
                    @if($record->medical_card_expiration_date)
                        <span class="font-bold">{{ $record->medical_card_expiration_date->format('m/d/Y') }}</span>
                        @if($isExpired)
                            <br><span class="expired-text">EXPIRED</span>
                        @elseif($isExpiring)
                            <br><span class="expiring-text">{{ $daysUntilExpiration }} days left</span>
                        @endif
                    @else
                        <span class="text-muted">N/A</span>
                    @endif
                </td>
                <td class="text-center">
                    @if($isExpired)
                        <span class="badge badge-expired">Expired</span>
                    @elseif($isExpiring)
                        <span class="badge badge-expiring">Expiring</span>
                    @else
                        <span class="badge badge-valid">Valid</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="no-data">
                    No medical records found matching the specified filters
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

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
