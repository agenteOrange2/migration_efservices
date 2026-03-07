@extends('../themes/' . $activeTheme)
@section('title', 'Archived Drivers')

@php
$breadcrumbLinks = [
    ['label' => 'Dashboard', 'url' => route('carrier.dashboard')],
    ['label' => 'Drivers', 'url' => route('carrier.drivers.index')],
    ['label' => 'Archived', 'active' => true],
];
@endphp

@section('subcontent')
    <livewire:admin.driver.archived-drivers-list />
@endsection
