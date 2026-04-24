<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Medical Records Report - {{ $carrier->name }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #0284c7; padding-bottom: 15px; }
        .header h1 { margin: 0; color: #0284c7; font-size: 24px; }
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
        th { background: #0284c7; color: white; padding: 10px 8px; text-align: left; font-size: 11px; }
        td { padding: 8px; border-bottom: 1px solid #e2e8f0; font-size: 11px; vertical-align: middle; }
        tr:nth-child(even) td { background: #f8fafc; }
        tr.expired td { background: #fef2f2; }
        tr.expiring td { background: #fef9c3; }
        .badge { padding: 3px 8px; border-radius: 3px; font-size: 10px; font-weight: bold; }
        .badge-valid { background: #dcfce7; color: #166534; }
        .badge-expiring { background: #fef3c7; color: #92400e; }
        .badge-expired { background: #fee2e2; color: #991b1b; }
        .footer { margin-top: 20px; text-align: center; font-size: 10px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Medical Records Report</h1>
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
            @if(!empty($filters['expiration_status'])) Status: {{ ucfirst(str_replace('_', ' ', $filters['expiration_status'])) }}. @endif
            @if(!empty($filters['date_from'])) From: {{ $filters['date_from'] }}. @endif
            @if(!empty($filters['date_to'])) To: {{ $filters['date_to'] }}. @endif
        </div>
    @endif

    <div class="stats-grid">
        <div class="stat-box">
            <div class="value">{{ $stats['total'] ?? 0 }}</div>
            <div class="label">Total Records</div>
        </div>
        <div class="stat-box success">
            <div class="value">{{ $stats['valid'] ?? 0 }}</div>
            <div class="label">Valid</div>
        </div>
        <div class="stat-box warning">
            <div class="value">{{ $stats['expiring_soon'] ?? 0 }}</div>
            <div class="label">Expiring Soon</div>
        </div>
        <div class="stat-box danger">
            <div class="value">{{ $stats['expired'] ?? 0 }}</div>
            <div class="label">Expired</div>
        </div>
        <div class="stat-box success">
            <div class="value">{{ $stats['percentage_valid'] ?? 0 }}%</div>
            <div class="label">Compliance Rate</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Driver Name</th>
                <th>Examiner Name</th>
                <th>Registry #</th>
                <th>Exam Date</th>
                <th>Expiration Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($medicalRecords as $record)
            @php
                $isExpired = $record->medical_card_expiration_date && $record->medical_card_expiration_date->isPast();
                $daysLeft = $record->medical_card_expiration_date ? (int) now()->diffInDays($record->medical_card_expiration_date, false) : null;
                $isExpiring = !$isExpired && $daysLeft !== null && $daysLeft >= 0 && $daysLeft <= 30;
            @endphp
            <tr class="{{ $isExpired ? 'expired' : ($isExpiring ? 'expiring' : '') }}">
                <td>
                    <strong>{{ $record->userDriverDetail?->full_name ?? 'N/A' }}</strong>
                    @if($isExpired) <br><small style="color:#dc2626;">Expired</small>
                    @elseif($isExpiring) <br><small style="color:#ca8a04;">⚠ {{ $daysLeft }} days left</small>
                    @endif
                </td>
                <td>{{ $record->medical_examiner_name ?? 'N/A' }}</td>
                <td>{{ $record->medical_examiner_registry_number ?? 'N/A' }}</td>
                <td>{{ $record->created_at ? $record->created_at->format('m/d/Y') : 'N/A' }}</td>
                <td>
                    @if($record->medical_card_expiration_date)
                        <strong>{{ $record->medical_card_expiration_date->format('m/d/Y') }}</strong>
                    @else
                        N/A
                    @endif
                </td>
                <td>
                    @if($isExpired) <span class="badge badge-expired">Expired</span>
                    @elseif($isExpiring) <span class="badge badge-expiring">Expiring</span>
                    @else <span class="badge badge-valid">Valid</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align: center; padding: 20px; color: #94a3b8;">No medical records found matching the specified filters</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>{{ $carrier->name }} - Medical Records Report | &copy; {{ now()->format('Y') }} EFCTS. All rights reserved. | Confidential</p>
    </div>
</body>
</html>
