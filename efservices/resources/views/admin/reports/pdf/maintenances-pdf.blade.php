<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Maintenances Report</title>
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
            color: #1d3557;
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
        .status-overdue {
            background-color: #dc3545;
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
            color: #1d3557;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Maintenances Report</h1>
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
                <th>Service Date</th>
                <th>Vehicle</th>
                <th>Carrier</th>
                <th>Tasks</th>
                <th>Vendor</th>
                <th>Cost</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($maintenances as $maintenance)
            <tr>
                <td>{{ $maintenance->service_date ? $maintenance->service_date->format('m/d/Y') : 'N/A' }}</td>
                <td>
                    {{ $maintenance->vehicle->company_unit_number ?? 'N/A' }}<br>
                    <small>{{ $maintenance->vehicle->make ?? '' }} {{ $maintenance->vehicle->model ?? '' }}</small>
                </td>
                <td>{{ $maintenance->vehicle->carrier->name ?? 'N/A' }}</td>
                <td style="max-width: 200px;">{{ \Illuminate\Support\Str::limit($maintenance->service_tasks ?? 'N/A', 50) }}</td>
                <td>{{ $maintenance->vendor_mechanic ?? 'N/A' }}</td>
                <td>${{ number_format($maintenance->cost ?? 0, 2) }}</td>
                <td>
                    @if($maintenance->status)
                        <span class="status-completed">Completed</span>
                    @elseif($maintenance->isOverdue())
                        <span class="status-overdue">Overdue</span>
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
