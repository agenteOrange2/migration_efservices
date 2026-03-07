@extends('../themes/' . $activeTheme)
@section('title', 'Drug Test Details')
@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('carrier.dashboard')],
        ['label' => 'Testing Drugs Management', 'url' => route('carrier.drivers.testings.index')],
        ['label' => 'Test Details', 'active' => true],
    ];
@endphp
@section('subcontent')
    <div>
        <!-- Flash Messages -->
        @if (session()->has('success'))
            <div class="alert alert-success flex items-center mb-5">
                <x-base.lucide class="w-6 h-6 mr-2" icon="check-circle" />
                {{ session('success') }}
            </div>
        @endif

        <!-- Header Section -->
        <div class="box box--stacked p-8 mb-8">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                        <x-base.lucide class="w-8 h-8 text-primary" icon="Activity" />
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-slate-800 mb-2">Drug & Alcohol Test</h1>
                        <div class="flex items-center gap-3">
                            <p class="text-slate-600">Test ID: #{{ $testing->id }}</p>
                            @php
                                $statuses = \App\Models\Admin\Driver\DriverTesting::getStatuses();
                                $statusDisplay = $statuses[$testing->status] ?? ucfirst($testing->status);
                            @endphp
                            @if ($testing->status == 'approved')
                                <x-base.badge variant="success" class="gap-1.5">
                                    <span class="w-2 h-2 bg-success rounded-full"></span>
                                    {{ $statusDisplay }}
                                </x-base.badge>
                            @elseif ($testing->status == 'rejected')
                                <x-base.badge variant="danger" class="gap-1.5">
                                    <span class="w-2 h-2 bg-danger rounded-full"></span>
                                    {{ $statusDisplay }}
                                </x-base.badge>
                            @elseif ($testing->status == 'pending')
                                <x-base.badge variant="warning" class="gap-1.5">
                                    <span class="w-2 h-2 bg-warning rounded-full"></span>
                                    {{ $statusDisplay }}
                                </x-base.badge>
                            @else
                                <x-base.badge variant="secondary" class="gap-1.5">
                                    <span class="w-2 h-2 bg-slate-400 rounded-full"></span>
                                    {{ $statusDisplay }}
                                </x-base.badge>
                            @endif
                            
                            @if (in_array($testing->test_result, ['passed', 'negative']))
                                <x-base.badge variant="success" class="gap-1.5">
                                    <span class="w-2 h-2 bg-success rounded-full"></span>
                                    {{ ucfirst($testing->test_result) }}
                                </x-base.badge>
                            @elseif (in_array($testing->test_result, ['failed', 'positive']))
                                <x-base.badge variant="danger" class="gap-1.5">
                                    <span class="w-2 h-2 bg-danger rounded-full"></span>
                                    {{ ucfirst($testing->test_result) }}
                                </x-base.badge>
                            @else
                                <x-base.badge variant="warning" class="gap-1.5">
                                    <span class="w-2 h-2 bg-warning rounded-full"></span>
                                    {{ ucfirst($testing->test_result) }}
                                </x-base.badge>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row gap-3">
                    <x-base.button as="a" href="{{ route('carrier.drivers.testings.download_pdf', ['testing' => $testing->id]) }}" target="_blank" variant="outline-primary" class="gap-2">
                        <x-base.lucide class="w-4 h-4" icon="Download" />
                        Download PDF
                    </x-base.button>
                    <form action="{{ route('carrier.drivers.testings.regenerate_pdf', ['testing' => $testing->id]) }}" method="POST" class="inline">
                        @csrf
                        <x-base.button type="submit" variant="outline-warning" class="gap-2">
                            <x-base.lucide class="w-4 h-4" icon="RefreshCw" />
                            Regenerate
                        </x-base.button>
                    </form>
                    <x-base.button as="a" href="{{ route('carrier.drivers.testings.edit', ['testing' => $testing->id]) }}" variant="primary" class="gap-2">
                        <x-base.lucide class="w-4 h-4" icon="Edit" />
                        Edit
                    </x-base.button>
                    <x-base.button as="a" href="{{ route('carrier.drivers.testings.index') }}" variant="secondary" class="gap-2">
                        <x-base.lucide class="w-4 h-4" icon="ArrowLeft" />
                        Back
                    </x-base.button>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
            <!-- Main Content (2/3) -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Test Details Card -->
                <div class="box box--stacked flex flex-col p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <x-base.lucide class="w-5 h-5 text-primary" icon="ClipboardCheck" />
                        <h2 class="text-lg font-semibold text-slate-800">Test Details</h2>
                    </div>
                    <div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Left Column -->
                            <div class="space-y-3">
                                <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                                    <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Test Date</label>
                                    <p class="text-sm font-semibold text-slate-800">
                                        {{ $testing->test_date ? $testing->test_date->format('m/d/Y') : 'Not specified' }}
                                    </p>
                                </div>
                                
                                <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                                    <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Test Type</label>
                                    <p class="text-sm font-semibold text-slate-800">{{ $testing->test_type ?: 'Not specified' }}</p>
                                </div>
                                
                                <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                                    <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Location</label>
                                    <p class="text-sm font-semibold text-slate-800">{{ $testing->location ?: 'Not specified' }}</p>
                                </div>
                                
                                <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                                    <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Administered By</label>
                                    <p class="text-sm font-semibold text-slate-800">{{ $testing->administered_by ?: 'Not specified' }}</p>
                                </div>
                            </div>

                            <!-- Right Column -->
                            <div class="space-y-3">
                                <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                                    <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Requested By</label>
                                    <p class="text-sm font-semibold text-slate-800">{{ $testing->requester_name ?: 'Not specified' }}</p>
                                </div>
                                
                                <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                                    <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Scheduled Time</label>
                                    <p class="text-sm font-semibold text-slate-800">
                                        {{ $testing->scheduled_time ? $testing->scheduled_time->format('m/d/Y h:i A') : 'Not scheduled' }}
                                    </p>
                                </div>
                                
                                <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                                    <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Next Test Due</label>
                                    <p class="text-sm font-semibold text-slate-800">
                                        {{ $testing->next_test_due ? $testing->next_test_due->format('m/d/Y') : 'Not specified' }}
                                    </p>
                                </div>
                                
                                <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                                    <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Bill To</label>
                                    <p class="text-sm font-semibold text-slate-800">
                                        {{ $testing->bill_to ?: ($testing->carrier ? $testing->carrier->name : 'Not specified') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Test Categories -->
                        <div class="mt-6 pt-6 border-t border-slate-200">
                            <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-3 block">Test Categories</label>
                            <div class="flex flex-wrap gap-2">
                                @if ($testing->is_random_test)
                                    <x-base.badge variant="primary" class="gap-1.5">
                                        <x-base.lucide class="w-3 h-3" icon="Shuffle" />
                                        Random
                                    </x-base.badge>
                                @endif
                                @if ($testing->is_post_accident_test)
                                    <x-base.badge variant="warning" class="gap-1.5">
                                        <x-base.lucide class="w-3 h-3" icon="AlertTriangle" />
                                        Post Accident
                                    </x-base.badge>
                                @endif
                                @if ($testing->is_reasonable_suspicion_test)
                                    <x-base.badge variant="danger" class="gap-1.5">
                                        <x-base.lucide class="w-3 h-3" icon="AlertCircle" />
                                        Reasonable Suspicion
                                    </x-base.badge>
                                @endif
                                @if ($testing->is_pre_employment_test)
                                    <x-base.badge variant="success" class="gap-1.5">
                                        <x-base.lucide class="w-3 h-3" icon="UserPlus" />
                                        Pre-Employment
                                    </x-base.badge>
                                @endif
                                @if ($testing->is_follow_up_test)
                                    <x-base.badge variant="primary" class="gap-1.5">
                                        <x-base.lucide class="w-3 h-3" icon="Repeat" />
                                        Follow-Up
                                    </x-base.badge>
                                @endif
                                @if ($testing->is_return_to_duty_test)
                                    <x-base.badge variant="primary" class="gap-1.5">
                                        <x-base.lucide class="w-3 h-3" icon="ArrowLeftCircle" />
                                        Return-To-Duty
                                    </x-base.badge>
                                @endif
                                @if ($testing->is_other_reason_test)
                                    <x-base.badge variant="secondary" class="gap-1.5">
                                        <x-base.lucide class="w-3 h-3" icon="MoreHorizontal" />
                                        Other
                                    </x-base.badge>
                                @endif
                                @if (!$testing->is_random_test && !$testing->is_post_accident_test && 
                                     !$testing->is_reasonable_suspicion_test && !$testing->is_pre_employment_test &&
                                     !$testing->is_follow_up_test && !$testing->is_return_to_duty_test && 
                                     !$testing->is_other_reason_test)
                                    <span class="text-sm text-slate-500">None specified</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notes Section Card -->
                <div class="box box--stacked flex flex-col p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <x-base.lucide class="w-5 h-5 text-primary" icon="StickyNote" />
                        <h2 class="text-lg font-semibold text-slate-800">Notes & Additional Information</h2>
                    </div>
                    <div>
                        <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100 min-h-[100px]">
                            <p class="text-sm text-slate-700 leading-relaxed whitespace-pre-wrap">{{ $testing->notes ?: 'No notes available for this test.' }}</p>
                        </div>
                        
                        <!-- Metadata Footer -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4 pt-4 border-t border-slate-200">
                            <div class="bg-slate-50/50 rounded-lg p-3 border border-slate-100">
                                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Created</label>
                                <p class="text-xs text-slate-700">
                                    {{ $testing->created_at ? $testing->created_at->format('m/d/Y h:i A') : 'Unknown' }}
                                </p>
                                <p class="text-xs text-slate-500 mt-1">by {{ $testing->createdBy ? $testing->createdBy->name : 'N/A' }}</p>
                            </div>
                            <div class="bg-slate-50/50 rounded-lg p-3 border border-slate-100">
                                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Updated</label>
                                <p class="text-xs text-slate-700">
                                    {{ $testing->updated_at ? $testing->updated_at->format('m/d/Y h:i A') : 'Unknown' }}
                                </p>
                                <p class="text-xs text-slate-500 mt-1">by {{ $testing->updatedBy ? $testing->updatedBy->name : 'N/A' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PDF Preview Card -->
                <div class="box box--stacked flex flex-col p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <x-base.lucide class="w-5 h-5 text-primary" icon="FileText" />
                        <h2 class="text-lg font-semibold text-slate-800">PDF Preview</h2>
                    </div>
                    <div>
                        @if ($testing->hasMedia('drug_test_pdf'))
                            @php
                                $pdfUrl = $testing->getFirstMediaUrl('drug_test_pdf');
                                $pdfMedia = $testing->getFirstMedia('drug_test_pdf');
                            @endphp
                            
                            <!-- PDF Viewer with Embedded Iframe -->
                            <div class="w-full border border-gray-200 rounded-lg overflow-hidden bg-gray-50">
                                <div class="pdf-viewer-container" style="height: 700px;">
                                    <iframe 
                                        src="{{ $pdfUrl }}#toolbar=1&navpanes=1&scrollbar=1&view=FitH" 
                                        class="w-full h-full border-0" 
                                        title="PDF Preview"
                                        style="background: white;">
                                    </iframe>
                                </div>
                            </div>
                            
                            <!-- Fallback Options -->
                            <div class="mt-4 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 bg-slate-50/50 p-4 rounded-lg border border-slate-100">
                                <div class="flex items-center gap-2 text-sm text-slate-700">
                                    <x-base.lucide class="w-4 h-4 text-primary" icon="Info" />
                                    <span>File Size: {{ human_filesize($pdfMedia->size) }}</span>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <x-base.button 
                                        as="a" 
                                        href="{{ $pdfUrl }}" 
                                        target="_blank"
                                        variant="primary" 
                                        size="sm"
                                        class="gap-2">
                                        <x-base.lucide class="w-3.5 h-3.5" icon="ExternalLink" />
                                        Open in New Tab
                                    </x-base.button>
                                    <x-base.button 
                                        as="a" 
                                        href="{{ $pdfUrl }}" 
                                        download
                                        variant="success" 
                                        size="sm"
                                        class="gap-2">
                                        <x-base.lucide class="w-3.5 h-3.5" icon="Download" />
                                        Download
                                    </x-base.button>
                                </div>
                            </div>
                        @else
                            <!-- No PDF Available -->
                            <div class="text-center py-12 bg-slate-50/50 rounded-lg border-2 border-dashed border-slate-200">
                                <x-base.lucide class="w-16 h-16 mx-auto text-slate-400 mb-4" icon="FileQuestion" />
                                <p class="text-lg font-medium text-slate-700 mb-2">No PDF Report Available</p>
                                <p class="text-sm text-slate-500 mb-6">No PDF report has been generated for this test yet.</p>
                                <x-base.button 
                                    as="a" 
                                    href="{{ route('carrier.drivers.testings.regenerate-pdf', $testing->id) }}"
                                    variant="primary" 
                                    class="gap-2">
                                    <x-base.lucide class="w-4 h-4" icon="RefreshCw" />
                                    Generate PDF Report
                                </x-base.button>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Test History Card -->
                @if ($testing->userDriverDetail && $relatedHistory->count() > 0)
                    <div class="box box--stacked flex flex-col p-6">
                        <div class="flex items-center gap-3 mb-6">
                            <x-base.lucide class="w-5 h-5 text-primary" icon="History" />
                            <h2 class="text-lg font-semibold text-slate-800">Test History</h2>
                        </div>
                        <div class="space-y-3">
                            @foreach ($relatedHistory as $test)
                                <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100 hover:border-primary/30 transition-colors">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-2">
                                                <p class="text-sm font-semibold text-slate-800">
                                                    {{ $test->test_date ? $test->test_date->format('m/d/Y') : 'No date' }}
                                                </p>
                                                @if ($test->test_result)
                                                    @if (in_array($test->test_result, ['passed', 'negative']))
                                                        <x-base.badge variant="success" class="text-xs">
                                                            {{ ucfirst($test->test_result) }}
                                                        </x-base.badge>
                                                    @elseif (in_array($test->test_result, ['failed', 'positive']))
                                                        <x-base.badge variant="danger" class="text-xs">
                                                            {{ ucfirst($test->test_result) }}
                                                        </x-base.badge>
                                                    @else
                                                        <x-base.badge variant="warning" class="text-xs">
                                                            {{ ucfirst($test->test_result) }}
                                                        </x-base.badge>
                                                    @endif
                                                @endif
                                            </div>
                                            <div class="space-y-1 text-xs text-slate-600">
                                                @if ($test->test_type)
                                                    <p><span class="font-medium">Type:</span> {{ $test->test_type }}</p>
                                                @endif
                                                @if ($test->location)
                                                    <p><span class="font-medium">Location:</span> {{ $test->location }}</p>
                                                @endif
                                            </div>
                                        </div>
                                        <x-base.button 
                                            as="a" 
                                            href="{{ route('carrier.drivers.testings.show', $test->id) }}"
                                            variant="outline-secondary" 
                                            size="sm"
                                            class="flex-shrink-0">
                                            <x-base.lucide class="w-3 h-3" icon="Eye" />
                                        </x-base.button>
                                    </div>
                                </div>
                            @endforeach
                            
                            @if ($testing->userDriverDetail)
                                <x-base.button 
                                    as="a" 
                                    href="{{ route('carrier.drivers.testings.driver-history', $testing->userDriverDetail->id) }}"
                                    variant="outline-primary" 
                                    class="w-full justify-center gap-2 mt-2">
                                    <x-base.lucide class="w-4 h-4" icon="List" />
                                    View All Tests
                                </x-base.button>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <!-- Sidebar (1/3) -->
            <div class="space-y-6">
                <!-- Carrier Information Card -->
                <div class="box box--stacked flex flex-col p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <x-base.lucide class="w-5 h-5 text-primary" icon="Building2" />
                        <h2 class="text-lg font-semibold text-slate-800">Carrier Information</h2>
                    </div>
                    @if ($testing->carrier)
                        <div class="space-y-4">
                            <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Carrier Name</label>
                                <p class="text-sm font-semibold text-slate-800">{{ $testing->carrier->name ?? 'N/A' }}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                                    <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">DOT Number</label>
                                    <p class="text-sm font-semibold text-slate-800">{{ $testing->carrier->dot_number ?: 'N/A' }}</p>
                                </div>
                                <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                                    <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">MC Number</label>
                                    <p class="text-sm font-semibold text-slate-800">{{ $testing->carrier->mc_number ? 'MC-' . $testing->carrier->mc_number : 'N/A' }}</p>
                                </div>
                            </div>
                            @if ($testing->carrier->email || $testing->carrier->phone || $testing->carrier->address)
                                <div class="space-y-3 pt-3 border-t border-slate-200">
                                    @if ($testing->carrier->email)
                                        <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                                            <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Email</label>
                                            <p class="text-sm font-semibold text-slate-800 break-all">{{ $testing->carrier->email }}</p>
                                        </div>
                                    @endif
                                    @if ($testing->carrier->phone)
                                        <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                                            <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Phone</label>
                                            <p class="text-sm font-semibold text-slate-800">{{ $testing->carrier->phone }}</p>
                                        </div>
                                    @endif
                                    @if ($testing->carrier->address)
                                        <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                                            <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Address</label>
                                            <p class="text-sm font-semibold text-slate-800">{{ $testing->carrier->address }}</p>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="text-center py-8">
                            <x-base.lucide class="w-12 h-12 mx-auto text-slate-400 mb-3" icon="AlertTriangle" />
                            <p class="text-sm text-slate-600">No carrier information available</p>
                        </div>
                    @endif
                </div>

                <!-- Driver Information Card -->
                <div class="box box--stacked flex flex-col p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <x-base.lucide class="w-5 h-5 text-primary" icon="User" />
                        <h2 class="text-lg font-semibold text-slate-800">Driver Information</h2>
                    </div>
                    @if ($testing->userDriverDetail && $testing->userDriverDetail->user)
                        @php
                            $driver = $testing->userDriverDetail;
                            $user = $driver->user;
                            $fullName = trim(($user->name ?? '') . ' ' . ($driver->middle_name ?? '') . ' ' . ($driver->last_name ?? ''));
                        @endphp
                        
                        <div class="space-y-4">
                            <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Full Name</label>
                                <p class="text-sm font-semibold text-slate-800">{{ $fullName ?: 'N/A' }}</p>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-3">
                                <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                                    <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Email</label>
                                    <p class="text-sm font-semibold text-slate-800 break-all">{{ $user->email ?: 'N/A' }}</p>
                                </div>
                                <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                                    <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Phone</label>
                                    <p class="text-sm font-semibold text-slate-800">{{ $driver->phone ?: 'N/A' }}</p>
                                </div>
                            </div>
                            
                            
                            @if ($driver->licenses && $driver->licenses->count() > 0)
                                <div class="pt-4 border-t border-slate-200">
                                    <div class="flex items-center gap-2 mb-3">
                                        <x-base.lucide class="w-4 h-4 text-slate-500" icon="FileText" />
                                        <span class="text-sm font-medium text-slate-700">License Details</span>
                                    </div>
                                    <div class="space-y-3">
                                        @foreach ($driver->licenses->take(3) as $license)
                                            <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                                                <div class="flex items-center justify-between mb-2">
                                                    <label class="text-xs font-medium text-slate-500 uppercase tracking-wide">{{ $license->license_type ?? 'License' }}</label>
                                                    @if ($license->status == 'active')
                                                        <x-base.badge variant="success" class="text-xs">Active</x-base.badge>
                                                    @else
                                                        <x-base.badge variant="secondary" class="text-xs">{{ ucfirst($license->status ?? 'Unknown') }}</x-base.badge>
                                                    @endif
                                                </div>
                                                <div class="space-y-1 text-xs text-slate-600">
                                                    @if ($license->license_number)
                                                        <p><span class="font-medium">Number:</span> <span class="font-mono">{{ $license->license_number }}</span></p>
                                                    @endif
                                                    @if ($license->state)
                                                        <p><span class="font-medium">State:</span> {{ $license->state }}</p>
                                                    @endif
                                                    @if ($license->expiration_date)
                                                        <p><span class="font-medium">Expires:</span> {{ \Carbon\Carbon::parse($license->expiration_date)->format('m/d/Y') }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                        @if ($driver->licenses->count() > 3)
                                            <p class="text-xs text-slate-500 text-center">+ {{ $driver->licenses->count() - 3 }} more license(s)</p>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="text-center py-8">
                            <x-base.lucide class="w-12 h-12 mx-auto text-slate-400 mb-3" icon="AlertTriangle" />
                            <p class="text-sm text-slate-600">No driver information available</p>
                        </div>
                    @endif
                </div>

                <!-- Upload Test Results Card -->
                <div class="box box--stacked flex flex-col p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <x-base.lucide class="w-5 h-5 text-primary" icon="Upload" />
                        <h2 class="text-lg font-semibold text-slate-800">Upload Test Results</h2>
                    </div>
                    
                    <form action="{{ route('carrier.drivers.testings.upload-results', $testing->id) }}" method="POST" enctype="multipart/form-data" id="upload-results-form">
                        @csrf
                        <div class="space-y-4">
                            <!-- Drag and Drop Area -->
                            <div class="border-2 border-dashed border-slate-300 rounded-lg p-6 text-center hover:border-primary transition-colors cursor-pointer" id="drop-zone">
                                <input type="file" name="results[]" id="results-input" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="hidden">
                                <x-base.lucide class="w-12 h-12 mx-auto text-slate-400 mb-3" icon="UploadCloud" />
                                <p class="text-sm font-medium text-slate-700 mb-1">Click to upload or drag and drop</p>
                                <p class="text-xs text-slate-500">PDF, JPG, PNG, DOC, DOCX (Max 10MB each)</p>
                            </div>
                            
                            <!-- Selected Files Preview -->
                            <div id="files-preview" class="space-y-2 hidden"></div>
                            
                            <!-- Upload Button -->
                            <x-base.button type="submit" variant="primary" class="w-full justify-center gap-2" id="upload-btn" disabled>
                                <x-base.lucide class="w-4 h-4" icon="Upload" />
                                Upload Files
                            </x-base.button>
                        </div>
                    </form>
                </div>

                <!-- Uploaded Files Card -->
                <div class="box box--stacked flex flex-col p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <x-base.lucide class="w-5 h-5 text-primary" icon="Paperclip" />
                        <h2 class="text-lg font-semibold text-slate-800">Uploaded Files</h2>
                    </div>
                    @php
                        $allMedia = collect();
                        $collections = ['test_results', 'test_certificates', 'test_authorization', 'document_attachments'];
                        foreach ($collections as $collection) {
                            $allMedia = $allMedia->merge($testing->getMedia($collection));
                        }
                    @endphp
                    
                    @if ($allMedia->count() > 0)
                        <div class="space-y-2">
                            @foreach ($allMedia as $media)
                                <div class="bg-slate-50/50 rounded-lg p-3 border border-slate-100 hover:bg-slate-100/50 transition-colors">
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="flex items-center gap-2 flex-1 min-w-0">
                                            @if (str_contains($media->mime_type, 'pdf'))
                                                <x-base.lucide class="w-4 h-4 text-danger flex-shrink-0" icon="FileText" />
                                            @elseif (str_contains($media->mime_type, 'image'))
                                                <x-base.lucide class="w-4 h-4 text-success flex-shrink-0" icon="Image" />
                                            @else
                                                <x-base.lucide class="w-4 h-4 text-primary flex-shrink-0" icon="File" />
                                            @endif
                                            <div class="flex-1 min-w-0">
                                                <p class="text-xs font-medium text-slate-800 truncate" title="{{ $media->name }}">{{ $media->name }}</p>
                                                <p class="text-xs text-slate-500">{{ human_filesize($media->size) }}</p>
                                            </div>
                                        </div>
                                        <div class="flex gap-1 flex-shrink-0">
                                            <x-base.button 
                                                as="a" 
                                                href="{{ $media->getUrl() }}" 
                                                target="_blank"
                                                variant="outline-secondary" 
                                                size="sm"
                                                class="p-1">
                                                <x-base.lucide class="w-3 h-3" icon="Eye" />
                                            </x-base.button>
                                            <x-base.button 
                                                as="a" 
                                                href="{{ $media->getUrl() }}"
                                                download
                                                variant="outline-secondary" 
                                                size="sm"
                                                class="p-1">
                                                <x-base.lucide class="w-3 h-3" icon="Download" />
                                            </x-base.button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-6">
                            <x-base.lucide class="w-8 h-8 mx-auto text-slate-400 mb-2" icon="FileQuestion" />
                            <p class="text-xs text-slate-600">No files uploaded</p>
                        </div>
                    @endif
                </div>

                <!-- Quick Actions Card -->
                <div class="box box--stacked flex flex-col p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <x-base.lucide class="w-5 h-5 text-primary" icon="Zap" />
                        <h2 class="text-lg font-semibold text-slate-800">Quick Actions</h2>
                    </div>
                    <div class="space-y-3">
                        @if ($testing->userDriverDetail)
                            <x-base.button 
                                as="a" 
                                href="{{ route('carrier.drivers.testings.driver_history', $testing->userDriverDetail->id) }}" 
                                variant="outline-primary" 
                                class="w-full justify-start gap-3">
                                <x-base.lucide class="w-4 h-4" icon="History" />
                                View Test History
                            </x-base.button>
                        @endif
                        
                        <x-base.button 
                            type="button"
                            variant="outline-danger" 
                            class="w-full justify-start gap-3"
                            data-tw-toggle="modal"
                            data-tw-target="#deleteTestingModal"
                            onclick="confirmDeleteTestingWithDetails({{ $testing->id }}, {
                                driverName: '{{ $testing->userDriverDetail->user->name ?? 'N/A' }} {{ $testing->userDriverDetail->user->last_name ?? '' }}',
                                testType: '{{ $testing->test_type }}',
                                testDate: '{{ $testing->test_date ? \Carbon\Carbon::parse($testing->test_date)->format('M d, Y') : '' }}',
                                redirectUrl: '{{ route('carrier.drivers.testings.index') }}'
                            })">
                            <x-base.lucide class="w-4 h-4" icon="Trash2" />
                            Delete Test Record
                        </x-base.button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Testing Confirmation Modal -->
    <x-base.dialog id="deleteTestingModal">
        <x-base.dialog.panel>
            <div class="p-5 text-center">
                <x-base.lucide class="w-16 h-16 mx-auto mt-3 text-danger" icon="XCircle" />
                <div class="mt-5 text-3xl">Are you sure?</div>
                <div class="mt-2 text-slate-500 modal-message">
                    Are you sure you want to delete this testing record? This action cannot be undone.
                </div>
            </div>
            <div class="px-5 pb-8 text-center">
                <form id="deleteTestingForm" method="POST">
                    @csrf
                    @method('DELETE')
                    <x-base.button
                        class="mr-1 w-24"
                        data-tw-dismiss="modal"
                        type="button"
                        variant="outline-secondary">
                        Cancel
                    </x-base.button>
                    <x-base.button class="w-24" type="submit" variant="danger">
                        Delete
                    </x-base.button>
                </form>
            </div>
        </x-base.dialog.panel>
    </x-base.dialog>
@endsection

@push('scripts')
    <script src="{{ asset('js/carrier-driver-testing-delete.js') }}"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const dropZone = document.getElementById('drop-zone');
        const fileInput = document.getElementById('results-input');
        const filesPreview = document.getElementById('files-preview');
        const uploadBtn = document.getElementById('upload-btn');
        const form = document.getElementById('upload-results-form');
        let selectedFiles = [];

        // Click to select files
        dropZone.addEventListener('click', () => fileInput.click());

        // Handle file selection
        fileInput.addEventListener('change', function(e) {
            handleFiles(Array.from(e.target.files));
        });

        // Drag and drop events
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('border-primary', 'bg-primary/5');
        });

        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('border-primary', 'bg-primary/5');
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('border-primary', 'bg-primary/5');
            handleFiles(Array.from(e.dataTransfer.files));
        });

        function handleFiles(files) {
            const validFiles = files.filter(file => {
                const validTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                const maxSize = 10 * 1024 * 1024; // 10MB
                
                if (!validTypes.includes(file.type)) {
                    alert(`File "${file.name}" has invalid type. Only PDF, JPG, PNG, DOC, DOCX allowed.`);
                    return false;
                }
                
                if (file.size > maxSize) {
                    alert(`File "${file.name}" is too large. Maximum size is 10MB.`);
                    return false;
                }
                
                return true;
            });

            if (validFiles.length > 0) {
                selectedFiles = validFiles;
                displayFiles();
                uploadBtn.disabled = false;
            }
        }

        function displayFiles() {
            filesPreview.innerHTML = '';
            filesPreview.classList.remove('hidden');
            
            selectedFiles.forEach((file, index) => {
                const fileItem = document.createElement('div');
                fileItem.className = 'flex items-center justify-between p-3 bg-slate-50 rounded-lg border border-slate-200';
                fileItem.innerHTML = `
                    <div class="flex items-center gap-2 flex-1 min-w-0">
                        <svg class="w-5 h-5 text-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-800 truncate">${file.name}</p>
                            <p class="text-xs text-slate-500">${formatFileSize(file.size)}</p>
                        </div>
                    </div>
                    <button type="button" class="text-danger hover:text-danger/80 p-1" onclick="removeFile(${index})">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                `;
                filesPreview.appendChild(fileItem);
            });
        }

        window.removeFile = function(index) {
            selectedFiles.splice(index, 1);
            if (selectedFiles.length === 0) {
                filesPreview.classList.add('hidden');
                uploadBtn.disabled = true;
                fileInput.value = '';
            } else {
                displayFiles();
            }
        };

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }

        form.addEventListener('submit', function(e) {
            if (selectedFiles.length === 0) {
                e.preventDefault();
                alert('Please select at least one file to upload.');
                return;
            }
            
            const dataTransfer = new DataTransfer();
            selectedFiles.forEach(file => dataTransfer.items.add(file));
            fileInput.files = dataTransfer.files;
            
            uploadBtn.disabled = true;
            uploadBtn.innerHTML = `
                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Uploading...
            `;
        });
    });
    </script>
@endpush
