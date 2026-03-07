<div>
    @if(session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h5 class="mb-0">Filters</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label for="driverId" class="form-label">Driver</label>
                    <select wire:model.live="driverId" id="driverId" class="form-select">
                        <option value="">All Drivers</option>
                        @foreach($drivers as $driver)
                            <option value="{{ $driver->id }}">{{ $driver->user->name ?? 'Unknown' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label for="severity" class="form-label">Severity</label>
                    <select wire:model.live="severity" id="severity" class="form-select">
                        <option value="">All</option>
                        <option value="minor">Minor</option>
                        <option value="moderate">Moderate</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label for="dateFrom" class="form-label">From</label>
                    <x-base.litepicker wire:model.live="dateFrom" id="dateFrom" class="form-control" placeholder="Select Date" />
                </div>
                <div class="col-md-2 mb-3">
                    <label for="dateTo" class="form-label">To</label>
                    <x-base.litepicker wire:model.live="dateTo" id="dateTo" class="form-control" placeholder="Select Date" />
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            @if($violations->isEmpty())
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-shield-check display-4"></i>
                    <p class="mt-2">No violations found.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Driver</th>
                                <th>Type</th>
                                <th>Severity</th>
                                <th>FMCSA Reference</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($violations as $violation)
                                <tr>
                                    <td>{{ $violation->violation_date?->format('M d, Y') ?? 'N/A' }}</td>
                                    <td>{{ $violation->driver->user->name ?? 'N/A' }}</td>
                                    <td>{{ $violation->violation_type_name }}</td>
                                    <td>
                                        <span class="badge bg-{{ $violation->severity_color }}">
                                            {{ $violation->severity_name }}
                                        </span>
                                    </td>
                                    <td>{{ $violation->fmcsa_rule_reference ?? 'N/A' }}</td>
                                    <td>
                                        @if($violation->acknowledged)
                                            <span class="badge bg-success">Acknowledged</span>
                                        @else
                                            <span class="badge bg-warning">Pending</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(!$violation->acknowledged)
                                            <button wire:click="acknowledge({{ $violation->id }})" 
                                                    class="btn btn-sm btn-outline-primary">
                                                Acknowledge
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $violations->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
