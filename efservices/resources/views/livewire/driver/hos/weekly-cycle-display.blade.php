<div class="card shadow-sm">
    <div class="card-header">
        <h5 class="mb-0">Weekly Cycle Status</h5>
    </div>
    <div class="card-body">
        @if(!empty($cycleStatus))
            <div class="mb-3">
                <div class="d-flex justify-content-between mb-1">
                    <span>{{ $cycleStatus['cycle_type'] === '60_7' ? '60h/7 days' : '70h/8 days' }} Cycle</span>
                    <span>{{ number_format($cycleStatus['hours_used'] ?? 0, 1) }}h / {{ $cycleStatus['cycle_type'] === '60_7' ? '60' : '70' }}h</span>
                </div>
                <div class="progress" style="height: 20px;">
                    <div class="progress-bar bg-{{ $this->progressColor }}" 
                         role="progressbar" 
                         style="width: {{ $cycleStatus['percentage_used'] ?? 0 }}%"
                         aria-valuenow="{{ $cycleStatus['percentage_used'] ?? 0 }}" 
                         aria-valuemin="0" 
                         aria-valuemax="100">
                        {{ number_format($cycleStatus['percentage_used'] ?? 0, 0) }}%
                    </div>
                </div>
            </div>

            <div class="row text-center">
                <div class="col-6">
                    <h4 class="mb-0 text-{{ $this->progressColor }}">{{ number_format($cycleStatus['remaining_hours'] ?? 0, 1) }}h</h4>
                    <small class="text-muted">Remaining</small>
                </div>
                <div class="col-6">
                    <h4 class="mb-0">{{ number_format($cycleStatus['hours_used'] ?? 0, 1) }}h</h4>
                    <small class="text-muted">Used</small>
                </div>
            </div>

            @if(!empty($dailyBreakdown))
                <hr>
                <h6>Daily Breakdown</h6>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Driving</th>
                                <th>On Duty</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dailyBreakdown as $day)
                                <tr>
                                    <td>{{ $day['date'] }}</td>
                                    <td>{{ number_format($day['driving_hours'] ?? 0, 1) }}h</td>
                                    <td>{{ number_format($day['on_duty_hours'] ?? 0, 1) }}h</td>
                                    <td>{{ number_format($day['total_hours'] ?? 0, 1) }}h</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @else
            <div class="text-center text-muted py-3">
                <i class="bi bi-clock display-4"></i>
                <p class="mt-2">No cycle data available</p>
            </div>
        @endif
    </div>
</div>
