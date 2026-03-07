@extends('../themes/' . $activeTheme)
@section('title', 'Notifications')

@php
$breadcrumbLinks = [
    ['label' => 'Home', 'url' => route('carrier.dashboard')],
    ['label' => 'Notifications', 'active' => true],
];
@endphp

@section('subcontent')
    <livewire:driver.notification-center />
@endsection
