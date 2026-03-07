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
    </div>

    <!-- Monthly Summary -->
    <div class="section-title">Monthly Summary</div>
    
    <table>
        <tr>
            <th>Metric</th>
            <th>Total</th>
            <th>Average per Day</th>
        </tr>
        <tr>
            <td><strong>Total Driving Time</strong></td>
            <td>{{ floor($monthlyTotals['total_driving_minutes'] / 60) }}h {{ $monthlyTotals['total_driving_minutes'] % 60 }}m</td>
            <td>{{ $monthlyTotals['days_worked'] > 0 ? floor(($monthlyTotals['total_driving_minutes'] / $monthlyTotals['days_worked']) / 60) . 'h ' . (($monthlyTotals['total_driving_minutes'] / $monthlyTotals['days_worked']) % 60) . 'm' : '0h 0m' }}</td>
        </tr>
        <tr>
            <td><strong>Total On-Duty Time</strong></td>
            <td>{{ floor($monthlyTotals['total_on_duty_minutes'] / 60) }}h {{ $monthlyTotals['total_on_duty_minutes'] % 60 }}m</td>
            <td>{{ $monthlyTotals['days_worked'] > 0 ? floor(($monthlyTotals['total_on_duty_minutes'] / $monthlyTotals['days_worked']) / 60) . 'h ' . (($monthlyTotals['total_on_duty_minutes'] / $monthlyTotals['days_worked']) % 60) . 'm' : '0h 0m' }}</td>
        </tr>
        <tr>
            <td><strong>Days Worked</strong></td>
            <td colspan="2">{{ $monthlyTotals['days_worked'] }} days</td>
        </tr>
        <tr>
            <td><strong>Days with Violations</strong></td>
            <td colspan="2">
                {{ $monthlyTotals['days_with_violations'] }} days
                @if($monthlyTotals['days_with_violations'] > 0)
                    <span class="badge badge-danger">ATTENTION REQUIRED</span>
                @else
                    <span class="badge badge-success">CLEAN RECORD</span>
                @endif
            </td>
        </tr>
    </table>

    <!-- Weekly Breakdown -->
    @if(!empty($weeklyBreakdown))
        <div class="section-title">Weekly Breakdown</div>
        
        <table>
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
                                <span class="badge badge-danger">OVER 60h</span>
                            @elseif($week['total_duty_minutes'] >= 3300)
                                <span class="badge badge-warning">NEAR LIMIT</span>
                            @else
                                <span class="badge badge-success">OK</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- Daily Breakdown -->
    @if($dailyLogs->isNotEmpty())
        <div class="section-title">Daily Breakdown</div>
        
        <table>
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
                                <span class="badge badge-danger">VIOLATION</span>
                            @elseif($log->total_driving_minutes >= 720)
                                <span class="badge badge-warning">MAX DRIVING</span>
                            @elseif($log->total_driving_minutes > 0)
                                <span class="badge badge-success">WORKED</span>
                            @else
                                <span class="badge">OFF</span>
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

    <!-- Violations Summary -->
    @if($violations && $violations->isNotEmpty())
        <div class="section-title">Violations Summary</div>
        
        <div class="alert alert-danger">
            <strong>Warning:</strong> {{ $violations->count() }} violation(s) recorded this month.
        </div>
        
        <table>
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
                        <td>
                            <span class="badge badge-danger">
                                {{ $violation->severity_name ?? $violation->violation_severity }}
                            </span>
                        </td>
                        <td style="font-size: 8pt;">{{ $violation->description ?? 'N/A' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- Compliance Notes -->
    <div class="section-title">Compliance Notes</div>
    
    <div style="padding: 10px; background-color: #f8fafc; border-left: 3px solid #64748b; font-size: 9pt;">
        <p><strong>FMCSA Regulations Summary:</strong></p>
        <ul style="margin-left: 20px; margin-top: 5px;">
            <li>Maximum 12 hours driving per day</li>
            <li>Maximum 14 hours duty period (window)</li>
            <li>30-minute break required after 8 hours driving</li>
            <li>10-hour rest period required between duty periods</li>
            <li>Maximum 60 hours on duty in 7 consecutive days</li>
        </ul>
    </div>
    
    <div class="certification-text">
        I certify that my record of duty status for this monthly period is true and correct, and that I have complied with all applicable FMCSA regulations.
    </div>
@endsection
