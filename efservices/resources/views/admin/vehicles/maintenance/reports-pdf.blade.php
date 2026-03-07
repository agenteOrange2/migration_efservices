<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Mantenimientos</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            color: #333;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .stats {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        .stat-item {
            display: table-cell;
            width: 25%;
            text-align: center;
            padding: 15px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
        }
        .stat-value {
            font-size: 18px;
            font-weight: bold;
            color: #007bff;
        }
        .stat-label {
            font-size: 11px;
            color: #666;
            margin-top: 5px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .table th,
        .table td {
            border: 1px solid #dee2e6;
            padding: 8px;
            text-align: left;
        }
        .table th {
            background-color: #f8f9fa;
            font-weight: bold;
            font-size: 11px;
        }
        .table td {
            font-size: 10px;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
        }
        .badge-success {
            background-color: #28a745;
            color: white;
        }
        .badge-secondary {
            background-color: #6c757d;
            color: white;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #dee2e6;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Maintenance Report</h1>
        <p>Generated at {{ now()->format('m/d/Y H:i:s') }}</p>
        @if($period == 'custom' && $startDate && $endDate)
            <p>Period: {{ \Carbon\Carbon::parse($startDate)->format('m/d/Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('m/d/Y') }}</p>
        @else
            <p>Period: {{ ucfirst($period) }}</p>
        @endif
    </div>

    <div class="stats">
        <div class="stat-item">
            <div class="stat-value">{{ $totalMaintenances }}</div>
            <div class="stat-label">Total Maintenance</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">{{ $vehiclesServiced }}</div>
            <div class="stat-label">Vehicles Serviced</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">${{ number_format($totalCost, 2) }}</div>
            <div class="stat-label">Total Cost</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">${{ number_format($avgCostPerVehicle, 2) }}</div>
            <div class="stat-label">Average per Vehicle</div>
        </div>
    </div>

    @if($maintenances->count() > 0)
        <table class="table">
            <thead>
                <tr>
                    <th class="text-center">Fecha</th>
                    <th>Vehicle</th>
                    <th>Service Type</th>
                    <th>Description</th>
                    <th class="text-center">Cost</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($maintenances as $maintenance)
                    <tr>
                        <td class="text-center">{{ $maintenance->service_date ? \Carbon\Carbon::parse($maintenance->service_date)->format('m/d/Y') : 'N/A' }}</td>
                        <td>{{ $maintenance->vehicle->make }} {{ $maintenance->vehicle->model }} ({{ $maintenance->vehicle->year }})</td>
                        <td>{{ $maintenance->service_tasks }}</td>
                        <td>{{ $maintenance->notes ?? 'No description' }}</td>
                        <td class="text-right">${{ number_format($maintenance->cost, 2) }}</td>
                        <td class="text-center">
                            <span class="badge {{ $maintenance->status ? 'badge-success' : 'badge-secondary' }}">
                                {{ $maintenance->status ? 'Completed' : 'Pending' }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div style="text-align: center; padding: 50px; color: #666;">
            <p>No maintenance records found with the applied filters.</p>
        </div>
    @endif

    <div class="footer">
        <p>This report was generated automatically by the Vehicle Management System. © {{ date('Y') }} EFCTS. All rights reserved.</p>
    </div>
</body>
</html>