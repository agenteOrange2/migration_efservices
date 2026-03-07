@extends('pdf.layouts.base')

@section('title', 'Pre-Trip Vehicle Inspection Report')

@section('document-title')
    Pre-Trip Vehicle Inspection Report
@endsection

@section('document-subtitle')
    {{ $trip->trip_number ?? 'Trip #' . $trip->id }} | {{ $trip->actual_start_time ? $trip->actual_start_time->format('F d, Y') : 'N/A' }}
@endsection

@section('company-info')
    <strong>{{ $trip->carrier->name ?? 'N/A' }}</strong><br>
    USDOT: {{ $trip->carrier->usdot_number ?? 'N/A' }}<br>
    MC: {{ $trip->carrier->mc_number ?? 'N/A' }}
@endsection

@section('styles')
<style>
    .checklist-grid {
        display: table;
        width: 100%;
        margin: 10px 0;
    }
    .checklist-row {
        display: table-row;
    }
    .checklist-cell {
        display: table-cell;
        padding: 3px 5px;
        font-size: 8pt;
        border-bottom: 1px solid #e2e8f0;
        vertical-align: middle;
    }
    .check-icon {
        color: #16a34a;
        font-weight: bold;
    }
    .x-icon {
        color: #dc2626;
        font-weight: bold;
    }
    .inspection-section {
        margin-bottom: 15px;
    }
    .two-column {
        display: table;
        width: 100%;
    }
    .two-column > div {
        display: table-cell;
        width: 50%;
        vertical-align: top;
        padding-right: 10px;
    }
</style>
@endsection

@section('content')
    <!-- Driver & Vehicle Information -->
    <div class="section-title">Driver & Vehicle Information</div>
    
    <div class="info-grid">
        <div class="info-row">
            <div class="info-label">Driver Name:</div>
            <div class="info-value">{{ $driverName }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Vehicle:</div>
            <div class="info-value">{{ $trip->vehicle->company_unit_number ?? ($trip->vehicle->make . ' ' . $trip->vehicle->model) ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">VIN:</div>
            <div class="info-value">{{ $trip->vehicle->vin ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Inspection Date/Time:</div>
            <div class="info-value">{{ $trip->actual_start_time ? $trip->actual_start_time->format('M d, Y \a\t H:i') : 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Trailer Attached:</div>
            <div class="info-value">{{ $trip->has_trailer ? 'Yes' : 'No' }}</div>
        </div>
    </div>

    <!-- Trip Route -->
    <div class="section-title">Trip Route</div>
    <div class="info-grid">
        <div class="info-row">
            <div class="info-label">Origin:</div>
            <div class="info-value">{{ $trip->origin_address ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Destination:</div>
            <div class="info-value">{{ $trip->destination_address ?? 'N/A' }}</div>
        </div>
    </div>

    <!-- Tractor/Truck Inspection Checklist -->
    <div class="section-title">Tractor/Truck Inspection Checklist</div>
    
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">✓</th>
                <th style="width: 30%;">Item</th>
                <th style="width: 5%;">✓</th>
                <th style="width: 30%;">Item</th>
                <th style="width: 5%;">✓</th>
                <th style="width: 25%;">Item</th>
            </tr>
        </thead>
        <tbody>
            @php
                $tractorItems = config('inspection.tractor_items');
                $tractorKeys = array_keys($tractorItems);
                $checkedTractor = $inspectionData['tractor'] ?? [];
                $chunks = array_chunk($tractorKeys, 3);
            @endphp
            @foreach($chunks as $row)
                <tr>
                    @foreach($row as $key)
                        <td style="text-align: center;">
                            @if(in_array($key, $checkedTractor))
                                <span class="check-icon">✓</span>
                            @else
                                <span class="x-icon">✗</span>
                            @endif
                        </td>
                        <td>{{ $tractorItems[$key] }}</td>
                    @endforeach
                    @for($i = count($row); $i < 3; $i++)
                        <td></td><td></td>
                    @endfor
                </tr>
            @endforeach
        </tbody>
    </table>

    @if(!empty($inspectionData['other_tractor']))
        <div style="margin-top: 5px; padding: 8px; background-color: #f8fafc; border-left: 3px solid #3b82f6;">
            <strong>Other (Tractor):</strong> {{ $inspectionData['other_tractor'] }}
        </div>
    @endif

    <!-- Trailer Inspection Checklist (if applicable) -->
    @if($trip->has_trailer)
        <div class="section-title">Trailer Inspection Checklist</div>
        
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">✓</th>
                    <th style="width: 28%;">Item</th>
                    <th style="width: 5%;">✓</th>
                    <th style="width: 28%;">Item</th>
                    <th style="width: 5%;">✓</th>
                    <th style="width: 29%;">Item</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $trailerItems = config('inspection.trailer_items');
                    $trailerKeys = array_keys($trailerItems);
                    $checkedTrailer = $inspectionData['trailer'] ?? [];
                    $trailerChunks = array_chunk($trailerKeys, 3);
                @endphp
                @foreach($trailerChunks as $row)
                    <tr>
                        @foreach($row as $key)
                            <td style="text-align: center;">
                                @if(in_array($key, $checkedTrailer))
                                    <span class="check-icon">✓</span>
                                @else
                                    <span class="x-icon">✗</span>
                                @endif
                            </td>
                            <td>{{ $trailerItems[$key] }}</td>
                        @endforeach
                        @for($i = count($row); $i < 3; $i++)
                            <td></td><td></td>
                        @endfor
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if(!empty($inspectionData['other_trailer']))
            <div style="margin-top: 5px; padding: 8px; background-color: #f8fafc; border-left: 3px solid #3b82f6;">
                <strong>Other (Trailer):</strong> {{ $inspectionData['other_trailer'] }}
            </div>
        @endif
    @endif

    <!-- Remarks -->
    @if(!empty($inspectionData['remarks']) || !empty($trip->pre_trip_remarks))
        <div class="section-title">Remarks / Defects Found</div>
        <div style="padding: 10px; background-color: #fef3c7; border-left: 3px solid #f59e0b; margin: 10px 0;">
            {{ $inspectionData['remarks'] ?? $trip->pre_trip_remarks }}
        </div>
    @endif

    <!-- Condition Certification -->
    <div class="section-title">Condition Certification</div>
    <div style="padding: 10px; background-color: #dcfce7; border-left: 3px solid #16a34a; margin: 10px 0;">
        <strong>✓ Vehicle condition is satisfactory for safe operation</strong>
    </div>

    <!-- Driver Signature -->
    <div class="section-title">Driver Certification & Signature</div>
    
    <div class="signature-box">
        @if($signatureData)
            <img src="{{ $signatureData }}" alt="Driver Signature" class="signature-image">
            <div style="margin-top: 8px; font-size: 9pt;">
                <strong>{{ $driverName }}</strong><br>
                Signed on: {{ $signedAt->format('F d, Y \a\t H:i') }}
            </div>
        @else
            <div style="color: #94a3b8; padding: 15px;">
                No signature provided
            </div>
        @endif
    </div>
    
    <div class="certification-text">
        I certify that I have performed a pre-trip inspection of the above vehicle in accordance with Federal Motor Carrier Safety Regulations (49 CFR 396.11, 396.13) and that the vehicle is in safe operating condition to begin this trip.
    </div>
@endsection
