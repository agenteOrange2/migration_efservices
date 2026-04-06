<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Emergency Repair Record</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #0f172a;
            margin: 0;
            padding: 24px;
        }

        .header {
            border-bottom: 2px solid #1e3a8a;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }

        .header h1 {
            font-size: 22px;
            margin: 0 0 4px 0;
        }

        .muted {
            color: #475569;
            font-size: 11px;
        }

        .section {
            margin-bottom: 18px;
        }

        .section-title {
            font-size: 13px;
            font-weight: bold;
            margin: 0 0 8px 0;
            color: #1e3a8a;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #cbd5e1;
            padding: 8px;
            vertical-align: top;
            text-align: left;
        }

        th {
            width: 28%;
            background: #eff6ff;
            font-weight: 600;
        }

        .box {
            border: 1px solid #cbd5e1;
            padding: 10px 12px;
            min-height: 52px;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    @php
        $formatDate = fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('m/d/Y') : 'N/A';
        $statusLabel = str($repair->status ?: 'pending')->replace('_', ' ')->title()->toString();
    @endphp

    <div class="header">
        <h1>Emergency Repair Record</h1>
        <div class="muted">Generated {{ now()->format('m/d/Y h:i A') }}</div>
    </div>

    <div class="section">
        <div class="section-title">Vehicle Information</div>
        <table>
            <tr>
                <th>Vehicle</th>
                <td>{{ trim(implode(' ', array_filter([$vehicle->year, $vehicle->make, $vehicle->model]))) ?: 'N/A' }}</td>
            </tr>
            <tr>
                <th>Carrier</th>
                <td>{{ $vehicle->carrier?->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Unit Number</th>
                <td>{{ $vehicle->company_unit_number ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>VIN</th>
                <td>{{ $vehicle->vin ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Repair Details</div>
        <table>
            <tr>
                <th>Repair Name</th>
                <td>{{ $repair->repair_name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Repair Date</th>
                <td>{{ $formatDate($repair->repair_date) }}</td>
            </tr>
            <tr>
                <th>Status</th>
                <td>{{ $statusLabel }}</td>
            </tr>
            <tr>
                <th>Cost</th>
                <td>{{ $repair->cost !== null ? '$' . number_format((float) $repair->cost, 2) : 'N/A' }}</td>
            </tr>
            <tr>
                <th>Odometer</th>
                <td>{{ $repair->odometer ? number_format((int) $repair->odometer) : 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Description</div>
        <div class="box">{{ $repair->description ?: 'No description provided.' }}</div>
    </div>

    <div class="section">
        <div class="section-title">Notes</div>
        <div class="box">{{ $repair->notes ?: 'No notes available.' }}</div>
    </div>
</body>
</html>
