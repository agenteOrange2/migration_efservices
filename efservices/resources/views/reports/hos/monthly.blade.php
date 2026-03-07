<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>HOS Monthly Report - {{ $month_name }} {{ $year }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
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
            font-size: 13px;
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
            padding: 6px;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
            font-size: 10px;
        }
        .totals-section {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        .totals-section h3 {
            margin: 0 0 10px 0;
            font-size: 13px;
        }
        .totals-grid {
            display: table;
            width: 100%;
        }
        .total-box {
            display: table-cell;
            width: 25%;
            text-align: center;
            padding: 10px;
            border: 1px solid #ccc;
        }
        .total-box .label {
            font-size: 9px;
            color: #666;
        }
        .total-box .value {
            font-size: 14px;
            font-weight: bold;
        }
        .violations-section {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        .violation-day {
            background-color: #fee;
            border: 1px solid #f00;
            padding: 8px;
            margin-bottom: 5px;
        }
        .violation-day .date {
            font-weight: bold;
        }
        .has-violation {
            background-color: #fee;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        .summary-stats {
            margin-bottom: 20px;
        }
        .summary-stats table {
            width: auto;
        }
        .summary-stats td {
            padding: 5px 15px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Hours of Service - Monthly Report</h1>
        <p>{{ $driver->carrier->name ?? 'N/A' }}</p>
        <p>{{ $month_name }} {{ $year }}</p>
    </div>

    <div class="info-section">
        <div class="info-box">
            <h3>Driver Information</h3>
            <p><span class="info-label">Name:</span> {{ $driver->full_name }}</p>
            <p><span class="info-label">ID:</span> {{ $driver->id }}</p>
            <p><span class="info-label">Phone:</span> {{ $driver->phone ?? 'N/A' }}</p>
        </div>
        <div class="info-box">
            <h3>Report Period</h3>
            <p><span class="info-label">From:</span> {{ $start_date->format('F j, Y') }}</p>
            <p><span class="info-label">To:</span> {{ $end_date->format('F j, Y') }}</p>
            @if($vehicle)
                <p><span class="info-label">Vehicle:</span> {{ $vehicle->company_unit_number ?? $vehicle->make }}</p>
            @endif
        </div>
    </div>

    <div class="totals-section">
        <h3>Monthly Totals</h3>
        <div class="totals-grid">
            <div class="total-box">
                <div class="label">Total Driving</div>
                <div class="value">{{ $totals['driving_formatted'] }}</div>
            </div>
            <div class="total-box">
                <div class="label">Total On Duty</div>
                <div class="value">{{ $totals['on_duty_formatted'] }}</div>
            </div>
            <div class="total-box">
                <div class="label">Total Off Duty</div>
                <div class="value">{{ $totals['off_duty_formatted'] }}</div>
            </div>
            <div class="total-box">
                <div class="label">Days Worked</div>
                <div class="value">{{ $totals['days_worked'] }}</div>
            </div>
        </div>
    </div>

    <div class="summary-stats">
        <table>
            <tr>
                <td><strong>Days with Violations:</strong></td>
                <td>{{ $totals['days_with_violations'] }}</td>
            </tr>
        </table>
    </div>

    <h3>Daily Breakdown</h3>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Day</th>
                <th>Driving</th>
                <th>On Duty</th>
                <th>Off Duty</th>
                <th>Violations</th>
            </tr>
        </thead>
        <tbody>
            @forelse($daily_breakdown as $day)
                <tr class="{{ $day['has_violations'] ? 'has-violation' : '' }}">
                    <td>{{ $day['date'] }}</td>
                    <td>{{ $day['day_name'] }}</td>
                    <td>{{ $day['driving'] }}</td>
                    <td>{{ $day['on_duty'] }}</td>
                    <td>{{ $day['off_duty'] }}</td>
                    <td>{{ $day['has_violations'] ? 'Yes' : '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center;">No data recorded for this month</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($violation_days->count() > 0)
        <div class="violations-section">
            <h3>Violation Details</h3>
            @foreach($violation_days as $day)
                <div class="violation-day">
                    <span class="date">{{ $day['date'] }}</span>
                    <ul style="margin: 5px 0; padding-left: 20px;">
                        @foreach($day['violations'] as $v)
                            <li>{{ $v['type'] }} - Exceeded by {{ $v['exceeded'] }}</li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    @endif

    <div class="footer">
        <p>Generated on {{ $generated_at->format('Y-m-d H:i:s') }}</p>
        <p>This is a local HOS record for non-interstate operations only. Not for federal ELD compliance.</p>
    </div>
</body>
</html>
