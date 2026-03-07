<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        @if ($eventType === 'step_completed')
            Paso Completado - Registro de Carrier
        @elseif($eventType === 'registration_completed')
            Nuevo Carrier Registrado
        @else
            Notificación de Carrier
        @endif
    </title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }

        .email-container {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            background: #03045E;
            color: white;
            padding: 30px 20px;
            text-align: center;
        }

        .header h1 {
            margin: 0;
            color: #f8f9fa;
            font-size: 24px;
            font-weight: 600;
        }

        .header .subtitle {
            color: #adb5bd;
            margin-top: 8px;
            font-size: 14px;
        }

        .step-badge {
            display: inline-block;
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 5px 15px;
            border-radius: 5px;
            font-size: 13px;
            margin-top: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .content {
            padding: 30px 20px;
        }

        .info-box {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 20px;
            margin: 15px 0;
            border-left: 4px solid #03045E;
        }

        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: #495057;
            margin: 0 0 15px 0;
        }

        .info-row {
            margin: 10px 0;
        }

        .label {
            font-weight: bold;
            color: #495057;
            display: inline-block;
            width: 140px;
        }

        .value {
            color: #212529;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }

        .timestamp {
            text-align: center;
            padding: 15px;
            background-color: #e9ecef;
            border-radius: 5px;
            margin-top: 20px;
            font-size: 14px;
            color: #495057;
        }

        .action-button {
            display: block;
            width: 100%;
            text-align: center;
            padding: 12px 20px;
            background-color: #03045E;
            color: white !important;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            font-size: 14px;
            margin-top: 25px;
            box-sizing: border-box;
        }

        .action-button:hover {
            background-color: #023e8a;
        }

        .footer {
            background-color: #03045E;
            color: #adb5bd;
            padding: 20px;
            text-align: center;
            font-size: 12px;
        }

        .footer p {
            margin: 5px 0;
        }

        .footer .company-name {
            color: white;
            font-weight: 600;
            font-size: 14px;
        }

        @media (max-width: 600px) {
            body {
                padding: 10px;
            }

            .header, .content, .footer {
                padding: 20px 15px;
            }

            .label {
                width: 100px;
                display: block;
                margin-bottom: 5px;
            }
        }
    </style>
</head>

<body>
    <div class="email-container">
        {{-- Header dinámico según el tipo de evento --}}
        @php
            $stepNames = [
                'step1' => 'Step 1: Basic Information',
                'step2' => 'Step 2: Company Information',
                'step3' => 'Step 3: Membership Selection',
                'step4' => 'Step 4: Bank Information',
            ];
            $stepDisplayName = $stepNames[$step] ?? ($step ?? 'Unknown Step');
        @endphp

        @if ($eventType === 'step_completed')
            <div class="header step-completed">
                <h1>📋 Registration Step Completed</h1>
                <p class="subtitle">A carrier is progressing through their registration process</p>
                <span class="step-badge">{{ $stepDisplayName }}</span>
            </div>
        @elseif($eventType === 'registration_completed')
            <div class="header registration-completed">
                <h1>🎉 New Carrier Registered!</h1>
                <p class="subtitle">A new carrier has successfully completed their registration</p>
            </div>
        @else
            <div class="header default">
                <h1>📢 Carrier Notification</h1>
                <p class="subtitle">New activity in the system</p>
            </div>
        @endif

        <div class="content">
            @if ($userCarrier)
                {{-- Sistema Legacy --}}
                <div class="info-box user-info">
                    <h3 class="section-title">👤 User Information</h3>
                    <div class="info-row">
                        <span class="label">Name:</span>
                        <span class="value">{{ $userCarrier->name ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Email Address:</span>
                        <span class="value">{{ $userCarrier->email ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Phone Number:</span>
                        <span class="value">{{ $userCarrier->phone ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Job Position:</span>
                        <span class="value">{{ $userCarrier->job_position ?? 'N/A' }}</span>
                    </div>
                </div>
            @else
                {{-- Nuevo Sistema --}}
                <div class="info-box user-info">
                    <h3 class="section-title">👤 User Information</h3>
                    <div class="info-row">
                        <span class="label">Full Name:</span>
                        <span class="value">{{ $user->name ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Email Address:</span>
                        <span class="value">{{ $user->email ?? 'N/A' }}</span>
                    </div>
                    @if ($user->carrierDetails ?? null)
                        <div class="info-row">
                            <span class="label">Phone Number:</span>
                            <span class="value">{{ $user->carrierDetails->phone ?? 'N/A' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Job Position:</span>
                            <span class="value">{{ $user->carrierDetails->job_position ?? 'N/A' }}</span>
                        </div>
                    @endif
                </div>

                @if ($carrier)
                    <div class="info-box carrier-info">
                        <h3 class="section-title">🏢 Carrier Information</h3>
                        <div class="info-row">
                            <span class="label">Company Name:</span>
                            <span class="value">{{ $carrier->name ?? 'N/A' }}</span>
                        </div>
                        @if ($carrier->dot_number)
                            <div class="info-row">
                                <span class="label">DOT Number:</span>
                                <span class="value">{{ $carrier->dot_number }}</span>
                            </div>
                        @endif
                        @if ($carrier->mc_number)
                            <div class="info-row">
                                <span class="label">MC Number:</span>
                                <span class="value">{{ $carrier->mc_number }}</span>
                            </div>
                        @endif
                        @if ($carrier->ein_number)
                            <div class="info-row">
                                <span class="label">EIN:</span>
                                <span class="value">{{ $carrier->ein_number }}</span>
                            </div>
                        @endif
                        @if ($carrier->address)
                            <div class="info-row">
                                <span class="label">Address:</span>
                                <span class="value">
                                    {{ $carrier->address }}
                                    @if ($carrier->state), {{ $carrier->state }}@endif
                                    @if ($carrier->zipcode) {{ $carrier->zipcode }}@endif
                                </span>
                            </div>
                        @endif
                        @if ($carrier->membership)
                            <div class="info-row">
                                <span class="label">Membership Plan:</span>
                                <span class="value">{{ $carrier->membership->name ?? 'N/A' }}</span>
                            </div>
                        @endif
                        <div class="info-row">
                            <span class="label">Status:</span>
                            <span class="value">
                                @if ($carrier->status == 2)
                                    <span class="status-badge status-pending">Pending Validation</span>
                                @elseif($carrier->status == 1)
                                    <span class="status-badge status-completed">Active</span>
                                @else
                                    <span class="status-badge status-pending">{{ $carrier->status_name ?? 'Pending' }}</span>
                                @endif
                            </span>
                        </div>
                    </div>
                @endif
            @endif

            {{-- Información adicional del evento --}}
            @if (!empty($data) && is_array($data))
                <div class="info-box additional-info">
                    <h3 class="section-title">📝 Registration Details</h3>
                    @foreach ($data as $key => $value)
                        @if (!is_array($value) && !is_object($value) && $value !== null)
                            <div class="info-row">
                                <span class="label">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                                <span class="value">{{ $value }}</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif

            <div class="timestamp">
                📅 {{ now()->format('m/d/Y') }} at {{ now()->format('H:i:s') }}
            </div>

            @if ($eventType === 'registration_completed' && $carrier)
                <a href="{{ config('app.url') }}/admin/carriers/{{ $carrier->id ?? '' }}" class="action-button">
                    View Carrier Details in the System
                </a>
            @else
                <a href="{{ config('app.url') }}/admin/carriers" class="action-button">
                    View Carriers in the System
                </a>
            @endif
        </div>

        <div class="footer">
            <p class="company-name">EFCTS - Carrier Management System</p>
            <p>This is an automated email from the system.</p>
            <p>Please do not reply to this message.</p>
            <p>&copy; {{ date('Y') }} EFCTS - All rights reserved</p>
        </div>
    </div>
</body>

</html>
