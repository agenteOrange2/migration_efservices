<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'HOS Document')</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #333;
        }
        
        .header {
            border-bottom: 2px solid #2563eb;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .header-title {
            font-size: 18pt;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 5px;
        }
        
        .header-subtitle {
            font-size: 10pt;
            color: #64748b;
        }
        
        .company-info {
            text-align: right;
            font-size: 9pt;
            color: #64748b;
        }
        
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
            font-size: 8pt;
            color: #94a3b8;
            text-align: center;
        }
        
        .page-number:after {
            content: "Page " counter(page);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        
        th {
            background-color: #f1f5f9;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #cbd5e1;
            font-size: 9pt;
        }
        
        td {
            padding: 6px 8px;
            border: 1px solid #e2e8f0;
            font-size: 9pt;
        }
        
        .info-grid {
            display: table;
            width: 100%;
            margin: 15px 0;
        }
        
        .info-row {
            display: table-row;
        }
        
        .info-label {
            display: table-cell;
            font-weight: bold;
            padding: 5px 10px 5px 0;
            width: 30%;
            color: #475569;
        }
        
        .info-value {
            display: table-cell;
            padding: 5px 0;
            color: #1e293b;
        }
        
        .section-title {
            font-size: 12pt;
            font-weight: bold;
            color: #1e40af;
            margin: 20px 0 10px 0;
            padding-bottom: 5px;
            border-bottom: 1px solid #cbd5e1;
        }
        
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 8pt;
            font-weight: bold;
        }
        
        .badge-success {
            background-color: #dcfce7;
            color: #166534;
        }
        
        .badge-warning {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .badge-danger {
            background-color: #fee2e2;
            color: #991b1b;
        }
        
        .badge-info {
            background-color: #dbeafe;
            color: #1e40af;
        }
        
        .signature-box {
            border: 1px solid #cbd5e1;
            padding: 10px;
            margin: 20px 0;
            min-height: 80px;
            text-align: center;
        }
        
        .signature-image {
            max-width: 300px;
            max-height: 60px;
        }
        
        .certification-text {
            font-size: 8pt;
            color: #64748b;
            font-style: italic;
            margin-top: 10px;
        }
        
        .content {
            padding: 20px 30px;
        }
        
        .alert {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            border-left: 4px solid;
        }
        
        .alert-warning {
            background-color: #fef3c7;
            border-color: #f59e0b;
            color: #92400e;
        }
        
        .alert-danger {
            background-color: #fee2e2;
            border-color: #ef4444;
            color: #991b1b;
        }
    </style>
    @yield('styles')
</head>
<body>
    <div class="header">
        <table style="border: none; margin: 0;">
            <tr>
                <td style="border: none; width: 60%;">
                    <div class="header-title">@yield('document-title')</div>
                    <div class="header-subtitle">@yield('document-subtitle')</div>
                </td>
                <td style="border: none; width: 40%; text-align: right;">
                    <div class="company-info">
                        @yield('company-info')
                    </div>
                </td>
            </tr>
        </table>
    </div>
    
    <div class="content">
        @yield('content')
    </div>
    
    <div class="footer">
        <div>Generated on {{ now()->format('F d, Y \a\t H:i') }} | FMCSA Compliant Document</div>
        <div class="page-number"></div>
    </div>
</body>
</html>
