@extends('../themes/' . $activeTheme)
@section('title', 'Migration Reports')

@php
$breadcrumbLinks = [
    ['label' => 'App', 'url' => route('admin.dashboard')],
    ['label' => 'Reports', 'url' => route('admin.reports.index')],
    ['label' => 'Migration Reports', 'active' => true],
];
@endphp

@section('subcontent')
    <!-- Professional Breadcrumbs -->
    <div class="mb-6">
        <x-base.breadcrumb :links="$breadcrumbLinks" />
    </div>

    <livewire:admin.driver.migration-reports />
@endsection
