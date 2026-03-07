@extends('../themes/' . $activeTheme)
@section('title', 'Licencenses Driver')
@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Licencenses Driver', 'active' => true],
    ];
@endphp

@section('subcontent')
<div class="container-fluid">
    <!-- Mensajes flash -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800"> Driver Licenses</h1>
        <div class="d-flex gap-2">
            <x-base.button as="a" href="{{ route('admin.driver-licenses.create') }}" variant="primary">
                <i class="fas fa-plus"></i> Add License
            </x-base.button>
            <x-base.button as="a" href="{{ route('admin.driver-licenses.docs.all') }}" variant="info">
                <i class="fas fa-file-alt"></i> View Documents
            </x-base.button>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.driver-licenses.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="{{ request('search') }}" placeholder="Search by license number...">
                </div>
                <div class="col-md-3">
                    <label for="driver_id" class="form-label">Driver</label>
                    <select class="form-select" id="driver_id" name="driver_id">
                        <option value="">All drivers</option>
                        @foreach($drivers as $driver)
                            <option value="{{ $driver->id }}" {{ request('driver_id') == $driver->id ? 'selected' : '' }}>
                                {{ $driver->first_name }} {{ $driver->last_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="date_from" class="form-label">From</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" 
                           value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label for="date_to" class="form-label">To</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" 
                           value="{{ request('date_to') }}">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <x-base.button type="submit" variant="primary" class="me-2">
                        <i class="fas fa-search"></i> Filter
                    </x-base.button>
                    <x-base.button as="a" href="{{ route('admin.driver-licenses.index') }}" variant="secondary">
                        <i class="fas fa-times"></i> Clear
                    </x-base.button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de licencias -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'created_at', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc']) }}" 
                                   class="text-white text-decoration-none">
                                    Creation Date
                                    @if(request('sort') === 'created_at')
                                        <i class="fas fa-sort-{{ request('direction') === 'asc' ? 'up' : 'down' }}"></i>
                                    @else
                                        <i class="fas fa-sort"></i>
                                    @endif
                                </a>
                            </th>
                            <th>
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'driver_id', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc']) }}" 
                                   class="text-white text-decoration-none">
                                    Driver
                                    @if(request('sort') === 'driver_id')
                                        <i class="fas fa-sort-{{ request('direction') === 'asc' ? 'up' : 'down' }}"></i>
                                    @else
                                        <i class="fas fa-sort"></i>
                                    @endif
                                </a>
                            </th>
                            <th>License Number</th>
                            <th>Class</th>
                            <th>Status</th>
                            <th>
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'expiration_date', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc']) }}" 
                                   class="text-white text-decoration-none">
                                    Expiration Date
                                    @if(request('sort') === 'expiration_date')
                                        <i class="fas fa-sort-{{ request('direction') === 'asc' ? 'up' : 'down' }}"></i>
                                    @else
                                        <i class="fas fa-sort"></i>
                                    @endif
                                </a>
                            </th>
                                <th>Expiration Status</th>
                            <th>Documents</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($driverLicenses as $license)
                            <tr>
                                <td>{{ $license->created_at->format('m/d/Y') }}</td>
                                <td>
                                    <strong>{{ $license->driver->first_name }} {{ $license->driver->last_name }}</strong><br>
                                    <small class="text-muted">{{ $license->driver->carrier->name ?? 'No carrier' }}</small>
                                </td>
                                <td>{{ $license->license_number }}</td>
                                <td>
                                    <span class="badge bg-info">{{ $license->license_class }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $license->license_status === 'active' ? 'success' : ($license->license_status === 'suspended' ? 'warning' : 'danger') }}">
                                        {{ ucfirst($license->license_status) }}
                                    </span>
                                </td>
                                <td>{{ $license->expiration_date ? $license->expiration_date->format('m/d/Y') : 'No specified' }}</td>
                                <td>
                                    @php
                                        $expirationStatus = $license->expiration_date ? 
                                            (now()->diffInDays($license->expiration_date, false) > 90 ? 'success' : 
                                            (now()->diffInDays($license->expiration_date, false) > 30 ? 'warning' : 'danger')) : 'secondary';
                                        $expirationText = $license->expiration_date ? 
                                            (now()->diffInDays($license->expiration_date, false) > 90 ? 'Valid' : 
                                            (now()->diffInDays($license->expiration_date, false) > 30 ? 'Expiring' : 'Expired')) : 'No specified';
                                    @endphp
                                    <span class="badge bg-{{ $expirationStatus }}">
                                        <i class="fas fa-circle"></i> {{ $expirationText }}
                                    </span>
                                    @if($license->expiration_date && now()->diffInDays($license->expiration_date, false) <= 30)
                                        <br><small class="text-muted">
                                            {{ now()->diffInDays($license->expiration_date, false) > 0 ? 
                                               'Expires in ' . now()->diffInDays($license->expiration_date, false) . ' days' : 
                                               'Expired ' . abs(now()->diffInDays($license->expiration_date, false)) . ' days ago' }}
                                        </small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-primary">{{ $license->getMedia('documents')->count() }}</span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.driver-licenses.docs.show', $license) }}" 
                                           class="btn btn-sm btn-outline-info" title="View documents">
                                            <i class="fas fa-file-alt"></i>
                                        </a>
                                        <a href="{{ route('admin.driver-licenses.edit', $license) }}" 
                                           class="btn btn-sm btn-outline-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.driver-licenses.destroy', $license) }}" 
                                              method="POST" class="d-inline" 
                                              onsubmit="return confirm('Are you sure you want to delete this license?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fas fa-id-card fa-3x mb-3"></i>
                                        <p>No driver licenses found.</p>
                                        <x-base.button as="a" href="{{ route('admin.driver-licenses.create') }}" variant="primary">
                                            <i class="fas fa-plus"></i> Add First License
                                        </x-base.button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($driverLicenses->hasPages())
                <div class="d-flex justify-content-center mt-4">
                    {{ $driverLicenses->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Auto-submit the filter form when the selects change
    document.getElementById('driver_id').addEventListener('change', function() {
        this.form.submit();
    });
</script>
@endpush