@extends('../themes/' . $activeTheme)
@section('title', 'Driver Documents - ' . $driver->full_name)

@php
$breadcrumbLinks = [
['label' => 'App', 'url' => route('admin.dashboard')],
['label' => 'Drivers', 'url' => route('admin.drivers.index')],
['label' => $driver->full_name, 'url' => route('admin.drivers.show', $driver->id)],
['label' => 'Documents', 'active' => true],
];
@endphp

@section('subcontent')

<!-- Professional Breadcrumbs -->
<div class="mb-6">
    <x-base.breadcrumb :links="$breadcrumbLinks" />
</div>

<!-- Success/Error Messages -->
@if(session('success'))
<div class="mb-6 bg-success/10 border border-success/20 text-success px-6 py-4 rounded-lg flex items-center gap-3">
    <x-base.lucide class="w-5 h-5" icon="CheckCircle" />
    <span class="font-medium">{{ session('success') }}</span>
</div>
@endif

@if(session('error'))
<div class="mb-6 bg-danger/10 border border-danger/20 text-danger px-6 py-4 rounded-lg flex items-center gap-3">
    <x-base.lucide class="w-5 h-5" icon="XCircle" />
    <span class="font-medium">{{ session('error') }}</span>
</div>
@endif

<!-- Professional Header with Driver Info -->
<div class="box box--stacked p-3 md:p-6 mb-8">
    <div class="flex flex-col lg:flex-row items-center lg:items-center justify-between gap-6">
        <div class="flex flex-col lg:flex-row items-center gap-4">
            <div class="p-3">
                @if ($driver->getFirstMediaUrl('profile_photo_driver'))
                <img
                    class="w-20 h-20 rounded-full object-cover border-2 border-white shadow"
                    src="{{ $driver->getFirstMediaUrl('profile_photo_driver') }}"
                    alt="{{ $driver->full_name }}" />
                @else
                <x-base.lucide class="w-8 h-8 text-primary" icon="User" />
                @endif
            </div>
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-slate-800 mb-2">{{ $driver->full_name }}</h2>
                <div class="flex items-center gap-3">
                    <x-base.lucide class="w-5 h-5 text-primary" icon="mail" />
                    <p class="text-slate-500">{{ $driver->user->email ?? 'No email' }}</p>
                </div>
                <div class="flex flex-col sm:flex-row gap-4 justify-center md:justify-start mt-2">
                    <div class="flex items-center text-slate-500 text-sm">
                        <i data-lucide="Building" class="w-4 h-4 mr-1"></i>
                        {{ $driver->carrier->name ?? 'No carrier' }}
                    </div>
                    <div class="flex items-center text-slate-500 text-sm">
                        @php $effectiveStatus = $driver->getEffectiveStatus(); @endphp
                        @switch($effectiveStatus)
                            @case('active')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full bg-success/10 text-success">
                                    <span class="w-1.5 h-1.5 rounded-full bg-success"></span>
                                    Active
                                </span>
                                @break
                            @case('pending_review')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full bg-warning/10 text-warning">
                                    <span class="w-1.5 h-1.5 rounded-full bg-warning"></span>
                                    Pending Review
                                </span>
                                @break
                            @case('draft')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full bg-slate-200/80 text-slate-600">
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                    Draft
                                </span>
                                @break
                            @case('rejected')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full bg-danger/10 text-danger">
                                    <span class="w-1.5 h-1.5 rounded-full bg-danger"></span>
                                    Rejected
                                </span>
                                @break
                            @default
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full bg-red-100 text-red-600">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-600"></span>
                                    Inactive
                                </span>
                        @endswitch
                    </div>
                </div>
            </div>
        </div>
        <div class="flex flex-col sm:flex-row gap-3">
            <x-base.button as="a" href="{{ route('admin.drivers.approved.show', $driver->id) }}" variant="secondary" class="gap-2">
                <x-base.lucide class="w-4 h-4" icon="ArrowLeft" />
                Back to Driver Profile
            </x-base.button>
            @if($documentStats['total_documents'] > 0)
            <form action="{{ route('admin.drivers.documents.download-all', $driver->id) }}" method="POST" class="inline">
                @csrf
                <x-base.button type="submit" variant="primary" class="gap-2">
                    <x-base.lucide class="w-4 h-4" icon="Download" />
                    Download All Documents
                </x-base.button>
            </form>
            @endif
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="box box--stacked p-6 hover:shadow-lg transition-all duration-200">
        <div class="flex items-center">
            <div class="p-3 bg-primary/10 rounded-xl">
                <x-base.lucide class="w-8 h-8 text-primary" icon="FileText" />
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-slate-500">Total Documents</p>
                <p class="text-3xl font-bold text-slate-800">{{ $documentStats['total_documents'] }}</p>
            </div>
        </div>
    </div>

    <div class="box box--stacked p-6 hover:shadow-lg transition-all duration-200">
        <div class="flex items-center">
            <div class="p-3 bg-success/10 rounded-xl">
                <x-base.lucide class="w-8 h-8 text-success" icon="Folder" />
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-slate-500">Categories</p>
                <p class="text-3xl font-bold text-slate-800">{{ $documentStats['categories_with_documents'] }}</p>
            </div>
        </div>
    </div>

    <div class="box box--stacked p-6 hover:shadow-lg transition-all duration-200">
        <div class="flex items-center">
            <div class="p-3 bg-warning/10 rounded-xl">
                <x-base.lucide class="w-8 h-8 text-warning" icon="Clock" />
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-slate-500">Recent (30 days)</p>
                <p class="text-3xl font-bold text-slate-800">{{ $documentStats['recent_documents'] }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Documents Content -->
<div class="box box--stacked p-4 md:p-3">
    <div class="flex items-center gap-3 mb-6">
        <x-base.lucide class="w-5 h-5 text-primary" icon="FileText" />
        <h2 class="text-lg font-semibold text-slate-800">All Documents</h2>
    </div>

    @if($documentStats['total_documents'] == 0)
        <!-- No Documents State -->
        <div class="text-center py-12">
            <x-base.lucide class="mx-auto w-16 h-16 text-slate-400 mb-4" icon="FileText" />
            <h3 class="text-lg font-medium text-slate-900 mb-2">No documents</h3>
            <p class="text-slate-500">No documents have been uploaded for this driver yet.</p>
        </div>
    @else
        <!-- Documents by Category -->
        @php
        $categoryLabels = [
            'license' => 'Licenses',
            'medical' => 'Medical Documents',
            'training_schools' => 'Training Schools',
            'courses' => 'Courses',
            'accidents' => 'Accidents',
            'traffic_violations' => 'Traffic Violations',
            'testing' => 'Testing',
            'inspections' => 'Inspections',
            'driving_records' => 'Driving Records',
            'criminal_records' => 'Criminal Records',
            'medical_records' => 'Medical Records',
            'clearing_house_records' => 'Clearing House Records',
            'vehicle_verifications' => 'Vehicle Verifications',
            'records' => 'General Records',
            'application_forms' => 'Application Forms',
            'individual_application_forms' => 'Individual Application Forms',
            'employment_verification' => 'Employment Verification',
            'w9_documents' => 'W-9 Tax Form',
            'complete_application' => 'Complete Application',
            'lease_agreements' => 'Lease Agreements',
            'dot_policy_documents' => 'DOT Drug & Alcohol Policy',
            'other' => 'Other Documents'
        ];

        $categoryIcons = [
            'license' => 'CreditCard',
            'medical' => 'Heart',
            'training_schools' => 'GraduationCap',
            'courses' => 'BookOpen',
            'accidents' => 'AlertTriangle',
            'traffic_violations' => 'AlertOctagon',
            'testing' => 'TestTube',
            'inspections' => 'Search',
            'driving_records' => 'FileText',
            'criminal_records' => 'Shield',
            'medical_records' => 'Stethoscope',
            'clearing_house_records' => 'Database',
            'vehicle_verifications' => 'Truck',
            'records' => 'Folder',
            'application_forms' => 'FileCheck',
            'individual_application_forms' => 'File',
            'employment_verification' => 'Briefcase',
            'w9_documents' => 'FileText',
            'complete_application' => 'FileCheck',
            'lease_agreements' => 'FileSignature',
            'dot_policy_documents' => 'ShieldCheck',
            'other' => 'FolderOpen'
        ];
        @endphp

        @foreach($documentsByCategory as $categoryKey => $documents)
            @if(count($documents) > 0)
                <div class="mb-8">
                    <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-200">
                        <h4 class="text-lg font-semibold text-slate-900 flex items-center gap-3">
                            <div class="p-2 bg-primary/10 rounded-lg">
                                <x-base.lucide class="w-5 h-5 text-primary" :icon="$categoryIcons[$categoryKey] ?? 'FileText'" />
                            </div>
                            {{ $categoryLabels[$categoryKey] }}
                            <x-base.badge variant="primary" class="ml-2">
                                {{ count($documents) }}
                            </x-base.badge>
                        </h4>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($documents as $document)
                            <div class="bg-slate-50 border border-slate-200 rounded-lg p-4 hover:shadow-md transition-all duration-200 hover:border-primary/50">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex items-center gap-3 flex-1 min-w-0">
                                        <div class="p-2 bg-white rounded-lg border border-slate-200">
                                            <x-base.lucide class="w-6 h-6 text-primary" icon="FileText" />
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-slate-900 truncate" title="{{ $document['name'] }}">
                                                {{ $document['name'] }}
                                            </p>
                                            <p class="text-xs text-slate-500">{{ $document['size'] }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-1 mb-4">
                                    <div class="flex items-center text-xs text-slate-600">
                                        <x-base.lucide class="w-3 h-3 mr-1 text-slate-400" icon="Calendar" />
                                        <span class="font-medium">Date:</span>
                                        <span class="ml-1">{{ $document['date'] }}</span>
                                    </div>
                                    @if(isset($document['related_info']))
                                        <div class="flex items-center text-xs text-slate-600">
                                            <x-base.lucide class="w-3 h-3 mr-1 text-slate-400" icon="Info" />
                                            <span class="font-medium">Info:</span>
                                            <span class="ml-1 truncate" title="{{ $document['related_info'] }}">{{ $document['related_info'] }}</span>
                                        </div>
                                    @endif
                                </div>

                                <div class="flex gap-2">
                                    <a href="{{ $document['url'] }}" target="_blank"
                                       class="flex-1 inline-flex justify-center items-center px-3 py-2 border border-slate-300 shadow-sm text-xs leading-4 font-medium rounded-md text-slate-700 bg-white hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                                        <x-base.lucide class="w-4 h-4 mr-1" icon="Eye" />
                                        View
                                    </a>
                                    <a href="{{ $document['url'] }}" download
                                       class="flex-1 inline-flex justify-center items-center px-3 py-2 border border-transparent shadow-sm text-xs leading-4 font-medium rounded-md text-white bg-primary hover:bg-secondary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                                        <x-base.lucide class="w-4 h-4 mr-1" icon="Download" />
                                        Download
                                    </a>
                                    <button type="button"
                                            onclick="confirmDelete({{ $document['id'] }}, '{{ addslashes($document['name']) }}')"
                                            class="inline-flex justify-center items-center px-3 py-2 border border-danger shadow-sm text-xs leading-4 font-medium rounded-md text-danger bg-white hover:bg-danger/10 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-danger transition-colors">
                                        <x-base.lucide class="w-4 h-4" icon="Trash2" />
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
    @endif
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-slate-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-danger/10 sm:mx-0 sm:h-10 sm:w-10">
                        <x-base.lucide class="w-6 h-6 text-danger" icon="AlertTriangle" />
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-slate-900" id="modal-title">
                            Delete Document
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-slate-500">
                                Are you sure you want to delete "<span id="documentName" class="font-semibold"></span>"? This action cannot be undone.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-slate-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                <form id="deleteForm" method="POST" action="">
                    @csrf
                    @method('DELETE')
                    <x-base.button type="submit" variant="danger">
                        <x-base.lucide class="w-4 h-4 mr-2" icon="Trash2" />
                        Delete Document
                    </x-base.button>
                </form>
                <x-base.button type="button" variant="secondary" onclick="closeDeleteModal()">
                    Cancel
                </x-base.button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function confirmDelete(documentId, documentName) {
    document.getElementById('documentName').textContent = documentName;
    document.getElementById('deleteForm').action = '{{ route("admin.drivers.documents.destroy", ["driver" => $driver->id, "document" => "__DOCUMENT_ID__"]) }}'.replace('__DOCUMENT_ID__', documentId);
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}

// Close modal on escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeDeleteModal();
    }
});
</script>
@endpush

@endsection
