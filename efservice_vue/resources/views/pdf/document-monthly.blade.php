@extends('pdf.layouts.base')

@section('title', 'Document Monthly - ' . $monthName)

@section('document-title')
    Drivers Daily Log Report
@endsection

@section('document-subtitle')
    {{ $monthName }} - Intermittent Driver Record
@endsection

@section('company-info')
    <strong>{{ $driver->carrier->name ?? 'N/A' }}</strong><br>
    USDOT: {{ $driver->carrier->dot_number ?? 'N/A' }}<br>
    MC: {{ $driver->carrier->mc_number ?? 'N/A' }}
@endsection

@section('styles')
<style>
    .info-banner {
        width: 100%;
        border-collapse: separate;
        border-spacing: 10px 0;
        margin-bottom: 14px;
    }

    .info-banner td {
        border: 1px solid #dbe5f1;
        border-radius: 8px;
        padding: 12px;
        vertical-align: top;
    }

    .notice-panel {
        background: #f8fbff;
    }

    .notice-title {
        font-size: 9pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #0f3c79;
        margin-bottom: 6px;
    }

    .notice-panel ul {
        margin: 8px 0 0 18px;
    }

    .notice-panel li {
        margin-bottom: 4px;
        font-size: 8.5pt;
        color: #334155;
    }

    .highlight-panel {
        background: #eef6ff;
    }

    .mini-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 10px 8px;
        margin: 0;
    }

    .mini-table td {
        border: none;
        padding: 0;
    }

    .mini-item {
        border: 1px solid #dbe5f1;
        border-radius: 8px;
        padding: 10px 12px;
        background: #fff;
        min-height: 58px;
    }

    .mini-item-label {
        font-size: 8pt;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #64748b;
        margin-bottom: 5px;
    }

    .mini-item-value {
        font-size: 10pt;
        font-weight: bold;
        color: #0f172a;
    }

    .monthly-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 12px;
    }

    .monthly-table th {
        background: #0f3c79;
        color: #fff;
        padding: 7px 5px;
        text-align: center;
        font-weight: bold;
        font-size: 7.5pt;
        border: 1px solid #0f3c79;
        text-transform: uppercase;
    }

    .monthly-table td {
        padding: 5px 4px;
        border: 1px solid #dbe5f1;
        text-align: center;
        font-size: 8pt;
        color: #1e293b;
        height: 20px;
    }

    .monthly-table tr:nth-child(even) td {
        background: #f8fbff;
    }

    .day-cell {
        width: 30px;
        font-weight: bold;
        background: #eef6ff !important;
    }

    .hq-cell {
        text-align: left !important;
        font-size: 7.4pt !important;
    }

    .totals-row td {
        background: #eef6ff !important;
        font-weight: bold;
    }

    .summary-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 12px;
    }

    .summary-table th {
        background: #f8fafc;
        color: #334155;
        border: 1px solid #dbe5f1;
        padding: 8px;
        text-align: left;
        font-size: 8.5pt;
        text-transform: uppercase;
    }

    .summary-table td {
        border: 1px solid #dbe5f1;
        padding: 8px;
        font-size: 8.8pt;
    }

    .signature-panel {
        margin-top: 16px;
        border: 1px solid #dbe5f1;
        border-radius: 8px;
        padding: 14px;
        background: #ffffff;
    }

    .signature-label {
        font-size: 8pt;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #64748b;
        margin-bottom: 8px;
    }

    .signature-image {
        max-width: 220px;
        max-height: 60px;
    }

    .signature-line {
        border-top: 1px solid #94a3b8;
        margin-top: 28px;
        padding-top: 6px;
        font-size: 8pt;
        color: #64748b;
        width: 240px;
    }
</style>
@endsection

@section('content')
    @php
        $driverName = trim(implode(' ', array_filter([
            $driver->user->name ?? null,
            $driver->middle_name ?? null,
            $driver->last_name ?? null,
        ])));
    @endphp

    <table class="info-banner">
        <tr>
            <td width="68%" class="notice-panel">
                <div class="notice-title">Driver radius exemption reminder</div>
                <ul>
                    <li>Operates within a 100 air-mile radius for CDL or 150 air-mile radius for non-CDL drivers.</li>
                    <li>Returns to headquarters and is released from duty within 12 consecutive hours.</li>
                    <li>Receives at least 8 consecutive hours off duty between on-duty periods.</li>
                </ul>
            </td>
            <td width="32%" class="highlight-panel">
                <div class="notice-title">Intermittent driver use</div>
                <div style="font-size: 8.5pt; color: #334155; line-height: 1.45;">
                    Complete this monthly record for the 7 days preceding any day driving is performed, including the prior month when required.
                </div>
            </td>
        </tr>
    </table>

    <div class="section-title">Driver Information</div>
    <table class="mini-table">
        <tr>
            <td width="25%">
                <div class="mini-item">
                    <div class="mini-item-label">Driver</div>
                    <div class="mini-item-value">{{ $driverName ?: 'N/A' }}</div>
                </div>
            </td>
            <td width="25%">
                <div class="mini-item">
                    <div class="mini-item-label">License Number</div>
                    <div class="mini-item-value">{{ $driver->primaryLicense->license_number ?? 'N/A' }}</div>
                </div>
            </td>
            <td width="25%">
                <div class="mini-item">
                    <div class="mini-item-label">License State</div>
                    <div class="mini-item-value">{{ $driver->primaryLicense->state_of_issue ?? 'N/A' }}</div>
                </div>
            </td>
            <td width="25%">
                <div class="mini-item">
                    <div class="mini-item-label">Report Period</div>
                    <div class="mini-item-value">{{ $monthName }}</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="section-title">Daily Record Sheet</div>
    <table class="monthly-table">
        <thead>
            <tr>
                <th class="day-cell">Date</th>
                <th>Start Time<br>All Duty</th>
                <th>End Time<br>All Duty</th>
                <th>Total<br>Hours</th>
                <th>Driving<br>Hours</th>
                <th>Truck<br>Number</th>
                <th>Headquarters</th>
            </tr>
        </thead>
        <tbody>
            @for($day = 1; $day <= $daysInMonth; $day++)
                @php
                    $dayData = $dailyData[$day] ?? null;
                    $hasData = $dayData && !empty($dayData['start_time']);
                @endphp
                <tr>
                    <td class="day-cell">{{ $day }}</td>
                    @if($hasData)
                        <td>{{ $dayData['start_time'] }}</td>
                        <td>{{ $dayData['end_time'] }}</td>
                        <td>{{ $dayData['total_hours'] }}</td>
                        <td>{{ $dayData['driving_hours'] }}</td>
                        <td>{{ $dayData['truck_number'] ?: '—' }}</td>
                        <td class="hq-cell">{{ $dayData['headquarters'] ?: 'N/A' }}</td>
                    @else
                        <td colspan="6" style="font-style: italic; color: #64748b;">Off Duty</td>
                    @endif
                </tr>
            @endfor
            <tr class="totals-row">
                <td class="day-cell">Total</td>
                <td>—</td>
                <td>—</td>
                <td>{{ $totals['total_hours'] }}</td>
                <td>{{ $totals['driving_hours'] }}</td>
                <td>—</td>
                <td class="hq-cell">—</td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">Monthly Summary</div>
    <table class="summary-table">
        <thead>
            <tr>
                <th>Metric</th>
                <th>Value</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Days Worked</strong></td>
                <td>{{ $summary['days_worked'] }} days</td>
            </tr>
            <tr>
                <td><strong>Total Driving Time</strong></td>
                <td>{{ $summary['total_driving_formatted'] }}</td>
            </tr>
            <tr>
                <td><strong>Total On-Duty Time</strong></td>
                <td>{{ $summary['total_on_duty_formatted'] }}</td>
            </tr>
            <tr>
                <td><strong>Total Off-Duty Time</strong></td>
                <td>{{ $summary['total_off_duty_formatted'] }}</td>
            </tr>
            <tr>
                <td><strong>Average Daily Hours</strong></td>
                <td>{{ $summary['avg_daily_hours'] }}</td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">Compliance Notes</div>
    <div class="notice-panel" style="border: 1px solid #dbe5f1; border-radius: 8px; padding: 12px;">
        <ul>
            <li>Driver must return to the work reporting location within 12 hours.</li>
            <li>Driver must have at least 8 consecutive hours off duty before the next on-duty period.</li>
            <li>CDL drivers: 100 air-mile radius. Non-CDL drivers: 150 air-mile radius.</li>
            <li>Time records must be retained for 6 months.</li>
        </ul>
    </div>

    <div class="signature-panel">
        <div class="signature-label">Driver Certification</div>
        <div class="certification-text">
            I hereby certify that the entries recorded above are true and correct to the best of my knowledge and that I have complied with all applicable Federal Motor Carrier Safety Regulations regarding hours of service.
        </div>
    </div>
@endsection
