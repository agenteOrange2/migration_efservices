@extends('../themes/' . $activeTheme)
@section('title', 'Notifications')

@php
$breadcrumbLinks = [
    ['label' => 'Dashboard', 'url' => route('carrier.dashboard')],
    ['label' => 'Notifications', 'active' => true],
];
@endphp

@section('subcontent')
    @livewire('carrier.notification-center')
@endsection
