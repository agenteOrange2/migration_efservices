@extends('pdf.layouts.base')

@section('title', 'Trip Report - ' . ($trip->trip_number ?? 'Trip #' . $trip->id))

@section('document-title')
    Trip Report
@endsection

@section('document-subtitle')
    {{ $trip->trip_number ?? 'Trip #' . $trip->id }} | {{ $trip->completed_at ? $trip->completed_at->format('F d, Y') : 'In Progress' }}
@endsection

@section('company-info')
    <strong>{{ $trip->carrier->name ?? 'N/A' }}</strong><br>
    USDOT: {{ $trip->carrier->usdot_number ?? 'N/A' }}<br>
    MC: {{ $trip->carrier->mc_number ?? 'N/A' }}
@endsection

@section('styles')
    <style>
        .content {
            padding: 20px 30px;
        }
        
        .section-title {
            margin-top: 25px;
            margin-bottom: 15px;
            padding-top: 10px;
        }
        
        .info-grid {
            margin: 15px 0 20px 0;
        }
        
        .info-row {
            margin-bottom: 8px;
        }
        
        .info-label {
            padding: 8px 15px 8px 0;
        }
        
        .info-value {
            padding: 8px 0;
        }
        
        table {
            margin: 15px 0 20px 0;
        }
        
        th {
            padding: 10px 12px;
        }
        
        td {
            padding: 10px 12px;
        }
        
        .signature-box {
            margin: 25px 0;
            padding: 20px;
        }
        
        .alert {
            margin: 15px 0;
            padding: 15px;
        }
    </style>
@endsection

@section('content')
    <!-- Trip Information -->
    <div class="section-title">Trip Information</div>
    
    <div class="info-grid">
        <div class="info-row">
            <div class="info-label">Driver:</div>
            <div class="info-value">{{ implode(' ', array_filter([$trip->driver->user->name ?? 'N/A', $trip->driver->middle_name ?? '', $trip->driver->last_name ?? ''])) }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Vehicle:</div>
            <div class="info-value">{{ $trip->vehicle->company_unit_number ?? $trip->vehicle->make . ' ' . $trip->vehicle->model ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Status:</div>
            <div class="info-value">
                @if($trip->status === 'completed')
                    <span class="badge badge-success">Completed</span>
                @elseif($trip->status === 'in_progress')
                    <span class="badge badge-info">In Progress</span>
                @else
                    <span class="badge">{{ $trip->status_name }}</span>
                @endif
            </div>
        </div>
    </div>

    <!-- Route Information -->
    <div class="section-title">Route</div>
    
    <table>
        <tr>
            <th style="width: 15%;">Type</th>
            <th>Address</th>            
        </tr>
        <tr>
            <td><strong>Origin</strong></td>
            <td>{{ $trip->origin_address ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td><strong>Destination</strong></td>
            <td>{{ $trip->destination_address ?? 'N/A' }}</td>            
        </tr>
    </table>

    <!-- Time Information -->
    <div class="section-title">Time Details</div>
    
    <div class="info-grid">
        <div class="info-row">
            <div class="info-label">Scheduled Start:</div>
            <div class="info-value">{{ $trip->scheduled_start_date ? $trip->scheduled_start_date->format('M d, Y H:i') : 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Actual Start:</div>
            <div class="info-value">{{ $trip->actual_start_time ? $trip->actual_start_time->format('M d, Y H:i') : 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Actual End:</div>
            <div class="info-value">{{ $trip->actual_end_time ? $trip->actual_end_time->format('M d, Y H:i') : 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Total Duration:</div>
            <div class="info-value">
                @if($trip->actual_duration_minutes)
                    {{ floor($trip->actual_duration_minutes / 60) }}h {{ $trip->actual_duration_minutes % 60 }}m
                @else
                    N/A
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
                        <td>{{ $entry['status'] }}</td>
                        <td>{{ $entry['start_time'] }}</td>
                        <td>{{ $entry['end_time'] }}</td>
                        <td>{{ $entry['duration'] }}</td>
                        <td>{{ $entry['location'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- GPS Statistics -->
    @if($gpsStats)
        <div class="section-title">GPS Statistics</div>
        
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Total Distance:</div>
                <div class="info-value">{{ number_format($gpsStats['total_distance_miles'] ?? 0, 2) }} miles</div>
            </div>
            <div class="info-row">
                <div class="info-label">Average Speed:</div>
                <div class="info-value">{{ number_format($gpsStats['average_speed_mph'] ?? 0, 1) }} mph</div>
            </div>
            <div class="info-row">
                <div class="info-label">Max Speed:</div>
                <div class="info-value">{{ number_format($gpsStats['max_speed_mph'] ?? 0, 1) }} mph</div>
            </div>
            <div class="info-row">
                <div class="info-label">GPS Points:</div>
                <div class="info-value">{{ $gpsStats['total_points'] ?? 0 }}</div>
            </div>
        </div>
    @endif

    <!-- Violations -->
    @if($trip->violations && $trip->violations->isNotEmpty())
        <div class="section-title">Violations</div>
        
        <div class="alert alert-danger">
            <strong>Warning:</strong> This trip has {{ $trip->violations->count() }} violation(s) recorded.
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Severity</th>
                    <th>Date</th>
                    <th>Reference</th>
                </tr>
            </thead>
            <tbody>
                @foreach($trip->violations as $violation)
                    <tr>
                        <td>{{ $violation->violation_type_name ?? $violation->violation_type ?? 'N/A' }}</td>
                        <td>
                            <span class="badge badge-danger">
                                {{ $violation->severity_name ?? $violation->violation_severity ?? 'N/A' }}
                            </span>
                        </td>
                        <td>{{ $violation->violation_date ? $violation->violation_date->format('M d, Y') : 'N/A' }}</td>
                        <td>{{ $violation->fmcsa_rule_reference ?? 'N/A' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- Driver Notes -->
    @if($trip->driver_notes)
        <div class="section-title">Driver Notes</div>
        <div style="padding: 15px 20px; margin: 15px 0 20px 0; background-color: #f8fafc; border-left: 3px solid #64748b;">
            {{ $trip->driver_notes }}
        </div>
    @endif

    <!-- Signature -->
    <div class="section-title">Driver Certification</div>
    
    <div class="signature-box">
        @if($signatureData)
            <img src="{{ $signatureData }}" alt="Driver Signature" class="signature-image">
            <div style="margin-top: 10px; font-size: 9pt;">
                <strong>{{ implode(' ', array_filter([$trip->driver->user->name ?? 'Driver', $trip->driver->middle_name ?? '', $trip->driver->last_name ?? ''])) }}</strong><br>
                Signed on: {{ now()->format('F d, Y \a\t H:i') }}
            </div>
        @else
            <div style="color: #94a3b8; padding: 20px;">
                No signature provided
            </div>
        @endif
    </div>
    
    <div class="certification-text">
        I certify that this trip report is true and correct to the best of my knowledge and that I have complied with all applicable FMCSA regulations during this trip.
    </div>
@endsection
