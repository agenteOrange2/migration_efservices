<div class="card shadow-sm">
    <div class="card-header">
        <h5 class="mb-0">Driver Availability</h5>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-4">
                <label for="tripDuration" class="form-label">Filter by Trip Duration (minutes)</label>
                <div class="input-group">
                    <input type="number" class="form-control" id="tripDuration" 
                           wire:model="tripDurationMinutes" min="0" placeholder="e.g., 120">
                    <button class="btn btn-outline-primary" wire:click="filterByDuration">Filter</button>
                </div>
            </div>
        </div>

        @if($drivers->isEmpty())
            <div class="text-center text-muted py-4">
                <i class="bi bi-people display-4"></i>
                <p class="mt-2">No drivers found</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Driver</th>
                            <th>Status</th>
                            <th>Daily Hours</th>
                            <th>Weekly Hours</th>
                            <th>Availability</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($drivers as $driver)
                            <tr>
                                <td>{{ $driver['driver_name'] }}</td>
                                <td>
                                    <span class="badge bg-{{ $driver['current_status'] === 'off_duty' ? 'secondary' : 'primary' }}">
                                        {{ str_replace('_', ' ', ucfirst($driver['current_status'])) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="progress flex-grow-1 me-2" style="height: 8px;">
                                            <div class="progress-bar bg-{{ (12 - $driver['remaining_daily_hours']) / 12 * 100 > 90 ? 'danger' : ((12 - $driver['remaining_daily_hours']) / 12 * 100 > 75 ? 'warning' : 'success') }}" 
                                                 style="width: {{ (12 - $driver['remaining_daily_hours']) / 12 * 100 }}%"></div>
                                        </div>
                                        <small>{{ number_format($driver['remaining_daily_hours'], 1) }}h left</small>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="progress flex-grow-1 me-2" style="height: 8px;">
                                            <div class="progress-bar bg-{{ $driver['weekly_percentage_used'] > 90 ? 'danger' : ($driver['weekly_percentage_used'] > 75 ? 'warning' : 'success') }}" 
                                                 style="width: {{ $driver['weekly_percentage_used'] }}%"></div>
                                        </div>
                                        <small>{{ number_format($driver['remaining_weekly_hours'], 1) }}h left</small>
                                    </div>
                                </td>
                                <td>
                                    @if($driver['has_blocking_penalty'])
                                        <span class="badge bg-danger">Blocked</span>
                                    @elseif($driver['can_complete_trip'] ?? $driver['can_drive'])
                                        <span class="badge bg-success">Available</span>
                                    @else
                                        <span class="badge bg-warning">Limited</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
