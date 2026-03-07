@extends('../themes/' . $activeTheme)
@section('title', 'Archived Driver Details')

@php
$breadcrumbLinks = [
    ['label' => 'Dashboard', 'url' => route('carrier.dashboard')],
    ['label' => 'Drivers', 'url' => route('carrier.drivers.index')],
    ['label' => 'Inactive Drivers', 'url' => route('carrier.drivers.inactive.index')],
    ['label' => 'Details', 'active' => true],
];
@endphp

@section('subcontent')
    <livewire:admin.driver.archived-driver-detail :archive-id="$archiveId" />
@endsection
