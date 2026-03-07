<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Emergency Repairs Report</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: "Segoe UI", Arial, sans-serif;
            font-size: 12px;
            color: #2c3e50;
            margin: 40px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            font-size: 22px;
            margin: 0;
            color: #dc3545;
        }
        .header p {
            font-size: 14px;
            color: #6c757d;
        }
        .summary {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }
        .card {
            flex: 1;
            min-width: 200px;
            background-color: #f1f3f5;
            border: 1px solid #ced4da;
            border-radius: 6px;
            padding: 15px;
        }
        .card p {
            margin: 5px 0;
            font-size: 13px;
        }
        .card strong {
            display: block;
            color: #343a40;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #dee2e6;
            padding: 8px 10px;
            font-size: 11px;
        }
        th {
            background-color: #e9ecef;
            text-align: left;
            color: #212529;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .status-completed {
            background-color: #28a745;
            color: #fff;
            padding: 4px 8px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: bold;
            display: inline-block;
        }
        .status-pending {
            background-color: #ffc107;
            color: #212529;
            padding: 4px 8px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: bold;
            display: inline-block;
        }
        .status-in-progress {
            background-color: #17a2b8;
            color: #fff;
            padding: 4px 8px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: bold;
            display: inline-block;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            font-size: 10px;
            color: #6c757d;
            border-top: 1px solid #dee2e6;
            padding-top: 10px;
        }
        .total-cost {
            font-size: 16px;
            font-weight: bold;
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Emergency Repairs Report</h1>
        <p>EF Services Management System</p>
    </div>

    <div class="summary">
        <div class="card">
            <p><strong>Report Date:</strong> {{ $date }}</p>
            @if(count($filtros) > 0)
            <p><strong>Applied Filters:</strong> {{ implode(', ', $filtros) }}</p>
            @endif
        </div>
        <div class="card">
            <p><strong>Total Records:</strong> {{ $totalRecords }}</p>
            <p class="total-cost"><strong>Total Cost:</strong> ${{ number_format($totalCost, 2) }}</p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Repair Date</th>
                <th>Vehicle</th>
                <th>Carrier</th>
                <th>Repair Name</th>
                <th>Odometer</th>
                <th>Cost</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($repairs as $repair)
            <tr>
                <td>{{ $repair->repair_date ? $repair->repair_date->format('m/d/Y') : 'N/A' }}</td>
                <td>
                    {{ $repair->vehicle->company_unit_number ?? 'N/A' }}<br>
                    <small>{{ $repair->vehicle->make ?? '' }} {{ $repair->vehicle->model ?? '' }}</small>
                </td>
                <td>{{ $repair->vehicle->carrier->name ?? 'N/A' }}</td>
                <td style="max-width: 200px;">{{ \Illuminate\Support\Str::limit($repair->repair_name ?? 'N/A', 50) }}</td>
                <td>{{ $repair->odometer ? number_format($repair->odometer) . ' mi' : 'N/A' }}</td>
                <td>${{ number_format($repair->cost ?? 0, 2) }}</td>
                <td>
                    @if($repair->status == 'completed')
                        <span class="status-completed">Completed</span>
                    @elseif($repair->status == 'in_progress')
                        <span class="status-in-progress">In Progress</span>
                    @else
                        <span class="status-pending">Pending</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>This document was automatically generated. EF Services &copy; {{ date('Y') }}</p>
    </div>
</body>
</html>
