@extends('../themes/' . $activeTheme)
@section('title', 'Archived Driver Details')

@php
$breadcrumbLinks = [
    ['label' => 'App', 'url' => route('admin.dashboard')],
    ['label' => 'Drivers', 'url' => route('admin.drivers.index')],
    ['label' => 'Archived', 'url' => route('admin.drivers.archived.index')],
    ['label' => 'Details', 'active' => true],
];
@endphp

@section('subcontent')
    <livewire:admin.driver.archived-driver-detail :archive-id="$archiveId" />
@endsection
