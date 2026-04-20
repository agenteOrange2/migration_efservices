@extends('pdf.layouts.base')

@section('title', 'Daily HOS Log - ' . $date->format('F d, Y'))

@section('document-title')
    Daily Hours of Service Log
@endsection

@section('document-subtitle')
    {{ $date->format('l, F d, Y') }}
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
        margin: 0 0 16px 0;
        border-collapse: separate;
        border-spacing: 8px 0;
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

    .detail-card {
        border: 1px solid #dbe5f1;
        border-radius: 8px;
        padding: 12px 14px;
        background: #ffffff;
        margin-bottom: 14px;
    }

    .detail-card--soft {
        background: #f8fbff;
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

    .status-bar {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }

    .status-bar td {
        border: 1px solid #dbe5f1;
        padding: 8px 10px;
        font-size: 8.5pt;
    }

    .status-bar .name {
        font-weight: bold;
        color: #334155;
        width: 34%;
        background: #f8fafc;
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

    .totals-row td {
        background: #eef6ff !important;
        font-weight: bold;
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

    .signature-panel {
        margin-top: 16px;
        border: 1px solid #dbe5f1;
        border-radius: 8px;
        padding: 14px;
        background: #f8fbff;
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
        $totalMinutes = array_sum($totals);
        $vehicleLabel = $dailyLog->vehicle
            ? ($dailyLog->vehicle->company_unit_number ?: trim(($dailyLog->vehicle->year ?? '') . ' ' . ($dailyLog->vehicle->make ?? '') . ' ' . ($dailyLog->vehicle->model ?? '')))
            : 'Unassigned';
        $drivingState = $totals['driving'] >= 720 ? 'danger' : ($totals['driving'] >= 660 ? 'warning' : 'success');
        $breakState = $dailyLog->thirty_minute_break_taken ? 'success' : 'warning';
    @endphp

    <table class="summary-strip">
        <tr>
            <td width="25%">
                <div class="metric-label">Driver</div>
                <div class="metric-value" style="font-size: 12pt;">{{ $driverName ?: 'N/A' }}</div>
                <div class="metric-subtle">{{ $driver->primaryLicense->license_number ?? 'No primary license' }}</div>
            </td>
            <td width="25%">
                <div class="metric-label">Vehicle</div>
                <div class="metric-value" style="font-size: 12pt;">{{ $vehicleLabel }}</div>
                <div class="metric-subtle">{{ $driver->primaryLicense->state_of_issue ?? 'N/A' }}</div>
            </td>
            <td width="25%">
                <div class="metric-label">Driving Time</div>
                <div class="metric-value">{{ floor($totals['driving'] / 60) }}h {{ $totals['driving'] % 60 }}m</div>
                <div class="metric-subtle">Daily 12-hour limit</div>
            </td>
            <td width="25%">
                <div class="metric-label">Recorded Time</div>
                <div class="metric-value">{{ floor($totalMinutes / 60) }}h {{ $totalMinutes % 60 }}m</div>
                <div class="metric-subtle">{{ $hosEntries->count() }} HOS entries</div>
            </td>
        </tr>
    </table>

    <div class="section-title">Driver & Duty Snapshot</div>

    <table class="mini-table">
        <tr>
            <td width="33.33%">
                <div class="mini-item">
                    <div class="mini-item-label">Duty Window</div>
                    <div class="mini-item-value">
                        @if($dailyLog->duty_period_start)
                            {{ $dailyLog->duty_period_start->format('H:i') }} -
                            {{ $dailyLog->duty_period_end ? $dailyLog->duty_period_end->format('H:i') : 'Ongoing' }}
                        @else
                            Not started
                        @endif
                    </div>
                </div>
            </td>
            <td width="33.33%">
                <div class="mini-item">
                    <div class="mini-item-label">30-Minute Break</div>
                    <div class="mini-item-value">
                        <span class="pill pill-{{ $breakState }}">{{ $dailyLog->thirty_minute_break_taken ? 'Completed' : 'Pending' }}</span>
                    </div>
                </div>
            </td>
            <td width="33.33%">
                <div class="mini-item">
                    <div class="mini-item-label">Latest 10-Hour Reset</div>
                    <div class="mini-item-value">{{ $dailyLog->last_10_hour_reset_at ? $dailyLog->last_10_hour_reset_at->format('M d, Y H:i') : 'Not recorded' }}</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="detail-card detail-card--soft">
        <div class="metric-label" style="margin-bottom: 8px;">FMCSA Compliance Review</div>
        <table class="status-bar">
            <tr>
                <td class="name">Daily Driving Limit</td>
                <td>
                    {{ floor($totals['driving'] / 60) }}h {{ $totals['driving'] % 60 }}m of 12h
                    <span class="pill pill-{{ $drivingState }}" style="margin-left: 8px;">
                        @if($drivingState === 'danger')
                            Limit Reached
                        @elseif($drivingState === 'warning')
                            Near Limit
                        @else
                            Within Limit
                        @endif
                    </span>
                </td>
            </tr>
            <tr>
                <td class="name">Duty Period Elapsed</td>
                <td>
                    @if($dailyLog->duty_period_start)
                        {{ floor($dailyLog->duty_period_elapsed_minutes / 60) }}h {{ $dailyLog->duty_period_elapsed_minutes % 60 }}m tracked in the 14-hour window
                    @else
                        No duty period recorded yet
                    @endif
                </td>
            </tr>
            <tr>
                <td class="name">Driver Certification</td>
                <td>Daily record prepared for FMCSA compliance review and archive.</td>
            </tr>
        </table>
    </div>

    <div class="section-title">Duty Time Breakdown</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Status</th>
                <th>Total Time</th>
                <th>Percent of Day</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Driving</strong></td>
                <td>{{ floor($totals['driving'] / 60) }}h {{ $totals['driving'] % 60 }}m</td>
                <td>{{ round(($totals['driving'] / 1440) * 100, 1) }}%</td>
            </tr>
            <tr>
                <td><strong>On Duty - Not Driving</strong></td>
                <td>{{ floor($totals['on_duty_not_driving'] / 60) }}h {{ $totals['on_duty_not_driving'] % 60 }}m</td>
                <td>{{ round(($totals['on_duty_not_driving'] / 1440) * 100, 1) }}%</td>
            </tr>
            <tr>
                <td><strong>Off Duty</strong></td>
                <td>{{ floor($totals['off_duty'] / 60) }}h {{ $totals['off_duty'] % 60 }}m</td>
                <td>{{ round(($totals['off_duty'] / 1440) * 100, 1) }}%</td>
            </tr>
            <tr class="totals-row">
                <td>Total Recorded</td>
                <td>{{ floor($totalMinutes / 60) }}h {{ $totalMinutes % 60 }}m</td>
                <td>{{ round(($totalMinutes / 1440) * 100, 1) }}%</td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">Hours of Service Entries</div>
    @if($hosEntries->isNotEmpty())
        <table class="data-table">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                    <th>Duration</th>
                    <th>Location</th>
                </tr>
            </thead>
            <tbody>
                @foreach($hosEntries as $entry)
                    <tr>
                        <td>{{ $entry->status_name }}</td>
                        <td>{{ $entry->start_time->format('H:i') }}</td>
                        <td>{{ $entry->end_time ? $entry->end_time->format('H:i') : 'Ongoing' }}</td>
                        <td>{{ $entry->formatted_duration }}</td>
                        <td>{{ $entry->location_display ?: 'N/A' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="alert alert-warning">
            <strong>No HOS entries recorded for this date.</strong>
        </div>
    @endif

    @if($violations && $violations->isNotEmpty())
        <div class="section-title">Violations</div>
        <div class="alert alert-danger">
            <strong>Attention:</strong> {{ $violations->count() }} violation(s) were recorded for this date.
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Severity</th>
                    <th>Time</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                @foreach($violations as $violation)
                    <tr>
                        <td>{{ $violation->violation_type_name ?? $violation->violation_type }}</td>
                        <td>
                            <span class="pill pill-danger">{{ $violation->severity_name ?? $violation->violation_severity }}</span>
                        </td>
                        <td>{{ $violation->violation_date->format('H:i') }}</td>
                        <td>{{ $violation->description ?? 'N/A' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="signature-panel">
        <div class="signature-label">Driver Certification</div>        
        <div class="certification-text">
            I certify that my record of duty status for this 24-hour period is true and correct, and that I have complied with all applicable FMCSA regulations.
        </div>
    </div>
@endsection
