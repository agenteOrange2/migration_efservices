<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Carriers Documents Report</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: sans-serif; font-size: 10px; color: #334155; background: #fff; }
    .header { background: #1e293b; color: #fff; padding: 16px 24px; margin-bottom: 16px; }
    .header h1 { font-size: 18px; font-weight: 700; }
    .header p { font-size: 10px; color: #94a3b8; margin-top: 4px; }
    .stats { display: flex; gap: 12px; margin: 0 24px 16px; }
    .stat-box { flex: 1; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 14px; }
    .stat-box .val { font-size: 22px; font-weight: 700; }
    .stat-box .lbl { font-size: 9px; color: #64748b; margin-top: 2px; }
    .stat-box.green .val { color: #059669; }
    .stat-box.amber .val { color: #d97706; }
    .stat-box.red .val { color: #dc2626; }
    table { width: calc(100% - 48px); margin: 0 24px; border-collapse: collapse; }
    thead tr { background: #1e293b; color: #fff; }
    thead th { padding: 8px 10px; text-align: left; font-size: 9px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }
    tbody tr { border-bottom: 1px solid #e2e8f0; }
    tbody tr:nth-child(even) { background: #f8fafc; }
    tbody td { padding: 7px 10px; font-size: 9px; }
    .badge { display: inline-block; padding: 2px 7px; border-radius: 20px; font-size: 8px; font-weight: 600; }
    .badge-green { background: #dcfce7; color: #166534; }
    .badge-amber { background: #fef3c7; color: #92400e; }
    .badge-red { background: #fee2e2; color: #991b1b; }
    .progress-bar { background: #e2e8f0; border-radius: 4px; height: 6px; width: 80px; display: inline-block; vertical-align: middle; overflow: hidden; }
    .progress-fill { height: 100%; border-radius: 4px; }
    .footer { margin: 16px 24px 0; font-size: 8px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 8px; }
</style>
</head>
<body>

<div class="header">
    <h1>Carriers Documents Report</h1>
    <p>Generated: {{ $generated_at }}
        @if(!empty($filters['search'])) &nbsp;·&nbsp; Search: "{{ $filters['search'] }}" @endif
        @if(!empty($filters['status'])) &nbsp;·&nbsp; Status: {{ ucfirst($filters['status']) }} @endif
    </p>
</div>

<div class="stats">
    <div class="stat-box">
        <div class="val">{{ $stats['total'] }}</div>
        <div class="lbl">Total Carriers</div>
    </div>
    <div class="stat-box green">
        <div class="val">{{ $stats['complete'] }}</div>
        <div class="lbl">Complete</div>
    </div>
    <div class="stat-box amber">
        <div class="val">{{ $stats['pending'] }}</div>
        <div class="lbl">In Progress</div>
    </div>
    <div class="stat-box red">
        <div class="val">{{ $stats['none'] }}</div>
        <div class="lbl">No Documents</div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Carrier</th>
            <th>MC Number</th>
            <th>DOT Number</th>
            <th>Plan</th>
            <th>Progress</th>
            <th>Docs</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse($carriers as $i => $c)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td style="font-weight:600;">{{ $c['name'] }}</td>
            <td>{{ $c['mc_number'] ?? '—' }}</td>
            <td>{{ $c['dot_number'] ?? '—' }}</td>
            <td>{{ $c['membership_name'] ?? '—' }}</td>
            <td>
                <div class="progress-bar">
                    <div class="progress-fill" style="width:{{ $c['completion_percentage'] }}%;background:{{ $c['completion_percentage'] >= 100 ? '#059669' : ($c['completion_percentage'] >= 50 ? '#d97706' : '#dc2626') }};"></div>
                </div>
                <span style="margin-left:5px;">{{ $c['completion_percentage'] }}%</span>
            </td>
            <td>{{ $c['approved'] }}/{{ $c['total'] }}</td>
            <td>
                @if($c['document_status'] === 'active')
                    <span class="badge badge-green">Complete</span>
                @elseif($c['document_status'] === 'pending')
                    <span class="badge badge-amber">In Progress</span>
                @else
                    <span class="badge badge-red">None</span>
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="8" style="text-align:center;padding:20px;color:#94a3b8;">No carriers found</td></tr>
        @endforelse
    </tbody>
</table>

<div class="footer">
    EFServices · Carriers Documents Report · {{ $generated_at }} · Total: {{ $stats['total'] }} carriers
</div>

</body>
</html>
