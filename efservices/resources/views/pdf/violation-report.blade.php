<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Violation Report - {{ $violation->violation_type_name }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            color: #1e293b;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #1e40af;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 20px;
            color: #1e40af;
            margin: 0 0 5px 0;
        }
        .header p {
            color: #64748b;
            margin: 2px 0;
            font-size: 11px;
        }
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-danger { background: #fee2e2; color: #dc2626; }
        .badge-warning { background: #fef3c7; color: #d97706; }
        .badge-success { background: #dcfce7; color: #16a34a; }
        .badge-info { background: #dbeafe; color: #2563eb; }
        .section {
            margin-bottom: 18px;
        }
        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #1e40af;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        table th, table td {
            padding: 6px 10px;
            text-align: left;
            border: 1px solid #e2e8f0;
            font-size: 10px;
        }
        table th {
            background: #f1f5f9;
            font-weight: bold;
            color: #475569;
            text-transform: uppercase;
            font-size: 9px;
        }
        .info-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .info-grid td {
            padding: 5px 10px;
            border: none;
            vertical-align: top;
        }
        .info-label {
            font-weight: bold;
            color: #64748b;
            font-size: 9px;
            text-transform: uppercase;
            width: 140px;
        }
        .info-value {
            color: #1e293b;
        }
        .forgiveness-box {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 6px;
            padding: 12px;
            margin-top: 10px;
        }
        .forgiveness-box .title {
            font-weight: bold;
            color: #16a34a;
            margin-bottom: 8px;
            font-size: 12px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            color: #94a3b8;
            font-size: 9px;
        }
        .signature-line {
            margin-top: 40px;
            display: inline-block;
            width: 250px;
            border-top: 1px solid #1e293b;
            padding-top: 5px;
            text-align: center;
            color: #64748b;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>HOS Violation Report</h1>
        <p>{{ $carrier->name ?? 'N/A' }} &mdash; DOT Compliance Record</p>
        <p>Generated: {{ $generatedAt->format('m/d/Y h:i A') }}</p>
    </div>

    <!-- Violation Details -->
    <div class="section">
        <div class="section-title">Violation Details</div>
        <table class="info-grid">
            <tr>
                <td class="info-label">Violation ID</td>
                <td class="info-value">#{{ $violation->id }}</td>
                <td class="info-label">Date</td>
                <td class="info-value">{{ $violation->violation_date?->format('m/d/Y') ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="info-label">Type</td>
                <td class="info-value">{{ $violation->violation_type_name }}</td>
                <td class="info-label">Severity</td>
                <td class="info-value">
                    <span class="badge {{ $violation->violation_severity === 'critical' ? 'badge-danger' : ($violation->violation_severity === 'moderate' ? 'badge-warning' : 'badge-info') }}">
                        {{ ucfirst($violation->violation_severity ?? 'N/A') }}
                    </span>
                </td>
            </tr>
            <tr>
                <td class="info-label">FMCSA Rule</td>
                <td class="info-value">{{ $violation->fmcsa_rule_reference ?? 'N/A' }}</td>
                <td class="info-label">Hours Exceeded</td>
                <td class="info-value">{{ $violation->formatted_hours_exceeded ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <!-- Driver Information -->
    <div class="section">
        <div class="section-title">Driver Information</div>
        <table class="info-grid">
            <tr>
                <td class="info-label">Driver Name</td>
                <td class="info-value">{{ $driverName }}</td>
                <td class="info-label">Email</td>
                <td class="info-value">{{ $violation->driver->user->email ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="info-label">Carrier</td>
                <td class="info-value">{{ $carrier->name ?? 'N/A' }}</td>
                <td class="info-label">Vehicle</td>
                <td class="info-value">{{ $violation->vehicle->company_unit_number ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <!-- Penalty Information -->
    @if($violation->penalty_type)
    <div class="section">
        <div class="section-title">Penalty Information</div>
        <table class="info-grid">
            <tr>
                <td class="info-label">Penalty Type</td>
                <td class="info-value">{{ ucfirst(str_replace('_', ' ', $violation->penalty_type)) }}</td>
                <td class="info-label">Penalty Hours</td>
                <td class="info-value">{{ $violation->penalty_hours ?? 'N/A' }}h</td>
            </tr>
            <tr>
                <td class="info-label">Penalty Start</td>
                <td class="info-value">{{ $violation->penalty_start?->format('m/d/Y h:i A') ?? 'N/A' }}</td>
                <td class="info-label">Penalty End</td>
                <td class="info-value">{{ $violation->penalty_end?->format('m/d/Y h:i A') ?? 'Cleared' }}</td>
            </tr>
        </table>
    </div>
    @endif

    <!-- Trip Information -->
    @if($violation->trip)
    <div class="section">
        <div class="section-title">Related Trip</div>
        <table class="info-grid">
            <tr>
                <td class="info-label">Trip Number</td>
                <td class="info-value">{{ $violation->trip->trip_number ?? 'N/A' }}</td>
                <td class="info-label">Status</td>
                <td class="info-value">{{ ucfirst($violation->trip->status ?? 'N/A') }}</td>
            </tr>
            <tr>
                <td class="info-label">Start Time</td>
                <td class="info-value">{{ $violation->trip->actual_start_time?->format('m/d/Y h:i A') ?? 'N/A' }}</td>
                <td class="info-label">End Time</td>
                <td class="info-value">{{ ($violation->trip->actual_end_time ?? $violation->trip->auto_stopped_at)?->format('m/d/Y h:i A') ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>
    @endif

    <!-- Forgiveness Details -->
    @if($violation->is_forgiven)
    <div class="section">
        <div class="section-title">Forgiveness Record</div>
        <div class="forgiveness-box">
            <div class="title">&#10003; Violation Forgiven</div>
            <table class="info-grid">
                <tr>
                    <td class="info-label">Forgiven By</td>
                    <td class="info-value">{{ $forgivenByName }}</td>
                    <td class="info-label">Forgiven At</td>
                    <td class="info-value">{{ $violation->forgiven_at?->format('m/d/Y h:i A') ?? 'N/A' }}</td>
                </tr>
            </table>
            <table class="info-grid" style="margin-top: 5px;">
                <tr>
                    <td class="info-label">Reason</td>
                    <td class="info-value" colspan="3">{{ $violation->forgiveness_reason ?? 'N/A' }}</td>
                </tr>
            </table>
            @if($violation->original_trip_end_time || $violation->adjusted_trip_end_time)
            <table class="info-grid" style="margin-top: 5px;">
                <tr>
                    <td class="info-label">Original End Time</td>
                    <td class="info-value">{{ $violation->original_trip_end_time?->format('m/d/Y h:i A') ?? 'N/A' }}</td>
                    <td class="info-label">Adjusted End Time</td>
                    <td class="info-value">{{ $violation->adjusted_trip_end_time?->format('m/d/Y h:i A') ?? 'N/A' }}</td>
                </tr>
            </table>
            @endif
        </div>
    </div>
    @endif

    <!-- Acknowledgment -->
    <div class="section">
        <div class="section-title">Acknowledgment</div>
        <table class="info-grid">
            <tr>
                <td class="info-label">Acknowledged</td>
                <td class="info-value">{{ $violation->acknowledged ? 'Yes' : 'No' }}</td>
                <td class="info-label">Acknowledged At</td>
                <td class="info-value">{{ $violation->acknowledged_at?->format('m/d/Y h:i A') ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <!-- Signatures -->
    {{-- <div style="margin-top: 40px;">
        <table style="width: 100%; border: none;">
            <tr>
                <td style="border: none; text-align: center; width: 50%;">
                    <div class="signature-line">Administrator Signature</div>
                </td>
                <td style="border: none; text-align: center; width: 50%;">
                    <div class="signature-line">Driver Signature</div>
                </td>
            </tr>
        </table>
    </div> --}}

    <!-- Footer -->
    <div class="footer">
        <p>This document is an official record of an HOS violation and its resolution.</p>
        <p>{{ $carrier->name ?? '' }} &mdash; Report generated on {{ $generatedAt->format('m/d/Y h:i A') }}</p>
    </div>
</body>
</html>
