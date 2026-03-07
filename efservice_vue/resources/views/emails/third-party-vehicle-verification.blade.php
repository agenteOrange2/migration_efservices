<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Verification Required – EF Services</title>
    <style>
        body { margin: 0; padding: 0; background: #f4f6f8; font-family: Arial, sans-serif; color: #333; }
        .wrapper { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .header { background: #1e40af; padding: 32px 40px; text-align: center; }
        .header h1 { color: #ffffff; margin: 0; font-size: 22px; font-weight: 700; }
        .header p { color: #bfdbfe; margin: 8px 0 0; font-size: 14px; }
        .body { padding: 32px 40px; }
        .body p { line-height: 1.7; font-size: 15px; margin: 0 0 16px; }
        .vehicle-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 20px; margin: 24px 0; }
        .vehicle-card table { width: 100%; border-collapse: collapse; }
        .vehicle-card td { padding: 6px 0; font-size: 14px; }
        .vehicle-card td:first-child { color: #64748b; width: 40%; }
        .vehicle-card td:last-child { font-weight: 600; }
        .cta-box { text-align: center; margin: 32px 0; }
        .cta-btn { display: inline-block; background: #1e40af; color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 6px; font-size: 15px; font-weight: 600; }
        .notice { background: #fffbeb; border: 1px solid #fde68a; border-radius: 6px; padding: 14px 18px; font-size: 13px; color: #92400e; margin-top: 24px; }
        .footer { background: #f1f5f9; padding: 20px 40px; text-align: center; font-size: 12px; color: #94a3b8; }
        .footer a { color: #94a3b8; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>EF Services</h1>
        <p>Vehicle Verification Required</p>
    </div>

    <div class="body">
        <p>Hello <strong>{{ $thirdPartyName }}</strong>,</p>

        <p>
            A driver, <strong>{{ $driverName }}</strong>, has submitted an application listing your company as the operating carrier
            for the vehicle below. Please review and verify the information to proceed.
        </p>

        <div class="vehicle-card">
            <table>
                <tr>
                    <td>Make</td>
                    <td>{{ $vehicleData['make'] ?? '—' }}</td>
                </tr>
                <tr>
                    <td>Model</td>
                    <td>{{ $vehicleData['model'] ?? '—' }}</td>
                </tr>
                <tr>
                    <td>Year</td>
                    <td>{{ $vehicleData['year'] ?? '—' }}</td>
                </tr>
                <tr>
                    <td>VIN</td>
                    <td>{{ $vehicleData['vin'] ?? '—' }}</td>
                </tr>
            </table>
        </div>

        <p>
            Please click the button below to verify your information and digitally sign the required documents.
        </p>

        <div class="cta-box">
            <a href="{{ url('/third-party/verify/' . $verificationToken) }}" class="cta-btn">
                Verify &amp; Sign Documents
            </a>
        </div>

        <div class="notice">
            <strong>Note:</strong> This link is unique to your request and will expire in 7 days.
            If you did not expect this email or believe it was sent in error, please contact us at
            <a href="mailto:support@efservices.com">support@efservices.com</a>.
        </div>
    </div>

    <div class="footer">
        &copy; {{ date('Y') }} EF Services. All rights reserved.<br>
        This is an automated message — please do not reply directly to this email.
    </div>
</div>
</body>
</html>
