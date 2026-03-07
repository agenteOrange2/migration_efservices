<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Vehicle Report - {{ $carrier->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }
        .header h2 {
            margin: 5px 0;
            font-size: 16px;
            color: #666;
        }
        .meta-info {
            margin-bottom: 20px;
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
        }
        .meta-info table {
            width: 100%;
        }
        .meta-info td {
            padding: 3px 0;
        }
        .meta-info .label {
            font-weight: bold;
            width: 120px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            font-size: 10px;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .status-active {
            color: #28a745;
            font-weight: bold;
        }
        .status-out-of-service {
            color: #dc3545;
            font-weight: bold;
        }
        .status-suspended {
            color: #ffc107;
            font-weight: bold;
        }
        .text-center {
            text-align: center;
        }
        .page-break {
            page-break-after: always;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 50px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Vehicle Report</h1>
        <h2>{{ $carrier->name }}</h2>
    </div>

    <div class="meta-info">
        <table>
            <tr>
                <td class="label">Carrier:</td>
                <td>{{ $carrier->name }}</td>
                <td class="label">Export Date:</td>
                <td>{{ $exportDate }}</td>
            </tr>
            <tr>
                <td class="label">Total Vehicles:</td>
                <td>{{ $vehicles->count() }}</td>
                <td class="label">Exported By:</td>
                <td>{{ $exportedBy }}</td>
            </tr>
            @if(!empty($filters))
            <tr>
                <td class="label">Applied Filters:</td>
                <td colspan="3">
                    @if(isset($filters['search']) && $filters['search'])
                        Search: "{{ $filters['search'] }}" |
                    @endif
                    @if(isset($filters['status']) && $filters['status'])
                        Status: {{ ucfirst(str_replace('_', ' ', $filters['status'])) }} |
                    @endif
                    @if(isset($filters['type']) && $filters['type'])
                        Type: {{ $filters['type'] }} |
                    @endif
                    @if(isset($filters['make']) && $filters['make'])
                        Make: {{ $filters['make'] }} |
                    @endif
                </td>
            </tr>
            @endif
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th>Unit #</th>
                <th>Make/Model</th>
                <th>Year</th>
                <th>Type</th>
                <th>VIN</th>
                <th>Registration</th>
                <th>Assigned Driver</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($vehicles as $vehicle)
            <tr>
                <td>{{ $vehicle->company_unit_number ?: 'N/A' }}</td>
                <td>{{ $vehicle->make }} {{ $vehicle->model }}</td>
                <td class="text-center">{{ $vehicle->year }}</td>
                <td>{{ $vehicle->type ?: 'N/A' }}</td>
                <td style="font-family: monospace; font-size: 9px;">{{ $vehicle->vin }}</td>
                <td>
                    {{ $vehicle->registration_number }}
                    @if($vehicle->registration_expiration_date)
                        <br><small>Exp: {{ $vehicle->registration_expiration_date->format('m/d/Y') }}</small>
                    @endif
                </td>
                <td>
                    @if ($vehicle->activeDriverAssignment && $vehicle->activeDriverAssignment->driver && $vehicle->activeDriverAssignment->driver->user)
                        {{ $vehicle->activeDriverAssignment->driver->user->name }}
                        <br><small>{{ ucfirst(str_replace('_', ' ', $vehicle->activeDriverAssignment->driver_type ?? 'company_driver')) }}</small>
                    @elseif($vehicle->driver && $vehicle->driver->user)
                        {{ $vehicle->driver->user->name }}
                        <br><small>Company Driver</small>
                    @else
                        Not assigned
                    @endif
                </td>
                <td class="text-center">
                    @if ($vehicle->out_of_service)
                        <span class="status-out-of-service">Out Of Service</span>
                    @elseif($vehicle->suspended)
                        <span class="status-suspended">Suspended</span>
                    @else
                        <span class="status-active">Active</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Generated on {{ $exportDate }} | {{ $carrier->name }} Vehicle Report | Page <span class="pagenum"></span></p>
    </div>
</body>
</html>