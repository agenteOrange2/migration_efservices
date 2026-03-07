@extends('pdf.layouts.base')

@section('title', 'Document Monthly - ' . $monthName)

@section('document-title')
    DRIVERS DAILY LOG REPORT
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
    .fmcsa-notice {
        font-size: 8pt;
        border: 1px solid #333;
        padding: 8px;
        margin-bottom: 15px;
        background-color: #f8fafc;
    }
    
    .fmcsa-notice-title {
        font-weight: bold;
        font-size: 9pt;
        text-transform: uppercase;
        margin-bottom: 5px;
    }
    
    .fmcsa-notice ul {
        margin-left: 15px;
        margin-top: 5px;
    }
    
    .intermittent-box {
        border: 1px solid #333;
        padding: 8px;
        margin-bottom: 15px;
        background-color: #e0f2fe;
        font-size: 8pt;
    }
    
    .intermittent-title {
        font-weight: bold;
        font-size: 9pt;
        text-transform: uppercase;
        margin-bottom: 5px;
    }
    
    .monthly-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 8pt;
    }
    
    .monthly-table th {
        background-color: #1e40af;
        color: white;
        padding: 6px 4px;
        text-align: center;
        font-weight: bold;
        font-size: 7pt;
        border: 1px solid #1e40af;
    }
    
    .monthly-table td {
        padding: 4px;
        border: 1px solid #cbd5e1;
        text-align: center;
        font-size: 8pt;
        height: 18px;
    }
    
    .monthly-table tr:nth-child(even) {
        background-color: #f8fafc;
    }
    
    .day-cell {
        font-weight: bold;
        background-color: #f1f5f9 !important;
        width: 30px;
    }
    
    .time-cell {
        width: 60px;
    }
    
    .hours-cell {
        width: 55px;
    }
    
    .truck-cell {
        width: 70px;
    }
    
    .hq-cell {
        width: 100px;
        text-align: left !important;
        font-size: 7pt;
    }
    
    .totals-row {
        background-color: #dbeafe !important;
        font-weight: bold;
    }
    
    .totals-row td {
        border: 2px solid #1e40af;
    }
</style>
@endsection

@section('content')
    <!-- FMCSA Notice -->
    <table style="width: 100%; border: none; margin-bottom: 10px;">
        <tr>
            <td style="border: none; width: 70%; vertical-align: top;">
                <div class="fmcsa-notice">
                    <div class="fmcsa-notice-title">DRIVERS MAY PREPARE THIS REPORT INSTEAD OF "DRIVERS DAILY LOG" IF THE FOLLOWING APPLIES:</div>
                    <ul>
                        <li>Operates within 100 air-mile radius for CDL or 150 mile radius for non CDL drivers.</li>
                        <li>Returns to headquarters and is released from work within 12 consecutive hours.</li>
                        <li>At least 8 consecutive hours off duty separate each 12 hours of duty.</li>
                    </ul>
                </div>
            </td>
            <td style="border: none; width: 30%; vertical-align: top;">
                <div class="intermittent-box">
                    <div class="intermittent-title">INTERMITTENT DRIVERS</div>
                    Shall complete this form for 7 days preceding any day driving is performed. This includes the preceding month.
                </div>
            </td>
        </tr>
    </table>

    <!-- Driver Information -->
    <div class="section-title">Driver Information</div>
    
    <div class="info-grid">
        <div class="info-row">
            <div class="info-label">Driver Name:</div>
            <div class="info-value">{{ $driver->user->name ?? 'N/A' }} {{ $driver->middle_name ?? '' }} {{ $driver->last_name ?? '' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">License Number:</div>
            <div class="info-value">{{ $driver->primaryLicense->license_number ?? $driver->primaryLicense->license_number ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">License State:</div>
            <div class="info-value">{{ $driver->primaryLicense->state_of_issue ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Report Period:</div>
            <div class="info-value">{{ $monthName }}</div>
        </div>
    </div>


    <!-- Monthly Log Table -->
    <div class="section-title">Daily Breakdown</div>
    
    <table class="monthly-table">
        <thead>
            <tr>
                <th class="day-cell">Date</th>
                <th class="time-cell">Start Time<br>"All Duty"</th>
                <th class="time-cell">End Time<br>"All Duty"</th>
                <th class="hours-cell">Total<br>Hours</th>
                <th class="hours-cell">Driving<br>Hours</th>
                <th class="truck-cell">Truck<br>Number</th>
                <th class="hq-cell">Headquarters</th>
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
                        <td class="time-cell">{{ $dayData['start_time'] }}</td>
                        <td class="time-cell">{{ $dayData['end_time'] }}</td>
                        <td class="hours-cell">{{ $dayData['total_hours'] }}</td>
                        <td class="hours-cell">{{ $dayData['driving_hours'] }}</td>
                        <td class="truck-cell">{{ $dayData['truck_number'] }}</td>
                        <td class="hq-cell">{{ $dayData['headquarters'] }}</td>
                    @else
                        <td colspan="6" style="text-align: center; color: #64748b; font-style: italic;">Off Duty</td>
                    @endif
                </tr>


            @endfor
            
            <!-- Totals Row -->
            <tr class="totals-row">
                <td class="day-cell">TOTAL</td>
                <td class="time-cell">-</td>
                <td class="time-cell">-</td>
                <td class="hours-cell">{{ $totals['total_hours'] }}</td>
                <td class="hours-cell">{{ $totals['driving_hours'] }}</td>
                <td class="truck-cell">-</td>
                <td class="hq-cell">-</td>
            </tr>
        </tbody>
    </table>

    <!-- Summary Statistics -->
    <div class="section-title">Monthly Summary</div>
    
    <table>
        <tr>
            <th>Metric</th>
            <th>Value</th>
        </tr>
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
    </table>
    
    <div class="certification-text">
        I hereby certify that the entries recorded above are true and correct to the best of my knowledge and that I have complied with all applicable Federal Motor Carrier Safety Regulations regarding hours of service.
    </div>

    <!-- Compliance Notes -->
    <div class="section-title" style="margin-top: 20px;">Compliance Notes</div>
    
    <div style="padding: 10px; background-color: #f8fafc; border-left: 3px solid #64748b; font-size: 8pt;">
        <p><strong>100/150 Air-Mile Radius Exemption (395.1(e)):</strong></p>
        <ul style="margin-left: 20px; margin-top: 5px;">
            <li>Driver must return to work reporting location within 12 hours</li>
            <li>Driver must have at least 8 consecutive hours off duty before next on-duty period</li>
            <li>CDL drivers: 100 air-mile radius | Non-CDL drivers: 150 mile radius</li>
            <li>Driver must maintain time records showing start/end times for 6 months</li>
        </ul>
    </div>
@endsection
