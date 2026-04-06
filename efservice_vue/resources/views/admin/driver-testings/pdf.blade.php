<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Authorization Sheet – Drug & Alcohol Test</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 40px; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .blue { color: #0000EE; }
        .space-y { margin-top: 20px; margin-bottom: 20px; }
        .box {
            display: inline-block; width: 14px; height: 14px;
            border: 1px solid black; text-align: center;
            line-height: 14px; margin-right: 4px; font-size: 11px;
        }
        .checked { background-color: #000; color: #fff; font-weight: bold; }
        .section { margin-top: 20px; border-top: 1px solid #000; padding-top: 10px; }
        .highlight { background-color: yellow; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; padding: 4px; }
        .info-table td { border: none; }
        .logo { float: left; margin-right: 20px; }
        .result-positive { color: red; font-weight: bold; }
        .result-negative { color: green; font-weight: bold; }
        .result-refusal { color: orange; font-weight: bold; }
    </style>
</head>
<body>

    {{-- Logo + header --}}
    <div>
        @php
            $logoPath = public_path('build/img/logo_efservices_logo.png');
            $logoBase64 = '';
            if (file_exists($logoPath)) {
                $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
            }
            $driver  = $driverTesting->userDriverDetail;
            $license = $driver ? $driver->primaryLicense : null;
            $licenseNumber = $license ? $license->license_number : 'N/A';
            $licenseState  = $license ? ($license->state_of_issue ?? 'N/A') : 'N/A';
        @endphp
        @if($logoBase64)
            <img src="{{ $logoBase64 }}" alt="EFServices Logo" class="logo" width="80">
        @endif
        <div class="center bold" style="font-size:16px;">EF Compliance Trucking Services - LLC</div>
        <div class="center bold" style="font-size:14px;">Authorization Sheet</div>
        <div class="center blue">drugtesting@efcts.com</div>
    </div>

    <div class="space-y">
        <p>
            <strong>Company:</strong>
            {{ $driver && $driver->carrier ? $driver->carrier->name : ($driverTesting->carrier ? $driverTesting->carrier->name : 'N/A') }}
        </p>
    </div>

    <p>
        <span class="box checked">X</span> FMCSA
    </p>

    <p>
        <strong>Donor Name:</strong>
        {{ $driver ? trim(($driver->user->name ?? '') . ' ' . ($driver->middle_name ?? '') . ' ' . ($driver->last_name ?? '')) : 'N/A' }}
        &nbsp;&nbsp;&nbsp;
        <strong>Donor SS# or Employee ID#</strong> {{ $driver ? ($driver->ssn ?? '_______________') : '_______________' }}
        &nbsp;&nbsp;&nbsp;
        <strong>License:</strong> {{ $licenseNumber }} &nbsp; <strong>State:</strong> {{ $licenseState }}
    </p>

    <div class="highlight center space-y">Please mark type of test needed</div>

    <div class="space-y">
        <span class="box {{ $driverTesting->is_pre_employment_test ? 'checked' : '' }}">{{ $driverTesting->is_pre_employment_test ? 'X' : '' }}</span> Pre-Employment &nbsp;&nbsp;
        <span class="box {{ $driverTesting->is_random_test ? 'checked' : '' }}">{{ $driverTesting->is_random_test ? 'X' : '' }}</span> Random &nbsp;&nbsp;
        <span class="box {{ $driverTesting->is_post_accident_test ? 'checked' : '' }}">{{ $driverTesting->is_post_accident_test ? 'X' : '' }}</span> Post Accident &nbsp;&nbsp;
        <span class="box {{ $driverTesting->is_follow_up_test ? 'checked' : '' }}">{{ $driverTesting->is_follow_up_test ? 'X' : '' }}</span> Follow Up &nbsp;&nbsp;
        <span class="box {{ $driverTesting->is_return_to_duty_test ? 'checked' : '' }}">{{ $driverTesting->is_return_to_duty_test ? 'X' : '' }}</span> Return To Duty &nbsp;&nbsp;
        <span class="box {{ $driverTesting->is_reasonable_suspicion_test ? 'checked' : '' }}">{{ $driverTesting->is_reasonable_suspicion_test ? 'X' : '' }}</span> Reasonable Suspicion
        <div class="space-y">
            <strong>Other:</strong>
            @if($driverTesting->is_other_reason_test && $driverTesting->other_reason_description)
                {{ $driverTesting->other_reason_description }}
            @else
                ________________________
            @endif
        </div>
    </div>

    <div class="section">
        <strong>Test Type *</strong>
        <div class="space-y">
            <span class="box {{ $driverTesting->test_type === 'dot_drug_test' ? 'checked' : '' }}">{{ $driverTesting->test_type === 'dot_drug_test' ? 'X' : '' }}</span> DOT Drug test (MRO)<br>
            <span class="box {{ $driverTesting->test_type === 'non_dot_lab' ? 'checked' : '' }}">{{ $driverTesting->test_type === 'non_dot_lab' ? 'X' : '' }}</span> NON-DOT Lab (MRO)<br>
            <span class="box {{ $driverTesting->test_type === 'dot_alcohol_test' ? 'checked' : '' }}">{{ $driverTesting->test_type === 'dot_alcohol_test' ? 'X' : '' }}</span> DOT Alcohol test<br>
            <span class="box {{ $driverTesting->test_type === 'non_dot_alcohol_test' ? 'checked' : '' }}">{{ $driverTesting->test_type === 'non_dot_alcohol_test' ? 'X' : '' }}</span> NON-DOT Alcohol test<br>
            <span class="box {{ $driverTesting->test_type === 'panel_instant_test' ? 'checked' : '' }}">{{ $driverTesting->test_type === 'panel_instant_test' ? 'X' : '' }}</span> 10 Panel Instant test<br>
            <span class="box {{ $driverTesting->test_type === 'dot_drug_alcohol_test' ? 'checked' : '' }}">{{ $driverTesting->test_type === 'dot_drug_alcohol_test' ? 'X' : '' }}</span> DOT Drug & Alcohol test<br>
        </div>
    </div>

    <p>
        <strong>Person Requesting test:</strong> {{ $driverTesting->requester_name ?? 'EFCTS' }}
        <span style="float:right"><strong>Time Sent:</strong> {{ $driverTesting->created_at ? $driverTesting->created_at->format('m/d/Y H:i') : now()->format('m/d/Y H:i') }}</span>
    </p>

    <div class="section">
        <strong>Administered By:</strong> {{ $driverTesting->administered_by ?? 'N/A' }}
        &nbsp;&nbsp;&nbsp;
        <strong>Location:</strong> {{ $driverTesting->location ?? 'N/A' }}
    </div>

    <div class="section">
        <table class="info-table">
            <tr>
                <td width="50%">
                    <strong>Test Result:</strong>
                    @if($driverTesting->test_result === 'Positive')
                        <span class="result-positive">POSITIVE</span>
                    @elseif($driverTesting->test_result === 'Negative')
                        <span class="result-negative">NEGATIVE</span>
                    @elseif($driverTesting->test_result === 'Refusal')
                        <span class="result-refusal">REFUSAL</span>
                    @else
                        <span style="color:gray;font-weight:bold;">NOT AVAILABLE</span>
                    @endif
                </td>
                <td width="50%">
                    <strong>Result Date:</strong>
                    {{ $driverTesting->test_date ? $driverTesting->test_date->format('m/d/Y') : 'N/A' }}
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <strong>Notes:</strong><br>
        {{ $driverTesting->notes ?: 'No notes available' }}
    </div>

    <div class="section">
        <table class="info-table">
            <tr>
                <td width="50%">
                    <strong>Odessa Location</strong><br>
                    1560 W. I-20 N. Service Rd<br>
                    Odessa, TX 79763<br>
                    432-332-5700<br>
                    <span class="blue">permianbasindrug@pbdctx.com</span>
                </td>
                <td width="50%">
                    <strong>Midland Location</strong><br>
                    606 A Kent St.<br>
                    Midland, TX 79701<br>
                    432-203-3212<br>
                    <span class="blue">midland@pbdctx.com</span>
                </td>
            </tr>
            <tr>
                <td width="50%">
                    <strong>Abilene Office</strong><br>
                    317 N Willis<br>
                    Abilene, TX 79603<br>
                    Tel: (325) 399-9248<br>
                    <span class="blue">abilene@pbdatx.com</span>
                </td>
                <td width="50%">
                    <strong>Seminole Office</strong><br>
                    1305 Hobbs Hwy<br>
                    Seminole, TX 79360<br>
                    Tel: (432) 758-3838<br>
                    <span class="blue">seminole@pbdatx.com</span>
                </td>
            </tr>
            <tr>
                <td width="50%">
                    <strong>EFCTS Office</strong><br>
                    801 Magnolia St,<br>
                    Kermit, TX 79745<br>
                    (432) 853-5493<br>
                    <span class="blue">drugtesting@efcts.com</span>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
