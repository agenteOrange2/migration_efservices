<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Vehicle Service Due Status Report</title>
    <style>
        @page {
            size: letter portrait;
            margin: 0.5in 0.6in 0.4in 0.6in;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12px;
            margin: 0;
            padding: 0;
            color: #000;
            background: white;
        }

        /* Header bar */
        .header-bar {
            background: #1e40af;
            color: #fff;
            text-align: center;
            padding: 5px 20px;
            font-size: 14px;
            font-weight: bold;
            font-style: italic;
            letter-spacing: 0.5px;
            margin-bottom: 15px;
        }

        /* Main title */
        .main-title {
            text-align: center;
            font-weight: bold;
            font-size: 12px;
            text-transform: uppercase;
            line-height: 1.4;
            margin-bottom: 12px;
        }

        /* Vehicle Identification Table */
        .vehicle-table {
            width: 100%;
            border-collapse: collapse;
            border: 2px solid #000;
            margin-bottom: 10px;
        }

        .vehicle-table .vh {
            background: #1e40af;
            color: #fff;
            text-align: center;
            font-weight: bold;
            font-size: 11px;
            padding: 3px;
            text-transform: uppercase;
            border-bottom: 2px solid #000;
        }

        .vehicle-table td {
            width: 50%;
            padding: 2px 6px 1px 6px;
            border-bottom: 1px solid #000;
            vertical-align: top;
            height: 32px;
        }

        .vehicle-table td.vr {
            border-right: 1px solid #000;
        }

        .vehicle-table tr:last-child td {
            border-bottom: none;
        }

        .field-label {
            font-size: 8px;
            display: block;
            margin-bottom: 1px;
            color: #000;
        }

        .field-value {
            font-size: 12px;
            padding: 0;
        }

        /* Operations Table */
        .ops-table {
            width: 100%;
            border-collapse: collapse;
            border: 2px solid #000;
        }

        .ops-table thead th {
            border: 2px solid #000;
            padding: 3px 4px;
            font-size: 10px;
            font-weight: bold;
            text-align: center;
            vertical-align: bottom;
            background: #fff;
            line-height: 1.3;
            width: 16.66%;
        }

        .ops-table thead th:last-child {
            width: 16.70%;
        }

        .ops-table tbody td {
            padding: 2px 4px;
            font-size: 10px;
            height: 20px;
            border-left: 2px solid #000;
            border-bottom: 1px solid #ccc;
            vertical-align: middle;
            text-align: center;
            width: 16.66%;
        }

        .ops-table tbody td:first-child {
            border-left: none;
        }

        .ops-table tbody td:last-child {
            width: 16.70%;
        }

        .ops-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Note */
        .note {
            border: 2px solid #000;
            border-top: none;
            padding: 6px 8px;
            font-size: 9px;
            font-weight: bold;
            line-height: 1.3;
        }

        /* Footer */
        .footer-table {
            width: 100%;
            margin-top: 15px;
        }

        .footer-table td {
            font-size: 9px;
            padding: 0;
        }

        .footer-left {
            font-weight: bold;
            font-size: 10px;
            text-align: left;
        }

        .footer-right {
            font-style: italic;
            text-align: right;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header-bar">Vehicle Service Due Status Report</div>

    <!-- Main Title -->
    <div class="main-title">
        Vehicle Service Due Status Report<br>
        Under 49 C.F.R. 396.3
    </div>

    <!-- Vehicle Identification -->
    <table class="vehicle-table">
        <tr>
            <td colspan="2" class="vh">Vehicle Identification</td>
        </tr>
        <tr>
            <td class="vr">
                <span class="field-label">Make</span>
                <div class="field-value">{{ $vehicle->make ?? '' }}</div>
            </td>
            <td>
                <span class="field-label">Serial Number</span>
                <div class="field-value">{{ $vehicle->vin ?? '' }}</div>
            </td>
        </tr>
        <tr>
            <td class="vr">
                <span class="field-label">Year</span>
                <div class="field-value">{{ $vehicle->year ?? '' }}</div>
            </td>
            <td>
                <span class="field-label">Tire Size</span>
                <div class="field-value">{{ $vehicle->tire_size ?? '' }}</div>
            </td>
        </tr>
        <tr>
            <td colspan="2" class="vr">
                <span class="field-label">Company Number/Other ID</span>
                <div class="field-value">{{ $vehicle->company_unit_number ?? '' }}</div>
            </td>            
        </tr>
    </table>

    <!-- Operations Table -->
    <table class="ops-table">
        <thead>
            <tr>
                <th>Date of<br>Inspection</th>
                <th>Type of<br>Inspection</th>
                <th>Mileage at Time<br>of Inspection</th>
                <th>Date Next<br>Inspection Due</th>
                <th>Mileage Type of<br>Inspection Due</th>
                <th>Inspection Due</th>
            </tr>
        </thead>
        <tbody>
            @foreach($maintenances as $m)
            @php
                $isOilChange = $m->service_tasks && stripos($m->service_tasks, 'Oil Change') !== false;
                $nextOilMileage = ($isOilChange && $m->odometer) ? number_format($m->odometer + 5000) : '';
            @endphp
            <tr>
                <td>{{ $m->service_date ? $m->service_date->format('m/d/Y') : '' }}</td>
                <td>{{ $m->service_tasks ?? '' }}</td>
                <td>{{ $m->odometer ? number_format($m->odometer) : '' }}</td>
                <td>{{ $m->next_service_date ? $m->next_service_date->format('m/d/Y') : '' }}</td>
                <td>{{ $nextOilMileage }}</td>
                <td>{{ $m->next_service_date ? $m->next_service_date->format('m/d/Y') : '' }}</td>
            </tr>
            @endforeach
            @php
                $totalRows = count($maintenances);
                $emptyRows = max(0, 20 - $totalRows);
                if ($totalRows > 20) { $emptyRows = 2; }
            @endphp
            @for($i = 0; $i < $emptyRows; $i++)
            <tr>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
            </tr>
            @endfor
        </tbody>
    </table>

    <!-- Note -->
    <div class="note">
        Note: This form is provided as a suggested format for performing and documenting a vehicle's inspection
        schedule. A motor carrier may use any format for tracking a vehicle's inspections which complies with 396.3.
    </div>

    <!-- Footer -->
    <table class="footer-table">
        <tr>
            <td class="footer-left">396-8</td>
            <td class="footer-right">A Texas Motor Carrier's Guide to Highway Safety</td>
        </tr>
    </table>
</body>
</html>
