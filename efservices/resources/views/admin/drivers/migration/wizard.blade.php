@extends('../themes/' . $activeTheme)
@section('title', 'Driver Migration')

@php
$breadcrumbLinks = [
    ['label' => 'App', 'url' => route('admin.dashboard')],
    ['label' => 'Drivers', 'url' => route('admin.drivers.index')],
    ['label' => 'Migration', 'active' => true],
];
@endphp

@section('subcontent')
    <livewire:admin.driver.driver-migration-wizard :driver-id="$driverId" />
@endsection
