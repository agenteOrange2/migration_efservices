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

@section('content')
    <!-- Driver Information -->
    <div class="section-title">Driver Information</div>
    
    <div class="info-grid">
        <div class="info-row">
            <div class="info-label">Driver Name:</div>
            <div class="info-value">{{ $driver->user->name ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">License Number:</div>
            <div class="info-value">{{ $driver->primaryLicense->license_number ?? $driver->primaryLicense->license_number ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">License State:</div>
            <div class="info-value">{{ $driver->primaryLicense->state_of_issue ?? 'N/A' }}</div>
        </div>
        @if($dailyLog->vehicle)
        <div class="info-row">
            <div class="info-label">Vehicle:</div>
            <div class="info-value">{{ $dailyLog->vehicle->company_unit_number ?? $dailyLog->vehicle->make . ' ' . $dailyLog->vehicle->model }}</div>
        </div>
        @endif
    </div>

    <!-- Daily Summary -->
    <div class="section-title">Daily Summary</div>
    
    <table>
        <tr>
            <th>Status</th>
            <th>Total Time</th>
            <th>Percentage</th>
        </tr>
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
        <tr style="background-color: #f8fafc; font-weight: bold;">
            <td>Total</td>
            <td>{{ floor(array_sum($totals) / 60) }}h {{ array_sum($totals) % 60 }}m</td>
            <td>{{ round((array_sum($totals) / 1440) * 100, 1) }}%</td>
        </tr>
    </table>

    <!-- FMCSA Compliance Status -->
    <div class="section-title">FMCSA Compliance Status</div>
    
    <div class="info-grid">
        <div class="info-row">
            <div class="info-label">Daily Driving Limit (12h):</div>
            <div class="info-value">
                {{ floor($totals['driving'] / 60) }}h / 12h
                @if($totals['driving'] >= 720)
                    <span class="badge badge-danger">LIMIT REACHED</span>
                @elseif($totals['driving'] >= 660)
                    <span class="badge badge-warning">APPROACHING LIMIT</span>
                @else
                    <span class="badge badge-success">OK</span>
                @endif
            </div>
        </div>
        <div class="info-row">
            <div class="info-label">Duty Period (14h window):</div>
            <div class="info-value">
                @if($dailyLog->duty_period_start)
                    {{ $dailyLog->duty_period_start->format('H:i') }} - 
                    {{ $dailyLog->duty_period_end ? $dailyLog->duty_period_end->format('H:i') : 'Ongoing' }}
                    ({{ floor($dailyLog->duty_period_elapsed_minutes / 60) }}h {{ $dailyLog->duty_period_elapsed_minutes % 60 }}m)
                @else
                    Not started
                @endif
            </div>
        </div>
        <div class="info-row">
            <div class="info-label">30-Minute Break:</div>
            <div class="info-value">
                @if($dailyLog->thirty_minute_break_taken)
                    <span class="badge badge-success">COMPLETED</span>
                @else
                    <span class="badge badge-warning">NOT TAKEN</span>
                @endif
            </div>
        </div>
        <div class="info-row">
            <div class="info-label">10-Hour Reset:</div>
            <div class="info-value">
                @if($dailyLog->last_10_hour_reset_at)
                    {{ $dailyLog->last_10_hour_reset_at->format('M d, Y H:i') }}
                @else
                    Not recorded
                @endif
            </div>
        </div>
    </div>

    <!-- HOS Entries -->
    @if($hosEntries->isNotEmpty())
        <div class="section-title">Hours of Service Entries</div>
        
        <table>
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
                        <td style="font-size: 8pt;">{{ $entry->location_display }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="alert alert-warning">
            <strong>No HOS entries recorded for this date.</strong>
        </div>
    @endif

    <!-- Violations -->
    @if($violations && $violations->isNotEmpty())
        <div class="section-title">Violations</div>
        
        <div class="alert alert-danger">
            <strong>Warning:</strong> {{ $violations->count() }} violation(s) recorded for this date.
        </div>
        
        <table>
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
                            <span class="badge badge-danger">
                                {{ $violation->severity_name ?? $violation->violation_severity }}
                            </span>
                        </td>
                        <td>{{ $violation->violation_date->format('H:i') }}</td>
                        <td style="font-size: 8pt;">{{ $violation->description ?? 'N/A' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
    
    <div class="certification-text">
        I certify that my record of duty status for this 24-hour period is true and correct, and that I have complied with all applicable FMCSA regulations.
    </div>
@endsection
