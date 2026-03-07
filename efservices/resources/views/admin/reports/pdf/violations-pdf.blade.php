<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Violations Report</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: "Segoe UI", Arial, sans-serif; font-size: 12px; color: #2c3e50; margin: 40px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { font-size: 22px; margin: 0; color: #dc3545; }
        .header p { font-size: 14px; color: #6c757d; }
        .summary { display: flex; justify-content: space-between; margin-bottom: 30px; flex-wrap: wrap; gap: 15px; }
        .card { flex: 1; min-width: 150px; background-color: #f1f3f5; border: 1px solid #ced4da; border-radius: 6px; padding: 15px; }
        .card p { margin: 5px 0; font-size: 13px; }
        .card strong { display: block; color: #343a40; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #dee2e6; padding: 8px 10px; font-size: 11px; }
        th { background-color: #e9ecef; text-align: left; color: #212529; }
        tr:nth-child(even) { background-color: #f8f9fa; }
        .severity-minor { background-color: #ffc107; color: #000; padding: 3px 8px; border-radius: 10px; font-size: 10px; }
        .severity-moderate { background-color: #fd7e14; color: #fff; padding: 3px 8px; border-radius: 10px; font-size: 10px; }
        .severity-critical { background-color: #dc3545; color: #fff; padding: 3px 8px; border-radius: 10px; font-size: 10px; }
        .status-acknowledged { background-color: #28a745; color: #fff; padding: 3px 8px; border-radius: 10px; font-size: 10px; }
        .status-pending { background-color: #6c757d; color: #fff; padding: 3px 8px; border-radius: 10px; font-size: 10px; }
        .footer { text-align: center; margin-top: 40px; font-size: 10px; color: #6c757d; border-top: 1px solid #dee2e6; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>HOS Violations Report</h1>
        <p>EF Services Management System</p>
    </div>

    <div class="summary">
        <div class="card">
            <p><strong>Report Date:</strong> {{ $date }}</p>
            @if(count($filters) > 0)
            <p><strong>Filters:</strong> {{ implode(', ', $filters) }}</p>
            @endif
        </div>
        <div class="card">
            <p><strong>Total Violations:</strong> {{ $stats['total_violations'] }}</p>
            <p><strong>Acknowledged:</strong> {{ $stats['acknowledged_count'] }}</p>
        </div>
        <div class="card">
            <p><strong>Unacknowledged:</strong> {{ $stats['unacknowledged_count'] }}</p>
            <p><strong>Ack. Rate:</strong> {{ $stats['acknowledgment_rate'] }}%</p>
        </div>
        <div class="card">
            <p><strong>Minor:</strong> {{ $stats['by_severity']['minor'] ?? 0 }}</p>
            <p><strong>Moderate:</strong> {{ $stats['by_severity']['moderate'] ?? 0 }}</p>
            <p><strong>Critical:</strong> {{ $stats['by_severity']['critical'] ?? 0 }}</p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Driver</th>
                <th>Carrier</th>
                <th>Type</th>
                <th>Severity</th>
                <th>Hours Exceeded</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($violations as $violation)
            <tr>
                <td>{{ $violation->violation_date->format('m/d/Y') }}</td>
                <td>{{ $violation->driver?->full_name ?? 'N/A' }}</td>
                <td>{{ $violation->carrier?->name ?? 'N/A' }}</td>
                <td>{{ $violation->violation_type_name }}</td>
                <td><span class="severity-{{ $violation->violation_severity }}">{{ ucfirst($violation->violation_severity) }}</span></td>
                <td>{{ $violation->formatted_hours_exceeded }}</td>
                <td>
                    @if($violation->acknowledged)
                        <span class="status-acknowledged">Acknowledged</span>
                    @else
                        <span class="status-pending">Pending</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Generated: {{ $generatedAt }} | EF Services &copy; {{ date('Y') }}</p>
    </div>
</body>
</html>
