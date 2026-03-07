<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Driver Archive Report - {{ $archive->full_name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #333;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #1a1a1a;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #1a1a1a;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #ddd;
        }
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            font-weight: bold;
            padding: 5px 10px 5px 0;
            width: 30%;
            vertical-align: top;
        }
        .info-value {
            display: table-cell;
            padding: 5px 0;
            vertical-align: top;
        }
        .item-box {
            background: #f9f9f9;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 3px;
        }
        .item-title {
            font-weight: bold;
            font-size: 13px;
            margin-bottom: 8px;
        }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 10px;
            color: #999;
            padding-top: 10px;
            border-top: 1px solid #ddd;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>Driver Archive Report</h1>
        <p><strong>{{ $archive->full_name }}</strong></p>
        <p>Archived on: {{ $archive->archived_at->format('F j, Y \a\t g:i A') }}</p>
        <p>Carrier: {{ $archive->carrier->name }}</p>
    </div>

    <!-- Personal Information -->
    <div class="section">
        <div class="section-title">Personal Information</div>
        <div class="info-grid">
            @if(isset($driver_data['name']))
            <div class="info-row">
                <div class="info-label">Full Name:</div>
                <div class="info-value">{{ $driver_data['name'] }} {{ $driver_data['middle_name'] ?? '' }} {{ $driver_data['last_name'] ?? '' }}</div>
            </div>
            @endif
            @if(isset($driver_data['email']))
            <div class="info-row">
                <div class="info-label">Email:</div>
                <div class="info-value">{{ $driver_data['email'] }}</div>
            </div>
            @endif
            @if(isset($driver_data['phone']))
            <div class="info-row">
                <div class="info-label">Phone:</div>
                <div class="info-value">{{ $driver_data['phone'] }}</div>
            </div>
            @endif
            @if(isset($driver_data['date_of_birth']))
            <div class="info-row">
                <div class="info-label">Date of Birth:</div>
                <div class="info-value">{{ $driver_data['date_of_birth'] }}</div>
            </div>
            @endif
            @if(isset($driver_data['hire_date']))
            <div class="info-row">
                <div class="info-label">Hire Date:</div>
                <div class="info-value">{{ $driver_data['hire_date'] }}</div>
            </div>
            @endif
        </div>
    </div>

    <!-- Licenses -->
    @if(!empty($licenses))
    <div class="section">
        <div class="section-title">Licenses</div>
        @foreach($licenses as $license)
        <div class="item-box">
            <div class="item-title">License #{{ $license['license_number'] ?? 'N/A' }}</div>
            <div class="info-grid">
                @if(isset($license['state']))
                <div class="info-row">
                    <div class="info-label">State:</div>
                    <div class="info-value">{{ $license['state'] }}</div>
                </div>
                @endif
                @if(isset($license['class']))
                <div class="info-row">
                    <div class="info-label">Class:</div>
                    <div class="info-value">{{ $license['class'] }}</div>
                </div>
                @endif
                @if(isset($license['expiration_date']))
                <div class="info-row">
                    <div class="info-label">Expiration:</div>
                    <div class="info-value">{{ $license['expiration_date'] }}</div>
                </div>
                @endif
                @if(!empty($license['endorsements']))
                <div class="info-row">
                    <div class="info-label">Endorsements:</div>
                    <div class="info-value">{{ implode(', ', $license['endorsements']) }}</div>
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <!-- Medical -->
    @if(!empty($medical))
    <div class="section">
        <div class="section-title">Medical Qualification</div>
        <div class="info-grid">
            @if(isset($medical['exam_date']))
            <div class="info-row">
                <div class="info-label">Exam Date:</div>
                <div class="info-value">{{ $medical['exam_date'] }}</div>
            </div>
            @endif
            @if(isset($medical['expiration_date']))
            <div class="info-row">
                <div class="info-label">Expiration Date:</div>
                <div class="info-value">{{ $medical['expiration_date'] }}</div>
            </div>
            @endif
            @if(isset($medical['examiner_name']))
            <div class="info-row">
                <div class="info-label">Examiner:</div>
                <div class="info-value">{{ $medical['examiner_name'] }}</div>
            </div>
            @endif
            @if(isset($medical['status']))
            <div class="info-row">
                <div class="info-label">Status:</div>
                <div class="info-value">{{ ucfirst($medical['status']) }}</div>
            </div>
            @endif
        </div>
    </div>
    @endif

    <div class="page-break"></div>

    <!-- Employment History -->
    @if(!empty($employment))
    <div class="section">
        <div class="section-title">Employment History</div>
        
        @if(isset($employment['employment_companies']))
        @foreach($employment['employment_companies'] as $company)
        <div class="item-box">
            <div class="item-title">{{ $company['company_name'] ?? 'Company' }}</div>
            <div class="info-grid">
                @if(isset($company['position']))
                <div class="info-row">
                    <div class="info-label">Position:</div>
                    <div class="info-value">{{ $company['position'] }}</div>
                </div>
                @endif
                @if(isset($company['start_date']) || isset($company['end_date']))
                <div class="info-row">
                    <div class="info-label">Period:</div>
                    <div class="info-value">{{ $company['start_date'] ?? 'N/A' }} - {{ $company['end_date'] ?? 'Present' }}</div>
                </div>
                @endif
                @if(isset($company['address']))
                <div class="info-row">
                    <div class="info-label">Address:</div>
                    <div class="info-value">{{ $company['address'] }}, {{ $company['city'] ?? '' }} {{ $company['state'] ?? '' }} {{ $company['zip'] ?? '' }}</div>
                </div>
                @endif
                @if(isset($company['reason_for_leaving']))
                <div class="info-row">
                    <div class="info-label">Reason for Leaving:</div>
                    <div class="info-value">{{ $company['reason_for_leaving'] }}</div>
                </div>
                @endif
            </div>
        </div>
        @endforeach
        @endif
        
        @if(isset($employment['work_histories']))
        @foreach($employment['work_histories'] as $history)
        <div class="item-box">
            <div class="item-title">{{ $history['employer_name'] ?? 'Employer' }}</div>
            <div class="info-grid">
                @if(isset($history['position']))
                <div class="info-row">
                    <div class="info-label">Position:</div>
                    <div class="info-value">{{ $history['position'] }}</div>
                </div>
                @endif
                @if(isset($history['start_date']) || isset($history['end_date']))
                <div class="info-row">
                    <div class="info-label">Period:</div>
                    <div class="info-value">{{ $history['start_date'] ?? 'N/A' }} - {{ $history['end_date'] ?? 'Present' }}</div>
                </div>
                @endif
            </div>
        </div>
        @endforeach
        @endif
    </div>
    @endif

    <!-- Testing -->
    @if(!empty($testing))
    <div class="section">
        <div class="section-title">Drug & Alcohol Testing</div>
        @foreach($testing as $test)
        <div class="item-box">
            <div class="item-title">{{ $test['test_type'] ?? 'Test' }} - {{ $test['test_date'] ?? 'N/A' }}</div>
            <div class="info-grid">
                @if(isset($test['result']))
                <div class="info-row">
                    <div class="info-label">Result:</div>
                    <div class="info-value">{{ ucfirst($test['result']) }}</div>
                </div>
                @endif
                @if(isset($test['reason']))
                <div class="info-row">
                    <div class="info-label">Reason:</div>
                    <div class="info-value">{{ $test['reason'] }}</div>
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <!-- Accidents -->
    @if(!empty($accidents))
    <div class="section">
        <div class="section-title">Accidents</div>
        @foreach($accidents as $accident)
        <div class="item-box">
            <div class="item-title">Accident - {{ $accident['accident_date'] ?? 'N/A' }}</div>
            <div class="info-grid">
                @if(isset($accident['location']))
                <div class="info-row">
                    <div class="info-label">Location:</div>
                    <div class="info-value">{{ $accident['location'] }}</div>
                </div>
                @endif
                @if(isset($accident['description']))
                <div class="info-row">
                    <div class="info-label">Description:</div>
                    <div class="info-value">{{ $accident['description'] }}</div>
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <!-- Traffic Convictions -->
    @if(!empty($convictions))
    <div class="section">
        <div class="section-title">Traffic Violations</div>
        @foreach($convictions as $conviction)
        <div class="item-box">
            <div class="item-title">Violation - {{ $conviction['conviction_date'] ?? 'N/A' }}</div>
            <div class="info-grid">
                @if(isset($conviction['location']))
                <div class="info-row">
                    <div class="info-label">Location:</div>
                    <div class="info-value">{{ $conviction['location'] }}</div>
                </div>
                @endif
                @if(isset($conviction['charge']))
                <div class="info-row">
                    <div class="info-label">Charge:</div>
                    <div class="info-value">{{ $conviction['charge'] }}</div>
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <p>This is an archived driver record. Generated on {{ now()->format('F j, Y \a\t g:i A') }}</p>
        <p>Archive ID: {{ $archive->id }} | Confidential Information</p>
    </div>
</body>
</html>
