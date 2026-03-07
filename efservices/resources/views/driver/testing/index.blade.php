@extends('../themes/' . $activeTheme)
@section('title', 'My Tests - EF Services')

@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('driver.dashboard')],
        ['label' => 'Testing', 'active' => true],
    ];
@endphp

@section('subcontent')

<div class="mb-6">
    <x-base.breadcrumb :links="$breadcrumbLinks" />
</div>

 <!-- Professional Header -->
 <div class="box box--stacked p-8 mb-8">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
            <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-4">
                <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                    <x-base.lucide class="w-8 h-8 text-primary" icon="TestTube" />
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-slate-800 mb-2">Drug & Alcohol Tests</h1>
                    <p class="text-slate-600">View and manage your drug and alcohol tests</p>
                </div>
            </div>
        </div>    
</div>
    
@if($testings->count() > 0)
<!-- Desktop Table View -->
<div class="box box--stacked overflow-hidden hidden md:block" role="region" aria-label="Test records table">
    <div class="overflow-x-auto">
        <table class="w-full text-left" role="table">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th scope="col" class="px-6 py-4 text-xs font-semibold text-slate-600 uppercase">Test Date</th>
                    <th scope="col" class="px-6 py-4 text-xs font-semibold text-slate-600 uppercase">Test Type & Location</th>
                    <th scope="col" class="px-6 py-4 text-xs font-semibold text-slate-600 uppercase">Result & Status</th>
                    <th scope="col" class="px-6 py-4 text-xs font-semibold text-slate-600 uppercase">Administered By</th>
                    <th scope="col" class="px-6 py-4 text-xs font-semibold text-slate-600 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($testings as $test)
                <tr class="hover:bg-slate-50" tabindex="0">
                    <td class="px-6 py-4">
                        <p class="font-medium text-slate-800">{{ $test->test_date ? $test->test_date->format('M d, Y') : 'N/A' }}</p>
                        @if($test->scheduled_time)
                            <p class="text-xs text-slate-500">{{ $test->scheduled_time->format('h:i A') }}</p>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <p class="text-slate-800 font-medium">{{ $test->test_type ?? 'Drug Test' }}</p>
                        @if($test->location)
                            <p class="text-xs text-slate-500">{{ $test->location }}</p>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <div class="space-y-1">
                            @if(in_array(strtolower($test->test_result ?? ''), ['negative', 'passed']))
                                <x-base.badge variant="success">{{ ucfirst($test->test_result) }}</x-base.badge>
                            @elseif(in_array(strtolower($test->test_result ?? ''), ['positive', 'failed']))
                                <x-base.badge variant="danger">{{ ucfirst($test->test_result) }}</x-base.badge>
                            @else
                                <x-base.badge variant="warning">{{ $test->test_result ?? 'Pending' }}</x-base.badge>
                            @endif
                            
                            @if($test->status == 'Pending Review')
                                <x-base.badge variant="warning" class="text-xs">{{ $test->status }}</x-base.badge>
                            @elseif($test->status == 'Completed')
                                <x-base.badge variant="success" class="text-xs">{{ $test->status }}</x-base.badge>
                            @else
                                <x-base.badge variant="secondary" class="text-xs">{{ $test->status }}</x-base.badge>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <p class="text-slate-600 text-sm">{{ $test->administered_by ?? 'N/A' }}</p>
                    </td>
                    <td class="px-6 py-4">
                        <a href="{{ route('driver.testing.show', $test->id) }}" class="text-primary hover:underline text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 rounded" aria-label="View details for test on {{ $test->test_date ? $test->test_date->format('M d, Y') : 'N/A' }}">
                            View Details
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Mobile Card View -->
<div class="md:hidden space-y-4" role="list" aria-label="Test records">
    @foreach($testings as $test)
    <div class="box box--stacked p-4" role="listitem">
        <div class="flex items-start justify-between mb-3">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-warning/10 rounded-lg">
                    <x-base.lucide class="w-5 h-5 text-warning" icon="TestTube" aria-hidden="true" />
                </div>
                <div>
                    <p class="font-semibold text-slate-800">{{ $test->test_date ? $test->test_date->format('M d, Y') : 'N/A' }}</p>
                    <p class="text-sm text-slate-500">{{ $test->test_type ?? 'Drug Test' }}</p>
                </div>
            </div>
            <div class="flex flex-col gap-1 items-end">
                @if(in_array(strtolower($test->test_result ?? ''), ['negative', 'passed']))
                    <x-base.badge variant="success">{{ ucfirst($test->test_result) }}</x-base.badge>
                @elseif(in_array(strtolower($test->test_result ?? ''), ['positive', 'failed']))
                    <x-base.badge variant="danger">{{ ucfirst($test->test_result) }}</x-base.badge>
                @else
                    <x-base.badge variant="warning">{{ $test->test_result ?? 'Pending' }}</x-base.badge>
                @endif
                
                @if($test->status == 'Pending Review')
                    <x-base.badge variant="warning" class="text-xs">{{ $test->status }}</x-base.badge>
                @elseif($test->status == 'Completed')
                    <x-base.badge variant="success" class="text-xs">{{ $test->status }}</x-base.badge>
                @else
                    <x-base.badge variant="secondary" class="text-xs">{{ $test->status }}</x-base.badge>
                @endif
            </div>
        </div>
        <div class="text-sm text-slate-600 mb-3 space-y-1">
            @if($test->location)
                <div><span class="text-slate-500">Location:</span> {{ $test->location }}</div>
            @endif
            @if($test->administered_by)
                <div><span class="text-slate-500">Administered By:</span> {{ $test->administered_by }}</div>
            @endif
            @if($test->scheduled_time)
                <div><span class="text-slate-500">Scheduled:</span> {{ $test->scheduled_time->format('M d, Y h:i A') }}</div>
            @endif
        </div>
        <a href="{{ route('driver.testing.show', $test->id) }}" class="block w-full text-center py-2 text-sm text-primary bg-primary/5 hover:bg-primary/10 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2" aria-label="View details for test on {{ $test->test_date ? $test->test_date->format('M d, Y') : 'N/A' }}">
            View Details
        </a>
    </div>
    @endforeach
</div>
@else
<div class="box box--stacked p-8 text-center">
    <x-base.lucide class="w-16 h-16 text-slate-300 mx-auto mb-4" icon="TestTube" />
    <h3 class="text-lg font-semibold text-slate-700 mb-2">No Test Records</h3>
    <p class="text-slate-500">You don't have any drug or alcohol test records yet.</p>
</div>
@endif

@endsection
