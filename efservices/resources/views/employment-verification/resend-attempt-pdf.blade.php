<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Employment Verification - Email Attempt Record</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            color: #2563eb;
            font-size: 22px;
        }
        .header h2 {
            margin: 5px 0 0 0;
            color: #666;
            font-size: 14px;
            font-weight: normal;
        }
        .section {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 5px;
        }
        .section h3 {
            margin-top: 0;
            font-size: 16px;
            color: #2563eb;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        .info-row {
            margin-bottom: 10px;
        }
        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 180px;
        }
        .info-value {
            display: inline-block;
        }
        .attempt-info {
            background-color: #e8f4fd;
            border-left: 4px solid #2563eb;
            padding: 15px;
            margin-bottom: 20px;
        }
        .attempt-info h3 {
            margin-top: 0;
            color: #2563eb;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>EMPLOYMENT VERIFICATION</h1>
        <h2>Email Attempt Record</h2>
    </div>

    <div class="attempt-info">
        <h3>Attempt Information</h3>
        <div class="info-row">
            <span class="info-label">Attempt Number:</span>
            <span class="info-value">{{ $attemptNumber }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Date:</span>
            <span class="info-value">{{ $attemptDate }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Time:</span>
            <span class="info-value">{{ $attemptTime }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Email Sent To:</span>
            <span class="info-value">{{ $emailSentTo }}</span>
        </div>
    </div>

    <div class="section">
        <h3>Driver Information</h3>
        <div class="info-row">
            <span class="info-label">Driver Name:</span>
            <span class="info-value">{{ $driverName }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Driver ID:</span>
            <span class="info-value">{{ $driverId }}</span>
        </div>
    </div>

    <div class="section">
        <h3>Company Information</h3>
        <div class="info-row">
            <span class="info-label">Company Name:</span>
            <span class="info-value">{{ $companyName }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Company Email:</span>
            <span class="info-value">{{ $companyEmail }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Employment Period:</span>
            <span class="info-value">{{ $employedFrom }} to {{ $employedTo }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Position(s) Held:</span>
            <span class="info-value">{{ $positionsHeld }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Reason for Leaving:</span>
            <span class="info-value">{{ $reasonForLeaving }}</span>
        </div>
    </div>

    <div class="section">
        <h3>Verification Token</h3>
        <div class="info-row">
            <span class="info-label">Token:</span>
            <span class="info-value" style="font-family: monospace; font-size: 11px;">{{ $token }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Expires At:</span>
            <span class="info-value">{{ $expiresAt }}</span>
        </div>
    </div>

    <div class="footer">
        <p>This document was automatically generated on {{ $generatedAt }}</p>
        <p>Employment Verification System - EF Services</p>
    </div>
</body>
</html>
