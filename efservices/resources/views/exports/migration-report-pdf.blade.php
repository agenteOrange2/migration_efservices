<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Driver Migration Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            color: #1e40af;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0 0;
            color: #6b7280;
        }
        .stats-grid {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        .stat-box {
            display: table-cell;
            width: 25%;
            padding: 10px;
            text-align: center;
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
        }
        .stat-box .value {
            font-size: 24px;
            font-weight: bold;
            color: #1e40af;
        }
        .stat-box .label {
            font-size: 11px;
            color: #6b7280;
            text-transform: uppercase;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #1e40af;
            margin: 20px 0 10px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #e5e7eb;
            padding: 8px;
            text-align: left;
        }
        th {
            background: #f3f4f6;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
        }
        tr:nth-child(even) {
            background: #f9fafb;
        }
        .status-completed {
            color: #059669;
            font-weight: bold;
        }
        .status-rolled_back {
            color: #d97706;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }
        .filters-info {
            background: #eff6ff;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 11px;
        }
        .filters-info strong {
            color: #1e40af;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Driver Migration Report</h1>
        <p>Generated on {{ $generatedAt }}</p>
    </div>

    @if(!empty($filters))
    <div class="filters-info">
        <strong>Applied Filters:</strong>
        @if(!empty($filters['date_from'])) From: {{ $filters['date_from'] }} @endif
        @if(!empty($filters['date_to'])) To: {{ $filters['date_to'] }} @endif
        @if(!empty($filters['status'])) Status: {{ ucfirst($filters['status']) }} @endif
    </div>
    @endif

    <div class="stats-grid">
        <div class="stat-box">
            <div class="value">{{ $statistics['total_migrations'] }}</div>
            <div class="label">Total Migrations</div>
        </div>
        <div class="stat-box">
            <div class="value">{{ $statistics['completed_migrations'] }}</div>
            <div class="label">Completed</div>
        </div>
        <div class="stat-box">
            <div class="value">{{ $statistics['rolled_back_migrations'] }}</div>
            <div class="label">Rolled Back</div>
        </div>
        <div class="stat-box">
            <div class="value">{{ $statistics['rollback_rate'] }}%</div>
            <div class="label">Rollback Rate</div>
        </div>
    </div>

    <div class="section-title">Migration Records</div>
    <table>
        <thead>
            <tr>
                <th>Driver</th>
                <th>From Carrier</th>
                <th>To Carrier</th>
                <th>Date</th>
                <th>Reason</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($migrations as $migration)
            <tr>
                <td>{{ $migration->driverUser->name ?? 'Unknown' }}</td>
                <td>{{ $migration->sourceCarrier->name ?? 'Unknown' }}</td>
                <td>{{ $migration->targetCarrier->name ?? 'Unknown' }}</td>
                <td>{{ $migration->migrated_at->format('M j, Y') }}</td>
                <td>{{ $migration->reason ?? 'N/A' }}</td>
                <td class="status-{{ $migration->status }}">
                    {{ ucfirst(str_replace('_', ' ', $migration->status)) }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align: center;">No migration records found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        This report was automatically generated. For questions, contact your system administrator.
    </div>
</body>
</html>
