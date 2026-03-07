<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>HOS Daily Report - {{ $date->format('Y-m-d') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .info-section {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .info-box {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .info-box h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
        }
        .info-box p {
            margin: 3px 0;
        }
        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 100px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .totals-section {
            margin-bottom: 20px;
        }
        .totals-section h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
        }
        .totals-grid {
            display: table;
            width: 100%;
        }
        .total-box {
            display: table-cell;
            width: 33%;
            text-align: center;
            padding: 10px;
            border: 1px solid #ccc;
        }
        .total-box .label {
            font-size: 10px;
            color: #666;
        }
        .total-box .value {
            font-size: 16px;
            font-weight: bold;
        }
        .violations-section {
            margin-bottom: 20px;
        }
        .violation-item {
            background-color: #fee;
            border: 1px solid #f00;
            padding: 10px;
            margin-bottom: 5px;
        }
        .signature-section {
            margin-top: 30px;
            border-top: 1px solid #ccc;
            padding-top: 20px;
        }
        .signature-box {
            border: 1px solid #ccc;
            padding: 10px;
            min-height: 50px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        .manual-entry {
            background-color: #fff3cd;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Hours of Service - Daily Log</h1>
        <p>{{ $driver->carrier->name ?? 'N/A' }}</p>
        <p>Date: {{ $date->format('l, F j, Y') }}</p>
    </div>

    <div class="info-section">
        <div class="info-box">
            <h3>Driver Information</h3>
            <p><span class="info-label">Name:</span> {{ $driver->full_name }}</p>
            <p><span class="info-label">ID:</span> {{ $driver->id }}</p>
            <p><span class="info-label">Phone:</span> {{ $driver->phone ?? 'N/A' }}</p>
        </div>
        <div class="info-box">
            <h3>Vehicle Information</h3>
            @if($vehicle)
                <p><span class="info-label">Unit #:</span> {{ $vehicle->company_unit_number ?? 'N/A' }}</p>
                <p><span class="info-label">Make/Model:</span> {{ $vehicle->make }} {{ $vehicle->model }}</p>
                <p><span class="info-label">VIN:</span> {{ $vehicle->vin ?? 'N/A' }}</p>
            @else
                <p>No vehicle assigned</p>
            @endif
        </div>
    </div>

    <h3>Status Entries</h3>
    <table>
        <thead>
            <tr>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Status</th>
                <th>Duration</th>
                <th>Location</th>
            </tr>
        </thead>
        <tbody>
            @forelse($entries as $entry)
                <tr class="{{ $entry['is_manual'] ? 'manual-entry' : '' }}">
                    <td>{{ $entry['start_time'] }}</td>
                    <td>{{ $entry['end_time'] }}</td>
                    <td>{{ $entry['status'] }}{{ $entry['is_manual'] ? ' *' : '' }}</td>
                    <td>{{ $entry['duration'] }}</td>
                    <td>{{ $entry['location'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center;">No entries recorded for this date</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    @if($entries->contains('is_manual', true))
        <p style="font-size: 10px; color: #666;">* Manual entry added by carrier/admin</p>
    @endif

    <div class="totals-section">
        <h3>Daily Totals</h3>
        <div class="totals-grid">
            <div class="total-box">
                <div class="label">Driving Time</div>
                <div class="value">{{ $totals['driving_formatted'] }}</div>
            </div>
            <div class="total-box">
                <div class="label">On Duty (Not Driving)</div>
                <div class="value">{{ $totals['on_duty_formatted'] }}</div>
            </div>
            <div class="total-box">
                <div class="label">Off Duty</div>
                <div class="value">{{ $totals['off_duty_formatted'] }}</div>
            </div>
        </div>
    </div>

    @if($violations->count() > 0)
        <div class="violations-section">
            <h3>Violations</h3>
            @foreach($violations as $violation)
                <div class="violation-item">
                    <strong>{{ $violation->violation_type_name }}</strong><br>
                    Exceeded by: {{ $violation->formatted_hours_exceeded }}
                </div>
            @endforeach
        </div>
    @endif

    <div class="signature-section">
        <h3>Driver Signature</h3>
        <div class="signature-box">
            @if($signature)
                <img src="{{ $signature }}" alt="Driver Signature" style="max-height: 50px;">
                <p style="font-size: 10px; color: #666;">Signed at: {{ $signed_at?->format('Y-m-d H:i') }}</p>
            @else
                <p style="color: #999;">Not signed</p>
            @endif
        </div>
    </div>

    <div class="footer">
        <p>Generated on {{ $generated_at->format('Y-m-d H:i:s') }}</p>
        <p>This is a local HOS record for non-interstate operations only. Not for federal ELD compliance.</p>
    </div>
</body>
</html>
