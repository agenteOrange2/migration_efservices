@extends('pdf.layouts.base')

@section('title', 'Monthly HOS Summary - ' . $monthName)

@section('document-title')
    Monthly Hours of Service Summary
@endsection

@section('document-subtitle')
    {{ $monthName }}
@endsection

@section('company-info')
    <strong>{{ $driver->carrier->name ?? 'N/A' }}</strong><br>
    USDOT: {{ $driver->carrier->usdot_number ?? 'N/A' }}<br>
    MC: {{ $driver->carrier->mc_number ?? 'N/A' }}
@endsection

@section('styles')
<style>
    .summary-strip {
        width: 100%;
        border-collapse: separate;
        border-spacing: 8px 0;
        margin-bottom: 16px;
    }

    .summary-strip td {
        border: 1px solid #dbe5f1;
        border-radius: 8px;
        background: #f8fbff;
        padding: 10px 12px;
        vertical-align: top;
    }

    .metric-label {
        font-size: 8pt;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #64748b;
        margin-bottom: 4px;
    }

    .metric-value {
        font-size: 15pt;
        font-weight: bold;
        color: #0f172a;
    }

    .metric-subtle {
        font-size: 8pt;
        color: #64748b;
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
        vertical-align: top;
    }

    .mini-item {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 10px 12px;
        min-height: 58px;
        background: #fff;
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

    .data-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 12px;
    }

    .data-table th {
        background: #0f3c79;
        color: #fff;
        border: 1px solid #0f3c79;
        padding: 9px 8px;
        font-size: 8.5pt;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .data-table td {
        border: 1px solid #dbe5f1;
        padding: 8px;
        font-size: 8.8pt;
        color: #1e293b;
        vertical-align: top;
    }

    .data-table tr:nth-child(even) td {
        background: #f8fbff;
    }

    .pill {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 999px;
        font-size: 7.5pt;
        font-weight: bold;
    }

    .pill-success {
        background: #dcfce7;
        color: #166534;
    }

    .pill-warning {
        background: #fef3c7;
        color: #92400e;
    }

    .pill-danger {
        background: #fee2e2;
        color: #991b1b;
    }

    .panel {
        border: 1px solid #dbe5f1;
        border-radius: 8px;
        padding: 12px 14px;
        background: #f8fbff;
        margin-top: 12px;
    }

    .panel-title {
        font-size: 9pt;
        font-weight: bold;
        color: #0f3c79;
        margin-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .panel ul {
        margin: 8px 0 0 18px;
    }

    .panel li {
        margin-bottom: 4px;
        font-size: 8.8pt;
        color: #334155;
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
        $averageDrivingMinutes = $monthlyTotals['days_worked'] > 0 ? intval($monthlyTotals['total_driving_minutes'] / $monthlyTotals['days_worked']) : 0;
        $averageOnDutyMinutes = $monthlyTotals['days_worked'] > 0 ? intval($monthlyTotals['total_on_duty_minutes'] / $monthlyTotals['days_worked']) : 0;
    @endphp

    <table class="summary-strip">
        <tr>
            <td width="25%">
                <div class="metric-label">Driver</div>
                <div class="metric-value" style="font-size: 12pt;">{{ $driverName ?: 'N/A' }}</div>
                <div class="metric-subtle">{{ $driver->primaryLicense->license_number ?? 'No primary license' }}</div>
            </td>
            <td width="25%">
                <div class="metric-label">Days Worked</div>
                <div class="metric-value">{{ $monthlyTotals['days_worked'] }}</div>
                <div class="metric-subtle">Days with recorded driving</div>
            </td>
            <td width="25%">
                <div class="metric-label">Driving Time</div>
                <div class="metric-value">{{ floor($monthlyTotals['total_driving_minutes'] / 60) }}h {{ $monthlyTotals['total_driving_minutes'] % 60 }}m</div>
                <div class="metric-subtle">Total for {{ $monthName }}</div>
            </td>
            <td width="25%">
                <div class="metric-label">Violations</div>
                <div class="metric-value">{{ $monthlyTotals['days_with_violations'] }}</div>
                <div class="metric-subtle">Days requiring follow-up</div>
            </td>
        </tr>
    </table>

    <div class="section-title">Monthly Snapshot</div>
    <table class="mini-table">
        <tr>
            <td width="33.33%">
                <div class="mini-item">
                    <div class="mini-item-label">Average Driving per Worked Day</div>
                    <div class="mini-item-value">{{ floor($averageDrivingMinutes / 60) }}h {{ $averageDrivingMinutes % 60 }}m</div>
                </div>
            </td>
            <td width="33.33%">
                <div class="mini-item">
                    <div class="mini-item-label">Average On-Duty per Worked Day</div>
                    <div class="mini-item-value">{{ floor($averageOnDutyMinutes / 60) }}h {{ $averageOnDutyMinutes % 60 }}m</div>
                </div>
            </td>
            <td width="33.33%">
                <div class="mini-item">
                    <div class="mini-item-label">Monthly Compliance</div>
                    <div class="mini-item-value">
                        @if($monthlyTotals['days_with_violations'] > 0)
                            <span class="pill pill-danger">Attention Required</span>
                        @else
                            <span class="pill pill-success">Clean Record</span>
                        @endif
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <div class="section-title">Monthly Totals</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Metric</th>
                <th>Total</th>
                <th>Average per Worked Day</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Total Driving Time</strong></td>
                <td>{{ floor($monthlyTotals['total_driving_minutes'] / 60) }}h {{ $monthlyTotals['total_driving_minutes'] % 60 }}m</td>
                <td>{{ floor($averageDrivingMinutes / 60) }}h {{ $averageDrivingMinutes % 60 }}m</td>
            </tr>
            <tr>
                <td><strong>Total On-Duty Time</strong></td>
                <td>{{ floor($monthlyTotals['total_on_duty_minutes'] / 60) }}h {{ $monthlyTotals['total_on_duty_minutes'] % 60 }}m</td>
                <td>{{ floor($averageOnDutyMinutes / 60) }}h {{ $averageOnDutyMinutes % 60 }}m</td>
            </tr>
            <tr>
                <td><strong>Days Worked</strong></td>
                <td colspan="2">{{ $monthlyTotals['days_worked'] }} day(s)</td>
            </tr>
            <tr>
                <td><strong>Days with Violations</strong></td>
                <td colspan="2">
                    {{ $monthlyTotals['days_with_violations'] }} day(s)
                    @if($monthlyTotals['days_with_violations'] > 0)
                        <span class="pill pill-danger" style="margin-left: 8px;">Attention Required</span>
                    @else
                        <span class="pill pill-success" style="margin-left: 8px;">Clean Record</span>
                    @endif
                </td>
            </tr>
        </tbody>
    </table>

    @if(!empty($weeklyBreakdown))
        <div class="section-title">Weekly Breakdown</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Week</th>
                    <th>Driving Time</th>
                    <th>Total Duty Time</th>
                    <th>Compliance</th>
                </tr>
            </thead>
            <tbody>
                @foreach($weeklyBreakdown as $week)
                    <tr>
                        <td>{{ $week['week_start'] }} - {{ $week['week_end'] }}</td>
                        <td>{{ floor($week['total_driving_minutes'] / 60) }}h {{ $week['total_driving_minutes'] % 60 }}m</td>
                        <td>{{ floor($week['total_duty_minutes'] / 60) }}h {{ $week['total_duty_minutes'] % 60 }}m</td>
                        <td>
                            @if($week['total_duty_minutes'] >= 3600)
                                <span class="pill pill-danger">Over 60h</span>
                            @elseif($week['total_duty_minutes'] >= 3300)
                                <span class="pill pill-warning">Near Limit</span>
                            @else
                                <span class="pill pill-success">OK</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if($dailyLogs->isNotEmpty())
        <div class="section-title">Daily Breakdown</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Driving</th>
                    <th>On Duty</th>
                    <th>Off Duty</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($dailyLogs as $log)
                    <tr>
                        <td>{{ $log->date->format('M d, D') }}</td>
                        <td>{{ floor($log->total_driving_minutes / 60) }}h {{ $log->total_driving_minutes % 60 }}m</td>
                        <td>{{ floor($log->total_on_duty_minutes / 60) }}h {{ $log->total_on_duty_minutes % 60 }}m</td>
                        <td>{{ floor($log->total_off_duty_minutes / 60) }}h {{ $log->total_off_duty_minutes % 60 }}m</td>
                        <td>
                            @if($log->has_violations)
                                <span class="pill pill-danger">Violation</span>
                            @elseif($log->total_driving_minutes >= 720)
                                <span class="pill pill-warning">Max Driving</span>
                            @elseif($log->total_driving_minutes > 0)
                                <span class="pill pill-success">Worked</span>
                            @else
                                <span class="pill">Off</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="alert alert-warning">
            <strong>No daily logs recorded for this month.</strong>
        </div>
    @endif

    @if($violations && $violations->isNotEmpty())
        <div class="section-title">Violations Summary</div>
        <div class="alert alert-danger">
            <strong>Attention:</strong> {{ $violations->count() }} violation(s) were recorded during this month.
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Severity</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                @foreach($violations as $violation)
                    <tr>
                        <td>{{ $violation->violation_date->format('M d') }}</td>
                        <td>{{ $violation->violation_type_name ?? $violation->violation_type }}</td>
                        <td><span class="pill pill-danger">{{ $violation->severity_name ?? $violation->violation_severity }}</span></td>
                        <td>{{ $violation->description ?? 'N/A' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="panel">
        <div class="panel-title">Compliance Notes</div>
        <ul>
            <li>Maximum 12 hours driving per day.</li>
            <li>Maximum 14 hours duty period window.</li>
            <li>30-minute break required after 8 hours driving.</li>
            <li>10-hour rest period required between duty periods.</li>
            <li>Maximum 60 hours on duty in 7 consecutive days.</li>
        </ul>
    </div>

    <div class="signature-panel">
        <div class="signature-label">Driver Certification</div>        
        <div class="certification-text">
            I certify that my record of duty status for this monthly period is true and correct, and that I have complied with all applicable FMCSA regulations.
        </div>
    </div>
@endsection
