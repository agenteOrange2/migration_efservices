@extends('../themes/' . $activeTheme)
@section('title', 'Archived Drivers')

@php
$breadcrumbLinks = [
    ['label' => 'App', 'url' => route('admin.dashboard')],
    ['label' => 'Drivers', 'url' => route('admin.drivers.index')],
    ['label' => 'Archived', 'active' => true],
];
@endphp

@section('subcontent')
    <livewire:admin.driver.archived-drivers-list />
@endsection
